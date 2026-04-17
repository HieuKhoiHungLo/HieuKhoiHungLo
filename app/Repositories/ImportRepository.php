<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class ImportRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllBatches() {
        $stmt = $this->db->query("SELECT * FROM dot_tuyen_sinh ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveBatch() {
        $stmt = $this->db->query("SELECT * FROM dot_tuyen_sinh WHERE kich_hoat = true ORDER BY id DESC LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createBatch($name, $year) {
        $stmt = $this->db->prepare("INSERT INTO dot_tuyen_sinh (ten_dot, nam_tuyen_sinh, dm_nam_tuyen_sinh_nam, kich_hoat) VALUES (?, ?, ?, true)");
        // Update dm_nam_tuyen_sinh_nam as well for FK consistency if needed
        $stmt->execute([$name, (int)$year, (int)$year]);
        return $this->db->lastInsertId();
    }

    public function logImport($fileName, $type, $recordCount, $adminId) {
        $stmt = $this->db->prepare("INSERT INTO log_import (file_name, loai_file, record_count, imported_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fileName, $type, $recordCount, $adminId]);
        return $this->db->lastInsertId();
    }

    public function updateImportLog($id, $successCount, $errorLog) {
        $stmt = $this->db->prepare("UPDATE log_import SET success_count = ?, error_log = ? WHERE id = ?");
        $stmt->execute([$successCount, $errorLog, $id]);
    }

    public function getImportHistory() {
        $stmt = $this->db->query("SELECT * FROM log_import ORDER BY id DESC LIMIT 50");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
