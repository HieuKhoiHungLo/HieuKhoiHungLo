<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $pdo;

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
            die("DB Connection Error: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        $this->ensureRLSContext();
        return $this->pdo;
    }

    private function ensureRLSContext() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            try {
                if (isset($_SESSION['cccd'])) {
                    $stmt = $this->pdo->prepare("SELECT set_config('app.current_cccd', ?, false)");
                    $stmt->execute([$_SESSION['cccd']]);
                } else {
                    $this->pdo->exec("SELECT set_config('app.current_cccd', '', false)");
                }
                
                $role = 'public';
                if (isset($_SESSION['admin_id'])) {
                    $role = 'admin';
                } elseif (isset($_SESSION['user_id'])) {
                    $role = 'candidate';
                }
                
                $stmt = $this->pdo->prepare("SELECT set_config('app.current_role', ?, false)");
                $stmt->execute([$role]);
            } catch (PDOException $e) {
                // Fail silently or log error
                error_log("RLS Context Error: " . $e->getMessage());
            }
        }
    }
}
