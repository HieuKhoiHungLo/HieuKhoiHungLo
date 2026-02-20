<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class AptitudeScore extends Model {
    protected $table = 'diem_nang_khieu';

    public function getScore($cccd, $col) {
        // $col should be nk1, nk2...
        // Validate col name to prevent SQL injection
        if (!preg_match('/^nk[1-9]$/', $col)) return 0;
        
        $stmt = $this->db->prepare("SELECT {$col} FROM {$this->table} WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        return (float)$stmt->fetchColumn();
    }

    public function saveScore($cccd, $col, $diem) {
        if (!preg_match('/^nk[1-9]$/', $col)) return false;

        // Check if record exists
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $sql = "UPDATE {$this->table} SET {$col} = ? WHERE so_cccd = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$diem, $cccd]);
        } else {
            $sql = "INSERT INTO {$this->table} (so_cccd, {$col}) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$cccd, $diem]);
        }
    }
    
    public function getAllScores($cccd) {
         $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE so_cccd = ?");
         $stmt->execute([$cccd]);
         return $stmt->fetch(PDO::FETCH_ASSOC); // Fetch single row
    }
}
