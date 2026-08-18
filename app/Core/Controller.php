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
    /**
     * Resolve a config file path - handles both absolute and relative paths.
     * If the path starts with / or X:\ it's treated as absolute.
     * Otherwise it's relative to project root.
     */
    protected static function resolveConfigPath($envValue, $default = '')
    {
        $path = $envValue ?: $default;
        if (empty($path)) return '';

        // Check if already absolute (Linux /path or Windows C:\path or \\network\path)
        if (preg_match('#^(/|[A-Za-z]:[\\\\/]|\\\\\\\\)#', $path)) {
            return $path;
        }

        // Relative: resolve from project root
        $rootDir = dirname(dirname(__DIR__));
        
        return rtrim($rootDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    protected function getApplicationStatus()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (empty($_SESSION['cccd'])) {
            return [
                'status' => '',
                'isLocked' => false,
                'editRequestPending' => false,
                'isSessionClosed' => false,
                'sessionName' => ''
            ];
        }

        // Simple 30-second TTL cache for application status to reduce DB load
        $cacheKey = 'app_status_' . $_SESSION['cccd'];
        $cacheTimeKey = $cacheKey . '_time';
        $ttl = 30; // 30 seconds

        if (isset($_SESSION[$cacheKey]) && isset($_SESSION[$cacheTimeKey]) && (time() - $_SESSION[$cacheTimeKey]) < $ttl) {
            return $_SESSION[$cacheKey];
        }

        $applicationModel = new \App\Models\Application();
        $sessionModel = new \App\Models\AdmissionSession();
        $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestActiveSession();

        // Fallback: If no active session is found, check if they have ANY existing application overall
        if (!$activeSession) {
            $allApps = $applicationModel->getByCCCD($_SESSION['cccd']);
            if (!empty($allApps)) {
                $activeSession = $sessionModel->find($allApps[0]->dot_tuyen_sinh_id);
            }
        }

        $status = '';
        $isLocked = false;
        $editRequestPending = false;
        $isSessionClosed = false;
        $sessionName = '';
        $isScoreLocked = false;
        $isMinistryData = false;

        if ($activeSession) {
            $app = $applicationModel->findByCCCDAndSession($_SESSION['cccd'], $activeSession['id']);
            if ($app) {
                $status = $app->trang_thai ?? '';
                $isLocked = ($status === 'Đã duyệt' || $status === 'approved' || $status === 'DaDuyet');
                $editRequestPending = !empty($app->yeu_cau_chinh_sua);
            }

            $isLockedSession = !$activeSession['kich_hoat'];
            $isExpiredSession = strtotime($activeSession['ngay_ket_thuc']) < time();
            $isSessionClosed = $isLockedSession || $isExpiredSession;
            $sessionName = $activeSession['ten_dot'] ?? '';

            // Check if score editing is locked for this session or candidate has ministry data
            $thiSinhRepo = new \App\Repositories\ThiSinhRepository();
            $isScoreLocked = $thiSinhRepo->isScoreLockedForCandidate($_SESSION['cccd'], $activeSession['id']);
            $isMinistryData = !empty($activeSession['la_du_lieu_bo']);
        }

        $result = [
            'status' => $status,
            'isLocked' => $isLocked,
            'isScoreLocked' => $isScoreLocked,
            'isMinistryData' => $isMinistryData,
            'editRequestPending' => $editRequestPending,
            'isSessionClosed' => $isSessionClosed,
            'sessionName' => $sessionName
        ];

        $_SESSION[$cacheKey] = $result;
        $_SESSION[$cacheTimeKey] = time();

        return $result;
    }

    protected function getUploadPathInfo($cccd)
    {
        $sessionModel = new \App\Models\AdmissionSession();
        $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestActiveSession();

        // Fallback: If no active session is found, check if they have ANY existing application overall
        if (!$activeSession) {
            $applicationModel = new \App\Models\Application();
            $allApps = $applicationModel->getByCCCD($cccd);
            if (!empty($allApps)) {
                $activeSession = $sessionModel->find($allApps[0]->dot_tuyen_sinh_id);
            }
        }

        $year = date('Y');
        $sessionName = 'Dot1';

        if ($activeSession) {
            $year = $activeSession['nam_tuyen_sinh'] ?? date('Y');
            $sessionName = $activeSession['ma_dot'] ?? ('Dot_' . ($activeSession['id'] ?? '1'));
            // Slugify
            $sessionName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $sessionName);
        }

        // Standard Path: uploads/YEAR/SESSION/CCCD
        $relativePath = "/uploads/{$year}/{$sessionName}/{$cccd}";
        $absolutePath = __DIR__ . '/../../public' . $relativePath;

        return [
            'relative' => $relativePath,
            'absolute' => $absolutePath,
            'year' => $year,
            'session' => $sessionName
        ];
    }
}

