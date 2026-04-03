<?php
/**
 * Final Migration & Optimization for Production (THV2026).
 * Safe data integrity fixes and optimization indexes.
 */

// 1. Context Loading
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim(trim($value), "\"'"));
        $_ENV[trim($key)] = trim(trim($value), "\"'");
    }
}

require_once __DIR__ . '/../app/Core/Database.php';
use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    echo "Connected to: " . ($_ENV['DB_HOST'] ?? 'unknown') . "\n";
    
    $db->beginTransaction();

    // STEP 1: Add column to nguyen_vong
    $db->exec("ALTER TABLE public.nguyen_vong ADD COLUMN IF NOT EXISTS dot_tuyen_sinh_id BIGINT;");
    echo "✅ Column nguyen_vong.dot_tuyen_sinh_id check completed.\n";

    // STEP 2: DATA INTEGRITY FIX (Set invalid IDs to NULL)
    echo "Cleaning up invalid dot_tuyen_sinh_id in nguyen_vong...\n";
    $affected = $db->exec("
        UPDATE public.nguyen_vong 
        SET dot_tuyen_sinh_id = NULL 
        WHERE dot_tuyen_sinh_id IS NOT NULL 
          AND dot_tuyen_sinh_id NOT IN (SELECT id FROM public.dot_tuyen_sinh)
    ");
    echo "✅ Cleaned up $affected rows with invalid IDs in nguyen_vong.\n";

    // STEP 3: Add Foreign Key Constraint to nguyen_vong
    $stmt = $db->prepare("SELECT 1 FROM pg_constraint WHERE conname = ?");
    $stmt->execute(['fk_nguyen_vong_dot_tuyen_sinh']);
    if (!$stmt->fetch()) {
        $db->exec("
            ALTER TABLE public.nguyen_vong 
            ADD CONSTRAINT fk_nguyen_vong_dot_tuyen_sinh 
            FOREIGN KEY (dot_tuyen_sinh_id) 
            REFERENCES public.dot_tuyen_sinh(id) 
            ON UPDATE CASCADE 
            ON DELETE SET NULL
        ");
        echo "✅ Foreign key fk_nguyen_vong_dot_tuyen_sinh added.\n";
    } else {
        echo "ℹ️ Foreign key fk_nguyen_vong_dot_tuyen_sinh already exists.\n";
    }

    // STEP 4: Resolve v_calc_summary schema issues
    // Check if v_calc_summary is a table and has the column
    echo "Ensuring v_calc_summary has dot_tuyen_sinh_id...\n";
    $db->exec("ALTER TABLE public.v_calc_summary ADD COLUMN IF NOT EXISTS dot_tuyen_sinh_id BIGINT;");
    echo "✅ Column v_calc_summary.dot_tuyen_sinh_id check completed.\n";

    // Data fix for v_calc_summary if needed
    $db->exec("
        UPDATE public.v_calc_summary 
        SET dot_tuyen_sinh_id = NULL 
        WHERE dot_tuyen_sinh_id IS NOT NULL 
          AND dot_tuyen_sinh_id NOT IN (SELECT id FROM public.dot_tuyen_sinh)
    ");

    $db->commit();
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    // We continue if it's just a view issue, but indexes might fail
    echo "⚠️ Schema migration message: " . $e->getMessage() . "\n";
}

// STEP 5: Apply Optimization Indexes
$indexFile = __DIR__ . '/add_optimization_indexes.sql';
if (!file_exists($indexFile)) {
    echo "❌ Index script not found: $indexFile\n";
    exit(1);
}

echo "Applying optimization indexes...\n";
$indexSql = file_get_contents($indexFile);
$queries = array_filter(array_map('trim', explode(';', $indexSql)));

foreach ($queries as $q) {
    if ($q === '') continue;
    $q = preg_replace('/--.*$/m', '', $q); // Strip comments
    if (trim($q) === '') continue;
    
    try {
        $db->exec($q);
        echo "✅ Index/Analyze success: " . substr(trim($q), 0, 40) . "...\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "ℹ️ Index already exists, skipped.\n";
        } else {
            echo "❌ Index error: " . $e->getMessage() . " in query: " . $q . "\n";
        }
    }
}
echo "✅ Optimization indexes check completed.\n";
?>
