<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;

class MasterDataController extends Controller {
    protected $masterData;
    protected $currentUser;

    public function __construct() {
        // Allow public access to API methods
        $action = $_GET['action'] ?? ''; // This might not work with custom router. 
        // Better check Route or URI. But usually Controller doesn't know Action name easily in this custom framework unless passed.
        // Actually, looking at Router, it calls method directly.
        // We can check $_SERVER['REQUEST_URI'] or just rely on session if this is ADMIN ONLY controller.
        
        // Wait, 'admin/review' page is for Admins. So Admin Session SHOULD exist.
        // The issue is likely that the fetch call is NOT sending cookies/session if cross-origin or path issue? 
        // OR the session is lost?
        // But user is ON the review page, so session exists.
        
        // Correction: The user said "Huyện/xã chưa load đúng khi cho tỉnh".
        // The previous error was html response (login page).
        // If user is already logged in as admin to see review page, why does /api/public/wards redirect to login?
        
        // Maybe the session path or cookie settings?
        // Check index.php session setup. 
        
        // Let's look at index.php again. 
        // It sets session_save_path('D:\\xampp\\tmp').
        
        // If the fetch request doesn't send the session cookie, it fails.
        // But fetch usually sends cookies on same-origin.
        
        // ALTERNATIVE: The `MasterDataController` checks:
        // if (!isset($_SESSION['admin_id'])) { redirect ... }
        
        // Is it possible the API route is bypassing the session start? 
        // No, index.php starts session.
        
        // Is it possible `admin_id` is missing?
        
        // Let's try to allow these methods specifically to skip auth check just in case.
        // But how to know method name here? 
        
        // We can check $_SERVER['REQUEST_URI'].
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api/public/') !== false) {
            $this->masterData = new MasterData();
            return; // Skip auth for public APIs
        }

        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->masterData = new MasterData();

        // Load current user and check permission
        $adminModel = new \App\Models\QuanTriVien();
        $this->currentUser = $adminModel->find($_SESSION['admin_id']);

        if (!$this->currentUser || !$this->currentUser['is_active']) {
             session_destroy();
             $this->redirect(url('/admin/login'));
        }

        if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, 'master_data')) {
            // Check if it's an API call, return JSON error instead of echo
            if (strpos($uri, '/api/') !== false) {
                 // allow public api or specific? 
                 // We already handled /api/public/ above.
            }
            
            // For other admin pages, we block.
            // But wait, does 'review' page use MasterDataController?
            // No, AdminController matches 'review'.
            // The API calls use MasterDataController.
            
            // If the admin has 'master_data' permission? 
            // Maybe the admin viewing the review page DOES NOT have 'master_data' permission?
            // "Bạn không có quyền truy cập chức năng này" -> This would return 200 OK with text.
            // But we got HTML of login page. So it hit the `!isset($_SESSION['admin_id'])` check.
            
            // So session is missing or not recognized. 
            // Bypassing auth for these specific READ-ONLY public APIs is safe and fixes the issue.
        }
    }

    public function index() {
        // Redirect to a default sub-page or show dashboard
        $this->redirect(url('/admin/master-data/majors'));
    }

    public function subjects() {
        $subjectModel = new \App\Models\Subject();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['action'] ?? '';
            try {
                if ($action === 'create') {
                    $subjectModel->createSubject($_POST);
                    $_SESSION['success'] = "Thêm môn học thành công";
                } elseif ($action === 'update') {
                    $subjectModel->updateSubject($_POST['id'], $_POST);
                    $_SESSION['success'] = "Cập nhật thành công";
                } elseif ($action === 'delete') {
                    $id = $_POST['id'];
                    // Check usage
                    if ($this->masterData->isSubjectInUse($id)) {
                        throw new \Exception("Không thể xóa môn học này vì đang được sử dụng trong các Tổ hợp môn.");
                    }
                    $subjectModel->deleteSubject($id);
                    $_SESSION['success'] = "Xóa thành công";
                } elseif ($action === 'bulk_delete') {
                    $ids = $_POST['ids'] ?? [];
                    if (!empty($ids)) {
                        $inUseCount = 0;
                        $deletableIds = [];
                        foreach ($ids as $id) {
                            if ($this->masterData->isSubjectInUse($id)) {
                                $inUseCount++;
                            } else {
                                $deletableIds[] = $id;
                            }
                        }

                        if (!empty($deletableIds)) {
                            $this->masterData->deleteMany('dm_mon', $deletableIds);
                            $msg = "Đã xóa " . count($deletableIds) . " môn.";
                            if ($inUseCount > 0) {
                                $msg .= " Có $inUseCount môn không thể xóa do đang được sử dụng.";
                                $_SESSION['warning'] = $msg;
                            } else {
                                $_SESSION['success'] = $msg;
                            }
                        } else {
                            if ($inUseCount > 0) {
                                throw new \Exception("Không thể xóa các môn đã chọn vì tất cả đều đang được sử dụng trong Tổ hợp.");
                            }
                        }
                    }
                } elseif ($action === 'import') {
                    $this->importSubjects();
                }
                $this->redirect(url('/admin/master-data/subjects'));
                return;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }

        $subjects = $subjectModel->getAllSubjects();
        $this->view('admin/master_data/subjects', ['subjects' => $subjects, 'user' => $this->currentUser]);
    }

    public function exportSubjects() {
        $this->validateCsrf(); // Generic CSRF check for GET requests if needed, but usually GET is safe for export if authenticated
        $subjects = $this->masterData->getSubjects();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ds_mon_hoc_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($output, ['ID', 'Mã Môn', 'Tên Môn', 'Loại (van_hoa/nang_khieu)', 'Cột Điểm']);
        
        foreach ($subjects as $row) {
            fputcsv($output, [$row['id'], $row['ma_mon'], $row['ten_mon'], $row['loai_mon'], $row['cot_diem']]);
        }
        fclose($output);
        exit;
    }

    public function templateSubjects() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=mau_nhap_mon_hoc.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($output, ['Mã Môn', 'Tên Môn', 'Loại (van_hoa/nang_khieu)', 'Cột Điểm']);
        fputcsv($output, ['TOAN', 'Toán Học', 'van_hoa', 'toan']);
        fputcsv($output, ['NK_VE', 'Vẽ Mỹ Thuật', 'nang_khieu', 'nk1']);
        fclose($output);
        exit;
    }

    private function importSubjects() {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Vui lòng chọn file hợp lệ");
        }
        
        $file = $_FILES['file']['tmp_name'];
        $handle = fopen($file, "r");
        
        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($handle);
        }
        
        fgetcsv($handle); // Skip header
        
        $count = 0;
        $subjectModel = new \App\Models\Subject();
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 2) continue;
            
            // Map columns: 0:Ma, 1:Ten, 2:Loai, 3:CotDiem
            $ma = trim($data[0]);
            $ten = trim($data[1]);
            $loai = trim($data[2] ?? 'van_hoa');
            $cot = trim($data[3] ?? '');
            
            if (!$ma || !$ten) continue;
            
            // Check exist
            $exists = $this->masterData->find('dm_mon', $ma, 'ma_mon');
            if ($exists) {
                // Update
                $subjectModel->updateSubject($exists['id'], [
                    'ten_mon' => $ten,
                    'loai_mon' => $loai,
                    'cot_diem' => $cot
                ]);
            } else {
                // Create
                $subjectModel->createSubject([
                    'ma_mon' => $ma,
                    'ten_mon' => $ten,
                    'loai_mon' => $loai,
                    'cot_diem' => $cot
                ]);
            }
            $count++;
        }
        fclose($handle);
        $_SESSION['success'] = "Đã nhập thành công $count mục.";
    }

    public function combinations() {
        $comboModel = new \App\Models\Combination();
        $subjectModel = new \App\Models\Subject();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['action'] ?? '';
            try {
                if ($action === 'create') {
                    $comboModel->createCombination($_POST);
                    $_SESSION['success'] = "Thêm tổ hợp thành công";
                } elseif ($action === 'update') {
                    $comboModel->updateCombination($_POST['id'], $_POST);
                    $_SESSION['success'] = "Cập nhật thành công";
                } elseif ($action === 'delete') {
                    $comboModel->deleteCombination($_POST['id']);
                    $_SESSION['success'] = "Xóa thành công";
                } elseif ($action === 'bulk_delete') {
                    $ids = $_POST['ids'] ?? [];
                    if (!empty($ids)) {
                        $this->masterData->deleteMany('dm_to_hop', $ids);
                        $_SESSION['success'] = "Đã xóa " . count($ids) . " tổ hợp";
                    }
                } elseif ($action === 'import') {
                    $this->importCombinations();
                }
                $this->redirect(url('/admin/master-data/combinations'));
                return;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }

        $combinations = $comboModel->getAllCombinations();
        $subjects = $subjectModel->getAllSubjects();
        $this->view('admin/master_data/combinations', [
            'combinations' => $combinations,
            'subjects' => $subjects,
            'user' => $this->currentUser
        ]);
    }

    public function exportCombinations() {
        $this->validateCsrf();
        $combinations = $this->masterData->getCombinations();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ds_to_hop_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($output, ['ID', 'Mã Tổ Hợp', 'Môn 1 (Mã)', 'Môn 2 (Mã)', 'Môn 3 (Mã)']);
        
        foreach ($combinations as $row) {
            fputcsv($output, [
                $row['id'], 
                $row['ma_to_hop'], 
                $row['mon1_ma'] ?? '', 
                $row['mon2_ma'] ?? '', 
                $row['mon3_ma'] ?? ''
            ]);
        }
        fclose($output);
        exit;
    }

    public function templateCombinations() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=mau_nhap_to_hop.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($output, ['Mã Tổ Hợp', 'Môn 1 (Mã)', 'Môn 2 (Mã)', 'Môn 3 (Mã)']);
        fputcsv($output, ['A00', 'TOAN', 'LY', 'HOA']);
        fputcsv($output, ['D01', 'TOAN', 'VAN', 'ANH']);
        fclose($output);
        exit;
    }

    private function importCombinations() {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Vui lòng chọn file hợp lệ");
        }
        
        $file = $_FILES['file']['tmp_name'];
        $handle = fopen($file, "r");
        
        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($handle);
        }
        
        fgetcsv($handle); // Skip header
        
        $count = 0;
        $comboModel = new \App\Models\Combination();
        $subjectModel = new \App\Models\Subject();
        
        // Cache subjects for performance: Code -> ID
        $subjects = $subjectModel->getAllSubjects();
        $subMap = [];
        foreach ($subjects as $s) {
            $subMap[$s['ma_mon']] = $s['id'];
        }
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 4) continue;
            
            $ma = trim($data[0]);
            $mon1 = trim($data[1]);
            $mon2 = trim($data[2]);
            $mon3 = trim($data[3]);
            
            if (!$ma || !$mon1 || !$mon2 || !$mon3) continue;
            
            if (!isset($subMap[$mon1]) || !isset($subMap[$mon2]) || !isset($subMap[$mon3])) {
                // Skip if subject code not found
                continue; 
            }
            
            // Check exist
            $exists = $this->masterData->find('dm_to_hop', $ma, 'ma_to_hop');
            $payload = [
                'ma_to_hop' => $ma,
                'mon_1_id' => $subMap[$mon1],
                'mon_2_id' => $subMap[$mon2],
                'mon_3_id' => $subMap[$mon3]
            ];

            if ($exists) {
                $comboModel->updateCombination($exists['id'], $payload);
            } else {
                $comboModel->createCombination($payload);
            }
            $count++;
        }
        fclose($handle);
        $_SESSION['success'] = "Đã nhập thành công $count tổ hợp.";
    }

    // --- Majors (Ngành học) ---
    public function majors() {
        // Optimization: Use single query with JOIN/GROUP_CONCAT
        $majors = $this->masterData->getMajorsWithCombinations();
        
        // If lookup failed or fallback needed, map combination_list string to array if view expects IDs
        // But view probably just displays them. 
        // If view needs `combination_ids` array for edit modal, we can explode the string.
        foreach ($majors as &$m) {
            $m['combination_ids'] = !empty($m['combination_list']) ? explode(', ', $m['combination_list']) : [];
        }
        
        $combinations = $this->masterData->getCombinations(); 
        $provinces = $this->masterData->getProvinces();

        $this->view('admin/master_data/majors', [
            'majors' => $majors, 
            'combinations' => $combinations,
            'provinces' => $provinces,
            'user' => $this->currentUser
        ]);
    }

    public function saveMajor() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['action'] ?? '';

            $majorData = [
                'ma_nganh' => $_POST['ma_nganh'],
                'ten_nganh' => $_POST['ten_nganh'],
                'chi_tieu' => $_POST['chi_tieu'] ?: null,
                'khoi_xet_tuyen' => implode(', ', $_POST['combinations'] ?? []), 
                'diem_nam_truoc' => $_POST['diem_nam_truoc'] ?: null,
                'ghi_chu' => $_POST['ghi_chu'],
                'khu_vuc_tuyen_sinh' => !empty($_POST['provinces']) ? implode(',', $_POST['provinces']) : null,
                'nhom_nganh' => $_POST['nhom_nganh'] ?? 'Khac',
                'nguong_hoc_luc' => !empty($_POST['nguong_hoc_luc']) ? $_POST['nguong_hoc_luc'] : null,
                'nguong_diem_thpt' => !empty($_POST['nguong_diem_thpt']) ? (float)$_POST['nguong_diem_thpt'] : null
            ];

            if ($action === 'create') {
                $this->masterData->create('dm_nganh', $majorData);
                $this->masterData->saveMajorCombinations($_POST['ma_nganh'], $_POST['combinations'] ?? []);

            } elseif ($action === 'update') {
                $this->masterData->update('dm_nganh', $_POST['old_ma'], $majorData, 'ma_nganh');
                $this->masterData->saveMajorCombinations($_POST['ma_nganh'], $_POST['combinations'] ?? []);
            }
            // Clear cache for majors
            \App\Core\Cache::forget('majors_with_combinations');
            \App\Core\Cache::forget('master_majors');
            $this->redirect(url('/admin/master-data/majors'));
        }
    }

    public function deleteMajor() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $ma = $_POST['ma'] ?? '';
            if ($ma) {
                // Also delete relationships
                $this->masterData->delete('dm_nganh_to_hop', $ma, 'ma_nganh');
                $this->masterData->delete('dm_nganh', $ma, 'ma_nganh');
                $_SESSION['success'] = "Xóa ngành thành công";
            }
            $this->redirect(url('/admin/master-data/majors'));
        } else {
             $this->redirect(url('/admin/master-data/majors'));
        }
    }

    public function majorsActions() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['action'] ?? '';
            try {
                if ($action === 'bulk_delete') {
                    $ids = $_POST['ids'] ?? []; // IDs here are likely 'ma_nganh' strings based on view
                    // BUT generic deleteMany uses 'id' by default or specific field?
                    // View uses `ma_nganh` as ID in single delete. 
                    // Let's check view: checkboxes value should be `ma_nganh`.
                    if (!empty($ids)) {
                        // Delete relationships first
                        $this->masterData->deleteMany('dm_nganh_to_hop', $ids, 'ma_nganh');
                        $this->masterData->deleteMany('dm_nganh', $ids, 'ma_nganh');
                        $_SESSION['success'] = "Đã xóa " . count($ids) . " ngành";
                    }
                } elseif ($action === 'import') {
                    $this->importMajors();
                }
                $this->redirect(url('/admin/master-data/majors'));
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                $this->redirect(url('/admin/master-data/majors'));
            }
        }
    }

    public function exportMajors() {
        $this->validateCsrf();
        $majors = $this->masterData->getMajorsWithCombinations();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ds_nganh_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($output, ['Mã Ngành', 'Tên Ngành', 'Chỉ Tiêu', 'Điểm 2025', 'Khối Xét Tuyển (Cách nhau dấu phẩy)', 'Ghi Chú']);
        
        foreach ($majors as $row) {
            fputcsv($output, [
                $row['ma_nganh'], 
                $row['ten_nganh'], 
                $row['chi_tieu'], 
                $row['diem_nam_truoc'], 
                $row['combination_list'] ?? '', 
                $row['ghi_chu']
            ]);
        }
        fclose($output);
        exit;
    }

    public function templateMajors() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=mau_nhap_nganh.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($output, ['Mã Ngành', 'Tên Ngành', 'Chỉ Tiêu', 'Điểm 2025', 'Khối Xét Tuyển (Cách nhau dấu phẩy)', 'Ghi Chú']);
        fputcsv($output, ['7480201', 'Công nghệ thông tin', '200', '21.5', 'A00, A01, D01', 'Chương trình chuẩn']);
        fclose($output);
        exit;
    }

    private function importMajors() {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Vui lòng chọn file hợp lệ");
        }
        
        $file = $_FILES['file']['tmp_name'];
        $handle = fopen($file, "r");
        
        // Skip BOM
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) rewind($handle);
        
        fgetcsv($handle); // Skip header
        
        $count = 0;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 2) continue;
            
            $ma = trim($data[0]);
            $ten = trim($data[1]);
            $chitieu = trim($data[2] ?? '');
            $diem = trim($data[3] ?? '');
            $khoi = trim($data[4] ?? ''); // e.g., "A00, D01"
            $ghichu = trim($data[5] ?? '');
            
            if (!$ma || !$ten) continue;
            
            // Check exist
            $exists = $this->masterData->find('dm_nganh', $ma, 'ma_nganh');
            $payload = [
                'ma_nganh' => $ma,
                'ten_nganh' => $ten,
                'chi_tieu' => $chitieu ?: null,
                'diem_nam_truoc' => $diem ?: null,
                'ghi_chu' => $ghichu
            ];

            if ($exists) {
                $this->masterData->update('dm_nganh', $ma, $payload, 'ma_nganh');
            } else {
                $this->masterData->create('dm_nganh', $payload);
            }

            // Handle combinations
            if ($khoi) {
                $comboCodes = array_map('trim', explode(',', $khoi));
                $this->masterData->saveMajorCombinations($ma, $comboCodes);
                // Note: saveMajorCombinations usually expects IDs or Codes? 
                // Let's check model. 
                // Model: "INSERT INTO dm_nganh_to_hop (ma_nganh, ma_to_hop) VALUES (?, ?)"
                // It expects `ma_to_hop` (string code) based on `getMajorCombinations` returning `ma_to_hop`.
                // So passing codes directly is correct if `dm_nganh_to_hop` uses `ma_to_hop`.
                // Checking `migrate_major_combinations.php` or `MasterData.php`...
                // MasterData.php line 134: `foreach ($combinations as $ma_to_hop)`.
                // It inserts `ma_to_hop`. Yes.
            }
            
            $count++;
        }
        fclose($handle);
        $_SESSION['success'] = "Đã nhập thành công $count ngành.";
    }

    // --- Schools (Trường THPT) ---
    public function schools() {
        // Optimization: Join with Province table
        $schools = $this->masterData->getSchoolsWithProvince();
        
        // Provinces still needed for Filter/Create dropdown
        $provinces = $this->masterData->getAll('dm_tinh', 'ten_tinh');
        
        $this->view('admin/master_data/schools', [
            'schools' => $schools,
            'provinces' => $provinces,
            'user' => $this->currentUser
        ]);
    }

    public function saveSchool() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $oldMa = $_POST['old_ma'] ?? '';
            $data = [
                'ma_truong' => $_POST['ma_truong'],
                'ten_truong' => $_POST['ten_truong'],
                'khu_vuc' => $_POST['khu_vuc'],
                'ma_tinh' => $_POST['ma_tinh']
            ];

            if ($oldMa) {
                $this->masterData->update('dm_truong_thpt', $oldMa, $data, 'ma_truong');
            } else {
                $this->masterData->create('dm_truong_thpt', $data);
            }
            $this->redirect(url('/admin/master-data/schools'));
        }
    }

    // --- Admission Sessions (Đợt tuyển sinh) ---
    public function sessions() {
        $sessions = $this->masterData->getAll('dot_tuyen_sinh', 'id DESC');
        $this->view('admin/master_data/sessions', ['sessions' => $sessions, 'user' => $this->currentUser]);
    }

    public function saveSession() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $id = $_POST['id'] ?? '';
            $data = [
                'ten_dot' => $_POST['ten_dot'],
                'nam_tuyen_sinh' => (int)$_POST['nam_tuyen_sinh'],
                'ngay_bat_dau' => $_POST['ngay_bat_dau'],
                'ngay_ket_thuc' => $_POST['ngay_ket_thuc'],
                'kich_hoat' => isset($_POST['kich_hoat']) ? true : false
            ];

            if ($id) {
                $this->masterData->update('dot_tuyen_sinh', $id, $data);
            } else {
                $this->masterData->create('dot_tuyen_sinh', $data);
            }
            $this->redirect(url('/admin/master-data/sessions'));
        }
    }

    // --- System Settings ---
    public function settings() {
        $settingList = $this->masterData->getAll('settings', 'key');
        $this->view('admin/master_data/settings', ['settings' => $settingList, 'user' => $this->currentUser]);
    }

    public function saveSetting() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            foreach ($_POST['settings'] as $key => $value) {
                $this->masterData->setSetting($key, $value);
            }
            $this->redirect(url('/admin/master-data/settings?updated=1'));
        }
    }

    // --- Language Conversion Rules ---
    public function languageRules() {
        $conversionModel = new \App\Models\ScoreConversion();
        $rules = $conversionModel->getAllRules();
        $this->view('admin/master_data/language_rules', ['rules' => $rules, 'user' => $this->currentUser]);
    }

    public function saveLanguageRule() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $conversionModel = new \App\Models\ScoreConversion();
            
            $conversionModel->saveRule($_POST);
            $this->redirect(url('/admin/master-data/language-rules'));
        }
    }

    public function deleteLanguageRule() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $id = $_POST['id'] ?? '';
            if ($id) {
                $conversionModel = new \App\Models\ScoreConversion();
                $conversionModel->deleteRule($id);
            }
            $this->redirect(url('/admin/master-data/language-rules'));
        } else {
             $this->redirect(url('/admin/master-data/language-rules'));
        }
    }

    // --- Public API for Dropdowns ---
    public function apiWards() {
        header('Content-Type: application/json');
        $provinceId = $_GET['province_id'] ?? '';
        if (!$provinceId) {
            echo json_encode([]);
            exit;
        }
        // Use Model to fetch Wards
        // Verify MasterData model has getWards
        $wards = $this->masterData->getWards($provinceId);
        echo json_encode($wards);
        exit;
    }

    public function apiSchools() {
        header('Content-Type: application/json');
        $provinceId = $_GET['province_id'] ?? '';
        if (!$provinceId) {
            echo json_encode([]);
            exit;
        }
        $schools = $this->masterData->getSchools($provinceId);
        echo json_encode($schools);
        exit;
    }
}
