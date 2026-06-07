<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class AcademicRecord extends \App\Core\Model
{
    protected $table = 'ket_qua_hoc_tap';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Normalize ratings (Hoc luc / Hanh kiem) to standard Vietnamese codes
     * Theo Thông tư 22/2021/TT-BGDĐT: TỐT, KHÁ, ĐẠT, TRUNG BÌNH, CHƯA ĐẠT
     */
    public function normalizeRating($text)
    {
        if ($text === null || $text === '') return null;

        $raw = mb_strtolower(trim($text), 'UTF-8');

        $map = [
            // TỐT
            'tốt'       => 'TỐT',
            'tot'       => 'TỐT',
            'giỏi'      => 'TỐT',
            'gioi'      => 'TỐT',
            'xuất sắc'  => 'TỐT',
            'xuat sac'  => 'TỐT',
            // KHÁ
            'khá'       => 'KHÁ',
            'kha'       => 'KHÁ',
            // ĐẠT
            'đạt'       => 'ĐẠT',
            'dat'       => 'ĐẠT',
            // TRUNG BÌNH
            'trung bình'  => 'TRUNG BÌNH',
            'trung binh'  => 'TRUNG BÌNH',
            'tb'          => 'TRUNG BÌNH',
            'trungbinh'   => 'TRUNG BÌNH',
            // CHƯA ĐẠT
            'chưa đạt'  => 'CHƯA ĐẠT',
            'chua dat'  => 'CHƯA ĐẠT',
            'chuadat'   => 'CHƯA ĐẠT',
            'yếu'       => 'CHƯA ĐẠT',
            'yeu'       => 'CHƯA ĐẠT',
            'kém'       => 'CHƯA ĐẠT',
            'kem'       => 'CHƯA ĐẠT',
        ];

        return $map[$raw] ?? mb_strtoupper($text, 'UTF-8');
    }

    public function getByCCCD($cccd)
    {
        $sql = "SELECT * FROM {$this->table} WHERE so_cccd = :cccd ORDER BY lop ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cccd' => $cccd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get academic records indexed by grade (10, 11, 12)
     */
    public function getByCCCDIndexed($cccd)
    {
        $records = $this->getByCCCD($cccd);
        $data = [10 => [], 11 => [], 12 => []];
        foreach ($records as $r) {
            $data[$r['lop']] = $r;
        }
        return $data;
    }

    public function getByCCCDAndGrade($cccd, $grade)
    {
        $sql = "SELECT * FROM {$this->table} WHERE so_cccd = :cccd AND lop = :grade";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cccd' => $cccd, 'grade' => $grade]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get annual academic summary for grade 12 (used for threshold validation)
     */
    public function getGrade12Summary($cccd)
    {
        $record = $this->getByCCCDAndGrade($cccd, 12);
        if (!$record) return null;
        return [
            'diem_tb_ca_nam' => $record['diem_tb_ca_nam'] ?? null,
            'hoc_luc_ca_nam' => $record['hoc_luc_ca_nam'] ?? null,
            'hanh_kiem_ca_nam' => $record['hanh_kiem_ca_nam'] ?? null
        ];
    }

    public function save($cccd, $grade, $data)
    {
        $record = $this->getByCCCDAndGrade($cccd, $grade);

        if ($record) {
            // Update
            $fields = [];
            $params = ['cccd' => $cccd, 'grade' => $grade];
            foreach ($data as $key => $value) {
                if (in_array($key, ['hoc_luc_ca_nam', 'hanh_kiem_ca_nam'])) {
                    $value = $this->normalizeRating($value);
                }
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
            
            // Normalize in INSERT as well
            foreach ($data as $key => $value) {
                if (in_array($key, ['hoc_luc_ca_nam', 'hanh_kiem_ca_nam'])) {
                    $data[$key] = $this->normalizeRating($value);
                }
            }
            $params = $data;
        }

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        if ($result) {
            $this->syncToNormalizedTable($cccd);
            try {
                $stmtTouch = $this->db->prepare("UPDATE nguyen_vong SET updated_at = CURRENT_TIMESTAMP WHERE so_cccd = ?");
                $stmtTouch->execute([$cccd]);
            } catch (\Exception $e) {
                error_log("Failed to touch nguyen_vong in AcademicRecord::save: " . $e->getMessage());
            }
        }
        return $result;
    }

    /**
     * Save batch of annual scores for all 3 grades (TT06/2026 format)
     * $items = [10 => ['diem_toan_cn' => 8.5, ...], 11 => [...], 12 => [...]]
     */
    public function saveBatch($cccd, $items)
    {
        try {
            // Pre-fetch all existing records for this candidate to avoid redundant queries in the loop
            $existingRecords = $this->getByCCCDIndexed($cccd);

            foreach ($items as $grade => $gradeData) {
                if (!in_array($grade, [10, 11, 12])) continue;
                if (empty($gradeData)) continue;

                $saveData = [];
                $subjects = ['toan', 'van', 'ngoai_ngu', 'ly', 'hoa', 'sinh', 'su', 'dia', 'gdcd', 'ktpl', 'cong_nghe', 'tin_hoc'];
                
                foreach ($subjects as $sub) {
                    $val = $gradeData[$sub] ?? null;
                    if ($val !== '' && $val !== null) {
                        if (!is_numeric($val) || $val < 0 || $val > 10) {
                            throw new \Exception("Điểm không hợp lệ lớp $grade môn $sub");
                        }
                        $saveData["diem_{$sub}_cn"] = (float)$val;
                    }
                }

                if (isset($gradeData['diem_tb']) && $gradeData['diem_tb'] !== '') {
                    $saveData['diem_tb_ca_nam'] = (float)$gradeData['diem_tb'];
                }
                if (isset($gradeData['hoc_luc']) && $gradeData['hoc_luc'] !== '') {
                    $saveData['hoc_luc_ca_nam'] = $this->normalizeRating($gradeData['hoc_luc']);
                }
                if (isset($gradeData['hanh_kiem']) && $gradeData['hanh_kiem'] !== '') {
                    $saveData['hanh_kiem_ca_nam'] = $this->normalizeRating($gradeData['hanh_kiem']);
                }
                if (isset($gradeData['file_hoc_ba']) && !empty($gradeData['file_hoc_ba'])) {
                    $saveData['file_hoc_ba'] = $gradeData['file_hoc_ba'];
                }

                if (!empty($saveData)) {
                    // Decide between INSERT and UPDATE using pre-fetched data
                    $record = $existingRecords[$grade] ?? null;
                    if ($record && !empty($record)) {
                        $fields = [];
                        $params = ['cccd' => $cccd, 'grade' => $grade];
                        foreach ($saveData as $key => $value) {
                            $fields[] = "$key = :$key";
                            $params[$key] = $value;
                        }
                        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE so_cccd = :cccd AND lop = :grade";
                    } else {
                        $saveData['so_cccd'] = $cccd;
                        $saveData['lop'] = $grade;
                        $cols = implode(', ', array_keys($saveData));
                        $vals = ':' . implode(', :', array_keys($saveData));
                        $sql = "INSERT INTO {$this->table} ($cols) VALUES ($vals)";
                        $params = $saveData;
                    }
                    $this->db->prepare($sql)->execute($params);
                }
            }

            // Sync to normalized diem_chi_tiet table
            $this->syncToNormalizedTable($cccd);

            try {
                $stmtTouch = $this->db->prepare("UPDATE nguyen_vong SET updated_at = CURRENT_TIMESTAMP WHERE so_cccd = ?");
                $stmtTouch->execute([$cccd]);
            } catch (\Exception $e) {
                error_log("Failed to touch nguyen_vong in AcademicRecord::saveBatch: " . $e->getMessage());
            }

            return true;
        } catch (\Exception $e) {
            error_log("saveBatch error: " . $e->getMessage());
            throw $e; // Rethrow to let controller handle rollback
        }
    }

    /**
     * Sync annual scores to normalized diem_chi_tiet table (type HB_CN_10, HB_CN_11, HB_CN_12)
     */
    private function syncToNormalizedTable($cccd)
    {
        try {
            // Map cot_diem to column suffix in ket_qua_hoc_tap
            $monToCol = [
                'toan' => 'toan',
                'van' => 'van',
                'tieng_anh' => 'ngoai_ngu',
                'tieng_trung' => 'ngoai_ngu',
                'ly' => 'ly',
                'hoa' => 'hoa',
                'sinh' => 'sinh',
                'su' => 'su',
                'dia' => 'dia',
                'gdcd' => 'gdcd',
                'ktpl' => 'ktpl',
                'cnnn' => 'cong_nghe',
                'tin_hoc' => 'tin_hoc'
            ];

            // Get Subject Mapping based on our defined columns
            $monCodes = array_keys($monToCol);
            $placeholders = implode(',', array_fill(0, count($monCodes), '?'));
            $stmt = $this->db->prepare("SELECT id, cot_diem FROM dm_mon WHERE cot_diem IN ($placeholders)");
            $stmt->execute($monCodes);
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    $colKey = $monToCol[$s['cot_diem']] ?? null;
                    if (!$colKey) continue;

                    $dbCol = "diem_{$colKey}_cn";
                    $score = $record[$dbCol] ?? null;

                    if ($score !== null && $score !== '') {
                        // DB only supports L12 scores in diem_chi_tiet via check constraint
                        $allowedTypes = ['HB_CN_12', 'HB_HK1_12', 'HB_HK2_12', 'THPT', 'NK', 'CC_NN'];
                        if (!in_array($loaiDiem, $allowedTypes)) continue;

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

    public function updateFiles($cccd, $grade, $paths)
    {
        return $this->save($cccd, $grade, [
            'file_hoc_ba' => $paths['hoc_ba'] ?? null,
            'file_bang_tot_nghiep' => $paths['bang_tot_nghiep'] ?? null
        ]);
    }
}
