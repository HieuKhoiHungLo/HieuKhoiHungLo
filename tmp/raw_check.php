<?php
$host = 'aws-1-ap-south-1.pooler.supabase.com';
$db   = 'postgres';
$user = 'postgres.oxhuzfqvlpntlymdwfiy'; 
$pass = 'HvuTuyenSinh2026';
$port = '6543';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // 1. Total records in thi_sinh
    $total = $pdo->query("SELECT COUNT(*) FROM thi_sinh WHERE deleted_at IS NULL")->fetchColumn();

    // 2. Records with at least one application (ever)
    $withAppAny = $pdo->query("SELECT COUNT(DISTINCT so_cccd) FROM ho_so_xet_tuyen")->fetchColumn();

    // 3. Records with 2026 application
    $withApp2026 = $pdo->query("
        SELECT COUNT(DISTINCT hs.so_cccd) 
        FROM ho_so_xet_tuyen hs 
        JOIN dot_tuyen_sinh dts ON hs.dot_tuyen_sinh_id = dts.id 
        WHERE dts.nam_tuyen_sinh = 2026
    ")->fetchColumn();

    // 4. Candidates registered in 2026 (assuming created_at or ngay_tao)
    // Let's check which column exists
    $columns = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'thi_sinh'")->fetchAll(PDO::FETCH_COLUMN);
    $timeCol = in_array('ngay_tao', $columns) ? 'ngay_tao' : (in_array('created_at', $columns) ? 'created_at' : null);

    if ($timeCol) {
        $reg2026 = $pdo->query("SELECT COUNT(*) FROM thi_sinh WHERE $timeCol >= '2026-01-01' AND deleted_at IS NULL")->fetchColumn();
    } else {
        $reg2026 = "Unknown (no date column)";
    }

    echo "--- DIAGNOSTIC RESULTS ---\n";
    echo "Total Active Candidates (thi_sinh): $total\n";
    echo "Candidates with ANY application (all time): $withAppAny\n";
    echo "Candidates with NO applications (ever): " . ($total - $withAppAny) . "\n";
    echo "\n--- 2026 SPECIFIC ---\n";
    echo "Total registered in 2026: $reg2026\n";
    echo "Total with 2026 application (submitted): $withApp2026\n";
    echo "Candidates registered in 2026 but NO application in 2026: " . (is_numeric($reg2026) ? ($reg2026 - $withApp2026) : "N/A") . "\n";

    // Sessions breakdown
    echo "\n--- BY SESSION ---\n";
    $sessions = $pdo->query("SELECT id, ten_dot, nam_tuyen_sinh FROM dot_tuyen_sinh ORDER BY nam_tuyen_sinh DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($sessions as $s) {
        $count = $pdo->prepare("SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ?");
        $count->execute([$s['id']]);
        $c = $count->fetchColumn();
        echo "- {$s['ten_dot']} ({$s['nam_tuyen_sinh']}): $c\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
