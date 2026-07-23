<?php
/**
 * Run migration v28 on PRODUCTION DB: THV2026
 * Supabase project: jrqcyjsozxysuuozrfla (ap-south-1)
 *
 * KHÔNG đổi .env - kết nối thẳng vào THV2026 production
 */

// ===================== PRODUCTION THV2026 CREDENTIALS =====================
define('DB_HOST',     'aws-1-ap-south-1.pooler.supabase.com');
define('DB_PORT',     '6543');
define('DB_DATABASE', 'postgres');
define('DB_USERNAME', 'postgres.jrqcyjsozxysuuozrfla');
define('DB_PASSWORD', 'HvuTuyenSinh2026');
define('DB_SSLMODE',  'require');
// ==========================================================================

echo "============================================================\n";
echo " MIGRATION v28 → PRODUCTION: THV2026\n";
echo " Host: " . DB_HOST . "\n";
echo " DB:   " . DB_DATABASE . " / User: " . DB_USERNAME . "\n";
echo "============================================================\n\n";

echo "CAUTION: This will modify the PRODUCTION database THV2026.\n";
echo "Press ENTER to continue or Ctrl+C to cancel...\n";
fgets(STDIN);

try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT
         . ";dbname=" . DB_DATABASE . ";sslmode=" . DB_SSLMODE;

    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "Connected to THV2026 OK\n\n";
} catch (PDOException $e) {
    die("CONNECTION ERROR: " . $e->getMessage() . "\n");
}

// ===================== CÁC STATEMENTS (theo thứ tự) =====================
$statements = [

    // --- 1. Tạo bảng ket_qua_loc_ao_bo_gd ---
    [
        'label' => 'CREATE TABLE ket_qua_loc_ao_bo_gd',
        'sql'   => "CREATE TABLE IF NOT EXISTS public.ket_qua_loc_ao_bo_gd (
            id                      SERIAL PRIMARY KEY,
            dot_tuyen_sinh_id       INTEGER NOT NULL REFERENCES public.dot_tuyen_sinh(id) ON DELETE CASCADE,
            lan_loc_ao              VARCHAR(50),
            so_cccd                 VARCHAR(20) NOT NULL,
            sbd                     VARCHAR(20),
            ho_va_ten               TEXT,
            ma_nganh_hvu            VARCHAR(20),
            thu_tu_nv               INTEGER,
            ket_qua                 VARCHAR(20),
            ttnv_do                 INTEGER,
            ma_truong_trung_tuyen   VARCHAR(20),
            ma_nganh_trung_tuyen    VARCHAR(50),
            ten_nganh_trung_tuyen   TEXT,
            imported_at             TIMESTAMP DEFAULT NOW(),
            imported_by             VARCHAR(100),
            UNIQUE(dot_tuyen_sinh_id, so_cccd)
        )"
    ],

    // --- 2. Thêm cột bi_loai_truong_khac vào v_calc_summary ---
    [
        'label' => 'ALTER v_calc_summary ADD bi_loai_truong_khac',
        'sql'   => "ALTER TABLE public.v_calc_summary
                        ADD COLUMN IF NOT EXISTS bi_loai_truong_khac BOOLEAN DEFAULT FALSE"
    ],

    // --- 3. Thêm cột ma_truong_trung_tuyen_bo vào v_calc_summary ---
    [
        'label' => 'ALTER v_calc_summary ADD ma_truong_trung_tuyen_bo',
        'sql'   => "ALTER TABLE public.v_calc_summary
                        ADD COLUMN IF NOT EXISTS ma_truong_trung_tuyen_bo VARCHAR(20) DEFAULT NULL"
    ],

    // --- 4. Index: dot_tuyen_sinh_id + so_cccd ---
    [
        'label' => 'CREATE INDEX idx_ket_qua_bo_dot_cccd',
        'sql'   => "CREATE INDEX IF NOT EXISTS idx_ket_qua_bo_dot_cccd
                        ON public.ket_qua_loc_ao_bo_gd(dot_tuyen_sinh_id, so_cccd)"
    ],

    // --- 5. Index: ma_truong_trung_tuyen ---
    [
        'label' => 'CREATE INDEX idx_ket_qua_bo_ma_truong',
        'sql'   => "CREATE INDEX IF NOT EXISTS idx_ket_qua_bo_ma_truong
                        ON public.ket_qua_loc_ao_bo_gd(ma_truong_trung_tuyen)"
    ],

    // --- 6. Partial index: bi_loai_truong_khac = TRUE ---
    [
        'label' => 'CREATE INDEX idx_v_calc_bi_loai (partial)',
        'sql'   => "CREATE INDEX IF NOT EXISTS idx_v_calc_bi_loai
                        ON public.v_calc_summary(bi_loai_truong_khac)
                        WHERE bi_loai_truong_khac = TRUE"
    ],

    // --- 7. Bật RLS cho bảng mới ---
    [
        'label' => 'ENABLE RLS on ket_qua_loc_ao_bo_gd',
        'sql'   => "ALTER TABLE public.ket_qua_loc_ao_bo_gd ENABLE ROW LEVEL SECURITY"
    ],

    // --- 8. Xóa policy cũ nếu tồn tại ---
    [
        'label' => 'DROP POLICY (if exists)',
        'sql'   => 'DROP POLICY IF EXISTS "Deny public access to ket_qua_loc_ao_bo_gd" ON public.ket_qua_loc_ao_bo_gd'
    ],

    // --- 9. Tạo policy deny public ---
    [
        'label' => 'CREATE POLICY deny public',
        'sql'   => 'CREATE POLICY "Deny public access to ket_qua_loc_ao_bo_gd"
                        ON public.ket_qua_loc_ao_bo_gd FOR ALL USING (false)'
    ],
];

// ===================== CHẠY TỪNG STATEMENT =====================
$ok   = 0;
$skip = 0;
$fail = 0;

foreach ($statements as $i => $item) {
    $n = $i + 1;
    try {
        $pdo->exec($item['sql']);
        echo "  [OK]   #$n {$item['label']}\n";
        $ok++;
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Bỏ qua các lỗi "đã tồn tại"
        if (str_contains($msg, 'already exists') ||
            str_contains($msg, 'duplicate column') ||
            str_contains($msg, 'multiple primary keys')) {
            echo "  [SKIP] #$n {$item['label']} (already exists)\n";
            $skip++;
        } else {
            echo "  [FAIL] #$n {$item['label']}\n";
            echo "         => " . trim($msg) . "\n";
            $fail++;
        }
    }
}

// ===================== VERIFY =====================
echo "\n------------------------------------------------------------\n";
echo " VERIFICATION\n";
echo "------------------------------------------------------------\n";

try {
    // Kiểm tra bảng
    $tableExists = $pdo->query(
        "SELECT to_regclass('public.ket_qua_loc_ao_bo_gd')"
    )->fetchColumn();
    echo " Table ket_qua_loc_ao_bo_gd : " . ($tableExists ? "EXISTS  OK" : "NOT FOUND!") . "\n";

    // Kiểm tra cột mới
    $cols = $pdo->query(
        "SELECT column_name, data_type
         FROM information_schema.columns
         WHERE table_schema = 'public'
           AND table_name   = 'v_calc_summary'
           AND column_name  IN ('bi_loai_truong_khac', 'ma_truong_trung_tuyen_bo')
         ORDER BY column_name"
    )->fetchAll();

    if (empty($cols)) {
        echo " New columns in v_calc_summary : NOT FOUND!\n";
    } else {
        foreach ($cols as $col) {
            echo " Column {$col['column_name']} ({$col['data_type']}) : OK\n";
        }
    }

    // Đếm số đợt tuyển sinh hiện có (để verify kết nối đúng DB)
    $dotCount = $pdo->query("SELECT COUNT(*) FROM public.dot_tuyen_sinh")->fetchColumn();
    echo " dot_tuyen_sinh records     : $dotCount (connectivity check)\n";

} catch (PDOException $e) {
    echo " VERIFY ERROR: " . $e->getMessage() . "\n";
}

echo "------------------------------------------------------------\n";
echo " RESULT: $ok OK   $skip SKIPPED   $fail FAILED\n";
echo "============================================================\n";

if ($fail === 0) {
    echo " Migration v28 applied to THV2026 SUCCESSFULLY!\n";
} else {
    echo " WARNINGS: $fail statement(s) failed — review output above.\n";
}
echo "============================================================\n";
