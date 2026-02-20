<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\QuanTriVien;
use App\Services\AuditService;

class AdminProfileController extends Controller {
    protected $adminModel;
    protected $auditService;
    protected $currentUser;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        
        $this->adminModel = new QuanTriVien();
        $this->auditService = new AuditService();
        $this->currentUser = $this->adminModel->find($_SESSION['admin_id']);
        
        if (!$this->currentUser) {
            session_destroy();
            $this->redirect(url('/admin/login'));
        }
    }

    public function index() {
        // Pass success/error messages
        $data = [
            'user' => $this->currentUser,
            'success' => $_GET['success'] ?? null,
            'error' => $_GET['error'] ?? null
        ];
        $this->view('admin/profile/index', $data);
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            
            $fullname = trim($_POST['fullname'] ?? '');
            
            if (empty($fullname)) {
                $this->redirect(url('/admin/profile?error=' . urlencode('Họ tên không được để trống')));
                return;
            }

            $updateData = ['ho_ten' => $fullname];

            // Handle Avatar Upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $filename = $_FILES['avatar']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowed)) {
                    $this->redirect(url('/admin/profile?error=' . urlencode('Chỉ chấp nhận file ảnh (JPG, PNG, WEBP)')));
                    return;
                }
                
                if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) { // 2MB
                    $this->redirect(url('/admin/profile?error=' . urlencode('Dung lượng ảnh tối đa 2MB')));
                    return;
                }

                // Create upload dir
                $uploadDir = 'uploads/avatars/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $newFilename = 'admin_' . $this->currentUser['id'] . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destPath)) {
                    // Remove old avatar if exists
                    if (!empty($this->currentUser['avatar']) && file_exists($this->currentUser['avatar'])) {
                        unlink($this->currentUser['avatar']);
                    }
                    $updateData['avatar'] = $destPath;
                    $_SESSION['admin_avatar'] = $destPath; // Update session
                }
            }

            if ($this->adminModel->update($this->currentUser['id'], $updateData)) {
                $_SESSION['admin_name'] = $fullname; // Update session
                $this->auditService->log('PROFILE_UPDATE', 'admin', $this->currentUser['id']);
                $this->redirect(url('/admin/profile?success=' . urlencode('Cập nhật thông tin thành công')));
            } else {
                $this->redirect(url('/admin/profile?error=' . urlencode('Lỗi cập nhật. Vui lòng thử lại')));
            }
        }
    }

    public function changePassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword)) {
                $this->redirect(url('/admin/profile?error=' . urlencode('Vui lòng nhập đầy đủ thông tin')));
                return;
            }

            if (!password_verify($currentPassword, $this->currentUser['mat_khau'])) {
                $this->redirect(url('/admin/profile?error=' . urlencode('Mật khẩu hiện tại không đúng')));
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $this->redirect(url('/admin/profile?error=' . urlencode('Mật khẩu xác nhận không khớp')));
                return;
            }

            if (strlen($newPassword) < 6) {
                $this->redirect(url('/admin/profile?error=' . urlencode('Mật khẩu mới phải có ít nhất 6 ký tự')));
                return;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            if ($this->adminModel->update($this->currentUser['id'], ['mat_khau' => $hashedPassword])) {
                $this->auditService->log('PASSWORD_CHANGE', 'admin', $this->currentUser['id']);
                $this->redirect(url('/admin/profile?success=' . urlencode('Đổi mật khẩu thành công')));
            } else {
                $this->redirect(url('/admin/profile?error=' . urlencode('Lỗi hệ thống. Vui lòng thử lại')));
            }
        }
    }
}
