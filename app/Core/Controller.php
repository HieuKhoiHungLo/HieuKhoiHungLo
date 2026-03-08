<?php

namespace App\Core;

class Controller
{
    public function view($view, $data = [])
    {
        // Shared Data Injection
        if (!isset($data['enableTHPTSetting'])) {
            if (!isset($_SESSION['enable_thpt'])) {
                // Lazy load repo only if session not set
                $repo = new \App\Repositories\MasterDataRepository();
                $_SESSION['enable_thpt'] = $repo->getSetting('enable_thpt_step') == '1';
            }
            $data['enableTHPTSetting'] = $_SESSION['enable_thpt'];
        }

        if (!isset($data['totalStepsCount'])) {
            $data['totalStepsCount'] = $data['enableTHPTSetting'] ? 5 : 4;
        }

        extract($data);
        require_once __DIR__ . "/../../resources/views/$view.php";
    }

    public function redirect($url)
    {
        // Nếu đã là URL tuyệt đối (http...) hoặc đã chứa base path thì không cần gọi url() nữa
        if (strpos($url, 'http') === 0) {
            $fullUrl = $url;
        } else {
            // Kiểm tra xem $url đã có base path chưa
            $basePath = \App\Core\App::getBaseUrl();
            if (!empty($basePath) && strpos($url, $basePath) === 0) {
                $fullUrl = $url;
            } else {
                $fullUrl = url($url);
            }
        }

        header("Location: $fullUrl");
        exit;
    }

    public function csrfToken()
    {
        return \App\Middleware\SecurityMiddleware::generateCsrfToken();
    }

    public function validateCsrf()
    {
        return \App\Middleware\SecurityMiddleware::validateCsrf();
    }

    public function verifyCsrf($token)
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return !empty($sessionToken) && $token === $sessionToken;
    }


    protected function requireAdmin()
    {
        if (empty($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
    }

    public function json($data, $status = 200)
    {
        // Flush any accidental output (PHP warnings, notices, HTML fragments) before sending JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Resolve a config file path - handles both absolute and relative paths.
     * If the path starts with / or X:\ it's treated as absolute.
     * Otherwise it's relative to project root.
     */
    protected static function resolveConfigPath($envValue, $default = '')
    {
        $path = $envValue ?: $default;
        // Already absolute (Linux /path or Windows C:\path)
        if (preg_match('#^(/|[A-Za-z]:\\\\)#', $path)) {
            return $path;
        }
        // Relative: resolve from project root
        return realpath(__DIR__ . '/../../') . '/' . $path;
    }
}
