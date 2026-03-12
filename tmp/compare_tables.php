<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/DotEnv.php';
$dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
$dotenv->load();
require_once __DIR__ . '/../app/Core/Database.php';

$db = App\Core\Database::getInstance()->getConnection();
try {
    $c1 = $db->query("SELECT COUNT(*) FROM diem_chung_chi_ngoai_ngu")->fetchColumn();
    echo "diem_chung_chi_ngoai_ngu: $c1\n";
} catch (Exception $e) { echo "diem: Error\n"; }

try {
    $c2 = $db->query("SELECT COUNT(*) FROM chung_chi_thi_sinh")->fetchColumn();
    echo "chung_chi_thi_sinh: $c2\n";
} catch (Exception $e) { echo "chungchi: Error\n"; }
