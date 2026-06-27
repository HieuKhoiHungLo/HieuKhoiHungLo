<?php
/**
 * Migration v26: Score Session Isolation
 * Drops the strict unique constraints (so_cccd, ma_mon) on diem_nang_khieu and diem_chung_chi,
 * and adds constraints (so_cccd, ma_mon, dot_tuyen_sinh_id) to allow different scores in different sessions.
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}

require_once __DIR__ . '/../app/Core/Database.php';

$dbInstance = \App\Core\Database::getInstance();
$dbInstance->setSystemRole('admin');
$db = $dbInstance->getConnection();

$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

echo "\n=== Migration v26: Score Session Isolation ===\n\n";

try {
    $db->beginTransaction();

    // 1. Update diem_nang_khieu
    echo "[ diem_nang_khieu ]\n";
    echo "  Dropping old unique constraint...\n";
    $db->exec("ALTER TABLE diem_nang_khieu DROP CONSTRAINT IF EXISTS diem_nang_khieu_so_cccd_ma_mon_key CASCADE");
    
    echo "  Adding new unique constraint including dot_tuyen_sinh_id...\n";
    $db->exec("ALTER TABLE diem_nang_khieu ADD CONSTRAINT unique_nang_khieu_cccd_mon_dot UNIQUE (so_cccd, ma_mon, dot_tuyen_sinh_id)");
    echo "  ✅ Success!\n\n";

    // 2. Update diem_chung_chi
    echo "[ diem_chung_chi ]\n";
    echo "  Dropping old unique constraint...\n";
    $db->exec("ALTER TABLE diem_chung_chi DROP CONSTRAINT IF EXISTS diem_chung_chi_so_cccd_ma_mon_key CASCADE");
    
    echo "  Adding new unique constraint including dot_tuyen_sinh_id...\n";
    $db->exec("ALTER TABLE diem_chung_chi ADD CONSTRAINT unique_chung_chi_cccd_mon_dot UNIQUE (so_cccd, ma_mon, dot_tuyen_sinh_id)");
    echo "  ✅ Success!\n\n";

    $db->commit();
    echo "=== Migration v26 Completed Successfully ===\n";

} catch (\Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
