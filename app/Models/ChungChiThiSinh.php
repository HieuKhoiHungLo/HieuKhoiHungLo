<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ChungChiThiSinh extends Model {
    protected $table = 'chung_chi_thi_sinh';
    protected $primaryKey = 'id';

    public function getByCCCD($cccd) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (so_cccd, loai_chung_chi, diem_chung_chi, file_minh_chung_cc) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        try {
            return $stmt->execute([
                $data['so_cccd'],
                $data['loai_chung_chi'],
                $data['diem_chung_chi'],
                $data['file_minh_chung_cc'] ?? null
            ]);
        } catch (\PDOException $e) {
            echo "SQL Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET loai_chung_chi = ?, diem_chung_chi = ?, file_minh_chung_cc = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['loai_chung_chi'],
            $data['diem_chung_chi'],
            $data['file_minh_chung_cc'] ?? null,
            $id
        ]);
    }
    
    public function getDb() {
        return $this->db;
    }
}
