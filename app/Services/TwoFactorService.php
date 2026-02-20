<?php
namespace App\Services;

/**
 * TwoFactorService - TOTP-based Two-Factor Authentication
 * Uses HOTP/TOTP algorithm (RFC 6238) compatible with Google Authenticator
 */
class TwoFactorService {
    
    private const SECRET_LENGTH = 16;
    private const CODE_LENGTH = 6;
    private const TIME_STEP = 30;
    private const BACKUP_CODES_COUNT = 8;
    
    protected $db;

    public function __construct() {
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }

    /**
     * Generate a new secret key (Base32 encoded)
     */
    public function generateSecret(): string {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < self::SECRET_LENGTH; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Generate QR code URL for Google Authenticator
     */
    public function getQRCodeUrl(string $secret, string $email, string $issuer = 'HVU Admin'): string {
        $label = urlencode($issuer . ':' . $email);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::CODE_LENGTH,
            'period' => self::TIME_STEP
        ]);
        
        $otpauth = "otpauth://totp/{$label}?{$params}";
        
        // Use Google Chart API for QR code
        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($otpauth);
    }

    /**
     * Verify TOTP code
     */
    public function verifyCode(string $secret, string $code, int $window = 1): bool {
        $code = preg_replace('/\s+/', '', $code);
        
        if (strlen($code) !== self::CODE_LENGTH || !ctype_digit($code)) {
            return false;
        }
        
        $timestamp = time();
        
        // Check codes within window (past and future)
        for ($i = -$window; $i <= $window; $i++) {
            $expectedCode = $this->generateTOTP($secret, $timestamp + ($i * self::TIME_STEP));
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate TOTP code for given timestamp
     */
    protected function generateTOTP(string $secret, int $timestamp): string {
        $counter = floor($timestamp / self::TIME_STEP);
        
        // Decode Base32 secret
        $secretBytes = $this->base32Decode($secret);
        
        // Pack counter as 64-bit big-endian
        $counterBytes = pack('N*', 0, $counter);
        
        // HMAC-SHA1
        $hash = hash_hmac('sha1', $counterBytes, $secretBytes, true);
        
        // Dynamic truncation
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % pow(10, self::CODE_LENGTH);
        
        return str_pad($code, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Base32 decode
     */
    protected function base32Decode(string $input): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;
        
        for ($i = 0; $i < strlen($input); $i++) {
            $char = strtoupper($input[$i]);
            $val = strpos($alphabet, $char);
            if ($val === false) continue;
            
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        
        return $output;
    }

    /**
     * Generate backup codes
     */
    public function generateBackupCodes(): array {
        $codes = [];
        for ($i = 0; $i < self::BACKUP_CODES_COUNT; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    /**
     * Enable 2FA for admin
     */
    public function enable(int $adminId, string $secret, array $backupCodes): bool {
        $stmt = $this->db->prepare("UPDATE quan_tri_vien SET two_factor_secret = ?, two_factor_enabled = 1, two_factor_backup_codes = ? WHERE id = ?");
        return $stmt->execute([$secret, json_encode($backupCodes), $adminId]);
    }

    /**
     * Disable 2FA for admin
     */
    public function disable(int $adminId): bool {
        $stmt = $this->db->prepare("UPDATE quan_tri_vien SET two_factor_secret = NULL, two_factor_enabled = 0, two_factor_backup_codes = NULL WHERE id = ?");
        return $stmt->execute([$adminId]);
    }

    /**
     * Check if admin has 2FA enabled
     */
    public function isEnabled(int $adminId): bool {
        $stmt = $this->db->prepare("SELECT two_factor_enabled FROM quan_tri_vien WHERE id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result && $result['two_factor_enabled'] == 1;
    }

    /**
     * Get admin's 2FA secret
     */
    public function getSecret(int $adminId): ?string {
        $stmt = $this->db->prepare("SELECT two_factor_secret FROM quan_tri_vien WHERE id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['two_factor_secret'] ?? null;
    }

    /**
     * Verify backup code
     */
    public function verifyBackupCode(int $adminId, string $code): bool {
        $stmt = $this->db->prepare("SELECT two_factor_backup_codes FROM quan_tri_vien WHERE id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$result || empty($result['two_factor_backup_codes'])) {
            return false;
        }
        
        $codes = json_decode($result['two_factor_backup_codes'], true);
        $code = strtoupper(preg_replace('/\s+/', '', $code));
        
        $index = array_search($code, $codes);
        if ($index !== false) {
            // Remove used code
            unset($codes[$index]);
            $codes = array_values($codes);
            
            $updateStmt = $this->db->prepare("UPDATE quan_tri_vien SET two_factor_backup_codes = ? WHERE id = ?");
            $updateStmt->execute([json_encode($codes), $adminId]);
            
            return true;
        }
        
        return false;
    }
}
