<?php
/**
 * Script dọn dẹp các hồ sơ trống (không có nguyện vọng) ở đợt Ghi danh sớm (Bản tối ưu hiệu năng cao)
 * 
 * Cách chạy:
 *   - Thử nghiệm: php scripts/remove_empty_applications.php --dry-run
 *   - Thực thi chính thức: php scripts/remove_empty_applications.php
 */

if (php_sapi_name() !== 'cli') {
    die("Script này chỉ có thể chạy từ dòng lệnh (CLI).\n");
}

define('CLR_RESET', "\033[0m");
define('CLR_GREEN', "\033[32m");
define('CLR_YELLOW', "\033[33m");
define('CLR_RED', "\033[31m");
define('CLR_CYAN', "\033[36m");
define('CLR_BOLD', "\033[1m");

echo CLR_BOLD . CLR_CYAN . "============================================================\n";
echo "   TIẾN TRÌNH DỌN DẸP HỒ SƠ TRỐNG (TỐI ƯU HÓA TRUY VẤN)\n";
echo "============================================================\n" . CLR_RESET;

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}

require_once __DIR__ . '/../app/Core/Database.php';

$dryRun = in_array('--dry-run', $argv);
if ($dryRun) {
    echo CLR_YELLOW . CLR_BOLD . "⚠️ CHẾ ĐỘ THỬ NGHIỆM (--dry-run). SẼ KHÔNG CÓ THAY ĐỔI NÀO ĐƯỢC LƯU VÀO DB!\n\n" . CLR_RESET;
}

try {
    $dbInstance = \App\Core\Database::getInstance();
    $dbInstance->setSystemRole('admin'); // Bypass RLS
    $db = $dbInstance->getConnection();
    echo "Kết nối CSDL thành công tới: " . CLR_GREEN . ($_ENV['DB_HOST'] ?? '127.0.0.1') . CLR_RESET . "\n\n";
} catch (PDOException $e) {
    echo CLR_RED . "❌ Lỗi kết nối CSDL: " . $e->getMessage() . "\n" . CLR_RESET;
    exit(1);
}

$sessionId = 3; // Ghi danh sớm

try {
    $db->beginTransaction();

    // 1. Đếm số lượng hồ sơ trống trước khi xoá
    $sqlCount = "
        SELECT COUNT(*)
        FROM ho_so_xet_tuyen hs
        INNER JOIN thi_sinh t ON hs.so_cccd = t.so_cccd
        WHERE hs.dot_tuyen_sinh_id = ?
          AND hs.deleted_at IS NULL
          AND t.deleted_at IS NULL
          AND NOT EXISTS (
              SELECT 1 FROM nguyen_vong nv_check 
              WHERE nv_check.ho_so_id = hs.id 
                 OR (nv_check.so_cccd = hs.so_cccd AND nv_check.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id)
          )
    ";
    $stmtCount = $db->prepare($sqlCount);
    $stmtCount->execute([$sessionId]);
    $count = (int)$stmtCount->fetchColumn();

    echo CLR_BOLD . "Tìm thấy $count hồ sơ trống cần dọn dẹp ở đợt 'Ghi danh sớm'.\n" . CLR_RESET;

    if ($count > 0) {
        $startTime = microtime(true);
        
        // Thực thi xoá toàn bộ bằng một câu lệnh đơn
        $sqlDelete = "
            DELETE FROM ho_so_xet_tuyen hs
            WHERE hs.dot_tuyen_sinh_id = ?
              AND hs.deleted_at IS NULL
              AND EXISTS (SELECT 1 FROM thi_sinh t WHERE t.so_cccd = hs.so_cccd AND t.deleted_at IS NULL)
              AND NOT EXISTS (
                  SELECT 1 FROM nguyen_vong nv_check 
                  WHERE nv_check.ho_so_id = hs.id 
                     OR (nv_check.so_cccd = hs.so_cccd AND nv_check.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id)
              )
        ";
        
        if (!$dryRun) {
            $stmtDelete = $db->prepare($sqlDelete);
            $stmtDelete->execute([$sessionId]);
        }
        
        $elapsed = round(microtime(true) - $startTime, 4);
        echo "Thời gian thực thi truy cập DB: $elapsed giây.\n";
    }

    if ($dryRun) {
        $db->rollBack();
        echo "\n" . CLR_YELLOW . CLR_BOLD . "⚠️ Chạy ở chế độ DRY-RUN: Đã rollback toàn bộ thay đổi. CSDL an toàn.\n" . CLR_RESET;
    } else {
        $db->commit();
        echo "\n" . CLR_GREEN . CLR_BOLD . "✅ ĐÃ XOÁ THÀNH CÔNG $count HỒ SƠ TRỐNG KHỎI CƠ SỞ DỮ LIỆU!\n" . CLR_RESET;
    }

} catch (Exception $e) {
    $db->rollBack();
    echo CLR_RED . "❌ Có lỗi xảy ra. Đã rollback: " . $e->getMessage() . "\n" . CLR_RESET;
    exit(1);
}
