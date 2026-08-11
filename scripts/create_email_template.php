<?php
require_once 'd:/xampp/htdocs/TS/app/Core/DotEnv.php';
$dotenv = new App\Core\DotEnv('d:/xampp/htdocs/TS/.env');
$dotenv->load();

require_once 'd:/xampp/htdocs/TS/app/Core/Database.php';

$subject = 'Thông báo trúng tuyển năm 2026 - Đợt 1 (Mẫu gửi Email)';
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
            Chào em <strong>{{HoTen}}</strong>, Hội đồng tuyển sinh Trường Đại học Hùng Vương trân trọng thông báo em đã trúng tuyển đại học chính quy năm 2026. Em vui lòng thực hiện đầy đủ các bước trong tiến trình nhập học dưới đây để chính thức trở thành tân sinh viên của trường.
        </p>

        <!-- TIẾN TRÌNH 6 BƯỚC -->
        {{THANH_TIEN_DO_6_BUOC}}

        <!-- PHẦN 1: THÔNG TIN CHI TIẾT -->
        <div style="margin-bottom: 24px;">
            <div style="display:flex; flex-direction:column; gap:16px;">
                <!-- Thông tin cá nhân -->
                <div style="background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; padding:16px;">
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
                <div style="background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; padding:16px; margin-top: 16px;">
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
            </div>
            {{NutXemGiayBao}}
            <div style="margin-top:12px; padding:10px 14px; background:#eff6ff; border-left:3px solid #3b82f6; border-radius:0 6px 6px 0; font-size:12px; color:#1e40af; font-family:Arial,Helvetica,sans-serif;">
                <i class="fas fa-info-circle" style="margin-right:4px;"></i> <em>Lưu ý: Thông tin xét tuyển được Hội đồng tuyển sinh nhà trường lấy từ hệ thống của Bộ GD&ĐT</em>
            </div>
        </div>

        <!-- PHẦN 2: XÁC NHẬN NHẬP HỌC -->
        <div style="margin-bottom: 24px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px;">
            <h3 style="font-size:14px; font-weight:bold; color:#111827; margin:0 0 16px 0; border-bottom:2px solid #e5e7eb; padding-bottom:6px; text-transform:uppercase; font-family:Arial,Helvetica,sans-serif;">
                <i class="fas fa-check-double" style="margin-right:5px; color:#dc2626;"></i> Xác nhận nhập học
            </h3>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <!-- Card Trường -->
                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:16px; text-align:center;">
                    <h4 style="font-weight:bold; color:#111827; margin:0 0 8px 0; font-size:13px;">1. HỆ THỐNG TRƯỜNG ĐẠI HỌC HÙNG VƯƠNG</h4>
                    <p style="font-size:12px; color:#6b7280; margin:0 0 12px 0;">Xác nhận nhập học trực tuyến trên Cổng thông tin Tuyển sinh của Trường.</p>
                    {{TrangThaiXacNhan}}
                </div>
                <!-- Card Bộ -->
                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:16px; text-align:center; margin-top: 12px;">
                    <h4 style="font-weight:bold; color:#111827; margin:0 0 8px 0; font-size:13px;">2. HỆ THỐNG BỘ GD&ĐT</h4>
                    <p style="font-size:12px; color:#6b7280; margin:0 0 12px 0;">Thí sinh bắt buộc phải xác nhận nhập học trên Cổng thông tin của Bộ Giáo dục và Đào tạo.</p>
                    {{TrangThaiXacNhanBo}}
                </div>
            </div>
        </div>

        <!-- PHẦN 3: KINH PHÍ NHẬP HỌC -->
        <div style="margin-bottom: 24px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px;">
            <h3 style="font-size:14px; font-weight:bold; color:#1e40af; margin:0 0 16px 0; border-bottom:2px solid #bfdbfe; padding-bottom:6px; text-transform:uppercase; font-family:Arial,Helvetica,sans-serif;">
                <i class="fas fa-money-check-alt" style="margin-right:5px; color:#3b82f6;"></i> Kinh phí nhập học
            </h3>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <!-- Thông tin CK -->
                <div style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; padding:16px;">
                    <table width="100%" cellpadding="6" cellspacing="0" style="font-size:13px; color:#374151; font-family:Arial,Helvetica,sans-serif; text-align:left;">
                        <tr><td width="38%" style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Ngân hàng:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{NGANHANG}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Số tài khoản:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0; font-family:monospace; font-size:15px;">{{SOTK}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Chủ tài khoản:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">TRUONG DAI HOC HUNG VUONG</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Số tiền cần nộp:</td><td style="font-weight:bold; color:#b91c1c; font-size:18px; border-bottom:1px dashed #e2e8f0;">{{SoTien}} VNĐ</td></tr>
                        <tr><td style="color:#6b7280;">Nội dung CK:</td><td style="font-weight:bold; color:#1e40af; font-family:monospace; font-size:14px; letter-spacing:.5px;">{{NOIDUNGCK}}</td></tr>
                    </table>
                    {{KhoiKinhPhi}}
                    {{XacNhanKinhPhi}}
                </div>
                <!-- QR Code -->
                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:16px; text-align:center; margin-top: 12px;">
                    <p style="font-size:12px; font-weight:bold; color:#374151; margin:0 0 10px 0; text-transform:uppercase;"><i class="fas fa-qrcode" style="margin-right:4px;"></i> Quét mã QR dưới đây để thực hiện chuyển tiền nhanh</p>
                    {{QR_ThanhToan}}
                </div>
            </div>
        </div>

        <!-- PHẦN 4: HƯỚNG DẪN NHẬP HỌC TRỰC TIẾP -->
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; margin-bottom:16px;">
            <h3 style="font-size:14px; font-weight:bold; color:#166534; margin:0 0 16px 0; border-bottom:2px solid #86efac; padding-bottom:6px; text-transform:uppercase; font-family:Arial,Helvetica,sans-serif;">
                <i class="fas fa-graduation-cap" style="margin-right:5px; color:#10b981;"></i> Thủ tục nhập học trực tiếp
            </h3>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:16px;">
                    <h4 style="font-weight:bold; color:#166534; margin:0 0 8px 0; font-size:13px;">1. Thời gian &amp; Địa điểm nhập học</h4>
                    <p style="margin:0 0 8px 0; font-size:13px; color:#374151;"><strong>Thời gian:</strong> {{ThoiGianNhap}}</p>
                    <p style="margin:0; font-size:13px; color:#374151;"><strong>Địa điểm:</strong> Nhà Hành chính hiệu bộ, Trường Đại học Hùng Vương, đường Nguyễn Tất Thành, phường Nông Trang, tỉnh Phú Thọ.</p>
                </div>
                
                <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:16px; margin-top: 12px;">
                    <h4 style="font-weight:bold; color:#9a3412; margin:0 0 8px 0; font-size:13px;">2. Các giấy tờ cần hoàn thiện (Khi đi nhập học hoặc nộp sau)</h4>
                    <ul style="margin:0; padding-left:0; list-style:none; font-size:13px; color:#374151; font-family:Arial,Helvetica,sans-serif; line-height:1.6;">
                        <li style="margin-bottom:6px;">• Giấy chứng nhận kết quả thi tốt nghiệp THPT năm 2026 (bản gốc);</li>
                        <li style="margin-bottom:6px;">• Học bạ Trung học phổ thông (bản gốc và bản sao chứng thực);</li>
                        <li style="margin-bottom:6px;">• Giấy báo trúng tuyển;</li>
                        <li style="margin-bottom:6px;">• Bằng tốt nghiệp THPT (bản sao chứng thực);</li>
                        <li style="margin-bottom:6px;">• Giấy khai sinh (bản sao chứng thực);</li>
                        <li style="margin-bottom:6px;">• Căn cước công dân (02 bản sao chứng thực);</li>
                        <li style="margin-bottom:6px;">• Lý lịch học sinh sinh viên (theo mẫu của trường);</li>
                        <li style="margin-bottom:6px;">• Ảnh 3x4 (04 chiếc);</li>
                        <li style="margin-bottom:0;">• Giấy di chuyển nghĩa vụ quân sự (đối với nam).</li>
                    </ul>
                </div>

                <div style="background: #ffffff; border-radius: 8px; padding: 16px; border: 1px solid #e5e7eb; text-align: center; margin-top: 12px;">
                    <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: bold; color: #15803d; text-transform: uppercase; font-family: Arial, Helvetica, sans-serif;"><i class="fas fa-qrcode" style="margin-right: 4px;"></i> Vui lòng lưu mã QR hồ sơ cá nhân để quét khi đến trường nhập học trực tiếp</p>
                    {{QR_CCCD}}
                </div>
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
