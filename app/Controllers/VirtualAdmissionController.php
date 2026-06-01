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
        if (!$sessionId) {
            die("Chưa chọn đợt xét tuyển.");
        }

        // Fetch combinations to map to_hop_toi_uu to rich label
        $combos = $this->db->query("
            SELECT th.ma_to_hop, m1.ma_mon as m1, m2.ma_mon as m2, m3.ma_mon as m3 
            FROM dm_to_hop th 
            LEFT JOIN dm_mon m1 ON th.mon_1_id = m1.id
            LEFT JOIN dm_mon m2 ON th.mon_2_id = m2.id
            LEFT JOIN dm_mon m3 ON th.mon_3_id = m3.id
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $comboMap = [];
        foreach ($combos as $c) {
            $comboMap[$c['ma_to_hop']] = $c['ma_to_hop'] . ' (' . $c['m1'] . '-' . $c['m2'] . '-' . $c['m3'] . ')';
        }

        $sql = "SELECT nv.so_cccd, ts.ho_va_ten, nv.ma_nganh, nv.thu_tu_nguyen_vong, 
                       cs.diem_mon_1, cs.diem_mon_2, cs.diem_mon_3, cs.diem_xet_tuyen, cs.trang_thai_trung_tuyen,
                       cs.to_hop_toi_uu, cs.phuong_thuc_toi_uu, cs.chi_tiet_diem
                FROM nguyen_vong nv
                JOIN thi_sinh ts ON nv.so_cccd = ts.so_cccd
                LEFT JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                WHERE nv.dot_tuyen_sinh_id = ? AND (nv.trang_thai = 'DaDuyet' OR nv.trang_thai LIKE '%Đã duyệt%')
                ORDER BY nv.so_cccd, nv.thu_tu_nguyen_vong ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        
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
            $diemToHop = ($row['diem_mon_1'] !== null || $row['diem_mon_2'] !== null || $row['diem_mon_3'] !== null) ? ($m1 + $m2 + $m3) : '-';

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

            $data[] = [
                'CCCD'              => "\t" . $row['so_cccd'], // Prefix with tab for ExportService detection
                'Họ tên'            => $row['ho_va_ten'],
                'Ngành'             => $row['ma_nganh'],
                'NV'                => $row['thu_tu_nguyen_vong'],
                'Tổ hợp max'        => $toHopMax,
                'PT max'            => $ptMax,
                'Điểm M1'           => $row['diem_mon_1'] !== null ? (float)$row['diem_mon_1'] : '-',
                'Điểm M2'           => $row['diem_mon_2'] !== null ? (float)$row['diem_mon_2'] : '-',
                'Điểm M3'           => $row['diem_mon_3'] !== null ? (float)$row['diem_mon_3'] : '-',
                'Điểm tổ hợp'       => $diemToHop,
                'Điểm quy đổi'      => $diemQuyDoi,
                'Điểm UT QĐ'        => $diemUtQd,
                'Điểm xét tuyển'    => $row['diem_xet_tuyen'] !== null ? (float)$row['diem_xet_tuyen'] : '-',
                'ĐK học lực'        => $dkHocLuc,
                'ĐK Ngưỡng'         => $dkNguong,
                'Kết quả xét tuyển' => ($row['trang_thai_trung_tuyen'] == 1) ? 'Trúng Tuyển' : 'Không đạt'
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'xet_tuyen_loc_ao_' . $sessionId . '.xls');
    }
}
