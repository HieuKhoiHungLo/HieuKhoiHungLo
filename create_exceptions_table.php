<?php
$configDev = [
    'dsn'  => 'pgsql:host=aws-1-ap-southeast-1.pooler.supabase.com;port=6543;dbname=postgres',
    'user' => 'postgres.czxcpfrkdccbjioytwtp',
    'pass' => 'Phutho2024@!'
];

try {
    $db = new PDO($configDev['dsn'], $configDev['user'], $configDev['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Create new independent table for exceptions
    $sql = "
    CREATE TABLE IF NOT EXISTS ngoai_le_xet_tuyen (
        id SERIAL PRIMARY KEY,
        dot_tuyen_sinh_id INTEGER NOT NULL,
        so_cccd VARCHAR(20) NOT NULL,
        ma_nganh VARCHAR(50) NOT NULL,
        trang_thai_ep_buoc VARCHAR(20) NOT NULL,
        ghi_chu TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(dot_tuyen_sinh_id, so_cccd, ma_nganh)
    );
    ";
    
    $db->exec($sql);
    echo "Table ngoai_le_xet_tuyen created successfully in DEV DB.\n";

} catch (\Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
