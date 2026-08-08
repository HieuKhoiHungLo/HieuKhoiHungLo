<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$db = new PDO('pgsql:host='.$_ENV['DB_HOST'].';port='.$_ENV['DB_PORT'].';dbname='.$_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
$stmt = $db->query("SELECT body FROM email_templates WHERE code = 'ADMISSION_LETTER'");
file_put_contents('scratch/template_dump.html', $stmt->fetchColumn());
echo "Dumped.";
