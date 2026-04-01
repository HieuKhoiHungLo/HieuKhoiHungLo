<?php
try {
    $dsn = 'pgsql:host=aws-0-ap-southeast-1.pooler.supabase.com;port=6543;dbname=postgres';
    $user = 'postgres.rmbwnhksvovvjigpvyzy';
    $pass = 'Aolang123@#$TS2025';
    $db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $tables = ['thi_sinh', 'nguyen_vong', 'ket_qua_hoc_tap', 'diem_thi_thpt', 'v_calc_summary'];
    foreach ($tables as $t) {
        $q = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = '$t'");
        $cols = $q->fetchAll(PDO::FETCH_COLUMN);
        echo "$t: " . implode(',', $cols) . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
