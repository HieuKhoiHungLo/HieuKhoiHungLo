<?php
/**
 * Run migration v28b: Thêm cột ket_qua_bo_gd vào v_calc_summary
 * để lưu kết quả lọc ảo Bộ GD&ĐT (Trúng / Đổ) ngay trong bảng tổng hợp
 *
 * Chạy cho cả NTK (dev) và THV2026 (production)
 */

$targets = [
    'NTK (dev)' => [
        'host'   => 'aws-1-ap-northeast-2.pooler.supabase.com',
        'port'   => '6543',
        'db'     => 'postgres',
        'user'   => 'postgres.zorxrwobsfhejutgjsbi',
        'pass'   => 'Phutho2024@!',
    ],
    'THV2026 (production)' => [
        'host'   => 'aws-1-ap-south-1.pooler.supabase.com',
        'port'   => '6543',
        'db'     => 'postgres',
        'user'   => 'postgres.jrqcyjsozxysuuozrfla',
        'pass'   => 'HvuTuyenSinh2026',
    ],
];

// Các statement cần chạy
$statements = [
    [
        'label' => 'ALTER v_calc_summary ADD ket_qua_bo_gd',
        'sql'   => "ALTER TABLE public.v_calc_summary
                        ADD COLUMN IF NOT EXISTS ket_qua_bo_gd VARCHAR(20) DEFAULT NULL",
    ],
    [
        'label' => 'CREATE INDEX idx_v_calc_ket_qua_bo_gd',
        'sql'   => "CREATE INDEX IF NOT EXISTS idx_v_calc_ket_qua_bo_gd
                        ON public.v_calc_summary(ket_qua_bo_gd)
                        WHERE ket_qua_bo_gd IS NOT NULL",
    ],
];

foreach ($targets as $name => $cfg) {
    echo "\n=== $name ===\n";
    try {
        $dsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']};sslmode=require";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "Connected OK\n";
    } catch (\PDOException $e) {
        echo "CONNECTION ERROR: " . $e->getMessage() . "\n";
        continue;
    }

    foreach ($statements as $s) {
        try {
            $pdo->exec($s['sql']);
            echo "  [OK]   {$s['label']}\n";
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'already exists') || str_contains($msg, 'duplicate column')) {
                echo "  [SKIP] {$s['label']} (already exists)\n";
            } else {
                echo "  [FAIL] {$s['label']}\n  => $msg\n";
            }
        }
    }

    // Verify
    try {
        $cols = $pdo->query(
            "SELECT column_name, data_type FROM information_schema.columns
             WHERE table_schema='public' AND table_name='v_calc_summary'
               AND column_name IN ('ket_qua_bo_gd','bi_loai_truong_khac','ma_truong_trung_tuyen_bo')
             ORDER BY column_name"
        )->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo "  VERIFY: {$c['column_name']} ({$c['data_type']}) OK\n";
        }
    } catch (\PDOException $e) {
        echo "  VERIFY ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\nDone.\n";
