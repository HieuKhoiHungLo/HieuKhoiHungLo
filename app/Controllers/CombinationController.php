<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Models\Combination;
use App\Models\Subject;
use App\Models\QuanTriVien;

class CombinationController extends Controller {
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
        $comboModel = new Combination();
        $subjectModel = new Subject();
        
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
                    $this->import();
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

    public function export() {
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

    public function template() {
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
        $comboModel = new Combination();
        $subjectModel = new Subject();
        
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
}
