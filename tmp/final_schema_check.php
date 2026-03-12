<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/DotEnv.php';
$dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
$dotenv->load();
require_once __DIR__ . '/../app/Core/Database.php';

$db = App\Core\Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM chung_chi_thi_sinh LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo implode(", ", array_keys($row)) . "\n";
} else {
    echo "No rows found\n";
}
