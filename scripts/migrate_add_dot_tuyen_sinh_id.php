<?php
require_once __DIR__ . '/../app/Core/Database.php';
use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();
    // Add column if not exists
    $db->exec("ALTER TABLE public.nguyen_vong ADD COLUMN IF NOT EXISTS dot_tuyen_sinh_id BIGINT;");
    // Add foreign key constraint
    $db->exec("ALTER TABLE public.nguyen_vong ADD CONSTRAINT IF NOT EXISTS fk_nguyen_vong_dot_tuyen_sinh FOREIGN KEY (dot_tuyen_sinh_id) REFERENCES public.dot_tuyen_sinh(id) ON UPDATE CASCADE ON DELETE SET NULL;");
    $db->commit();
    echo "Migration completed successfully.\n";
} catch (\Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
