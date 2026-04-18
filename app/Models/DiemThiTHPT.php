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
        $fields = [
            'nam_thi', 'da_co_diem', 'toan', 'van', 'ly', 'hoa', 'sinh', 'su', 'dia', 
            'gdcd', 'tieng_anh', 'tieng_trung', 'ktpl', 'tin_hoc', 'cnnn', 'file_chung_nhan'
        ];
        
        // Use a single query to check and update if needed, or insert
        $existing = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE so_cccd = ?");
        $existing->execute([$cccd]);
        $exists = $existing->fetchColumn();

        $success = false;
        if ($exists) {
            $setClauses = [];
            $params = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $val = $data[$field];
                    if ($val === '') $val = null;
                    if ($field === 'da_co_diem') $val = ($val ? 'true' : 'false');
                    
                    $setClauses[] = "{$field} = ?";
                    $params[] = $val;
                }
            }
            if (empty($setClauses)) return true;

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

        if ($success) {
            $this->syncToNormalizedTable($cccd, $data);
        }

        return $success;
    }

    private function syncToNormalizedTable($cccd, $data) {
        try {
            // Pre-fetch constants
            static $subjectsMap = null;
            if ($subjectsMap === null) {
                $stmt = $this->db->query("SELECT id, cot_diem FROM dm_mon WHERE cot_diem IS NOT NULL");
                $subjectsMap = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // map cot_diem => id
            }

            // Only delete and re-insert if we have relevant data
            $hasData = false;
            foreach ($subjectsMap as $col => $monId) {
                if (isset($data[$col]) && $data[$col] !== null && $data[$col] !== '') {
                    $hasData = true;
                    break;
                }
            }

            if ($hasData) {
                $this->db->prepare("DELETE FROM diem_chi_tiet WHERE so_cccd = ? AND loai_diem = 'THPT'")->execute([$cccd]);
                
                $insertValues = [];
                $insertParams = [];
                foreach ($subjectsMap as $col => $monId) {
                    if (isset($data[$col]) && $data[$col] !== null && $data[$col] !== '') {
                        $insertValues[] = "(?, ?, 'THPT', ?)";
                        $insertParams[] = $cccd;
                        $insertParams[] = $monId;
                        $insertParams[] = $data[$col];
                    }
                }
                
                $insertSql = "INSERT INTO diem_chi_tiet (so_cccd, mon_id, loai_diem, diem) VALUES " . implode(', ', $insertValues);
                $this->db->prepare($insertSql)->execute($insertParams);
            }
        } catch (\Exception $e) {
            error_log("Sync THPT dual write failed: " . $e->getMessage());
        }
    }

    public function upsertBatch(array $scoresData) {
        if (empty($scoresData)) return 0;
        
        $fields = [
            'so_cccd', 'nam_thi', 'da_co_diem', 'toan', 'van', 'ly', 'hoa', 'sinh', 'su', 'dia', 
            'gdcd', 'tieng_anh', 'tieng_trung', 'ktpl', 'tin_hoc', 'cnnn', 'file_chung_nhan'
        ];

        $placeholders = [];
        $values = [];

        foreach ($scoresData as $cccd => $data) {
            $rowPlaceholders = [];
            foreach ($fields as $field) {
                $rowPlaceholders[] = '?';
                if ($field === 'so_cccd') {
                    $values[] = $cccd;
                } else {
                    $val = $data[$field] ?? null;
                    if ($val === '') $val = null;
                    if ($field === 'da_co_diem') $val = ($val ? 'true' : 'false');
                    $values[] = $val;
                }
            }
            $placeholders[] = '(' . implode(',', $rowPlaceholders) . ')';
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES " . implode(', ', $placeholders);
        $sql .= " ON CONFLICT (so_cccd) DO UPDATE SET ";

        $updateCols = [];
        foreach ($fields as $field) {
            if ($field !== 'so_cccd') {
                $updateCols[] = "$field = EXCLUDED.$field";
            }
        }
        $sql .= implode(', ', $updateCols);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        // Bulk sync to diem_chi_tiet
        $this->syncBatchToNormalizedTable($scoresData);

        return count($scoresData);
    }

    public function syncBatchToNormalizedTable(array $scoresData) {
        if (empty($scoresData)) return;

        static $subjectsMap = null;
        if ($subjectsMap === null) {
            $stmt = $this->db->query("SELECT cot_diem, id FROM dm_mon WHERE cot_diem IS NOT NULL");
            $subjectsMap = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        $cccdsWithData = [];
        $insertValues = [];
        $insertParams = [];

        foreach ($scoresData as $cccd => $data) {
            $hasData = false;
            foreach ($subjectsMap as $col => $monId) {
                if (isset($data[$col]) && $data[$col] !== null && $data[$col] !== '') {
                    $hasData = true;
                    $insertValues[] = "(?, ?, 'THPT', ?)";
                    $insertParams[] = $cccd;
                    $insertParams[] = $monId;
                    $insertParams[] = $data[$col];
                }
            }
            if ($hasData) {
                $cccdsWithData[] = $cccd;
            }
        }

        if (!empty($cccdsWithData)) {
            try {
                // Delete existing for these CCCDs
                $placeholders = implode(',', array_fill(0, count($cccdsWithData), '?'));
                $this->db->prepare("DELETE FROM diem_chi_tiet WHERE so_cccd IN ($placeholders) AND loai_diem = 'THPT'")->execute($cccdsWithData);

                if (!empty($insertValues)) {
                    // Postgres has a limit of 65535 parameters per statement. 
                    // Chunks of 500 candidates max will have ~ 500 * 9 = 4500 params, which is safe.
                    $insertSql = "INSERT INTO diem_chi_tiet (so_cccd, mon_id, loai_diem, diem) VALUES " . implode(', ', $insertValues);
                    $this->db->prepare($insertSql)->execute($insertParams);
                }
            } catch (\Exception $e) {
                error_log("Batch Sync THPT dual write failed: " . $e->getMessage());
            }
        }
    }
}
