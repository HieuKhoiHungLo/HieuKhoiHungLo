<?php
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Database;
use App\Core\Cache;

$mapping = [
    '01' => ['new_code' => '01', 'name' => 'Thành phố Hà Nội'],
    '02' => ['new_code' => '24', 'name' => 'Tỉnh Bắc Ninh'],
    '03' => ['new_code' => '22', 'name' => 'Tỉnh Quảng Ninh'],
    '04' => ['new_code' => '31', 'name' => 'Thành phố Hải Phòng'],
    '05' => ['new_code' => '33', 'name' => 'Tỉnh Hưng Yên'],
    '06' => ['new_code' => '37', 'name' => 'Tỉnh Ninh Bình'],
    '07' => ['new_code' => '04', 'name' => 'Tỉnh Cao Bằng'],
    '08' => ['new_code' => '08', 'name' => 'Tỉnh Tuyên Quang'],
    '09' => ['new_code' => '15', 'name' => 'Tỉnh Lào Cai'],
    '10' => ['new_code' => '19', 'name' => 'Tỉnh Thái Nguyên'],
    '11' => ['new_code' => '20', 'name' => 'Tỉnh Lạng Sơn'],
    '12' => ['new_code' => '25', 'name' => 'Tỉnh Phú Thọ'],
    '13' => ['new_code' => '11', 'name' => 'Tỉnh Điện Biên'],
    '14' => ['new_code' => '12', 'name' => 'Tỉnh Lai Châu'],
    '15' => ['new_code' => '14', 'name' => 'Tỉnh Sơn La'],
    '16' => ['new_code' => '38', 'name' => 'Tỉnh Thanh Hóa'],
    '17' => ['new_code' => '40', 'name' => 'Tỉnh Nghệ An'],
    '18' => ['new_code' => '42', 'name' => 'Tỉnh Hà Tĩnh'],
    '19' => ['new_code' => '44', 'name' => 'Tỉnh Quảng Trị'],
    '20' => ['new_code' => '46', 'name' => 'Thành phố Huế'],
    '21' => ['new_code' => '48', 'name' => 'Thành phố Đà Nẵng'],
    '22' => ['new_code' => '51', 'name' => 'Tỉnh Quảng Ngãi'],
    '23' => ['new_code' => '56', 'name' => 'Tỉnh Khánh Hòa'],
    '24' => ['new_code' => '52', 'name' => 'Tỉnh Gia Lai'],
    '25' => ['new_code' => '66', 'name' => 'Tỉnh Đắk Lắk'],
    '26' => ['new_code' => '68', 'name' => 'Tỉnh Lâm Đồng'],
    '27' => ['new_code' => '80', 'name' => 'Tỉnh Tây Ninh'],
    '28' => ['new_code' => '75', 'name' => 'Tỉnh Đồng Nai'],
    '29' => ['new_code' => '79', 'name' => 'Thành phố Hồ Chí Minh'],
    '30' => ['new_code' => '86', 'name' => 'Tỉnh Vĩnh Long'],
    '31' => ['new_code' => '82', 'name' => 'Tỉnh Đồng Tháp'],
    '32' => ['new_code' => '91', 'name' => 'Tỉnh An Giang'],
    '33' => ['new_code' => '92', 'name' => 'Thành phố Cần Thơ'],
    '34' => ['new_code' => '96', 'name' => 'Tỉnh Cà Mau']
];

try {
    $db = Database::getInstance()->getConnection();
    echo "=== STARTING PROVINCE CODE MIGRATION (FIXED UNIQUE VIOLATION) ===\n";
    $db->beginTransaction();

    // 1. Fetch foreign keys dynamically
    $sql = "
        SELECT
            tc.constraint_name, 
            tc.table_name, 
            kcu.column_name, 
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name 
        FROM 
            information_schema.table_constraints AS tc 
            JOIN information_schema.key_column_usage AS kcu
              ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
              ON ccu.constraint_name = tc.constraint_name
              AND ccu.table_schema = tc.table_schema
        WHERE tc.constraint_type = 'FOREIGN KEY' 
          AND ccu.table_name = 'dm_tinh'
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($fks) . " foreign key constraints referencing 'dm_tinh'. Dropping them...\n";
    foreach ($fks as $fk) {
        $dropSql = "ALTER TABLE {$fk['table_name']} DROP CONSTRAINT {$fk['constraint_name']}";
        $db->exec($dropSql);
        echo "Dropped constraint '{$fk['constraint_name']}' on table '{$fk['table_name']}'.\n";
    }

    // 2. Stage 1: Add TEMP_ prefix to all old codes in referencing tables to prevent collision/ordering issues
    echo "\nStage 1: Renaming codes in referencing tables to TEMP_ prefix...\n";
    foreach ($mapping as $oldCode => $info) {
        $tempCode = "TEMP_" . $oldCode;
        
        // Update dm_xa
        $stmt = $db->prepare("UPDATE dm_xa SET ma_tinh = ? WHERE ma_tinh = ?");
        $stmt->execute([$tempCode, $oldCode]);

        // Update dm_truong_thpt
        $stmt = $db->prepare("UPDATE dm_truong_thpt SET ma_tinh = ? WHERE ma_tinh = ?");
        $stmt->execute([$tempCode, $oldCode]);

        // Update config_vung_tuyen_sinh
        $stmt = $db->prepare("UPDATE config_vung_tuyen_sinh SET ma_tinh = ? WHERE ma_tinh = ?");
        $stmt->execute([$tempCode, $oldCode]);

        // Update thi_sinh
        $stmt = $db->prepare("UPDATE thi_sinh SET ma_tinh_ho_khau = ? WHERE ma_tinh_ho_khau = ?");
        $stmt->execute([$tempCode, $oldCode]);

        $stmt = $db->prepare("UPDATE thi_sinh SET ma_tinh_lop_12 = ? WHERE ma_tinh_lop_12 = ?");
        $stmt->execute([$tempCode, $oldCode]);

        $stmt = $db->prepare("UPDATE thi_sinh SET ma_tinh_thuong_tru = ? WHERE ma_tinh_thuong_tru = ?");
        $stmt->execute([$tempCode, $oldCode]);
    }
    echo "Stage 1 finished successfully.\n";

    // 3. Stage 2: Convert from TEMP_ prefix to new standard code in referencing tables
    echo "\nStage 2: Renaming to standard codes in referencing tables...\n";
    foreach ($mapping as $oldCode => $info) {
        $tempCode = "TEMP_" . $oldCode;
        $newCode = $info['new_code'];

        // Update dm_xa
        $stmt = $db->prepare("UPDATE dm_xa SET ma_tinh = ? WHERE ma_tinh = ?");
        $stmt->execute([$newCode, $tempCode]);

        // Update dm_truong_thpt
        $stmt = $db->prepare("UPDATE dm_truong_thpt SET ma_tinh = ? WHERE ma_tinh = ?");
        $stmt->execute([$newCode, $tempCode]);

        // Update config_vung_tuyen_sinh
        $stmt = $db->prepare("UPDATE config_vung_tuyen_sinh SET ma_tinh = ? WHERE ma_tinh = ?");
        $stmt->execute([$newCode, $tempCode]);

        // Update thi_sinh
        $stmt = $db->prepare("UPDATE thi_sinh SET ma_tinh_ho_khau = ? WHERE ma_tinh_ho_khau = ?");
        $stmt->execute([$newCode, $tempCode]);

        $stmt = $db->prepare("UPDATE thi_sinh SET ma_tinh_lop_12 = ? WHERE ma_tinh_lop_12 = ?");
        $stmt->execute([$newCode, $tempCode]);

        $stmt = $db->prepare("UPDATE thi_sinh SET ma_tinh_thuong_tru = ? WHERE ma_tinh_thuong_tru = ?");
        $stmt->execute([$newCode, $tempCode]);
    }
    echo "Stage 2 finished successfully.\n";

    // 4. Migrate dm_nganh.khu_vuc_tuyen_sinh (comma-separated string config)
    echo "\nMigrating dm_nganh.khu_vuc_tuyen_sinh...\n";
    $stmt = $db->query("SELECT ma_nganh, khu_vuc_tuyen_sinh FROM dm_nganh WHERE khu_vuc_tuyen_sinh IS NOT NULL AND khu_vuc_tuyen_sinh != ''");
    $majors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $majorUpdateCount = 0;
    foreach ($majors as $m) {
        $allowed = explode(',', $m['khu_vuc_tuyen_sinh']);
        $allowed = array_map('trim', $allowed);
        $updated = false;
        foreach ($allowed as &$code) {
            // Standardize to 2-digit format if it was saved as single-digit
            $paddedCode = str_pad($code, 2, '0', STR_PAD_LEFT);
            if (isset($mapping[$paddedCode])) {
                $code = $mapping[$paddedCode]['new_code'];
                $updated = true;
            }
        }
        unset($code);
        if ($updated) {
            $newKhuVuc = implode(',', array_unique($allowed));
            $stmtUpdate = $db->prepare("UPDATE dm_nganh SET khu_vuc_tuyen_sinh = ? WHERE ma_nganh = ?");
            $stmtUpdate->execute([$newKhuVuc, $m['ma_nganh']]);
            echo "Updated major {$m['ma_nganh']} khu_vuc_tuyen_sinh to: $newKhuVuc\n";
            $majorUpdateCount++;
        }
    }
    echo "Migrated $majorUpdateCount majors admission area configs.\n";

    // 5. Recreate foreign keys
    echo "\nRecreating foreign key constraints...\n";
    foreach ($fks as $fk) {
        $createSql = "ALTER TABLE {$fk['table_name']} ADD CONSTRAINT {$fk['constraint_name']} FOREIGN KEY ({$fk['column_name']}) REFERENCES {$fk['foreign_table_name']} ({$fk['foreign_column_name']})";
        $db->exec($createSql);
        echo "Recreated constraint '{$fk['constraint_name']}' on table '{$fk['table_name']}'.\n";
    }

    $db->commit();
    echo "\n=== MIGRATION COMPLETED SUCCESSFULLY IN TRANSACTION ===\n";

    // Clear caches
    Cache::forget('master_provinces');
    echo "Cleared master_provinces cache.\n";

} catch (\Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Transaction rolled back safely.\n";
}
