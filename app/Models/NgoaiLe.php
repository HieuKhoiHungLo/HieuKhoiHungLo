<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class NgoaiLe extends Model {
    protected $table = 'ngoai_le_xet_tuyen';
    protected $primaryKey = 'id';

    public function getByCCCDAndMajor($sessionId, $cccd, $majorCode) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE dot_tuyen_sinh_id = ? AND so_cccd = ? AND ma_nganh = ?");
        $stmt->execute([$sessionId, $cccd, $majorCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllForSession($sessionId) {
        $stmt = $this->db->prepare("
            SELECT nl.*, ts.ho_va_ten, n.ten_nganh 
            FROM {$this->table} nl
            LEFT JOIN thi_sinh ts ON nl.so_cccd = ts.so_cccd
            LEFT JOIN dm_nganh n ON nl.ma_nganh = n.ma_nganh
            WHERE nl.dot_tuyen_sinh_id = ?
            ORDER BY nl.created_at DESC
        ");
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveRule($sessionId, $cccd, $majorCode, $status, $note) {
        $existing = $this->getByCCCDAndMajor($sessionId, $cccd, $majorCode);
        
        if ($existing) {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET trang_thai_ep_buoc = ?, ghi_chu = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            return $stmt->execute([$status, $note, $existing['id']]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (dot_tuyen_sinh_id, so_cccd, ma_nganh, trang_thai_ep_buoc, ghi_chu) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([$sessionId, $cccd, $majorCode, $status, $note]);
        }
    }

    public function deleteRule($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
