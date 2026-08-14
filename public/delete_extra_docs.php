<?php
require_once __DIR__ . '/../vendor/autoload.php';
try {
    $dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
    $dotenv->load();
} catch (\Exception $e) {}
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});
try {
    $db = \App\Core\Database::getInstance()->getConnection();
    // Keep only the first 2 items based on thu_tu
    $stmt = $db->exec("DELETE FROM nhap_hoc_ho_so WHERE thu_tu > 2");
    echo "Deleted $stmt rows. Only 2 documents remain.";
} catch(Exception $e) {
    echo $e->getMessage();
}
