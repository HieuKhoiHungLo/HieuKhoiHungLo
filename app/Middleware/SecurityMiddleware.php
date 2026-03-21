<?php
namespace App\Middleware;

use App\Models\MasterData;
use App\Repositories\OnlineTrackingRepository;

class SecurityMiddleware {
    
    /**
     * Track online activity
     */
    public static function trackOnlineActivity() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionId = session_id();
        $userId = $_SESSION['user_id'] ?? null;
        $adminId = $_SESSION['admin_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        try {
            $repo = new OnlineTrackingRepository();
            $repo->trackActivity($sessionId, $userId, $adminId, $ip, $userAgent);
        } catch (\Exception $e) {
            // Silently fail to not break the app if DB is busy
            error_log("Online Tracking Error: " . $e->getMessage());
        }
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Get hidden input for forms
     */
    public static function csrfField() {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            $sessionToken = $_SESSION['csrf_token'] ?? '';
            
            if (empty($token) || !hash_equals($sessionToken, $token)) {
                http_response_code(403);
                // Trả về JSON nếu là request AJAX, ngược lại die với thông báo thân thiện
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
                
                if ($isAjax || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
                    header('Content-Type: application/json');
                    die(json_encode(['error' => 'Phiên làm việc đã hết hạn. Vui lòng Refresh (F5) lại trang.']));
                } else {
                    die('Phiên làm việc đã hết hạn. Vui lòng quay lại trang trước, nhấn F5 (Refresh) và thử lại.');
                }
            }
        }
    }

    /**
     * Regenerate session after login
     */
    public static function regenerateSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Set secure session cookies
     */
    public static function secureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            session_set_cookie_params([
                'lifetime' => 86400, // 24 hours (Matches checkSessionTimeout)
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax' // Allowed for top-level navigation, prevents mobile webview session dropping
            ]);
        }
    }

    /**
     * Sanitize input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Set security HTTP headers (CSP, X-Frame-Options, etc.)
     */
    public static function setSecurityHeaders() {
        // Content Security Policy
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.tailwindcss.com unpkg.com cdnjs.cloudflare.com cdn.jsdelivr.net; ";
        $csp .= "style-src 'self' 'unsafe-inline' fonts.googleapis.com cdnjs.cloudflare.com; ";
        $csp .= "font-src 'self' fonts.gstatic.com cdnjs.cloudflare.com data:; ";
        $csp .= "img-src 'self' data: https: blob:; ";
        $csp .= "connect-src 'self' cdn.jsdelivr.net cdnjs.cloudflare.com unpkg.com; ";
        $csp .= "frame-src https://www.youtube.com https://www.youtube-nocookie.com; ";
        $csp .= "frame-ancestors 'none';";
        
        header("Content-Security-Policy: " . $csp);
        
        // Prevent clickjacking
        header("X-Frame-Options: DENY");
        
        // Prevent MIME sniffing
        header("X-Content-Type-Options: nosniff");
        
        // XSS Protection (legacy browsers)
        header("X-XSS-Protection: 1; mode=block");
        
        // Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        // Permissions Policy
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

        // HSTS (Strict-Transport-Security) - Force HTTPS for 1 year
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }

        // Prevent Flash/PDF XSS
        header("X-Permitted-Cross-Domain-Policies: none");
    }

    /**
 * Check session timeout for admin and student (configurable minutes)
 */
public static function checkSessionTimeout($timeoutMinutes = 30) {
    // Track activity first
    // self::trackOnlineActivity();

    $timeoutSeconds = $timeoutMinutes * 60;
    
    // Admin timeout
    if (isset($_SESSION['admin_id'])) {
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive > $timeoutSeconds) {
                session_destroy();
                header('Location: ' . \App\Core\App::url('/admin/login?timeout=1'));
                exit;
            }
        }
        $_SESSION['last_activity'] = time();
    }

    // Student timeout
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive > $timeoutSeconds) {
                session_destroy();
                header('Location: ' . \App\Core\App::url('/?timeout=1'));
                exit;
            }
        }
        $_SESSION['last_activity'] = time();
    }
}

    /**
     * Get remaining session time in seconds
     */
    public static function getSessionTimeRemaining($timeoutMinutes = 30) {
        if (!isset($_SESSION['last_activity'])) {
            return $timeoutMinutes * 60;
        }
        $elapsed = time() - $_SESSION['last_activity'];
        $remaining = ($timeoutMinutes * 60) - $elapsed;
        return max(0, $remaining);
    }
}

// Helper function for views
function csrf_field() {
    return \App\Middleware\SecurityMiddleware::csrfField();
}
