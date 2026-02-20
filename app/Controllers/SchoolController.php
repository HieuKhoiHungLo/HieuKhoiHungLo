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
}
