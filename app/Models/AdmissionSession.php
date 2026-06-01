<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Cache;

class AdmissionSession extends \App\Core\Model {

    public function __construct() {
        parent::__construct();
    }

    public function getActiveSession() {
        return Cache::remember('active_session', 30, function() {
            $db = Database::getInstance()->getConnection();
            $sql = "
                SELECT dt.*, 
                       COALESCE(dt.nam_tuyen_sinh, nts.nam, dt.dm_nam_tuyen_sinh_nam) as nam_tuyen_sinh, 
                       nts.mo_ta as nam_mo_ta 
                FROM dot_tuyen_sinh dt
                LEFT JOIN dm_nam_tuyen_sinh nts ON dt.dm_nam_tuyen_sinh_nam = nts.nam
                WHERE dt.kich_hoat = true 
                AND CURRENT_DATE BETWEEN dt.ngay_bat_dau AND dt.ngay_ket_thuc
                ORDER BY dt.ngay_bat_dau DESC
                LIMIT 1
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        });
    }
    
    // Fallback if no specific date range is active, gets the latest open session
    public function getLatestActiveSession() {
         $sql = "
            SELECT dt.*, 
                   COALESCE(dt.nam_tuyen_sinh, nts.nam, dt.dm_nam_tuyen_sinh_nam) as nam_tuyen_sinh
            FROM dot_tuyen_sinh dt
            LEFT JOIN dm_nam_tuyen_sinh nts ON dt.dm_nam_tuyen_sinh_nam = nts.nam
            WHERE dt.kich_hoat = true
            ORDER BY dt.id DESC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Get the latest session in the system overall (active or inactive, expired or not)
    public function getLatestSession() {
         $sql = "
            SELECT dt.*, 
                   COALESCE(dt.nam_tuyen_sinh, nts.nam, dt.dm_nam_tuyen_sinh_nam) as nam_tuyen_sinh
            FROM dot_tuyen_sinh dt
            LEFT JOIN dm_nam_tuyen_sinh nts ON dt.dm_nam_tuyen_sinh_nam = nts.nam
            ORDER BY dt.id DESC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function getAll() {
        $sql = "
            SELECT dt.*, 
                   COALESCE(dt.nam_tuyen_sinh, nts.nam, dt.dm_nam_tuyen_sinh_nam) as nam_tuyen_sinh, 
                   nts.mo_ta as nam_mo_ta
            FROM dot_tuyen_sinh dt
            LEFT JOIN dm_nam_tuyen_sinh nts ON dt.dm_nam_tuyen_sinh_nam = nts.nam
            ORDER BY COALESCE(dt.nam_tuyen_sinh, nts.nam, dt.dm_nam_tuyen_sinh_nam) DESC, dt.ngay_bat_dau DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
