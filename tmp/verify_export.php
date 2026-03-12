<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/DotEnv.php';
$dotenv = new App\Core\DotEnv(__DIR__ . '/../.env');
$dotenv->load();

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Services/ExportService.php';

try {
    $service = new \App\Services\ExportService();
    $data = $service->exportCertificates();
    
    echo "Export Certificates returned " . count($data) . " rows.\n";
    if (count($data) >= 0) {
        echo "SUCCESS: Export query executed successfully.\n";
        if (count($data) > 0) {
            echo "First row sample: " . json_encode($data[0], JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
