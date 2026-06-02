<?php
require_once 'd:/xampp/htdocs/TS/vendor/autoload.php';

// Set timezone to Vietnam
date_default_timezone_set('Asia/Ho_Chi_Minh');

try {
    $dotenv = new App\Core\DotEnv('d:/xampp/htdocs/TS/.env');
    $dotenv->load();
} catch (\Exception $e) {}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = 'd:/xampp/htdocs/TS/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

function batchUpsert($db, $table, $columns, $conflictTarget, $updateCols, $rows, $chunkSize = 300) {
    if (empty($rows)) return 0;
    
    $chunks = array_chunk($rows, $chunkSize);
    $count = 0;
    foreach ($chunks as $chunk) {
        $placeholders = [];
        $params = [];
        foreach ($chunk as $row) {
            $placeholders[] = '(' . implode(',', array_fill(0, count($row), '?')) . ')';
            foreach ($row as $val) {
                $params[] = $val;
            }
            $count++;
        }
        
        $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES " . implode(',', $placeholders);
        if ($conflictTarget && $updateCols) {
            $updateSet = implode(', ', array_map(fn($col) => "$col = EXCLUDED.$col", $updateCols));
            $sql .= " ON CONFLICT ($conflictTarget) DO UPDATE SET $updateSet";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }
    return $count;
}

try {
    $dbInstance = \App\Core\Database::getInstance();
    $dbInstance->setSystemRole('admin');
    $db = $dbInstance->getConnection();

    echo "Starting high-performance batch catalog migration inside a transaction...\n";
    $db->beginTransaction();

    // Clean up incorrect 3-digit school records
    echo "Cleaning up temporary 3-digit school records...\n";
    $db->query("DELETE FROM dm_truong_thpt WHERE length(ma_truong) = 3");

    // ----------------------------------------------------
    // 1. IMPORT TỈNH
    // ----------------------------------------------------
    echo "\nProcessing dm_tinh (Tỉnh/Thành phố) in batches...\n";
    $tinhCsv = 'd:/xampp/htdocs/TS/scratch/tinh.csv';
    if (!file_exists($tinhCsv)) {
        throw new Exception("Missing tinh.csv at $tinhCsv");
    }

    $db->query("UPDATE dm_tinh SET is_active = FALSE");
    
    $fp = fopen($tinhCsv, 'r');
    fgetcsv($fp); // skip header
    
    $tinhRows = [];
    while (($row = fgetcsv($fp)) !== false) {
        if (count($row) < 2) continue;
        $ma = str_pad(trim($row[0]), 2, '0', STR_PAD_LEFT);
        $ten = trim($row[1]);
        if (!$ma || !$ten) continue;
        
        $tinhRows[] = [$ma, $ten, true]; // values matching columns: ma_tinh, ten_tinh, is_active
    }
    fclose($fp);

    $tinhCount = batchUpsert(
        $db, 
        'dm_tinh', 
        ['ma_tinh', 'ten_tinh', 'is_active'], 
        'ma_tinh', 
        ['ten_tinh', 'is_active'], 
        $tinhRows
    );
    echo "Imported/Updated $tinhCount Tỉnh/Thành phố.\n";

    // ----------------------------------------------------
    // 2. IMPORT XÃ
    // ----------------------------------------------------
    echo "\nProcessing dm_xa (Xã/Phường) in batches...\n";
    $xaCsv = 'd:/xampp/htdocs/TS/scratch/xa.csv';
    if (!file_exists($xaCsv)) {
        throw new Exception("Missing xa.csv at $xaCsv");
    }

    $db->query("UPDATE dm_xa SET is_active = FALSE");

    $fp = fopen($xaCsv, 'r');
    fgetcsv($fp); // skip header
    
    $xaRows = [];
    while (($row = fgetcsv($fp)) !== false) {
        if (count($row) < 3) continue;
        $ma_xa = str_pad(trim($row[0]), 5, '0', STR_PAD_LEFT);
        $ten_xa = trim($row[1]);
        $ma_tinh = str_pad(trim($row[2]), 2, '0', STR_PAD_LEFT);
        if (!$ma_xa || !$ten_xa || !$ma_tinh) continue;
        
        $xaRows[] = [$ma_xa, $ten_xa, $ma_tinh, true]; // ma_xa, ten_xa, ma_tinh, is_active
    }
    fclose($fp);

    $xaCount = batchUpsert(
        $db, 
        'dm_xa', 
        ['ma_xa', 'ten_xa', 'ma_tinh', 'is_active'], 
        'ma_xa', 
        ['ten_xa', 'ma_tinh', 'is_active'], 
        $xaRows
    );
    echo "Imported/Updated $xaCount Xã/Phường.\n";

    // ----------------------------------------------------
    // 3. IMPORT TRƯỜNG THPT
    // ----------------------------------------------------
    echo "\nProcessing dm_truong_thpt (Trường THPT) in batches...\n";
    $thptCsv = 'd:/xampp/htdocs/TS/scratch/thpt.csv';
    if (!file_exists($thptCsv)) {
        throw new Exception("Missing thpt.csv at $thptCsv");
    }

    $db->query("UPDATE dm_truong_thpt SET is_active = FALSE");

    $fp = fopen($thptCsv, 'r');
    fgetcsv($fp); // skip header
    
    $thptRows = [];
    while (($row = fgetcsv($fp)) !== false) {
        if (count($row) < 6) continue;
        $ma_tinh = str_pad(trim($row[0]), 2, '0', STR_PAD_LEFT);
        $ma_truong = str_pad(trim($row[2]), 3, '0', STR_PAD_LEFT);
        $ten_truong = trim($row[3]);
        $khu_vuc = trim($row[5]);
        if (!$ma_truong || !$ten_truong || !$ma_tinh) continue;
        
        $ma_truong_db = $ma_tinh . $ma_truong;
        $thptRows[] = [$ma_truong_db, $ten_truong, $khu_vuc ?: 'KV2', $ma_tinh, true]; // ma_truong, ten_truong, khu_vuc, ma_tinh, is_active
    }
    fclose($fp);

    $thptCount = batchUpsert(
        $db, 
        'dm_truong_thpt', 
        ['ma_truong', 'ten_truong', 'khu_vuc', 'ma_tinh', 'is_active'], 
        'ma_truong', 
        ['ten_truong', 'khu_vuc', 'ma_tinh', 'is_active'], 
        $thptRows
    );
    echo "Imported/Updated $thptCount Trường THPT.\n";

    // ----------------------------------------------------
    // 4. DISPLAY STATISTICS OF ACTIVE/INACTIVE
    // ----------------------------------------------------
    echo "\n--- Catalog Statistics After Migration ---\n";
    
    $stats = [
        'dm_tinh' => 'ma_tinh',
        'dm_xa' => 'ma_xa',
        'dm_truong_thpt' => 'ma_truong'
    ];
    
    foreach ($stats as $table => $idCol) {
        $total = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        $active = $db->query("SELECT COUNT(*) FROM $table WHERE is_active = TRUE")->fetchColumn();
        $inactive = $db->query("SELECT COUNT(*) FROM $table WHERE is_active = FALSE")->fetchColumn();
        echo "Table $table:\n";
        echo "  Total rows: $total\n";
        echo "  Active rows (new catalog): $active\n";
        echo "  Inactive rows (legacy historical): $inactive\n";
    }

    $db->commit();
    echo "\nTransaction committed successfully! All catalogs have been normalized.\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
        echo "Transaction rolled back due to error.\n";
    }
    echo "ERROR: " . $e->getMessage() . "\n";
}
