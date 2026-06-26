<?php
require 'vendor/autoload.php';
$dotenv = new \App\Core\DotEnv('.env');
$dotenv->load();
require 'app/Core/Database.php';
$db = \App\Core\Database::getInstance()->getConnection();

$sql = "CREATE TABLE IF NOT EXISTS session_templates (
    session_id INT PRIMARY KEY,
    template_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$db->exec($sql);
echo "Table session_templates created.\n";
