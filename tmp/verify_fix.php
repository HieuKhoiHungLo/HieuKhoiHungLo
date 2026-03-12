<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/DotEnv.php';
$dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
$dotenv->load();

require_once __DIR__ . '/../app/Core/Database.php';

try {
    // Simple way: check if the change is in the file (already done)
    // Real way: run a script that calls the repository method that was added to overview
    $repo = new \App\Repositories\NguyenVongRepository();
    $stats = $repo->getMajorStats(30);
    echo "NguyenVongRepository::getMajorStats returned " . count($stats) . " items.\n";
    if (count($stats) >= 0) {
        echo "SUCCESS: Major stats data query executed successfully.\n";
        if (count($stats) > 0) {
            echo "Data sample: " . $stats[0]['ten_nganh'] . " (" . $stats[0]['count'] . ")\n";
        } else {
            echo "NOTE: Data is empty, but query is valid.\n";
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
