<?php
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    echo "<h1>Cài đặt Tài khoản Nhập học</h1>";
    echo "<pre>";

    // 1. Đảm bảo role_id = 4 tồn tại
    $db->exec("
        INSERT INTO public.roles (id, name, display_name, permissions)
        VALUES (4, 'enrollment_officer', 'Nhập học', '[\"enrollment.process\"]')
        ON CONFLICT (id) DO UPDATE
            SET name = EXCLUDED.name,
                display_name = EXCLUDED.display_name,
                permissions = EXCLUDED.permissions;
    ");
    echo "Đã đảm bảo Role 'Nhập học' (ID=4) tồn tại.\n";

    // Update sequence
    $db->exec("SELECT setval('roles_id_seq', GREATEST((SELECT MAX(id) FROM roles), 4));");

    // 2. Tạo 12 tài khoản bàn nhập học
    $accounts = [
        ['ban1',  'Bàn 1 - HT Trung tâm'],
        ['ban2',  'Bàn 2 - HT Trung tâm'],
        ['ban3',  'Bàn 3 - HT Trung tâm'],
        ['ban4',  'Bàn 4 - Giảng đường D'],
        ['ban5',  'Bàn 5 - Giảng đường D'],
        ['ban6',  'Bàn 6 - Giảng đường E'],
        ['ban7',  'Bàn 7 - Giảng đường E'],
        ['ban8',  'Bàn 8 - Góc VH Hàn Quốc'],
        ['ban9',  'Bàn 9 - Góc VH Hàn Quốc'],
        ['ban10', 'Bàn 10 - HT Tầng 3'],
        ['ban11', 'Bàn 11 - HT Tầng 3'],
        ['ban12', 'Bàn 12 - HT Tầng 3']
    ];

    $defaultPassword = 'hvu2026';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

    $stmt = $db->prepare("
        INSERT INTO public.quan_tri_vien (ten_dang_nhap, mat_khau, ho_ten, role_id, is_active, permissions)
        VALUES (?, ?, ?, 4, 1, '[\"enrollment.process\"]')
        ON CONFLICT (ten_dang_nhap) DO NOTHING
    ");

    $count = 0;
    foreach ($accounts as $acc) {
        $stmt->execute([$acc[0], $hashedPassword, $acc[1]]);
        if ($stmt->rowCount() > 0) {
            echo "Đã tạo tài khoản: {$acc[0]} - {$acc[1]}\n";
            $count++;
        } else {
            echo "Tài khoản đã tồn tại: {$acc[0]}\n";
        }
    }

    echo "\nHoàn tất! Đã tạo mới {$count} tài khoản.\n";
    echo "</pre>";

} catch (\Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
