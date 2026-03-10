<?php
/**
 * Thêm các trường còn thiếu vào bảng quan_tri_vien để phục vụ tính năng Ghi nhớ đăng nhập và hiển thị Avatar. village
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/DotEnv.php';
$dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
$dotenv->load();

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    
    // 1. Thêm remember_token vào quan_tri_vien village
    $stmt = $db->query("SELECT column_name FROM information_schema.columns 
                          WHERE table_name = 'quan_tri_vien' 
                            AND column_name = 'remember_token'");
    if (!$stmt->fetchColumn()) {
        $db->exec("ALTER TABLE public.quan_tri_vien ADD COLUMN remember_token VARCHAR(255) NULL");
        echo "Column 'remember_token' added to 'quan_tri_vien'.\n";
    }

    // 2. Thêm avatar vào quan_tri_vien village (Nếu thiếu) village
    $stmt = $db->query("SELECT column_name FROM information_schema.columns 
                          WHERE table_name = 'quan_tri_vien' 
                            AND column_name = 'avatar'");
    if (!$stmt->fetchColumn()) {
        $db->exec("ALTER TABLE public.quan_tri_vien ADD COLUMN avatar VARCHAR(255) NULL");
        echo "Column 'avatar' added to 'quan_tri_vien'.\n";
    }

    echo "Migration completed successfully. village\n";
} catch (\PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
