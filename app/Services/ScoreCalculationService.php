<?php
namespace App\Services;

use App\Repositories\ThiSinhRepository;
use App\Repositories\MasterDataRepository;
use App\Models\AcademicRecord;
use App\Models\DiemThiTHPT;
use App\Core\Database;
use PDO;

class ScoreCalculationService {
    protected $db;
    protected $masterDataRepo;
    protected $thiSinhRepo;
    protected $academicModel;
    protected $diemThiModel;
    
    /**
     * Helper to sanitize numeric inputs from potentially dirty database fields
     * (e.g. handles strings like "266/300")
     */
    protected function sanitizeNumeric($val) {
        if ($val === null || $val === '') return 0;
        if (is_numeric($val)) return (float)$val;
        
        if (is_string($val) && strpos($val, '/') !== false) {
            $parts = explode('/', $val);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1]) && (float)$parts[1] > 0) {
                return (float)$parts[0] / (float)$parts[1];
            }
        }
        return (float)$val;
    }
    
    // Internal cache for master data (persists for the entire request)
    private $cachedMajors = null;
    private $cachedSubjects = null;
    private $cachedPriorityAreas = null;
    private $cachedPriorityObjects = null;
    private $cachedComboSubjects = [];
    private $cachedMajorCombos = [];
    private $cachedComboIds = [];
    private $cachedHashes = [];
    private $cachedHeSoHocBa = null;
    private $cachedTranscriptColToMonId = null;
    private $cachedAptitudeSubjectIds = [];
    private $cachedSubjectCodeToId = null;
    private $cachedSubjectIdToCode = null;

    // Bulk loading buffer
    private $bulkData = null;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->masterDataRepo = new MasterDataRepository();
        $this->thiSinhRepo = new ThiSinhRepository();
        $this->academicModel = new AcademicRecord();
        $this->diemThiModel = new DiemThiTHPT();
        
        // Load he so quy doi hoc ba tu cau hinh (dung cache neu co)
        $cacheKey = 'conf_he_so_hoc_ba';
        $val = \App\Services\CacheService::get($cacheKey);
        
        if ($val === null) {
            try {
                $stmt = $this->db->query("SELECT value FROM cau_hinh WHERE key = 'he_so_hoc_ba' LIMIT 1");
                $dbVal = $stmt ? $stmt->fetchColumn() : null;
                $val = ($dbVal !== null && $dbVal !== false) ? (float)$dbVal : 0.95;
                \App\Services\CacheService::set($cacheKey, $val, 3600); // Cache 1h
            } catch (\Exception $e) {
                $val = 0.95;
            }
        }
        $this->cachedHeSoHocBa = $val;
        
        // Optimize: Pre-load and cache subject-related mappings
        $this->initializeSubjectCaches();
    }

    private function initializeSubjectCaches() {
        // Cache Subjects
        $sKey = 'master_subjects';
        $subjects = \App\Services\CacheService::get($sKey);
        if ($subjects === null) {
            $subjects = $this->masterDataRepo->getSubjects();
            \App\Services\CacheService::set($sKey, $subjects, 1800);
        }
        $this->cachedSubjects = $subjects;

        // Cache Combinations
        $cKey = 'master_combos';
        $combos = \App\Services\CacheService::get($cKey);
        if ($combos === null) {
            $combos = [];
            try {
                $stmt = $this->db->query("SELECT id, ma_to_hop, mon_1_id, mon_2_id, mon_3_id FROM dm_to_hop");
                $combos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                \App\Services\CacheService::set($cKey, $combos, 1800);
            } catch (\Exception $e) {}
        }
        
        if (empty($this->cachedComboIds)) {
            foreach ($combos as $row) {
                $this->cachedComboIds[$row['ma_to_hop']] = $row['id'];
                $this->cachedComboSubjects[$row['ma_to_hop']] = [
                    'mon_1_id' => $row['mon_1_id'],
                    'mon_2_id' => $row['mon_2_id'],
                    'mon_3_id' => $row['mon_3_id']
                ];
            }
        }
        
        // Map code -> ID
        $this->cachedSubjectCodeToId = [];
        $this->cachedSubjectIdToCode = [];
        $this->cachedAptitudeSubjectIds = [];
        foreach($this->cachedSubjects as $s) {
            $id = $s['id'];
            $code = strtoupper($s['ma_mon']);
            $this->cachedSubjectCodeToId[$code] = $id;
            $this->cachedSubjectIdToCode[$id] = $code;
            
            if (in_array($code, ['NK1', 'NK2', 'NK3', 'NK4'])) {
                $this->cachedAptitudeSubjectIds[$id] = true;
            }
        }
        
        // Pre-calculate column-to-monID mapping for transcripts
        $aliases = $this->getSubjectAliases();
        $this->cachedTranscriptColToMonId = [];
        foreach ($aliases as $colName => $possibleCodes) {
            foreach ($possibleCodes as $code) {
                if (isset($this->cachedSubjectCodeToId[$code])) {
                    $this->cachedTranscriptColToMonId[$colName] = $this->cachedSubjectCodeToId[$code];
                    break;
                }
            }
        }
    }

    /**
     * Get all eligible candidate IDs for a session
     * Smart Implementation: Only returns 'dirty' candidates (missing or changed info)
     * unless $force = true
     */
    public function getCandidateIds($sessionId, $force = false) {
        $sql = "
            SELECT DISTINCT nv.so_cccd 
            FROM nguyen_vong nv
            LEFT JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
            WHERE nv.dot_tuyen_sinh_id = ? 
            AND (nv.trang_thai = 'DaDuyet' OR nv.trang_thai = 'approved' OR nv.trang_thai LIKE '%Đã duyệt%')
        ";

        if (!$force) {
            // SQL Dirty Checking: Only need to process if:
            // 1. Never calculated (cs.id is null)
            // 2. Application/profile/scores updated (nv.updated_at > cs.updated_at)
            $sql .= "
                AND (
                    cs.id IS NULL
                    OR nv.updated_at > cs.updated_at
                )
            ";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Generates a unique fingerprint (MD5) of input data for a candidate
     */
    public function generateDataHash($cccd) {
        $transcriptData = "";
        $thptData = "";
        $applicationData = "";
        $candidateData = "";
        $certsData = "";
        $aptitudeData = "";
        $configData = "";

        $this->loadPriorityCaches();

        if ($this->bulkData) {
            $transcriptData = json_encode($this->bulkData['transcripts'][$cccd] ?? []);
            $thptData = json_encode($this->bulkData['thpt'][$cccd] ?? []);
            $applicationData = json_encode($this->bulkData['applications'][$cccd] ?? []);
            $cProfile = $this->bulkData['candidates_profile'][$cccd] ?? [];
            $candidateData = json_encode($cProfile);
            $certsData = json_encode($this->bulkData['certs'][$cccd] ?? []);
            $aptitudeData = json_encode($this->bulkData['aptitude'][$cccd] ?? []);

            $rawKV = $cProfile['khu_vuc_uu_tien'] ?? '';
            $rawDT = $cProfile['doi_tuong_uu_tien'] ?? '';
            $maKV = $this->normalizePriorityCode($rawKV);
            $maDT = $this->normalizePriorityCode($rawDT);
            $valKV = $this->cachedPriorityAreas[$maKV] ?? $this->cachedPriorityAreas[trim($rawKV)] ?? 0;
            $valDT = $this->cachedPriorityObjects[$maDT] ?? $this->cachedPriorityObjects[trim($rawDT)] ?? 0;

            $configData = json_encode([
                'he_so_hoc_ba' => $this->cachedHeSoHocBa,
                'val_kv' => $valKV,
                'val_dt' => $valDT
            ]);
        } else {
            // Lazy fallback (rarely used in batch)
            $transcriptData = json_encode($this->academicModel->getByCCCD($cccd));
            $thptData = json_encode($this->diemThiModel->getByCCCD($cccd));
            $applicationData = json_encode($this->getApplications($cccd));

            $stmt = $this->db->prepare("SELECT so_cccd, khu_vuc_uu_tien, doi_tuong_uu_tien, nam_tot_nghiep FROM thi_sinh WHERE so_cccd = ?");
            $stmt->execute([$cccd]);
            $cProfile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $candidateData = json_encode($cProfile);
            $certsData = json_encode($this->getCertificates($cccd));
            $aptitudeData = json_encode($this->getAptitudeScores($cccd));

            $rawKV = $cProfile['khu_vuc_uu_tien'] ?? '';
            $rawDT = $cProfile['doi_tuong_uu_tien'] ?? '';
            $maKV = $this->normalizePriorityCode($rawKV);
            $maDT = $this->normalizePriorityCode($rawDT);
            $valKV = $this->cachedPriorityAreas[$maKV] ?? $this->cachedPriorityAreas[trim($rawKV)] ?? 0;
            $valDT = $this->cachedPriorityObjects[$maDT] ?? $this->cachedPriorityObjects[trim($rawDT)] ?? 0;

            $configData = json_encode([
                'he_so_hoc_ba' => $this->cachedHeSoHocBa,
                'val_kv' => $valKV,
                'val_dt' => $valDT
            ]);
        }

        // Thêm hậu tố phiên bản để ép buộc tính lại toàn bộ khi công thức thay đổi (Cache Invalidation)
        return md5($transcriptData . $thptData . $applicationData . $candidateData . $certsData . $aptitudeData . $configData . "v14");
    }

    public function calculate($cccd, $sessionId = null, $returnOnly = false, $force = false, $skipThptCondition = false) {
        // 1. Fetch ALL Data (Uses bulkData if pre-loaded)
        $transcriptavgs = $this->calculateTranscriptAverages($cccd);
        $thptScores = $this->getThptScores($cccd);
        $certificates = $this->getCertificates($cccd, $sessionId);
        $aptitudeScores = $this->getAptitudeScores($cccd, $sessionId);
        
        // 1b. Calculate Priority Points (Area + Object)
        $priorityPoints = $this->calculatePriorityPoints($cccd, $sessionId);
        
        // 1c. Dirty Checking (Incremental Calculation)
        $currentHash = $this->generateDataHash($cccd);
        
        // 2. Get Applications (Nguyen Vong)
        $applications = $this->getApplications($cccd);
        
        if (empty($applications)) return [];

        $results = [];
        foreach ($applications as $app) {
            $nvId = $app['id'];
            
            // TỐI ƯU HÓA: Sử dụng Dirty Checking (Duy trì hash cũ nếu data không đổi)
            // Nếu $force là true (ví dụ khi sửa công thức), ta BỎ QUA check hash để tính lại toàn bộ.
            if (!$force && isset($this->cachedHashes[$nvId]) && $this->cachedHashes[$nvId] === $currentHash) {
                continue;
            }

            $majorCode = $app['ma_nganh'];
            $majorDetails = $this->getMajorDetails($majorCode);
            if (!$majorDetails) continue;

            $combinations = $this->getMajorCombinations($majorCode);
            
            $bestScore = 0;
            $bestCombo = null;
            $bestMethod = null;
            $bestDetails = [];
            $allCombinationsParams = [];
            $bestAdmitted = false;
            $thresholdResult = null;

            // 3. Iterate Combinations
            foreach ($combinations as $comboCode) {
                $comboSubjects = $this->getComboSubjects($comboCode);
                if (!$comboSubjects) continue;

                // --- Kiểm tra phương thức HỌC BẠ (200) ---
                $hbResult = $this->calculateMethodScore('HOC_BA', $comboSubjects, $transcriptavgs, $certificates, $aptitudeScores, $majorDetails, $priorityPoints);
                if ($hbResult) {
                    $allCombinationsParams["HB_{$comboCode}"] = $hbResult['total_raw'];
                    $hbResult['details']['combo_code'] = $comboCode;
                    $hbThreshold = $this->checkAdmissionThresholdInternal($cccd, $majorCode, $majorDetails, $hbResult['total'], '200', $hbResult['total_raw'], $skipThptCondition, $hbResult['details']);
                    
                    $isBetter = false;
                    if ($hbThreshold['passed']) {
                        // Nếu đạt ngưỡng, so sánh với bestScore hiện tại (nếu bestScore cũng đạt ngưỡng)
                        if ($bestAdmitted && $hbResult['total'] > $bestScore) $isBetter = true;
                        if (!$bestAdmitted) $isBetter = true; // Ưu tiên cái đạt ngưỡng đầu tiên
                    } else if (!$bestAdmitted && $hbResult['total'] > $bestScore) {
                        // Nếu chưa có cái nào đạt ngưỡng, chọn cái cao nhất
                        $isBetter = true;
                    }

                    if ($isBetter) {
                        $bestScore = $hbResult['total'];
                        $bestCombo = $comboCode;
                        $bestMethod = '200';
                        $bestDetails = $hbResult['details'];
                        $bestAdmitted = $hbThreshold['passed'];
                        $thresholdResult = $hbThreshold;
                    }
                }

                // --- Kiểm tra phương thức ĐIỂM THI THPT (100) ---
                if (!empty($thptScores)) {
                    $thptResult = $this->calculateMethodScore('DIEM_THI', $comboSubjects, $thptScores, $certificates, $aptitudeScores, $majorDetails, $priorityPoints);
                    if ($thptResult) {
                        $allCombinationsParams["THPT_{$comboCode}"] = $thptResult['total_raw'];
                        $thptResult['details']['combo_code'] = $comboCode;
                        $thptThreshold = $this->checkAdmissionThresholdInternal($cccd, $majorCode, $majorDetails, $thptResult['total'], '100', $thptResult['total_raw'], $skipThptCondition, $thptResult['details']);
                        
                        $isBetter = false;
                        if ($thptThreshold['passed']) {
                            if ($bestAdmitted && $thptResult['total'] > $bestScore) $isBetter = true;
                            if (!$bestAdmitted) $isBetter = true;
                        } else if (!$bestAdmitted && $thptResult['total'] > $bestScore) {
                            $isBetter = true;
                        }

                        if ($isBetter) {
                            $bestScore = $thptResult['total'];
                            $bestCombo = $comboCode;
                            $bestMethod = '100';
                            $bestDetails = $thptResult['details'];
                            $bestAdmitted = $thptThreshold['passed'];
                            $thresholdResult = $thptThreshold;
                        }
                    }
                }
            }

            $details = $bestDetails;
            $details['all_combinations'] = $allCombinationsParams;
            
            $admitted = $bestAdmitted;
            if ($thresholdResult && !$thresholdResult['passed']) {
                $details['threshold_note'] = implode('; ', $thresholdResult['errors']);
                $admitted = false;
            }
            
            $certActuallyUsed = false;
            foreach ($details as $k => $d) {
                if (is_array($d) && isset($d['source']) && $d['source'] === 'CERT') {
                    $certActuallyUsed = true;
                    break;
                }
            }
            $finalMethodCode = \App\Helpers\AdmissionMethodHelper::resolvePhuongThuc($bestMethod ?? '', $majorDetails, $certActuallyUsed);
            $resultItem = [
                'cccd' => $cccd,
                'nv_id' => $nvId,
                'ma_nganh' => $majorCode,
                'score' => $bestScore,
                'combo' => $bestCombo,
                'method' => $finalMethodCode,
                'details' => $details,
                'data_hash' => $currentHash,
                'admitted' => $admitted,
            ];

            if ($returnOnly) {
                $results[] = $resultItem;
            } else {
                $this->updateApplicationScore($nvId, $bestScore, $bestCombo, $finalMethodCode, $details, $currentHash, $admitted);
            }
        }
        return $results;
    }


    /**
     * High-Performance Chunk Recalculation (Targeted at 15k+ scaling)
     */
    public function recalculateBatch($sessionId, array $cccds, $force = false, $skipThptCondition = false) {
        if (empty($cccds)) return 0;
        
        try {
            // 1. Bulk load data for this specific chunk
            $this->loadBatchDataPartial($sessionId, $cccds);
            
            $allResults = []; // Khởi tạo mảng kết quả
            
            foreach ($cccds as $cccd) {
                // Pass $force and $skipThptCondition through to calculate()
                $results = $this->calculate($cccd, $sessionId, true, $force, $skipThptCondition);
                if (!empty($results)) {
                    $allResults = array_merge($allResults, $results);
                }
            }
            
            // 2. High-speed Bulk Update
            if (!empty($allResults)) {
                return $this->saveScoresBulk($sessionId, $allResults);
            }
            return 0;
        } catch (\Throwable $e) {
            $msg = date('Y-m-d H:i:s') . " - recalculateBatch Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
            file_put_contents('error_log_service.txt', $msg, FILE_APPEND);
            throw $e;
        } finally {
            // Memory Management: Clear chunk-specific buffers
            $this->bulkData = null;
        }
    }

    /**
     * Partial load for chunked processing
     */
    protected function loadBatchDataPartial($sessionId, array $cccds) {
        $this->bulkData = [
            'candidates' => $cccds,
            'candidates_profile' => [],
            'transcripts' => [],
            'thpt' => [],
            'certs' => [],
            'aptitude' => [],
            'priority' => [],
            'applications' => [],
            'hashes' => []
        ];
        
        $placeholders = implode(',', array_fill(0, count($cccds), '?'));

        // Load Previous Hashes
        $stmtH = $this->db->prepare("SELECT nguyen_vong_id, data_hash FROM v_calc_summary WHERE nguyen_vong_id IN (SELECT id FROM nguyen_vong WHERE so_cccd IN ($placeholders) AND dot_tuyen_sinh_id = ?)");
        $stmtH->execute(array_merge($cccds, [$sessionId]));
        foreach ($stmtH->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->cachedHashes[$row['nguyen_vong_id']] = $row['data_hash'];
        }

        // Load Applications
        $stmt = $this->db->prepare("SELECT * FROM nguyen_vong WHERE dot_tuyen_sinh_id = ? AND so_cccd IN ($placeholders)");
        $stmt->execute(array_merge([$sessionId], $cccds));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['applications'][$row['so_cccd']][] = $row;
        }

        // Load Transcripts
        $stmt = $this->db->prepare("SELECT * FROM ket_qua_hoc_tap WHERE so_cccd IN ($placeholders)");
        $stmt->execute($cccds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['transcripts'][$row['so_cccd']][] = $row;
        }

        // Load THPT Scores
        $stmt = $this->db->prepare("SELECT * FROM diem_thi_thpt WHERE so_cccd IN ($placeholders)");
        $stmt->execute($cccds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['thpt'][$row['so_cccd']] = $row;
        }

        // Load Certificates (Imported pre-calculated scores)
        $stmt = $this->db->prepare("
            SELECT d.so_cccd, m.id as mon_id, d.diem 
            FROM diem_chung_chi d
            JOIN dm_mon m ON d.ma_mon = m.ma_mon
            WHERE d.so_cccd IN ($placeholders) AND d.dot_tuyen_sinh_id = ?
        ");
        $stmt->execute(array_merge($cccds, [$sessionId]));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['certs'][$row['so_cccd']][$row['mon_id']] = (float)$row['diem'];
        }


        // Load Aptitude
        $stmt = $this->db->prepare("
            SELECT d.so_cccd, m.id as mon_id, d.diem 
            FROM diem_nang_khieu d
            JOIN dm_mon m ON d.ma_mon = m.ma_mon
            WHERE d.so_cccd IN ($placeholders) AND d.dot_tuyen_sinh_id = ?
        ");
        $stmt->execute(array_merge($cccds, [$sessionId]));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['aptitude'][$row['so_cccd']][$row['mon_id']] = (float)$row['diem'];
        }

        // Load Priority Areas/Objects (Quy chế: ưu tiên KV áp dụng cho năm TN < 2023 hoặc năm TN + 1)
        $stmtThiSinh = $this->db->prepare("SELECT ts.so_cccd, ts.khu_vuc_uu_tien, ts.doi_tuong_uu_tien, ts.nam_tot_nghiep, ts.ma_tinh_lop_12, ts.ma_truong_lop_12, ts.is_dac_cach, t.khu_vuc as kv_truong FROM thi_sinh ts LEFT JOIN dm_truong_thpt t ON ts.ma_truong_lop_12 = t.ma_truong WHERE ts.so_cccd IN ($placeholders)");
        $stmtThiSinh->execute($cccds);
        $this->loadPriorityCaches();
        
        // Lấy năm tuyển sinh từ session
        $currentYear = (int)date('Y');
        static $sessionYearsCache = [];
        if (!isset($sessionYearsCache[$sessionId])) {
            $stmtYear = $this->db->prepare("SELECT nam_tuyen_sinh FROM dot_tuyen_sinh WHERE id = ?");
            $stmtYear->execute([$sessionId]);
            $y = $stmtYear->fetchColumn();
            $sessionYearsCache[$sessionId] = $y ? (int)$y : (int)date('Y');
        }
        $currentYear = $sessionYearsCache[$sessionId];
        
        $isChinhThuc = $this->isChinhThucSession($sessionId);
        foreach ($stmtThiSinh->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['candidates_profile'][$row['so_cccd']] = $row;
            
            // Đối với đợt chính thức, chỉ lấy khu vực ưu tiên theo import từ Bộ GD (thi_sinh.khu_vuc_uu_tien)
            // Không tự động fall back về trường THPT
            $rawKV = $row['khu_vuc_uu_tien'];
            if (!$isChinhThuc && empty($rawKV)) {
                $rawKV = $row['kv_truong'] ?? '';
            }
            
            $rawDT = $row['doi_tuong_uu_tien'] ?? '';
            $namTN = $row['nam_tot_nghiep'] ?? null;
            
            $maKV = $this->normalizePriorityCode($rawKV);
            $maDT = $this->normalizePriorityCode($rawDT);
            
            // Ưu tiên Khu vực: áp dụng nếu trong hạn (năm TN hoặc năm TN + 1)
            $diemKV = 0;
            $kvEligible = ($namTN === null || $namTN === '' || ($currentYear - (int)$namTN) <= 1);
            if ($kvEligible) {
                $diemKV = $this->cachedPriorityAreas[$maKV] ?? $this->cachedPriorityAreas[trim($rawKV)] ?? 0;
            }
            // Ưu tiên Đối tượng: luôn áp dụng
            $diemDT = $this->cachedPriorityObjects[$maDT] ?? $this->cachedPriorityObjects[trim($rawDT)] ?? 0;
            
            $this->bulkData['priority'][$row['so_cccd']] = $diemKV + $diemDT;
        }
    }

    /**
     * ATOMIC HIGH-SPEED BULK UPDATE to v_calc_summary
     */
    protected function saveScoresBulk($sessionId, array $results) {
        if (empty($results)) return 0;
        
        $this->db->beginTransaction();
        try {
            $this->db->exec("CREATE TEMPORARY TABLE temp_calc_results (
                nv_id BIGINT,
                score DECIMAL(10,3),
                combo VARCHAR(20),
                combo_id INT,
                method VARCHAR(50),
                details TEXT,
                m1 DECIMAL(10,3),
                m2 DECIMAL(10,3),
                m3 DECIMAL(10,3),
                prio_raw DECIMAL(10,3),
                prio_qd DECIMAL(10,3),
                is_passed BOOLEAN,
                d_hash VARCHAR(64)
            ) ON COMMIT DROP");

            $sql = "INSERT INTO temp_calc_results (nv_id, score, combo, combo_id, method, details, m1, m2, m3, prio_raw, prio_qd, is_passed, d_hash) VALUES ";
            $placeholders = [];
            $values = [];
            
            foreach ($results as $r) {
                $combo = $r['combo'];
                $comboId = null;
                if ($combo) {
                    if (!array_key_exists($combo, $this->cachedComboIds)) {
                        $stmtC = $this->db->prepare("SELECT id FROM dm_to_hop WHERE ma_to_hop = ? LIMIT 1");
                        $stmtC->execute([$combo]);
                        $this->cachedComboIds[$combo] = $stmtC->fetchColumn() ?: null;
                    }
                    $comboId = $this->cachedComboIds[$combo];
                }
                
                $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $values[] = $r['nv_id'];
                $values[] = $r['score'];
                $values[] = $combo;
                $values[] = $comboId;
                $values[] = $r['method'];
                $values[] = json_encode($r['details'], JSON_UNESCAPED_UNICODE);
                $values[] = $r['details']['diem_mon_1'] ?? 0;
                $values[] = $r['details']['diem_mon_2'] ?? 0;
                $values[] = $r['details']['diem_mon_3'] ?? 0;
                $values[] = $r['details']['priority_raw'] ?? 0;
                $values[] = $r['details']['priority_converted'] ?? 0;
                $values[] = $r['admitted'] ? 1 : 0;
                $values[] = $r['data_hash'];

                if (count($placeholders) >= 100) {
                    $stmt = $this->db->prepare($sql . implode(',', $placeholders));
                    $stmt->execute($values);
                    $placeholders = [];
                    $values = [];
                }
            }
            
            if (!empty($placeholders)) {
                $stmt = $this->db->prepare($sql . implode(',', $placeholders));
                $stmt->execute($values);
            }

            // UPSERT into v_calc_summary
            $upsertSql = "
                INSERT INTO v_calc_summary (
                    nguyen_vong_id, dot_tuyen_sinh_id, diem_xet_tuyen, to_hop_toi_uu, phuong_thuc_toi_uu,
                    chi_tiet_diem, data_hash, diem_mon_1, diem_mon_2, diem_mon_3,
                    diem_uu_tien_goc, diem_uu_tien_qd, trang_thai_do, updated_at
                )
                SELECT 
                    tmp.nv_id, nv.dot_tuyen_sinh_id, tmp.score, tmp.combo, tmp.method,
                    CAST(tmp.details AS JSONB), tmp.d_hash, tmp.m1, tmp.m2, tmp.m3,
                    tmp.prio_raw, tmp.prio_qd, tmp.is_passed, CURRENT_TIMESTAMP
                FROM temp_calc_results tmp
                JOIN nguyen_vong nv ON tmp.nv_id = nv.id
                ON CONFLICT (nguyen_vong_id) DO UPDATE SET
                    dot_tuyen_sinh_id = EXCLUDED.dot_tuyen_sinh_id,
                    diem_xet_tuyen = EXCLUDED.diem_xet_tuyen,
                    to_hop_toi_uu = EXCLUDED.to_hop_toi_uu,
                    phuong_thuc_toi_uu = EXCLUDED.phuong_thuc_toi_uu,
                    chi_tiet_diem = EXCLUDED.chi_tiet_diem,
                    data_hash = EXCLUDED.data_hash,
                    diem_mon_1 = EXCLUDED.diem_mon_1,
                    diem_mon_2 = EXCLUDED.diem_mon_2,
                    diem_mon_3 = EXCLUDED.diem_mon_3,
                    diem_uu_tien_goc = EXCLUDED.diem_uu_tien_goc,
                    diem_uu_tien_qd = EXCLUDED.diem_uu_tien_qd,
                    trang_thai_do = EXCLUDED.trang_thai_do,
                    updated_at = CURRENT_TIMESTAMP
            ";
            $count = $this->db->exec($upsertSql);
            
            $this->db->commit();
            return $count;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * Legacy method preserved for compatibility but now suboptimal
     */
    public function recalculateSession($sessionId, $force = false, $skipThptCondition = false) {
        $cccds = $this->getCandidateIds($sessionId, $force);
        return $this->recalculateBatch($sessionId, $cccds, $force, $skipThptCondition);
    }


    protected function loadBatchData($sessionId) {
        $this->bulkData = [
            'candidates' => [],
            'candidates_profile' => [],
            'transcripts' => [],
            'thpt' => [],
            'certs' => [],
            'aptitude' => [],
            'priority' => [],
            'applications' => []
        ];

        // 1. Get ALL approved candidates and their applications in this session
        $stmt = $this->db->prepare("SELECT DISTINCT so_cccd FROM nguyen_vong WHERE dot_tuyen_sinh_id = ? AND (trang_thai = 'DaDuyet' OR trang_thai = 'approved' OR trang_thai LIKE '%Đã duyệt%')");
        $stmt->execute([$sessionId]);
        $candidates = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($candidates)) return;
        
        $this->bulkData['candidates'] = $candidates;
        $placeholders = implode(',', array_fill(0, count($candidates), '?'));

        // 2. Bulk load Applications
        $stmt = $this->db->prepare("SELECT * FROM nguyen_vong WHERE dot_tuyen_sinh_id = ? AND so_cccd IN ($placeholders)");
        $stmt->execute(array_merge([$sessionId], $candidates));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['applications'][$row['so_cccd']][] = $row;
        }

        // 3. Bulk load Transcripts
        $stmt = $this->db->prepare("SELECT * FROM ket_qua_hoc_tap WHERE so_cccd IN ($placeholders)");
        $stmt->execute($candidates);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['transcripts'][$row['so_cccd']][] = $row;
        }

        // 4. Bulk load THPT Scores
        $stmt = $this->db->prepare("SELECT * FROM diem_thi_thpt WHERE so_cccd IN ($placeholders)");
        $stmt->execute($candidates);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['thpt'][$row['so_cccd']] = $row;
        }

        // 5. Bulk load Certificates (Imported pre-calculated scores)
        $stmt = $this->db->prepare("
            SELECT d.so_cccd, m.id as mon_id, d.diem 
            FROM diem_chung_chi d
            JOIN dm_mon m ON d.ma_mon = m.ma_mon
            WHERE d.so_cccd IN ($placeholders) AND d.dot_tuyen_sinh_id = ?
        ");
        $stmt->execute(array_merge($candidates, [$sessionId]));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['certs'][$row['so_cccd']][$row['mon_id']] = (float)$row['diem'];
        }

        // 6. Bulk load Aptitude
        $stmt = $this->db->prepare("
            SELECT d.so_cccd, m.id as mon_id, d.diem 
            FROM diem_nang_khieu d
            JOIN dm_mon m ON d.ma_mon = m.ma_mon
            WHERE d.so_cccd IN ($placeholders) AND d.dot_tuyen_sinh_id = ?
        ");
        $stmt->execute(array_merge($candidates, [$sessionId]));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['aptitude'][$row['so_cccd']][$row['mon_id']] = (float)$row['diem'];
        }

        // 7. Bulk load Priority (Quy chế: ưu tiên KV áp dụng cho năm TN < 2023 hoặc năm TN + 1)
        $stmt = $this->db->prepare("SELECT ts.so_cccd, ts.khu_vuc_uu_tien, ts.doi_tuong_uu_tien, ts.nam_tot_nghiep, ts.ma_tinh_lop_12, ts.ma_truong_lop_12, ts.is_dac_cach, t.khu_vuc as kv_truong FROM thi_sinh ts LEFT JOIN dm_truong_thpt t ON ts.ma_truong_lop_12 = t.ma_truong WHERE ts.so_cccd IN ($placeholders)");
        $stmt->execute($candidates);
        $this->loadPriorityCaches();
        
        $currentYear = (int)date('Y');
        // Lấy năm tuyển sinh thực tế từ dot_tuyen_sinh
        $stmtYear = $this->db->prepare("SELECT nam_tuyen_sinh FROM dot_tuyen_sinh WHERE id = ?");
        $stmtYear->execute([$sessionId]);
        $y = $stmtYear->fetchColumn();
        if ($y) {
            $currentYear = (int)$y;
        }

        $isChinhThuc = $this->isChinhThucSession($sessionId);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['candidates_profile'][$row['so_cccd']] = $row;
            
            // Đối với đợt chính thức, chỉ lấy khu vực ưu tiên theo import từ Bộ GD (thi_sinh.khu_vuc_uu_tien)
            // Không tự động fall back về trường THPT
            $rawKV = $row['khu_vuc_uu_tien'];
            if (!$isChinhThuc && empty($rawKV)) {
                $rawKV = $row['kv_truong'] ?? '';
            }
            
            $rawDT = $row['doi_tuong_uu_tien'] ?? '';
            $namTN = $row['nam_tot_nghiep'] ?? null;
            
            $maKV = $this->normalizePriorityCode($rawKV);
            $maDT = $this->normalizePriorityCode($rawDT);
            
            // Ưu tiên Khu vực: áp dụng nếu trong hạn (năm TN hoặc năm TN + 1)
            $diemKV = 0;
            $kvEligible = ($namTN === null || $namTN === '' || ($currentYear - (int)$namTN) <= 1);
            if ($kvEligible) {
                $diemKV = $this->cachedPriorityAreas[$maKV] ?? $this->cachedPriorityAreas[trim($rawKV)] ?? 0;
            }
            // Ưu tiên Đối tượng: luôn áp dụng
            $diemDT = $this->cachedPriorityObjects[$maDT] ?? $this->cachedPriorityObjects[trim($rawDT)] ?? 0;
            
            $this->bulkData['priority'][$row['so_cccd']] = $diemKV + $diemDT;
        }
    }
    protected function loadPriorityCaches() {
        if ($this->cachedPriorityAreas === null) {
            $areas = $this->masterDataRepo->getPriorityAreas();
            $this->cachedPriorityAreas = [];
            foreach ($areas as $k => $v) {
                $this->cachedPriorityAreas[strtolower(trim($k))] = $v;
            }
        }
        if ($this->cachedPriorityObjects === null) {
            $objects = $this->masterDataRepo->getPriorityObjects();
            $this->cachedPriorityObjects = [];
            foreach ($objects as $k => $v) {
                $this->cachedPriorityObjects[strtolower(trim($k))] = $v;
            }
        }
    }

    protected function normalizePriorityCode($code) {
        if (!$code) return '';
        $s = strtolower(trim((string)$code));
        // Loại bỏ tiền tố KV, DT (VD: KV2 -> 2, KV2-NT -> 2nt)
        $s = preg_replace('/^(kv|dt)/i', '', $s);
        $s = str_replace(['-', '_'], '', $s);
        // Giữ nguyên dạng gốc: '04' → '04', '04b' → '04b', '2' → '2', '2nt' → '2nt'
        return $s;
    }

    protected function isChinhThucSession($sessionId) {
        if (!$sessionId) return false;
        static $sessionTypes = [];
        if (!isset($sessionTypes[$sessionId])) {
            $stmt = $this->db->prepare("SELECT loai_xet_tuyen FROM dot_tuyen_sinh WHERE id = ?");
            $stmt->execute([$sessionId]);
            $val = $stmt->fetchColumn();
            $sessionTypes[$sessionId] = ($val === 'chinh_thuc');
        }
        return $sessionTypes[$sessionId];
    }

    protected function calculatePriorityPoints($cccd, $sessionId = null) {
        if ($this->bulkData) {
            return $this->bulkData['priority'][$cccd] ?? 0;
        }

        // Fetch Candidate Priority Info + năm tốt nghiệp
        $stmt = $this->db->prepare("SELECT ts.khu_vuc_uu_tien, ts.doi_tuong_uu_tien, ts.nam_tot_nghiep, t.khu_vuc as kv_truong FROM thi_sinh ts LEFT JOIN dm_truong_thpt t ON ts.ma_truong_lop_12 = t.ma_truong WHERE ts.so_cccd = ?");
        $stmt->execute([$cccd]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$candidate) return 0;
        
        $diemKhuVuc = 0;
        $diemDoiTuong = 0;
        
        $this->loadPriorityCaches();

        // Quy chế: Ưu tiên KV áp dụng nếu trong hạn (năm TN hoặc năm TN + 1)
        $namTN = $candidate['nam_tot_nghiep'] ?? null;
        
        $currentYear = (int)date('Y');
        if ($sessionId) {
            static $sessionYears = [];
            if (!isset($sessionYears[$sessionId])) {
                $stmt = $this->db->prepare("SELECT nam_tuyen_sinh FROM dot_tuyen_sinh WHERE id = ?");
                $stmt->execute([$sessionId]);
                $y = $stmt->fetchColumn();
                $sessionYears[$sessionId] = $y ? (int)$y : (int)date('Y');
            }
            $currentYear = $sessionYears[$sessionId];
        }
        
        $kvEligible = ($namTN === null || $namTN === '' || ($currentYear - (int)$namTN) <= 1);

        $isChinhThuc = $this->isChinhThucSession($sessionId);
        // Get Area Points (chỉ nếu còn trong hạn)
        // Đối với đợt chính thức, chỉ lấy khu vực ưu tiên theo import từ Bộ GD (thi_sinh.khu_vuc_uu_tien)
        // Không tự động fall back về trường THPT
        $rawKV = $candidate['khu_vuc_uu_tien'];
        if (!$isChinhThuc && empty($rawKV)) {
            $rawKV = $candidate['kv_truong'] ?? '';
        }
        if ($kvEligible && !empty($rawKV)) {
            $maKV = $this->normalizePriorityCode($rawKV);
            if (isset($this->cachedPriorityAreas[$maKV])) {
                $diemKhuVuc = $this->cachedPriorityAreas[$maKV];
            } else if (isset($this->cachedPriorityAreas[trim($rawKV)])) {
                $diemKhuVuc = $this->cachedPriorityAreas[trim($rawKV)];
            }
        }
        
        // Get Object Points (luôn áp dụng, không phụ thuộc năm TN)
        if (!empty($candidate['doi_tuong_uu_tien'])) {
            $rawDT = $candidate['doi_tuong_uu_tien'];
            $maDT = $this->normalizePriorityCode($rawDT);
            if (isset($this->cachedPriorityObjects[$maDT])) {
                $diemDoiTuong = $this->cachedPriorityObjects[$maDT];
            } else if (isset($this->cachedPriorityObjects[trim($rawDT)])) {
                $diemDoiTuong = $this->cachedPriorityObjects[trim($rawDT)];
            }
        }
        return $diemKhuVuc + $diemDoiTuong;
    }

    protected function getMajorDetails($majorCode) {
        if ($this->cachedMajors === null) {
            $majors = $this->masterDataRepo->getMajors();
            $this->cachedMajors = array_column($majors, null, 'ma_nganh'); // O(1) Hashmap
        }
        return $this->cachedMajors[$majorCode] ?? null;
    }

    protected function getMajorCombinations($majorCode) {
        if (!isset($this->cachedMajorCombos[$majorCode])) {
            $this->cachedMajorCombos[$majorCode] = $this->masterDataRepo->getMajorCombinations($majorCode);
        }
        return $this->cachedMajorCombos[$majorCode];
    }

    private function getSubjectAliases() {
        return [
            'toan' => ['TOAN', 'TO'],
            'van' => ['VAN', 'NGU_VAN', 'VA'],
            'ngoai_ngu' => ['ANH', 'TIENG_ANH', 'NGOAI_NGU', 'TA', 'NN', 'N1', 'N2', 'N3', 'N4', 'N5', 'N6'],
            'ly' => ['LY', 'VAT_LY', 'VAT LI', 'LI'],
            'hoa' => ['HOA', 'HOA_HOC', 'HO'],
            'sinh' => ['SINH', 'SINH_HOC', 'SI'],
            'su' => ['SU', 'LICH_SU'],
            'dia' => ['DIA', 'DIA_LY', 'DI'],
            'gdcd' => ['GDCD', 'GD'],
            'ktpl' => ['GDKT_PL', 'KTPL', 'GDKTPL'],
            'cong_nghe' => ['CONG_NGHE', 'CN'],
            'tin_hoc' => ['TIN', 'TIN_HOC', 'TH']
        ];
    }

    protected function calculateTranscriptAverages($cccd) {
        $records = [];
        if ($this->bulkData) {
            $records = $this->bulkData['transcripts'][$cccd] ?? [];
        } else {
            $records = $this->academicModel->getByCCCD($cccd);
        }
        
        $colToMonId = $this->cachedTranscriptColToMonId;

        $sums = []; 
        $counts = [];

        foreach ($records as $r) {
            foreach ($colToMonId as $colKey => $monId) {
                $colPrefix = ($colKey == 'ngoai_ngu') ? "diem_ngoai_ngu" : "diem_{$colKey}";
                // Hỗ trợ cả điểm trung bình bộ môn cả năm nếu có
                $valCn = isset($r["{$colPrefix}_cn"]) && $r["{$colPrefix}_cn"] !== '' ? (float)$r["{$colPrefix}_cn"] : null;

                // Đối với Ngoại ngữ, xử lý chính xác theo mã môn ngoại ngữ 1 và ngoại ngữ 2
                if ($colKey === 'ngoai_ngu') {
                    // Extract language codes
                    $maNn1 = 'N1';
                    $maNn2 = '';
                    if (!empty($r['ghi_chu'])) {
                        if (preg_match('/ma_nn1:([^;]*)/', $r['ghi_chu'], $matches)) {
                            $maNn1 = strtoupper(trim($matches[1]));
                        }
                        if (preg_match('/ma_nn2:([^;]*)/', $r['ghi_chu'], $matches)) {
                            $maNn2 = strtoupper(trim($matches[1]));
                        }
                    } else {
                        // Fallback check from diem_thi_thpt
                        $dtRecord = null;
                        if ($this->bulkData) {
                            $dtRecord = $this->bulkData['thpt'][$cccd] ?? null;
                        } else {
                            $dtRecord = $this->diemThiModel->getByCCCD($cccd);
                        }
                        if ($dtRecord) {
                            $hasAnh = isset($dtRecord['tieng_anh']) && $dtRecord['tieng_anh'] !== null && $dtRecord['tieng_anh'] !== '';
                            $hasTrung = isset($dtRecord['tieng_trung']) && $dtRecord['tieng_trung'] !== null && $dtRecord['tieng_trung'] !== '';
                            if ($hasTrung && !$hasAnh) {
                                $maNn1 = 'N4';
                            } elseif ($hasAnh && !$hasTrung) {
                                $maNn1 = 'N1';
                            }
                        }
                    }

                    if (empty($maNn1)) $maNn1 = 'N1'; // Default fallback

                    // Map primary foreign language score to its specific code if valid (N1/N4)
                    $valNn1 = isset($r["diem_ngoai_ngu_cn"]) && $r["diem_ngoai_ngu_cn"] !== '' ? (float)$r["diem_ngoai_ngu_cn"] : null;
                    if ($valNn1 !== null && in_array($maNn1, ['N1', 'N4'])) {
                        $monId1 = $this->cachedSubjectCodeToId[$maNn1] ?? null;
                        if ($monId1) {
                            if (!isset($sums[$monId1])) { $sums[$monId1] = 0; $counts[$monId1] = 0; }
                            $sums[$monId1] += $valNn1;
                            $counts[$monId1]++;
                        }
                    }

                    // Map secondary foreign language score to its specific code if valid (N1/N4)
                    $valNn2 = isset($r["diem_ngoai_ngu_2_cn"]) && $r["diem_ngoai_ngu_2_cn"] !== '' ? (float)$r["diem_ngoai_ngu_2_cn"] : null;
                    if ($valNn2 !== null && in_array($maNn2, ['N1', 'N4'])) {
                        $monId2 = $this->cachedSubjectCodeToId[$maNn2] ?? null;
                        if ($monId2) {
                            if (!isset($sums[$monId2])) { $sums[$monId2] = 0; $counts[$monId2] = 0; }
                            $sums[$monId2] += $valNn2;
                            $counts[$monId2]++;
                        }
                    }
                    continue;
                }

                // Fallback chéo giữa GDCD và GDKTPL
                if ($colKey === 'gdcd' && $valCn === null) {
                    $valCn = isset($r["diem_ktpl_cn"]) && $r["diem_ktpl_cn"] !== '' ? (float)$r["diem_ktpl_cn"] : null;
                }
                if ($colKey === 'ktpl' && $valCn === null) {
                    $valCn = isset($r["diem_gdcd_cn"]) && $r["diem_gdcd_cn"] !== '' ? (float)$r["diem_gdcd_cn"] : null;
                }

                if ($valCn !== null) {
                    if (!isset($sums[$monId])) { $sums[$monId] = 0; $counts[$monId] = 0; }
                    $sums[$monId] += $valCn;
                    $counts[$monId]++;
                }
            }
        }
        
        $averages = [];
        foreach ($sums as $id => $total) {
            if ($counts[$id] === 3) {
                $averages[$id] = round($total / 3, 3);
            } else {
                $averages[$id] = 0.0;
            }
        }
        return $averages;
    }

    protected function getThptScores($cccd) {
        $record = null;
        if ($this->bulkData) {
            $record = $this->bulkData['thpt'][$cccd] ?? null;
        } else {
            $record = $this->diemThiModel->getByCCCD($cccd);
        }

        if (!$record) return [];

        $scores = [];
        $codeToId = $this->cachedSubjectCodeToId;

        $directMap = [
            'toan' => 'TO',
            'van' => 'VA',
            'tieng_anh' => 'N1',
            'tieng_trung' => 'N4',
            'ly' => 'LI',
            'hoa' => 'HO',
            'sinh' => 'SI',
            'su' => 'SU',
            'dia' => 'DI',
            'gdcd' => 'GDCD',
            'ktpl' => 'GDKTPL',
            'tin_hoc' => 'TH',
            'cnnn' => 'CN',
            'cong_nghe' => 'CN'
        ];

        foreach ($directMap as $dbCol => $subjectCode) {
            if (isset($record[$dbCol]) && $record[$dbCol] !== null && $record[$dbCol] !== '') {
                if (isset($codeToId[$subjectCode])) {
                    $scores[$codeToId[$subjectCode]] = (float)$record[$dbCol];
                }
            }
        }

        return $scores;
    }

    protected function getCertificates($cccd, $sessionId = null) {
        if ($this->bulkData) {
            return $this->bulkData['certs'][$cccd] ?? [];
        }

        if (!$sessionId) {
            $sessionModel = new \App\Models\AdmissionSession();
            $activeSession = $sessionModel->getActiveSession();
            $sessionId = $activeSession ? $activeSession['id'] : null;
        }

        if ($sessionId) {
            $sql = "SELECT m.id as mon_id, d.diem 
                    FROM diem_chung_chi d
                    JOIN dm_mon m ON d.ma_mon = m.ma_mon
                    WHERE d.so_cccd = ? AND d.dot_tuyen_sinh_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cccd, $sessionId]);
        } else {
            $sql = "SELECT m.id as mon_id, d.diem 
                    FROM diem_chung_chi d
                    JOIN dm_mon m ON d.ma_mon = m.ma_mon
                    WHERE d.so_cccd = ? AND d.dot_tuyen_sinh_id IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cccd]);
        }
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    protected function getAptitudeScores($cccd, $sessionId = null) {
        if ($this->bulkData) {
            return $this->bulkData['aptitude'][$cccd] ?? [];
        }

        if (!$sessionId) {
            $sessionModel = new \App\Models\AdmissionSession();
            $activeSession = $sessionModel->getActiveSession();
            $sessionId = $activeSession ? $activeSession['id'] : null;
        }

        if ($sessionId) {
            $sql = "SELECT m.id as mon_id, d.diem 
                    FROM diem_nang_khieu d
                    JOIN dm_mon m ON d.ma_mon = m.ma_mon
                    WHERE d.so_cccd = ? AND d.dot_tuyen_sinh_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cccd, $sessionId]);
        } else {
            $sql = "SELECT m.id as mon_id, d.diem 
                    FROM diem_nang_khieu d
                    JOIN dm_mon m ON d.ma_mon = m.ma_mon
                    WHERE d.so_cccd = ? AND d.dot_tuyen_sinh_id IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cccd]);
        }
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    protected function getApplications($cccd) {
        if ($this->bulkData) {
            return $this->bulkData['applications'][$cccd] ?? [];
        }

        $stmt = $this->db->prepare("SELECT * FROM nguyen_vong WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    protected function getComboSubjects($comboCode) {
        if (isset($this->cachedComboSubjects[$comboCode])) return $this->cachedComboSubjects[$comboCode];

        $stmt = $this->db->prepare("SELECT mon_1_id, mon_2_id, mon_3_id FROM dm_to_hop WHERE ma_to_hop = ?");
        $stmt->execute([$comboCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->cachedComboSubjects[$comboCode] = [$row['mon_1_id'], $row['mon_2_id'], $row['mon_3_id']];
        } else {
            $this->cachedComboSubjects[$comboCode] = null;
        }
        return $this->cachedComboSubjects[$comboCode];
    }

    protected function calculateMethodScore($method, $subjects, $scores, $certs, $aptitude, $majorDetails, $priorityPointsRaw) {
        $totalRaw = 0;
        $details = [];
        $monScores = []; // Trace individual subject points
        
        // Chỉ cho phép quy đổi chứng chỉ ngoại ngữ với phương thức 200 (Học bạ) VÀ ngành có cấu hình cho phép xét chứng chỉ
        $allowCert = ($method === 'HOC_BA' && !empty($majorDetails['co_xet_chung_chi']));
        
        $subjectIdx = 1;
        foreach ($subjects as $monId) {
            $baseScore = $scores[$monId] ?? 0;
            
            // Quy đổi hệ số học bạ theo cấu hình (mặc định 0.95)
            if ($method === 'HOC_BA') {
                $baseScore = round($baseScore * $this->cachedHeSoHocBa, 3);
            }

            $certScore = ($allowCert && isset($certs[$monId])) ? $certs[$monId] : 0;
            if ($certScore > 0 && $method === 'HOC_BA') {
                $certScore = round($certScore * $this->cachedHeSoHocBa, 3);
            }
            $aptitudeScore = isset($aptitude[$monId]) ? $aptitude[$monId] : null;
            
            // Nhận diện môn năng khiếu dựa trên ID đã cache (nhanh hơn so sánh chuỗi)
            $isAptitude = isset($this->cachedAptitudeSubjectIds[$monId]);

            $finalScore = 0;
            $source = 'MISSING';

            if ($isAptitude) {
                if ($aptitudeScore === null) {
                    return null; // Bắt buộc phải có điểm năng khiếu cho các ngành đặc thù
                }
                $finalScore = $aptitudeScore;
                $source = 'APTITUDE';
            } else {
                $finalScore = max($baseScore, $certScore);
                if ($finalScore > 0) {
                     $source = ($finalScore == $certScore && $certScore > $baseScore) ? 'CERT' : $method;
                }
            }

            if ($finalScore <= 0 && !$isAptitude) {
                return null; // Yêu cầu: Không đủ điểm 3 môn thì không tính tổ hợp này
            }

            $totalRaw += $finalScore;
            
            // LƯU Ý: Hiển thị ĐIỂM GỐC (chưa quy đổi 95%) để người dùng dễ đối soát dữ liệu
            if ($source === 'CERT') {
                $monScores['mon_'.$subjectIdx] = $certs[$monId] ?? 0;
            } else if ($source === 'APTITUDE') {
                $monScores['mon_'.$subjectIdx] = $aptitudeScore ?? 0;
            } else {
                $monScores['mon_'.$subjectIdx] = $scores[$monId] ?? 0;
            }
            $details['mon_'.$subjectIdx] = [
                'mon_id' => $monId,
                'raw' => $scores[$monId] ?? 0,
                'base_scaled' => $baseScore, 
                'cert' => $certScore, 
                'apt' => $aptitudeScore,
                'final' => $finalScore,
                'source' => $source
            ];
            $subjectIdx++;
        }
        
        // Công thức tính Điểm Ưu tiên Quy đổi của Bộ GD&ĐT (Áp dụng từ mốc 22.5 điểm)
        $priorityConverted = $priorityPointsRaw;
        if ($totalRaw >= 22.5) {
            $priorityConverted = ((30 - $totalRaw) / 7.5) * $priorityPointsRaw;
        }
        $priorityConverted = round($priorityConverted, 3);

        $totalFinal = round($totalRaw + $priorityConverted, 3);
        
        $details['priority_raw'] = $priorityPointsRaw;
        $details['priority_converted'] = $priorityConverted;
        $details['total_raw'] = $totalRaw;
        $details['diem_mon_1'] = $monScores['mon_1'] ?? 0;
        $details['diem_mon_2'] = $monScores['mon_2'] ?? 0;
        $details['diem_mon_3'] = $monScores['mon_3'] ?? 0;

        return ['total' => $totalFinal, 'total_raw' => $totalRaw, 'details' => $details];
    }
    
    /**
     * Kiểm tra ngưỡng đảm bảo chất lượng đầu vào theo quy chế Bộ GD&ĐT
     * 
     * @param string $cccd Số CCCD thí sinh
     * @param string $ma_nganh Mã ngành xét tuyển
     * @param array $majorDetails Thông tin ngành (nhom_nganh, nguong_hoc_luc, nguong_diem_thpt, nguong_diem_xtn, nguong_diem_hocba)
     * @param float $bestScore Điểm xét tuyển tổng (đã cộng ưu tiên)
     * @param string|null $bestMethod Phương thức xét tuyển ('100' = THPT, '200' = Học bạ)
     * @param array $details Thông tin chi tiết điểm tổ hợp (diem_mon_1, diem_mon_2, diem_mon_3, priority_raw)
     * @return array ['passed' => bool, 'errors' => string[]]
     */
    protected function checkAdmissionThresholdInternal($cccd, $ma_nganh, $majorDetails, $bestScore, $bestMethod = null, $totalRaw = 0, $skipThptCondition = false, $details = []) {
        $result = ['passed' => true, 'errors' => []];

        // 0. Kiểm tra quy chế nguồn tuyển đối với thí sinh tốt nghiệp từ năm 2026
        // Bỏ qua điều kiện này cho đợt Xét tuyển Học Bạ (thí sinh chưa thi THPT)
        if (!$skipThptCondition) {
            $namTN = null;
            if ($this->bulkData && isset($this->bulkData['candidates_profile'][$cccd])) {
                $namTN = $this->bulkData['candidates_profile'][$cccd]['nam_tot_nghiep'] ?? null;
            } else {
                try {
                    $stmt = $this->db->prepare("SELECT nam_tot_nghiep FROM thi_sinh WHERE so_cccd = ?");
                    $stmt->execute([$cccd]);
                    $namTN = $stmt->fetchColumn();
                } catch (\Exception $e) {}
            }

            if ($namTN !== null && $namTN !== '' && (int)$namTN >= 2026) {
                $err = "";
                if (!$this->checkThptExamSourceThreshold($cccd, $majorDetails, $bestMethod, $err)) {
                    $result['passed'] = false;
                    $result['errors'][] = "Vi phạm nguồn tuyển: " . $err;
                }
            }
        }
        
        $nhomNganh = $majorDetails['nhom_nganh'] ?? 'Khac';
        $nguongHocLuc = $majorDetails['nguong_hoc_luc'] ?? null;
        $nguongDiemTHPT = $majorDetails['nguong_diem_thpt'] ?? null;
        $nguongDiemXTN = $majorDetails['nguong_diem_xtn'] ?? null;
        $nguongDiemHocBa = $majorDetails['nguong_diem_hocba'] ?? null;
        $isTalentPedagogy = in_array((string)$ma_nganh, ['7140201', '7140206', '7140221', '7140222']);

        // Quy chế Bộ GD&ĐT: TS01 (100) và TS04 (THPT + Năng khiếu) KHÔNG xét học bạ Giỏi/Khá
        // Chỉ áp dụng điều kiện học lực cho TS02 (200) và TS05 (Học bạ + Năng khiếu)
        if ($bestMethod === '100') {
            $nguongHocLuc = null;
        }

        // TỰ ĐỘNG HÓA QUY CHẾ BỘ GD&ĐT CHO KHỐI NGÀNH SƯ PHẠM (PEDAGOGY)
        // Nếu là ngành Sư phạm (71) và chưa có ngưỡng trong DB, mặc định là GIỎI (chỉ áp dụng cho xét học bạ)
        if ($bestMethod !== '100' && strpos((string)$ma_nganh, '71') === 0 && !$nguongHocLuc) {
             $nguongHocLuc = 'Gioi';
             
             // Các ngành đặc thù (Âm nhạc, Mỹ thuật, TDTT) - Cho phép mức KHÁ
             $specificMajors = ['7140206', '7140221', '7140222'];
             if (in_array($ma_nganh, $specificMajors)) {
                 $nguongHocLuc = 'Kha';
             }
        }

        // 1. Check Học lực lớp 12
        if ($nguongHocLuc) {
             $hocLuc12 = null;
             $diemTb12 = null;
            
            // Extract from bulkData if available to avoid DB hit (Fix N+1 query)
            if ($this->bulkData && isset($this->bulkData['transcripts'][$cccd])) {
                foreach ($this->bulkData['transcripts'][$cccd] as $tr) {
                    if ($tr['lop'] == 12) {
                        $hocLuc12 = $tr['hoc_luc_ca_nam'] ?? null;
                        $diemTb12 = $tr['diem_tb_ca_nam'] ?? null;
                        break;
                    }
                }
            } else {
                $grade12 = $this->academicModel->getGrade12Summary($cccd);
                $hocLuc12 = $grade12['hoc_luc_ca_nam'] ?? null;
                $diemTb12 = $grade12['diem_tb_ca_nam'] ?? null;
            }

            if (empty($hocLuc12) && !empty($diemTb12)) {
                $dtb = floatval($diemTb12);
                if ($dtb >= 8.0) $hocLuc12 = 'Gioi';
                elseif ($dtb >= 6.5) $hocLuc12 = 'Kha';
                elseif ($dtb >= 5.0) $hocLuc12 = 'TrungBinh';
                else $hocLuc12 = 'Yeu';
            }
            
            // Kiểm tra điều kiện OR: Điểm xét tốt nghiệp THPT >= ngưỡng tốt nghiệp tương ứng (ví dụ: SP: 8.0/8.5, Y: 6.5)
            $passedByGraduationScore = false;
            $diemXTN = null;
            if ($nguongDiemXTN) {
                $diemXTN = $this->getDiemXetTotNghiep($cccd);
                if ($diemXTN !== null && $diemXTN >= $nguongDiemXTN) {
                    $passedByGraduationScore = true;
                }
            }

            if ($hocLuc12) {
                // Chuyển tất cả về chữ thường và cắt khoảng trắng để so sánh chính xác tuyệt đối
                $hocLucRank = [
                    'tốt' => 4, 'tot' => 4, 'giỏi' => 4, 'gioi' => 4,
                    'khá' => 3, 'kha' => 3,
                    'đạt' => 2, 'dat' => 2,
                    'trung bình' => 1, 'trungbinh' => 1, 'tb' => 1,
                    'chưa đạt' => 0, 'chua dat' => 0, 'yếu' => 0, 'yeu' => 0, 'kém' => 0, 'kem' => 0
                ];
                
                $searchRank = mb_strtolower(trim($hocLuc12), 'UTF-8');
                $searchRequired = mb_strtolower(trim($nguongHocLuc), 'UTF-8');
                
                $requiredRank = $hocLucRank[$searchRequired] ?? 0;
                $actualRank = $hocLucRank[$searchRank] ?? 0;
                
                if ($actualRank < $requiredRank && !$passedByGraduationScore) {
                    $result['passed'] = false;
                    $labels = ['Gioi' => 'Giỏi', 'Kha' => 'Khá', 'TrungBinh' => 'Trung bình', 'Yeu' => 'Yếu', 'Tot' => 'Tốt'];
                    $err = "Học lực lớp 12 đạt " . ($labels[$hocLuc12] ?? $hocLuc12) . " (Yêu cầu: " . ($labels[$nguongHocLuc] ?? $nguongHocLuc) . ")";
                    if ($nguongDiemXTN) {
                        $err .= " HOẶC Điểm xét tốt nghiệp THPT >= " . number_format($nguongDiemXTN, 2) . " (hiện tại: " . ($diemXTN !== null ? number_format($diemXTN, 2) : 'N/A') . ")";
                    }
                    $result['errors'][] = $err;
                }
            } else {
                if (!$passedByGraduationScore) {
                    $result['passed'] = false;
                    $err = "Thiếu thông tin Học lực lớp 12";
                    if ($nguongDiemXTN) {
                        $err .= " HOẶC Điểm xét tốt nghiệp THPT >= " . number_format($nguongDiemXTN, 2) . " (hiện tại: " . ($diemXTN !== null ? number_format($diemXTN, 2) : 'N/A') . ")";
                    }
                    $result['errors'][] = $err;
                }
            }
        }
        
        // 2. Check Tổng điểm 3 môn THPT theo tổ hợp (Đã cộng điểm ưu tiên)
        if ($bestMethod === '100' && $nguongDiemTHPT && !$isTalentPedagogy) {
            $diemSoSanh = $bestScore; // Dùng điểm xét tuyển đã tính ưu tiên
            
            if ($diemSoSanh > 0 && $diemSoSanh < $nguongDiemTHPT) {
                // Kiểm tra điều kiện OR: Điểm xét tốt nghiệp THPT >= ngưỡng
                // Quy chế: "HOẶC điểm xét TN THPT >= X.XX" (SP: 8.50, ĐD: 6.50, SP đặc thù: 6.50)
                $passedByXTN = false;
                if ($nguongDiemXTN) {
                    $diemXTN = $this->getDiemXetTotNghiep($cccd);
                    if ($diemXTN !== null && $diemXTN >= $nguongDiemXTN) {
                        $passedByXTN = true;
                    }
                }
                
                if (!$passedByXTN) {
                    $result['passed'] = false;
                    $errorMsg = "Tổng điểm xét tuyển (" . number_format($diemSoSanh, 2) . ") thấp hơn ngưỡng " . number_format($nguongDiemTHPT, 2);
                    if ($nguongDiemXTN) {
                        $diemXTN = $this->getDiemXetTotNghiep($cccd);
                        $errorMsg .= " và ĐXét TN (" . ($diemXTN !== null ? number_format($diemXTN, 2) : 'N/A') . ") < " . number_format($nguongDiemXTN, 2);
                    }
                    $result['errors'][] = $errorMsg;
                }
            }
        }
        
        // 3. Check ĐTB Học bạ cho ngành ngoài Sư phạm (Phương thức xét học bạ)
        // Quy chế Bộ GD&ĐT mục 3.1.2 khoản 3: ĐTB 3 năm của 3 môn tổ hợp chưa quy đổi + điểm ưu tiên >= ngưỡng (mặc định 18.0)
        if ($bestMethod === '200' && $nguongDiemHocBa && $nhomNganh === 'Khac') {
            $unscaledRaw = 0;
            $priorityRaw = 0;
            if (is_array($details)) {
                $unscaledRaw = ($details['diem_mon_1'] ?? 0) + ($details['diem_mon_2'] ?? 0) + ($details['diem_mon_3'] ?? 0);
                $priorityRaw = $details['priority_raw'] ?? 0;
            }
            $scoreToCompare = $unscaledRaw + $priorityRaw;
            
            if ($scoreToCompare > 0 && $scoreToCompare < $nguongDiemHocBa) {
                $result['passed'] = false;
                $result['errors'][] = "ĐTB học bạ 3 môn tổ hợp chưa quy đổi (" . number_format($scoreToCompare, 2) . ") thấp hơn ngưỡng " . number_format($nguongDiemHocBa, 2);
            }
        }
        
        // 4. Kiểm tra điều kiện bổ sung đối với các ngành sư phạm năng khiếu (TT06/2026)
        $isTalentPedagogy = in_array((string)$ma_nganh, ['7140201', '7140206', '7140221', '7140222']);
        if ($isTalentPedagogy && is_array($details)) {
            $comboCode = $details['combo_code'] ?? null;
            $culturalRaw = 0;
            $hasAptitude = false;
            
            // Duyệt qua 3 môn của tổ hợp
            for ($i = 1; $i <= 3; $i++) {
                $monInfo = $details['mon_' . $i] ?? null;
                if ($monInfo) {
                    $monId = $monInfo['mon_id'] ?? null;
                    if ($monId !== null && isset($this->cachedAptitudeSubjectIds[$monId])) {
                        $hasAptitude = true;
                    } else {
                        // Lấy điểm thô (raw) trước khi quy đổi hệ số học bạ (0.95) để so với sàn Bộ
                        $culturalRaw += $monInfo['raw'];
                    }
                }
            }
            
            if ($hasAptitude) {
                // Điểm ưu tiên gốc (scale 30)
                $priorityRaw = $details['priority_raw'] ?? 0;
                
                // Quy đổi điểm ưu tiên sang thang điểm 20: UT_qd = UT * 2/3
                $priorityScaled = $priorityRaw * 2.0 / 3.0;
                
                $culturalWithPriority = $culturalRaw + $priorityScaled;
                
                // Xác định ngưỡng tối thiểu cho tổng 2 môn văn hóa từ database (13.33 hoặc 12.67)
                $requiredCultural = $nguongDiemTHPT ? (float)$nguongDiemTHPT : (((string)$ma_nganh === '7140201' ) ? 13.33 : 12.67);
                $requiredTotal = round($requiredCultural * 1.5, 1);
                
                // a. Kiểm tra ngưỡng 2 môn văn hóa + ưu tiên * 2/3
                if (round($culturalWithPriority, 3) < $requiredCultural) {
                    $result['passed'] = false;
                    $result['errors'][] = "Tổng điểm 2 môn văn hóa tổ hợp " . ($comboCode ?: '') . " (" . number_format($culturalRaw, 2) . " + điểm UT quy đổi " . number_format($priorityScaled, 2) . " = " . number_format($culturalWithPriority, 2) . ") dưới ngưỡng tối thiểu " . number_format($requiredCultural, 2) . " (Yêu cầu Bộ GD&ĐT)";
                }
                
                // b. Kiểm tra tổng điểm xét tuyển (Đã quy đổi, bao gồm cả điểm ưu tiên) phải đạt điểm sàn của trường
                if ($bestScore > 0 && $bestScore < $requiredTotal) {
                    $result['passed'] = false;
                    $result['errors'][] = "Tổng điểm xét tuyển tổ hợp " . ($comboCode ?: '') . " (" . number_format($bestScore, 3) . ") dưới điểm sàn quy định " . number_format($requiredTotal, 2);
                }
            }
        }
        
        return $result;
    }

    /**
     * Kiểm tra quy chế nguồn tuyển đối với thí sinh tốt nghiệp từ năm 2026:
     * Quy chế Bộ GD&ĐT quy định ngưỡng sàn thi THPT đối với các ngành đào tạo:
     * - Xét bằng phương thức khác THPT (Học bạ, kết hợp):
     *   + Sư phạm (714): Sàn THPT từ 18.00 điểm (riêng GDTC, SPAN, SPMT từ 16.50 điểm).
     *   + Sức khỏe (772): Sàn THPT từ 20.00 điểm (riêng Điều dưỡng, Hộ sinh... từ 16.50 điểm).
     *   + Các ngành khác: Sàn THPT từ 15.00 điểm.
     */
    protected function checkThptExamSourceThreshold($cccd, $majorDetails = null, $bestMethod = null, &$errorMessage = '') {
        $isDacCach = false;
        if ($this->bulkData && isset($this->bulkData['candidates_profile'][$cccd])) {
            $isDacCach = !empty($this->bulkData['candidates_profile'][$cccd]['is_dac_cach']);
        } else {
            try {
                $stmt = $this->db->prepare("SELECT is_dac_cach FROM thi_sinh WHERE so_cccd = ?");
                $stmt->execute([$cccd]);
                $isDacCach = (bool)$stmt->fetchColumn();
            } catch (\Exception $e) {}
        }

        if ($isDacCach) {
            return true;
        }

        $record = null;
        if ($this->bulkData) {
            $record = $this->bulkData['thpt'][$cccd] ?? null;
        } else {
            $record = $this->diemThiModel->getByCCCD($cccd);
        }

        if (!$record) {
            $errorMessage = "Không có thông tin điểm thi tốt nghiệp THPT";
            return false;
        }

        $scores = [];
        $subjectFields = [
            'toan', 'van', 'tieng_anh', 'tieng_trung', 'ly', 'hoa', 'sinh', 
            'su', 'dia', 'gdcd', 'ktpl', 'tin_hoc', 'cnnn', 'gdtc', 'gdqp', 
            'nghe_thuat', 'tieng_dan_toc', 'ngoai_ngu_2', 'cong_nghe'
        ];

        foreach ($subjectFields as $field) {
            if (isset($record[$field]) && $record[$field] !== null && $record[$field] !== '') {
                $scores[$field] = (float)$record[$field];
            }
        }

        // Xác định ngưỡng điểm sàn của Bộ dựa trên nhóm ngành và phương thức
        $maNganh = $majorDetails['ma_nganh'] ?? '';
        
        $requiredThpt = 15.00;
        $requiredXTN = null;
        
        if ($bestMethod === '200') {
            // Nhóm Sư phạm (714)
            if (strpos($maNganh, '714') === 0) {
                $isSpecialPedagogy = in_array((string)$maNganh, ['7140206', '7140221', '7140222']);
                if ($isSpecialPedagogy) {
                    $requiredThpt = 16.50;
                    $requiredXTN = 6.50;
                } else {
                    $requiredThpt = 18.00;
                    $requiredXTN = 8.50;
                }
            }
            // Nhóm Sức khỏe (772)
            elseif (strpos($maNganh, '772') === 0) {
                $isNursingGroup = in_array((string)$maNganh, [
                    '7720301', '7720501', '7720302', '7720303', '7720401', '7720402', '7720601'
                ]);
                if ($isNursingGroup) {
                    $requiredThpt = 16.50;
                    $requiredXTN = 6.50;
                } else {
                    $requiredThpt = 20.00;
                    $requiredXTN = 8.50;
                }
            }
        }

        // A. Kiểm tra điều kiện tốt nghiệp (Nếu đạt thì được giảm ngưỡng điểm sàn THPT cần đạt xuống sàn tối thiểu 15.00 điểm)
        $passedGraduationScoreExemption = false;
        if ($requiredXTN !== null) {
            $diemXTN = $this->getDiemXetTotNghiep($cccd);
            if ($diemXTN !== null && $diemXTN >= $requiredXTN) {
                $passedGraduationScoreExemption = true;
            }
        }

        $activeRequiredThpt = $passedGraduationScoreExemption ? 15.00 : $requiredThpt;

        if (count($scores) < 3) {
            $errorMessage = "Thiếu thông tin điểm thi (yêu cầu tối thiểu 3 môn)";
            return false;
        }

        // B. Kiểm tra THPT theo Toán + Văn + 1 môn bất kỳ
        $passedToanVan = false;
        if (isset($scores['toan']) && isset($scores['van'])) {
            $toanVal = $scores['toan'];
            $vanVal = $scores['van'];
            
            $maxOther = -1;
            foreach ($scores as $k => $v) {
                if ($k !== 'toan' && $k !== 'van') {
                    if ($v > $maxOther) {
                        $maxOther = $v;
                    }
                }
            }
            if ($maxOther >= 0 && ($toanVal + $vanVal + $maxOther) >= $activeRequiredThpt) {
                $passedToanVan = true;
            }
        }

        if ($passedToanVan) {
            return true;
        }

        // C. Kiểm tra THPT theo các tổ hợp hợp lệ của ngành đăng ký xét tuyển
        $allowedCombos = [];
        if ($majorDetails && !empty($majorDetails['khoi_xet_tuyen'])) {
            $allowedCombos = array_map('trim', explode(',', $majorDetails['khoi_xet_tuyen']));
        }

        $cKey = 'master_combos';
        $combos = \App\Services\CacheService::get($cKey);
        if ($combos === null) {
            try {
                $stmt = $this->db->query("SELECT id, ma_to_hop, mon_1_id, mon_2_id, mon_3_id FROM dm_to_hop");
                $combos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                \App\Services\CacheService::set($cKey, $combos, 1800);
            } catch (\Exception $e) {
                $combos = [];
            }
        }

        // Map logical subject code -> student score
        $aliases = $this->getSubjectAliases();
        $scoresByCode = [];
        foreach ($aliases as $logicalCol => $possibleCodes) {
            $dbField = $logicalCol;
            if ($logicalCol === 'ngoai_ngu') $dbField = 'tieng_anh';
            
            if (isset($scores[$dbField])) {
                foreach ($possibleCodes as $code) {
                    $scoresByCode[$code] = $scores[$dbField];
                }
            }
        }

        // Map directly by uppercase field names just in case
        foreach ($scores as $f => $s) {
            $code = strtoupper($f);
            if (!isset($scoresByCode[$code])) {
                $scoresByCode[$code] = $s;
            }
        }

        foreach ($combos as $combo) {
            if (!empty($allowedCombos) && !in_array($combo['ma_to_hop'], $allowedCombos)) {
                continue;
            }

            $m1_code = $this->cachedSubjectIdToCode[$combo['mon_1_id']] ?? null;
            $m2_code = $this->cachedSubjectIdToCode[$combo['mon_2_id']] ?? null;
            $m3_code = $this->cachedSubjectIdToCode[$combo['mon_3_id']] ?? null;

            if ($m1_code && $m2_code && $m3_code) {
                if (isset($scoresByCode[$m1_code]) && isset($scoresByCode[$m2_code]) && isset($scoresByCode[$m3_code])) {
                    $sum = $scoresByCode[$m1_code] + $scoresByCode[$m2_code] + $scoresByCode[$m3_code];
                    if ($sum >= $activeRequiredThpt) {
                        return true;
                    }
                }
            }
        }

        $errorMessage = "Tổng điểm 3 môn thi tốt nghiệp THPT theo tổ hợp xét tuyển dưới " . number_format($activeRequiredThpt, 2);
        if ($requiredXTN !== null) {
            $errorMessage .= " và điểm xét tốt nghiệp dưới " . number_format($requiredXTN, 2);
        }
        return false;
    }

    /**
     * Lấy điểm xét tốt nghiệp THPT của thí sinh (hỗ trợ điều kiện OR)
     * @return float|null
     */
    protected function getDiemXetTotNghiep($cccd) {
        // Ưu tiên lấy từ bulkData nếu đã pre-load
        if ($this->bulkData && isset($this->bulkData['thpt'][$cccd])) {
            $val = $this->bulkData['thpt'][$cccd]['diem_xet_tot_nghiep'] ?? null;
            return ($val !== null && $val !== '') ? (float)$val : null;
        }
        
        // Fallback: Query trực tiếp
        $stmt = $this->db->prepare("SELECT diem_xet_tot_nghiep FROM diem_thi_thpt WHERE so_cccd = ? LIMIT 1");
        $stmt->execute([$cccd]);
        $val = $stmt->fetchColumn();
        return ($val !== null && $val !== false && $val !== '') ? (float)$val : null;
    }

    protected function updateApplicationScore($nvId, $score, $combo, $method, $details, $dataHash, $admitted = true) {
        try {
            $diem_mon_1 = $details['diem_mon_1'] ?? 0;
            $diem_mon_2 = $details['diem_mon_2'] ?? 0;
            $diem_mon_3 = $details['diem_mon_3'] ?? 0;
            $priority_raw = $details['priority_raw'] ?? 0;
            $priority_converted = $details['priority_converted'] ?? 0;

            $sql = "INSERT INTO v_calc_summary (
                        nguyen_vong_id, dot_tuyen_sinh_id, diem_xet_tuyen, to_hop_toi_uu, phuong_thuc_toi_uu,
                        chi_tiet_diem, data_hash, diem_mon_1, diem_mon_2, diem_mon_3,
                        diem_uu_tien_goc, diem_uu_tien_qd, trang_thai_do, updated_at
                    )
                    SELECT ?, nv.dot_tuyen_sinh_id, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP
                    FROM nguyen_vong nv WHERE nv.id = ?
                    ON CONFLICT (nguyen_vong_id) DO UPDATE SET
                        dot_tuyen_sinh_id = EXCLUDED.dot_tuyen_sinh_id,
                        diem_xet_tuyen = EXCLUDED.diem_xet_tuyen,
                        to_hop_toi_uu = EXCLUDED.to_hop_toi_uu,
                        phuong_thuc_toi_uu = EXCLUDED.phuong_thuc_toi_uu,
                        chi_tiet_diem = EXCLUDED.chi_tiet_diem,
                        data_hash = EXCLUDED.data_hash,
                        diem_mon_1 = EXCLUDED.diem_mon_1,
                        diem_mon_2 = EXCLUDED.diem_mon_2,
                        diem_mon_3 = EXCLUDED.diem_mon_3,
                        diem_uu_tien_goc = EXCLUDED.diem_uu_tien_goc,
                        diem_uu_tien_qd = EXCLUDED.diem_uu_tien_qd,
                        trang_thai_do = EXCLUDED.trang_thai_do,
                        updated_at = CURRENT_TIMESTAMP";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $nvId,
                $score,
                $combo,
                $method,
                json_encode($details, JSON_UNESCAPED_UNICODE),
                $dataHash,
                $diem_mon_1, $diem_mon_2, $diem_mon_3,
                $priority_raw, $priority_converted,
                $admitted ? 1 : 0,
                $nvId
            ]);
        } catch (\PDOException $e) {
            $msg = "SQL Error in updateApplicationScore: " . $e->getMessage() . "\n";
            file_put_contents('error_log_service.txt', $msg, FILE_APPEND);
        }
    }
}
