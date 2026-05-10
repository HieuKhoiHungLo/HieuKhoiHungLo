<?php
if (!function_exists('csrf_field')) {
    function csrf_field() {
        return \App\Middleware\SecurityMiddleware::csrfField();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        return \App\Middleware\SecurityMiddleware::generateCsrfToken();
    }
}

if (!function_exists('url')) {
    function url($path, $absolute = false) {
        return \App\Core\App::url($path, $absolute);
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        // Simple asset helper
        $base = \App\Core\App::getBaseUrl();
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('google_drive_embed_url')) {
    function google_drive_embed_url($url, $size = 'w1000') {
        if (empty($url)) return '';
        
        // If it's already a thumbnail link, preserve it but try to update size if possible (regex might be overkill here so we leave as is or basic replace)
        if (strpos($url, 'drive.google.com/thumbnail') !== false) {
             // Optional: replace size param if present, but for now just return
            return $url;
        }

        // Apply for View/Open/File links
        $id = '';
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $id = $matches[1];
        } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $id = $matches[1];
        }

        if ($id) {
            // Return thumbnail with requested size
            // s400 = size 400px (width or height max)
            // w400 = width 400px
            return "https://drive.google.com/thumbnail?id={$id}&sz={$size}";
        }

        return $url;
    }
}

if (!function_exists('google_drive_thumbnail_url')) {
    function google_drive_thumbnail_url($url, $size = 'w1000') {
        return google_drive_embed_url($url, $size);
    }
}

if (!function_exists('normalize_name')) {
    /**
     * Chuẩn hóa họ tên: Xóa khoảng trắng thừa và chuyển sang IN HOA
     */
    function normalize_name($name) {
        if (empty($name)) return '';
        // Xóa khoảng trắng thừa ở đầu, cuối và giữa các từ
        $name = preg_replace('/\s+/', ' ', trim($name));
        // Chuyển sang in hoa (hỗ trợ tiếng Việt multibyte)
        return mb_strtoupper($name, 'UTF-8');
    }
}

if (!function_exists('can')) {
    /**
     * Global helper for permission check
     */
    function can($permission) {
        static $service = null;
        if ($service === null) {
            $service = new \App\Services\PermissionService();
        }
        return $service->can($permission);
    }
}
