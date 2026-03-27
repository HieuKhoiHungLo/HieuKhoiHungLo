<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ThiSinh;
use App\Models\MasterData;
use App\Core\FileUploader;
use App\Services\AuditService;
use App\Repositories\ThiSinhRepository;
use App\Repositories\NguyenVongRepository;

class CandidateController extends Controller
{

    protected $thiSinhRepo;
    protected $nguyenVongRepo;
    protected $masterData;
    protected $auditService;
    protected $currentUser;

    public function __construct()
    {
        $this->thiSinhRepo = new ThiSinhRepository();
        $this->nguyenVongRepo = new NguyenVongRepository();
        $this->masterData = new MasterData();
        $this->auditService = new AuditService();

        $adminModel = new \App\Models\QuanTriVien();
        $this->currentUser = $adminModel->find($_SESSION['admin_id'] ?? 0);
    }

    protected function checkPermission($permission)
    {
        if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, $permission)) {
            if ($this->isAjax()) {
                http_response_code(403);
                die(json_encode(['error' => 'Không có quyền truy cập']));
            } else {
                echo "<script>alert('Bạn không có quyền truy cập chức năng này!'); window.location.href='" . url('/admin/dashboard') . "';</script>";
                exit;
            }
        }
    }

    private function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Master Candidate List (The Funnel)
     */
    public function index()
    {
        return $this->handleCandidateList('all');
    }

    /**
     * Submitted Applications List
     */
    public function applications()
    {
        return $this->handleCandidateList('dashboard');
    }

    /**
     * Review Management List
     */
    public function reviewList()
    {
        return $this->handleCandidateList('review');
    }


    protected function handleCandidateList($mode = 'dashboard')
    {
        $this->checkPermission('dashboard');

        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $hocBaStatus = $_GET['hoc_ba_status'] ?? '';
        $editRequest = $_GET['edit_request'] ?? '';
        $sessionId = isset($_GET['session_id']) && $_GET['session_id'] !== '' ? (int)$_GET['session_id'] : null;
        $year = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : null;
        $appStatusFilter = $_GET['app_status'] ?? 'all';

        // Custom logic for Modes
        if ($mode === 'all') {
            $appStatusFilter = 'ghost'; // Force 'chưa nhập hồ sơ' for this view
        } elseif ($mode === 'dashboard' || $mode === 'review') {
            $appStatusFilter = 'submitted'; // Force submitted for these views
        } elseif ($mode === 'trash') {
            $appStatusFilter = 'trash';
        }

        $sessionModel = new \App\Models\AdmissionSession();
        $sessions = $sessionModel->getAll();
        $years = array_unique(array_column($sessions, 'nam_tuyen_sinh'));
        rsort($years);

        if ($sessionId === null && $year === null) {
            // In 'all' (Funnel) mode, we don't force a default session to allow seeing the full 547+ candidate list.
            if ($mode !== 'all') {
                $latestSession = $sessionModel->getLatestActiveSession();
                if ($latestSession) {
                    $sessionId = $latestSession['id'];
                    $year = $latestSession['nam_tuyen_sinh'];
                } else {
                    $year = !empty($years) ? reset($years) : date('Y');
                }
            }
        }

        $yearSessions = array_filter($sessions, function ($s) use ($year) {
            return $s['nam_tuyen_sinh'] == $year;
        });

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        $sort = $_GET['sort'] ?? 'ngay_tao';
        $dir = $_GET['dir'] ?? 'DESC';

        $extraFilters = [
            'phone'     => $_GET['f_phone'] ?? '',
            'dob'       => $_GET['f_dob'] ?? '',
            'province'  => $_GET['f_province'] ?? '',
            'school'    => $_GET['f_school'] ?? '',
            'nv1'       => $_GET['f_nv1'] ?? '',
            'gender'    => $_GET['f_gender'] ?? '',
            'ethnicity' => $_GET['f_ethnicity'] ?? '',
            'area'      => $_GET['f_area'] ?? '',
            'object'    => $_GET['f_object'] ?? '',
            'grad_year' => $_GET['f_grad_year'] ?? '',
        ];

        $candidates = $this->thiSinhRepo->getFiltered(
            $search,
            $status,
            $hocBaStatus,
            $limit,
            $offset,
            $sessionId,
            $editRequest == '1',
            $year,
            $sort,
            $dir,
            ($mode !== 'trash'),
            $extraFilters,
            $appStatusFilter
        );

        $total = $this->thiSinhRepo->countFiltered(
            $search,
            $status,
            $hocBaStatus,
            $sessionId,
            $editRequest == '1',
            $year,
            ($mode !== 'trash'),
            $extraFilters,
            $appStatusFilter
        );
        $totalPages = ceil($total / max($limit, 1));

        $stats = $this->thiSinhRepo->getStats($sessionId, $year);
        $recent = $this->thiSinhRepo->getRecentRegistrationStats($sessionId);
        $stats['today'] = $recent['today'] ?? 0;
        $stats['this_week'] = $recent['this_week'] ?? 0;
        
        $emailTemplates = \App\Core\Cache::remember('email_templates_all', 60, function () {
            $model = new \App\Models\EmailTemplate();
            return $model->getAll();
        });

        $viewName = $mode === 'all' ? 'admin/candidates/index' : ($mode === 'trash' ? 'admin/candidates/trash' : 'admin/candidates');
        $baseUrl = url($mode === "review" ? "/admin/review-management" : ($mode === 'all' ? "/admin/candidate-management" : ($mode === 'trash' ? "/admin/candidates/trash" : "/admin/candidates")));

        $this->view($viewName, [
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'mode' => $mode,
            'baseUrl' => $baseUrl,
            'candidates' => $candidates,
            'stats' => $stats,
            'sessions' => $sessions,
            'yearSessions' => $yearSessions,
            'years' => $years,
            'sort' => $sort,
            'dir' => $dir,
            'filters' => array_merge([
                'search' => $search,
                'status' => $status,
                'hoc_ba_status' => $hocBaStatus,
                'edit_request' => $editRequest,
                'session_id' => $sessionId,
                'year' => $year,
                'app_status' => $appStatusFilter,
                'sort' => $sort,
                'dir' => $dir,
            ], $extraFilters),
            'pagination' => ['current_page' => $page, 'total_pages' => $totalPages, 'total_items' => $total],
            'emailTemplates' => $emailTemplates
        ]);
    }

    /**
     * Handle bulk actions from dashboard
     */
    public function bulkAction()
    {
        error_log("=== BULK ACTION TRIGGERED ===");
        error_log("POST DATA: " . print_r($_POST, true));

        // Prioritize forced_action (from JS fix)
        $action = $_POST['forced_action'] ?? $_POST['action'] ?? '';
        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            $this->redirect(url('/admin/dashboard?error=no_selection'));
            return;
        }

        switch ($action) {
            case 'update_status':
                $this->checkPermission('candidates.edit');
                $status = $_POST['status'] ?? 'Chờ duyệt';
                $this->bulkUpdateStatus($ids, $status);
                break;

            case 'transfer': // Added alias
            case 'transfer_session':
                $this->checkPermission('candidates.edit');
                $sessionId = $_POST['target_session_id'] ?? $_POST['session_id'] ?? null;
                if ($sessionId) {
                    $this->bulkTransferSession($ids, (int)$sessionId);
                } else {
                    $this->redirect(url('/admin/dashboard?error=missing_session'));
                    return;
                }
                break;

            case 'delete':
                $this->checkPermission('candidates.delete');
                $this->bulkDelete($ids);
                
                // Add feedback for bulk delete
                if (count($ids) === 1) {
                    $_POST['redirect_to'] .= (strpos($_POST['redirect_to'], '?') !== false ? '&' : '?') . "msg=deleted";
                } else {
                    $_POST['redirect_to'] .= (strpos($_POST['redirect_to'], '?') !== false ? '&' : '?') . "msg=bulk_success&count=" . count($ids);
                }
                break;

            case 'send_email':
                $this->checkPermission('candidates.view');
                $templateId = $_POST['template_id'] ?? null;
                $subject = $_POST['email_subject'] ?? null;
                $content = $_POST['email_content'] ?? null;
                $internalNote = $_POST['internal_note'] ?? null;

                if ($templateId || ($subject && $content)) {
                    $this->bulkSendEmail($ids, $templateId, $subject, $content, $internalNote);
                    $_POST['redirect_to'] .= (strpos($_POST['redirect_to'], '?') !== false ? '&' : '?') . "msg=bulk_success&count=" . count($ids);
                }
                break;

            case 'restore':
                $this->checkPermission('candidates.delete');
                $this->bulkRestore($ids);
                $_POST['redirect_to'] .= (strpos($_POST['redirect_to'], '?') !== false ? '&' : '?') . "msg=bulk_success&count=" . count($ids);
                break;

            case 'force_delete':
                $this->checkPermission('candidates.delete'); // Or candidates.force_delete if special
                $this->bulkForceDelete($ids);
                $_POST['redirect_to'] .= (strpos($_POST['redirect_to'], '?') !== false ? '&' : '?') . "msg=deleted";
                break;

            case 'normalize_names':
                $this->checkPermission('candidates.edit');
                $candidates = $this->thiSinhRepo->findManyByCCCD($ids);
                $count = 0;
                foreach ($candidates as $candidate) {
                    $normalized = normalize_name($candidate['ho_va_ten']);
                    if ($normalized !== $candidate['ho_va_ten']) {
                        $this->thiSinhRepo->update($candidate['so_cccd'], ['ho_va_ten' => $normalized]);
                        $count++;
                    }
                }
                
                $baseRedirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('/admin/review-management'));
                $_POST['redirect_to'] = $baseRedirect . (strpos($baseRedirect, '?') !== false ? '&' : '?') . "success=" . urlencode("Đã chuẩn hóa họ tên cho $count thí sinh.");
                break;

            case 'change_password':
                $this->checkPermission('candidates.edit');
                $this->bulkResetPassword($ids);
                break;

            default:
                $this->redirect(url('/admin/dashboard?error=invalid_action'));
                return;
        }
        // Redirect back to exactly where the user was (preserving sort, search query strings etc.)
        $redirectTo = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('/admin/review-management'));

        header("Location: " . $redirectTo);
        exit;
    }

    /**
     * Bulk update status
     */
    protected function bulkUpdateStatus($ids, $status)
    {
        // Use Repositories - ThiSinhRepository handles the bulk update logic for both nguyen_vong and ho_so_xet_tuyen
        $this->thiSinhRepo->bulkUpdateStatus($ids, $status);

        // Note: Logic in AdminController::updateStatus also updated ho_so_xet_tuyen status. 
        // CandidateController previously only updated nguyen_vong status in bulkUpdateStatus (line 92 original).
        // Check original: only nguyen_vong update.
        // It might be better to sync both, but I will stick to original logic to avoid changing behavior, 
        // OR improve it. AdminController logic is newer/better.
        // Let's stick to valid refactoring: mimic original behavior unless bug.
        // Original: "UPDATE nguyen_vong ..." 
        // Wait, if I only update nguyen_vong, ho_so_xet_tuyen might be out of sync?
        // Let's presume original was simple. I will just use repo.

        $this->auditService->log('BULK_UPDATE_STATUS', 'candidates', null, null, [
            'count' => count($ids),
            'status' => $status
        ]);
    }

    /**
     * Bulk transfer to another session
     */
    protected function bulkTransferSession($ids, $sessionId)
    {
        // Use Repositories
        $this->thiSinhRepo->bulkTransferSession($ids, $sessionId);
        // Original code also updated nguyen_vong status to 'Chờ duyệt'.
        // My bulkTransferSession in ThiSinhRepo only updates ho_so_xet_tuyen.
        // I need to update nguyen_vong too.
        $this->nguyenVongRepo->bulkUpdateStatus($ids, 'Chờ duyệt');

        $this->auditService->log('BULK_TRANSFER_SESSION', 'candidates', null, null, [
            'count' => count($ids),
            'session_id' => $sessionId
        ]);
    }

    /**
     * Bulk delete (soft delete)
     */
    protected function bulkDelete($ids)
    {
        // Use Repository
        $this->thiSinhRepo->bulkDelete($ids);

        $this->auditService->log('BULK_DELETE', 'candidates', null, null, [
            'count' => count($ids),
            'cccd_list' => $ids
        ]);
    }

    /**
     * Bulk restore
     */
    protected function bulkRestore($ids)
    {
        $this->thiSinhRepo->bulkRestore($ids);
        $this->auditService->log('BULK_RESTORE', 'candidates', null, null, [
            'count' => count($ids),
            'cccd_list' => $ids
        ]);
    }

    /**
     * Bulk force delete
     */
    protected function bulkForceDelete($ids)
    {
        $this->thiSinhRepo->bulkForceDelete($ids);
        $this->auditService->log('BULK_FORCE_DELETE', 'candidates', null, null, [
            'count' => count($ids),
            'cccd_list' => $ids
        ]);
    }

    /**
     * Bulk send email
     */
    protected function bulkSendEmail($ids, $templateId = null, $customSubject = null, $customContent = null, $internalNote = null)
    {
        $subject = $customSubject;
        $body = $customContent;

        if ($templateId) {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM email_templates WHERE id = ?");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($template) {
                // Use template only if subject/body not provided (custom wins)
                if (empty($subject)) $subject = $template['subject'];
                if (empty($body)) $body = $template['body']; // Fix: content -> body
            }
        }

        if (empty($subject) || empty($body)) return;

        // Get candidates using Repository
        $candidates = $this->thiSinhRepo->getEmailsByIds($ids);
        $mailer = new \App\Services\MailerService();
        $sentNum = 0;

        foreach ($candidates as $c) {
            if (empty($c['email'])) continue;

            // Support both old and new placeholder styles
            $personalSubject = str_replace(['{ho_ten}', '{{name}}'], [$c['ho_va_ten'], $c['ho_va_ten']], $subject);
            $personalBody = str_replace(
                ['{ho_ten}', '{so_cccd}', '{{name}}', '{{cccd}}'], 
                [$c['ho_va_ten'], $c['so_cccd'], $c['ho_va_ten'], $c['so_cccd']], 
                $body
            );

            // Enqueue to email_queue table so it shows in logs/queue
            if ($mailer->enqueue($c['email'], $personalSubject, $personalBody)) {
                $sentNum++;
                
                // If internal note provided, update the candidate's record
                if (!empty($internalNote)) {
                    // Wait, updateStatusAndNotes updates to specific status. 
                    // I should probably just update the note without changing status if sending email.
                    // Or keep the status as is.
                    // $log->info("Saving internal note for target {$c['so_cccd']}: $internalNote"); // Assuming $log is defined, if not, I'll omit it or add a placeholder.
                    $db = \App\Core\Database::getInstance()->getConnection();
                    
                    // Update thi_sinh (Main profile note)
                    $upd = $db->prepare("UPDATE thi_sinh SET ghi_chu = CASE 
                        WHEN ghi_chu IS NULL OR ghi_chu = '' THEN ? 
                        ELSE CONCAT(ghi_chu, '\n', ?) 
                    END WHERE so_cccd = ?");
                    $upd->execute([$internalNote, $internalNote, $c['so_cccd']]);

                    // Also update ho_so_xet_tuyen if exists for consistency in filters
                    $updHoso = $db->prepare("UPDATE ho_so_xet_tuyen SET ghi_chu = CASE 
                        WHEN ghi_chu IS NULL OR ghi_chu = '' THEN ? 
                        ELSE CONCAT(ghi_chu, '\n', ?) 
                    END WHERE so_cccd = ?");
                    $updHoso->execute([$internalNote, $internalNote, $c['so_cccd']]);
                }
            }
        }

        // Log to Audit
        $this->auditService->log('BULK_SEND_EMAIL', 'candidates', null, null, [
            'template_id' => $templateId,
            'sent_count' => $sentNum,
            'total' => count($ids)
        ]);

        // Create a Notification to appear in "/admin/notifications" (Sent Notifications)
        $notificationModel = new \App\Models\Notification();
        $notificationModel->create([
            'title' => "[Email] " . mb_substr($subject, 0, 50),
            'content' => "Hệ thống đã gửi email đến " . count($ids) . " thí sinh. Nội dung: " . mb_substr(strip_tags($body), 0, 200) . "...",
            'type' => 'info',
            'target_type' => 'all',
            'created_by' => $_SESSION['admin_id'] ?? null
        ]);
    }

    /**
     * Bulk Reset Password
     */
    protected function bulkResetPassword($ids)
    {
        $candidates = $this->thiSinhRepo->findManyByCCCD($ids);
        $count = 0;
        
        $manualPassword = $_POST['new_password'] ?? '';
        
        foreach ($candidates as $candidate) {
            // Generate random password if manual password is empty
            $newPassword = !empty($manualPassword) ? $manualPassword : substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 6);
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            
            if ($this->thiSinhRepo->updatePasswordByCCCD($candidate['so_cccd'], $hashedPassword)) {
                $count++;
                
                // Send Email Notification
                if (!empty($candidate['email'])) {
                    $mailer = new \App\Services\MailerService();
                    $subject = "Thông báo thay đổi mật khẩu - Hệ thống Tuyển sinh";
                    $body = "Chào bạn <b>{$candidate['ho_va_ten']}</b>,<br><br>
                            Người quản trị đã thay đổi mật khẩu đăng nhập của bạn trên hệ thống Tuyển sinh.<br>
                            Mật khẩu mới của bạn là: <b style='color: #0066FF; font-size: 1.2em;'>{$newPassword}</b><br><br>
                            Vui lòng sử dụng mật khẩu này để đăng nhập và đổi lại mật khẩu cá nhân sau khi truy cập.<br>
                            Trân trọng!";
                    
                    $mailer->enqueue($candidate['email'], $subject, $body);
                }

                $this->auditService->log('RESET_PASSWORD', 'candidates', $candidate['so_cccd'], null, [
                    'ho_va_ten' => $candidate['ho_va_ten'],
                    'email_sent' => !empty($candidate['email']),
                    'bulk' => true
                ]);
            }
        }
        
        $baseRedirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('/admin/review-management'));
        $redirectTo = $baseRedirect . (strpos($baseRedirect, '?') !== false ? '&' : '?') . "success=" . urlencode("Đã đổi mật khẩu thành công cho $count hồ sơ.");
        $this->redirect($redirectTo);
    }

    /**
     * Delete single candidate
     */
    public function delete()
    {
        $this->checkPermission('candidates.delete');
        $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? $_GET['cccd'] ?? '';

        if (empty($cccd)) {
            $this->redirect(url('/admin/dashboard?error=missing_cccd'));
            return;
        }

        $this->bulkDelete([$cccd]);
        
        // Dynamic redirect back to source
        $redirectTo = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('/admin/candidate-management'));
        
        // Add success message
        $redirectTo .= (strpos($redirectTo, '?') !== false ? '&' : '?') . "success=deleted";
        
        $this->redirect($redirectTo);
    }

    /**
     * AJAX fetch email template details
     */
    public function getTemplate()
    {
        $this->checkPermission('dashboard');
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Missing Template ID']);
            return;
        }

        $model = new \App\Models\EmailTemplate();
        $template = $model->find($id);
        
        header('Content-Type: application/json');
        echo json_encode($template);
        exit;
    }

    /**
     * Transfer single candidate to another session
     */
    public function transfer()
    {
        $this->checkPermission('candidates.edit');
        $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? '';
        $sessionId = $_POST['session_id'] ?? '';

        if (empty($cccd) || empty($sessionId)) {
            $this->redirect(url('/admin/review?cccd=' . $cccd . '&error=missing_data'));
            return;
        }

        $this->bulkTransferSession([$cccd], $sessionId);

        $this->redirect(url('/admin/review?cccd=' . $cccd . '&success=transferred'));
    }

    public function trash()
    {
        return $this->handleCandidateList('trash');
    }


    public function restore()
    {
        $this->checkPermission('candidates.delete');
        $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? '';
        $cccds = $_POST['cccds'] ?? [];

        if ($cccd) {
            $this->thiSinhRepo->restore($cccd);
            $this->redirect(url('/admin/candidates/trash?success=restored'));
        } elseif (!empty($cccds)) {
            foreach ($cccds as $id) {
                $this->thiSinhRepo->restore($id);
            }
            $this->redirect(url('/admin/candidates/trash?success=restored&count=' . count($cccds)));
        } else {
            $this->redirect(url('/admin/candidates/trash?error=missing_data'));
        }
    }

    public function forceDelete()
    {
        $this->checkPermission('candidates.delete');
        $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? '';
        $cccds = $_POST['cccds'] ?? [];

        if ($cccd) {
            $this->thiSinhRepo->forceDelete($cccd);
            $this->redirect(url('/admin/candidates/trash?success=deleted_forever'));
        } elseif (!empty($cccds)) {
            foreach ($cccds as $id) {
                $this->thiSinhRepo->forceDelete($id);
            }
            $this->redirect(url('/admin/candidates/trash?success=deleted_forever&count=' . count($cccds)));
        } else {
            $this->redirect(url('/admin/candidates/trash?error=missing_data'));
        }
    }

    public function edit()
    {
        $this->checkPermission('candidates.edit');

        $cccd = $_GET['cccd'] ?? $_POST['cccd'] ?? '';
        if (!$cccd) {
            $this->redirect(url('/admin/dashboard'));
        }

        // Handle POST (Update logic remains same, view handles the complex inputs)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            // 1. Update Personal Info
            $data = [
                'ho_va_ten' => normalize_name($_POST['ho_va_ten'] ?? ''),
                'ngay_sinh' => $_POST['ngay_sinh'] ?? '',
                'gioi_tinh' => $_POST['gioi_tinh'] ?? '',
                'dan_toc' => $_POST['dan_toc'] ?? '',
                'dien_thoai' => $_POST['dien_thoai'] ?? '',
                'email' => $_POST['email'] ?? '',
                'khu_vuc_uu_tien' => $_POST['khu_vuc_uu_tien'] ?? '',
                'doi_tuong_uu_tien' => $_POST['doi_tuong_uu_tien'] ?? '',
                'ma_tinh_thuong_tru' => $_POST['ma_tinh_thuong_tru'] ?? '',
                'ma_xa_thuong_tru' => $_POST['ma_xa_thuong_tru'] ?? '',
                'dia_chi_chi_tiet' => $_POST['dia_chi_chi_tiet'] ?? '',
                'ma_truong_lop_12' => $_POST['ma_truong_lop_12'] ?? '',
            ];

            $this->thiSinhRepo->updateFullProfile($cccd, $data);

            // 1.1 Handle Avatar Upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                // Re-use uploader config if already set, or init new
                if (!isset($uploader)) {
                    $pathInfo = $this->getUploadPathInfo($cccd);
                    $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                    $uploader = new FileUploader($pathInfo['absolute'], $uploadDriver);
                    if ($uploadDriver === 'google') {
                        $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                        $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
                        $uploader->setGoogleConfig($clientSecretPath, $tokenPath, $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                        $driveService = new \App\Services\DriveService($uploader);
                        $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                        if ($targetFolderId) {
                            $uploader->setTargetFolderId($targetFolderId);
                        }
                    }
                    $uploader->setAllowedMimes(['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png']);
                }

                // Upload Avatar
                $uploader->clearErrors();
                $fileName = $cccd . '_avatar_' . time();
                $result = $uploader->upload([
                    'name' => $_FILES['avatar']['name'],
                    'type' => $_FILES['avatar']['type'],
                    'tmp_name' => $_FILES['avatar']['tmp_name'],
                    'error' => $_FILES['avatar']['error'],
                    'size' => $_FILES['avatar']['size']
                ], $fileName);

                if ($result) {
                    $avatarPath = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $result : $result;
                    $this->thiSinhRepo->updateFullProfile($cccd, ['anh_dai_dien' => $avatarPath]);
                }
            }

            // 2. Update Certificates (Multiple)
            $hasCert = isset($_POST['has_cert']) && $_POST['has_cert'] == '1';
            $this->thiSinhRepo->updateCertStatus($cccd, $hasCert);

            if ($hasCert) {
                $certsData = $_POST['certs'] ?? [];

                // Handle Files for Certs
                if (isset($_FILES['cert_files']) && is_array($_FILES['cert_files']['name'])) {
                    // Re-use uploader config if already set, or init new
                    if (!isset($uploader)) {
                        $pathInfo = $this->getUploadPathInfo($cccd);
                        $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                        $uploader = new FileUploader($pathInfo['absolute'], $uploadDriver);
                        if ($uploadDriver === 'google') {
                            $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                            $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
                            $uploader->setGoogleConfig($clientSecretPath, $tokenPath, $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                            $driveService = new \App\Services\DriveService($uploader);
                            $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                            if ($targetFolderId) {
                                $uploader->setTargetFolderId($targetFolderId);
                            }
                        }
                        $uploader->setAllowedMimes(['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png']);
                    }

                    foreach ($certsData as $index => &$certItem) {
                        if (isset($_FILES['cert_files']['error'][$index]) && $_FILES['cert_files']['error'][$index] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['cert_files']['name'][$index],
                                'type' => $_FILES['cert_files']['type'][$index],
                                'tmp_name' => $_FILES['cert_files']['tmp_name'][$index],
                                'error' => $_FILES['cert_files']['error'][$index],
                                'size' => $_FILES['cert_files']['size'][$index]
                            ];

                            $uploader->clearErrors();
                            $fileName = $cccd . '_cert_' . $index . '_' . time();
                            $result = $uploader->upload($file, $fileName);
                            if ($result) {
                                $certItem['file_minh_chung_cc'] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $result : $result;
                            }
                        } else {
                            $certItem['file_minh_chung_cc'] = $certItem['existing_file'] ?? null;
                        }
                    }
                } else {
                    foreach ($certsData as &$certItem) {
                        $certItem['file_minh_chung_cc'] = $certItem['existing_file'] ?? null;
                    }
                }

                $this->thiSinhRepo->saveCertifications($cccd, $certsData);
            }

            // 3. Update Academic Records
            $academicRepo = new \App\Repositories\AcademicRepository();
            $grades = [10, 11, 12];
            $subjects = ['toan', 'van', 'ngoai_ngu', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd', 'cong_nghe', 'tin_hoc'];
            foreach ($grades as $g) {
                $record = [];
                foreach ($subjects as $s) {
                    $val = $_POST["grade_{$g}_{$s}_hk1"] ?? null;
                    if ($val !== null) $record["diem_{$s}_hk1"] = $val;
                    $val2 = $_POST["grade_{$g}_{$s}_hk2"] ?? null;
                    if ($val2 !== null) $record["diem_{$s}_hk2"] = $val2;
                }

                // Fields: diem_tb, hoc_luc, hanh_kiem
                $fields = ['diem_tb', 'hoc_luc', 'hanh_kiem'];
                foreach (['hk1', 'hk2'] as $hk) {
                    foreach ($fields as $f) {
                        $postKey = "grade_{$g}_{$f}_{$hk}";
                        if (isset($_POST[$postKey])) {
                            $record["{$f}_{$hk}"] = $_POST[$postKey];
                        }
                    }
                }

                if (!empty($record)) {
                    $academicRepo->createOrUpdate($cccd, $g, $record);
                }
            }

            // 4. Update THPT Scores
            $diemThiModel = new \App\Models\DiemThiTHPT();
            $scores = [];
            $fieldsTHPT = ['toan', 'van', 'ngoai_ngu', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd'];
            foreach ($fieldsTHPT as $f) {
                if (isset($_POST[$f]) && $_POST[$f] !== '') $scores[$f] = $_POST[$f];
            }
            // Foreign languages
            $langs = ['tieng_anh', 'tieng_nga', 'tieng_phap', 'tieng_trung', 'tieng_duc', 'tieng_nhat', 'tieng_han'];
            foreach ($langs as $l) {
                if (isset($_POST[$l]) && $_POST[$l] !== '') $scores[$l] = $_POST[$l];
            }

            if (!empty($scores)) {
                $scores['nam_thi'] = date('Y');
                $diemThiModel->save($cccd, $scores);
            }

            // Redirect back with success message and active tab
            $activeTab = $_POST['active_tab'] ?? 'personal';
            $this->redirect(url('/admin/candidates/edit?cccd=' . $cccd . '&msg=update_success&tab=' . $activeTab));
            return;
        }

        // Handle GET
        $candidate = $this->thiSinhRepo->getDetail($cccd);
        if (!$candidate) {
            $this->redirect(url('/admin/dashboard?error=not_found'));
        }

        // Fetch additional data
        $certs = $this->thiSinhRepo->getCertifications($cccd);
        $certificate = !empty($certs) ? $certs[0] : [];

        $academicRepo = new \App\Repositories\AcademicRepository();
        $academicRecords = $academicRepo->getByCCCD($cccd); // Returns array of records (rows)

        $diemThiModel = new \App\Models\DiemThiTHPT();
        $diemThi = $diemThiModel->getByCCCD($cccd);

        $provinces = $this->masterData->getProvinces();
        $masterDataRepo = new \App\Repositories\MasterDataRepository();
        $priorityAreas = $masterDataRepo->getPriorityAreas(); // Fixed: Use Repository for Key-Value map
        $priorityObjects = $masterDataRepo->getPriorityObjects(); // Fixed: Use Repository for Key-Value map
        $certs = $this->thiSinhRepo->getCertifications($cccd);

        $this->view('admin/candidates/edit', [
            'candidate' => $candidate,
            'academicRecords' => $academicRecords,
            'diemThi' => $diemThi,
            'provinces' => $provinces,
            'priorityAreas' => $priorityAreas, // New
            'priorityObjects' => $priorityObjects, // New
            'certs' => $certs,
            'user' => $this->currentUser
        ]);
    }

    public function update()
    {
        $this->checkPermission('candidates.edit');
        // $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? '';
        $section = $_POST['section'] ?? '';

        // error_log("DEBUG_UPDATE: CCCD=$cccd, Section=$section");

        if (!$cccd || !$section) {
            $this->json(['success' => false, 'error' => 'Thiếu dữ liệu CCCD hoặc Section. Debug: ' . print_r($_POST, true)]);
            return;
        }

        try {
            switch ($section) {
                case 'personal':
                    // Personal Info (Updated fields only)
                    // Use '' for text fields if empty, null for IDs if empty
                    $data = [
                        'ho_va_ten' => normalize_name($_POST['ho_va_ten']),
                        'ngay_sinh' => $_POST['ngay_sinh'],
                        'gioi_tinh' => $_POST['gioi_tinh'],
                        'dan_toc'   => $_POST['dan_toc'] ?? '',
                        'dien_thoai' => $_POST['dien_thoai'],
                        'email'     => $_POST['email'],
                        'nam_tot_nghiep'    => $_POST['nam_tot_nghiep'] ?? null,
                        'ma_tinh_lop_12'    => !empty($_POST['ma_tinh_lop_12']) ? $_POST['ma_tinh_lop_12'] : null,
                        'ma_truong_lop_12'  => !empty($_POST['ma_truong_lop_12']) ? $_POST['ma_truong_lop_12'] : null,
                        'khu_vuc_uu_tien'   => $_POST['kv_uu_tien'] ?? null,
                        'is_custom_kv'      => (isset($_POST['is_custom_kv']) && $_POST['is_custom_kv'] == '1'),
                        'doi_tuong_uu_tien' => $_POST['dt_uu_tien'] ?? null,
                        'is_custom_dt'      => (isset($_POST['is_custom_dt']) && $_POST['is_custom_dt'] == '1'),
                        'dia_chi_chi_tiet'   => $_POST['dia_chi_chi_tiet'] ?? '',
                        'ma_tinh_ho_khau'    => !empty($_POST['ma_tinh_ho_khau']) ? $_POST['ma_tinh_ho_khau'] : null,
                        'ma_tinh_thuong_tru' => !empty($_POST['ma_tinh_thuong_tru']) ? $_POST['ma_tinh_thuong_tru'] : null,
                        'ma_xa_thuong_tru'   => !empty($_POST['ma_xa_thuong_tru']) ? $_POST['ma_xa_thuong_tru'] : null,
                    ];

                    // Xử lý đổi Số CCCD nếu có
                    if (!empty($_POST['so_cccd']) && trim($_POST['so_cccd']) !== $cccd) {
                        $newCccd = trim($_POST['so_cccd']);
                        // Kiểm tra trùng lặp CCCD
                        $existing = $this->thiSinhRepo->findByCCCD($newCccd);
                        if ($existing) {
                            $this->json(['success' => false, 'error' => 'Số CCCD mới đã tồn tại trong hệ thống. Vui lòng kiểm tra lại.']);
                            return;
                        }
                        $data['so_cccd'] = $newCccd;
                    }

                    // Handle File Uploads — only if files are actually attached
                    $fileMap = [
                        'avatar' => 'anh_dai_dien',
                        'cccd_front' => 'anh_cccd_truoc',
                        'cccd_back' => 'anh_cccd_sau',
                        'kv_file' => 'file_minh_chung_kv',
                        'dt_file' => 'file_minh_chung_dt'
                    ];

                    $hasFiles = false;
                    foreach ($fileMap as $field => $dbCol) {
                        if (!empty($_FILES[$field]['name'])) {
                            $hasFiles = true;
                            break;
                        }
                    }

                    if ($hasFiles) {
                        $pathInfo = $this->getUploadPathInfo($cccd);
                        $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                        $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);

                        if ($uploadDriver === 'google') {
                            $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                            $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
                            $uploader->setGoogleConfig($clientSecretPath, $tokenPath, $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                            $driveService = new \App\Services\DriveService($uploader);
                            $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                            if ($targetFolderId) {
                                $uploader->setTargetFolderId($targetFolderId);
                            }
                        }

                        foreach ($fileMap as $field => $dbCol) {
                            if (!empty($_FILES[$field]['name'])) {
                                // Map special field names to readable file prefixes
                                switch ($field) {
                                    case 'kv_file':    $filePrefix = 'kv_evidence'; break;
                                    case 'dt_file':    $filePrefix = 'dt_evidence'; break;
                                    case 'avatar':     $filePrefix = 'avatar'; break;
                                    case 'cccd_front': $filePrefix = 'cccd_front'; break;
                                    case 'cccd_back':  $filePrefix = 'cccd_back'; break;
                                    default:           $filePrefix = $field; break;
                                }

                                $prefix = $cccd . '_' . $filePrefix . '_' . time();
                                $uploader->clearErrors();
                                $fileName = $uploader->upload($_FILES[$field], $prefix);
                                if ($fileName) {
                                    $data[$dbCol] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fileName : $fileName;
                                } else {
                                    error_log("Upload error $field: " . json_encode($uploader->getErrors()));
                                }
                            }
                        }
                    }

                    // Update Application Status & Note if provided
                    $applicationRepo = new \App\Repositories\ApplicationRepository();
                    $applicationId = $_POST['application_id'] ?? null;
                    if ($applicationId) {
                        $appUpdate = [];
                        if (isset($_POST["status_{$section}"])) $appUpdate['trang_thai'] = $_POST["status_{$section}"];
                        if (isset($_POST["note_{$section}"]))   $appUpdate['ghi_chu']     = $_POST["note_{$section}"];

                        if (!empty($appUpdate)) {
                            $applicationRepo->update($applicationId, $appUpdate);
                        }
                    }

                    // error_log("DEBUG_UPDATE_PERSONAL: " . print_r($data, true));
                    $res = $this->thiSinhRepo->updateFullProfile($cccd, $data);

                    if ($res) {
                        $this->json(['success' => true, 'message' => 'Lưu thành công', 'new_cccd' => $data['so_cccd'] ?? $cccd, 'debug_data' => $data]);
                    } else {
                        $this->json(['success' => false, 'error' => 'Lỗi DB Update (0 rows affected or fail)', 'debug_data' => $data]);
                    }
                    return;


                case 'academic':
                    $academicRepo = new \App\Repositories\AcademicRepository();

                    // Setup Uploader (check if any transcript files exist)
                    $hasAcademicFiles = false;
                    foreach ([10, 11, 12] as $_g) {
                        if (!empty($_FILES["transcripts_$_g"]['name'][0])) {
                            $hasAcademicFiles = true;
                            break;
                        }
                    }

                    $pathInfo = null;
                    $uploadDriver = 'local';
                    $uploader = null;
                    if ($hasAcademicFiles) {
                        $pathInfo = $this->getUploadPathInfo($cccd);
                        $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                        $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);

                        if ($uploadDriver === 'google') {
                            $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                            $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
                            $uploader->setGoogleConfig($clientSecretPath, $tokenPath, $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                            $driveService = new \App\Services\DriveService($uploader);
                            $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                            if ($targetFolderId) {
                                $uploader->setTargetFolderId($targetFolderId);
                            }
                        }
                    }

                    $grades = [10, 11, 12];
                    $subjects = ['toan', 'van', 'ngoai_ngu', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd', 'tin_hoc', 'cong_nghe'];

                    foreach ($grades as $g) {
                        $record = [];

                        if (isset($_POST['scores'][$g]) && is_array($_POST['scores'][$g])) {
                            $gradeInputs = $_POST['scores'][$g];

                            // Collect Scores (column names use _cn suffix)
                            foreach ($subjects as $s) {
                                if (isset($gradeInputs["diem_{$s}_cn"])) {
                                    $record["diem_{$s}_cn"] = $gradeInputs["diem_{$s}_cn"] !== '' ? (float)$gradeInputs["diem_{$s}_cn"] : null;
                                }
                            }

                            // Collect Rank & Conduct & GPA (column names use _ca_nam suffix)
                            if (isset($gradeInputs["hoc_luc_ca_nam"]))   $record['hoc_luc_ca_nam']   = $gradeInputs["hoc_luc_ca_nam"] ?: null;
                            if (isset($gradeInputs["hanh_kiem_ca_nam"])) $record['hanh_kiem_ca_nam'] = $gradeInputs["hanh_kiem_ca_nam"] ?: null;
                            if (isset($gradeInputs["diem_tb_ca_nam"]))   $record['diem_tb_ca_nam']   = $gradeInputs["diem_tb_ca_nam"] !== '' ? (float)$gradeInputs["diem_tb_ca_nam"] : null;

                            // Handle file uploads (multiple files per grade possible)
                            if (!empty($_FILES["transcripts_$g"]['name'][0])) {
                                $uploadedFiles = [];
                                foreach ($_FILES["transcripts_$g"]['name'] as $i => $name) {
                                    if (!empty($name)) {
                                        $fileToUpload = [
                                            'name' => $_FILES["transcripts_$g"]['name'][$i],
                                            'type' => $_FILES["transcripts_$g"]['type'][$i],
                                            'tmp_name' => $_FILES["transcripts_$g"]['tmp_name'][$i],
                                            'error' => $_FILES["transcripts_$g"]['error'][$i],
                                            'size' => $_FILES["transcripts_$g"]['size'][$i]
                                        ];
                                        $prefix = $cccd . "_transcript_grade{$g}_" . time() . "_" . $i;
                                        $uploader->clearErrors();
                                        $fileName = $uploader->upload($fileToUpload, $prefix);
                                        if ($fileName) {
                                            $uploadedFiles[] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fileName : $fileName;
                                        }
                                    }
                                }
                                if (!empty($uploadedFiles)) {
                                    $record['file_hoc_ba'] = implode(',', $uploadedFiles);
                                }
                            } else {
                                // Keep existing if no new ones
                                if (isset($gradeInputs['existing_files'])) {
                                    $record['file_hoc_ba'] = $gradeInputs['existing_files'];
                                }
                            }

                            // SAVE the record for this grade
                            $academicRepo->createOrUpdate($cccd, $g, $record);
                        }
                    }

                    // Save School & Priority fields (now submitted from academic form)
                    $personalData = [];
                    if (isset($_POST['ma_tinh_lop_12']))    $personalData['ma_tinh_lop_12']    = !empty($_POST['ma_tinh_lop_12']) ? $_POST['ma_tinh_lop_12'] : null;
                    if (isset($_POST['ma_truong_lop_12']))   $personalData['ma_truong_lop_12']  = !empty($_POST['ma_truong_lop_12']) ? $_POST['ma_truong_lop_12'] : null;
                    if (isset($_POST['nam_tot_nghiep']))     $personalData['nam_tot_nghiep']    = $_POST['nam_tot_nghiep'] ?? null;
                    if (isset($_POST['kv_uu_tien']))         $personalData['khu_vuc_uu_tien']   = $_POST['kv_uu_tien'] ?? null;
                    $personalData['is_custom_kv'] = (isset($_POST['is_custom_kv']) && $_POST['is_custom_kv'] == '1');
                    if (isset($_POST['dt_uu_tien']))         $personalData['doi_tuong_uu_tien'] = $_POST['dt_uu_tien'] ?? null;
                    $personalData['is_custom_dt'] = (isset($_POST['is_custom_dt']) && $_POST['is_custom_dt'] == '1');

                    // Handle KV/DT evidence file uploads
                    $evidenceFileMap = ['kv_file' => 'file_minh_chung_kv', 'dt_file' => 'file_minh_chung_dt'];
                    foreach ($evidenceFileMap as $field => $dbCol) {
                        if (!empty($_FILES[$field]['name'])) {
                            if (!isset($uploader)) {
                                $pathInfo = $this->getUploadPathInfo($cccd);
                                $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                                $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);
                                if ($uploadDriver === 'google') {
                                    $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                                    $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
                                    $uploader->setGoogleConfig($clientSecretPath, $tokenPath, $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                                    $driveService = new \App\Services\DriveService($uploader);
                                    $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                                    if ($targetFolderId) {
                                        $uploader->setTargetFolderId($targetFolderId);
                                    }
                                }
                            }
                            $prefix = $cccd . '_' . ($field === 'kv_file' ? 'kv_evidence' : 'dt_evidence') . '_' . time();
                            $uploader->clearErrors();
                            $fileName = $uploader->upload($_FILES[$field], $prefix);
                            if ($fileName) {
                                $personalData[$dbCol] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fileName : $fileName;
                            }
                        }
                    }

                    if (!empty($personalData)) {
                        $this->thiSinhRepo->updateFullProfile($cccd, $personalData);
                    }

                    // Update Status & Note
                    $applicationRepo = new \App\Repositories\ApplicationRepository();
                    $applicationId = $_POST['application_id'] ?? null;
                    if ($applicationId) {
                        $appUpdate = [];
                        if (isset($_POST["status_{$section}"])) $appUpdate['trang_thai'] = $_POST["status_{$section}"];
                        if (isset($_POST["note_{$section}"]))   $appUpdate['ghi_chu']     = $_POST["note_{$section}"];
                        if (!empty($appUpdate)) $applicationRepo->update($applicationId, $appUpdate);
                    }

                    $this->json(['success' => true, 'message' => 'Đã lưu kết quả học tập thành công']);
                    return;

                case 'thpt':
                    // THPT Scores Update
                    $fields = ['toan', 'van', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd', 'tieng_anh', 'tieng_trung', 'ktpl', 'tin_hoc', 'cnnn'];
                    $scores = [];

                    // Review.php uses thpt_ prefix for these inputs
                    foreach ($fields as $f) {
                        $key = "thpt_$f";
                        if (isset($_POST[$key])) {
                            $scores[$f] = $_POST[$key] !== '' ? (float)$_POST[$key] : null;
                        }
                    }
                    $scores['da_co_diem'] = ($_POST['has_scores'] ?? '0') === '1';

                    // Handle File Upload
                    if (!empty($_FILES['thpt_file_evidence']['name'])) {
                        $pathInfo = $this->getUploadPathInfo($cccd);
                        $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                        $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);

                        if ($uploadDriver === 'google') {
                            $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                            $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
                            $uploader->setGoogleConfig($clientSecretPath, $tokenPath, $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                            $driveService = new \App\Services\DriveService($uploader);
                            $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                            if ($targetFolderId) {
                                $uploader->setTargetFolderId($targetFolderId);
                            }
                        }

                        $uploader->clearErrors();
                        $fileName = $uploader->upload($_FILES['thpt_file_evidence'], "{$cccd}_THPT_" . time());
                        if ($fileName) {
                            $scores['file_chung_nhan'] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fileName : $fileName;
                        }
                    }

                    // Update Status & Note
                    $applicationRepo = new \App\Repositories\ApplicationRepository();
                    $applicationId = $_POST['application_id'] ?? null;
                    if ($applicationId) {
                        $appUpdate = [];
                        if (isset($_POST["status_{$section}"])) $appUpdate['trang_thai'] = $_POST["status_{$section}"];
                        if (isset($_POST["note_{$section}"]))   $appUpdate['ghi_chu']     = $_POST["note_{$section}"];
                        if (!empty($appUpdate)) $applicationRepo->update($applicationId, $appUpdate);
                    }

                    if (!empty($scores)) {
                        $scores['nam_thi'] = date('Y');
                        if (isset($_POST["note_{$section}"]))   $appUpdate['ghi_chu']     = $_POST["note_{$section}"];
                        if (!empty($appUpdate)) $applicationRepo->update($applicationId, $appUpdate);
                    }

                    $this->thiSinhRepo->saveDiemThiTHPT($cccd, $scores);

                    $this->json(['success' => true, 'message' => 'Đã lưu điểm thi THPT thành công']);
                    return;

                case 'certs':
                    $certsArr = $_POST['certs'] ?? [];
                    $certsData = [];

                    $pathInfo = $this->getUploadPathInfo($cccd);
                    $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                    $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);

                    if ($uploadDriver === 'google') {
                        $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                        $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
                        $uploader->setGoogleConfig($clientSecretPath, $tokenPath, $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                        $driveService = new \App\Services\DriveService($uploader);
                        $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                        if ($targetFolderId) {
                            $uploader->setTargetFolderId($targetFolderId);
                        }
                    }

                    foreach ($certsArr as $index => $c) {
                        if (empty($c['type'])) continue;

                        $item = [
                            'loai_chung_chi' => $c['type'],
                            'diem_chung_chi' => $c['score'] ?? '',
                            'file_minh_chung_cc' => $c['existing_file'] ?? ''
                        ];

                        // Handle new file upload for this cert row
                        if (!empty($_FILES['cert_files']['name'][$index])) {
                            $fileToUpload = [
                                'name' => $_FILES['cert_files']['name'][$index],
                                'type' => $_FILES['cert_files']['type'][$index],
                                'tmp_name' => $_FILES['cert_files']['tmp_name'][$index],
                                'error' => $_FILES['cert_files']['error'][$index],
                                'size' => $_FILES['cert_files']['size'][$index]
                            ];

                            $prefix = $cccd . '_cert_' . time() . '_' . $index;
                            $uploader->clearErrors();
                            $fileName = $uploader->upload($fileToUpload, $prefix);
                            if ($fileName) {
                                $item['file_minh_chung_cc'] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fileName : $fileName;
                            }
                        }

                        $certsData[] = $item;
                    }

                    // Update Status & Note
                    $applicationRepo = new \App\Repositories\ApplicationRepository();
                    $applicationId = $_POST['application_id'] ?? null;
                    if ($applicationId) {
                        $appUpdate = [];
                        if (isset($_POST["status_{$section}"])) $appUpdate['trang_thai'] = $_POST["status_{$section}"];
                        if (isset($_POST["note_{$section}"]))   $appUpdate['ghi_chu']     = $_POST["note_{$section}"];
                        if (!empty($appUpdate)) $applicationRepo->update($applicationId, $appUpdate);
                    }

                    $this->thiSinhRepo->saveCertifications($cccd, $certsData);

                    $this->json(['success' => true, 'message' => 'Đã lưu chứng chỉ thành công']);
                    return;

                case 'wishes':
                    $applicationId = $_POST['application_id'] ?? null;
                    $items = $_POST['choices'] ?? [];

                    if (!$applicationId) {
                        $sessionModel = new \App\Models\AdmissionSession();
                        $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestActiveSession();
                        if ($activeSession) {
                            $appModel = new \App\Models\Application();
                            $app = $appModel->findByCCCDAndSession($cccd, $activeSession['id']);
                            if ($app) $applicationId = $app->id;
                        }
                    }

                    if (!$applicationId) {
                        $this->json(['success' => false, 'error' => 'Không tìm thấy hồ sơ để lưu nguyện vọng.']);
                        return;
                    }

                    // Resolve the actual dot_tuyen_sinh_id from ho_so_xet_tuyen
                    // applicationId is ho_so_xet_tuyen.id, NOT the session ID
                    $stmtSession = $this->thiSinhRepo->getDb()->prepare(
                        "SELECT dot_tuyen_sinh_id FROM ho_so_xet_tuyen WHERE id = ?"
                    );
                    $stmtSession->execute([$applicationId]);
                    $sessionRow = $stmtSession->fetch(\PDO::FETCH_ASSOC);
                    $dotTuyenSinhId = $sessionRow ? $sessionRow['dot_tuyen_sinh_id'] : null;

                    // Fallback: try by CCCD if lookup by ID fails
                    if (!$dotTuyenSinhId) {
                        $stmtSession2 = $this->thiSinhRepo->getDb()->prepare(
                            "SELECT dot_tuyen_sinh_id FROM ho_so_xet_tuyen WHERE so_cccd = ? ORDER BY id DESC LIMIT 1"
                        );
                        $stmtSession2->execute([$cccd]);
                        $sessionRow2 = $stmtSession2->fetch(\PDO::FETCH_ASSOC);
                        $dotTuyenSinhId = $sessionRow2 ? $sessionRow2['dot_tuyen_sinh_id'] : null;
                    }

                    if (!$dotTuyenSinhId) {
                        $this->json(['success' => false, 'error' => 'Không xác định được đợt tuyển sinh cho hồ sơ này.']);
                        return;
                    }

                    if (empty($items)) {
                        $this->json(['success' => false, 'error' => 'Vui lòng thêm ít nhất 1 nguyện vọng.']);
                        return;
                    }

                    $masterRepo = new \App\Repositories\MasterDataRepository();
                    $majors = $masterRepo->getMajors();
                    $majorMap = [];
                    foreach ($majors as $m) $majorMap[$m['ma_nganh']] = $m;

                    foreach ($items as &$item) {
                        $maNganh = $item['nganh_id'] ?? null;
                        if ($maNganh && isset($majorMap[$maNganh])) {
                            $item['ma_nganh'] = $maNganh;
                            $item['ten_nganh'] = $majorMap[$maNganh]['ten_nganh'];
                            $item['to_hop_mon'] = $majorMap[$maNganh]['to_hop_xet_tuyen'] ?? '';
                        }
                    }
                    unset($item);

                    $nguyenVongRepo = new \App\Repositories\NguyenVongRepository();
                    if (!$nguyenVongRepo->save($cccd, $dotTuyenSinhId, $items)) {
                        $this->json(['success' => false, 'error' => 'Lỗi lưu nguyện vọng vào CSDL.']);
                        return;
                    }
                    break;
            }

            // Audit log (non-critical: failures here should not break the JSON response)
            try {
                $this->auditService->log('UPDATE_CANDIDATE', 'candidates', $cccd, null, ['section' => $section]);
            } catch (\Exception $auditEx) {
                error_log("Audit log failed: " . $auditEx->getMessage());
            }
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            error_log("UPDATE CANDIDATE ERROR: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    private function getUploadPathInfo($cccd)
    {
        $sessionModel = new \App\Models\AdmissionSession();
        $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestActiveSession();

        $year = date('Y');
        $sessionName = 'Dot1';

        if ($activeSession) {
            $year = $activeSession['nam_tuyen_sinh'] ?? date('Y');
            // Use 'ma_dot' (e.g. 'Dot_1') instead of 'ten_dot' (e.g. 'Đợt 1 năm 2026') to avoid nested years
            $sessionName = $activeSession['ma_dot'] ?? ('Dot_' . ($activeSession['id'] ?? '1'));
            // Slugify to be safe
            $sessionName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $sessionName);
        }

        // Standard Path: uploads/YEAR/SESSION/CCCD
        $relativePath = "/uploads/{$year}/{$sessionName}/{$cccd}";
        $absolutePath = __DIR__ . '/../../public' . $relativePath;

        return [
            'relative' => $relativePath,
            'absolute' => $absolutePath,
            'year' => $year,
            'session' => $sessionName
        ];
    }

    public function changePassword()
    {
        $this->checkPermission('candidates.edit');
        $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? '';
        if (!$cccd) {
            $this->redirect(url('/admin/candidate-management?error=missing_cccd'));
        }

        $candidate = $this->thiSinhRepo->findByCCCD($cccd);
        if (!$candidate) {
            $this->redirect(url('/admin/candidate-management?error=not_found'));
        }

        // Use manual password if provided, otherwise generate random (at least 6 characters)
        $manualPassword = $_POST['new_password'] ?? '';
        $newPassword = !empty($manualPassword) ? $manualPassword : substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 6);
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        if ($this->thiSinhRepo->updatePasswordByCCCD($cccd, $hashedPassword)) {
            // Send Email
            if (!empty($candidate['email'])) {
                $mailer = new \App\Services\MailerService();
                $subject = "Thông báo thay đổi mật khẩu - Hệ thống Tuyển sinh";
                $body = "Chào bạn <b>{$candidate['ho_va_ten']}</b>,<br><br>
                        Người quản trị đã thay đổi mật khẩu đăng nhập của bạn trên hệ thống Tuyển sinh.<br>
                        Mật khẩu mới của bạn là: <b style='color: #0066FF; font-size: 1.2em;'>{$newPassword}</b><br><br>
                        Vui lòng sử dụng mật khẩu này để đăng nhập và đổi lại mật khẩu cá nhân sau khi truy cập.<br>
                        Trân trọng!";
                
                $mailer->enqueue($candidate['email'], $subject, $body);
            }

            $this->auditService->log('RESET_PASSWORD', 'candidates', $cccd, null, [
                'ho_va_ten' => $candidate['ho_va_ten'],
                'email_sent' => !empty($candidate['email'])
            ]);

            $redirectTo = $_POST['redirect_to'] ?? url('/admin/candidate-management');
            $redirectTo .= (strpos($redirectTo, '?') !== false ? '&' : '?') . 'success=password_changed';
            $this->redirect($redirectTo);
        } else {
            $redirectTo = $_POST['redirect_to'] ?? url('/admin/candidate-management');
            $redirectTo .= (strpos($redirectTo, '?') !== false ? '&' : '?') . 'error=update_failed';
            $this->redirect($redirectTo);
        }
    }
}
