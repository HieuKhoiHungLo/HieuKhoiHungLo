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

try {
    $db = Database::getInstance()->getConnection();
    echo "=== STARTING DAK LAK TO PHU THO MIGRATION (OPTIMIZED BULK UPDATE) ===\n";
    $db->beginTransaction();

    // 1. Fetch skipped candidates (Dak Lak with Miennam note) for logging
    $stmtSkipped = $db->prepare("
        SELECT DISTINCT t.ho_va_ten, t.so_cccd, t.ghi_chu as ts_ghi_chu, hs.ghi_chu as hs_ghi_chu
        FROM thi_sinh t
        LEFT JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd
        WHERE (t.ma_tinh_ho_khau = '66' OR t.ma_tinh_thuong_tru = '66' OR t.ma_tinh_lop_12 = '66')
          AND (t.ghi_chu ILIKE '%Miennam%' OR hs.ghi_chu ILIKE '%Miennam%')
    ");
    $stmtSkipped->execute();
    $skippedList = $stmtSkipped->fetchAll(PDO::FETCH_ASSOC);

    // 2. Perform bulk update using RETURNING clause to get updated records in a single query
    $updateSql = "
        UPDATE thi_sinh 
        SET ma_tinh_ho_khau = CASE WHEN ma_tinh_ho_khau = '66' THEN '25' ELSE ma_tinh_ho_khau END,
            ma_tinh_thuong_tru = CASE WHEN ma_tinh_thuong_tru = '66' THEN '25' ELSE ma_tinh_thuong_tru END,
            ma_tinh_lop_12 = CASE WHEN ma_tinh_lop_12 = '66' THEN '25' ELSE ma_tinh_lop_12 END
        WHERE (ma_tinh_ho_khau = '66' OR ma_tinh_thuong_tru = '66' OR ma_tinh_lop_12 = '66')
          AND so_cccd NOT IN (
              SELECT so_cccd FROM thi_sinh WHERE ghi_chu ILIKE '%Miennam%'
              UNION
              SELECT so_cccd FROM ho_so_xet_tuyen WHERE ghi_chu ILIKE '%Miennam%' AND so_cccd IS NOT NULL
          )
        RETURNING ho_va_ten, so_cccd
    ";
    
    $stmtUpdate = $db->prepare($updateSql);
    $stmtUpdate->execute();
    $updatedList = $stmtUpdate->fetchAll(PDO::FETCH_ASSOC);

    $db->commit();
    echo "Transaction committed successfully.\n\n";

    echo "=== RESULTS SUMMARY ===\n";
    echo "Total Updated ( Dak Lak -> Phu Tho ): " . count($updatedList) . "\n";
    echo "Total Skipped ( Miennam ): " . count($skippedList) . "\n\n";

    if (!empty($updatedList)) {
        echo "--- SAMPLES OF UPDATED CANDIDATES (Showing first 50 of " . count($updatedList) . ") ---\n";
        $displayLimit = 50;
        foreach (array_slice($updatedList, 0, $displayLimit) as $index => $item) {
            echo ($index + 1) . ". {$item['ho_va_ten']} (CCCD: {$item['so_cccd']})\n";
        }
        if (count($updatedList) > $displayLimit) {
            echo "... and " . (count($updatedList) - $displayLimit) . " more candidates.\n";
        }
        echo "\n";
    }

    if (!empty($skippedList)) {
        echo "--- DETAILS OF SKIPPED CANDIDATES (Miennam) ---\n";
        foreach ($skippedList as $index => $item) {
            echo ($index + 1) . ". {$item['ho_va_ten']} (CCCD: {$item['so_cccd']})\n";
            echo "   - Thi Sinh Note: '" . ($item['ts_ghi_chu'] ?? 'NULL') . "'\n";
            echo "   - Ho So Note: '" . ($item['hs_ghi_chu'] ?? 'NULL') . "'\n";
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
