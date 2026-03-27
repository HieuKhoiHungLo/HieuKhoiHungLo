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
    
    // Internal cache for master data (persists for the entire request)
    private $cachedMajors = null;
    private $cachedSubjects = null;
    private $cachedPriorityAreas = null;
    private $cachedPriorityObjects = null;
    private $cachedComboSubjects = [];
    private $cachedMajorCombos = [];
    private $cachedComboIds = [];

    // Bulk loading buffer
    private $bulkData = null;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->masterDataRepo = new MasterDataRepository();
        $this->thiSinhRepo = new ThiSinhRepository();
        $this->academicModel = new AcademicRecord();
        $this->diemThiModel = new DiemThiTHPT();
    }

    public function calculate($cccd) {
        // 1. Fetch ALL Data (Uses bulkData if pre-loaded)
        $transcriptavgs = $this->calculateTranscriptAverages($cccd);
        $thptScores = $this->getThptScores($cccd);
        $certificates = $this->getCertificates($cccd);
        $aptitudeScores = $this->getAptitudeScores($cccd);
        
        // 1b. Calculate Priority Points (Area + Object)
        $priorityPoints = $this->calculatePriorityPoints($cccd);
        
        // 2. Get Applications (Nguyen Vong)
        $applications = $this->getApplications($cccd);
        
        if (empty($applications)) return;

        foreach ($applications as $app) {
            $majorCode = $app['ma_nganh'];
            $majorDetails = $this->getMajorDetails($majorCode);
            if (!$majorDetails) continue;

            $combinations = $this->getMajorCombinations($majorCode);
            
            $bestScore = 0;
            $bestCombo = null;
            $bestMethod = null;
            $bestDetails = [];
            $allCombinationsParams = [];

            // 3. Iterate Combinations
            foreach ($combinations as $comboCode) {
                $comboSubjects = $this->getComboSubjects($comboCode);
                if (!$comboSubjects) continue;

                $hbResult = $this->calculateMethodScore('HOC_BA', $comboSubjects, $transcriptavgs, $certificates, $aptitudeScores, $majorDetails, $priorityPoints);
                if ($hbResult) {
                    $allCombinationsParams["HB_{$comboCode}"] = $hbResult['total'];
                    if ($hbResult['total'] > $bestScore) {
                        $bestScore = $hbResult['total'];
                        $bestCombo = $comboCode;
                        $bestMethod = '200';
                        $bestDetails = $hbResult['details'];
                    }
                }

                if (!empty($thptScores)) {
                    $thptResult = $this->calculateMethodScore('DIEM_THI', $comboSubjects, $thptScores, $certificates, $aptitudeScores, $majorDetails, $priorityPoints);
                    if ($thptResult) {
                        $allCombinationsParams["THPT_{$comboCode}"] = $thptResult['total'];
                        if ($thptResult['total'] > $bestScore) {
                            $bestScore = $thptResult['total'];
                            $bestCombo = $comboCode;
                            $bestMethod = '100';
                            $bestDetails = $thptResult['details'];
                        }
                    }
                }
            }

            // 4. Check Threshold (TT06/2026) and Update Nguyen Vong
            $thresholdResult = null;
            if (!empty($majorDetails['nguong_hoc_luc']) || !empty($majorDetails['nguong_diem_thpt'])) {
                $thresholdResult = $this->checkAdmissionThresholdInternal($cccd, $majorCode, $majorDetails, $bestScore);
            }
            
            if ($thresholdResult && !$thresholdResult['passed']) {
                $bestScore = 0;
            }
            
            $details = $bestDetails;
            $details['all_combinations'] = $allCombinationsParams;
            if ($thresholdResult && !$thresholdResult['passed']) {
                $details['threshold_note'] = 'KHÔNG ĐẠT NGƯỠNG: ' . implode('; ', $thresholdResult['errors']);
            }
            
            $finalMethodCode = \App\Helpers\AdmissionMethodHelper::resolvePhuongThuc($bestMethod ?? '', $majorDetails);
            $this->updateApplicationScore($cccd, $majorCode, $bestScore, $bestCombo, $finalMethodCode, $details);
        }
    }

    /**
     * High-Performance Batch Recalculation for an entire session
     */
    public function recalculateSession($sessionId) {
        try {
            // 1. Pre-load ALL data for every candidate in this session
            $this->loadBatchData($sessionId);
        } catch (\Throwable $e) {
            file_put_contents('error_log_service.txt', "FATAL ERROR in loadBatchData: " . $e->getMessage() . "\n" . $e->getTraceAsString(), FILE_APPEND);
            return 0;
        }
        
        if (empty($this->bulkData['candidates'])) {
            return 0;
        }
        
        $successCount = 0;
        
        // Wrap all candidate calculations in a single Database Transaction
        // This ensures the 60+ UPDATEs compile into a single rapid network flight.
        $this->db->beginTransaction();
        try {
            foreach ($this->bulkData['candidates'] as $idx => $cccd) {
                // calculate() processes the data purely in memory and cues updates
                $this->calculate($cccd);
                $successCount++;
            }
            $this->db->commit();
            return $successCount;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            file_put_contents('error_log_service.txt', "Transaction Error Throwable: " . $e->getMessage() . "\n" . $e->getTraceAsString(), FILE_APPEND);
            return 0;
        }
    }

    protected function loadBatchData($sessionId) {
        $this->bulkData = [
            'candidates' => [],
            'transcripts' => [],
            'thpt' => [],
            'certs' => [],
            'aptitude' => [],
            'priority' => [],
            'applications' => []
        ];

        // 1. Get ALL approved candidates and their applications in this session
        $stmt = $this->db->prepare("SELECT DISTINCT so_cccd FROM nguyen_vong WHERE dot_tuyen_sinh_id = ? AND (trang_thai = 'DaDuyet' OR trang_thai LIKE '%Đã duyệt%')");
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

        // 5. Bulk load Certificates
        $stmt = $this->db->prepare("SELECT * FROM chung_chi_thi_sinh WHERE so_cccd IN ($placeholders)");
        $stmt->execute($candidates);
        $allCerts = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
        // Helper mapping for cert rules (cached effectively)
        try {
            $certRulesStmt = $this->db->query("SELECT * FROM cau_hinh_chung_chi ORDER BY diem_quy_doi DESC");
            $certRules = $certRulesStmt ? $certRulesStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Exception $e) {
            $certRules = [];
        }

        foreach ($allCerts as $cert) {
            foreach ($certRules as $rule) {
                if ($rule['loai_chung_chi'] === $cert['loai_chung_chi'] && 
                    $cert['diem_chung_chi'] >= $rule['muc_diem_tu'] && 
                    ($rule['muc_diem_den'] === null || $cert['diem_chung_chi'] <= $rule['muc_diem_den'])) {
                    
                    $monId = $rule['mon_id'];
                    $score = (float)$rule['diem_quy_doi'];
                    if (!isset($this->bulkData['certs'][$cert['so_cccd']][$monId]) || $score > $this->bulkData['certs'][$cert['so_cccd']][$monId]) {
                        $this->bulkData['certs'][$cert['so_cccd']][$monId] = $score;
                    }
                    break;
                }
            }
        }

        // 6. Bulk load Aptitude
        $stmt = $this->db->prepare("
            SELECT d.so_cccd, m.id as mon_id, d.diem 
            FROM diem_nang_khieu d
            JOIN dm_mon m ON d.ma_mon = m.ma_mon
            WHERE d.so_cccd IN ($placeholders)
        ");
        $stmt->execute($candidates);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->bulkData['aptitude'][$row['so_cccd']][$row['mon_id']] = (float)$row['diem'];
        }

        // 7. Bulk load Priority
        $stmt = $this->db->prepare("SELECT so_cccd, khu_vuc_uu_tien, doi_tuong_uu_tien FROM thi_sinh WHERE so_cccd IN ($placeholders)");
        $stmt->execute($candidates);
        $prioAreas = $this->masterDataRepo->getPriorityAreas();
        $prioObjects = $this->masterDataRepo->getPriorityObjects();
        
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $maKV = trim($row['khu_vuc_uu_tien'] ?? '');
            $maDT = trim($row['doi_tuong_uu_tien'] ?? '');
            $score = ($prioAreas[$maKV] ?? 0) + ($prioObjects[$maDT] ?? 0);
            $this->bulkData['priority'][$row['so_cccd']] = $score;
        }
    }
    
    protected function calculatePriorityPoints($cccd) {
        if ($this->bulkData && isset($this->bulkData['priority'][$cccd])) {
            return $this->bulkData['priority'][$cccd];
        }

        // Fetch Candidate Priority Info (Text Columns)
        $stmt = $this->db->prepare("SELECT khu_vuc_uu_tien, doi_tuong_uu_tien FROM thi_sinh WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$candidate) return 0;
        
        $diemKhuVuc = 0;
        $diemDoiTuong = 0;
        
        // Get Area Points
        if (!empty($candidate['khu_vuc_uu_tien'])) {
            $maKV = trim($candidate['khu_vuc_uu_tien']);
            if ($this->cachedPriorityAreas === null) {
                $this->cachedPriorityAreas = $this->masterDataRepo->getPriorityAreas();
            }
            if (isset($this->cachedPriorityAreas[$maKV])) {
                $diemKhuVuc = $this->cachedPriorityAreas[$maKV];
            }
        }
        
        // Get Object Points
        if (!empty($candidate['doi_tuong_uu_tien'])) {
            $maDT = trim($candidate['doi_tuong_uu_tien']);
            if ($this->cachedPriorityObjects === null) {
                $this->cachedPriorityObjects = $this->masterDataRepo->getPriorityObjects();
            }
            if (isset($this->cachedPriorityObjects[$maDT])) {
                $diemDoiTuong = $this->cachedPriorityObjects[$maDT];
            }
        }
        return $diemKhuVuc + $diemDoiTuong;
    }

    protected function getMajorDetails($majorCode) {
        if ($this->cachedMajors === null) {
            $this->cachedMajors = $this->masterDataRepo->getMajors();
        }
        foreach ($this->cachedMajors as $m) {
            if ($m['ma_nganh'] == $majorCode) {
                return $m;
            }
        }
        return null;
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
        if ($this->bulkData && isset($this->bulkData['transcripts'][$cccd])) {
            $records = $this->bulkData['transcripts'][$cccd];
        } else {
            $records = $this->academicModel->getByCCCD($cccd);
        }
        
        if ($this->cachedSubjects === null) {
            $this->cachedSubjects = $this->masterDataRepo->getSubjects(); 
        }
        
        $codeToId = [];
        foreach($this->cachedSubjects as $s) {
            $codeToId[strtoupper($s['ma_mon'])] = $s['id'];
        }
        
        $aliases = $this->getSubjectAliases();
        $colToMonId = [];

        foreach ($aliases as $colName => $possibleCodes) {
            foreach ($possibleCodes as $code) {
                if (isset($codeToId[$code])) {
                    $colToMonId[$colName] = $codeToId[$code];
                    break;
                }
            }
        }

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
            if ($counts[$id] > 0) $averages[$id] = round($total / $counts[$id], 2);
        }
        return $averages;
    }

    protected function getThptScores($cccd) {
        $record = null;
        if ($this->bulkData && isset($this->bulkData['thpt'][$cccd])) {
            $record = $this->bulkData['thpt'][$cccd];
        } else {
            $record = $this->diemThiModel->getByCCCD($cccd);
        }

        if (!$record) return [];

        if ($this->cachedSubjects === null) {
            $this->cachedSubjects = $this->masterDataRepo->getSubjects();
        }
        
        $codeToId = [];
        foreach($this->cachedSubjects as $s) {
            $codeToId[strtoupper($s['ma_mon'])] = $s['id'];
        }
        
        $scores = [];
        // Map db columns in diem_thi_thpt to logical column names and use aliases
        $fieldMap = [
            'toan' => 'toan', 'van' => 'van', 'tieng_anh' => 'ngoai_ngu', 
            'ly' => 'ly', 'hoa' => 'hoa', 'sinh' => 'sinh', 
            'su' => 'su', 'dia' => 'dia', 'gdcd' => 'gdcd', 
            'ktpl' => 'ktpl',
            'tin_hoc' => 'tin_hoc'
        ];
        
        $aliases = $this->getSubjectAliases();

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
        if ($this->bulkData && isset($this->bulkData['certs'][$cccd])) {
            return $this->bulkData['certs'][$cccd];
        }

        $chungChiModel = new \App\Models\ChungChiThiSinh();
        $certs = $chungChiModel->getByCCCD($cccd);
        $validCerts = [];
        
        foreach ($certs as $cert) {
            if (empty($cert['loai_chung_chi']) || empty($cert['diem_chung_chi'])) continue;

            $sql = "SELECT mon_id, diem_quy_doi FROM cau_hinh_chung_chi 
                    WHERE loai_chung_chi = ? 
                    AND muc_diem_tu <= ? 
                    AND (muc_diem_den IS NULL OR muc_diem_den >= ?) 
                    ORDER BY diem_quy_doi DESC LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cert['loai_chung_chi'], $cert['diem_chung_chi'], $cert['diem_chung_chi']]);
            $rule = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($rule) {
                $monId = $rule['mon_id'];
                $score = $rule['diem_quy_doi'];
                if (!isset($validCerts[$monId]) || $score > $validCerts[$monId]) {
                    $validCerts[$monId] = $score;
                }
            }
        }
        return $validCerts;
    }

    protected function getAptitudeScores($cccd) {
        if ($this->bulkData && isset($this->bulkData['aptitude'][$cccd])) {
            return $this->bulkData['aptitude'][$cccd];
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
        if ($this->bulkData && isset($this->bulkData['applications'][$cccd])) {
            return $this->bulkData['applications'][$cccd];
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
        
        $allowCert = $majorDetails['co_xet_chung_chi'] ?? false;
        
        $subjectIdx = 1;
        foreach ($subjects as $monId) {
            $baseScore = $scores[$monId] ?? 0;
            
            // Theo quy định của HVU, điểm học bạ nhận hệ số quy đổi 95%
            if ($method === 'HOC_BA') {
                $baseScore = $baseScore * 0.95;
            }

            $certScore = ($allowCert && isset($certs[$monId])) ? $certs[$monId] : 0;
            $aptitudeScore = isset($aptitude[$monId]) ? $aptitude[$monId] : null;

            $finalScore = 0;
            $source = 'MISSING';

            if ($aptitudeScore !== null) {
                $finalScore = $aptitudeScore;
                $source = 'APTITUDE';
            } else {
                $finalScore = max($baseScore, $certScore);
                if ($finalScore > 0) {
                     $source = ($finalScore == $certScore && $certScore > $baseScore) ? 'CERT' : $method;
                }
            }

            if ($finalScore <= 0) {
                return null; // Yêu cầu: Không đủ điểm 3 môn thì không tính tổ hợp này
            }

            $totalRaw += $finalScore;
            $monScores['mon_'.$subjectIdx] = $finalScore;
            $subjectIdx++;
            
            $details[$monId] = [
                'base' => $baseScore, 
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
        $priorityConverted = round($priorityConverted, 2);

        $totalFinal = $totalRaw + $priorityConverted;
        
        $details['priority_raw'] = $priorityPointsRaw;
        $details['priority_converted'] = $priorityConverted;
        $details['total_raw'] = $totalRaw;
        $details['diem_mon_1'] = $monScores['mon_1'] ?? 0;
        $details['diem_mon_2'] = $monScores['mon_2'] ?? 0;
        $details['diem_mon_3'] = $monScores['mon_3'] ?? 0;

        return ['total' => $totalRaw, 'details' => $details];
    }
    
    /**
     * @param float $bestScore Calculated best score for threshold comparison
     * @return array ['passed' => bool, 'errors' => string[]]
     */
    protected function checkAdmissionThresholdInternal($cccd, $ma_nganh, $majorDetails, $bestScore) {
        $result = ['passed' => true, 'errors' => []];
        
        $nhomNganh = $majorDetails['nhom_nganh'] ?? 'Khac';
        $nguongHocLuc = $majorDetails['nguong_hoc_luc'] ?? null;
        $nguongDiemTHPT = $majorDetails['nguong_diem_thpt'] ?? null;
        
        // No threshold for regular majors
        if ($nhomNganh === 'Khac' && !$nguongHocLuc && !$nguongDiemTHPT) {
            return $result;
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
                $hocLucRank = ['Gioi' => 4, 'Kha' => 3, 'TrungBinh' => 2, 'Yeu' => 1];
                $requiredRank = $hocLucRank[$nguongHocLuc] ?? 0;
                $actualRank = $hocLucRank[$hocLuc12] ?? 0;
                
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

    protected function updateApplicationScore($cccd, $majorCode, $score, $combo, $method, $details) {
        try {
            // Find combo ID with fast in-memory caching instead of raw SQL query
            $comboId = null;
            if ($combo) {
                if (!array_key_exists($combo, $this->cachedComboIds)) {
                    $stmtCombo = $this->db->prepare("SELECT id FROM dm_to_hop WHERE ma_to_hop = ? LIMIT 1");
                    $stmtCombo->execute([$combo]);
                    $this->cachedComboIds[$combo] = $stmtCombo->fetchColumn() ?: null;
                }
                $comboId = $this->cachedComboIds[$combo];
            }
            
            // Extract from details
            $diem_mon_1 = $details['diem_mon_1'] ?? 0;
            $diem_mon_2 = $details['diem_mon_2'] ?? 0;
            $diem_mon_3 = $details['diem_mon_3'] ?? 0;
            $priority_raw = $details['priority_raw'] ?? 0;
            $priority_converted = $details['priority_converted'] ?? 0;

            $sql = "UPDATE nguyen_vong SET 
                    diem_xet_tuyen = ?, 
                    to_hop_xet_tuyen_id = ?,
                    phuong_thuc_xet_tuyen = ?,
                    chi_tiet_diem = ?,
                    to_hop_toi_uu = ?,
                    phuong_thuc_toi_uu = ?,
                    diem_mon_1 = ?,
                    diem_mon_2 = ?,
                    diem_mon_3 = ?,
                    diem_uu_tien_goc = ?,
                    diem_uu_tien_qd = ?
                    WHERE so_cccd = ? AND ma_nganh = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $score,
                $comboId ?: null,
                $method,
                json_encode($details, JSON_UNESCAPED_UNICODE),
                $combo,
                $method,
                $diem_mon_1,
                $diem_mon_2,
                $diem_mon_3,
                $priority_raw,
                $priority_converted,
                $cccd,
                $majorCode
            ]);
        } catch (\PDOException $e) {
            $msg = "SQL Error: " . $e->getMessage() . "\n" .
                   "SQL: UPDATE nguyen_vong ... WHERE cccd=$cccd AND nganh=$majorCode\n";
            file_put_contents('error_log_service.txt', $msg, FILE_APPEND);
        }
    }
}
