<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class OnlineTracking extends Model
{
    protected $table = 'online_tracking';

    public function upsertActivity($sessionId, $userId, $adminId, $ip, $userAgent)
    {
        $sql = "INSERT INTO {$this->table} (session_id, user_id, admin_id, ip_address, user_agent, last_activity)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON CONFLICT (session_id) 
                DO UPDATE SET 
                    user_id = EXCLUDED.user_id,
                    admin_id = EXCLUDED.admin_id,
                    last_activity = NOW()";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$sessionId, $userId, $adminId, $ip, $userAgent]);
    }

    public function getOnlineCounts($minutes = 15)
    {
        $minutes = intval($minutes);
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(user_id) as logged_in_users,
                    COUNT(admin_id) as logged_in_admins,
                    COUNT(*) - COUNT(user_id) - COUNT(admin_id) as guests
                FROM {$this->table}
                WHERE last_activity > NOW() - INTERVAL '$minutes minutes'";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cleanOldSessions($minutes = 30)
    {
        $minutes = intval($minutes);
        $sql = "DELETE FROM {$this->table} WHERE last_activity < NOW() - INTERVAL '$minutes minutes'";
        return $this->db->exec($sql);
    }
}
