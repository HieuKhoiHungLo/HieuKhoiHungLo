<?php
namespace App\Services;

use App\Models\DiemThiTHPT;
use App\Models\AcademicRecord;
use App\Models\Subject;
use App\Models\Combination;
use App\Models\AptitudeScore;
use App\Models\MasterData;

class ScoreCalculator {
    protected $thptModel;
    protected $academicModel;
    protected $subjectModel;
    protected $aptitudeModel;
    protected $masterData;

    // Cache variables to avoid N+1 remote database queries
    private $cachedCandidates = [];
    private $cachedBasePriorities = [];
    private $cachedScores = [];
    private $cachedRecords = [];
    private $cachedSubjects = null;
    private $cachedSubjectMap = null;
    private $cachedSubjectCodeMap = null;
    private $cachedExtScores = [];
    private $cachedLangRecords = [];
    private $cachedCombinations = [];
    private $cachedMajorCombinations = [];
    private $cachedWeightRatio = null;
    private $cachedSettings = [];
    private $cachedDiemXTN = [];
    private $cachedMajorInfos = [];
    private $cachedGrade12Summaries = [];

    public function __construct() {
        $this->thptModel = new DiemThiTHPT();
        $this->academicModel = new AcademicRecord();
        $this->subjectModel = new Subject();
        $this->aptitudeModel = new AptitudeScore();
        $this->masterData = new MasterData();
    }

    /**
     * Cache-enabled wrapper for masterData->getSetting
     */
    private function getSettingCached($key) {
        if (!isset($this->cachedSettings[$key])) {
            $this->cachedSettings[$key] = $this->masterData->getSetting($key);
        }
        return $this->cachedSettings[$key];
    }

    /**
     * Calculate best score for a candidate for a specific major
     */
    public function calculateBestScore($cccd, $ma_nganh) {
        // 1. Get Combinations (Cached)
        if (!isset($this->cachedMajorCombinations[$ma_nganh])) {
            $combinationCodes = $this->masterData->getMajorCombinations($ma_nganh);
            if (empty($combinationCodes)) {
                 $major = $this->masterData->find('dm_nganh', $ma_nganh, 'ma_nganh');
                 if ($major) {
                     if (!empty($major['khoi_xet_tuyen'])) {
                        $combinationCodes = array_map('trim', explode(',', $major['khoi_xet_tuyen']));
                     }
                 }
            }
            $this->cachedMajorCombinations[$ma_nganh] = $combinationCodes ?? [];
        }
        $combinationCodes = $this->cachedMajorCombinations[$ma_nganh];

        // 2. Fetch ALL scores from Normalized Table (Cached)
        if (!isset($this->cachedScores[$cccd])) {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT mon_id, loai_diem, diem FROM diem_chi_tiet WHERE so_cccd = ?");
            $stmt->execute([$cccd]);
            $allScores = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Group Scores
            $scores = [];
            foreach ($allScores as $row) {
                $scores[$row['loai_diem']][$row['mon_id']] = (float)$row['diem'];
            }
            $this->cachedScores[$cccd] = $scores;
        }
        $scores = $this->cachedScores[$cccd];

        // Fetch raw academic records to calculate 3-year averages for transcript (Cached)
        if (!isset($this->cachedRecords[$cccd])) {
            $this->cachedRecords[$cccd] = $this->academicModel->getByCCCD($cccd);
        }
        $records = $this->cachedRecords[$cccd];
        
        $aliases = [
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
        
        // Fetch and map subjects (Cached)
        if ($this->cachedSubjects === null) {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmtM = $db->query("SELECT * FROM dm_mon");
            $subjects = $stmtM->fetchAll(\PDO::FETCH_ASSOC);
            
            $this->cachedSubjects = [];
            $this->cachedSubjectMap = [];
            $this->cachedSubjectCodeMap = [];
            
            foreach ($subjects as $s) {
                $this->cachedSubjects[strtoupper($s['ma_mon'])] = $s['id'];
                $this->cachedSubjectMap[$s['id']] = $s;
                $this->cachedSubjectCodeMap[strtoupper(trim($s['ma_mon']))] = $s;
            }
        }
        $subjectCodeToId = $this->cachedSubjects;
        $subjectsMetadataGlobal = $this->cachedSubjectMap;

        $colToMonId = [];
        foreach ($aliases as $colName => $possibleCodes) {
            foreach ($possibleCodes as $code) {
                if (isset($subjectCodeToId[$code])) {
                    $colToMonId[$colName] = $subjectCodeToId[$code];
                    break;
                }
            }
        }

        $sums = [];
        $counts = [];
        foreach ($records as $r) {
            foreach ($colToMonId as $colKey => $monId) {
                $colPrefix = ($colKey == 'ngoai_ngu') ? "diem_ngoai_ngu" : "diem_{$colKey}";
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
        
        $hbAverages = [];
        foreach ($sums as $id => $total) {
            if ($counts[$id] === 3) {
                $hbAverages[$id] = round($total / 3, 3);
            } else {
                $hbAverages[$id] = 0.0;
            }
        }

        // 3. Fetch External Aptitude Scores (Cached)
        if (!isset($this->cachedExtScores[$cccd])) {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmtExt = $db->prepare("SELECT ma_mon, diem FROM diem_nang_khieu WHERE so_cccd = ?");
            $stmtExt->execute([$cccd]);
            $this->cachedExtScores[$cccd] = $stmtExt->fetchAll(\PDO::FETCH_ASSOC);
        }
        $extScores = $this->cachedExtScores[$cccd];

        // Map ma_mon to mon_id using cached mappings
        if (!empty($extScores)) {
            foreach ($extScores as $ext) {
                $codeUpper = strtoupper(trim($ext['ma_mon']));
                $subj = $this->cachedSubjectCodeMap[$codeUpper] ?? null;
                if ($subj) {
                    $scores['NK'][$subj['id']] = (float)$ext['diem'];
                }
            }
        }

        // Get Language Certificate Score if any (Cached)
        if (!isset($this->cachedLangRecords[$cccd])) {
            $langScoreModel = new \App\Models\LanguageScore();
            $this->cachedLangRecords[$cccd] = $langScoreModel->getByCCCD($cccd);
        }
        $langRecord = $this->cachedLangRecords[$cccd];
        $convertedLangScore = $langRecord ? (float)$langRecord['diem_quy_doi'] : 0;

        // Get Transcript Weight Ratio from cau_hinh (Cached)
        if ($this->cachedWeightRatio === null) {
            $db = \App\Core\Database::getInstance()->getConnection();
            $start = $db->query("SELECT value FROM cau_hinh WHERE key = 'he_so_hoc_ba' LIMIT 1");
            $this->cachedWeightRatio = (float)($start->fetchColumn() ?: 0.95);
        }
        $weightRatio = $this->cachedWeightRatio;

        $bestResult = [
            'total' => 0,
            'thpt_total' => 0,
            'transcript_total' => 0,
            'final_score' => 0,
            'method_code' => null, // 100 or 200
            'combination' => null,
            'details' => []
        ];

        $combinationModel = new Combination();

        foreach ($combinationCodes as $code) {
            // Get Combination info (Cached)
            if (!isset($this->cachedCombinations[$code])) {
                $this->cachedCombinations[$code] = $combinationModel->findByCode($code);
            }
            $combo = $this->cachedCombinations[$code];
            if (!$combo) continue;

            $subjectIds = [
                $combo['mon_1_id'],
                $combo['mon_2_id'],
                $combo['mon_3_id']
            ];
            
            // Resolve Subject Codes for details from cached metadata map
            $subjectsMetadata = [];
            foreach ($subjectIds as $sId) {
                $subjectsMetadata[$sId] = $subjectsMetadataGlobal[$sId] ?? null;
            }

            // --- Method 100: THPT ---
            $thptSum = 0;
            $thptDetails = [];
            $thptValid = true;

            foreach ($subjectIds as $sId) {
                $rawScore = $scores['THPT'][$sId] ?? ($scores['NK'][$sId] ?? 0);
                
                // Handle Lang Cert
                $subMeta = $subjectsMetadata[$sId] ?? null;
                if ($subMeta && in_array($subMeta['cot_diem'], ['ngoai_ngu', 'tieng_anh']) && $convertedLangScore > 0) {
                     $rawScore = max($rawScore, $convertedLangScore);
                }

                if ($rawScore <= 0) $thptValid = false; // Rigid check: must have score > 0
                $thptSum += $rawScore;
                $thptDetails[$subMeta['ma_mon'] ?? $sId] = $rawScore;
            }

            // --- Method 200: HOC_BA (Avg Grade 12) ---
            $hbSum = 0;
            $hbRawSum = 0;
            $hbDetails = [];
            $hbValid = true;

            foreach ($subjectIds as $sId) {
                // Use 3-year average instead of only CN_12
                $rawScore = $hbAverages[$sId] ?? ($scores['NK'][$sId] ?? 0);

                 // Handle Lang Cert for Transcript too? Usually Yes.
                 $subMeta = $subjectsMetadata[$sId] ?? null;
                 if ($subMeta && in_array($subMeta['cot_diem'], ['ngoai_ngu', 'tieng_anh']) && $convertedLangScore > 0) {
                      $rawScore = max($rawScore, $convertedLangScore);
                 }

                if ($rawScore <= 0) $hbValid = false;
                
                // Môn năng khiếu không nhân hệ số 0.95, môn văn hóa và chứng chỉ nhân hệ số 0.95
                $isAptitude = ($subMeta && $subMeta['loai_mon'] === 'nang_khieu');
                $finalSubjectScore = $rawScore;
                if (!$isAptitude) {
                    $finalSubjectScore = round($rawScore * $weightRatio, 3);
                }
                
                $hbSum += $finalSubjectScore;
                $hbRawSum += $rawScore;
                $hbDetails[$subMeta['ma_mon'] ?? $sId] = $rawScore;
            }
            
            // Check Best for this specific Combination
            // Case A: THPT
            if ($thptValid && $thptSum > $bestResult['final_score']) {
                $bestResult = [
                    'total' => $thptSum, // Raw sum for display
                    'thpt_total' => $thptSum,
                    'transcript_total' => 0, // Not selected
                    'final_score' => $thptSum, // No weighting for THPT
                    'method_code' => '100',
                    'combination' => $code,
                    'details' => $thptDetails
                ];
            }

            // Case B: Transcript (Apply Weight)
            if ($hbValid) {
                $weightedHB = round($hbSum, 3);
                
                if ($weightedHB > $bestResult['final_score']) {
                    $bestResult = [
                        'total' => $hbRawSum, 
                        'thpt_total' => 0,
                        'transcript_total' => $hbRawSum,
                        'final_score' => $weightedHB, 
                        'method_code' => '200',
                        'combination' => $code,
                        'details' => $hbDetails
                    ];
                }
            }
        }

        // --- Advanced Priority Calculation (Phase 4) ---
        $basePriority = $this->calculateBasePriority($cccd);
        $finalPriority = $basePriority;

        // Formula: If Total >= Threshold, dampen priority
        // Note: Applying to the chosen method's score (Method 100 or 200)
        // Check if we have a valid score
        if ($bestResult['final_score'] > 0) {
            $threshold = (float)$this->getSettingCached('score_threshold_dampening') ?: 22.5;
            $divisor = (float)$this->getSettingCached('score_dampening_divisor') ?: 7.5;

            // Using raw total (on scale of 30) for the threshold check
            // For THPT: raw is total. For Transcript: raw is total (not weighted? wait. Formula says "Tong diem 3 mon")
            // Assuming formula applies to the 3-subject sum on scale of 30.
            $checkScore = ($bestResult['method_code'] == '200') ? $bestResult['transcript_total'] : $bestResult['thpt_total'];

            if ($checkScore >= $threshold) {
                // Formula: ((30 - Total) / 7.5) * Priority
                $dampened = ((30 - $checkScore) / $divisor) * $basePriority;
                $finalPriority = max(0, $dampened); // Cannot be negative
            }
        }

        $bestResult['priority_score'] = round($finalPriority, 3);
        // $bestResult['final_score'] += $bestResult['priority_score']; // Removed per user request (no priority points)
        $bestResult['final_score'] = round($bestResult['final_score'], 3);

        return $bestResult;
    }

    private function calculateBasePriority($cccd) {
        if (isset($this->cachedBasePriorities[$cccd])) {
            return $this->cachedBasePriorities[$cccd];
        }

        if (!isset($this->cachedCandidates[$cccd])) {
            $candidateModel = new \App\Models\ThiSinh();
            $this->cachedCandidates[$cccd] = $candidateModel->findByCCCD($cccd);
        }
        $candidate = $this->cachedCandidates[$cccd];
        if (!$candidate) {
            $this->cachedBasePriorities[$cccd] = 0.0;
            return 0.0;
        }

        // 1. Check Graduation Year Condition (Grad Year + 1)
        if (!empty($candidate['nam_tot_nghiep'])) {
            $currentYear = (int)date('Y');
            if (($currentYear - (int)$candidate['nam_tot_nghiep']) > 1) {
                $this->cachedBasePriorities[$cccd] = 0.0;
                return 0.0; // Expired priority
            }
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        
        $diemKhuVuc = 0;
        $diemDoiTuong = 0;

        // 2. Area Priority
        if (!empty($candidate['khu_vuc_uu_tien'])) {
            $rawKV = $candidate['khu_vuc_uu_tien'];
            // Normalize
            $maKV = $rawKV;
            if ($maKV) {
                $maKV = strtoupper(trim((string)$maKV));
                $maKV = preg_replace('/^(KV|DT)/', '', $maKV);
                $maKV = str_replace(['-', '_'], '', $maKV);
                if (is_numeric($maKV)) {
                    $maKV = (string)(int)$maKV;
                }
            }
            
            $stmtKV = $db->prepare("SELECT diem_uu_tien FROM dm_khu_vuc WHERE ma_kv = ? OR TRIM(ma_kv) = ?");
            $stmtKV->execute([$maKV, trim($rawKV)]);
            $diemKhuVuc = (float)($stmtKV->fetchColumn() ?: 0);
        }

        // 3. Object Priority
        if (!empty($candidate['doi_tuong_uu_tien'])) {
            $rawDT = $candidate['doi_tuong_uu_tien'];
            $maDT = $rawDT;
            if ($maDT) {
                $maDT = strtoupper(trim((string)$maDT));
                $maDT = preg_replace('/^(KV|DT)/', '', $maDT);
                $maDT = str_replace(['-', '_'], '', $maDT);
                if (is_numeric($maDT)) {
                    $maDT = (string)(int)$maDT;
                }
            }
            
            $stmtDT = $db->prepare("SELECT diem_uu_tien FROM dm_doi_tuong WHERE ma_dt = ? OR TRIM(ma_dt) = ?");
            $stmtDT->execute([$maDT, trim($rawDT)]);
            $diemDoiTuong = (float)($stmtDT->fetchColumn() ?: 0);
        }

        $priority = $diemKhuVuc + $diemDoiTuong;
        $this->cachedBasePriorities[$cccd] = $priority;
        return $priority;
    }

    /**
     * Check admission threshold for a specific major (TT06/2026)
     * Used during score calculation and admission processing
     * 
     * @param string $cccd Candidate's CCCD
     * @param string $ma_nganh Major code
     * @return array ['passed' => bool, 'errors' => string[], 'threshold' => [...]]
     */
    public function checkAdmissionThreshold($cccd, $ma_nganh) {
        $result = ['passed' => true, 'errors' => [], 'threshold' => null];
        
        // Get major info (Cached)
        if (!isset($this->cachedMajorInfos[$ma_nganh])) {
            $this->cachedMajorInfos[$ma_nganh] = $this->masterData->find('dm_nganh', $ma_nganh, 'ma_nganh');
        }
        $major = $this->cachedMajorInfos[$ma_nganh];
        if (!$major) return $result;
        
        $nhomNganh = $major['nhom_nganh'] ?? 'Khac';
        $nguongHocLuc = $major['nguong_hoc_luc'] ?? null;
        $nguongDiemTHPT = $major['nguong_diem_thpt'] ?? null;
        $nguongDiemXTN = $major['nguong_diem_xtn'] ?? null;
        $nguongDiemHocBa = $major['nguong_diem_hocba'] ?? null;
        
        // No threshold for regular majors without any configured thresholds
        if ($nhomNganh === 'Khac' && !$nguongHocLuc && !$nguongDiemTHPT && !$nguongDiemHocBa) {
            return $result;
        }
        
        $result['threshold'] = [
            'nhom_nganh' => $nhomNganh,
            'nguong_hoc_luc' => $nguongHocLuc,
            'nguong_diem_thpt' => $nguongDiemTHPT,
            'nguong_diem_xtn' => $nguongDiemXTN,
            'nguong_diem_hocba' => $nguongDiemHocBa
        ];
        
        // 1. Check Học lực lớp 12
        if ($nguongHocLuc) {
            if (!isset($this->cachedGrade12Summaries[$cccd])) {
                $this->cachedGrade12Summaries[$cccd] = $this->academicModel->getGrade12Summary($cccd);
            }
            $grade12 = $this->cachedGrade12Summaries[$cccd];
            $hocLuc12 = $grade12['hoc_luc_ca_nam'] ?? null;
            $diemTb12 = $grade12['diem_tb_ca_nam'] ?? null;
            
            if (empty($hocLuc12) && !empty($diemTb12)) {
                $dtb = floatval($diemTb12);
                if ($dtb >= 8.0) $hocLuc12 = 'Giỏi';
                elseif ($dtb >= 6.5) $hocLuc12 = 'Khá';
                elseif ($dtb >= 5.0) $hocLuc12 = 'Trung bình';
                else $hocLuc12 = 'Yếu';
            }
            
            // Kiểm tra điều kiện OR: Điểm xét tốt nghiệp THPT >= ngưỡng tốt nghiệp tương ứng (ví dụ: SP: 8.0/8.5, Y: 6.5)
            $passedByGraduationScore = false;
            $diemXTN = null;
            if ($nguongDiemXTN) {
                if (!isset($this->cachedDiemXTN[$cccd])) {
                    $db = \App\Core\Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT diem_xet_tot_nghiep FROM diem_thi_thpt WHERE so_cccd = ? LIMIT 1");
                    $stmt->execute([$cccd]);
                    $val = $stmt->fetchColumn();
                    $this->cachedDiemXTN[$cccd] = ($val !== null && $val !== false && $val !== '') ? (float)$val : null;
                }
                $diemXTN = $this->cachedDiemXTN[$cccd];
                
                if ($diemXTN !== null && $diemXTN >= $nguongDiemXTN) {
                    $passedByGraduationScore = true;
                }
            }

            if ($hocLuc12) {
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
                    $err = "Học lực lớp 12 phải đạt loại " . ($labels[$nguongHocLuc] ?? $nguongHocLuc) 
                        . " trở lên (hiện tại: " . ($labels[$hocLuc12] ?? $hocLuc12) . ")";
                    if ($nguongDiemXTN) {
                        $err .= " HOẶC Điểm xét tốt nghiệp THPT >= " . number_format($nguongDiemXTN, 2) . " (hiện tại: " . ($diemXTN !== null ? number_format($diemXTN, 2) : 'N/A') . ")";
                    }
                    $result['errors'][] = $err;
                }
            } else {
                if (!$passedByGraduationScore) {
                    $result['passed'] = false;
                    $err = "Chưa có thông tin Học lực lớp 12 (cần cập nhật Bước 2 - Học bạ)";
                    if ($nguongDiemXTN) {
                        $err .= " HOẶC Điểm xét tốt nghiệp THPT >= " . number_format($nguongDiemXTN, 2) . " (hiện tại: " . ($diemXTN !== null ? number_format($diemXTN, 2) : 'N/A') . ")";
                    }
                    $result['errors'][] = $err;
                }
            }
        }
        
        // 2. Check Tổng điểm 3 môn THPT theo tổ hợp (Đã cộng điểm ưu tiên)
        if ($nguongDiemTHPT) {
            $bestScore = $this->calculateBestScore($cccd, $ma_nganh);
            $methodCode = $bestScore['method_code'] ?? null;
            $thptTotal = $bestScore['thpt_total'] ?? 0;
            $diemSoSanh = $thptTotal + ($bestScore['priority_score'] ?? 0);
            
            if ($methodCode === '100' && $thptTotal > 0 && $diemSoSanh < $nguongDiemTHPT) {
                // Kiểm tra điều kiện OR: Điểm xét tốt nghiệp THPT >= ngưỡng
                $passedByXTN = false;
                if ($nguongDiemXTN) {
                    $db = \App\Core\Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT diem_xet_tot_nghiep FROM diem_thi_thpt WHERE so_cccd = ? LIMIT 1");
                    $stmt->execute([$cccd]);
                    $diemXTN = $stmt->fetchColumn();
                    $diemXTN = ($diemXTN !== null && $diemXTN !== false && $diemXTN !== '') ? (float)$diemXTN : null;
                    
                    if ($diemXTN !== null && $diemXTN >= $nguongDiemXTN) {
                        $passedByXTN = true;
                    }
                }
                
                if (!$passedByXTN) {
                    $result['passed'] = false;
                    $errorMsg = "Tổng điểm 3 môn thi THPT theo tổ hợp xét tuyển phải đạt từ " 
                        . number_format($nguongDiemTHPT, 2) . " trở lên (hiện tại: " 
                        . number_format($thptTotal, 2) . ")";
                    if ($nguongDiemXTN) {
                        $errorMsg .= " HOẶC điểm xét TN THPT >= " . number_format($nguongDiemXTN, 2) 
                            . " (hiện tại: " . ($diemXTN !== null ? number_format($diemXTN, 2) : 'N/A') . ")";
                    }
                    $result['errors'][] = $errorMsg;
                }
            }
            // If thptTotal == 0, candidate hasn't entered THPT scores yet - don't block
        }
        
        // 3. Check ĐTB Học bạ cho ngành ngoài Sư phạm (Phương thức xét học bạ)
        // Quy chế Bộ GD&ĐT mục 3.1.2 khoản 3: "ĐTB 3 năm của 3 môn tổ hợp chưa quy đổi + điểm ưu tiên >= ngưỡng (mặc định 18.0)"
        if ($nguongDiemHocBa && $nhomNganh === 'Khac') {
            $bestScore = isset($bestScore) ? $bestScore : $this->calculateBestScore($cccd, $ma_nganh);
            $transcriptTotal = $bestScore['transcript_total'] ?? 0;
            $priority = $bestScore['priority_score'] ?? 0;
            $scoreToCompare = $transcriptTotal + $priority;
            
            if ($transcriptTotal > 0 && $scoreToCompare < $nguongDiemHocBa) {
                $result['passed'] = false;
                $result['errors'][] = "ĐTB học bạ 3 môn tổ hợp chưa quy đổi đã tính ưu tiên (" 
                    . number_format($scoreToCompare, 2) . ") phải đạt từ " 
                    . number_format($nguongDiemHocBa, 2) . " trở lên";
            }
        }
        
        return $result;
    }
}
