<?php
require 'vendor/autoload.php';

try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "--- SAMPLE OF ACTIVE COMMUNES (is_active = 1) ---\n";
    $stmt = $db->query("SELECT ma_xa, ten_xa, ma_tinh FROM dm_xa WHERE is_active = TRUE LIMIT 20");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
