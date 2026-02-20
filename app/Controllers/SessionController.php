<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Models\QuanTriVien;
use App\Core\Cache;

class SessionController extends Controller {
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
        $sessions = $this->masterData->getAll('dot_tuyen_sinh', 'id DESC');
        $this->view('admin/master_data/sessions', ['sessions' => $sessions, 'user' => $this->currentUser]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $id = $_POST['id'] ?? '';
            $data = [
                'ten_dot' => $_POST['ten_dot'],
                'nam_tuyen_sinh' => (int)$_POST['nam_tuyen_sinh'],
                'dm_nam_tuyen_sinh_nam' => (int)$_POST['nam_tuyen_sinh'], // Ensure FK is updated
                'ngay_bat_dau' => $_POST['ngay_bat_dau'],
                'ngay_ket_thuc' => $_POST['ngay_ket_thuc'],
                'kich_hoat' => isset($_POST['kich_hoat']) ? true : false
            ];

            if ($id) {
                $this->masterData->update('dot_tuyen_sinh', $id, $data);
            } else {
                $this->masterData->create('dot_tuyen_sinh', $data);
            }
            Cache::forget('active_session');
            $this->redirect(url('/admin/master-data/sessions'));
        }
    }
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $id = $_POST['id'] ?? '';
            
            if ($id) {
                // Optional: Check dependencies manually if needed, or rely on DB constraints
                // For now, try/catch around delete
                try {
                    $this->masterData->delete('dot_tuyen_sinh', $id);
                    Cache::forget('active_session');
                    $this->redirect(url('/admin/master-data/sessions?success=' . urlencode('Đã xóa đợt tuyển sinh thành công')));
                } catch (\Exception $e) {
                    $this->redirect(url('/admin/master-data/sessions?error=' . urlencode('Không thể xóa đợt này vì đã có dữ liệu hồ sơ liên quan.')));
                }
            } else {
                $this->redirect(url('/admin/master-data/sessions'));
            }
        }
    }
}
