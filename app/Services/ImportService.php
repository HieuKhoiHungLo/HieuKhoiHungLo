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

    private function updateProgress($adminId, $current, $total, $message = '') {
        $logDir = __DIR__ . '/../../../storage/logs';
        if (!is_dir($logDir)) mkdir($logDir, 0777, true);
        
        $status = [
            'current' => $current,
            'total' => $total,
            'percent' => $total > 0 ? round(($current / $total) * 100) : 0,
            'message' => $message,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($logDir . "/import_progress_{$adminId}.json", json_encode($status));
    }

    public function parseCandidates($filePath, $batchId, $adminId, $year) {
        if (!file_exists($filePath)) {
            return ['status' => false, 'message' => 'File not found'];
        }

        try {
            $rows = $this->loadData($filePath);
            
            if (empty($rows)) return ['status' => false, 'message' => 'File is empty or invalid'];

            array_shift($rows); // Skip header
            $totalRows = count($rows);
            $this->updateProgress($adminId, 0, $totalRows, 'Bắt đầu xử lý dữ liệu Thí sinh...');
            $success = 0;
            $errors = [];

            $this->db->beginTransaction();

            $schoolCodes = $this->getSchoolCodes();
            
            // Pre-fetch valid category codes for validation
            $validProvinces = $this->db->query("SELECT ma_tinh FROM dm_tinh")->fetchAll(PDO::FETCH_COLUMN);
            $validWards = $this->db->query("SELECT ma_xa FROM dm_xa")->fetchAll(PDO::FETCH_COLUMN);
            $validObjects = $this->db->query("SELECT ma_dt FROM dm_doi_tuong")->fetchAll(PDO::FETCH_COLUMN);
            $validAreas = $this->db->query("SELECT ma_kv FROM dm_khu_vuc")->fetchAll(PDO::FETCH_COLUMN);

            $candidateBatch = [];
            $scoresBatch = [];
            $hoSoBatch = [];

            $flushBatches = function() use (&$candidateBatch, &$scoresBatch, &$hoSoBatch, $batchId) {
                if (empty($candidateBatch)) return;

                $this->thiSinhRepo->upsertBatch($candidateBatch);
                $this->diemThiModel->upsertBatch($scoresBatch);
                
                // Process HoSo batch
                $cccds = array_unique($hoSoBatch);
                $placeholders = implode(',', array_fill(0, count($cccds), '?'));
                $stmt = $this->db->prepare("SELECT so_cccd FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ? AND so_cccd IN ($placeholders)");
                $params = array_merge([$batchId], $cccds);
                $stmt->execute($params);
                $existingCccds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $toInsert = array_diff($cccds, $existingCccds);

                if (!empty($toInsert)) {
                    $insertSql = "INSERT INTO ho_so_xet_tuyen (so_cccd, dot_tuyen_sinh_id, trang_thai, created_at, updated_at) VALUES ";
                    $insertValues = [];
                    $insertParams = [];
                    foreach ($toInsert as $c) {
                        $insertValues[] = "(?, ?, 'Chờ duyệt', NOW(), NOW())";
                        $insertParams[] = $c;
                        $insertParams[] = $batchId;
                    }
                    $insertSql .= implode(',', $insertValues);
                    $this->db->prepare($insertSql)->execute($insertParams);
                }



                $candidateBatch = [];
                $scoresBatch = [];
                $hoSoBatch = [];
            };

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
                    'ma_tinh_thuong_tru' => $maTinh, 
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

                $profileData['email'] = $cccd . '@import.local';
                $profileData['mat_khau'] = password_hash($cccd, PASSWORD_DEFAULT);

                $candidateBatch[] = $profileData;

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

                $scoresBatch[$cccd] = $scores;
                $hoSoBatch[] = $cccd;

                $success++;

                // Process every 500 rows to save memory and avoid parameter limits
                if (count($candidateBatch) >= 500) {
                    $flushBatches();
                    $this->updateProgress($adminId, $count, $totalRows, "Đã xử lý $count / $totalRows thí sinh...");
                }
            }
            
            // Process any remaining rows
            $flushBatches();
            $this->updateProgress($adminId, $totalRows, $totalRows, "Đã hoàn thành xử lý $totalRows thí sinh.");



            $this->db->commit();
            $this->importRepo->logImport(basename($filePath), 'candidates', $count, $adminId);
            return ['status' => true, 'count' => $count, 'success' => $success, 'errors' => $errors];

        } catch (\Throwable $e) {
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
            $totalRows = count($rows);
            $this->updateProgress($adminId, 0, $totalRows, 'Đang chuẩn bị nạp nguyện vọng...');

            $count = 0;
            $success = 0;
            $errors = [];
            
            // 1. Pre-fetch Majors for fast lookup
            $majors = $this->db->query("SELECT ma_nganh, ten_nganh FROM dm_nganh")->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // 2. Pre-fetch Profiles (so_cccd -> id) to eliminate per-row lookups
            // We use both the original CCCD and the one without leading zero for safety
            $profiles = $this->db->prepare("SELECT so_cccd, id FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ?");
            $profiles->execute([$batchId]);
            $profileMap = $profiles->fetchAll(PDO::FETCH_KEY_PAIR);

            $batchSize = 1000;
            $chunks = array_chunk($rows, $batchSize);
            
            foreach ($chunks as $chunkIndex => $chunk) {
                $countInChunk = 0;
                $sqlValues = [];
                $sqlParams = [];
                
                foreach ($chunk as $row) {
                    $count++;
                    if (count($row) < 5) continue;

                    $schoolCode = trim($row[3] ?? '');
                    if ($schoolCode !== $targetSchoolCode) continue;

                    $cccdRaw = trim($row[1] ?? '');
                    if (empty($cccdRaw)) continue;

                    // Match profile from memory cache
                    $hoSoId = $profileMap[$cccdRaw] ?? $profileMap[ltrim($cccdRaw, '0')] ?? null;
                    
                    if (!$hoSoId) {
                        $errors[] = "Dòng $count: Thí sinh $cccdRaw chưa có hồ sơ. Cần nạp File 1 trước.";
                        continue;
                    }

                    $majorCode = trim($row[5] ?? '');
                    $majorName = $majors[$majorCode] ?? trim($row[6] ?? '');
                    if (empty($majorName)) {
                        $errors[] = "Dòng $count: Ngành $majorCode không tồn tại.";
                        continue;
                    }

                    $priority = (int)trim($row[2] ?? 0);
                    $methodCode = trim($row[8] ?? '');
                    $methodName = trim($row[9] ?? '');
                    $comboCode = trim($row[10] ?? '');

                    // Collect for batch UPSERT
                    $sqlValues[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, 'bo_gddt', NOW(), NOW())";
                    array_push($sqlParams, 
                        $hoSoId, $cccdRaw, $priority, $majorCode, $majorName, 
                        $methodCode, $methodName, $comboCode, $batchId
                    );
                    $countInChunk++;
                    $success++;
                }

                if ($countInChunk > 0) {
                    $insertSql = "
                        INSERT INTO nguyen_vong (
                            ho_so_id, so_cccd, thu_tu_nguyen_vong, ma_nganh, ten_nganh, 
                            ma_phuong_thuc, ten_phuong_thuc, to_hop_mon, dot_tuyen_sinh_id, 
                            nguon_du_lieu, created_at, updated_at
                        ) VALUES " . implode(',', $sqlValues) . "
                        ON CONFLICT (ho_so_id, thu_tu_nguyen_vong, ma_phuong_thuc, to_hop_mon) 
                        DO UPDATE SET 
                            ma_nganh = EXCLUDED.ma_nganh,
                            ten_nganh = EXCLUDED.ten_nganh,
                            ten_phuong_thuc = EXCLUDED.ten_phuong_thuc,
                            updated_at = NOW()
                    ";
                    $this->db->prepare($insertSql)->execute($sqlParams);
                }

                $this->updateProgress($adminId, min($count, $totalRows), $totalRows, "Đã xử lý " . min($count, $totalRows) . " nguyện vọng...");
            }

            $this->db->commit();
            $this->updateProgress($adminId, $totalRows, $totalRows, "Hoàn thành: Đã nạp xong $success nguyện vọng.");
            $this->importRepo->logImport(basename($filePath), 'applications', $success, $adminId);
            
            return [
                'status' => true, 
                'success' => $success, 
                'errors' => array_slice($errors, 0, 50), 
                'message' => "Đã nạp thành công $success nguyện vọng theo phương thức đồng bộ lô tốc độ cao."
            ];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['status' => false, 'message' => "Lỗi thực thi: " . $e->getMessage(), 'errors' => $errors];
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
            
            // Convert from UTF-16 to UTF-8 if it's not UTF-8
            $encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16', 'ASCII']);
            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }

            $lines = explode("\n", str_replace("\r", "", $content));
            if (!empty($lines)) {
                $firstLine = $lines[0];
                // Simple auto-detect: count commas vs semicolons in the header
                $commaCount = substr_count($firstLine, ',');
                $semiCount = substr_count($firstLine, ';');
                $delimiter = ($semiCount > $commaCount) ? ';' : ',';

                foreach ($lines as $line) {
                    if (trim($line) === '') continue;
                    $rows[] = array_map('trim', str_getcsv($line, $delimiter));
                }
            }
        } else {
            // Memory efficient loading for Excel files
            try {
                $reader = IOFactory::createReaderForFile($filePath);
                
                // CRITICAL: setReadDataOnly(true) avoids loading styles, fonts, etc.
                // This is the single biggest performance improvement for large files.
                $reader->setReadDataOnly(true);
                $reader->setReadEmptyCells(false);
                
                $spreadsheet = $reader->load($filePath);
                
                // Find the sheet with the most rows to avoid reading blank default/saved sheets
                $highestRowCount = 0;
                $targetSheet = null;
                $sheets = $spreadsheet->getAllSheets();
                
                if (count($sheets) === 1) {
                    $targetSheet = $sheets[0];
                } else {
                    foreach ($sheets as $currentSheet) {
                        $rowCount = $currentSheet->getHighestDataRow();
                        if ($rowCount > $highestRowCount) {
                            $highestRowCount = $rowCount;
                            $targetSheet = $currentSheet;
                        }
                    }
                }
                
                // Fallback to active sheet if something goes wrong
                if ($targetSheet === null) {
                    $targetSheet = $spreadsheet->getActiveSheet();
                }
                
                $data = $targetSheet->toArray(null, true, true, true);
                foreach ($data as $row) {
                    $rows[] = array_values($row);
                }
                
                // Free memory immediately
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                
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
