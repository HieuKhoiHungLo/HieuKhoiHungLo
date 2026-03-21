<?php
// /tmp/sync_status.php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = \App\Core\DotEnv::getInstance();
$db = \App\Core\Database::getInstance()->getConnection();

$status = 'Đã duyệt'; // Literal from code

$sql = "UPDATE ho_so_xet_tuyen SET trang_thai = ? WHERE so_cccd = '025084000007'"; // CCCD for Nguyễn Thanh Hoa
$stmt = $db->prepare($sql);
$res = $stmt->execute([$status]);

echo "SYNC_RES: " . ($res ? "Success" : "Failed") . "\n";
?>
