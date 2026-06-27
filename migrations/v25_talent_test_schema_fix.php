<?php
/**
 * Migration v25: Talent Test Schema Fix
 * Adds missing columns for the V2 6-step workflow:
 *   - talent_test_assignments: is_eligible, ineligible_reason, bag_number, is_manual_add
 *   - talent_test_subjects: exam_type, exam_date, exam_time, duration_minutes, preparation_minutes
 *   - talent_test_sessions: is_published
 *   - talent_test_exam_configs: new table for storing SBD prefix/length config
 * Also relaxes NOT NULL constraints on room_id and exam_number.
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

// Use system role to bypass RLS for migrations
$dbInstance = \App\Core\Database::getInstance();
$dbInstance->setSystemRole('admin');
$db = $dbInstance->getConnection();

$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

echo "\n=== Migration v25: Talent Test Schema Fix ===\n\n";

$steps = [];
$errors = [];

function run(PDO $db, string $label, string $sql): void {
    global $steps, $errors;
    try {
        $db->exec($sql);
        echo "  ✅  $label\n";
        $steps[] = $label;
    } catch (\PDOException $e) {
        $msg = $e->getMessage();
        // "already exists" / "duplicate column" errors are safe to ignore
        if (
            stripos($msg, 'already exists') !== false ||
            stripos($msg, 'duplicate column') !== false
        ) {
            echo "  ⏭️  $label (already exists, skipped)\n";
            $steps[] = $label . ' [skipped]';
        } else {
            echo "  ❌  $label\n     ERROR: $msg\n";
            $errors[] = $label . ': ' . $msg;
        }
    }
}

// ── 1. talent_test_assignments ────────────────────────────────────────────────
echo "[ talent_test_assignments ]\n";

run($db, 'ALTER room_id → nullable',
    "ALTER TABLE talent_test_assignments ALTER COLUMN room_id DROP NOT NULL");

run($db, 'ALTER exam_number → nullable',
    "ALTER TABLE talent_test_assignments ALTER COLUMN exam_number DROP NOT NULL");

run($db, 'ADD is_eligible',
    "ALTER TABLE talent_test_assignments ADD COLUMN IF NOT EXISTS is_eligible BOOLEAN NOT NULL DEFAULT TRUE");

run($db, 'ADD ineligible_reason',
    "ALTER TABLE talent_test_assignments ADD COLUMN IF NOT EXISTS ineligible_reason TEXT");

run($db, 'ADD bag_number',
    "ALTER TABLE talent_test_assignments ADD COLUMN IF NOT EXISTS bag_number VARCHAR(50)");

run($db, 'ADD is_manual_add',
    "ALTER TABLE talent_test_assignments ADD COLUMN IF NOT EXISTS is_manual_add BOOLEAN NOT NULL DEFAULT FALSE");

// Backfill: any existing row with NULL is_eligible should default to TRUE
run($db, 'Backfill is_eligible = TRUE for existing rows',
    "UPDATE talent_test_assignments SET is_eligible = TRUE WHERE is_eligible IS NULL");

// ── 2. talent_test_subjects ───────────────────────────────────────────────────
echo "\n[ talent_test_subjects ]\n";

run($db, 'ADD exam_type',
    "ALTER TABLE talent_test_subjects ADD COLUMN IF NOT EXISTS exam_type VARCHAR(20) DEFAULT 'written'");

run($db, 'ADD exam_date',
    "ALTER TABLE talent_test_subjects ADD COLUMN IF NOT EXISTS exam_date DATE");

run($db, 'ADD exam_time',
    "ALTER TABLE talent_test_subjects ADD COLUMN IF NOT EXISTS exam_time TIME");

run($db, 'ADD duration_minutes',
    "ALTER TABLE talent_test_subjects ADD COLUMN IF NOT EXISTS duration_minutes INT DEFAULT 120");

run($db, 'ADD preparation_minutes',
    "ALTER TABLE talent_test_subjects ADD COLUMN IF NOT EXISTS preparation_minutes INT DEFAULT 15");

// ── 3. talent_test_sessions ───────────────────────────────────────────────────
echo "\n[ talent_test_sessions ]\n";

run($db, 'ADD is_published',
    "ALTER TABLE talent_test_sessions ADD COLUMN IF NOT EXISTS is_published BOOLEAN NOT NULL DEFAULT FALSE");

// ── 4. talent_test_exam_configs (new table) ───────────────────────────────────
echo "\n[ talent_test_exam_configs ]\n";

run($db, 'CREATE talent_test_exam_configs',
    "CREATE TABLE IF NOT EXISTS talent_test_exam_configs (
        id          SERIAL PRIMARY KEY,
        session_id  INT NOT NULL,
        config_key  VARCHAR(100) NOT NULL,
        config_value TEXT,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_exam_config UNIQUE (session_id, config_key),
        CONSTRAINT fk_exam_config_session FOREIGN KEY (session_id)
            REFERENCES talent_test_sessions(id) ON DELETE CASCADE
    )");

// ── 5. Indexes for performance ─────────────────────────────────────────────────
echo "\n[ Indexes ]\n";

run($db, 'INDEX talent_test_assignments.is_eligible',
    "CREATE INDEX IF NOT EXISTS idx_tta_is_eligible
     ON talent_test_assignments(is_eligible)");

run($db, 'INDEX talent_test_assignments.subject_id',
    "CREATE INDEX IF NOT EXISTS idx_tta_subject_id
     ON talent_test_assignments(subject_id)");

run($db, 'INDEX talent_test_assignments.room_id',
    "CREATE INDEX IF NOT EXISTS idx_tta_room_id
     ON talent_test_assignments(room_id)");

// ── Summary ────────────────────────────────────────────────────────────────────
echo "\n=== Done ===\n";
echo "  Steps completed : " . count($steps) . "\n";
echo "  Errors          : " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nErrors that need attention:\n";
    foreach ($errors as $err) {
        echo "  - $err\n";
    }
    exit(1);
}

echo "\nMigration v25 completed successfully.\n\n";
