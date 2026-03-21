<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ThiSinhRepository;
use App\Core\FileUploader;

class ProfileController extends Controller
{

    protected $thiSinhRepo;
    protected $user;

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(url('/login'));
        }

        $this->thiSinhRepo = new ThiSinhRepository(); // Use Repository

        // Cache user in session (optimization)
        if (!isset($_SESSION['user_cached']) || $_SESSION['user_cached_cccd'] !== $_SESSION['cccd']) {
            $this->user = $this->thiSinhRepo->findByCCCD($_SESSION['cccd']);
            $_SESSION['user_cached'] = $this->user;
            $_SESSION['user_cached_cccd'] = $_SESSION['cccd'];
        } else {
            $this->user = $_SESSION['user_cached'];
        }
    }

    /**
     * Invalidate user cache (call after profile updates)
     */
    private function invalidateUserCache()
    {
        unset($_SESSION['user_cached']);
        unset($_SESSION['user_cached_cccd']);
        unset($_SESSION['user_avatar_cached']); // Invalidate avatar cache (used in header.php)
        // Reload user data
        $this->user = $this->thiSinhRepo->findByCCCD($_SESSION['cccd']);
        $_SESSION['user_cached'] = $this->user;
        $_SESSION['user_cached_cccd'] = $_SESSION['cccd'];
    }

    private function getApplicationStatus()
    {
        // Simple 30-second TTL cache for application status to reduce DB load
        // Because status is unlikely to change exactly while user is filling forms step by step
        $cacheKey = 'app_status_' . $_SESSION['cccd'];
        $cacheTimeKey = $cacheKey . '_time';
        $ttl = 30; // 30 seconds

        if (isset($_SESSION[$cacheKey]) && isset($_SESSION[$cacheTimeKey]) && (time() - $_SESSION[$cacheTimeKey]) < $ttl) {
            return $_SESSION[$cacheKey];
        }

        $applicationModel = new \App\Models\Application();
        // Determine active session or latest
        $sessionModel = new \App\Models\AdmissionSession();
        $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestActiveSession();

        $status = '';
        $isLocked = false;
        $editRequestPending = false;

        if ($activeSession) {
            $app = $applicationModel->findByCCCDAndSession($_SESSION['cccd'], $activeSession['id']);
            if ($app) {
                $status = $app->trang_thai ?? '';
                $isLocked = ($status === 'Đã duyệt');
                $editRequestPending = !empty($app->yeu_cau_chinh_sua);
            }
        }

        $result = [
            'status' => $status,
            'isLocked' => $isLocked,
            'editRequestPending' => $editRequestPending
        ];

        $_SESSION[$cacheKey] = $result;
        $_SESSION[$cacheTimeKey] = time();

        return $result;
    }

    private function getUploadPathInfo($cccd)
    {
        $sessionModel = new \App\Models\AdmissionSession();
        $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestActiveSession();

        $year = date('Y');
        $sessionName = 'Dot1';

        if ($activeSession) {
            $year = $activeSession['nam_tuyen_sinh'] ?? date('Y');
            $sessionName = $activeSession['ma_dot'] ?? ('Dot_' . ($activeSession['id'] ?? '1'));
            // Slugify
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

    public function index()
    {
        $this->redirect(url('/profile/step1'));
    }

    public function step1()
    {
        $appStatus = $this->getApplicationStatus();
        $isLocked = $appStatus['isLocked'];

        $masterData = new \App\Models\MasterData();
        $provinces = $masterData->getProvinces();
        $priorityAreas = $masterData->getPriorityAreas();
        $priorityObjects = $masterData->getPriorityObjects();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Block POST if locked
            if ($isLocked) {
                $this->view('application/error', ['error' => 'Hồ sơ đã được duyệt. Bạn không thể chỉnh sửa.']);
                return;
            }

            // Validation logic
            $validator = new \App\Core\Validator($_POST);
            $rules = [
                'fullname' => 'required|min:3',
                'dob' => 'required',
                'gender' => 'required',
                'phone' => 'required|numeric',
                'email' => 'required|email',
                'ma_tinh_ho_khau' => 'required',
                'ma_tinh_lop_12' => 'required',
                'ma_truong_lop_12' => 'required',
                'ma_tinh_thuong_tru' => 'required',
                'ma_xa_thuong_tru' => 'required',
                'address' => 'required'
            ];

            if (!$validator->validate($rules)) {
                $this->view('profile/step1', [
                    'user' => $this->user,
                    'provinces' => $provinces,
                    'priorityAreas' => $priorityAreas,
                    'priorityObjects' => $priorityObjects,
                    'error' => $validator->getFirstError(),
                    'old' => $_POST,
                    'isLocked' => $isLocked,
                    'editRequestPending' => $appStatus['editRequestPending'],
                    'applicationStatus' => $appStatus['status']
                ]);
                return;
            }

            // 1. Prepare Data for updateFullProfile
            $data = [
                'ho_va_ten' => normalize_name($_POST['fullname']),
                'ngay_sinh' => $_POST['dob'],
                'gioi_tinh' => $_POST['gender'],
                'dan_toc' => $_POST['ethnic'],
                'khu_vuc_uu_tien' => $_POST['kv_uu_tien'],
                'doi_tuong_uu_tien' => $_POST['dt_uu_tien'],
                'dien_thoai' => trim($_POST['phone']),
                'email' => trim($_POST['email']),
                'ma_tinh_ho_khau' => $_POST['ma_tinh_ho_khau'],
                'ma_tinh_lop_12' => $_POST['ma_tinh_lop_12'],
                'ma_truong_lop_12' => $_POST['ma_truong_lop_12'],
                'nam_tot_nghiep' => $_POST['nam_tot_nghiep'],
                'ma_tinh_thuong_tru' => $_POST['ma_tinh_thuong_tru'],
                'ma_xa_thuong_tru' => $_POST['ma_xa_thuong_tru'],
                'dia_chi_chi_tiet' => trim($_POST['address']),
                'is_custom_kv' => isset($_POST['is_custom_kv']) && $_POST['is_custom_kv'] == '1',
                'is_custom_dt' => isset($_POST['is_custom_dt']) && $_POST['is_custom_dt'] == '1'
            ];

            if (isset($_FILES) && !empty($_FILES)) {
                $pathInfo = $this->getUploadPathInfo($_SESSION['cccd']);
                $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';

                $uploader = new FileUploader($pathInfo['absolute'], $uploadDriver);

                $driveInitialized = false;

                $uploader->setAllowedMimes(['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png']);
                $uploader->setMaxSize(2 * 1024 * 1024);

                $files = [
                    'avatar' => 'anh_dai_dien',
                    'cccd_front' => 'anh_cccd_truoc',
                    'cccd_back' => 'anh_cccd_sau',
                    'kv_file' => 'file_minh_chung_kv',
                    'dt_file' => 'file_minh_chung_dt'
                ];
                $paths = [];

                foreach ($files as $field => $dbColumn) {
                    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                        
                        // Initialize Google Drive ON DEMAND (only if a file is actually uploaded)
                        if ($uploadDriver === 'google' && !$driveInitialized) {
                            $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                            $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
                            $folderId = $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '';
                            $uploader->setGoogleConfig($clientSecretPath, $tokenPath, $folderId);

                            // Resolve folder in Drive
                            $driveService = new \App\Services\DriveService($uploader);
                            $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $_SESSION['cccd']);
                            if ($targetFolderId) {
                                $uploader->setTargetFolderId($targetFolderId);
                            }
                            $driveInitialized = true;
                        }

                        $uploader->clearErrors(); // Clear errors before each upload to avoid repetition
                        $fileName = $_SESSION['cccd'] . '_' . ($field === 'kv_file' ? 'kv_evidence' : ($field === 'dt_file' ? 'dt_evidence' : $field));
                        $result = $uploader->upload($_FILES[$field], $fileName);
                        if ($result) {
                            $paths[$dbColumn] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $result : $result;
                        } else {
                            error_log("Upload failed for field '$field': " . implode(', ', $uploader->getErrors()));
                            $_SESSION['upload_error'] = "Lỗi tải lên $field: " . implode(', ', $uploader->getErrors());
                        }
                    } elseif (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                        error_log("Upload error code for '$field': " . $_FILES[$field]['error']);
                    }
                }

                $data = array_merge($data, $paths);
            }

            if ($this->thiSinhRepo->updateFullProfile($_SESSION['cccd'], $data)) {
                if (isset($_SESSION['upload_error'])) {
                    $errorMsg = $_SESSION['upload_error'];
                    unset($_SESSION['upload_error']);
                    $this->view('profile/step1', [
                        'user' => $this->user,
                        'provinces' => $provinces,
                        'priorityAreas' => $priorityAreas,
                        'priorityObjects' => $priorityObjects,
                        'error' => $errorMsg,
                        'isLocked' => $isLocked,
                        'editRequestPending' => $appStatus['editRequestPending'],
                        'applicationStatus' => $appStatus['status']
                    ]);
                    return;
                }
                $this->invalidateUserCache();
                $this->redirect(url('/profile/step2'));
            } else {
                $errorMsg = $_SESSION['upload_error'] ?? 'Lỗi lưu thông tin.';
                unset($_SESSION['upload_error']);
                $this->view('profile/step1', [
                    'user' => $this->user,
                    'provinces' => $provinces,
                    'priorityAreas' => $priorityAreas,
                    'priorityObjects' => $priorityObjects,
                    'error' => $errorMsg,
                    'isLocked' => $isLocked,
                    'editRequestPending' => $appStatus['editRequestPending'],
                    'applicationStatus' => $appStatus['status']
                ]);
            }
        } else {
            $this->view('profile/step1', [
                'user' => $this->user,
                'provinces' => $provinces,
                'priorityAreas' => $priorityAreas,
                'priorityObjects' => $priorityObjects,
                'isLocked' => $isLocked,
                'editRequestPending' => $appStatus['editRequestPending'],
                'applicationStatus' => $appStatus['status']
            ]);
        }
    }

    public function step2()
    {
        $appStatus = $this->getApplicationStatus();
        $isLocked = $appStatus['isLocked'];

        $academicModel = new \App\Models\AcademicRecord();
        $masterData = new \App\Models\MasterData();

        $records = $academicModel->getByCCCDIndexed($_SESSION['cccd']);
        $subjects = $masterData->getSubjects('Mon_hoc_ba');

        // Custom sort subjects
        $subjectOrder = [
            'Ngữ văn' => 1,
            'Toán' => 2,
            'Lịch sử' => 3,
            'Địa lí' => 4,
            'Địa lý' => 4,
            'Giáo dục kinh tế và pháp luật' => 5,
            'GDKT & PL' => 5,
            'GD KT&PL' => 5,
            'Vật lí' => 6,
            'Vật lý' => 6,
            'Hóa học' => 7,
            'Sinh học' => 8,
            'Công nghệ' => 9,
            'Tin học' => 10,
            'Ngoại ngữ' => 11,
            'Tiếng Anh' => 11
        ];

        usort($subjects, function ($a, $b) use ($subjectOrder) {
            $nameA = trim($a['ten_mon'] ?? '');
            $nameB = trim($b['ten_mon'] ?? '');
            $orderA = $subjectOrder[$nameA] ?? 99;
            $orderB = $subjectOrder[$nameB] ?? 99;
            if ($orderA === $orderB) {
                return strcmp($nameA, $nameB);
            }
            return $orderA <=> $orderB;
        });

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($isLocked) {
                $this->view('application/error', ['error' => 'Hồ sơ đã được duyệt. Bạn không thể chỉnh sửa.']);
                return;
            }

            $items = $_POST['records'] ?? [];

            // Handle File Uploads (Transcripts)
            $hasFiles = false;
            foreach ([10, 11, 12] as $g) {
                if (isset($_FILES["transcripts_$g"]) && !empty($_FILES["transcripts_$g"]['name'][0])) {
                    $hasFiles = true;
                    break;
                }
            }

            if ($hasFiles) {
                $pathInfo = $this->getUploadPathInfo($_SESSION['cccd']);
                $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                $uploader = new FileUploader($pathInfo['absolute'], $uploadDriver);

                if ($uploadDriver === 'google') {
                    $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                    $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
                    $uploader->setGoogleConfig($clientSecretPath, $tokenPath, $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                    $driveService = new \App\Services\DriveService($uploader);
                    $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $_SESSION['cccd']);
                    if ($targetFolderId) {
                        $uploader->setTargetFolderId($targetFolderId);
                    }
                }

                $uploader->setAllowedMimes(['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png']);
                $uploader->setMaxSize(5 * 1024 * 1024); // 5MB

                foreach ([10, 11, 12] as $g) {
                    if (isset($_FILES["transcripts_$g"]) && !empty($_FILES["transcripts_$g"]['name'][0])) {
                        $uploadedPaths = [];
                        foreach ($_FILES["transcripts_$g"]['name'] as $i => $name) {
                            if (empty($name)) continue;

                            $file = [
                                'name' => $_FILES["transcripts_$g"]['name'][$i],
                                'type' => $_FILES["transcripts_$g"]['type'][$i],
                                'tmp_name' => $_FILES["transcripts_$g"]['tmp_name'][$i],
                                'error' => $_FILES["transcripts_$g"]['error'][$i],
                                'size' => $_FILES["transcripts_$g"]['size'][$i]
                            ];

                            $prefix = $_SESSION['cccd'] . "_transcript_grade{$g}_" . time() . "_" . $i;
                            $result = $uploader->upload($file, $prefix);
                            if ($result) {
                                $uploadedPaths[] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $result : $result;
                            }
                        }

                        if (!empty($uploadedPaths)) {
                            $items[$g]['file_hoc_ba'] = implode(',', $uploadedPaths);
                        }
                    }
                }
            }

            if ($academicModel->saveBatch($_SESSION['cccd'], $items)) {
                $daDu6Ky = isset($_POST['da_du_6_ky']) && $_POST['da_du_6_ky'] == '1';
                $this->thiSinhRepo->updateHocBaStatus($_SESSION['cccd'], $daDu6Ky);
                $this->invalidateUserCache();

                $this->redirect(url('/profile/step3'));
            } else {
                $this->view('profile/step2', [
                    'user' => $this->user,
                    'records' => $records,
                    'subjects' => $subjects,
                    'error' => 'Lỗi lưu học bạ.',
                    'isLocked' => $isLocked,
                    'editRequestPending' => $appStatus['editRequestPending'],
                    'applicationStatus' => $appStatus['status']
                ]);
            }
        } else {
            $this->view('profile/step2', [
                'user' => $this->user,
                'records' => $records,
                'subjects' => $subjects,
                'isLocked' => $isLocked,
                'editRequestPending' => $appStatus['editRequestPending'],
                'applicationStatus' => $appStatus['status']
            ]);
        }
    }

    public function step3()
    {
        $appStatus = $this->getApplicationStatus();
        $isLocked = $appStatus['isLocked'];

        $certs = $this->thiSinhRepo->getCertifications($_SESSION['cccd']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($isLocked) {
                $this->view('application/error', ['error' => 'Hồ sơ đã được duyệt. Bạn không thể chỉnh sửa.']);
                return;
            }

            $hasCert = isset($_POST['has_cert']) && $_POST['has_cert'] == '1';
            // updateCertStatus also needs to be in Repo
            $this->thiSinhRepo->updateCertStatus($_SESSION['cccd'], $hasCert);

            if ($hasCert) {
                $data = $_POST['certs'] ?? [];

                // Handle Files for Certs
                if (isset($_FILES['cert_files']) && !empty($_FILES['cert_files']['name'])) {
                    $pathInfo = $this->getUploadPathInfo($_SESSION['cccd']);
                    $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                    $uploader = new FileUploader($pathInfo['absolute'], $uploadDriver);

                    // Google Config (Reuse if needed, brevity here)
                    if ($uploadDriver === 'google') {
                        $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                        $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
                        $uploader->setGoogleConfig($clientSecretPath, $tokenPath, $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                        // Resolve folder
                        $driveService = new \App\Services\DriveService($uploader);
                        $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $_SESSION['cccd']);
                        if ($targetFolderId) {
                            $uploader->setTargetFolderId($targetFolderId);
                        }
                    }

                    $uploader->setAllowedMimes(['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png']);

                    foreach ($data as $index => &$certItem) {
                        // Check if file uploaded for this index
                        if (isset($_FILES['cert_files']['error'][$index]) && $_FILES['cert_files']['error'][$index] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['cert_files']['name'][$index],
                                'type' => $_FILES['cert_files']['type'][$index],
                                'tmp_name' => $_FILES['cert_files']['tmp_name'][$index],
                                'error' => $_FILES['cert_files']['error'][$index],
                                'size' => $_FILES['cert_files']['size'][$index]
                            ];

                            $fileName = $_SESSION['cccd'] . '_cert_' . $index . '_' . time();
                            $result = $uploader->upload($file, $fileName);
                            if ($result) {
                                $certItem['file_minh_chung_cc'] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $result : $result;
                            }
                        } else {
                            // Use existing if set
                            $certItem['file_minh_chung_cc'] = $certItem['existing_file'] ?? null;
                        }
                    }
                } else {
                    // No new files, keep existing
                    foreach ($data as &$certItem) {
                        $certItem['file_minh_chung_cc'] = $certItem['existing_file'] ?? null;
                    }
                }

                $this->thiSinhRepo->saveCertifications($_SESSION['cccd'], $data);
            }

            $this->invalidateUserCache();
            $this->redirect(url('/profile/step4'));
        } else {
            $this->view('profile/step3', [
                'user' => $this->user,
                'certs' => $certs,
                'isLocked' => $isLocked,
                'editRequestPending' => $appStatus['editRequestPending'],
                'applicationStatus' => $appStatus['status']
            ]);
        }
    }

    public function step4()
    {
        $appStatus = $this->getApplicationStatus();
        $isLocked = $appStatus['isLocked'];

        $masterData = new \App\Models\MasterData();
        $enableTHPT = $masterData->getSetting('enable_thpt_step');
        if ($enableTHPT != '1') {
            $this->redirect(url('/profile/step5'));
            return;
        }

        $thptModel = new \App\Models\DiemThiTHPT();
        $scores = $thptModel->getByCCCD($_SESSION['cccd']);
        $subjects = $masterData->getSubjects('Mon_thi_THPT');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($isLocked) {
                $this->view('application/error', ['error' => 'Hồ sơ đã được duyệt. Bạn không thể chỉnh sửa.']);
                return;
            }

            $data = [
                'da_co_diem' => (int)((isset($_POST['has_scores']) && $_POST['has_scores'] == '1') ? 1 : 0),
                'nam_thi' => 2026
            ];

            if ($data['da_co_diem']) {
                $subjects = ['toan', 'van', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd', 'tieng_anh', 'tieng_trung', 'ktpl', 'tin_hoc', 'cnnn'];
                foreach ($subjects as $s) {
                    $data[$s] = !empty($_POST[$s]) ? (float)$_POST[$s] : null;
                }

                // Handle File Upload
                if (isset($_FILES['file_chung_nhan']) && $_FILES['file_chung_nhan']['error'] === UPLOAD_ERR_OK) {
                    $pathInfo = $this->getUploadPathInfo($_SESSION['cccd']);
                    $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                    $uploader = new FileUploader($pathInfo['absolute'], $uploadDriver);

                    if ($uploadDriver === 'google') {
                        $clientSecretPath = self::resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
                        $tokenPath = self::resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
                        $uploader->setGoogleConfig($clientSecretPath, $tokenPath, $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '');
                        // Resolve folder
                        $driveService = new \App\Services\DriveService($uploader);
                        $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $_SESSION['cccd']);
                        if ($targetFolderId) {
                            $uploader->setTargetFolderId($targetFolderId);
                        }
                    }

                    $uploader->setAllowedMimes(['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png']);
                    $uploader->setMaxSize(2 * 1024 * 1024);

                    $fileName = $_SESSION['cccd'] . '_diem_thi_thpt';
                    $result = $uploader->upload($_FILES['file_chung_nhan'], $fileName);
                    if ($result) {
                        $data['file_chung_nhan'] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $result : $result;
                    }
                } else if (isset($scores['file_chung_nhan'])) {
                    // Keep old file if not uploading new one
                    $data['file_chung_nhan'] = $scores['file_chung_nhan'];
                }
            } else {
                // Reset scores if they choose "No scores yet"
                $subjects = ['toan', 'van', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd', 'tieng_anh', 'tieng_trung', 'ktpl', 'tin_hoc', 'cnnn'];
                foreach ($subjects as $s) $data[$s] = null;
                $data['file_chung_nhan'] = null; // Clear file if they say no scores
            }

            if ($thptModel->save($_SESSION['cccd'], $data)) {
                $this->redirect(url('/profile/step5'));
            } else {
                $this->view('profile/step4', [
                    'user' => $this->user,
                    'scores' => $scores,
                    'subjects' => $subjects, // Add missing subjects var logic 
                    'error' => 'Lỗi lưu thông tin.',
                    'isLocked' => $isLocked,
                    'editRequestPending' => $appStatus['editRequestPending'],
                    'applicationStatus' => $appStatus['status']
                ]);
            }
        } else {
            $this->view('profile/step4', [
                'user' => $this->user,
                'scores' => $scores,
                'subjects' => $subjects, // Add missing subjects var logic
                'isLocked' => $isLocked,
                'editRequestPending' => $appStatus['editRequestPending'],
                'applicationStatus' => $appStatus['status']
            ]);
        }
    }

    public function changePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $this->view('profile/change_password', ['user' => $this->user, 'error' => 'Vui lòng điền đầy đủ thông tin.']);
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $this->view('profile/change_password', ['user' => $this->user, 'error' => 'Mật khẩu mới không khớp.']);
                return;
            }

            if (strlen($newPassword) < 6) {
                $this->view('profile/change_password', ['user' => $this->user, 'error' => 'Mật khẩu mới phải có ít nhất 6 ký tự.']);
                return;
            }

            // Verify current password
            if (!password_verify($currentPassword, $this->user['mat_khau'])) {
                $this->view('profile/change_password', ['user' => $this->user, 'error' => 'Mật khẩu hiện tại không chính xác.']);
                return;
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($this->thiSinhRepo->updatePasswordByCCCD($_SESSION['cccd'], $hashedPassword)) {
                $this->invalidateUserCache();
                $this->view('profile/change_password', ['user' => $this->user, 'success' => 'Đổi mật khẩu thành công.']);
            } else {
                $this->view('profile/change_password', ['user' => $this->user, 'error' => 'Có lỗi xảy ra, vui lòng thử lại sau.']);
            }
        } else {
            $this->view('profile/change_password', ['user' => $this->user]);
        }
    }
}
