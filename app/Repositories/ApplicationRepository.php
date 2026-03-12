<?php

namespace App\Repositories;

use App\Models\Application;
use PDO;

class ApplicationRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new Application();
    }

    public function getByCCCD(string $cccd): ?array
    {
        return $this->model->getByCCCD($cccd);
    }

    public function findByCCCDAndSession($cccd, $sessionId)
    {
        return $this->model->findByCCCDAndSession($cccd, $sessionId);
    }

    public function create($cccd, $sessionId)
    {
        return $this->model->create($cccd, $sessionId);
    }

    public function getStats(?int $sessionId = null): array
    {
        if (method_exists($this->model, 'getStats')) {
            return $this->model->getStats($sessionId);
        }
        return [];
    }

    public function getDailyStats(string $startDate, string $endDate, $sessionId = null): array
    {
        return $this->model->getDailyStats($startDate, $endDate, $sessionId);
    }

    public function getStatusStats(string $startDate, string $endDate, $sessionId = null): array
    {
        if (method_exists($this->model, 'getStatusStats')) {
            return $this->model->getStatusStats($startDate, $endDate, $sessionId);
        }
        return [];
    }

    public function transferSession(array $cccds, int $sessionId): int
    {
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

    /**
     * Update application fields by ID
     * e.g. update trang_thai, ghi_chu
     */
    public function update(int $applicationId, array $data): bool
    {
        if (empty($data)) return false;

        $db = \App\Core\Database::getInstance()->getConnection();
        $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $applicationId;

        $stmt = $db->prepare("UPDATE ho_so_xet_tuyen SET $sets WHERE id = ?");
        return $stmt->execute($values);
    }

    // Add other methods as needed
}
