<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AcademicRecord;
use App\Models\ThiSinh;

class AcademicController extends Controller {

    protected $academicRecordModel;
    protected $thiSinhModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(url('/login'));
        }
        $this->academicRecordModel = new AcademicRecord();
        $this->thiSinhModel = new ThiSinh();
    }

    // Consolidated input for all 3 grades + transcript uploads
    public function step2() {
        $user = $this->thiSinhModel->findByCCCD($_SESSION['cccd']);
        $records = $this->academicRecordModel->getByCCCD($_SESSION['cccd']);
        $data = [10 => [], 11 => [], 12 => []];
        foreach ($records as $r) { $data[$r['lop']] = $r; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             $gradesInput = $_POST['grades'] ?? [];
             $this->db = \App\Core\Database::getInstance()->getConnection();
             $this->db->beginTransaction();

             try {
                // Initialize Uploader
                $pathInfo = $this->getUploadPathInfo($_SESSION['cccd']);
                $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);
                
                if ($uploadDriver === 'google') {
                    $uploader->setGoogleConfig(
                        __DIR__ . '/../../' . ($_ENV['GOOGLE_CLIENT_SECRET'] ?? 'client_secret.json'),
                        __DIR__ . '/../../' . ($_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json'),
                        $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? ''
                    );
                    
                    // Resolve folder in Drive
                    $driveService = new \App\Services\DriveService($uploader);
                    $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $_SESSION['cccd']);
                    if ($targetFolderId) { $uploader->setTargetFolderId($targetFolderId); }
                }

                $uploader->setAllowedMimes(['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png']);
                $uploader->setMaxSize(5 * 1024 * 1024);

                foreach ([10, 11, 12] as $grade) {
                    $gradeData = $gradesInput[$grade] ?? [];
                    if (empty($gradeData) && !isset($_FILES['transcripts_'.$grade])) continue; 

                    $saveData = [];
                    $semesters = ['hk1', 'hk2', 'cn'];
                    $subjects = ['toan', 'van', 'ngoai', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd', 'cong_nghe', 'tin_hoc'];
                    $summaries = ['diem_tb', 'hoc_luc', 'hanh_kiem'];
                    if (isset($gradeData['nam_hoc'])) $saveData['nam_hoc'] = $gradeData['nam_hoc'];

                    foreach ($semesters as $sem) {
                        if (!isset($gradeData[$sem])) continue;
                        foreach ($subjects as $sub) {
                             $val = $gradeData[$sem][$sub] ?? null;
                             if ($val !== '' && $val !== null) {
                                 if (!is_numeric($val) || $val < 0 || $val > 10) throw new \Exception("Điểm không hợp lệ lớp $grade kỳ $sem môn $sub");
                                 $dbCol = ($sub === 'ngoai') ? "diem_ngoai_ngu_$sem" : "diem_{$sub}_{$sem}";
                                 $saveData[$dbCol] = $val;
                             }
                        }
                        foreach ($summaries as $sum) {
                            $val = $gradeData[$sem][$sum] ?? null;
                             if ($val !== '' && $val !== null) {
                                 $dbCol = ($sem === 'cn') ? "{$sum}_ca_nam" : "{$sum}_{$sem}";
                                 $saveData[$dbCol] = $val;
                             }
                        }
                    }

                    // Handle Uploads for this grade (max 2 files)
                    if (isset($_FILES['transcripts_'.$grade])) {
                        foreach ($_FILES['transcripts_'.$grade]['name'] as $i => $name) {
                            if ($_FILES['transcripts_'.$grade]['error'][$i] === UPLOAD_ERR_OK) {
                                $fileObj = [
                                    'name' => $_FILES['transcripts_'.$grade]['name'][$i],
                                    'type' => $_FILES['transcripts_'.$grade]['type'][$i],
                                    'tmp_name' => $_FILES['transcripts_'.$grade]['tmp_name'][$i],
                                    'error' => $_FILES['transcripts_'.$grade]['error'][$i],
                                    'size' => $_FILES['transcripts_'.$grade]['size'][$i]
                                ];
                                $fileName = $_SESSION['cccd'] . "_Lop{$grade}_" . ($i+1);
                                $result = $uploader->upload($fileObj, $fileName);
                                if ($result) {
                                    $col = "file_minh_chung_" . ($i+1);
                                    $saveData[$col] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $result : $result;
                                }
                            }
                        }
                    }

                    if (!empty($saveData)) {
                        $this->academicRecordModel->save($_SESSION['cccd'], $grade, $saveData);
                    }
                }
                
                $daDu6Ky = isset($_POST['da_du_6_ky']) && $_POST['da_du_6_ky'] == '1';
                $this->thiSinhModel->updateHocBaStatus($_SESSION['cccd'], $daDu6Ky);

                $this->db->commit();
                $this->redirect(url('/profile/step3')); // Redirect to step 3
                return;

             } catch (\Exception $e) {
                 if (isset($this->db)) $this->db->rollBack();
                 $this->view('profile/step2', [
                    'user' => $user, 
                    'records' => $data, 
                    'error' => $e->getMessage()
                ]);
             }
        } else {
            $this->view('profile/step2', ['user' => $user, 'records' => $data]);
        }
    }

    // Step 4: International Certifications
    public function step3() {
        $user = $this->thiSinhModel->findByCCCD($_SESSION['cccd']);
        $existingCerts = $this->thiSinhModel->getCertifications($_SESSION['cccd']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $hasCert = isset($_POST['has_cert']) && $_POST['has_cert'] == '1';
            $certInputs = $_POST['certs'] ?? [];
            $finalCerts = [];

            if ($hasCert && !empty($certInputs)) {
                $pathInfo = $this->getUploadPathInfo($_SESSION['cccd']);
                $uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'local';
                $uploader = new \App\Core\FileUploader($pathInfo['absolute'], $uploadDriver);
                
                if ($uploadDriver === 'google') {
                    $uploader->setGoogleConfig(
                        __DIR__ . '/../../' . ($_ENV['GOOGLE_CLIENT_SECRET'] ?? 'client_secret.json'),
                        __DIR__ . '/../../' . ($_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json'),
                        $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? ''
                    );
                    
                    // Resolve folder in Drive
                    $driveService = new \App\Services\DriveService($uploader);
                    $targetFolderId = $driveService->resolveCandidateFolder($pathInfo['year'], $pathInfo['session'], $_SESSION['cccd']);
                    if ($targetFolderId) { $uploader->setTargetFolderId($targetFolderId); }
                }

                $uploader->setAllowedMimes(['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png']);
                $uploader->setMaxSize(5 * 1024 * 1024);

                foreach ($certInputs as $index => $input) {
                    if (empty($input['type'])) continue;

                    $certData = [
                        'loai_chung_chi' => $input['type'],
                        'diem_chung_chi' => $input['score'],
                        'file_minh_chung_cc' => $input['existing_file'] ?? null
                    ];

                    // Check for new upload for this specifically indexed cert
                    if (isset($_FILES['cert_files']['name'][$index]) && $_FILES['cert_files']['error'][$index] === UPLOAD_ERR_OK) {
                        $fileData = [
                            'name' => $_FILES['cert_files']['name'][$index],
                            'type' => $_FILES['cert_files']['type'][$index],
                            'tmp_name' => $_FILES['cert_files']['tmp_name'][$index],
                            'error' => $_FILES['cert_files']['error'][$index],
                            'size' => $_FILES['cert_files']['size'][$index]
                        ];
                        
                        $result = $uploader->upload($fileData, $_SESSION['cccd'] . "_Cert_" . ($index + 1));
                        if ($result) {
                            $certData['file_minh_chung_cc'] = ($uploadDriver === 'local') ? $pathInfo['relative'] . '/' . $result : $result;
                        }
                    }
                    $finalCerts[] = $certData;
                }
            }

            if ($this->thiSinhModel->saveCertifications($_SESSION['cccd'], $finalCerts)) {
                $masterData = new \App\Models\MasterData();
                $enableTHPT = $masterData->getSetting('enable_thpt_step');
                
                if ($enableTHPT == '1') {
                    $this->redirect(url('/profile/step4'));
                } else {
                    $this->redirect(url('/profile/step5'));
                }
                return;
            } else {
                $this->view('profile/step3', [
                    'user' => $user, 
                    'certs' => $existingCerts,
                    'error' => 'Lỗi lưu dữ liệu certification.'
                ]);
            }
        } else {
            $this->view('profile/step3', [
                'user' => $user,
                'certs' => $existingCerts
            ]);
        }
    }

    private function getUploadPathInfo($cccd) {
        $sessionModel = new \App\Models\AdmissionSession();
        $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestActiveSession();
    
        $year = date('Y');
        $sessionName = 'Dot1';
    
        if ($activeSession) {
            $year = $activeSession['nam_tuyen_sinh'] ?? date('Y');
            $sessionName = $activeSession['ma_dot'] ?? ('Dot_' . ($activeSession['id'] ?? '1'));
            // Slugify
            $sessionName = preg_replace('/[^A-Za-z0-9_\\-]/', '_', $sessionName);
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
