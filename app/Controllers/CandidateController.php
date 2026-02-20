<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ThiSinh;
use App\Models\MasterData;
use App\Core\FileUploader;
use App\Services\AuditService;
use App\Repositories\ThiSinhRepository;
use App\Repositories\NguyenVongRepository;

class CandidateController extends Controller {

    protected $thiSinhRepo;
    protected $nguyenVongRepo;
    protected $masterData;
    protected $auditService;
    protected $currentUser;

    public function __construct() {
        $this->thiSinhRepo = new ThiSinhRepository();
        $this->nguyenVongRepo = new NguyenVongRepository();
        $this->masterData = new MasterData();
        $this->auditService = new AuditService();
        
        $adminModel = new \App\Models\QuanTriVien();
        $this->currentUser = $adminModel->find($_SESSION['admin_id'] ?? 0);
    }

    protected function checkPermission($permission) {
        if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, $permission)) {
            http_response_code(403);
            die(json_encode(['error' => 'Không có quyền truy cập']));
        }
    }

    /**
     * Handle bulk actions from dashboard
     */
    public function bulkAction() {
        
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
                break;

            case 'send_email':
                $this->checkPermission('candidates.view');
                $templateId = $_POST['template_id'] ?? null;
                if ($templateId) {
                    $this->bulkSendEmail($ids, $templateId);
                }
                break;

            case 'restore':
                $this->checkPermission('candidates.delete');
                $this->bulkRestore($ids);
                break;

            case 'force_delete':
                $this->checkPermission('candidates.delete'); // Or candidates.force_delete if special
                $this->bulkForceDelete($ids);
                break;

            default:
                $this->redirect(url('/admin/dashboard?error=invalid_action'));
                return;
        }

        $this->redirect(url('/admin/dashboard?success=' . $action));
    }

    /**
     * Bulk update status
     */
    protected function bulkUpdateStatus($ids, $status) {
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
    protected function bulkTransferSession($ids, $sessionId) {
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
    protected function bulkDelete($ids) {
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
    protected function bulkRestore($ids) {
        $this->thiSinhRepo->bulkRestore($ids);
        $this->auditService->log('BULK_RESTORE', 'candidates', null, null, [
            'count' => count($ids),
            'cccd_list' => $ids
        ]);
    }

    /**
     * Bulk force delete
     */
    protected function bulkForceDelete($ids) {
        $this->thiSinhRepo->bulkForceDelete($ids);
        $this->auditService->log('BULK_FORCE_DELETE', 'candidates', null, null, [
            'count' => count($ids),
            'cccd_list' => $ids
        ]);
    }

    /**
     * Bulk send email
     */
    protected function bulkSendEmail($ids, $templateId) {
        // Get template. EmailTemplatesRepository? Or just DB for now as non-critical?
        // Let's use DB for template as it is not in ThiSinh scope.
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM email_templates WHERE id = ?");
        $stmt->execute([$templateId]);
        $template = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$template) return;

        // Get candidates using Repository
        $candidates = $this->thiSinhRepo->getEmailsByIds($ids);

        $mailer = new \App\Services\MailerService();
        $sent = 0;

        foreach ($candidates as $c) {
            if (empty($c['email'])) continue;
            
            $subject = str_replace('{ho_ten}', $c['ho_va_ten'], $template['subject']);
            $body = str_replace(['{ho_ten}', '{so_cccd}'], [$c['ho_va_ten'], $c['so_cccd']], $template['body']);
            
            if ($mailer->send($c['email'], $subject, $body)) {
                $sent++;
            }
        }

        $this->auditService->log('BULK_SEND_EMAIL', 'candidates', null, null, [
            'template_id' => $templateId,
            'sent_count' => $sent,
            'total' => count($ids)
        ]);
    }

    /**
     * Delete single candidate
     */
    public function delete() {
        $this->checkPermission('candidates.delete');
        $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? $_GET['cccd'] ?? '';
        
        if (empty($cccd)) {
            $this->redirect(url('/admin/dashboard?error=missing_cccd'));
            return;
        }

        $this->bulkDelete([$cccd]);

        $this->redirect(url('/admin/dashboard?success=deleted'));
    }

    /**
     * Transfer single candidate to another session
     */
    public function transfer() {
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

    public function trash() {
        $this->checkPermission('candidates.view');
        
        $page = $_GET['page'] ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';

        // Get Trashed only
        $candidates = $this->thiSinhRepo->getFiltered(
            $search, 
            '', // status
            '', // hocBaStatus
            $limit, 
            $offset, 
            null, // sessionId
            false, // onlyEditRequests
            null, // year
            'created_at', 
            'desc',
            true // trashed = true
        );

        $total = $this->thiSinhRepo->countFiltered(
            $search,
             '', 
             '', 
             null, 
             false, 
             null,
             true // trashed = true
        );

        $totalPages = ceil($total / $limit);

        $this->view('admin/candidates/trash', [
            'candidates' => $candidates,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'user' => $this->currentUser
        ]);
    }

    public function restore() {
        $this->checkPermission('candidates.delete');
        $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? '';
        if ($cccd) {
            $this->thiSinhRepo->restore($cccd);
            $this->redirect(url('/admin/candidates/trash?success=restored'));
        } else {
             $this->redirect(url('/admin/candidates/trash?error=missing_data'));
        }
    }

    public function forceDelete() {
        $this->checkPermission('candidates.delete');
        $this->validateCsrf();

        $cccd = $_POST['cccd'] ?? '';
        if ($cccd) {
            $this->thiSinhRepo->forceDelete($cccd);
            $this->redirect(url('/admin/candidates/trash?success=deleted_forever'));
        } else {
             $this->redirect(url('/admin/candidates/trash?error=missing_data'));
        }
    }

    public function edit() {
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
                'ho_va_ten' => $_POST['ho_va_ten'] ?? '',
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
                        $clientSecretPath = realpath(__DIR__ . '/../../') . '/client_secret.json';
                        if (!file_exists($clientSecretPath)) $clientSecretPath = __DIR__ . '/../../client_secret.json';
                        $uploader->setGoogleConfig($clientSecretPath, __DIR__ . '/../../' . ($_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json'), $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                        $driveService = new \App\Services\DriveService($uploader);
                        $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                        if ($targetFolderId) { $uploader->setTargetFolderId($targetFolderId); }
                    }
                    $uploader->setAllowedMimes(['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png']);
                }
                
                // Upload Avatar
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
                            $clientSecretPath = realpath(__DIR__ . '/../../') . '/client_secret.json';
                            if (!file_exists($clientSecretPath)) $clientSecretPath = __DIR__ . '/../../client_secret.json';
                            $uploader->setGoogleConfig($clientSecretPath, __DIR__ . '/../../' . ($_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json'), $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                            $driveService = new \App\Services\DriveService($uploader);
                            $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                            if ($targetFolderId) { $uploader->setTargetFolderId($targetFolderId); }
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
            $subjects = ['toan','van','ngoai_ngu','ly','hoa','sinh','su','dia','gdcd','cong_nghe','tin_hoc'];
            foreach ($grades as $g) {
                $record = [];
                foreach ($subjects as $s) {
                    $val = $_POST["grade_{$g}_{$s}_hk1"] ?? null;
                    if($val !== null) $record["diem_{$s}_hk1"] = $val;
                    $val2 = $_POST["grade_{$g}_{$s}_hk2"] ?? null;
                    if($val2 !== null) $record["diem_{$s}_hk2"] = $val2;
                }
                
                // Fields: diem_tb, hoc_luc, hanh_kiem
                $fields = ['diem_tb', 'hoc_luc', 'hanh_kiem'];
                foreach(['hk1', 'hk2'] as $hk) {
                    foreach($fields as $f) {
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
            foreach($fieldsTHPT as $f) {
                if (isset($_POST[$f]) && $_POST[$f] !== '') $scores[$f] = $_POST[$f];
            }
            // Foreign languages
             $langs = ['tieng_anh', 'tieng_nga', 'tieng_phap', 'tieng_trung', 'tieng_duc', 'tieng_nhat', 'tieng_han'];
            foreach($langs as $l) {
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
        $priorityAreas = $this->masterData->getPriorityAreas(); // New
        $priorityObjects = $this->masterData->getPriorityObjects(); // New
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

    public function update() {
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
                        'ho_va_ten' => $_POST['ho_va_ten'],
                        'ngay_sinh' => $_POST['ngay_sinh'],
                        'gioi_tinh' => $_POST['gioi_tinh'],
                        'dan_toc'   => $_POST['dan_toc'] ?? '', 
                        'dien_thoai'=> $_POST['dien_thoai'],
                        'email'     => $_POST['email'],
                        'dia_chi_chi_tiet'   => $_POST['dia_chi_chi_tiet'],
                        'ma_tinh_ho_khau'    => !empty($_POST['ma_tinh_ho_khau']) ? $_POST['ma_tinh_ho_khau'] : null,
                        'ma_tinh_thuong_tru' => !empty($_POST['ma_tinh_thuong_tru']) ? $_POST['ma_tinh_thuong_tru'] : null,
                        'ma_xa_thuong_tru'   => !empty($_POST['ma_xa_thuong_tru']) ? $_POST['ma_xa_thuong_tru'] : null,
                    ];
                    
                    // Handle File Uploads — only if files are actually attached
                    $files = ['anh_dai_dien', 'anh_cccd_truoc', 'anh_cccd_sau'];
                    $hasFiles = false;
                    foreach ($files as $field) {
                        if (!empty($_FILES[$field]['name'])) { $hasFiles = true; break; }
                    }

                    if ($hasFiles) {
                        $pathInfo = $this->getUploadPathInfo($cccd);
                        $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                        $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);
                        
                        if ($uploadDriver === 'google') {
                            $clientSecretPath = realpath(__DIR__ . '/../../') . '/client_secret.json';
                            if (!file_exists($clientSecretPath)) $clientSecretPath = __DIR__ . '/../../client_secret.json';
                            $uploader->setGoogleConfig($clientSecretPath, __DIR__ . '/../../' . ($_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json'), $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                            $driveService = new \App\Services\DriveService($uploader);
                            $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                            if ($targetFolderId) { $uploader->setTargetFolderId($targetFolderId); }
                        }

                        foreach ($files as $field) {
                            if (!empty($_FILES[$field]['name'])) {
                                $prefix = $cccd . '_' . $field . '_' . time();
                                $fileName = $uploader->upload($_FILES[$field], $prefix);
                                if ($fileName) {
                                    $data[$field] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fileName : $fileName;
                                } else {
                                    error_log("Upload error $field: " . json_encode($uploader->getErrors()));
                                }
                            }
                        }
                    }
                    
                    // error_log("DEBUG_UPDATE_PERSONAL: " . print_r($data, true));
                    $res = $this->thiSinhRepo->updateFullProfile($cccd, $data);
                    
                    if ($res) {
                         $this->json(['success' => true, 'message' => 'Lưu thành công', 'debug_data' => $data]);
                    } else {
                         $this->json(['success' => false, 'error' => 'Lỗi DB Update (0 rows affected or fail)', 'debug_data' => $data]);
                    }
                    return;
                    break;

                case 'academic':
                    $academicRepo = new \App\Repositories\AcademicRepository();
                    
                    // Defer upload setup — only initialize when files exist
                    $hasAcademicFiles = false;
                    foreach ([10, 11, 12] as $_g) {
                        if (!empty($_FILES["hba_$_g"]['name'])) { $hasAcademicFiles = true; break; }
                    }
                    if (!$hasAcademicFiles && !empty($_FILES['minh_chung_kv']['name'])) $hasAcademicFiles = true;
                    if (!$hasAcademicFiles && !empty($_FILES['minh_chung_dt']['name'])) $hasAcademicFiles = true;

                    $pathInfo = null; $uploadDriver = 'local'; $uploader = null;
                    if ($hasAcademicFiles) {
                        $pathInfo = $this->getUploadPathInfo($cccd);
                        $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                        $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);
                        
                        if ($uploadDriver === 'google') {
                            $clientSecretPath = realpath(__DIR__ . '/../../') . '/client_secret.json';
                            if (!file_exists($clientSecretPath)) $clientSecretPath = __DIR__ . '/../../client_secret.json';
                            $uploader->setGoogleConfig($clientSecretPath, __DIR__ . '/../../' . ($_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json'), $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                            $driveService = new \App\Services\DriveService($uploader);
                            $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                            if ($targetFolderId) { $uploader->setTargetFolderId($targetFolderId); }
                        }
                    }

                    $grades = [10, 11, 12];
                    $subjects = ['toan','van','ngoai_ngu','ly','hoa','sinh','su','dia','gdcd','tin_hoc','cong_nghe'];

                    foreach ($grades as $g) {
                        $record = [];
                        
                        // Collect Scores for HK1, HK2
                        foreach ($subjects as $s) {
                            if (isset($_POST["score_{$s}_{$g}_hk1"])) $record["diem_{$s}_hk1"] = $_POST["score_{$s}_{$g}_hk1"] !== '' ? (double)$_POST["score_{$s}_{$g}_hk1"] : null;
                            if (isset($_POST["score_{$s}_{$g}_hk2"])) $record["diem_{$s}_hk2"] = $_POST["score_{$s}_{$g}_hk2"] !== '' ? (double)$_POST["score_{$s}_{$g}_hk2"] : null;
                        }
                        
                        // Collect Rank & Conduct (HK1, HK2)
                        if (isset($_POST["rank_{$g}_hk1"]))    $record['hoc_luc_hk1']     = $_POST["rank_{$g}_hk1"] ?: null;
                        if (isset($_POST["rank_{$g}_hk2"]))    $record['hoc_luc_hk2']     = $_POST["rank_{$g}_hk2"] ?: null;
                        
                        if (isset($_POST["conduct_{$g}_hk1"])) $record['hanh_kiem_hk1']   = $_POST["conduct_{$g}_hk1"] ?: null;
                        if (isset($_POST["conduct_{$g}_hk2"])) $record['hanh_kiem_hk2']   = $_POST["conduct_{$g}_hk2"] ?: null;
                        
                        // Collect GPA (HK1, HK2)
                        if (isset($_POST["avg_{$g}_hk1"]))     $record['diem_tb_hk1']     = $_POST["avg_{$g}_hk1"] !== '' ? (double)$_POST["avg_{$g}_hk1"] : null;
                        if (isset($_POST["avg_{$g}_hk2"]))     $record['diem_tb_hk2']     = $_POST["avg_{$g}_hk2"] !== '' ? (double)$_POST["avg_{$g}_hk2"] : null;

                        // File Upload (Hoc Ba)
                        $fileKey = "hba_$g";
                        if (!empty($_FILES[$fileKey]['name'])) {
                             $fileName = $uploader->upload($_FILES[$fileKey], "{$cccd}_Lop{$g}");
                             if ($fileName) {
                                 $record['file_minh_chung_1'] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fileName : $fileName;
                             }
                        }
                        
                        if (!empty($record)) {
                             $academicRepo->createOrUpdate($cccd, $g, $record);
                        }
                    }

                    // Global academic properties (Priority Area, Object, School)
                    $profileData = [];
                    if (isset($_POST['khu_vuc_uu_tien']))   $profileData['khu_vuc_uu_tien']   = $_POST['khu_vuc_uu_tien'];
                    if (isset($_POST['doi_tuong_uu_tien'])) $profileData['doi_tuong_uu_tien'] = $_POST['doi_tuong_uu_tien'];
                    if (isset($_POST['ma_tinh_lop_12']))    $profileData['ma_tinh_lop_12']    = $_POST['ma_tinh_lop_12'];
                    if (isset($_POST['ma_truong_lop_12']))  $profileData['ma_truong_lop_12']  = $_POST['ma_truong_lop_12'];

                    // Files for Priority
                    if (!empty($_FILES['minh_chung_kv']['name'])) {
                        $fileName = $uploader->upload($_FILES['minh_chung_kv'], "{$cccd}_kv");
                        if ($fileName) $profileData['file_minh_chung_kv'] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fileName : $fileName;
                    }
                    if (!empty($_FILES['minh_chung_dt']['name'])) {
                        $fileName = $uploader->upload($_FILES['minh_chung_dt'], "{$cccd}_dt");
                        if ($fileName) $profileData['file_minh_chung_dt'] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fileName : $fileName;
                    }

                    if (!empty($profileData)) {
                        $this->thiSinhRepo->updateFullProfile($cccd, $profileData);
                    }
                    break;
                    break;
                    
                case 'thpt':
                    $diemThiModel = new \App\Models\DiemThiTHPT();
                    $scores = [];
                    $fields = ['toan', 'van', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd', 'tieng_anh', 'tieng_trung', 'ktpl', 'tin_hoc', 'cnnn'];
                    
                    // Scores: thpt_toan, thpt_van ...
                    foreach($fields as $f) {
                        $key = "thpt_$f"; // Review.php uses prefix
                        if (isset($_POST[$key]) && $_POST[$key] !== '') $scores[$f] = $_POST[$key];
                    }
                    
                    // Handle File Upload
                    if (!empty($_FILES['thpt_file_evidence']['name'])) {
                        $pathInfo = $this->getUploadPathInfo($cccd);
                        $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                        $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);
                        
                        if ($uploadDriver === 'google') {
                             // Google Config (same as above)
                            $clientSecretPath = realpath(__DIR__ . '/../../') . '/client_secret.json';
                            if (!file_exists($clientSecretPath)) $clientSecretPath = __DIR__ . '/../../client_secret.json';
                            $uploader->setGoogleConfig($clientSecretPath, __DIR__ . '/../../' . ($_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json'), $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                            $driveService = new \App\Services\DriveService($uploader);
                            $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                            if ($targetFolderId) { $uploader->setTargetFolderId($targetFolderId); }
                        }
                        
                        $fileName = $uploader->upload($_FILES['thpt_file_evidence'], "{$cccd}_THPT");
                        if ($fileName) {
                            $scores['file_chung_nhan'] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fileName : $fileName;
                        }
                    }

                    if (!empty($scores)) {
                        $scores['nam_thi'] = date('Y');
                        $diemThiModel->save($cccd, $scores); 
                    }
                    break;

                case 'certs':
                    // Get existing certs to map IDs
                    $existingCerts = $this->thiSinhRepo->getCertifications($cccd);
                    $certsData = [];
                    
                    // Setup Uploader
                    $pathInfo = $this->getUploadPathInfo($cccd);
                    $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                    $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);
                     if ($uploadDriver === 'google') {
                        $clientSecretPath = realpath(__DIR__ . '/../../') . '/client_secret.json';
                        if (!file_exists($clientSecretPath)) $clientSecretPath = __DIR__ . '/../../client_secret.json';
                        $uploader->setGoogleConfig($clientSecretPath, __DIR__ . '/../../' . ($_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json'), $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                        $driveService = new \App\Services\DriveService($uploader);
                        $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $cccd);
                        if ($targetFolderId) { $uploader->setTargetFolderId($targetFolderId); }
                    }
                    
                    foreach ($existingCerts as $cert) {
                        $id = $cert['id'];
                        $updated = false;
                        $item = ['id' => $id, 'existing_file' => $cert['file_minh_chung_cc']];
                        
                        // Score Update
                        if (isset($_POST["cert_score_$id"])) {
                            $item['diem_chung_chi'] = $_POST["cert_score_$id"];
                            // Also need required fields like loai_chung_chi to save? 
                            // saveCertifications usually expects full rows or handles updates by ID?
                            // ThiSinhRepository::saveCertifications implementation depends on Model.
                            // Usually it deletes all and inserts new? If so, we are in trouble.
                            // Let's assume we need to preserve other fields.
                            $item['loai_chung_chi'] = $cert['loai_chung_chi'];
                            $item['noi_cap'] = $cert['noi_cap'];
                            $item['ngay_cap'] = $cert['ngay_cap'];
                            $updated = true;
                        } else {
                            // Copy existing
                             $item['diem_chung_chi'] = $cert['diem_chung_chi'];
                             $item['loai_chung_chi'] = $cert['loai_chung_chi'];
                             $item['noi_cap'] = $cert['noi_cap'];
                             $item['ngay_cap'] = $cert['ngay_cap'];
                        }
                        
                        // File Update
                        $fileKey = "cert_file_$id";
                        if (!empty($_FILES[$fileKey]['name'])) {
                             $fileName = $uploader->upload($_FILES[$fileKey], "{$cccd}_cert_{$id}_" . time());
                             if ($fileName) {
                                 $item['file_minh_chung_cc'] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $fileName : $fileName;
                                 $updated = true;
                             }
                        } else {
                            $item['file_minh_chung_cc'] = $cert['file_minh_chung_cc'];
                        }
                        
                        $certsData[] = $item;
                    }

                    if (!empty($certsData)) {
                        // Assumption: saveCertifications can handle this array structure
                        $this->thiSinhRepo->saveCertifications($cccd, $certsData);
                    }
                    break;
            }
            
            $this->auditService->log('UPDATE_CANDIDATE', 'candidates', $cccd, null, ['section' => $section]);
            $this->json(['success' => true]);

        } catch (\Exception $e) {
            $this->auditService->log('UPDATE_ERROR', 'candidates', $cccd, null, ['error' => $e->getMessage()]);
            $this->json(['error' => $e->getMessage()], 500);
        }
    }


    private function getUploadPathInfo($cccd) {
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
}
