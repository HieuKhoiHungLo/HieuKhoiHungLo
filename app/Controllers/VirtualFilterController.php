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
        $this->db = Database::getInstance()->getConnection();
    }

    private function getFilterService() {
        if (!$this->filterService) {
            $this->filterService = new VirtualFilterService();
        }
        return $this->filterService;
    }

    private function getScoreService() {
        if (!$this->scoreService) {
            $this->scoreService = new ScoreCalculationService();
        }
        return $this->scoreService;
    }

    // Hiển thị giao diện Dashboard Xét tuyển Lọc Ảo
    public function index() {
        $stmt = $this->db->query("SELECT id, ten_dot, nam_tuyen_sinh, kich_hoat FROM dot_tuyen_sinh ORDER BY id DESC");
        $batches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('admin/admission/virtual_filter', [
            'batches' => $batches,
            'title' => 'Xét tuyển Lọc Ảo'
        ]);
    }

    // API: Lấy thông tin đợt xét tuyển, ngành, và điểm chuẩn dự kiến cũ
    public function loadBatchData() {
        try {
            $batchId = $_GET['batch_id'] ?? 0;
            if (!$batchId) {
                $this->json(['status' => false, 'message' => 'Missing batch ID']);
                return;
            }

            $service = $this->getFilterService();

            $stmtMajors = $this->db->prepare("
                SELECT ab.ma_nganh, m.ten_nganh, m.chi_tieu, ab.diem_chuan
                FROM admission_benchmarks ab
                JOIN dm_nganh m ON ab.ma_nganh = m.ma_nganh
                WHERE ab.session_id = ?
            ");
            $stmtMajors->execute([$batchId]);
            $majors = $stmtMajors->fetchAll(\PDO::FETCH_ASSOC);

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

            $stmtCount = $this->db->prepare("
                SELECT ma_nganh, COUNT(DISTINCT so_cccd) as tong_dk
                FROM nguyen_vong 
                WHERE dot_tuyen_sinh_id = ?
                GROUP BY ma_nganh
            ");
            $stmtCount->execute([$batchId]);
            $counts = $stmtCount->fetchAll(\PDO::FETCH_KEY_PAIR);

            $benchmarksRaw = $service->getExpectedBenchmarks($batchId);
            $draftBenchmarks = [];
            foreach ($benchmarksRaw as $b) {
                $draftBenchmarks[$b['ma_nganh']] = $b['diem_chuan'];
            }

            foreach ($majors as &$m) {
                $mCode = $m['ma_nganh'];
                $m['diem_chuan_nhap'] = $draftBenchmarks[$mCode] ?? ($m['diem_chuan'] ?? 15.0);
                $m['chi_tieu'] = $m['chi_tieu'] ?? 100; 
                $m['tong_dang_ky'] = $counts[$mCode] ?? 0;
                $m['so_luong_dat'] = 0;
            }

            $currentStats = $service->getFilterStats($batchId);
            if ($currentStats['status'] && !empty($currentStats['data'])) {
                 foreach ($majors as &$m) {
                     $mCode = $m['ma_nganh'];
                     $m['so_luong_dat'] = $currentStats['data'][$mCode] ?? 0;
                 }
            }

            $this->json(['status' => true, 'majors' => $majors]);
        } catch (\Throwable $e) {
            $this->json(['status' => false, 'message' => 'Lỗi tải dữ liệu: ' . $e->getMessage()]);
        }
    }

    // API: Kích hoạt chạy lại hệ thống tính TỔNG ĐIỂM (ScoreCalculationService) cho đợt tuyển
    public function recalculateScores() {
        set_time_limit(300);
        try {
            $batchId = $_POST['batch_id'] ?? 0;
            if (!$batchId) {
                $this->json(['status' => false, 'message' => 'Missing batch ID']);
                return;
            }

            $sessionModel = new \App\Models\AdmissionSession();
            if ($sessionModel->isLocked($batchId)) {
                $this->json(['status' => false, 'message' => 'Đợt xét tuyển này đã bị khóa. Vui lòng mở khóa để thực hiện thao tác.']);
                return;
            }

            $sql = "
                SELECT DISTINCT nv.so_cccd 
                FROM nguyen_vong nv
                WHERE nv.dot_tuyen_sinh_id = ? 
                AND NOT EXISTS (SELECT 1 FROM v_calc_summary cs WHERE cs.nguyen_vong_id = nv.id)
            ";
            
            if (isset($_POST['force']) && $_POST['force'] == '1') {
                $sql = "SELECT DISTINCT so_cccd FROM nguyen_vong WHERE dot_tuyen_sinh_id = ?";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$batchId]);
            $cccds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $force = isset($_POST['force']) && $_POST['force'] == '1';
            $chunks = array_chunk($cccds, 500);
            $count = 0;
            $service = $this->getScoreService();
            foreach ($chunks as $chunk) {
                $service->recalculateBatch($batchId, $chunk, $force);
                $count += count($chunk);
            }

            $this->json(['status' => true, 'message' => "Đã tính điểm thành công cho $count thí sinh."]);
        } catch (\Throwable $e) {
            $this->json(['status' => false, 'message' => 'Lỗi tính điểm: ' . $e->getMessage()]);
        }
    }

    // API: Thực hiện thuật toán Lọc Ảo ưu tiên (Trượt dây chuyền)
    public function runFiltering() {
        @ini_set('display_errors', '0');
        @error_reporting(0);
        ob_start();

        try {
            $batchId = $_POST['session_id'] ?? ($_POST['batch_id'] ?? 0);
            $benchmarks = $_POST['benchmarks'] ?? []; 
            $quotas = $_POST['quotas'] ?? []; 
            $mode = $_POST['mode'] ?? 'chinh_thuc'; // 'hoc_ba' | 'chinh_thuc'
            $isHocBa = ($mode === 'hoc_ba');

            if (is_string($benchmarks)) {
                $benchmarks = json_decode($benchmarks, true) ?? [];
            }

            if (!$batchId) {
                $this->json(['status' => false, 'message' => 'Thiếu ID đợt tuyển sinh (session_id)']);
                return;
            }

            $sessionModel = new \App\Models\AdmissionSession();
            if ($sessionModel->isLocked($batchId)) {
                $this->json(['status' => false, 'message' => 'Đợt xét tuyển này đã bị khóa. Vui lòng mở khóa để thực hiện thao tác.']);
                return;
            }

            $service = $this->getFilterService();

            if (empty($benchmarks)) {
                $saved = $service->getExpectedBenchmarks($batchId);
                foreach ($saved as $s) {
                    $benchmarks[$s['ma_nganh']] = $s['diem_chuan'];
                    $quotas[$s['ma_nganh']] = $s['chi_tieu_du_kien'];
                }
            }

            if (empty($benchmarks)) {
                $this->json(['status' => false, 'message' => 'Chưa thiết lập điểm chuẩn. Hãy nhập điểm chuẩn dự kiến cho các ngành.']);
                return;
            }

            if (!empty($_POST['benchmarks'])) {
                $service->saveExpectedBenchmarks($batchId, $benchmarks, $quotas);
            }

            $result = $service->runVirtualFilter($batchId, $benchmarks, $isHocBa);
            
            if (ob_get_length() > 0) ob_clean();
            $this->json($result);
        } catch (\Throwable $e) {
            if (ob_get_length() > 0) ob_clean();
            $this->json(['status' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }
}
