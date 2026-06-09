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
        $today = date('Y-m-d');
        
        // Robust settings-based check using PHP's timezone to avoid PostgreSQL timezone mismatches.
        $stmt = $db->prepare("SELECT value FROM settings WHERE \"key\" = 'last_email_reset_date'");
        $stmt->execute();
        $lastReset = $stmt->fetchColumn();
        
        if ($lastReset !== $today) {
            $db->exec("UPDATE email_senders SET sent_today = 0, locked_until = NULL");
            
            $stmtUpdate = $db->prepare("UPDATE settings SET value = ? WHERE \"key\" = 'last_email_reset_date'");
            $stmtUpdate->execute([$today]);
            if ($stmtUpdate->rowCount() === 0) {
                $db->prepare("INSERT INTO settings (\"key\", value) VALUES ('last_email_reset_date', ?)")->execute([$today]);
            }
        }
    }

    protected function getRotatingSender($category = null) {
        $db = \App\Core\Database::getInstance()->getConnection();
        
        // Pick and lock an active sender that hasn't reached daily limit, isn't default, and isn't locked.
        // We atomically set locked_until = NOW() + 45 seconds to prevent concurrent worker usage.
        $params = [];
        $categorySql = "";
        if ($category && $category !== 'all') {
            if ($category === 'admission_letter') {
                $categorySql = " AND (category = 'admission_letter' OR category = 'bulk' OR category = 'all')";
            } elseif ($category === 'bulk') {
                $categorySql = " AND (category = 'bulk' OR category = 'admission_letter' OR category = 'all')";
            } else {
                $categorySql = " AND (category = ? OR category = 'all')";
                $params[] = $category;
            }
        }
        
        $sql = "
            UPDATE email_senders 
            SET locked_until = NOW() + INTERVAL '45 seconds'
            WHERE id = (
                SELECT id FROM email_senders 
                WHERE is_active = TRUE 
                AND is_default = FALSE
                AND sent_today < daily_limit
                AND (locked_until IS NULL OR locked_until < NOW())
                $categorySql
                ORDER BY last_sent_at ASC NULLS FIRST 
                LIMIT 1 
                FOR UPDATE SKIP LOCKED
            )
            RETURNING *
        ";
        
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

        $db = \App\Core\Database::getInstance()->getConnection();
        if ($result === true) {
            // Update sender stats and release lock
            $upd = $db->prepare("UPDATE email_senders SET sent_today = sent_today + 1, last_sent_at = NOW(), locked_until = NULL WHERE id = ?");
            $upd->execute([$config['id']]);
        } else {
            // Release lock on failure so it can be retried by other processes immediately or later
            // Still update last_sent_at to allow rotation
            $upd = $db->prepare("UPDATE email_senders SET last_sent_at = NOW(), locked_until = NULL WHERE id = ?");
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
                if (strpos($response, '220') === false) { fclose($socket); return "STARTTLS failed: $response"; }
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
            $response = $this->getResponse($socket);
            if (strpos($response, '250') === false) { fclose($socket); return "MAIL FROM failed: $response"; }

            fwrite($socket, "RCPT TO:<$to>\r\n");
            $response = $this->getResponse($socket);
            if (strpos($response, '250') === false) { fclose($socket); return "RCPT TO failed: $response"; }

            fwrite($socket, "DATA\r\n");
            $response = $this->getResponse($socket);
            if (strpos($response, '354') === false) { fclose($socket); return "DATA failed: $response"; }
 
            $contentType = $isHtml ? "text/html" : "text/plain";
            $message = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
            $message .= "To: $to\r\n";
            $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: $contentType; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($body));
            $message .= "\r\n.\r\n";
            
            // Write in chunks to avoid SSL bad-length errors on large bodies
            $writeOk = true;
            $offset = 0;
            $chunkSize = 4096;
            $msgLen = strlen($message);
            while ($offset < $msgLen) {
                $chunk = substr($message, $offset, $chunkSize);
                $written = @fwrite($socket, $chunk);
                if ($written === false || $written === 0) {
                    $writeOk = false;
                    break;
                }
                $offset += $written;
            }

            if (!$writeOk) {
                fclose($socket);
                return "SSL write error";
            }

            $response = $this->getResponse($socket);
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
 
            return (strpos($response, '250') !== false) ? true : "Send failed: $response";
        } catch (\Exception $e) {
            return "SMTP Error: " . $e->getMessage();
        }
    }

    public function enqueue($to, $subject, $body, $isHtml = true, $category = 'system') {
        // Fix common domain typos
        $email = trim(strtolower($to));
        $domainFixes = [
            '@gmai.com' => '@gmail.com',
            '@gamil.com' => '@gmail.com',
            '@gmail.con' => '@gmail.com',
            '@gmal.com'  => '@gmail.com',
            '@yaho.com'  => '@yahoo.com',
        ];
        foreach ($domainFixes as $wrong => $right) {
            if (str_ends_with($email, $wrong)) {
                $email = substr($email, 0, -strlen($wrong)) . $right;
                break;
            }
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO email_queue (recipient, subject, body, status, category, created_at) VALUES (?, ?, ?, 'pending', ?, NOW())");
        return $stmt->execute([$email, $subject, $body, $category]);
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

    public function sendBatchByCategory(array $emails, $category = 'system') {
        $this->ensureDailyReset();

        // 1. Get rotating sender
        $sender = $this->getRotatingSender($category);
        if ($sender) {
            return $this->sendBatch($emails, $sender);
        }

        // 2. Try default sender as fallback
        $default = $this->getDefaultSender();
        if ($default) {
            return $this->sendBatch($emails, $default);
        }

        // 3. Fallback error
        return [
            "success" => false, 
            "error" => "No active SMTP sender available."
        ];
    }


    public function sendBatch(array $emails, array $config) {
        $host     = $config['smtp_host'];
        $port     = (int)$config['smtp_port'];
        $user     = $config['smtp_user'];
        $pass     = str_replace(' ', '', $config['smtp_pass']);
        $secure   = $config['smtp_encryption'];
        $from     = $config['email'];
        $fromName = $config['name'] ?: ($this->settings['email_from_name'] ?? 'Tuyen Sinh');

        $results   = [];
        $sentCount = 0;

        // Send each email with its own fresh SMTP connection.
        // This avoids SSL bad-length errors caused by Google dropping the TLS
        // session after receiving large HTML bodies over a persistent connection.
        foreach ($emails as $email) {
            $to      = $email['recipient'];
            $subject = $email['subject'];
            $body    = $email['body'];

            $result = $this->executeSmtpSend($to, $subject, $body, true, $host, $port, $user, $pass, $secure, $from, $fromName);

            $results[$email['id']] = $result;
            if ($result === true) {
                $sentCount++;
                // Update stats immediately in database to prevent exceeding limit during active batch
                $db = \App\Core\Database::getInstance()->getConnection();
                $upd = $db->prepare("UPDATE email_senders SET sent_today = sent_today + 1, last_sent_at = NOW() WHERE id = ?");
                $upd->execute([$config['id']]);
            } else {
                // Check if this error warrants deactivating the sender (e.g. limit hit, auth error)
                $errLower = strtolower((string)$result);
                $shouldDeactivate = false;
                if (
                    strpos($errLower, 'daily user sending limit exceeded') !== false ||
                    strpos($errLower, '550 5.4.5') !== false ||
                    strpos($errLower, 'too many login attempts') !== false ||
                    strpos($errLower, 'auth failed') !== false ||
                    strpos($errLower, 'authentication failed') !== false ||
                    strpos($errLower, 'username rejected') !== false ||
                    strpos($errLower, 'credentials') !== false
                ) {
                    $shouldDeactivate = true;
                }

                if ($shouldDeactivate) {
                    error_log("[EmailDeactivate] Deactivating sender ID {$config['id']} ({$config['email']}) due to error: $result");
                    $db = \App\Core\Database::getInstance()->getConnection();
                    $db->prepare("UPDATE email_senders SET is_active = FALSE, locked_until = NULL WHERE id = ?")->execute([$config['id']]);
                    
                    // Stop sending subsequent emails in this batch using this sender.
                    // Mark remaining emails as skipped so they will be retried with a different sender.
                    $remaining = array_slice($emails, array_search($email, $emails) + 1);
                    foreach ($remaining as $rem) {
                        $results[$rem['id']] = "Sender deactivated: $result";
                    }
                    break;
                }
            }

            // Small delay between connections to stay within rate limits
            usleep(150000); // 150ms
        }

        // Release sender lock
        $db = \App\Core\Database::getInstance()->getConnection();
        if ($sentCount > 0) {
            $upd = $db->prepare("UPDATE email_senders SET locked_until = NULL WHERE id = ?");
            $upd->execute([$config['id']]);
        } else {
            // Still update last_sent_at to prevent this failing sender from being prioritized forever
            $upd = $db->prepare("UPDATE email_senders SET last_sent_at = NOW(), locked_until = NULL WHERE id = ?");
            $upd->execute([$config['id']]);
        }

        return ["success" => true, "results" => $results];
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
