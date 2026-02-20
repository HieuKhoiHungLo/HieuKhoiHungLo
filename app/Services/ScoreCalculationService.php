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

            // 3. Iterate Combinations
            
            // 3. Iterate Combinations
            foreach ($combinations as $comboCode) {
                // echo "  Checking Combo: $comboCode\n"; flush();
                $comboSubjects = $this->getComboSubjects($comboCode);
                if (!$comboSubjects) continue;
                // echo "    Subjects: " . json_encode($comboSubjects) . "\n"; flush();

                // 3a. Calculate HOC_BA
                // echo "    Calculating HOC_BA...\n"; flush();
                $hbResult = $this->calculateMethodScore(
                    'HOC_BA', 
                    $comboSubjects, 
                    $transcriptavgs, 
                    $certificates,
                    $aptitudeScores,
                    $majorDetails,
                    $priorityPoints
                );
                // echo "    HOC_BA Result: " . json_encode($hbResult) . "\n"; flush();
                
                if ($hbResult && $hbResult['total'] > $bestScore) {
                    $bestScore = $hbResult['total'];
                    $bestCombo = $comboCode;
                    $bestMethod = 'HOC_BA';
                    $bestDetails = $hbResult['details'];
                }

                // 3b. Calculate DIEM_THI (Only if THPT scores exist)
                if (!empty($thptScores)) {
                    // echo "    Calculating DIEM_THI...\n"; flush();
                    $thptResult = $this->calculateMethodScore(
                        'DIEM_THI', 
                        $comboSubjects, 
                        $thptScores, 
                        $certificates, 
                        $aptitudeScores,
                        $majorDetails,
                        $priorityPoints
                    );
                    // echo "    DIEM_THI Result: " . json_encode($thptResult) . "\n"; flush();

                    if ($thptResult && $thptResult['total'] > $bestScore) {
                        $bestScore = $thptResult['total'];
                        $bestCombo = $comboCode;
                        $bestMethod = 'DIEM_THI';
                        $bestDetails = $thptResult['details'];
                    }
                }
            }

            // 4. Update Nguyen Vong
            $this->updateApplicationScore($cccd, $majorCode, $bestScore, $bestCombo, $bestMethod, $bestDetails);
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

    protected function calculateTranscriptAverages($cccd) {
        $records = $this->academicModel->getByCCCD($cccd);
        
        // Optimize: Fetch Subject Map only once
        static $codeToId = null;
        if ($codeToId === null) {
            $subjects = $this->masterDataRepo->getSubjects(); 
            $codeToId = [];
            foreach($subjects as $s) {
                $codeToId[strtoupper($s['ma_mon'])] = $s['id'];
            }
        }
        
        $colToCode = [
            'toan' => 'TOAN', 'van' => 'VAN', 'ngoai' => 'ANH',
            'ly' => 'LY', 'hoa' => 'HOA', 'sinh' => 'SINH',
            'su' => 'SU', 'dia' => 'DIA', 'gdcd' => 'GDCD',
            'cong_nghe' => 'CONG_NGHE', 'tin_hoc' => 'TIN'
        ];
        
        // Alias Helper
        if (!isset($codeToId['ANH']) && isset($codeToId['NGOAI_NGU'])) $colToCode['ngoai'] = 'NGOAI_NGU';
        if (!isset($codeToId['ANH']) && isset($codeToId['TIENG_ANH'])) $colToCode['ngoai'] = 'TIENG_ANH';

        $sums = []; 
        $counts = [];

        foreach ($records as $r) {
            foreach ($colToCode as $colKey => $maMon) {
                $checkCode = strtoupper($maMon);
                if (!isset($codeToId[$checkCode])) continue;
                $monId = $codeToId[$checkCode];
                
                $colPrefix = ($colKey == 'ngoai') ? "diem_ngoai_ngu" : "diem_{$colKey}";
                $val1 = isset($r["{$colPrefix}_hk1"]) && $r["{$colPrefix}_hk1"] !== '' ? (float)$r["{$colPrefix}_hk1"] : null;
                $val2 = isset($r["{$colPrefix}_hk2"]) && $r["{$colPrefix}_hk2"] !== '' ? (float)$r["{$colPrefix}_hk2"] : null;
                
                if (!isset($sums[$monId])) { $sums[$monId] = 0; $counts[$monId] = 0; }
                if ($val1 !== null) { $sums[$monId] += $val1; $counts[$monId]++; }
                if ($val2 !== null) { $sums[$monId] += $val2; $counts[$monId]++; }
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

        static $monMap = null;
        if (!$monMap) {
            $mons = $this->masterDataRepo->getSubjects();
            foreach ($mons as $m) $monMap[$m['ma_mon']] = $m['id']; 
        }
        
        $scores = [];
        $fieldMap = [
            'toan' => 'TO', 'van' => 'VA', 'tieng_anh' => 'TA', 
            'ly' => 'LY', 'hoa' => 'HO', 'sinh' => 'SI', 
            'su' => 'SU', 'dia' => 'DI', 'gdcd' => 'GD'
        ];
        // Handle code variations (TA vs ANH vs TIENG_ANH) - Basic map for now
        // Assuming Data in dm_mon uses standardized codes like 'TOAN', 'VAN', 'ANH' or 'TO', 'VA', 'TA'
        // Let's broaden the map
         $codeAliases = [
            'TO' => ['TOAN', 'TO'], 'VA' => ['VAN', 'NGU_VAN', 'VA'], 'TA' => ['ANH', 'TIENG_ANH', 'TA'],
            'LY' => ['LY', 'VAT_LY'], 'HO' => ['HOA', 'HOA_HOC', 'HO'], 'SI' => ['SINH', 'SINH_HOC', 'SI'],
            'SU' => ['SU', 'LICH_SU'], 'DI' => ['DIA', 'DIA_LY', 'DI'], 'GD' => ['GDCD', 'GD']
        ];
        
        foreach ($fieldMap as $field => $codeKey) {
            if (!isset($record[$field]) || $record[$field] === null || $record[$field] === '') continue;
            
            $aliases = $codeAliases[$codeKey] ?? [$codeKey];
            foreach ($aliases as $alias) {
                if (isset($monMap[$alias])) {
                    $scores[$monMap[$alias]] = (float)$record[$field];
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

    protected function calculateMethodScore($method, $subjects, $scores, $certs, $aptitude, $majorDetails, $priorityPoints) {
        $totalRaw = 0;
        $details = [];
        $allowCert = $majorDetails['co_xet_chung_chi'] ?? false;

        foreach ($subjects as $monId) {
            $baseScore = $scores[$monId] ?? 0;
            $certScore = ($allowCert && isset($certs[$monId])) ? $certs[$monId] : 0;
            
            // Aptitude Override
            $aptitudeScore = isset($aptitude[$monId]) ? $aptitude[$monId] : null;

            $finalScore = 0;
            $source = 'MISSING';

            if ($aptitudeScore !== null) {
                $finalScore = $aptitudeScore;
                $source = 'APTITUDE';
            } else {
                // Take max of base (transcript/thpt) and cert
                $finalScore = max($baseScore, $certScore);
                if ($finalScore > 0) {
                     $source = ($finalScore == $certScore && $certScore > $baseScore) ? 'CERT' : $method;
                }
            }

            $totalRaw += $finalScore;
            $details[$monId] = [
                'base' => $baseScore, 
                'cert' => $certScore, 
                'apt' => $aptitudeScore,
                'final' => $finalScore,
                'source' => $source
            ];
        }
        
        // Add Priority Points
        $totalFinal = $totalRaw + $priorityPoints;
        
        // Add Priority to details for transparency
        $details['priority'] = $priorityPoints;
        $details['total_raw'] = $totalRaw;

        return ['total' => $totalFinal, 'details' => $details];
    }
    
    protected function updateApplicationScore($cccd, $majorCode, $score, $combo, $method, $details) {
        try {
            $stmtCombo = $this->db->prepare("SELECT id FROM dm_to_hop WHERE ma_to_hop = ? LIMIT 1");
            $stmtCombo->execute([$combo]);
            $comboId = $stmtCombo->fetchColumn();

            $sql = "UPDATE nguyen_vong SET 
                    diem_xet_tuyen = ?, 
                    to_hop_xet_tuyen_id = ?,
                    phuong_thuc_xet_tuyen = ?,
                    chi_tiet_diem = ?
                    WHERE so_cccd = ? AND ma_nganh = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $score,
                $comboId ?: null,
                $method,
                json_encode($details),
                $cccd,
                $majorCode
            ]);
        } catch (\PDOException $e) {
            $msg = "SQL Error: " . $e->getMessage() . "\n" .
                   "SQL: $sql\n" .
                   "Params: " . json_encode([$score, $combo, $method, $details, $cccd, $majorCode]);
            file_put_contents('error_log_service.txt', $msg, FILE_APPEND);
            // Don't throw to stop other calculations? Or throw?
            // Logging is enough for batch process.
        }
    }
}
