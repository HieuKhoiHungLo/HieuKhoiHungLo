<?php
require_once 'd:/xampp/htdocs/TS/app/Core/DotEnv.php';
$dotenv = new App\Core\DotEnv('d:/xampp/htdocs/TS/.env');
$dotenv->load();

require_once 'd:/xampp/htdocs/TS/app/Core/Database.php';

$subject = 'Thông báo trúng tuyển năm 2026 - Đợt 1 ({{HoTen}})';
$body = '<!-- BẮT ĐẦU MẪU THÔNG BÁO TRÚNG TUYỂN (GỬI EMAIL) -->
<div style="width:100%; max-width: 750px; margin:0 auto; font-family:Arial,Helvetica,sans-serif; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.05); border:1px solid #e2e8f0;">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#b91c1c,#7f1d1d); padding:18px; text-align:center; color:#fff;">
        <p style="margin:0 0 4px 0; font-size:14px; font-weight:bold; opacity:.9; text-transform:uppercase; letter-spacing:1px;">Chúc mừng TÂN SINH VIÊN Trường Đại học Hùng Vương</p>
        <h1 style="color:#fff; font-size:24px; font-weight:bold; margin:0; text-transform:uppercase; letter-spacing:1px; font-family:Arial,Helvetica,sans-serif;">{{HoTen}}</h1>
    </div>

    <div style="padding:20px;">
        <!-- Lời chào mở đầu -->
        <p style="font-size:14px; color:#374151; line-height:1.6; margin:0 0 16px 0;">
            Chào em <strong>{{HoTen}}</strong>, Hội đồng tuyển sinh Trường Đại học Hùng Vương trân trọng thông báo em đã trúng tuyển đại học chính quy năm 2026. Em vui lòng tra cứu thông tin chi tiết và hoàn thiện các thủ tục để chính thức trở thành tân sinh viên của trường.
        </p>

        <!-- PHẦN 1: THÔNG TIN CHI TIẾT -->
        <div style="margin-bottom: 24px;">
            <!-- Thông tin cá nhân -->
            <div style="background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; padding:16px; margin-bottom: 16px; display: block;">
                <h3 style="font-size:13px; font-weight:bold; color:#1e3a8a; margin:0 0 12px 0; border-bottom:2px solid #bfdbfe; padding-bottom:6px; text-transform:uppercase; font-family:Arial,Helvetica,sans-serif;"><i class="fas fa-user-circle" style="margin-right:5px;"></i> Thông tin cá nhân</h3>
                <table width="100%" cellpadding="5" cellspacing="0" style="font-size:13px; color:#374151; text-align:left; font-family:Arial,Helvetica,sans-serif;">
                    <tr><td width="135" style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Họ và tên:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0; text-transform:uppercase;">{{HoTen}}</td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Ngày sinh:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{NgaySinh}}</td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Số CCCD:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{CCCD}}</td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Mã HS / SBD:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{SBD}}</td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Giới tính:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{GioiTinh}}</td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Điện thoại:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{SDT}}</td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Email:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{Email}}</td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Khu vực UT:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{KhuVuc}}</td></tr>
                    <tr><td style="color:#6b7280; white-space:nowrap; padding-right:10px;">Đối tượng UT:</td><td style="font-weight:bold; color:#111827;">{{DoiTuong}}</td></tr>
                </table>
            </div>
            <!-- Thông tin xét tuyển -->
            <div style="background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; padding:16px; margin-bottom: 16px; display: block;">
                <h3 style="font-size:13px; font-weight:bold; color:#b91c1c; margin:0 0 12px 0; border-bottom:2px solid #fecaca; padding-bottom:6px; text-transform:uppercase; font-family:Arial,Helvetica,sans-serif;"><i class="fas fa-graduation-cap" style="margin-right:5px;"></i> Thông tin xét tuyển</h3>
                <table width="100%" cellpadding="5" cellspacing="0" style="font-size:13px; color:#374151; text-align:left; font-family:Arial,Helvetica,sans-serif;">
                    <tr><td width="135" style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Ngành trúng tuyển:</td><td style="font-weight:bold; color:#b91c1c; border-bottom:1px dashed #e2e8f0; text-transform:uppercase;">{{Nganh}}</td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Mã ngành:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{MaNganh}}</td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Phương thức:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{PhuongThuc}}</td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Tổ hợp xét:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{ToHop}}</td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Điểm các môn (QĐ):</td><td style="color:#111827; border-bottom:1px dashed #e2e8f0;">ĐM1: <b>{{DM1}}</b> | ĐM2: <b>{{DM2}}</b> | ĐM3: <b>{{DM3}}</b></td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Điểm ƯT / Quy đổi:</td><td style="color:#111827; border-bottom:1px dashed #e2e8f0;"><b>{{DiemUT}}</b> / <b>{{UTQ}}</b></td></tr>
                    <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0; white-space:nowrap; padding-right:10px;">Điểm tổ hợp:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{DiemToHop}}</td></tr>
                    <tr><td style="font-weight:bold; color:#b91c1c; white-space:nowrap; padding-right:10px;">ĐIỂM XÉT TUYỂN:</td><td style="font-weight:bold; color:#b91c1c;">{{DiemXT}}</td></tr>
                </table>
            </div>

            <!-- Nút Tra cứu thông tin chi tiết -->
            <div style="margin-top: 20px; text-align: center;">
                <a href="https://tuyensinh.hvu.edu.vn/tra-cuu-trung-tuyen" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #1e40af, #1d4ed8); color: #ffffff; text-decoration: none; padding: 12px 32px; border-radius: 8px; font-weight: bold; font-size: 14px; font-family: Arial, Helvetica, sans-serif; box-shadow: 0 4px 6px rgba(30, 64, 175, 0.2);">
                    <i class="fas fa-search" style="margin-right: 8px;"></i> Tra cứu thông tin chi tiết
                </a>
            </div>

        </div>

        <!-- PHẦN 2: MÃ QR NHẬP HỌC & HƯỚNG DẪN KHUYẾN KHÍCH -->
        <div style="margin-top: 24px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:20px; text-align:center;">
            <h3 style="font-size:14px; font-weight:bold; color:#166534; margin:0 0 12px 0; text-transform:uppercase; font-family:Arial,Helvetica,sans-serif;">
                <i class="fas fa-qrcode" style="margin-right:6px;"></i> Mã QR Nhập học của bạn
            </h3>
            <p style="font-size:13px; color:#374151; line-height:1.6; margin:0 0 16px 0; font-family:Arial,Helvetica,sans-serif;">
                Vui lòng lưu lại mã QR dưới đây và sử dụng để quét mã làm thủ tục khi đến trường nhập học trực tiếp.
            </p>
            <div style="background:#ffffff; display:inline-block; padding:12px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:16px;">
                {{QR_CCCD}}
            </div>
            
            <div style="margin-top:8px; font-size:13px; color:#15803d; font-weight:bold; line-height:1.6; font-family:Arial,Helvetica,sans-serif; text-align:left; background:#ffffff; border-left:4px solid #166534; padding:10px 14px; border-radius:0 8px 8px 0;">
                <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i> 
                Trường Đại học Hùng Vương khuyến khích Tân sinh viên K24 thanh toán kinh phí nhập học theo mã QR trên Giấy báo nhập học trước ngày 15/8/2026 để thuận tiện trong quá trình nhập học!
            </div>
        </div>

        <!-- Footer -->
        <div style="margin-top:20px; padding-top:16px; border-top:1px solid #e5e7eb; text-align:center; font-size:12px; color:#6b7280; font-family:Arial,Helvetica,sans-serif; line-height:1.6;">
            <p style="font-weight:bold; color:#374151; margin:0 0 6px 0; text-transform:uppercase;">HỘI ĐỒNG TUYỂN SINH TRƯỜNG ĐẠI HỌC HÙNG VƯƠNG</p>
            <p style="margin:0 0 6px 0;"><i class="fas fa-phone-alt"></i> Hotline: 0866 993 468 &nbsp;|&nbsp; <i class="fas fa-envelope"></i> Email: tuyensinh@hvu.edu.vn</p>
            <p style="margin:0;"><a href="http://www.hvu.edu.vn" target="_blank" style="color:#2563eb; text-decoration:none; font-weight:bold;">www.hvu.edu.vn</a></p>
        </div>
    </div>
</div>
<!-- KẾT THÚC MẪU THÔNG BÁO -->';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    
    // Check if the template code EMAIL_ADMISSION_LETTER_9 already exists
    $stmtCheck = $db->prepare("SELECT id FROM email_templates WHERE code = ?");
    $stmtCheck->execute(['EMAIL_ADMISSION_LETTER_9']);
    $id = $stmtCheck->fetchColumn();
    
    if ($id) {
        $stmtUpd = $db->prepare("UPDATE email_templates SET subject = ?, body = ? WHERE id = ?");
        $stmtUpd->execute([$subject, $body, $id]);
        echo "Updated existing template EMAIL_ADMISSION_LETTER_9 (ID: $id) successfully.\n";
    } else {
        $stmtIns = $db->prepare("INSERT INTO email_templates (code, subject, body) VALUES (?, ?, ?)");
        $stmtIns->execute(['EMAIL_ADMISSION_LETTER_9', $subject, $body]);
        $newId = $db->lastInsertId();
        echo "Inserted new template EMAIL_ADMISSION_LETTER_9 (ID: $newId) successfully.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
