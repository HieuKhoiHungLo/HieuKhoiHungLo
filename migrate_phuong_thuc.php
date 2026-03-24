<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$db   = $_ENV['DB_DATABASE'];
$user = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];

$dsn = "pgsql:host=$host;port=$port;dbname=$db;";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // 1. Tạo bảng
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS dm_phuong_thuc (
            ma_phuong_thuc VARCHAR(5) PRIMARY KEY,
            ten_phuong_thuc VARCHAR(200) NOT NULL,
            ma_noi_bo VARCHAR(3) NOT NULL,
            flag_nganh VARCHAR(50) NULL,
            thu_tu SMALLINT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE
        );
    ");
    
    // 2. Insert data
    $pdo->exec("
        INSERT INTO dm_phuong_thuc (ma_phuong_thuc, ten_phuong_thuc, ma_noi_bo, flag_nganh, thu_tu, is_active) VALUES
        ('TS01', 'Xét điểm thi THPT Quốc gia', '100', NULL, 1, TRUE),
        ('TS02', 'Xét học bạ THPT', '200', NULL, 2, TRUE),
        ('TS03', 'Xét học bạ + chứng chỉ quốc tế', '200', 'co_xet_chung_chi', 3, TRUE),
        ('TS04', 'Xét ĐTHI THPT + điểm năng khiếu', '100', 'co_diem_nangkhieu_thpt', 4, TRUE),
        ('TS05', 'Xét học bạ + điểm năng khiếu', '200', 'co_diem_nangkhieu_hochba', 5, TRUE)
        ON CONFLICT (ma_phuong_thuc) DO NOTHING;
    ");

    // 3. Alter dm_nganh
    $pdo->exec("
        ALTER TABLE dm_nganh
        ADD COLUMN IF NOT EXISTS co_diem_nangkhieu_thpt BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS co_diem_nangkhieu_hochba BOOLEAN DEFAULT FALSE;
    ");

    echo "Migration thành công!\n";

} catch (\PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
