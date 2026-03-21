<?php
namespace App\Services;

use App\Core\Database;

class AuditService {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Log an action
     */
    public function log($action, $entityType = null, $entityId = null, $oldValue = null, $newValue = null) {
        $adminId = $_SESSION['admin_id'] ?? null;
        $adminName = $_SESSION['admin_name'] ?? 'System';
        
        $sql = "INSERT INTO audit_logs (admin_id, admin_name, action, entity_type, entity_id, old_value, new_value, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $adminId,
            $adminName,
            $action,
            $entityType,
            $entityId,
            $oldValue ? json_encode($oldValue) : null,
            $newValue ? json_encode($newValue) : null,
            $this->getClientIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    /**
     * Log login attempt
     */
    public function logLogin($username, $success) {
        $sql = "INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        // Cast boolean to int (0/1) for PostgreSQL compatibility
        $stmt->execute([$username, $this->getClientIp(), (int)$success]);
    }

    /**
     * Check if IP is rate limited (optimized - session-based)
     */
    public function isRateLimited($maxAttempts = 5, $windowMinutes = 15) {
        // Use session for rate limiting instead of DB query
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
            $_SESSION['login_lockout_until'] = null;
        }
        
        // Check if currently locked out
        if ($_SESSION['login_lockout_until'] && time() < $_SESSION['login_lockout_until']) {
            return true;
        }
        
        // Clear old attempts outside window
        $cutoff = time() - ($windowMinutes * 60);
        $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });
        
        // Check if max attempts reached
        if (count($_SESSION['login_attempts']) >= $maxAttempts) {
            $_SESSION['login_lockout_until'] = time() + ($windowMinutes * 60);
            return true;
        }
        
        return false;
    }
    
    /**
     * Record failed login attempt in session
     */
    public function recordFailedAttempt() {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        $_SESSION['login_attempts'][] = time();
    }
    
    /**
     * Clear login attempts on successful login
     */
    public function clearLoginAttempts() {
        $_SESSION['login_attempts'] = [];
        $_SESSION['login_lockout_until'] = null;
    }

    /**
     * Get recent logs
     */
    public function getLogs($limit = 100, $offset = 0, $filters = []) {
        $sql = "SELECT * FROM audit_logs WHERE 1=1";
        $params = [];

        if (!empty($filters['admin_id'])) {
            $sql .= " AND admin_id = ?";
            $params[] = $filters['admin_id'];
        }
        if (!empty($filters['action'])) {
            $sql .= " AND action = ?";
            $params[] = $filters['action'];
        }
        if (!empty($filters['entity_type'])) {
            $sql .= " AND entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get log statistics
     */
    public function getStats() {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURRENT_DATE) as today_actions,
                    (SELECT COUNT(*) FROM login_attempts WHERE success = false AND created_at > NOW() - INTERVAL '24 hours') as failed_logins_24h,
                    (SELECT COUNT(DISTINCT admin_id) FROM audit_logs WHERE DATE(created_at) = CURRENT_DATE) as active_admins";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Purge old audit logs and login attempts
     */
    public function purgeOldRecords($days = 20) {
        // Purge audit logs
        $sql1 = "DELETE FROM audit_logs WHERE created_at < NOW() - INTERVAL '$days days'";
        $this->db->exec($sql1);
        
        // Purge login attempts
        $sql2 = "DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL '$days days'";
        $this->db->exec($sql2);
        
        // Purge online tracking (just in case, keep it extra clean)
        $sql3 = "DELETE FROM online_tracking WHERE last_activity < NOW() - INTERVAL '$days days'";
        $this->db->exec($sql3);

        return true;
    }

    /**
     * Clear ALL audit logs and login attempts
     */
    public function clearAll() {
        $this->db->exec("DELETE FROM audit_logs");
        $this->db->exec("DELETE FROM login_attempts");
        $this->db->exec("DELETE FROM online_tracking");
        return true;
    }

    private function getClientIp() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
