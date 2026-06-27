<?php
namespace App\Core;

class App {
    public $router;
    private static $baseUrl = null;

    public function __construct() {
        $this->router = new Router();
    }

    public static function getBaseUrl() {
        if (self::$baseUrl === null) {
            // Check Env first
            if (!empty($_ENV['APP_URL'])) {
                $appUrl = rtrim($_ENV['APP_URL'], '/');
                // Always use relative path — extract path if full URL given
                if (preg_match('#^https?://#i', $appUrl)) {
                    $parsed = parse_url($appUrl, PHP_URL_PATH);
                    self::$baseUrl = $parsed ? rtrim($parsed, '/') : '';
                } else {
                    self::$baseUrl = $appUrl;
                }
                return self::$baseUrl;
            }

            $scriptName = $_SERVER['SCRIPT_NAME'];
            $dir = str_replace('\\', '/', dirname($scriptName)); // /TS/public
            
            // Nếu URL hiện tại không chứa đường dẫn đầy đủ (bao gồm /public), 
            // có nghĩa là đang dùng .htaccess để chạy clean URL.
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            if (stripos($requestUri, $dir) === false) {
                $dir = preg_replace('/\/public$/i', '', $dir);
            }
            
            self::$baseUrl = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
        }
        return self::$baseUrl;
    }

    public static function url($path = '', $absolute = false) {
        if (empty($path)) return '';
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }

        // Intercept register and login paths for external redirection
        static $redirectCache = null;
        $trimmedPath = '/' . ltrim($path, '/');
        if ($trimmedPath === '/register' || $trimmedPath === '/login') {
            if ($redirectCache === null) {
                $redirectCache = ['enabled' => false, 'url' => ''];
                try {
                    $db = \App\Core\Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT value FROM settings WHERE \"key\" = 'redirect_external_enable'");
                    $stmt->execute();
                    $enabled = $stmt->fetchColumn();
                    if ($enabled == '1') {
                        $stmt = $db->prepare("SELECT value FROM settings WHERE \"key\" = 'redirect_external_url'");
                        $stmt->execute();
                        $url = $stmt->fetchColumn();
                        if (!empty($url)) {
                            $redirectCache = ['enabled' => true, 'url' => $url];
                        }
                    }
                } catch (\Exception $e) {
                    // Fail silently during initial setup
                }
            }

            if ($redirectCache['enabled']) {
                return $redirectCache['url'];
            }
        }

        if ($absolute) {
            return self::fullUrl($path);
        }

        $base = self::getBaseUrl();
        return $base . '/' . ltrim($path, '/');
    }

    public static function fullUrl($path = '') {
        $baseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
        if (empty($baseUrl)) {
            // Fallback to auto-detection of host if APP_URL missing
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $protocol . "://" . $host . self::getBaseUrl();
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    public function run() {
        $this->router->resolve();
    }
}
