<?php
require_once __DIR__ . '/bootstrap_test.php';

use App\Core\Database;

echo "=== MIGRATION PHASE 7: ADMISSION EMAIL TEMPLATE ===\n";

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Ensure Table Exists
    echo "Checking/Creating table 'email_templates'...\n";
    $sql = "CREATE TABLE IF NOT EXISTS email_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        variables VARCHAR(255),
        type VARCHAR(20) DEFAULT 'system',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"; // Removed ENGINE/CHARSET for compatibility
    $db->exec($sql);

    // 2. Ensure Columns Exist (Defensive)
    echo "Checking columns...\n";
    try {
        $db->exec("ALTER TABLE email_templates ADD COLUMN type VARCHAR(20) DEFAULT 'system'");
        echo "   Added column 'type'.\n";
    } catch (PDOException $e) {
        // Column likely exists
    }
    
    try {
        $db->exec("ALTER TABLE email_templates ADD COLUMN variables VARCHAR(255)");
        echo "   Added column 'variables'.\n";
    } catch (PDOException $e) {
        // Column likely exists
    }

    // 3. Insert/Update Template
    $code = 'admission_success';
    $subject = 'Thông báo Trúng tuyển - Trường Đại học Hùng Vương';
    $body = '
<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="text-align: center; margin-bottom: 20px;">
        <h2 style="color: #e11d48;">ĐẠI HỌC HÙNG VƯƠNG</h2>
        <p style="font-weight: bold; font-size: 18px;">GIẤY BÁO TRÚNG TUYỂN</p>
    </div>
    
    <p>Chào bạn <strong>{{ho_ten}}</strong> (CCCD: {{cccd}}),</p>
    
    <p>Hội đồng Tuyển sinh Trường Đại học Hùng Vương trân trọng chúc mừng bạn đã chính thức trúng tuyển đợt xét tuyển sớm năm 2026.</p>
    
    <div style="background: #f0fdf4; padding: 20px; border: 1px solid #bbf7d0; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 8px 0;">🎯 <strong>Ngành trúng tuyển:</strong> <span style="color: #166534; font-size: 16px;">{{ten_nganh}}</span></p>
        <p style="margin: 8px 0;">🔢 <strong>Mã ngành:</strong> {{ma_nganh}}</p>
        <p style="margin: 8px 0;">⭐ <strong>Điểm xét tuyển:</strong> {{diem_xet_tuyen}}</p>
    </div>
    
    <p>Đây là kết quả xứng đáng cho những nỗ lực của bạn. Nhà trường rất hân hạnh được chào đón bạn trở thành tân sinh viên.</p>
    
    <p>Vui lòng đăng nhập vào hệ thống để xác nhận nhập học và xem hướng dẫn chi tiết về thủ tục, hồ sơ cần chuẩn bị.</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{login_url}}" style="display: inline-block; padding: 12px 24px; background: #e11d48; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">Xác nhận Nhập học</a>
    </div>
    
    <p style="font-size: 13px; color: #666; border-top: 1px solid #eee; padding-top: 10px;">
        Trường Đại học Hùng Vương - Đường Nguyễn Tất Thành, TP. Việt Trì, Phú Thọ.<br>
        Hotline: 0210.3821.970 - Website: caodang.hvu.edu.vn
    </p>
</div>
';

    // Check if exists
    $stmt = $db->prepare("SELECT id FROM email_templates WHERE code = ?");
    $stmt->execute([$code]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        echo "Updating existing template '$code'...\n";
        $upd = $db->prepare("UPDATE email_templates SET subject = ?, body = ?, updated_at = NOW() WHERE code = ?");
        $upd->execute([$subject, $body, $code]);
    } else {
        echo "Creating new template '$code'...\n";
        $ins = $db->prepare("INSERT INTO email_templates (code, subject, body, variables, type) VALUES (?, ?, ?, ?, 'system')");
        $ins->execute([$code, $subject, $body, 'ho_ten, cccd, ten_nganh, ma_nganh, diem_xet_tuyen, login_url']);
    }

    echo "Migration Complete.\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
