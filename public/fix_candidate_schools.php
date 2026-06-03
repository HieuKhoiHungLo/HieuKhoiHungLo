<?php
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Database;
use App\Core\Cache;

// Set content type to text/plain for clean output in browser or CLI
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$mappings = [
    '25713' => '25212', // THPT Chân Mộng (trước 01/7/2025) -> THPT Chân Mộng
    '25714' => '25215', // THPT Đoan Hùng (trước 01/7/2025) -> THPT Đoan Hùng
    '25715' => '25219', // THPT Hạ Hòa (trước 01/7/2025) -> THPT Hạ Hòa
    '25719' => '25275', // THPT Thanh Ba (trước 01/7/2025) -> THPT Thanh Ba
    '25723' => '25290', // THPT Xuân Áng (trước 01/7/2025) -> THPT Xuân Áng
    '25724' => '25293', // THPT Yển Khê (trước 01/7/2025) -> THPT Yển Khê
    '25725' => '2539074', // THPT Nguyễn Bỉnh Khiêm (trước 01/7/2025) -> THPT Nguyễn Bỉnh Khiêm
    '25708' => '2539071', // THPT Ngô Gia Tự (trước 01/7/2025) -> THPT Ngô Gia Tự
    '2539032' => '2539076', // Cao đẳng nghề Phú Yên (Trước 25/1/2017) -> Cao đẳng nghề Phú Yên
    '2539007' => '2539071', // THPT Ngô Gia Tự (Trước 25/1/2017) -> THPT Ngô Gia Tự
];

try {
    $db = Database::getInstance()->getConnection();
    echo "=== STARTING CANDIDATE SCHOOL CODES BULK UPDATE ===\n";
    $db->beginTransaction();

    $totalUpdated = 0;
    $updatedDetails = [];

    foreach ($mappings as $oldCode => $newCode) {
        // Fetch candidate details using the old school code before updating
        $stmtSelect = $db->prepare("SELECT ho_va_ten, so_cccd FROM thi_sinh WHERE ma_truong_lop_12 = ?");
        $stmtSelect->execute([$oldCode]);
        $candidates = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($candidates)) {
            // Update school code
            $stmtUpdate = $db->prepare("UPDATE thi_sinh SET ma_truong_lop_12 = ? WHERE ma_truong_lop_12 = ?");
            $stmtUpdate->execute([$newCode, $oldCode]);
            $count = $stmtUpdate->rowCount();
            $totalUpdated += $count;

            // Fetch school names for logging
            $stmtSchoolOld = $db->prepare("SELECT ten_truong FROM dm_truong_thpt WHERE ma_truong = ?");
            $stmtSchoolOld->execute([$oldCode]);
            $schoolOldName = $stmtSchoolOld->fetchColumn() ?: "Mã $oldCode";

            $stmtSchoolNew = $db->prepare("SELECT ten_truong FROM dm_truong_thpt WHERE ma_truong = ?");
            $stmtSchoolNew->execute([$newCode]);
            $schoolNewName = $stmtSchoolNew->fetchColumn() ?: "Mã $newCode";

            foreach ($candidates as $c) {
                $updatedDetails[] = [
                    'ho_va_ten' => $c['ho_va_ten'],
                    'so_cccd' => $c['so_cccd'],
                    'old_school' => $schoolOldName,
                    'new_school' => $schoolNewName
                ];
            }
        }
    }

    $db->commit();
    echo "Transaction committed successfully.\n\n";

    echo "=== RESULTS SUMMARY ===\n";
    echo "Total Candidates Updated: " . $totalUpdated . "\n\n";

    if (!empty($updatedDetails)) {
        echo "--- DETAILS OF UPDATED CANDIDATES ---\n";
        foreach ($updatedDetails as $index => $item) {
            echo ($index + 1) . ". {$item['ho_va_ten']} (CCCD: {$item['so_cccd']})\n";
            echo "   - Từ: '{$item['old_school']}'\n";
            echo "   - Sang: '{$item['new_school']}'\n";
        }
        echo "\n";
    }

    // Flush Cache to reflect changes on Admin dashboard immediately
    echo "Flushing application cache...\n";
    Cache::flush();
    echo "Cache flushed successfully.\n";

} catch (\Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
        echo "Transaction rolled back safely.\n";
    }
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}
