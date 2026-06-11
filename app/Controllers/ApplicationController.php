<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\UserStatus;
use App\Repositories\ThiSinhRepository;
use App\Repositories\NguyenVongRepository;
use App\Repositories\MasterDataRepository;
use App\Repositories\SessionRepository;
use App\Repositories\ApplicationRepository;
use App\Repositories\AcademicRepository;
use App\Repositories\ThptRepository;

class ApplicationController extends Controller
{

    protected SessionRepository $sessionRepo;
    protected ApplicationRepository $applicationRepo;
    protected ThiSinhRepository $thiSinhRepo;
    protected NguyenVongRepository $nguyenVongRepo;
    protected MasterDataRepository $masterDataRepo;
    protected AcademicRepository $academicRepo;
    protected ThptRepository $thptRepo;

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(url('/login'));
        }
        $this->sessionRepo = new SessionRepository();
        $this->applicationRepo = new ApplicationRepository();
        $this->thiSinhRepo = new ThiSinhRepository();
        $this->nguyenVongRepo = new NguyenVongRepository();
        $this->masterDataRepo = new MasterDataRepository();
        $this->academicRepo = new AcademicRepository();
        $this->thptRepo = new ThptRepository();
    }

    // List all sessions and user's applications
    public function index(): void
    {
        $cccd = $_SESSION['cccd'];

        // 1. Get User Profile & Statuses using Repository
        $user = $this->thiSinhRepo->findByCCCD($cccd);
        $records = $this->academicRepo->getByCCCD($cccd);
        $certs = $this->thiSinhRepo->getCertifications($cccd);
        $thptScores = $this->thptRepo->getByCCCD($cccd);

        // 2. Get Active Session & Applications
        $activeSession = $this->sessionRepo->getActiveSession() ?? $this->sessionRepo->getLatestActiveSession();
        $applications = $this->applicationRepo->getByCCCD($cccd);

        // Find if user has a record for the active session
        $currentApp = null;
        if ($activeSession) {
            foreach ($applications as $app) {
                if ($app->dot_tuyen_sinh_id == $activeSession['id']) {
                    $currentApp = $app;
                    break;
                }
            }
        }

        $isLocked = false;
        $editRequestPending = false;
        $applicationStatus = '';

        if ($currentApp) {
            $applicationStatus = $currentApp->trang_thai ?? '';
            $isLocked = ($applicationStatus === UserStatus::APPROVED);
            $editRequestPending = !empty($currentApp->yeu_cau_chinh_sua);
        }

        // 3. Get Choices status using Repository
        $choices = [];
        if ($currentApp) {
            $choices = $this->nguyenVongRepo->getByHoSoId($currentApp->id);
        }

        // 4. Determine Step Statuses
        $stepStatus = [
            1 => !empty($user['ho_va_ten']) && !empty($user['anh_dai_dien']),
            2 => !empty($records),
            3 => !empty($certs) || (isset($user['co_chung_chi_qt']) && $user['co_chung_chi_qt'] == 0),
            4 => !empty($thptScores) && !empty($thptScores['nam_thi']),
            5 => !empty($choices)
        ];

        // Get Settings
        // Note: Cache::remember is handled inside Repo/Model now.
        $enableTHPT = $this->masterDataRepo->getSetting('enable_thpt_step') == '1';

        $this->view('application/index', [
            'user' => $user,
            'activeSession' => $activeSession,
            'applications' => $applications,
            'currentApp' => $currentApp,
            'stepStatus' => $stepStatus,
            'isLocked' => $isLocked,
            'editRequestPending' => $editRequestPending,
            'applicationStatus' => $applicationStatus,
            'enableTHPT' => $enableTHPT
        ]);
    }

    // Register for a specific session
    public function register(): void
    {
        // Fix: Router calls call_user_func($callback) with no args
        $sessionId = $_GET['id'] ?? null;

        if (!$sessionId) {
            $this->redirect(url('/application/index'));
            return;
        }

        // Check if already registered
        $existing = $this->applicationRepo->findByCCCDAndSession($_SESSION['cccd'], $sessionId);
        if ($existing) {
            // Validation 1: Show error instead of redirect
            $this->view('application/error', ['error' => 'Bạn đã đăng ký hồ sơ cho đợt tuyển sinh này rồi. Không thể tạo thêm hồ sơ trùng lặp.']);
            return;
        }

        // Use Repository
        $session = $this->sessionRepo->getActiveSession();
        // If specific ID requested, verify it matches or just use active. 
        // Note: session is array now.
        if ($session && $session['id'] == $sessionId) {
            // Create new application
            $newId = $this->applicationRepo->create($_SESSION['cccd'], $sessionId);
            if ($newId) {
                $this->redirect(url('/application/choices?id=' . $newId));
            } else {
                $this->view('application/error', ['error' => 'Không thể tạo hồ sơ. Vui lòng thử lại.']);
            }
        } else {
            $this->redirect(url('/application/index?error=session_closed'));
        }
    }

    // Manage choices for an application (Step 5 of Registration)
    public function step5(): void
    {
        $applicationId = $_GET['id'] ?? null;
        $activeSession = null;

        // If no ID, try to find an application for the active session or fallback to any existing application overall
        if (!$applicationId) {
            $currentlyActive = $this->sessionRepo->getActiveSession();
            $activeSession = $currentlyActive ?? $this->sessionRepo->getLatestActiveSession();
            
            if ($activeSession) {
                $existing = $this->applicationRepo->findByCCCDAndSession($_SESSION['cccd'], $activeSession['id']);
                if ($existing) {
                    $applicationId = $existing->id;
                }
            }

            // If no applicationId but we have a currently active session, create a new one FIRST
            if (!$applicationId && $currentlyActive) {
                try {
                    $applicationId = $this->applicationRepo->create($_SESSION['cccd'], $currentlyActive['id']);
                    $activeSession = $currentlyActive;
                } catch (\Exception $e) {
                    $applicationId = 0;
                }
            }

            // Fallback: If still no application (e.g. no currently active session), show their latest existing application
            if (!$applicationId) {
                $allApps = $this->applicationRepo->getByCCCD($_SESSION['cccd']);
                if (!empty($allApps)) {
                    $applicationId = $allApps[0]->id;
                    $activeSession = $this->sessionRepo->find($allApps[0]->dot_tuyen_sinh_id);
                } else {
                    // Registration deadline has passed, and no existing application exists
                    $enableTHPTSetting = true; // Default enabled
                    $this->view('profile/step5', [
                        'applicationId' => 0,
                        'choices' => [],
                        'majors' => [],
                        'error' => 'Thời hạn đăng ký đợt tuyển sinh này đã kết thúc. Bạn không thể tạo hồ sơ mới.',
                        'enableTHPTSetting' => $enableTHPTSetting,
                        'isLocked' => false
                    ]);
                    return;
                }
            }
        }

        // Allow step5 even without applicationId (for viewing/adding choices)
        if (!$applicationId && !$activeSession) {
            // No active session - show informative message
            $enableTHPTSetting = true; // Default enabled
            $this->view('profile/step5', [
                'applicationId' => 0,
                'choices' => [],
                'majors' => [],
                'error' => 'Chưa có đợt tuyển sinh đang mở. Vui lòng liên hệ phòng Tuyển sinh.',
                'enableTHPTSetting' => $enableTHPTSetting,
                'isLocked' => false
            ]);
            return;
        }

        // Ensure applicationId is valid (even if 0, we continue to show UI)
        $applicationId = $applicationId ?: 0;

        // --- LOCKING LOGIC ---
        // Check if application is "Đã duyệt"
        $currentApp = null;
        if ($applicationId > 0) {
            $allApps = $this->applicationRepo->getByCCCD($_SESSION['cccd']);
            foreach ($allApps as $app) {
                if ($app->id == $applicationId) {
                    $currentApp = $app;
                    break;
                }
            }
        }

        $isLocked = false;
        $editRequestPending = false;
        $applicationStatus = '';
        $error = null;

        if ($currentApp) {
            $status = $currentApp->trang_thai ?? '';
            if ($status === 'Đã duyệt') {
                $isLocked = true;
            }
            $editRequestPending = !empty($currentApp->yeu_cau_chinh_sua);
            $applicationStatus = $status;
            
            // Đảm bảo load activeSession nếu có hồ sơ nhưng chưa có activeSession
            if (!$activeSession && !empty($currentApp->dot_tuyen_sinh_id)) {
                $activeSession = $this->sessionRepo->find($currentApp->dot_tuyen_sinh_id);
            }
        }

        $isSessionClosed = false;
        $sessionName = '';

        // Also check if the associated session is locked or expired
        if ($activeSession) {
            $isLockedSession = !$activeSession['kich_hoat'];
            $isExpiredSession = strtotime($activeSession['ngay_ket_thuc']) < time();
            $isSessionClosed = $isLockedSession || $isExpiredSession;
            $sessionName = $activeSession['ten_dot'] ?? '';
            
            // If they have an existing application that is NOT 'Đã duyệt', we DO NOT lock!
            $hasExistingPendingApp = ($currentApp && $status !== 'Đã duyệt' && $status !== 'approved' && $status !== 'DaDuyet');
            
            if (!$hasExistingPendingApp) {
                if ($isLockedSession) {
                    $error = 'Đợt tuyển sinh này (' . $activeSession['ten_dot'] . ') đã hết thời hạn đăng ký. Bạn chỉ có thể xem hồ sơ. Cần hỗ trợ liên hệ số 0866993468';
                    $isLocked = true;
                } elseif ($isExpiredSession) {
                    $error = 'Đợt tuyển sinh này (' . $activeSession['ten_dot'] . ') đã hết thời hạn đăng ký. Bạn chỉ có thể xem hồ sơ. Cần hỗ trợ liên hệ số 0866993468';
                    $isLocked = true;
                }
            }
        }
        // ---------------------

        // Use Repository
        $choices = $this->nguyenVongRepo->getByCCCD($_SESSION['cccd']);

        // Use MasterDataRepository for Majors - Chỉ ngành đang kích hoạt theo đợt này
        $sessionId = $activeSession['id'] ?? 0;
        $majors = $this->masterDataRepo->getActiveMajorsWithCombinationsBySession($sessionId);

        // Đảm bảo các ngành đã chọn của thí sinh (ngay cả khi bị ngưng kích hoạt) vẫn xuất hiện trong danh sách để hiển thị
        if (!empty($choices)) {
            $allMajors = $this->masterDataRepo->getMajorsWithCombinations();
            $majorMap = [];
            foreach ($majors as $m) {
                $majorMap[$m['ma_nganh']] = true;
            }
            foreach ($choices as $choice) {
                $maNganhChoice = $choice['ma_nganh'] ?? '';
                if ($maNganhChoice && !isset($majorMap[$maNganhChoice])) {
                    // Tìm ngành này trong danh mục tổng và thêm vào $majors
                    foreach ($allMajors as $m) {
                        if ($m['ma_nganh'] === $maNganhChoice) {
                            $m['is_inactive_choice'] = true; // Đánh dấu ngành này đã bị ngưng tuyển
                            $majors[] = $m;
                            $majorMap[$maNganhChoice] = true;
                            break;
                        }
                    }
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Block POST if locked
            if ($isLocked) {
                $this->view('application/error', ['error' => 'Hồ sơ đã được duyệt. Bạn không thể chỉnh sửa. Vui lòng gửi yêu cầu nếu cần thay đổi.']);
                return;
            }

            // Save choices logic
            // Assume POST contains 'choices' array
            $items = $_POST['choices'] ?? [];
            
            // Map POST nganh_id to ma_nganh so that the view can retrieve old values upon validation error
            foreach ($items as &$item) {
                if (isset($item['nganh_id'])) {
                    $item['ma_nganh'] = $item['nganh_id'];
                }
            }
            unset($item);

            // Basic Validation
            if (empty($items)) {
                $error = "Vui lòng chọn ít nhất 1 nguyện vọng.";
            } elseif (count($items) > 6) {
                $error = "Bạn chỉ được đăng ký tối đa 6 nguyện vọng.";
            } else {
                // Check for duplicate majors
                $selectedMajors = array_column($items, 'nganh_id');
                $uniqueMajors = array_unique($selectedMajors);
                if (count($selectedMajors) !== count($uniqueMajors)) {
                    $error = "Bạn không được đăng ký trùng ngành. Vui lòng chọn các ngành khác nhau.";
                }
            }

            if (empty($error)) {
                // --- Pedagogy Major Constraint ---
                // Use Repository
                $candidate = $this->thiSinhRepo->findByCCCD($_SESSION['cccd']);
                $candidateProvince = $candidate['ma_tinh_ho_khau'] ?? '';

                foreach ($items as &$item) {
                    // Auto-fill required fields (even if NULL allowed, good to have placeholders or explicit NULL)
                    $item['to_hop_mon'] = null;
                    $item['ma_phuong_thuc'] = null;
                    $item['ten_phuong_thuc'] = null;

                    // Find Major Name
                    foreach ($majors as $m) {
                        if ($m['ma_nganh'] == $item['nganh_id']) { // Frontend sends ma_nganh as nganh_id
                            $item['ma_nganh'] = $m['ma_nganh'];
                            $item['ten_nganh'] = $m['ten_nganh'];
                            break;
                        }
                    }

                    if (strpos($item['ma_nganh'], '7140') === 0) {
                        // Check if candidate province is allowed
                        // Use MasterDataRepository
                        if (!$this->masterDataRepo->isPedagogyProvinceAllowed($item['ma_nganh'], $candidateProvince)) {
                            // Get Province Name for better error message
                            $allProvinces = $this->masterDataRepo->getProvinces();
                            $provinceName = $candidateProvince; // Fallback to ID
                            foreach ($allProvinces as $p) {
                                if ($p['ma_tinh'] == $candidateProvince) {
                                    $provinceName = $p['ten_tinh'];
                                    break;
                                }
                            }

                            $error = "Ngành " . $item['ma_nganh'] . " (Sư phạm) có ràng buộc chỉ dành cho thí sinh có hộ khẩu tại các tỉnh được quy định (VD: Phú Thọ). Hộ khẩu của bạn hiện tại (" . ($provinceName ?: 'Chưa cập nhật') . ") không thuộc đối tượng này.";
                            break;
                        }
                    }
                }
                unset($item); // Break the reference with the last element
                // --------------------------------------

                if (!isset($error)) {
                    // Use Repository
                    if ($this->nguyenVongRepo->save($_SESSION['cccd'], $applicationId, $items)) {
                        // Success - Send Confirmation Email
                        try {
                            if (!isset($candidate)) {
                                $candidate = $this->thiSinhRepo->findByCCCD($_SESSION['cccd']);
                            }
                            $email = $candidate['email'] ?? '';

                            if ($email) {
                                // Build choices HTML table
                                $choicesHtml = '<table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px;">';
                                $choicesHtml .= '<thead><tr style="background-color: #f2f2f2;"><th style="padding: 10px; border: 1px solid #ddd; text-align: center;">TT</th><th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Mã ngành</th><th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Tên ngành</th><th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Tổ hợp xét tuyển</th></tr></thead>';
                                $choicesHtml .= '<tbody>';

                                foreach ($items as $item) {
                                    // Find combination from majors list if not already set
                                    $toHop = '';
                                    foreach ($majors as $m) {
                                        if ($m['ma_nganh'] == $item['nganh_id']) {
                                            $toHop = $m['to_hop_xet_tuyen'] ?? '';
                                            break;
                                        }
                                    }

                                    $choicesHtml .= '<tr>';
                                    $choicesHtml .= '<td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><strong>' . htmlspecialchars($item['thu_tu']) . '</strong></td>';
                                    $choicesHtml .= '<td style="padding: 10px; border: 1px solid #ddd; text-align: center;">' . htmlspecialchars($item['nganh_id']) . '</td>';
                                    $choicesHtml .= '<td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($item['ten_nganh'] ?? '') . '</td>';
                                    $choicesHtml .= '<td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($toHop) . '</td>';
                                    $choicesHtml .= '</tr>';
                                }
                                $choicesHtml .= '</tbody></table>';

                                $emailService = new \App\Services\EmailTemplateService();
                                $emailService->queueWithTemplate($email, 'choices_confirmation', [
                                    'ho_ten' => $candidate['ho_va_ten'] ?? 'Thí sinh',
                                    'danh_sach_nguyen_vong' => $choicesHtml,
                                    'login_url' => url('/login')
                                ]);
                            }
                        } catch (\Exception $e) {
                            error_log("Failed to send step5 email: " . $e->getMessage());
                            // Don't block redirect
                        }

                        $this->redirect(url('/application/index'));
                        return;
                    } else {
                        $error = "Lỗi lưu nguyện vọng (Có thể do mã ngành không tồn tại trong CSDL hoặc lỗi hệ thống).";
                    }
                }
            }

            // Get THPT setting
            $enableTHPTSetting = true; // Default enabled

            $this->view('profile/step5', [
                'applicationId' => $applicationId,
                'choices' => $items,
                'majors' => $majors,
                'error' => $error ?? null,
                'enableTHPTSetting' => $enableTHPTSetting,
                'isLocked' => $isLocked,
                'editRequestPending' => $editRequestPending,
                'applicationStatus' => $applicationStatus,
                'isSessionClosed' => $isSessionClosed,
                'sessionName' => $sessionName
            ]);
        } else {
            // Get THPT setting
            $enableTHPTSetting = true; // Default enabled

            $this->view('profile/step5', [
                'applicationId' => $applicationId,
                'choices' => $choices,
                'majors' => $majors,
                'error' => $error ?? null,
                'enableTHPTSetting' => $enableTHPTSetting,
                'isLocked' => $isLocked,
                'editRequestPending' => $editRequestPending,
                'applicationStatus' => $applicationStatus,
                'isSessionClosed' => $isSessionClosed,
                'sessionName' => $sessionName
            ]);
        }
    }

    // Request Edit Permission
    public function requestEdit()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(url('/application/index'));
            return;
        }

        $applicationId = $_POST['id'] ?? null;
        if (!$applicationId) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Không tìm thấy mã hồ sơ.'];
            $this->redirect(url('/application/index'));
            return;
        }

        // Verify Application belongs to user
        $apps = $this->applicationRepo->getByCCCD($_SESSION['cccd']);
        $targetApp = null;
        foreach ($apps as $app) {
            if ($app->id == $applicationId) {
                $targetApp = $app;
                break;
            }
        }

        if (!$targetApp) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Hồ sơ không tồn tại hoặc không thuộc về bạn.'];
            $this->redirect(url('/application/index'));
            return;
        }

        // Only allow request if status is "Đã duyệt"
        if ($targetApp->trang_thai !== 'Đã duyệt') {
            $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Hồ sơ chưa được duyệt, bạn có thể chỉnh sửa trực tiếp.'];
            $this->redirect(url('/application/index'));
            return;
        }

        try {
            // Use ThiSinhRepository
            if ($this->thiSinhRepo->requestEditPermission($applicationId)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Đã gửi yêu cầu chỉnh sửa. Vui lòng chờ Quản trị viên xử lý.'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Có lỗi xảy ra. Vui lòng thử lại.'];
            }
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }

        $this->redirect(url('/application/index'));
    }

    // Check Admission Results
    public function results()
    {
        $cccd = $_SESSION['cccd'];

        $enableResults = $this->masterDataRepo->getSetting('enable_admission_results') == '1';

        // Ensure user has choices
        $choices = $this->nguyenVongRepo->getByCCCD($cccd);
        if (empty($choices)) {
            $this->redirect(url('/application/index'));
            return;
        }

        $results = [];

        // Only calculate/show results if enabled
        if ($enableResults) {
            $calculator = new \App\Services\ScoreCalculator();

            // Get Major info map for efficiency
            $majors = $this->masterDataRepo->getMajors(); // Returns all majors
            $majorMap = [];
            foreach ($majors as $m) {
                $majorMap[$m['ma_nganh']] = $m;
            }

            foreach ($choices as $choice) {
                $maNganh = $choice['ma_nganh'];
                $bestScore = $calculator->calculateBestScore($cccd, $maNganh);

                // Enrich with Major info
                $majorInfo = $majorMap[$maNganh] ?? [];

                // Determine Status based on 'trung_tuyen' column
                $status = '';
                $isAdmitted = isset($choice['trung_tuyen']) && $choice['trung_tuyen'] == 1;

                if ($isAdmitted) {
                    $status = 'Trúng tuyển';
                } else {
                    $status = 'Chưa trúng tuyển';
                }

                $results[] = [
                    'choice' => $choice,
                    'is_admitted' => $isAdmitted, // Boolean flag for view styling
                    'major' => $majorInfo,
                    'score_data' => $bestScore,
                    'status_hint' => $status
                ];
            }
        }

        // Get user for header
        // Use Repository
        $user = $this->thiSinhRepo->findByCCCD($cccd);

        // Lấy thông tin trúng tuyển chi tiết để render thư
        $admissionRecord = null;
        $renderedAdmissionLetter = null;

        $db = \App\Core\Database::getInstance()->getConnection();
        $admStmt = $db->prepare("SELECT * FROM ket_qua_trung_tuyen WHERE so_cccd = ? ORDER BY created_at DESC LIMIT 1");
        $admStmt->execute([$cccd]);
        $admissionRecord = $admStmt->fetch(\PDO::FETCH_ASSOC);

        if ($admissionRecord) {
            $sessionId = $admissionRecord['session_id'];
            
            // Tìm template ID cho session_id này, nếu không có thì mặc định lấy ADMISSION_LETTER
            $tplIdStmt = $db->prepare("SELECT template_id FROM session_templates WHERE session_id = ?");
            $tplIdStmt->execute([$sessionId]);
            $templateId = $tplIdStmt->fetchColumn();
            
            if ($templateId) {
                $tplStmt = $db->prepare("SELECT * FROM email_templates WHERE id = ?");
                $tplStmt->execute([$templateId]);
            } else {
                $tplStmt = $db->prepare("SELECT * FROM email_templates WHERE code = 'ADMISSION_LETTER' LIMIT 1");
                $tplStmt->execute();
            }
            
            $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);

            if ($template) {
                $letterService = new \App\Services\AdmissionResultService();
                $renderedAdmissionLetter = $letterService->renderTemplate($template['body'], $admissionRecord);
            }
        }

        $this->view('application/results', [
            'results' => $results,
            'user' => $user,
            'enableResults' => $enableResults,
            'talentResults' => $this->getTalentTestResults($cccd),
            'admissionRecord' => $admissionRecord,
            'renderedAdmissionLetter' => $renderedAdmissionLetter
        ]);
    }

    /**
     * Lấy kết quả thi năng khiếu (nếu có và đã công bố)
     */
    private function getTalentTestResults(string $cccd)
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT a.exam_number, s.subject_name, r.room_name, sc.score, sc.note,
                   sess.session_name, sess.is_published
            FROM talent_test_assignments a
            JOIN thi_sinh c ON c.id = a.candidate_id
            JOIN talent_test_subjects s ON s.id = a.subject_id
            JOIN talent_test_sessions sess ON sess.id = s.session_id
            LEFT JOIN talent_test_rooms r ON r.id = a.room_id
            LEFT JOIN talent_test_scores sc ON sc.assignment_id = a.id
            WHERE c.so_cccd = ? AND sess.is_published = TRUE
            ORDER BY sess.year DESC
        ");
        $stmt->execute([$cccd]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
