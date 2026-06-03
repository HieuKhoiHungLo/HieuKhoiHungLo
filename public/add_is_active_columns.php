<?php
require_once __DIR__ . '/../app/Core/DotEnv.php';
$dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
$dotenv->load();

require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    
    $tables = ['dm_tinh', 'dm_xa', 'dm_truong_thpt'];
    
    foreach ($tables as $table) {
        echo "Checking table '$table'...\n";
        
        // Check if is_active column exists
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM information_schema.columns 
            WHERE table_name = ? AND column_name = 'is_active'
        ");
        $stmt->execute([$table]);
        $exists = (int)$stmt->fetchColumn() > 0;
        
        if (!$exists) {
            echo "Column 'is_active' is missing. Adding it...\n";
            $db->exec("ALTER TABLE $table ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
            echo "Column 'is_active' added to '$table' successfully.\n";
        } else {
            echo "Column 'is_active' already exists in '$table'.\n";
        }
    }
    
    echo "\nAll checks completed!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
