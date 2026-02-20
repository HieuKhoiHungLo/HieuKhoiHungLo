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
        return $this->pdo;
    }
}
