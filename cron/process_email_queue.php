<?php
/**
 * Email Queue Processor
 * 
 * Processes pending emails from the email_queue table.
 * Run via cron or Windows Task Scheduler every minute:
 *   php d:\xampp\htdocs\TS\cron\process_email_queue.php
 * 
 * Or call via web: http://localhost/TS/cron/process_email_queue.php?key=SECRET
 */

// Bootstrap
require_once __DIR__ . '/../vendor/autoload.php';

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

try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

// Simple security for web access
if (php_sapi_name() !== 'cli') {
    $key = $_GET['key'] ?? '';
    if ($key !== 'hvu_cron_2024') {
        http_response_code(403);
        die('Forbidden');
    }
}

$db = App\Core\Database::getInstance()->getConnection();
$mailer = new App\Services\MailerService();

// Fetch pending emails (limit 10 per run to avoid timeout)
$stmt = $db->prepare("SELECT * FROM email_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 10");
$stmt->execute();
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

$processed = 0;
$failed = 0;

foreach ($emails as $email) {
    $id = $email['id'];
    
    // Mark as processing
    $db->prepare("UPDATE email_queue SET status = 'processing' WHERE id = ?")->execute([$id]);
    
    $result = $mailer->send($email['recipient'], $email['subject'], $email['body'], true);
    
    if ($result === true) {
        $db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$id]);
        $processed++;
    } else {
        // Increment retry or mark failed
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

$msg = date('Y-m-d H:i:s') . " - Processed: $processed, Failed: $failed, Total: " . count($emails);

if (php_sapi_name() === 'cli') {
    echo $msg . "\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $msg]);
}
