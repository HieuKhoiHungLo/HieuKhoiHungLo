<?php
require_once __DIR__ . '/../app/Core/DotEnv.php';
$dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
$dotenv->load();

require_once __DIR__ . '/../config/db.php';

try {
    $db = new PDO($dsn, $user, $pass, $options);
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    echo "Tables in database:\n";
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $table) {
        echo "- $table\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
