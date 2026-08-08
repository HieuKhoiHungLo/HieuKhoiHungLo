<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Local
try {
    $db1 = new PDO("pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $db1->exec("DELETE FROM nhap_hoc WHERE trang_thai = 'chua_nhap_hoc'");
    echo "Cleaned Local.\n";
} catch (Exception $e) {
    echo "Local error: " . $e->getMessage() . "\n";
}

// Supabase
try {
    $db2 = new PDO("pgsql:host=aws-1-ap-northeast-2.pooler.supabase.com;port=6543;dbname=postgres", "postgres.zorxrwobsfhejutgjsbi", "Phutho2024@!");
    $db2->exec("DELETE FROM nhap_hoc WHERE trang_thai = 'chua_nhap_hoc'");
    echo "Cleaned Supabase.\n";
} catch (Exception $e) {
    echo "Supabase error: " . $e->getMessage() . "\n";
}
