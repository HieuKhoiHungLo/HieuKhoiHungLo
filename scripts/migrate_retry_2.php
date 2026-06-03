<?php
/**
 * Script Chạy lại Di cư cho 2 hồ sơ thất bại
 * - LÊ NGUYỄN NGỌC HÀ (025308005316)
 * - BÙI THU HÀ (017308005895)
 */

if (php_sapi_name() !== 'cli') {
    die("Script này chỉ có thể chạy từ môi trường dòng lệnh (CLI).\n");
}

mb_internal_encoding('UTF-8');

define('CLR_RESET', "\033[0m");
define('CLR_RED', "\033[31m");
define('CLR_GREEN', "\033[32m");
define('CLR_YELLOW', "\033[33m");
define('CLR_CYAN', "\033[36m");
define('CLR_BOLD', "\033[1m");

echo CLR_BOLD . CLR_CYAN . "============================================================\n";
echo "   CHẠY LẠI 2 HỒ SƠ THẤT BẠI (NTK -> THV2026)\n";
echo "============================================================\n" . CLR_RESET;

// Danh sách CCCD cần chạy lại
$retryList = [
    '025308005316', // LÊ NGUYỄN NGỌC HÀ
    '017308005895', // BÙI THU HÀ
];

$sourceConfig = [
    'host' => 'aws-1-ap-northeast-2.pooler.supabase.com',
    'port' => 6543,
    'dbname' => 'postgres',
    'user' => 'postgres.zorxrwobsfhejutgjsbi',
    'pass' => 'Phutho2024@!'
];

$destConfig = [
    'host' => 'aws-1-ap-south-1.pooler.supabase.com',
    'port' => 6543,
    'dbname' => 'postgres',
    'user' => 'postgres.oxhuzfqvlpntlymdwfiy',
    'pass' => 'HvuTuyenSinh2026'
];

// Khởi tạo kết nối
try {
    echo "Đang kết nối tới Cơ sở dữ liệu Nguồn (NTK)... ";
    $pdoSrc = new PDO(
        "pgsql:host={$sourceConfig['host']};port={$sourceConfig['port']};dbname={$sourceConfig['dbname']}",
        $sourceConfig['user'], $sourceConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 10]
    );
    echo CLR_GREEN . "OK!\n" . CLR_RESET;

    echo "Đang kết nối tới Cơ sở dữ liệu Đích (THV2026)... ";
    $pdoDest = new PDO(
        "pgsql:host={$destConfig['host']};port={$destConfig['port']};dbname={$destConfig['dbname']}",
        $destConfig['user'], $destConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 10]
    );
    echo CLR_GREEN . "OK!\n\n" . CLR_RESET;

    $pdoSrc->exec("SELECT set_config('app.current_role', 'admin', false), set_config('timezone', 'Asia/Ho_Chi_Minh', false)");
    $pdoDest->exec("SELECT set_config('app.current_role', 'admin', false), set_config('timezone', 'Asia/Ho_Chi_Minh', false)");

} catch (PDOException $e) {
    echo CLR_RED . "❌ Lỗi kết nối: " . $e->getMessage() . "\n" . CLR_RESET;
    exit(1);
}

// Tải cấu trúc cột từ DB đích
echo CLR_BOLD . "Đang tải cấu trúc bảng từ THV2026...\n" . CLR_RESET;
$tablesToMigrate = ['thi_sinh', 'ho_so_xet_tuyen', 'nguyen_vong', 'ket_qua_hoc_tap', 'diem_thi_thpt', 'chung_chi_thi_sinh', 'diem_chi_tiet', 'diem_nang_khieu'];
$tableColumns = [];
$tableColumnTypes = [];
foreach ($tablesToMigrate as $table) {
    $stmt = $pdoDest->prepare("SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ?");
    $stmt->execute([$table]);
    $info = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $tableColumns[$table] = array_keys($info);
    $tableColumnTypes[$table] = $info;
}
echo CLR_GREEN . "✅ Hoàn tất!\n\n" . CLR_RESET;

// Hàm chuẩn hóa kiểu dữ liệu
function sanitizeRowData($rowData, $columnTypes) {
    $sanitized = [];
    foreach ($rowData as $col => $val) {
        if (!isset($columnTypes[$col])) { $sanitized[$col] = $val; continue; }
        $type = $columnTypes[$col];
        if ($type === 'boolean') {
            $sanitized[$col] = ($val === '' || $val === null) ? null : (filter_var($val, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false');
        } elseif ($val === '') {
            $numericDateTypes = ['integer','bigint','numeric','double precision','real','smallint','timestamp with time zone','timestamp without time zone','date'];
            $sanitized[$col] = in_array($type, $numericDateTypes) ? null : '';
        } else {
            $sanitized[$col] = $val;
        }
    }
    return $sanitized;
}

$session_id = 1;
$migratedCount = 0;
$failedCount = 0;

echo CLR_BOLD . "BẮT ĐẦU CHẠY LẠI " . count($retryList) . " HỒ SƠ...\n" . CLR_RESET;
echo str_repeat('-', 70) . "\n";

foreach ($retryList as $idx => $cccd) {

    // Lấy tên thí sinh
    $stmtName = $pdoSrc->prepare("SELECT ho_va_ten FROM thi_sinh WHERE so_cccd = ?");
    $stmtName->execute([$cccd]);
    $nameRow = $stmtName->fetch();
    $hoTen = $nameRow['ho_va_ten'] ?? 'KHÔNG RÕ';

    echo CLR_CYAN . "[" . ($idx + 1) . "/" . count($retryList) . "] $hoTen (CCCD: $cccd)\n" . CLR_RESET;

    // Kiểm tra trùng trong thi_sinh trên DB đích
    $stmtCheck = $pdoDest->prepare("SELECT 1 FROM thi_sinh WHERE so_cccd = ?");
    $stmtCheck->execute([$cccd]);
    if ($stmtCheck->fetch()) {
        echo CLR_YELLOW . "  -> [BỎ QUA] CCCD đã tồn tại trong thi_sinh trên DB đích.\n" . CLR_RESET;
        continue;
    }

    // BƯỚC QUAN TRỌNG: Dọn dẹp dữ liệu orphan trên DB đích trước khi insert
    echo "  -> Kiểm tra và dọn dẹp dữ liệu orphan trên DB đích...\n";
    $orphanTables = ['diem_thi_thpt', 'ket_qua_hoc_tap', 'nguyen_vong', 'chung_chi_thi_sinh', 'diem_chi_tiet', 'diem_nang_khieu'];
    foreach ($orphanTables as $orphanTable) {
        $stmtOrphan = $pdoDest->prepare("SELECT COUNT(*) FROM $orphanTable WHERE so_cccd = ?");
        $stmtOrphan->execute([$cccd]);
        $orphanCount = $stmtOrphan->fetchColumn();
        if ($orphanCount > 0) {
            $stmtDel = $pdoDest->prepare("DELETE FROM $orphanTable WHERE so_cccd = ?");
            $stmtDel->execute([$cccd]);
            echo CLR_YELLOW . "    Đã xóa $orphanCount bản ghi orphan khỏi bảng '$orphanTable'.\n" . CLR_RESET;
        }
    }

    // Thực hiện di cư trong Transaction
    try {
        $pdoDest->beginTransaction();

        // A. Migrate thi_sinh
        $stmtTs = $pdoSrc->prepare("SELECT * FROM thi_sinh WHERE so_cccd = ?");
        $stmtTs->execute([$cccd]);
        $rowTs = $stmtTs->fetch();
        if (!$rowTs) throw new Exception("Không tìm thấy thi_sinh ở DB nguồn cho CCCD: $cccd");
        $validDataTs = sanitizeRowData(array_intersect_key($rowTs, array_flip($tableColumns['thi_sinh'])), $tableColumnTypes['thi_sinh']);
        $fieldsTs = array_keys($validDataTs);
        $sqlTs = "INSERT INTO thi_sinh (" . implode(', ', $fieldsTs) . ") VALUES (" . implode(', ', array_map(fn($f) => ":$f", $fieldsTs)) . ")";
        $pdoDest->prepare($sqlTs)->execute($validDataTs);
        echo "    ✅ thi_sinh\n";

        // B. Migrate ho_so_xet_tuyen
        $stmtHs = $pdoSrc->prepare("SELECT * FROM ho_so_xet_tuyen WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
        $stmtHs->execute([$cccd, $session_id]);
        $rowsHs = $stmtHs->fetchAll();
        $oldToNewHoSoIdMap = [];
        foreach ($rowsHs as $rowHs) {
            $oldHsId = $rowHs['id'];
            $validDataHs = sanitizeRowData(array_intersect_key($rowHs, array_flip($tableColumns['ho_so_xet_tuyen'])), $tableColumnTypes['ho_so_xet_tuyen']);
            unset($validDataHs['id']);
            $fieldsHs = array_keys($validDataHs);
            $sqlHs = "INSERT INTO ho_so_xet_tuyen (" . implode(', ', $fieldsHs) . ") VALUES (" . implode(', ', array_map(fn($f) => ":$f", $fieldsHs)) . ") RETURNING id";
            $stmtInsHs = $pdoDest->prepare($sqlHs);
            $stmtInsHs->execute($validDataHs);
            $newHsId = $stmtInsHs->fetchColumn();
            $oldToNewHoSoIdMap[$oldHsId] = $newHsId;
        }
        echo "    ✅ ho_so_xet_tuyen (" . count($rowsHs) . " bản ghi)\n";

        // C. Migrate nguyen_vong
        $stmtNv = $pdoSrc->prepare("SELECT * FROM nguyen_vong WHERE so_cccd = ?");
        $stmtNv->execute([$cccd]);
        $rowsNv = $stmtNv->fetchAll();
        foreach ($rowsNv as $rowNv) {
            $validDataNv = sanitizeRowData(array_intersect_key($rowNv, array_flip($tableColumns['nguyen_vong'])), $tableColumnTypes['nguyen_vong']);
            unset($validDataNv['id']);
            $oldHsId = $rowNv['ho_so_id'] ?? null;
            $validDataNv['ho_so_id'] = ($oldHsId !== null && isset($oldToNewHoSoIdMap[$oldHsId])) ? $oldToNewHoSoIdMap[$oldHsId] : null;
            $fieldsNv = array_keys($validDataNv);
            $pdoDest->prepare("INSERT INTO nguyen_vong (" . implode(', ', $fieldsNv) . ") VALUES (" . implode(', ', array_map(fn($f) => ":$f", $fieldsNv)) . ")")->execute($validDataNv);
        }
        echo "    ✅ nguyen_vong (" . count($rowsNv) . " bản ghi)\n";

        // D. Các bảng con theo so_cccd
        $childTables = ['ket_qua_hoc_tap', 'diem_thi_thpt', 'chung_chi_thi_sinh', 'diem_chi_tiet', 'diem_nang_khieu'];
        foreach ($childTables as $childTable) {
            $stmtChild = $pdoSrc->prepare("SELECT * FROM $childTable WHERE so_cccd = ?");
            $stmtChild->execute([$cccd]);
            $rowsChild = $stmtChild->fetchAll();
            foreach ($rowsChild as $rowChild) {
                $validDataChild = sanitizeRowData(array_intersect_key($rowChild, array_flip($tableColumns[$childTable])), $tableColumnTypes[$childTable]);
                unset($validDataChild['id']);
                $fieldsChild = array_keys($validDataChild);
                $pdoDest->prepare("INSERT INTO $childTable (" . implode(', ', $fieldsChild) . ") VALUES (" . implode(', ', array_map(fn($f) => ":$f", $fieldsChild)) . ")")->execute($validDataChild);
            }
            if (count($rowsChild) > 0) echo "    ✅ $childTable (" . count($rowsChild) . " bản ghi)\n";
        }

        $pdoDest->commit();
        echo CLR_GREEN . CLR_BOLD . "  -> [THÀNH CÔNG] Đã di cư đầy đủ hồ sơ $hoTen!\n\n" . CLR_RESET;
        $migratedCount++;

    } catch (Exception $e) {
        $pdoDest->rollBack();
        echo CLR_RED . "  -> [THẤT BẠI] Lỗi: " . $e->getMessage() . "\n\n" . CLR_RESET;
        $failedCount++;
    }
}

echo str_repeat('-', 70) . "\n";
echo CLR_BOLD . CLR_CYAN . "TỔNG KẾT:\n" . CLR_RESET;
echo "  Di cư thành công : " . CLR_GREEN . CLR_BOLD . $migratedCount . CLR_RESET . "\n";
echo "  Thất bại         : " . CLR_RED . CLR_BOLD . $failedCount . CLR_RESET . "\n";
echo CLR_BOLD . CLR_GREEN . "\nHoàn tất!\n" . CLR_RESET;
