<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Models\AdmissionSession;
use App\Models\Application;
use App\Models\NguyenVong;
use App\Services\ScoreCalculator;
use App\Services\EmailTemplateService;

class AdmissionController extends Controller {
    
    protected $masterData;
    protected $currentUser;
    protected $emailService;
    protected $scoreCalculator;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->masterData = new MasterData();
        $this->scoreCalculator = new \App\Services\ScoreCalculator();
        $this->emailService = new EmailTemplateService();

        // Load current user and check permission
        $adminModel = new \App\Models\QuanTriVien();
        $this->currentUser = $adminModel->find($_SESSION['admin_id']);

        if (!$this->currentUser || !$this->currentUser['is_active']) {
             session_destroy();
             $this->redirect(url('/admin/login'));
        }
    }

    public function benchmarks() {
        $majors = $this->masterData->getMajors();
        
        // Fetch existing benchmarks (Assume stored in settings or a new table `major_benchmarks`?)
        // Let's use `dm_nganh` column `diem_chuan` (legacy) or `diem_nam_truoc` or a new table.
        // Implementation Plan said: "Giao diện nhập diem_chuan cho từng Ngành".
        // Let's use a new table `major_benchmarks` for session-specific or just update `dm_nganh.diem_chuan` for simplicity if session-less.
        // Better: `admission_benchmarks` table with `session_id`.
        
        // checking if `admission_benchmarks` exists
        $db = \App\Core\Database::getInstance()->getConnection();
        
        // Create table if not exists (Lazy migration)
        $db->exec("CREATE TABLE IF NOT EXISTS admission_benchmarks (
            id SERIAL PRIMARY KEY,
            session_id INT,
            ma_nganh VARCHAR(50),
            diem_chuan FLOAT,
            tieuchi_phu FLOAT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Get active session
        $sessionModel = new AdmissionSession();
        $activeSession = $sessionModel->getActiveSession();
        $sessionId = $activeSession['id'] ?? 0;

        $stmt = $db->prepare("SELECT ma_nganh, diem_chuan, tieuchi_phu FROM admission_benchmarks WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $benchmarks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $benchmarkMap = [];
        foreach ($benchmarks as $b) {
            $benchmarkMap[$b['ma_nganh']] = $b;
        }

        $this->view('admin/admission/benchmarks', [
            'majors' => $majors,
            'benchmarks' => $benchmarkMap,
            'activeSession' => $activeSession
        ]);
    }

    public function saveBenchmarks() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $sessionModel = new AdmissionSession();
        $activeSession = $sessionModel->getActiveSession();
        $sessionId = $activeSession['id'] ?? 0;

        $data = $_POST['benchmarks'] ?? [];
        $db = \App\Core\Database::getInstance()->getConnection();

        $db->beginTransaction();
        try {
            // Clear old for this session (Simple approach)
            // Or Upsert. Let's do delete-insert for simplicity or Upsert if supported.
            // Postgres supports ON CONFLICT.
            
            $stmt = $db->prepare("INSERT INTO admission_benchmarks (session_id, ma_nganh, diem_chuan, tieuchi_phu) 
                VALUES (?, ?, ?, ?) 
                ON CONFLICT (id) DO UPDATE SET diem_chuan = EXCLUDED.diem_chuan"); 
            // Wait, ID is primary key. To use upsert logic on conflict we need unique constraint on session_id + ma_nganh
            
            // Re-define table constraint if needed or just use Delete/Insert
            $db->prepare("DELETE FROM admission_benchmarks WHERE session_id = ?")->execute([$sessionId]);
            
            $insert = $db->prepare("INSERT INTO admission_benchmarks (session_id, ma_nganh, diem_chuan, tieuchi_phu) VALUES (?, ?, ?, ?)");
            
            foreach ($data as $ma_nganh => $values) {
                $score = floatval($values['score']);
                $sub = floatval($values['sub_score'] ?? 0);
                if ($score > 0) {
                    $insert->execute([$sessionId, $ma_nganh, $score, $sub]);
                }
            }
            
            $db->commit();
            $this->redirect(url('/admin/admission/benchmarks?status=success'));
        } catch (\Exception $e) {
            $db->rollBack();
            // log error
            $this->redirect(url('/admin/admission/benchmarks?status=error'));
        }
    }

    public function process() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        // 1. Get Active Session
        $sessionModel = new AdmissionSession();
        $activeSession = $sessionModel->getActiveSession();
        if (!$activeSession) {
             $this->redirect(url('/admin/admission/benchmarks?error=no_session'));
             return;
        }

        // 2. Call AdmissionService
        $service = new \App\Services\AdmissionService();
        $result = $service->calculateBatchScores($activeSession['id']);

        // 3. Redirect
        if ($result['status']) {
            $msg = "Calculated scores for " . $result['processed'] . " candidates.";
            if (!empty($result['errors'])) {
                $msg .= " With " . count($result['errors']) . " errors.";
            }
            $this->redirect(url('/admin/admission/results?message=' . urlencode($msg)));
        } else {
             $this->redirect(url('/admin/admission/benchmarks?error=calc_failed'));
        }
    }

    public function results() {
        $db = \App\Core\Database::getInstance()->getConnection();
        $sessionModel = new AdmissionSession();
        $activeSession = $sessionModel->getActiveSession();
        
        // Filter by Major if requested
        $filterMajor = $_GET['major'] ?? '';
        
        $sql = "SELECT nv.*, t.ho_va_ten, t.so_cccd 
                FROM nguyen_vong nv
                JOIN thi_sinh t ON nv.so_cccd = t.so_cccd
                WHERE nv.dot_tuyen_sinh_id = ?";
        
        $params = [$activeSession['id'] ?? 0];

        if ($filterMajor) {
            $sql .= " AND nv.ma_nganh = ?";
            $params[] = $filterMajor;
        }

        $sql .= " ORDER BY nv.ma_nganh, nv.diem_xet_tuyen DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Group by Major
        $grouped = [];
        foreach ($results as $r) {
            $grouped[$r['ma_nganh']][] = $r;
        }

        $this->view('admin/admission/results', [
            'groupedResults' => $grouped, 
            'majors' => $this->masterData->getMajors(),
            'filterMajor' => $filterMajor
        ]);
    }
    public function finalize() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $sessionModel = new AdmissionSession();
        $activeSession = $sessionModel->getActiveSession();
        if (!$activeSession) {
             $this->redirect(url('/admin/admission/benchmarks?error=no_session'));
             return;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        
        // 1. Get Benchmarks
        $stmt = $db->prepare("SELECT ma_nganh, diem_chuan, tieuchi_phu FROM admission_benchmarks WHERE session_id = ?");
        $stmt->execute([$activeSession['id']]);
        $benchmarks = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $benchmarks[$row['ma_nganh']] = $row;
        }

        // 2. Get Candidates with Aspirations
        // We need to process candidate by candidate.
        // Fetch ordered by CCCD, NV_Order
        $sql = "SELECT * FROM nguyen_vong WHERE dot_tuyen_sinh_id = ? ORDER BY so_cccd, thu_tu_nguyen_vong ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$activeSession['id']]);
        $allAspirations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Group by CCCD
        $candidates = [];
        foreach ($allAspirations as $asp) {
            $candidates[$asp['so_cccd']][] = $asp;
        }

        $countPassed = 0;

        $db->beginTransaction();
        try {
            foreach ($candidates as $cccd => $aspirations) {
                $hasPassed = false;
                foreach ($aspirations as $asp) {
                    $ma_nganh = $asp['ma_nganh'];
                    $score = floatval($asp['diem_xet_tuyen']); // Calculated in Phase 5
                    
                    // If score is 0, they failed calculation or missing data -> Fail
                    if ($score <= 0) {
                        $db->prepare("UPDATE nguyen_vong SET trang_thai = 'Truot' WHERE id = ?")->execute([$asp['id']]);
                        continue;
                    }

                    $benchmarkData = $benchmarks[$ma_nganh] ?? null;
                    $benchmark = $benchmarkData ? floatval($benchmarkData['diem_chuan']) : 999; // Default high if no benchmark set
                    
                    $status = 'Truot';
                    
                    if (!$hasPassed && $score >= $benchmark) {
                        // Check sub-criteria if equal? (Skipped for now for simplicity, assuming score is precise enough or manual review)
                        $status = 'Trung tuyen';
                        $hasPassed = true;
                        $countPassed++;
                    } elseif ($hasPassed) {
                        $status = 'Truot (NV cao hon)';
                    }

                    // Update
                    $upd = $db->prepare("UPDATE nguyen_vong SET trang_thai = ? WHERE id = ?");
                    $upd->execute([$status, $asp['id']]);
                }
            }
            $db->commit();
            $this->redirect(url('/admin/admission/results?message=' . urlencode("Đã công bố kết quả. $countPassed nguyện vọng trúng tuyển.")));
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("AdmissionController::finalize Error: " . $e->getMessage());
            $this->redirect(url('/admin/admission/benchmarks?status=error'));
        }
    }
    public function notifyAdmitted() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $maNganh = $_POST['ma_nganh'] ?? '';
        
        $db = \App\Core\Database::getInstance()->getConnection();
        
        // Query Admitted Candidates
        $sql = "SELECT t.so_cccd, t.ho_va_ten, t.email, nv.ma_nganh, n.ten_nganh, nv.diem_xet_tuyen 
                FROM nguyen_vong nv
                JOIN thi_sinh t ON nv.so_cccd = t.so_cccd
                JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh
                WHERE (nv.trang_thai = 'Trung tuyen' OR nv.trang_thai = 'Trúng tuyển')";
                
        $params = [];
        if ($maNganh) {
            $sql .= " AND nv.ma_nganh = ?";
            $params[] = $maNganh;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $count = 0;
        $errors = 0;
        
        foreach ($candidates as $cand) {
            if (empty($cand['email'])) continue;
            
            $data = [
                'ho_ten' => $cand['ho_va_ten'],
                'cccd' => $cand['so_cccd'],
                'ten_nganh' => $cand['ten_nganh'],
                'ma_nganh' => $cand['ma_nganh'],
                'diem_xet_tuyen' => number_format($cand['diem_xet_tuyen'], 2),
                'login_url' => url('/login') // Helper function url() assumed globally available
            ];
            
            // Use 'admission_success' template code
            $res = $this->emailService->sendWithTemplate($cand['email'], 'admission_success', $data);
            
            if ($res === true) {
                $count++;
            } else {
                $errors++;
                error_log("Failed to send email to " . $cand['email'] . ": " . print_r($res, true));
            }
        }
        
        $msg = "Đã gửi email trúng tuyển cho $count thí sinh.";
        if ($errors > 0) $msg .= " ($errors thất bại. Kiểm tra log).";
        
        $redirectUrl = '/admin/admission/results' . ($maNganh ? '?major=' . $maNganh : '');
        $this->redirect(url($redirectUrl . '&message=' . urlencode($msg)));
    }
}
