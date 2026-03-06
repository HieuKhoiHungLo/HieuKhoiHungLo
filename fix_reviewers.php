<?php
$host = 'aws-1-ap-south-1.pooler.supabase.com';
$port = '6543';
$db_name = 'postgres';
$user = 'postgres.oxhuzfqvlpntlymdwfiy';
$pass = 'HvuTuyenSinh2026';

$dsn = "pgsql:host=$host;port=$port;dbname=$db_name";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->query("SELECT id, so_cccd, trang_thai, nguoi_duyet_id FROM ho_so_xet_tuyen WHERE trang_thai = 'Đã duyệt'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total Approved: " . count($rows) . "<br>";
    foreach ($rows as $r) {
        echo "ID: " . $r['id'] . " - CCCD: " . $r['so_cccd'] . " - NguoiDuyet: " . ($r['nguoi_duyet_id'] ?? 'NULL') . "<br>";
    }

    // Fix nulls
    $updated = $pdo->exec("UPDATE ho_so_xet_tuyen SET nguoi_duyet_id = 1 WHERE trang_thai IN ('Đã duyệt', 'Từ chối') AND nguoi_duyet_id IS NULL");
    echo "Updated $updated nulls to admin ID 1.<br>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
