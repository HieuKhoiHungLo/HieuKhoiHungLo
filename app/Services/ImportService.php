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

    private function updateProgress($token, $current, $total, $message = '') {
        $progressDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($progressDir)) mkdir($progressDir, 0777, true);
        
        $status = [
            'current' => $current,
            'total' => $total,
            'percent' => $total > 0 ? round(($current / $total) * 100) : 0,
            'message' => $message,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($progressDir . "/import_progress_{$token}.json", json_encode($status));
    }

    public function parseCandidates($filePath, $batchId, $token, $year) {
        if (!file_exists($filePath)) {
            return ['status' => false, 'message' => 'File not found'];
        }

        try {
            $rows = $this->loadData($filePath);
            
            if (empty($rows)) return ['status' => false, 'message' => 'File is empty or invalid'];

            array_shift($rows); // Skip header
            $totalRows = count($rows);
            $this->updateProgress($token, 0, $totalRows, 'Bắt đầu xử lý dữ liệu Thí sinh...');
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

            $count = 0;
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

                $profileData['email'] = $this->nullIfEmpty(trim($row[12] ?? '')); // Try to get email if exists
                if (!$profileData['email']) $profileData['email'] = null;
                
                $profileData['so_dien_thoai'] = $this->nullIfEmpty(trim($row[11] ?? '')); // Try to get phone if exists
                if (!$profileData['so_dien_thoai']) $profileData['so_dien_thoai'] = null;

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

                // Process every 2000 rows (Optimized V12)
                if (count($candidateBatch) >= 2000) {
                    $flushBatches();
                    $this->updateProgress($token, $count, $totalRows, "Đang nạp: $count/$totalRows thí sinh...");
                }
            }
            
            // Process any remaining rows
            $flushBatches();
            $this->db->commit();
            $this->updateProgress($token, $totalRows, $totalRows, "Đã hoàn thành xử lý $totalRows thí sinh.");

            $this->importRepo->logImport(basename($filePath), 'candidates', $count, 1);
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

    public function parseApplications($filePath, $batchId, $token, $targetSchoolCode) {
        if (!file_exists($filePath)) {
            return ['status' => false, 'message' => 'File not found'];
        }

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $totalRows = $sheet->getHighestDataRow();
            
            $this->updateProgress($token, 0, $totalRows, 'Đang đẩy hàng vạn nguyện vọng vào Database Singapore (Siêu tốc V12)...');

            // 1. Pre-fetch Majors and Profiles
            $majors = $this->db->query("SELECT ma_nganh, ten_nganh FROM dm_nganh")->fetchAll(PDO::FETCH_KEY_PAIR);
            $profiles = $this->db->prepare("SELECT so_cccd, id FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ?");
            $profiles->execute([$batchId]);
            $profileMap = $profiles->fetchAll(PDO::FETCH_KEY_PAIR);

            $count = 0;
            $success = 0;
            $errors = [];
            
            $this->db->beginTransaction(); // Move transaction OUTSIDE loop for speed
            
            $rowIterator = $sheet->getRowIterator(2); 
            $buffer = [];
            
            foreach ($rowIterator as $row) {
                $count++;
                $cells = $row->getCellIterator();
                $cells->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cells as $cell) {
                    $rowData[] = $cell->getValue();
                }

                $schoolCode = trim($rowData[3] ?? '');
                if ($schoolCode !== $targetSchoolCode) continue;

                $cccdRaw = trim($rowData[1] ?? '');
                if (empty($cccdRaw)) continue;

                $hoSoId = $profileMap[$cccdRaw] ?? $profileMap[ltrim($cccdRaw, '0')] ?? null;
                if (!$hoSoId) {
                    if (count($errors) < 50) $errors[] = "Dòng $count: Thí sinh $cccdRaw chưa có hồ sơ.";
                    continue;
                }

                $majorCode = trim($rowData[5] ?? '');
                $majorName = $majors[$majorCode] ?? trim($rowData[6] ?? '');
                
                $buffer[] = [
                    'ho_so_id' => $hoSoId,
                    'so_cccd' => $cccdRaw,
                    'thu_tu' => (int)trim($rowData[2] ?? 0),
                    'ma_nganh' => $majorCode,
                    'ten_nganh' => $majorName,
                    'ma_pt' => trim($rowData[8] ?? ''),
                    'ten_pt' => trim($rowData[9] ?? ''),
                    'to_hop' => trim($rowData[10] ?? '')
                ];

                if (count($buffer) >= 2000) {
                    $this->flushApplicationBuffer($buffer, $batchId);
                    $success += count($buffer);
                    $buffer = [];
                    $this->updateProgress($token, $count, $totalRows, "Đang nạp NV: $count/$totalRows dòng...");
                }
            }

            if (!empty($buffer)) {
                $this->flushApplicationBuffer($buffer, $batchId);
                $success += count($buffer);
            }

            $this->db->commit(); // One single commit at the end

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $this->updateProgress($token, $totalRows, $totalRows, "Hoàn thành: Đã nạp xong $success nguyện vọng.");
            return ['status' => true, 'success' => $success, 'errors' => array_slice($errors, 0, 50)];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['status' => false, 'message' => "Lỗi thực thi: " . $e->getMessage()];
        }
    }

    private function flushApplicationBuffer($buffer, $batchId) {
        $sqlValues = [];
        $sqlParams = [];
        foreach ($buffer as $data) {
            $sqlValues[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, 'bo_gddt', NOW(), NOW())";
            array_push($sqlParams, 
                $data['ho_so_id'], $data['so_cccd'], $data['thu_tu'], 
                $data['ma_nganh'], $data['ten_nganh'], $data['ma_pt'], 
                $data['ten_pt'], $data['to_hop'], $batchId
            );
        }

        $sql = "
            INSERT INTO nguyen_vong (
                ho_so_id, so_cccd, thu_tu_nguyen_vong, ma_nganh, ten_nganh, 
                ma_phuong_thuc, ten_phuong_thuc, to_hop_mon, dot_tuyen_sinh_id, 
                nguon_du_lieu, created_at, updated_at
            ) VALUES " . implode(',', $sqlValues) . "
            ON CONFLICT ON CONSTRAINT uk_hoso_nv_aspiration DO UPDATE SET 
                ma_nganh = EXCLUDED.ma_nganh,
                ten_nganh = EXCLUDED.ten_nganh,
                ten_phuong_thuc = EXCLUDED.ten_phuong_thuc,
                updated_at = NOW()
        ";
        $this->db->prepare($sql)->execute($sqlParams);
    }

    public function parseTranscripts($filePath, $batchId, $token) {
        if (!file_exists($filePath)) {
            return ['status' => false, 'message' => 'File not found'];
        }

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $totalRows = $sheet->getHighestDataRow();

            $this->updateProgress($token, 0, $totalRows, 'Đang truyền điểm học bạ tới Singapore (Siêu tốc V12)...');
        
            // 1. Pre-fetch valid candidates to prevent FK violations
            $stmt = $this->db->query("SELECT so_cccd FROM thi_sinh");
            $validCCCDs = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

            $count = 0;
            $success = 0;
            $skipped = 0;
            
            $this->db->beginTransaction(); // Move transaction OUTSIDE loop
            
            $rowIterator = $sheet->getRowIterator(2);
            $buffer = [];

            foreach ($rowIterator as $row) {
                $count++;
                $cells = $row->getCellIterator();
                $cells->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cells as $cell) {
                    $rowData[] = $cell->getValue();
                }

                if (count($rowData) < 50) continue;
                
                $cccd = trim($rowData[1] ?? '');
                if (strlen($cccd) == 13 && strpos($cccd, '0') === 0) $cccd = substr($cccd, 1);
                if (empty($cccd)) continue; 

                // SKIP logic: if student not found in File 1, don't try to import scores
                if (!isset($validCCCDs[$cccd])) {
                    $skipped++;
                    continue;
                }

                $lop = trim($rowData[5] ?? '');
                if (!in_array($lop, ['10', '11', '12'])) continue;

                $buffer[] = [
                    'cccd' => $cccd,
                    'lop' => (int)$lop,
                    'scores' => [
                        $this->parseFloat($rowData[25] ?? ''), // Toán
                        $this->parseFloat($rowData[28] ?? ''), // Văn
                        $this->parseFloat($rowData[31] ?? ''), // Lý
                        $this->parseFloat($rowData[34] ?? ''), // Hóa
                        $this->parseFloat($rowData[37] ?? ''), // Sinh
                        $this->parseFloat($rowData[40] ?? ''), // Sử
                        $this->parseFloat($rowData[43] ?? ''), // Địa
                        $this->parseFloat($rowData[46] ?? ''), // GDCD
                        $this->parseFloat($rowData[49] ?? ''), // KTPL
                        $this->parseFloat($rowData[52] ?? ''), // Tin
                        $this->parseFloat($rowData[61] ?? '')  // Ngoại ngữ
                    ]
                ];

                if (count($buffer) >= 2000) {
                    $this->flushTranscriptBuffer($buffer);
                    $success += count($buffer);
                    $buffer = [];
                    $this->updateProgress($token, $count, $totalRows, "Đang nạp: $count/$totalRows dòng...");
                }
            }

            if (!empty($buffer)) {
                $this->flushTranscriptBuffer($buffer);
                $success += count($buffer);
            }

            $this->db->commit(); // One single commit

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $msg = "Hoàn thành: Đã nạp xong $success bản ghi.";
            if ($skipped > 0) $msg .= " (Đã bỏ qua $skipped thí sinh chưa có thông tin ở File 1)";
            $this->updateProgress($token, $totalRows, $totalRows, $msg);
            
            return ['status' => true, 'success' => $success, 'skipped' => $skipped];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['status' => false, 'message' => "Lỗi thực thi: " . $e->getMessage()];
        }
    }

    private function flushTranscriptBuffer($buffer) {
        $sqlValues = [];
        $sqlParams = [];
        foreach ($buffer as $data) {
            $sqlValues[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $sqlParams[] = $data['cccd'];
            $sqlParams[] = $data['lop'];
            foreach ($data['scores'] as $s) $sqlParams[] = $s;
        }

        $sql = "
            INSERT INTO ket_qua_hoc_tap (
                so_cccd, lop, 
                diem_toan_cn, diem_van_cn, diem_ly_cn, diem_hoa_cn, 
                diem_sinh_cn, diem_su_cn, diem_dia_cn, diem_gdcd_cn, 
                diem_ktpl_cn, diem_tin_hoc_cn, diem_ngoai_ngu_cn
            ) VALUES " . implode(',', $sqlValues) . "
            ON CONFLICT (so_cccd, lop) DO UPDATE SET
                diem_toan_cn = EXCLUDED.diem_toan_cn,
                diem_van_cn = EXCLUDED.diem_van_cn,
                diem_ly_cn = EXCLUDED.diem_ly_cn,
                diem_hoa_cn = EXCLUDED.diem_hoa_cn,
                diem_sinh_cn = EXCLUDED.diem_sinh_cn,
                diem_su_cn = EXCLUDED.diem_su_cn,
                diem_dia_cn = EXCLUDED.diem_dia_cn,
                diem_gdcd_cn = EXCLUDED.diem_gdcd_cn,
                diem_ktpl_cn = EXCLUDED.diem_ktpl_cn,
                diem_tin_hoc_cn = EXCLUDED.diem_tin_hoc_cn,
                diem_ngoai_ngu_cn = EXCLUDED.diem_ngoai_ngu_cn
        ";
        $this->db->prepare($sql)->execute($sqlParams);
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
        if ($extension === 'csv') {
            $data = [];
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                while (($row = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    $data[] = $row;
                }
                fclose($handle);
            }
            return $data;
        } else {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            // Always use first sheet - Government files may have active sheet set to an empty sheet
            $sheet = $spreadsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            
            // Limit to column AO (41 cols) - government files may have 257+ cols but data is only in first ~37
            $range = 'A1:AO' . $highestRow;
            $data = $sheet->rangeToArray($range, null, true, true, false);
            
            // Ensure 0-based numeric keys for each row
            foreach ($data as &$row) {
                $row = array_values($row);
            }
            unset($row);
            
            return $data;
        }
    }
}
