<?php
namespace App\Services;

use App\Core\Database;

class ExportService {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Format a date string to dd/mm/yyyy. Returns '' if empty/invalid.
     */
    private function formatDate(?string $value): string {
        if (empty($value)) return '';
        $ts = strtotime($value);
        return $ts ? date('d/m/Y', $ts) : $value;
    }

    /**
     * Prefix CCCD/ĐDCN with a tab character so Excel treats it as text
     * and does NOT strip the leading zero.
     * Note: In toExcel(), we strip this tab and apply mso-number-format:"\@".
     */
    private function textCell(?string $value): string {
        if ($value === null || $value === '') return '';
        // In the new toExcel(), we'll use <Data ss:Type="String"> to handle this
        return (string)$value;
    }

    /**
     * Format Area (Khu vực) to standard notation (KV1, KV2, KV2-NT, KV3)
     */
    private function formatArea($value): string {
        if ($value === null || $value === '') return 'KV3'; // Default to KV3 if empty
        $val = trim((string)$value);
        $map = [
            '1' => 'KV1',
            '2' => 'KV2',
            '2NT' => 'KV2-NT',
            '3' => 'KV3',
            'KV1' => 'KV1',
            'KV2' => 'KV2',
            'KV2-NT' => 'KV2-NT',
            'KV2NT' => 'KV2-NT',
            'KV3' => 'KV3'
        ];
        return $map[$val] ?? $val;
    }

    /**
     * Format Priority Object (Đối tượng) to 2-digit notation (01, 02, etc.)
     */
    private function formatObject($value): string {
        if ($value === null || $value === '' || $value == '0') return '';
        $val = trim((string)$value);
        // If it's a single digit 1-9, prefix with 0
        if (preg_match('/^[1-9]$/', $val)) {
            return '0' . $val;
        }
        return $val;
    }

    /**
     * Format decimal score to 3 decimal places.
     * Returns raw string with dot so toExcel can treat it as Number.
     */
    private function formatDecimal($value): string {
        if ($value === null || $value === '' || $value === false) return '';
        return number_format((float)$value, 3, '.', '');
    }

    /**
     * Normalize Academic labels (Học lực/Hạnh kiểm) to standard Vietnamese.
     */
    private function normalizeAcademic(?string $value): string {
        if (empty($value)) return '';
        $val = trim((string)$value);
        $map = [
            'Tot' => 'Tốt', 'TOT' => 'Tốt', 'TỐT' => 'Tốt',
            'Kha' => 'Khá', 'KHA' => 'Khá', 'KHÁ' => 'Khá',
            'Trung binh' => 'Trung bình', 'TB' => 'Trung bình',
            'Yeu' => 'Yếu', 'Kem' => 'Kém',
            'Gioi' => 'Giỏi', 'GIOI' => 'Giỏi', 'GIỎI' => 'Giỏi',
            'Dat' => 'Đạt', 'DAT' => 'Đạt', 'ĐẠT' => 'Đạt'
        ];
        
        $upper = mb_strtoupper($val, 'UTF-8');
        foreach ($map as $k => $v) {
            if (mb_strtoupper($k, 'UTF-8') === $upper) return $v;
        }
        return $val;
    }

    // ----------------------------------------------------------------
    // Basic Exports
    // ----------------------------------------------------------------

    public function exportCandidatesToCsv($filters = []) {
        $sql = "SELECT t.so_cccd AS \"Số CCCD\",
                       t.ho_va_ten AS \"Họ và Tên\",
                       t.ngay_sinh AS \"Ngày Sinh\",
                       t.gioi_tinh AS \"Giới tính\",
                       t.dien_thoai AS \"Điện thoại\",
                       t.email AS \"Email\",
                       t.dan_toc AS \"Dân tộc\",
                       tinh_hk.ten_tinh AS \"Hộ khẩu\",
                       xa.ten_xa AS \"Xã/Phường\",
                       p.ten_tinh as \"Tỉnh/Thành phố\",
                       truong.ten_truong AS \"Trường THPT\",
                       t.khu_vuc_uu_tien AS \"Khu vực ƯT\",
                       t.doi_tuong_uu_tien AS \"Đối tượng ƯT\",
                       t.nam_tot_nghiep AS \"Năm TN\",
                       (SELECT 
                            CASE 
                                WHEN COUNT(*) = 0 THEN 'not_entered'
                                WHEN COUNT(*) FILTER (WHERE lop = 12) = 0 AND COUNT(*) FILTER (WHERE lop IN (10, 11)) > 0 THEN 'missing_12'
                                WHEN COUNT(DISTINCT lop) >= 3 THEN 'full'
                                ELSE 'partial'
                            END
                        FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd) as \"Học bạ\",
                       qtv.ho_ten AS \"Người duyệt\",
                       (SELECT dth.diem_xet_tot_nghiep FROM diem_thi_thpt dth WHERE dth.so_cccd = t.so_cccd LIMIT 1) AS \"Điểm tốt nghiệp\",
                       (SELECT hb.diem_tb_ca_nam FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 LIMIT 1) AS \"TB chung L12\",
                       (SELECT hb.hoc_luc_ca_nam FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 LIMIT 1) AS \"Học lực L12\",
                       (SELECT hb.hanh_kiem_ca_nam FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 LIMIT 1) AS \"Hạnh kiểm L12\",
                       h.trang_thai AS \"Trạng thái hồ sơ\",
                       h.ghi_chu AS \"Ghi chú\",
                       h.dot_tuyen_sinh_id AS \"Mã đợt tuyển sinh\"
                FROM thi_sinh t
                LEFT JOIN dm_tinh p ON t.ma_tinh_thuong_tru = p.ma_tinh
                LEFT JOIN dm_xa xa ON t.ma_xa_thuong_tru = xa.ma_xa
                LEFT JOIN dm_tinh tinh_hk ON t.ma_tinh_ho_khau = tinh_hk.ma_tinh
                LEFT JOIN dm_truong_thpt truong ON t.ma_truong_lop_12 = truong.ma_truong
                LEFT JOIN ho_so_xet_tuyen h ON t.so_cccd = h.so_cccd
                LEFT JOIN quan_tri_vien qtv ON h.nguoi_duyet_id = qtv.id
                WHERE 1=1";

        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND h.trang_thai = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['session_id'])) {
            $sql .= " AND h.dot_tuyen_sinh_id = ?";
            $params[] = $filters['session_id'];
        }
        $sql .= " ORDER BY t.ho_va_ten";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Normalize
        foreach ($rows as &$r) {
            $r["Số CCCD"]   = $this->textCell($r["Số CCCD"]);
            $r["Họ và Tên"]  = mb_strtoupper($r["Họ và Tên"] ?? '', 'UTF-8');
            $r["Điện thoại"] = $this->textCell($r["Điện thoại"]);
            $r["Ngày Sinh"]  = $this->formatDate($r["Ngày Sinh"]);
            $r["Khu vực ƯT"] = $this->formatArea($r["Khu vực ƯT"]);
            $r["Đối tượng ƯT"] = $this->formatObject($r["Đối tượng ƯT"]);
            
            // Format "Học bạ" status text
            $tStatus = $r["Học bạ"] ?? 'not_entered';
            if ($tStatus === 'full') $r["Học bạ"] = 'Đủ 3 năm';
            elseif ($tStatus === 'missing_12') $r["Học bạ"] = 'Thiếu lớp 12';
            elseif ($r["Học bạ"] === 'not_entered') $r["Học bạ"] = 'Chưa nhập';
            else $r["Học bạ"] = 'Chưa đủ';

            // Format decimal scores
            $r["Điểm tốt nghiệp"] = $this->formatDecimal($r["Điểm tốt nghiệp"]);
            $r["TB chung L12"] = $this->formatDecimal($r["TB chung L12"]);

            // Normalize academic descriptions
            $r["Học lực L12"] = $this->normalizeAcademic($r["Học lực L12"]);
            $r["Hạnh kiểm L12"] = $this->normalizeAcademic($r["Hạnh kiểm L12"]);
        }
        return $rows;
    }

    public function exportAdmittedByMajor($maNganh, $filters = []) {
        $sql = "SELECT t.so_cccd AS \"Số CCCD\",
                       t.ho_va_ten AS \"Họ và Tên\",
                       t.ngay_sinh AS \"Ngày Sinh\",
                       t.gioi_tinh AS \"Giới tính\",
                       t.dan_toc AS \"Dân tộc\",
                       t.dien_thoai AS \"Điện thoại\",
                       t.email AS \"Email\",
                       t.dia_chi_chi_tiet AS \"Địa chỉ chi tiết\",
                       xa.ten_xa AS \"Xã/Phường\",
                       tinh.ten_tinh AS \"Tỉnh/Thành phố\",
                       truong.ten_truong AS \"Trường THPT\",
                       t.khu_vuc_uu_tien AS \"Khu vực\",
                       t.doi_tuong_uu_tien AS \"Đối tượng\",
                       nv.ma_nganh AS \"Mã Ngành Trúng Tuyển\",
                       n.ten_nganh AS \"Tên Ngành\",
                       nv.to_hop_toi_uu AS \"Tổ Hợp Tối Ưu\",
                       nv.diem_xet_tuyen AS \"Điểm Xét Tuyển\",
                       nv.phuong_thuc_toi_uu AS \"Mã Phương Thức\",
                       h.trang_thai AS \"Trạng thái hồ sơ\",
                       h.ghi_chu AS \"Ghi chú\",
                       n.co_xet_chung_chi,
                       n.co_diem_nangkhieu_thpt,
                       n.co_diem_nangkhieu_hochba,
                       EXISTS (SELECT 1 FROM diem_chung_chi d WHERE d.so_cccd = t.so_cccd) AS co_chung_chi_chuan
                FROM nguyen_vong nv
                JOIN thi_sinh t ON nv.so_cccd = t.so_cccd
                JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh
                LEFT JOIN dm_xa xa ON t.ma_xa_thuong_tru = xa.ma_xa
                LEFT JOIN dm_tinh tinh ON t.ma_tinh_thuong_tru = tinh.ma_tinh
                LEFT JOIN dm_truong_thpt truong ON t.ma_truong_lop_12 = truong.ma_truong
                JOIN ho_so_xet_tuyen h ON t.so_cccd = h.so_cccd
                WHERE 1=1 AND (nv.trang_thai_trung_tuyen = TRUE OR nv.trang_thai = 'Trung tuyen' OR nv.trang_thai = 'Trúng tuyển')";

        $params = [];
        if (!empty($maNganh)) {
            $sql .= " AND nv.ma_nganh = ?";
            $params[] = $maNganh;
        }

        if (!empty($filters['session_id'])) {
            $sql .= " AND nv.dot_tuyen_sinh_id = ?";
            $params[] = $filters['session_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND h.trang_thai = ?";
            $params[] = $filters['status'];
        }
        $sql .= " ORDER BY nv.ma_nganh, nv.diem_xet_tuyen DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r["Số CCCD"]   = $this->textCell($r["Số CCCD"]);
            $r["Họ và Tên"]  = mb_strtoupper($r["Họ và Tên"] ?? '', 'UTF-8');
            $r["Điện thoại"] = $this->textCell($r["Điện thoại"]);
            $r["Ngày Sinh"]  = $this->formatDate($r["Ngày Sinh"]);
            $r["Khu vực"]    = $this->formatArea($r["Khu vực"]);
            $r["Đối tượng"]  = $this->formatObject($r["Đối tượng"]);
            $r["Mã Phương Thức"] = \App\Helpers\AdmissionMethodHelper::resolvePhuongThuc($r["Mã Phương Thức"], [
                'co_xet_chung_chi' => $r['co_xet_chung_chi'],
                'co_diem_nangkhieu_thpt' => $r['co_diem_nangkhieu_thpt'],
                'co_diem_nangkhieu_hochba' => $r['co_diem_nangkhieu_hochba'],
            ], !empty($r['co_chung_chi_chuan']));
            unset($r['co_xet_chung_chi'], $r['co_diem_nangkhieu_thpt'], $r['co_diem_nangkhieu_hochba'], $r['co_chung_chi_chuan']);
        }
        return $rows;
    }

    /**
     * Xuất toàn bộ dữ liệu xét tuyển (nguyện vọng) trong đợt.
     */
    public function exportAdmissionData($filters = []) {
        $sql = "SELECT t.so_cccd AS \"Số CCCD\",
                       t.ho_va_ten AS \"Họ và Tên\",
                       t.ngay_sinh AS \"Ngày Sinh\",
                       t.gioi_tinh AS \"Giới tính\",
                       t.dien_thoai AS \"Điện thoại\",
                       COALESCE(nv.thu_tu_nv_bo, nv.thu_tu_nguyen_vong) AS \"Thứ tự NV\",
                       nv.ma_nganh AS \"Mã Ngành\",
                       n.ten_nganh AS \"Tên Ngành\",
                       nv.to_hop_toi_uu AS \"Tổ hợp\",
                       nv.diem_xet_tuyen AS \"Điểm xét tuyển\",
                       nv.trang_thai AS \"Trạng thái xét tuyển\",
                       h.trang_thai AS \"Trạng thái hồ sơ\",
                       h.ghi_chu AS \"Ghi chú\"
                FROM nguyen_vong nv
                JOIN thi_sinh t ON nv.so_cccd = t.so_cccd
                JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh
                LEFT JOIN ho_so_xet_tuyen h ON t.so_cccd = h.so_cccd AND nv.dot_tuyen_sinh_id = h.dot_tuyen_sinh_id
                WHERE 1=1";

        $params = [];
        if (!empty($filters['session_id'])) {
            $sql .= " AND nv.dot_tuyen_sinh_id = ?";
            $params[] = $filters['session_id'];
        }
        if (!empty($filters['major'])) {
            $sql .= " AND nv.ma_nganh = ?";
            $params[] = $filters['major'];
        }
        
        $sql .= " ORDER BY t.ho_va_ten, COALESCE(nv.thu_tu_nv_bo, nv.thu_tu_nguyen_vong)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r["Số CCCD"]   = $this->textCell($r["Số CCCD"]);
            $r["Họ và Tên"]  = mb_strtoupper($r["Họ và Tên"] ?? '', 'UTF-8');
            $r["Điện thoại"] = $this->textCell($r["Điện thoại"]);
            $r["Ngày Sinh"]  = $this->formatDate($r["Ngày Sinh"]);
            $r["Điểm xét tuyển"] = $this->formatDecimal($r["Điểm xét tuyển"]);
        }
        return $rows;
    }

    /**
     * Danh sách thí sinh có chứng chỉ ngoại ngữ, filtered by session.
     */
    public function exportCertificatesFiltered($filters = []) {
        $sql = "SELECT t.so_cccd AS \"Số CCCD\",
                       t.ho_va_ten AS \"Họ và Tên\",
                       t.ngay_sinh AS \"Ngày Sinh\",
                       t.dien_thoai AS \"Điện thoại\",
                       cc.loai_chung_chi AS \"Loại chứng chỉ\",
                       cc.diem_chung_chi AS \"Điểm/Xếp loại\",
                       CASE WHEN EXISTS (
                           SELECT 1 FROM nguyen_vong nv 
                           WHERE nv.so_cccd = t.so_cccd 
                             AND nv.dot_tuyen_sinh_id = h.dot_tuyen_sinh_id
                             AND nv.ma_nganh IN ('7140231', '7220201', '7220204')
                       ) THEN 'Có' ELSE 'Không' END AS \"Đăng ký ngành xét CC\",
                       h.trang_thai AS \"Trạng thái hồ sơ\",
                       h.ghi_chu AS \"Ghi chú\",
                       cc.file_minh_chung_cc
                FROM chung_chi_thi_sinh cc
                JOIN thi_sinh t ON cc.so_cccd = t.so_cccd
                JOIN ho_so_xet_tuyen h ON t.so_cccd = h.so_cccd
                WHERE 1=1";

        $params = [];
        if (!empty($filters['session_id'])) {
            $sql .= " AND h.dot_tuyen_sinh_id = ?";
            $params[] = $filters['session_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND h.trang_thai = ?";
            $params[] = $filters['status'];
        }
        $sql .= " ORDER BY t.ho_va_ten, cc.loai_chung_chi";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r["Số CCCD"]  = $this->textCell($r["Số CCCD"]);
            $r["Họ và Tên"] = mb_strtoupper($r["Họ và Tên"] ?? '', 'UTF-8');
            $r["Điện thoại"] = $this->textCell($r["Điện thoại"] ?? '');
            $r["Ngày Sinh"] = $this->formatDate($r["Ngày Sinh"]);
        }
        return $rows;
    }

    /**
     * Legacy (no session filter) – kept for backward compat.
     */
    public function exportCertificates() {
        return $this->exportCertificatesFiltered([]);
    }

    /**
     * Danh sách thí sinh đăng ký thi năng khiếu.
     */
    public function exportAptitudeList($filters = []) {
        // Identify aptitude candidates:
        // 1. Have a wish in a major belonging to 'SuPhamDacThu' group
        // 2. OR already have a score in 'diem_nang_khieu'
        $sql = "SELECT DISTINCT t.so_cccd AS \"Số CCCD\",
                       t.ho_va_ten AS \"Họ và Tên\",
                       t.ngay_sinh AS \"Ngày Sinh\",
                       t.gioi_tinh AS \"Giới tính\",
                       t.dien_thoai AS \"Điện thoại\",
                       t.email AS \"Email\",
                       n.ma_nganh AS \"Mã ngành\",
                       n.ten_nganh AS \"Tên ngành\",
                       nv.thu_tu_nguyen_vong AS \"Thứ tự NV\",
                       h.trang_thai AS \"Trạng thái hồ sơ\",
                       h.ghi_chu AS \"Ghi chú\",
                       t.anh_dai_dien
                FROM thi_sinh t
                JOIN ho_so_xet_tuyen h ON t.so_cccd = h.so_cccd
                JOIN nguyen_vong nv ON t.so_cccd = nv.so_cccd AND h.dot_tuyen_sinh_id = nv.dot_tuyen_sinh_id
                JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh
                WHERE (n.ma_nganh IN ('7140201', '7140206', '7140221', '7140222') OR t.so_cccd IN (SELECT DISTINCT so_cccd FROM diem_nang_khieu))";

        $params = [];
        if (!empty($filters['session_id'])) {
            $sql .= " AND h.dot_tuyen_sinh_id = ?";
            $params[] = $filters['session_id'];
        }
        $sql .= " ORDER BY t.ho_va_ten";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r["Số CCCD"]  = $this->textCell($r["Số CCCD"]);
            $r["Họ và Tên"] = mb_strtoupper($r["Họ và Tên"] ?? '', 'UTF-8');
            $r["Ngày Sinh"] = $this->formatDate($r["Ngày Sinh"]);
        }
        return $rows;
    }

    // ----------------------------------------------------------------
    // Statistics
    // ----------------------------------------------------------------

    public function getStatistics($filters = []) {
        $stats = [];
        $sessionId = $filters['session_id'] ?? null;
        $where = $sessionId ? " WHERE dot_tuyen_sinh_id = ?" : "";
        $whereNv = $sessionId ? " AND nv.dot_tuyen_sinh_id = ?" : "";
        $params = $sessionId ? [$sessionId] : [];

        // By Status
        $sqlStatus = "SELECT trang_thai, COUNT(*) as count FROM ho_so_xet_tuyen" . $where . " GROUP BY trang_thai";
        $stmt = $this->db->prepare($sqlStatus);
        $stmt->execute($params);
        $stats['by_status'] = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        // By Major (Top 10)
        $sqlMajor = "SELECT n.ten_nganh, COUNT(*) as count 
                     FROM nguyen_vong nv 
                     JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh 
                     WHERE nv.thu_tu_nguyen_vong = 1" . $whereNv . " 
                     GROUP BY n.ten_nganh 
                     ORDER BY count DESC LIMIT 10";
        $stmt = $this->db->prepare($sqlMajor);
        $stmt->execute($params);
        $stats['by_major'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // By Date (Last 14 days)
        $sqlDate = "SELECT DATE(created_at) as date, COUNT(*) as count 
                    FROM ho_so_xet_tuyen 
                    WHERE created_at > NOW() - INTERVAL '14 days'" . ($sessionId ? " AND dot_tuyen_sinh_id = ?" : "") . " 
                    GROUP BY DATE(created_at) ORDER BY date";
        $stmt = $this->db->prepare($sqlDate);
        $stmt->execute($params);
        $stats['by_date'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Total
        $sqlTotal = "SELECT COUNT(*) FROM ho_so_xet_tuyen" . $where;
        $stmt = $this->db->prepare($sqlTotal);
        $stmt->execute($params);
        $stats['total_candidates'] = $stmt->fetchColumn();

        // Approved
        $sqlApproved = "SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE trang_thai = 'Đã duyệt'" . ($sessionId ? " AND dot_tuyen_sinh_id = ?" : "");
        $stmt = $this->db->prepare($sqlApproved);
        $stmt->execute($params);
        $stats['total_approved'] = $stmt->fetchColumn();

        return $stats;
    }

    // ----------------------------------------------------------------
    // MOET Exports
    // ----------------------------------------------------------------

    public function exportMoetInfoCsv($filters = []) {
        $sql = "SELECT t.*, p.ten_tinh as ten_tinh_tt,
                       x.ten_xa as ten_xa_tt, tr.ten_truong as ten_truong_thpt,
                       kq.diem_tb_ca_nam as diem_tb_12,
                       kq.hoc_luc_ca_nam as hoc_luc_12,
                       kq.hanh_kiem_ca_nam as hanh_kiem_12,
                       dt.toan as dt_toan, dt.van as dt_van, dt.ly as dt_ly, dt.hoa as dt_hoa,
                       dt.sinh as dt_sinh, dt.su as dt_su, dt.dia as dt_dia, dt.gdcd as dt_gdcd,
                       dt.tieng_anh as dt_anh, dt.tieng_trung as dt_trung, dt.ktpl as dt_ktpl,
                       dt.tin_hoc as dt_tin, dt.cnnn as dt_cnnn, hs.created_at,
                       hs.trang_thai, hs.ghi_chu
                FROM thi_sinh t
                LEFT JOIN dm_tinh p ON t.ma_tinh_thuong_tru = p.ma_tinh
                LEFT JOIN dm_xa x ON t.ma_xa_thuong_tru = x.ma_xa
                LEFT JOIN dm_truong_thpt tr ON t.ma_truong_lop_12 = tr.ma_truong
                JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd
                LEFT JOIN ket_qua_hoc_tap kq ON t.so_cccd = kq.so_cccd AND kq.lop = 12
                LEFT JOIN diem_thi_thpt dt ON t.so_cccd = dt.so_cccd
                WHERE 1=1";

        $params = [];
        if (!empty($filters['session_id'])) {
            $sql .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $filters['session_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND hs.trang_thai = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY t.ho_va_ten";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $data = [];
        $stt = 1;
        foreach ($candidates as $c) {
            $maMonNgoaiNgu = '';
            $diemNgoaiNgu = '';
            if ($c['dt_anh'] !== null) {
                $maMonNgoaiNgu = 'N1';
                $diemNgoaiNgu = $c['dt_anh'];
            } elseif ($c['dt_trung'] !== null) {
                $maMonNgoaiNgu = 'N4';
                $diemNgoaiNgu = $c['dt_trung'];
            }

            $hocLuc = $this->normalizeAcademic($c['hoc_luc_12']);
            $hanhKiem = $this->normalizeAcademic($c['hanh_kiem_12']);

            $data[] = [
                'STT'                               => (string)$stt++,
                'SBD'                               => '',
                'Họ Tên'                            => mb_strtoupper($c['ho_va_ten'], 'UTF-8'),
                'ĐDCN'                              => $this->textCell($c['so_cccd'] ?? ''),
                'Ngày sinh'                         => $this->formatDate($c['ngay_sinh']),
                'Giới tính'                         => $c['gioi_tinh'],
                'ĐTƯT'                              => $this->formatObject($c['doi_tuong_uu_tien']),
                'KVƯT'                              => $this->formatArea($c['khu_vuc_uu_tien']),
                'Năm TN THPT'                       => (string)$c['nam_tot_nghiep'],
                'Học lực/Kết quả học tập'           => $hocLuc,
                'Hạnh kiểm/Kết quả rèn luyện'       => $hanhKiem,
                'Điểm TB Lớp 12/Điểm TB các năm học' => $this->formatDecimal($c['diem_tb_12']),
                'TN Cao Đẳng'                       => '',
                'TN Trung Cấp'                      => '',
                'Nơi thường trú - Mã tỉnh'          => (string)$c['ma_tinh_thuong_tru'],
                'Nơi thường trú - Tên tỉnh'         => $c['ten_tinh_tt'],
                'Nơi thường trú - Mã Quận huyện'    => (string)($c['ma_huyen_thuong_tru'] ?? ''),
                'Nơi thường trú - Tên Quận huyện'   => '',
                'Nơi thường trú - Mã xã phường'     => (string)$c['ma_xa_thuong_tru'],
                'Nơi thường trú - Tên xã phường'    => $c['ten_xa_tt'],
                'Mã tỉnh lớp 12'                    => (string)$c['ma_tinh_lop_12'],
                'Mã trường lớp 12'                  => (string)$c['ma_truong_lop_12'],
                'KQ Sơ Tuyển'                       => '',
                'TO'                                => $this->formatDecimal($c['dt_toan']),
                'VA'                                => $this->formatDecimal($c['dt_van']),
                'LI'                                => $this->formatDecimal($c['dt_ly']),
                'HO'                                => $this->formatDecimal($c['dt_hoa']),
                'SI'                                => $this->formatDecimal($c['dt_sinh']),
                'SU'                                => $this->formatDecimal($c['dt_su']),
                'DI'                                => $this->formatDecimal($c['dt_dia']),
                'GDCD'                              => $this->formatDecimal($c['dt_gdcd']),
                'NN'                                => $this->formatDecimal($diemNgoaiNgu),
                'Mã môn NN'                         => $maMonNgoaiNgu,
                'KTPL'                              => $this->formatDecimal($c['dt_ktpl']),
                'TI'                                => $this->formatDecimal($c['dt_tin']),
                'CNCN'                              => '', 
                'CNNN'                              => $this->formatDecimal($c['dt_cnnn']),
                'Chương trình học'                  => '',
                'NK1'                               => '', 'NK2' => '', 'NK3' => '', 'NK4' => '', 'NK5' => '',
                'NK6'                               => '', 'NK7' => '', 'NK8' => '', 'NK9' => '', 'NK10' => '',
                'Điểm xét tốt nghiệp'               => '',
                'Người tạo'                         => 'Hệ thống',
                'Ngày tạo'                          => !empty($c['created_at']) ? date('d/m/Y', strtotime($c['created_at'])) : date('d/m/Y'),
                'Trạng thái hồ sơ'                  => $c['trang_thai'],
                'Ghi chú'                           => $c['ghi_chu'],
                'Dân tộc'                           => $c['dan_toc'],
                'Mã dân tộc'                        => '',
                'Nơi sinh'                          => '',
            ];
        }
        return $data;
    }

    public function exportMoetWishesCsv($filters = []) {
        $sql = "SELECT nv.*, n.ten_nganh, n.co_xet_chung_chi, n.co_diem_nangkhieu_thpt, n.co_diem_nangkhieu_hochba, hs.trang_thai, hs.ghi_chu,
                       EXISTS (SELECT 1 FROM diem_chung_chi d WHERE d.so_cccd = nv.so_cccd) AS co_chung_chi_chuan
                FROM nguyen_vong nv
                JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh
                JOIN ho_so_xet_tuyen hs ON nv.so_cccd = hs.so_cccd AND nv.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id
                WHERE 1=1";

        $params = [];
        if (!empty($filters['session_id'])) {
            $sql .= " AND nv.dot_tuyen_sinh_id = ?";
            $params[] = $filters['session_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND hs.trang_thai = ?";
            $params[] = $filters['status'];
        }
        $sql .= " ORDER BY nv.so_cccd, COALESCE(nv.thu_tu_nv_bo, nv.thu_tu_nguyen_vong)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $wishes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $data = [];
        $stt = 1;
        foreach ($wishes as $w) {
            $maTho = $w['phuong_thuc_toi_uu'] ?? $w['ma_phuong_thuc'] ?? '';
            $ptxtChu = '';
            if ($maTho) {
                $ptxtChu = \App\Helpers\AdmissionMethodHelper::resolvePhuongThuc($maTho, [
                    'co_xet_chung_chi' => $w['co_xet_chung_chi'],
                    'co_diem_nangkhieu_thpt' => $w['co_diem_nangkhieu_thpt'],
                    'co_diem_nangkhieu_hochba' => $w['co_diem_nangkhieu_hochba']
                ], !empty($w['co_chung_chi_chuan']));
            }

            $data[] = [
                'STT'                   => (string)$stt++,
                'Số ĐDCN'               => $this->textCell($w['so_cccd'] ?? ''),
                'Thứ tự nguyện vọng'    => (string)($w['thu_tu_nv_bo'] ?? $w['thu_tu_nguyen_vong']),
                'Mã trường'             => 'THV',
                'Tên trường'            => 'Trường Đại học Hùng Vương',
                'Mã xét tuyển'          => $w['ma_nganh'],
                'Tên mã xét tuyển'      => $w['ten_nganh'],
                'Thứ tự ngành đợt TS'   => '',
                'Mã PTXT'               => $ptxtChu,
                'Tên PTXT'              => '',
                'Mã PTXT chuẩn'         => '',
                'Tên PTXT chuẩn'        => '',
                'Loại PTXT'             => '',
                'Mã THM'                => $w['ma_to_hop'] ?? $w['to_hop_toi_uu'] ?? '',
                'Mã môn NN để XT'       => '',
                'Điểm NN làm điểm XT'   => '',
                'Mã môn NN làm TCP'     => '',
                'Điểm NN làm TCP'       => '',
                'NV tuyển thẳng(điều 8)'=> '',
                'Thang điểm'            => '30',
                'Trạng thái hồ sơ'      => $w['trang_thai'],
                'Ghi chú'               => $w['ghi_chu'],
            ];
        }
        return $data;
    }

    /**
     * Dữ liệu điểm học bạ (MOET format) - Lấy tất cả các lớp 10, 11, 12.
     */
    /**
     * Dữ liệu điểm học bạ (MOET format) - Luôn xuất đủ 3 dòng (lớp 10, 11, 12) cho mỗi thí sinh.
     */
    public function exportMoetTranscriptsCsv($filters = []) {
        // 1. Lấy danh sách thí sinh thỏa mãn bộ lọc
        $sqlC = "SELECT hs.so_cccd, hs.trang_thai, hs.ghi_chu, t.ho_va_ten, t.ngay_sinh, t.gioi_tinh
                 FROM ho_so_xet_tuyen hs
                 JOIN thi_sinh t ON hs.so_cccd = t.so_cccd
                 WHERE 1=1";
        $params = [];
        if (!empty($filters['session_id'])) {
            $sqlC .= " AND hs.dot_tuyen_sinh_id = ?";
            $params[] = $filters['session_id'];
        }
        if (!empty($filters['status'])) {
            $sqlC .= " AND hs.trang_thai = ?";
            $params[] = $filters['status'];
        }
        $sqlC .= " ORDER BY t.ho_va_ten";
        
        $stmtC = $this->db->prepare($sqlC);
        $stmtC->execute($params);
        $candidates = $stmtC->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($candidates)) return [];

        // 2. Lấy dữ liệu điểm của các thí sinh này
        $cccds = array_column($candidates, 'so_cccd');
        // Chunk to avoid potential parameter limit issues if many candidates
        $mapped = [];
        $chunks = array_chunk($cccds, 500);
        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $sqlK = "SELECT * FROM ket_qua_hoc_tap WHERE so_cccd IN ($placeholders)";
            $stmtK = $this->db->prepare($sqlK);
            $stmtK->execute($chunk);
            $results = $stmtK->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($results as $kq) {
                $mapped[$kq['so_cccd']][$kq['lop']] = $kq;
            }
        }

        $data = [];
        $stt  = 1;

        // Subjects mapping
        $subjects_map = [
            'Toán' => 'toan', 'Văn' => 'van', 'Vật lí' => 'ly', 'Hóa học' => 'hoa',
            'Sinh học' => 'sinh', 'Lịch sử' => 'su', 'Địa lí' => 'dia', 'GDCD' => 'gdcd',
            'KTPL' => 'ktpl', 'Tin học' => 'tin_hoc', 'CNCN' => 'cong_nghe', 
            'CNNN' => 'cnnn', 'Ngoại ngữ' => 'ngoai_ngu'
        ];

        foreach ($candidates as $c) {
            $cccd = $c['so_cccd'];
            
            // Luôn xuất đủ 3 dòng cho 3 lớp
            foreach ([10, 11, 12] as $lop) {
                $r = $mapped[$cccd][$lop] ?? [];
                
                $row = [
                    'STT'                      => (string)$stt++,
                    'Số ĐDCN'                  => $this->textCell($cccd),
                    'Họ và tên'                => mb_strtoupper($c['ho_va_ten'], 'UTF-8'),
                    'Ngày sinh'                => $this->formatDate($c['ngay_sinh']),
                    'Giới tính'                => $c['gioi_tinh'],
                    'Lớp'                      => (string)$lop,
                    'Chương trình học'         => '',
                    'Điểm trung bình năm'      => $this->formatDecimal($r['diem_tb_ca_nam'] ?? null),
                    'Điểm tổng kết HK I'       => $this->formatDecimal($r['diem_tb_hk1'] ?? null),
                    'Điểm tổng kết HK II'      => $this->formatDecimal($r['diem_tb_hk2'] ?? null),
                    'Điểm tổng kết CN'         => $this->formatDecimal($r['diem_tb_ca_nam'] ?? null),
                    'Học lực HK I'             => $this->normalizeAcademic($r['hoc_luc_hk1'] ?? ''),
                    'Học lực HK II'            => $this->normalizeAcademic($r['hoc_luc_hk2'] ?? ''),
                    'Học lực CN'               => $this->normalizeAcademic($r['hoc_luc_ca_nam'] ?? ''),
                    'Hạnh kiểm HK I'           => $this->normalizeAcademic($r['hanh_kiem_hk1'] ?? ''),
                    'Hạnh kiểm HK II'          => $this->normalizeAcademic($r['hanh_kiem_hk2'] ?? ''),
                    'Hạnh kiểm CN'             => $this->normalizeAcademic($r['hanh_kiem_ca_nam'] ?? ''),
                    'Kết quả học tập HK I'     => '',
                    'Kết quả học tập HK II'    => '',
                    'Kết quả học tập CN'       => '',
                    'Kết quả rèn luyện HK I'   => '',
                    'Kết quả rèn luyện HK II'  => '',
                    'Kết quả rèn luyện CN'     => '',
                ];

                foreach ($subjects_map as $label => $key) {
                    $row[$label . ' HK I']  = $this->formatDecimal($r['diem_' . $key . '_hk1'] ?? null);
                    $row[$label . ' HK II'] = $this->formatDecimal($r['diem_' . $key . '_hk2'] ?? null);
                    $row[$label . ' CN']    = $this->formatDecimal($r['diem_' . $key . '_cn'] ?? null);
                }

                $row['Môn ngoại ngữ'] = '';
                $extra_fields = ['Tự chọn song ngữ', 'QPAN', 'Tiếng dân tộc', 'Ngoại ngữ 2', 'Toán Pháp'];
                foreach ($extra_fields as $field) {
                    $row[$field . ' HK I'] = '';
                    $row[$field . ' HK II'] = '';
                    $row[$field . ' CN'] = '';
                }

                $row['Trạng thái hồ sơ'] = $c['trang_thai'];
                $row['Ghi chú'] = $c['ghi_chu'];

                $data[] = $row;
            }
        }
        return $data;
    }

    // ----------------------------------------------------------------
    // Output
    // ----------------------------------------------------------------

    /**
     * Stream Excel file (XLS) using HTML representation.
     * Supports basic formatting and ensures data types (e.g. text for CCCD).
     */
    /**
     * Export data for auditing purposes (Feature 8)
     */
    public function exportDataAudit($type, $filters = []) {
        if ($type === 'comprehensive') {
            $session_id = $filters['session_id'] ?? null;
            if (!$session_id) {
                return [];
            }
            $sql = "SELECT t.so_cccd, t.ho_va_ten, t.ngay_sinh, t.dien_thoai, t.email, 
                           t.doi_tuong_uu_tien as ma_doi_tuong, t.khu_vuc_uu_tien as ma_khu_vuc, 
                           t.dan_toc, t.ma_truong_lop_12,
                           COALESCE(s.ten_truong, 'Chưa có') as ten_truong_thpt,
                           COALESCE(p.ten_tinh, 'Chưa rõ') as ten_tinh_thuong_tru,
                           COALESCE(hs.trang_thai, 'Chưa tạo') as trang_thai_ho_so,
                           qtv.ho_ten as nguoi_duyet,
                           (SELECT COUNT(*) FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd) as grade_count,
                           (SELECT COUNT(*) FROM nguyen_vong nv WHERE nv.so_cccd = t.so_cccd AND nv.dot_tuyen_sinh_id = ?) as wish_count,
                           (SELECT hb.hoc_luc_ca_nam FROM ket_qua_hoc_tap hb WHERE hb.so_cccd = t.so_cccd AND hb.lop = 12 LIMIT 1) as hoc_luc_12
                    FROM thi_sinh t
                    LEFT JOIN dm_truong_thpt s ON t.ma_truong_lop_12 = s.ma_truong
                    LEFT JOIN dm_tinh p ON t.ma_tinh_thuong_tru = p.ma_tinh
                    LEFT JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd AND hs.dot_tuyen_sinh_id = ?
                    LEFT JOIN quan_tri_vien qtv ON hs.nguoi_duyet_id = qtv.id
                    WHERE t.deleted_at IS NULL AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = t.so_cccd AND hs2.dot_tuyen_sinh_id = ?)
                    ORDER BY t.ho_va_ten ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$session_id, $session_id, $session_id]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $data = [];
            $stt = 1;
            foreach ($results as $r) {
                $errors = [];
                if ($r['grade_count'] < 3) {
                    $errors[] = "Chưa đủ học bạ 3 lớp";
                }
                if ($r['wish_count'] == 0) {
                    $errors[] = "Chưa đăng ký nguyện vọng";
                }
                if (empty($r['ngay_sinh']) || date('Y-m-d', strtotime($r['ngay_sinh'])) === '2008-01-01') {
                    $errors[] = "Sai ngày sinh 01/01/2008";
                }
                $danToc = trim($r['dan_toc'] ?? '');
                $doiTuong = trim($r['ma_doi_tuong'] ?? '');
                if (strcasecmp($danToc, 'Kinh') === 0 && ($doiTuong === '01' || $doiTuong === 'DT01' || $doiTuong === 'DT1')) {
                    $errors[] = "Dân tộc Kinh nhưng là Đối tượng 01";
                }
                if (empty($r['ma_truong_lop_12'])) {
                    $errors[] = "Thiếu tên trường THPT";
                }
                if (empty($r['hoc_luc_12'])) {
                    $errors[] = "Thiếu học lực lớp 12";
                }

                if (!empty($errors)) {
                    $data[] = [
                        'STT'               => $stt++,
                        'Số CCCD'           => $this->textCell($r['so_cccd']),
                        'Họ và tên'         => $r['ho_va_ten'],
                        'Ngày sinh'         => $this->formatDate($r['ngay_sinh']),
                        'Điện thoại'        => $r['dien_thoai'],
                        'Email'             => $r['email'],
                        'Đối tượng'         => $this->formatObject($r['ma_doi_tuong']),
                        'Khu vực'           => $this->formatArea($r['ma_khu_vuc']),
                        'Tỉnh'              => $r['ten_tinh_thuong_tru'],
                        'Tên trường THPT'   => $r['ten_truong_thpt'],
                        'Ghi chú rà soát'   => implode(', ', $errors),
                        'Tình trạng hồ sơ'  => $r['trang_thai_ho_so'],
                        'Người duyệt'       => $r['nguoi_duyet'] ?? '',
                    ];
                }
            }
            return $data;
        }

        $sql = "SELECT t.so_cccd, t.ho_va_ten, t.ngay_sinh, t.dien_thoai, t.email, 
                       t.doi_tuong_uu_tien as ma_doi_tuong, t.khu_vuc_uu_tien as ma_khu_vuc, 
                       COALESCE(s.ten_truong, 'Chưa có') as ten_truong_thpt,
                       COALESCE(p.ten_tinh, 'Chưa rõ') as ten_tinh_thuong_tru,
                       COALESCE(hs.trang_thai, 'Chưa tạo') as trang_thai_ho_so,
                       qtv.ho_ten as nguoi_duyet
                FROM thi_sinh t
                LEFT JOIN dm_truong_thpt s ON t.ma_truong_lop_12 = s.ma_truong
                LEFT JOIN dm_tinh p ON t.ma_tinh_thuong_tru = p.ma_tinh
                LEFT JOIN ho_so_xet_tuyen hs ON t.so_cccd = hs.so_cccd" . 
                (!empty($filters['session_id']) ? " AND hs.dot_tuyen_sinh_id = " . (int)$filters['session_id'] : "") . "
                LEFT JOIN quan_tri_vien qtv ON hs.nguoi_duyet_id = qtv.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['session_id'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = t.so_cccd AND hs2.dot_tuyen_sinh_id = ?)";
            $params[] = $filters['session_id'];
        }

        switch ($type) {
            case 'dob':
                $sql .= " AND (t.ngay_sinh IS NULL OR t.ngay_sinh = '2008-01-01')";
                break;
            case 'wishes':
                $session_id = $filters['session_id'] ?? null;
                if ($session_id) {
                    $sql .= " AND NOT EXISTS (SELECT 1 FROM nguyen_vong nv WHERE nv.so_cccd = t.so_cccd AND nv.dot_tuyen_sinh_id = ?)";
                    // We need to add session_id to params again for this specific subquery if needed, 
                    // but the $sql construction here is a bit tricky since params are added at the end.
                    // For now, I will use a direct subquery that matches the session_id already in the where clause.
                    $sql .= " AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = t.so_cccd AND hs2.dot_tuyen_sinh_id = " . (int)$session_id . ")";
                    $params[] = $session_id;
                } else {
                    $sql .= " AND NOT EXISTS (SELECT 1 FROM nguyen_vong nv WHERE nv.so_cccd = t.so_cccd)";
                }
                break;
            case 'contact':
                $sql .= " AND (t.email IS NULL OR t.email = '' 
                           OR t.dien_thoai IS NULL OR t.dien_thoai = ''
                           OR NOT (t.dien_thoai ~ '^0[0-9]{9,10}$')
                           OR NOT (t.email ~ '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'))";
                break;
            case 'priority':
                $sql .= " AND (t.khu_vuc_uu_tien IS NULL OR t.khu_vuc_uu_tien = '' 
                           OR t.ma_truong_lop_12 IS NULL OR t.ma_truong_lop_12 = '')";
                break;
            case 'free':
                $sql .= " AND t.ngay_sinh IS NOT NULL AND EXTRACT(YEAR FROM t.ngay_sinh) <= 2007";
                break;
            case 'scores':
                $sql .= " AND NOT EXISTS (SELECT 1 FROM ket_qua_hoc_tap d WHERE d.so_cccd = t.so_cccd)";
                break;
            default:
                return [];
        }

        $sql .= " ORDER BY t.ho_va_ten ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $data = [];
        foreach ($results as $i => $r) {
            $data[] = [
                'STT'               => $i + 1,
                'Số CCCD'           => $this->textCell($r['so_cccd']),
                'Họ và tên'         => $r['ho_va_ten'],
                'Ngày sinh'         => $this->formatDate($r['ngay_sinh']),
                'Điện thoại'        => $r['dien_thoai'],
                'Email'             => $r['email'],
                'Đối tượng'         => $this->formatObject($r['ma_doi_tuong']),
                'Khu vực'           => $this->formatArea($r['ma_khu_vuc']),
                'Tỉnh'              => $r['ten_tinh_thuong_tru'],
                'Tên trường THPT'   => $r['ten_truong_thpt'],
                'Tình trạng hồ sơ'  => $r['trang_thai_ho_so'],
                'Người duyệt'       => $r['nguoi_duyet'] ?? '',
            ];
        }

        return $data;
    }

    public function toExcel($data, $filename) {
        // Ensure .xls extension for simple browser handling
        $filename = str_replace(['.csv', '.xlsx', '.xls'], '', $filename) . '.xls';

        ob_clean();
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Excel 2003 XML SpreadsheetML boilerplate
        echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        
        echo ' <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office"><Author>Virtual Admission</Author></DocumentProperties>' . "\n";
        
        // Define Styles
        echo ' <Styles>' . "\n";
        echo '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="11" ss:Color="#334155"/></Style>' . "\n";
        echo '  <Style ss:ID="sHeader"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/></Borders><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="11" ss:Color="#475569" ss:Bold="1"/><Interior ss:Color="#f8fafc" ss:Pattern="Solid"/></Style>' . "\n";
        echo '  <Style ss:ID="sText"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/></Borders><NumberFormat ss:Format="@"/></Style>' . "\n";
        echo '  <Style ss:ID="sNum"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/></Borders><NumberFormat ss:Format="0.000"/></Style>' . "\n";
        echo ' </Styles>' . "\n";

        echo ' <Worksheet ss:Name="Report">' . "\n";
        echo '  <Table>' . "\n";

        if (!empty($data)) {
            $keys = array_keys($data[0]);
            
            // Columns definition (optional but good for widths)
            foreach ($keys as $k) {
                echo '   <Column ss:AutoFitWidth="1" ss:Width="120"/>' . "\n";
            }

            // Header Row
            echo '   <Row ss:Height="25">' . "\n";
            foreach ($keys as $key) {
                // Humanize keys (replace underscores)
                $label = str_replace('_', ' ', $key);
                echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">' . htmlspecialchars($label) . '</Data></Cell>' . "\n";
            }
            echo '   </Row>' . "\n";

            // Data Rows
            foreach ($data as $row) {
                echo '   <Row ss:AutoFitHeight="1">' . "\n";
                foreach ($row as $key => $cell) {
                    $type = 'String';
                    $style = 'sText';
                    
                    $cellStr = ($cell !== null) ? (string)$cell : '';
                    $trimmedStr = trim($cellStr);
                    
                    if ($trimmedStr !== '' && is_numeric($trimmedStr) && strpos($trimmedStr, ',') === false && !in_array($key, [
                        'Số CCCD', 'Số ĐDCN', 'CCCD', 'Số_ĐDCN', 'Điện thoại', 'Đối tượng', 'Đối tượng ƯT', 'Khu vực ƯT',
                        'stt', 'ddcn', 'dtu', 'kvu', 'nam_tn_thpt', 
                        'ma_tinh_tt', 'ma_huyen_tt', 'ma_xa_tt', 'ma_tinh_lop12', 'ma_truong_lop12',
                        'STT', 'ĐDCN', 'ĐTƯT', 'KVƯT', 'Năm TN THPT', 'Năm TN',
                        'Nơi thường trú - Mã tỉnh', 'Nơi thường trú - Mã Quận huyện', 'Nơi thường trú - Mã xã phường',
                        'Mã tỉnh lớp 12', 'Mã trường lớp 12',
                        'Số ĐDCN', 'Thứ tự nguyện vọng', 'Thang điểm', 'Mã xét tuyển', 'Lớp',
                        'Mã ngành', 'Thứ tự NV',
                        'Mã Trường', 'Mã trường', 'Mã Tỉnh', 'Mã tỉnh', 
                        'Mã Huyện', 'Mã huyện', 'Mã Xã', 'Mã xã',
                        'Mã Huyện/Quận', 'Mã Xã/Phường', 'Mã Tỉnh/Thành', 'Mã Tỉnh/Thành phố',
                        'Ngành', 'NV'
                    ])) {
                        $type = 'Number';
                        $style = 'sNum';
                        $cell = $trimmedStr;
                    }

                    echo '    <Cell ss:StyleID="' . $style . '"><Data ss:Type="' . $type . '">' . htmlspecialchars((string)$cell) . '</Data></Cell>' . "\n";
                }
                echo '   </Row>' . "\n";
            }
        }

        echo '  </Table>' . "\n";
        echo ' </Worksheet>' . "\n";
        echo '</Workbook>' . "\n";
        exit;
    }

    /**
     * Stream CSV with UTF-8 BOM. 
     */
    public function toCsv($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit;
    }
}
