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
    echo "<h1>Cài đặt Schema và Menu - Phân hệ Nhập học</h1>";
    echo "<pre>";

    // 1. Tạo bảng nhap_hoc_ho_so
    $db->exec("
        CREATE TABLE IF NOT EXISTS nhap_hoc_ho_so (
            id SERIAL PRIMARY KEY,
            session_id INT NOT NULL,
            ten_ho_so VARCHAR(255) NOT NULL,
            cac_gia_tri TEXT NOT NULL,
            gia_tri_mac_dinh VARCHAR(255),
            bat_buoc BOOLEAN DEFAULT true,
            thu_tu INT DEFAULT 0,
            ghi_chu TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES dot_tuyen_sinh(id) ON DELETE CASCADE
        );
    ");
    echo "Đã tạo bảng `nhap_hoc_ho_so`\n";

    // 2. Tạo bảng nhap_hoc
    $db->exec("
        CREATE TABLE IF NOT EXISTS nhap_hoc (
            id SERIAL PRIMARY KEY,
            ket_qua_id INT NOT NULL,
            session_id INT NOT NULL,
            so_cccd VARCHAR(20) NOT NULL,
            nguoi_nhap INT NULL,
            trang_thai VARCHAR(50) DEFAULT 'da_nhap_hoc',
            ngay_nhap_hoc TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            da_nop_tien BOOLEAN DEFAULT false,
            so_tien_da_nop DECIMAL(15,2) DEFAULT 0,
            ghi_chu_can_bo TEXT,
            da_gui_email BOOLEAN DEFAULT false,
            ma_phieu VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ket_qua_id) REFERENCES ket_qua_trung_tuyen(id) ON DELETE CASCADE,
            FOREIGN KEY (session_id) REFERENCES dot_tuyen_sinh(id) ON DELETE CASCADE,
            FOREIGN KEY (nguoi_nhap) REFERENCES quan_tri_vien(id) ON DELETE SET NULL
        );
    ");
    echo "Đã tạo bảng `nhap_hoc`\n";

    // 3. Tạo bảng nhap_hoc_ho_so_gia_tri
    $db->exec("
        CREATE TABLE IF NOT EXISTS nhap_hoc_ho_so_gia_tri (
            id SERIAL PRIMARY KEY,
            nhap_hoc_id INT NOT NULL,
            ho_so_id INT NOT NULL,
            gia_tri VARCHAR(255) NOT NULL,
            ghi_chu TEXT,
            nguoi_nhan VARCHAR(255),
            FOREIGN KEY (nhap_hoc_id) REFERENCES nhap_hoc(id) ON DELETE CASCADE,
            FOREIGN KEY (ho_so_id) REFERENCES nhap_hoc_ho_so(id) ON DELETE CASCADE
        );
    ");
    echo "Đã tạo bảng `nhap_hoc_ho_so_gia_tri`\n";

    // 4. Tạo nhóm menu cha "NHẬP HỌC"
    $stmt = $db->prepare("SELECT id FROM menus WHERE title = 'NHẬP HỌC' AND parent_id IS NULL AND position = 'admin_sidebar' LIMIT 1");
    $stmt->execute();
    $groupId = $stmt->fetchColumn();

    if (!$groupId) {
        $stmt = $db->prepare("INSERT INTO menus (title, url, icon, position, order_index, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['NHẬP HỌC', '#', 'fa-user-graduate', 'admin_sidebar', 45, 1]);
        $groupId = $db->lastInsertId();
        echo "Đã tạo nhóm menu cha 'NHẬP HỌC'\n";
    } else {
        echo "Tìm thấy nhóm menu cha 'NHẬP HỌC' ID: {$groupId}\n";
    }

    // 5. Thêm các menu con
    $items = [
        [
            'title' => 'Cấu hình Hồ sơ',
            'url' => '/admin/enrollment/setup',
            'icon' => 'fa-cogs',
            'perm' => 'admission.edit',
            'order' => 10
        ],
        [
            'title' => 'Xử lý Nhập học',
            'url' => '/admin/enrollment/process',
            'icon' => 'fa-id-card',
            'perm' => 'admission.edit',
            'order' => 20
        ],
        [
            'title' => 'Thống kê Nhập học',
            'url' => '/admin/enrollment/stats',
            'icon' => 'fa-chart-bar',
            'perm' => 'admission.view',
            'order' => 30
        ]
    ];

    foreach ($items as $item) {
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
            $upd = $db->prepare("UPDATE menus SET parent_id = ? WHERE id = ?");
            $upd->execute([$groupId, $existingId]);
            echo "Menu '{$item['title']}' đã tồn tại, đã cập nhật parent_id\n";
        }
    }

    // Xóa cache
    Cache::forget("menus_active_admin_sidebar");
    echo "Đã xóa cache menu.\n";
    echo "\nHoàn tất!\n";
    echo "</pre>";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
