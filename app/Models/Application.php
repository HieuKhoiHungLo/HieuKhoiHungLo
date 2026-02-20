<?php
namespace App\Models;

use App\Core\Database;

class Application extends \App\Core\Model {

    public function __construct() {
        parent::__construct();
    }

    public function findByCCCDAndSession($cccd, $sessionId) {
        $sql = "SELECT * FROM ho_so_xet_tuyen WHERE so_cccd = :cccd AND dot_tuyen_sinh_id = :session_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cccd' => $cccd, 'session_id' => $sessionId]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    public function getByCCCD($cccd) {
        $sql = "
            SELECT hs.*, dt.ten_dot, dt.ma_dot, COALESCE(dt.nam_tuyen_sinh, nts.nam, dt.dm_nam_tuyen_sinh_nam) as nam_tuyen_sinh 
            FROM ho_so_xet_tuyen hs
            LEFT JOIN dot_tuyen_sinh dt ON hs.dot_tuyen_sinh_id = dt.id
            LEFT JOIN dm_nam_tuyen_sinh nts ON dt.dm_nam_tuyen_sinh_nam = nts.nam
            WHERE hs.so_cccd = :cccd
            ORDER BY hs.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cccd' => $cccd]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function create($cccd, $sessionId) {
        $sql = "INSERT INTO ho_so_xet_tuyen (so_cccd, dot_tuyen_sinh_id, trang_thai) VALUES (:cccd, :session_id, 'Chờ duyệt') RETURNING id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cccd' => $cccd, 'session_id' => $sessionId]);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);
        return $result ? $result->id : false;
    }
    
    public function updateDriveFolder($id, $folderId) {
        $sql = "UPDATE ho_so_xet_tuyen SET path_driver_folder = :folder_id WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['folder_id' => $folderId, 'id' => $id]);
    }

    public function getDailyStats($startDate, $endDate, $sessionId = null) {
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM ho_so_xet_tuyen 
                WHERE created_at >= :start AND created_at <= :end";
        
        $params = ['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59'];
        
        if ($sessionId) {
            $sql .= " AND dot_tuyen_sinh_id = :session_id";
            $params['session_id'] = $sessionId;
        }

        $sql .= " GROUP BY DATE(created_at) ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getStatusStats($startDate, $endDate, $sessionId = null) {
        $sql = "SELECT trang_thai, COUNT(*) as count 
                FROM ho_so_xet_tuyen 
                WHERE created_at >= :start AND created_at <= :end";

        $params = ['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59'];
        
        if ($sessionId) {
            $sql .= " AND dot_tuyen_sinh_id = :session_id";
            $params['session_id'] = $sessionId;
        }

        $sql .= " GROUP BY trang_thai";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateSession($cccd, $sessionId) {
        $sql = "UPDATE ho_so_xet_tuyen SET dot_tuyen_sinh_id = :session_id WHERE so_cccd = :cccd";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['session_id' => $sessionId, 'cccd' => $cccd]);
    }


}
