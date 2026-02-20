<?php
// scripts/process_mail_queue.php

// 1. Bootstrap
require_once __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require_once __DIR__ . '/../app/Helpers/functions.php';

// Load Env
try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {
    echo "Warning: .env not found.\n";
}

// 2. Main Loop
echo "Starting Email Queue Processor...\n";

$db = \App\Core\Database::getInstance()->getConnection();
$mailer = new \App\Services\MailerService();

while (true) {
    // Fetch pending emails
    $stmt = $db->prepare("SELECT * FROM email_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT 5");
    $stmt->execute();
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($emails)) {
        if (in_array('--once', $argv)) {
            echo "No more emails. Exiting (--once).\n";
            break;
        }
        // No emails, sleep for 5 seconds
        sleep(5);
        continue;
    }

    foreach ($emails as $email) {
        echo "Processing email ID: {$email['id']} to {$email['recipient']}... ";

        // Mark as processing (optional, but good for concurrency if multiple workers)
        // For simple setup, we just process.

        try {
            // Attempt to send
            $result = $mailer->send($email['recipient'], $email['subject'], $email['body'], true);

            if ($result === true) {
                // Success
                $update = $db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
                $update->execute([$email['id']]);
                echo "SENT.\n";
            } else {
                // Failed (SMTP error return)
                $errorInfo = is_string($result) ? $result : 'Unknown error';
                throw new Exception($errorInfo);
            }

        } catch (Exception $e) {
            // Error handling
            echo "FAILED: " . $e->getMessage() . "\n";
            
            $attempts = $email['attempts'] + 1;
            $newStatus = ($attempts >= 3) ? 'failed' : 'pending';
            
            $update = $db->prepare("UPDATE email_queue SET status = ?, attempts = ?, last_error = ? WHERE id = ?");
            $update->execute([$newStatus, $attempts, $e->getMessage(), $email['id']]);
        }
    }
    
    // Tiny sleep to prevent CPU hogging if looking for jobs rapidly
    usleep(100000); // 0.1s
}
