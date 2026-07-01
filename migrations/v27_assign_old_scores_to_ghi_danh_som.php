<?php
/**
 * Migration v27: Assign Old Scores to Ghi Danh Som
 * Assigns all existing NULL dot_tuyen_sinh_id records in diem_nang_khieu and diem_chung_chi to "Ghi danh sớm".
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

echo "\n=== Migration v27: Assign Old Scores to Ghi Danh Som ===\n\n";

try {
    $db->beginTransaction();

    // 1. Find or create the "Ghi danh sớm" admission session
    $stmt = $db->prepare("SELECT id FROM dot_tuyen_sinh WHERE ten_dot = ?");
    $stmt->execute(['Ghi danh sớm']);
    $sessionId = $stmt->fetchColumn();

    if (!$sessionId) {
        echo "Creating 'Ghi danh sớm' session...\n";
        $stmtIns = $db->prepare("
            INSERT INTO dot_tuyen_sinh (ten_dot, nam_tuyen_sinh, ngay_bat_dau, ngay_ket_thuc, kich_hoat)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtIns->execute(['Ghi danh sớm', 2026, '2026-01-01', '2026-12-31', false]);
        $sessionId = $db->lastInsertId();
        echo "Created session with ID: $sessionId\n";
    } else {
        echo "Found existing 'Ghi danh sớm' session with ID: $sessionId\n";
    }

    // 2. Update diem_nang_khieu
    echo "[ diem_nang_khieu ]\n";
    $stmtNK = $db->prepare("UPDATE diem_nang_khieu SET dot_tuyen_sinh_id = ? WHERE dot_tuyen_sinh_id IS NULL");
    $stmtNK->execute([$sessionId]);
    $nkCount = $stmtNK->rowCount();
    echo "  Updated $nkCount records.\n";

    // 3. Update diem_chung_chi
    echo "[ diem_chung_chi ]\n";
    $stmtCC = $db->prepare("UPDATE diem_chung_chi SET dot_tuyen_sinh_id = ? WHERE dot_tuyen_sinh_id IS NULL");
    $stmtCC->execute([$sessionId]);
    $ccCount = $stmtCC->rowCount();
    echo "  Updated $ccCount records.\n";

    $db->commit();
    echo "=== Migration v27 Completed Successfully ===\n";

} catch (\Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
