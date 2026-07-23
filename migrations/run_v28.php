<?php
/**
 * Run migration v28 (fixed): BGD Virtual Filter Import
 */

$envFile = __DIR__ . '/../.env';
$env = [];
foreach (file($envFile) as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#' || strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\n\r\"'");
}

$host     = $env['DB_HOST']     ?? 'localhost';
$port     = $env['DB_PORT']     ?? '5432';
$dbname   = $env['DB_DATABASE'] ?? 'postgres';
$user     = $env['DB_USERNAME'] ?? 'postgres';
$password = $env['DB_PASSWORD'] ?? '';
$sslmode  = $env['DB_SSLMODE']  ?? 'require';

echo "Connecting to: $host:$port/$dbname\n";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected OK\n\n";
} catch (PDOException $e) {
    die("CONNECTION ERROR: " . $e->getMessage() . "\n");
}

// Mảng các câu lệnh SQL - mỗi câu một entry để tránh lỗi semicolon split
$statements = [
    // 1. Tạo bảng chứa kết quả từ Bộ GD&ĐT
    "CREATE TABLE IF NOT EXISTS public.ket_qua_loc_ao_bo_gd (
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
    )",

    // 2. Thêm cột bi_loai_truong_khac
    "ALTER TABLE public.v_calc_summary
        ADD COLUMN IF NOT EXISTS bi_loai_truong_khac BOOLEAN DEFAULT FALSE",

    // 3. Thêm cột ma_truong_trung_tuyen_bo
    "ALTER TABLE public.v_calc_summary
        ADD COLUMN IF NOT EXISTS ma_truong_trung_tuyen_bo VARCHAR(20) DEFAULT NULL",

    // 4. Index dot+cccd
    "CREATE INDEX IF NOT EXISTS idx_ket_qua_bo_dot_cccd
        ON public.ket_qua_loc_ao_bo_gd(dot_tuyen_sinh_id, so_cccd)",

    // 5. Index ma_truong
    "CREATE INDEX IF NOT EXISTS idx_ket_qua_bo_ma_truong
        ON public.ket_qua_loc_ao_bo_gd(ma_truong_trung_tuyen)",

    // 6. Index bi_loai (partial index)
    "CREATE INDEX IF NOT EXISTS idx_v_calc_bi_loai
        ON public.v_calc_summary(bi_loai_truong_khac)
        WHERE bi_loai_truong_khac = TRUE",

    // 7. RLS
    "ALTER TABLE public.ket_qua_loc_ao_bo_gd ENABLE ROW LEVEL SECURITY",

    // 8. Drop old policy if exists
    "DROP POLICY IF EXISTS \"Allow service role full access to ket_qua_loc_ao_bo_gd\" ON public.ket_qua_loc_ao_bo_gd",

    // 9. Create deny policy (standard pattern)
    "CREATE POLICY \"Deny public access to ket_qua_loc_ao_bo_gd\"
        ON public.ket_qua_loc_ao_bo_gd FOR ALL USING (false)",
];

$ok = 0;
$skip = 0;
$fail = 0;
foreach ($statements as $i => $stmt) {
    $label = mb_substr(preg_replace('/\s+/', ' ', $stmt), 0, 70);
    try {
        $pdo->exec($stmt);
        echo "OK [$i]: $label\n";
        $ok++;
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, 'already exists') ||
            str_contains($msg, 'duplicate column') ||
            str_contains($msg, 'multiple primary keys')) {
            echo "SKIP [$i]: $label\n";
            $skip++;
        } else {
            echo "FAIL [$i]: $msg\n";
            echo "       SQL: $label\n";
            $fail++;
        }
    }
}

echo "\n============================\n";
echo "Done: $ok OK, $skip skipped, $fail failed\n\n";

// Verify
try {
    $tableExists = $pdo->query("SELECT to_regclass('public.ket_qua_loc_ao_bo_gd')")->fetchColumn();
    echo "Table ket_qua_loc_ao_bo_gd: " . ($tableExists ? "EXISTS OK" : "NOT FOUND!") . "\n";

    $cols = $pdo->query(
        "SELECT column_name FROM information_schema.columns
         WHERE table_schema = 'public' AND table_name = 'v_calc_summary'
         AND column_name IN ('bi_loai_truong_khac', 'ma_truong_trung_tuyen_bo')
         ORDER BY column_name"
    )->fetchAll(PDO::FETCH_COLUMN);
    echo "New columns in v_calc_summary: " . (count($cols) ? implode(', ', $cols) : 'NONE - CHECK ERRORS') . "\n";
} catch (PDOException $e) {
    echo "Verify error: " . $e->getMessage() . "\n";
}
