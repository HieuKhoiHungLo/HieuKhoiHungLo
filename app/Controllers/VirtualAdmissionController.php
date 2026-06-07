<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Services\ScoreCalculationService;
use App\Services\VirtualFilterService;
use PDO;

class VirtualAdmissionController extends Controller {
    protected $masterData;
    protected $scoreService;
    protected $filterService;
    protected $db;

    public function __construct() {
        $this->masterData = new MasterData();
        $this->scoreService = new ScoreCalculationService();
        $this->filterService = new VirtualFilterService();
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }

    public function index() {
        $sessions = $this->masterData->getSessions();
        // Lấy danh sách năm duy nhất
        $years = $this->db->query("SELECT DISTINCT nam_tuyen_sinh FROM dot_tuyen_sinh ORDER BY nam_tuyen_sinh DESC")->fetchAll(PDO::FETCH_COLUMN);
        
        // Cần truyền thêm danh sách tổ hợp để render table headers và hiển thị chi tiết (ví dụ: A00 (TO-LI-HO))
        $combinations = $this->db->query("
            SELECT th.ma_to_hop, m1.ma_mon as m1, m2.ma_mon as m2, m3.ma_mon as m3 
            FROM dm_to_hop th 
            LEFT JOIN dm_mon m1 ON th.mon_1_id = m1.id
            LEFT JOIN dm_mon m2 ON th.mon_2_id = m2.id
            LEFT JOIN dm_mon m3 ON th.mon_3_id = m3.id
            ORDER BY th.ma_to_hop
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $this->view('admin/virtual_admission/index', [
            'title' => 'Xét Tuyển Lọc Ảo',
            'sessions' => $sessions,
            'years' => $years,
            'combinations' => $combinations,
            'needsDataTables' => true
        ]);
    }

    public function loadBatchData() {
        $sessionId = $_POST['session_id'] ?? $_GET['session_id'] ?? null;
        if (!$sessionId) {
            $this->json(['draw' => intval($_POST['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
            return;
        }

        try {
            ini_set('memory_limit', '1024M');

            // 1. Nhận tham số cấu hình Server-Side Processing từ DataTables
            $draw = $_POST['draw'] ?? 1;
            $start = $_POST['start'] ?? 0;
            $length = $_POST['length'] ?? 50;
            $search = $_POST['search']['value'] ?? '';

            if ($length < 1) $length = 50;

            // 2. Base Query và mệnh đề Tìm kiếm
            $baseFrom = "FROM thi_sinh ts 
                         JOIN nguyen_vong nv ON ts.so_cccd = nv.so_cccd 
                         LEFT JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id 
                         WHERE nv.dot_tuyen_sinh_id = ?
                         AND (nv.trang_thai = 'DaDuyet' OR nv.trang_thai LIKE '%Đã duyệt%')";
            
            $searchSql = "";
            $params = [$sessionId];
            
            if (!empty($search)) {
                // Postgres hỗ trợ ILIKE để tìm kiếm không phân biệt hoa thường
                $searchSql = " AND (ts.ho_va_ten ILIKE ? OR ts.so_cccd ILIKE ? OR nv.ma_nganh ILIKE ? OR cs.to_hop_toi_uu ILIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            // 3. Đếm tổng số lượng bản ghi (trước khi tìm kiếm)
            $stmtTotal = $this->db->prepare("SELECT COUNT(*) " . explode("WHERE", $baseFrom)[0] . "WHERE nv.dot_tuyen_sinh_id = ? AND (nv.trang_thai = 'DaDuyet' OR nv.trang_thai LIKE '%Đã duyệt%')");
            $stmtTotal->execute([$sessionId]);
            $recordsTotal = $stmtTotal->fetchColumn() ?: 0;

            // 4. Đếm tổng số lượng bản ghi (SAU khi tìm kiếm)
            if (!empty($search)) {
                $stmtFiltered = $this->db->prepare("SELECT COUNT(*) $baseFrom $searchSql");
                $stmtFiltered->execute($params);
                $recordsFiltered = $stmtFiltered->fetchColumn() ?: 0;
            } else {
                $recordsFiltered = $recordsTotal;
            }

            // 5. Tính tổng số thí sinh duy nhất (Candidate Count) - Độc lập tìm kiếm
            $stmtC = $this->db->prepare("SELECT COUNT(DISTINCT nv.so_cccd) FROM nguyen_vong nv WHERE nv.dot_tuyen_sinh_id = ? AND (nv.trang_thai = 'DaDuyet' OR nv.trang_thai LIKE '%Đã duyệt%')");
            $stmtC->execute([$sessionId]);
            $candidateCount = $stmtC->fetchColumn() ?: 0;

            // 6. Truy vấn Dữ liệu Phân trang (OFFSET & LIMIT) trực tiếp trên Database
            // Cố định Order By cấu trúc để đảm bảo DataTables luôn hiện đúng
            $dataSql = "
                SELECT 
                    ts.id, ts.so_cccd, ts.ho_va_ten, ts.gioi_tinh, ts.nam_tot_nghiep, 
                    ts.khu_vuc_uu_tien, ts.doi_tuong_uu_tien,
                    nv.ma_nganh, nv.thu_tu_nguyen_vong,
                    cs.diem_xet_tuyen, cs.to_hop_toi_uu, cs.phuong_thuc_toi_uu,
                    cs.chi_tiet_diem, cs.trang_thai_trung_tuyen,
                    cs.diem_mon_1, cs.diem_mon_2, cs.diem_mon_3,
                    (SELECT string_agg(ma_to_hop, ', ' ORDER BY ma_to_hop) 
                     FROM dm_nganh_to_hop nth 
                     WHERE nth.ma_nganh = nv.ma_nganh) as all_combos
                $baseFrom $searchSql 
                ORDER BY ts.so_cccd, nv.thu_tu_nguyen_vong ASC 
                LIMIT " . (int)$length . " OFFSET " . (int)$start;
            
            $stmt = $this->db->prepare($dataSql);
            $stmt->execute($params);

            $rows = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if (!empty($row['chi_tiet_diem'])) {
                    $p = json_decode($row['chi_tiet_diem'], true);
                    if ($p && is_array($p)) {
                        $row['chi_tiet_diem'] = json_encode([
                            'all_combinations' => $p['all_combinations'] ?? [],
                            'total_raw' => $p['total_raw'] ?? 0,
                            'priority_raw' => $p['priority_raw'] ?? 0,
                            'priority_converted' => $p['priority_converted'] ?? 0,
                            'threshold_note' => $p['threshold_note'] ?? ''
                        ], JSON_UNESCAPED_UNICODE);
                    }
                }
                $rows[] = $row;
            }

            // 7. Gói dữ liệu chuẩn JSON cho DataTables SSP
            $this->json([
                'draw' => intval($draw),
                'recordsTotal' => intval($recordsTotal),
                'recordsFiltered' => intval($recordsFiltered),
                'data' => $rows,
                'candidate_count' => $candidateCount,
                'aspiration_count' => $recordsTotal
            ]);
        } catch (\Exception $e) {
            error_log("loadBatchData SSP Error: " . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function recalculateScores() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid Request']);
            exit;
        }

        $sessionId = $_POST['session_id'] ?? null;
        if (!$sessionId) {
            echo json_encode(['success' => false, 'message' => 'Chưa chọn đợt xét tuyển.']);
            exit;
        }

        try {
            // Support Chunked Processing: Check if specific CCCDs were sent
            $cccds = $_POST['cccds'] ?? null;
            if ($cccds && is_string($cccds)) {
                $cccds = json_decode($cccds, true);
            }

            $force = isset($_POST['force']) && $_POST['force'] == '1';

            if (!empty($cccds) && is_array($cccds)) {
                // High-performance batch calculation (Still synchronous for small chunks from UI)
                $successCount = $this->scoreService->recalculateBatch($sessionId, $cccds, $force);
                $this->json(['success' => true, 'count' => $successCount]);
            } else {
                // Full Recalculate -> Lead to Queue for true background processing
                $queue = new \App\Services\QueueService();
                $queue->enqueue([
                    'type' => 'recalculate_session',
                    'session_id' => $sessionId,
                    'force' => $force,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $this->json([
                    'success' => true, 
                    'queued' => true,
                    'message' => "Yêu cầu tính toán lại toàn bộ đã được đưa vào hàng đợi. Vui lòng theo dõi tiến độ."
                ]);
            }
        } catch (\Throwable $e) {
            error_log("Recalculate Error: " . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Get all candidate IDs to process (for frontend progress bar)
     */
    public function apiGetCccds() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) {
            $this->json(['success' => false, 'message' => 'Missing session ID'], 400);
            return;
        }

        $force = ($_GET['force'] ?? 0) == 1;
        $cccds = $this->scoreService->getCandidateIds($sessionId, $force);
        $this->json([
            'success' => true,
            'cccds' => $cccds,
            'total' => count($cccds)
        ]);
    }

    /**
     * Tạm thời để tạo dữ liệu test 15.000 hồ sơ
     */
    public function runStressSeeder() {
        set_time_limit(900); 
        $db = \App\Core\Database::getInstance()->getConnection();
        $targetSessionId = 3; 

        $totalCandidates = 15000;
        $chunkSize = 500;

        $majors = $db->query("SELECT ma_nganh FROM dm_nganh")->fetchAll(PDO::FETCH_COLUMN);

        for ($i = 0; $i < $totalCandidates; $i += $chunkSize) {
            $batchCccds = [];
            for ($j = 0; $j < min($chunkSize, $totalCandidates - $i); $j++) {
                $batchCccds[] = sprintf("%012d", $i + $j + 100000000000);
            }

            $db->beginTransaction();
            try {
                $tsValues = [];
                foreach ($batchCccds as $cccd) {
                    $tsValues[] = "('$cccd', 'Candidate $cccd', '2008-01-01', 'Nam', 'KV" . rand(1, 3) . "', '0" . rand(1, 7) . "')";
                }
                $db->exec("INSERT INTO thi_sinh (so_cccd, ho_va_ten, ngay_sinh, gioi_tinh, khu_vuc_uu_tien, doi_tuong_uu_tien) VALUES " . implode(',', $tsValues));

                $hsValues = [];
                foreach ($batchCccds as $cccd) {
                    $hsValues[] = "('$cccd', $targetSessionId, 'Đã duyệt')";
                }
                $db->exec("INSERT INTO ho_so_xet_tuyen (so_cccd, dot_tuyen_sinh_id, trang_thai) VALUES " . implode(',', $hsValues));

                $nvValues = [];
                foreach ($batchCccds as $cccd) {
                    $numNv = rand(1, 4);
                    $randKeys = (array)array_rand($majors, $numNv);
                    foreach ($randKeys as $idx => $key) {
                        $m = $majors[$key];
                        $nvValues[] = "('$cccd', $targetSessionId, '$m', " . ($idx + 1) . ", 'DaDuyet')";
                    }
                }
                $db->exec("INSERT INTO nguyen_vong (so_cccd, dot_tuyen_sinh_id, ma_nganh, thu_tu_nguyen_vong, trang_thai) VALUES " . implode(',', $nvValues));

                $kqhtValues = [];
                foreach ($batchCccds as $cccd) {
                    for ($lop = 10; $lop <= 12; $lop++) {
                        $v = array_map(fn() => rand(50, 95) / 10, range(1, 9));
                        $kqhtValues[] = "('$cccd', $lop, " . implode(',', $v) . ")";
                    }
                }
                $db->exec("INSERT INTO ket_qua_hoc_tap (so_cccd, lop, diem_toan_cn, diem_van_cn, diem_ngoai_ngu_cn, diem_ly_cn, diem_hoa_cn, diem_sinh_cn, diem_su_cn, diem_dia_cn, diem_gdcd_cn) VALUES " . implode(',', $kqhtValues));

                $thptValues = [];
                foreach ($batchCccds as $cccd) {
                    $v = array_map(fn() => rand(50, 95) / 10, range(1, 9));
                    $thptValues[] = "('$cccd', " . implode(',', $v) . ")";
                }
                $db->exec("INSERT INTO diem_thi_thpt (so_cccd, toan, van, tieng_anh, ly, hoa, sinh, su, dia, gdcd) VALUES " . implode(',', $thptValues));

                $db->commit();
            } catch (\Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                echo "Error at $i: " . $e->getMessage();
                exit;
            }
        }
        echo "SUCCESS: Generated 15,000 records.";
        exit;
    }

    public function apiSync() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid Request']);
            exit;
        }

        $sessionId = $_POST['session_id'] ?? null;
        if (!$sessionId) {
            echo json_encode(['success' => false, 'message' => 'Chưa chọn đợt xét tuyển.']);
            exit;
        }

        $result = $this->filterService->syncData($sessionId);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Đã đồng bộ dữ liệu hồ sơ được duyệt thành công.']);
        } else {
            $this->json(['success' => false, 'message' => 'Lỗi khi đồng bộ dữ liệu.'], 500);
        }
    }

    public function exportExcel() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) die("Chưa chọn đợt xét tuyển.");
        $this->doExportWithFilter($sessionId, 'all');
    }

    public function exportAdmitted() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) die("Chưa chọn đợt xét tuyển.");
        $this->doExportWithFilter($sessionId, 'admitted');
    }

    public function exportFailed() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) die("Chưa chọn đợt xét tuyển.");
        $this->doExportWithFilter($sessionId, 'failed');
    }

    public function exportAcademicFail() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) die("Chưa chọn đợt xét tuyển.");
        $this->doExportWithFilter($sessionId, 'academic_fail');
    }

    private function doExportWithFilter($sessionId, $type = 'all') {

        // Fetch combinations to map to_hop_toi_uu to rich label and get subject IDs
        $combos = $this->db->query("
            SELECT th.ma_to_hop, th.mon_1_id, th.mon_2_id, th.mon_3_id, m1.ma_mon as m1, m2.ma_mon as m2, m3.ma_mon as m3 
            FROM dm_to_hop th 
            LEFT JOIN dm_mon m1 ON th.mon_1_id = m1.id
            LEFT JOIN dm_mon m2 ON th.mon_2_id = m2.id
            LEFT JOIN dm_mon m3 ON th.mon_3_id = m3.id
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $comboMap = [];
        $comboSubjectsMap = [];
        foreach ($combos as $c) {
            $comboMap[$c['ma_to_hop']] = $c['ma_to_hop'] . ' (' . $c['m1'] . '-' . $c['m2'] . '-' . $c['m3'] . ')';
            $comboSubjectsMap[$c['ma_to_hop']] = [
                'mon_1_id' => $c['mon_1_id'],
                'mon_2_id' => $c['mon_2_id'],
                'mon_3_id' => $c['mon_3_id']
            ];
        }

        // Map subject IDs to their respective ket_qua_hoc_tap database columns
        $subjectsList = $this->db->query("SELECT id, ma_mon FROM dm_mon")->fetchAll(PDO::FETCH_ASSOC);
        $subjectIdToCol = [];
        $aliases = [
            'TOAN' => 'toan', 'TO' => 'toan',
            'VAN' => 'van', 'NGU_VAN' => 'van', 'VA' => 'van',
            'ANH' => 'ngoai_ngu', 'TIENG_ANH' => 'ngoai_ngu', 'NGOAI_NGU' => 'ngoai_ngu', 'TA' => 'ngoai_ngu', 'NN' => 'ngoai_ngu',
            'N1' => 'ngoai_ngu', 'N2' => 'ngoai_ngu', 'N3' => 'ngoai_ngu', 'N4' => 'ngoai_ngu', 'N5' => 'ngoai_ngu', 'N6' => 'ngoai_ngu',
            'LY' => 'ly', 'VAT_LY' => 'ly', 'VAT LI' => 'ly', 'LI' => 'ly',
            'HOA' => 'hoa', 'HOA_HOC' => 'hoa', 'HO' => 'hoa',
            'SINH' => 'sinh', 'SINH_HOC' => 'sinh', 'SI' => 'sinh',
            'SU' => 'su', 'LICH_SU' => 'su',
            'DIA' => 'dia', 'DIA_LY' => 'dia', 'DI' => 'dia',
            'GDCD' => 'gdcd', 'GD' => 'gdcd',
            'GDKT_PL' => 'ktpl', 'KTPL' => 'ktpl', 'GDKTPL' => 'ktpl',
            'CONG_NGHE' => 'cong_nghe', 'CN' => 'cong_nghe',
            'TIN' => 'tin_hoc', 'TIN_HOC' => 'tin_hoc', 'TH' => 'tin_hoc'
        ];
        foreach ($subjectsList as $s) {
            $code = strtoupper(trim($s['ma_mon']));
            if (isset($aliases[$code])) {
                $subjectIdToCol[$s['id']] = 'diem_' . $aliases[$code] . '_cn';
            }
        }

        // Fetch academic records for all candidates in this session to avoid N+1 queries
        $academicRows = $this->db->prepare("
            SELECT * FROM ket_qua_hoc_tap 
            WHERE so_cccd IN (
                SELECT DISTINCT so_cccd FROM nguyen_vong 
                WHERE dot_tuyen_sinh_id = ? AND (trang_thai = 'DaDuyet' OR trang_thai LIKE '%Đã duyệt%')
            )
        ");
        $academicRows->execute([$sessionId]);
        $academicMap = [];
        while ($ar = $academicRows->fetch(PDO::FETCH_ASSOC)) {
            $academicMap[$ar['so_cccd']][$ar['lop']] = $ar;
        }

        // Xây dựng WHERE / ORDER BY / filename theo loại xuất
        $baseWhere = "WHERE nv.dot_tuyen_sinh_id = ? AND (nv.trang_thai = 'DaDuyet' OR nv.trang_thai LIKE '%Đã duyệt%')";
        $extraWhere = '';
        $orderBy    = 'ORDER BY nv.so_cccd, nv.thu_tu_nguyen_vong ASC';
        $params     = [$sessionId];
        $filename   = 'xet_tuyen_loc_ao_' . $sessionId . '.xls';

        switch ($type) {
            case 'admitted':
                $extraWhere = " AND cs.trang_thai_trung_tuyen = TRUE";
                $orderBy    = "ORDER BY nv.ma_nganh ASC, cs.diem_xet_tuyen DESC";
                $filename   = 'danh_sach_trung_tuyen_' . $sessionId . '.xls';
                break;
            case 'failed':
                $extraWhere = " AND nv.so_cccd NOT IN (
                    SELECT DISTINCT nv2.so_cccd
                    FROM nguyen_vong nv2
                    JOIN v_calc_summary cs2 ON nv2.id = cs2.nguyen_vong_id
                    WHERE nv2.dot_tuyen_sinh_id = ? AND cs2.trang_thai_trung_tuyen = TRUE
                )";
                $params[]   = $sessionId; // tham số thứ 2 cho subquery
                $orderBy    = "ORDER BY nv.so_cccd, nv.thu_tu_nguyen_vong ASC";
                $filename   = 'danh_sach_truot_' . $sessionId . '.xls';
                break;
            case 'academic_fail':
                $extraWhere = " AND (cs.chi_tiet_diem->>'threshold_note') ILIKE '%HỌC LỰC%'";
                $orderBy    = "ORDER BY nv.ma_nganh ASC, cs.diem_xet_tuyen DESC";
                $filename   = 'khong_dat_hoc_luc_' . $sessionId . '.xls';
                break;
        }

        $sql = "SELECT nv.so_cccd, ts.ho_va_ten, nv.ma_nganh, nv.thu_tu_nguyen_vong, 
                       cs.diem_mon_1, cs.diem_mon_2, cs.diem_mon_3, cs.diem_xet_tuyen, cs.trang_thai_trung_tuyen,
                       cs.to_hop_toi_uu, cs.phuong_thuc_toi_uu, cs.chi_tiet_diem,
                       ts.ngay_sinh, ts.email, ts.dien_thoai, ts.khu_vuc_uu_tien, ts.doi_tuong_uu_tien, ts.ghi_chu as ts_ghi_chu,
                       nv.diem_uu_tien_goc, nv.diem_uu_tien_qd, nv.ten_nganh, nv.ghi_chu as nv_ghi_chu
                FROM nguyen_vong nv
                JOIN thi_sinh ts ON nv.so_cccd = ts.so_cccd
                LEFT JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                $baseWhere $extraWhere
                $orderBy";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        
        $ptLabels = [
            '100' => 'TS01',
            '200' => 'TS02',
            'TS01' => 'TS01',
            'TS02' => 'TS02',
            'TS03' => 'TS03',
            'TS04' => 'TS04',
            'TS05' => 'TS05'
        ];

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $toHopMax = isset($comboMap[$row['to_hop_toi_uu']]) ? $comboMap[$row['to_hop_toi_uu']] : ($row['to_hop_toi_uu'] ?: '-');
            $ptMax = isset($ptLabels[$row['phuong_thuc_toi_uu']]) ? $ptLabels[$row['phuong_thuc_toi_uu']] : ($row['phuong_thuc_toi_uu'] ?: '-');

            $m1 = $row['diem_mon_1'] !== null ? (float)$row['diem_mon_1'] : 0.0;
            $m2 = $row['diem_mon_2'] !== null ? (float)$row['diem_mon_2'] : 0.0;
            $m3 = $row['diem_mon_3'] !== null ? (float)$row['diem_mon_3'] : 0.0;
            $diemToHop = ($row['diem_mon_1'] !== null || $row['diem_mon_2'] !== null || $row['diem_mon_3'] !== null) ? ($m1 + $m2 + $m3) : 0.0;

            // Get Grade 10, 11, 12 average subject scores
            $combo = $row['to_hop_toi_uu'];
            $cccd = $row['so_cccd'];
            
            $m1_l10 = '-'; $m1_l11 = '-'; $m1_l12 = '-';
            $m2_l10 = '-'; $m2_l11 = '-'; $m2_l12 = '-';
            $m3_l10 = '-'; $m3_l11 = '-'; $m3_l12 = '-';
            
            if ($combo && isset($comboSubjectsMap[$combo])) {
                $subIds = $comboSubjectsMap[$combo];
                $col1 = $subjectIdToCol[$subIds['mon_1_id']] ?? null;
                $col2 = $subjectIdToCol[$subIds['mon_2_id']] ?? null;
                $col3 = $subjectIdToCol[$subIds['mon_3_id']] ?? null;
                
                $getScoreHelper = function($record, $colName) {
                    if (!$colName) return null;
                    if (isset($record[$colName]) && $record[$colName] !== '') {
                        return (float)$record[$colName];
                    }
                    // GDCD <-> GDKTPL Cross fallback
                    if ($colName === 'diem_gdcd_cn' && isset($record['diem_ktpl_cn']) && $record['diem_ktpl_cn'] !== '') {
                        return (float)$record['diem_ktpl_cn'];
                    }
                    if ($colName === 'diem_ktpl_cn' && isset($record['diem_gdcd_cn']) && $record['diem_gdcd_cn'] !== '') {
                        return (float)$record['diem_gdcd_cn'];
                    }
                    return null;
                };
                
                foreach ([10, 11, 12] as $g) {
                    if (isset($academicMap[$cccd][$g])) {
                        $record = $academicMap[$cccd][$g];
                        
                        $val1 = $getScoreHelper($record, $col1);
                        if ($val1 !== null) ${"m1_l$g"} = $val1;
                        
                        $val2 = $getScoreHelper($record, $col2);
                        if ($val2 !== null) ${"m2_l$g"} = $val2;
                        
                        $val3 = $getScoreHelper($record, $col3);
                        if ($val3 !== null) ${"m3_l$g"} = $val3;
                    }
                }
            }

            if ($type === 'admitted') {
                $formattedNgaySinh = '';
                if (!empty($row['ngay_sinh'])) {
                    $formattedNgaySinh = date('d/m/Y', strtotime($row['ngay_sinh']));
                }
                
                $sbd = '';
                if (!empty($row['so_cccd'])) {
                    $sbdStmt = $this->db->prepare("SELECT sbd FROM diem_nang_khieu WHERE so_cccd = ? LIMIT 1");
                    $sbdStmt->execute([$row['so_cccd']]);
                    $sbd = $sbdStmt->fetchColumn() ?: '';
                }

                $detail = null;
                if (!empty($row['chi_tiet_diem'])) {
                    $detail = json_decode($row['chi_tiet_diem'], true);
                }
                $diemUt = isset($detail['priority_raw']) ? (float)$detail['priority_raw'] : ($row['diem_uu_tien_goc'] !== null ? (float)$row['diem_uu_tien_goc'] : 0.0);
                $diemUtQd = isset($detail['priority_converted']) ? (float)$detail['priority_converted'] : ($row['diem_uu_tien_qd'] !== null ? (float)$row['diem_uu_tien_qd'] : 0.0);
                $ghiChu = $row['nv_ghi_chu'] ?: ($row['ts_ghi_chu'] ?: '');

                $data[] = [
                    'CCCD'       => $row['so_cccd'],
                    'HOTEN'      => $row['ho_va_ten'],
                    'NGAYSINH'   => $formattedNgaySinh,
                    'SBD'        => $sbd,
                    'KV'         => $row['khu_vuc_uu_tien'] ?: '',
                    'DOITUONG'   => $row['doi_tuong_uu_tien'] ?: '',
                    'TOHOP'      => $row['to_hop_toi_uu'] ?: '',
                    'DM1'        => $m1,
                    'DM2'        => $m2,
                    'DM3'        => $m3,
                    'DIEMTOHOP'  => $diemToHop,
                    'DIEMUT'     => $diemUt,
                    'UTQ'        => $diemUtQd,
                    'DIEMXT'     => $row['diem_xet_tuyen'] !== null ? (float)$row['diem_xet_tuyen'] : 0.0,
                    'MANGANH'    => $row['ma_nganh'],
                    'NGANH'      => $row['ten_nganh'] ?: '',
                    'SOTK'       => '',
                    'NGANHANG'   => '',
                    'SOTIEN'     => 0,
                    'NOIDUNG'    => '',
                    'EMAIL'      => $row['email'] ?: '',
                    'SDT'        => $row['dien_thoai'] ?: '',
                    'GHICHU'     => $ghiChu,
                    'PHUONGTHUC' => $ptMax,
                    'M1 L10'     => $m1_l10,
                    'M1 L11'     => $m1_l11,
                    'M1 L12'     => $m1_l12,
                    'M2 L10'     => $m2_l10,
                    'M2 L11'     => $m2_l11,
                    'M2 L12'     => $m2_l12,
                    'M3 L10'     => $m3_l10,
                    'M3 L11'     => $m3_l11,
                    'M3 L12'     => $m3_l12
                ];
                continue;
            }

            $diemToHopText = ($row['diem_mon_1'] !== null || $row['diem_mon_2'] !== null || $row['diem_mon_3'] !== null) ? $diemToHop : '-';

            $detail = null;
            if (!empty($row['chi_tiet_diem'])) {
                $detail = json_decode($row['chi_tiet_diem'], true);
            }

            $diemQuyDoi = isset($detail['total_raw']) ? (float)$detail['total_raw'] : '-';
            $diemUtQd = isset($detail['priority_converted']) ? (float)$detail['priority_converted'] : '-';
            $thresholdNote = isset($detail['threshold_note']) ? (string)$detail['threshold_note'] : '';

            $dkHocLuc = 'Đạt';
            if (mb_strpos(mb_strtoupper($thresholdNote, 'UTF-8'), 'HỌC LỰC') !== false) {
                $dkHocLuc = 'K.Đạt';
            }

            $dkNguong = 'Đạt';
            if (mb_strpos(mb_strtoupper($thresholdNote, 'UTF-8'), 'NGƯỠNG') !== false) {
                $dkNguong = 'K.Đạt';
            }

            $dataRow = [
                'CCCD'              => $row['so_cccd'],
                'Họ tên'            => $row['ho_va_ten'],
                'Ngành'             => $row['ma_nganh'],
                'NV'                => $row['thu_tu_nguyen_vong'],
                'Tổ hợp max'        => $toHopMax,
                'PT max'            => $ptMax,
                'Điểm M1'           => $row['diem_mon_1'] !== null ? (float)$row['diem_mon_1'] : '-',
                'Điểm M2'           => $row['diem_mon_2'] !== null ? (float)$row['diem_mon_2'] : '-',
                'Điểm M3'           => $row['diem_mon_3'] !== null ? (float)$row['diem_mon_3'] : '-',
                'Điểm tổ hợp'       => $diemToHopText,
                'Điểm quy đổi'      => $diemQuyDoi,
                'Điểm UT QĐ'        => $diemUtQd,
                'Điểm xét tuyển'    => $row['diem_xet_tuyen'] !== null ? (float)$row['diem_xet_tuyen'] : '-',
                'ĐK học lực'        => $dkHocLuc,
                'ĐK Ngưỡng'         => $dkNguong,
                'Kết quả xét tuyển' => ($row['trang_thai_trung_tuyen'] == 1) ? 'Trúng Tuyển' : 'Không đạt',
            ];

            // Thêm cột Lý do không đỗ cho danh sách trượt
            if ($type === 'failed') {
                $reasons = [];
                $upperNote = mb_strtoupper($thresholdNote, 'UTF-8');
                if (mb_strpos($upperNote, 'HỌC LỰC') !== false) {
                    $reasons[] = 'K.đạt ĐK Học lực';
                }
                if (mb_strpos($upperNote, 'NGƯỠNG') !== false) {
                    $reasons[] = 'K.đạt Ngưỡng đầu vào';
                }
                if (empty($reasons)) {
                    if ($row['diem_xet_tuyen'] !== null && $row['diem_xet_tuyen'] > 0) {
                        $reasons[] = 'Điểm xét tuyển không đủ';
                    } else {
                        $reasons[] = 'Chưa có điểm hoặc thiếu dữ liệu';
                    }
                }
                $dataRow['Lý do không đỗ'] = implode('; ', $reasons);
            }

            // Điểm thành phần theo lớp - luôn ở cuối
            $dataRow['M1 L10'] = $m1_l10;
            $dataRow['M1 L11'] = $m1_l11;
            $dataRow['M1 L12'] = $m1_l12;
            $dataRow['M2 L10'] = $m2_l10;
            $dataRow['M2 L11'] = $m2_l11;
            $dataRow['M2 L12'] = $m2_l12;
            $dataRow['M3 L10'] = $m3_l10;
            $dataRow['M3 L11'] = $m3_l11;
            $dataRow['M3 L12'] = $m3_l12;

            $data[] = $dataRow;
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, $filename);
    }
}
