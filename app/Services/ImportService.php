<?php
namespace App\Services;

use App\Repositories\ImportRepository;
use App\Repositories\ThiSinhRepository;
use PDO;
use App\Models\DiemThiTHPT;
use App\Core\Database;
use App\Repositories\MasterDataRepository;

class ImportService {
    protected $importRepo;
    protected $thiSinhRepo;
    protected $diemThiModel;
    protected $masterDataRepo;
    protected $db;

    public function __construct() {
        $this->importRepo = new ImportRepository();
        $this->thiSinhRepo = new ThiSinhRepository();
        $this->diemThiModel = new DiemThiTHPT();
        $this->masterDataRepo = new MasterDataRepository(); // Assumption
        $this->db = Database::getInstance()->getConnection();
    }

    public function parseCandidates($filePath, $batchId, $adminId) {
        if (!file_exists($filePath)) {
            return ['status' => false, 'message' => 'File not found'];
        }

        $handle = fopen($filePath, "r");
        $header = fgetcsv($handle); 
        
        $count = 0;
        $success = 0;
        $errors = [];

        $this->db->beginTransaction();

        try {
            $schoolCodes = $this->getSchoolCodes();

            while (($row = fgetcsv($handle)) !== false) {
                // If row is empty or too short, skip
                if (count($row) < 30) continue;
                
                $count++;
                
                // Index Mapping based on Google Sheet Bang 1
                $sbd = trim($row[1] ?? '');
                $cccd = trim($row[3] ?? ''); // DDCN
                if (empty($cccd)) {
                    $errors[] = "Row $count: Missing CCCD";
                    continue; 
                }

                // Prepare Profile Data
                $profileData = [
                    'so_cccd' => $cccd,
                    'so_bao_danh' => $sbd,
                    'ho_va_ten' => trim($row[2] ?? ''),
                    'ngay_sinh' => $this->parseDate(trim($row[4] ?? '')), // DD/MM/YYYY
                    'gioi_tinh' => $this->parseGender(trim($row[5] ?? '')),
                    'doi_tuong_uu_tien' => trim($row[6] ?? ''),
                    'khu_vuc_uu_tien' => trim($row[7] ?? ''),
                    'nam_tot_nghiep' => (int)trim($row[8] ?? date('Y')), // nam_tn_thpt
                    'hoc_luc' => trim($row[9] ?? ''),
                    'hanh_kiem' => trim($row[10] ?? ''),
                    'ma_tinh_ho_khau' => trim($row[14] ?? ''), // ma_tinh_tt
                    'ma_huyen_thuong_tru' => trim($row[16] ?? ''), // ma_huyen_tt
                    'ma_xa_thuong_tru' => trim($row[18] ?? ''), // ma_xa_tt
                    'nguon_du_lieu' => 'bo_gddt'
                ];

                // School Logic: ma_tinh_lop12 (20) + ma_truong_lop12 (21)
                $maTinhLop12 = trim($row[20] ?? '');
                $maTruongLop12 = trim($row[21] ?? '');
                // Ensure maTruongLop12 is 3 chars? Sometimes it's '71', needs to be '071'? The DB might handle it, or we just concat.
                // Assuming it matches dm_truong_thpt format directly (e.g. 16071)
                $maTruongLop12Padded = str_pad($maTruongLop12, 3, '0', STR_PAD_LEFT);
                $fullSchoolCode = $maTinhLop12 . $maTruongLop12Padded;
                
                if (in_array($fullSchoolCode, $schoolCodes)) {
                    $profileData['ma_truong_lop_12'] = $fullSchoolCode;
                } else if (in_array($maTinhLop12 . $maTruongLop12, $schoolCodes)) {
                    $profileData['ma_truong_lop_12'] = $maTinhLop12 . $maTruongLop12;
                } else {
                    $profileData['ma_truong_lop_12'] = null;
                }

                if (!$this->thiSinhRepo->findByCCCD($cccd)) {
                    $profileData['email'] = $cccd . '@import.local'; // Dummy
                    $profileData['so_dien_thoai'] = '';
                    $profileData['mat_khau'] = password_hash($cccd, PASSWORD_DEFAULT); // Default password = CCCD
                }

                $this->thiSinhRepo->saveImportedCandidate($profileData);

                // Prepare THPT Score Data
                // toan (23), van (24), ly (25), hoa (26), sinh (27), su (28), dia (29), gdcd (30), 
                // ngoai_ngu (31), ma_mon_nn (32)
                
                $scores = [
                    'nam_thi' => $batchId ? 2026 : date('Y'),
                    'toan' => $this->parseFloat($row[23] ?? ''),
                    'van' => $this->parseFloat($row[24] ?? ''),
                    'ly' => $this->parseFloat($row[25] ?? ''),
                    'hoa' => $this->parseFloat($row[26] ?? ''),
                    'sinh' => $this->parseFloat($row[27] ?? ''),
                    'su' => $this->parseFloat($row[28] ?? ''),
                    'dia' => $this->parseFloat($row[29] ?? ''),
                    'gdcd' => $this->parseFloat($row[30] ?? ''),
                    'cnnn' => $this->parseFloat($row[36] ?? ''), 
                    'ktpl' => $this->parseFloat($row[33] ?? ''), 
                    'tin_hoc' => $this->parseFloat($row[34] ?? '') 
                ];

                $nnScore = $this->parseFloat($row[31] ?? ''); // 31: NN
                $maMonNN = trim($row[32] ?? ''); // 32: Ma mon NN
                
                if ($maMonNN == 'N1') $scores['tieng_anh'] = $nnScore;
                if ($maMonNN == 'N4') $scores['tieng_trung'] = $nnScore;
                if ($maMonNN == 'N3') $scores['tieng_phap'] = $nnScore;
                if ($maMonNN == 'N6') $scores['tieng_nhat'] = $nnScore;

                $this->diemThiModel->save($cccd, $scores);
                $success++;
            }
            
            $this->db->commit();
            
            // Log Import
            $this->importRepo->logImport(basename($filePath), 'candidates', $count, $adminId);

            return ['status' => true, 'count' => $count, 'success' => $success, 'errors' => $errors];

        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("ImportService::parseCandidates Exception: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage(), 'errors' => $errors];
        } finally {
            fclose($handle);
        }
    }

    private function getSchoolCodes() {
        $stmt = $this->db->query("SELECT ma_truong FROM dm_truong_thpt");
        if (!$stmt) {
            error_log("ImportService::getSchoolCodes Error: " . print_r($this->db->errorInfo(), true));
            return [];
        }
        $codes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $codes[] = $row['ma_truong'];
        }
        return $codes;
    } // End of getSchoolCodes

    private function getMajors() {
        $stmt = $this->db->query("SELECT ma_nganh FROM dm_nganh");
        if (!$stmt) {
             error_log("ImportService::getMajors Error: " . print_r($this->db->errorInfo(), true));
             return [];
        }
        $codes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $codes[] = $row['ma_nganh'];
        }
        return $codes;
    }

    private function parseDate($str) {
        if (empty($str)) return null;
        // Assume DD/MM/YYYY
        $parts = explode('/', $str);
        if (count($parts) == 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        return null;
    }

    private function parseGender($str) {
        // MOET: 0=Nu, 1=Nam.
        // DB stores strings: 'Nam', 'Nữ'
        return $str === '1' ? 'Nam' : 'Nữ';
    }

    public function parseApplications($filePath, $batchId, $adminId, $targetSchoolCode) {
        if (!file_exists($filePath)) {
            return ['status' => false, 'message' => 'File not found'];
        }

        $handle = fopen($filePath, "r");
        fgetcsv($handle); // Header

        $count = 0;
        $success = 0;
        $errors = [];
        
        $this->db->beginTransaction();

        try {
            // Load Majors for Validation
            $majors = $this->getMajors();

            while (($row = fgetcsv($handle)) !== false) {
                $count++;
                // Col 2: So DDCN (Index 1)
                // Col 4: Ma Truong (Index 3)
                // Col 6: Ma Xet Tuyen (Index 5) - Major Code
                // Col 14: Ma THM (Index 13) - Combo Code
                // Col 3: Thu Tu NV (Index 2)

                $cccd = trim($row[1] ?? '');
                // Dữ liệu CSV Bảng 3 bị dư 1 số 0 ở đầu ví dụ "026307014381" (13 ký tự thay vì 12)
                if (strlen($cccd) == 13 && strpos($cccd, '0') === 0) {
                    $cccd = substr($cccd, 1);
                }

                $schoolCode = trim($row[3] ?? '');
                
                if ($schoolCode !== $targetSchoolCode) {
                    continue; // Skip other schools
                }

                if (empty($cccd)) {
                    $errors[] = "Row $count: Missing CCCD";
                    continue;
                }

                $majorCode = trim($row[5] ?? '');
                $comboCode = trim($row[13] ?? '');
                $priority = (int)trim($row[2] ?? 0);

                if (!in_array($majorCode, $majors)) {
                    $errors[] = "Row $count: Major $majorCode not found";
                }

                $data = [
                    'so_cccd' => $cccd,
                    'ma_nganh' => $majorCode,
                    'ma_to_hop' => $comboCode, 
                    'thu_tu_nguyen_vong' => $priority,
                    'thu_tu_nv_bo' => $priority,
                    'dot_tuyen_sinh_id' => $batchId,
                    'nguon_du_lieu' => 'bo_gddt',
                    'trang_thai' => 'DaNop' 
                ];
                
                $this->saveApplication($data);
                $success++;
            }
            
            $this->db->commit();
            $this->importRepo->logImport(basename($filePath), 'applications', $count, $adminId);

            return ['status' => true, 'count' => $count, 'success' => $success, 'errors' => $errors];

        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("ImportService::parseApplications Exception: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage(), 'errors' => $errors];
        } finally {
            fclose($handle);
        }
    }

    public function parseTranscripts($filePath, $batchId, $adminId) {
        if (!file_exists($filePath)) {
            return ['status' => false, 'message' => 'File not found'];
        }

        $handle = fopen($filePath, "r");
        $header = fgetcsv($handle); 
        
        $count = 0;
        $success = 0;
        $errors = [];

        $this->db->beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 50) continue;
                $count++;
                
                $cccd = trim($row[1] ?? '');
                if (strlen($cccd) == 13 && strpos($cccd, '0') === 0) {
                    $cccd = substr($cccd, 1);
                }

                if (empty($cccd)) {
                    $errors[] = "Row $count: Missing CCCD";
                    continue; 
                }

                $lop = trim($row[5] ?? ''); // Lớp 10, 11, 12
                if (!in_array($lop, ['10', '11', '12'])) {
                    continue; // Skip invalid rows
                }

                // Điểm Tổng kết Cả Năm (CN) theo ánh xạ Bảng 9
                // Toan CN(25), Van CN(28), Ly CN(31), Hoa CN(34), Sinh CN(37)
                // Su CN(40), Dia CN(43), GDCD CN(46), KTPL(49), Tin(52)
                // Ngoai ngu CN(61) -> Tieng Anh
                
                $scores = [];
                $scores['toan_' . $lop] = $this->parseFloat($row[25] ?? '');
                $scores['van_' . $lop] = $this->parseFloat($row[28] ?? '');
                $scores['ly_' . $lop] = $this->parseFloat($row[31] ?? '');
                $scores['hoa_' . $lop] = $this->parseFloat($row[34] ?? '');
                $scores['sinh_' . $lop] = $this->parseFloat($row[37] ?? '');
                $scores['su_' . $lop] = $this->parseFloat($row[40] ?? '');
                $scores['dia_' . $lop] = $this->parseFloat($row[43] ?? '');
                $scores['gdcd_' . $lop] = $this->parseFloat($row[46] ?? '');
                $scores['ktpl_' . $lop] = $this->parseFloat($row[49] ?? '');
                $scores['tin_hoc_' . $lop] = $this->parseFloat($row[52] ?? '');
                $scores['tieng_anh_' . $lop] = $this->parseFloat($row[61] ?? '');

                // Lọc bỏ môn bị NULL để không đè dữ liệu cũ
                $scores = array_filter($scores, function($val) {
                    return $val !== null;
                });

                if (empty($scores)) continue;

                // Cập nhật/Thêm mới vào DB bảng hoc_ba_thpt
                $this->upsertTranscript($cccd, $scores);
                $success++;
            }
            
            $this->db->commit();
            $this->importRepo->logImport(basename($filePath), 'transcripts', $count, $adminId);

            return ['status' => true, 'count' => $count, 'success' => $success, 'errors' => $errors];

        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("ImportService::parseTranscripts Exception: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage(), 'errors' => $errors];
        } finally {
            fclose($handle);
        }
    }

    private function upsertTranscript($cccd, $scores) {
        // Kiểm tra xem đã có học bạ chưa
        $stmt = $this->db->prepare("SELECT id FROM hoc_ba_thpt WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            // Update động theo mảng $scores
            $setClause = [];
            $params = [];
            foreach ($scores as $col => $val) {
                $setClause[] = "$col = ?";
                $params[] = $val;
            }
            $params[] = $cccd;
            
            $sql = "UPDATE hoc_ba_thpt SET " . implode(', ', $setClause) . " WHERE so_cccd = ?";
            $this->db->prepare($sql)->execute($params);
        } else {
            // Insert
            $cols = array_keys($scores);
            $vals = array_values($scores);
            
            $cols[] = 'so_cccd';
            $vals[] = $cccd;
            
            $placeholders = implode(',', array_fill(0, count($cols), '?'));
            $colNames = implode(',', $cols);
            
            $sql = "INSERT INTO hoc_ba_thpt ($colNames) VALUES ($placeholders)";
            $this->db->prepare($sql)->execute($vals);
        }
    }


    
    private function saveApplication($data) {
        // Simple Insert for now. Update if logic changes.
        // Columns: so_cccd, ma_nganh, ma_to_hop, thu_tu_nguyen_vong, dot_tuyen_sinh_id, nguon_du_lieu
        
        // Xóa nguyện vọng cũ dựa trên CCCD, Đợt và Thứ tự NV (để ghi đè bản cập nhật mới nhất từ Bộ)
        $sql = "DELETE FROM nguyen_vong WHERE so_cccd = ? AND dot_tuyen_sinh_id = ? AND (thu_tu_nguyen_vong = ? OR thu_tu_nv_bo = ?)";
        $this->db->prepare($sql)->execute([$data['so_cccd'], $data['dot_tuyen_sinh_id'], $data['thu_tu_nguyen_vong'] ?? 0, $data['thu_tu_nv_bo'] ?? 0]);

        $cols = array_keys($data);
        $vals = array_values($data);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $colNames = implode(',', $cols);
        
        $sql = "INSERT INTO nguyen_vong ($colNames) VALUES ($placeholders)";
        $this->db->prepare($sql)->execute($vals);
    }

    private function parseFloat($str) {
        if ($str === null || $str === '') return null;
        $val = str_replace(',', '.', trim($str));
        return is_numeric($val) ? (float)$val : null;
    }
}
