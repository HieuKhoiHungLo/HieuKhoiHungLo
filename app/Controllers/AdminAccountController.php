<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\QuanTriVien;

class AdminAccountController extends Controller {
    protected $adminModel;
    protected $currentUser;
    protected $permissionService;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        
        $this->adminModel = new QuanTriVien();
        $this->permissionService = new \App\Services\PermissionService();
        $this->currentUser = $this->adminModel->find($_SESSION['admin_id']);
        
        // Use the service for permission check
        if (!$this->permissionService->checkUserPermission($this->currentUser, 'accounts')) {
             echo "Bạn không có quyền truy cập chức năng này.";
             exit;
        }
    }

    public function index() {
        $accounts = $this->adminModel->getAll();
        $this->view('admin/accounts/index', ['accounts' => $accounts, 'user' => $this->currentUser]);
    }

    public function create() {
        $roles = $this->permissionService->getAllRoles();
        $this->view('admin/accounts/form', ['user' => $this->currentUser, 'roles' => $roles]);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $fullname = $_POST['fullname'] ?? '';
            $permissions = $_POST['permissions'] ?? [];
            
            // Validate
            if (empty($username) || empty($password)) {
                $error = "Tên đăng nhập và mật khẩu không được để trống";
                $this->view('admin/accounts/form', ['error' => $error]);
                return;
            }

            // Check duplicate
            if ($this->adminModel->timTheoTenDangNhap($username)) {
                 $error = "Tên đăng nhập đã tồn tại";
                 $this->view('admin/accounts/form', ['error' => $error]);
                 return;
            }

            // Create
            $data = [
                'ten_dang_nhap' => $username,
                'mat_khau' => password_hash($password, PASSWORD_DEFAULT),
                'ho_ten' => $fullname,
                'permissions' => json_encode($permissions),
                'role_id' => !empty($_POST['role_id']) ? $_POST['role_id'] : null,
                'is_active' => true
            ];
            
            if ($this->adminModel->create($data)) {
                $this->redirect(url('/admin/accounts'));
            } else {
                 $this->view('admin/accounts/form', ['error' => 'Lỗi hệ thống']);
            }
        }
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) $this->redirect(url('/admin/accounts'));

        $account = $this->adminModel->find($id);
        if (!$account) $this->redirect(url('/admin/accounts'));

        $roles = $this->permissionService->getAllRoles();
        $this->view('admin/accounts/form', ['account' => $account, 'user' => $this->currentUser, 'roles' => $roles]);
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $password = $_POST['password'] ?? '';
            $fullname = $_POST['fullname'] ?? '';
            $permissions = $_POST['permissions'] ?? [];
            $isActive = isset($_POST['is_active']) ? true : false;

            // Prevent blocking self? Maybe
            if ($id == $_SESSION['admin_id'] && $isActive == 0) {
                 // Cannot ban self
                 $isActive = 1;
            }
            if ($id == 1 && $isActive == 0) {
                // Cannot ban super admin
                $isActive = 1;
            }

            $data = [
                'ho_ten' => $fullname,
                'permissions' => json_encode($permissions),
                'role_id' => !empty($_POST['role_id']) ? $_POST['role_id'] : null,
                'is_active' => $isActive
            ];

            if (!empty($password)) {
                $data['mat_khau'] = password_hash($password, PASSWORD_DEFAULT);
            }

            if ($this->adminModel->update($id, $data)) {
                $this->redirect(url('/admin/accounts'));
            } else {
                 // Fetch again to show error
                 $account = $this->adminModel->find($id);
                 $this->view('admin/accounts/form', ['account' => $account, 'error' => 'Lỗi cập nhật']);
            }
        }
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Protect ID 1 and Self
            if ($id == 1) {
                echo "<script>alert('Không thể xóa Super Admin!'); window.location.href='/admin/accounts';</script>";
                return;
            }
            if ($id == $_SESSION['admin_id']) {
                 echo "<script>alert('Không thể xóa chính mình!'); window.location.href='/admin/accounts';</script>";
                 return;
            }

            $this->adminModel->delete($id);
        }
        $this->redirect(url('/admin/accounts'));
    }
}
