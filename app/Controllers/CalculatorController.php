<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Services\ScoreCalculator;

class CalculatorController extends Controller {
    public function index() {
        // Track visit
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $stmt = $db->prepare("INSERT INTO page_views (url, ip_address, user_agent) VALUES (?, ?, ?)");
            $stmt->execute(['/tinh-diem-xet-tuyen', $ip, $ua]);
        } catch (\Exception $e) {
            // Ignore error
        }

        $masterData = new MasterData();
        $majors = $masterData->getMajorsWithCombinations();
        
        $subjects = [
            'Ngữ văn' => 'ngu_van',
            'Toán' => 'toan',
            'Lịch sử' => 'lich_su',
            'Địa lí' => 'dia_li',
            'GDKT & PL' => 'gdkt_pl',
            'Vật lí' => 'vat_li',
            'Hóa học' => 'hoa_hoc',
            'Sinh học' => 'sinh_hoc',
            'Công nghệ' => 'cong_nghe',
            'Tin học' => 'tin_hoc',
            'Ngoại ngữ' => 'ngoai_ngu'
        ];

        // THPT Subjects map similarly, though grouped differently sometimes.
        $thptSubjects = [
            'Ngữ văn' => 'ngu_van',
            'Toán' => 'toan',
            'Ngoại ngữ' => 'ngoai_ngu',
            'Vật lí' => 'vat_li',
            'Hóa học' => 'hoa_hoc',
            'Sinh học' => 'sinh_hoc',
            'Lịch sử' => 'lich_su',
            'Địa lí' => 'dia_li',
            'GDCD' => 'gdkt_pl' // Map GDCD to gdkt_pl for combination lookup if needed
        ];

        $combinations = $masterData->getCombinations();
        
        // Priority Settings
        $priorityData = [
            'KV1' => (float)$masterData->getSetting('score_priority_kv1'),
            'KV2-NT' => (float)$masterData->getSetting('score_priority_kv2_nt'),
            'KV2' => (float)$masterData->getSetting('score_priority_kv2'),
            'KV3' => (float)$masterData->getSetting('score_priority_kv3'),
            'DT1' => (float)$masterData->getSetting('score_priority_ut1'),
            'DT2' => (float)$masterData->getSetting('score_priority_ut2'),
        ];

        $this->view('calculator', [
            'majors' => $majors,
            'subjects' => $subjects,
            'thptSubjects' => $thptSubjects,
            'combinations' => $combinations,
            'priorityData' => $priorityData
        ]);
    }

    public function calculate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            return;
        }

        $masterData = new MasterData();
        
        // Convert JS input payload to expected format for ScoreCalculator
        $khuVuc = $input['khuVuc'] ?? '';
        $doiTuong = $input['doiTuong'] ?? '';
        $majorCode = $input['majorCode'] ?? '';
        $method = $input['method'] ?? 'XHB'; // XHB or XDT

        // Build mocked scores for calculation
        // The ScoreCalculator expects points mapped by subject string names or codes depending on the implementation.
        // Let's rely on standard logic 
        $diemThanhPhan = [];
        
        if ($method === 'XHB') {
            $transcripts = $input['transcripts'] ?? []; // format: ['ngu_van' => [hk10, hk11, hk12], ...]
            // Calculate TBC for each subject
            foreach ($transcripts as $code => $scores) {
                // Ignore empty
                $validScores = array_filter($scores, function($s) { return is_numeric($s) && $s >= 0; });
                if (count($validScores) === 3) {
                    $tbc = round(array_sum($validScores) / 3, 2);
                    $diemThanhPhan[$code] = $tbc;
                }
            }
        } else {
            $thpt = $input['thpt'] ?? [];
            foreach ($thpt as $code => $score) {
                 if (is_numeric($score) && $score >= 0) {
                     $diemThanhPhan[$code] = floatval($score);
                 }
            }
        }

        // Get priority points
        $priorityPoints = $this->calculatePriorityPoints($khuVuc, $doiTuong, $masterData);
        
        // Get all combinations
        $allCombinations = $masterData->getCombinations();
        
        // Allowed combinations for specific major (if majorCode is provided)
        $allowedCombs = [];
        if ($majorCode) {
            $allowedCombs = $masterData->getMajorCombinations($majorCode);
        }

        $combinationsList = [];
        foreach ($allCombinations as $c) {
            if ($majorCode && !in_array($c['ma_to_hop'], $allowedCombs)) continue;
            $combinationsList[] = $c;
        }
        
        $results = [];
        $maxScore = 0;
        $bestComb = null;

        foreach ($combinationsList as $comb) {
            $code = $comb['ma_to_hop'];
            $m1 = $comb['mon1_ten'];
            $m2 = $comb['mon2_ten'];
            $m3 = $comb['mon3_ten'];
            
            // Map subjective mappings from DB to internal code if needed
            // For simplicity, assume $diemThanhPhan is keyed by exact db strings like 'Toán', 'Vật lí'
            // Wait, we need to ensure keys match. The $calc->extractSubjectScore() expects specific structures.
            // Let's use simple logic:
            $s1 = $this->getSubjectScore($m1, $diemThanhPhan);
            $s2 = $this->getSubjectScore($m2, $diemThanhPhan);
            $s3 = $this->getSubjectScore($m3, $diemThanhPhan);

            if ($s1 !== null && $s2 !== null && $s3 !== null) {
                $total = round($s1 + $s2 + $s3 + $priorityPoints, 2);
                $isPassLiet = ($s1 > 1.0 && $s2 > 1.0 && $s3 > 1.0); // Assuming > 1.0 is passing anti-paralysis
                
                if ($isPassLiet) {
                    $results[] = [
                        'mth' => $code,
                        'name' => "$m1, $m2, $m3",
                        's1' => $s1, 's2' => $s2, 's3' => $s3,
                        'totalRaw' => round($s1 + $s2 + $s3, 2),
                        'total' => $total
                    ];
                    if ($total > $maxScore) {
                        $maxScore = $total;
                        $bestComb = $code;
                    }
                }
            }
        }

        // Sort results by total desc
        usort($results, function($a, $b) { return $b['total'] <=> $a['total']; });

        echo json_encode([
            'success' => true,
            'priority' => $priorityPoints,
            'method' => $method,
            'results' => $results,
            'best' => $bestComb
        ]);
        exit;
    }

    private function getSubjectScore($subjectName, $scores) {
        // Map DB string to standard keys used in our form
        $map = [
            'Ngữ văn' => 'ngu_van',
            'Toán' => 'toan',
            'Lịch sử' => 'lich_su',
            'Địa lí' => 'dia_li',
            'GDCD' => 'gdkt_pl',
            'GDKT&PL' => 'gdkt_pl',
            'Vật lý' => 'vat_li',
            'Vật lí' => 'vat_li',
            'Hóa học' => 'hoa_hoc',
            'Sinh học' => 'sinh_hoc',
            'Công nghệ' => 'cong_nghe',
            'Tin học' => 'tin_hoc',
            'Tiếng Anh' => 'ngoai_ngu',
            'Ngoại ngữ' => 'ngoai_ngu'
        ];
        $key = $map[$subjectName] ?? strtolower($subjectName);
        return isset($scores[$key]) ? $scores[$key] : null;
    }

    private function calculatePriorityPoints($khuVuc, $doiTuong, $masterData) {
        $prioSum = 0;

        // KV Priority
        $keyMap = [
            'KV1' => 'score_priority_kv1',
            'KV2-NT' => 'score_priority_kv2_nt',
            'KV2' => 'score_priority_kv2',
            'KV3' => 'score_priority_kv3'
        ];
        if (isset($keyMap[$khuVuc])) {
            $prioSum += (float)$masterData->getSetting($keyMap[$khuVuc]);
        }

        // DT Priority
        if (in_array($doiTuong, ['1','2','3','4', '01','02','03','04'])) {
            $prioSum += (float)$masterData->getSetting('score_priority_ut1');
        } elseif (in_array($doiTuong, ['5','6','7', '05','06','07'])) {
            $prioSum += (float)$masterData->getSetting('score_priority_ut2');
        }

        return $prioSum;
    }
}
