<?php
namespace App\Services;

use App\Repositories\ImportRepository;
use App\Repositories\ThiSinhRepository;
use PDO;
use App\Models\DiemThiTHPT;
use App\Core\Database;
use App\Repositories\MasterDataRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
        $this->masterDataRepo = new MasterDataRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function parseCandidates($filePath, $batchId, $adminId, $year) {
        if (!file_exists($filePath)) {
            return ['status' => false, 'message' => 'File not found'];
        }

        try {
            $rows = $this->loadData($filePath);
            if (empty($rows)) return ['status' => false, 'message' => 'File is empty or invalid'];

            array_shift($rows); // Skip header

            $count = 0;
            $success = 0;
            $errors = [];

            $this->db->beginTransaction();

            $schoolCodes = $this->getSchoolCodes();
            
            // Pre-fetch valid category codes for validation
            $validProvinces = $this->db->query("SELECT ma_tinh FROM dm_tinh")->fetchAll(PDO::FETCH_COLUMN);
            $validWards = $this->db->query("SELECT ma_xa FROM dm_xa")->fetchAll(PDO::FETCH_COLUMN);
            $validObjects = $this->db->query("SELECT ma_dt FROM dm_doi_tuong")->fetchAll(PDO::FETCH_COLUMN);
            $validAreas = $this->db->query("SELECT ma_kv FROM dm_khu_vuc")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($rows as $row) {
                if (count($row) < 30) continue;
                $count++;
                
                $sbd = trim($row[1] ?? '');
                $cccd = trim($row[3] ?? '');
                if (empty($cccd)) {
                    $errors[] = "Dòng $count: Thiếu CCCD";
                    continue; 
                }

                $maTinh = $this->nullIfEmpty(trim($row[14] ?? ''));
                $maXa = $this->nullIfEmpty(trim($row[18] ?? ''));
                $maDT = $this->nullIfEmpty(trim($row[6] ?? ''));
                $maKV = $this->nullIfEmpty(trim($row[7] ?? ''));

                // Validate codes against database
                if ($maTinh && !in_array($maTinh, $validProvinces)) $maTinh = null;
                if ($maXa && !in_array($maXa, $validWards)) $maXa = null;
                if ($maDT && !in_array($maDT, $validObjects)) $maDT = null;
                if ($maKV && !in_array($maKV, $validAreas)) $maKV = null;

                $profileData = [
                    'so_cccd' => $cccd,
                    'so_bao_danh' => $sbd,
                    'ho_va_ten' => trim($row[2] ?? ''),
                    'ngay_sinh' => $this->parseDate(trim($row[4] ?? '')),
                    'gioi_tinh' => $this->parseGender(trim($row[5] ?? '')),
                    'doi_tuong_uu_tien' => $maDT,
                    'khu_vuc_uu_tien' => $maKV,
                    'nam_tot_nghiep' => (int)trim($row[8] ?? date('Y')),
                    'hoc_luc' => trim($row[9] ?? ''),
                    'hanh_kiem' => trim($row[10] ?? ''),
                    'ma_tinh_ho_khau' => $maTinh,
                    'ma_tinh_thuong_tru' => $maTinh, // Fallback to province code if needed
                    'ma_xa_thuong_tru' => $maXa,
                    'nguon_du_lieu' => 'bo_gddt'
                ];

                $maTinhLop12 = trim($row[20] ?? '');
                $maTruongLop12 = trim($row[21] ?? '');
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
                    $profileData['email'] = $cccd . '@import.local';
                    $profileData['so_dien_thoai'] = '';
                    $profileData['mat_khau'] = password_hash($cccd, PASSWORD_DEFAULT);
                }

                $this->thiSinhRepo->saveImportedCandidate($profileData);

                // Ensure ho_so_xet_tuyen exists for this candidate in this batch
                $stmtCheckHoso = $this->db->prepare("SELECT id FROM ho_so_xet_tuyen WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
                $stmtCheckHoso->execute([$cccd, $batchId]);
                if (!$stmtCheckHoso->fetchColumn()) {
                    $stmtInsHoso = $this->db->prepare("INSERT INTO ho_so_xet_tuyen (so_cccd, dot_tuyen_sinh_id, trang_thai, created_at, updated_at) VALUES (?, ?, 'Chờ duyệt', NOW(), NOW())");
                    $stmtInsHoso->execute([$cccd, $batchId]);
                }

                $scores = [
                    'nam_thi' => $year,
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

                $nnScore = $this->parseFloat($row[31] ?? '');
                $maMonNN = trim($row[32] ?? '');
                
                if ($maMonNN == 'N1') $scores['tieng_anh'] = $nnScore;
                if ($maMonNN == 'N4') $scores['tieng_trung'] = $nnScore;
                if ($maMonNN == 'N3') $scores['tieng_phap'] = $nnScore;
                if ($maMonNN == 'N6') $scores['tieng_nhat'] = $nnScore;

                $this->diemThiModel->save($cccd, $scores);
                $success++;
            }
            
            $this->db->commit();
            $this->importRepo->logImport(basename($filePath), 'candidates', $count, $adminId);

            return ['status' => true, 'count' => $count, 'success' => $success, 'errors' => $errors];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("ImportService::parseCandidates Exception: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage(), 'errors' => $errors];
        }
    }

    private function getSchoolCodes() {
        $stmt = $this->db->query("SELECT ma_truong FROM dm_truong_thpt");
        if (!$stmt) return [];
        $codes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $codes[] = $row['ma_truong'];
        }
        return $codes;
    }

    private function getMajors() {
        $stmt = $this->db->query("SELECT ma_nganh FROM dm_nganh");
        if (!$stmt) return [];
        $codes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $codes[] = $row['ma_nganh'];
        }
        return $codes;
    }

    private function parseDate($str) {
        if (empty($str)) return null;
        $parts = explode('/', $str);
        if (count($parts) == 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        return null;
    }

    private function parseGender($str) {
        return $str === '1' ? 'Nam' : 'Nữ';
    }

    public function parseApplications($filePath, $batchId, $adminId, $targetSchoolCode) {
        if (!file_exists($filePath)) {
            return ['status' => false, 'message' => 'File not found'];
        }

        try {
            $rows = $this->loadData($filePath);
            if (empty($rows)) return ['status' => false, 'message' => 'File empty'];

            array_shift($rows);

            $count = 0;
            $success = 0;
            $errors = [];
            
            $this->db->beginTransaction();
            // Pre-fetch majors for mapping
            $stmtMajors = $this->db->query("SELECT ma_nganh, ten_nganh FROM dm_nganh");
            $majorMap = $stmtMajors->fetchAll(PDO::FETCH_KEY_PAIR);

            foreach ($rows as $row) {
                $count++;
                $cccd = trim($row[1] ?? '');
                if (strlen($cccd) == 13 && strpos($cccd, '0') === 0) {
                    $cccd = substr($cccd, 1);
                }

                $schoolCode = trim($row[3] ?? '');
                if ($schoolCode !== $targetSchoolCode) continue;

                if (empty($cccd)) {
                    $errors[] = "Dòng $count: Thiếu CCCD";
                    continue;
                }

                // Check if candidate has a registration in this batch
                $stmtHoso = $this->db->prepare("SELECT id FROM ho_so_xet_tuyen WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
                $stmtHoso->execute([$cccd, $batchId]);
                $hoSoId = $stmtHoso->fetchColumn();

                if (!$hoSoId) {
                    $errors[] = "Dòng $count (CCCD: $cccd): Thí sinh chưa có hồ sơ trong đợt này. Bỏ qua nguyện vọng.";
                    continue;
                }

                $majorCode = trim($row[5] ?? '');
                $comboCode = trim($row[13] ?? '');
                $priority = (int)trim($row[2] ?? 0);

                if (!isset($majorMap[$majorCode])) {
                    $errors[] = "Dòng $count: Ngành $majorCode không tồn tại trong danh mục";
                }

                $data = [
                    'so_cccd' => $cccd,
                    'ho_so_id' => $hoSoId,
                    'ma_nganh' => $majorCode,
                    'ten_nganh' => $majorMap[$majorCode] ?? '',
                    'to_hop_mon' => $comboCode, 
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
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("ImportService::parseApplications Exception: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage(), 'errors' => $errors];
        }
    }

    public function parseTranscripts($filePath, $batchId, $adminId) {
        if (!file_exists($filePath)) {
            return ['status' => false, 'message' => 'File not found'];
        }

        try {
            $rows = $this->loadData($filePath);
            if (empty($rows)) return ['status' => false, 'message' => 'File error'];

            array_shift($rows);
        
            $count = 0;
            $success = 0;
            $errors = [];

            $this->db->beginTransaction();

            foreach ($rows as $row) {
                if (count($row) < 50) continue;
                $count++;
                
                $cccd = trim($row[1] ?? '');
                if (strlen($cccd) == 13 && strpos($cccd, '0') === 0) {
                    $cccd = substr($cccd, 1);
                }

                if (empty($cccd)) {
                    $errors[] = "Dòng $count: Thiếu CCCD";
                    continue; 
                }

                $lop = trim($row[5] ?? '');
                if (!in_array($lop, ['10', '11', '12'])) continue;

                $scores = [];
                $scores['diem_toan_cn'] = $this->parseFloat($row[25] ?? '');
                $scores['diem_van_cn'] = $this->parseFloat($row[28] ?? '');
                $scores['diem_ly_cn'] = $this->parseFloat($row[31] ?? '');
                $scores['diem_hoa_cn'] = $this->parseFloat($row[34] ?? '');
                $scores['diem_sinh_cn'] = $this->parseFloat($row[37] ?? '');
                $scores['diem_su_cn'] = $this->parseFloat($row[40] ?? '');
                $scores['diem_dia_cn'] = $this->parseFloat($row[43] ?? '');
                $scores['diem_gdcd_cn'] = $this->parseFloat($row[46] ?? '');
                $scores['diem_ktpl_cn'] = $this->parseFloat($row[49] ?? '');
                $scores['diem_tin_hoc_cn'] = $this->parseFloat($row[52] ?? '');
                $scores['diem_ngoai_ngu_cn'] = $this->parseFloat($row[61] ?? '');

                $scores = array_filter($scores, function($val) { return $val !== null; });
                if (empty($scores)) continue;

                $this->upsertTranscript($cccd, $lop, $scores);
                $success++;
            }
            
            $this->db->commit();
            $this->importRepo->logImport(basename($filePath), 'transcripts', $count, $adminId);

            return ['status' => true, 'count' => $count, 'success' => $success, 'errors' => $errors];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("ImportService::parseTranscripts Exception: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage(), 'errors' => $errors];
        }
    }

    private function upsertTranscript($cccd, $lop, $scores) {
        $stmt = $this->db->prepare("SELECT id FROM ket_qua_hoc_tap WHERE so_cccd = ? AND lop = ?");
        $stmt->execute([$cccd, (int)$lop]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $setClause = [];
            $params = [];
            foreach ($scores as $col => $val) {
                $setClause[] = "$col = ?";
                $params[] = $val;
            }
            $params[] = $cccd;
            $params[] = (int)$lop;
            $sql = "UPDATE ket_qua_hoc_tap SET " . implode(', ', $setClause) . " WHERE so_cccd = ? AND lop = ?";
            $this->db->prepare($sql)->execute($params);
        } else {
            $cols = array_keys($scores);
            $vals = array_values($scores);
            $cols[] = 'so_cccd';
            $vals[] = $cccd;
            $cols[] = 'lop';
            $vals[] = (int)$lop;
            
            $placeholders = implode(',', array_fill(0, count($cols), '?'));
            $colNames = implode(',', $cols);
            $sql = "INSERT INTO ket_qua_hoc_tap ($colNames) VALUES ($placeholders)";
            $this->db->prepare($sql)->execute($vals);
        }
    }

    private function saveApplication($data) {
        // Cleanup existing wishes for this candidate in this specific batch and priority level
        $sql = "DELETE FROM nguyen_vong WHERE so_cccd = ? AND dot_tuyen_sinh_id = ? AND (thu_tu_nguyen_vong = ? OR thu_tu_nv_bo = ?)";
        $this->db->prepare($sql)->execute([
            $data['so_cccd'], 
            $data['dot_tuyen_sinh_id'], 
            $data['thu_tu_nguyen_vong'] ?? 0, 
            $data['thu_tu_nv_bo'] ?? 0
        ]);

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

    private function nullIfEmpty($str) {
        $val = trim($str);
        return $val === '' ? null : $val;
    }

    private function loadData($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $rows = [];

        if ($extension === 'csv') {
            $content = file_get_contents($filePath);
            
            // Detect and remove UTF-8 BOM if present
            if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                $content = substr($content, 3);
            }
            
            // Convert from Windows-1258 (Vietnamese) or UTF-16 to UTF-8 if it's not UTF-8
            $encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16', 'Windows-1258', 'ASCII']);
            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }

            $lines = explode("\n", str_replace("\r", "", $content));
            foreach ($lines as $line) {
                if (trim($line) === '') continue;
                $rows[] = str_getcsv($line, ",");
            }
        } else {
            // Re-throw the error with a helpful message if PhpSpreadsheet fails due to PHP version
            try {
                $spreadsheet = IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray(null, true, true, true);
                foreach ($data as $row) {
                    $rows[] = array_values($row);
                }
            } catch (\Throwable $e) {
                if (strpos($e->getMessage(), 'Enum') !== false || strpos($e->getMessage(), 'final') !== false) {
                    throw new \Exception("Phiên bản PHP 8.0 của bạn không tương thích với bộ đọc Excel (.xlsx). Vui lòng lưu file (Save As) dưới dạng .csv và thử lại.");
                }
                throw $e;
            }
        }
        return $rows;
    }
}
