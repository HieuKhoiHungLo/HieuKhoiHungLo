<?php
namespace App\Repositories;

use App\Models\Application;
use PDO;

class ApplicationRepository {
    protected $model;

    public function __construct() {
        $this->model = new Application();
    }

    public function getByCCCD(string $cccd): ?array {
        return $this->model->getByCCCD($cccd);
    }

    public function findByCCCDAndSession($cccd, $sessionId) {
        return $this->model->findByCCCDAndSession($cccd, $sessionId);
    }

    public function create($cccd, $sessionId) {
        return $this->model->create($cccd, $sessionId);
    }

    public function getStats(?int $sessionId = null): array {
        // Delegate to model for now
        return $this->model->getStats($sessionId);
    }

    public function getDailyStats(string $startDate, string $endDate, $sessionId = null): array {
        return $this->model->getDailyStats($startDate, $endDate, $sessionId);
    }

    public function getStatusStats(string $startDate, string $endDate, $sessionId = null): array {
        if (method_exists($this->model, 'getStatusStats')) {
            return $this->model->getStatusStats($startDate, $endDate, $sessionId);
        }
        return []; 
    }

    public function transferSession(array $cccds, int $sessionId): int {
        $count = 0;
        foreach ($cccds as $cccd) {
            // Debug Loop
            $result = $this->model->updateSession($cccd, $sessionId);
            if ($result) {
                $count++;
            } else {
                error_log("Failed to transfer CCCD: $cccd to Session: $sessionId");
            }
        }
        return $count;
    }

    // Add other methods as needed
}
