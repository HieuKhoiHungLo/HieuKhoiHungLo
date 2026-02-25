<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class AcademicRecord extends \App\Core\Model {
    protected $table = 'ket_qua_hoc_tap';

    public function __construct() {
        parent::__construct();
    }

    public function getByCCCD($cccd) {
        $sql = "SELECT * FROM {$this->table} WHERE so_cccd = :cccd ORDER BY lop ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cccd' => $cccd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get academic records indexed by grade (10, 11, 12)
     */
    public function getByCCCDIndexed($cccd) {
        $records = $this->getByCCCD($cccd);
        $data = [10 => [], 11 => [], 12 => []];
        foreach ($records as $r) {
            $data[$r['lop']] = $r;
        }
        return $data;
    }

    public function getByCCCDAndGrade($cccd, $grade) {
        $sql = "SELECT * FROM {$this->table} WHERE so_cccd = :cccd AND lop = :grade";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cccd' => $cccd, 'grade' => $grade]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get annual academic summary for grade 12 (used for threshold validation)
     */
    public function getGrade12Summary($cccd) {
        $record = $this->getByCCCDAndGrade($cccd, 12);
        if (!$record) return null;
        return [
            'diem_tb_ca_nam' => $record['diem_tb_ca_nam'] ?? null,
            'hoc_luc_ca_nam' => $record['hoc_luc_ca_nam'] ?? null,
            'hanh_kiem_ca_nam' => $record['hanh_kiem_ca_nam'] ?? null
        ];
    }

    public function save($cccd, $grade, $data) {
        $record = $this->getByCCCDAndGrade($cccd, $grade);
        
        if ($record) {
             // Update
             $fields = [];
             $params = ['cccd' => $cccd, 'grade' => $grade];
             foreach ($data as $key => $value) {
                 $fields[] = "$key = :$key";
                 $params[$key] = $value;
             }
             $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE so_cccd = :cccd AND lop = :grade";
        } else {
             // Insert
             $data['so_cccd'] = $cccd;
             $data['lop'] = $grade;
             
             $cols = implode(', ', array_keys($data));
             $vals = ':' . implode(', :', array_keys($data));
             
             $sql = "INSERT INTO {$this->table} ($cols) VALUES ($vals)";
             $params = $data;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Save batch of annual scores for all 3 grades (TT06/2026 format)
     * $items = [10 => ['diem_toan_cn' => 8.5, ...], 11 => [...], 12 => [...]]
     */
    public function saveBatch($cccd, $items) {
        $this->db->beginTransaction();
        try {
            foreach ($items as $grade => $gradeData) {
                if (!in_array($grade, [10, 11, 12])) continue;
                if (empty($gradeData)) continue;
                
                $saveData = [];
                
                // Subject scores (annual average per subject)
                $subjects = ['toan', 'van', 'ngoai_ngu', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd', 'cong_nghe', 'tin_hoc'];
                foreach ($subjects as $sub) {
                    $val = $gradeData[$sub] ?? null;
                    if ($val !== '' && $val !== null) {
                        if (!is_numeric($val) || $val < 0 || $val > 10) {
                            throw new \Exception("Điểm không hợp lệ lớp $grade môn $sub");
                        }
                        $saveData["diem_{$sub}_cn"] = (float)$val;
                    }
                }
                
                // Summary fields (annual)
                if (isset($gradeData['diem_tb']) && $gradeData['diem_tb'] !== '') {
                    $saveData['diem_tb_ca_nam'] = (float)$gradeData['diem_tb'];
                }
                if (isset($gradeData['hoc_luc']) && $gradeData['hoc_luc'] !== '') {
                    $saveData['hoc_luc_ca_nam'] = $gradeData['hoc_luc'];
                }
                if (isset($gradeData['hanh_kiem']) && $gradeData['hanh_kiem'] !== '') {
                    $saveData['hanh_kiem_ca_nam'] = $gradeData['hanh_kiem'];
                }
                
                if (!empty($saveData)) {
                    $this->save($cccd, $grade, $saveData);
                }
            }
            
            // Sync to normalized diem_chi_tiet table for score calculation
            $this->syncToNormalizedTable($cccd);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("saveBatch error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync annual scores to normalized diem_chi_tiet table (type HB_CN_10, HB_CN_11, HB_CN_12)
     */
    private function syncToNormalizedTable($cccd) {
        try {
            // Get Subject Mapping
            $stmt = $this->db->query("SELECT id, ma_mon FROM dm_mon WHERE loai_mon = 'Mon_hoc_ba'");
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Map ma_mon to column suffix in ket_qua_hoc_tap
            $monToCol = [
                'toan' => 'toan', 'van' => 'van', 'ngoai_ngu' => 'ngoai_ngu',
                'ly' => 'ly', 'hoa' => 'hoa', 'sinh' => 'sinh',
                'su' => 'su', 'dia' => 'dia', 'gdcd' => 'gdcd',
                'cong_nghe' => 'cong_nghe', 'tin_hoc' => 'tin_hoc'
            ];

            $insertValues = [];
            $insertParams = [];

            // Delete old scores for these grades in a single query
            $this->db->prepare("DELETE FROM diem_chi_tiet WHERE so_cccd = ? AND loai_diem IN ('HB_CN_10', 'HB_CN_11', 'HB_CN_12')")
                ->execute([$cccd]);

            foreach ([10, 11, 12] as $grade) {
                $loaiDiem = "HB_CN_$grade";
                $record = $this->getByCCCDAndGrade($cccd, $grade);
                if (!$record) continue;

                foreach ($subjects as $s) {
                    $colKey = $monToCol[$s['ma_mon']] ?? null;
                    if (!$colKey) continue;
                    
                    $dbCol = "diem_{$colKey}_cn";
                    $score = $record[$dbCol] ?? null;
                    
                    if ($score !== null && $score !== '') {
                        $insertValues[] = "(?, ?, ?, ?)";
                        $insertParams[] = $cccd;
                        $insertParams[] = $s['id'];
                        $insertParams[] = $loaiDiem;
                        $insertParams[] = $score;
                    }
                }
            }

            if (!empty($insertValues)) {
                $insertSql = "INSERT INTO diem_chi_tiet (so_cccd, mon_id, loai_diem, diem) VALUES " . implode(', ', $insertValues);
                $this->db->prepare($insertSql)->execute($insertParams);
            }
        } catch (\Exception $e) {
            error_log("syncToNormalizedTable failed: " . $e->getMessage());
        }
    }

    public function updateFiles($cccd, $grade, $paths) {
        return $this->save($cccd, $grade, [
            'file_hoc_ba' => $paths['hoc_ba'] ?? null,
            'file_bang_tot_nghiep' => $paths['bang_tot_nghiep'] ?? null
        ]);
    }
}
