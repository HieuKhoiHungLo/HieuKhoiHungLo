<?php
/**
 * CLI Worker for Background Scoring Calculations
 * Run with: php scripts/score_worker.php
 */

require_once __DIR__ . '/../public/index.php'; // Reuse autoloader and core initialization

use App\Services\QueueService;
use App\Services\ScoreCalculationService;

$queue = new QueueService();
$service = new ScoreCalculationService();

echo "Worker started. Listening for jobs...\n";

while (true) {
    try {
        $job = $queue->dequeue(30); // Block for 30s
        
        if ($job) {
            $type = $job['type'] ?? '';
            $id = $job['session_id'] ?? null;
            $force = $job['force'] ?? false;
            
            echo "[" . date('H:i:s') . "] Processing job: $type ($id)...\n";
            
            if ($type === 'recalculate_session' && $id) {
                $count = $service->recalculateSession($id, $force);
                echo "DONE: Recalculated $count candidates.\n";
            }
        }
    } catch (\Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        error_log("Worker Error: " . $e->getMessage());
        sleep(5); // Prevent tight loop on error
    }
}
?>
