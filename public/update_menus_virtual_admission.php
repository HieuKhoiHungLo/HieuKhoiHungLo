<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load Env
try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Database;
use App\Core\Cache;

try {
    $db = Database::getInstance()->getConnection();
    echo "<h1>Cập nhật Menu Sidebar - Xét tuyển Lọc ảo</h1>";
    echo "<pre>";

    // 1. Tìm ID của nhóm menu cha "TỔNG QUAN" hoặc "QUẢN LÝ CHUNG"
    $stmt = $db->prepare("
        SELECT id FROM menus 
        WHERE (title = 'TỔNG QUAN' OR title = 'QUẢN LÝ CHUNG') 
          AND parent_id IS NULL 
          AND position = 'admin_sidebar' 
        LIMIT 1
    ");
    $stmt->execute();
    $groupId = $stmt->fetchColumn();

    if (!$groupId) {
        // Nếu không có, tạo nhóm TỔNG QUAN mới
        $stmt = $db->prepare("INSERT INTO menus (title, icon, position, order_index, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['TỔNG QUAN', 'fa-chart-line', 'admin_sidebar', 10, 1]);
        $groupId = $db->lastInsertId();
        echo "Đã tạo nhóm menu cha 'TỔNG QUAN'\n";
    } else {
        echo "Tìm thấy nhóm menu cha ID: {$groupId}\n";
    }

    // 2. Định nghĩa các menu con cần thêm vào TỔNG QUAN
    // Thêm cả "Tổng quan Lọc ảo" (/admin/admission/overview-virtual-filter)
    $items = [
        [
            'title' => 'Tổng quan Lọc ảo',
            'url' => '/admin/admission/overview-virtual-filter',
            'icon' => 'fa-chart-pie',
            'perm' => 'admission.view',
            'order' => 30
        ]
    ];

    foreach ($items as $item) {
        // Kiểm tra xem đã tồn tại chưa
        $check = $db->prepare("SELECT id FROM menus WHERE url = ? AND position = 'admin_sidebar' LIMIT 1");
        $check->execute([$item['url']]);
        $existingId = $check->fetchColumn();

        if (!$existingId) {
            $ins = $db->prepare("
                INSERT INTO menus (parent_id, title, url, icon, permission_required, order_index, position, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$groupId, $item['title'], $item['url'], $item['icon'], $item['perm'], $item['order'], 'admin_sidebar', 1]);
            echo "Đã thêm thành công menu: '{$item['title']}' (URL: {$item['url']})\n";
        } else {
            // Nếu đã tồn tại nhưng sai parent_id, cập nhật lại parent_id để đưa vào TỔNG QUAN
            $upd = $db->prepare("UPDATE menus SET parent_id = ? WHERE id = ?");
            $upd->execute([$groupId, $existingId]);
            echo "Menu '{$item['title']}' đã tồn tại, đã đồng bộ đưa vào nhóm 'TỔNG QUAN'\n";
        }
    }

    // Xóa cache menu để hiển thị ngay lập tức
    Cache::forget("menus_active_admin_sidebar");
    echo "Đã làm mới (clear) cache menu thành công!\n";
    echo "\nHoàn thành! Bạn có thể tải lại trang quản trị để xem kết quả.\n";
    echo "</pre>";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
