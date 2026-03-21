<?php
require 'vendor/autoload.php';
if (class_exists('Dotenv\Dotenv')) { $dotenv = Dotenv\Dotenv::createImmutable(__DIR__); $dotenv->safeLoad(); }
$db = App\Core\Database::getInstance()->getConnection();

echo "Test Records in ThiSinh:" . PHP_EOL;
$res = $db->query("SELECT so_cccd, ho_va_ten, email FROM thi_sinh WHERE ho_va_ten ILIKE '%test%' OR email ILIKE '%test%'")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);

echo PHP_EOL . "Approved Records in Applications:" . PHP_EOL;
$res2 = $db->query("SELECT so_cccd, trang_thai FROM ho_so_xet_tuyen WHERE trang_thai ILIKE '%Đã duyệt%'")->fetchAll(PDO::FETCH_ASSOC);
print_r($res2);

echo PHP_EOL . "Stats grouped by Date (YYYY-MM-DD):" . PHP_EOL;
$res3 = $db->query("SELECT TO_CHAR(created_at, 'YYYY-MM-DD') as date, COUNT(*) as count FROM ho_so_xet_tuyen GROUP BY date ORDER BY date ASC")->fetchAll(PDO::FETCH_ASSOC);
print_r($res3);

echo PHP_EOL . "Stats grouped by Date for ThiSinh (Ghost Search):" . PHP_EOL;
$res4 = $db->query("SELECT TO_CHAR(ngay_tao, 'YYYY-MM-DD') as date, COUNT(*) as count FROM thi_sinh WHERE NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs WHERE hs.so_cccd = thi_sinh.so_cccd) GROUP BY date ORDER BY date ASC")->fetchAll(PDO::FETCH_ASSOC);
print_r($res4);
