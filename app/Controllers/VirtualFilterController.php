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

        // Lấy danh sách Ngành được cấu hình tuyển sinh trong đợt này
        $stmtMajors = $this->db->prepare("
            SELECT ab.ma_nganh, m.ten_nganh, m.chi_tieu, ab.diem_chuan
            FROM admission_benchmarks ab
            JOIN dm_nganh m ON ab.ma_nganh = m.ma_nganh
            WHERE ab.session_id = ?
        ");
        $stmtMajors->execute([$batchId]);
        $majors = $stmtMajors->fetchAll(\PDO::FETCH_ASSOC);

        // Nếu đợt này chưa được cấu hình ngành nào, fallback lấy những ngành có thí sinh đăng ký (để user biết mà cấu hình)
        if (empty($majors)) {
            $stmtCount = $this->db->prepare("
                SELECT DISTINCT n.ma_nganh, m.ten_nganh 
                FROM nguyen_vong n
                JOIN dm_nganh m ON n.ma_nganh = m.ma_nganh
                WHERE n.dot_tuyen_sinh_id = ?
            ");
            $stmtCount->execute([$batchId]);
            $majors = $stmtCount->fetchAll(\PDO::FETCH_ASSOC);
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

        // Lấy điểm chuẩn dự kiến (nháp) nếu có
        $benchmarksRaw = $this->filterService->getExpectedBenchmarks($batchId);
        $draftBenchmarks = [];
        foreach ($benchmarksRaw as $b) {
            $draftBenchmarks[$b['ma_nganh']] = $b['diem_chuan'];
        }

        foreach ($majors as &$m) {
            $mCode = $m['ma_nganh'];
            // Ưu tiên điểm chuẩn nháp (nếu đang trong quá trình thử nghiệm lọc ảo) 
            // Nếu không có nháp thì lấy điểm chuẩn chính thức đã cấu hình
            $m['diem_chuan_nhap'] = $draftBenchmarks[$mCode] ?? ($m['diem_chuan'] ?? 15.0);
            $m['chi_tieu'] = $m['chi_tieu'] ?? 100; 
            $m['tong_dang_ky'] = $counts[$mCode] ?? 0;
            $m['so_luong_dat'] = 0;
        }

        // Cập nhật số lượng đạt thật nếu đã chạy lọc ảo
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
            // Lấy danh sách CCCD có đăng ký NV trong đợt này nhưng CHƯA có kết quả trong bảng summary
            $sql = "
                SELECT DISTINCT nv.so_cccd 
                FROM nguyen_vong nv
                WHERE nv.dot_tuyen_sinh_id = ? 
                AND NOT EXISTS (SELECT 1 FROM v_calc_summary cs WHERE cs.nguyen_vong_id = nv.id)
            ";
            
            // Tối ưu: Tính lại hết nếu Request có cờ force
            if (isset($_POST['force']) && $_POST['force'] == '1') {
                $sql = "SELECT DISTINCT so_cccd FROM nguyen_vong WHERE dot_tuyen_sinh_id = ?";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$batchId]);
            $cccds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $count = 0;
            // TỐI ƯU HÓA: Thay đổi vòng lặp O(N) thành xử lý hàng loạt O(1) database trip
            $chunks = array_chunk($cccds, 500);
            foreach ($chunks as $chunk) {
                $this->scoreService->recalculateBatch($batchId, $chunk);
                $count += count($chunk);
            }

            $this->json(['status' => true, 'message' => "Đã tính điểm thành công cho $count thí sinh."]);
        } catch (\Exception $e) {
            $this->json(['status' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }

    // API: Thực hiện thuật toán Lọc Ảo ưu tiên (Trượt dây chuyền)
    public function runFiltering() {
        $batchId = $_POST['session_id'] ?? ($_POST['batch_id'] ?? 0);
        $benchmarks = $_POST['benchmarks'] ?? []; // ['CNTT' => 24.5, 'QTKD' => 20]
        $quotas = $_POST['quotas'] ?? []; // ['CNTT' => 100]

        if (!$batchId) {
            $this->json(['status' => false, 'message' => 'Thiếu ID đợt tuyển sinh (session_id)']);
            return;
        }

        // Nếu không truyền benchmarks từ UI, thử lấy từ DB đã lưu trước đó
        if (empty($benchmarks)) {
            $saved = $this->filterService->getExpectedBenchmarks($batchId);
            foreach ($saved as $s) {
                $benchmarks[$s['ma_nganh']] = $s['diem_chuan'];
                $quotas[$s['ma_nganh']] = $s['chi_tieu_du_kien'];
            }
        }

        if (empty($benchmarks)) {
            $this->json(['status' => false, 'message' => 'Chưa thiết lập điểm chuẩn dự kiến cho đợt này.']);
            return;
        }

        // Lưu lại mốc điểm chuẩn BGH vừa thiết lập (nếu có truyền lên mới)
        if (!empty($_POST['benchmarks'])) {
            $this->filterService->saveExpectedBenchmarks($batchId, $benchmarks, $quotas);
        }

        // Chạy lọc ảo dây chuyền
        $result = $this->filterService->runVirtualFilter($batchId, $benchmarks);

        $this->json($result);
    }
}
