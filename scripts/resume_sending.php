<?php
/**
 * HƯỚNG DẪN SỬ DỤNG:
 * Chạy script này vào ngày mai (sau 14:00 giờ VN) để gửi nốt 11 thư còn lại.
 * Lệnh: d:\xampp\php\php.exe d:\xampp\htdocs\TS\scripts\resume_sending.php
 */

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
} catch (\Exception $e) {
    echo "Warning: .env not found.\n";
}

$db = \App\Core\Database::getInstance()->getConnection();

echo "=== KÍCH HOẠT LẠI HỆ THỐNG GỬI EMAIL ===" . PHP_EOL;

// 1. Bật lại tất cả sender
$db->exec("UPDATE email_senders SET is_active = TRUE, sent_today = 0, locked_until = NULL");
echo "✅ Đã bật lại tất cả 12 tài khoản SMTP, reset sent_today = 0." . PHP_EOL;

// 2. Reset failed emails nếu có
$stmt = $db->prepare("UPDATE email_queue SET status = 'pending', attempts = 0, error = NULL WHERE status = 'failed'");
$stmt->execute();
$resetCount = $stmt->rowCount();
if ($resetCount > 0) {
    echo "✅ Đã reset $resetCount thư lỗi về pending." . PHP_EOL;
}

// 3. Unpause queue
$db->prepare("UPDATE settings SET value = '0' WHERE key = 'email_queue_paused'")->execute();
echo "✅ Hàng đợi đã được mở." . PHP_EOL;

// 4. Kiểm tra pending
$pending = (int)$db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'")->fetchColumn();
echo "📬 Có $pending thư đang chờ gửi." . PHP_EOL;

if ($pending === 0) {
    echo "🎉 Không còn thư nào cần gửi! Hoàn tất." . PHP_EOL;
    exit(0);
}

// 5. Gửi email
$mailer = new \App\Services\MailerService();
echo "🚀 Bắt đầu gửi $pending thư..." . PHP_EOL;

$totalProcessed = 0;
$totalFailed = 0;
$batchSize = 20;

while (true) {
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            UPDATE email_queue 
            SET status = 'processing' 
            WHERE id IN (
                SELECT id FROM email_queue 
                WHERE status = 'pending' 
                ORDER BY created_at ASC 
                LIMIT $batchSize 
                FOR UPDATE SKIP LOCKED
            )
            RETURNING *
        ");
        $stmt->execute();
        $emails = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $db->commit();
    } catch (\Exception $e) {
        $db->rollBack();
        echo "Transaction error: " . $e->getMessage() . PHP_EOL;
        sleep(3);
        continue;
    }

    if (empty($emails)) {
        echo "✅ Không còn thư pending. Hoàn tất!" . PHP_EOL;
        break;
    }

    $category = $emails[0]['category'] ?? 'system';
    $batchResult = $mailer->sendBatchByCategory($emails, $category);

    $processed = 0;
    $failed = 0;

    if (isset($batchResult['success']) && $batchResult['success'] === true) {
        $results = $batchResult['results'];
        foreach ($emails as $email) {
            $id = $email['id'];
            $result = $results[$id] ?? 'Not processed';

            if ($result === true) {
                $db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$id]);
                
                // Sync to thu_trung_tuyen
                $db->prepare("
                    UPDATE thu_trung_tuyen 
                    SET status = 'sent', sent_at = NOW() 
                    WHERE email = ? AND status = 'queued'
                ")->execute([$email['recipient']]);

                $processed++;
            } else {
                $attempts = ($email['attempts'] ?? 0) + 1;
                if ($attempts >= 3) {
                    $db->prepare("UPDATE email_queue SET status = 'failed', error = ?, attempts = ? WHERE id = ?")->execute([(string)$result, $attempts, $id]);
                    $failed++;
                } else {
                    $db->prepare("UPDATE email_queue SET status = 'pending', error = ?, attempts = ? WHERE id = ?")->execute([(string)$result, $attempts, $id]);
                }
            }
        }
    } else {
        $errorMsg = $batchResult['error'] ?? 'No active SMTP sender available';
        echo "⚠️ Lỗi batch: $errorMsg" . PHP_EOL;
        foreach ($emails as $email) {
            $id = $email['id'];
            $attempts = ($email['attempts'] ?? 0) + 1;
            if ($attempts >= 3) {
                $db->prepare("UPDATE email_queue SET status = 'failed', error = ?, attempts = ? WHERE id = ?")->execute([$errorMsg, $attempts, $id]);
                $failed++;
            } else {
                $db->prepare("UPDATE email_queue SET status = 'pending', error = ?, attempts = ? WHERE id = ?")->execute([$errorMsg, $attempts, $id]);
            }
        }
    }

    $totalProcessed += $processed;
    $totalFailed += $failed;
    $remaining = (int)$db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'")->fetchColumn();
    echo "[" . date('H:i:s') . "] Processed: $processed, Failed: $failed, Remaining: $remaining" . PHP_EOL;

    if ($remaining === 0) break;
    usleep(500000);
}

echo PHP_EOL . "=== KẾT QUẢ CUỐI CÙNG ===" . PHP_EOL;
echo "Tổng gửi thành công: $totalProcessed" . PHP_EOL;
echo "Tổng thất bại: $totalFailed" . PHP_EOL;

// Clear cache
\App\Core\Cache::forget('email_queue_summary_stats');
echo "✅ Đã xóa cache dashboard." . PHP_EOL;
