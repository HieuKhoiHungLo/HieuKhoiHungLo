<?php
require_once __DIR__ . '/../app/Core/DotEnv.php';
$dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
$dotenv->load();

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Cache.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    
    // We update is_active = FALSE for schools matching the custom patterns
    $sql = "UPDATE dm_truong_thpt 
            SET is_active = FALSE 
            WHERE (ten_truong ILIKE '%Dự phòng%' 
               OR ten_truong ILIKE '%LC_%' 
               OR ten_truong ILIKE '%YB_%' 
               OR ten_truong ILIKE '%tự tạo%') 
              AND is_active = TRUE";
              
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rowsAffected = $stmt->rowCount();
    
    echo "Successfully deactivated $rowsAffected custom/reserve schools.\n";
    
    // Flush application cache to reflect changes
    \App\Core\Cache::flush();
    echo "System cache flushed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
