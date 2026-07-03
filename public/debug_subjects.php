<?php
require_once __DIR__ . '/../vendor/autoload.php';
try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    echo "<h1>Debug Subject Table (dm_mon)</h1>";

    $stmt = $db->query("SELECT * FROM dm_mon WHERE ma_mon LIKE 'NK%' ORDER BY ma_mon");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($subjects)) {
        echo "<p>No NK subjects found in database!</p>";
        // Dump all subjects just in case
        echo "<h2>All Subjects in dm_mon:</h2>";
        $stmtAll = $db->query("SELECT * FROM dm_mon ORDER BY ma_mon");
        $all = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
        echo "<ul>";
        foreach ($all as $s) {
            echo "<li>{$s['ma_mon']} - {$s['ten_mon']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Mã Môn (ma_mon)</th><th>Tên Môn (ten_mon)</th></tr>";
        foreach ($subjects as $s) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($s['ma_mon']) . "</td>";
            echo "<td>" . htmlspecialchars($s['ten_mon']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
