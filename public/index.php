<?php
// public/index.php

// Autoloader (load first for middleware)
require_once __DIR__ . '/../vendor/autoload.php';

// Set timezone to Vietnam (UTC+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Load Env early (Before middleware and session)
try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {
    // Fail silently if .env not found
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require_once __DIR__ . '/../app/Helpers/functions.php';

// Secure session setup
\App\Middleware\SecurityMiddleware::secureSession();

// Fix session path for cross-platform compatibility and permission issues
$sessionPath = __DIR__ . '/../storage/framework/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
if (is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

// Prevent PHP Garbage Collector from clearing the session file before 24 hours
ini_set('session.gc_maxlifetime', 86400);

session_start();

// Security headers
\App\Middleware\SecurityMiddleware::setSecurityHeaders();

// Session timeout check (1 day = 1440 minutes)
\App\Middleware\SecurityMiddleware::checkSessionTimeout(1440);

// --- REMEMBER ME AUTO-LOGIN LOGIC (Secure Hash Version) ---
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    // Check Candidate
    if (isset($_COOKIE['remember_ts'])) {
        $token = $_COOKIE['remember_ts'];
        $hash = hash('sha256', $token);
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, ho_va_ten, so_cccd FROM thi_sinh WHERE remember_token = ? LIMIT 1");
            $stmt->execute([$hash]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['ho_va_ten'];
                $_SESSION['cccd'] = $user['so_cccd'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
            } else {
                setcookie('remember_ts', '', ['expires' => time() - 3600, 'path' => '/']);
            }
        } catch (\Exception $e) {}
    }
    // Check Admin
    elseif (isset($_COOKIE['remember_admin'])) {
        $token = $_COOKIE['remember_admin'];
        $hash = hash('sha256', $token);
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, ho_ten, ten_dang_nhap, vai_tro, avatar, role_id FROM quan_tri_vien WHERE remember_token = ? LIMIT 1");
            $stmt->execute([$hash]);
            $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($admin) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['ho_ten'];
                $_SESSION['admin_avatar'] = $admin['avatar'] ?? null;
                $_SESSION['admin_role'] = $admin['vai_tro'];
                $_SESSION['admin_role_id'] = $admin['role_id'] ?? 1;
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // Redirect immediately if on home page and logged in as admin
                $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
                $base = \App\Core\App::getBaseUrl();
                if ($uri === $base . '/' || $uri === $base) {
                    header('Location: ' . \App\Core\App::url('/admin/dashboard'));
                    exit;
                }
            } else {
                setcookie('remember_admin', '', ['expires' => time() - 3600, 'path' => '/']);
            }
        } catch (\Exception $e) {}
    }
}

require_once __DIR__ . '/../routes/web.php';

// Helper function
function url($path = '') {
    return App\Core\App::url($path);
}

$app = new App\Core\App();
$app->router = $router;

try {
    $app->run();
} catch (\PDOException $e) {
    if (isset($_GET['debug_db'])) {
        die("<div style='padding:20px;background:#fff5f5;border:1px solid #ffc1c1;color:#c00;font-family:sans-serif;'>"
          . "<h3>[Debug Query] Lỗi truy vấn cơ sở dữ liệu:</h3>"
          . "<p><b>Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>"
          . "<p><b>File:</b> " . htmlspecialchars($e->getFile()) . " (Line " . $e->getLine() . ")</p>"
          . "<pre style='background:#fff;padding:10px;border-radius:4px;border:1px solid #ffc1c1;color:#555;overflow-x:auto;font-size:12px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>"
          . "</div>");
    }

    // Show detailed database query errors on local machine for easy debugging
    $isLocal = isset($_SERVER['HTTP_HOST']) && 
               (str_contains($_SERVER['HTTP_HOST'], 'localhost') || str_contains($_SERVER['HTTP_HOST'], '127.0.0.1'));
    if ($isLocal) {
        die("<div style='padding:20px;background:#fff5f5;border:1px solid #ffc1c1;color:#c00;font-family:sans-serif;border-radius:6px;margin:20px;'>"
          . "<h3>[Local Debug] Lỗi truy vấn cơ sở dữ liệu:</h3>"
          . "<p><b>Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>"
          . "<p><b>File:</b> " . htmlspecialchars($e->getFile()) . " (Line " . $e->getLine() . ")</p>"
          . "<pre style='background:#fff;padding:10px;border-radius:4px;border:1px solid #ffc1c1;color:#555;overflow-x:auto;font-size:12px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>"
          . "</div>");
    }

    $errorViewPath = __DIR__ . '/../resources/views/errors/maintenance.php';
    if (file_exists($errorViewPath)) {
        http_response_code(503);
        require $errorViewPath;
        exit();
    }
    die("Hệ thống đang bảo trì hoặc mất kết nối cơ sở dữ liệu. Vui lòng thử lại sau.");
}
