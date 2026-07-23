<?php
/**
 * CLI Script to Recalculate Scores for All Sessions on THV2026 Database
 * Run with: php scripts/recalculate_all.php
 */

require_once __DIR__ . '/../public/index.php'; // Reuse autoloader and core initialization

use App\Models\AdmissionSession;
use App\Services\ScoreCalculationService;

$sessionModel = new AdmissionSession();
$service = new ScoreCalculationService();

$sessions = $sessionModel->getAll();

if (empty($sessions)) {
    echo "No admission sessions found in the database.\n";
    exit(0);
}

echo "Starting score recalculation for all sessions on THV2026 database...\n";

foreach ($sessions as $session) {
    $id = $session['id'];
    $name = $session['ten_dot'] ?? 'Unnamed Session';
    $year = $session['nam_tuyen_sinh'] ?? 'N/A';
    
    echo "--------------------------------------------------\n";
    echo "Processing Session: ID $id - $name (Year: $year)...\n";
    
    try {
        // Force recalculation by setting $force = true
        $count = $service->recalculateSession($id, true);
        echo "SUCCESS: Recalculated $count candidates for Session $id.\n";
    } catch (\Throwable $e) {
        echo "ERROR on Session $id: " . $e->getMessage() . "\n";
    }
}

echo "--------------------------------------------------\n";
echo "All score recalculations completed successfully!\n";
?>
