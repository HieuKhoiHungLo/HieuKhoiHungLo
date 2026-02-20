<?php
namespace App\Core;

class Cache {
    protected static $cacheDir = __DIR__ . '/../../storage/cache';

    public static function init() {
        if (!file_exists(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0777, true);
        }
    }

    public static function get($key, $default = null) {
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

        return $data['value'];
    }

    public static function put($key, $value, $minutes = 60) {
        self::init();
        $file = self::getFilePath($key);
        $data = [
            'value' => $value,
            'expires_at' => time() + ($minutes * 60)
        ];
        file_put_contents($file, json_encode($data));
    }

    public static function forget($key) {
        $file = self::getFilePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public static function flush() {
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
