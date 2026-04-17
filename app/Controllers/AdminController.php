<?php

namespace App\Controllers;


use App\Core\Controller;
use App\Core\UserStatus;
use App\Models\AdmissionSession;



use App\Repositories\ThiSinhRepository;
use App\Repositories\NguyenVongRepository;

class AdminController extends Controller
{

    protected $currentUser;
    protected ThiSinhRepository $thiSinhRepo;
    protected ?NguyenVongRepository $nguyenVongRepo = null;
    protected ?\App\Repositories\MasterDataRepository $masterDataRepo = null;
    protected ?\App\Repositories\AcademicRepository $academicRepo = null;
    protected ?\App\Repositories\ApplicationRepository $applicationRepo = null;
    protected ?\App\Services\MailerService $mailerService = null;
    protected ?\App\Services\EmailTemplateService $emailTemplateService = null;
    protected ?\App\Services\AuditService $auditService = null;
    protected ?\App\Services\ScoreCalculationService $scoreService = null;
    protected ?\App\Models\DiemThiTHPT $diemThiModel = null;

    public function __construct()
    {
        // Only initialize the essential repository used by nearly every action
        $this->thiSinhRepo = new ThiSinhRepository();

        // Load current user for permission checking - Use Cache
        if (isset($_SESSION['admin_id'])) {
            $adminId = $_SESSION['admin_id'];
            $sessionKey = '_cached_admin_user_' . $adminId;
            
            if (isset($_SESSION[$sessionKey])) {
                $this->currentUser = $_SESSION[$sessionKey];
            } else {
                $adminModel = new \App\Models\QuanTriVien();
                $this->currentUser = $adminModel->find($adminId);
                if ($this->currentUser) {
                    $_SESSION[$sessionKey] = $this->currentUser;
                }
            }
        } else {
            $this->currentUser = null;
        }

        // Enforce active status
        if (!$this->currentUser || !$this->currentUser['is_active']) {
            session_destroy();
            $this->redirect(url('/admin/login'));
        }
    }

    // --- Lazy Getters: Services & Repositories initialized on first use ---
    protected function getNguyenVongRepo(): NguyenVongRepository {
        if (!$this->nguyenVongRepo) $this->nguyenVongRepo = new NguyenVongRepository();
        return $this->nguyenVongRepo;
    }
    protected function getMasterDataRepo(): \App\Repositories\MasterDataRepository {
        if (!$this->masterDataRepo) $this->masterDataRepo = new \App\Repositories\MasterDataRepository();
        return $this->masterDataRepo;
    }
    protected function getAcademicRepo(): \App\Repositories\AcademicRepository {
        if (!$this->academicRepo) $this->academicRepo = new \App\Repositories\AcademicRepository();
        return $this->academicRepo;
    }
    protected function getApplicationRepo(): \App\Repositories\ApplicationRepository {
        if (!$this->applicationRepo) $this->applicationRepo = new \App\Repositories\ApplicationRepository();
        return $this->applicationRepo;
    }
    protected function getMailerService(): \App\Services\MailerService {
        if (!$this->mailerService) $this->mailerService = new \App\Services\MailerService();
        return $this->mailerService;
    }
    protected function getEmailTemplateService(): \App\Services\EmailTemplateService {
        if (!$this->emailTemplateService) $this->emailTemplateService = new \App\Services\EmailTemplateService();
        return $this->emailTemplateService;
    }
    protected function getAuditService(): \App\Services\AuditService {
        if (!$this->auditService) $this->auditService = new \App\Services\AuditService();
        return $this->auditService;
    }
    protected function getScoreService(): \App\Services\ScoreCalculationService {
        if (!$this->scoreService) $this->scoreService = new \App\Services\ScoreCalculationService();
        return $this->scoreService;
    }
    protected function getDiemThiModel(): \App\Models\DiemThiTHPT {
        if (!$this->diemThiModel) $this->diemThiModel = new \App\Models\DiemThiTHPT();
        return $this->diemThiModel;
    }

    // ... (existing methods)

    // bulkAction was moved to CandidateController
    /*
    public function bulkAction()
    {
        // ...
    }
    */

    protected function checkPermission($permission)
    {
        if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, $permission)) {
            echo "<script>alert('Bạn không có quyền truy cập chức năng này!'); window.location.href='" . url('/admin/dashboard') . "';</script>";
            exit;
        }
    }




    public function rotateImage()
    {
        $this->checkPermission('settings.edit'); // Ideally candidate.edit but letting settings editors/admins do it
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $filePath = $_POST['path'] ?? '';
        if (empty($filePath)) {
            echo json_encode(['success' => false, 'error' => 'Thiếu đường dẫn ảnh']);
            return;
        }

        // ============================================
        // Handle Google Drive Images
        // ============================================
        if (strpos($filePath, 'http') === 0 && strpos($filePath, 'drive.google.com') !== false) {
            preg_match('/id=([^&]+)/', $filePath, $matches);
            $fileId = $matches[1] ?? '';

            if (!$fileId) {
                echo json_encode(['success' => false, 'error' => 'Không thể trích xuất ID ảnh từ Google Drive']);
                return;
            }

            try {
                // Initialize Google Client
                $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');

                if (!file_exists($clientSecretPath) || !file_exists($tokenPath)) {
                    echo json_encode(['success' => false, 'error' => 'Chưa cấu hình JSON hoặc thiếu Token Google Drive trên máy chủ']);
                    return;
                }

                $client = new \Google\Client();
                $client->setAuthConfig($clientSecretPath);
                $client->addScope(\Google\Service\Drive::DRIVE_FILE);
                $client->setAccessType('offline');

                $accessToken = json_decode(file_get_contents($tokenPath), true);
                if (!$accessToken) {
                    echo json_encode(['success' => false, 'error' => 'Token Google Drive không hợp lệ']);
                    return;
                }

                $client->setAccessToken($accessToken);
                if ($client->isAccessTokenExpired()) {
                    if ($client->getRefreshToken()) {
                        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Token Google Drive hết hạn. Cần xác thực lại.']);
                        return;
                    }
                }

                $service = new \Google\Service\Drive($client);

                // Download file content
                /** @var \Psr\Http\Message\ResponseInterface $response */
                $response = $service->files->get($fileId, ['alt' => 'media']);
                $content = $response->getBody()->getContents();

                $sourceImage = @imagecreatefromstring($content);
                if (!$sourceImage) {
                    echo json_encode(['success' => false, 'error' => 'Google Drive: Không giải mã được định dạng ảnh gốc']);
                    return;
                }

                $degrees = -90; // Rotate 90 degrees clockwise
                $rotatedImage = imagerotate($sourceImage, $degrees, 0);
                if (!$rotatedImage) {
                    echo json_encode(['success' => false, 'error' => 'Google Drive: Lỗi Engine trong quá trình xoay.']);
                    imagedestroy($sourceImage);
                    return;
                }

                // Save rotated to temp file
                $tmpFile = tempnam(sys_get_temp_dir(), 'rot_');
                imagejpeg($rotatedImage, $tmpFile, 90);

                // Upload back to override
                $emptyFile = new \Google\Service\Drive\DriveFile();
                $service->files->update($fileId, $emptyFile, [
                    'data' => file_get_contents($tmpFile),
                    'mimeType' => 'image/jpeg',
                    'uploadType' => 'media'
                ]);

                imagedestroy($sourceImage);
                imagedestroy($rotatedImage);
                @unlink($tmpFile);

                echo json_encode(['success' => true]);
                return;
            } catch (\Exception $e) {
                error_log("Google Drive Rotate Error: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Lỗi API Google Drive - Bật log kiểm tra chi tiết.']);
                return;
            }
        }

        // ============================================
        // Handle Local Server Images
        // ============================================
        // Clean path and prevent directory traversal
        $filePath = ltrim(parse_url($filePath, PHP_URL_PATH), '/');
        $filePath = str_replace('../', '', $filePath);

        // Map public URI to local file path
        // Assume URL might start with /TS/ or similar depending on setup
        // Strip base path if exists
        $basePath = '/TS/';
        if (strpos('/' . $filePath, $basePath) === 0) {
            $filePath = substr('/' . $filePath, strlen($basePath));
        }

        // Standardize file path
        $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
        $absolutePath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $filePath;

        if (!$absolutePath || !file_exists($absolutePath) || !is_file($absolutePath)) {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy file ảnh gốc: ' . $filePath]);
            return;
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png'];

        if (!in_array($extension, $allowedExtensions)) {
            echo json_encode(['success' => false, 'error' => 'Chỉ hỗ trợ xoay ảnh JPG/PNG']);
            return;
        }

        $degrees = -90; // Rotate 90 degrees clockwise
        $sourceImage = null;

        if ($extension === 'png') {
            $sourceImage = @imagecreatefrompng($absolutePath);
        } else {
            $sourceImage = @imagecreatefromjpeg($absolutePath);
        }

        if (!$sourceImage) {
            echo json_encode(['success' => false, 'error' => 'Không thể đọc file ảnh (cấp quyền hoặc ảnh lỗi)']);
            return;
        }

        $rotatedImage = imagerotate($sourceImage, $degrees, 0);

        if (!$rotatedImage) {
            echo json_encode(['success' => false, 'error' => 'Gặp lỗi trong quá trình xoay ảnh']);
            imagedestroy($sourceImage);
            return;
        }

        $success = false;
        if ($extension === 'png') {
            imagesavealpha($rotatedImage, true);
            $success = imagepng($rotatedImage, $absolutePath);
        } else {
            $success = imagejpeg($rotatedImage, $absolutePath, 90);
        }

        imagedestroy($sourceImage);
        imagedestroy($rotatedImage);

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Lỗi phân quyền khi ghi đè file ảnh']);
        }
    }

    public function approveEditRequest()
    {
        $this->checkPermission('review');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cccd = $_POST['cccd'] ?? '';

            if (!$cccd) {
                $this->redirect(url('/admin/dashboard'));
            }

            // Use Repository
            if ($this->thiSinhRepo->approveEditRequest($cccd)) {
                $this->redirect(url('/admin/review?cccd=' . $cccd . '&updated=1'));
            } else {
                $this->redirect(url('/admin/review?cccd=' . $cccd . '&error=1'));
            }
        } else {
            $this->redirect(url('/admin/dashboard'));
        }
    }

    public function review()
    {
        $this->checkPermission('review');
        $cccd = $_GET['cccd'] ?? '';
        if (empty($cccd)) {
            $this->redirect(url('/admin/dashboard'));
        }

        $data = $this->prepareReviewData($cccd);
        if (!$data) {
            $this->redirect(url('/admin/dashboard'));
        }

        $this->view('admin/review', $data);
    }

    /**
     * AJAX Endpoint for lazy loading review tabs
     */
    public function reviewTab()
    {
        $this->checkPermission('review');
        $cccd = $_GET['cccd'] ?? '';
        $tab = $_GET['tab'] ?? '';

        if (empty($cccd) || empty($tab)) {
            return $this->json(['success' => false, 'error' => 'Missing params']);
        }

        $data = $this->prepareReviewData($cccd);
        if (!$data) {
            return $this->json(['success' => false, 'error' => 'Candidate not found']);
        }

        // Include view helpers for things like render_evidence_item
        include __DIR__ . '/../../resources/views/admin/review/_helpers.php';

        // Render just the specific tab partial
        $viewPath = "admin/review/_tab_{$tab}";
        $this->view($viewPath, $data);
        exit;
    }

    /**
     * Optimized AJAX Endpoint for batch loading all remaining review tabs in one request
     */
    public function reviewBatchTabs()
    {
        $this->checkPermission('review');
        $cccd = $_GET['cccd'] ?? '';

        if (empty($cccd)) {
            return $this->json(['success' => false, 'error' => 'Missing CCCD']);
        }

        $data = $this->prepareReviewData($cccd);
        if (!$data) {
            return $this->json(['success' => false, 'error' => 'Candidate not found']);
        }

        // Include view helpers for things like render_evidence_item
        include __DIR__ . '/../../resources/views/admin/review/_helpers.php';

        $tabs = ['academic', 'certs', 'thpt', 'wishes'];
        $rendered = [];

        foreach ($tabs as $tab) {
            ob_start();
            $this->view("admin/review/_tab_{$tab}", $data);
            $rendered[$tab] = ob_get_clean();
        }

        return $this->json([
            'success' => true,
            'tabs' => $rendered
        ]);
    }

    private function prepareReviewData($cccd)
    {
        // SINGLE query: candidate + academic + nguyen_vong + certificates + diemThi
        $bundle = $this->thiSinhRepo->getReviewBundle($cccd);
        if (!$bundle) {
            return null;
        }

        $user = $bundle['user'];
        $academicRecords = $bundle['academic'];
        $choices = $bundle['choices'];
        $certificates = $bundle['certificates'];
        $diemThi = $bundle['diemThi'];

        // Fetch majors with combinations for the wishes tab display
        $majors = $this->getMasterDataRepo()->getMajorsWithCombinations();

        // Province list (file-cached — no DB hit after first call)  
        $provinces = $this->getMasterDataRepo()->getProvinces();
        $priorityAreas = $this->getMasterDataRepo()->getPriorityAreas();
        $priorityObjects = $this->getMasterDataRepo()->getPriorityObjects();
        
        $emailTemplates = \App\Core\Cache::remember('email_templates_all', 60, function () {
            $model = new \App\Models\EmailTemplate();
            return $model->getAll();
        });

        // Adjacent candidates
        $adjacent = $this->thiSinhRepo->getAdjacentCandidates($cccd);

        // Subject mapping (match student step 2 exactly)
        $subjects = [
            'van' => 'Ngữ văn',
            'toan' => 'Toán',
            'su' => 'Lịch sử',
            'dia' => 'Địa lí',
            'gdcd' => 'GDKT & PL',
            'ly' => 'Vật lí',
            'hoa' => 'Hóa học',
            'sinh' => 'Sinh học',
            'cong_nghe' => 'Công nghệ',
            'tin_hoc' => 'Tin học',
            'ngoai_ngu' => 'Ngoại ngữ'
        ];

        // Process certificates (Use existing data without adding new DB dependencies)
        if (!empty($certificates)) {
            foreach ($certificates as &$cert) {
                // Keep existing fields, don't try to resolve rules from missing tables
                $cert['diem_quy_doi'] = $cert['diem_quy_doi'] ?? 0;
            }
            unset($cert);
        }

        // Helper mapDisplay for grades/conduct
        $mapDisplay = function ($val) {
            $map = [
                'Gioi' => 'Giỏi',
                'Kha' => 'Khá',
                'Trung binh' => 'Trung bình',
                'TrungBinh' => 'Trung bình',
                'Yeu' => 'Yếu',
                'Tot' => 'Tốt'
            ];
            return $map[$val] ?? $val;
        };

        // Prepare rowsByGrade
        $rowsByGrade = [];
        foreach ($academicRecords as $r) {
            $rowsByGrade[$r['lop']] = $r;
        }

        // Already in getDetail JOINs — no extra queries
        $wardName = $user['ten_xa_tt'] ?? '';
        $schoolName = $user['ten_truong_lop_12'] ?? '';
        $hasEditRequest = !empty($user['yeu_cau_chinh_sua']);

        return [
            'user' => $user,
            'wardName' => $wardName,
            'schoolName' => $schoolName,
            'subjects' => $subjects,
            'mapDisplay' => $mapDisplay,
            'rowsByGrade' => $rowsByGrade,
            'certificates' => $certificates,
            'academicRecords' => $academicRecords,
            'academicRows' => $academicRecords,
            'choices' => $choices,
            'majors' => $majors,
            'currentUser' => $this->currentUser,
            'hasEditRequest' => $hasEditRequest,
            'diemThi' => $diemThi,
            'provinces' => $provinces,
            'priorityAreas' => $priorityAreas,
            'priorityObjects' => $priorityObjects,
            'prevCCCD' => $adjacent['prev'],
            'nextCCCD' => $adjacent['next'],
            'navPosition' => $adjacent['position'],
            'navTotal' => $adjacent['total'],
            'emailTemplates' => $emailTemplates
        ];
    }

    public function updateStatus()
    {
        $this->checkPermission('review');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $so_cccd = $_POST['cccd'] ?? '';
            $status = $_POST['status'] ?? '';
            $note = $_POST['note'] ?? '';
            $sendEmail = isset($_POST['send_email']) && $_POST['send_email'] == '1';

            // Sync status to ho_so_xet_tuyen (and nguyen_vong) using Repository
            $reviewerId = $this->currentUser['id'] ?? null;
            $this->thiSinhRepo->updateApplicationStatus($so_cccd, $status, $note, $reviewerId);

            // Send email notification if requested
            if ($sendEmail) {
                try {
                    $candidate = $this->thiSinhRepo->findByCCCD($so_cccd);
                    if ($candidate && !empty($candidate['email'])) {
                        $sections = [];
                        $sections[] = ['name' => 'Trạng thái hồ sơ', 'status' => $status === UserStatus::APPROVED ? 'ok' : 'missing', 'note' => $status];

                        // Check profile completeness
                        if (!empty($candidate['anh_chan_dung'])) {
                            $sections[] = ['name' => 'Ảnh chân dung', 'status' => 'ok', 'note' => ''];
                        } else {
                            $sections[] = ['name' => 'Ảnh chân dung', 'status' => 'missing', 'note' => 'Chưa upload'];
                        }

                        $resultHtml = $this->getEmailTemplateService()->buildReviewResultHtml($sections);

                        $this->getEmailTemplateService()->queueWithTemplate($candidate['email'], 'application_reviewed', [
                            'ho_ten' => $candidate['ho_va_ten'],
                            'ket_qua_chi_tiet' => $resultHtml,
                            'ghi_chu' => $note ?: 'Không có ghi chú.'
                        ]);
                    }
                } catch (\Exception $e) {
                    error_log("Failed to send review email: " . $e->getMessage());
                }
            }

            $nextCccd = $this->thiSinhRepo->getNextPendingCandidate($so_cccd);

            if ($nextCccd) {
                // Tự động chuyển trang đến hồ sơ chờ duyệt kế tiếp
                $this->redirect(url('/admin/review?cccd=' . $nextCccd . '&updated=1'));
            } else {
                // Trở về danh sách nếu đã duyệt hết tất cả
                $this->redirect(url('/admin/candidates?success=1'));
            }
        }
    }

    public function dashboard()
    {
        $this->checkPermission('stats');

        $sessionModel = new AdmissionSession();
        $sessions = \App\Core\Cache::remember('all_sessions', 30, fn() => $sessionModel->getAll());

        $years = array_unique(array_column($sessions, 'nam_tuyen_sinh'));
        rsort($years);

        $selectedYear = $_GET['year'] ?? null;
        $sessionId    = $_GET['session_id'] ?? null;

        if ($selectedYear === null && $sessionId === null) {
            $activeSession = $sessionModel->getActiveSession();
            
            // Smarter logic: If active session is empty, but we have older sessions with data, 
            // maybe default to the one with data? 
            // Actually, let's stick to active but if no active, get latest.
            if (!$activeSession) {
                $activeSession = $sessionModel->getLatestActiveSession();
            }

            if ($activeSession) {
                $sessionId   = $activeSession['id'];
                $selectedYear = $activeSession['nam_tuyen_sinh'];
                
                // Extra check: if this session is totally empty, check if it's the 2026 one we just added
                // if it's empty, and there's a 2025 session with data, the user probably wants to see that.
                $hasData = \App\Core\Cache::remember('session_has_data_' . $sessionId, 60, function() use ($sessionId) {
                    $db = \App\Core\Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ?");
                    $stmt->execute([$sessionId]);
                    return $stmt->fetchColumn() > 0;
                });

                if (!$hasData && $selectedYear == 2026) {
                    // Fallback to 2025 latest session if 2026 is empty
                    $prevSession = \App\Core\Cache::remember('latest_session_2025', 60, function() {
                        $db = \App\Core\Database::getInstance()->getConnection();
                        $stmt = $db->query("SELECT * FROM dot_tuyen_sinh WHERE nam_tuyen_sinh = 2025 ORDER BY id DESC LIMIT 1");
                        return $stmt->fetch(\PDO::FETCH_ASSOC);
                    });
                    if ($prevSession) {
                        $sessionId = $prevSession['id'];
                        $selectedYear = 2025;
                    }
                }
            } elseif (!empty($sessions)) {
                $firstSession = $sessions[0];
                $sessionId   = $firstSession['id'];
                $selectedYear = $firstSession['nam_tuyen_sinh'];
            } else {
                $selectedYear = date('Y');
            }
        } elseif ($selectedYear === null) {
            $selectedYear = !empty($years) ? reset($years) : date('Y');
        }

        if ($selectedYear && !isset($_GET['start']) && !isset($_GET['end'])) {
            $startDate = "$selectedYear-01-01";
            $endDate   = "$selectedYear-12-31";
        } else {
            $y         = $selectedYear ?: date('Y');
            $startDate = $_GET['start'] ?? "$y-01-01";
            $endDate   = $_GET['end']   ?? "$y-12-31";
        }

        $this->view('admin/dashboard', [
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'sessions'        => $sessions,
            'years'           => $years,
            'selectedYear'    => $selectedYear,
            'currentSessionId' => $sessionId,
            'stats'           => ['total' => 0, 'pending' => 0, 'approved' => 0, 'require_edit' => 0, 'ghost' => 0],
            'user'            => $this->currentUser
        ]);
    }

    public function stats()
    {
        // Mặc định bây giờ stats là dashboard
        $this->redirect(url('/admin/dashboard'));
    }

    public function statsApi()
    {
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $sessionModel = new AdmissionSession();

        $selectedYear = $_GET['year'] ?? null;
        $sessionId = $_GET['session_id'] ?? null;

        if ($selectedYear === null && $sessionId === null) {
            $activeSession = $sessionModel->getActiveSession();
            if ($activeSession) {
                $sessionId = $activeSession['id'];
                $selectedYear = $activeSession['nam_tuyen_sinh'];
            } else {
                // Fallback: Get year from latest active session or just latest session
                $latestSession = $sessionModel->getLatestActiveSession();
                if ($latestSession) {
                    $selectedYear = $latestSession['nam_tuyen_sinh'];
                } else {
                    // Get all sessions to find latest year
                    $sessions = $sessionModel->getAll();
                    if (!empty($sessions)) {
                        $years = array_unique(array_column($sessions, 'nam_tuyen_sinh'));
                        rsort($years);
                        $selectedYear = reset($years);
                    } else {
                        $selectedYear = date('Y');
                    }
                }
            }
        } elseif ($selectedYear === null) {
            // Fallback year logic (if passed session_id but no year, or just weird state)
            $sessions = $sessionModel->getAll();
            $years = array_unique(array_column($sessions, 'nam_tuyen_sinh'));
            rsort($years);
            $selectedYear = !empty($years) ? reset($years) : date('Y');
        }

        if ($selectedYear && !isset($_GET['start']) && !isset($_GET['end'])) {
            $startDate = "$selectedYear-01-01";
            $endDate = "$selectedYear-12-31";
        } else {
            $y = $selectedYear ?: date('Y');
            $startDate = $_GET['start'] ?? "$y-01-01";
            $endDate = $_GET['end'] ?? "$y-12-31";
        }

        $type = $_GET['type'] ?? 'all';
        $forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';

        // Fetch Data — cached per unique filter combo (2 min TTL)
        $cacheKey = 'stats_api_' . md5("$selectedYear|$sessionId|$startDate|$endDate|$type");
        
        try {
            // Function to fetch fresh data
            $fetchData = function () use ($startDate, $endDate, $sessionId, $selectedYear, $type) {
                $data = [];

                if ($type === 'overview' || $type === 'all') {
                    $data['overview'] = $this->thiSinhRepo->getStats($sessionId, $selectedYear, $startDate, $endDate);
                    $data['daily']    = $this->getApplicationRepo()->getDailyStats($startDate, $endDate, $sessionId);
                    $data['recent']   = $this->thiSinhRepo->getRecentRegistrationStats($sessionId);
                    $data['latest']   = $this->thiSinhRepo->getLatestCandidates(5, $sessionId);
                    $data['major']    = $this->getNguyenVongRepo()->getMajorStats(30, $startDate, $endDate, $sessionId);
                }

                if ($type === 'majors' || $type === 'all') {
                    $data['major']                = $this->getNguyenVongRepo()->getMajorStats(30, $startDate, $endDate, $sessionId);
                    $data['detailed_major_stats'] = $this->getNguyenVongRepo()->getDetailedMajorStats($startDate, $endDate, $sessionId);
                }

                if ($type === 'demographics' || $type === 'all') {
                    $data['province']  = $this->thiSinhRepo->getProvinceStats(10, $startDate, $endDate, $sessionId);
                    $data['school']    = $this->thiSinhRepo->getSchoolStats(15, $startDate, $endDate, $sessionId);
                    $data['reviewers'] = $this->thiSinhRepo->getReviewerStats($sessionId, $selectedYear);
                    
                    // Demographic query (Gender, Area, Object) - use combined query to save 2 DB roundtrips
                    $combinedDemos = $this->thiSinhRepo->getCombinedDemographicStats($startDate, $endDate, $sessionId);
                    $data['gender'] = $combinedDemos['gender'] ?? [];
                    $data['area'] = $combinedDemos['area'] ?? [];
                    $data['object'] = $combinedDemos['object'] ?? [];
                }

                return $data;
            };

            if ($forceRefresh) {
                $result = $fetchData();
                \App\Core\Cache::put($cacheKey, $result, 10);
                $result['refreshed'] = true;
            } else {
                $result = \App\Core\Cache::remember($cacheKey, 10, $fetchData);
            }

            // Real-time Online Stats (Always fetch, never cache)
            if ($type === 'overview' || $type === 'all') {
                $onlineRepo = new \App\Repositories\OnlineTrackingRepository();
                $result['online_stats'] = $onlineRepo->getOnlineStats(15);
            }

            $result['meta'] = [
                'type'          => $type,
                'version_debug' => '1.0.8-PRECISION-LOGIC',
                'year'          => $selectedYear,
                'session_id'    => $sessionId,
                'start'         => $startDate,
                'end'           => $endDate
            ];

            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    public function homeSettings()
    {
        $this->checkPermission('stats'); // Reuse stats permission

        $masterDataRepo = new \App\Repositories\MasterDataRepository();

        // Handle POST - Save settings
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $videoInput = $_POST['video_url'] ?? '';
            $statsMajors = $_POST['stats_majors'] ?? '27';
            $statsQuota = $_POST['stats_quota'] ?? '3070';
            $statsEmploy = $_POST['stats_employ'] ?? '98%';
            $announcement = $_POST['announcement'] ?? '';

            // Extract YouTube video ID
            $videoId = $this->extractYouTubeId($videoInput);

            if (!$videoId) {
                $this->view('admin/home_settings', [
                    'error' => 'URL YouTube không hợp lệ. Vui lòng nhập URL hoặc Video ID hợp lệ.',
                    'settings' => $_POST,
                    'user' => $this->currentUser
                ]);
                return;
            }

            // Update settings using Repository
            if ($masterDataRepo->updateHomeSettings($videoId, $statsMajors, $statsQuota, $statsEmploy, $announcement)) {
                // Invalidate homepage session cache
                unset($_SESSION['cache_home_settings']);
                unset($_SESSION['cache_admission_conditions']);

                $this->redirect(url('/admin/settings/home?success=1'));
            } else {
                $this->view('admin/home_settings', [
                    'error' => 'Lỗi khi lưu cài đặt.',
                    'settings' => $_POST,
                    'user' => $this->currentUser
                ]);
            }
            return;
        }

        // GET - Display form
        $settings = $masterDataRepo->getHomeSettings();

        $this->view('admin/home_settings', [
            'settings' => $settings,
            'success' => isset($_GET['success']),
            'user' => $this->currentUser
        ]);
    }

    private function extractYouTubeId($input)
    {
        $input = trim($input);

        // If already just ID (11 chars alphanumeric, dash, underscore)
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $input)) {
            return $input;
        }

        // Extract from various YouTube URL formats
        $patterns = [
            '/youtube\\.com\\/watch\\?v=([a-zA-Z0-9_-]+)/',
            '/youtu\\.be\\/([a-zA-Z0-9_-]+)/',
            '/youtube\\.com\\/embed\\/([a-zA-Z0-9_-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function submitReview()
    {
        $this->checkPermission('review');
        $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? '';

        if (!$cccd) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Missing CCCD']);
            exit;
        }

        // Pre-load candidate info to avoid extra DB query inside processReview
        $candidate = $this->thiSinhRepo->findByCCCD($cccd);
        $candidateInfo = $candidate ? [
            'email' => $candidate['email'] ?? '',
            'ho_va_ten' => $candidate['ho_va_ten'] ?? ''
        ] : null;

        $admissionService = new \App\Services\AdmissionService();
        $reviewerId = $_SESSION['admin_id'] ?? null;
        $result = $admissionService->processReview($cccd, $_POST, $candidateInfo, $reviewerId);

        // Return JSON for AJAX handling
        header('Content-Type: application/json');
        $nextUrl = $result['next_cccd']
            ? url('/admin/review?cccd=' . $result['next_cccd'] . '&updated=1')
            : url('/admin/dashboard?updated=1');

        echo json_encode([
            'success' => true,
            'is_rejected' => $result['is_rejected'],
            'next_url' => $nextUrl
        ]);
        exit;
    }

    public function calculateScores()
    {
        $this->checkPermission('review');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            set_time_limit(0); // Prevent timeout

            // Fetch ALL candidates
            // Consider chunking if memory issues, but 3000 rows is small for memory.
            $candidates = $this->thiSinhRepo->findAll();
            $count = 0;

            foreach ($candidates as $c) {
                // We should only calculate for those with completed profile?
                // Or just all. Safe to calculate all.
                try {
                    $this->getScoreService()->calculate($c['so_cccd']);
                    $count++;
                } catch (\Exception $e) {
                    error_log("Calculation error for " . $c['so_cccd'] . ": " . $e->getMessage());
                }
            }

            $this->redirect(url('/admin/dashboard?msg=' . urlencode("Đã tính toán điểm xét tuyển cho $count thí sinh.")));
        }
    }
}
