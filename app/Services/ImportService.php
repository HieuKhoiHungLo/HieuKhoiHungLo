<?php
namespace App\Services;

use App\Repositories\ImportRepository;
use App\Repositories\ThiSinhRepository;
use PDO;
use App\Models\DiemThiTHPT;
use App\Core\Database;
use App\Repositories\MasterDataRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ColumnFilter implements IReadFilter {
    private $maxCol;
    public function __construct($maxCol) { $this->maxCol = $maxCol; }
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnAddress) <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($this->maxCol);
    }
}

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

    private function loadArrayData($filePath, $maxCol) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'xls') {
            require_once __DIR__ . '/SimpleXLS.php';
            if ($xls = \Shuchkin\SimpleXLS::parse($filePath)) {
                $data = $xls->rows();
                unset($xls);
                return $data;
            }
        }
        
        if ($extension === 'xlsx') {
            require_once __DIR__ . '/SimpleXLSX.php';
            if ($xlsx = \Shuchkin\SimpleXLSX::parse($filePath)) {
                $data = $xlsx->rows();
                unset($xlsx);
                return $data;
            }
        }
        
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        if ($maxCol) $reader->setReadFilter(new ColumnFilter($maxCol));
        
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        
        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 200000) $highestRow = 200000; // Hard cap to avoid memory exhaustion
        
        $data = [];
        $emptyStreak = 0;
        $chunkSize = 5000;
        
        for ($startRow = 1; $startRow <= $highestRow; $startRow += $chunkSize) {
            $endRow = min($startRow + $chunkSize - 1, $highestRow);
            $chunk = $sheet->rangeToArray("A{$startRow}:{$maxCol}{$endRow}", null, false, false, false);
            
            foreach ($chunk as $row) {
                $data[] = $row;
                
                $isEmpty = true;
                foreach ($row as $cell) {
                    if ($cell !== null && $cell !== '') {
                        $isEmpty = false;
                        break;
                    }
                }
                
                if ($isEmpty) {
                    $emptyStreak++;
                    // If we see 100 completely empty rows in a row, assume the real data has ended
                    if ($emptyStreak >= 100) {
                        // Remove the trailing empty rows we just added
                        array_splice($data, -100);
                        break 2;
                    }
                } else {
                    $emptyStreak = 0;
                }
            }
        }
        
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
        return $data;
    }

    public function parseCandidates($filePath, $batchId, $token, $year) {
        if (!file_exists($filePath)) return ['status' => false, 'message' => 'File not found'];

        try {
            $this->updateProgress($token, 0, 1, 'Đang phân tích cấu trúc file Thí sinh...');
            
            $dataArray = $this->loadArrayData($filePath, 'BB');
            $totalRows = count($dataArray);
            
            $success = 0;
            $errors = [];

            // Pre-hashed default password to bypass massive CPU overhead
            $defaultHash = password_hash('thv2026@', PASSWORD_BCRYPT);
            $year = $year ?: (int)date('Y');
            
            // Fast O(1) HashMaps
            $schoolCodes = array_flip($this->getSchoolCodes());
            $validProvinces = array_flip($this->db->query("SELECT ma_tinh FROM dm_tinh")->fetchAll(PDO::FETCH_COLUMN));
            $validWards = array_flip($this->db->query("SELECT ma_xa FROM dm_xa")->fetchAll(PDO::FETCH_COLUMN));
            $validObjects = array_flip($this->db->query("SELECT ma_dt FROM dm_doi_tuong")->fetchAll(PDO::FETCH_COLUMN));
            $validAreas = array_flip($this->db->query("SELECT ma_kv FROM dm_khu_vuc")->fetchAll(PDO::FETCH_COLUMN));
            
            $this->db->beginTransaction();
            
            $candidateBatch = [];
            $academicBatch = [];
            $scoresBatch = [];
            $hoSoBatch = [];

            $flushBatches = function() use (&$candidateBatch, &$scoresBatch, &$hoSoBatch, &$academicBatch, $batchId) {
                if (!empty($candidateBatch)) $this->thiSinhRepo->upsertBatch($candidateBatch);
                if (!empty($academicBatch)) $this->flushTranscriptBuffer($academicBatch);
                if (!empty($scoresBatch)) $this->diemThiModel->upsertBatch($scoresBatch);
                if (!empty($hoSoBatch)) {
                    $cccds = array_unique($hoSoBatch);
                    $placeholders = implode(',', array_fill(0, count($cccds), '?'));
                    $stmt = $this->db->prepare("SELECT so_cccd FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ? AND so_cccd IN ($placeholders)");
                    $stmt->execute(array_merge([$batchId], $cccds));
                    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $toInsert = array_diff($cccds, $existing);
                    if (!empty($toInsert)) {
                        $insertSql = "INSERT INTO ho_so_xet_tuyen (so_cccd, dot_tuyen_sinh_id, trang_thai, created_at, updated_at) VALUES ";
                        $vals = []; $params = [];
                        foreach ($toInsert as $c) {
                            $vals[] = "(?, ?, 'Chờ duyệt', NOW(), NOW())";
                            $params[] = $c; $params[] = $batchId;
                        }
                        $this->db->prepare($insertSql . implode(',', $vals))->execute($params);
                    }
                }
                $candidateBatch = [];
                $academicBatch = [];
                $scoresBatch = [];
                $hoSoBatch = [];
            };

            $count = 0;
            foreach ($dataArray as $idx => $row) {
                // Skip header (row 0 implies simple array format and likely contains headers)
                if ($idx === 0 || count($row) < 30) continue;
                $count++;
                    
                $sbd = trim($row[1] ?? '');
                $cccd = trim($row[3] ?? '');
                if (empty($cccd)) continue; 

                $maTinh = $this->nullIfEmpty(trim($row[14] ?? ''));
                $maXa = $this->nullIfEmpty(trim($row[18] ?? ''));
                
                // Chuẩn hóa đối tượng ưu tiên (VD: 05a, 05b, 05c -> 05; 6a -> 06; 1 -> 01, riêng 04b giữ 04b)
                $rawDT = trim($row[6] ?? '');
                $maDT = null;
                if ($rawDT !== '') {
                    $sDT = strtolower($rawDT);
                    if ($sDT === '04b' || $sDT === '4b') {
                        $maDT = '04b';
                    } else {
                        $digits = preg_replace('/[^0-9]/', '', $rawDT);
                        if ($digits !== '') {
                            $maDT = strlen($digits) === 1 ? '0' . $digits : $digits;
                        }
                    }
                }
                
                $maKV = $this->nullIfEmpty(trim($row[7] ?? ''));

                if ($maTinh && !isset($validProvinces[$maTinh])) $maTinh = null;
                if ($maXa && !isset($validWards[$maXa])) $maXa = null;
                if ($maDT && !isset($validObjects[$maDT])) $maDT = null;
                if ($maKV && !isset($validAreas[$maKV])) $maKV = null;

                $isDacCach = 0;
                $val50 = trim($row[50] ?? '');
                $val55 = trim($row[55] ?? '');
                
                if ($val50 !== '' && (strtoupper($val50) === 'X' || mb_strtolower($val50, 'UTF-8') === 'đặc cách' || $val50 == 1)) {
                    $isDacCach = 1;
                }
                if ($val55 !== '' && (strtoupper($val55) === 'X' || mb_strtolower($val55, 'UTF-8') === 'đặc cách' || mb_strtolower($val55, 'UTF-8') === 'miễn thi tn' || $val55 == 1)) {
                    $isDacCach = 1;
                }

                $profileData = [
                    'so_cccd' => $cccd,
                    'so_bao_danh' => $sbd,
                    'ho_va_ten' => trim($row[2] ?? ''),
                    'ngay_sinh' => $this->parseDate(trim($row[4] ?? '')),
                    'gioi_tinh' => $this->parseGender(trim($row[5] ?? '')),
                    'doi_tuong_uu_tien' => $maDT,
                    'khu_vuc_uu_tien' => $maKV,
                    'nam_tot_nghiep' => (int)trim($row[8] ?? date('Y')),
                    'hoc_luc' => $this->normalizeTerm($row[9] ?? ''),
                    'hanh_kiem' => $this->normalizeTerm($row[10] ?? ''),
                    'ma_tinh_ho_khau' => $maTinh,
                    'ma_huyen_ho_khau' => $this->nullIfEmpty(trim($row[16] ?? '')),
                    'ma_xa_ho_khau' => $maXa,
                    'is_dac_cach' => $isDacCach,
                ];

                $maTinhLop12 = $this->nullIfEmpty(trim($row[18] ?? ''));
                $maTruongLop12 = $this->nullIfEmpty(trim($row[19] ?? ''));
                $fullSchoolCode = $maTinhLop12 . $maTruongLop12;
                
                if (isset($schoolCodes[$fullSchoolCode])) {
                    $profileData['ma_truong_lop_12'] = $fullSchoolCode;
                } else if (isset($schoolCodes[$maTinhLop12 . $maTruongLop12])) {
                    $profileData['ma_truong_lop_12'] = $maTinhLop12 . $maTruongLop12;
                } else {
                    $profileData['ma_truong_lop_12'] = null;
                }

                $profileData['email'] = $this->nullIfEmpty(trim($row[12] ?? ''));
                $profileData['so_dien_thoai'] = $this->nullIfEmpty(trim($row[11] ?? ''));
                $profileData['mat_khau'] = $defaultHash;

                $candidateBatch[] = $profileData;

                $scores = [
                    'nam_thi' => $year,
                    'toan' => $this->parseFloat($row[21] ?? ''),
                    'van' => $this->parseFloat($row[22] ?? ''),
                    'ly' => $this->parseFloat($row[23] ?? ''),
                    'hoa' => $this->parseFloat($row[24] ?? ''),
                    'sinh' => $this->parseFloat($row[25] ?? ''),
                    'su' => $this->parseFloat($row[26] ?? ''),
                    'dia' => $this->parseFloat($row[27] ?? ''),
                    'gdcd' => $this->parseFloat($row[28] ?? ''),
                    'ktpl' => $this->parseFloat($row[31] ?? ''), 
                    'tin_hoc' => $this->parseFloat($row[32] ?? ''),
                    'cong_nghe' => $this->parseFloat($row[33] ?? ''),
                    'cnnn' => $this->parseFloat($row[34] ?? ''), 
                    'diem_xet_tot_nghiep' => $this->parseFloat($row[45] ?? '')
                ];

                $val29 = trim($row[29] ?? '');
                $val30 = trim($row[30] ?? '');

                if (in_array(strtoupper($val30), ['N1', 'N2', 'N3', 'N4', 'N5', 'N6', 'N7'])) {
                    // Style A: Column 29 is score, Column 30 is language code
                    $nnScore = $this->parseFloat($val29);
                    if ($val30 == 'N1') $scores['tieng_anh'] = $nnScore;
                    if ($val30 == 'N4') $scores['tieng_trung'] = $nnScore;
                    if ($val30 == 'N3') $scores['tieng_phap'] = $nnScore;
                    if ($val30 == 'N6') $scores['tieng_nhat'] = $nnScore;
                } else {
                    // Style B: Column 29 is N1 (Tiếng Anh) score, Column 30 is N4 (Tiếng Trung) score
                    $n1Score = $this->parseFloat($val29);
                    if ($n1Score !== null && $n1Score !== '') {
                        $scores['tieng_anh'] = $n1Score;
                    }
                    $n4Score = $this->parseFloat($val30);
                    if ($n4Score !== null && $n4Score !== '') {
                        $scores['tieng_trung'] = $n4Score;
                    }
                }

                $scoresBatch[$cccd] = $scores;
                $hoSoBatch[] = $cccd;

                // Parse Grade 12 academic summary from File 1 (national exam list)
                $gpa12 = $this->parseFloat($row[11] ?? ''); // L (index 11) is Grade 12 GPA
                $hl12 = $this->normalizeTerm($row[9] ?? ''); // J (index 9) is Grade 12 Academic Performance
                $hk12 = $this->normalizeTerm($row[10] ?? ''); // K (index 10) is Grade 12 Conduct
                
                // Only insert/update Grade 12 record if we have at least one of these values
                if ($hl12 !== '' || $hk12 !== '' || $gpa12 !== null) {
                    $academicBatch[] = [
                        'cccd' => $cccd,
                        'lop' => 12,
                        'scores' => [null, null, null, null, null, null, null, null, null, null, null, null, null, null],
                        'summaries' => [
                            'diem_tb_hk1' => null,
                            'diem_tb_hk2' => null,
                            'diem_tb_ca_nam' => $gpa12,
                            'hoc_luc_ca_nam' => $hl12,
                            'hanh_kiem_ca_nam' => $hk12,
                            'ghi_chu' => ''
                        ]
                    ];
                }

                $success++;

                if (count($candidateBatch) >= 5000) {
                    $flushBatches();
                    $this->updateProgress($token, $count, $totalRows, "Đang nạp: $count/$totalRows thí sinh...");
                }
            }
            
            $flushBatches();
            $this->db->commit();
            $this->updateProgress($token, $totalRows, $totalRows, "Đã hoàn thành xử lý $totalRows thí sinh.");

            return ['status' => true, 'count' => $count, 'success' => $success, 'errors' => $errors];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
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
        $str = trim($str);
        if ($str === '1' || strcasecmp($str, 'Nam') === 0) {
            return 'Nam';
        }
        return 'Nữ';
    }

    public function parseApplications($filePath, $batchId, $token, $targetSchoolCode) {
        if (!file_exists($filePath)) return ['status' => false, 'message' => 'File not found'];

        try {
            $this->updateProgress($token, 0, 1, 'Đang phân tích cấu trúc file Nguyện vọng (Ultra Fast JSON Mode)...');
            
            $dataArray = $this->loadArrayData($filePath, 'T');
            $totalRows = count($dataArray);

            $majors = array_flip($this->getMajors());
            $profiles = $this->db->prepare("SELECT so_cccd, id FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ?");
            $profiles->execute([$batchId]);
            $profileMap = $profiles->fetchAll(PDO::FETCH_KEY_PAIR);

            $count = 0;
            $success = 0;
            $errors = [];
            
            $this->db->beginTransaction(); 

            // Collect all ho_so_ids in the uploaded file for this school
            $hoSoIdsToDelete = [];
            foreach ($dataArray as $idx => $rowData) {
                if ($idx === 0 || count($rowData) < 11) continue;
                $schoolCode = trim($rowData[3] ?? '');
                if ($schoolCode !== $targetSchoolCode) continue;

                $cccdRaw = trim($rowData[1] ?? '');
                if (empty($cccdRaw)) continue;

                $hoSoId = $profileMap[$cccdRaw] ?? $profileMap[ltrim($cccdRaw, '0')] ?? null;
                if ($hoSoId) {
                    $hoSoIdsToDelete[] = (int)$hoSoId;
                }
            }
            $hoSoIdsToDelete = array_values(array_unique($hoSoIdsToDelete));

            // Clean up existing aspirations for these candidates to prevent old ones from persisting
            if (!empty($hoSoIdsToDelete)) {
                $chunks = array_chunk($hoSoIdsToDelete, 500);
                foreach ($chunks as $chunk) {
                    $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                    $sqlDel = "DELETE FROM nguyen_vong WHERE dot_tuyen_sinh_id = ? AND ho_so_id IN ($placeholders)";
                    $stmtDel = $this->db->prepare($sqlDel);
                    $stmtDel->execute(array_merge([$batchId], $chunk));
                }
            }

            $buffer = [];

            foreach ($dataArray as $idx => $rowData) {
                if ($idx === 0 || count($rowData) < 11) continue;
                $count++;

                $schoolCode = trim($rowData[3] ?? '');
                if ($schoolCode !== $targetSchoolCode) continue;

                $cccdRaw = trim($rowData[1] ?? '');
                if (empty($cccdRaw)) continue;

                $hoSoId = $profileMap[$cccdRaw] ?? $profileMap[ltrim($cccdRaw, '0')] ?? null;
                if (!$hoSoId) continue;

                $maNganhRaw = trim($rowData[5] ?? '');
                $maNganh = isset($majors[$maNganhRaw]) ? $maNganhRaw : null;
                if (!$maNganh) continue;
                
                $buffer[] = [
                    'ho_so_id' => $hoSoId,
                    'so_cccd' => $cccdRaw,
                    'thu_tu' => (int)trim($rowData[2] ?? 0),
                    'ma_nganh' => $maNganh,
                    'ten_nganh' => trim($rowData[6] ?? ''),
                    'ma_pt' => trim($rowData[8] ?? ''),
                    'ten_pt' => trim($rowData[9] ?? ''),
                    'to_hop' => trim($rowData[10] ?? '')
                ];

                if (count($buffer) >= 5000) {
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

            $this->db->commit();
            $this->updateProgress($token, $totalRows, $totalRows, "Hoàn thành: Đã nạp xong $success nguyện vọng.");
            return ['status' => true, 'count' => $totalRows, 'success' => $success, 'errors' => $errors];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['status' => false, 'message' => "Lỗi thực thi: " . $e->getMessage()];
        }
    }

    private function flushApplicationBuffer($buffer, $batchId) {
        $unique = [];
        foreach ($buffer as $data) {
            $key = $data['ho_so_id'] . '_' . $data['thu_tu'];
            $unique[$key] = $data; 
        }
        if (empty($unique)) return;
        
        $jsonParam = json_encode(array_values($unique), JSON_UNESCAPED_UNICODE);
        $sql = "
            INSERT INTO nguyen_vong (
                ho_so_id, so_cccd, thu_tu_nguyen_vong, thu_tu_nv_bo, ma_nganh, ten_nganh, 
                ma_phuong_thuc, ten_phuong_thuc, to_hop_mon, dot_tuyen_sinh_id, 
                nguon_du_lieu, created_at, updated_at
            )
            SELECT 
                (elem->>'ho_so_id')::int,
                elem->>'so_cccd',
                (elem->>'thu_tu')::int,
                (elem->>'thu_tu')::int,
                elem->>'ma_nganh',
                elem->>'ten_nganh',
                elem->>'ma_pt',
                elem->>'ten_pt',
                elem->>'to_hop',
                ?,
                'bo_gddt', NOW(), NOW()
            FROM json_array_elements(?::json) AS elem
            ON CONFLICT ON CONSTRAINT uk_hoso_nv_aspiration DO UPDATE SET 
                ma_nganh = EXCLUDED.ma_nganh,
                ten_nganh = EXCLUDED.ten_nganh,
                ten_phuong_thuc = EXCLUDED.ten_phuong_thuc,
                thu_tu_nv_bo = EXCLUDED.thu_tu_nv_bo,
                updated_at = NOW()
        ";
        $this->db->prepare($sql)->execute([$batchId, $jsonParam]);
    }

    public function parseTranscripts($filePath, $batchId, $token) {
        if (!file_exists($filePath)) return ['status' => false, 'message' => 'File not found'];

        try {
            $this->updateProgress($token, 0, 1, 'Đang phân tích cấu trúc file Học bạ...');
            
            $dataArray = $this->loadArrayData($filePath, 'BZ');
            $totalRows = count($dataArray);
        
            $stmt = $this->db->query("SELECT so_cccd FROM thi_sinh");
            $validCCCDs = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

            $count = 0; $success = 0; $skipped = 0;
            
            $this->db->beginTransaction();
            $buffer = [];

            foreach ($dataArray as $idx => $rowData) {
                if ($idx === 0 || count($rowData) < 50) continue;
                $count++;
                
                $cccd = trim($rowData[1] ?? '');
                if (strlen($cccd) == 13 && strpos($cccd, '0') === 0) $cccd = substr($cccd, 1);
                if (empty($cccd)) continue; 

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
                        $this->parseFloat($rowData[55] ?? ''), // Công nghệ
                        $this->parseFloat($rowData[68] ?? ''), // GDQP (QPAN)
                        in_array(strtoupper(trim($rowData[62] ?? '')), ['N1', 'N4']) ? $this->parseFloat($rowData[61] ?? '') : null,  // Ngoại ngữ (Chỉ nhận N1, N4)
                        in_array(strtoupper(trim($rowData[75] ?? '')), ['N1', 'N4']) ? $this->parseFloat($rowData[74] ?? '') : null   // Ngoại ngữ 2 (Chỉ nhận N1, N4)
                    ],
                    'summaries' => [
                        'diem_tb_hk1' => $this->parseFloat($rowData[8] ?? ''),
                        'diem_tb_hk2' => $this->parseFloat($rowData[9] ?? ''),
                        'diem_tb_ca_nam' => $this->parseFloat($rowData[7] ?? ''),
                        'hoc_luc_ca_nam' => $this->normalizeTerm($rowData[19] ?? ''),
                        'hanh_kiem_ca_nam' => $this->normalizeTerm($rowData[22] ?? ''),
                        'ghi_chu' => "ma_nn1:" . strtoupper(trim($rowData[62] ?? '')) . ";ma_nn2:" . strtoupper(trim($rowData[75] ?? ''))
                    ]
                ];

                if (count($buffer) >= 5000) {
                    $this->flushTranscriptBuffer($buffer);
                    $success += count($buffer);
                    $buffer = [];
                    $this->updateProgress($token, $count, $totalRows, "Đang nạp: $count/$totalRows dòng... (V17 Ultra Speed)");
                }
            }
            
            if (!empty($buffer)) {
                $this->flushTranscriptBuffer($buffer);
                $success += count($buffer);
            }

            $this->db->commit();
            $msg = "Hoàn thành: Đã nạp xong $success bản ghi.";
            if ($skipped > 0) $msg .= " (Đã bỏ qua $skipped thí sinh chưa có thông tin ở File 1)";
            $this->updateProgress($token, $totalRows, $totalRows, $msg);
            
            return ['status' => true, 'count' => $totalRows, 'success' => $success, 'skipped' => $skipped];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['status' => false, 'message' => "Lỗi thực thi: " . $e->getMessage()];
        }
    }

    private function flushTranscriptBuffer($buffer) {
        $unique = [];
        foreach ($buffer as $data) {
            $unique[$data['cccd'] . '_' . $data['lop']] = $data; 
        }

        if (empty($unique)) return;
        
        $jsonParam = json_encode(array_values($unique), JSON_UNESCAPED_UNICODE);
        $sql = "
            INSERT INTO ket_qua_hoc_tap (
                so_cccd, lop, 
                diem_toan_cn, diem_van_cn, diem_ly_cn, diem_hoa_cn, 
                diem_sinh_cn, diem_su_cn, diem_dia_cn, diem_gdcd_cn, 
                diem_ktpl_cn, diem_tin_hoc_cn, diem_cong_nghe_cn, diem_gdqp_cn, diem_ngoai_ngu_cn, diem_ngoai_ngu_2_cn,
                diem_tb_hk1, diem_tb_hk2, diem_tb_ca_nam,
                hoc_luc_ca_nam, hanh_kiem_ca_nam, ghi_chu
            )
            SELECT 
                elem->>'cccd',
                (elem->>'lop')::int,
                (elem->'scores'->>0)::numeric,
                (elem->'scores'->>1)::numeric,
                (elem->'scores'->>2)::numeric,
                (elem->'scores'->>3)::numeric,
                (elem->'scores'->>4)::numeric,
                (elem->'scores'->>5)::numeric,
                (elem->'scores'->>6)::numeric,
                (elem->'scores'->>7)::numeric,
                (elem->'scores'->>8)::numeric,
                (elem->'scores'->>9)::numeric,
                (elem->'scores'->>10)::numeric,
                (elem->'scores'->>11)::numeric,
                (elem->'scores'->>12)::numeric,
                (elem->'scores'->>13)::numeric,
                (elem->'summaries'->>'diem_tb_hk1')::numeric,
                (elem->'summaries'->>'diem_tb_hk2')::numeric,
                (elem->'summaries'->>'diem_tb_ca_nam')::numeric,
                elem->'summaries'->>'hoc_luc_ca_nam',
                elem->'summaries'->>'hanh_kiem_ca_nam',
                elem->'summaries'->>'ghi_chu'
            FROM json_array_elements(?::json) AS elem
            ON CONFLICT (so_cccd, lop) DO UPDATE SET
                diem_toan_cn = COALESCE(EXCLUDED.diem_toan_cn, ket_qua_hoc_tap.diem_toan_cn),
                diem_van_cn = COALESCE(EXCLUDED.diem_van_cn, ket_qua_hoc_tap.diem_van_cn),
                diem_ly_cn = COALESCE(EXCLUDED.diem_ly_cn, ket_qua_hoc_tap.diem_ly_cn),
                diem_hoa_cn = COALESCE(EXCLUDED.diem_hoa_cn, ket_qua_hoc_tap.diem_hoa_cn),
                diem_sinh_cn = COALESCE(EXCLUDED.diem_sinh_cn, ket_qua_hoc_tap.diem_sinh_cn),
                diem_su_cn = COALESCE(EXCLUDED.diem_su_cn, ket_qua_hoc_tap.diem_su_cn),
                diem_dia_cn = COALESCE(EXCLUDED.diem_dia_cn, ket_qua_hoc_tap.diem_dia_cn),
                diem_gdcd_cn = COALESCE(EXCLUDED.diem_gdcd_cn, ket_qua_hoc_tap.diem_gdcd_cn),
                diem_ktpl_cn = COALESCE(EXCLUDED.diem_ktpl_cn, ket_qua_hoc_tap.diem_ktpl_cn),
                diem_tin_hoc_cn = COALESCE(EXCLUDED.diem_tin_hoc_cn, ket_qua_hoc_tap.diem_tin_hoc_cn),
                diem_cong_nghe_cn = COALESCE(EXCLUDED.diem_cong_nghe_cn, ket_qua_hoc_tap.diem_cong_nghe_cn),
                diem_gdqp_cn = COALESCE(EXCLUDED.diem_gdqp_cn, ket_qua_hoc_tap.diem_gdqp_cn),
                diem_ngoai_ngu_cn = COALESCE(EXCLUDED.diem_ngoai_ngu_cn, ket_qua_hoc_tap.diem_ngoai_ngu_cn),
                diem_ngoai_ngu_2_cn = COALESCE(EXCLUDED.diem_ngoai_ngu_2_cn, ket_qua_hoc_tap.diem_ngoai_ngu_2_cn),
                diem_tb_hk1 = COALESCE(EXCLUDED.diem_tb_hk1, ket_qua_hoc_tap.diem_tb_hk1),
                diem_tb_hk2 = COALESCE(EXCLUDED.diem_tb_hk2, ket_qua_hoc_tap.diem_tb_hk2),
                diem_tb_ca_nam = COALESCE(EXCLUDED.diem_tb_ca_nam, ket_qua_hoc_tap.diem_tb_ca_nam),
                hoc_luc_ca_nam = COALESCE(EXCLUDED.hoc_luc_ca_nam, ket_qua_hoc_tap.hoc_luc_ca_nam),
                hanh_kiem_ca_nam = COALESCE(EXCLUDED.hanh_kiem_ca_nam, ket_qua_hoc_tap.hanh_kiem_ca_nam),
                ghi_chu = CASE 
                    WHEN EXCLUDED.ghi_chu = '' OR EXCLUDED.ghi_chu IS NULL THEN ket_qua_hoc_tap.ghi_chu 
                    ELSE EXCLUDED.ghi_chu 
                END
        ";
        $this->db->prepare($sql)->execute([$jsonParam]);
    }

    private function nullIfEmpty($value) {
        return empty($value) ? null : $value;
    }

    private function parseFloat($value) {
        if ($value === null || $value === '') return null;
        $val = str_replace(',', '.', (string)$value);
        return is_numeric($val) ? (float)$val : null;
    }

    private function normalizeTerm($value) {
        if ($value === null || $value === '') return null;
        $val = trim((string)$value);
        if (empty($val)) return null;

        $map = [
            'G' => 'Giỏi', 'K' => 'Khá', 'TB' => 'Trung bình', 'Y' => 'Yếu',
            'T' => 'Tốt', 'D' => 'Đạt', 'CD' => 'Chưa đạt',
            'GIOI' => 'Giỏi', 'KHA' => 'Khá', 'TRUNG BINH' => 'Trung bình', 'YEU' => 'Yếu', 'KEM' => 'Kém',
            'TOT' => 'Tốt'
        ];
        
        $upper = mb_strtoupper($val, 'UTF-8');
        if (isset($map[$upper])) return $map[$upper];
        
        return $val;
    }
}
