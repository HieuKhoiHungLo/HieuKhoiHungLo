<?php
namespace App\Services;

use App\Models\MasterData;

class MailerService {
    protected $masterData;
    protected $settings = [];
    protected $debug = false;
    protected $settingsLoaded = false;

    public function __construct() {
        // Don't load settings eagerly — enqueue() doesn't need them
    }

    protected function loadSettings() {
        if ($this->settingsLoaded) return;
        $this->settingsLoaded = true;
        
        $this->masterData = new MasterData();
        // Batch: single query for all SMTP settings
        $db = \App\Core\Database::getInstance()->getConnection();
        $keys = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_secure', 'email_from_name', 'email_from_address'];
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $db->prepare("SELECT \"key\", value FROM settings WHERE \"key\" IN ($placeholders)");
        $stmt->execute($keys);
        $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        foreach ($keys as $k) {
            $this->settings[$k] = $rows[$k] ?? '';
        }
    }

    public function send($to, $subject, $body, $isHtml = true, $category = 'system') {
        $this->ensureDailyReset();

        // 1. Critical/System emails — Use DEFAULT sender if configured
        if (in_array($category, ['critical', 'system'])) {
            $default = $this->getDefaultSender();
            if ($default) {
                return $this->sendSmtpWithConfig($to, $subject, $body, $isHtml, $default);
            }
            // Fallback to legacy settings table
            $this->loadSettings();
            $host = $this->settings['smtp_host'] ?? '';
            if (empty($host)) {
                return $this->sendBasicMail($to, $subject, $body, $isHtml);
            }
            return $this->sendSmtp($to, $subject, $body, $isHtml);
        }

        // 2. Bulk/Admission/Notification emails — Use ROTATING pool
        $sender = $this->getRotatingSender($category);
        if ($sender) {
            return $this->sendSmtpWithConfig($to, $subject, $body, $isHtml, $sender);
        }

        // 3. Last fallback — Try default sender again or legacy settings
        $default = $this->getDefaultSender();
        if ($default) {
            return $this->sendSmtpWithConfig($to, $subject, $body, $isHtml, $default);
        }

        $this->loadSettings();
        $host = $this->settings['smtp_host'] ?? '';
        if (empty($host)) {
            return $this->sendBasicMail($to, $subject, $body, $isHtml);
        }
        return $this->sendSmtp($to, $subject, $body, $isHtml);
    }

    protected function getDefaultSender() {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM email_senders WHERE is_default = TRUE AND is_active = TRUE LIMIT 1");
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    protected function ensureDailyReset() {
        $db = \App\Core\Database::getInstance()->getConnection();
        // Auto-reset sent_today nếu sang ngày mới (last_sent_at của ngày hôm trước)
        $db->exec("UPDATE email_senders SET sent_today = 0 WHERE DATE(last_sent_at) < CURRENT_DATE");
    }

    protected function getRotatingSender($category = null) {
        $db = \App\Core\Database::getInstance()->getConnection();
        
        // Pick an active sender that hasn't reached daily limit, isn't default, sorted by oldest last_sent_at
        // If category is provided, try matching it first, otherwise take any from 'all' or matching category
        $sql = "
            SELECT * FROM email_senders 
            WHERE is_active = TRUE 
            AND is_default = FALSE
            AND sent_today < daily_limit
        ";
        
        $params = [];
        if ($category && $category !== 'all') {
            if ($category === 'admission_letter') {
                $sql .= " AND (category = 'admission_letter' OR category = 'bulk' OR category = 'all')";
            } elseif ($category === 'bulk') {
                $sql .= " AND (category = 'bulk' OR category = 'admission_letter' OR category = 'all')";
            } else {
                $sql .= " AND (category = ? OR category = 'all')";
                $params[] = $category;
            }
        }
        
        $sql .= " ORDER BY last_sent_at ASC NULLS FIRST LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function sendSmtpWithConfig($to, $subject, $body, $isHtml, $config) {
        $host = $config['smtp_host'];
        $port = (int)$config['smtp_port'];
        $user = $config['smtp_user'];
        $pass = str_replace(' ', '', $config['smtp_pass']);
        $secure = $config['smtp_encryption'];
        $from = $config['email'];
        $fromName = $config['name'] ?: $this->settings['email_from_name'];

        $result = $this->executeSmtpSend($to, $subject, $body, $isHtml, $host, $port, $user, $pass, $secure, $from, $fromName);

        if ($result === true) {
            // Update sender stats
            $db = \App\Core\Database::getInstance()->getConnection();
            $upd = $db->prepare("UPDATE email_senders SET sent_today = sent_today + 1, last_sent_at = NOW() WHERE id = ?");
            $upd->execute([$config['id']]);
        }

        return $result;
    }

    protected function executeSmtpSend($to, $subject, $body, $isHtml, $host, $port, $user, $pass, $secure, $from, $fromName) {
        $log = [];
        try {
            if ($secure === 'ssl') {
                $prefix = 'ssl://';
            } else {
                $prefix = ''; 
            }
            
            $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 30);
            if (!$socket) return "Connection failed: $errstr ($errno)";

            stream_set_timeout($socket, 30);
            $this->getResponse($socket);

            fwrite($socket, "EHLO localhost\r\n");
            $this->getResponse($socket);
            
            if ($secure === 'tls') {
                fwrite($socket, "STARTTLS\r\n");
                $response = $this->getResponse($socket);
                if (strpos($response, '220') === false) { fclose($socket); return "STARTTLS failed"; }
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) { fclose($socket); return "TLS failed"; }
                fwrite($socket, "EHLO localhost\r\n");
                $this->getResponse($socket);
            }

            fwrite($socket, "AUTH LOGIN\r\n");
            $this->getResponse($socket);
            fwrite($socket, base64_encode($user) . "\r\n");
            $this->getResponse($socket);
            fwrite($socket, base64_encode($pass) . "\r\n");
            $response = $this->getResponse($socket);
            if (strpos($response, '235') === false) { fclose($socket); return "Auth failed: $response"; }

            fwrite($socket, "MAIL FROM:<$from>\r\n");
            $this->getResponse($socket);
            fwrite($socket, "RCPT TO:<$to>\r\n");
            $this->getResponse($socket);
            fwrite($socket, "DATA\r\n");
            $this->getResponse($socket);

            $contentType = $isHtml ? "text/html" : "text/plain";
            $message = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
            $message .= "To: $to\r\n";
            $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: $contentType; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($body));
            $message .= "\r\n.\r\n";
            
            fwrite($socket, $message);
            $response = $this->getResponse($socket);
            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            return (strpos($response, '250') !== false) ? true : "Send failed: $response";
        } catch (\Exception $e) {
            return "SMTP Error: " . $e->getMessage();
        }
    }

    public function enqueue($to, $subject, $body, $isHtml = true, $category = 'system') {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO email_queue (recipient, subject, body, status, category, created_at) VALUES (?, ?, ?, 'pending', ?, NOW())");
        return $stmt->execute([$to, $subject, $body, $category]);
    }

    protected function sendBasicMail($to, $subject, $body, $isHtml) {
        $headers = "From: {$this->settings['email_from_name']} <{$this->settings['email_from_address']}>\r\n";
        $headers .= "Reply-To: {$this->settings['email_from_address']}\r\n";
        if ($isHtml) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        
        if (mail($to, $subject, $body, $headers)) {
            return true;
        }
        return "Failed to send email using mail() function.";
    }

    protected function sendSmtp($to, $subject, $body, $isHtml) {
        $host = $this->settings['smtp_host'];
        $port = (int)($this->settings['smtp_port'] ?: 587);
        $user = $this->settings['smtp_user'];
        $pass = str_replace(' ', '', $this->settings['smtp_pass']); // Remove spaces from App Password
        $secure = $this->settings['smtp_secure'];
        $from = $this->settings['email_from_address'];
        $fromName = $this->settings['email_from_name'];

        $log = [];

        try {
            // Determine connection method
            if ($secure === 'ssl') {
                $prefix = 'ssl://';
            } else {
                $prefix = ''; // TLS starts as plain then upgrades
            }
            
            $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 30);
            
            if (!$socket) {
                return "Connection failed: $errstr ($errno)";
            }

            stream_set_timeout($socket, 30);

            // Read greeting
            $greeting = $this->getResponse($socket);
            $log[] = "S: " . $greeting;

            // EHLO
            $log[] = "C: EHLO localhost";
            fwrite($socket, "EHLO localhost\r\n");
            $response = $this->getResponse($socket);
            $log[] = "S: " . $response;
            
            // STARTTLS for TLS
            if ($secure === 'tls') {
                $log[] = "C: STARTTLS";
                fwrite($socket, "STARTTLS\r\n");
                $response = $this->getResponse($socket);
                $log[] = "S: " . $response;
                
                if (strpos($response, '220') === false) {
                    fclose($socket);
                    return "STARTTLS failed: $response";
                }
                
                // Enable crypto
                $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
                if (!$crypto) {
                    fclose($socket);
                    return "TLS encryption failed";
                }
                
                // EHLO again after TLS
                $log[] = "C: EHLO localhost (after TLS)";
                fwrite($socket, "EHLO localhost\r\n");
                $response = $this->getResponse($socket);
                $log[] = "S: " . $response;
            }

            // AUTH LOGIN
            $log[] = "C: AUTH LOGIN";
            fwrite($socket, "AUTH LOGIN\r\n");
            $response = $this->getResponse($socket);
            $log[] = "S: " . $response;
            
            if (strpos($response, '334') === false) {
                fclose($socket);
                return "AUTH LOGIN not supported: $response";
            }

            // Send username (base64)
            $log[] = "C: [username hidden]";
            fwrite($socket, base64_encode($user) . "\r\n");
            $response = $this->getResponse($socket);
            $log[] = "S: " . $response;
            
            if (strpos($response, '334') === false) {
                fclose($socket);
                return "Username rejected: $response";
            }

            // Send password (base64)
            $log[] = "C: [password hidden]";
            fwrite($socket, base64_encode($pass) . "\r\n");
            $response = $this->getResponse($socket);
            $log[] = "S: " . $response;
            
            if (strpos($response, '235') === false) {
                fclose($socket);
                return "Authentication failed. Kiểm tra lại Username và App Password.";
            }

            // MAIL FROM
            $log[] = "C: MAIL FROM:<$from>";
            fwrite($socket, "MAIL FROM:<$from>\r\n");
            $response = $this->getResponse($socket);
            $log[] = "S: " . $response;

            // RCPT TO
            $log[] = "C: RCPT TO:<$to>";
            fwrite($socket, "RCPT TO:<$to>\r\n");
            $response = $this->getResponse($socket);
            $log[] = "S: " . $response;

            // DATA
            $log[] = "C: DATA";
            fwrite($socket, "DATA\r\n");
            $response = $this->getResponse($socket);
            $log[] = "S: " . $response;

            // Build message
            $contentType = $isHtml ? "text/html" : "text/plain";
            $message = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
            $message .= "To: $to\r\n";
            $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: $contentType; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "\r\n";
            $message .= chunk_split(base64_encode($body));
            $message .= "\r\n.\r\n";
            
            fwrite($socket, $message);
            $response = $this->getResponse($socket);
            $log[] = "S: " . $response;

            // QUIT
            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            if (strpos($response, '250') !== false) {
                return true;
            }
            
            error_log("[EmailDebug] Send failed final response. Log: " . implode(" | ", $log));
            return "Send failed: $response";

        } catch (\Exception $e) {
            error_log("[EmailDebug] Exception: " . $e->getMessage());
            return "SMTP Error: " . $e->getMessage();
        }
    }

    protected function getResponse($socket) {
        $response = '';
        while ($line = @fgets($socket, 515)) {
            $response .= $line;
            // Check if this is the last line (4th char is space, not dash)
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return trim($response);
    }
}
