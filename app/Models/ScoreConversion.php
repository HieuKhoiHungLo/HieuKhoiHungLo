<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class ScoreConversion extends Model
{
    protected $table = 'dm_quy_doi_ngoai_ngu';

    public function getAllRules()
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY loai_chung_chi, diem_min ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getConvertedScore($type, $score)
    {
        try {
            $sql = "SELECT diem_quy_doi FROM {$this->table} 
                    WHERE loai_chung_chi = ? 
                    AND diem_min <= ? 
                    AND diem_max >= ? 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$type, $score, $score]);

            $result = $stmt->fetchColumn();
            return $result !== false ? (float)$result : 0;
        } catch (\PDOException $e) {
            error_log("ScoreConversion Error: " . $e->getMessage());
            return 0;
        }
    }

    public function saveRule($data)
    {
        $id = $data['id'] ?? null;

        $fields = [
            'loai_chung_chi' => $data['loai_chung_chi'],
            'diem_min' => (float)$data['diem_min'],
            'diem_max' => (float)$data['diem_max'],
            'diem_quy_doi' => (float)$data['diem_quy_doi'],
            'ghi_chu' => $data['ghi_chu'] ?? null
        ];

        if ($id) {
            $setClauses = [];
            $params = [];
            foreach ($fields as $key => $val) {
                $setClauses[] = "$key = ?";
                $params[] = $val;
            }
            $params[] = $id;

            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } else {
            $cols = implode(', ', array_keys($fields));
            $placeholders = implode(', ', array_fill(0, count($fields), '?'));

            $sql = "INSERT INTO {$this->table} ($cols) VALUES ($placeholders)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(array_values($fields));
        }
    }

    public function deleteRule($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
