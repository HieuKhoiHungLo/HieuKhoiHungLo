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
                    'khu_vuc_tuyen_sinh' => !empty($_POST['provinces']) ? implode(',', $_POST['provinces']) : null,
                    'co_xet_chung_chi' => isset($_POST['co_xet_chung_chi']) ? 'true' : 'false',
                    'co_diem_nangkhieu_thpt' => isset($_POST['co_diem_nangkhieu_thpt']) ? 'true' : 'false',
                    'co_diem_nangkhieu_hochba' => isset($_POST['co_diem_nangkhieu_hochba']) ? 'true' : 'false'
                ]);
                $this->masterData->saveMajorCombinations($_POST['ma_nganh'], $_POST['combinations'] ?? []);
                \App\Core\Cache::forget('master_majors_combinations');

            } elseif ($action === 'update') {
                $oldMa = $_POST['old_ma'];
                $newMa = $_POST['ma_nganh'];
                
                $this->masterData->update('dm_nganh', $oldMa, [
                    'ma_nganh' => $newMa,
                    'ten_nganh' => $_POST['ten_nganh'],
                    'chi_tieu' => $_POST['chi_tieu'] ?: null,
                    'nhom_nganh' => $_POST['nhom_nganh'] ?? 'Khac',
                    'nguong_hoc_luc' => $_POST['nguong_hoc_luc'] ?: null,
                    'nguong_diem_thpt' => $_POST['nguong_diem_thpt'] ?: null,
                    'khoi_xet_tuyen' => implode(', ', $_POST['combinations'] ?? []), 
                    'diem_nam_truoc' => $_POST['diem_nam_truoc'] ?: null,
                    'ghi_chu' => $_POST['ghi_chu'],
                    'khu_vuc_tuyen_sinh' => !empty($_POST['provinces']) ? implode(',', $_POST['provinces']) : null,
                    'co_xet_chung_chi' => isset($_POST['co_xet_chung_chi']) ? 'true' : 'false',
                    'co_diem_nangkhieu_thpt' => isset($_POST['co_diem_nangkhieu_thpt']) ? 'true' : 'false',
                    'co_diem_nangkhieu_hochba' => isset($_POST['co_diem_nangkhieu_hochba']) ? 'true' : 'false'
                ], 'ma_nganh');
                
                // If ID changed, clear old relationships to avoid orphans
                if ($oldMa !== $newMa) {
                    $this->masterData->delete('dm_nganh_to_hop', $oldMa, 'ma_nganh');
                }
                
                $this->masterData->saveMajorCombinations($newMa, $_POST['combinations'] ?? []);
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

    public function toggleActive() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $ma = $_POST['ma_nganh'] ?? '';
            if ($ma) {
                $db = \App\Core\Database::getInstance()->getConnection();
                // Đảo trạng thái kich_hoat
                $stmt = $db->prepare("UPDATE dm_nganh SET kich_hoat = NOT COALESCE(kich_hoat, true) WHERE ma_nganh = ?");
                $stmt->execute([$ma]);
                
                // Xóa cache
                \App\Core\Cache::forget('master_majors_combinations');
                \App\Core\Cache::forget('master_active_majors_combinations');
                \App\Core\Cache::forget('active_majors_with_combinations_v2');
                
                // Lấy trạng thái mới
                $stmt2 = $db->prepare("SELECT COALESCE(kich_hoat, true) as kich_hoat FROM dm_nganh WHERE ma_nganh = ?");
                $stmt2->execute([$ma]);
                $result = $stmt2->fetch(\PDO::FETCH_ASSOC);
                
                $this->json([
                    'status' => true, 
                    'kich_hoat' => ($result['kich_hoat'] === true || $result['kich_hoat'] === 't' || $result['kich_hoat'] === '1'),
                    'message' => 'Cập nhật trạng thái thành công'
                ]);
                return;
            }
            $this->json(['status' => false, 'message' => 'Mã ngành không hợp lệ']);
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
        
        $data = [];
        foreach ($majors as $row) {
            $data[] = [
                'Mã Ngành' => $row['ma_nganh'], 
                'Tên Ngành' => $row['ten_nganh'], 
                'Chỉ Tiêu' => $row['chi_tieu'], 
                'Điểm 2025' => $row['diem_nam_truoc'], 
                'Khối Xét Tuyển (Cách nhau dấu phẩy)' => $row['combination_list'] ?? '', 
                'Ghi Chú' => $row['ghi_chu']
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'ds_nganh_' . date('Y-m-d') . '.xls');
    }

    public function template() {
        $data = [[
            'Mã Ngành' => '7480201', 
            'Tên Ngành' => 'Công nghệ thông tin', 
            'Chỉ Tiêu' => '200', 
            'Điểm 2025' => '21.5', 
            'Khối Xét Tuyển (Cách nhau dấu phẩy)' => 'A00, A01, D01', 
            'Ghi Chú' => 'Chương trình chuẩn'
        ]];

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'mau_nhap_nganh.xls');
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
