<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Models\Subject;
use App\Models\QuanTriVien;

class SubjectController extends Controller {
    protected $masterData;
    protected $currentUser;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->masterData = new MasterData();

        // Load current user and check permission
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
        $subjectModel = new Subject();
        
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
                    $this->import();
                }
                $this->redirect(url('/admin/master-data/subjects'));
                return;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }

        // Pagination setup
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 15; // Số môn mỗi trang
        $offset = ($page - 1) * $limit;
        
        $totalRecords = $subjectModel->countAll();
        $totalPages = ceil($totalRecords / $limit);
        $subjects = $subjectModel->getAllSubjects($limit, $offset);

        $this->view('admin/master_data/subjects', [
            'subjects' => $subjects, 
            'user' => $this->currentUser,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords
        ]);
    }

    public function export() {
        $this->validateCsrf();
        $subjects = $this->masterData->getSubjects();
        
        $data = [];
        foreach ($subjects as $row) {
            $data[] = [
                'ID' => $row['id'], 
                'Mã Môn' => $row['ma_mon'], 
                'Tên Môn' => $row['ten_mon'], 
                'Loại (van_hoa/nang_khieu)' => $row['loai_mon'], 
                'Cột Điểm' => $row['cot_diem']
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'ds_mon_hoc_' . date('Y-m-d') . '.xls');
    }

    public function template() {
        $data = [
            ['Mã Môn' => 'TOAN', 'Tên Môn' => 'Toán Học', 'Loại (van_hoa/nang_khieu)' => 'van_hoa', 'Cột Điểm' => 'toan'],
            ['Mã Môn' => 'NK_VE', 'Tên Môn' => 'Vẽ Mỹ Thuật', 'Loại (van_hoa/nang_khieu)' => 'nang_khieu', 'Cột Điểm' => 'nk1']
        ];

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'mau_nhap_mon_hoc.xls');
    }

    private function import() {
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
        $subjectModel = new Subject();
        
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
}
