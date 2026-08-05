<?php
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    echo "<h1>Chạy Migration v32: Cột `ket_qua_bo_gd_du_kien` trong v_calc_summary</h1>";
    echo "<pre>";

    // 1. Thêm cột ket_qua_bo_gd_du_kien
    $db->exec("
        ALTER TABLE public.v_calc_summary 
        ADD COLUMN IF NOT EXISTS ket_qua_bo_gd_du_kien VARCHAR(20) DEFAULT NULL;
    ");
    echo "✓ Đã thêm cột `ket_qua_bo_gd_du_kien` vào `v_calc_summary`\n";

    // 2. Tạo index
    $db->exec("
        CREATE INDEX IF NOT EXISTS idx_v_calc_ket_qua_bo_gd_du_kien
        ON public.v_calc_summary(ket_qua_bo_gd_du_kien)
        WHERE ket_qua_bo_gd_du_kien IS NOT NULL;
    ");
    echo "✓ Đã tạo index `idx_v_calc_ket_qua_bo_gd_du_kien`\n";

    echo "\nHoàn tất migration v32!\n";
    echo "</pre>";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
