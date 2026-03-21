<?php
require_once __DIR__ . '/app/Core/Database.php';

// Mock $_ENV since it's used in config/db.php
$_ENV['DB_HOST'] = 'aws-1-ap-south-1.pooler.supabase.com';
$_ENV['DB_PORT'] = '6543';
$_ENV['DB_DATABASE'] = 'postgres';
$_ENV['DB_USERNAME'] = 'postgres.oxhuzfqvlpntlymdwfiy';
$_ENV['DB_PASSWORD'] = 'HvuTuyenSinh2026';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'thi_sinh' AND table_schema = 'public'");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode(", ", $cols);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
