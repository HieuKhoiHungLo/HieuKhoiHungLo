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

    public function send($to, $subject, $body, $isHtml = true) {
        $this->loadSettings(); // Lazy-load SMTP settings only when actually sending
        $host = $this->settings['smtp_host'];
        
        // If no SMTP host, use basic mail()
        if (empty($host)) {
            return $this->sendBasicMail($to, $subject, $body, $isHtml);
        }

        // Use socket-based SMTP
        return $this->sendSmtp($to, $subject, $body, $isHtml);
    }

    public function enqueue($to, $subject, $body, $isHtml = true) {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO email_queue (recipient, subject, body, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
        return $stmt->execute([$to, $subject, $body]);
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
