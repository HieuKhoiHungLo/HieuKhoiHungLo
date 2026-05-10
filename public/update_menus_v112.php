<?php
require_once __DIR__ . '/../vendor/autoload.php';
// Load Env
try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

// Mocking basic env if needed, but assuming standard HVU environment
use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    echo "Updating menus for V 1.1.2...\n";

    // 1. Get the group ID for "TIN TỨC & THÔNG BÁO"
    $stmt = $db->prepare("SELECT id FROM menus WHERE title = 'TIN TỨC & THÔNG BÁO' AND position = 'admin_sidebar' LIMIT 1");
    $stmt->execute();
    $groupId = $stmt->fetchColumn();

    if (!$groupId) {
        // Create the group if it doesn't exist
        $stmt = $db->prepare("INSERT INTO menus (title, icon, position, order_index, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['TIN TỨC & THÔNG BÁO', 'fa-bullhorn', 'admin_sidebar', 50, 1]);
        $groupId = $db->lastInsertId();
    }

    // 2. Define the sub-menus for this group
    $items = [
        [
            'title' => 'Quản lý Chuyên mục',
            'url' => '/admin/categories',
            'icon' => 'fa-tags',
            'perm' => 'posts',
            'order' => 40
        ],
        [
            'title' => 'Thư viện Media',
            'url' => '/admin/media',
            'icon' => 'fa-photo-video',
            'perm' => 'posts',
            'order' => 50
        ]
    ];

    foreach ($items as $item) {
        // Check if item already exists
        $check = $db->prepare("SELECT id FROM menus WHERE title = ? AND parent_id = ? LIMIT 1");
        $check->execute([$item['title'], $groupId]);
        if (!$check->fetch()) {
            $ins = $db->prepare("INSERT INTO menus (parent_id, title, url, icon, permission_required, order_index, position, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$groupId, $item['title'], $item['url'], $item['icon'], $item['perm'], $item['order'], 'admin_sidebar', 1]);
            echo "Added menu: {$item['title']}\n";
        } else {
            echo "Menu already exists: {$item['title']}\n";
        }
    }

    echo "Update complete. Please delete this file after running.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
