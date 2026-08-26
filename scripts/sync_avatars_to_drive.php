<?php
/**
 * CLI Script: Đồng bộ toàn bộ ảnh thẻ cục bộ trên VPS lên Google Drive và xóa file vật lý trên VPS
 * 
 * Cách chạy trên VPS:
 *   php scripts/sync_avatars_to_drive.php
 * 
 * Hoặc chỉ định cụ thể đợt tuyển sinh (session_id):
 *   php scripts/sync_avatars_to_drive.php --session_id=1
 */

if (php_sapi_name() !== 'cli') {
    die("Script này chỉ được phép chạy từ giao diện dòng lệnh (CLI).\n");
}

set_time_limit(0);
ini_set('memory_limit', '1024M');

require_once dirname(__DIR__) . '/vendor/autoload.php';

try {
    $dotenv = new App\Core\DotEnv(dirname(__DIR__) . '/.env');
    $dotenv->load();
} catch (\Exception $e) {
    // Fail silently
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

require_once dirname(__DIR__) . '/app/Helpers/functions.php';

use App\Core\Database;
use App\Services\AvatarDriveImportService;

echo "========================================================================\n";
echo "   HVU - ĐỒNG BỘ ẢNH THẺ TỪ VPS LÊN GOOGLE DRIVE & XÓA FILE CỤC BỘ     \n";
echo "========================================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    echo "[1/4] Kết nối cơ sở dữ liệu: THÀNH CÔNG.\n";

    // Phân tích tham số CLI
    $options = getopt("", ["session_id::", "all::", "dry-run::"]);
    $sessionId = $options['session_id'] ?? null;
    $dryRun = isset($options['dry-run']);

    if ($dryRun) {
        echo "[CHẾ ĐỘ THỬ NGHIỆM - DRY RUN] Sẽ không upload và không xóa file thật.\n";
    }

    // Truy vấn danh sách thí sinh có ảnh cục bộ
    if ($sessionId) {
        $sql = "
            SELECT ts.so_cccd, ts.anh_dai_dien, k.session_id
            FROM thi_sinh ts
            JOIN ket_qua_trung_tuyen k ON ts.so_cccd = k.so_cccd
            WHERE k.session_id = :session_id AND (ts.anh_dai_dien LIKE 'public/uploads/%' OR ts.anh_dai_dien LIKE 'uploads/%')
            ORDER BY k.id ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':session_id' => $sessionId]);
    } else {
        $sql = "
            SELECT ts.so_cccd, ts.anh_dai_dien, COALESCE(k.session_id, dt.id) as session_id
            FROM thi_sinh ts
            LEFT JOIN ket_qua_trung_tuyen k ON ts.so_cccd = k.so_cccd
            LEFT JOIN dot_tuyen_sinh dt ON dt.is_active = 1
            WHERE ts.anh_dai_dien LIKE 'public/uploads/%' OR ts.anh_dai_dien LIKE 'uploads/%'
            ORDER BY ts.id ASC
        ";
        $stmt = $db->query($sql);
    }

    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($candidates);

    echo "[2/4] Tìm thấy tổng cộng: {$total} thí sinh có ảnh thẻ lưu cục bộ trên VPS.\n";

    if ($total === 0) {
        echo "\n=> Không có ảnh thẻ cục bộ nào cần đồng bộ. Tất cả đã ở trên Google Drive!\n";
        exit(0);
    }

    // Khởi tạo Service Google Drive
    echo "[3/4] Khởi tạo kết nối Google Drive API...\n";
    $service = new AvatarDriveImportService();

    echo "[4/4] Bắt đầu tiến trình đồng bộ và dọn dẹp file VPS...\n";
    echo "------------------------------------------------------------------------\n";

    $successCount = 0;
    $notFoundCount = 0;
    $errorCount = 0;
    $freedBytes = 0;

    $rootDir = dirname(__DIR__);

    foreach ($candidates as $index => $c) {
        $stt = $index + 1;
        $cccd = $c['so_cccd'];
        $relPath = $c['anh_dai_dien'];
        $currSessionId = $c['session_id'] ?: 1;

        // Chuẩn hóa đường dẫn file vật lý
        $localPath = $rootDir . '/' . ltrim($relPath, '/');
        
        // Dự phòng nếu đường dẫn trong DB không có tiền tố public/
        if (!file_exists($localPath) && strpos($relPath, 'public/') !== 0) {
            $localPath = $rootDir . '/public/' . ltrim($relPath, '/');
        }

        if (!file_exists($localPath)) {
            echo " [{$stt}/{$total}] CCCD: {$cccd} | BỎ QUA (Không thấy file: {$relPath})\n";
            $notFoundCount++;
            continue;
        }

        $fileSize = filesize($localPath);

        if ($dryRun) {
            echo " [{$stt}/{$total}] CCCD: {$cccd} | [DRY-RUN] Sẽ upload: " . basename($localPath) . " (" . round($fileSize / 1024, 1) . " KB)\n";
            $successCount++;
            $freedBytes += $fileSize;
            continue;
        }

        try {
            $driveUrl = $service->uploadSingleLocalFileToDrive($localPath, $cccd, $currSessionId);

            if ($driveUrl) {
                // Xóa file vật lý trên VPS sau khi upload Drive thành công
                if (@unlink($localPath)) {
                    $freedBytes += $fileSize;
                    echo " [{$stt}/{$total}] CCCD: {$cccd} | OK => Đã lên Drive & Xóa file VPS (" . round($fileSize / 1024, 1) . " KB)\n";
                } else {
                    echo " [{$stt}/{$total}] CCCD: {$cccd} | OK => Đã lên Drive (Nhưng chưa xóa được file VPS)\n";
                }
                $successCount++;
            } else {
                echo " [{$stt}/{$total}] CCCD: {$cccd} | LỖI => Không thể upload lên Google Drive\n";
                $errorCount++;
            }
        } catch (\Exception $e) {
            echo " [{$stt}/{$total}] CCCD: {$cccd} | LỖI NGOẠI LỆ: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }

    echo "------------------------------------------------------------------------\n";
    echo " KẾT QUẢ TỔNG KẾT:\n";
    echo "  - Tổng số ảnh xử lý thành công: {$successCount} / {$total}\n";
    echo "  - Số file không tìm thấy trên đĩa: {$notFoundCount}\n";
    echo "  - Số file gặp lỗi: {$errorCount}\n";
    echo "  - Tổng dung lượng đĩa đã giải phóng trên VPS: " . round($freedBytes / (1024 * 1024), 2) . " MB\n";
    echo "========================================================================\n";

} catch (\Exception $e) {
    echo "\n[LỖI NGHIÊM TRỌNG]: " . $e->getMessage() . "\n";
    exit(1);
}
