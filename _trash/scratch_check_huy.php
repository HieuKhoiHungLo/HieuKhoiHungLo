<?php
require 'vendor/autoload.php';

try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "--- CANDIDATE NGUYỄN QUANG HUY ---\n";
    echo "--- ALL MINH HOA COMMUNES ---\n";
    $stmt2 = $db->prepare("SELECT * FROM dm_xa WHERE ten_xa LIKE ?");
    $stmt2->execute(['%Minh Hòa%']);
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
