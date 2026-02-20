<?php
namespace App\Repositories;

use App\Models\AcademicRecord;
use PDO;

class AcademicRepository {
    protected $model;

    public function __construct() {
        $this->model = new AcademicRecord();
    }

    public function getByCCCD(string $cccd): array {
        return $this->model->getByCCCD($cccd);
    }

    public function createOrUpdate(string $cccd, int $grade, array $data): bool {
        // Delegate to model
        return $this->model->save($cccd, $grade, $data);
    }
    
    // Add other methods as needed from direct model usage
}
