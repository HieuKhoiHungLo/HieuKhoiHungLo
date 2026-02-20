<?php
// seed_email_template.php
require_once __DIR__ . '/public/index.php'; 

use App\Models\MasterData;

echo "--- Seed Email Template ---\n";

$db = \App\Core\Database::getInstance()->getConnection();

$code = 'application_reviewed';
$subject = 'Thông báo Kết quả Xét duyệt Hồ sơ - Trường Đại học Hùng Vương';
$body = '
<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2 style="color: #0066FF;">Thông báo Kết quả Xét duyệt Hồ sơ</h2>
    <p>Xin chào <strong>{{ho_ten}}</strong>,</p>
    <p>Hội đồng Tuyển sinh Trường Đại học Hùng Vương đã tiến hành xét duyệt hồ sơ đăng ký xét tuyển của bạn.</p>
    
    <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee; margin: 20px 0;">
        <h3 style="margin-top: 0;">Kết quả chi tiết:</h3>
        {{ket_qua_chi_tiet}}
    </div>

    <p><strong>Lưu ý từ Hội đồng:</strong></p>
    <p style="font-style: italic; color: #555;">{{ghi_chu}}</p>

    <p>Nếu hồ sơ yêu cầu chỉnh sửa, vui lòng đăng nhập vào hệ thống và cập nhật lại thông tin sớm nhất có thể.</p>
    
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 13px; color: #888;">
        Trường Đại học Hùng Vương<br>
        Phường Nông Trang, TP. Việt Trì, Tỉnh Phú Thọ<br>
        Hotline: 0983.123.456
    </p>
</div>
';

// Check if exists
$stmt = $db->prepare("SELECT id FROM email_templates WHERE code = ?");
$stmt->execute([$code]);
if ($stmt->fetch()) {
    echo "Template '$code' already exists. Updating...\n";
    $stmt = $db->prepare("UPDATE email_templates SET subject = ?, body = ?, updated_at = NOW() WHERE code = ?");
    $stmt->execute([$subject, $body, $code]);
} else {
    echo "Creating new template '$code'...\n";
    $stmt = $db->prepare("INSERT INTO email_templates (code, subject, body, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmt->execute([$code, $subject, $body]);
}

echo "Done.\n";
