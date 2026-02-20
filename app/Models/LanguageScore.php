<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class LanguageScore extends Model {
    protected $table = 'diem_chung_chi_ngoai_ngu';

    public function getByCCCD($cccd) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE so_cccd = ?");
            $stmt->execute([$cccd]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Table may not exist - return null gracefully
            return null;
        }
    }

    public function save($data) {
        $cccd = $data['so_cccd'];
        $existing = $this->getByCCCD($cccd);
        
        $fields = [
            'so_cccd', 'loai_chung_chi', 'diem_goc', 'diem_quy_doi', 'minh_chung', 'ngay_cap', 'noi_cap'
        ];
        
        if ($existing) {
            $setClauses = [];
            $params = [];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                     $setClauses[] = "{$field} = ?";
                     $params[] = $data[$field];
                }
            }
            // Where
            $params[] = $cccd;
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . ", updated_at = CURRENT_TIMESTAMP WHERE so_cccd = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } else {
             $cols = [];
             $placeholders = [];
             $params = [];
             
             foreach ($fields as $field) {
                 if (isset($data[$field])) {
                     $cols[] = $field;
                     $placeholders[] = '?';
                     $params[] = $data[$field];
                 }
             }
             
             $sql = "INSERT INTO {$this->table} (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
             $stmt = $this->db->prepare($sql);
             return $stmt->execute($params);
        }
    }

    public function deleteByCCCD($cccd) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE so_cccd = ?");
        return $stmt->execute([$cccd]);
    }
}
