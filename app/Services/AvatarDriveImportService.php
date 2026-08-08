<?php
namespace App\Services;

use App\Core\Database;
use App\Core\FileUploader;
use App\Services\DriveService;
use App\Models\AdmissionSession;

class AvatarDriveImportService {
    protected $db;
    protected $uploader;
    protected $driveService;
    protected $uploadDriver;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->uploadDriver = $_ENV['UPLOAD_DRIVER'] ?? 'google';
        
        $targetLocalDir = dirname(__DIR__, 2) . '/public/uploads/candidates/avatars';
        if (!is_dir($targetLocalDir)) {
            @mkdir($targetLocalDir, 0777, true);
        }

        $this->uploader = new FileUploader($targetLocalDir, $this->uploadDriver);
        
        if ($this->uploadDriver === 'google') {
            $clientSecretPath = $this->resolveConfigPath($_ENV['GOOGLE_CLIENT_SECRET'] ?? '', 'client_secret.json');
            $tokenPath = $this->resolveConfigPath($_ENV['GOOGLE_TOKEN_FILE'] ?? '', 'token.json');
            $folderId = $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '';
            
            $this->uploader->setGoogleConfig($clientSecretPath, $tokenPath, $folderId);
            $this->driveService = new DriveService($this->uploader);
        }
    }

    private function resolveConfigPath($envPath, $defaultFilename) {
        if (!empty($envPath) && file_exists($envPath)) {
            return $envPath;
        }
        $rootPath = dirname(__DIR__, 2) . '/' . $defaultFilename;
        if (file_exists($rootPath)) {
            return $rootPath;
        }
        return $defaultFilename;
    }

    /**
     * Lấy thông tin đợt tuyển sinh (Năm & Tên Đợt)
     */
    private function getSessionInfo($sessionId) {
        $stmt = $this->db->prepare("
            SELECT dt.*, 
                   COALESCE(dt.nam_tuyen_sinh, nts.nam, dt.dm_nam_tuyen_sinh_nam, EXTRACT(YEAR FROM CURRENT_DATE)::integer) as nam_tuyen_sinh
            FROM dot_tuyen_sinh dt
            LEFT JOIN dm_nam_tuyen_sinh nts ON dt.dm_nam_tuyen_sinh_nam = nts.nam
            WHERE dt.id = ?
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(\PDO::FETCH_ASSOC);

        $year = $session['nam_tuyen_sinh'] ?? date('Y');
        $sessionName = $session['ten_dot'] ?? ('Dot_' . $sessionId);

        return [
            'year' => $year,
            'session_name' => $sessionName,
            'session' => $session
        ];
    }

    /**
     * Import danh sách ảnh thẻ từ file ZIP và đẩy lên thư mục Google Drive của từng thí sinh
     */
    public function importFromZip($zipFilePath, $sessionId, $overwrite = true) {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        if (!class_exists('\ZipArchive')) {
            throw new \Exception("Máy chủ chưa bật extension php_zip.");
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            throw new \Exception("Không thể mở file ZIP. File có thể bị hỏng.");
        }

        // 1. Tạo thư mục tạm giải nén
        $tempExtractDir = dirname(__DIR__, 2) . '/public/uploads/temp/zip_avatar_' . uniqid() . '_' . time();
        if (!is_dir($tempExtractDir)) {
            @mkdir($tempExtractDir, 0777, true);
        }

        $zip->extractTo($tempExtractDir);
        $zip->close();

        // 2. Lấy danh sách CCCD trúng tuyển của đợt tuyển sinh này
        $stmtCCCD = $this->db->prepare("SELECT DISTINCT so_cccd FROM ket_qua_trung_tuyen WHERE session_id = ?");
        $stmtCCCD->execute([$sessionId]);
        $validCCCDs = array_flip($stmtCCCD->fetchAll(\PDO::FETCH_COLUMN));

        $sessionInfo = $this->getSessionInfo($sessionId);
        $year = $sessionInfo['year'];
        $sessionName = $sessionInfo['session_name'];

        // 3. Duyệt đệ quy tất cả các file ảnh trong thư mục tạm
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempExtractDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $totalFiles = 0;
        $inserted = 0;
        $unmatched = [];
        $errors = [];

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                
                // Bỏ qua các file ẩn/MacOS
                if (strpos($filename, '._') === 0 || strpos($file->getPathname(), '__MACOSX') !== false) {
                    continue;
                }

                $ext = strtolower($file->getExtension());
                if (!in_array($ext, $allowedExts)) {
                    continue;
                }

                $totalFiles++;

                // Trích xuất CCCD từ tên file (Ví dụ: 025308012171.jpg -> 025308012171)
                $rawName = pathinfo($filename, PATHINFO_FILENAME);
                $cccd = preg_replace('/[^0-9]/', '', $rawName); // Chỉ lấy ký tự số

                if (empty($cccd)) {
                    $errors[] = "File '{$filename}': Tên file không chứa số CCCD hợp lệ.";
                    continue;
                }

                if (!isset($validCCCDs[$cccd])) {
                    $unmatched[] = $cccd;
                    continue;
                }

                try {
                    // Tối ưu hóa kích thước ảnh (Resize & Compress) trước khi upload
                    $optimizedFile = $this->optimizeImage($file->getPathname());
                    $uploadSrcPath = $optimizedFile ?: $file->getPathname();

                    $avatarUrl = null;

                    // Buộc lưu local khi import ZIP để tối ưu tốc độ (không upload Google Drive trực tiếp)
                    $useGoogleUpload = false;

                    if ($useGoogleUpload && $this->uploadDriver === 'google' && $this->driveService) {
                        // Xác định / Tạo mới thư mục cá nhân của thí sinh trên Google Drive
                        $targetFolderId = $this->driveService->resolveCandidateFolder($year, $sessionName, $cccd);
                        if ($targetFolderId) {
                            $this->uploader->setTargetFolderId($targetFolderId);
                        }

                        $newFileName = $cccd . '_avatar_' . time() . '.jpg';
                        $avatarUrl = $this->uploader->uploadLocalFile($uploadSrcPath, $newFileName, 'image/jpeg');
                    } else {
                        // Local storage fallback
                        $newFileName = $cccd . '.jpg';
                        $localDest = dirname(__DIR__, 2) . '/public/uploads/candidates/avatars/' . $newFileName;
                        @copy($uploadSrcPath, $localDest);
                        $avatarUrl = 'public/uploads/candidates/avatars/' . $newFileName;
                    }

                    if ($optimizedFile && file_exists($optimizedFile)) {
                        @unlink($optimizedFile);
                    }

                    if ($avatarUrl) {
                        $this->syncAvatarToDatabase($cccd, $sessionId, $avatarUrl, $overwrite);
                        $inserted++;
                    } else {
                        $errors[] = "Lỗi upload ảnh cho CCCD '{$cccd}': " . implode(', ', $this->uploader->getErrors());
                    }
                } catch (\Exception $e) {
                    $errors[] = "Lỗi xử lý CCCD '{$cccd}': " . $e->getMessage();
                }
            }
        }

        // 4. Dọn dẹp thư mục tạm
        $this->deleteDirectory($tempExtractDir);

        return [
            'status' => true,
            'total' => $totalFiles,
            'inserted' => $inserted,
            'unmatched' => array_values(array_unique($unmatched)),
            'errors' => $errors
        ];
    }

    /**
     * Quét tự động thư mục hồ sơ thí sinh trên Google Drive để đồng bộ ảnh thẻ
     */
    public function scanAndSyncFromDrive($sessionId) {
        if ($this->uploadDriver !== 'google' || !$this->driveService) {
            throw new \Exception("Chức năng này yêu cầu kết nối Google Drive API.");
        }

        set_time_limit(0);

        $sessionInfo = $this->getSessionInfo($sessionId);
        $year = $sessionInfo['year'];
        $sessionName = $sessionInfo['session_name'];

        // Lấy toàn bộ thí sinh trong kết quả trúng tuyển
        $stmt = $this->db->prepare("SELECT DISTINCT so_cccd FROM ket_qua_trung_tuyen WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $cccddList = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $inserted = 0;
        $notFound = 0;
        $errors = [];

        foreach ($cccddList as $cccd) {
            try {
                $candidateFolderId = $this->driveService->resolveCandidateFolder($year, $sessionName, $cccd);
                if (!$candidateFolderId) {
                    $notFound++;
                    continue;
                }

                // Tìm file avatar trong thư mục thí sinh
                $query = "'$candidateFolderId' in parents and (name contains 'avatar' or name contains '$cccd') and trashed = false";
                $files = $this->uploader->findFilesByQuery($query);

                if (!empty($files)) {
                    $fileId = $files[0]['id'];
                    $avatarUrl = "https://drive.google.com/thumbnail?id=" . $fileId . "&sz=w1000";
                    
                    $this->syncAvatarToDatabase($cccd, $sessionId, $avatarUrl, true);
                    $inserted++;
                } else {
                    $notFound++;
                }
            } catch (\Exception $e) {
                $errors[] = "Lỗi quét CCCD '{$cccd}': " . $e->getMessage();
            }
        }

        return [
            'status' => true,
            'total' => count($cccddList),
            'inserted' => $inserted,
            'not_found' => $notFound,
            'errors' => $errors
        ];
    }

    /**
     * Cập nhật URL ảnh đại diện đồng bộ vào 3 bảng database: ket_qua_trung_tuyen, thi_sinh, users
     */
    private function syncAvatarToDatabase($cccd, $sessionId, $avatarUrl, $overwrite = true) {
        // Chỉ cập nhật bảng thi_sinh (do ket_qua_trung_tuyen và users không có cột lưu ảnh)
        if ($overwrite) {
            $stmt = $this->db->prepare("UPDATE thi_sinh SET anh_dai_dien = ? WHERE so_cccd = ?");
            $stmt->execute([$avatarUrl, $cccd]);
        } else {
            $stmt = $this->db->prepare("UPDATE thi_sinh SET anh_dai_dien = ? WHERE so_cccd = ? AND (anh_dai_dien IS NULL OR anh_dai_dien = '')");
            $stmt->execute([$avatarUrl, $cccd]);
        }
    }

    /**
     * Tối ưu hóa dung lượng & kích thước ảnh thẻ về chuẩn 3x4 (400x533px), chất lượng JPEG 85%
     */
    private function optimizeImage($sourcePath) {
        if (!function_exists('imagecreatefromstring')) {
            return false;
        }

        $imageData = @file_get_contents($sourcePath);
        if (!$imageData) return false;

        $srcImg = @imagecreatefromstring($imageData);
        if (!$srcImg) return false;

        $origW = imagesx($srcImg);
        $origH = imagesy($srcImg);

        // Kích thước chuẩn ảnh thẻ 3x4
        $targetW = 400;
        $targetH = 533;

        $targetImg = imagecreatetruecolor($targetW, $targetH);
        
        // Màu nền trắng
        $white = imagecolorallocate($targetImg, 255, 255, 255);
        imagefill($targetImg, 0, 0, $white);

        imagecopyresampled($targetImg, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

        $tmpFile = tempnam(sys_get_temp_dir(), 'avatar_opt_') . '.jpg';
        imagejpeg($targetImg, $tmpFile, 85);

        imagedestroy($srcImg);
        imagedestroy($targetImg);

        return $tmpFile;
    }

    /**
     * Dọn dẹp đệ quy thư mục tạm
     */
    private function deleteDirectory($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        return rmdir($dir);
    }
}
