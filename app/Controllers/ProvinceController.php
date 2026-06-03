<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Models\QuanTriVien;

class ProvinceController extends Controller {
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
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? 'ma_tinh';
        $dir = $_GET['dir'] ?? 'ASC';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 10;
        $offset = ($page - 1) * $limit;

        $totalRecords = $this->masterData->countProvincesFiltered($search);
        $totalPages = ceil($totalRecords / $limit);

        $provinces = $this->masterData->getProvincesPaginated($search, $sort, $dir, $limit, $offset);
        
        $this->view('admin/master_data/provinces', [
            'provinces' => $provinces,
            'user' => $this->currentUser,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'limit' => $limit,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'dir' => $dir
            ]
        ]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $oldMa = $_POST['old_ma'] ?? '';
            $data = [
                'ma_tinh' => $_POST['ma_tinh'],
                'ten_tinh' => $_POST['ten_tinh'],
                'is_active' => true
            ];

            if ($oldMa) {
                $this->masterData->update('dm_tinh', $oldMa, $data, 'ma_tinh');
                $_SESSION['success'] = "Cập nhật tỉnh/thành phố thành công.";
            } else {
                $exists = $this->masterData->find('dm_tinh', $_POST['ma_tinh'], 'ma_tinh');
                if ($exists) {
                    $_SESSION['error'] = "Mã tỉnh/thành phố đã tồn tại.";
                } else {
                    $this->masterData->create('dm_tinh', $data);
                    $_SESSION['success'] = "Thêm tỉnh/thành phố thành công.";
                }
            }
            \App\Core\Cache::forget('master_provinces');
            $this->redirect(url('/admin/master-data/provinces'));
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
                        $inUseCount = 0;
                        $deletableIds = [];
                        foreach ($ids as $id) {
                            if ($this->masterData->isProvinceInUse($id)) {
                                $inUseCount++;
                            } else {
                                $deletableIds[] = $id;
                            }
                        }

                        if (!empty($deletableIds)) {
                            $this->masterData->deleteMany('dm_tinh', $deletableIds, 'ma_tinh');
                            \App\Core\Cache::forget('master_provinces');
                            $msg = "Đã xóa " . count($deletableIds) . " tỉnh/thành phố.";
                            if ($inUseCount > 0) {
                                $msg .= " Có $inUseCount tỉnh không thể xóa do đang được sử dụng.";
                                $_SESSION['warning'] = $msg;
                            } else {
                                $_SESSION['success'] = $msg;
                            }
                        } else {
                            if ($inUseCount > 0) {
                                throw new \Exception("Không thể xóa các tỉnh đã chọn vì tất cả đều đang được sử dụng trong CSDL.");
                            }
                        }
                    }
                } elseif ($action === 'import') {
                    $this->import();
                }
                $this->redirect(url('/admin/master-data/provinces'));
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                $this->redirect(url('/admin/master-data/provinces'));
            }
        }
    }

    public function export() {
        $this->validateCsrf();
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? 'ma_tinh';
        $dir = $_GET['dir'] ?? 'ASC';

        $provinces = $this->masterData->getProvincesPaginated($search, $sort, $dir, 100000, 0);
        
        $data = [];
        foreach ($provinces as $row) {
            $data[] = [
                'Mã Tỉnh' => $row['ma_tinh'], 
                'Tên Tỉnh' => $row['ten_tinh']
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'ds_tinh_' . date('Y-m-d') . '.xls');
    }

    public function template() {
        $data = [
            ['Mã Tỉnh' => '17', 'Tên Tỉnh' => 'Phú Thọ'],
            ['Mã Tỉnh' => '01', 'Tên Tỉnh' => 'Hà Nội']
        ];

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'mau_nhap_tinh.xls');
    }

    private function import() {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Vui lòng chọn file hợp lệ");
        }
        
        $file = $_FILES['file']['tmp_name'];
        
        // Suppress errors and try using PHP Spreadsheet first
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $rows = array_values($spreadsheet->getActiveSheet()->toArray(null, true, true, true));
            
            if (count($rows) <= 1) {
                throw new \Exception("File không chứa dữ liệu hoặc chỉ có dòng tiêu đề.");
            }
            
            // Skip first header row
            $count = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $ma = isset($row['A']) ? trim((string)$row['A']) : '';
                $ten = isset($row['B']) ? trim((string)$row['B']) : '';
                
                if (!$ma || !$ten) continue;
                
                // Keep codes exactly as strings to avoid numeric stripping
                $payload = [
                    'ma_tinh' => $ma,
                    'ten_tinh' => $ten,
                    'is_active' => true
                ];
                
                $exists = $this->masterData->find('dm_tinh', $ma, 'ma_tinh');
                if ($exists) {
                    $this->masterData->update('dm_tinh', $ma, $payload, 'ma_tinh');
                } else {
                    $this->masterData->create('dm_tinh', $payload);
                }
                $count++;
            }
            
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            
            \App\Core\Cache::forget('master_provinces');
            $_SESSION['success'] = "Đã nhập thành công $count tỉnh/thành phố.";
            return;
        } catch (\Exception $spreadsheetEx) {
            // Fallback to CSV parser if PHP Spreadsheet fails
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
                
                if (!$ma || !$ten) continue;
                
                $payload = [
                    'ma_tinh' => $ma,
                    'ten_tinh' => $ten,
                    'is_active' => true
                ];

                $exists = $this->masterData->find('dm_tinh', $ma, 'ma_tinh');
                if ($exists) {
                    $this->masterData->update('dm_tinh', $ma, $payload, 'ma_tinh');
                } else {
                    $this->masterData->create('dm_tinh', $payload);
                }
                $count++;
            }
            fclose($handle);
            \App\Core\Cache::forget('master_provinces');
            $_SESSION['success'] = "Đã nhập thành công $count tỉnh/thành phố.";
        }
    }
}
