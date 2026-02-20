<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\MasterDataRepository;
use App\Models\QuanTriVien;

class ZoneController extends Controller {
    protected $masterDataRepo;
    protected $currentUser;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->masterDataRepo = new MasterDataRepository();

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
        $zones = $this->masterDataRepo->getZoneConfigs();
        $provinces = $this->masterDataRepo->getProvinces();
        
        $this->view('admin/master_data/zones', [
            'zones' => $zones,
            'provinces' => $provinces,
            'user' => $this->currentUser
        ]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            
            $ma_nganh_prefix = $_POST['ma_nganh_prefix'] ?? '';
            $ma_tinh = $_POST['ma_tinh'] ?? '';
            
            if (!$ma_nganh_prefix || !$ma_tinh) {
                $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin.';
                $this->redirect(url('/admin/master-data/zones'));
                return;
            }

            // Simple Insert (no update needed as it's a mapping table)
            // But check for duplicate
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) FROM config_vung_tuyen_sinh WHERE ma_nganh_prefix = ? AND ma_tinh = ?");
            $stmt->execute([$ma_nganh_prefix, $ma_tinh]);
            if ($stmt->fetchColumn() > 0) {
                 $_SESSION['error'] = 'Cấu hình này đã tồn tại.';
            } else {
                try {
                    $insert = $db->prepare("INSERT INTO config_vung_tuyen_sinh (ma_nganh_prefix, ma_tinh) VALUES (?, ?)");
                    $insert->execute([$ma_nganh_prefix, $ma_tinh]);
                    $_SESSION['success'] = 'Thêm cấu hình thành công.';
                } catch (\Exception $e) {
                    $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
                }
            }
            
            $this->redirect(url('/admin/master-data/zones'));
        }
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $id = $_POST['id'] ?? '';
            if ($id) {
                try {
                     $db = \App\Core\Database::getInstance()->getConnection();
                     $stmt = $db->prepare("DELETE FROM config_vung_tuyen_sinh WHERE id = ?");
                     $stmt->execute([$id]);
                     $_SESSION['success'] = 'Xóa thành công.';
                } catch (\Exception $e) {
                     $_SESSION['error'] = 'Lỗi xóa: ' . $e->getMessage();
                }
            }
            $this->redirect(url('/admin/master-data/zones'));
        }
    }
}
