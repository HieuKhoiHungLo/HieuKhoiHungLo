<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Core\Cache;

try {
    Cache::flush();
    echo "<h1>Cache Flushed Successfully!</h1>";
    echo "<p>All files in storage/cache have been deleted.</p>";
    echo "<a href='/admin/dashboard'>Go back to Dashboard</a>";
} catch (\Exception $e) {
    echo "<h1>Error flushing cache</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
