<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Models\QuanTriVien;

class WardController extends Controller {
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
        $maTinh = $_GET['ma_tinh'] ?? '';
        $sort = $_GET['sort'] ?? 'ma_tinh';
        $dir = $_GET['dir'] ?? 'ASC';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 10;
        $offset = ($page - 1) * $limit;

        $totalRecords = $this->masterData->countWardsFiltered($search, $maTinh);
        $totalPages = ceil($totalRecords / $limit);

        $wards = $this->masterData->getWardsPaginated($search, $maTinh, $sort, $dir, $limit, $offset);
        
        // Provinces still needed for filters and create dropdown
        $provinces = $this->masterData->getAll('dm_tinh', 'ma_tinh');
        
        $this->view('admin/master_data/wards', [
            'wards' => $wards,
            'provinces' => $provinces,
            'user' => $this->currentUser,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'limit' => $limit,
            'filters' => [
                'search' => $search,
                'ma_tinh' => $maTinh,
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
                'ma_xa' => $_POST['ma_xa'],
                'ten_xa' => $_POST['ten_xa'],
                'ma_tinh' => $_POST['ma_tinh'],
                'is_active' => true
            ];

            if ($oldMa) {
                $this->masterData->update('dm_xa', $oldMa, $data, 'ma_xa');
                $_SESSION['success'] = "Cập nhật xã/phường thành công.";
            } else {
                $exists = $this->masterData->find('dm_xa', $_POST['ma_xa'], 'ma_xa');
                if ($exists) {
                    $_SESSION['error'] = "Mã xã/phường đã tồn tại.";
                } else {
                    $this->masterData->create('dm_xa', $data);
                    $_SESSION['success'] = "Thêm xã/phường thành công.";
                }
            }
            \App\Core\Cache::forgetByPattern('/^master_wards_/');
            $this->redirect(url('/admin/master-data/wards'));
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
                            if ($this->masterData->isWardInUse($id)) {
                                $inUseCount++;
                            } else {
                                $deletableIds[] = $id;
                            }
                        }

                        if (!empty($deletableIds)) {
                            $this->masterData->deleteMany('dm_xa', $deletableIds, 'ma_xa');
                            \App\Core\Cache::forgetByPattern('/^master_wards_/');
                            $msg = "Đã xóa " . count($deletableIds) . " xã/phường.";
                            if ($inUseCount > 0) {
                                $msg .= " Có $inUseCount xã/phường không thể xóa do đang được sử dụng.";
                                $_SESSION['warning'] = $msg;
                            } else {
                                $_SESSION['success'] = $msg;
                            }
                        } else {
                            if ($inUseCount > 0) {
                                throw new \Exception("Không thể xóa các xã/phường đã chọn vì tất cả đều đang được sử dụng trong hồ sơ thí sinh.");
                            }
                        }
                    }
                } elseif ($action === 'import') {
                    $this->import();
                }
                $this->redirect(url('/admin/master-data/wards'));
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                $this->redirect(url('/admin/master-data/wards'));
            }
        }
    }

    public function export() {
        $this->validateCsrf();
        $search = $_GET['search'] ?? '';
        $maTinh = $_GET['ma_tinh'] ?? '';
        $sort = $_GET['sort'] ?? 'ma_tinh';
        $dir = $_GET['dir'] ?? 'ASC';

        $wards = $this->masterData->getWardsPaginated($search, $maTinh, $sort, $dir, 100000, 0);
        
        $data = [];
        foreach ($wards as $row) {
            $data[] = [
                'Mã Xã' => $row['ma_xa'], 
                'Tên Xã' => $row['ten_xa'],
                'Mã Tỉnh' => $row['ma_tinh'],
                'Tên Tỉnh' => $row['ten_tinh'] ?? ''
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'ds_xa_phuong_' . date('Y-m-d') . '.xls');
    }

    public function template() {
        $data = [
            ['Mã Xã' => '25255', 'Tên Xã' => 'Phường Tiên Cát', 'Mã Tỉnh' => '17'],
            ['Mã Xã' => '00001', 'Tên Xã' => 'Phường Phúc Xá', 'Mã Tỉnh' => '01']
        ];

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'mau_nhap_xa_phuong.xls');
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
            
            // Cache provinces check: Code -> exists
            $provs = $this->masterData->getAll('dm_tinh', 'ma_tinh');
            $provMap = [];
            foreach ($provs as $p) {
                $provMap[$p['ma_tinh']] = true;
            }
            
            // Skip first header row
            $count = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $ma = isset($row['A']) ? trim((string)$row['A']) : '';
                $ten = isset($row['B']) ? trim((string)$row['B']) : '';
                $ma_tinh = isset($row['C']) ? trim((string)$row['C']) : '';
                
                if (!$ma || !$ten || !$ma_tinh) continue;
                
                // Add province if it does not exist (or skip if we enforce safety)
                if (!isset($provMap[$ma_tinh])) {
                    // Pre-create generic province just in case to maintain foreign key integrity
                    $this->masterData->create('dm_tinh', [
                        'ma_tinh' => $ma_tinh,
                        'ten_tinh' => 'Tỉnh/TP ' . $ma_tinh
                    ]);
                    $provMap[$ma_tinh] = true;
                }
                
                $payload = [
                    'ma_xa' => $ma,
                    'ten_xa' => $ten,
                    'ma_tinh' => $ma_tinh,
                    'is_active' => true
                ];
                
                $exists = $this->masterData->find('dm_xa', $ma, 'ma_xa');
                if ($exists) {
                    $this->masterData->update('dm_xa', $ma, $payload, 'ma_xa');
                } else {
                    $this->masterData->create('dm_xa', $payload);
                }
                $count++;
            }
            
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            
            \App\Core\Cache::forgetByPattern('/^master_wards_/');
            $_SESSION['success'] = "Đã nhập thành công $count xã/phường.";
            return;
        } catch (\Exception $spreadsheetEx) {
            // Fallback to CSV parser if PHP Spreadsheet fails
            $handle = fopen($file, "r");
            
            // Skip BOM
            $bom = fread($handle, 3);
            if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) rewind($handle);
            
            fgetcsv($handle); // Skip header row
            
            $provs = $this->masterData->getAll('dm_tinh', 'ma_tinh');
            $provMap = [];
            foreach ($provs as $p) {
                $provMap[$p['ma_tinh']] = true;
            }
            
            $count = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) < 3) continue;
                
                $ma = trim($data[0]);
                $ten = trim($data[1]);
                $ma_tinh = trim($data[2]);
                
                if (!$ma || !$ten || !$ma_tinh) continue;
                
                if (!isset($provMap[$ma_tinh])) {
                    $this->masterData->create('dm_tinh', [
                        'ma_tinh' => $ma_tinh,
                        'ten_tinh' => 'Tỉnh/TP ' . $ma_tinh
                    ]);
                    $provMap[$ma_tinh] = true;
                }
                
                $payload = [
                    'ma_xa' => $ma,
                    'ten_xa' => $ten,
                    'ma_tinh' => $ma_tinh,
                    'is_active' => true
                ];

                $exists = $this->masterData->find('dm_xa', $ma, 'ma_xa');
                if ($exists) {
                    $this->masterData->update('dm_xa', $ma, $payload, 'ma_xa');
                } else {
                    $this->masterData->create('dm_xa', $payload);
                }
                $count++;
            }
            fclose($handle);
            \App\Core\Cache::forgetByPattern('/^master_wards_/');
            $_SESSION['success'] = "Đã nhập thành công $count xã/phường.";
        }
    }
}
