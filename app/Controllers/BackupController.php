<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\FileUploader;

class BackupController extends Controller
{
    protected $backupDir;
    protected $uploader;

    public function __construct()
    {
        $this->requireAdmin();
        $this->backupDir = dirname(dirname(__DIR__)) . '/storage/backups';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }

        $this->uploader = new FileUploader($this->backupDir, 'google');
        $this->uploader->setGoogleConfig(
            DIRECTORY_SEPARATOR . ($_ENV['GOOGLE_CLIENT_SECRET'] ?? 'client_secret.json'),
            DIRECTORY_SEPARATOR . ($_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json'),
            $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? ''
        );
    }

    public function index()
    {
        $localFiles = glob($this->backupDir . '/*.sql.gz');
        $backups = [];

        foreach ($localFiles as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => round(filesize($file) / 1024, 2) . ' KB',
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'type' => 'Local'
            ];
        }

        // GDrive Files
        $driveBackups = [];
        $backupFolderId = $this->uploader->findFolder('Backups');
        if ($backupFolderId) {
            $files = $this->uploader->listFiles($backupFolderId);
            if ($files) {
                foreach ($files as $file) {
                    $driveBackups[] = [
                        'id' => $file->id,
                        'name' => $file->name,
                        'size' => round(($file->size ?? 0) / 1024, 2) . ' KB',
                        'date' => date('Y-m-d H:i:s', strtotime($file->createdTime)),
                        'type' => 'Cloud'
                    ];
                }
            }
        }

        $this->view('admin/system/backup', [
            'title' => 'Quản lý Sao lưu',
            'localBackups' => $backups,
            'driveBackups' => $driveBackups
        ]);
    }

    public function create()
    {
        try {
            $isTest = isset($_GET['test']) && $_GET['test'] == '1';
            $service = new \App\Services\BackupService($this->backupDir);
            $result = $service->run($isTest);

            if ($result['success']) {
                $this->redirect(url('/admin/system/backup?success=Tạo bản sao lưu thành công: ' . $result['file']));
            } else {
                $this->redirect(url('/admin/system/backup?error=Lỗi khi tạo bản sao lưu'));
            }
        } catch (\Exception $e) {
            $this->redirect(url('/admin/system/backup?error=Lỗi: ' . $e->getMessage()));
        }
    }

    public function delete()
    {
        $name = $_GET['name'] ?? '';
        $type = $_GET['type'] ?? 'local';
        $id = $_GET['id'] ?? '';

        if ($type === 'local') {
            $path = $this->backupDir . '/' . $name;
            if (file_exists($path)) {
                unlink($path);
                $this->redirect(url('/admin/system/backup?success=Đã xóa bản sao lưu cục bộ'));
            }
        } else {
            $uploader = new \App\Core\FileUploader($this->backupDir, 'google');
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
        }
    }

    public function restore()
    {
        try {
            $name = $_GET['name'] ?? '';
            $targetDb = $_GET['target_db'] ?? null;

            if (empty($name)) {
                throw new \Exception("Tên file không hợp lệ.");
            }

            $service = new \App\Services\BackupService($this->backupDir);
            $result = $service->restore($name, $targetDb);

            if ($result['success']) {
                $this->redirect(url('/admin/system/backup?success=Khôi phục thành công' . ($targetDb ? " vào database $targetDb" : "")));
            } else {
                $this->redirect(url('/admin/system/backup?error=Lỗi khi khôi phục'));
            }
        } catch (\Exception $e) {
            $this->redirect(url('/admin/system/backup?error=Lỗi: ' . $e->getMessage()));
        }
    }
}
