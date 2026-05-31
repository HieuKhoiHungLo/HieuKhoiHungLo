<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

echo "=== MIGRATION V23: ADDING NEW EMAIL TEMPLATES ===\n";

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Connect using direct connection if configured, otherwise default DB config
$host = $_ENV['DB_RESTORE_HOST'] ?? $_ENV['DB_HOST'];
$port = $_ENV['DB_RESTORE_PORT'] ?? $_ENV['DB_PORT'];
$dbname = $_ENV['DB_DATABASE'];
$user = $_ENV['DB_RESTORE_USERNAME'] ?? $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_RESTORE_PASSWORD'] ?? $_ENV['DB_PASSWORD'];

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Connected to Database via port $port.\n";

    $templates = [
        [
            'code' => 'wrong_admission_zone',
            'subject' => '[HVU] Thông báo điều chỉnh Khu vực/Đối tượng ưu tiên xét tuyển',
            'variables' => 'ho_ten, cccd, khu_vuc_dung, doi_tuong_dung, ghi_chu, login_url',
            'type' => 'system',
            'body' => '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #334155; background-color: #ffffff; border: 1px solid #f1f5f9; border-radius: 16px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);">
    <div style="background: linear-gradient(135deg, #CE1B22, #a8151a); padding: 24px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Trường Đại học Hùng Vương</h1>
        <p style="color: rgba(255,255,255,0.85); margin: 6px 0 0; font-size: 14px; font-weight: bold;">Hệ thống Tuyển sinh Trực tuyến</p>
    </div>
    <div style="padding: 24px 30px; line-height: 1.6;">
        <h2 style="color: #1e293b; margin-top: 0; font-size: 18px; font-weight: bold; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">Thông báo điều chỉnh Khu vực/Đối tượng ưu tiên</h2>
        
        <p>Kính chào bạn <strong>{{ho_ten}}</strong> (CCCD: {{cccd}}),</p>
        
        <p>Bộ phận tiếp nhận hồ sơ tuyển sinh Trường Đại học Hùng Vương đã thực hiện kiểm tra và đối chiếu thông tin khai báo của bạn với minh chứng kèm theo (CCCD, học bạ, hộ khẩu hoặc giấy tờ chứng nhận diện ưu tiên).</p>
        
        <p>Hội đồng tuyển sinh xin thông báo thông tin ưu tiên của bạn đã được điều chỉnh lại để đảm bảo chính xác theo quy chế tuyển sinh hiện hành:</p>
        
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 20px 0;">
            <p style="margin: 0 0 10px; font-size: 14px;">📍 <strong>Khu vực ưu tiên chuẩn:</strong> <span style="color: #CE1B22; font-weight: bold;">{{khu_vuc_dung}}</span></p>
            <p style="margin: 0 0 10px; font-size: 14px;">🎖️ <strong>Đối tượng ưu tiên chuẩn:</strong> <span style="color: #CE1B22; font-weight: bold;">{{doi_tuong_dung}}</span></p>
            <p style="margin: 0; font-size: 13px; color: #64748b; border-top: 1px dashed #cbd5e1; padding-top: 10px; margin-top: 10px;">
                📝 <strong>Ghi chú từ Cán bộ tuyển sinh:</strong> {{ghi_chu}}
            </p>
        </div>
        
        <p>Sự điều chỉnh này đã được cập nhật vào hồ sơ đăng ký xét tuyển trực tuyến của bạn để đảm bảo tính công bằng và quyền lợi hợp pháp của bạn khi xét tuyển vào trường.</p>
        
        <p>Vui lòng đăng nhập vào tài khoản tuyển sinh của bạn để xem chi tiết thông tin hồ sơ đã cập nhật.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{login_url}}" style="display: inline-block; background: #CE1B22; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 6px rgba(206, 27, 34, 0.2); transition: all 0.2s;">Đăng nhập kiểm tra hồ sơ</a>
        </div>
        
        <p style="margin-bottom: 0;">Trân trọng,<br><strong>Hội đồng Tuyển sinh Trường Đại học Hùng Vương</strong></p>
    </div>
    <div style="text-align: center; padding: 15px; color: #94a3b8; font-size: 12px; border-top: 1px solid #f1f5f9; margin-top: 20px;">
        <p style="margin: 0 0 4px;">📞 Hotline hỗ trợ: 0210.3993.970 | 📧 Email: tuyensinh@hvu.edu.vn</p>
        <p style="margin: 0;">© 2026 Trường Đại học Hùng Vương - Nguyễn Tất Thành, Việt Trì, Phú Thọ.</p>
    </div>
</div>
'
        ],
        [
            'code' => 'check_admission_info',
            'subject' => '[HVU] Yêu cầu rà soát, kiểm tra lại thông tin hồ sơ xét tuyển',
            'variables' => 'ho_ten, cccd, ghi_chu, login_url',
            'type' => 'system',
            'body' => '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #334155; background-color: #ffffff; border: 1px solid #f1f5f9; border-radius: 16px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);">
    <div style="background: linear-gradient(135deg, #f59e0b, #d97706); padding: 24px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Trường Đại học Hùng Vương</h1>
        <p style="color: rgba(255,255,255,0.85); margin: 6px 0 0; font-size: 14px; font-weight: bold;">Hệ thống Tuyển sinh Trực tuyến</p>
    </div>
    <div style="padding: 24px 30px; line-height: 1.6;">
        <h2 style="color: #1e293b; margin-top: 0; font-size: 18px; font-weight: bold; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">Yêu cầu kiểm tra, đính chính thông tin xét tuyển</h2>
        
        <p>Kính chào bạn <strong>{{ho_ten}}</strong> (CCCD: {{cccd}}),</p>
        
        <p>Hội đồng Tuyển sinh Trường Đại học Hùng Vương đã tiến hành hậu kiểm và rà soát hồ sơ xét tuyển trực tuyến của bạn. Hiện tại, chúng tôi nhận thấy một số thông tin bạn đã đăng ký có sự chưa nhất quán hoặc cần đính chính lại để tránh ảnh hưởng đến kết quả xét tuyển và quá trình đối chiếu hồ sơ gốc sau này.</p>
        
        <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; padding: 20px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; color: #b45309; line-height: 1.7;">
                ⚠️ <strong>Nội dung cán bộ yêu cầu bạn rà soát:</strong><br>
                <span style="font-weight: bold; display: block; margin-top: 8px; color: #451a03;">{{ghi_chu}}</span>
            </p>
        </div>
        
        <p style="color: #b45309; font-weight: bold;">⚠️ Lưu ý quan trọng:</p>
        <ul style="color: #64748b; font-size: 13px; padding-left: 20px; margin-top: 5px;">
            <li style="margin-bottom: 6px;">Vui lòng kiểm tra lại điểm số học bạ (điểm trung bình học kỳ) so với sổ học bạ gốc.</li>
            <li style="margin-bottom: 6px;">Kiểm tra lại thông tin họ tên, ngày sinh, nơi sinh, số CCCD chính xác.</li>
            <li style="margin-bottom: 6px;">Nếu phát hiện sai sót, vui lòng sửa lại ngay hoặc liên hệ Hotline hỗ trợ để mở khóa quyền chỉnh sửa hồ sơ.</li>
        </ul>
        
        <p>Để đảm bảo quyền lợi xét tuyển tối đa, vui lòng đăng nhập ngay vào tài khoản của bạn để tiến hành rà soát và cập nhật thông tin.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{login_url}}" style="display: inline-block; background: #d97706; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 6px rgba(217, 119, 6, 0.2); transition: all 0.2s;">Đăng nhập rà soát thông tin</a>
        </div>
        
        <p style="margin-bottom: 0;">Trân trọng,<br><strong>Hội đồng Tuyển sinh Trường Đại học Hùng Vương</strong></p>
    </div>
    <div style="text-align: center; padding: 15px; color: #94a3b8; font-size: 12px; border-top: 1px solid #f1f5f9; margin-top: 20px;">
        <p style="margin: 0 0 4px;">📞 Hotline hỗ trợ: 0210.3993.970 | 📧 Email: tuyensinh@hvu.edu.vn</p>
        <p style="margin: 0;">© 2026 Trường Đại học Hùng Vương - Nguyễn Tất Thành, Việt Trì, Phú Thọ.</p>
    </div>
</div>
'
        ],
        [
            'code' => 'request_profile_supplement',
            'subject' => '[HVU] Thông báo yêu cầu bổ sung minh chứng, hồ sơ xét tuyển',
            'variables' => 'ho_ten, cccd, ghi_chu, login_url',
            'type' => 'system',
            'body' => '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #334155; background-color: #ffffff; border: 1px solid #f1f5f9; border-radius: 16px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);">
    <div style="background: linear-gradient(135deg, #0284c7, #0369a1); padding: 24px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Trường Đại học Hùng Vương</h1>
        <p style="color: rgba(255,255,255,0.85); margin: 6px 0 0; font-size: 14px; font-weight: bold;">Hệ thống Tuyển sinh Trực tuyến</p>
    </div>
    <div style="padding: 24px 30px; line-height: 1.6;">
        <h2 style="color: #1e293b; margin-top: 0; font-size: 18px; font-weight: bold; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">Thông báo bổ sung hồ sơ xét tuyển</h2>
        
        <p>Kính chào bạn <strong>{{ho_ten}}</strong> (CCCD: {{cccd}}),</p>
        
        <p>Hội đồng Tuyển sinh Trường Đại học Hùng Vương xin thông báo hồ sơ đăng ký xét tuyển trực tuyến của bạn đã được cán bộ tuyển sinh tiếp nhận sơ bộ. Tuy nhiên, qua quá trình đối chiếu, hồ sơ của bạn hiện đang bị thiếu hoặc mờ các tài liệu, ảnh chụp minh chứng bắt buộc sau:</p>
        
        <div style="background: #f0f9ff; border: 1px solid #e0f2fe; border-radius: 12px; padding: 20px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; color: #0369a1; line-height: 1.7;">
                📂 <strong>Minh chứng bạn cần tải lên bổ sung:</strong><br>
                <span style="font-weight: bold; display: block; margin-top: 8px; color: #0c4a6e;">{{ghi_chu}}</span>
            </p>
        </div>
        
        <p><strong>Hướng dẫn thực hiện:</strong></p>
        <ol style="color: #64748b; font-size: 13.5px; padding-left: 20px; line-height: 1.8;">
            <li>Chụp ảnh hoặc scan tài liệu minh chứng yêu cầu rõ nét, không bị lóa sáng hay mất góc.</li>
            <li>Đăng nhập vào hệ thống tuyển sinh của nhà trường.</li>
            <li>Đến các bước tương ứng (Bước 1: thông tin cá nhân/ảnh thẻ, Bước 2: học bạ THPT...) để tải lên file minh chứng mới thay thế.</li>
            <li>Nhấn <strong>Lưu lại và tiếp tục</strong> ở mỗi bước để hoàn thành việc đính kèm tài liệu.</li>
        </ol>
        
        <p style="color: #0369a1; font-weight: bold;">🔔 Vui lòng bổ sung sớm trước thời gian kết thúc nhận hồ sơ đợt tuyển sinh này để hồ sơ của bạn đủ điều kiện duyệt hợp lệ.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{login_url}}" style="display: inline-block; background: #0284c7; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 6px rgba(2, 132, 199, 0.2); transition: all 0.2s;">Đăng nhập bổ sung hồ sơ</a>
        </div>
        
        <p style="margin-bottom: 0;">Trân trọng,<br><strong>Hội đồng Tuyển sinh Trường Đại học Hùng Vương</strong></p>
    </div>
    <div style="text-align: center; padding: 15px; color: #94a3b8; font-size: 12px; border-top: 1px solid #f1f5f9; margin-top: 20px;">
        <p style="margin: 0 0 4px;">📞 Hotline hỗ trợ: 0210.3993.970 | 📧 Email: tuyensinh@hvu.edu.vn</p>
        <p style="margin: 0;">© 2026 Trường Đại học Hùng Vương - Nguyễn Tất Thành, Việt Trì, Phú Thọ.</p>
    </div>
</div>
'
        ]
    ];

    foreach ($templates as $tpl) {
        $stmt = $db->prepare("SELECT id FROM email_templates WHERE code = ?");
        $stmt->execute([$tpl['code']]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            echo "Template '{$tpl['code']}' already exists, updating...\n";
            $upd = $db->prepare("UPDATE email_templates SET subject = ?, body = ?, variables = ?, updated_at = NOW() WHERE code = ?");
            $upd->execute([$tpl['subject'], $tpl['body'], $tpl['variables'], $tpl['code']]);
        } else {
            echo "Creating new template '{$tpl['code']}'...\n";
            $ins = $db->prepare("INSERT INTO email_templates (code, subject, body, variables, type) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$tpl['code'], $tpl['subject'], $tpl['body'], $tpl['variables'], $tpl['type']]);
        }
    }

    echo "Migration Complete successfully!\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
