<?php
namespace App\Core;

class Logger {
    public static function log($message) {
        $logPath = __DIR__ . '/../../storage/logs';
        if (!is_dir($logPath)) {
            mkdir($logPath, 0777, true);
        }
        $file = $logPath . '/debug.log';
        $time = date('Y-m-d H:i:s');
        file_put_contents($file, "[$time] $message" . PHP_EOL, FILE_APPEND);
    }
}
