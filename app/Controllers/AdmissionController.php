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
            if (!$activeSession) $activeSession = $sessionModel->getActiveSession() ?: $sessionModel->getLatestSession();
        } else {
            // Fallback: nếu không có session đang kích hoạt hoặc trong thời hạn,
            // lấy session mới nhất để hiển thị kết quả lọc ảo gần nhất
            $activeSession = $sessionModel->getActiveSession() ?: $sessionModel->getLatestSession();
        }
        $sessionId = $activeSession['id'] ?? 0;

        // Filters (for initial page state, API will handle actual data loading)
        $filterMajor = $_GET['major'] ?? '';
        $filterStatus = $_GET['status'] ?? '';
        $showAll = isset($_GET['show_all']) && $_GET['show_all'] == '1';

        // 1. Global Stats
        $statsSql = "SELECT 
                        COUNT(DISTINCT so_cccd) as total_candidates,
                        COUNT(*) as total_wishes,
                        COUNT(*) as total_admitted,
                        0 as nv1_admit,
                        0 as nv2_admit,
                        0 as nv3_admit
                     FROM ket_qua_trung_tuyen
                     WHERE session_id = ?";
        $statsStmt = $db->prepare($statsSql);
        $statsStmt->execute([$sessionId]);
        $stats = $statsStmt->fetch(\PDO::FETCH_ASSOC);

        // 2. Per-major stats (admitted vs chi_tieu)  
        $majorStatsSql = "SELECT n.ma_nganh, n.ten_nganh, n.chi_tieu, n.nhom_nganh,
                            COUNT(k.id) as so_trung_tuyen,
                            COUNT(k.id) as tong_nguyen_vong,
                            MAX(k.diem_xt) as diem_cao_nhat,
                            MIN(k.diem_xt) as diem_thap_nhat
                          FROM dm_nganh n
                          LEFT JOIN ket_qua_trung_tuyen k ON n.ma_nganh = k.ma_nganh AND k.session_id = ?
                          GROUP BY n.ma_nganh, n.ten_nganh, n.chi_tieu, n.nhom_nganh
                          ORDER BY n.ma_nganh";
        $majorStatsStmt = $db->prepare($majorStatsSql);
        $majorStatsStmt->execute([$sessionId]);
        $majorStats = $majorStatsStmt->fetchAll(\PDO::FETCH_ASSOC);

        // 3. Demographics for Charts
        $demoSql = "SELECT t.gioi_tinh, t.khu_vuc_uu_tien, t.doi_tuong_uu_tien, 
                           COALESCE(dt.ten_tinh, t.ma_tinh_lop_12) as ten_tinh, 
                           COALESCE(dthpt.ten_truong, t.ma_truong_lop_12) as ten_truong
                    FROM ket_qua_trung_tuyen k
                    JOIN thi_sinh t ON k.so_cccd = t.so_cccd
                    LEFT JOIN dm_tinh dt ON t.ma_tinh_lop_12 = dt.ma_tinh
                    LEFT JOIN dm_truong_thpt dthpt ON t.ma_truong_lop_12 = dthpt.ma_truong AND t.ma_tinh_lop_12 = dthpt.ma_tinh AND dthpt.is_active = TRUE
                    WHERE k.session_id = ?";
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
            $gt = trim($r['gioi_tinh'] ?? '');
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

        $allTemplates = $db->query("SELECT id, subject as name, code FROM email_templates")->fetchAll(\PDO::FETCH_ASSOC);
        $emailTemplates = \App\Core\Cache::remember('email_templates_all', 60, function () {
            $model = new \App\Models\EmailTemplate();
            return $model->getAll();
        });
        $currentTemplateId = null;
        if ($sessionId) {
            $stmt = $db->prepare("SELECT template_id FROM session_templates WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            $currentTemplateId = $stmt->fetchColumn();
        }

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
            'allSessions' => $allSessions,
            'allTemplates' => $allTemplates,
            'emailTemplates' => $emailTemplates,
            'currentTemplateId' => $currentTemplateId
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
        $baseFrom = "FROM ket_qua_trung_tuyen
                     WHERE session_id = ?";
        $params = [$sessionId];

        // Status filter (all uploaded rows in ket_qua_trung_tuyen are 'Trúng tuyển')
        // We can just omit filtering by status unless there's a reason.

        // Major filter  
        if ($filterMajor) {
            $baseFrom .= " AND ma_nganh = ?";
            $params[] = $filterMajor;
        }

        // Search filter
        $searchSql = "";
        if (!empty($search)) {
            $searchSql = " AND (ho_ten ILIKE ? OR so_cccd ILIKE ? OR ma_nganh ILIKE ? OR ten_nganh ILIKE ?)";
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
        $dataSql = "SELECT *
                    $baseFrom $searchSql
                    ORDER BY ma_nganh, diem_xt DESC NULLS LAST
                    LIMIT $length OFFSET $start";
        
        $stmt = $db->prepare($dataSql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Process rows
        foreach ($rows as &$row) {
            $row['is_pass'] = true; // Everyone in this table is admitted
            $row['chi_tiet_diem'] = null;
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

    public function import() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $sessionId = $_POST['session_id'] ?? '';
        if (empty($sessionId)) {
            $this->redirect(url('/admin/admission/results?error=' . urlencode('Vui lòng chọn đợt tuyển sinh.')));
        }

        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirect(url('/admin/admission/results?error=' . urlencode('Lỗi tải file. Vui lòng thử lại.')));
        }

        $fileTmpPath = $_FILES['excel_file']['tmp_name'];
        $fileName = $_FILES['excel_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, ['xls', 'xlsx'])) {
            $this->redirect(url('/admin/admission/results?error=' . urlencode('Chỉ hỗ trợ file Excel (.xls, .xlsx).')));
        }

        try {
            $service = new \App\Services\AdmissionResultService();
            $result = $service->importFromExcel($fileTmpPath, $sessionId);

            if ($result['status']) {
                $msg = "Đã tải lên thành công {$result['inserted']} thí sinh.";
                $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&success=' . urlencode($msg)));
            } else {
                $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&error=' . urlencode($result['message'])));
            }
        } catch (\Exception $e) {
            $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&error=' . urlencode('Đã xảy ra lỗi: ' . $e->getMessage())));
        }
    }

    public function clearBatch() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $sessionId = $_POST['session_id'] ?? '';
        if (empty($sessionId)) {
            $this->redirect(url('/admin/admission/results?error=' . urlencode('Dữ liệu không hợp lệ.')));
        }
        
        try {
            $service = new \App\Services\AdmissionResultService();
            $service->deleteBatch($sessionId);
            $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&success=' . urlencode('Đã xóa toàn bộ kết quả của đợt này.')));
        } catch (\Exception $e) {
            $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&error=' . urlencode('Có lỗi xảy ra: ' . $e->getMessage())));
        }
    }

    /**
     * Set Email template for session
     */
    public function setSessionTemplate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $sessionId = $_POST['session_id'] ?? '';
        $templateId = $_POST['template_id'] ?? '';
        
        if ($sessionId && $templateId) {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO session_templates (session_id, template_id) VALUES (?, ?) ON CONFLICT (session_id) DO UPDATE SET template_id = EXCLUDED.template_id");
            $stmt->execute([$sessionId, $templateId]);
            $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&success=' . urlencode('Đã lưu thiết lập mẫu email thành công.')));
        }
        $this->redirect(url('/admin/admission/results'));
    }

    /**
     * Sync kết quả lọc ảo từ v_calc_summary → nguyen_vong.trang_thai
     * Cập nhật trạng thái 'Trúng tuyển' hoặc 'Không đạt' cho các nguyện vọng đã tính điểm
     */
    public function syncVirtualResults() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $db = \App\Core\Database::getInstance()->getConnection();
        $sessionModel = new AdmissionSession();
        
        $sessionId = intval($_POST['session_id'] ?? 0);
        if (!$sessionId) {
            $activeSession = $sessionModel->getActiveSession() ?: $sessionModel->getLatestSession();
            $sessionId = $activeSession['id'] ?? 0;
        }
        
        if (!$sessionId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đợt tuyển sinh.']);
            exit;
        }
        
        try {
            // 0. Reset kết quả đồng bộ cũ về 'DaDuyet' để tránh trùng lặp khi chạy lại
            $stmtReset = $db->prepare("
                UPDATE nguyen_vong
                SET trang_thai = 'DaDuyet'
                WHERE dot_tuyen_sinh_id = ?
                  AND trang_thai IN ('Trúng tuyển', 'Trung tuyen', 'Không đạt')
            ");
            $stmtReset->execute([$sessionId]);

            // 1. Cập nhật trạng thái Trúng tuyển cho NV đỗ theo lọc ảo
            $stmtPass = $db->prepare("
                UPDATE nguyen_vong nv
                SET trang_thai = 'Trúng tuyển'
                FROM v_calc_summary cs
                WHERE nv.id = cs.nguyen_vong_id
                  AND nv.dot_tuyen_sinh_id = ?
                  AND cs.trang_thai_trung_tuyen = TRUE
                  AND nv.trang_thai NOT IN ('Trúng tuyển', 'Trung tuyen')
            ");
            $stmtPass->execute([$sessionId]);
            $passCount = $stmtPass->rowCount();
            
            // 2. Cập nhật trạng thái Không đạt cho NV rớt (có điểm trong v_calc_summary nhưng không trúng tuyển)
            $stmtFail = $db->prepare("
                UPDATE nguyen_vong nv
                SET trang_thai = 'Không đạt'
                FROM v_calc_summary cs
                WHERE nv.id = cs.nguyen_vong_id
                  AND nv.dot_tuyen_sinh_id = ?
                  AND cs.trang_thai_trung_tuyen = FALSE
                  AND nv.trang_thai IN ('DaDuyet', 'Đã duyệt')
            ");
            $stmtFail->execute([$sessionId]);
            $failCount = $stmtFail->rowCount();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => "Đồng bộ thành công: {$passCount} trúng tuyển, {$failCount} không đạt được cập nhật.",
                'pass_count' => $passCount,
                'fail_count' => $failCount
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Lỗi đồng bộ: ' . $e->getMessage()]);
            exit;
        }
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
        
        $sql = "SELECT id, so_cccd, ho_ten, email, ma_nganh, ten_nganh, diem_xt
                FROM ket_qua_trung_tuyen
                WHERE id IN ($placeholders)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($ids);
        $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $count = 0;
        $errors = 0;
        
        // Find template code
        $tplIdStmt = $db->prepare("SELECT template_id FROM session_templates WHERE session_id = ?");
        $tplIdStmt->execute([$sessionId]);
        $templateId = $tplIdStmt->fetchColumn();
        
        $templateCode = 'admission_success';
        if ($templateId) {
            $tplStmt = $db->prepare("SELECT code FROM email_templates WHERE id = ?");
            $tplStmt->execute([$templateId]);
            $customCode = $tplStmt->fetchColumn();
            if ($customCode) {
                $templateCode = $customCode;
            }
        }
        
        $customSubject = $_POST['email_subject'] ?? '';
        $customContent = $_POST['email_content'] ?? '';
        
        $mailer = new \App\Services\MailerService();
        
        foreach ($candidates as $cand) {
            if (empty($cand['email'])) continue;
            
            $data = [
                'ho_ten' => $cand['ho_ten'],
                'name' => $cand['ho_ten'],
                'cccd' => $cand['so_cccd'],
                'ten_nganh' => $cand['ten_nganh'],
                'ma_nganh' => $cand['ma_nganh'],
                'diem_xet_tuyen' => number_format((float)$cand['diem_xt'], 2),
                'login_url' => url('/login')
            ];
            
            if (!empty($customSubject) && !empty($customContent)) {
                $subject = $customSubject;
                $body = $customContent;
                foreach ($data as $key => $value) {
                    $subject = str_replace('{{' . $key . '}}', htmlspecialchars($value ?? ''), $subject);
                    $body = str_replace('{{' . $key . '}}', $value ?? '', $body);
                }
                $res = $mailer->send($cand['email'], $subject, $body, true, 'system');
            } else {
                $res = $this->emailService->sendWithTemplate($cand['email'], $templateCode, $data);
            }
            
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

    /**
     * Toggle Admission Results Publishing
     */
    public function togglePublish() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $sessionId = $_POST['session_id'] ?? 0;
        $status = $_POST['status'] ?? 0; // 1 to publish, 0 to unpublish
        
        if ($sessionId) {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE dot_tuyen_sinh SET is_published_results = ? WHERE id = ?");
            $stmt->execute([$status ? 'true' : 'false', $sessionId]);
            
            $msg = $status ? 'Đã công bố kết quả xét tuyển cho đợt này.' : 'Đã hủy công bố kết quả xét tuyển.';
            $this->redirect(url('/admin/admission/results?session_id=' . $sessionId . '&success=' . urlencode($msg)));
        } else {
            $this->redirect(url('/admin/admission/results?error=' . urlencode('Vui lòng chọn đợt tuyển sinh.')));
        }
    }

    public function syncFromVirtualFilter() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $sessionId = $_POST['session_id'] ?? 0;
        if (!$sessionId) {
            $this->redirect(url('/admin/admission/results?error=' . urlencode('Vui lòng chọn đợt tuyển sinh.')));
            return;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();

            // 1. Xóa kết quả hiện tại của đợt
            $db->prepare("DELETE FROM ket_qua_trung_tuyen WHERE session_id = ?")->execute([$sessionId]);

            // 2. Insert từ v_calc_summary (chỉ những người có trang_thai_trung_tuyen = TRUE)
            $sql = "INSERT INTO ket_qua_trung_tuyen (
                        session_id, so_cccd, ho_ten, ngay_sinh, sbd, khu_vuc, doi_tuong, to_hop,
                        diem_mon_1, diem_mon_2, diem_mon_3, diem_to_hop, diem_ut, ut_quy_doi,
                        diem_xt, ma_nganh, ten_nganh, phuong_thuc, email, sdt, ghi_chu
                    )
                    SELECT 
                        ?, nv.so_cccd, ts.ho_va_ten, ts.ngay_sinh, COALESCE(ts.so_bao_danh, ''), ts.khu_vuc_uu_tien, ts.doi_tuong_uu_tien, cs.to_hop_toi_uu,
                        cs.diem_mon_1, cs.diem_mon_2, cs.diem_mon_3, (COALESCE(cs.diem_mon_1,0) + COALESCE(cs.diem_mon_2,0) + COALESCE(cs.diem_mon_3,0)), nv.diem_uu_tien_goc, nv.diem_uu_tien_qd,
                        cs.diem_xet_tuyen, nv.ma_nganh, nv.ten_nganh, cs.phuong_thuc_toi_uu, ts.email, ts.dien_thoai, COALESCE(nv.ghi_chu, ts.ghi_chu)
                    FROM nguyen_vong nv
                    JOIN thi_sinh ts ON nv.so_cccd = ts.so_cccd
                    JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                    WHERE nv.dot_tuyen_sinh_id = ? AND cs.trang_thai_trung_tuyen = TRUE";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$sessionId, $sessionId]);
            $inserted = $stmt->rowCount();

            $db->commit();
            
            $this->redirect(url('/admin/admission/results?session_id=' . $sessionId . '&success=' . urlencode("Đồng bộ thành công $inserted thí sinh trúng tuyển từ dữ liệu lọc ảo.")));
        } catch (\Exception $e) {
            $db->rollBack();
            $this->redirect(url('/admin/admission/results?session_id=' . $sessionId . '&error=' . urlencode('Lỗi đồng bộ: ' . $e->getMessage())));
        }
    }

    public function getTemplate() {
        $sessionId = $_GET['session_id'] ?? 0;
        $db = \App\Core\Database::getInstance()->getConnection();
        
        $tplIdStmt = $db->prepare("SELECT template_id FROM session_templates WHERE session_id = ?");
        $tplIdStmt->execute([$sessionId]);
        $templateId = $tplIdStmt->fetchColumn();
        
        $template = null;
        if ($templateId) {
            $tplStmt = $db->prepare("SELECT * FROM email_templates WHERE id = ?");
            $tplStmt->execute([$templateId]);
            $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);
        }
        
        if (!$template) {
            $tplStmt = $db->prepare("SELECT * FROM email_templates WHERE code = 'ADMISSION_LETTER' LIMIT 1");
            $tplStmt->execute();
            $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'template' => $template]);
        exit;
    }

    public function saveTemplate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $sessionId = $_POST['session_id'] ?? 0;
        $body = $_POST['body'] ?? '';
        $subject = $_POST['subject'] ?? 'Thông báo trúng tuyển';
        
        if (!$sessionId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Chưa chọn đợt tuyển sinh']);
            exit;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Tìm template đang dùng cho session, nếu không có thì tạo mới
            $tplIdStmt = $db->prepare("SELECT template_id FROM session_templates WHERE session_id = ?");
            $tplIdStmt->execute([$sessionId]);
            $templateId = $tplIdStmt->fetchColumn();
            
            if ($templateId) {
                // Check nếu là template gốc ADMISSION_LETTER, không nên ghi đè gốc mà nên clone
                $checkStmt = $db->prepare("SELECT code FROM email_templates WHERE id = ?");
                $checkStmt->execute([$templateId]);
                $code = $checkStmt->fetchColumn();
                
                if ($code === 'ADMISSION_LETTER') {
                    // Clone
                    $insStmt = $db->prepare("INSERT INTO email_templates (subject, body, code, category, created_at) VALUES (?, ?, ?, 'admission_letter', NOW()) RETURNING id");
                    $insStmt->execute([$subject, $body, 'ADMISSION_SESSION_' . $sessionId]);
                    $templateId = $insStmt->fetchColumn();
                    
                    // Update session
                    $updStmt = $db->prepare("UPDATE session_templates SET template_id = ? WHERE session_id = ?");
                    $updStmt->execute([$templateId, $sessionId]);
                } else {
                    // Update existing specific template
                    $upd = $db->prepare("UPDATE email_templates SET subject = ?, body = ?, updated_at = NOW() WHERE id = ?");
                    $upd->execute([$subject, $body, $templateId]);
                }
            } else {
                // Tạo mới
                $insStmt = $db->prepare("INSERT INTO email_templates (subject, body, code, category, created_at) VALUES (?, ?, ?, 'admission_letter', NOW()) RETURNING id");
                $insStmt->execute([$subject, $body, 'ADMISSION_SESSION_' . $sessionId]);
                $templateId = $insStmt->fetchColumn();
                
                $sessStmt = $db->prepare("INSERT INTO session_templates (session_id, template_id) VALUES (?, ?)");
                $sessStmt->execute([$sessionId, $templateId]);
            }
            
            $db->commit();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Đã lưu mẫu Giấy báo trúng tuyển thành công']);
            exit;
        } catch (\Exception $e) {
            $db->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Tải xuống file Excel mẫu để nhập kết quả trúng tuyển
     * Bao gồm đầy đủ các cột cần thiết để in Giấy báo nhập học
     */
    public function downloadSampleExcel() {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DS_Trung_Tuyen_Mau');

        // Định nghĩa các cột tiêu đề (đúng với fieldMap trong AdmissionResultService)
        $headers = [
            'A' => ['header' => 'CCCD',         'note' => 'Bắt buộc. Số CCCD/CMND của thí sinh'],
            'B' => ['header' => 'HoTen',         'note' => 'Họ và tên đầy đủ'],
            'C' => ['header' => 'NgaySinh',      'note' => 'Ngày sinh, ví dụ: 01/01/2006'],
            'D' => ['header' => 'SBD',           'note' => 'Số báo danh thi THPT'],
            'E' => ['header' => 'KV',            'note' => 'Khu vực ưu tiên: KV1, KV2, KV2-NT, KV3'],
            'F' => ['header' => 'DoiTuong',      'note' => 'Đối tượng ưu tiên: 01..08 hoặc để trống'],
            'G' => ['header' => 'ToHop',         'note' => 'Tổ hợp môn: A00, A01, D01...'],
            'H' => ['header' => 'DM1',           'note' => 'Điểm môn 1'],
            'I' => ['header' => 'DM2',           'note' => 'Điểm môn 2'],
            'J' => ['header' => 'DM3',           'note' => 'Điểm môn 3'],
            'K' => ['header' => 'DiemToHop',     'note' => 'Tổng điểm 3 môn'],
            'L' => ['header' => 'DiemUT',        'note' => 'Điểm ưu tiên gốc'],
            'M' => ['header' => 'UTQ',           'note' => 'Điểm ưu tiên quy đổi'],
            'N' => ['header' => 'DiemXT',        'note' => 'Điểm xét tuyển cuối cùng'],
            'O' => ['header' => 'MaNganh',       'note' => 'Mã ngành theo danh mục Bộ GD&ĐT, VD: 7480201'],
            'P' => ['header' => 'Nganh',         'note' => 'Tên ngành đào tạo'],
            'Q' => ['header' => 'PhuongThuc',    'note' => 'Phương thức xét tuyển: TS01, TS02...'],
            'R' => ['header' => 'SOTK',          'note' => 'Số tài khoản ngân hàng nhận học phí'],
            'S' => ['header' => 'NGANHANG',      'note' => 'Tên ngân hàng, ví dụ: Vietcombank'],
            'T' => ['header' => 'SOTIEN',        'note' => 'Số tiền học phí (đồng), ví dụ: 5000000'],
            'U' => ['header' => 'NOIDUNG',       'note' => 'Nội dung chuyển khoản học phí'],
            'V' => ['header' => 'SOGIAYBAO',     'note' => 'Số giấy báo nhập học (để in giấy báo)'],
            'W' => ['header' => 'THOIGIANNHAP',  'note' => 'Thời gian nhập học, ví dụ: 05/09/2026'],
            'X' => ['header' => 'KINHPHI',       'note' => 'Thông tin kinh phí bổ sung (để in giấy báo)'],
            'Y' => ['header' => 'KHOAHOC',       'note' => 'Khóa học, ví dụ: Khóa 2026-2030'],
            'Z' => ['header' => 'LINKANH',       'note' => 'Link ảnh chân dung thí sinh (URL)'],
            'AA'=> ['header' => 'Email',         'note' => 'Bắt buộc. Email gửi Giấy báo trúng tuyển'],
            'AB'=> ['header' => 'SDT',           'note' => 'Số điện thoại liên lạc'],
            'AC'=> ['header' => 'GhiChu',        'note' => 'Ghi chú thêm (xếp hạng, ghi chú đặc biệt...)'],
        ];

        // Dữ liệu mẫu 1 dòng
        $sampleRow = [
            'A' => '012345678912',
            'B' => 'Nguyễn Văn An',
            'C' => '01/01/2006',
            'D' => '24B123456',
            'E' => 'KV1',
            'F' => '01',
            'G' => 'A00',
            'H' => 8.5,
            'I' => 9.0,
            'J' => 8.75,
            'K' => 26.25,
            'L' => 2.0,
            'M' => 1.5,
            'N' => 27.75,
            'O' => '7480201',
            'P' => 'Công nghệ thông tin',
            'Q' => 'TS01',
            'R' => '1903123456789',
            'S' => 'Vietcombank',
            'T' => 5000000,
            'U' => 'Nguyen Van An nop hoc phi K2026',
            'V' => 'GB-2026-001',
            'W' => '05/09/2026',
            'X' => '5.000.000đ/năm',
            'Y' => 'Khóa 2026-2030',
            'Z' => 'https://example.com/anh/012345678912.jpg',
            'AA'=> 'nguyenvanan@email.com',
            'AB'=> '0987654321',
            'AC'=> 'Thứ hạng 5/120 toàn ngành',
        ];

        // Style cho header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']]],
        ];
        $noteStyle = [
            'font' => ['italic' => true, 'color' => ['rgb' => '555555'], 'size' => 9],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4F8']],
            'alignment' => ['wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ];
        $dataStyle = [
            'font' => ['size' => 10],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];

        // Hàng 1: tiêu đề cột
        foreach ($headers as $col => $info) {
            $cell = $col . '1';
            $sheet->setCellValue($cell, $info['header']);
            $sheet->getStyle($cell)->applyFromArray($headerStyle);
            $sheet->getColumnDimension($col)->setWidth(18);
        }
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Hàng 2: ghi chú mô tả từng cột
        foreach ($headers as $col => $info) {
            $cell = $col . '2';
            $sheet->setCellValue($cell, $info['note']);
            $sheet->getStyle($cell)->applyFromArray($noteStyle);
        }
        $sheet->getRowDimension(2)->setRowHeight(32);

        // Hàng 3: dữ liệu mẫu
        foreach ($sampleRow as $col => $val) {
            $cell = $col . '3';
            $sheet->setCellValue($cell, $val);
            $sheet->getStyle($cell)->applyFromArray($dataStyle);
        }
        $sheet->getRowDimension(3)->setRowHeight(18);

        // Freeze pane tại hàng 3 (cố định 2 hàng tiêu đề)
        $sheet->freezePane('A3');

        // Đánh dấu 2 cột bắt buộc bằng màu nền vàng trên hàng 1
        foreach (['A', 'AA'] as $reqCol) {
            $sheet->getStyle($reqCol . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('c0392b');
        }

        // Output
        $filename = 'File_Mau_Nhap_KQ_Trung_Tuyen_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
