<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\FileUploader;
use App\Models\MasterData;

class BackupController extends Controller
{
    protected $backupDir;
    protected $masterData;

    public function __construct()
    {
        $this->requireAdmin();
        $this->backupDir = dirname(dirname(__DIR__)) . '/storage/backups';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
        $this->masterData = new MasterData();
    }

    public function index()
    {
        $service = new \App\Services\BackupService($this->backupDir);
        $localBackups = $service->getLocalBackups();

        // Load schedule settings
        $settings = [
            'backup_enabled' => $this->masterData->getSetting('backup_enabled') ?? '0',
            'backup_hour'    => $this->masterData->getSetting('backup_hour') ?? '1',
            'backup_minute'  => $this->masterData->getSetting('backup_minute') ?? '0',
            'backup_last_run'    => $this->masterData->getSetting('backup_last_run') ?? '',
            'backup_last_status' => $this->masterData->getSetting('backup_last_status') ?? '',
            'backup_last_file'   => $this->masterData->getSetting('backup_last_file') ?? '',
        ];

        $cronUrl = ($_ENV['APP_URL'] ?? 'http://localhost/TS')
                 . '/api/cron/backup?key=' . ($_ENV['CRON_SECRET_KEY'] ?? '');

        $this->view('admin/system/backup', [
            'title'        => 'Quản lý Sao lưu',
            'localBackups' => $localBackups,
            'settings'     => $settings,
            'cronUrl'      => $cronUrl,
            'currentDb'    => $_ENV['DB_DATABASE'] ?? 'postgres',
            'dbHost'       => $_ENV['DB_HOST'] ?? '',
        ]);
    }

    public function create()
    {
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') 
               || (isset($_GET['ajax']) && $_GET['ajax'] == '1');

        try {
            $isTest = isset($_GET['test']) && $_GET['test'] == '1';
            $service = new \App\Services\BackupService($this->backupDir);
            $result = $service->run($isTest);

            if ($result['success']) {
                try {
                    $this->masterData->setSetting('backup_last_run', date('Y-m-d H:i:s'));
                    $this->masterData->setSetting('backup_last_status', 'success');
                    $this->masterData->setSetting('backup_last_file', $result['file']);
                } catch (\Exception $dbEx) {
                    // Fail silently
                }

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => true, 'message' => 'Tạo bản sao lưu thành công!', 'file' => $result['file']]);
                    exit;
                }

                $this->redirect(url('/admin/system/backup?success=Tạo bản sao lưu thành công: ' . $result['file']));
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => false, 'message' => 'Lỗi khi tạo bản sao lưu']);
                    exit;
                }
                $this->redirect(url('/admin/system/backup?error=Lỗi khi tạo bản sao lưu'));
            }
        } catch (\Exception $e) {
            try {
                $this->masterData->setSetting('backup_last_run', date('Y-m-d H:i:s'));
                $this->masterData->setSetting('backup_last_status', substr('failed: ' . $e->getMessage(), 0, 200));
            } catch (\Exception $dbEx) {
                // Fail silently
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
                exit;
            }

            $this->redirect(url('/admin/system/backup?error=Lỗi: ' . urlencode($e->getMessage())));
        }
    }

    public function delete()
    {
        $name = $_GET['name'] ?? '';
        $type = $_GET['type'] ?? 'local';
        $id = $_GET['id'] ?? '';

        if ($type === 'local') {
            $safeName = basename($name); // Prevent path traversal
            $path = $this->backupDir . '/' . $safeName;
            if (file_exists($path)) {
                unlink($path);
                $this->redirect(url('/admin/system/backup?success=Đã xóa bản sao lưu cục bộ'));
            } else {
                $this->redirect(url('/admin/system/backup?error=File không tồn tại'));
            }
        } else {
            try {
                $uploader = new FileUploader($this->backupDir, 'google');
                $uploader->setGoogleConfig(
                    dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . ($_ENV['GOOGLE_CLIENT_SECRET'] ?? 'client_secret.json'),
                    dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . ($_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json'),
                    $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? ''
                );
                if ($uploader->deleteFile($id)) {
                    $this->redirect(url('/admin/system/backup?success=Đã xóa bản sao lưu trên Cloud'));
                } else {
                    $this->redirect(url('/admin/system/backup?error=Lỗi khi xóa file trên Cloud'));
                }
            } catch (\Exception $e) {
                $this->redirect(url('/admin/system/backup?error=Lỗi: ' . $e->getMessage()));
            }
        }
    }

    public function restore()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect(url('/admin/system/backup?error=Yêu cầu không hợp lệ.'));
                return;
            }

            $this->validateCsrf();

            $name = $_POST['name'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($name)) {
                throw new \Exception("Tên file không hợp lệ.");
            }

            if (empty($password)) {
                throw new \Exception("Vui lòng nhập mật khẩu xác nhận.");
            }

            // Verify admin password
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT mat_khau FROM public.quan_tri_vien WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id'] ?? 0]);
            $admin = $stmt->fetch();

            if (!$admin || !password_verify($password, $admin['mat_khau'])) {
                throw new \Exception("Mật khẩu xác nhận không chính xác.");
            }

            $service = new \App\Services\BackupService($this->backupDir);
            $result = $service->restore($name);

            if ($result['success']) {
                $this->redirect(url('/admin/system/backup?success=Khôi phục thành công từ file: ' . basename($name)));
            } else {
                $this->redirect(url('/admin/system/backup?error=Lỗi khi khôi phục'));
            }
        } catch (\Exception $e) {
            $this->redirect(url('/admin/system/backup?error=Lỗi: ' . urlencode($e->getMessage())));
        }
    }

    public function download()
    {
        $name = $_GET['name'] ?? '';
        if (empty($name)) {
            $this->redirect(url('/admin/system/backup?error=Tên file không hợp lệ'));
        }

        $safeName = basename($name);
        $path = $this->backupDir . '/' . $safeName;

        if (file_exists($path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $safeName . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
        } else {
            $this->redirect(url('/admin/system/backup?error=File không tồn tại'));
        }
    }

    public function saveSettings()
    {
        $enabled = $_POST['backup_enabled'] ?? '0';
        $hour = (int)($_POST['backup_hour'] ?? 1);
        $hour = max(0, min(23, $hour));
        
        $minute = (int)($_POST['backup_minute'] ?? 0);
        $minute = max(0, min(59, $minute));

        $this->masterData->setSetting('backup_enabled', $enabled);
        $this->masterData->setSetting('backup_hour', (string)$hour);
        $this->masterData->setSetting('backup_minute', (string)$minute);

        $this->redirect(url('/admin/system/backup?success=Đã lưu cài đặt sao lưu tự động'));
    }
}
