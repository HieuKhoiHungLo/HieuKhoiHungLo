<?php
namespace App\Repositories;

use App\Models\NguyenVong;
use App\Core\Database;
use PDO;

class NguyenVongRepository {
    protected $db;
    protected $model;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->model = new NguyenVong();
    }

    public function getByCCCD($cccd) {
        return $this->model->getByCCCD($cccd);
    }

    public function save($cccd, $hoSoId, $data) {
        return $this->model->save($cccd, $hoSoId, $data);
    }

    public function updateStatus($cccd, $status) {
        return $this->model->updateStatus($cccd, $status);
    }

    public function getMajorStats($limit = 10, $startDate = null, $endDate = null, $sessionId = null) {
        return $this->model->getMajorStats($limit, $startDate, $endDate, $sessionId);
    }

    public function bulkUpdateStatus($cccds, $status) {
        if (empty($cccds)) return false;
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));
        $sql = "UPDATE nguyen_vong SET trang_thai = ? WHERE so_cccd IN ($placeholders)";
        $params = array_merge([$status], $cccds);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
