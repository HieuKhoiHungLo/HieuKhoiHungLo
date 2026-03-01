<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\VirtualFilterService;
use App\Services\ScoreCalculationService;
use App\Repositories\ThiSinhRepository;
use App\Core\Database;

class VirtualFilterController extends Controller {
    protected $filterService;
    protected $scoreService;
    protected $db;

    public function __construct() {
        $this->filterService = new VirtualFilterService();
        $this->scoreService = new ScoreCalculationService();
        $this->db = Database::getInstance()->getConnection();
    }

    // Hiển thị giao diện Dashboard Xét tuyển Lọc Ảo
    public function index() {
        // Lấy danh sách các đợt tuyển sinh đang mở
        $stmt = $this->db->query("SELECT id, ten_dot, nam_tuyen_sinh, kich_hoat FROM dot_tuyen_sinh ORDER BY id DESC");
        $batches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('admin/admission/virtual_filter', [
            'batches' => $batches,
            'title' => 'Xét tuyển Lọc Ảo'
        ]);
    }

    // API: Lấy thông tin đợt xét tuyển, ngành, và điểm chuẩn dự kiến cũ
    public function loadBatchData() {
        $batchId = $_GET['batch_id'] ?? 0;
        if (!$batchId) {
            $this->json(['status' => false, 'message' => 'Missing batch ID']);
            return;
        }

        // Lấy danh sách Ngành có thí sinh đăng ký trong đợt này
        $stmtMajors = $this->db->prepare("
            SELECT DISTINCT n.ma_nganh, m.ten_nganh 
            FROM nguyen_vong n
            JOIN dm_nganh m ON n.ma_nganh = m.ma_nganh
            WHERE n.dot_tuyen_sinh_id = ?
        ");
        $stmtMajors->execute([$batchId]);
        $majors = $stmtMajors->fetchAll(\PDO::FETCH_ASSOC);

        // Lấy lại Điểm chuẩn dự kiến từ lần lưu trước
        $benchmarksRaw = $this->filterService->getExpectedBenchmarks($batchId);
        $benchmarks = [];
        $quotas = [];
        foreach ($benchmarksRaw as $b) {
            $benchmarks[$b['ma_nganh']] = $b['diem_chuan'];
            $quotas[$b['ma_nganh']] = $b['chi_tieu_du_kien'];
        }

        // Lấy số lượng thí sinh đăng ký của mỗi ngành
        $stmtCount = $this->db->prepare("
            SELECT ma_nganh, COUNT(DISTINCT so_cccd) as tong_dk
            FROM nguyen_vong 
            WHERE dot_tuyen_sinh_id = ?
            GROUP BY ma_nganh
        ");
        $stmtCount->execute([$batchId]);
        $counts = $stmtCount->fetchAll(\PDO::FETCH_KEY_PAIR);

        foreach ($majors as &$m) {
            $mCode = $m['ma_nganh'];
            $m['diem_chuan_nhap'] = $benchmarks[$mCode] ?? 15.0; // Default 15
            $m['chi_tieu'] = $quotas[$mCode] ?? 100; // Default 100
            $m['tong_dang_ky'] = $counts[$mCode] ?? 0;
            $m['so_luong_dat'] = 0; // Sẽ tính sau khi Run
        }

        // Nếu đã từng lọc ảo thành công, lấy số lượng đạt thật luôn hiển thị
        $currentStats = $this->filterService->getFilterStats($batchId);
        if ($currentStats['status'] && !empty($currentStats['data'])) {
             foreach ($majors as &$m) {
                 $mCode = $m['ma_nganh'];
                 $m['so_luong_dat'] = $currentStats['data'][$mCode] ?? 0;
             }
        }

        $this->json(['status' => true, 'majors' => $majors]);
    }

    // API: Kích hoạt chạy lại hệ thống tính TỔNG ĐIỂM (ScoreCalculationService) cho đợt tuyển
    public function recalculateScores() {
        $batchId = $_POST['batch_id'] ?? 0;
        if (!$batchId) {
            $this->json(['status' => false, 'message' => 'Missing batch ID']);
            return;
        }

        set_time_limit(300); // 5 phút vì chạy nặng
        try {
            // Lấy danh sách CCCD có đăng ký NV trong đợt này
            $stmt = $this->db->prepare("SELECT DISTINCT so_cccd FROM nguyen_vong WHERE dot_tuyen_sinh_id = ? AND diem_xet_tuyen IS NULL");
            // Tối ưu: Chỉ tính cho người chưa có diem_xet_tuyen, HOẶC tính lại hết nếu Request có cờ force
            if (isset($_POST['force']) && $_POST['force'] == '1') {
                $stmt = $this->db->prepare("SELECT DISTINCT so_cccd FROM nguyen_vong WHERE dot_tuyen_sinh_id = ?");
            }
            $stmt->execute([$batchId]);
            $cccds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $count = 0;
            foreach ($cccds as $cccd) {
                // Hàm này tự tìm MAX và UPDATE vào DB cho thí sinh
                $this->scoreService->calculate($cccd);
                $count++;
            }

            $this->json(['status' => true, 'message' => "Đã tính điểm thành công cho $count thí sinh."]);
        } catch (\Exception $e) {
            $this->json(['status' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }

    // API: Thực hiện thuật toán Lọc Ảo ưu tiên (Trượt dây chuyền)
    public function runFiltering() {
        $batchId = $_POST['batch_id'] ?? 0;
        $benchmarks = $_POST['benchmarks'] ?? []; // ['CNTT' => 24.5, 'QTKD' => 20]
        $quotas = $_POST['quotas'] ?? []; // ['CNTT' => 100]

        if (!$batchId || empty($benchmarks)) {
            $this->json(['status' => false, 'message' => 'Missing data']);
            return;
        }

        // Lưu lại mốc điểm chuẩn BGH vừa thiết lập
        $this->filterService->saveExpectedBenchmarks($batchId, $benchmarks, $quotas);

        // Chạy lọc ảo dây chuyền
        $result = $this->filterService->runVirtualFilter($batchId, $benchmarks);

        $this->json($result);
    }
}
