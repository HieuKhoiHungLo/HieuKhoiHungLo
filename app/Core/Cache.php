<?php
namespace App\Core;

class Cache {
    protected static $cacheDir = __DIR__ . '/../../storage/cache';

    /** Request-scoped in-memory layer — avoids repeated disk I/O for the same key within one request */
    protected static array $memory = [];

    public static function init() {
        if (!file_exists(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0777, true);
        }
    }

    public static function get($key, $default = null) {
        // 1. In-memory hit (fastest — zero I/O)
        if (array_key_exists($key, self::$memory)) {
            return self::$memory[$key];
        }

        // 2. File cache
        $file = self::getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);

        if (!$data || !isset($data['expires_at'])) {
            return $default;
        }

        if (time() > $data['expires_at']) {
            unlink($file);
            return $default;
        }

        // Warm memory layer for subsequent reads this request
        self::$memory[$key] = $data['value'];
        return $data['value'];
    }

    public static function put($key, $value, $minutes = 60) {
        self::$memory[$key] = $value;  // Warm memory immediately
        self::init();
        $file = self::getFilePath($key);
        $data = [
            'key' => $key,
            'value' => $value,
            'expires_at' => time() + ($minutes * 60)
        ];
        file_put_contents($file, json_encode($data));
    }

    public static function forget($key) {
        unset(self::$memory[$key]);  // Evict from memory too
        $file = self::getFilePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public static function forgetByPattern($pattern) {
        self::init();
        $files = glob(self::$cacheDir . '/*.json');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    $content = file_get_contents($file);
                    $data = json_decode($content, true);
                    if (isset($data['key']) && preg_match($pattern, $data['key'])) {
                        unlink($file);
                        unset(self::$memory[$data['key']]);
                    }
                }
            }
        }
    }

    public static function flush() {
        self::$memory = [];  // Clear memory layer
        self::init();
        $files = glob(self::$cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public static function remember($key, $minutes, $callback) {
        $value = self::get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::put($key, $value, $minutes);
        return $value;
    }

    protected static function getFilePath($key) {
        return self::$cacheDir . '/' . md5($key) . '.json';
    }
}
