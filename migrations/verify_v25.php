<?php
$envFile = __DIR__ . '/.env';
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $l) {
    $l = trim($l);
    if ($l === '' || $l[0] === '#' || strpos($l, '=') === false) continue;
    [$n, $v] = explode('=', $l, 2);
    $_ENV[trim($n)] = trim($v);
    putenv(trim($n) . '=' . trim($v));
}

require_once __DIR__ . '/app/Core/Database.php';
$dbInst = App\Core\Database::getInstance();
$dbInst->setSystemRole('admin');
$pdo = $dbInst->getConnection();

$checks = [
    ['talent_test_assignments', 'is_eligible'],
    ['talent_test_assignments', 'ineligible_reason'],
    ['talent_test_assignments', 'bag_number'],
    ['talent_test_assignments', 'is_manual_add'],
    ['talent_test_subjects', 'exam_type'],
    ['talent_test_subjects', 'exam_date'],
    ['talent_test_subjects', 'exam_time'],
    ['talent_test_subjects', 'duration_minutes'],
    ['talent_test_subjects', 'preparation_minutes'],
    ['talent_test_sessions', 'is_published'],
];

echo "Column verification:\n";
$allOk = true;
foreach ($checks as [$tbl, $col]) {
    $stmt = $pdo->prepare('SELECT column_name FROM information_schema.columns WHERE table_name=? AND column_name=?');
    $stmt->execute([$tbl, $col]);
    $found = $stmt->fetchColumn();
    echo ($found ? "  [OK]  " : "  [!!]  ") . "$tbl.$col\n";
    if (!$found) $allOk = false;
}

$stmt = $pdo->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name='talent_test_exam_configs')");
$exists = $stmt->fetchColumn();
echo ($exists ? "  [OK]  " : "  [!!]  ") . "TABLE talent_test_exam_configs\n";
if (!$exists) $allOk = false;

echo "\n" . ($allOk ? "✅ All checks passed!" : "❌ Some checks failed!") . "\n";
