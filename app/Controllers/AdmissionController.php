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
                $score = round(floatval($values['score']), 2);
                $sub = $values['sub_score'] ?? '';
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
        
        // Session selector: allow viewing any session, default to active
        $allSessions = $sessionModel->getAll();
        $selectedSessionId = $_GET['session_id'] ?? null;
        
        if ($selectedSessionId) {
            $activeSession = null;
            foreach ($allSessions as $s) {
                if ($s['id'] == $selectedSessionId) { $activeSession = $s; break; }
            }
            if (!$activeSession) $activeSession = $sessionModel->getActiveSession();
        } else {
            $activeSession = $sessionModel->getActiveSession();
        }
        $sessionId = $activeSession['id'] ?? 0;

        // Filters (for initial page state, API will handle actual data loading)
        $filterMajor = $_GET['major'] ?? '';
        $filterStatus = $_GET['status'] ?? '';
        $showAll = isset($_GET['show_all']) && $_GET['show_all'] == '1';

        // 1. Global Stats
        $statsSql = "SELECT 
                        COUNT(DISTINCT nv.so_cccd) as total_candidates,
                        COUNT(*) as total_wishes,
                        COUNT(*) FILTER (WHERE nv.trang_thai IN ('Trung tuyen', 'Trúng tuyển') OR cs.trang_thai_trung_tuyen = TRUE) as total_admitted,
                        COUNT(*) FILTER (WHERE (nv.trang_thai IN ('Trung tuyen', 'Trúng tuyển') OR cs.trang_thai_trung_tuyen = TRUE) AND COALESCE(nv.thu_tu_nv_bo, nv.thu_tu_nguyen_vong) = 1) as nv1_admit,
                        COUNT(*) FILTER (WHERE (nv.trang_thai IN ('Trung tuyen', 'Trúng tuyển') OR cs.trang_thai_trung_tuyen = TRUE) AND COALESCE(nv.thu_tu_nv_bo, nv.thu_tu_nguyen_vong) = 2) as nv2_admit,
                        COUNT(*) FILTER (WHERE (nv.trang_thai IN ('Trung tuyen', 'Trúng tuyển') OR cs.trang_thai_trung_tuyen = TRUE) AND COALESCE(nv.thu_tu_nv_bo, nv.thu_tu_nguyen_vong) = 3) as nv3_admit
                     FROM nguyen_vong nv
                     LEFT JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                     WHERE nv.dot_tuyen_sinh_id = ?";
        $statsStmt = $db->prepare($statsSql);
        $statsStmt->execute([$sessionId]);
        $stats = $statsStmt->fetch(\PDO::FETCH_ASSOC);

        // 2. Per-major stats (admitted vs chi_tieu)  
        $majorStatsSql = "SELECT n.ma_nganh, n.ten_nganh, n.chi_tieu, n.nhom_nganh,
                            COUNT(*) FILTER (WHERE nv.trang_thai IN ('Trung tuyen', 'Trúng tuyển') OR cs.trang_thai_trung_tuyen = TRUE) as so_trung_tuyen,
                            COUNT(*) as tong_nguyen_vong,
                            MAX(cs.diem_xet_tuyen) FILTER (WHERE nv.trang_thai IN ('Trung tuyen', 'Trúng tuyển') OR cs.trang_thai_trung_tuyen = TRUE) as diem_cao_nhat,
                            MIN(cs.diem_xet_tuyen) FILTER (WHERE nv.trang_thai IN ('Trung tuyen', 'Trúng tuyển') OR cs.trang_thai_trung_tuyen = TRUE) as diem_thap_nhat
                          FROM dm_nganh n
                          LEFT JOIN nguyen_vong nv ON n.ma_nganh = nv.ma_nganh AND nv.dot_tuyen_sinh_id = ?
                          LEFT JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                          GROUP BY n.ma_nganh, n.ten_nganh, n.chi_tieu, n.nhom_nganh
                          ORDER BY n.ma_nganh";
        $majorStatsStmt = $db->prepare($majorStatsSql);
        $majorStatsStmt->execute([$sessionId]);
        $majorStats = $majorStatsStmt->fetchAll(\PDO::FETCH_ASSOC);

        // 3. Demographics for Charts
        $demoSql = "SELECT t.gioi_tinh, t.khu_vuc_uu_tien, t.doi_tuong_uu_tien, 
                           COALESCE(dt.ten_tinh, t.ma_tinh_lop_12) as ten_tinh, 
                           COALESCE(dthpt.ten_truong, t.ma_truong_lop_12) as ten_truong
                    FROM nguyen_vong nv
                    JOIN thi_sinh t ON nv.so_cccd = t.so_cccd
                    LEFT JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                    LEFT JOIN dm_tinh dt ON t.ma_tinh_lop_12 = dt.ma_tinh
                    LEFT JOIN dm_truong_thpt dthpt ON t.ma_truong_lop_12 = dthpt.ma_truong AND t.ma_tinh_lop_12 = dthpt.ma_tinh AND dthpt.is_active = TRUE
                    WHERE nv.dot_tuyen_sinh_id = ? AND (nv.trang_thai IN ('Trung tuyen', 'Trúng tuyển') OR cs.trang_thai_trung_tuyen = TRUE)";
        $demoStmt = $db->prepare($demoSql);
        $demoStmt->execute([$sessionId]);
        $demoRows = $demoStmt->fetchAll(\PDO::FETCH_ASSOC);

        $chartDist = [
            'gender' => [],
            'area' => [],
            'object' => [],
            'province' => [],
            'school' => []
        ];

        foreach ($demoRows as $r) {
            $gt = trim($r['gioi_tinh']);
            // Standardize gender
            if (strcasecmp($gt, 'Nam') === 0 || $gt === '1') $gt = 'Nam';
            elseif (strcasecmp($gt, 'Nữ') === 0 || strcasecmp($gt, 'Nu') === 0 || $gt === '0') $gt = 'Nữ';
            else $gt = 'Khác';
            $chartDist['gender'][$gt] = ($chartDist['gender'][$gt] ?? 0) + 1;
            
            $ar = $r['khu_vuc_uu_tien'] ?: 'Khác';
            $chartDist['area'][$ar] = ($chartDist['area'][$ar] ?? 0) + 1;

            $obj = $r['doi_tuong_uu_tien'] ?: 'Không';
            $chartDist['object'][$obj] = ($chartDist['object'][$obj] ?? 0) + 1;

            $prov = $r['ten_tinh'] ?: 'Khác';
            $chartDist['province'][$prov] = ($chartDist['province'][$prov] ?? 0) + 1;

            $sch = $r['ten_truong'] ?: 'Khác';
            $chartDist['school'][$sch] = ($chartDist['school'][$sch] ?? 0) + 1;
        }

        // Sort province and school by count DESC to pick top
        arsort($chartDist['province']);
        arsort($chartDist['school']);

        // 4. Page visit stats for Calculator
        $visitStatsSql = "SELECT 
                            COUNT(*) as total_visits,
                            COUNT(*) FILTER (WHERE created_at >= date_trunc('week', CURRENT_DATE)) as weekly_visits,
                            COUNT(*) FILTER (WHERE created_at >= CURRENT_DATE) as daily_visits
                          FROM page_views 
                          WHERE url = '/tinh-diem-xet-tuyen'";
        $visitStatsStmt = $db->query($visitStatsSql);
        $visitStats = $visitStatsStmt->fetch(\PDO::FETCH_ASSOC);

        $this->view('admin/admission/results', [
            'stats' => $stats,
            'visitStats' => $visitStats,
            'majorStats' => $majorStats,
            'chartDist' => $chartDist,
            'majors' => $this->masterData->getMajors(),
            'filterMajor' => $filterMajor,
            'filterStatus' => $filterStatus,
            'showAll' => $showAll,
            'activeSession' => $activeSession,
            'allSessions' => $allSessions
        ]);
    }

    /**
     * SSP API endpoint for the results table (AJAX DataTables)
     */
    public function resultsApi() {
        $db = \App\Core\Database::getInstance()->getConnection();
        
        $sessionId = $_GET['session_id'] ?? 0;
        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 50);
        $search = trim($_GET['search'] ?? '');
        $filterMajor = $_GET['major'] ?? '';
        $filterStatus = $_GET['status'] ?? '';
        $showAll = ($_GET['show_all'] ?? '0') === '1';

        if ($length < 1) $length = 50;
        if ($length > 200) $length = 200;

        // Base query
        $baseFrom = "FROM nguyen_vong nv
                     JOIN thi_sinh t ON nv.so_cccd = t.so_cccd
                     JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh
                     LEFT JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                     WHERE nv.dot_tuyen_sinh_id = ?";
        $params = [$sessionId];

        // Status filter
        if ($filterStatus === 'Trung tuyen') {
            $baseFrom .= " AND (nv.trang_thai IN ('Trung tuyen', 'Trúng tuyển') OR cs.trang_thai_trung_tuyen = TRUE)";
        } elseif ($filterStatus) {
            $baseFrom .= " AND nv.trang_thai = ?";
            $params[] = $filterStatus;
        } elseif (!$showAll) {
            $baseFrom .= " AND (nv.trang_thai IN ('Trung tuyen', 'Trúng tuyển') OR cs.trang_thai_trung_tuyen = TRUE)";
        }

        // Major filter  
        if ($filterMajor) {
            $baseFrom .= " AND nv.ma_nganh = ?";
            $params[] = $filterMajor;
        }

        // Search filter
        $searchSql = "";
        if (!empty($search)) {
            $searchSql = " AND (t.ho_va_ten ILIKE ? OR t.so_cccd ILIKE ? OR nv.ma_nganh ILIKE ? OR n.ten_nganh ILIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        // Count total (without search)
        $paramsNoSearch = array_slice($params, 0, count($params) - ($search ? 4 : 0));
        $stmtTotal = $db->prepare("SELECT COUNT(*) $baseFrom");
        $stmtTotal->execute($paramsNoSearch);
        $recordsTotal = $stmtTotal->fetchColumn() ?: 0;

        // Count filtered (with search)
        if (!empty($search)) {
            $stmtFiltered = $db->prepare("SELECT COUNT(*) $baseFrom $searchSql");
            $stmtFiltered->execute($params);
            $recordsFiltered = $stmtFiltered->fetchColumn() ?: 0;
        } else {
            $recordsFiltered = $recordsTotal;
        }

        // Data query
        $dataSql = "SELECT nv.id, nv.so_cccd, nv.ma_nganh, nv.thu_tu_nguyen_vong, nv.thu_tu_nv_bo, nv.trang_thai,
                           nv.phuong_thuc_xet_tuyen,
                           t.ho_va_ten, t.khu_vuc_uu_tien, t.doi_tuong_uu_tien,
                           n.ten_nganh, n.nhom_nganh,
                           cs.diem_xet_tuyen, cs.to_hop_toi_uu, cs.phuong_thuc_toi_uu,
                           cs.chi_tiet_diem, cs.trang_thai_trung_tuyen,
                           cs.diem_mon_1, cs.diem_mon_2, cs.diem_mon_3
                    $baseFrom $searchSql
                    ORDER BY nv.ma_nganh, cs.diem_xet_tuyen DESC NULLS LAST, COALESCE(nv.thu_tu_nv_bo, nv.thu_tu_nguyen_vong) ASC
                    LIMIT $length OFFSET $start";
        
        $stmt = $db->prepare($dataSql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Process rows
        foreach ($rows as &$row) {
            $row['is_pass'] = ($row['trang_thai'] == 'Trung tuyen' || $row['trang_thai'] == 'Trúng tuyển' || ($row['trang_thai_trung_tuyen'] ?? false));
            if (!empty($row['chi_tiet_diem'])) {
                $row['chi_tiet_diem'] = json_decode($row['chi_tiet_diem'], true);
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => intval($recordsTotal),
            'recordsFiltered' => intval($recordsFiltered),
            'data' => $rows
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Bulk email to selected candidates
     */
    public function bulkEmail() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        $sessionId = $_POST['session_id'] ?? 0;
        
        if (empty($ids)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Chưa chọn thí sinh nào.']);
            exit;
        }
        
        $db = \App\Core\Database::getInstance()->getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $sql = "SELECT t.so_cccd, t.ho_va_ten, t.email, nv.ma_nganh, n.ten_nganh, nv.trang_thai, cs.diem_xet_tuyen
                FROM nguyen_vong nv
                JOIN thi_sinh t ON nv.so_cccd = t.so_cccd
                JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh
                LEFT JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                WHERE nv.id IN ($placeholders)
                AND (nv.trang_thai IN ('Trung tuyen', 'Trúng tuyển') OR cs.trang_thai_trung_tuyen = TRUE)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($ids);
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
                'login_url' => url('/login')
            ];
            
            $res = $this->emailService->sendWithTemplate($cand['email'], 'admission_success', $data);
            if ($res === true) { $count++; } else { $errors++; }
        }
        
        // Audit log
        $auditService = new \App\Services\AuditService();
        $auditService->log('BULK_EMAIL_SENT', 'admission', null, null, [
            'count' => $count,
            'errors' => $errors,
            'session_id' => $sessionId
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "Đã gửi email cho $count thí sinh." . ($errors > 0 ? " ($errors lỗi)" : "")
        ]);
        exit;
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
        $sql = "SELECT * FROM nguyen_vong WHERE dot_tuyen_sinh_id = ? ORDER BY so_cccd, COALESCE(thu_tu_nv_bo, thu_tu_nguyen_vong) ASC";
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
            
            // Audit log
            $auditService = new \App\Services\AuditService();
            $auditService->log('ADMISSION_FINALIZED', 'admission', null, null, [
                'session_id' => $activeSession['id'],
                'count_passed' => $countPassed
            ]);

            $this->redirect(url('/admin/admission/results?message=' . urlencode("Đã công bố kết quả. $countPassed nguyện vọng trúng tuyển.")));
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("AdmissionController::finalize Error: " . $e->getMessage());
            $this->redirect(url('/admin/admission/benchmarks?status=error'));
        }
    }
    public function notify() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $maNganh = $_POST['major'] ?? $_POST['ma_nganh'] ?? '';
        $statusFilter = $_POST['status'] ?? '';
        $showAll = isset($_POST['show_all']) && $_POST['show_all'] == '1';
        
        $db = \App\Core\Database::getInstance()->getConnection();
        
        // 1. Build Query for filtered list
        $sql = "SELECT t.so_cccd, t.ho_va_ten, t.email, nv.ma_nganh, n.ten_nganh, nv.trang_thai, cs.diem_xet_tuyen 
                FROM nguyen_vong nv
                JOIN thi_sinh t ON nv.so_cccd = t.so_cccd
                JOIN dm_nganh n ON nv.ma_nganh = n.ma_nganh
                LEFT JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                WHERE (nv.trang_thai = 'Trung tuyen' OR nv.trang_thai = 'Trúng tuyển')";
                
        $params = [];
        if ($maNganh) {
            $sql .= " AND nv.ma_nganh = ?";
            $params[] = $maNganh;
        }

        // Note: Even if showAll is true, we ONLY notify those who PASS for now
        // since the template is 'admission_success'.
        
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
        
        $redirectUrl = '/admin/admission/results?message=' . urlencode($msg);
        if ($maNganh) $redirectUrl .= '&major=' . $maNganh;
        if ($showAll) $redirectUrl .= '&show_all=1';
        
        $this->redirect(url($redirectUrl));
    }
}
