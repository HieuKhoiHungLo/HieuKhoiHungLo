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
        return $stmt->execute([$name, (int)$year, (int)$year]);
    }

    public function logImport($fileName, $type, $recordCount, $adminId, $duration = 0) {
        $stmt = $this->db->prepare("INSERT INTO log_import (file_name, loai_file, record_count, imported_by, duration, created_at) VALUES (?, ?, ?, ?, ?, NOW() AT TIME ZONE 'Asia/Ho_Chi_Minh')");
        return $stmt->execute([$fileName, $type, $recordCount, $adminId, (int)$duration]);
    }

    public function deleteImportLog($id) {
        $stmt = $this->db->prepare("DELETE FROM log_import WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function clearBatchData($batchId) {
        try {
            $this->db->beginTransaction();

            // 1. Delete Nguyen Vong (by batch_id) - Direct link
            $stmt = $this->db->prepare("DELETE FROM nguyen_vong WHERE dot_tuyen_sinh_id = ?");
            $stmt->execute([$batchId]);

            // 2. Delete ket_qua_hoc_tap (via subquery to avoid large parameter lists)
            $stmt = $this->db->prepare("DELETE FROM ket_qua_hoc_tap WHERE so_cccd IN (SELECT so_cccd FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ?)");
            $stmt->execute([$batchId]);

            // 3. Delete diem_thi_thpt (via subquery)
            $stmt = $this->db->prepare("DELETE FROM diem_thi_thpt WHERE so_cccd IN (SELECT so_cccd FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ?)");
            $stmt->execute([$batchId]);

            // 4. Delete diem_chi_tiet (via subquery)
            $stmt = $this->db->prepare("DELETE FROM diem_chi_tiet WHERE so_cccd IN (SELECT so_cccd FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ?)");
            $stmt->execute([$batchId]);

            // 5. Delete ho_so_xet_tuyen (The linker)
            $stmt = $this->db->prepare("DELETE FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ?");
            $stmt->execute([$batchId]);

            // 6. Delete thi_sinh (Orphans)
            // Chỉ xóa thí sinh nếu họ không còn bất kỳ hồ sơ, học bạ hay điểm thi nào trong hệ thống
            $orphanCondition = "
                NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen WHERE so_cccd = thi_sinh.so_cccd)
                AND NOT EXISTS (SELECT 1 FROM ket_qua_hoc_tap WHERE so_cccd = thi_sinh.so_cccd)
                AND NOT EXISTS (SELECT 1 FROM diem_thi_thpt WHERE so_cccd = thi_sinh.so_cccd)
            ";

            // 6.1 Delete certificates of orphans to avoid FK violation
            $stmt = $this->db->prepare("DELETE FROM chung_chi_thi_sinh WHERE so_cccd IN (SELECT so_cccd FROM thi_sinh WHERE $orphanCondition)");
            $stmt->execute();

            // 6.2 Now safe to delete orphan candidates
            $stmt = $this->db->prepare("DELETE FROM thi_sinh WHERE $orphanCondition");
            $stmt->execute();

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("clearBatchData Error: " . $e->getMessage());
            throw $e;
        }
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
