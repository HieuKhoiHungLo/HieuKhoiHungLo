<?php
try {
    $dsn = 'pgsql:host=aws-1-ap-southeast-1.pooler.supabase.com;port=6543;dbname=postgres';
    $user = 'postgres.czxcpfrkdccbjioytwtp';
    $pass = 'Phutho2024@!';
    $db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $tables = ['thi_sinh', 'nguyen_vong', 'ket_qua_hoc_tap', 'diem_thi_thpt', 'v_calc_summary'];
    foreach ($tables as $t) {
        $q = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = '$t' ORDER BY column_name");
        $cols = $q->fetchAll(PDO::FETCH_COLUMN);
        
        $hasUpdatedAt = in_array('updated_at', $cols) ? '✅ YES' : '❌ NO';
        $hasNgayCapNhat = in_array('ngay_cap_nhat', $cols) ? '✅ YES' : '❌ NO';
        
        echo "$t:\n  Columns: " . implode(', ', $cols) . "\n  updated_at: $hasUpdatedAt\n  ngay_cap_nhat: $hasNgayCapNhat\n\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
