<?php
try {
    require 'vendor/autoload.php';
    $env = parse_ini_file('.env');
    $dsn = "pgsql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}";
    $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM ho_so_xet_tuyen) as total_applications,
            (SELECT COUNT(*) FROM thi_sinh) as total_candidates,
            (SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE trang_thai = 'Đã duyệt') as approved,
            (SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE trang_thai = 'Chờ duyệt') as pending,
            (SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE trang_thai = 'Từ chối') as rejected
    ")->fetch(PDO::FETCH_ASSOC);

    file_put_contents('tmp/db_stats.json', json_encode($stats, JSON_PRETTY_PRINT));
    echo "Stats written to tmp/db_stats.json\n";
} catch (Exception $e) {
    file_put_contents('tmp/db_stats_error.txt', $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
