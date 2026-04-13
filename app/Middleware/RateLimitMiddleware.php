<?php

namespace App\Middleware;

use App\Core\RateLimiter;

class RateLimitMiddleware
{
    /**
     * Handle rate limiting for incoming requests
     * 
     * @param int $maxAttempts Default 60 per minute for authenticated sessions, but overridden by route group
     * @param int $decayMinutes 1 minute block window
     */
    public function handle($maxAttempts = 60, $decayMinutes = 1)
    {
        // Whitelist authenticated admins and students
        // This ensures professional work is never interrupted by false-positives
        if (isset($_SESSION['admin_id']) || isset($_SESSION['user_id'])) {
            return;
        }

        // Improve IP detection (Handles standard X-Forwarded-For if behind a proxy)
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // Strip query parameters from URI to make the limit consistent for the same endpoint
        $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        
        $key = 'rl_' . md5($ip . $uri);

        if (!RateLimiter::check($key, $maxAttempts, $decayMinutes)) {
            http_response_code(429);
            header('Content-Type: application/json');
            die(json_encode([
                'error' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau vài phút.'
            ]));
        }
    }
}
