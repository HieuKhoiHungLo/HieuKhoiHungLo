<?php
// Tải autoloader của dự án để sử dụng Database class
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $tables = ['thi_sinh', 'nguyen_vong', 'ket_qua_hoc_tap', 'diem_thi_thpt', 'v_calc_summary'];
    
    echo "<h1>Database Schema Diagnosis</h1>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Table</th><th>Columns</th><th>Has updated_at?</th></tr>";
    
    foreach ($tables as $t) {
        $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = '$t' ORDER BY ordinal_position");
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $hasUpdatedAt = in_array('updated_at', $cols) || in_array('ngay_cap_nhat', $cols) ? '✅ YES' : '❌ NO';
        
        echo "<tr>";
        echo "<td><b>$t</b></td>";
        echo "<td>" . implode(', ', $cols) . "</td>";
        echo "<td>$hasUpdatedAt</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<h2>Error: " . $e->getMessage() . "</h2>";
}
