<?php
namespace App\Services;

use App\Core\Database;

class ExportService {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Export candidates to CSV
     */
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
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Export admitted candidates by major with full details for Mail Merge
     */
    public function exportAdmittedByMajor($maNganh) {
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
                       nv.phuong_thuc_toi_uu AS \"Mã Phương Thức\"
                FROM nguyen_vong nv
                JOIN thi_sinh t ON nv.so_cccd = t.so_cccd
                JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh
                LEFT JOIN dm_xa xa ON t.ma_xa_thuong_tru = xa.ma_xa
                LEFT JOIN dm_tinh tinh ON t.ma_tinh_thuong_tru = tinh.ma_tinh
                LEFT JOIN dm_truong_thpt truong ON t.ma_truong_lop_12 = truong.ma_truong
                WHERE nv.ma_nganh = ? 
                  AND (nv.trang_thai_trung_tuyen = TRUE OR nv.trang_thai = 'Trung tuyen' OR nv.trang_thai = 'Trúng tuyển')
                ORDER BY nv.diem_xet_tuyen DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$maNganh]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics() {
        $stats = [];
        
        // By status
        $stmt = $this->db->query("SELECT trang_thai, COUNT(*) as count FROM ho_so_xet_tuyen GROUP BY trang_thai");
        $stats['by_status'] = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        // By major
        $stmt = $this->db->query("SELECT n.ten_nganh, COUNT(*) as count FROM nguyen_vong nv JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh WHERE nv.thu_tu_nguyen_vong = 1 GROUP BY n.ten_nganh ORDER BY count DESC LIMIT 10");
        $stats['by_major'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // By date (last 14 days)
        $stmt = $this->db->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM ho_so_xet_tuyen WHERE created_at > NOW() - INTERVAL '14 days' GROUP BY DATE(created_at) ORDER BY date");
        $stats['by_date'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Totals
        $stmt = $this->db->query("SELECT COUNT(*) FROM thi_sinh");
        $stats['total_candidates'] = $stmt->fetchColumn();
        
        $stmt = $this->db->query("SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE trang_thai = 'Đã duyệt'");
        $stats['total_approved'] = $stmt->fetchColumn();
        
        return $stats;
    }

    /**
     * Generate CSV output
     */
    public function toCsv($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
        
        if (!empty($data)) {
            // Header
            fputcsv($output, array_keys($data[0]));
            // Data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
}
