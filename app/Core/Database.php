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
            // Show detailed database error on local machine for easy debugging
            $isLocal = isset($_SERVER['HTTP_HOST']) && 
                       (str_contains($_SERVER['HTTP_HOST'], 'localhost') || str_contains($_SERVER['HTTP_HOST'], '127.0.0.1'));
            if ($isLocal) {
                die("<div style='padding:20px;background:#fff5f5;border:1px solid #ffc1c1;color:#c00;font-family:sans-serif;border-radius:6px;margin:20px;'>"
                  . "<h3>[Local Debug] Lỗi kết nối cơ sở dữ liệu:</h3>"
                  . "<p><b>Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>"
                  . "<p>Vui lòng kiểm tra lại file <b>.env</b> hoặc đảm bảo đã bật extension <b>pdo_pgsql</b> trong file <b>php.ini</b> của XAMPP.</p>"
                  . "</div>");
            }

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
            // Combine CCCD, Role and Timezone into a single query to reduce network round-trips.
            // Critical optimization for remote databases (e.g. Supabase) where latency is high.
            $stmt = $this->pdo->prepare("SELECT 
                set_config('app.current_cccd', ?, false), 
                set_config('app.current_role', ?, false),
                set_config('timezone', 'Asia/Ho_Chi_Minh', false)");
            $stmt->execute([$cccd, $role]);
            
            $this->rlsContextSet = true;
        } catch (PDOException $e) {
            // Fail silently or log error
            error_log("RLS Context Error: " . $e->getMessage());
        }
    }
}
