<?php
namespace App\Middleware;

use App\Core\RateLimiter;

class RateLimitMiddleware {
    public function handle($maxAttempts = 5, $decayMinutes = 1) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = 'rl_' . md5($ip . $_SERVER['REQUEST_URI']);

        if (!RateLimiter::check($key, $maxAttempts, $decayMinutes)) {
            http_response_code(429);
            header('Content-Type: application/json');
            die(json_encode([
                'error' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau vài phút.'
            ]));
        }
    }
}
