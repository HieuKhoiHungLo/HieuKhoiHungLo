<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $pdo;
    private $rlsContextSet = false;
    private $systemRole = null;

    private function __construct() {
        $config = require __DIR__ . '/../../config/db.php';
        
        $dsn = $config['dsn'] ?? '';
        $user = $config['user'] ?? '';
        $pass = $config['pass'] ?? '';
        $options = $config['options'] ?? [];
        
        if (empty($dsn)) {
            die("DB Connection Error: DSN is empty. Check config/db.php.");
        }
        if (empty($user)) {
            die("DB Connection Error: DB_USERNAME is empty. Check your .env file.");
        }
        if (empty($pass)) {
            die("DB Connection Error: DB_PASSWORD is empty. Check your .env file.");
        }

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            $errorViewPath = __DIR__ . '/../../resources/views/errors/maintenance.php';
            if (file_exists($errorViewPath)) {
                http_response_code(503);
                require $errorViewPath;
                exit();
            }
            die("DB Connection Error: Hệ thống đang bảo trì hoặc mất kết nối. Vui lòng thử lại sau.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Override session-based RLS role for system/cron tasks
     * Must be called before getConnection() the first time
     */
    public function setSystemRole($role) {
        $this->systemRole = $role;
    }

    public function getConnection() {
        $this->ensureRLSContext();
        return $this->pdo;
    }

    private function ensureRLSContext() {
        if ($this->rlsContextSet) return;
        
        $role = $this->systemRole ?? 'public';
        $cccd = '';

        if (!$this->systemRole && session_status() === PHP_SESSION_ACTIVE) {
            if (isset($_SESSION['cccd'])) {
                $cccd = $_SESSION['cccd'];
            }
            
            if (isset($_SESSION['admin_id'])) {
                $role = 'admin';
            } elseif (isset($_SESSION['user_id'])) {
                $role = 'candidate';
            }
        }

        try {
            // Set CCCD
            $stmt = $this->pdo->prepare("SELECT set_config('app.current_cccd', ?, false)");
            $stmt->execute([$cccd]);
            
            // Set Role
            $stmt = $this->pdo->prepare("SELECT set_config('app.current_role', ?, false)");
            $stmt->execute([$role]);
            
            $this->rlsContextSet = true;
        } catch (PDOException $e) {
            // Fail silently or log error
            error_log("RLS Context Error: " . $e->getMessage());
        }
    }
}
