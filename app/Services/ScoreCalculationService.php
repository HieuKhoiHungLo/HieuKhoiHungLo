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
        
        $this->cachedSubjectCodeToId = [];
        $this->cachedAptitudeSubjectIds = [];
        foreach($this->cachedSubjects as $s) {
            $id = $s['id'];
            $code = strtoupper($s['ma_mon']);
            $this->cachedSubjectCodeToId[$code] = $id;
            
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
        $configData = "";

        if ($this->cachedPriorityAreas === null) {
            $this->cachedPriorityAreas = $this->masterDataRepo->getPriorityAreas();
        }
        if ($this->cachedPriorityObjects === null) {
            $this->cachedPriorityObjects = $this->masterDataRepo->getPriorityObjects();
        }

        if ($this->bulkData) {
            $transcriptData = json_encode($this->bulkData['transcripts'][$cccd] ?? []);
            $thptData = json_encode($this->bulkData['thpt'][$cccd] ?? []);
            $applicationData = json_encode($this->bulkData['applications'][$cccd] ?? []);
            $cProfile = $this->bulkData['candidates_profile'][$cccd] ?? [];
            $candidateData = json_encode($cProfile);

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
        return md5($transcriptData . $thptData . $applicationData . $candidateData . $configData . "v3");
    }

    public function calculate($cccd, $sessionId = null, $returnOnly = false, $force = false) {
        // 1. Fetch ALL Data (Uses bulkData if pre-loaded)
        $transcriptavgs = $this->calculateTranscriptAverages($cccd);
        $thptScores = $this->getThptScores($cccd);
        $certificates = $this->getCertificates($cccd);
        $aptitudeScores = $this->getAptitudeScores($cccd);
        
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
                    $hbThreshold = $this->checkAdmissionThresholdInternal($cccd, $majorCode, $majorDetails, $hbResult['total'], '200');
                    
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
                        $thptThreshold = $this->checkAdmissionThresholdInternal($cccd, $majorCode, $majorDetails, $thptResult['total'], '100');
                        
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
            
            $hasCert = !empty($certificates);
            $finalMethodCode = \App\Helpers\AdmissionMethodHelper::resolvePhuongThuc($bestMethod ?? '', $majorDetails, $hasCert);
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
    public function recalculateBatch($sessionId, array $cccds, $force = false) {
        if (empty($cccds)) return 0;
        
        try {
            // 1. Bulk load data for this specific chunk
            $this->loadBatchDataPartial($sessionId, $cccds);
            
            $allResults = []; // Khởi tạo mảng kết quả
            
            foreach ($cccds as $cccd) {
                // Pass $force to bypass dirty checking
                $results = $this->calculate($cccd, $sessionId, true, $force);
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
            WHERE d.so_cccd IN ($placeholders)
        ");
        $stmt->execute($cccds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['certs'][$row['so_cccd']][$row['mon_id']] = (float)$row['diem'];
        }


        // Load Aptitude
        $stmt = $this->db->prepare("
            SELECT d.so_cccd, m.id as mon_id, d.diem 
            FROM diem_nang_khieu d
            JOIN dm_mon m ON d.ma_mon = m.ma_mon
            WHERE d.so_cccd IN ($placeholders)
        ");
        $stmt->execute($cccds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['aptitude'][$row['so_cccd']][$row['mon_id']] = (float)$row['diem'];
        }

        // Load Priority Areas/Objects (Quy chế: ưu tiên KV chỉ trong năm TN + 1 năm)
        $stmtThiSinh = $this->db->prepare("SELECT so_cccd, khu_vuc_uu_tien, doi_tuong_uu_tien, nam_tot_nghiep FROM thi_sinh WHERE so_cccd IN ($placeholders)");
        $stmtThiSinh->execute($cccds);
        
        if ($this->cachedPriorityAreas === null) $this->cachedPriorityAreas = $this->masterDataRepo->getPriorityAreas();
        if ($this->cachedPriorityObjects === null) $this->cachedPriorityObjects = $this->masterDataRepo->getPriorityObjects();
        
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
        
        foreach ($stmtThiSinh->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['candidates_profile'][$row['so_cccd']] = $row;
            $rawKV = $row['khu_vuc_uu_tien'] ?? '';
            $rawDT = $row['doi_tuong_uu_tien'] ?? '';
            $namTN = $row['nam_tot_nghiep'] ?? null;
            
            $maKV = $this->normalizePriorityCode($rawKV);
            $maDT = $this->normalizePriorityCode($rawDT);
            
            // Ưu tiên Khu vực: chỉ áp dụng nếu có năm TN và trong hạn (năm TN + 1)
            $diemKV = 0;
            if ($namTN !== null && $namTN !== '' && ($currentYear - (int)$namTN) <= 1) {
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
                    nguyen_vong_id, diem_xet_tuyen, to_hop_toi_uu, phuong_thuc_toi_uu,
                    chi_tiet_diem, data_hash, diem_mon_1, diem_mon_2, diem_mon_3,
                    diem_uu_tien_goc, diem_uu_tien_qd, trang_thai_do, updated_at
                )
                SELECT 
                    tmp.nv_id, tmp.score, tmp.combo, tmp.method,
                    CAST(tmp.details AS JSONB), tmp.d_hash, tmp.m1, tmp.m2, tmp.m3,
                    tmp.prio_raw, tmp.prio_qd, tmp.is_passed, CURRENT_TIMESTAMP
                FROM temp_calc_results tmp
                ON CONFLICT (nguyen_vong_id) DO UPDATE SET
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
    public function recalculateSession($sessionId, $force = false) {
        $cccds = $this->getCandidateIds($sessionId, $force);
        return $this->recalculateBatch($sessionId, $cccds, $force);
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

        // 7. Bulk load Priority (Quy chế: ưu tiên KV chỉ trong năm TN + 1 năm)
        $stmt = $this->db->prepare("SELECT so_cccd, khu_vuc_uu_tien, doi_tuong_uu_tien, nam_tot_nghiep FROM thi_sinh WHERE so_cccd IN ($placeholders)");
        $stmt->execute($candidates);
        $prioAreas = $this->masterDataRepo->getPriorityAreas();
        $prioObjects = $this->masterDataRepo->getPriorityObjects();
        
        $currentYear = (int)date('Y');
        
        $this->cachedPriorityAreas = $prioAreas;
        $this->cachedPriorityObjects = $prioObjects;

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['candidates_profile'][$row['so_cccd']] = $row;
            $rawKV = $row['khu_vuc_uu_tien'] ?? '';
            $rawDT = $row['doi_tuong_uu_tien'] ?? '';
            $namTN = $row['nam_tot_nghiep'] ?? null;
            
            $maKV = $this->normalizePriorityCode($rawKV);
            $maDT = $this->normalizePriorityCode($rawDT);
            
            // Ưu tiên Khu vực: chỉ áp dụng nếu có năm TN và trong hạn (năm TN + 1)
            $diemKV = 0;
            if ($namTN !== null && $namTN !== '' && ($currentYear - (int)$namTN) <= 1) {
                $diemKV = $prioAreas[$maKV] ?? $prioAreas[trim($rawKV)] ?? 0;
            }
            // Ưu tiên Đối tượng: luôn áp dụng
            $diemDT = $prioObjects[$maDT] ?? $prioObjects[trim($rawDT)] ?? 0;
            
            $this->bulkData['priority'][$row['so_cccd']] = $diemKV + $diemDT;
        }
    }
    
    protected function normalizePriorityCode($code) {
        if (!$code) return '';
        $s = strtoupper(trim((string)$code));
        // Loại bỏ tiền tố KV, DT và các ký tự đặc biệt như dấu gạch ngang
        $s = preg_replace('/^(KV|DT)/', '', $s);
        $s = str_replace('-', '', $s);
        // Nếu là số đơn lẻ (1, 2, 3), chuẩn hóa về dạng 1 chữ số (ví dụ 01 -> 1)
        if (is_numeric($s)) {
            $s = (string)(int)$s;
        }
        return $s;
    }

    protected function calculatePriorityPoints($cccd, $sessionId = null) {
        if ($this->bulkData) {
            return $this->bulkData['priority'][$cccd] ?? 0;
        }

        // Fetch Candidate Priority Info + năm tốt nghiệp
        $stmt = $this->db->prepare("SELECT khu_vuc_uu_tien, doi_tuong_uu_tien, nam_tot_nghiep FROM thi_sinh WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$candidate) return 0;
        
        $diemKhuVuc = 0;
        $diemDoiTuong = 0;
        
        if ($this->cachedPriorityAreas === null) {
            $this->cachedPriorityAreas = $this->masterDataRepo->getPriorityAreas();
        }
        if ($this->cachedPriorityObjects === null) {
            $this->cachedPriorityObjects = $this->masterDataRepo->getPriorityObjects();
        }

        // Quy chế: Ưu tiên KV chỉ trong năm tốt nghiệp + 1 năm kế tiếp
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
        
        $kvEligible = ($namTN !== null && $namTN !== '' && ($currentYear - (int)$namTN) <= 1);

        // Get Area Points (chỉ nếu còn trong hạn)
        if ($kvEligible && !empty($candidate['khu_vuc_uu_tien'])) {
            $rawKV = $candidate['khu_vuc_uu_tien'];
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
        $fieldMap = [
            'toan' => 'toan', 'van' => 'van', 'tieng_anh' => 'ngoai_ngu', 
            'ly' => 'ly', 'hoa' => 'hoa', 'sinh' => 'sinh', 
            'su' => 'su', 'dia' => 'dia', 'gdcd' => 'gdcd', 
            'ktpl' => 'ktpl',
            'tin_hoc' => 'tin_hoc'
        ];
        
        $aliases = $this->getSubjectAliases();
        $codeToId = $this->cachedSubjectCodeToId;

        foreach ($fieldMap as $dbCol => $logicalCol) {
            if (!isset($record[$dbCol]) || $record[$dbCol] === null || $record[$dbCol] === '') continue;
            
            $possibleCodes = $aliases[$logicalCol] ?? [];
            foreach ($possibleCodes as $code) {
                if (isset($codeToId[$code])) {
                    $scores[$codeToId[$code]] = (float)$record[$dbCol];
                    break;
                }
            }
        }
        return $scores;
    }

    protected function getCertificates($cccd) {
        if ($this->bulkData) {
            return $this->bulkData['certs'][$cccd] ?? [];
        }

        $sql = "SELECT m.id as mon_id, d.diem 
                FROM diem_chung_chi d
                JOIN dm_mon m ON d.ma_mon = m.ma_mon
                WHERE d.so_cccd = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cccd]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    protected function getAptitudeScores($cccd) {
        if ($this->bulkData) {
            return $this->bulkData['aptitude'][$cccd] ?? [];
        }

        $sql = "SELECT m.id as mon_id, d.diem 
                FROM diem_nang_khieu d
                JOIN dm_mon m ON d.ma_mon = m.ma_mon
                WHERE d.so_cccd = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cccd]);
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
        
        // Chỉ cho phép quy đổi chứng chỉ ngoại ngữ với phương thức 200 (Học bạ)
        $allowCert = ($method === 'HOC_BA'); 
        
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
            $subjectIdx++;
            
            $details[$monId] = [
                'raw' => $scores[$monId] ?? 0,
                'base_scaled' => $baseScore, 
                'cert' => $certScore, 
                'apt' => $aptitudeScore,
                'final' => $finalScore,
                'source' => $source
            ];
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
     * @param float $bestScore Calculated best score for threshold comparison
     * @return array ['passed' => bool, 'errors' => string[]]
     */
    protected function checkAdmissionThresholdInternal($cccd, $ma_nganh, $majorDetails, $bestScore, $bestMethod = null) {
        $result = ['passed' => true, 'errors' => []];
        
        $nhomNganh = $majorDetails['nhom_nganh'] ?? 'Khac';
        $nguongHocLuc = $majorDetails['nguong_hoc_luc'] ?? null;
        $nguongDiemTHPT = $majorDetails['nguong_diem_thpt'] ?? null;

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
            
            // Extract from bulkData if available to avoid DB hit (Fix N+1 query)
            if ($this->bulkData && isset($this->bulkData['transcripts'][$cccd])) {
                foreach ($this->bulkData['transcripts'][$cccd] as $tr) {
                    if ($tr['lop'] == 12) {
                        $hocLuc12 = $tr['hoc_luc_ca_nam'] ?? null;
                        break;
                    }
                }
            } else {
                $grade12 = $this->academicModel->getGrade12Summary($cccd);
                $hocLuc12 = $grade12['hoc_luc_ca_nam'] ?? null;
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
                
                if ($actualRank < $requiredRank) {
                    $result['passed'] = false;
                    $labels = ['Gioi' => 'Giỏi', 'Kha' => 'Khá', 'TrungBinh' => 'Trung bình', 'Yeu' => 'Yếu'];
                    $result['errors'][] = "Học lực lớp 12 đạt " . ($labels[$hocLuc12] ?? $hocLuc12) . " (Yêu cầu: " . ($labels[$nguongHocLuc] ?? $nguongHocLuc) . ")";
                }
            } else {
                $result['passed'] = false;
                $result['errors'][] = "Thiếu thông tin Học lực lớp 12";
            }
        }
        
        // 2. Check Tổng điểm 3 môn THPT theo tổ hợp
        if ($nguongDiemTHPT) {
            // Note: bestScore is already calculated in caller
            if ($bestScore > 0 && $bestScore < $nguongDiemTHPT) {
                $result['passed'] = false;
                $result['errors'][] = "Tổng điểm thấp hơn ngưỡng " . number_format($nguongDiemTHPT, 2);
            }
        }
        
        return $result;
    }

    protected function updateApplicationScore($nvId, $score, $combo, $method, $details, $dataHash, $admitted = true) {
        try {
            $diem_mon_1 = $details['diem_mon_1'] ?? 0;
            $diem_mon_2 = $details['diem_mon_2'] ?? 0;
            $diem_mon_3 = $details['diem_mon_3'] ?? 0;
            $priority_raw = $details['priority_raw'] ?? 0;
            $priority_converted = $details['priority_converted'] ?? 0;

            $sql = "INSERT INTO v_calc_summary (
                        nguyen_vong_id, diem_xet_tuyen, to_hop_toi_uu, phuong_thuc_toi_uu,
                        chi_tiet_diem, data_hash, diem_mon_1, diem_mon_2, diem_mon_3,
                        diem_uu_tien_goc, diem_uu_tien_qd, trang_thai_do, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                    ON CONFLICT (nguyen_vong_id) DO UPDATE SET
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
                $admitted ? 1 : 0
            ]);
        } catch (\PDOException $e) {
            $msg = "SQL Error in updateApplicationScore: " . $e->getMessage() . "\n";
            file_put_contents('error_log_service.txt', $msg, FILE_APPEND);
        }
    }
}
