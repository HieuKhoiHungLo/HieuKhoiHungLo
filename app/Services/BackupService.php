<?php

namespace App\Services;

use App\Core\FileUploader;
use Dotenv\Dotenv;

class BackupService
{
    protected $backupDir;
    protected $dbConfig;

    public function __construct($backupDir = null)
    {
        $this->backupDir = $backupDir ?? dirname(dirname(__DIR__)) . '/storage/backups';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }

        $this->dbConfig = [
            'host' => $_ENV['DB_HOST'],
            'port' => $_ENV['DB_PORT'],
            'database' => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
        ];
    }

    public function run($isTest = false)
    {
        $results = [];
        $results[] = "[INFO] Starting backup process...";

        if ($isTest) {
            $results[] = "[TEST MODE] Running in test mode.";
        }

        // 1. Check for binaries
        $hasPgDump = $this->commandExists('pg_dump');
        $hasGzip = $this->commandExists('gzip');

        if (!$hasPgDump && !$isTest) {
            throw new \Exception("'pg_dump' not found. Please install PostgreSQL tools.");
        }

        $date = date('Y-m-d_H-i-s');
        $filename = "backup_v1_{$date}" . ($isTest ? "_TEST" : "") . ".sql";
        $localFilePath = "{$this->backupDir}/{$filename}";
        $gzFilePath = "{$localFilePath}.gz";

        // 2. Perform Dump
        if ($isTest) {
            $results[] = "[INFO] Creating mock dump file...";
            file_put_contents($localFilePath, "-- Mock SQL Backup Content\nSELECT current_timestamp;");
        } else {
            putenv("PGPASSWORD={$this->dbConfig['password']}");
            $cmd = "pg_dump -h {$this->dbConfig['host']} -p {$this->dbConfig['port']} -U {$this->dbConfig['username']} -F p -b --clean --if-exists --no-owner --no-privileges -d {$this->dbConfig['database']} > \"{$localFilePath}\" 2>&1";
            
            exec($cmd, $dumpOutput, $returnCode);

            if ($returnCode !== 0) {
                if (file_exists($localFilePath)) unlink($localFilePath);
                throw new \Exception("Backup failed with code {$returnCode}: " . implode("\n", $dumpOutput));
            }
            $results[] = "[SUCCESS] Database dumped to " . basename($localFilePath);
        }

        // 3. Compress
        if ($hasGzip) {
            $gzCmd = "gzip -f \"{$localFilePath}\"";
            exec($gzCmd, $gzOutput, $gzReturn);
            if ($gzReturn !== 0) {
                $hasGzip = false;
            }
        }

        if (!$hasGzip) {
            $content = file_get_contents($localFilePath);
            $compressed = gzencode($content, 9);
            if ($compressed !== false) {
                file_put_contents($gzFilePath, $compressed);
                unlink($localFilePath);
            } else {
                throw new \Exception("PHP compression failed.");
            }
        }

        if (!file_exists($gzFilePath)) {
            throw new \Exception("Compression result file not found.");
        }
        $results[] = "[SUCCESS] Backup compressed: " . basename($gzFilePath) . " (" . round(filesize($gzFilePath)/1024, 2) . " KB)";

        // 4. Upload to Cloud
        $folderId = $_ENV['GOOGLE_DRIVE_FOLDER_ID'] ?? '';
        if ($isTest && empty($folderId)) {
            $results[] = "[SKIP] GOOGLE_DRIVE_FOLDER_ID is empty. Skipping upload in test mode.";
        } else {
            $uploader = new FileUploader($this->backupDir, 'google');
            $uploader->setGoogleConfig(
                dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . ($_ENV['GOOGLE_CLIENT_SECRET'] ?? 'client_secret.json'),
                dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . ($_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json'),
                $folderId
            );

            $backupFolderId = $uploader->findFolder('Backups');
            if (!$backupFolderId) {
                $backupFolderId = $uploader->createFolder('Backups');
            }

            if ($backupFolderId) {
                $uploader->setTargetFolderId($backupFolderId);
                $fakeFile = [
                    'name' => basename($gzFilePath),
                    'tmp_name' => $gzFilePath,
                    'size' => filesize($gzFilePath),
                    'error' => 0
                ];
                
                $driveLink = $uploader->upload($fakeFile, pathinfo($filename, PATHINFO_FILENAME));
                
                if ($driveLink) {
                    $results[] = "[SUCCESS] Backup uploaded to Google Drive: {$driveLink}";
                } else {
                    $results[] = "[ERROR] Google Drive upload failed: " . $uploader->getFirstError();
                }
            }
        }

        // 5. Cleanup
        $results[] = "[INFO] Cleaning up backups older than 7 days...";
        $files = glob("{$this->backupDir}/*.sql.gz");
        $now = time();
        $retentionSeconds = 7 * 24 * 60 * 60;

        foreach ($files as $file) {
            if ($now - filemtime($file) > $retentionSeconds) {
                unlink($file);
                $results[] = "[CLEANUP] Deleted local file: " . basename($file);
            }
        }

        return [
            'success' => true,
            'log' => $results,
            'file' => basename($gzFilePath)
        ];
    }

    public function restore($filename, $targetDb = null)
    {
        $targetDb = $targetDb ?? $this->dbConfig['database'];
        $filePath = "{$this->backupDir}/{$filename}";

        if (!file_exists($filePath)) {
            throw new \Exception("Backup file not found: {$filename}");
        }

        $isGz = str_ends_with($filename, '.gz');
        $sqlPath = $filePath;

        if ($isGz) {
            $sqlPath = substr($filePath, 0, -3);
            if (str_contains($filename, '_TEST')) {
                // Mock test
                $content = gzdecode(file_get_contents($filePath));
                file_put_contents($sqlPath, $content);
            } else {
                // Real decompression
                if ($this->commandExists('gzip')) {
                    exec("gzip -dk -f \"{$filePath}\"");
                } else {
                    $content = gzdecode(file_get_contents($filePath));
                    file_put_contents($sqlPath, $content);
                }
            }
        }

        if (!file_exists($sqlPath)) {
            throw new \Exception("Failed to prepare SQL file for restore.");
        }

        if (str_contains($filename, '_TEST')) {
            unlink($sqlPath);
            return [
                'success' => true,
                'log' => ["[TEST] Restore simulation successful to database: {$targetDb}"]
            ];
        }

        if (!$this->commandExists('psql')) {
            if (file_exists($sqlPath) && $isGz) unlink($sqlPath);
            throw new \Exception("'psql' utility not found. Restore requires PostgreSQL tools.");
        }

        putenv("PGPASSWORD={$this->dbConfig['password']}");

        // Cleanup schema before restore to avoid conflicts
        $cleanupCmd = "psql -h {$this->dbConfig['host']} -p {$this->dbConfig['port']} -U {$this->dbConfig['username']} -d {$targetDb} -c \"DROP SCHEMA public CASCADE; CREATE SCHEMA public;\" 2>&1";
        exec($cleanupCmd, $cleanupOutput, $cleanupReturnCode);
        
        if ($cleanupReturnCode !== 0) {
            if ($isGz && file_exists($sqlPath)) unlink($sqlPath);
            throw new \Exception("Pre-restore schema cleanup failed: " . implode("\n", $cleanupOutput));
        }

        $cmd = "psql -h {$this->dbConfig['host']} -p {$this->dbConfig['port']} -U {$this->dbConfig['username']} -d {$targetDb} -f \"{$sqlPath}\" 2>&1";
        
        exec($cmd, $output, $returnCode);

        // Cleanup temporary SQL file if it was decompressed
        if ($isGz && file_exists($sqlPath)) {
            unlink($sqlPath);
        }

        if ($returnCode !== 0) {
            throw new \Exception("Restore failed (Code {$returnCode}): " . implode("\n", $output));
        }

        return [
            'success' => true,
            'log' => $output
        ];
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
