<?php
namespace App\Services;

use App\Core\Database;
use App\Models\ThiSinh;

/**
 * PasswordResetService - Handle password reset via email
 */
class PasswordResetService {
    
    protected $db;
    protected $mailer;
    protected const TOKEN_EXPIRY_HOURS = 1;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->mailer = new MailerService();
    }

    /**
     * Create password reset token
     */
    public function createToken(string $email): ?string {
        // Check if email exists
        $thiSinh = new ThiSinh();
        $user = $thiSinh->findByEmail($email);
        
        if (!$user) {
            return null;
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_HOURS . ' hour'));

        // Delete existing tokens for this email
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);

        // Insert new token
        $stmt = $this->db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expiresAt]);

        return $token;
    }

    /**
     * Validate token and return email if valid
     */
    public function validateToken(string $token): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM password_resets 
            WHERE token = ? 
              AND expires_at > NOW() 
              AND (used = FALSE OR used IS NULL)
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Mark token as used
     */
    public function markUsed(string $token): bool {
        $stmt = $this->db->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?");
        return $stmt->execute([$token]);
    }

    /**
     * Send password reset email
     */
    public function sendResetEmail(string $email, string $token): bool {
        $resetUrl = \App\Core\App::url('/reset-password-email?token=' . $token);
        
        $subject = "Đặt lại mật khẩu - HVU Tuyển sinh";
        $body = $this->getEmailTemplate($resetUrl);

        return $this->mailer->send($email, $subject, $body, true);
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $token, string $newPassword): bool {
        $tokenData = $this->validateToken($token);
        
        if (!$tokenData) {
            return false;
        }

        $email = $tokenData['email'];
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        // Update password
        $thiSinh = new ThiSinh();
        $result = $thiSinh->updatePasswordByEmail($email, $hashedPassword);

        if ($result) {
            $this->markUsed($token);
            
            // Audit log
            $auditService = new AuditService();
            $auditService->log('PASSWORD_RESET_EMAIL', 'candidate', null, null, ['email' => $email]);
        }

        return $result;
    }

    /**
     * Get email template
     */
    protected function getEmailTemplate(string $resetUrl): string {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #CE1B22; margin: 0;">ĐẠI HỌC HÙNG VƯƠNG</h1>
                <p style="color: #666; margin: 5px 0;">Hệ thống Xét tuyển Trực tuyến</p>
            </div>
            
            <div style="background: #f8f9fa; border-radius: 10px; padding: 30px; margin-bottom: 20px;">
                <h2 style="color: #333; margin-top: 0;">Yêu cầu Đặt lại Mật khẩu</h2>
                <p style="color: #555; line-height: 1.6;">
                    Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản xét tuyển. 
                    Vui lòng nhấn vào nút bên dưới để tạo mật khẩu mới:
                </p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $resetUrl . '" 
                       style="display: inline-block; background: #CE1B22; color: white; padding: 15px 40px; 
                              text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                        Đặt lại Mật khẩu
                    </a>
                </div>
                
                <p style="color: #888; font-size: 13px;">
                    <strong>Lưu ý:</strong> Link này chỉ có hiệu lực trong <strong>1 giờ</strong>.
                    Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.
                </p>
            </div>
            
            <div style="text-align: center; color: #999; font-size: 12px;">
                <p>© ' . date('Y') . ' Đại học Hùng Vương - Phú Thọ</p>
            </div>
        </div>';
    }
}
