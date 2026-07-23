<?php
/**
 * Migration: Add so_bao_danh column to thi_sinh table
 */
require_once __DIR__ . '/../app/Core/DotEnv.php';
$dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
$dotenv->load();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/Database.php';

try {
    // Set admin role to bypass Row Level Security (RLS) in Supabase/PostgreSQL
    \App\Core\Database::getInstance()->setSystemRole('admin');
    $db = \App\Core\Database::getInstance()->getConnection();
    
    // Check if so_bao_danh column exists in thi_sinh table
    $stmt = $db->query("SELECT column_name FROM information_schema.columns 
                          WHERE table_name = 'thi_sinh' 
                            AND column_name = 'so_bao_danh'");
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        $db->exec("ALTER TABLE thi_sinh ADD COLUMN so_bao_danh VARCHAR(50) NULL");
        echo "Column 'so_bao_danh' added to 'thi_sinh' table successfully.\n";
    } else {
        echo "Column 'so_bao_danh' already exists.\n";
    }
} catch (\PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
