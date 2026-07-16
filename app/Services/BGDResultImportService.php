<?php
namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * BGDResultImportService
 *
 * Xử lý import file Excel kết quả lọc ảo liên trường từ Bộ GD&ĐT.
 * Cập nhật cờ bi_loai_truong_khac trong v_calc_summary để loại thí sinh
 * đã trúng tuyển trường khác ra khỏi danh sách chính thức của HVU.
 */
class BGDResultImportService {
    protected $db;

    /** Mã trường của HVU trong hệ thống Bộ GD&ĐT */
    const MA_TRUONG_HVU = 'DKS';

    /** Mapping tên cột trong file Excel Bộ → index (0-based) */
    const COL_LAN_LOC_AO      = 1;  // Lần lọc ảo
    const COL_SBD              = 2;  // SBD
    const COL_HO_VA_TEN        = 3;  // Họ tên
    const COL_SO_CCCD          = 4;  // ĐDCN (Định danh cá nhân)
    const COL_NGAY_SINH        = 5;  // Ngày sinh
    const COL_GIOI_TINH        = 6;  // Giới tính
    const COL_MA_NGANH_HVU     = 7;  // Mã xét tuyển (tại HVU)
    const COL_TEN_NGANH_HVU    = 8;  // Tên mã xét tuyển
    const COL_THU_TU_NV        = 9;  // Thứ tự NV
    const COL_KET_QUA          = 10; // Kết quả: Trúng / Đổ
    const COL_TTNV_DO          = 11; // Thứ tự NV đỗ
    const COL_MA_TRUONG        = 12; // Mã trường trúng tuyển
    const COL_MA_NGANH_TRUNG   = 13; // Mã ngành tại trường trúng
    const COL_TEN_NGANH_TRUNG  = 14; // Tên ngành tại trường trúng

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Import file Excel kết quả từ Bộ GD&ĐT.
     *
     * @param string $filePath         Đường dẫn tạm thời của file upload (tmp_name)
     * @param int    $sessionId        ID đợt tuyển sinh
     * @param string $originalFilename Tên file gốc (dùng để lấy đúng extension .xls/.xlsx)
     * @param string $adminName        Tên admin thực hiện import (để ghi log)
     * @return array { success, total_rows, inserted, updated, bi_loai, giu_lai, errors[] }
     */
    public function importFromFile(string $filePath, int $sessionId, string $originalFilename = '', string $adminName = 'admin'): array {
        // Lấy extension từ tên file gốc (KHÔNG phải từ $filePath vì file upload lưu
        // dưới dạng /tmp/phpXXXXXX.tmp, pathinfo sẽ trả về 'tmp' sai)
        $ext = strtolower(pathinfo($originalFilename ?: $filePath, PATHINFO_EXTENSION));

        // Bắt mọi PHP warning/notice trước khi đọc file để tránh lấn vào JSON response
        ob_start();
        $rows = $this->loadRows($filePath, $ext);
        ob_end_clean();

        if ($rows === null) {
            return ['success' => false, 'message' => "Không thể đọc file Excel (.$ext). Kiểm tra lại file có đúng định dạng .xls hoặc .xlsx từ Bộ GD&ĐT không."];
        }

        // Bỏ qua header (dòng đầu tiên)
        $header = array_shift($rows);
        if (empty($rows)) {
            return ['success' => false, 'message' => 'File Excel không có dữ liệu (chỉ có header).'];
        }

        // Validate cấu trúc cột tối thiểu
        $validationError = $this->validateFileStructure($header);
        if ($validationError) {
            return ['success' => false, 'message' => $validationError];
        }

        // --- Bước 0: Load thông tin đối chiếu vào bộ nhớ ---
        // Tải toàn bộ CCCD của thí sinh đăng ký trong đợt này
        $stmtCccds = $this->db->prepare("
            SELECT DISTINCT so_cccd FROM nguyen_vong WHERE dot_tuyen_sinh_id = ?
        ");
        $stmtCccds->execute([$sessionId]);
        $registeredCccds = array_flip($stmtCccds->fetchAll(PDO::FETCH_COLUMN));

        // Tải toàn bộ nguyện vọng kèm kết quả trúng tuyển nội bộ của đợt này
        $stmtAsps = $this->db->prepare("
            SELECT nv.so_cccd, TRIM(nv.ma_nganh) as ma_nganh, nv.thu_tu_nguyen_vong, cs.trang_thai_trung_tuyen
            FROM nguyen_vong nv
            LEFT JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
            WHERE nv.dot_tuyen_sinh_id = ?
        ");
        $stmtAsps->execute([$sessionId]);
        $aspirationsMap = [];
        while ($r = $stmtAsps->fetch(PDO::FETCH_ASSOC)) {
            $key = $r['so_cccd'] . '_' . $r['ma_nganh'] . '_' . $r['thu_tu_nguyen_vong'];
            $aspirationsMap[$key] = (bool)$r['trang_thai_trung_tuyen'];
        }

        $stats = [
            'total_rows' => 0,
            'inserted'   => 0,
            'updated'    => 0,
            'bi_loai'    => 0,
            'giu_lai'    => 0,
            'errors'     => [],
            'report'     => []
        ];

        $this->db->beginTransaction();
        try {
            // --- Bước 1: Chuẩn bị dữ liệu từ file và Đối chiếu logic ---
            $validRows = [];
            foreach ($rows as $lineNum => $row) {
                $cccd = trim($row[self::COL_SO_CCCD] ?? '');
                if (empty($cccd)) continue;

                $lanLocAo   = trim($row[self::COL_LAN_LOC_AO]     ?? '');
                $sbd        = trim($row[self::COL_SBD]             ?? '');
                $hoVaTen    = trim($row[self::COL_HO_VA_TEN]       ?? '');
                $maNganhHvu = trim($row[self::COL_MA_NGANH_HVU]    ?? '');
                $thuTuNv    = intval($row[self::COL_THU_TU_NV]     ?? 0) ?: null;
                $ketQua     = trim($row[self::COL_KET_QUA]         ?? '');
                $ttnvDo     = intval($row[self::COL_TTNV_DO]       ?? 0) ?: null;
                $maTruong   = trim($row[self::COL_MA_TRUONG]        ?? '');
                $maNganhT   = trim($row[self::COL_MA_NGANH_TRUNG]   ?? '');
                $tenNganhT  = trim($row[self::COL_TEN_NGANH_TRUNG]  ?? '');

                // Logic đối chiếu
                $status = 'Thành công';
                $reason = 'Khớp dữ liệu';

                if (!isset($registeredCccds[$cccd])) {
                    $status = 'Thất bại';
                    $reason = 'Thí sinh không có trên hệ thống (không khớp CCCD)';
                } else {
                    if ($ketQua === 'Đỗ') {
                        // Đỗ tại HVU (ma_truong trống hoặc bằng DKS)
                        if (empty($maTruong) || $maTruong === self::MA_TRUONG_HVU) {
                            $aspKey = $cccd . '_' . $maNganhHvu . '_' . $thuTuNv;
                            if (!isset($aspirationsMap[$aspKey])) {
                                $status = 'Thất bại';
                                $reason = 'Nguyện vọng không có trên hệ thống của trường';
                            } else {
                                $isAdmitted = $aspirationsMap[$aspKey];
                                if (!$isAdmitted) {
                                    $status = 'Thất bại';
                                    $reason = 'Nguyện vọng trúng tuyển ở Bộ nhưng kết quả xét tuyển nội bộ không phải Đỗ';
                                } else {
                                    $reason = 'Thành công (Trúng tuyển chính thức tại HVU)';
                                }
                            }
                        } else {
                            // Trường hợp Bộ báo Đỗ nhưng lại ghi mã trường khác (lỗi logic file Bộ, thường Bộ báo Trượt ở HVU và Đỗ trường khác)
                            $status = 'Thất bại';
                            $reason = 'Trạng thái Đỗ nhưng mã trường trúng tuyển khác HVU: ' . $maTruong;
                        }
                    } elseif ($ketQua === 'Trượt') {
                        if (!empty($maTruong) && $maTruong !== self::MA_TRUONG_HVU) {
                            $reason = 'Thành công (Đã loại do trúng tuyển trường khác: ' . $maTruong . ')';
                        } else {
                            $reason = 'Thành công (Đã loại do Bộ báo trượt tất cả)';
                        }
                    } else {
                        $status = 'Thất bại';
                        $reason = 'Trạng thái kết quả từ Bộ không hợp lệ (yêu cầu Đỗ/Trượt). Giá trị đọc được: ' . $ketQua;
                    }
                }

                $validRows[] = [
                    'cccd'         => $cccd,
                    'lan_loc_ao'   => $lanLocAo,
                    'sbd'          => $sbd,
                    'ho_va_ten'    => $hoVaTen,
                    'ma_nganh_hvu' => $maNganhHvu,
                    'thu_tu_nv'    => $thuTuNv,
                    'ket_qua'      => $ketQua,
                    'ttnv_do'      => $ttnvDo,
                    'ma_truong'    => $maTruong,
                    'ma_nganh_t'   => $maNganhT,
                    'ten_nganh_t'  => $tenNganhT,
                ];

                $stats['report'][] = [
                    'Lần lọc ảo' => $lanLocAo,
                    'SBD' => $sbd,
                    'Họ và tên' => $hoVaTen,
                    'ĐDCN (CCCD)' => $cccd,
                    'Mã xét tuyển (HVU)' => $maNganhHvu,
                    'Thứ tự NV' => $thuTuNv,
                    'Kết quả từ Bộ' => $ketQua,
                    'Trường trúng tuyển' => $maTruong,
                    'Trạng thái kiểm tra' => $status,
                    'Lý do / Chi tiết kiểm tra' => $reason
                ];

                $stats['total_rows']++;
            }

            if (empty($validRows)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'File không có dòng dữ liệu hợp lệ (tất cả ĐDCN đều rỗng).'];
            }

            // --- Bước 2: Xóa dữ liệu cũ của đợt này (nếu có) rồi bulk INSERT ---
            // Dùng DELETE + INSERT thay vì UPSERT từng dòng để tối ưu hiệu năng
            $stmtDelete = $this->db->prepare(
                "DELETE FROM ket_qua_loc_ao_bo_gd WHERE dot_tuyen_sinh_id = ?"
            );
            $stmtDelete->execute([$sessionId]);

            // Bulk INSERT theo batch 200 dòng/lần
            // → Giảm từ ~5000 round-trips xuống còn ~25 round-trips
            $batchSize  = 200;
            $batches    = array_chunk($validRows, $batchSize);

            foreach ($batches as $batch) {
                $placeholders = implode(', ', array_fill(0, count($batch),
                    '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)'
                ));

                $sql = "INSERT INTO ket_qua_loc_ao_bo_gd
                            (dot_tuyen_sinh_id, lan_loc_ao, so_cccd, sbd, ho_va_ten,
                             ma_nganh_hvu, thu_tu_nv, ket_qua, ttnv_do,
                             ma_truong_trung_tuyen, ma_nganh_trung_tuyen, ten_nganh_trung_tuyen,
                             imported_at, imported_by)
                        VALUES $placeholders";

                $params = [];
                foreach ($batch as $r) {
                    $params[] = $sessionId;
                    $params[] = $r['lan_loc_ao'];
                    $params[] = $r['cccd'];
                    $params[] = $r['sbd'];
                    $params[] = $r['ho_va_ten'];
                    $params[] = $r['ma_nganh_hvu'];
                    $params[] = $r['thu_tu_nv'];
                    $params[] = $r['ket_qua'];
                    $params[] = $r['ttnv_do'];
                    $params[] = $r['ma_truong'];
                    $params[] = $r['ma_nganh_t'];
                    $params[] = $r['ten_nganh_t'];
                    $params[] = $adminName;
                }

                $this->db->prepare($sql)->execute($params);
                $stats['inserted'] += count($batch);
            }


            // --- Bước 2: Cập nhật cờ và kết quả bộ trong v_calc_summary ---
            // Reset tất cả cờ cũ của đợt này trước
            $stmtResetFlag = $this->db->prepare("
                UPDATE v_calc_summary cs
                SET bi_loai_truong_khac = FALSE,
                    ma_truong_trung_tuyen_bo = NULL,
                    ket_qua_bo_gd = NULL
                FROM nguyen_vong nv
                WHERE cs.nguyen_vong_id = nv.id
                  AND nv.dot_tuyen_sinh_id = ?
            ");
            $stmtResetFlag->execute([$sessionId]);

            // Mặc định gán kết quả 'Trượt' cho tất cả nguyện vọng tại HVU của các thí sinh có trong danh sách Bộ
            $stmtSetDefaultFail = $this->db->prepare("
                UPDATE v_calc_summary cs
                SET ket_qua_bo_gd = 'Trượt'
                FROM nguyen_vong nv
                JOIN ket_qua_loc_ao_bo_gd bgd
                    ON bgd.so_cccd = nv.so_cccd
                    AND bgd.dot_tuyen_sinh_id = nv.dot_tuyen_sinh_id
                WHERE cs.nguyen_vong_id = nv.id
                  AND nv.dot_tuyen_sinh_id = ?
            ");
            $stmtSetDefaultFail->execute([$sessionId]);

            // Cập nhật 'Đỗ' cho đúng nguyện vọng trúng tuyển tại HVU (ma_truong trống hoặc DKS)
            $stmtSetPassHVU = $this->db->prepare("
                UPDATE v_calc_summary cs
                SET ket_qua_bo_gd = 'Đỗ'
                FROM nguyen_vong nv
                JOIN ket_qua_loc_ao_bo_gd bgd
                    ON bgd.so_cccd = nv.so_cccd
                    AND bgd.dot_tuyen_sinh_id = nv.dot_tuyen_sinh_id
                WHERE cs.nguyen_vong_id = nv.id
                  AND nv.dot_tuyen_sinh_id = ?
                  AND bgd.ket_qua = 'Đỗ'
                  AND (bgd.ma_truong_trung_tuyen IS NULL OR TRIM(bgd.ma_truong_trung_tuyen) = '' OR TRIM(bgd.ma_truong_trung_tuyen) = ?)
                  AND TRIM(nv.ma_nganh) = TRIM(bgd.ma_nganh_hvu)
                  AND nv.thu_tu_nguyen_vong = bgd.thu_tu_nv
            ");
            $stmtSetPassHVU->execute([$sessionId, self::MA_TRUONG_HVU]);

            // Đánh cờ những thí sinh đã trúng tuyển trường KHÁC HVU (bi_loai_truong_khac = TRUE)
            // Điều kiện: ket_qua = 'Trượt' VÀ ma_truong_trung_tuyen ≠ 'DKS' (và khác trống)
            $stmtSetFlag = $this->db->prepare("
                UPDATE v_calc_summary cs
                SET bi_loai_truong_khac = TRUE,
                    ma_truong_trung_tuyen_bo = bgd.ma_truong_trung_tuyen
                FROM nguyen_vong nv
                JOIN ket_qua_loc_ao_bo_gd bgd
                    ON bgd.so_cccd = nv.so_cccd
                    AND bgd.dot_tuyen_sinh_id = nv.dot_tuyen_sinh_id
                WHERE cs.nguyen_vong_id = nv.id
                  AND nv.dot_tuyen_sinh_id = ?
                  AND cs.trang_thai_trung_tuyen = TRUE
                  AND bgd.ket_qua = 'Trượt'
                  AND bgd.ma_truong_trung_tuyen IS NOT NULL
                  AND TRIM(bgd.ma_truong_trung_tuyen) <> ''
                  AND TRIM(bgd.ma_truong_trung_tuyen) <> ?
            ");
            $stmtSetFlag->execute([$sessionId, self::MA_TRUONG_HVU]);

            $this->db->commit();

            // Đếm số bị loại (Bộ báo Trượt trên nguyện vọng trúng tuyển nội bộ)
            $stmtBiLoai = $this->db->prepare("
                SELECT COUNT(*) FROM v_calc_summary cs
                JOIN nguyen_vong nv ON cs.nguyen_vong_id = nv.id
                WHERE nv.dot_tuyen_sinh_id = ?
                  AND cs.trang_thai_trung_tuyen = TRUE
                  AND cs.ket_qua_bo_gd = 'Trượt'
            ");
            $stmtBiLoai->execute([$sessionId]);
            $stats['bi_loai'] = (int)$stmtBiLoai->fetchColumn();

            // Đếm số giữ lại (Bộ báo Đỗ trên nguyện vọng trúng tuyển nội bộ)
            $stmtGiuLai = $this->db->prepare("
                SELECT COUNT(*) FROM v_calc_summary cs
                JOIN nguyen_vong nv ON cs.nguyen_vong_id = nv.id
                WHERE nv.dot_tuyen_sinh_id = ?
                  AND cs.trang_thai_trung_tuyen = TRUE
                  AND cs.ket_qua_bo_gd = 'Đỗ'
            ");
            $stmtGiuLai->execute([$sessionId]);
            $stats['giu_lai'] = (int)$stmtGiuLai->fetchColumn();

            $stats['success'] = true;
            return $stats;

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("BGDResultImportService Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }

    /**
     * Lấy trạng thái import hiện tại của một đợt (đã import chưa, thống kê).
     *
     * @param int $sessionId
     * @return array
     */
    public function getImportStatus(int $sessionId): array {
        $intSessionId = (int)$sessionId;
        // Kiểm tra đã import chưa
        $stmtCheck = $this->db->query("
            SELECT COUNT(*) as tong,
                   MAX(imported_at) as lan_import_cuoi,
                   MAX(lan_loc_ao) as lan_loc_ao,
                   MAX(imported_by) as imported_by
            FROM ket_qua_loc_ao_bo_gd
            WHERE dot_tuyen_sinh_id = $intSessionId
        ");
        $info = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (empty($info['tong']) || (int)$info['tong'] === 0) {
            return ['imported' => false];
        }

        // Đếm số bị loại (Bộ báo Trượt trên nguyện vọng trúng tuyển nội bộ) - Optimized
        $stmtBiLoai = $this->db->query("
            SELECT COUNT(*) FROM v_calc_summary cs
            JOIN nguyen_vong nv ON cs.nguyen_vong_id = nv.id
            WHERE nv.dot_tuyen_sinh_id = $intSessionId
              AND cs.dot_tuyen_sinh_id = $intSessionId
              AND cs.trang_thai_trung_tuyen = TRUE
              AND cs.ket_qua_bo_gd = 'Trượt'
        ");
        $biLoai = (int)$stmtBiLoai->fetchColumn();

        // Đếm số giữ lại (Bộ báo Đỗ trên nguyện vọng trúng tuyển nội bộ) - Optimized
        $stmtGiuLai = $this->db->query("
            SELECT COUNT(*) FROM v_calc_summary cs
            JOIN nguyen_vong nv ON cs.nguyen_vong_id = nv.id
            WHERE nv.dot_tuyen_sinh_id = $intSessionId
              AND cs.dot_tuyen_sinh_id = $intSessionId
              AND cs.trang_thai_trung_tuyen = TRUE
              AND cs.ket_qua_bo_gd = 'Đỗ'
        ");
        $giuLai = (int)$stmtGiuLai->fetchColumn();

        return [
            'imported'       => true,
            'tong_bo_gd'     => (int)$info['tong'],
            'lan_loc_ao'     => $info['lan_loc_ao'],
            'lan_import_cuoi'=> $info['lan_import_cuoi'],
            'imported_by'    => $info['imported_by'],
            'bi_loai'        => $biLoai,
            'giu_lai'        => $giuLai,
        ];
    }

    /**
     * Đọc rows từ file Excel, hỗ trợ cả .xls (Excel 97-2003) và .xlsx (Excel 2007+)
     *
     * @param string $filePath  Đường dẫn tạm (tmp_name)
     * @param string $ext       Extension gốc của file (xls | xlsx) — lấy từ tên file gốc
     * @return array|null       Rows nếu thành công, null nếu không đọc được
     */
    private function loadRows(string $filePath, string $ext = 'xls'): ?array {
        if ($ext === 'xls') {
            require_once __DIR__ . '/SimpleXLS.php';
            $xls = \Shuchkin\SimpleXLS::parse($filePath);
            if (!$xls) {
                error_log('BGDImport SimpleXLS error: ' . \Shuchkin\SimpleXLS::parseError());
                return null;
            }
            return $xls->rows();
        }

        if ($ext === 'xlsx') {
            require_once __DIR__ . '/SimpleXLSX.php';
            $xlsx = \Shuchkin\SimpleXLSX::parse($filePath);
            if (!$xlsx) {
                error_log('BGDImport SimpleXLSX error: ' . \Shuchkin\SimpleXLSX::parseError());
                return null;
            }
            return $xlsx->rows();
        }

        error_log("BGDImport: Unsupported extension '$ext'");
        return null;
    }

    /**
     * Validate cấu trúc header file Excel.
     * Kiểm tra các cột bắt buộc có tồn tại không.
     */
    private function validateFileStructure(array $header): ?string {
        // Kiểm tra số cột tối thiểu
        if (count($header) < 11) {
            return 'File Excel không đủ cột. Cần ít nhất 11 cột theo đúng format của Bộ GD&ĐT.';
        }

        // Kiểm tra cột ĐDCN (cột E, index 4) - cột quan trọng nhất
        $cccdHeader = mb_strtolower(trim($header[self::COL_SO_CCCD] ?? ''));
        if (!str_contains($cccdHeader, 'đdcn') && !str_contains($cccdHeader, 'ddcn')
            && !str_contains($cccdHeader, 'cccd') && !str_contains($cccdHeader, 'định danh')) {
            return "Không tìm thấy cột ĐDCN ở vị trí cột E (index 4). Header tìm thấy: \"" . ($header[self::COL_SO_CCCD] ?? 'trống') . "\". Vui lòng kiểm tra đúng file Bộ GD&ĐT.";
        }

        // Kiểm tra cột Kết quả (cột K, index 10)
        $ketQuaHeader = mb_strtolower(trim($header[self::COL_KET_QUA] ?? ''));
        if (!str_contains($ketQuaHeader, 'kết quả') && !str_contains($ketQuaHeader, 'ket qua')) {
            return "Không tìm thấy cột 'Kết quả' ở vị trí cột K (index 10). Header tìm thấy: \"" . ($header[self::COL_KET_QUA] ?? 'trống') . "\".";
        }

        return null;
    }
}
