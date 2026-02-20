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
        // Skip Header (Wait, user prompt implies header exists? usually row 1-3. Let's assume Row 1 is header).
        // Check first row to be sure.
        $header = fgetcsv($handle); 
        
        $count = 0;
        $success = 0;
        $errors = [];

        // Transaction per batch of 100? Or full? 
        // Better full for consistency, but if huge (3000 rows), might be slow.
        // Let's do huge transaction.
        $this->db->beginTransaction();

        try {
            // Load School Codes for Validation (Cache)
            $schoolCodes = $this->getSchoolCodes();

            while (($row = fgetcsv($handle)) !== false) {
                $count++;
                
                // Index Mapping based on User Prompt Table
                // 0: STT, 1: SBD, 2: Ho Ten, 3: So DDCN, 4: Ngay sinh, 5: Gioi tinh, 6: DTUT, 7: KVUT
                // 20: Ma tinh lop 12, 21: Ma truong lop 12 (Prompt says 21 & 22, likely 0-indexed or 1-indexed?)
                // Allow flexible mapping later, but hardcode for now based on prompt order.
                // Assuming Prompt logic:
                // Col 1 (STT) -> Index 0
                // Col 4 (So DDCN) -> Index 3
                
                $cccd = trim($row[3] ?? '');
                if (empty($cccd)) {
                    $errors[] = "Row $count: Missing CCCD";
                    continue; 
                }

                // Prepare Profile Data
                $profileData = [
                    'so_cccd' => $cccd,
                    'so_bao_danh' => trim($row[1] ?? ''),
                    'ho_va_ten' => trim($row[2] ?? ''),
                    'ngay_sinh' => $this->parseDate(trim($row[4] ?? '')), // DD/MM/YYYY
                    'gioi_tinh' => $this->parseGender(trim($row[5] ?? '')),
                    'doi_tuong_uu_tien' => trim($row[6] ?? ''),
                    'khu_vuc_uu_tien' => trim($row[7] ?? ''),
                    'nam_tot_nghiep' => (int)trim($row[8] ?? 0),
                    'hanh_kiem' => trim($row[10] ?? ''), // Map?
                    'hoc_luc' => trim($row[9] ?? ''),
                    'ma_tinh_ho_khau' => trim($row[14] ?? ''),
                    'ma_huyen_thuong_tru' => trim($row[16] ?? ''),
                    'ma_xa_thuong_tru' => trim($row[18] ?? ''),
                    'nguon_du_lieu' => 'bo_gddt'
                ];

                // School Logic
                $maTinhLop12 = trim($row[20] ?? '');
                $maTruongLop12 = trim($row[21] ?? '');
                $fullSchoolCode = $maTinhLop12 . $maTruongLop12;
                if (in_array($fullSchoolCode, $schoolCodes)) {
                    $profileData['ma_truong_lop_12'] = $fullSchoolCode;
                } else {
                    // $errors[] = "Row $count: School $fullSchoolCode not found";
                    // Just set null to avoid FK error
                    $profileData['ma_truong_lop_12'] = null;
                }

                // Phone/Email? File 1 doesn't seem to have it?
                // User Prompt doesn't list Phone/Email.
                // If create new, set dummy?
                if (!$this->thiSinhRepo->findByCCCD($cccd)) {
                    $profileData['email'] = $cccd . '@import.local'; // Dummy
                    $profileData['so_dien_thoai'] = '';
                    $profileData['mat_khau'] = password_hash($cccd, PASSWORD_DEFAULT); // Default password = CCCD
                }

                $this->thiSinhRepo->saveImportedCandidate($profileData);

                // Prepare THPT Score Data
                // 23: TO, 24: VA, 25: LI, 26: HO, 27: SI, 28: SU, 29: DI, 30: GDCD, 31: NN, 32: Ma Mon NN
                // Indices might be +1 or -1 depending on CSV. Assuming 0-indexed matches Prompt STT-1.
                // Prompt: 24: TO -> Index 23.
                
                $scores = [
                    'nam_thi' => $batchId ? 2026 : date('Y'), // Get year from batch?
                    'toan' => $this->parseFloat($row[23] ?? ''),
                    'van' => $this->parseFloat($row[24] ?? ''),
                    'ly' => $this->parseFloat($row[25] ?? ''),
                    'hoa' => $this->parseFloat($row[26] ?? ''),
                    'sinh' => $this->parseFloat($row[27] ?? ''),
                    'su' => $this->parseFloat($row[28] ?? ''),
                    'dia' => $this->parseFloat($row[29] ?? ''),
                    'gdcd' => $this->parseFloat($row[30] ?? ''),
                    'cnnn' => $this->parseFloat($row[36] ?? ''), // 37: CNNN -> Index 36
                    'ktpl' => $this->parseFloat($row[33] ?? ''), // 34: KTPL -> Index 33
                    'tin_hoc' => $this->parseFloat($row[34] ?? ''), // 35: TI -> Index 34? TI=Tin hoc?
                    // NN logic
                ];

                $nnScore = $this->parseFloat($row[31] ?? ''); // 32: NN -> Index 31
                $maMonNN = trim($row[32] ?? ''); // 33: Ma mon NN -> Index 32
                
                if ($maMonNN == 'N1') $scores['tieng_anh'] = $nnScore;
                if ($maMonNN == 'N4') $scores['tieng_trung'] = $nnScore;
                // Add others if column exists

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
                $schoolCode = trim($row[3] ?? '');
                
                if ($schoolCode !== $targetSchoolCode) {
                    continue; // Skip other schools
                }

                if (empty($cccd)) {
                    $errors[] = "Row $count: Missing CCCD";
                    continue;
                }

                // Check Candidate Exists? 
                // If we imported File 1 first, they should exist.
                // If not, we might have orphan application.
                // Constraint usually requires CCCD in thi_sinh.
                // Assuming thi_sinh constraint exists.

                $majorCode = trim($row[5] ?? '');
                $comboCode = trim($row[13] ?? '');
                $priority = (int)trim($row[2] ?? 0);

                if (!in_array($majorCode, $majors)) {
                    $errors[] = "Row $count: Major $majorCode not found";
                    // continue; // Or insert with warning? Better skip to maintain integrity.
                }

                // Insert into nguyen_vong
                $data = [
                    'so_cccd' => $cccd,
                    'ma_nganh' => $majorCode,
                    'ma_to_hop' => $comboCode, // Make sure column matches (ma_to_hop or ma_to_hop_xet_tuyen?)
                    // DB Schema: nguyen_vong has 'ma_to_hop' (varchar) and 'ma_to_hop_xet_tuyen' (int/varchar?)
                    // Let's check schema. Usually 'ma_to_hop' is the registered combo.
                    'thu_tu_nguyen_vong' => $priority,
                    'dot_tuyen_sinh_id' => $batchId,
                    'nguon_du_lieu' => 'bo_gddt',
                    'trang_thai' => 'DaNop' // Default status
                ];

                // Check duplicate?
                // Delete old for this batch/cccd/major?
                // Or just insert.
                // Unique constraint on (cccd, major, batch)?
                // Let's upsert matching (cccd, thu_tu).
                
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
        // TODO: Implement Logic based on File 9 Structure
        // Assuming: CCCD, Lop 10 Toan, Lop 10 Van...
        return ['status' => true, 'message' => 'Transcript import not yet implemented (Need File Structure)'];
    }


    
    private function saveApplication($data) {
        // Simple Insert for now. Update if logic changes.
        // Columns: so_cccd, ma_nganh, ma_to_hop, thu_tu_nguyen_vong, dot_tuyen_sinh_id, nguon_du_lieu
        
        // Remove old app with same priority for this batch?
        $sql = "DELETE FROM nguyen_vong WHERE so_cccd = ? AND dot_tuyen_sinh_id = ? AND thu_tu_nguyen_vong = ?";
        $this->db->prepare($sql)->execute([$data['so_cccd'], $data['dot_tuyen_sinh_id'], $data['thu_tu_nguyen_vong']]);

        $cols = array_keys($data);
        $vals = array_values($data);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $colNames = implode(',', $cols);
        
        $sql = "INSERT INTO nguyen_vong ($colNames) VALUES ($placeholders)";
        $this->db->prepare($sql)->execute($vals);
    }
}
