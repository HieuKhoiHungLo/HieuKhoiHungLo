<?php
namespace App\Core;

class FileUploader {
    protected $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf'
    ];
    
    protected $maxSize = 31457280; // 30MB default
    protected $targetDir;
    protected $errors = [];

    protected $driver = 'local'; // 'local' or 'google'
    protected $googleService;
    protected $googleFolderId;
    protected $googleTokenPath;
    protected $googleClientSecretPath;

    public function __construct($targetDir, $driver = 'local') {
        $this->targetDir = rtrim($targetDir, '/');
        $this->driver = $driver;

        if ($this->driver === 'local') {
            if (!is_dir($this->targetDir)) {
                 mkdir($this->targetDir, 0777, true);
            }
        }
    }

    public function setAllowedMimes(array $mimes) {
        $this->allowedMimes = $mimes;
    }

    public function setMaxSize($bytes) {
        $this->maxSize = $bytes;
    }

    public function setGoogleConfig($clientSecretPath, $tokenPath, $folderId) {
        if ($this->driver === 'google') {
            if (!file_exists($clientSecretPath)) {
                $this->errors[] = "Không tìm thấy file client_secret Google ($clientSecretPath).";
                return;
            }

            if (!class_exists(\Google\Client::class)) {
                $this->errors[] = "Thư viện Google Client chưa được cài đặt.";
                return;
            }

            try {
                $client = new \Google\Client();
                $client->setAuthConfig($clientSecretPath);
                $client->addScope(\Google\Service\Drive::DRIVE_FILE);
                $client->setAccessType('offline');
                
                if (file_exists($tokenPath)) {
                    $accessToken = json_decode(file_get_contents($tokenPath), true);
                    $client->setAccessToken($accessToken);
                } else {
                    $this->errors[] = "Không tìm thấy file token.json. Bạn hãy chạy 'php generate_token.php' để tạo token.";
                    return;
                }

                // If token expired, refresh it
                if ($client->isAccessTokenExpired()) {
                    if ($client->getRefreshToken()) {
                        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
                    } else {
                        $this->errors[] = "Token Google Drive đã hết hạn và không có Refresh Token. Bạn hãy chạy 'php generate_token.php' để cấp quyền lại.";
                        return;
                    }
                }

                $this->googleService = new \Google\Service\Drive($client);
                $this->googleFolderId = $folderId;
                error_log("GOOGLE_DRIVE_CONFIG_SUCCESS: FolderID=$folderId");
            } catch (\Exception $e) {
                error_log("GOOGLE_DRIVE_CONFIG_ERROR: " . $e->getMessage());
                $this->errors[] = "Lỗi xác thực Google Drive: " . $e->getMessage();
            }
        }
    }

    public function upload($file, $prefix = 'file') {
        if (!isset($file['error']) || is_array($file['error'])) {
            $this->errors[] = "Tham số file không hợp lệ.";
            return false;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = "Lỗi upload file (Code: {$file['error']}).";
            return false;
        }

        // 1. Size Check
        if ($file['size'] > $this->maxSize) {
            $mb = round($this->maxSize / 1024 / 1024, 2);
            $this->errors[] = "File vượt quá dung lượng cho phép ({$mb}MB).";
            return false;
        }

        // 2. MIME Check
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        // Map common MIME to standard extension
        $mimeToExt = array_flip($this->allowedMimes);
        $ext = $mimeToExt[$mime] ?? null;

        if (!$ext) {
            // Fallback check for similar mimes
            foreach ($this->allowedMimes as $e => $m) {
                if ($m === $mime) {
                    $ext = $e;
                    break;
                }
            }
        }

        if (!$ext) {
            $this->errors[] = "Định dạng file không hợp lệ ($mime).";
            return false;
        }

        $filename = sprintf('%s.%s', $prefix, $ext);

        // 3. STORAGE
        if ($this->driver === 'google') {
            return $this->uploadToGoogleDrive($file['tmp_name'], $filename, $mime);
        } else {
            return $this->uploadToLocal($file['tmp_name'], $filename);
        }
    }

    protected function uploadToLocal($tmpName, $filename) {
        $destination = $this->targetDir . '/' . $filename;
        if (!move_uploaded_file($tmpName, $destination)) {
            $this->errors[] = "Không thể lưu file cục bộ.";
            return false;
        }
        return $filename; // Return filename only, path constructed by controller if needed
    }

    protected function uploadToGoogleDrive($tmpName, $filename, $mimeType) {
        if (!$this->googleService || empty($this->googleFolderId)) {
            $this->errors[] = "Chưa cấu hình Google Drive hoặc kết nối thất bại.";
            return false;
        }

        try {
            // Determine parent folder. If a custom folder ID is set via setTargetFolderId, use it.
            // Otherwise use the root configured folder.
            $parentId = $this->targetFolderId ?? $this->googleFolderId;

            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $filename,
                'parents' => [$parentId]
            ]);

            $content = file_get_contents($tmpName);
            $file = $this->googleService->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink, parents',
                'supportsAllDrives' => true
            ]);

            // Set permission to anyone with link (for image preview)
            try {
                $permission = new \Google\Service\Drive\Permission();
                $permission->setRole('reader');
                $permission->setType('anyone');
                $this->googleService->permissions->create($file->id, $permission, ['supportsAllDrives' => true]);
            } catch (\Exception $e) {
                // Ignore permission error if restricted by organization, 
                // but usually needed for student previews.
            }

            // Return direct display link (thumbnail API is more reliable for direct <img> tags)
            return "https://drive.google.com/thumbnail?id=" . $file->id . "&sz=w1000"; 

        } catch (\Exception $e) {
            error_log("GOOGLE_UPLOAD_EX: " . $e->getMessage());
            $this->errors[] = "Lỗi upload lên Google Drive: " . $e->getMessage();
            return false;
        }
    }

    // New property to allow overriding the target folder per upload
    protected $targetFolderId = null;

    public function setTargetFolderId($id) {
        $this->targetFolderId = $id;
    }

    public function findFolder($name, $parentId = null) {
        if (!$this->googleService) return false;
        $parentId = $parentId ?? $this->googleFolderId;
        
        $query = "mimeType = 'application/vnd.google-apps.folder' and name = '$name' and '$parentId' in parents and trashed = false";
        
        try {
            $files = $this->googleService->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)',
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true
            ]);

            if (count($files->getFiles()) > 0) {
                return $files->getFiles()[0]->id;
            }
            return null;
        } catch (\Exception $e) {
            $this->errors[] = "Lỗi tìm folder: " . $e->getMessage();
            return false;
        }
    }

    public function createFolder($name, $parentId = null) {
        if (!$this->googleService) return false;
        $parentId = $parentId ?? $this->googleFolderId;

        $fileMetadata = new \Google\Service\Drive\DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId]
        ]);

        try {
            $file = $this->googleService->files->create($fileMetadata, [
                'fields' => 'id',
                'supportsAllDrives' => true
            ]);
            return $file->id;
        } catch (\Exception $e) {
            $this->errors[] = "Lỗi tạo folder: " . $e->getMessage();
            return false;
        }
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getFirstError() {
         return $this->errors[0] ?? null;
    }
}
