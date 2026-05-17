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

        if ($isTest) {
            $date = date('Y-m-d_H-i-s');
            $filename = "backup_{$date}_TEST.backup";
            $filePath = "{$this->backupDir}/{$filename}";
            file_put_contents($filePath, "-- Mock Backup Content\n");
            $results[] = "[TEST] Tạo file mock thành công: {$filename}";
            return ['success' => true, 'log' => $results, 'file' => $filename];
        }

        $pgDump = $this->findBinary('pg_dump');
        if (!$pgDump) {
            throw new \Exception("Không tìm thấy pg_dump. Hãy cấu hình PG_BIN_PATH trong file .env (hiện tại: '{$this->pgBinPath}')");
        }

        $date = date('Y-m-d_H-i-s');
        $filename = "backup_{$date}.backup";
        $filePath = "{$this->backupDir}/{$filename}";

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

        $cmd = "\"{$pgRestore}\" "
             . "-h {$this->restoreConfig['host']} "
             . "-p {$this->restoreConfig['port']} "
             . "-U {$this->restoreConfig['username']} "
             . "-d {$targetDb} "
             . "--clean --if-exists --no-owner --no-privileges -v "
             . "\"{$filePath}\" 2>&1";

        exec($cmd, $output, $returnCode);

        // pg_restore returns 1 for non-fatal warnings (e.g., "relation does not exist" during --clean)
        if ($returnCode > 1) {
            throw new \Exception("Khôi phục thất bại (code {$returnCode}): " . implode("\n", array_slice($output, -10)));
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
            $backups[] = [
                'name' => basename($file),
                'size' => $size >= 1048576
                    ? round($size / 1048576, 2) . ' MB'
                    : round($size / 1024, 2) . ' KB',
                'size_bytes' => $size,
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'timestamp' => filemtime($file),
                'type' => 'Local',
                'format' => str_ends_with(basename($file), '.backup') ? 'custom' : 'legacy',
            ];
        }

        // Sort by timestamp descending (newest first)
        usort($backups, fn($a, $b) => $b['timestamp'] - $a['timestamp']);

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
}
