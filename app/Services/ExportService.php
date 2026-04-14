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
     * Format decimal score: replace '.' with ',' for Vietnamese locale.
     * Returns '' when null.
     */
    private function formatDecimal($value): string {
        if ($value === null || $value === '') return '';
        return str_replace('.', ',', (string)$value);
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
                       t.khu_vuc_uu_tien AS \"Khu vực\",
                       t.doi_tuong_uu_tien AS \"Đối tượng\",
                       xa.ten_xa AS \"Xã/Phường\",
                       p.ten_tinh as \"Tỉnh/Thành phố\",
                       truong.ten_truong AS \"Trường THPT\",
                       h.trang_thai AS \"Trạng thái hồ sơ\",
                       h.dot_tuyen_sinh_id AS \"Mã đợt tuyển sinh\"
                FROM thi_sinh t
                LEFT JOIN dm_tinh p ON t.ma_tinh_thuong_tru = p.ma_tinh
                LEFT JOIN dm_xa xa ON t.ma_xa_thuong_tru = xa.ma_xa
                LEFT JOIN dm_truong_thpt truong ON t.ma_truong_lop_12 = truong.ma_truong
                LEFT JOIN ho_so_xet_tuyen h ON t.so_cccd = h.so_cccd
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
            $r["Điện thoại"] = $this->textCell($r["Điện thoại"]);
            $r["Ngày Sinh"]  = $this->formatDate($r["Ngày Sinh"]);
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
                       n.co_xet_chung_chi,
                       n.co_diem_nangkhieu_thpt,
                       n.co_diem_nangkhieu_hochba
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
            $r["Điện thoại"] = $this->textCell($r["Điện thoại"]);
            $r["Ngày Sinh"]  = $this->formatDate($r["Ngày Sinh"]);
            $r["Mã Phương Thức"] = \App\Helpers\AdmissionMethodHelper::resolvePhuongThuc($r["Mã Phương Thức"], [
                'co_xet_chung_chi' => $r['co_xet_chung_chi'],
                'co_diem_nangkhieu_thpt' => $r['co_diem_nangkhieu_thpt'],
                'co_diem_nangkhieu_hochba' => $r['co_diem_nangkhieu_hochba'],
            ]);
            unset($r['co_xet_chung_chi'], $r['co_diem_nangkhieu_thpt'], $r['co_diem_nangkhieu_hochba']);
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
                       nv.trang_thai AS \"Trạng thái xét tuyển\"
                FROM nguyen_vong nv
                JOIN thi_sinh t ON nv.so_cccd = t.so_cccd
                JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh
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
                       cc.loai_chung_chi AS \"Loại chứng chỉ\",
                       cc.diem_chung_chi AS \"Điểm/Xếp loại\"
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
                       h.trang_thai AS \"Trạng thái hồ sơ\"
                FROM thi_sinh t
                JOIN ho_so_xet_tuyen h ON t.so_cccd = h.so_cccd
                JOIN nguyen_vong nv ON t.so_cccd = nv.so_cccd AND h.dot_tuyen_sinh_id = nv.dot_tuyen_sinh_id
                JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh
                WHERE (n.nhom_nganh = 'SuPhamDacThu' OR t.so_cccd IN (SELECT DISTINCT so_cccd FROM diem_nang_khieu))";

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
                       dt.tin_hoc as dt_tin, dt.cnnn as dt_cnnn
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

            $data[] = [
                'stt'             => $stt++,
                'sbd'             => '',
                'ho_ten'          => mb_strtoupper($c['ho_va_ten'], 'UTF-8'),
                'ddcn'            => ($c['so_cccd'] ?? ''),
                'ngay_sinh'       => $this->formatDate($c['ngay_sinh']),
                'gioi_tinh'       => $c['gioi_tinh'],
                'dtu'             => $c['doi_tuong_uu_tien'] ?: '0',
                'kvu'             => $c['khu_vuc_uu_tien'] ?: '3',
                'nam_tn_thpt'     => $c['nam_tot_nghiep'],
                'hoc_luc'         => $c['hoc_luc_12'],
                'hanh_kiem'       => $c['hanh_kiem_12'],
                'diem_tb_12'      => $this->formatDecimal($c['diem_tb_12']),
                'tn_cao_dang'     => '',
                'tn_trung_cap'    => '',
                'ma_tinh_tt'      => $c['ma_tinh_thuong_tru'],
                'ten_tinh_tt'     => $c['ten_tinh_tt'],
                'ma_huyen_tt'     => $c['ma_huyen_thuong_tru'] ?? '',
                'ten_huyen_tt'    => '',
                'ma_xa_tt'        => $c['ma_xa_thuong_tru'],
                'ten_xa_tt'       => $c['ten_xa_tt'],
                'ma_tinh_lop12'   => $c['ma_tinh_lop_12'],
                'ma_truong_lop12' => $c['ma_truong_lop_12'],
                'kq_so_tuyen'     => '',
                'toan'            => $this->formatDecimal($c['dt_toan']),
                'van'             => $this->formatDecimal($c['dt_van']),
                'ly'              => $this->formatDecimal($c['dt_ly']),
                'hoa'             => $this->formatDecimal($c['dt_hoa']),
                'sinh'            => $this->formatDecimal($c['dt_sinh']),
                'su'              => $this->formatDecimal($c['dt_su']),
                'dia'             => $this->formatDecimal($c['dt_dia']),
                'gdcd'            => $this->formatDecimal($c['dt_gdcd']),
                'ngoai_ngu'       => $this->formatDecimal($diemNgoaiNgu),
                'ma_mon_nn'       => $maMonNgoaiNgu,
                'ktpl'            => $this->formatDecimal($c['dt_ktpl']),
                'tin_hoc'         => $this->formatDecimal($c['dt_tin']),
                'cncn'            => '', 
                'cnnn'            => $this->formatDecimal($c['dt_cnnn']),
                'chuong_trinh_hoc'=> '',
                'diem_xet_tn'     => '',
                'dan_toc'         => $c['dan_toc'],
                'ma_dan_toc'      => '',
                'noi_sinh'        => '',
            ];
        }
        return $data;
    }

    public function exportMoetWishesCsv($filters = []) {
        $sql = "SELECT nv.*, n.ten_nganh, n.co_xet_chung_chi, n.co_diem_nangkhieu_thpt, n.co_diem_nangkhieu_hochba
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
            $maTho = $w['ma_phuong_thuc'] ?? $w['phuong_thuc_toi_uu'] ?? '';
            $ptxtChu = '';
            if ($maTho) {
                $ptxtChu = \App\Helpers\AdmissionMethodHelper::resolvePhuongThuc($maTho, [
                    'co_xet_chung_chi' => $w['co_xet_chung_chi'],
                    'co_diem_nangkhieu_thpt' => $w['co_diem_nangkhieu_thpt'],
                    'co_diem_nangkhieu_hochba' => $w['co_diem_nangkhieu_hochba']
                ]);
            }

            $data[] = [
                'STT'                   => $stt++,
                'Số ĐDCN'               => ($w['so_cccd'] ?? ''),
                'Thứ tự nguyện vọng'    => $w['thu_tu_nv_bo'] ?? $w['thu_tu_nguyen_vong'],
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
            ];
        }
        return $data;
    }

    public function exportMoetTranscriptsCsv($filters = []) {
        $sql = "SELECT hs.so_cccd, t.ho_va_ten, t.ngay_sinh, t.gioi_tinh, kq.*
                FROM ho_so_xet_tuyen hs
                JOIN thi_sinh t ON hs.so_cccd = t.so_cccd
                LEFT JOIN ket_qua_hoc_tap kq ON hs.so_cccd = kq.so_cccd AND kq.lop = 12
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
        $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $data = [];
        $stt  = 1;

        $map_diem    = [
            'toan' => 'Toán', 
            'van' => 'Văn', 
            'ly' => 'Vật lí', 
            'hoa' => 'Hóa học', 
            'sinh' => 'Sinh học', 
            'su' => 'Lịch sử', 
            'dia' => 'Địa lí', 
            'gdcd' => 'GDCD', 
            'tin_hoc' => 'Tin học', 
            'cong_nghe' => 'CNCN',
            'ngoai_ngu' => 'Ngoại ngữ'
        ];
        $moet_subjects = ['Toán', 'Văn', 'Vật lí', 'Hóa học', 'Sinh học', 'Lịch sử', 'Địa lí', 'GDCD', 'KTPL', 'Tin học', 'CNCN', 'CNNN', 'Ngoại ngữ'];

        foreach ($records as $r) {
            $row = [
                'STT'                      => $stt++,
                'Số ĐDCN'                  => ($r['so_cccd'] ?? ''),
                'Họ và tên'                => mb_strtoupper($r['ho_va_ten'], 'UTF-8'),
                'Ngày sinh'                => $this->formatDate($r['ngay_sinh']),
                'Giới tính'                => $r['gioi_tinh'],
                'Lớp'                      => '12',
                'Chương trình học'         => '',
                'Điểm trung bình năm'      => $this->formatDecimal($r['diem_tb_ca_nam'] ?? null),
                'Điểm tổng kết HK I'       => $this->formatDecimal($r['diem_tb_hk1'] ?? null),
                'Điểm tổng kết HK II'      => $this->formatDecimal($r['diem_tb_hk2'] ?? null),
                'Điểm tổng kết CN'         => $this->formatDecimal($r['diem_tb_ca_nam'] ?? null),
                'Học lực HK I'             => $r['hoc_luc_hk1'] ?? '',
                'Học lực HK II'            => $r['hoc_luc_hk2'] ?? '',
                'Học lực CN'               => $r['hoc_luc_ca_nam'] ?? '',
                'Hạnh kiểm HK I'           => $r['hanh_kiem_hk1'] ?? '',
                'Hạnh kiểm HK II'          => $r['hanh_kiem_hk2'] ?? '',
                'Hạnh kiểm CN'             => $r['hanh_kiem_ca_nam'] ?? '',
                'Kết quả học tập HK I'     => '',
                'Kết quả học tập HK II'    => '',
                'Kết quả học tập CN'       => '',
                'Kết quả rèn luyện HK I'   => '',
                'Kết quả rèn luyện HK II'  => '',
                'Kết quả rèn luyện CN'     => '',
            ];

            foreach ($moet_subjects as $msub) {
                $internal_key = array_search($msub, $map_diem);
                if ($internal_key) {
                    $row[$msub . ' HK I']  = $this->formatDecimal($r['diem_' . $internal_key . '_hk1'] ?? null);
                    $row[$msub . ' HK II'] = $this->formatDecimal($r['diem_' . $internal_key . '_hk2'] ?? null);
                    $row[$msub . ' CN']    = $this->formatDecimal($r['diem_' . $internal_key . '_cn'] ?? null);
                } else {
                    $row[$msub . ' HK I']  = '';
                    $row[$msub . ' HK II'] = '';
                    $row[$msub . ' CN']    = '';
                }
            }

            $data[] = $row;
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
    public function toExcel($data, $filename) {
        // Ensure .xls extension for simple browser handling
        $filename = str_replace(['.csv', '.xlsx'], '', $filename) . '.xls';

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
        echo '  <Style ss:ID="sNum"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/></Borders><NumberFormat ss:Format="Fixed"/></Style>' . "\n";
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
                    
                    if (is_numeric($cell) && strpos((string)$cell, ',') === false && !in_array($key, ['Số CCCD', 'Số ĐDCN', 'CCCD', 'Số_ĐDCN', 'Điện thoại'])) {
                        $type = 'Number';
                        $style = 'sNum';
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
