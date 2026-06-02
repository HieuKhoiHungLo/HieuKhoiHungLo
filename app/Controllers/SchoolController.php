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
        $search = $_GET['search'] ?? '';
        $maTinh = $_GET['ma_tinh'] ?? '';
        $khuVuc = $_GET['khu_vuc'] ?? '';
        $sort = $_GET['sort'] ?? 'ma_tinh';
        $dir = $_GET['dir'] ?? 'ASC';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 10;
        $offset = ($page - 1) * $limit;

        $totalRecords = $this->masterData->countSchoolsFiltered($search, $maTinh, $khuVuc);
        $totalPages = ceil($totalRecords / $limit);

        $schools = $this->masterData->getSchoolsPaginated($search, $maTinh, $khuVuc, $sort, $dir, $limit, $offset);
        
        // Provinces still needed for Filter/Create dropdown
        $provinces = $this->masterData->getAll('dm_tinh', 'ma_tinh');
        
        $this->view('admin/master_data/schools', [
            'schools' => $schools,
            'provinces' => $provinces,
            'user' => $this->currentUser,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'limit' => $limit,
            'filters' => [
                'search' => $search,
                'ma_tinh' => $maTinh,
                'khu_vuc' => $khuVuc,
                'sort' => $sort,
                'dir' => $dir
            ]
        ]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $oldMa = $_POST['old_ma'] ?? '';
            
            $ma_tinh = str_pad(trim($_POST['ma_tinh']), 2, '0', STR_PAD_LEFT);
            $ma_truong = str_pad(trim($_POST['ma_truong']), 3, '0', STR_PAD_LEFT);
            
            if (strlen($ma_truong) === 3) {
                $ma_truong_db = $ma_tinh . $ma_truong;
            } else {
                $ma_truong_db = $_POST['ma_truong'];
            }

            $data = [
                'ma_truong' => $ma_truong_db,
                'ten_truong' => $_POST['ten_truong'],
                'khu_vuc' => $_POST['khu_vuc'],
                'ma_tinh' => $ma_tinh,
                'is_active' => true
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
        $search = $_GET['search'] ?? '';
        $maTinh = $_GET['ma_tinh'] ?? '';
        $khuVuc = $_GET['khu_vuc'] ?? '';
        $sort = $_GET['sort'] ?? 'ten_truong';
        $dir = $_GET['dir'] ?? 'ASC';

        // Query matched schools up to a safety ceiling (100,000)
        $schools = $this->masterData->getSchoolsPaginated($search, $maTinh, $khuVuc, $sort, $dir, 100000, 0);
        
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
                        $inUseCount = 0;
                        $deletableIds = [];
                        foreach ($ids as $id) {
                            if ($this->masterData->isSchoolInUse($id)) {
                                $inUseCount++;
                            } else {
                                $deletableIds[] = $id;
                            }
                        }

                        if (!empty($deletableIds)) {
                            $this->masterData->deleteMany('dm_truong_thpt', $deletableIds, 'ma_truong');
                            $msg = "Đã xóa " . count($deletableIds) . " trường.";
                            if ($inUseCount > 0) {
                                $msg .= " Có $inUseCount trường không thể xóa do đang được sử dụng.";
                                $_SESSION['warning'] = $msg;
                            } else {
                                $_SESSION['success'] = $msg;
                            }
                        } else {
                            if ($inUseCount > 0) {
                                throw new \Exception("Không thể xóa các trường đã chọn vì tất cả đều đang được sử dụng trong hồ sơ thí sinh.");
                            }
                        }
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
        
        // Try using PHP Spreadsheet first
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
            
            $count = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $ma = isset($row['A']) ? trim((string)$row['A']) : '';
                $ten = isset($row['B']) ? trim((string)$row['B']) : '';
                $khu_vuc = isset($row['C']) ? trim((string)$row['C']) : 'KV2';
                $ma_tinh = isset($row['D']) ? trim((string)$row['D']) : '';
                
                if (!$ma || !$ten) continue;
                
                if ($ma_tinh && !isset($provMap[$ma_tinh])) {
                    $this->masterData->create('dm_tinh', [
                        'ma_tinh' => $ma_tinh,
                        'ten_tinh' => 'Tỉnh/TP ' . $ma_tinh,
                        'is_active' => true
                    ]);
                    $provMap[$ma_tinh] = true;
                }
                
                $ma_tinh_padded = str_pad($ma_tinh, 2, '0', STR_PAD_LEFT);
                $ma_padded = str_pad($ma, 3, '0', STR_PAD_LEFT);
                $ma_db = (strlen($ma) === 3) ? ($ma_tinh_padded . $ma_padded) : $ma;
                
                $payload = [
                    'ma_truong' => $ma_db,
                    'ten_truong' => $ten,
                    'khu_vuc' => $khu_vuc ?: 'KV2',
                    'ma_tinh' => $ma_tinh_padded ?: null,
                    'is_active' => true
                ];
                
                $exists = $this->masterData->find('dm_truong_thpt', $ma_db, 'ma_truong');
                if ($exists) {
                    $this->masterData->update('dm_truong_thpt', $ma_db, $payload, 'ma_truong');
                } else {
                    $this->masterData->create('dm_truong_thpt', $payload);
                }
                $count++;
            }
            
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            
            $_SESSION['success'] = "Đã nhập thành công $count trường.";
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
                if (count($data) < 2) continue;
                
                $ma = trim($data[0]);
                $ten = trim($data[1]);
                $khu_vuc = trim($data[2] ?? 'KV2');
                $ma_tinh = trim($data[3] ?? '');
                
                if (!$ma || !$ten) continue;
                
                if ($ma_tinh && !isset($provMap[$ma_tinh])) {
                    $this->masterData->create('dm_tinh', [
                        'ma_tinh' => $ma_tinh,
                        'ten_tinh' => 'Tỉnh/TP ' . $ma_tinh,
                        'is_active' => true
                    ]);
                    $provMap[$ma_tinh] = true;
                }
                
                $ma_tinh_padded = str_pad($ma_tinh, 2, '0', STR_PAD_LEFT);
                $ma_padded = str_pad($ma, 3, '0', STR_PAD_LEFT);
                $ma_db = (strlen($ma) === 3) ? ($ma_tinh_padded . $ma_padded) : $ma;

                $payload = [
                    'ma_truong' => $ma_db,
                    'ten_truong' => $ten,
                    'khu_vuc' => $khu_vuc ?: 'KV2',
                    'ma_tinh' => $ma_tinh_padded ?: null,
                    'is_active' => true
                ];
 
                $exists = $this->masterData->find('dm_truong_thpt', $ma_db, 'ma_truong');
                if ($exists) {
                    $this->masterData->update('dm_truong_thpt', $ma_db, $payload, 'ma_truong');
                } else {
                    $this->masterData->create('dm_truong_thpt', $payload);
                }
                $count++;
            }
            fclose($handle);
            $_SESSION['success'] = "Đã nhập thành công $count trường.";
        }
    }
}
