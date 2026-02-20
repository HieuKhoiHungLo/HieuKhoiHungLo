<?php
require __DIR__ . '/../vendor/autoload.php';
// Load .env
$dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
$dotenv->load();

// Connect to DB
$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_DATABASE'];
$user = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Connected to Database.\n";
    
    $sql = file_get_contents(__DIR__ . '/v4_add_semester_columns.sql');
    if (!$sql) {
        die("Error reading SQL file.\n");
    }
    
    $pdo->exec($sql);
    echo "Migration v4 executed successfully!\n";
    
} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
