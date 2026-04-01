<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class DiemChungChi extends Model {
    protected $table = 'diem_chung_chi';
    protected $primaryKey = 'id';

    public function getByCCCD($cccd) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (so_cccd, ma_mon, diem, ghi_chu) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        try {
            return $stmt->execute([
                $data['so_cccd'],
                $data['ma_mon'],
                $data['diem'],
                $data['ghi_chu'] ?? ''
            ]);
        } catch (\PDOException $e) {
            echo "SQL Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET ma_mon = ?, diem = ?, ghi_chu = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['ma_mon'],
            $data['diem'],
            $data['ghi_chu'] ?? '',
            $id
        ]);
    }

    public function saveScore($cccd, $maMon, $score, $note = '') {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE so_cccd = ? AND ma_mon = ?");
        $stmt->execute([$cccd, $maMon]);
        $existingId = $stmt->fetchColumn();

        $data = [
            'so_cccd' => $cccd,
            'ma_mon' => $maMon,
            'diem' => $score,
            'ghi_chu' => $note
        ];

        if ($existingId) {
            return $this->update($existingId, $data);
        } else {
            return $this->create($data);
        }
    }

    public function getDb() { return $this->db; }
}
