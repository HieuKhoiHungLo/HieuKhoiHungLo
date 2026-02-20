<?php
namespace App\Middleware;

use App\Core\Controller;

class AuthMiddleware {
    public function handle() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Kiểm tra quyền Admin dựa trên session
        if (empty($_SESSION['admin_id'])) {
            $url = \App\Core\App::url('/admin/login');
            header("Location: $url");
            exit;
        }
    }
}
