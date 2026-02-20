<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class Subject extends Model {
    protected $table = 'dm_mon';

    public function getAllSubjects() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY loai_mon ASC, ma_mon ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE ma_mon = ?");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createSubject($data) {
        // Basic validation
        if (empty($data['ma_mon']) || empty($data['ten_mon'])) return false;

        $sql = "INSERT INTO {$this->table} (ma_mon, ten_mon, loai_mon, cot_diem) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['ma_mon'],
            $data['ten_mon'],
            $data['loai_mon'] ?? 'van_hoa',
            $data['cot_diem'] ?? null
        ]);
    }

    public function updateSubject($id, $data) {
        $sql = "UPDATE {$this->table} SET ma_mon = ?, ten_mon = ?, loai_mon = ?, cot_diem = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['ma_mon'],
            $data['ten_mon'],
            $data['loai_mon'],
            $data['cot_diem'],
            $id
        ]);
    }

    public function deleteSubject($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
