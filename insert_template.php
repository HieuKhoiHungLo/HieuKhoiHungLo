<?php
require 'vendor/autoload.php';
if (class_exists('Dotenv\Dotenv')) { Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad(); }
$db = App\Core\Database::getInstance()->getConnection();

$subject = 'THÔNG BÁO VỀ VIỆC CẬP NHẬT HỒ SƠ ĐĂNG KÝ GHI DANH';
$code = 'update_profile_reminder';
$type = 'marketing';
$body = '
<p>Chào <b> {{name}}</b>,</p>
<p>
Hệ thống ghi danh sớm của <b>Trường Đại học Hùng Vương</b> đã thiết lập tài khoản đăng nhập cho em.
</p>
<!-- Nội dung chính -->
<p>
👉 Để hoàn tất quá trình đăng ký, em vui lòng:
</p>
<ul>
<li><b>Đăng nhập lại hệ thống</b></li>
<li><b>Cập nhật đầy đủ hồ sơ ghi danh</b> (thông tin cá nhân, điểm học bạ, nguyện vọng...)</li>
</ul>
<!-- Link -->
<p>
🔗 <b>Link đăng nhập:</b><br>
<a href="https://tuyensinh.hvu.edu.vn" style="color: #1155cc;">
https://tuyensinh.hvu.edu.vn
</a>
</p>
<!-- Video -->
<p>
🎥 <b>Video hướng dẫn:</b><br>
<a href="https://www.youtube.com/watch?v=xt4XfrWChfs&list=TLGGTWZoiCZZhsYyMDAzMjAyNg&t=1s" style="color: #1155cc;">
Xem hướng dẫn tại đây
</a>
</p>
<!-- Support -->
<p>
📞 <b>Cần hỗ trợ?</b><br>
Liên hệ: <b style="color: red;">0866 993 468</b>
</p>
<!-- Note -->
<div style="background-color: #f4f8ff; padding: 10px; border-left: 4px solid #0b5394;">
<b>Lưu ý:</b> Việc cập nhật hồ sơ đầy đủ và chính xác sẽ giúp nhà trường hỗ trợ em tốt hơn trong quá trình xét tuyển.
</div>
<br>
<!-- Footer -->
<p>
Trân trọng,<br>
<b>Hệ thống ghi danh sớm</b><br>
Trường Đại học Hùng Vương
</p>
';

$stmt = $db->prepare("INSERT INTO email_templates (code, subject, body, type, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
$res = $stmt->execute([$code, $subject, trim($body), $type]);

if ($res) {
    echo "Successfully inserted template.\n";
} else {
    echo "Failed to insert template.\n";
}
