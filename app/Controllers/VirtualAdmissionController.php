<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Services\ScoreCalculationService;
use App\Services\VirtualFilterService;
use PDO;

class VirtualAdmissionController extends Controller {
    protected $masterData;
    protected $scoreService;
    protected $filterService;
    protected $db;

    public function __construct() {
        $this->masterData = new MasterData();
        $this->scoreService = new ScoreCalculationService();
        $this->filterService = new VirtualFilterService();
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }

    public function index() {
        $sessions = $this->masterData->getSessions();
        // Lấy danh sách năm duy nhất
        $years = $this->db->query("SELECT DISTINCT nam_tuyen_sinh FROM dot_tuyen_sinh ORDER BY nam_tuyen_sinh DESC")->fetchAll(PDO::FETCH_COLUMN);
        
        // Cần truyền thêm danh sách tổ hợp để render table headers (các cột điểm từng tổ hợp)
        $combinations = $this->db->query("SELECT ma_to_hop FROM dm_to_hop ORDER BY ma_to_hop")->fetchAll(PDO::FETCH_ASSOC);
        
        $this->view('admin/virtual_admission/index', [
            'title' => 'Xét Tuyển Lọc Ảo',
            'sessions' => $sessions,
            'years' => $years,
            'combinations' => $combinations
        ]);
    }

    public function loadBatchData() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) {
            echo json_encode(['data' => []]);
            exit;
        }

        // Fetch candidates and their aspirations (only approved NV for the chosen session)
        // Note: Joining thi_sinh, nguyen_vong, and dm_nganh
        // We only care about n.khao_sat = 1 (Da duyet)
        try {
            $sql = "
                SELECT 
                    ts.id, ts.so_cccd, ts.ho_va_ten, ts.gioi_tinh, ts.nam_tot_nghiep, 
                    ts.khu_vuc_uu_tien, ts.doi_tuong_uu_tien,
                    n.ma_nganh, n.thu_tu_nguyen_vong, n.diem_xet_tuyen, n.to_hop_toi_uu, n.phuong_thuc_toi_uu,
                    n.chi_tiet_diem, n.trang_thai_trung_tuyen,
                    n.diem_mon_1, n.diem_mon_2, n.diem_mon_3
                FROM thi_sinh ts
                JOIN nguyen_vong n ON ts.so_cccd = n.so_cccd
                WHERE 
                    n.dot_tuyen_sinh_id = ?
                    AND (n.trang_thai = 'DaDuyet' OR n.trang_thai LIKE '%Đã duyệt%')
                ORDER BY ts.so_cccd, n.thu_tu_nguyen_vong ASC
                LIMIT 100
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sessionId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Grouping logic (optional if frontend handles flat lists)
            $this->json(['data' => $rows]);
        } catch (\Exception $e) {
            error_log("loadBatchData Error: " . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function recalculateScores() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid Request']);
            exit;
        }

        $sessionId = $_POST['session_id'] ?? null;
        if (!$sessionId) {
            echo json_encode(['success' => false, 'message' => 'Chưa chọn đợt xét tuyển.']);
            exit;
        }

        // Get all approved candidates in this session
        $sql = "SELECT DISTINCT n.so_cccd 
                FROM nguyen_vong n 
                WHERE n.dot_tuyen_sinh_id = ? AND (n.trang_thai = 'DaDuyet' OR n.trang_thai LIKE '%Đã duyệt%')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        $candidates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $success = 0;
        foreach ($candidates as $cccd) {
            $this->scoreService->calculate($cccd);
            $success++;
        }

        $this->json(['success' => true, 'message' => "Đã tính điểm cho $success thí sinh."]);
    }

    public function apiSync() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid Request']);
            exit;
        }

        $sessionId = $_POST['session_id'] ?? null;
        if (!$sessionId) {
            echo json_encode(['success' => false, 'message' => 'Chưa chọn đợt xét tuyển.']);
            exit;
        }

        $result = $this->filterService->syncData($sessionId);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Đã đồng bộ dữ liệu hồ sơ được duyệt thành công.']);
        } else {
            $this->json(['success' => false, 'message' => 'Lỗi khi đồng bộ dữ liệu.'], 500);
        }
    }

    public function exportExcel() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) {
            die("Chưa chọn đợt xét tuyển.");
        }

        // Logic xuất excel đơn giản (CSV)
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=xet_tuyen_loc_ao_' . $sessionId . '.csv');
        
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // BOM for Excel UTF-8
        
        // Header
        fputcsv($output, ['CCCD', 'Họ tên', 'Ngành', 'NV', 'Điểm M1', 'Điểm M2', 'Điểm M3', 'Tổng Điểm', 'Trạng Thái']);

        $sql = "SELECT n.so_cccd, ts.ho_va_ten, n.ma_nganh, n.thu_tu_nguyen_vong, n.diem_mon_1, n.diem_mon_2, n.diem_mon_3, n.diem_xet_tuyen, n.trang_thai_trung_tuyen
                FROM nguyen_vong n
                JOIN thi_sinh ts ON n.so_cccd = ts.so_cccd
                WHERE n.dot_tuyen_sinh_id = ? AND (n.trang_thai = 'DaDuyet' OR n.trang_thai LIKE '%Đã duyệt%')
                ORDER BY n.so_cccd, n.thu_tu_nguyen_vong ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = ($row['trang_thai_trung_tuyen'] == 1) ? 'Trúng Tuyển' : 'Không đạt';
            fputcsv($output, [
                $row['so_cccd'],
                $row['ho_va_ten'],
                $row['ma_nganh'],
                $row['thu_tu_nguyen_vong'],
                $row['diem_mon_1'],
                $row['diem_mon_2'],
                $row['diem_mon_3'],
                $row['diem_xet_tuyen'],
                $status
            ]);
        }
        fclose($output);
        exit;
    }
}
