<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class Combination extends Model {
    protected $table = 'dm_to_hop';

    public function getAllCombinations() {
        $sql = "SELECT c.*, 
                m1.ten_mon as mon1_ten, m1.ma_mon as mon1_ma,
                m2.ten_mon as mon2_ten, m2.ma_mon as mon2_ma,
                m3.ten_mon as mon3_ten, m3.ma_mon as mon3_ma
                FROM {$this->table} c
                LEFT JOIN dm_mon m1 ON c.mon_1_id = m1.id
                LEFT JOIN dm_mon m2 ON c.mon_2_id = m2.id
                LEFT JOIN dm_mon m3 ON c.mon_3_id = m3.id
                ORDER BY c.ma_to_hop";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE ma_to_hop = ?");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createCombination($data) {
        $sql = "INSERT INTO {$this->table} (ma_to_hop, mon_1_id, mon_2_id, mon_3_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['ma_to_hop'],
            $data['mon_1_id'],
            $data['mon_2_id'],
            $data['mon_3_id']
        ]);
    }

    public function updateCombination($id, $data) {
        $sql = "UPDATE {$this->table} SET ma_to_hop = ?, mon_1_id = ?, mon_2_id = ?, mon_3_id = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['ma_to_hop'],
            $data['mon_1_id'],
            $data['mon_2_id'],
            $data['mon_3_id'],
            $id
        ]);
    }

    public function deleteCombination($id) {
         $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
         return $stmt->execute([$id]);
    }
}
