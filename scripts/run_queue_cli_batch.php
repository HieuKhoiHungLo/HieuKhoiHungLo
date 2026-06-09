<?php
// scripts/run_queue_cli_batch.php

require_once __DIR__ . '/../vendor/autoload.php';

// Register autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

require_once __DIR__ . '/../app/Helpers/functions.php';

// Load Env
try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {
    echo "Warning: .env not found.\n";
}

$db = \App\Core\Database::getInstance()->getConnection();
$mailer = new \App\Services\MailerService();

echo "=== STARTING BATCH EMAIL QUEUE PROCESSOR CLI ===" . PHP_EOL;

while (true) {
    // Check if queue is paused
    $stmtPaused = $db->query("SELECT value FROM settings WHERE key='email_queue_paused'");
    $paused = $stmtPaused->fetchColumn();
    if ($paused === '1') {
        echo "Queue is paused. Waiting 10 seconds..." . PHP_EOL;
        sleep(10);
        continue;
    }

    // Fetch batch of 40 pending emails using SKIP LOCKED
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            UPDATE email_queue 
            SET status = 'processing' 
            WHERE id IN (
                SELECT id FROM email_queue 
                WHERE status = 'pending' 
                ORDER BY created_at ASC 
                LIMIT 20 
                FOR UPDATE SKIP LOCKED
            )
            RETURNING *
        ");
        $stmt->execute();
        $emails = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $db->commit();
    } catch (\Exception $e) {
        $db->rollBack();
        echo "Transaction error: " . $e->getMessage() . ". Retrying in 5s..." . PHP_EOL;
        sleep(5);
        continue;
    }

    if (empty($emails)) {
        echo "No pending emails. Sleeping 5 seconds..." . PHP_EOL;
        sleep(5);
        continue;
    }

    $processed = 0;
    $failed = 0;
    $category = $emails[0]['category'] ?? 'system';
    
    echo "Fetched " . count($emails) . " emails (Category: $category). Sending batch..." . PHP_EOL;
    
    $batchResult = $mailer->sendBatchByCategory($emails, $category);
    
    if (isset($batchResult['success']) && $batchResult['success'] === true) {
        $results = $batchResult['results'];
        foreach ($emails as $email) {
            $id = $email['id'];
            $result = $results[$id] ?? 'Not processed';
            
            if ($result === true) {
                $db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$id]);
                
                // Sync status to thu_trung_tuyen table
                $db->prepare("
                    UPDATE thu_trung_tuyen 
                    SET status = 'sent', sent_at = NOW() 
                    WHERE email = ? AND status = 'queued'
                ")->execute([$email['recipient']]);

                $processed++;
            } else {
                $attempts = ($email['attempts'] ?? 0) + 1;
                $maxAttempts = 3;
                
                if ($attempts >= $maxAttempts) {
                    $db->prepare("UPDATE email_queue SET status = 'failed', error = ?, attempts = ? WHERE id = ?")->execute([(string)$result, $attempts, $id]);
                    $failed++;
                } else {
                    $db->prepare("UPDATE email_queue SET status = 'pending', error = ?, attempts = ? WHERE id = ?")->execute([(string)$result, $attempts, $id]);
                }
            }
        }
    } else {
        $errorMsg = $batchResult['error'] ?? 'SMTP Connection/Authentication failed';
        echo "Batch sending failed completely: $errorMsg" . PHP_EOL;
        foreach ($emails as $email) {
            $id = $email['id'];
            $attempts = ($email['attempts'] ?? 0) + 1;
            $maxAttempts = 3;
            
            if ($attempts >= $maxAttempts) {
                $db->prepare("UPDATE email_queue SET status = 'failed', error = ?, attempts = ? WHERE id = ?")->execute([$errorMsg, $attempts, $id]);
                $failed++;
            } else {
                $db->prepare("UPDATE email_queue SET status = 'pending', error = ?, attempts = ? WHERE id = ?")->execute([$errorMsg, $attempts, $id]);
            }
        }
    }

    $remaining = (int)$db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'")->fetchColumn();
    echo "[" . date('H:i:s') . "] Processed: $processed, Failed: $failed, Remaining Pending: $remaining" . PHP_EOL;
    
    // Brief sleep between batches
    usleep(500000); // 0.5s
}
