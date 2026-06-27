<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class DiemChungChi extends Model {
    protected $table = 'diem_chung_chi';
    protected $primaryKey = 'id';

    public function getByCCCD($cccd, $sessionId = null) {
        if ($sessionId === null) {
            $sessionModel = new \App\Models\AdmissionSession();
            $activeSession = $sessionModel->getActiveSession();
            $sessionId = $activeSession ? $activeSession['id'] : null;
        }

        if ($sessionId) {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
            $stmt->execute([$cccd, $sessionId]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE so_cccd = ? AND dot_tuyen_sinh_id IS NULL");
            $stmt->execute([$cccd]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (so_cccd, ma_mon, diem, ghi_chu, dot_tuyen_sinh_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        try {
            return $stmt->execute([
                $data['so_cccd'],
                $data['ma_mon'],
                $data['diem'],
                $data['ghi_chu'] ?? '',
                $data['dot_tuyen_sinh_id'] ?? null
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

    public function saveScore($cccd, $maMon, $score, $note = '', $sessionId = null) {
        $sqlCheck = "SELECT id FROM {$this->table} WHERE so_cccd = ? AND ma_mon = ?";
        $params = [$cccd, $maMon];
        
        if ($sessionId) {
            $sqlCheck .= " AND dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        }

        $stmt = $this->db->prepare($sqlCheck);
        $stmt->execute($params);
        $existingId = $stmt->fetchColumn();

        $data = [
            'so_cccd' => $cccd,
            'ma_mon' => $maMon,
            'diem' => $score,
            'ghi_chu' => $note,
            'dot_tuyen_sinh_id' => $sessionId
        ];

        if ($existingId) {
            return $this->update($existingId, $data);
        } else {
            return $this->create($data);
        }
    }

    public function getDb() { return $this->db; }
}
