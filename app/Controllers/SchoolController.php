<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Models\QuanTriVien;

class SchoolController extends Controller {
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

    public function save() {
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

    public function export() {
        $this->validateCsrf();
        $schools = $this->masterData->getSchoolsWithProvince();
        
        $data = [];
        foreach ($schools as $row) {
            $data[] = [
                'Mã Trường' => $row['ma_truong'], 
                'Tên Trường' => $row['ten_truong'], 
                'Khu Vực' => $row['khu_vuc'], 
                'Mã Tỉnh' => $row['ma_tinh'], 
                'Tên Tỉnh' => $row['ten_tinh'] ?? ''
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'ds_truong_thpt_' . date('Y-m-d') . '.xls');
    }

    public function template() {
        $data = [[
            'Mã Trường' => '17001', 
            'Tên Trường' => 'THPT Chuyên Hùng Vương', 
            'Khu Vực' => 'KV2-NT', 
            'Mã Tỉnh' => '17'
        ]];

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'mau_nhap_truong_thpt.xls');
    }

    public function actions() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['action'] ?? '';
            try {
                if ($action === 'bulk_delete') {
                    $ids = $_POST['ids'] ?? []; 
                    if (!empty($ids)) {
                        $this->masterData->deleteMany('dm_truong_thpt', $ids, 'ma_truong');
                        $_SESSION['success'] = "Đã xóa " . count($ids) . " trường.";
                    }
                } elseif ($action === 'import') {
                    $this->import();
                }
                $this->redirect(url('/admin/master-data/schools'));
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                $this->redirect(url('/admin/master-data/schools'));
            }
        }
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
        
        fgetcsv($handle); // Skip header row
        
        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 2) continue;
            
            $ma = trim($data[0]);
            $ten = trim($data[1]);
            $khu_vuc = trim($data[2] ?? 'KV2');
            $ma_tinh = trim($data[3] ?? '');
            
            if (!$ma || !$ten) continue;
            
            $payload = [
                'ma_truong' => $ma,
                'ten_truong' => $ten,
                'khu_vuc' => $khu_vuc,
                'ma_tinh' => $ma_tinh
            ];

            $exists = $this->masterData->find('dm_truong_thpt', $ma, 'ma_truong');
            if ($exists) {
                $this->masterData->update('dm_truong_thpt', $ma, $payload, 'ma_truong');
            } else {
                $this->masterData->create('dm_truong_thpt', $payload);
            }
            $count++;
        }
        fclose($handle);
        $_SESSION['success'] = "Đã nhập thành công $count trường.";
    }
}
