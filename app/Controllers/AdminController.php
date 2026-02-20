<?php
namespace App\Controllers;


use App\Core\Controller;
use App\Core\UserStatus;
use App\Models\AdmissionSession;



use App\Repositories\ThiSinhRepository;
use App\Repositories\NguyenVongRepository;

class AdminController extends Controller {

    // ... (existing properties)
    protected \App\Services\MailerService $mailerService;
    protected \App\Services\EmailTemplateService $emailTemplateService;
    protected \App\Services\AuditService $auditService;
    protected \App\Services\ScoreCalculationService $scoreService;
    protected \App\Models\DiemThiTHPT $diemThiModel;

    public function __construct() {
        // Repositories
        $this->thiSinhRepo = new ThiSinhRepository();
        $this->nguyenVongRepo = new NguyenVongRepository();
        $this->masterDataRepo = new \App\Repositories\MasterDataRepository();
        $this->academicRepo = new \App\Repositories\AcademicRepository();
        $this->applicationRepo = new \App\Repositories\ApplicationRepository();
        
        // Services
        $this->mailerService = new \App\Services\MailerService();
        $this->emailTemplateService = new \App\Services\EmailTemplateService();
        $this->auditService = new \App\Services\AuditService();
        $this->scoreService = new \App\Services\ScoreCalculationService();

        // Load current user for permission checking
        $adminModel = new \App\Models\QuanTriVien();
        $this->currentUser = $adminModel->find($_SESSION['admin_id'] ?? 0);
        
        // Enforce active status
        if (!$this->currentUser || !$this->currentUser['is_active']) {
            session_destroy();
            $this->redirect(url('/admin/login'));
        }

        // Models
        $this->diemThiModel = new \App\Models\DiemThiTHPT();
    }
    
    // ... (existing methods)

    public function bulkAction() {
        $this->checkPermission('manage_candidates'); // Assume basic permission

        // Check forced_action first (from JS fix)
        $action = $_POST['forced_action'] ?? $_POST['action'] ?? '';
        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            $this->redirect(url('/admin/dashboard?error=No candidates selected'));
            return;
        }



        switch ($action) {
            case 'update_status':
                $status = $_POST['status'] ?? '';
                if ($status && !empty($ids)) {
                    if ($this->thiSinhRepo->bulkUpdateStatus($ids, $status)) {
                        $count = count($ids);
                        $this->redirect(url("/admin/dashboard?success=Cập nhật trạng thái cho $count thí sinh thành công."));
                    } else {
                        $this->redirect(url("/admin/dashboard?error=Lỗi cập nhật trạng thái."));
                    }
                }
                break;
                
            case 'delete':
                $count = 0;
                foreach ($ids as $cccd) {
                    if ($this->thiSinhRepo->delete($cccd)) {
                        $count++;
                    }
                }
                $this->redirect(url("/admin/dashboard?success=Deleted $count candidates"));
                break;
                
            case 'transfer':
                $targetSessionId = $_POST['target_session_id'] ?? '';
                if ($targetSessionId) {
                    try {
                        $count = $this->applicationRepo->transferSession($ids, (int)$targetSessionId);
                        
                        // Preserve filters
                        $filters = [
                            'success' => "Đã chuyển $count hồ sơ sang đợt mới.",
                            'year' => $_POST['current_year'] ?? '',
                            'session_id' => $_POST['current_session_id'] ?? '',
                            'status' => $_POST['current_status'] ?? '',
                            'search' => $_POST['current_search'] ?? ''
                        ];
                        
                        $this->redirect(url("/admin/dashboard?" . http_build_query(array_filter($filters))));
                    } catch (\Throwable $e) {
                         $this->redirect(url("/admin/dashboard?error=Lỗi hệ thống: " . urlencode($e->getMessage())));
                    }
                } else {
                    $this->redirect(url("/admin/dashboard?error=Chưa chọn đợt đích."));
                }
                break;
                
            case 'send_email':
                $subject = $_POST['email_subject'] ?? '';
                $content = $_POST['email_content'] ?? '';
                
                if ($subject && $content) {
                    $candidates = $this->thiSinhRepo->findManyByCCCD($ids);
                    $count = 0;
                    foreach ($candidates as $candidate) {
                        if (!empty($candidate['email'])) {
                            // Simple personalization
                            $personalContent = str_replace(['{{name}}', '{{ho_ten}}'], $candidate['ho_va_ten'], $content);
                            $this->mailerService->send($candidate['email'], $subject, nl2br($personalContent), true);
                            $count++;
                        }
                    }
                    $this->redirect(url("/admin/dashboard?success=Đã gửi email tới $count thí sinh."));
                } else {
                    $this->redirect(url("/admin/dashboard?error=Thiếu tiêu đề hoặc nội dung email."));
                }
                break;

            default:
                $debugAction = is_array($action) ? 'Array' : $action;
                $this->redirect(url('/admin/dashboard?error=Invalid action: ' . urlencode((string)$debugAction)));
        }
    }

    protected function checkPermission($permission) {
        if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, $permission)) {
             echo "<script>alert('Bạn không có quyền truy cập chức năng này!'); window.location.href='" . url('/admin/dashboard') . "';</script>";
             exit;
        }
    }

    public function dashboard() {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $hocBaStatus = $_GET['hoc_ba_status'] ?? '';
        $editRequest = $_GET['edit_request'] ?? '';
        
        // Load sessions (cached 30 min — rarely changes)
        $admissionSessionModel = new \App\Models\AdmissionSession();
        $sessions = \App\Core\Cache::remember('all_sessions', 30, function() use ($admissionSessionModel) {
            return $admissionSessionModel->getAll();
        });
        
        // Extract distinct years for filter
        $years = [];
        foreach ($sessions as $s) {
            $years[$s['nam_tuyen_sinh']] = $s['nam_tuyen_sinh'];
        }
        arsort($years);

        // Time Filters
        $year = $_GET['year'] ?? null;
        $sessionId = $_GET['session_id'] ?? null;

        // Default to Active Session if no filters provided
        if ($year === null && $sessionId === null) {
            $activeSession = $admissionSessionModel->getActiveSession(); // already cached
            if (!$activeSession) {
                $activeSession = $admissionSessionModel->getLatestActiveSession();
            }
            
            if ($activeSession) {
                $sessionId = $activeSession['id'];
                $year = $activeSession['nam_tuyen_sinh'];
            } elseif (!empty($years)) {
                 $year = reset($years);
                 if (!empty($sessions)) {
                     $firstSession = $sessions[0];
                     $sessionId = $firstSession['id'];
                     $year = $firstSession['nam_tuyen_sinh'];
                 }
            } else {
                $year = date('Y');
            }
        } elseif ($year === null) {
             $year = !empty($years) ? reset($years) : date('Y');
        }
        
        // Filter sessions by selected year for display logic
        $yearSessions = array_filter($sessions, function($s) use ($year) {
            return $s['nam_tuyen_sinh'] == $year;
        });

        // Pagination & Sorting Defaults
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        $sort = $_GET['sort'] ?? 'created_at';
        $dir = $_GET['dir'] ?? 'DESC';

        // Single query: candidates + total count (via COUNT OVER window function)
        $candidates = $this->thiSinhRepo->getFiltered(
            $search, $status, $hocBaStatus, $limit, $offset, 
            $sessionId, 
            $editRequest == '1',
            $year,
            $sort,
            $dir
        );

        // Extract total from _total_count window function — no separate countFiltered query needed
        $total = !empty($candidates) ? (int)($candidates[0]['_total_count'] ?? 0) : 0;
        $totalPages = ceil($total / max($limit, 1));
        
        // Clean _total_count from candidate rows
        foreach ($candidates as &$c) {
            unset($c['_total_count']);
        }
        unset($c);

        // Stats query (1 round-trip)
        $stats = $this->thiSinhRepo->getStats($sessionId, $year); 

        // Email templates (cached 60 min — rarely changes)
        $emailTemplates = \App\Core\Cache::remember('email_templates_all', 60, function() {
            $model = new \App\Models\EmailTemplate();
            return $model->getAll();
        });

        $this->view('admin/dashboard', [
            'candidates' => $candidates,
            'stats' => $stats,
            'sessions' => $sessions,
            'yearSessions' => $yearSessions,
            'years' => $years,
            'filters' => [
                'search' => $search, 
                'status' => $status, 
                'hoc_ba_status' => $hocBaStatus,
                'edit_request' => $editRequest,
                'session_id' => $sessionId,
                'year' => $year,
                'sort' => $sort,
                'dir' => $dir
            ],
            'pagination' => ['current_page' => $page, 'total_pages' => $totalPages],
            'emailTemplates' => $emailTemplates
        ]);
    }


    public function approveEditRequest() {
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

    public function review() {
        $this->checkPermission('review');
        $cccd = $_GET['cccd'] ?? null;
        if (empty($cccd)) {
            $this->redirect(url('/admin/dashboard'));
        }

        // SINGLE query: candidate + academic + nguyen_vong + certificates + diemThi
        $bundle = $this->thiSinhRepo->getReviewBundle($cccd);
        if (!$bundle) {
            $this->redirect(url('/admin/dashboard'));
        }

        $user = $bundle['user'];
        $academicRecords = $bundle['academic'];
        $choices = $bundle['choices'];
        $certificates = $bundle['certificates'];
        $diemThi = $bundle['diemThi'];
        
        // Province list (file-cached — no DB hit after first call)  
        $provinces = $this->masterDataRepo->getProvinces();

        // Navigation: prev/next pending candidates (1 query)
        $adjacent = $this->thiSinhRepo->getAdjacentCandidates($cccd);

        // Already in getDetail JOINs — no extra queries
        $wardName = $user['ten_xa_tt'] ?? '';
        $schoolName = $user['ten_truong_lop_12'] ?? '';
        $hasEditRequest = !empty($user['yeu_cau_chinh_sua']);

        $this->view('admin/review', [
            'user' => $user,
            'wardName' => $wardName,
            'schoolName' => $schoolName,
            'certificates' => $certificates,
            'academicRecords' => $academicRecords,
            'academicRows' => $academicRecords,
            'choices' => $choices,
            'currentUser' => $this->currentUser,
            'hasEditRequest' => $hasEditRequest,
            'diemThi' => $diemThi,
            'provinces' => $provinces,
            'prevCCCD' => $adjacent['prev'],
            'nextCCCD' => $adjacent['next'],
            'navPosition' => $adjacent['position'],
            'navTotal' => $adjacent['total']
        ]);
    }

    public function updateStatus() {
        $this->checkPermission('review');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $so_cccd = $_POST['cccd'] ?? '';
            $status = $_POST['status'] ?? '';
            $note = $_POST['note'] ?? '';
            $sendEmail = isset($_POST['send_email']) && $_POST['send_email'] == '1';
            
            // Sync status to ho_so_xet_tuyen (and nguyen_vong) using Repository
            $this->thiSinhRepo->updateApplicationStatus($so_cccd, $status, $note);

            // Send email notification if requested
            if ($sendEmail) {
                try {
                    $candidate = $this->thiSinhRepo->findByCCCD($so_cccd);
                    if ($candidate && !empty($candidate['email'])) {
                        $emailService = new \App\Services\EmailTemplateService();
                        
                        // Build review result sections
                        $sections = [];
                        $sections[] = ['name' => 'Trạng thái hồ sơ', 'status' => $status === UserStatus::APPROVED ? 'ok' : 'missing', 'note' => $status];
                        
                        // Check profile completeness
                        if (!empty($candidate['anh_chan_dung'])) {
                            $sections[] = ['name' => 'Ảnh chân dung', 'status' => 'ok', 'note' => ''];
                        } else {
                            $sections[] = ['name' => 'Ảnh chân dung', 'status' => 'missing', 'note' => 'Chưa upload'];
                        }
                        
                        $resultHtml = $emailService->buildReviewResultHtml($sections);
                        
                        $emailService->queueWithTemplate($candidate['email'], 'application_reviewed', [
                            'ho_ten' => $candidate['ho_va_ten'],
                            'ket_qua_chi_tiet' => $resultHtml,
                            'ghi_chu' => $note ?: 'Không có ghi chú.'
                        ]);
                    }
                } catch (\Exception $e) {
                    error_log("Failed to send review email: " . $e->getMessage());
                }
            }

            $this->redirect(url('/admin/review?cccd=' . $so_cccd . '&updated=1'));
        }
    }

    public function stats() {
        $this->checkPermission('stats');
        
        // Load sessions first to get years (needed for filter dropdowns only)
        $sessionModel = new \App\Models\AdmissionSession();
        $sessions = \App\Core\Cache::remember('all_sessions', 30, fn() => $sessionModel->getAll());
        
        // Extract years
        $years = array_unique(array_column($sessions, 'nam_tuyen_sinh'));
        rsort($years);
        
        $selectedYear = $_GET['year'] ?? null;
        $sessionId    = $_GET['session_id'] ?? null;

        // Default to Active Session if no filters provided
        if ($selectedYear === null && $sessionId === null) {
            $activeSession = $sessionModel->getActiveSession();
            if (!$activeSession) {
                $activeSession = $sessionModel->getLatestActiveSession();
            }
            if ($activeSession) {
                $sessionId   = $activeSession['id'];
                $selectedYear = $activeSession['nam_tuyen_sinh'];
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
        
        // Determine date range (passed to view as defaults for AJAX calls)
        if ($selectedYear && !isset($_GET['start']) && !isset($_GET['end'])) {
            $startDate = "$selectedYear-01-01";
            $endDate   = "$selectedYear-12-31";
        } else {
            $y         = $selectedYear ?: date('Y');
            $startDate = $_GET['start'] ?? "$y-01-01";
            $endDate   = $_GET['end']   ?? "$y-12-31";
        }

        // NOTE: No heavy stat queries here — all data is loaded via AJAX to /admin/stats/api
        $this->view('admin/stats', [
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'sessions'        => $sessions,
            'years'           => $years,
            'selectedYear'    => $selectedYear,
            'currentSessionId'=> $sessionId,
            'stats'           => ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0],
            'user'            => $this->currentUser
        ]);
    }

    public function statsApi() {
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $sessionModel = new \App\Models\AdmissionSession();
        
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

        // Fetch Data — cached per unique filter combo (5 min TTL)
        $cacheKey = 'stats_api_' . md5("$selectedYear|$sessionId|$startDate|$endDate");
        try {
            $result = \App\Core\Cache::remember($cacheKey, 30, function() use ($startDate, $endDate, $sessionId, $selectedYear) {
                $dailyStats    = $this->applicationRepo->getDailyStats($startDate, $endDate, $sessionId);
                $majorStats    = $this->nguyenVongRepo->getMajorStats(30, $startDate, $endDate, $sessionId);
                $provinceStats = $this->thiSinhRepo->getProvinceStats(10, $startDate, $endDate, $sessionId);
                $schoolStats   = $this->thiSinhRepo->getSchoolStats(15, $startDate, $endDate, $sessionId);
                $overviewStats = $this->thiSinhRepo->getStats($sessionId, $selectedYear);

                // Consolidated query: gender + area + object in ONE round-trip
                $demographic = $this->thiSinhRepo->getCombinedDemographicStats($startDate, $endDate, $sessionId);

                return [
                    'overview' => $overviewStats,
                    'daily'    => $dailyStats,
                    'major'    => $majorStats,
                    'province' => $provinceStats,
                    'school'   => $schoolStats,
                    'gender'   => $demographic['gender'],
                    'area'     => $demographic['area'],
                    'object'   => $demographic['object'],
                ];
            });

            $result['meta'] = [
                'year'       => $selectedYear,
                'session_id' => $sessionId,
                'start'      => $startDate,
                'end'        => $endDate
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

    public function homeSettings() {
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
    
    private function extractYouTubeId($input) {
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

    public function submitReview() {
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
        $result = $admissionService->processReview($cccd, $_POST, $candidateInfo);

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

    public function calculateScores() {
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
                    $this->scoreService->calculate($c['so_cccd']);
                    $count++;
                } catch (\Exception $e) {
                    error_log("Calculation error for " . $c['so_cccd'] . ": " . $e->getMessage());
                }
            }
            
            $this->redirect(url('/admin/dashboard?msg=' . urlencode("Đã tính toán điểm xét tuyển cho $count thí sinh.")));
        }
    }
}

