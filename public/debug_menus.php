<?php
require_once __DIR__ . '/../vendor/autoload.php';
try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    echo "<h1>Debug Menus</h1>";

    $stmt = $db->query("SELECT id, parent_id, title, url, position, is_active FROM menus ORDER BY position, id");
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Parent ID</th><th>Title</th><th>URL</th><th>Position</th><th>Active</th></tr>";
    foreach ($menus as $m) {
        echo "<tr>";
        echo "<td>{$m['id']}</td>";
        echo "<td>{$m['parent_id']}</td>";
        echo "<td>" . htmlspecialchars($m['title']) . "</td>";
        echo "<td>" . htmlspecialchars($m['url']) . "</td>";
        echo "<td>{$m['position']}</td>";
        echo "<td>{$m['is_active']}</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
