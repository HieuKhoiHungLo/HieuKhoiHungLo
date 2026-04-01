<?php
namespace App\Services;

class CacheService
{
    /** Cache a value for $ttl seconds */
    public static function set(string $key, $value, int $ttl = 300): void
    {
        if (function_exists('apcu_store')) {
            apcu_store($key, $value, $ttl);
        }
    }

    /** Retrieve a cached value or null */
    public static function get(string $key)
    {
        if (function_exists('apcu_fetch')) {
            $success = false;
            $value = apcu_fetch($key, $success);
            return $success ? $value : null;
        }
        return null;
    }
}
?>
