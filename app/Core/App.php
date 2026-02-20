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

    public static function url($path = '') {
        if (empty($path)) return '';
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }
        $base = self::getBaseUrl();
        return $base . '/' . ltrim($path, '/');
    }

    public function run() {
        $this->router->resolve();
    }
}
