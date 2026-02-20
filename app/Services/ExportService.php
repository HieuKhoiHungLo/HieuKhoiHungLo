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
        $sql = "SELECT t.so_cccd, t.ho_va_ten, t.ngay_sinh, t.gioi_tinh, t.dien_thoai, t.email, 
                       t.khu_vuc_uu_tien, t.doi_tuong_uu_tien,
                       p.ten_tinh as tinh_thuong_tru,
                       h.trang_thai, h.dot_tuyen_sinh_id
                FROM thi_sinh t
                LEFT JOIN dm_tinh p ON t.ma_tinh_thuong_tru = p.ma_tinh
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
     * Export admitted candidates by major
     */
    public function exportAdmittedByMajor($maNganh) {
        $sql = "SELECT t.so_cccd, t.ho_va_ten, t.ngay_sinh, t.dien_thoai, t.email,
                       nv.ma_nganh, n.ten_nganh, nv.diem_xet_tuyen, nv.trang_thai as trang_thai_nv
                FROM nguyen_vong nv
                JOIN thi_sinh t ON nv.so_cccd = t.so_cccd
                JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh
                WHERE nv.ma_nganh = ? AND (nv.trang_thai = 'Trung tuyen' OR nv.trang_thai = 'Trúng tuyển')
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
        $stmt = $this->db->query("SELECT n.ten_nganh, COUNT(*) as count FROM nguyen_vong nv JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh WHERE nv.thu_tu = 1 GROUP BY n.ten_nganh ORDER BY count DESC LIMIT 10");
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
