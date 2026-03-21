<?php
try {
    $db = new PDO('pgsql:host=aws-1-ap-south-1.pooler.supabase.com;port=6543;dbname=postgres', 'postgres.oxhuzfqvlpntlymdwfiy', 'HvuTuyenSinh2026');
    $stmt = $db->query("SELECT * FROM ket_qua_hoc_tap LIMIT 1");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
