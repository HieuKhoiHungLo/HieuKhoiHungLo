<?php
$host = 'aws-1-ap-south-1.pooler.supabase.com';
$db   = 'postgres';
$user = 'postgres.oxhuzfqvlpntlymdwfiy';
$pass = 'HvuTuyenSinh2026';
$port = '6543';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true, // Required for Supabase PgBouncer
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $stmt = $pdo->prepare("UPDATE ho_so_xet_tuyen SET trang_thai = 'Yêu cầu sửa', ghi_chu = CONCAT(COALESCE(ghi_chu, ''), '\n[Hệ thống]: Hồ sơ cũ được nâng cấp trạng thái.') WHERE yeu_cau_chinh_sua = TRUE AND trang_thai != 'Yêu cầu sửa'");
    $stmt->execute();
    echo "Fixed " . $stmt->rowCount() . " records.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
