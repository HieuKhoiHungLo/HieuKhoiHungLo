<?php
/**
 * Database Backup Script for Supabase (PostgreSQL)
 * Author: Antigravity AI
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\FileUploader;
use Dotenv\Dotenv;

// 1. Load Environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Services\BackupService;

$isTest = in_array('--test', $argv);

try {
    echo "[SYSTEM] Initializing Backup Service...\n";
    $service = new BackupService();
    $result = $service->run($isTest);
    
    foreach ($result['log'] as $line) {
        echo $line . "\n";
    }
    echo "[DONE] Backup task completed successfully.\n";
} catch (\Exception $e) {
    echo "[CRITICAL ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

