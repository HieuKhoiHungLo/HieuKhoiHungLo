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

    public function overviewResults() {
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
            $activeSession = $sessionModel->getActiveSession() ?: $sessionModel->getLatestSession();
        }
        $sessionId = $activeSession['id'] ?? 0;

        // Filters
        $filterMajor = $_GET['major'] ?? '';
        $filterStatus = $_GET['status'] ?? '';
        $showAll = isset($_GET['show_all']) && $_GET['show_all'] == '1';

        // 1. Global Stats
        $statsSql = "SELECT 
                        COUNT(DISTINCT k.so_cccd) as total_candidates,
                        COUNT(k.id) as total_wishes,
                        COUNT(k.id) as total_admitted,
                        SUM(CASE WHEN nv.thu_tu_nguyen_vong = 1 THEN 1 ELSE 0 END) as nv1_admit,
                        SUM(CASE WHEN nv.thu_tu_nguyen_vong = 2 THEN 1 ELSE 0 END) as nv2_admit,
                        SUM(CASE WHEN nv.thu_tu_nguyen_vong = 3 THEN 1 ELSE 0 END) as nv3_admit
                     FROM ket_qua_trung_tuyen k
                     LEFT JOIN nguyen_vong nv ON k.so_cccd = nv.so_cccd AND k.ma_nganh = nv.ma_nganh AND nv.dot_tuyen_sinh_id = k.session_id
                     WHERE k.session_id = ?";
        $statsStmt = $db->prepare($statsSql);
        $statsStmt->execute([$sessionId]);
        $stats = $statsStmt->fetch(\PDO::FETCH_ASSOC);

        // 2. Per-major stats
        $majorStatsSql = "SELECT n.ma_nganh, n.ten_nganh, n.chi_tieu, n.nhom_nganh,
                            COUNT(k.id) as so_trung_tuyen,
                            COUNT(k.id) as tong_nguyen_vong,
                            SUM(CASE WHEN nv.thu_tu_nguyen_vong = 1 THEN 1 ELSE 0 END) as nv1_admit,
                            MAX(k.diem_xt) as diem_cao_nhat,
                            MIN(k.diem_xt) as diem_thap_nhat
                          FROM dm_nganh n
                          LEFT JOIN ket_qua_trung_tuyen k ON n.ma_nganh = k.ma_nganh AND k.session_id = ?
                          LEFT JOIN nguyen_vong nv ON k.so_cccd = nv.so_cccd AND k.ma_nganh = nv.ma_nganh AND nv.dot_tuyen_sinh_id = k.session_id
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
            'isReadOnly' => true,
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
            'allTemplates' => [],
            'emailTemplates' => [],
            'currentTemplateId' => null
        ]);
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
                        COUNT(DISTINCT k.so_cccd) as total_candidates,
                        COUNT(k.id) as total_wishes,
                        COUNT(k.id) as total_admitted,
                        SUM(CASE WHEN nv.thu_tu_nguyen_vong = 1 THEN 1 ELSE 0 END) as nv1_admit,
                        SUM(CASE WHEN nv.thu_tu_nguyen_vong = 2 THEN 1 ELSE 0 END) as nv2_admit,
                        SUM(CASE WHEN nv.thu_tu_nguyen_vong = 3 THEN 1 ELSE 0 END) as nv3_admit
                     FROM ket_qua_trung_tuyen k
                     LEFT JOIN nguyen_vong nv ON k.so_cccd = nv.so_cccd AND k.ma_nganh = nv.ma_nganh AND nv.dot_tuyen_sinh_id = k.session_id
                     WHERE k.session_id = ?";
        $statsStmt = $db->prepare($statsSql);
        $statsStmt->execute([$sessionId]);
        $stats = $statsStmt->fetch(\PDO::FETCH_ASSOC);

        // 2. Per-major stats (admitted vs chi_tieu)  
        $majorStatsSql = "SELECT n.ma_nganh, n.ten_nganh, n.chi_tieu, n.nhom_nganh,
                            COUNT(k.id) as so_trung_tuyen,
                            COUNT(k.id) as tong_nguyen_vong,
                            SUM(CASE WHEN nv.thu_tu_nguyen_vong = 1 THEN 1 ELSE 0 END) as nv1_admit,
                            MAX(k.diem_xt) as diem_cao_nhat,
                            MIN(k.diem_xt) as diem_thap_nhat
                          FROM dm_nganh n
                          LEFT JOIN ket_qua_trung_tuyen k ON n.ma_nganh = k.ma_nganh AND k.session_id = ?
                          LEFT JOIN nguyen_vong nv ON k.so_cccd = nv.so_cccd AND k.ma_nganh = nv.ma_nganh AND nv.dot_tuyen_sinh_id = k.session_id
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

        // Column-level quick search filters
        $colCccd    = trim($_GET['col_cccd'] ?? '');
        $colName    = trim($_GET['col_name'] ?? '');
        $colMaNganh = trim($_GET['col_ma_nganh'] ?? '');
        $colTenNganh= trim($_GET['col_ten_nganh'] ?? '');
        $colDiem    = trim($_GET['col_diem'] ?? '');
        $colGb      = trim($_GET['col_gb'] ?? '');
        $colNote    = trim($_GET['col_note'] ?? '');
        $colXnBo    = trim($_GET['col_xn_bo'] ?? '');
        $colXnTruong= trim($_GET['col_xn_truong'] ?? '');

        if ($length < 1) $length = 50;
        if ($length > 200) $length = 200;

        // Base query
        $baseFrom = "FROM ket_qua_trung_tuyen k
                     LEFT JOIN thi_sinh ts ON ts.so_cccd = k.so_cccd
                     LEFT JOIN dm_tinh dt ON (COALESCE(ts.ma_tinh_ho_khau, ts.ma_tinh_thuong_tru, ts.ma_tinh_lop_12) = dt.ma_tinh)
                     LEFT JOIN dm_truong_thpt dthpt ON (ts.ma_truong_lop_12 = dthpt.ma_truong AND ts.ma_tinh_lop_12 = dthpt.ma_tinh AND dthpt.is_active = TRUE)
                     LEFT JOIN ket_qua_hoc_tap kqht ON (ts.so_cccd = kqht.so_cccd AND kqht.lop = 12)
                     LEFT JOIN nhap_hoc nh ON (nh.session_id = k.session_id AND nh.so_cccd = k.so_cccd)
                     WHERE k.session_id = ?";
        $params = [$sessionId];

        // Major filter  
        if ($filterMajor) {
            $baseFrom .= " AND k.ma_nganh = ?";
            $params[] = $filterMajor;
        }

        // Global search filter
        if (!empty($search)) {
            $baseFrom .= " AND (k.ho_ten ILIKE ? OR k.so_cccd ILIKE ? OR k.ma_nganh ILIKE ? OR k.ten_nganh ILIKE ? OR dthpt.ten_truong ILIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        // Column-specific filters
        if (!empty($colCccd)) {
            $baseFrom .= " AND k.so_cccd ILIKE ?";
            $params[] = "%{$colCccd}%";
        }
        if (!empty($colName)) {
            $baseFrom .= " AND k.ho_ten ILIKE ?";
            $params[] = "%{$colName}%";
        }
        if (!empty($colMaNganh)) {
            $baseFrom .= " AND k.ma_nganh ILIKE ?";
            $params[] = "%{$colMaNganh}%";
        }
        if (!empty($colTenNganh)) {
            $baseFrom .= " AND k.ten_nganh ILIKE ?";
            $params[] = "%{$colTenNganh}%";
        }
        if (!empty($colDiem)) {
            $baseFrom .= " AND CAST(k.diem_xt AS TEXT) ILIKE ?";
            $params[] = "%{$colDiem}%";
        }
        if (!empty($colGb)) {
            $baseFrom .= " AND k.so_giay_bao ILIKE ?";
            $params[] = "%{$colGb}%";
        }
        if (!empty($colNote)) {
            $baseFrom .= " AND k.ghi_chu ILIKE ?";
            $params[] = "%{$colNote}%";
        }
        if ($colXnBo !== '') {
            if ($colXnBo === '1') {
                $baseFrom .= " AND (k.xac_nhan_bo = 1 OR k.xac_nhan_nhap_hoc = 1 OR k.is_confirm = true)";
            } else {
                $baseFrom .= " AND (COALESCE(k.xac_nhan_bo, 0) = 0 AND COALESCE(k.xac_nhan_nhap_hoc, 0) = 0 AND COALESCE(k.is_confirm, false) = false)";
            }
        }
        if ($colXnTruong !== '') {
            if ($colXnTruong === '1') {
                $baseFrom .= " AND k.xac_nhan_truong = 1";
            } else {
                $baseFrom .= " AND COALESCE(k.xac_nhan_truong, 0) = 0";
            }
        }

        // Count total (without extra filters)
        $stmtTotal = $db->prepare("SELECT COUNT(*) FROM ket_qua_trung_tuyen WHERE session_id = ?");
        $stmtTotal->execute([$sessionId]);
        $recordsTotal = $stmtTotal->fetchColumn() ?: 0;

        // Count filtered
        $stmtFiltered = $db->prepare("SELECT COUNT(*) $baseFrom");
        $stmtFiltered->execute($params);
        $recordsFiltered = $stmtFiltered->fetchColumn() ?: 0;

        // Data query
        $dataSql = "SELECT k.*, 
                           ts.gioi_tinh, ts.dan_toc, ts.nam_tot_nghiep, ts.dia_chi_chi_tiet, ts.ma_tinh_lop_12, ts.ma_truong_lop_12,
                           dt.ten_tinh, dthpt.ten_truong as ten_truong_thpt,
                           kqht.hoc_luc_ca_nam as hoc_luc_12, kqht.hanh_kiem_ca_nam as hanh_kiem_12, kqht.diem_tb_ca_nam as diem_tb_12,
                           nh.trang_thai as nh_trang_thai, nh.ngay_nhap_hoc as nh_ngay_nhap_hoc,
                           nh.da_nop_tien as nh_da_nop_tien, nh.so_tien_da_nop as nh_so_tien_da_nop
                    $baseFrom
                    ORDER BY k.ma_nganh, k.diem_xt DESC NULLS LAST
                    LIMIT $length OFFSET $start";
        
        $stmt = $db->prepare($dataSql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Process rows
        foreach ($rows as &$row) {
            $row['is_pass'] = true;
            $row['chi_tiet_diem'] = null;
            if (!empty($row['ho_ten'])) {
                $row['ho_ten'] = mb_convert_case($row['ho_ten'], MB_CASE_TITLE, 'UTF-8');
            }
            if ($row['diem_ut'] === null || $row['ut_quy_doi'] === null) {
                $prio = self::calcPriorityPoints(
                    $row['khu_vuc'] ?? $row['khu_vuc_uu_tien'] ?? '',
                    $row['doi_tuong'] ?? $row['doi_tuong_uu_tien'] ?? '',
                    $row['diem_mon_1'] ?? null,
                    $row['diem_mon_2'] ?? null,
                    $row['diem_mon_3'] ?? null,
                    $row['diem_to_hop'] ?? null
                );
                if ($row['diem_ut'] === null) $row['diem_ut'] = $prio['diem_ut'];
                if ($row['ut_quy_doi'] === null) $row['ut_quy_doi'] = $prio['ut_quy_doi'];
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

    public static function calcPriorityPoints($khuVuc, $doiTuong, $m1, $m2, $m3, $diemToHop = null) {
        $kv = strtoupper(trim((string)$khuVuc));
        $diemKv = 0.0;
        if (in_array($kv, ['1', 'KV1', '01'])) {
            $diemKv = 0.75;
        } elseif (in_array($kv, ['2-NT', 'KV2-NT', '2NT', '02-NT', '02NT'])) {
            $diemKv = 0.5;
        } elseif (in_array($kv, ['2', 'KV2', '02'])) {
            $diemKv = 0.25;
        }

        $dt = strtoupper(trim((string)$doiTuong));
        $diemDt = 0.0;
        if (in_array($dt, ['01', '1', '02', '2', '03', '3', '04', '4', '04B', '4B'])) {
            $diemDt = 2.0;
        } elseif (in_array($dt, ['05', '5', '06', '6', '07', '7'])) {
            $diemDt = 1.0;
        }

        $diemUtRaw = $diemKv + $diemDt;

        $sum3M = 0.0;
        if ($m1 !== null && $m2 !== null && $m3 !== null && ($m1 > 0 || $m2 > 0 || $m3 > 0)) {
            $sum3M = (float)$m1 + (float)$m2 + (float)$m3;
        } elseif ($diemToHop !== null) {
            $sum3M = (float)$diemToHop;
        }

        if ($diemUtRaw <= 0) {
            return ['diem_ut' => 0.0, 'ut_quy_doi' => 0.0];
        }

        if ($sum3M >= 22.5) {
            $utQuyDoi = round(((30.0 - $sum3M) / 7.5) * $diemUtRaw, 3);
            if ($utQuyDoi < 0) $utQuyDoi = 0.0;
        } else {
            $utQuyDoi = $diemUtRaw;
        }

        return [
            'diem_ut' => round($diemUtRaw, 3),
            'ut_quy_doi' => round($utQuyDoi, 3)
        ];
    }

    public function exportResults() {
        $db = \App\Core\Database::getInstance()->getConnection();
        
        $sessionId = $_GET['session_id'] ?? 0;
        $filterMajor = $_GET['major'] ?? '';
        $search = trim($_GET['search'] ?? '');

        // Column-level quick search filters
        $colCccd    = trim($_GET['col_cccd'] ?? '');
        $colName    = trim($_GET['col_name'] ?? '');
        $colMaNganh = trim($_GET['col_ma_nganh'] ?? '');
        $colTenNganh= trim($_GET['col_ten_nganh'] ?? '');
        $colDiem    = trim($_GET['col_diem'] ?? '');
        $colGb      = trim($_GET['col_gb'] ?? '');
        $colNote    = trim($_GET['col_note'] ?? '');
        $colXnBo    = trim($_GET['col_xn_bo'] ?? '');
        $colXnTruong= trim($_GET['col_xn_truong'] ?? '');

        // Fetch session info
        $sessionStmt = $db->prepare("SELECT ten_dot, nam_tuyen_sinh FROM dot_tuyen_sinh WHERE id = ?");
        $sessionStmt->execute([$sessionId]);
        $sessionInfo = $sessionStmt->fetch(\PDO::FETCH_ASSOC);
        $sessionName = $sessionInfo['ten_dot'] ?? "Dot_$sessionId";

        $exportType = $_GET['export_type'] ?? 'full';

        // Lấy danh sách Tổ hợp để định dạng
        $toHopMap = [];
        try {
            $thStmt = $db->query("
                SELECT th.ma_to_hop, m1.ma_mon as m1, m2.ma_mon as m2, m3.ma_mon as m3 
                FROM dm_to_hop th
                LEFT JOIN dm_mon m1 ON th.mon_1_id = m1.id
                LEFT JOIN dm_mon m2 ON th.mon_2_id = m2.id
                LEFT JOIN dm_mon m3 ON th.mon_3_id = m3.id
            ");
            while ($row = $thStmt->fetch(\PDO::FETCH_ASSOC)) {
                $mons = array_filter([$row['m1'], $row['m2'], $row['m3']]);
                if (!empty($mons)) {
                    $toHopMap[$row['ma_to_hop']] = $row['ma_to_hop'] . ' (' . strtoupper(implode('-', $mons)) . ')';
                }
            }
        } catch (\Exception $e) {}

        $sql = "SELECT k.*, 
                       ts.gioi_tinh, ts.dan_toc, ts.nam_tot_nghiep, ts.dia_chi_chi_tiet, ts.ma_tinh_lop_12, ts.ma_truong_lop_12,
                       ts.ngay_sinh, ts.dien_thoai, ts.email as thi_sinh_email, ts.anh_dai_dien,
                       dt.ten_tinh, dthpt.ten_truong as ten_truong_thpt,
                       kqht.hoc_luc_ca_nam as hoc_luc_12, kqht.hanh_kiem_ca_nam as hanh_kiem_12, kqht.diem_tb_ca_nam as diem_tb_12,
                       nh.trang_thai as nh_trang_thai, nh.ngay_nhap_hoc as nh_ngay_nhap_hoc, 
                       nh.da_nop_tien as nh_da_nop_tien, nh.so_tien_da_nop as nh_so_tien_da_nop,
                       nv.thu_tu_nguyen_vong as thu_tu_nv
                FROM ket_qua_trung_tuyen k
                LEFT JOIN thi_sinh ts ON ts.so_cccd = k.so_cccd
                LEFT JOIN dm_tinh dt ON (COALESCE(ts.ma_tinh_ho_khau, ts.ma_tinh_thuong_tru, ts.ma_tinh_lop_12) = dt.ma_tinh)
                LEFT JOIN dm_truong_thpt dthpt ON (ts.ma_truong_lop_12 = dthpt.ma_truong AND ts.ma_tinh_lop_12 = dthpt.ma_tinh AND dthpt.is_active = TRUE)
                LEFT JOIN ket_qua_hoc_tap kqht ON (ts.so_cccd = kqht.so_cccd AND kqht.lop = 12)
                LEFT JOIN nhap_hoc nh ON (nh.session_id = k.session_id AND nh.so_cccd = k.so_cccd)
                LEFT JOIN nguyen_vong nv ON (nv.so_cccd = k.so_cccd AND nv.ma_nganh = k.ma_nganh AND nv.dot_tuyen_sinh_id = k.session_id)
                WHERE k.session_id = ?";
        $params = [$sessionId];

        if ($filterMajor) {
            $sql .= " AND k.ma_nganh = ?";
            $params[] = $filterMajor;
        }

        if (!empty($search)) {
            $sql .= " AND (k.ho_ten ILIKE ? OR k.so_cccd ILIKE ? OR k.ma_nganh ILIKE ? OR k.ten_nganh ILIKE ? OR dthpt.ten_truong ILIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        if (!empty($colCccd)) {
            $sql .= " AND k.so_cccd ILIKE ?";
            $params[] = "%{$colCccd}%";
        }
        if (!empty($colName)) {
            $sql .= " AND k.ho_ten ILIKE ?";
            $params[] = "%{$colName}%";
        }
        if (!empty($colMaNganh)) {
            $sql .= " AND k.ma_nganh ILIKE ?";
            $params[] = "%{$colMaNganh}%";
        }
        if (!empty($colTenNganh)) {
            $sql .= " AND k.ten_nganh ILIKE ?";
            $params[] = "%{$colTenNganh}%";
        }
        if (!empty($colDiem)) {
            $sql .= " AND CAST(k.diem_xt AS TEXT) ILIKE ?";
            $params[] = "%{$colDiem}%";
        }
        if (!empty($colGb)) {
            $sql .= " AND k.so_giay_bao ILIKE ?";
            $params[] = "%{$colGb}%";
        }
        if (!empty($colNote)) {
            $sql .= " AND k.ghi_chu ILIKE ?";
            $params[] = "%{$colNote}%";
        }
        if ($colXnBo !== '') {
            if ($colXnBo === '1') {
                $sql .= " AND (k.xac_nhan_bo = 1 OR k.xac_nhan_nhap_hoc = 1 OR k.is_confirm = true)";
            } else {
                $sql .= " AND (COALESCE(k.xac_nhan_bo, 0) = 0 AND COALESCE(k.xac_nhan_nhap_hoc, 0) = 0 AND COALESCE(k.is_confirm, false) = false)";
            }
        }
        if ($colXnTruong !== '') {
            if ($colXnTruong === '1') {
                $sql .= " AND k.xac_nhan_truong = 1";
            } else {
                $sql .= " AND COALESCE(k.xac_nhan_truong, 0) = 0";
            }
        }

        $sql .= " ORDER BY k.ma_nganh ASC, k.diem_xt DESC NULLS LAST";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $exportData = [];
        $maxScores = [];
        foreach ($rows as $i => $r) {
            if ($r['diem_ut'] === null || $r['ut_quy_doi'] === null) {
                $prio = self::calcPriorityPoints(
                    $r['khu_vuc'] ?? $r['khu_vuc_uu_tien'] ?? '',
                    $r['doi_tuong'] ?? $r['doi_tuong_uu_tien'] ?? '',
                    $r['diem_mon_1'] ?? null,
                    $r['diem_mon_2'] ?? null,
                    $r['diem_mon_3'] ?? null,
                    $r['diem_to_hop'] ?? null
                );
                if ($r['diem_ut'] === null) $r['diem_ut'] = $prio['diem_ut'];
                if ($r['ut_quy_doi'] === null) $r['ut_quy_doi'] = $prio['ut_quy_doi'];
            }

            if ($exportType === 'full' || $exportType === 'default') {
                $toHopCode = $r['to_hop'] ?? '';
                $toHopFormatted = $toHopMap[$toHopCode] ?? $toHopCode;

                $ngaySinh = $r['ngay_sinh'] ?? '';
                if (!empty($ngaySinh) && strtotime($ngaySinh)) {
                    $ngaySinh = date('d/m/Y', strtotime($ngaySinh));
                }

                $orderedRow = [
                    'STT' => $i + 1,
                    'SBD' => $r['sbd'] ?? '',
                    'HOTEN' => $r['ho_ten'] ?? '',
                    'NGAYSINH' => $ngaySinh,
                    'GT' => $r['gioi_tinh'] ?? '',
                    'CCCD' => $r['so_cccd'] ?? '',
                    'KV' => $r['khu_vuc'] ?? ($r['khu_vuc_uu_tien'] ?? ''),
                    'DOITUONG' => $r['doi_tuong'] ?? ($r['doi_tuong_uu_tien'] ?? ''),
                    'TH' => $toHopFormatted,
                    'TOHOP' => $toHopFormatted,
                    'DM1' => $r['diem_mon_1'] ?? '',
                    'DM2' => $r['diem_mon_2'] ?? '',
                    'DM3' => $r['diem_mon_3'] ?? '',
                    'DIEMTOHOP' => $r['diem_to_hop'] ?? '',
                    'DIEMUT' => $r['diem_ut'] ?? '',
                    'UTQ' => $r['ut_quy_doi'] ?? '',
                    'DIEMXT' => $r['diem_xt'] ?? '',
                    'SDT' => $r['sdt'] ?? ($r['dien_thoai'] ?? ''),
                    'MANGANH' => $r['ma_nganh'] ?? '',
                    'NGANH' => $r['ten_nganh'] ?? '',
                    'TTNV' => $r['thu_tu_nv'] ?? '',
                    'PHUONGTHUC' => $r['phuong_thuc'] ?? '',
                    'TINH' => $r['ten_tinh'] ?? ($r['ma_tinh_lop_12'] ?? ''),
                    'XA' => $r['phuong_xa'] ?? '',
                    'DIACHI' => $r['dia_chi_chi_tiet'] ?? ''
                ];

                $linkAnh = $r['anh_dai_dien'] ?? '';
                if ($linkAnh && !preg_match('/^https?:\/\//', $linkAnh)) {
                    $linkAnh = url('/' . ltrim($linkAnh, '/'));
                }

                $originalData = [
                    'Trường THPT' => $r['ten_truong_thpt'] ?? ($r['ma_truong_lop_12'] ?? ''),
                    'Dân tộc' => $r['dan_toc'] ?? '',
                    'Email' => $r['email'] ?? ($r['thi_sinh_email'] ?? ''),
                    'Năm tốt nghiệp' => $r['nam_tot_nghiep'] ?? '',
                    'Học lực Lớp 12' => $r['hoc_luc_12'] ?? '',
                    'Hạnh kiểm Lớp 12' => $r['hanh_kiem_12'] ?? '',
                    'ĐTB Lớp 12' => $r['diem_tb_12'] ?? '',
                    'XACNHANBO' => (!empty($r['xac_nhan_bo']) || !empty($r['xac_nhan_nhap_hoc']) || !empty($r['is_confirm'])) ? 'Đã XN' : 'Chưa XN',
                    'XACNHANTRUONG' => !empty($r['xac_nhan_truong']) ? 'Đã XN' : 'Chưa XN',
                    'NHAPHOC' => (!empty($r['nh_trang_thai']) || !empty($r['is_nhap_hoc']) || !empty($r['nh_ngay_nhap_hoc'])) ? 'Đã nhập học' : 'Chưa nhập học',
                    'NOPKINHPHI' => (!empty($r['nh_da_nop_tien']) || !empty($r['da_nop_tien'])) ? 'Đã nộp' : 'Chưa nộp',
                    'Ghi Chú' => $r['ghi_chu'] ?? '',
                    // Yêu cầu chuyển về cuối danh sách:
                    'SOTK' => $r['so_tai_khoan'] ?? '',
                    'NGANHANG' => $r['ngan_hang'] ?? '',
                    'SOTIEN' => $r['so_tien'] ?? '',
                    'NOIDUNG' => $r['noi_dung_ck'] ?? '',
                    'SOTIENNOP' => $r['nh_so_tien_da_nop'] ?? ($r['so_tien_da_nop'] ?? ''),
                    'THOIGIANNHAP' => $r['thoi_gian_nhap_hoc'] ?? ($r['nh_ngay_nhap_hoc'] ?? ''),
                    'KINHPHI' => $r['kinh_phi'] ?? ($r['noi_dung_thu'] ?? ''),
                    'KHOA' => $r['ten_khoa'] ?? '',
                    'Số Giấy Báo' => $r['so_giay_bao'] ?? '',
                    'Ngành in Giấy Báo' => $r['nganh_in_giay_bao'] ?? '',
                    'Link Ảnh' => $linkAnh,
                    'FILE_GIAY_BAO' => $r['file_giay_bao'] ?? ''
                ];

                $exportData[] = array_merge($orderedRow, $originalData);
            } elseif ($exportType === 'print_letter') {
                $exportData[] = [
                    'Check_CCCD' => '',
                    'STT' => $i + 1,
                    'SBD' => $r['sbd'] ?? '',
                    'HOTEN' => $r['ho_ten'] ?? '',
                    'NGAYSINH' => $r['ngay_sinh'] ?? '',
                    'GT' => $r['gioi_tinh'] ?? '',
                    'CCCD' => $r['so_cccd'] ?? '',
                    'KV' => $r['khu_vuc'] ?? ($r['khu_vuc_uu_tien'] ?? ''),
                    'DOITUONG' => $r['doi_tuong'] ?? ($r['doi_tuong_uu_tien'] ?? ''),
                    'TH' => $r['to_hop'] ?? '',
                    'TOHOP' => $r['to_hop'] ?? '',
                    'DM1' => $r['diem_mon_1'] ?? '',
                    'DM2' => $r['diem_mon_2'] ?? '',
                    'DM3' => $r['diem_mon_3'] ?? '',
                    'DIEMTOHOP' => $r['diem_to_hop'] ?? '',
                    'DIEMUT' => $r['diem_ut'] ?? '',
                    'UTQ' => $r['ut_quy_doi'] ?? '',
                    'DIEMXT' => $r['diem_xt'] ?? '',
                    'SDT' => $r['sdt'] ?? ($r['dien_thoai'] ?? ''),
                    'MANGANH' => $r['ma_nganh'] ?? '',
                    'NGANH' => $r['ten_nganh'] ?? '',
                    'TTNV' => $r['thu_tu_nv'] ?? '',
                    'PT' => $r['phuong_thuc'] ?? '',
                    'TTT' => '',
                    'TINH' => $r['ten_tinh'] ?? ($r['ma_tinh_lop_12'] ?? ''),
                    'HUYEN' => $r['ten_huyen'] ?? ($r['ma_huyen_lop_12'] ?? ''),
                    'XA' => $r['phuong_xa'] ?? '',
                    'DIACHI' => $r['dia_chi_chi_tiet'] ?? '',
                    'DIENTHOAI' => $r['dien_thoai'] ?? ($r['sdt'] ?? ''),
                    'MATRUONG' => $r['ma_truong_lop_12'] ?? '',
                    'TENTRUONG' => $r['ten_truong_thpt'] ?? '',
                    'TT' => '',
                    'SOTK' => $r['so_tai_khoan'] ?? '',
                    'THOIGIANNHAP' => $r['thoi_gian_nhap_hoc'] ?? ($r['nh_ngay_nhap_hoc'] ?? ''),
                    'KINHPHI' => $r['kinh_phi'] ?? ($r['noi_dung_thu'] ?? ''),
                    'DT' => $r['dan_toc'] ?? '',
                    'TN' => $r['nam_tot_nghiep'] ?? '',
                    'NGANH_TT' => $r['nganh_in_giay_bao'] ?? ($r['ten_nganh'] ?? ''),
                    'KHOA' => $r['ten_khoa'] ?? '',
                    'Linkanh' => $r['link_anh'] ?? '',
                    'SOTIEN' => $r['so_tien'] ?? '',
                    'NOIDUNG' => $r['noi_dung_ck'] ?? '',
                    'NGANHANG' => $r['ngan_hang'] ?? '',
                    'PHUONGTHUC' => $r['phuong_thuc'] ?? '',
                    'Email' => $r['email'] ?? ($r['thi_sinh_email'] ?? '')
                ];
            } elseif ($exportType === 'top_students') {
                $maNganh = $r['ma_nganh'] ?? 'N/A';
                $diemXt = (float)($r['diem_xt'] ?? 0);
                
                if (!isset($maxScores[$maNganh])) {
                    $maxScores[$maNganh] = $diemXt;
                } else {
                    if (abs($diemXt - $maxScores[$maNganh]) > 0.0001 && $diemXt < $maxScores[$maNganh]) {
                        continue;
                    }
                }
                
                $exportData[] = [
                    'STT' => count($exportData) + 1,
                    'MANGANH' => $r['ma_nganh'] ?? '',
                    'NGANH' => $r['ten_nganh'] ?? '',
                    'SBD' => $r['sbd'] ?? '',
                    'HOTEN' => $r['ho_ten'] ?? '',
                    'NGAYSINH' => $r['ngay_sinh'] ?? '',
                    'GT' => $r['gioi_tinh'] ?? '',
                    'CCCD' => $r['so_cccd'] ?? '',
                    'KV' => $r['khu_vuc'] ?? ($r['khu_vuc_uu_tien'] ?? ''),
                    'DOITUONG' => $r['doi_tuong'] ?? ($r['doi_tuong_uu_tien'] ?? ''),
                    'TH' => $r['to_hop'] ?? '',
                    'TOHOP' => $r['to_hop'] ?? '',
                    'DM1' => $r['diem_mon_1'] ?? '',
                    'DM2' => $r['diem_mon_2'] ?? '',
                    'DM3' => $r['diem_mon_3'] ?? '',
                    'DIEMTOHOP' => $r['diem_to_hop'] ?? '',
                    'DIEMUT' => $r['diem_ut'] ?? '',
                    'UTQ' => $r['ut_quy_doi'] ?? '',
                    'DIEMXT' => $r['diem_xt'] ?? '',
                    'SDT' => $r['sdt'] ?? ($r['dien_thoai'] ?? '')
                ];
            }
        }

        $exportService = new \App\Services\ExportService();
        $safeFileName = 'ds_trung_tuyen_' . date('Y-m-d') . '.xls';
        $exportService->toExcel($exportData, $safeFileName, true);
        exit;
    }

    public function import() {
        // Allow unlimited time and enough memory for large Excel files
        set_time_limit(0);
        ini_set('memory_limit', '512M');

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
            $updateExisting = isset($_POST['update_existing']) && $_POST['update_existing'] == '1';
            $result = $service->importFromExcel($fileTmpPath, $sessionId, $updateExisting);

            if (!empty($result['result_file'])) {
                $_SESSION['last_import_result_file'] = $result['result_file'];
            }

            $msg = "Đã xử lý xong: Thêm mới {$result['imported']} thí sinh";
            if (!empty($result['updated']) && $result['updated'] > 0) {
                $msg .= ", Cập nhật/Bổ sung dữ liệu cho {$result['updated']} thí sinh";
            }
            if (!empty($result['ignored']) && $result['ignored'] > 0) {
                $msg .= " (Bỏ qua {$result['ignored']} dòng không hợp lệ hoặc trùng).";
            } else {
                $msg .= ".";
            }
            
            $redirectUrl = url('/admin/admission/results?session_id='.$sessionId.'&success=' . urlencode($msg));
            if (!empty($result['result_file'])) {
                $redirectUrl .= '&download_result=1';
            }
            $this->redirect($redirectUrl);
        } catch (\Exception $e) {
            $redirectUrl = url('/admin/admission/results?session_id='.$sessionId.'&error=' . urlencode('Đã xảy ra lỗi: ' . $e->getMessage()));
            if (!empty($_SESSION['last_import_result_file'])) {
                $redirectUrl .= '&download_result=1';
            }
            $this->redirect($redirectUrl);
        }
    }

    /**
     * Tải xuống file Excel kết quả import ghi nhận chi tiết lỗi/thành công của từng dòng
     */
    public function downloadResultFile() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $filePathRelative = $_SESSION['last_import_result_file'] ?? '';
        if (empty($filePathRelative)) {
            $this->redirect(url('/admin/admission/results?error=' . urlencode('Không tìm thấy file kết quả import.')));
        }

        $absolutePath = __DIR__ . '/../../public/' . $filePathRelative;
        if (!file_exists($absolutePath)) {
            $absolutePath = __DIR__ . '/../../' . $filePathRelative; // fallback
        }

        if (!file_exists($absolutePath)) {
            unset($_SESSION['last_import_result_file']);
            $this->redirect(url('/admin/admission/results?error=' . urlencode('File kết quả không tồn tại trên hệ thống.')));
        }

        // Send headers to force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="ket_qua_import_' . date('Ymd_His') . '.xlsx"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($absolutePath));
        
        // Clean output buffer to prevent corrupted file
        if (ob_get_level()) {
            ob_end_clean();
        }

        readfile($absolutePath);
        
        // Delete the temporary file and clear session
        @unlink($absolutePath);
        unset($_SESSION['last_import_result_file']);
        exit;
    }

    /**
     * Import ảnh thẻ từ file ZIP và đẩy lên Google Drive tương ứng theo từng thí sinh
     */
    public function importAvatarsZip() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $sessionId = $_POST['session_id'] ?? '';
        $overwrite = isset($_POST['overwrite']) && $_POST['overwrite'] == '1';

        if (empty($sessionId)) {
            $this->redirect(url('/admin/admission/results?error=' . urlencode('Vui lòng chọn đợt tuyển sinh.')));
        }

        if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&error=' . urlencode('Vui lòng chọn file ZIP ảnh thẻ hợp lệ.')));
        }

        $fileTmpPath = $_FILES['zip_file']['tmp_name'];
        $fileName = $_FILES['zip_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension !== 'zip') {
            $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&error=' . urlencode('Chỉ hỗ trợ file nén (.zip).')));
        }

        try {
            $service = new \App\Services\AvatarDriveImportService();
            $result = $service->importFromZip($fileTmpPath, $sessionId, $overwrite);

            if ($result['status']) {
                $unmatchedCount = count($result['unmatched']);
                $msg = "Đã xử lý {$result['total']} file ảnh. Đã cập nhật thành công ảnh thẻ cho {$result['inserted']} thí sinh trúng tuyển.";
                if (session_status() == PHP_SESSION_NONE) session_start();
                $_SESSION['avatar_import_result'] = $result;
                $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&success=' . urlencode($msg)));
            } else {
                if (session_status() == PHP_SESSION_NONE) session_start();
                $_SESSION['avatar_import_result'] = $result;
                $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&error=' . urlencode('Đã xảy ra lỗi khi import ảnh.')));
            }
        } catch (\Exception $e) {
            $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&error=' . urlencode('Đã xảy ra lỗi: ' . $e->getMessage())));
        }
    }

    /**
     * Quét tự động thư mục Google Drive của từng thí sinh để đồng bộ link ảnh thẻ
     */
    public function syncDriveAvatars() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $sessionId = $_POST['session_id'] ?? '';
        if (empty($sessionId)) {
            $this->redirect(url('/admin/admission/results?error=' . urlencode('Vui lòng chọn đợt tuyển sinh.')));
        }

        try {
            $service = new \App\Services\AvatarDriveImportService();
            $result = $service->scanAndSyncFromDrive($sessionId);

            if ($result['status']) {
                $msg = "Đã quét {$result['total']} thí sinh đợt này. Đã đồng bộ thành công ảnh thẻ Google Drive cho {$result['inserted']} thí sinh.";
                $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&success=' . urlencode($msg)));
            } else {
                $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&error=' . urlencode('Có lỗi xảy ra khi quét Google Drive.')));
            }
        } catch (\Exception $e) {
            $this->redirect(url('/admin/admission/results?session_id='.$sessionId.'&error=' . urlencode('Lỗi quét Google Drive: ' . $e->getMessage())));
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
                    WHERE nv.dot_tuyen_sinh_id = ? 
                      AND cs.trang_thai_trung_tuyen = TRUE
                      AND COALESCE(cs.ket_qua_bo_gd_du_kien, cs.ket_qua_bo_gd) = 'Đỗ'";
            
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
        
        // Lấy danh sách toàn bộ Mẫu trong Thư viện để lựa chọn
        $allTplsStmt = $db->query("SELECT id, code, subject, body FROM email_templates ORDER BY id ASC");
        $allTemplates = $allTplsStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true, 
            'template' => $template,
            'all_templates' => $allTemplates
        ], JSON_UNESCAPED_UNICODE);
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
            'V' => ['header' => 'SoGB',           'note' => 'Số giấy báo trúng tuyển'],
            'W' => ['header' => 'THOIGIANNHAP',  'note' => 'Thời gian tập trung nhập học, ví dụ: 05/09/2026'],
            'X' => ['header' => 'NGANH_TT',      'note' => 'Tên ngành in trên giấy báo trúng tuyển'],
            'Y' => ['header' => 'KHOA',          'note' => 'Tên Khoa quản lý'],
            'Z' => ['header' => 'KINHPHI',       'note' => 'Nội dung kinh phí thu (học phí, lệ phí...)'],
            'AA'=> ['header' => 'XACNHANBO',     'note' => 'Xác nhận nhập học Bộ GD&ĐT (1: Đã XN, 0: Chưa)'],
            'AB'=> ['header' => 'XACNHANTRUONG', 'note' => 'Xác nhận nhập học Hệ thống Trường (1: Đã XN, 0: Chưa)'],
            'AC'=> ['header' => 'FILEGIAYBAO',   'note' => 'Đường dẫn/URL file ảnh Giấy báo trúng tuyển'],
            'AD'=> ['header' => 'Email',         'note' => 'Bắt buộc. Email gửi Giấy báo trúng tuyển'],
            'AE'=> ['header' => 'SDT',           'note' => 'Số điện thoại liên lạc'],
            'AF'=> ['header' => 'GhiChu',        'note' => 'Ghi chú thêm (xếp hạng, ghi chú đặc biệt...)'],
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
            'X' => 'Công nghệ thông tin',
            'Y' => 'Khoa Công nghệ thông tin',
            'Z' => 'Học phí KĐ1: 5.000.000đ, Lệ phí: 200.000đ',
            'AA'=> '1',
            'AB'=> '1',
            'AC'=> 'https://example.com/giay-bao/012345678912.pdf',
            'AD'=> 'nguyenvanan@email.com',
            'AE'=> '0987654321',
            'AF'=> 'Thứ hạng 5/120 toàn ngành',
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

        // Đánh dấu 2 cột bắt buộc bằng màu nền đỏ trên hàng 1
        foreach (['A', 'AD'] as $reqCol) {
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
