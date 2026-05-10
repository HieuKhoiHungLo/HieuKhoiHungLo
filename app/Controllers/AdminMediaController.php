<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService;

class AdminMediaController extends Controller {
    protected $db;
    protected $auditService;

    public function __construct() {
        $this->requireAdmin();
        $this->db = Database::getInstance()->getConnection();
        $this->auditService = new AuditService();
    }

    public function index() {
        $stmt = $this->db->query("SELECT * FROM media ORDER BY created_at DESC");
        $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->view('admin/media/index', ['files' => $files]);
    }

    public function apiList() {
        header('Content-Type: application/json');
        $type = $_GET['type'] ?? '';
        
        $sql = "SELECT * FROM media";
        if ($type === 'image') {
            $sql .= " WHERE filename LIKE '%.jpg' OR filename LIKE '%.jpeg' OR filename LIKE '%.png' OR filename LIKE '%.webp' OR filename LIKE '%.gif'";
        }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->query($sql);
        $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Ensure URLs are absolute
        foreach ($files as &$f) {
            $f['full_url'] = url('/' . $f['path']);
        }
        
        echo json_encode($files);
        exit;
    }

    public function upload() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCsrf();
                
                if (!isset($_FILES['file'])) {
                    $this->redirect(url('/admin/media?error=' . urlencode('Không có tệp nào được chọn.')));
                }

                $file = $_FILES['file'];
                if ($file['error'] === 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $allowed)) {
                        $rootDir = dirname(dirname(__DIR__));
                        $uploadRelative = 'uploads/media/';
                        $uploadDir = $rootDir . '/public/' . $uploadRelative;
                        
                        // Resolve absolute path properly for Windows/XAMPP
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        
                        $filename = time() . '_' . uniqid() . '.' . $ext;
                        $destPath = $uploadDir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $destPath)) {
                            $stmt = $this->db->prepare("INSERT INTO media (filename, original_name, file_type, file_size, path, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $filename,
                                $file['name'],
                                $file['type'],
                                $file['size'],
                                $uploadRelative . $filename,
                                $_SESSION['admin_id']
                            ]);
                            
                            $this->auditService->log('UPLOAD_MEDIA', 'media', null, null, ['filename' => $filename]);
                            $this->redirect(url('/admin/media?success=1'));
                        } else {
                            throw new \Exception('Không thể di chuyển tệp tải lên vào thư mục đích.');
                        }
                    } else {
                        throw new \Exception('Định dạng tệp không được phép.');
                    }
                } else {
                    throw new \Exception('Lỗi tải tệp: Code ' . $file['error']);
                }
            } catch (\Exception $e) {
                die('Lỗi hệ thống: ' . $e->getMessage());
            }
        }
        $this->redirect(url('/admin/media'));
    }

    public function delete() {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $stmt = $this->db->prepare("SELECT * FROM media WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($file) {
                $rootDir = dirname(dirname(__DIR__));
                $absPath = $rootDir . '/public/' . $file['path'];
                if (file_exists($absPath)) unlink($absPath);
                
                $del = $this->db->prepare("DELETE FROM media WHERE id = ?");
                $del->execute([$id]);
                
                $this->auditService->log('DELETE_MEDIA', 'media', $id);
            }
        }
        $this->redirect(url('/admin/media?msg=deleted'));
    }
}
