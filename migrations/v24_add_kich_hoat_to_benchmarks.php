<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        $_SERVER[trim($name)] = trim($value);
    }
}

try {
    $db = Database::getInstance()->getConnection();
    echo "Connected to Database.\n";

    // Check if column already exists
    $stmt = $db->query("
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'admission_benchmarks' AND column_name = 'kich_hoat'
    ");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Adding column 'kich_hoat' to 'admission_benchmarks'...\n";
        $db->exec("ALTER TABLE admission_benchmarks ADD COLUMN kich_hoat BOOLEAN DEFAULT true;");
        echo "Column added successfully.\n";
    } else {
        echo "Column 'kich_hoat' already exists.\n";
    }

} catch (\Exception $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
