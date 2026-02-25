<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class DiemThiTHPT extends Model {
    protected $table = 'diem_thi_thpt';

    public function getByCCCD($cccd) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function save($cccd, $data) {
        // First check if exists
        $existing = $this->getByCCCD($cccd);
        
        $fields = [
            'nam_thi', 'da_co_diem', 'toan', 'van', 'ly', 'hoa', 'sinh', 'su', 'dia', 
            'gdcd', 'tieng_anh', 'tieng_trung', 'ktpl', 'tin_hoc', 'cnnn', 'file_chung_nhan'
        ];
        
        $success = false;
        if ($existing) {
            $setClauses = [];
            $params = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $val = $data[$field];
                    // Handle empty strings for numeric/boolean fields
                    if ($val === '') $val = null;
                    if ($field === 'da_co_diem') $val = ($val ? 'true' : 'false');
                    
                    $setClauses[] = "{$field} = ?";
                    $params[] = $val;
                }
            }
            $params[] = $cccd;
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . ", ngay_cap_nhat = NOW() WHERE so_cccd = ?";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);
        } else {
            $cols = ['so_cccd'];
            $placeholders = ['?'];
            $params = [$cccd];
            
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $val = $data[$field];
                    if ($val === '') $val = null;
                    if ($field === 'da_co_diem') $val = ($val ? 'true' : 'false');

                    $cols[] = $field;
                    $placeholders[] = '?';
                    $params[] = $val;
                }
            }
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);
        }

        // --- DUAL WRITE TO NORMALIZED TABLE ---
        if ($success) {
            $this->syncToNormalizedTable($cccd, $data);
        }

        return $success;
    }

    private function syncToNormalizedTable($cccd, $data) {
        try {
            $stmt = $this->db->query("SELECT id, cot_diem FROM dm_mon WHERE cot_diem IS NOT NULL");
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $mapColToId = [];
            foreach ($subjects as $s) $mapColToId[$s['cot_diem']] = $s['id'];

            // Delete old THPT scores for this candidate to avoid duplicates/stale data
            $this->db->prepare("DELETE FROM diem_chi_tiet WHERE so_cccd = ? AND loai_diem = 'THPT'")->execute([$cccd]);

            $insertValues = [];
            $insertParams = [];

            foreach ($mapColToId as $col => $monId) {
                if (isset($data[$col]) && $data[$col] !== null && $data[$col] !== '') {
                    $insertValues[] = "(?, ?, 'THPT', ?)";
                    $insertParams[] = $cccd;
                    $insertParams[] = $monId;
                    $insertParams[] = $data[$col];
                }
            }

            if (!empty($insertValues)) {
                $insertSql = "INSERT INTO diem_chi_tiet (so_cccd, mon_id, loai_diem, diem) VALUES " . implode(', ', $insertValues);
                $this->db->prepare($insertSql)->execute($insertParams);
            }
        } catch (\Exception $e) {
            // Silent fail for dual write to not block main flow? 
            // Or log it. For now, we just continue.
            error_log("Dual write failed: " . $e->getMessage());
        }
    }
}
