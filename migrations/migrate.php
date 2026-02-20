<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

// Mock App\Core\App to get config
class AppMock {
    public static $config = [];
}
// Manually load config for this script context if Database depends on App::config
// However, looking at previous Database usage, it might use DotEnv directly or App::config. 
// Let's assume we can load .env and use PDO.

// Load .env
$dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
$dotenv->load();

// Connect
$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_DATABASE'];
$user = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Connected to Database.\n";
    
    $sql = file_get_contents(__DIR__ . '/v3_combined_upgrade.sql');
    if (!$sql) {
        die("Error reading SQL file.\n");
    }
    
    $pdo->exec($sql);
    echo "Migration executed successfully!\n";
    
} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
