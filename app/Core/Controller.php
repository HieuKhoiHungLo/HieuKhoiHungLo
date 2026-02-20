<?php
namespace App\Core;

class Controller {
    public function view($view, $data = []) {
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

    public function redirect($url) {
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

    public function csrfToken() {
        return \App\Middleware\SecurityMiddleware::generateCsrfToken();
    }

    public function validateCsrf() {
        return \App\Middleware\SecurityMiddleware::validateCsrf();
    }

    public function verifyCsrf($token) {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return !empty($sessionToken) && $token === $sessionToken;
    }


    protected function requireAdmin() {
        if (empty($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
    }

    public function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

