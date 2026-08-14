<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\QuanTriVien;
use App\Models\SystemLog;
use PDO;

class TemplateController extends Controller
{
    protected $currentUser;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }

        $adminModel = new \App\Models\QuanTriVien();
        $this->currentUser = $adminModel->find($_SESSION['admin_id']);

        if (!$this->currentUser || !$this->currentUser['is_active']) {
            session_destroy();
            $this->redirect(url('/admin/login'));
        }
    }

    public function index()
    {
        // Require admin permission
        if (!QuanTriVien::hasPermission($this->currentUser, 'settings.view')) {
            $this->redirect('/admin/dashboard', 'Không có quyền truy cập');
        }

        $db = Database::getInstance()->getConnection();
        
        // Fetch templates
        $stmt = $db->query("SELECT * FROM mau_in ORDER BY id ASC");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/templates/index', [
            'title' => 'Cấu Hình Mẫu In (WYSIWYG)',
            'templates' => $templates,
            'user' => $this->currentUser
        ]);
    }

    public function save()
    {
        ob_start(); // Start capturing output to prevent warnings from breaking JSON
        
        if (!QuanTriVien::hasPermission($this->currentUser, 'settings.edit')) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Không có quyền cập nhật']);
            return;
        }

        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Lỗi dữ liệu']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT ma_mau FROM mau_in WHERE id = ?");
            $stmt->execute([$id]);
            $maMau = $stmt->fetchColumn();

            if (!$maMau) {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Mẫu in không tồn tại']);
                return;
            }

            // Handle file upload
            if (isset($_FILES['template_file']) && $_FILES['template_file']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['template_file']['tmp_name'];
                $fileName = $_FILES['template_file']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if ($fileExtension !== 'docx') {
                    ob_end_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Vui lòng tải lên file Word (.docx)']);
                    return;
                }

                // Ensure upload directory exists
                $uploadDir = __DIR__ . '/../../storage/templates/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0777, true);
                }

                $newFileName = $maMau . '_' . time() . '.docx';
                $destPath = $uploadDir . $newFileName;

                if (@move_uploaded_file($fileTmpPath, $destPath)) {
                    // Update DB with file path
                    $updateStmt = $db->prepare("UPDATE mau_in SET file_path = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $updateStmt->execute([$newFileName, $id]);

                    ob_end_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Tải lên mẫu in thành công!']);
                    return;
                } else {
                    $error = error_get_last();
                    $errorStr = $error ? $error['message'] : 'Không xác định';
                    
                    ob_end_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Lỗi di chuyển file tải lên: ' . $errorStr]);
                    return;
                }
            } else {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy file tải lên hoặc có lỗi tải file']);
                return;
            }

        } catch (\Exception $e) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }
}
