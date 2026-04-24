<?php
require_once __DIR__ . '/../app/Core/Database.php';

// Load .env manually for CLI
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(sprintf('%s=%s', trim($name), trim($value)));
    }
}

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    $db->exec("ALTER TABLE talent_test_assignments ADD COLUMN IF NOT EXISTS bag_number VARCHAR(50)");
    echo "Column bag_number added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
