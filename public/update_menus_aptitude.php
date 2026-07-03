<?php
require_once __DIR__ . '/../vendor/autoload.php';
// Load Env
try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Database;
use App\Models\Menu;

try {
    $db = Database::getInstance()->getConnection();
    echo "<h1>Updating Menu: Moving Aptitude Scores to XÉT TUYỂN LỌC ẢO Group</h1>";

    // 1. Get the group ID for "XÉT TUYỂN LỌC ẢO"
    $stmt = $db->prepare("SELECT id FROM menus WHERE title = 'XÉT TUYỂN LỌC ẢO' AND position = 'admin_sidebar' LIMIT 1");
    $stmt->execute();
    $groupId = $stmt->fetchColumn();

    if (!$groupId) {
        throw new \Exception("Menu group 'XÉT TUYỂN LỌC ẢO' not found in database.");
    }

    // 2. Find and update parent_id of /admin/aptitude-scores and title to 'Điểm năng khiếu'
    $stmtUpdate = $db->prepare("UPDATE menus SET parent_id = ?, title = 'Điểm năng khiếu' WHERE url = '/admin/aptitude-scores' AND position = 'admin_sidebar'");
    $stmtUpdate->execute([$groupId]);
    $affected = $stmtUpdate->rowCount();

    // 3. Clear menu cache
    $menuModel = new Menu();
    $menuModel->clearCache();

    if ($affected > 0) {
        echo "<p style='color: green; font-weight: bold;'>Success: Updated menu item 'Điểm năng khiếu' successfully!</p>";
    } else {
        // Run a fallback update in case parent_id is already set but title is not
        $stmtUpdateTitle = $db->prepare("UPDATE menus SET title = 'Điểm năng khiếu' WHERE url = '/admin/aptitude-scores' AND position = 'admin_sidebar'");
        $stmtUpdateTitle->execute();
        $menuModel->clearCache();
        echo "<p style='color: green; font-weight: bold;'>Success: Updated title of menu item to 'Điểm năng khiếu' successfully!</p>";
    }

    echo "<p><a href='/admin/dashboard'>Go back to Dashboard</a></p>";

} catch (\Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</p>";
}
