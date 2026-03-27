<?php
namespace App\Models;

use App\Core\Model;

class CertificateRule extends Model {
    protected $table = 'cau_hinh_chung_chi';

    public function all() {
        return $this->db->query("SELECT r.*, m.ten_mon 
                                FROM {$this->table} r
                                LEFT JOIN dm_mon m ON r.mon_id = m.id
                                ORDER BY r.loai_chung_chi ASC, r.muc_diem_tu ASC")
                        ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (loai_chung_chi, muc_diem_tu, muc_diem_den, diem_quy_doi, mon_id) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['loai_chung_chi'],
            $data['muc_diem_tu'],
            $data['muc_diem_den'] ?? null,
            $data['diem_quy_doi'],
            $data['mon_id']
        ]);
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                loai_chung_chi = ?, 
                muc_diem_tu = ?, 
                muc_diem_den = ?, 
                diem_quy_doi = ?, 
                mon_id = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['loai_chung_chi'],
            $data['muc_diem_tu'],
            $data['muc_diem_den'] ?? null,
            $data['diem_quy_doi'],
            $data['mon_id'],
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
