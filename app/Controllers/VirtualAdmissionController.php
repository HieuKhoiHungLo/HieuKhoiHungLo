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
        $sql = "
            SELECT 
                ts.id, ts.so_cccd, ts.ho_va_ten, ts.gioi_tinh, ts.nam_tot_nghiep, 
                ts.khu_vuc_uu_tien, ts.doi_tuong_uu_tien,
                n.ma_nganh, n.thu_tu_nv, n.diem_xet_tuyen, n.to_hop_toi_uu, n.phuong_thuc_toi_uu,
                n.chi_tiet_diem, n.trang_thai_trung_tuyen
            FROM thi_sinh ts
            JOIN nguyen_vong n ON ts.so_cccd = n.so_cccd
            WHERE 
                ts.dot_xet_tuyen_id = ?
                AND n.trang_thai_ho_so = 'DaDuyet'
            ORDER BY ts.so_cccd, n.thu_tu_nv ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by CCCD so we can format them for DataTables gracefully
        $data = [];
        $candidates = [];

        foreach ($rows as $r) {
            $cccd = $r['so_cccd'];
            if (!isset($candidates[$cccd])) {
                $candidates[$cccd] = [
                    'so_cccd' => $cccd,
                    'ho_va_ten' => $r['ho_va_ten'],
                    'khu_vuc_uu_tien' => $r['khu_vuc_uu_tien'],
                    'doi_tuong_uu_tien' => $r['doi_tuong_uu_tien'],
                    'nguyen_vongs' => []
                ];
            }
            $candidates[$cccd]['nguyen_vongs'][] = $r;
        }

        // Flatten back for easy table iteration OR let frontend handle it.
        // Actually for the grid, each Row is ONE NguyenVong with its details appended. Let's return flattened:
        echo json_encode(['data' => $rows]);
        exit;
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
        $sql = "SELECT DISTINCT ts.so_cccd 
                FROM thi_sinh ts 
                JOIN nguyen_vong n ON ts.so_cccd = n.so_cccd 
                WHERE ts.dot_xet_tuyen_id = ? AND n.trang_thai_ho_so = 'DaDuyet'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        $candidates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $success = 0;
        foreach ($candidates as $cccd) {
            $this->scoreService->calculate($cccd);
            $success++;
        }

        echo json_encode(['success' => true, 'message' => "Đã tính điểm cho $success thí sinh."]);
        exit;
    }
}
