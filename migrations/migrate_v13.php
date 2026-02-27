<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\DotEnv;

$dotenv = new DotEnv(__DIR__ . '/../.env');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_DATABASE'];
$user = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Connected to Database.\n";
    
    $sql = file_get_contents(__DIR__ . '/v13_virtual_filtering.sql');
    if (!$sql) {
        die("Error reading SQL file.\n");
    }
    
    $pdo->exec($sql);
    echo "Migration v13 executed successfully!\n";
    
} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
