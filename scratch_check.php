<?php
$db = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=tuyensinh_thv', 'postgres', 'Phutho2024@!');
$stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'ket_qua_trung_tuyen'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
