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

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->masterDataRepo = new MasterDataRepository();
        $this->thiSinhRepo = new ThiSinhRepository(); // Note: Check if Repo is best or Model
        $this->academicModel = new AcademicRecord();
        $this->diemThiModel = new DiemThiTHPT();
    }

    public function calculate($cccd) {
        // 1. Fetch ALL Data
        $transcriptavgs = $this->calculateTranscriptAverages($cccd);
        $thptScores = $this->getThptScores($cccd);
        $certificates = $this->getCertificates($cccd);
        $aptitudeScores = $this->getAptitudeScores($cccd);
        // $certificates = [];
        // $aptitudeScores = [];
        
        // 1b. Calculate Priority Points (Area + Object)
        $priorityPoints = $this->calculatePriorityPoints($cccd);
        
        // 2. Get Applications (Nguyen Vong)
        $applications = $this->getApplications($cccd);
        
        if (empty($applications)) return;

        foreach ($applications as $app) {
            $majorCode = $app['ma_nganh'];
            $majorDetails = $this->getMajorDetails($majorCode);
            if (!$majorDetails) continue;

            $combinations = $this->masterDataRepo->getMajorCombinations($majorCode);
            
            $bestScore = 0;
            $bestCombo = null;
            $bestMethod = null;
            $bestDetails = [];

            $allCombinationsParams = [];

            // 3. Iterate Combinations
            foreach ($combinations as $comboCode) {
                $comboSubjects = $this->getComboSubjects($comboCode);
                if (!$comboSubjects) continue;

                // 3a. Calculate HOC_BA (Method 200)
                $hbResult = $this->calculateMethodScore(
                    'HOC_BA', 
                    $comboSubjects, 
                    $transcriptavgs, 
                    $certificates,
                    $aptitudeScores,
                    $majorDetails,
                    $priorityPoints
                );
                
                if ($hbResult) {
                    $allCombinationsParams["HB_{$comboCode}"] = $hbResult['total'];
                    if ($hbResult['total'] > $bestScore) {
                        $bestScore = $hbResult['total'];
                        $bestCombo = $comboCode;
                        $bestMethod = '200'; // Code 200 for Hoc Ba
                        $bestDetails = $hbResult['details'];
                    }
                }

                // 3b. Calculate DIEM_THI (Method 100)
                if (!empty($thptScores)) {
                    $thptResult = $this->calculateMethodScore(
                        'DIEM_THI', 
                        $comboSubjects, 
                        $thptScores, 
                        $certificates, 
                        $aptitudeScores,
                        $majorDetails,
                        $priorityPoints
                    );

                    if ($thptResult) {
                        $allCombinationsParams["THPT_{$comboCode}"] = $thptResult['total'];
                        if ($thptResult['total'] > $bestScore) {
                            $bestScore = $thptResult['total'];
                            $bestCombo = $comboCode;
                            $bestMethod = '100'; // Code 100 for THPT
                            $bestDetails = $thptResult['details'];
                        }
                    }
                }
            }

            // 4. Check Threshold (TT06/2026) and Update Nguyen Vong
            $thresholdResult = null;
            if (!empty($majorDetails['nguong_hoc_luc']) || !empty($majorDetails['nguong_diem_thpt'])) {
                $scoreCalc = new \App\Services\ScoreCalculator();
                $thresholdResult = $scoreCalc->checkAdmissionThreshold($cccd, $majorCode);
            }
            
            // Set score to 0 and add failure note if threshold not met
            $thresholdNote = '';
            if ($thresholdResult && !$thresholdResult['passed']) {
                $bestScore = 0;
                $thresholdNote = 'KHÔNG ĐẠT NGƯỠNG: ' . implode('; ', $thresholdResult['errors']);
            }
            
            $details = $bestDetails;
            $details['all_combinations'] = $allCombinationsParams;
            if ($thresholdNote) {
                $details['threshold_note'] = $thresholdNote;
            }
            
            $this->updateApplicationScore($cccd, $majorCode, $bestScore, $bestCombo, $bestMethod, $details);
        }
    }
    
    protected function calculatePriorityPoints($cccd) {
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
            $areas = $this->masterDataRepo->getPriorityAreas();
            if (isset($areas[$maKV])) {
                $diemKhuVuc = $areas[$maKV];
            }
        }
        
        // Get Object Points
        if (!empty($candidate['doi_tuong_uu_tien'])) {
            $maDT = trim($candidate['doi_tuong_uu_tien']);
            $objects = $this->masterDataRepo->getPriorityObjects();
            if (isset($objects[$maDT])) {
                $diemDoiTuong = $objects[$maDT];
            }
        }
        return $diemKhuVuc + $diemDoiTuong;
    }

    protected function getMajorDetails($majorCode) {
        // Optimize: Cache this call if needed
        $majors = $this->masterDataRepo->getMajors();
        foreach ($majors as $m) {
            if ($m['ma_nganh'] == $majorCode) {
                return $m;
            }
        }
        return null;
    }

    private function getSubjectAliases() {
        return [
            'toan' => ['TOAN', 'TO'],
            'van' => ['VAN', 'NGU_VAN', 'VA'],
            'ngoai_ngu' => ['ANH', 'TIENG_ANH', 'NGOAI_NGU', 'TA', 'NN'],
            'ly' => ['LY', 'VAT_LY', 'VAT LI'],
            'hoa' => ['HOA', 'HOA_HOC', 'HO'],
            'sinh' => ['SINH', 'SINH_HOC', 'SI'],
            'su' => ['SU', 'LICH_SU'],
            'dia' => ['DIA', 'DIA_LY', 'DI'],
            'gdcd' => ['GDCD', 'GD', 'GDKT_PL', 'KTPL'],
            'cong_nghe' => ['CONG_NGHE', 'CN'],
            'tin_hoc' => ['TIN', 'TIN_HOC']
        ];
    }

    protected function calculateTranscriptAverages($cccd) {
        $records = $this->academicModel->getByCCCD($cccd);
        
        static $codeToId = null;
        if ($codeToId === null) {
            $subjects = $this->masterDataRepo->getSubjects(); 
            $codeToId = [];
            foreach($subjects as $s) {
                $codeToId[strtoupper($s['ma_mon'])] = $s['id'];
            }
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
        $record = $this->diemThiModel->getByCCCD($cccd);
        if (!$record) return [];

        static $codeToId = null;
        if ($codeToId === null) {
            $subjects = $this->masterDataRepo->getSubjects();
            $codeToId = [];
            foreach($subjects as $s) {
                $codeToId[strtoupper($s['ma_mon'])] = $s['id'];
            }
        }
        
        $scores = [];
        // Map db columns in diem_thi_thpt to logical column names and use aliases
        $fieldMap = [
            'toan' => 'toan', 'van' => 'van', 'tieng_anh' => 'ngoai_ngu', 
            'ly' => 'ly', 'hoa' => 'hoa', 'sinh' => 'sinh', 
            'su' => 'su', 'dia' => 'dia', 'gdcd' => 'gdcd', 'tin_hoc' => 'tin_hoc'
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
        $certs = $this->thiSinhRepo->getCertifications($cccd);
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
        $sql = "SELECT m.id as mon_id, d.diem 
                FROM diem_nang_khieu d
                JOIN dm_mon m ON d.ma_mon = m.ma_mon
                WHERE d.so_cccd = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cccd]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    protected function getApplications($cccd) {
        $stmt = $this->db->prepare("SELECT * FROM nguyen_vong WHERE so_cccd = ?");
        $stmt->execute([$cccd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    protected function getComboSubjects($comboCode) {
        static $comboCache = [];
        if (isset($comboCache[$comboCode])) return $comboCache[$comboCode];

        $stmt = $this->db->prepare("SELECT mon_1_id, mon_2_id, mon_3_id FROM dm_to_hop WHERE ma_to_hop = ?");
        $stmt->execute([$comboCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $comboCache[$comboCode] = [$row['mon_1_id'], $row['mon_2_id'], $row['mon_3_id']];
        } else {
            $comboCache[$comboCode] = null;
        }
        return $comboCache[$comboCode];
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

        return ['total' => $totalFinal, 'details' => $details];
    }
    
    protected function updateApplicationScore($cccd, $majorCode, $score, $combo, $method, $details) {
        try {
            $stmtCombo = $this->db->prepare("SELECT id FROM dm_to_hop WHERE ma_to_hop = ? LIMIT 1");
            $stmtCombo->execute([$combo]);
            $comboId = $stmtCombo->fetchColumn();
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
