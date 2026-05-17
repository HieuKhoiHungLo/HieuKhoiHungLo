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

        // Google Drive backups
        $driveBackups = [];
        try {
            $uploader = new FileUploader($this->backupDir, 'google');
            $uploader->setGoogleConfig(
                DIRECTORY_SEPARATOR . ($_ENV['GOOGLE_CLIENT_SECRET'] ?? 'client_secret.json'),
                DIRECTORY_SEPARATOR . ($_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json'),
                $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? ''
            );
            $backupFolderId = $uploader->findFolder('Backups');
            if ($backupFolderId) {
                $files = $uploader->listFiles($backupFolderId);
                if ($files) {
                    foreach ($files as $file) {
                        $driveBackups[] = [
                            'id'   => $file->id,
                            'name' => $file->name,
                            'size' => round(($file->size ?? 0) / 1024, 2) . ' KB',
                            'date' => date('Y-m-d H:i:s', strtotime($file->createdTime)),
                            'type' => 'Cloud'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Google Drive not configured, ignore
        }

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
            'driveBackups' => $driveBackups,
            'settings'     => $settings,
            'cronUrl'      => $cronUrl,
            'currentDb'    => $_ENV['DB_DATABASE'] ?? 'postgres',
            'dbHost'       => $_ENV['DB_HOST'] ?? '',
        ]);
    }

    public function create()
    {
        try {
            $isTest = isset($_GET['test']) && $_GET['test'] == '1';
            $service = new \App\Services\BackupService($this->backupDir);
            $result = $service->run($isTest);

            if ($result['success']) {
                // Save last run info
                $this->masterData->setSetting('backup_last_run', date('Y-m-d H:i:s'));
                $this->masterData->setSetting('backup_last_status', 'success');
                $this->masterData->setSetting('backup_last_file', $result['file']);

                $this->redirect(url('/admin/system/backup?success=Tạo bản sao lưu thành công: ' . $result['file']));
            } else {
                $this->redirect(url('/admin/system/backup?error=Lỗi khi tạo bản sao lưu'));
            }
        } catch (\Exception $e) {
            $this->masterData->setSetting('backup_last_run', date('Y-m-d H:i:s'));
            $this->masterData->setSetting('backup_last_status', 'failed: ' . $e->getMessage());
            $this->redirect(url('/admin/system/backup?error=Lỗi: ' . $e->getMessage()));
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
            $name = $_GET['name'] ?? '';

            if (empty($name)) {
                throw new \Exception("Tên file không hợp lệ.");
            }

            $service = new \App\Services\BackupService($this->backupDir);
            $result = $service->restore($name);

            if ($result['success']) {
                $this->redirect(url('/admin/system/backup?success=Khôi phục thành công từ file: ' . basename($name)));
            } else {
                $this->redirect(url('/admin/system/backup?error=Lỗi khi khôi phục'));
            }
        } catch (\Exception $e) {
            $this->redirect(url('/admin/system/backup?error=Lỗi: ' . $e->getMessage()));
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
