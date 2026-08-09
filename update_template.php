<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = null;
try {
    $db = new PDO('pgsql:host=127.0.0.1;port=5433;dbname=tuyensinh_thv', 'tuyensinh_app', 'Phutho2024@!');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo "Warning: Local tuyensinh_thv connection failed, will skip local update: " . $e->getMessage() . "\n";
}

$templateHtml = <<<'HTML'
<!-- BẮT ĐẦU MẪU THÔNG BÁO TRÚNG TUYỂN -->
<style>
.hvu-tab-link { width: 25%; text-align:center; padding:10px 4px; font-size:12px; font-weight:bold; text-transform:uppercase; text-decoration:none; color:#4b5563; border-bottom:3px solid transparent; font-family:Arial,Helvetica,sans-serif; cursor:pointer; transition: all .2s; background: transparent; box-sizing: border-box; }
.hvu-tab-link:hover { color:#b91c1c; background:#fef2f2; }
.hvu-tab-link.active { color:#b91c1c; border-bottom-color:#b91c1c; background:#fff; }
.hvu-tab-content { display:none; margin-bottom:10px; }
.hvu-tab-content.active { display:block; }
</style>
<div style="width:100%; margin:0 auto; font-family:Arial,Helvetica,sans-serif; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.05); border:1px solid #e2e8f0;">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#b91c1c,#7f1d1d); padding:10px 15px 8px 15px; text-align:center; color:#fff;">
        <p style="margin:0 0 2px 0; font-size:14px; font-weight:bold; opacity:.9; text-transform:uppercase; letter-spacing:1px;">Chúc mừng TÂN SINH VIÊN Trường Đại học Hùng Vương</p>
        <h1 style="color:#fff; font-size:24px; font-weight:bold; margin:0; text-transform:uppercase; letter-spacing:1px; font-family:Arial,Helvetica,sans-serif;">{{HoTen}}</h1>
    </div>

    <div style="padding:12px;">

        <!-- Tab Navigation -->
        <div style="display:flex; flex-wrap:wrap; border-bottom:2px solid #e5e7eb; margin-bottom:12px; background:#f9fafb; border-radius:8px 8px 0 0;">
            <a class="hvu-tab-link active" onclick="hvuSwitchTab(1)" id="hvu-tl-1"><i class="fas fa-user-graduate" style="display:block; font-size:14px; margin-bottom:2px;"></i> Thông tin</a>
            <a class="hvu-tab-link" onclick="hvuSwitchTab(2)" id="hvu-tl-2"><i class="fas fa-check-double" style="display:block; font-size:14px; margin-bottom:2px;"></i> Xác nhận</a>
            <a class="hvu-tab-link" onclick="hvuSwitchTab(3)" id="hvu-tl-3"><i class="fas fa-money-check-alt" style="display:block; font-size:14px; margin-bottom:2px;"></i> Kinh phí</a>
            <a class="hvu-tab-link" onclick="hvuSwitchTab(4)" id="hvu-tl-4"><i class="fas fa-folder-open" style="display:block; font-size:14px; margin-bottom:2px;"></i> Nhập học</a>
        </div>

        <!-- TAB 1: THÔNG TIN THÍ SINH -->
        <div id="hvu-tab-1" class="hvu-tab-content active">
            <div style="display:flex; flex-wrap:wrap; gap:16px;">
                <!-- Cột trái: Thông tin cá nhân -->
                <div style="flex:1; min-width:280px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; padding:16px;">
                    <h3 style="font-size:13px; font-weight:bold; color:#1e3a8a; margin:0 0 12px 0; border-bottom:2px solid #bfdbfe; padding-bottom:6px; text-transform:uppercase; font-family:Arial,Helvetica,sans-serif;"><i class="fas fa-user-circle" style="margin-right:5px;"></i> Thông tin cá nhân</h3>
                    <table width="100%" cellpadding="5" cellspacing="0" style="font-size:13px; color:#374151; text-align:left; font-family:Arial,Helvetica,sans-serif;">
                        <tr><td width="180" style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Họ và tên:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0; text-transform:uppercase;">{{HoTen}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Ngày sinh:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{NgaySinh}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Số CCCD:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{CCCD}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">SBD / Mã hồ sơ:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{SBD}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Giới tính:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{GioiTinh}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Điện thoại:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{SDT}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Email:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{Email}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Khu vực ưu tiên:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{KhuVuc}}</td></tr>
                        <tr><td style="color:#6b7280;">Đối tượng ưu tiên:</td><td style="font-weight:bold; color:#111827;">{{DoiTuong}}</td></tr>
                    </table>
                </div>
                <!-- Cột phải: Thông tin xét tuyển -->
                <div style="flex:1; min-width:280px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; padding:16px;">
                    <h3 style="font-size:13px; font-weight:bold; color:#b91c1c; margin:0 0 12px 0; border-bottom:2px solid #fecaca; padding-bottom:6px; text-transform:uppercase; font-family:Arial,Helvetica,sans-serif;"><i class="fas fa-graduation-cap" style="margin-right:5px;"></i> Thông tin xét tuyển</h3>
                    <table width="100%" cellpadding="5" cellspacing="0" style="font-size:13px; color:#374151; text-align:left; font-family:Arial,Helvetica,sans-serif;">
                        <tr><td width="180" style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Ngành trúng tuyển:</td><td style="font-weight:bold; color:#b91c1c; border-bottom:1px dashed #e2e8f0; text-transform:uppercase;">{{Nganh}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Mã ngành:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{MaNganh}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Phương thức:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{PhuongThuc}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Tổ hợp xét:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{ToHop}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Điểm các môn (quy đổi):</td><td style="color:#111827; border-bottom:1px dashed #e2e8f0;">ĐM1: <b>{{DM1}}</b> | ĐM2: <b>{{DM2}}</b> | ĐM3: <b>{{DM3}}</b></td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Điểm ƯT / Quy đổi:</td><td style="color:#111827; border-bottom:1px dashed #e2e8f0;"><b>{{DiemUT}}</b> / <b>{{UTQ}}</b></td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Điểm tổ hợp:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{DiemToHop}}</td></tr>
                        <tr><td style="font-weight:bold; color:#b91c1c;">ĐIỂM XÉT TUYỂN:</td><td style="font-weight:bold; color:#b91c1c;">{{DiemXT}}</td></tr>
                    </table>
                </div>
            </div>
            {{NutXemGiayBao}}
            <div style="margin-top:12px; padding:10px 14px; background:#eff6ff; border-left:3px solid #3b82f6; border-radius:0 6px 6px 0; font-size:12px; color:#1e40af; font-family:Arial,Helvetica,sans-serif;">
                <i class="fas fa-info-circle" style="margin-right:4px;"></i> <em>Lưu ý: Thông tin xét tuyển được Hội đồng tuyển sinh nhà trường lấy từ hệ thống của Bộ GD&ĐT</em>
            </div>
        </div>

        <!-- TAB 2: XÁC NHẬN NHẬP HỌC -->
        <div id="hvu-tab-2" class="hvu-tab-content">
            <div style="display:flex; flex-wrap:wrap; gap:16px;">
                <!-- Card Trường -->
                <div style="flex:1; min-width:280px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; text-align:center;">
                    <div style="width:50px; height:50px; margin:0 auto 12px; background:#fee2e2; color:#dc2626; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px;"><i class="fas fa-university"></i></div>
                    <h3 style="font-weight:bold; color:#111827; margin-bottom:8px; font-size:14px; font-family:Arial,Helvetica,sans-serif;">HỆ THỐNG NHÀ TRƯỜNG</h3>
                    <p style="font-size:12px; color:#6b7280; margin-bottom:16px;">Xác nhận nhập học trực tuyến trên Cổng thông tin Tuyển sinh.</p>
                    {{TrangThaiXacNhan}}
                </div>
                <!-- Card Bộ -->
                <div style="flex:1; min-width:280px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; text-align:center;">
                    <div style="width:50px; height:50px; margin:0 auto 12px; background:#dbeafe; color:#2563eb; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px;"><i class="fas fa-globe"></i></div>
                    <h3 style="font-weight:bold; color:#111827; margin-bottom:8px; font-size:14px; font-family:Arial,Helvetica,sans-serif;">HỆ THỐNG BỘ GD&ĐT</h3>
                    <p style="font-size:12px; color:#6b7280; margin-bottom:16px;">Thí sinh bắt buộc phải xác nhận nhập học trên <a href="https://thisinh.thitotnghiepthpt.edu.vn/" target="_blank" style="color:#2563eb; font-weight:bold; text-decoration:underline;">Cổng thông tin của Bộ Giáo dục</a>.</p>
                    {{TrangThaiXacNhanBo}}
                </div>
            </div>
        </div>

        <!-- TAB 3: KINH PHÍ -->
        <div id="hvu-tab-3" class="hvu-tab-content">
            <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:stretch;">
                <!-- QR Code -->
                <div style="flex:1; min-width:250px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:20px; text-align:center; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                    <h3 style="font-size:13px; font-weight:bold; color:#374151; margin:0 0 12px 0; text-transform:uppercase; font-family:Arial,Helvetica,sans-serif;"><i class="fas fa-qrcode" style="margin-right:4px;"></i> Mã QR nộp tiền</h3>
                    {{QR_ThanhToan}}
                </div>
                <!-- Thông tin CK -->
                <div style="flex:1.3; min-width:280px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px;">
                    <h3 style="font-size:13px; font-weight:bold; color:#1e40af; margin:0 0 14px 0; border-bottom:2px solid #bfdbfe; padding-bottom:6px; text-transform:uppercase; font-family:Arial,Helvetica,sans-serif;"><i class="fas fa-info-circle" style="margin-right:4px;"></i> Thông tin chuyển khoản</h3>
                    <table width="100%" cellpadding="6" cellspacing="0" style="font-size:13px; color:#374151; font-family:Arial,Helvetica,sans-serif;">
                        <tr><td width="38%" style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Ngân hàng:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">{{NGANHANG}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Số tài khoản:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0; font-family:monospace; font-size:15px;">{{SOTK}}</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Chủ tài khoản:</td><td style="font-weight:bold; color:#111827; border-bottom:1px dashed #e2e8f0;">TRUONG DAI HOC HUNG VUONG</td></tr>
                        <tr><td style="color:#6b7280; border-bottom:1px dashed #e2e8f0;">Số tiền cần nộp:</td><td style="font-weight:bold; color:#b91c1c; font-size:18px; border-bottom:1px dashed #e2e8f0;">{{SoTien}} VNĐ</td></tr>
                        <tr><td style="color:#6b7280;">Nội dung CK:</td><td style="font-weight:bold; color:#1e40af; font-family:monospace; font-size:14px; letter-spacing:.5px;">{{NOIDUNGCK}}</td></tr>
                    </table>
                    {{KhoiKinhPhi}}
                    {{XacNhanKinhPhi}}
                </div>
            </div>
        </div>

        <!-- TAB 4: NHẬP HỌC -->
        <div id="hvu-tab-4" class="hvu-tab-content">
            <div style="display:flex; flex-wrap:wrap; gap:16px;">
                <!-- Nộp hồ sơ -->
                <div style="flex:1; min-width:280px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:16px;">
                    <h3 style="font-size:14px; font-weight:bold; color:#166534; margin:0 0 14px 0; border-bottom:2px solid #86efac; padding-bottom:6px; font-family:Arial,Helvetica,sans-serif;"><i class="fas fa-file-alt" style="margin-right:4px;"></i> 1. NỘP HỒ SƠ TRỰC TIẾP</h3>
                    <div style="font-size:13px; color:#374151; line-height:1.8; font-family:Arial,Helvetica,sans-serif;">
                        <p style="margin:0 0 8px 0;"><strong>Thời gian:</strong> {{ThoiGianNhap}}</p>
                        <p style="margin:0 0 8px 0;"><strong>Địa điểm:</strong> Nhà Hành chính hiệu bộ, Trường Đại học Hùng Vương, đường Nguyễn Tất Thành, phường Nông Trang, tỉnh Phú Thọ.</p>
                        <p style="margin:0 0 6px 0;"><strong>Khi đi mang theo Căn cước và các giấy tờ sau:</strong></p>
                        <ul style="margin:0; padding-left:18px; font-size:13px;">
                            <li style="margin-bottom:4px;">Giấy chứng nhận kết quả thi TN THPT năm 2026 <strong>bản gốc</strong></li>
                            <li style="margin-bottom:4px;">Nộp học bạ THPT (gốc và sao chứng thực)</li>
                        </ul>
                        <div style="margin-top: 16px; text-align: center; background: #ffffff; border-radius: 8px; padding: 12px; border: 1px solid #dcfce7; display: inline-block; width: 100%; box-sizing: border-box;">
                            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: bold; color: #15803d; text-transform: uppercase; font-family: Arial, Helvetica, sans-serif;"><i class="fas fa-qrcode" style="margin-right: 4px;"></i> QR Nhập học nhanh (CCCD)</p>
                            {{QR_CCCD}}
                        </div>
                    </div>
                </div>
                <!-- Giấy tờ cần hoàn thiện -->
                <div style="flex:1; min-width:280px; background:#fff7ed; border:1px solid #fed7aa; border-radius:10px; padding:16px;">
                    <h3 style="font-size:14px; font-weight:bold; color:#9a3412; margin:0 0 14px 0; border-bottom:2px solid #fdba74; padding-bottom:6px; font-family:Arial,Helvetica,sans-serif;"><i class="fas fa-clipboard-list" style="margin-right:4px;"></i> 3. GIẤY TỜ CẦN HOÀN THIỆN</h3>
                    <p style="margin:0 0 8px 0; font-size:12px; color:#9a3412; font-style:italic;">(Nộp sau ngày nhập học)</p>
                    <ul style="margin:0; padding-left:0; list-style:none; font-size:13px; color:#374151; font-family:Arial,Helvetica,sans-serif;">
                        <li style="margin-bottom:6px; display:flex; align-items:flex-start;"><i class="fas fa-check-square" style="color:#ea580c; margin-right:8px; margin-top:3px; font-size:12px;"></i> <span>Giấy báo trúng tuyển</span></li>
                        <li style="margin-bottom:6px; display:flex; align-items:flex-start;"><i class="fas fa-check-square" style="color:#ea580c; margin-right:8px; margin-top:3px; font-size:12px;"></i> <span>Chứng chỉ ngoại ngữ quốc tế <em>(nếu có)</em></span></li>
                        <li style="margin-bottom:6px; display:flex; align-items:flex-start;"><i class="fas fa-check-square" style="color:#ea580c; margin-right:8px; margin-top:3px; font-size:12px;"></i> <span>Giấy CN tốt nghiệp THPT bản gốc</span></li>
                        <li style="margin-bottom:6px; display:flex; align-items:flex-start;"><i class="fas fa-check-square" style="color:#ea580c; margin-right:8px; margin-top:3px; font-size:12px;"></i> <span>Bằng TN THPT bản sao chứng thực</span></li>
                        <li style="margin-bottom:6px; display:flex; align-items:flex-start;"><i class="fas fa-check-square" style="color:#ea580c; margin-right:8px; margin-top:3px; font-size:12px;"></i> <span>Giấy khai sinh bản sao chứng thực</span></li>
                        <li style="margin-bottom:6px; display:flex; align-items:flex-start;"><i class="fas fa-check-square" style="color:#ea580c; margin-right:8px; margin-top:3px; font-size:12px;"></i> <span>Lý lịch sinh viên <em>(theo mẫu)</em></span></li>
                        <li style="margin-bottom:6px; display:flex; align-items:flex-start;"><i class="fas fa-check-square" style="color:#ea580c; margin-right:8px; margin-top:3px; font-size:12px;"></i> <span>Căn cước công dân — 02 bản</span></li>
                        <li style="margin-bottom:6px; display:flex; align-items:flex-start;"><i class="fas fa-check-square" style="color:#ea580c; margin-right:8px; margin-top:3px; font-size:12px;"></i> <span>Ảnh 3x4 — 4 cái</span></li>
                        <li style="margin-bottom:6px; display:flex; align-items:flex-start;"><i class="fas fa-check-square" style="color:#ea580c; margin-right:8px; margin-top:3px; font-size:12px;"></i> <span>Giấy tờ CN đối tượng ưu tiên <em>(nếu có)</em></span></li>
                        <li style="margin-bottom:0; display:flex; align-items:flex-start;"><i class="fas fa-check-square" style="color:#ea580c; margin-right:8px; margin-top:3px; font-size:12px;"></i> <span>Giấy di chuyển NVQS <em>(với nam)</em></span></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="margin-top:12px; padding-top:8px; border-top:1px solid #e5e7eb; text-align:center; font-size:12px; color:#6b7280; font-family:Arial,Helvetica,sans-serif;">
            <p style="font-weight:bold; color:#374151; margin:0 0 4px 0; text-transform:uppercase;">HỘI ĐỒNG TUYỂN SINH TRƯỜNG ĐẠI HỌC HÙNG VƯƠNG</p>
            <p style="margin:0 0 4px 0;"><i class="fas fa-phone-alt"></i> Hotline: 0866 993 468 &nbsp;|&nbsp; <i class="fas fa-envelope"></i> Email: tuyensinh@hvu.edu.vn</p>
        </div>
    </div>
</div>
<script>
function hvuSwitchTab(n) {
    for (var i = 1; i <= 4; i++) {
        var tab = document.getElementById('hvu-tab-' + i);
        var link = document.getElementById('hvu-tl-' + i);
        if (tab) tab.className = 'hvu-tab-content' + (i === n ? ' active' : '');
        if (link) link.className = 'hvu-tab-link' + (i === n ? ' active' : '');
    }
}
</script>
<!-- KẾT THÚC MẪU THÔNG BÁO -->
HTML;

if ($db) {
    $stmt = $db->prepare("UPDATE email_templates SET body = :body, subject = :subject WHERE code = 'ADMISSION_LETTER'");
    $stmt->execute(['body' => $templateHtml, 'subject' => 'Thông báo trúng tuyển năm 2026 - Đợt 1']);
    echo "Updated email template ADMISSION_LETTER successfully on tuyensinh_thv.\n";
}

// Update Supabase
try {
    $dbSupa = new PDO('pgsql:host='.$_ENV['DB_HOST'].';port='.$_ENV['DB_PORT'].';dbname='.$_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $dbSupa->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmtSupa = $dbSupa->prepare("UPDATE email_templates SET body = :body, subject = :subject WHERE code = 'ADMISSION_LETTER'");
    $stmtSupa->execute(['body' => $templateHtml, 'subject' => 'Thông báo trúng tuyển năm 2026 - Đợt 1']);
    echo "Updated email template ADMISSION_LETTER successfully on Supabase.\n";
} catch (Exception $e) {
    echo "Error updating Supabase: " . $e->getMessage() . "\n";
}
