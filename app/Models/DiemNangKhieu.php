<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class DiemNangKhieu extends Model {
    protected $table = 'diem_nang_khieu';
    protected $primaryKey = 'id';

    public function getByCCCD($cccd) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (so_cccd, sbd, diem, ghi_chu, ma_mon) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        try {
            return $stmt->execute([
                $data['so_cccd'],
                $data['sbd'],
                $data['diem'],
                $data['ghi_chu'] ?? '',
                $data['ma_mon'] ?? 'NK1'
            ]);
        } catch (\PDOException $e) {
            echo "SQL Error: " . $e->getMessage() . "\n";
            echo "SQL: $sql\n";
            print_r($data);
            throw $e;
        }
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET sbd = ?, diem = ?, ghi_chu = ?, ma_mon = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['sbd'],
            $data['diem'],
            $data['ghi_chu'] ?? '',
            $data['ma_mon'] ?? 'NK1',
            $id
        ]);
    }
    
    public function saveScore($cccd, $sbd, $score, $note = '', $maMon = 'NK1') {
        $existing = $this->getByCCCD($cccd);
        
        // Prepare Data
        $data = [
            'so_cccd' => $cccd,
            'sbd' => $sbd,
            'diem' => $score,
            'ghi_chu' => $note,
            'ma_mon' => $maMon
        ];

        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            return $this->create($data);
        }
    }
    
    public function getDb() {
        return $this->db;
    }
}
