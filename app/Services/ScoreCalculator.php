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

    public function __construct() {
        $this->thptModel = new DiemThiTHPT();
        $this->academicModel = new AcademicRecord();
        $this->subjectModel = new Subject();
        $this->aptitudeModel = new AptitudeScore();
        $this->masterData = new MasterData();
    }

    /**
     * Calculate best score for a candidate for a specific major
     */
    public function calculateBestScore($cccd, $ma_nganh) {
        // 1. Get Combinations
        $combinationCodes = $this->masterData->getMajorCombinations($ma_nganh);
        if (empty($combinationCodes)) {
             $major = $this->masterData->find('dm_nganh', $ma_nganh, 'ma_nganh');
             if ($major) {
                 // Try to use new relation first, fallback to string
                 // For now, assume we rely on string if relation helper returns empty
                 if (!empty($major['khoi_xet_tuyen'])) {
                    $combinationCodes = array_map('trim', explode(',', $major['khoi_xet_tuyen']));
                 }
             }
        }

        // 2. Fetch ALL scores from Normalized Table
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT mon_id, loai_diem, diem FROM diem_chi_tiet WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        $allScores = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Group Scores: $scores['THPT'][mon_id] = 8.5
        $scores = [];
        foreach ($allScores as $row) {
            $scores[$row['loai_diem']][$row['mon_id']] = (float)$row['diem'];
        }

        // 3. Fetch External Aptitude Scores
        $stmtExt = $db->prepare("SELECT ma_mon, diem FROM diem_nang_khieu_ngoai WHERE so_cccd = ?");
        $stmtExt->execute([$cccd]);
        $extScores = $stmtExt->fetchAll(\PDO::FETCH_ASSOC);

        // Map ma_mon to mon_id
        if (!empty($extScores)) {
            foreach ($extScores as $ext) {
                // Find subject ID by ma_mon
                $subj = $this->masterData->find('dm_mon', $ext['ma_mon'], 'ma_mon');
                if ($subj) {
                    // Store as 'NK' (Nang Khieu) type or specific type if needed
                    // Using 'NK' allows generic fallback in logic below
                    $scores['NK'][$subj['id']] = (float)$ext['diem'];
                }
            }
        }

        // Get Language Certificate Score if any
        $langScoreModel = new \App\Models\LanguageScore();
        $langRecord = $langScoreModel->getByCCCD($cccd);
        $convertedLangScore = $langRecord ? (float)$langRecord['diem_quy_doi'] : 0;

        // Get Transcript Weight Ratio
        $start = $db->query("SELECT value FROM settings WHERE key = 'transcript_weight_ratio'");
        $weightRatio = (float)($start->fetchColumn() ?: 1.0);

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
            $combo = $combinationModel->findByCode($code);
            if (!$combo) continue;

            $subjectIds = [
                $combo['mon_1_id'],
                $combo['mon_2_id'],
                $combo['mon_3_id']
            ];
            
            // Resolve Subject Codes for details
            $subjectsMetadata = [];
            foreach ($subjectIds as $sId) {
                $subjectsMetadata[$sId] = $this->masterData->find('dm_mon', $sId);
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
            $hbDetails = [];
            $hbValid = true;

            foreach ($subjectIds as $sId) {
                // Prefer CN_12, fallback to others if logic dictates (currently just CN_12 based on requirements)
                $rawScore = $scores['HB_CN_12'][$sId] ?? ($scores['NK'][$sId] ?? 0);

                 // Handle Lang Cert for Transcript too? Usually Yes.
                 $subMeta = $subjectsMetadata[$sId] ?? null;
                 if ($subMeta && in_array($subMeta['cot_diem'], ['ngoai_ngu', 'tieng_anh']) && $convertedLangScore > 0) {
                      $rawScore = max($rawScore, $convertedLangScore);
                 }

                if ($rawScore <= 0) $hbValid = false;
                $hbSum += $rawScore;
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
                $weightedHB = round($hbSum * $weightRatio, 3);
                
                if ($weightedHB > $bestResult['final_score']) {
                    $bestResult = [
                        'total' => $hbSum, 
                        'thpt_total' => 0,
                        'transcript_total' => $hbSum,
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
            $threshold = (float)$this->masterData->getSetting('score_threshold_dampening') ?: 22.5;
            $divisor = (float)$this->masterData->getSetting('score_dampening_divisor') ?: 7.5;

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
        $candidateModel = new \App\Models\ThiSinh();
        $candidate = $candidateModel->findByCCCD($cccd);
        if (!$candidate) return 0;

        // 1. Check Graduation Year Condition (Grad Year + 1)
        if (!empty($candidate['nam_tot_nghiep'])) {
            $currentYear = date('Y');
            if (($currentYear - $candidate['nam_tot_nghiep']) > 1) {
                return 0; // Expired priority
            }
        }

        $prioSum = 0;

        // 2. Area Priority
        if (!empty($candidate['khu_vuc_uu_tien'])) {
            $keyMap = [
                'KV1' => 'score_priority_kv1',
                'KV2-NT' => 'score_priority_kv2_nt',
                'KV2' => 'score_priority_kv2',
                'KV3' => 'score_priority_kv3'
            ];
            $key = $keyMap[$candidate['khu_vuc_uu_tien']] ?? null;
            if ($key) {
                $prioSum += (float)$this->masterData->getSetting($key);
            }
        }

        // 3. Object Priority
        if (!empty($candidate['doi_tuong_uu_tien'])) {
            $dt = $candidate['doi_tuong_uu_tien'];
            if (in_array($dt, ['01','02','03','04'])) {
                $prioSum += (float)$this->masterData->getSetting('score_priority_ut1');
            } 
            elseif (in_array($dt, ['05','06','07'])) {
                $prioSum += (float)$this->masterData->getSetting('score_priority_ut2');
            }
        }

        return $prioSum;
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
        
        // Get major info
        $major = $this->masterData->find('dm_nganh', $ma_nganh, 'ma_nganh');
        if (!$major) return $result;
        
        $nhomNganh = $major['nhom_nganh'] ?? 'Khac';
        $nguongHocLuc = $major['nguong_hoc_luc'] ?? null;
        $nguongDiemTHPT = $major['nguong_diem_thpt'] ?? null;
        
        // No threshold for regular majors
        if ($nhomNganh === 'Khac' && !$nguongHocLuc && !$nguongDiemTHPT) {
            return $result;
        }
        
        $result['threshold'] = [
            'nhom_nganh' => $nhomNganh,
            'nguong_hoc_luc' => $nguongHocLuc,
            'nguong_diem_thpt' => $nguongDiemTHPT
        ];
        
        // 1. Check Học lực lớp 12
        if ($nguongHocLuc) {
            $grade12 = $this->academicModel->getGrade12Summary($cccd);
            $hocLuc12 = $grade12['hoc_luc_ca_nam'] ?? null;
            
            if ($hocLuc12) {
                $hocLucRank = [
                    'TỐT' => 4, 'ĐẠT' => 3, 'TRUNG BÌNH' => 2, 'CHƯA ĐẠT' => 1,
                    'Gioi' => 4, 'Kha' => 3, 'TrungBinh' => 2, 'Yeu' => 1,
                    'Giỏi' => 4, 'Khá' => 3, 'Trung bình' => 2, 'Yếu' => 1
                ];
                $requiredRank = $hocLucRank[$nguongHocLuc] ?? 0;
                $actualRank = $hocLucRank[$hocLuc12] ?? 0;
                
                if ($actualRank < $requiredRank) {
                    $result['passed'] = false;
                    $labels = ['Gioi' => 'Giỏi', 'Kha' => 'Khá', 'TrungBinh' => 'Trung bình', 'Yeu' => 'Yếu'];
                    $result['errors'][] = "Học lực lớp 12 phải đạt loại " . ($labels[$nguongHocLuc] ?? $nguongHocLuc) 
                        . " trở lên (hiện tại: " . ($labels[$hocLuc12] ?? $hocLuc12) . ")";
                }
            } else {
                $result['passed'] = false;
                $result['errors'][] = "Chưa có thông tin Học lực lớp 12 (cần cập nhật Bước 2 - Học bạ)";
            }
        }
        
        // 2. Check Tổng điểm 3 môn THPT theo tổ hợp
        if ($nguongDiemTHPT) {
            $bestScore = $this->calculateBestScore($cccd, $ma_nganh);
            $thptTotal = $bestScore['thpt_total'] ?? 0;
            
            if ($thptTotal > 0 && $thptTotal < $nguongDiemTHPT) {
                $result['passed'] = false;
                $result['errors'][] = "Tổng điểm 3 môn thi THPT theo tổ hợp xét tuyển phải đạt từ " 
                    . number_format($nguongDiemTHPT, 2) . " trở lên (hiện tại: " 
                    . number_format($thptTotal, 2) . ")";
            }
            // If thptTotal == 0, candidate hasn't entered THPT scores yet - don't block
        }
        
        return $result;
    }
}
