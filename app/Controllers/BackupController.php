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
            'backup_last_log'    => json_decode($this->masterData->getSetting('backup_last_log') ?? '[]', true),
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
            ignore_user_abort(true);
            set_time_limit(0);

            $isTest = isset($_GET['test']) && $_GET['test'] == '1';
            $service = new \App\Services\BackupService($this->backupDir);
            $result = $service->run($isTest);

            if ($result['success']) {
                try {
                    $this->masterData->setSetting('backup_last_run', date('Y-m-d H:i:s'));
                    $this->masterData->setSetting('backup_last_status', 'success');
                    $this->masterData->setSetting('backup_last_file', $result['file']);
                    $this->masterData->setSetting('backup_last_log', json_encode($result['log'] ?? []));
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

    public function bulkDelete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(url('/admin/system/backup?error=Yêu cầu không hợp lệ.'));
            return;
        }

        $this->validateCsrf();

        $files = $_POST['files'] ?? [];
        if (empty($files) || !is_array($files)) {
            $this->redirect(url('/admin/system/backup?error=Chưa chọn bản sao lưu nào để xóa.'));
            return;
        }

        $deleted = 0;
        $errors = 0;
        foreach ($files as $name) {
            $safeName = basename($name); // Prevent path traversal
            $path = $this->backupDir . '/' . $safeName;
            if (file_exists($path)) {
                unlink($path);
                $deleted++;
            } else {
                $errors++;
            }
        }

        if ($deleted > 0) {
            $msg = "Đã xóa thành công {$deleted} bản sao lưu cục bộ.";
            if ($errors > 0) $msg .= " ({$errors} file không tồn tại)";
            $this->redirect(url('/admin/system/backup?success=' . urlencode($msg)));
        } else {
            $this->redirect(url('/admin/system/backup?error=Không tìm thấy file nào để xóa.'));
        }
    }

    public function restore()
    {
        try {
            ignore_user_abort(true);
            set_time_limit(3600);
            ini_set('max_execution_time', '3600');

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect(url('/admin/system/backup?error=Yêu cầu không hợp lệ.'));
                return;
            }

            $this->validateCsrf();

            $isUploadedFile = isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] !== UPLOAD_ERR_NO_FILE;
            $name = $_POST['name'] ?? '';
            $password = $_POST['password'] ?? '';

            if (!$isUploadedFile && empty($name)) {
                throw new \Exception("Vui lòng chọn tệp tin hoặc chọn một bản sao lưu sẵn có.");
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

            $tempFilePath = null;
            $restoreFileName = $name;
            $displayFileName = $name;

            if ($isUploadedFile) {
                $file = $_FILES['backup_file'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new \Exception("Lỗi tải tệp tin lên máy chủ (Mã lỗi: {$file['error']}).");
                }

                $originalName = $file['name'];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                
                // Support double extensions
                if (str_ends_with(strtolower($originalName), '.sql.gz')) {
                    $ext = 'sql.gz';
                }

                if (!in_array($ext, ['backup', 'sql', 'gz', 'sql.gz'])) {
                    throw new \Exception("Định dạng tệp khôi phục không hợp lệ. Chỉ chấp nhận tệp tin .backup, .sql hoặc .sql.gz");
                }

                // Create a secure unique temporary name in storage/backups
                $tempName = 'temp_upload_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . ($ext === 'sql.gz' ? 'sql.gz' : $ext);
                $tempFilePath = $this->backupDir . DIRECTORY_SEPARATOR . $tempName;
                
                if (!move_uploaded_file($file['tmp_name'], $tempFilePath)) {
                    throw new \Exception("Không thể sao chép tệp tin khôi phục vào thư mục lưu trữ.");
                }

                $restoreFileName = $tempName;
                $displayFileName = $originalName;
            }

            // Explicitly close the active database connection to release all locks held by the current PHP thread
            \App\Core\Database::closeConnection();

            $service = new \App\Services\BackupService($this->backupDir);
            
            try {
                $result = $service->restore($restoreFileName);
            } finally {
                // Securely clean up the temporary uploaded file in all scenarios
                if ($tempFilePath && file_exists($tempFilePath)) {
                    unlink($tempFilePath);
                }
            }

            if ($result['success']) {
                $this->redirect(url('/admin/system/backup?success=Khôi phục thành công từ file: ' . basename($displayFileName)));
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
