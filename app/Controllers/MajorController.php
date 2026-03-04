<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Models\QuanTriVien;

class MajorController extends Controller {
    protected $masterData;
    protected $currentUser;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->masterData = new MasterData();

        $adminModel = new QuanTriVien();
        $this->currentUser = $adminModel->find($_SESSION['admin_id']);

        if (!$this->currentUser || !$this->currentUser['is_active']) {
             session_destroy();
             $this->redirect(url('/admin/login'));
        }

        if (!QuanTriVien::hasPermission($this->currentUser, 'master_data')) {
            echo "Bạn không có quyền truy cập chức năng này.";
            exit;
        }
    }

    public function index() {
        $majors = $this->masterData->getMajorsWithCombinations();
        
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

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $this->masterData->create('dm_nganh', [
                    'ma_nganh' => $_POST['ma_nganh'],
                    'ten_nganh' => $_POST['ten_nganh'],
                    'chi_tieu' => $_POST['chi_tieu'] ?: null,
                    'nhom_nganh' => $_POST['nhom_nganh'] ?? 'Khac',
                    'nguong_hoc_luc' => $_POST['nguong_hoc_luc'] ?: null,
                    'nguong_diem_thpt' => $_POST['nguong_diem_thpt'] ?: null,
                    'khoi_xet_tuyen' => implode(', ', $_POST['combinations'] ?? []), 
                    'diem_nam_truoc' => $_POST['diem_nam_truoc'] ?: null,
                    'ghi_chu' => $_POST['ghi_chu'],
                    'khu_vuc_tuyen_sinh' => !empty($_POST['provinces']) ? implode(',', $_POST['provinces']) : null
                ]);
                $this->masterData->saveMajorCombinations($_POST['ma_nganh'], $_POST['combinations'] ?? []);
                \App\Core\Cache::forget('master_majors_combinations');

            } elseif ($action === 'update') {
                $this->masterData->update('dm_nganh', $_POST['old_ma'], [
                    'ma_nganh' => $_POST['ma_nganh'],
                    'ten_nganh' => $_POST['ten_nganh'],
                    'chi_tieu' => $_POST['chi_tieu'] ?: null,
                    'nhom_nganh' => $_POST['nhom_nganh'] ?? 'Khac',
                    'nguong_hoc_luc' => $_POST['nguong_hoc_luc'] ?: null,
                    'nguong_diem_thpt' => $_POST['nguong_diem_thpt'] ?: null,
                    'khoi_xet_tuyen' => implode(', ', $_POST['combinations'] ?? []), 
                    'diem_nam_truoc' => $_POST['diem_nam_truoc'] ?: null,
                    'ghi_chu' => $_POST['ghi_chu'],
                    'khu_vuc_tuyen_sinh' => !empty($_POST['provinces']) ? implode(',', $_POST['provinces']) : null
                ], 'ma_nganh');
                $this->masterData->saveMajorCombinations($_POST['ma_nganh'], $_POST['combinations'] ?? []);
                \App\Core\Cache::forget('master_majors_combinations');
            }
            $this->redirect(url('/admin/master-data/majors'));
        }
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $ma = $_POST['ma'] ?? '';
            if ($ma) {
                // Also delete relationships
                $this->masterData->delete('dm_nganh_to_hop', $ma, 'ma_nganh');
                $this->masterData->delete('dm_nganh', $ma, 'ma_nganh');
                \App\Core\Cache::forget('master_majors_combinations');
                $_SESSION['success'] = "Xóa ngành thành công";
            }
            $this->redirect(url('/admin/master-data/majors'));
        } else {
             $this->redirect(url('/admin/master-data/majors'));
        }
    }

    public function actions() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['action'] ?? '';
            try {
                if ($action === 'bulk_delete') {
                    $ids = $_POST['ids'] ?? []; 
                    if (!empty($ids)) {
                        // Delete relationships first
                        $this->masterData->deleteMany('dm_nganh_to_hop', $ids, 'ma_nganh');
                        $this->masterData->deleteMany('dm_nganh', $ids, 'ma_nganh');
                        \App\Core\Cache::forget('master_majors_combinations');
                        $_SESSION['success'] = "Đã xóa " . count($ids) . " ngành";
                    }
                } elseif ($action === 'import') {
                    $this->import();
                }
                $this->redirect(url('/admin/master-data/majors'));
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                $this->redirect(url('/admin/master-data/majors'));
            }
        }
    }

    public function export() {
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

    public function template() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=mau_nhap_nganh.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($output, ['Mã Ngành', 'Tên Ngành', 'Chỉ Tiêu', 'Điểm 2025', 'Khối Xét Tuyển (Cách nhau dấu phẩy)', 'Ghi Chú']);
        fputcsv($output, ['7480201', 'Công nghệ thông tin', '200', '21.5', 'A00, A01, D01', 'Chương trình chuẩn']);
        fclose($output);
        exit;
    }

    private function import() {
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
            }
            \App\Core\Cache::forget('master_majors_combinations');
            
            $count++;
        }
        fclose($handle);
        $_SESSION['success'] = "Đã nhập thành công $count ngành.";
    }
}
