<?php
require __DIR__ . '/../app/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    $sql = "UPDATE public.mau_in SET file_path = 'PHIEU_NHAP_HOC_1786695997.docx' WHERE ma_mau = 'PHIEU_NHAP_HOC';";
    $db->exec($sql);
    echo "Thành công! Đã cập nhật file_path cho mẫu in PHIEU_NHAP_HOC.";
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}
