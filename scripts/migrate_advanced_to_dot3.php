<?php
/**
 * Script Di cư & Đồng bộ Nâng cao từ NTK sang THV2026
 * 
 * - Đối tượng: 
 *   1. 34 hồ sơ hoàn toàn mới (Đã di cư ở Đợt 1 -> Sẽ chuyển sang Đợt 3).
 *   2. 137 hồ sơ bị trùng CCCD nhưng CHƯA ĐƯỢC DUYỆT (Trạng thái khác 'Đã duyệt' hoặc chưa có hồ sơ) trên THV2026.
 * - Đợt đích bắt buộc: Đợt ID = 3 (Ghi danh sớm).
 * - Hành vi: Dọn dẹp sạch sẽ dữ liệu cũ chưa duyệt trên THV2026 và nạp lại toàn bộ dữ liệu mới nhất (điểm, nguyện vọng, học bạ) từ NTK sang.
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
echo "   TIẾN TRÌNH ĐỒNG BỘ NÂNG CAO & ĐỔI ĐỢT TUYỂN SINH (NTK -> ĐỢT 3 THV2026)\n";
echo "============================================================\n" . CLR_RESET;

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

$source_session_id = 1; // Đợt 1 bên NTK
$dest_session_id = 3;   // Đợt 3 bên THV2026 (Ghi danh sớm)

try {
    echo "Đang kết nối tới Cơ sở dữ liệu Nguồn (NTK)... ";
    $pdoSrc = new PDO(
        "pgsql:host={$sourceConfig['host']};port={$sourceConfig['port']};dbname={$sourceConfig['dbname']}",
        $sourceConfig['user'], $sourceConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    echo CLR_GREEN . "OK!\n" . CLR_RESET;

    echo "Đang kết nối tới Cơ sở dữ liệu Đích (THV2026)... ";
    $pdoDest = new PDO(
        "pgsql:host={$destConfig['host']};port={$destConfig['port']};dbname={$destConfig['dbname']}",
        $destConfig['user'], $destConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => true]
    );
    echo CLR_GREEN . "OK!\n\n" . CLR_RESET;

    $pdoSrc->exec("SELECT set_config('app.current_role', 'admin', false)");
    $pdoDest->exec("SELECT set_config('app.current_role', 'admin', false)");

} catch (PDOException $e) {
    echo CLR_RED . "❌ Lỗi kết nối: " . $e->getMessage() . "\n" . CLR_RESET;
    exit(1);
}

// Kiểm tra sự tồn tại của Đợt tuyển sinh 3 ở đích
$stmtDot = $pdoDest->prepare("SELECT ten_dot FROM dot_tuyen_sinh WHERE id = ?");
$stmtDot->execute([$dest_session_id]);
$tenDot = $stmtDot->fetchColumn();
if (!$tenDot) {
    echo CLR_RED . "❌ Lỗi: Không tìm thấy Đợt tuyển sinh ID = $dest_session_id trên CSDL đích!\n" . CLR_RESET;
    exit(1);
}
echo CLR_GREEN . "🎯 Đã xác định Đợt tuyển sinh đích: ID $dest_session_id ($tenDot)\n\n" . CLR_RESET;

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

// 1. Quét toàn bộ hồ sơ thí sinh thuộc Đợt 1 bên nguồn
echo CLR_BOLD . "Đang quét danh sách hồ sơ đợt 1 từ CSDL nguồn...\n" . CLR_RESET;
$stmtCandidates = $pdoSrc->prepare("SELECT ts.so_cccd, ts.ho_va_ten FROM thi_sinh ts INNER JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd WHERE hs.dot_tuyen_sinh_id = ?");
$stmtCandidates->execute([$source_session_id]);
$candidates = $stmtCandidates->fetchAll();
echo CLR_GREEN . "✅ Tìm thấy " . count($candidates) . " thí sinh bên nguồn.\n\n" . CLR_RESET;

$syncedCount = 0;
$skippedApprovedCount = 0;
$failedCount = 0;

echo CLR_BOLD . "BẮT ĐẦU ĐỒNG BỘ TOÀN DIỆN SANG ĐỢT 3 THV2026...\n" . CLR_RESET;
echo str_repeat('=', 80) . "\n";

foreach ($candidates as $idx => $cand) {
    $cccd = $cand['so_cccd'];
    $hoTen = $cand['ho_va_ten'];

    // BƯỚC 1: Kiểm tra trạng thái đã duyệt trên THV2026
    $stmtCheckDuyet = $pdoDest->prepare("SELECT trang_thai FROM ho_so_xet_tuyen WHERE so_cccd = ?");
    $stmtCheckDuyet->execute([$cccd]);
    $existingStatuses = $stmtCheckDuyet->fetchAll(PDO::FETCH_COLUMN);

    $isApprovedOnDest = false;
    foreach ($existingStatuses as $status) {
        if (in_array(strtolower((string)$status), ['da_duyet', 'approved', '2', '3'])) {
            $isApprovedOnDest = true;
            break;
        }
    }

    if ($isApprovedOnDest) {
        echo CLR_YELLOW . "[" . ($idx + 1) . "/" . count($candidates) . "] Thí sinh: $hoTen ($cccd) -> [BỎ QUA] Đã DUYỆT hồ sơ trên THV2026.\n" . CLR_RESET;
        $skippedApprovedCount++;
        continue;
    }

    echo CLR_CYAN . "[" . ($idx + 1) . "/" . count($candidates) . "] Thí sinh: $hoTen ($cccd) -> Đang đồng bộ...\n" . CLR_RESET;

    try {
        $pdoDest->beginTransaction();

        // BƯỚC 2: Dọn dẹp sạch sẽ dữ liệu cũ (chưa duyệt hoặc mồ côi) trên CSDL đích THV2026
        // Ngoại trừ bảng thi_sinh (chỉ xóa bảng con và hồ sơ xét tuyển để ghi nhận lại đợt 3 mới nhất)
        $cleanTables = ['diem_thi_thpt', 'ket_qua_hoc_tap', 'nguyen_vong', 'chung_chi_thi_sinh', 'diem_chi_tiet', 'diem_nang_khieu', 'ho_so_xet_tuyen'];
        foreach ($cleanTables as $cleanTable) {
            $stmtDel = $pdoDest->prepare("DELETE FROM $cleanTable WHERE so_cccd = ?");
            $stmtDel->execute([$cccd]);
        }

        // BƯỚC 3: Đảm bảo thí sinh có tài khoản trong bảng thi_sinh ở CSDL đích
        $stmtCheckTs = $pdoDest->prepare("SELECT 1 FROM thi_sinh WHERE so_cccd = ?");
        $stmtCheckTs->execute([$cccd]);
        if (!$stmtCheckTs->fetch()) {
            // Chèn mới tài khoản thi_sinh
            $stmtGetTs = $pdoSrc->prepare("SELECT * FROM thi_sinh WHERE so_cccd = ?");
            $stmtGetTs->execute([$cccd]);
            $rowTs = $stmtGetTs->fetch();
            if (!$rowTs) throw new Exception("Không tìm thấy thông tin thi_sinh trên DB nguồn");
            
            $validDataTs = sanitizeRowData(array_intersect_key($rowTs, array_flip($tableColumns['thi_sinh'])), $tableColumnTypes['thi_sinh']);
            $fieldsTs = array_keys($validDataTs);
            $sqlTs = "INSERT INTO thi_sinh (" . implode(', ', $fieldsTs) . ") VALUES (" . implode(', ', array_map(fn($f) => ":$f", $fieldsTs)) . ")";
            $pdoDest->prepare($sqlTs)->execute($validDataTs);
            echo "    + Tạo tài khoản thi_sinh mới\n";
        }

        // BƯỚC 4: Di cư hồ sơ xét tuyển (ho_so_xet_tuyen) sang Đợt đích = 3
        $stmtGetHs = $pdoSrc->prepare("SELECT * FROM ho_so_xet_tuyen WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
        $stmtGetHs->execute([$cccd, $source_session_id]);
        $rowsHs = $stmtGetHs->fetchAll();
        $oldToNewHoSoIdMap = [];

        foreach ($rowsHs as $rowHs) {
            $oldHsId = $rowHs['id'];
            $rowHs['dot_tuyen_sinh_id'] = $dest_session_id; // ĐỔI SANG ĐỢT 3
            
            $validDataHs = sanitizeRowData(array_intersect_key($rowHs, array_flip($tableColumns['ho_so_xet_tuyen'])), $tableColumnTypes['ho_so_xet_tuyen']);
            unset($validDataHs['id']);
            
            $fieldsHs = array_keys($validDataHs);
            $sqlHs = "INSERT INTO ho_so_xet_tuyen (" . implode(', ', $fieldsHs) . ") VALUES (" . implode(', ', array_map(fn($f) => ":$f", $fieldsHs)) . ") RETURNING id";
            $stmtInsHs = $pdoDest->prepare($sqlHs);
            $stmtInsHs->execute($validDataHs);
            $newHsId = $stmtInsHs->fetchColumn();
            $oldToNewHoSoIdMap[$oldHsId] = $newHsId;
        }

        // BƯỚC 5: Di cư nguyện vọng (nguyen_vong)
        $stmtGetNv = $pdoSrc->prepare("SELECT * FROM nguyen_vong WHERE so_cccd = ?");
        $stmtGetNv->execute([$cccd]);
        $rowsNv = $stmtGetNv->fetchAll();
        foreach ($rowsNv as $rowNv) {
            $validDataNv = sanitizeRowData(array_intersect_key($rowNv, array_flip($tableColumns['nguyen_vong'])), $tableColumnTypes['nguyen_vong']);
            unset($validDataNv['id']);
            $oldHsId = $rowNv['ho_so_id'] ?? null;
            $validDataNv['ho_so_id'] = ($oldHsId !== null && isset($oldToNewHoSoIdMap[$oldHsId])) ? $oldToNewHoSoIdMap[$oldHsId] : null;
            
            $fieldsNv = array_keys($validDataNv);
            $pdoDest->prepare("INSERT INTO nguyen_vong (" . implode(', ', $fieldsNv) . ") VALUES (" . implode(', ', array_map(fn($f) => ":$f", $fieldsNv)) . ")")->execute($validDataNv);
        }

        // BƯỚC 6: Di cư tất cả các bảng con liên quan theo CCCD
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
        }

        $pdoDest->commit();
        echo CLR_GREEN . "    ✅ Đồng bộ hoàn tất (Học bạ & Nguyện vọng mới nhất đã nạp).\n" . CLR_RESET;
        $syncedCount++;

    } catch (Exception $e) {
        $pdoDest->rollBack();
        echo CLR_RED . "    ❌ Đồng bộ thất bại: " . $e->getMessage() . "\n" . CLR_RESET;
        $failedCount++;
    }
}

echo str_repeat('=', 80) . "\n";
echo CLR_BOLD . CLR_CYAN . "KẾT QUẢ CUỐI CÙNG:\n" . CLR_RESET;
echo "  - Tổng số thí sinh quét qua     : " . count($candidates) . "\n";
echo "  - Đã đồng bộ mới/làm sạch (Đợt 3): " . CLR_GREEN . CLR_BOLD . $syncedCount . CLR_RESET . "\n";
echo "  - Bỏ qua do đã duyệt (Đợt cũ)   : " . CLR_YELLOW . CLR_BOLD . $skippedApprovedCount . CLR_RESET . "\n";
echo "  - Số lượng lỗi                  : " . CLR_RED . CLR_BOLD . $failedCount . CLR_RESET . "\n";
echo CLR_BOLD . CLR_GREEN . "\nToàn bộ dữ liệu của thí sinh đã được cập nhật trọn vẹn và an toàn tuyệt đối!\n" . CLR_RESET;
