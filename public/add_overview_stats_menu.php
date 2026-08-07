<?php
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Database;
use App\Core\Cache;

try {
    $db = Database::getInstance()->getConnection();
    echo "<h1>Cập nhật Menu 'Số liệu nhập học'</h1>\n";

    // 1. Tìm ID của nhóm "TỔNG QUAN"
    $stmtGroup = $db->prepare("SELECT id FROM menus WHERE title = 'TỔNG QUAN' AND parent_id IS NULL AND position = 'admin_sidebar' LIMIT 1");
    $stmtGroup->execute();
    $groupId = $stmtGroup->fetchColumn();

    if (!$groupId) {
        $insGroup = $db->prepare("INSERT INTO menus (title, icon, position, order_index, is_active) VALUES ('TỔNG QUAN', 'fa-chart-line', 'admin_sidebar', 10, TRUE)");
        $insGroup->execute();
        $groupId = $db->lastInsertId();
    }

    // 2. Kiểm tra & Cập nhật menu "Số liệu nhập học" (/admin/enrollment/overview-stats)
    $checkMenu = $db->prepare("SELECT id FROM menus WHERE url = '/admin/enrollment/overview-stats' LIMIT 1");
    $checkMenu->execute();
    $menu = $checkMenu->fetch(\PDO::FETCH_ASSOC);

    if ($menu) {
        $updMenu = $db->prepare("UPDATE menus SET parent_id = ?, permission_required = 'stats', is_active = TRUE WHERE id = ?");
        $updMenu->execute([$groupId, $menu['id']]);
        echo "Đã cập nhật Menu 'Số liệu nhập học' (ID: {$menu['id']}) thuộc nhóm 'TỔNG QUAN'\n";
    } else {
        $insMenu = $db->prepare("INSERT INTO menus (parent_id, title, url, icon, permission_required, order_index, position, is_active) VALUES (?, 'Số liệu nhập học', '/admin/enrollment/overview-stats', 'fa-chart-pie', 'stats', 50, 'admin_sidebar', TRUE)");
        $insMenu->execute([$groupId]);
        echo "Đã tạo mới Menu 'Số liệu nhập học' thuộc nhóm 'TỔNG QUAN'\n";
    }

    // 3. Xóa Cache liên quan đến Menu
    Cache::forget("menus_active_admin_sidebar");

    echo "\n[THÀNH CÔNG] Đã đồng bộ Menu!\n";
} catch (\Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
