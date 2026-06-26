<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = new \App\Core\DotEnv(__DIR__ . '/.env');
$dotenv->load();

require 'app/Core/Database.php';
$db = \App\Core\Database::getInstance()->getConnection();

$stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name='dot_tuyen_sinh' AND column_name='email_template_id'");
if (!$stmt->fetch()) {
    $db->exec("ALTER TABLE dot_tuyen_sinh ADD COLUMN email_template_id INT DEFAULT NULL");
    echo "Column email_template_id added to dot_tuyen_sinh.\n";
} else {
    echo "Column email_template_id already exists in dot_tuyen_sinh.\n";
}
