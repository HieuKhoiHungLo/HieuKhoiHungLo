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
    echo "<h1>Cập nhật quyền và Menu cho Tài khoản Lãnh đạo</h1>";
    echo "<pre>";

    // 1. Cập nhật quyền cho vai trò Lãnh đạo (role_id = 3)
    $stmt = $db->prepare("SELECT id, permissions FROM roles WHERE id = 3 OR name = 'leadership'");
    $stmt->execute();
    $role = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($role) {
        $perms = json_decode($role['permissions'], true) ?? [];
        if (!in_array('admission.view', $perms)) {
            $perms[] = 'admission.view';
        }
        if (!in_array('stats', $perms)) {
            $perms[] = 'stats';
        }
        $updRole = $db->prepare("UPDATE roles SET permissions = ? WHERE id = ?");
        $updRole->execute([json_encode($perms), $role['id']]);
        echo "Đã bổ sung quyền 'admission.view' và 'stats' cho vai trò Lãnh đạo (Role ID: {$role['id']})\n";
    }

    // 2. Kiểm tra & Cập nhật menu "Tổng quan Lọc ảo" (/admin/admission/overview-virtual-filter)
    $checkMenu = $db->prepare("SELECT id, parent_id, permission_required, is_active FROM menus WHERE url = '/admin/admission/overview-virtual-filter' LIMIT 1");
    $checkMenu->execute();
    $menu = $checkMenu->fetch(\PDO::FETCH_ASSOC);

    // Tìm ID của nhóm "TỔNG QUAN"
    $stmtGroup = $db->prepare("SELECT id FROM menus WHERE title = 'TỔNG QUAN' AND parent_id IS NULL AND position = 'admin_sidebar' LIMIT 1");
    $stmtGroup->execute();
    $groupId = $stmtGroup->fetchColumn();

    if (!$groupId) {
        $insGroup = $db->prepare("INSERT INTO menus (title, icon, position, order_index, is_active) VALUES ('TỔNG QUAN', 'fa-chart-line', 'admin_sidebar', 10, TRUE)");
        $insGroup->execute();
        $groupId = $db->lastInsertId();
    }

    if ($menu) {
        $updMenu = $db->prepare("UPDATE menus SET parent_id = ?, permission_required = 'admission.view', is_active = TRUE WHERE id = ?");
        $updMenu->execute([$groupId, $menu['id']]);
        echo "Đã cập nhật Menu 'Tổng quan Lọc ảo' (ID: {$menu['id']}) thuộc nhóm 'TỔNG QUAN' với quyền 'admission.view'\n";
    } else {
        $insMenu = $db->prepare("INSERT INTO menus (parent_id, title, url, icon, permission_required, order_index, position, is_active) VALUES (?, 'Tổng quan Lọc ảo', '/admin/admission/overview-virtual-filter', 'fa-chart-pie', 'admission.view', 30, 'admin_sidebar', TRUE)");
        $insMenu->execute([$groupId]);
        echo "Đã tạo mới Menu 'Tổng quan Lọc ảo' thuộc nhóm 'TỔNG QUAN'\n";
    }

    // 3. Xóa toàn bộ Cache liên quan đến Menu và Permissions
    Cache::forget("menus_active_admin_sidebar");
    
    // Clear session role permission caches in PHP session if active
    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['_role_perms_3']);
    }

    echo "\n[THÀNH CÔNG] Đã đồng bộ Menu và phân quyền Lãnh đạo!\n";
    echo "</pre>";

} catch (\Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
