<?php
require_once __DIR__ . '/../vendor/autoload.php';
try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\Cache;

try {
    Cache::flush();
    echo "<h1 style='color: green;'>Success: Cleared all cache files in storage/cache!</h1>";
    echo "<p><a href='/admin/dashboard'>Go back to Dashboard</a></p>";
} catch (\Exception $e) {
    echo "<h1 style='color: red;'>Error: " . $e->getMessage() . "</h1>";
}
