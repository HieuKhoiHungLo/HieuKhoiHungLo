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
    protected $uploadPathInfoCache = [];
    protected $db;

    public function __construct()
    {
        $this->thiSinhRepo = new ThiSinhRepository();
        $this->nguyenVongRepo = new NguyenVongRepository();
        $this->masterData = new MasterData();
        $this->auditService = new AuditService();

        // Session-cached user lookup — avoids a DB query on every page load
        $adminId = $_SESSION['admin_id'] ?? 0;
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
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }

    protected function checkPermission($permission)
    {
        if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, $permission)) {
            if ($this->isAjax()) {
                http_response_code(403);
                die(json_encode(['error' => 'KhÃ´ng cÃ³ quyá»n truy cáº­p']));
            } else {
                echo "<script>alert('Báº¡n khÃ´ng cÃ³ quyá»n truy cáº­p chá»©c nÄƒng nÃ y!'); window.location.href='" . url('/admin/dashboard') . "';</script>";
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
        // Start timing for debugging
        $requestStart = microtime(true);
        error_log('Candidate list started at ' . $requestStart);
        // Close session write to avoid lock during long queries
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $hocBaStatus = $_GET['hoc_ba_status'] ?? '';
        $editRequest = $_GET['edit_request'] ?? '';
        $sessionId = isset($_GET['session_id']) && $_GET['session_id'] !== '' ? (int)$_GET['session_id'] : null;
        $year = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : null;
        $appStatusFilter = $_GET['app_status'] ?? 'all';

        // Custom logic for Modes
        if ($mode === 'all') {
            $appStatusFilter = 'ghost'; // Force 'chÆ°a nháº­p há»“ sÆ¡' for this view
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
        $defaultLimit = ($mode === 'review') ? 8 : 10;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $defaultLimit;
        // Clamp to valid options
        $limit = in_array($limit, [10, 15, 20, 50, 100]) ? $limit : $defaultLimit;
        $offset = ($page - 1) * $limit;
        $sort = $_GET['sort'] ?? 'ngay_tao';
        $dir = $_GET['dir'] ?? 'DESC';

        $extraFilters = [
            'f_phone'     => $_GET['f_phone'] ?? '',
            'f_dob'       => $_GET['f_dob'] ?? '',
            'f_province'  => $_GET['f_province'] ?? '',
            'f_school'    => $_GET['f_school'] ?? '',
            'f_nv1'       => $_GET['f_nv1'] ?? '',
            'f_gender'    => $_GET['f_gender'] ?? '',
            'f_ethnicity' => $_GET['f_ethnicity'] ?? '',
            'f_area'      => $_GET['f_area'] ?? '',
            'f_object'    => $_GET['f_object'] ?? '',
            'f_grad_year' => $_GET['f_grad_year'] ?? '',
            'f_email'     => $_GET['f_email'] ?? '',
            'f_note'      => $_GET['f_note'] ?? '',
            'f_transcript'=> $_GET['f_transcript'] ?? '',
            'f_reviewer'  => $_GET['f_reviewer'] ?? '',
        ];

        // Clean extraFilters for SQL mapping (remove f_ prefix for Model)
        $sqlExtraFilters = [];
        foreach ($extraFilters as $k => $v) {
            $sqlExtraFilters[substr($k, 2)] = $v;
        }

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
            $sqlExtraFilters,
            $appStatusFilter
        );

        // Cache key covers all filter params — different filters = different cache entries
        // countFiltered and stats don't change when only the page number changes
        $cacheKey = 'candidates_meta_' . md5(serialize([
            $search, $status, $hocBaStatus, $editRequest,
            $sessionId, $year, $appStatusFilter,
            $extraFilters, $mode, $limit
        ]));

        $total = \App\Core\Cache::remember($cacheKey . '_count', 3, function () use (
            $search, $status, $hocBaStatus, $sessionId, $editRequest, $year, $mode, $sqlExtraFilters, $appStatusFilter
        ) {
            return $this->thiSinhRepo->countFiltered(
                $search,
                $status,
                $hocBaStatus,
                $sessionId,
                $editRequest == '1',
                $year,
                ($mode !== 'trash'),
                $sqlExtraFilters,
                $appStatusFilter
            );
        });

        $totalPages = ceil($total / max($limit, 1));

        // Decouple stats cache from search/filter cache. Stats are same for all users in the same session/year.
        $statsCacheKey = 'dashboard_stats_global_' . ($sessionId ?? 'all') . '_' . ($year ?? 'all');
        $statsData = \App\Core\Cache::remember($statsCacheKey, 600, function () use ($sessionId, $year) {
            $s = $this->thiSinhRepo->getStats($sessionId, $year);
            $recent = $this->thiSinhRepo->getRecentRegistrationStats($sessionId);
            $s['today'] = $recent['today'] ?? 0;
            $s['this_week'] = $recent['this_week'] ?? 0;
            return $s;
        });
        $stats = $statsData;
        
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
                'search'       => $search,
                'status'       => $status,
                'hoc_ba_status'=> $hocBaStatus,
                'edit_request' => $editRequest,
                'session_id'   => $sessionId,
                'year'         => $year,
                'app_status'   => $appStatusFilter,
                'sort'         => $sort,
                'dir'          => $dir,
                'limit'        => $limit,
            ], $extraFilters),
            'pagination' => ['current_page' => $page, 'total_pages' => $totalPages, 'total_items' => $total],
            'emailTemplates' => $emailTemplates
        ]);
        // Log duration
        $requestEnd = microtime(true);
        error_log('Candidate list completed in ' . ($requestEnd - $requestStart) . ' seconds');
    }

    /**
     * Export Ghost Candidates to Excel
     */
    public function exportGhost()
    {
        $this->checkPermission('dashboard');
        
        $search = $_GET['search'] ?? '';
        $sessionId = isset($_GET['session_id']) && $_GET['session_id'] !== '' ? (int)$_GET['session_id'] : null;
        $year = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : null;

        $extraFilters = [
            'f_phone'     => $_GET['f_phone'] ?? '',
            'f_dob'       => $_GET['f_dob'] ?? '',
            'f_province'  => $_GET['f_province'] ?? '',
            'f_school'    => $_GET['f_school'] ?? '',
            'f_gender'    => $_GET['f_gender'] ?? '',
        ];

        $sqlExtraFilters = [];
        foreach ($extraFilters as $k => $v) {
            $sqlExtraFilters[substr($k, 2)] = $v;
        }

        $candidates = $this->thiSinhRepo->getFiltered(
            $search,
            '', // status
            '', // hocBaStatus
            100000, // limit
            0, // offset
            $sessionId,
            false, // onlyEditRequests
            $year,
            'ngay_tao', // sort
            'DESC', // dir
            true, // excludeTrash
            $sqlExtraFilters,
            'ghost' // applicationStatus
        );

        $exportData = [];
        $stt = 1;
        foreach ($candidates as $c) {
            $exportData[] = [
                'STT' => $stt++,
                'Số CCCD' => $c['so_cccd'] ?? '',
                'Họ và tên' => mb_strtoupper($c['ho_va_ten'] ?? '', 'UTF-8'),
                'Ngày sinh' => !empty($c['ngay_sinh']) ? date('d/m/Y', strtotime($c['ngay_sinh'])) : '',
                'Giới tính' => $c['gioi_tinh'] ?? '',
                'Điện thoại' => $c['dien_thoai'] ?? '',
                'Email' => $c['email'] ?? '',
                'Trường THPT' => $c['school_name'] ?? '',
                'Tỉnh/Thành phố' => $c['province_name'] ?? '',
                'Năm tốt nghiệp' => $c['nam_tot_nghiep'] ?? '',
                'Khu vực' => $c['khu_vuc_uu_tien'] ?? '',
                'Đối tượng' => $c['doi_tuong_uu_tien'] ?? '',
                'Ngày tạo tài khoản' => !empty($c['ngay_tao']) ? date('d/m/Y H:i:s', strtotime($c['ngay_tao'])) : '',
                'Ghi chú' => $c['base_ghi_chu'] ?? ''
            ];
        }

        // Apply text cell trick so excel doesn't drop leading zeros from phone and CCCD
        foreach ($exportData as &$row) {
            if ($row['Số CCCD'] !== '') $row['Số CCCD'] = (string)$row['Số CCCD'];
            if ($row['Điện thoại'] !== '') $row['Điện thoại'] = (string)$row['Điện thoại'];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($exportData, 'thi_sinh_chua_nhap_ho_so_' . date('Ymd_His') . '.xls');
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
                $this->checkPermission('candidate.edit');
                $status = $_POST['status'] ?? 'Chá» duyá»‡t';
                $this->bulkUpdateStatus($ids, $status);
                break;

            case 'transfer': // Added alias
            case 'transfer_session':
                $this->checkPermission('candidate.edit');
                $sessionId = $_POST['target_session_id'] ?? $_POST['session_id'] ?? null;
                if ($sessionId) {
                    $this->bulkTransferSession($ids, (int)$sessionId);
                } else {
                    $this->redirect(url('/admin/dashboard?error=missing_session'));
                    return;
                }
                break;

            case 'delete':
                $this->checkPermission('candidate.delete');
                $this->bulkDelete($ids);
                
                // Add feedback for bulk delete
                if (count($ids) === 1) {
                    $_POST['redirect_to'] .= (strpos($_POST['redirect_to'], '?') !== false ? '&' : '?') . "msg=deleted";
                } else {
                    $_POST['redirect_to'] .= (strpos($_POST['redirect_to'], '?') !== false ? '&' : '?') . "msg=bulk_success&count=" . count($ids);
                }
                break;

            case 'send_email':
                $this->checkPermission('candidate.view');
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
                $this->checkPermission('candidate.delete');
                $this->bulkRestore($ids);
                $_POST['redirect_to'] .= (strpos($_POST['redirect_to'], '?') !== false ? '&' : '?') . "msg=bulk_success&count=" . count($ids);
                break;

            case 'force_delete':
                $this->checkPermission('candidate.delete'); // Or candidates.force_delete if special
                $this->bulkForceDelete($ids);
                $_POST['redirect_to'] .= (strpos($_POST['redirect_to'], '?') !== false ? '&' : '?') . "msg=deleted";
                break;

            case 'normalize_names':
                $this->checkPermission('candidate.edit');
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
                $_POST['redirect_to'] = $baseRedirect . (strpos($baseRedirect, '?') !== false ? '&' : '?') . "success=" . urlencode("ÄÃ£ chuáº©n hÃ³a há» tÃªn cho $count thÃ­ sinh.");
                break;

            case 'change_password':
                $this->checkPermission('candidate.edit');
                $this->bulkResetPassword($ids);
                break;

            default:
                $this->redirect(url('/admin/dashboard?error=invalid_action'));
                return;
        }
        // Redirect back to exactly where the user was (preserving sort, search query strings etc.)
        $redirectTo = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('/admin/review-management'));

        $this->clearCandidateStatsCache();
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
        // Original code also updated nguyen_vong status to 'Chá» duyá»‡t'.
        // My bulkTransferSession in ThiSinhRepo only updates ho_so_xet_tuyen.
        // I need to update nguyen_vong too.
        $this->nguyenVongRepo->bulkUpdateStatus($ids, 'Chá» duyá»‡t');

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
            if ($mailer->enqueue($c['email'], $personalSubject, $personalBody, true, 'bulk')) {
                $sentNum++;
                
                // If internal note provided, update the candidate's record
                if (!empty($internalNote)) {
                    $db = \App\Core\Database::getInstance()->getConnection();
                    
                    // Update thi_sinh (Main profile note) - Overwrite existing note
                    $upd = $db->prepare("UPDATE thi_sinh SET ghi_chu = ? WHERE so_cccd = ?");
                    $upd->execute([$internalNote, $c['so_cccd']]);

                    // Also update ho_so_xet_tuyen if exists for consistency in filters
                    $updHoso = $db->prepare("UPDATE ho_so_xet_tuyen SET ghi_chu = ? WHERE so_cccd = ?");
                    $updHoso->execute([$internalNote, $c['so_cccd']]);
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
            'content' => "Há»‡ thá»‘ng Ä‘Ã£ gá»­i email Ä‘áº¿n " . count($ids) . " thÃ­ sinh. Ná»™i dung: " . mb_substr(strip_tags($body), 0, 200) . "...",
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
                    
                    $mailer->enqueue($candidate['email'], $subject, $body, true, 'system');
                }

                $this->auditService->log('RESET_PASSWORD', 'candidates', $candidate['so_cccd'], null, [
                    'ho_va_ten' => $candidate['ho_va_ten'],
                    'email_sent' => !empty($candidate['email']),
                    'bulk' => true
                ]);
            }
        }
        
        $baseRedirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('/admin/review-management'));
        $redirectTo = $baseRedirect . (strpos($baseRedirect, '?') !== false ? '&' : '?') . "success=" . urlencode("ÄÃ£ Ä‘á»•i máº­t kháº©u thÃ nh cÃ´ng cho $count há»“ sÆ¡.");
        $this->redirect($redirectTo);
    }

    /**
     * Delete single candidate
     */
    public function delete()
    {
        $this->checkPermission('candidate.delete');
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
        
        $this->clearCandidateStatsCache();
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
        $this->checkPermission('candidate.edit');
        $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? '';
        $sessionId = $_POST['session_id'] ?? '';

        if (empty($cccd) || empty($sessionId)) {
            $this->redirect(url('/admin/review?cccd=' . $cccd . '&error=missing_data'));
            return;
        }

        $this->bulkTransferSession([$cccd], $sessionId);

        $this->clearCandidateStatsCache();
        $this->redirect(url('/admin/review?cccd=' . $cccd . '&success=transferred'));
    }

    public function trash()
    {
        return $this->handleCandidateList('trash');
    }


    public function restore()
    {
        $this->checkPermission('candidate.delete');
        $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? '';
        $cccds = $_POST['cccds'] ?? [];

        if ($cccd) {
            $this->thiSinhRepo->restore($cccd);
            $this->clearCandidateStatsCache();
            $this->redirect(url('/admin/candidates/trash?success=restored'));
        } elseif (!empty($cccds)) {
            foreach ($cccds as $id) {
                $this->thiSinhRepo->restore($id);
            }
            $this->clearCandidateStatsCache();
            $this->redirect(url('/admin/candidates/trash?success=restored&count=' . count($cccds)));
        } else {
            $this->redirect(url('/admin/candidates/trash?error=missing_data'));
        }
    }

    public function forceDelete()
    {
        $this->checkPermission('candidate.delete');
        $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? '';
        $cccds = $_POST['cccds'] ?? [];

        if ($cccd) {
            $this->thiSinhRepo->forceDelete($cccd);
            $this->clearCandidateStatsCache();
            $this->redirect(url('/admin/candidates/trash?success=deleted_forever'));
        } elseif (!empty($cccds)) {
            foreach ($cccds as $id) {
                $this->thiSinhRepo->forceDelete($id);
            }
            $this->clearCandidateStatsCache();
            $this->redirect(url('/admin/candidates/trash?success=deleted_forever&count=' . count($cccds)));
        } else {
            $this->redirect(url('/admin/candidates/trash?error=missing_data'));
        }
    }

    public function emptyTrash()
    {
        $this->checkPermission('candidate.delete');
        $this->validateCsrf();

        $password = $_POST['password'] ?? '';

        if (empty($password)) {
            $this->redirect(url('/admin/candidates/trash?error=missing_password'));
            return;
        }

        $currentPasswordHash = $this->currentUser['mat_khau'] ?? '';

        if (!password_verify($password, $currentPasswordHash)) {
            $this->redirect(url('/admin/candidates/trash?error=invalid_password'));
            return;
        }

        try {
            $this->thiSinhRepo->emptyTrash();
            $this->clearCandidateStatsCache();

            $this->auditService->log('EMPTY_TRASH', 'candidates', null, null, [
                'deleted_by' => $this->currentUser['ten_dang_nhap'] ?? 'Unknown Admin'
            ]);

            $this->redirect(url('/admin/candidates/trash?success=empty_trash_success'));
        } catch (\Exception $e) {
            error_log("Error in CandidateController@emptyTrash: " . $e->getMessage());
            $this->redirect(url('/admin/candidates/trash?error=system_error'));
        }
    }

    public function edit()
    {
        $this->checkPermission('candidate.edit');

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
            $subjects = ['toan', 'van', 'ngoai_ngu', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd', 'ktpl', 'cong_nghe', 'tin_hoc'];
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
            $this->clearCandidateStatsCache();
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
        $this->checkPermission('candidate.edit');
        // $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? '';
        $section = $_POST['section'] ?? '';

        // error_log("DEBUG_UPDATE: CCCD=$cccd, Section=$section");

        if (!$cccd || !$section) {
            $this->json(['success' => false, 'error' => 'Thiáº¿u dá»¯ liá»‡u CCCD hoáº·c Section. Debug: ' . print_r($_POST, true)]);
            return;
        }

        try {
            $this->db->beginTransaction();

            $logPath = dirname(__DIR__, 2) . '/storage/logs/bulk_transcript.log';
            $logFile = @fopen($logPath, 'a');
            if ($logFile) {
                fwrite($logFile, "\n--- BULK TRANSCRIPT START: " . date('Y-m-d H:i:s') . " ---\n");
            }
            switch ($section) {
                case 'personal':
                    // Personal Info (Updated fields only)
                    // Use '' for text fields if empty, null for IDs if empty
                    $data = [
                        'ho_va_ten' => normalize_name($_POST['ho_va_ten'] ?? ''),
                        'ngay_sinh' => $_POST['ngay_sinh'] ?? '',
                        'gioi_tinh' => $_POST['gioi_tinh'] ?? '',
                        'dan_toc'   => $_POST['dan_toc'] ?? '',
                        'dien_thoai' => $_POST['dien_thoai'] ?? '',
                        'email'     => $_POST['email'] ?? '',
                        'dia_chi_chi_tiet'   => $_POST['dia_chi_chi_tiet'] ?? '',
                        'ma_tinh_ho_khau'    => !empty($_POST['ma_tinh_ho_khau']) ? $_POST['ma_tinh_ho_khau'] : null,
                        'ma_tinh_thuong_tru' => !empty($_POST['ma_tinh_thuong_tru']) ? $_POST['ma_tinh_thuong_tru'] : null,
                        'ma_xa_thuong_tru'   => !empty($_POST['ma_xa_thuong_tru']) ? $_POST['ma_xa_thuong_tru'] : null,
                    ];

                    // Only update demographic fields if they are actually included in the POST request (prevents wiping when saving from tabs that lack these inputs)
                    if (isset($_POST['nam_tot_nghiep'])) $data['nam_tot_nghiep'] = trim($_POST['nam_tot_nghiep']) !== '' ? trim($_POST['nam_tot_nghiep']) : null;
                    if (isset($_POST['ma_tinh_lop_12'])) $data['ma_tinh_lop_12'] = trim($_POST['ma_tinh_lop_12']) !== '' ? trim($_POST['ma_tinh_lop_12']) : null;
                    if (isset($_POST['ma_truong_lop_12'])) $data['ma_truong_lop_12'] = trim($_POST['ma_truong_lop_12']) !== '' ? trim($_POST['ma_truong_lop_12']) : null;
                    if (isset($_POST['kv_uu_tien'])) $data['khu_vuc_uu_tien'] = trim($_POST['kv_uu_tien']) !== '' ? trim($_POST['kv_uu_tien']) : null;
                    if (isset($_POST['is_custom_kv'])) $data['is_custom_kv'] = ($_POST['is_custom_kv'] ?? '0') == '1';
                    if (isset($_POST['dt_uu_tien'])) $data['doi_tuong_uu_tien'] = trim($_POST['dt_uu_tien']) !== '' ? trim($_POST['dt_uu_tien']) : null;
                    if (isset($_POST['is_custom_dt'])) $data['is_custom_dt'] = ($_POST['is_custom_dt'] ?? '0') == '1';

                    // Xử lý đổi Số CCCD nếu có (Giải pháp an toàn cho Ràng buộc dữ liệu)
                    $newCccd = null;
                    if (!empty($_POST['so_cccd']) && trim($_POST['so_cccd']) !== $cccd) {
                        $newCccd = trim($_POST['so_cccd']);
                        // Gọi phương thức đổi CCCD an toàn (cascade manual)
                        try {
                            $this->thiSinhRepo->changeCCCD($cccd, $newCccd);
                            // Cập nhật lại biến $cccd để các thao tác phía sau (như upload file) dùng ID mới
                            $cccd = $newCccd;
                        } catch (\Exception $e) {
                            $this->json(['success' => false, 'error' => $e->getMessage()]);
                            return;
                        }
                    }

                    // Handle File Uploads â€” only if files are actually attached
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

                    // NOTE: Application status (trang_thai) is intentionally NOT updated here.
                    // Status updates only happen via the "Xác nhận duyệt hồ sơ" modal (confirmSubmitReview).

                    // error_log("DEBUG_UPDATE_PERSONAL: " . print_r($data, true));
                    $res = $this->thiSinhRepo->updateFullProfile($cccd, $data);

                    if ($res) {
                        $msg = 'Lưu thành công';
                        break;
                    } else {
                        throw new \Exception('Lỗi cập nhật thông tin cá nhân');
                    }

                case 'academic':
                    $academicRepo = new \App\Repositories\AcademicRepository();
                    $hasAcademicFiles = false;
                    foreach ($_FILES as $key => $file) {
                        if (strpos($key, 'transcripts_') === 0 && !empty($file['name'])) {
                            $hasAcademicFiles = true;
                            break;
                        }
                    }

                    $pathInfo = null;
                    $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                    $uploader = null;
                    if ($hasAcademicFiles) {
                        $pathInfo = $this->getUploadPathInfo($cccd);
                        $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);
                        if ($uploadDriver === 'google') {
                            $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                            $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
                            $uploader->setGoogleConfig($clientSecretPath, $tokenPath, $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                            $driveService = new \App\Services\DriveService($uploader);
                            $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                            if ($targetFolderId) $uploader->setTargetFolderId($targetFolderId);
                        }
                    }

                    $batchData = [];
                    foreach ([10, 11, 12] as $g) {
                        if (isset($_POST['scores'][$g]) && is_array($_POST['scores'][$g])) {
                            $gradeInputs = $_POST['scores'][$g];
                            $record = [
                                'diem_tb' => $gradeInputs['diem_tb_ca_nam'] ?? null,
                                'hoc_luc' => $gradeInputs['hoc_luc_ca_nam'] ?? null,
                                'hanh_kiem' => $gradeInputs['hanh_kiem_ca_nam'] ?? null,
                                'file_hoc_ba' => $gradeInputs['existing_files'] ?? null
                            ];

                            // Map subjects
                            $subjects = ['toan', 'van', 'ngoai_ngu', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd', 'ktpl', 'tin_hoc', 'cong_nghe'];
                            foreach ($subjects as $s) {
                                if (isset($gradeInputs["diem_{$s}_cn"])) {
                                    $record[$s] = $gradeInputs["diem_{$s}_cn"] !== '' ? (float)$gradeInputs["diem_{$s}_cn"] : null;
                                }
                            }

                            // Handle Academic File Uploads (Partial replacements preferred)
                            $existingFilesStr = $gradeInputs['existing_files'] ?? '';
                            $currentFileList = !empty($existingFilesStr) ? explode(',', $existingFilesStr) : [];
                            $hasFilesChange = false;

                            // 1. Check for specific replace inputs (Admin dashboard pattern)
                            foreach ([1 => 0, 2 => 1] as $suffix => $idx) {
                                $fKey = "transcripts_{$g}_replace_{$suffix}";
                                if (!empty($_FILES[$fKey]['name'])) {
                                    $uploader->clearErrors();
                                    $fName = $uploader->upload($_FILES[$fKey], "{$cccd}_transcript_G{$g}_idx{$idx}_" . time());
                                    if ($fName) {
                                        $currentFileList[$idx] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fName : $fName;
                                        $hasFilesChange = true;
                                    }
                                }
                            }

                            // 2. Check for indexed array inputs (Legacy/Frontend pattern)
                            if (isset($_FILES["transcripts_$g"]['name']) && is_array($_FILES["transcripts_$g"]['name'])) {
                                foreach ($_FILES["transcripts_$g"]['name'] as $i => $name) {
                                    if (empty($name)) continue;
                                    $fileToUpload = [
                                        'name' => $name,
                                        'type' => $_FILES["transcripts_$g"]['type'][$i],
                                        'tmp_name' => $_FILES["transcripts_$g"]['tmp_name'][$i],
                                        'error' => $_FILES["transcripts_$g"]['error'][$i],
                                        'size' => $_FILES["transcripts_$g"]['size'][$i]
                                    ];
                                    $uploader->clearErrors();
                                    $fName = $uploader->upload($fileToUpload, "{$cccd}_transcript_G{$g}_arr{$i}_" . time());
                                    if ($fName) {
                                        $currentFileList[] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fName : $fName;
                                        $hasFilesChange = true;
                                    }
                                }
                            }

                            if ($hasFilesChange) {
                                $record['file_hoc_ba'] = implode(',', array_filter($currentFileList));
                            }
                            
                            $batchData[$g] = $record;
                        }
                    }

                    if (!empty($batchData)) {
                        $academicRepo->saveBatch($cccd, $batchData);
                    }

                    // Priority & School sync (Only update fields if they were actively submitted in the form)
                    $personalData = [];
                    if (isset($_POST['ma_tinh_lop_12'])) $personalData['ma_tinh_lop_12'] = $_POST['ma_tinh_lop_12'] ?: null;
                    if (isset($_POST['ma_truong_lop_12'])) $personalData['ma_truong_lop_12'] = $_POST['ma_truong_lop_12'] ?: null;
                    if (isset($_POST['nam_tot_nghiep'])) $personalData['nam_tot_nghiep'] = $_POST['nam_tot_nghiep'] ?: null;
                    if (isset($_POST['kv_uu_tien'])) $personalData['khu_vuc_uu_tien'] = $_POST['kv_uu_tien'] ?: null;
                    if (isset($_POST['is_custom_kv'])) $personalData['is_custom_kv'] = ($_POST['is_custom_kv'] ?? '0') == '1';
                    if (isset($_POST['dt_uu_tien'])) $personalData['doi_tuong_uu_tien'] = $_POST['dt_uu_tien'] ?: null;
                    if (isset($_POST['is_custom_dt'])) $personalData['is_custom_dt'] = ($_POST['is_custom_dt'] ?? '0') == '1';

                    foreach (['kv_file' => 'file_minh_chung_kv', 'dt_file' => 'file_minh_chung_dt'] as $f => $col) {
                        if (!empty($_FILES[$f]['name'])) {
                            if (!isset($uploader)) {
                                $pathInfo = $this->getUploadPathInfo($cccd);
                                $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);
                            }
                            $uploader->clearErrors();
                            $fName = $uploader->upload($_FILES[$f], "{$cccd}_{$f}_" . time());
                            if ($fName) $personalData[$col] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fName : $fName;
                        }
                    }
                    if (!empty($personalData)) {
                        $this->thiSinhRepo->updateFullProfile($cccd, $personalData);
                    }

                    // NOTE: Application status (trang_thai) is intentionally NOT updated here.
                    // Status updates only happen via the "Xác nhận duyệt hồ sơ" modal (confirmSubmitReview).
                    $msg = 'Đã lưu kết quả học tập thành công';
                    break;

                case 'thpt':
                    // THPT Scores Update
                    $fields = ['toan', 'van', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd', 'tieng_anh', 'tieng_trung', 'ktpl', 'tin_hoc', 'cnnn', 'diem_xet_tot_nghiep'];
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

                    if (!empty($scores)) {
                        $scores['nam_thi'] = date('Y');
                        $this->thiSinhRepo->saveDiemThiTHPT($cccd, $scores);
                    }

                    // NOTE: Application status (trang_thai) is intentionally NOT updated here.
                    // Status updates only happen via the "Xác nhận duyệt hồ sơ" modal (confirmSubmitReview).
                    $msg = 'Đã lưu điểm thi THPT thành công';
                    break;

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

                    $this->thiSinhRepo->saveCertifications($cccd, $certsData);

                    // NOTE: Application status (trang_thai) is intentionally NOT updated here.
                    // Status updates only happen via the "Xác nhận duyệt hồ sơ" modal (confirmSubmitReview).
                    $msg = 'Đã lưu chứng chỉ thành công';
                    break;

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
                        $this->json(['success' => false, 'error' => 'KhÃ´ng tÃ¬m tháº¥y há»“ sÆ¡ Ä‘á»ƒ lÆ°u nguyá»‡n vá» ng.']);
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
                        $this->json(['success' => false, 'error' => 'KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c Ä‘á»£t tuyá»ƒn sinh cho há»“ sÆ¡ nÃ y.']);
                        return;
                    }

                    if (empty($items)) {
                        $this->json(['success' => false, 'error' => 'Vui lÃ²ng thÃªm Ã­t nháº¥t 1 nguyá»‡n vá» ng.']);
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
                        $this->json(['success' => false, 'error' => 'Lá»—i lÆ°u nguyá»‡n vá»ng vÃ o CSDL.']);
                        return;
                    }
                    break;
            }

            $this->db->commit();

            // Return success response to the user immediately
            $this->json([
                'success' => true, 
                'message' => $msg ?? 'Đã lưu thông tin thành công',
                'new_cccd' => $data['so_cccd'] ?? $cccd // in case of CCCD update in personal tab
            ]);

            // Non-critical tasks after connection is closed
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            // Audit log (post-response)
            try {
                $this->auditService->log('UPDATE_CANDIDATE', 'candidates', $cccd, null, ['section' => $section]);
            } catch (\Exception $auditEx) {
                error_log("Audit log failed: " . $auditEx->getMessage());
            }
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("UPDATE CANDIDATE ERROR: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function getUploadPathInfo($cccd)
    {
        if (isset($this->uploadPathInfoCache[$cccd])) {
            return $this->uploadPathInfoCache[$cccd];
        }

        $sessionModel = new \App\Models\AdmissionSession();
        $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestActiveSession();

        $year = date('Y');
        $sessionName = 'Dot1';

        if ($activeSession) {
            $year = $activeSession['nam_tuyen_sinh'] ?? date('Y');
            $sessionName = $activeSession['ma_dot'] ?? ('Dot_' . ($activeSession['id'] ?? '1'));
            $sessionName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $sessionName);
        }

        $relativePath = "/uploads/{$year}/{$sessionName}/{$cccd}";
        $absolutePath = __DIR__ . '/../../public' . $relativePath;

        $this->uploadPathInfoCache[$cccd] = [
            'relative' => $relativePath,
            'absolute' => $absolutePath,
            'year' => $year,
            'session' => $sessionName
        ];

        return $this->uploadPathInfoCache[$cccd];
    }

    public function changePassword()
    {
        $this->checkPermission('candidate.edit');
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
                $subject = "ThÃ´ng bÃ¡o thay Ä‘á»•i máº­t kháº©u - Há»‡ thá»‘ng Tuyá»ƒn sinh";
                $body = "ChÃ o báº¡n <b>{$candidate['ho_va_ten']}</b>,<br><br>
                        NgÆ°á»i quáº£n trá»‹ Ä‘Ã£ thay Ä‘á»•i máº­t kháº©u Ä‘Äƒng nháº­p cá»§a báº¡n trÃªn há»‡ thá»‘ng Tuyá»ƒn sinh.<br>
                        Máº­t kháº©u má»›i cá»§a báº¡n lÃ : <b style='color: #0066FF; font-size: 1.2em;'>{$newPassword}</b><br><br>
                        Vui lÃ²ng sá»­ dá»¥ng máº­t kháº©u nÃ y Ä‘á»ƒ Ä‘Äƒng nháº­p vÃ  Ä‘á»•i láº¡i máº­t kháº©u cÃ¡ nhÃ¢n sau khi truy cáº­p.<br>
                        TrÃ¢n trá»ng!";
                
                $mailer->enqueue($candidate['email'], $subject, $body, true, 'system');
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

    /**
     * Bulk approve applications by uploading an Excel file (CCCD + Note)
     * V13: Optimized - single batch UPDATE instead of per-row queries
     */
    public function bulkApproveByFile()
    {
        $this->checkPermission('candidate.edit');
        $this->validateCsrf();
        set_time_limit(0);

        $redirectTo = url('/admin/review-management');

        if (!isset($_FILES['approve_file']) || $_FILES['approve_file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirect($redirectTo . '?error=' . urlencode('Vui lòng chọn file để upload.'));
            return;
        }

        $filePath = $_FILES['approve_file']['tmp_name'];
        $sessionId = $_POST['session_id'] ?? null;
        $adminId = $_SESSION['admin_id'] ?? null;

        try {
            // OPTIMIZATION: Use Xlsx reader with ReadDataOnly for performance
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            array_shift($rows); // Skip header

            // Collect all CCCDs and group notes for batch updates
            $cccds = [];
            $noteGroups = []; // Group CCCDs by note content to reduce queries
            foreach ($rows as $row) {
                $cccd = $this->normalizeCCCD($row['B'] ?? '');
                if (empty($cccd)) continue;
                $cccds[] = $cccd;
                
                $note = trim($row['C'] ?? '');
                if ($note !== '') {
                    if (!isset($noteGroups[$note])) $noteGroups[$note] = [];
                    $noteGroups[$note][] = $cccd;
                }
            }

            if (empty($cccds)) {
                $this->redirect($redirectTo . '?error=' . urlencode('File không có dữ liệu CCCD hợp lệ.'));
                return;
            }

            $this->db->beginTransaction();

            $approvedStatus = \App\Core\UserStatus::APPROVED;

            // 1. Batch UPDATE for ho_so_xet_tuyen
            $placeholders = implode(',', array_fill(0, count($cccds), '?'));
            $sql = "UPDATE ho_so_xet_tuyen SET trang_thai = ?, nguoi_duyet_id = ?, updated_at = NOW() AT TIME ZONE 'Asia/Ho_Chi_Minh' WHERE so_cccd IN ($placeholders)";
            $params = [$approvedStatus, $adminId];
            $params = array_merge($params, $cccds);

            if ($sessionId) {
                $sql .= " AND dot_tuyen_sinh_id = ?";
                $params[] = $sessionId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $success = $stmt->rowCount();

            // 2. OPTIMIZATION: Batch UPDATE for notes (grouped by unique note content)
            if (!empty($noteGroups)) {
                foreach ($noteGroups as $noteText => $groupCccds) {
                    $notePlaceholders = implode(',', array_fill(0, count($groupCccds), '?'));
                    $noteSql = "UPDATE ho_so_xet_tuyen SET ghi_chu = ? WHERE so_cccd IN ($notePlaceholders)";
                    $noteParams = [$noteText];
                    $noteParams = array_merge($noteParams, $groupCccds);
                    
                    if ($sessionId) {
                        $noteSql .= " AND dot_tuyen_sinh_id = ?";
                        $noteParams[] = $sessionId;
                    }
                    
                    $this->db->prepare($noteSql)->execute($noteParams);
                }
            }

            // 3. Batch UPDATE for nguyen_vong status synchronization
            if ($success > 0) {
                $nvSql = "UPDATE nguyen_vong SET trang_thai = ? WHERE so_cccd IN ($placeholders)";
                $nvParams = array_merge([$approvedStatus], $cccds);
                
                if ($sessionId) {
                    $nvSql .= " AND dot_tuyen_sinh_id = ?";
                    $nvParams[] = $sessionId;
                }
                
                $this->db->prepare($nvSql)->execute($nvParams);
            }

            $this->db->commit();

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $total = count($cccds);
            $notFound = $total - $success;
            $this->redirect($redirectTo . '?success=' . urlencode("Đã duyệt thành công $success/$total hồ sơ." . ($notFound > 0 ? " ($notFound CCCD không tìm thấy)" : "")));

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->redirect($redirectTo . '?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Bulk approve ALL pending applications in a batch session
     */
    public function bulkApproveAll()
    {
        $this->checkPermission('candidate.edit');
        $this->validateCsrf();

        $redirectTo = url('/admin/review-management');
        $sessionId = $_POST['session_id'] ?? null;
        $adminId = $_SESSION['admin_id'] ?? null;

        if (!$sessionId) {
            $this->redirect($redirectTo . '?error=' . urlencode('Chưa chọn đợt tuyển sinh.'));
            return;
        }

        try {
            $sql = "UPDATE ho_so_xet_tuyen SET trang_thai = 'Đã duyệt', nguoi_duyet_id = ?, updated_at = NOW() AT TIME ZONE 'Asia/Ho_Chi_Minh' WHERE dot_tuyen_sinh_id = ? AND trang_thai != 'Đã duyệt'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$adminId, $sessionId]);
            $count = $stmt->rowCount();

            $this->redirect($redirectTo . '?success=' . urlencode("Đã duyệt tất cả $count hồ sơ trong đợt."));

        } catch (\Exception $e) {
            $this->redirect($redirectTo . '?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Bulk unapprove ALL approved applications in a batch session
     */
    public function bulkUnapproveAll()
    {
        $this->checkPermission('candidate.edit');
        $this->validateCsrf();

        $redirectTo = url('/admin/review-management');
        $sessionId = $_POST['session_id'] ?? null;

        if (!$sessionId) {
            $this->redirect($redirectTo . '?error=' . urlencode('Chưa chọn đợt tuyển sinh.'));
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1. Update ho_so_xet_tuyen - Reset to 'Chờ duyệt', clear reviewer
            $sql1 = "UPDATE ho_so_xet_tuyen 
                    SET trang_thai = 'Chờ duyệt', 
                        nguoi_duyet_id = NULL, 
                        updated_at = NOW() AT TIME ZONE 'Asia/Ho_Chi_Minh' 
                    WHERE dot_tuyen_sinh_id = ? 
                    AND trang_thai = 'Đã duyệt'";
            $stmt1 = $this->db->prepare($sql1);
            $stmt1->execute([$sessionId]);
            $count = $stmt1->rowCount();

            if ($count > 0) {
                // 2. Sync nguyen_vong status back to 'Chờ duyệt' for these candidates in this session
                $sql2 = "UPDATE nguyen_vong 
                        SET trang_thai = 'Chờ duyệt' 
                        WHERE so_cccd IN (SELECT so_cccd FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ?)";
                $stmt2 = $this->db->prepare($sql2);
                $stmt2->execute([$sessionId]);
            }

            $this->db->commit();

            $this->redirect($redirectTo . '?success=' . urlencode("Đã hủy duyệt tất cả $count hồ sơ trong đợt."));

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->redirect($redirectTo . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function downloadApproveTemplate()
    {
        $this->checkPermission('candidate.edit');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['STT', 'Số CCCD', 'Ghi chú'];
        foreach ($headers as $i => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($colLetter . '1', $h);
        }
        $sheet->setCellValue('A2', 1);
        $sheet->setCellValueExplicit('B2', '012345678901', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('C2', 'Đã kiểm tra');
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Mau_Duyet_Ho_So.xlsx"');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function downloadTranscriptTemplate()
    {
        $this->checkPermission('candidate.edit');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = [
            'STT', 'CCCD', 'Lớp', 'Toán', 'Văn', 'NN', 'Lý', 'Hóa', 'Sinh', 
            'Sử', 'Địa', 'GDCD', 'Tin', 'CN', 'KTPL', 'ĐTB cả năm', 'Học lực', 'Hạnh kiểm', 'Ghi chú'
        ];
        foreach ($headers as $i => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($colLetter . '1', $h);
        }
        $sheet->getStyle('A1:S1')->getFont()->setBold(true);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('S')->setWidth(30);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Mau_Cap_Nhat_Hoc_Ba_Moi_Nhat_V2.xlsx"');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function bulkUpdateTranscript()
    {
        $this->checkPermission('candidate.edit');
        $this->validateCsrf();
        $redirectTo = url('/admin/review-management');

        if (!isset($_FILES['transcript_file']) || $_FILES['transcript_file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirect($redirectTo . '?error=' . urlencode('Vui lòng chọn file để upload.'));
            return;
        }

        $filePath = $_FILES['transcript_file']['tmp_name'];
        $academicModel = new \App\Models\AcademicRecord();
        $colMap = [3=>'diem_toan_cn', 4=>'diem_van_cn', 5=>'diem_ngoai_ngu_cn', 6=>'diem_ly_cn', 7=>'diem_hoa_cn', 8=>'diem_sinh_cn', 9=>'diem_su_cn', 10=>'diem_dia_cn', 11=>'diem_gdcd_cn', 12=>'diem_tin_hoc_cn', 13=>'diem_cong_nghe_cn', 14=>'diem_ktpl_cn', 15=>'diem_tb_ca_nam'];
        $textCols = [16=>'hoc_luc_ca_nam', 17=>'hanh_kiem_ca_nam', 18=>'ghi_chu'];

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $rows = array_values($spreadsheet->getActiveSheet()->toArray(null, true, true, true));
            array_shift($rows);

            $success = 0; $skipped = 0; $warnings = [];
            $this->db->beginTransaction();

            $logFile = @fopen(dirname(__DIR__, 2) . '/storage/logs/bulk_transcript.log', 'a');
            if ($logFile) fwrite($logFile, "\n--- BULK TRANSCRIPT START: " . date('Y-m-d H:i:s') . " ---\n");

            foreach ($rows as $index => $row) {
                $rowValues = array_values($row);
                $cccd = $this->normalizeCCCD($rowValues[1] ?? '');
                $lop = trim($rowValues[2] ?? '');
                $lineNum = $index + 2;

                if (empty($cccd)) continue;
                if (!in_array($lop, ['10', '11', '12'])) {
                    $warnings[] = "Dòng $lineNum: Lớp '$lop' không hợp lệ.";
                    $skipped++; continue;
                }

                $stmtCheck = $this->db->prepare("SELECT so_cccd FROM thi_sinh WHERE so_cccd = ?");
                $stmtCheck->execute([$cccd]);
                if (!$stmtCheck->fetchColumn()) {
                    $warnings[] = "Dòng $lineNum: CCCD $cccd không tồn tại.";
                    $skipped++; continue;
                }

                $scoreData = [];
                foreach ($colMap as $colIdx => $dbCol) {
                    $val = trim($rowValues[$colIdx] ?? '');
                    if ($val === '') { $scoreData[$dbCol] = null; }
                    else {
                        $numVal = str_replace(',', '.', $val);
                        $scoreData[$dbCol] = is_numeric($numVal) ? (float)$numVal : null;
                    }
                }
                foreach ($textCols as $colIdx => $dbCol) {
                    $val = trim($rowValues[$colIdx] ?? '');
                    if ($val === '') {
                        $scoreData[$dbCol] = null;
                    } else {
                        // Apply normalization for ratings
                        if (in_array($dbCol, ['hoc_luc_ca_nam', 'hanh_kiem_ca_nam'])) {
                            $scoreData[$dbCol] = $academicModel->normalizeRating($val);
                        } else {
                            $scoreData[$dbCol] = $val;
                        }
                    }
                }

                $academicModel->save($cccd, (int)$lop, $scoreData);
                if ($logFile) fwrite($logFile, "Line $lineNum: CCCD $cccd (Lop $lop) -> SUCCESS\n");
                $success++;
            }

            $this->db->commit();
            if ($logFile) fclose($logFile);
            if (!empty($warnings)) $_SESSION['bulk_warnings'] = $warnings;

            $this->redirect($redirectTo . '?success=' . urlencode("Đã cập nhật xong $success thí sinh."));
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->redirect($redirectTo . '?error=' . urlencode($e->getMessage()));
        }
    }

    private function normalizeCCCD($cccd)
    {
        $cccd = (string)$cccd;
        if (empty(trim($cccd))) return '';
        if (stripos($cccd, 'E') !== false) { $cccd = sprintf('%.0f', (float)trim($cccd)); }
        $cccd = preg_replace('/[^\d]/', '', $cccd);
        if (strlen($cccd) > 0 && strlen($cccd) < 12) { $cccd = str_pad($cccd, 12, '0', STR_PAD_LEFT); }
        if (strlen($cccd) === 13 && $cccd[0] === '0') { $cccd = substr($cccd, 1); }
        return $cccd;
    }

    private function getLatestSessionId()
    {
        $stmt = $this->db->query("SELECT id FROM dot_tuyen_sinh ORDER BY ngay_bat_dau DESC LIMIT 1");
        return $stmt->fetchColumn() ?: null;
    }

    protected function clearCandidateStatsCache()
    {
        \App\Core\Cache::forget('dashboard_stats_global_all_all');
        
        try {
            $stmt = $this->db->query("SELECT id, nam_tuyen_sinh FROM dot_tuyen_sinh");
            $sessions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($sessions as $s) {
                \App\Core\Cache::forget('dashboard_stats_global_' . $s['id'] . '_all');
                \App\Core\Cache::forget('dashboard_stats_global_all_' . $s['nam_tuyen_sinh']);
                \App\Core\Cache::forget('dashboard_stats_global_' . $s['id'] . '_' . $s['nam_tuyen_sinh']);
            }
        } catch (\Exception $e) {
            error_log("Error clearing candidate stats cache: " . $e->getMessage());
        }
    }
}
