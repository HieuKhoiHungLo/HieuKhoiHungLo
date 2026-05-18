<?php

namespace App\Services;

use App\Core\FileUploader;

class BackupService
{
    protected $backupDir;
    protected $dbConfig;
    protected $restoreConfig;
    protected $pgBinPath;

    public function __construct($backupDir = null)
    {
        $this->backupDir = $backupDir ?? dirname(dirname(__DIR__)) . '/storage/backups';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }

        $this->dbConfig = [
            'host'     => $_ENV['DB_HOST'],
            'port'     => $_ENV['DB_PORT'],
            'database' => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
        ];

        // Restore may need a direct connection (Supabase pooler doesn't support pg_restore)
        $this->restoreConfig = [
            'host'     => $_ENV['DB_RESTORE_HOST'] ?? $this->dbConfig['host'],
            'port'     => $_ENV['DB_RESTORE_PORT'] ?? $this->dbConfig['port'],
            'username' => $_ENV['DB_RESTORE_USERNAME'] ?? $this->dbConfig['username'],
            'password' => $_ENV['DB_RESTORE_PASSWORD'] ?? $this->dbConfig['password'],
        ];

        $this->pgBinPath = trim($_ENV['PG_BIN_PATH'] ?? '', '"\'');
    }

    /**
     * Run a backup using pg_dump in Custom format (-F c).
     */
    public function run($isTest = false)
    {
        $results = [];
        $results[] = "[INFO] Bắt đầu sao lưu...";

        $filename = $this->generateBackupFilename($isTest);
        $filePath = "{$this->backupDir}/{$filename}";

        if ($isTest) {
            file_put_contents($filePath, "-- Mock Backup Content\n");
            $results[] = "[TEST] Tạo file mock thành công: {$filename}";
            return ['success' => true, 'log' => $results, 'file' => $filename];
        }

        $pgDump = $this->findBinary('pg_dump');
        if (!$pgDump) {
            throw new \Exception("Không tìm thấy pg_dump. Hãy cấu hình PG_BIN_PATH trong file .env (hiện tại: '{$this->pgBinPath}')");
        }

        putenv("PGPASSWORD={$this->dbConfig['password']}");

        $cmd = "\"{$pgDump}\" "
             . "-h {$this->dbConfig['host']} "
             . "-p {$this->dbConfig['port']} "
             . "-U {$this->dbConfig['username']} "
             . "-d {$this->dbConfig['database']} "
             . "-F c -b -v --no-owner --no-privileges "
             . "-f \"{$filePath}\" 2>&1";

        $results[] = "[INFO] Đang chạy pg_dump...";
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($filePath) || filesize($filePath) === 0) {
            if (file_exists($filePath)) unlink($filePath);
            $errorLines = array_slice($output, -5);
            throw new \Exception("Sao lưu thất bại (code {$returnCode}): " . implode("\n", $errorLines));
        }

        $sizeMb = round(filesize($filePath) / 1024 / 1024, 2);
        $results[] = "[SUCCESS] Sao lưu hoàn tất: {$filename} ({$sizeMb} MB)";

        // Auto-upload to Google Drive if configured
        $googleClientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? 'client_secret.json';
        $googleTokenFile = $_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json';
        $googleDriveFolderId = $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '';

        $secretPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . $googleClientSecret;
        $tokenPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . $googleTokenFile;

        if (file_exists($secretPath) && file_exists($tokenPath)) {
            try {
                $results[] = "[INFO] Đang đồng bộ hóa lên Google Drive...";
                $uploader = new FileUploader($this->backupDir, 'google');
                $uploader->setGoogleConfig($secretPath, $tokenPath, $googleDriveFolderId);
                
                $backupFolderId = $uploader->findFolder('Backups');
                if (!$backupFolderId) {
                    $backupFolderId = $uploader->createFolder('Backups');
                }

                if ($backupFolderId) {
                    $uploader->setTargetFolderId($backupFolderId);
                    $uploadResult = $uploader->uploadLocalFile($filePath, $filename, 'application/octet-stream');
                    if ($uploadResult) {
                        $results[] = "[SUCCESS] Đã tự động đồng bộ thành công lên Google Drive.";
                    } else {
                        $results[] = "[WARNING] Đồng bộ Google Drive thất bại: " . ($uploader->getFirstError() ?? 'Không rõ lỗi');
                    }
                } else {
                    $results[] = "[WARNING] Không tìm thấy hoặc không thể tạo folder 'Backups' trên Google Drive.";
                }
            } catch (\Exception $e) {
                $results[] = "[WARNING] Lỗi khi đồng bộ Google Drive: " . $e->getMessage();
            }
        } else {
            $results[] = "[INFO] Bỏ qua đồng bộ Cloud (Chưa cấu hình đầy đủ Google API).";
        }

        return ['success' => true, 'log' => $results, 'file' => $filename];
    }

    /**
     * Restore a backup file using pg_restore (Custom format) or psql (Plain text legacy).
     */
    public function restore($filename, $targetDb = null)
    {
        $targetDb = $targetDb ?: $this->dbConfig['database'];
        $filePath = "{$this->backupDir}/{$filename}";

        if (!file_exists($filePath)) {
            throw new \Exception("Không tìm thấy file: {$filename}");
        }

        if (str_contains($filename, '_TEST')) {
            return ['success' => true, 'log' => ["[TEST] Mô phỏng khôi phục thành công vào database: {$targetDb}"]];
        }

        // Terminate other active sessions to avoid locking table DROPs and stale connection bugs (due to PDO persistent connections)
        try {
            $dsn = "pgsql:host={$this->restoreConfig['host']};port={$this->restoreConfig['port']};dbname={$targetDb}";
            $pdo = new \PDO($dsn, $this->restoreConfig['username'], $this->restoreConfig['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_PERSISTENT => false
            ]);
            $stmt = $pdo->prepare("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ? AND pid <> pg_backend_pid()");
            $stmt->execute([$targetDb]);
            $pdo = null; // Instantly disconnect
        } catch (\Exception $e) {
            // Fail silently if we lack permissions to terminate database backends
        }

        $isCustomFormat = str_ends_with(strtolower($filename), '.backup');

        putenv("PGPASSWORD={$this->restoreConfig['password']}");

        if ($isCustomFormat) {
            return $this->restoreCustomFormat($filePath, $targetDb);
        }

        return $this->restoreLegacy($filePath, $filename, $targetDb);
    }

    /**
     * Restore using pg_restore for Custom format (.backup) files.
     */
    protected function restoreCustomFormat($filePath, $targetDb)
    {
        $pgRestore = $this->findBinary('pg_restore');
        if (!$pgRestore) {
            throw new \Exception("Không tìm thấy pg_restore. Hãy cấu hình PG_BIN_PATH trong file .env");
        }

        $jobs = (int)($_ENV['DB_RESTORE_JOBS'] ?? '4');
        $jobsOption = $jobs > 1 ? "-j {$jobs} " : "";

        $cmd = "\"{$pgRestore}\" "
             . "-h {$this->restoreConfig['host']} "
             . "-p {$this->restoreConfig['port']} "
             . "-U {$this->restoreConfig['username']} "
             . "-d {$targetDb} "
             . $jobsOption
             . "--clean --if-exists --no-owner --no-privileges -v "
             . "\"{$filePath}\" 2>&1";

        exec($cmd, $output, $returnCode);

        // Scan output for fatal errors (connection issues, authentication failures, missing binaries, etc.)
        $hasFatalError = false;
        $fatalErrorMsg = '';
        foreach ($output as $line) {
            if (preg_match('/(FATAL:|error:|could not connect|authentication failed|permission denied|not found|not recognized|no such file)/i', $line)) {
                $hasFatalError = true;
                $fatalErrorMsg = $line;
                break;
            }
        }

        // Throw exception if the command failed with a fatal error or returnCode is greater than 1
        if ($returnCode !== 0 && ($hasFatalError || $returnCode > 1)) {
            throw new \Exception("Khôi phục thất bại (code {$returnCode}): " . ($fatalErrorMsg ?: implode("\n", array_slice($output, -10))));
        }

        return ['success' => true, 'log' => $output];
    }

    /**
     * Legacy restore for .sql.gz files (backward compatibility).
     */
    protected function restoreLegacy($filePath, $filename, $targetDb)
    {
        $isGz = str_ends_with($filename, '.gz');
        $sqlPath = $filePath;

        if ($isGz) {
            $sqlPath = substr($filePath, 0, -3);
            $content = gzdecode(file_get_contents($filePath));
            if ($content === false) {
                throw new \Exception("Không thể giải nén file backup.");
            }
            file_put_contents($sqlPath, $content);
        }

        $psql = $this->findBinary('psql');
        if (!$psql) {
            if ($isGz && file_exists($sqlPath)) unlink($sqlPath);
            throw new \Exception("Không tìm thấy psql. Hãy cấu hình PG_BIN_PATH trong file .env");
        }

        $cmd = "\"{$psql}\" "
             . "-h {$this->restoreConfig['host']} "
             . "-p {$this->restoreConfig['port']} "
             . "-U {$this->restoreConfig['username']} "
             . "-d {$targetDb} "
             . "-f \"{$sqlPath}\" 2>&1";

        exec($cmd, $output, $returnCode);

        if ($isGz && file_exists($sqlPath)) unlink($sqlPath);

        if ($returnCode !== 0) {
            throw new \Exception("Khôi phục thất bại (code {$returnCode}): " . implode("\n", array_slice($output, -10)));
        }

        return ['success' => true, 'log' => $output];
    }

    /**
     * Get list of local backup files, sorted newest first.
     */
    public function getLocalBackups()
    {
        $files = array_merge(
            glob("{$this->backupDir}/*.backup") ?: [],
            glob("{$this->backupDir}/*.sql.gz") ?: []
        );

        $backups = [];
        foreach ($files as $file) {
            $size = filesize($file);
            $filename = basename($file);
            
            // Parse date from filename (pattern: YYYY-MM-DD_HH-ii-ss)
            $backupDate = date('Y-m-d H:i:s', filemtime($file));
            if (preg_match('/(\d{4}-\d{2}-\d{2})_(\d{2})-(\d{2})-(\d{2})/', $filename, $matches)) {
                // Reconstruct to YYYY-MM-DD HH:ii:ss
                $backupDate = $matches[1] . ' ' . $matches[2] . ':' . $matches[3] . ':' . $matches[4];
            }

            $backups[] = [
                'name' => $filename,
                'size' => $size >= 1048576
                    ? round($size / 1048576, 2) . ' MB'
                    : round($size / 1024, 2) . ' KB',
                'size_bytes' => $size,
                'date' => $backupDate,
                'timestamp' => strtotime($backupDate),
                'type' => 'Local',
                'format' => str_ends_with($filename, '.backup') ? 'custom' : 'legacy',
            ];
        }
        // Extract YYYY-MM-DD_HH-ii-ss from filename to sort chronologically descending in an absolutely robust way
        usort($backups, function($a, $b) {
            preg_match('/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $a['name'], $m1);
            preg_match('/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $b['name'], $m2);
            $timeA = $m1[1] ?? '';
            $timeB = $m2[1] ?? '';
            if ($timeA !== $timeB) {
                return strcmp($timeB, $timeA); // Descending chronological
            }
            return strnatcasecmp($b['name'], $a['name']);
        });

        return $backups;
    }

    /**
     * Find a PostgreSQL binary (pg_dump, pg_restore, psql).
     */
    protected function findBinary($name)
    {
        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $ext = $isWin ? '.exe' : '';

        // Try PG_BIN_PATH first
        if (!empty($this->pgBinPath)) {
            $path = rtrim($this->pgBinPath, '/\\') . DIRECTORY_SEPARATOR . $name . $ext;
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try system PATH
        if ($this->commandExists($name)) {
            return $name;
        }

        return null;
    }

    protected function commandExists($cmd)
    {
        $where = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'where' : 'which';
        $process = proc_open("$where $cmd", [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        if (!is_resource($process)) return false;
        fclose($pipes[1]);
        fclose($pipes[2]);
        return proc_close($process) === 0;
    }

    /**
     * Generate the backup filename based on school code, period code, and current timestamp
     */
    protected function generateBackupFilename($isTest = false)
    {
        $masterData = new \App\Models\MasterData();
        
        // 1. Get School Code
        $schoolCode = $masterData->getSetting('school_code') ?? $masterData->getSetting('ma_truong') ?? '';
        if (empty($schoolCode)) {
            $schoolName = $masterData->getSetting('school_name') ?? 'Trường Đại học Hùng Vương';
            if (str_contains(mb_strtolower($schoolName), 'hùng vương') || str_contains(mb_strtolower($schoolName), 'hung vuong')) {
                $schoolCode = 'THV';
            } else {
                $words = explode(' ', preg_replace('/\s+/', ' ', trim($schoolName)));
                $schoolCode = '';
                foreach ($words as $w) {
                    $firstChar = mb_substr($w, 0, 1);
                    if (mb_strtolower($firstChar) !== $firstChar) {
                        $schoolCode .= strtoupper($firstChar);
                    }
                }
                if (empty($schoolCode)) $schoolCode = 'THV';
            }
        }
        // Clean characters for safe filename
        $schoolCode = str_replace([' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $schoolCode);

        // 2. Get Period Code (Đợt tuyển sinh)
        $periodCode = '1';
        try {
            $sessionModel = new \App\Models\AdmissionSession();
            $activeSession = $sessionModel->getActiveSession() ?: $sessionModel->getLatestActiveSession();
            if (!empty($activeSession)) {
                if (isset($activeSession['ma_dot']) && !empty($activeSession['ma_dot'])) {
                    $periodCode = $activeSession['ma_dot'];
                } elseif (isset($activeSession['ten_dot'])) {
                    if (preg_match('/(\d+)/', $activeSession['ten_dot'], $matches)) {
                        $periodCode = $matches[1];
                    } else {
                        $periodCode = $activeSession['id'];
                    }
                } else {
                    $periodCode = $activeSession['id'] ?? '1';
                }
            }
        } catch (\Exception $e) {
            $periodCode = '1';
        }
        $periodCode = str_replace([' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $periodCode);

        $date = date('Y-m-d_H-i-s');
        $suffix = $isTest ? '_TEST' : '';
        
        return "{$schoolCode}_{$periodCode}_{$date}{$suffix}.backup";
    }
}
