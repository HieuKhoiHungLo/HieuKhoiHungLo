-- Migration: Create email_templates table
-- Run this SQL in your PostgreSQL database

CREATE TABLE IF NOT EXISTS email_templates (
    id SERIAL PRIMARY KEY,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    variables TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Initial Templates
INSERT INTO email_templates (slug, name, subject, body, variables) VALUES
('registration_success', 'Đăng ký thành công', 
 '[HVU] Đăng ký tài khoản thành công',
 '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #CE1B22, #8E161A); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Đại học Hùng Vương</h1>
        <p style="color: rgba(255,255,255,0.8); margin: 5px 0 0;">Hệ thống Tuyển sinh</p>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Xin chào {{ho_ten}},</h2>
        <p style="color: #555; line-height: 1.6;">Bạn đã đăng ký thành công tài khoản trên Hệ thống Tuyển sinh Đại học Hùng Vương.</p>
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">
            <p style="margin: 0 0 10px;"><strong>Tên đăng nhập:</strong> <code style="background: #f0f0f0; padding: 2px 8px; border-radius: 4px;">{{cccd}}</code></p>
            <p style="margin: 0;"><strong>Mật khẩu:</strong> <code style="background: #f0f0f0; padding: 2px 8px; border-radius: 4px;">{{mat_khau}}</code></p>
        </div>
        <p style="color: #555;">Vui lòng đăng nhập để hoàn tất hồ sơ xét tuyển.</p>
        <a href="{{login_url}}" style="display: inline-block; background: #CE1B22; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 10px;">Đăng nhập ngay</a>
    </div>
    <div style="text-align: center; padding: 15px; color: #999; font-size: 12px;">
        <p>© 2026 Đại học Hùng Vương. Email này được gửi tự động.</p>
    </div>
</div>',
 'ho_ten,cccd,mat_khau,login_url'),

('application_reviewed', 'Hồ sơ đã được duyệt',
 '[HVU] Kết quả xét duyệt hồ sơ tuyển sinh',
 '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #CE1B22, #8E161A); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Đại học Hùng Vương</h1>
        <p style="color: rgba(255,255,255,0.8); margin: 5px 0 0;">Thông báo Xét duyệt</p>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border: 1px solid #eee; border-top: none;">
        <h2 style="color: #333; margin-top: 0;">Xin chào {{ho_ten}},</h2>
        <p style="color: #555; line-height: 1.6;">Hồ sơ tuyển sinh của bạn đã được xét duyệt. Dưới đây là kết quả chi tiết:</p>
        {{ket_qua_chi_tiet}}
        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 15px; margin-top: 20px;">
            <p style="margin: 0; color: #856404;"><strong>Ghi chú từ Cán bộ:</strong> {{ghi_chu}}</p>
        </div>
        <p style="color: #555; margin-top: 20px;">Nếu có thắc mắc, vui lòng liên hệ Phòng Tuyển sinh.</p>
    </div>
    <div style="text-align: center; padding: 15px; color: #999; font-size: 12px;">
        <p>© 2026 Đại học Hùng Vương. Email này được gửi tự động.</p>
    </div>
</div>',
 'ho_ten,ket_qua_chi_tiet,ghi_chu')
ON CONFLICT (slug) DO NOTHING;
