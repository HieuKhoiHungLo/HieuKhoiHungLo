<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = new \App\Core\DotEnv(__DIR__ . '/.env');
$dotenv->load();

require 'app/Core/Database.php';
$db = \App\Core\Database::getInstance()->getConnection();

$sql = "CREATE TABLE IF NOT EXISTS ket_qua_trung_tuyen (
    id SERIAL PRIMARY KEY,
    session_id INT NOT NULL,
    so_cccd VARCHAR(50),
    ho_ten VARCHAR(150),
    ngay_sinh VARCHAR(50),
    sbd VARCHAR(50),
    khu_vuc VARCHAR(50),
    doi_tuong VARCHAR(50),
    to_hop VARCHAR(50),
    diem_mon_1 NUMERIC,
    diem_mon_2 NUMERIC,
    diem_mon_3 NUMERIC,
    diem_to_hop NUMERIC,
    diem_ut NUMERIC,
    ut_quy_doi NUMERIC,
    diem_xt NUMERIC,
    ma_nganh VARCHAR(50),
    ten_nganh VARCHAR(255),
    phuong_thuc VARCHAR(255),
    so_tai_khoan VARCHAR(50),
    ngan_hang VARCHAR(100),
    so_tien NUMERIC,
    noi_dung_ck TEXT,
    email VARCHAR(150),
    sdt VARCHAR(50),
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$db->exec($sql);
echo "Table ket_qua_trung_tuyen created.\n";

$stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name='dot_tuyen_sinh' AND column_name='email_template_id'");
if (!$stmt->fetch()) {
    $db->exec("ALTER TABLE dot_tuyen_sinh ADD COLUMN email_template_id INT DEFAULT NULL");
    echo "Column email_template_id added to dot_tuyen_sinh.\n";
} else {
    echo "Column email_template_id already exists in dot_tuyen_sinh.\n";
}
