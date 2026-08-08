<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function migrate($db, $name) {
    try {
        $db->exec("ALTER TABLE ket_qua_trung_tuyen ADD COLUMN IF NOT EXISTS file_giay_bao VARCHAR(255)");
        $stmt = $db->query("SELECT 1 FROM settings WHERE key = 'google_drive_giay_bao_folder_id'");
        if (!$stmt->fetch()) {
            $db->exec("INSERT INTO settings (key, value, description) VALUES ('google_drive_giay_bao_folder_id', '', 'ID thư mục Google Drive chứa File Giấy báo PDF')");
        }
        echo "$name DB migrated.\n";
    } catch (Exception $e) {
        echo "$name DB error: " . $e->getMessage() . "\n";
    }
}

// Local
$db1 = new PDO("pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
migrate($db1, 'Local');

// Supabase
$db2 = new PDO("pgsql:host=aws-1-ap-northeast-2.pooler.supabase.com;port=6543;dbname=postgres", "postgres.zorxrwobsfhejutgjsbi", "Phutho2024@!");
migrate($db2, 'Supabase');
