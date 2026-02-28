<?php
/**
 * Thêm trường remember_token vào bảng thi_sinh để phục vụ tính năng Ghi nhớ Đăng nhập (PostgreSQL Version)
 */
require_once __DIR__ . '/../app/Core/DotEnv.php';
$dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
$dotenv->load();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    
    // Check if column exists (PostgreSQL syntax)
    $stmt = $db->query("SELECT column_name FROM information_schema.columns 
                          WHERE table_name = 'thi_sinh' 
                            AND column_name = 'remember_token'");
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        $db->exec("ALTER TABLE thi_sinh ADD COLUMN remember_token VARCHAR(255) NULL");
        echo "Column 'remember_token' added to 'thi_sinh' table successfully.\n";
    } else {
        echo "Column 'remember_token' already exists.\n";
    }
} catch (\PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
