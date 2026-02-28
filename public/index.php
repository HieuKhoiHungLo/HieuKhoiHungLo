<?php
// public/index.php

// Autoloader (load first for middleware)
require_once __DIR__ . '/../vendor/autoload.php';

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

// Fix session path for Windows XAMPP
$sessionPath = 'D:\\xampp\\tmp';
if (!is_dir($sessionPath)) {
    $sessionPath = sys_get_temp_dir();
}
session_save_path($sessionPath);

// Prevent PHP Garbage Collector from clearing the session file before 24 hours
ini_set('session.gc_maxlifetime', 86400);

session_start();

// Security headers
\App\Middleware\SecurityMiddleware::setSecurityHeaders();

// Session timeout check (1 day = 1440 minutes)
\App\Middleware\SecurityMiddleware::checkSessionTimeout(1440);

// --- REMEMBER ME AUTO-LOGIN LOGIC ---
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_ts'])) {
    $token = $_COOKIE['remember_ts'];
    // Connect to DB directly or load repo to verify token
    try {
        require_once __DIR__ . '/../config/db.php';
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, ho_va_ten, so_cccd FROM thi_sinh WHERE remember_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user) {
            // Token hợp lệ, phục hồi Session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['ho_va_ten'];
            $_SESSION['cccd'] = $user['so_cccd'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
        } else {
            // Token lỗi thời hoặc tài khoản bị đổi pass, huỷ Cookie
            setcookie('remember_ts', '', ['expires' => time() - 3600, 'path' => '/']);
        }
    } catch (\Exception $e) {
        // Ignore DB error during early boot
    }
}

// Load Env
try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {
    // Fail silently if .env not found
}

require_once __DIR__ . '/../routes/web.php';

// Helper function
function url($path = '') {
    return App\Core\App::url($path);
}

$app = new App\Core\App();
$app->router = $router;
$app->run();
