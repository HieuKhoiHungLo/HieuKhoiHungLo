<?php
namespace App\Core;

class RateLimiter {
    protected static $cacheDir = __DIR__ . '/../../storage/framework/ratelimit';

    public static function check($key, $maxAttempts = 5, $decayMinutes = 1) {
        self::init();
        $file = self::getFilePath($key);
        
        $now = time();
        $data = [
            'attempts' => 0,
            'last_attempt' => $now,
            'blocked_until' => 0
        ];

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
        }

        // Kiểm tra xem có đang bị chặn không
        if ($data['blocked_until'] > $now) {
            return false;
        }

        // Reset nếu đã qua thời gian decay
        if ($now - $data['last_attempt'] > ($decayMinutes * 60)) {
            $data['attempts'] = 0;
            $data['blocked_until'] = 0;
        }

        $data['attempts']++;
        $data['last_attempt'] = $now;

        if ($data['attempts'] > $maxAttempts) {
            $data['blocked_until'] = $now + ($decayMinutes * 60);
            file_put_contents($file, json_encode($data));
            return false;
        }

        file_put_contents($file, json_encode($data));
        return true;
    }

    public static function remaining($key, $maxAttempts = 5) {
        $file = self::getFilePath($key);
        if (!file_exists($file)) return $maxAttempts;
        
        $data = json_decode(file_get_contents($file), true);
        return max(0, $maxAttempts - $data['attempts']);
    }

    protected static function init() {
        if (!file_exists(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0777, true);
        }
    }

    protected static function getFilePath($key) {
        return self::$cacheDir . '/' . md5($key) . '.json';
    }
}
