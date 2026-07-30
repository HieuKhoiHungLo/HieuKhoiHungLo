<?php
/**
 * Migration: Thêm các cột phục vụ in giấy báo vào bảng ket_qua_trung_tuyen
 */
require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Load Env
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {
    echo "DotEnv Warning: " . $e->getMessage() . "\n";
}

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    echo "Đang kết nối cơ sở dữ liệu...\n";
    
    // Kiểm tra sự tồn tại của bảng ket_qua_trung_tuyen
    $tableExists = $db->query("SELECT to_regclass('public.ket_qua_trung_tuyen')")->fetchColumn();
    if (!$tableExists) {
        throw new Exception("Bảng public.ket_qua_trung_tuyen không tồn tại trong database này!");
    }
    
    // Lấy danh sách các cột hiện có
    $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name='ket_qua_trung_tuyen'");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredCols = [
        'so_giay_bao' => 'VARCHAR(100)',
        'thoi_gian_nhap' => 'VARCHAR(100)',
        'kinh_phi' => 'VARCHAR(255)',
        'khoa_hoc' => 'VARCHAR(100)',
        'link_anh' => 'TEXT'
    ];
    
    foreach ($requiredCols as $colName => $colType) {
        if (!in_array($colName, $cols)) {
            echo "Thêm cột $colName...\n";
            $db->exec("ALTER TABLE public.ket_qua_trung_tuyen ADD COLUMN $colName $colType");
        } else {
            echo "Cột $colName đã tồn tại.\n";
        }
    }
    
    echo "Cập nhật cấu trúc bảng ket_qua_trung_tuyen thành công!\n";
} catch (Exception $e) {
    echo "LỖI: " . $e->getMessage() . "\n";
}
