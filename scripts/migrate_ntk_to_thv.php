<?php
/**
 * Script Di cư Dữ liệu Thí sinh An toàn từ NTK sang THV2026
 *
 * Chạy từ CLI:
 * - Chạy thử (Dry-run): php scripts/migrate_ntk_to_thv.php --dry-run
 * - Chạy thật (Execute): php scripts/migrate_ntk_to_thv.php --execute
 */

// Đảm bảo chỉ chạy từ dòng lệnh (CLI)
if (php_sapi_name() !== 'cli') {
    die("Script này chỉ có thể chạy từ môi trường dòng lệnh (CLI).\n");
}

// Thiết lập mã hóa UTF-8 cho console
mb_internal_encoding('UTF-8');

// Định nghĩa màu sắc cho output console
define('CLR_RESET', "\033[0m");
define('CLR_RED', "\033[31m");
define('CLR_GREEN', "\033[32m");
define('CLR_YELLOW', "\033[33m");
define('CLR_BLUE', "\033[34m");
define('CLR_CYAN', "\033[36m");
define('CLR_BOLD', "\033[1m");

echo CLR_BOLD . CLR_CYAN . "============================================================\n";
echo "       HỆ THỐNG DI CƯ DỮ LIỆU THÍ SINH AN TOÀN (NTK -> THV2026)\n";
echo "============================================================\n" . CLR_RESET;

// 1. Phân tích tham số dòng lệnh
$dryRun = true; // Mặc định là chạy thử
if (isset($argv[1])) {
    if ($argv[1] === '--execute') {
        $dryRun = false;
    } elseif ($argv[1] !== '--dry-run') {
        echo CLR_YELLOW . "Tham số không hợp lệ. Sử dụng --dry-run (mặc định) hoặc --execute.\n" . CLR_RESET;
        exit(1);
    }
}

if ($dryRun) {
    echo CLR_YELLOW . CLR_BOLD . "[CHẾ ĐỘ] CHẠY THỬ (DRY-RUN) - Sẽ không có dữ liệu nào được ghi vào CSDL đích.\n\n" . CLR_RESET;
} else {
    echo CLR_RED . CLR_BOLD . "[CHẾ ĐỘ] THỰC THI THẬT (EXECUTE) - Dữ liệu sẽ được ghi trực tiếp vào CSDL đích.\n\n" . CLR_RESET;
    echo "Chuẩn bị chạy trong 3 giây... Nhấn Ctrl+C để hủy.\n";
    sleep(3);
}

// 2. Cấu hình kết nối Database
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

// Khởi tạo kết nối PDO
try {
    echo "Đang kết nối tới Cơ sở dữ liệu Nguồn (NTK)... ";
    $dsnSrc = "pgsql:host={$sourceConfig['host']};port={$sourceConfig['port']};dbname={$sourceConfig['dbname']}";
    $pdoSrc = new PDO($dsnSrc, $sourceConfig['user'], $sourceConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    echo CLR_GREEN . "Thành công!\n" . CLR_RESET;

    echo "Đang kết nối tới Cơ sở dữ liệu Đích (THV2026)... ";
    $dsnDest = "pgsql:host={$destConfig['host']};port={$destConfig['port']};dbname={$destConfig['dbname']}";
    $pdoDest = new PDO($dsnDest, $destConfig['user'], $destConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    echo CLR_GREEN . "Thành công!\n\n" . CLR_RESET;

    // Thiết lập RLS bypass cho quyền admin
    $pdoSrc->exec("SELECT set_config('app.current_role', 'admin', false)");
    $pdoDest->exec("SELECT set_config('app.current_role', 'admin', false)");
    $pdoSrc->exec("SELECT set_config('timezone', 'Asia/Ho_Chi_Minh', false)");
    $pdoDest->exec("SELECT set_config('timezone', 'Asia/Ho_Chi_Minh', false)");

} catch (PDOException $e) {
    echo CLR_RED . "\n❌ Lỗi kết nối CSDL: " . $e->getMessage() . "\n" . CLR_RESET;
    exit(1);
}

// 3. Lấy danh sách cột và kiểu dữ liệu của các bảng trên DB đích
echo CLR_BOLD . "Đang tải cấu trúc bảng từ CSDL đích (THV2026)...\n" . CLR_RESET;
$tablesToMigrate = [
    'thi_sinh',
    'ho_so_xet_tuyen',
    'nguyen_vong',
    'ket_qua_hoc_tap',
    'diem_thi_thpt',
    'chung_chi_thi_sinh',
    'diem_chi_tiet',
    'diem_nang_khieu'
];

$tableColumns = [];
$tableColumnTypes = [];

foreach ($tablesToMigrate as $table) {
    $stmt = $pdoDest->prepare("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = ?
    ");
    $stmt->execute([$table]);
    $columnsInfo = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $tableColumns[$table] = array_keys($columnsInfo);
    $tableColumnTypes[$table] = $columnsInfo;
    
    echo "  - Bảng " . CLR_CYAN . str_pad($table, 20) . CLR_RESET . ": Tìm thấy " . count($columnsInfo) . " cột.\n";
}
echo CLR_GREEN . "✅ Tải cấu trúc bảng hoàn tất!\n\n" . CLR_RESET;

/**
 * Hàm chuẩn hóa và làm sạch dữ liệu dòng trước khi insert để tránh lỗi kiểu dữ liệu PostgreSQL
 */
function sanitizeRowData($rowData, $columnTypes) {
    $sanitized = [];
    foreach ($rowData as $col => $val) {
        if (!isset($columnTypes[$col])) {
            $sanitized[$col] = $val;
            continue;
        }

        $type = $columnTypes[$col];
        
        // 1. Xử lý cột Boolean bằng cách trả về chuỗi 'true' hoặc 'false' (tránh bug PDO cast boolean false thành "")
        if ($type === 'boolean') {
            if ($val === '' || $val === null) {
                $sanitized[$col] = null;
            } else {
                $sanitized[$col] = filter_var($val, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            }
        } 
        // 2. Xử lý các cột số và ngày tháng khi có giá trị chuỗi rỗng
        elseif ($val === '') {
            $numericAndDateTypes = [
                'integer', 'bigint', 'numeric', 'double precision', 'real', 'smallint', 
                'timestamp with time zone', 'timestamp without time zone', 'date'
            ];
            if (in_array($type, $numericAndDateTypes)) {
                $sanitized[$col] = null;
            } else {
                $sanitized[$col] = '';
            }
        } 
        // 3. Giá trị mặc định
        else {
            $sanitized[$col] = $val;
        }
    }
    return $sanitized;
}

// 4. Lấy danh sách thí sinh thuộc Đợt 1 (Ghi danh) ở CSDL nguồn
echo CLR_BOLD . "Đang tìm kiếm hồ sơ thuộc Đợt 1 ở CSDL nguồn (NTK)...\n" . CLR_RESET;
$session_id = 1;
$stmt = $pdoSrc->prepare("
    SELECT hs.*, ts.ho_va_ten, ts.so_cccd
    FROM ho_so_xet_tuyen hs
    JOIN thi_sinh ts ON hs.so_cccd = ts.so_cccd
    WHERE hs.dot_tuyen_sinh_id = ?
    ORDER BY hs.created_at ASC
");
$stmt->execute([$session_id]);
$candidatesToMigrate = $stmt->fetchAll();
$totalCandidates = count($candidatesToMigrate);

echo CLR_GREEN . "✅ Tìm thấy " . CLR_BOLD . $totalCandidates . CLR_RESET . CLR_GREEN . " hồ sơ thí sinh thuộc Đợt 1.\n\n" . CLR_RESET;

if ($totalCandidates === 0) {
    echo CLR_YELLOW . "Không có hồ sơ nào cần di cư. Kết thúc.\n" . CLR_RESET;
    exit(0);
}

// 5. Khởi tạo các biến thống kê
$migratedCount = 0;
$skippedCount = 0;
$failedCount = 0;
$skippedCandidates = [];

// Đường dẫn lưu log trùng
$logDir = __DIR__ . '/../storage/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}
$logFile = $logDir . '/migration_skipped_candidates.json';

// 6. Vòng lặp di cư từng thí sinh
echo CLR_BOLD . "BẮT ĐẦU QUÁ TRÌNH DI CƯ THÍ SINH...\n" . CLR_RESET;
echo str_repeat('-', 80) . "\n";

foreach ($candidatesToMigrate as $index => $candidate) {
    $cccd = $candidate['so_cccd'];
    $hoTen = $candidate['ho_va_ten'];
    $idxDisplay = $index + 1;

    echo CLR_CYAN . "[$idxDisplay/$totalCandidates] Thí sinh: $hoTen (CCCD: $cccd) -> " . CLR_RESET;

    // Bước 6.1: Kiểm tra trùng lặp trên DB đích
    $stmtCheck = $pdoDest->prepare("SELECT 1 FROM thi_sinh WHERE so_cccd = ?");
    $stmtCheck->execute([$cccd]);
    if ($stmtCheck->fetch()) {
        echo CLR_YELLOW . "[BỎ QUA] Đã tồn tại CCCD này trên DB đích.\n" . CLR_RESET;
        $skippedCount++;
        $skippedCandidates[] = [
            'so_cccd' => $cccd,
            'ho_ten' => $hoTen,
            'reason' => 'Duplicate CCCD on Destination DB'
        ];
        continue;
    }

    if ($dryRun) {
        echo CLR_GREEN . "[OK] Sẵn sàng chuyển (Mô phỏng)\n" . CLR_RESET;
        $migratedCount++;
        continue;
    }

    // Bước 6.2: Thực thi ghi thật trong Transaction
    try {
        $pdoDest->beginTransaction();

        // ----------------------------------------------------
        // A. Migrate bảng: thi_sinh
        // ----------------------------------------------------
        $stmtTs = $pdoSrc->prepare("SELECT * FROM thi_sinh WHERE so_cccd = ?");
        $stmtTs->execute([$cccd]);
        $rowTs = $stmtTs->fetch();

        if (!$rowTs) {
            throw new Exception("Không tìm thấy dữ liệu thi_sinh ở DB nguồn cho CCCD: $cccd");
        }

        // Lọc cột hợp lệ và chuẩn hóa kiểu dữ liệu
        $validDataTs = array_intersect_key($rowTs, array_flip($tableColumns['thi_sinh']));
        $validDataTs = sanitizeRowData($validDataTs, $tableColumnTypes['thi_sinh']);
        
        $fieldsTs = array_keys($validDataTs);
        $placeholdersTs = array_map(fn($f) => ":$f", $fieldsTs);
        
        $sqlTs = "INSERT INTO thi_sinh (" . implode(', ', $fieldsTs) . ") VALUES (" . implode(', ', $placeholdersTs) . ")";
        $stmtInsertTs = $pdoDest->prepare($sqlTs);
        $stmtInsertTs->execute($validDataTs);

        // ----------------------------------------------------
        // B. Migrate bảng: ho_so_xet_tuyen (nhận ID tự tăng mới)
        // ----------------------------------------------------
        $stmtHs = $pdoSrc->prepare("SELECT * FROM ho_so_xet_tuyen WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
        $stmtHs->execute([$cccd, $session_id]);
        $rowsHs = $stmtHs->fetchAll();

        $oldToNewHoSoIdMap = [];

        foreach ($rowsHs as $rowHs) {
            $oldHsId = $rowHs['id'];
            
            // Loại bỏ cột id tự tăng và chuẩn hóa kiểu dữ liệu
            $validDataHs = array_intersect_key($rowHs, array_flip($tableColumns['ho_so_xet_tuyen']));
            unset($validDataHs['id']);
            $validDataHs = sanitizeRowData($validDataHs, $tableColumnTypes['ho_so_xet_tuyen']);
            
            $fieldsHs = array_keys($validDataHs);
            $placeholdersHs = array_map(fn($f) => ":$f", $fieldsHs);
            
            $sqlHs = "INSERT INTO ho_so_xet_tuyen (" . implode(', ', $fieldsHs) . ") VALUES (" . implode(', ', $placeholdersHs) . ") RETURNING id";
            $stmtInsertHs = $pdoDest->prepare($sqlHs);
            $stmtInsertHs->execute($validDataHs);
            
            $newHsId = $stmtInsertHs->fetchColumn();
            if (!$newHsId) {
                $newHsId = $pdoDest->lastInsertId('ho_so_xet_tuyen_id_seq');
            }
            
            $oldToNewHoSoIdMap[$oldHsId] = $newHsId;
        }

        // ----------------------------------------------------
        // C. Migrate bảng: nguyen_vong (ánh xạ ho_so_id mới)
        // ----------------------------------------------------
        $stmtNv = $pdoSrc->prepare("SELECT * FROM nguyen_vong WHERE so_cccd = ?");
        $stmtNv->execute([$cccd]);
        $rowsNv = $stmtNv->fetchAll();

        foreach ($rowsNv as $rowNv) {
            $validDataNv = array_intersect_key($rowNv, array_flip($tableColumns['nguyen_vong']));
            unset($validDataNv['id']); // Bỏ qua PK tự sinh
            
            // Ánh xạ ho_so_id mới
            $oldHsId = $rowNv['ho_so_id'] ?? null;
            if ($oldHsId !== null && isset($oldToNewHoSoIdMap[$oldHsId])) {
                $validDataNv['ho_so_id'] = $oldToNewHoSoIdMap[$oldHsId];
            } else {
                $validDataNv['ho_so_id'] = null;
            }
            
            $validDataNv = sanitizeRowData($validDataNv, $tableColumnTypes['nguyen_vong']);

            $fieldsNv = array_keys($validDataNv);
            $placeholdersNv = array_map(fn($f) => ":$f", $fieldsNv);

            $sqlNv = "INSERT INTO nguyen_vong (" . implode(', ', $fieldsNv) . ") VALUES (" . implode(', ', $placeholdersNv) . ")";
            $stmtInsertNv = $pdoDest->prepare($sqlNv);
            $stmtInsertNv->execute($validDataNv);
        }

        // ----------------------------------------------------
        // D. Các bảng con khác (chỉ liên kết theo so_cccd)
        // ----------------------------------------------------
        $childTables = [
            'ket_qua_hoc_tap',
            'diem_thi_thpt',
            'chung_chi_thi_sinh',
            'diem_chi_tiet',
            'diem_nang_khieu'
        ];

        foreach ($childTables as $childTable) {
            $stmtChild = $pdoSrc->prepare("SELECT * FROM $childTable WHERE so_cccd = ?");
            $stmtChild->execute([$cccd]);
            $rowsChild = $stmtChild->fetchAll();

            foreach ($rowsChild as $rowChild) {
                $validDataChild = array_intersect_key($rowChild, array_flip($tableColumns[$childTable]));
                unset($validDataChild['id']); // Bỏ qua PK tự sinh
                $validDataChild = sanitizeRowData($validDataChild, $tableColumnTypes[$childTable]);

                $fieldsChild = array_keys($validDataChild);
                $placeholdersChild = array_map(fn($f) => ":$f", $fieldsChild);

                $sqlChild = "INSERT INTO $childTable (" . implode(', ', $fieldsChild) . ") VALUES (" . implode(', ', $placeholdersChild) . ")";
                $stmtInsertChild = $pdoDest->prepare($sqlChild);
                $stmtInsertChild->execute($validDataChild);
            }
        }

        // Hoàn thành di cư trọn vẹn thí sinh này
        $pdoDest->commit();
        echo CLR_GREEN . "[THÀNH CÔNG] Đã di cư đầy đủ hồ sơ.\n" . CLR_RESET;
        $migratedCount++;

    } catch (Exception $e) {
        $pdoDest->rollBack();
        echo CLR_RED . "[THẤT BẠI] Lỗi: " . $e->getMessage() . "\n" . CLR_RESET;
        $failedCount++;
    }
}

echo str_repeat('-', 80) . "\n";
echo CLR_BOLD . CLR_CYAN . "TỔNG KẾT QUÁ TRÌNH DI CƯ:\n" . CLR_RESET;
echo "  - Tổng số hồ sơ quét từ NTK   : " . CLR_BOLD . $totalCandidates . CLR_RESET . "\n";
echo "  - Số hồ sơ di cư thành công   : " . CLR_GREEN . CLR_BOLD . $migratedCount . CLR_RESET . "\n";
echo "  - Số hồ sơ bỏ qua do trùng    : " . CLR_YELLOW . CLR_BOLD . $skippedCount . CLR_RESET . "\n";
echo "  - Số hồ sơ di cư thất bại     : " . CLR_RED . CLR_BOLD . $failedCount . CLR_RESET . "\n";

// 7. Ghi file log các hồ sơ trùng lặp
if ($skippedCount > 0) {
    file_put_contents($logFile, json_encode($skippedCandidates, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo CLR_YELLOW . "\nDanh sách các hồ sơ trùng lặp đã được ghi lại tại: \n" . CLR_RESET;
    echo "  " . CLR_BOLD . realpath($logFile) . CLR_RESET . "\n";
}

echo CLR_BOLD . CLR_GREEN . "\nQuá trình hoàn tất!\n" . CLR_RESET;
?>
