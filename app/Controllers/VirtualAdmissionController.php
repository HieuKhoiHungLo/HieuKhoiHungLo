<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Services\ScoreCalculationService;
use App\Services\VirtualFilterService;
use App\Services\BGDResultImportService;
use PDO;

class VirtualAdmissionController extends Controller {
    protected $masterData;
    protected $scoreService;
    protected $filterService;
    protected $bgdImportService;
    protected $db;

    public function __construct() {
        $this->masterData = new MasterData();
        $this->scoreService = new ScoreCalculationService();
        $this->filterService = new VirtualFilterService();
        $this->bgdImportService = new BGDResultImportService();
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

    public function overviewVirtualFilter() {
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
            'title' => 'Tổng quan Lọc ảo',
            'sessions' => $sessions,
            'years' => $years,
            'combinations' => $combinations,
            'needsDataTables' => true,
            'isReadOnly' => true
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
                         LEFT JOIN ket_qua_loc_ao_bo_gd bgd 
                             ON bgd.so_cccd = nv.so_cccd 
                             AND bgd.dot_tuyen_sinh_id = nv.dot_tuyen_sinh_id 
                         WHERE nv.dot_tuyen_sinh_id = ?
                         AND (nv.trang_thai IN ('DaDuyet', 'Trúng tuyển', 'Không đạt', 'Đủ điều kiện', 'approved') OR nv.trang_thai LIKE '%Đã duyệt%')";
            
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

            // 3. Đếm tổng số lượng bản ghi (trước khi tìm kiếm) - Tối ưu bằng cách chỉ join thi_sinh và nguyen_vong, loại bỏ các bảng LEFT JOIN không ảnh hưởng đến số lượng dòng
            $stmtTotal = $this->db->prepare("
                SELECT COUNT(*) 
                FROM thi_sinh ts 
                JOIN nguyen_vong nv ON ts.so_cccd = nv.so_cccd 
                WHERE nv.dot_tuyen_sinh_id = ? 
                  AND (nv.trang_thai IN ('DaDuyet', 'Trúng tuyển', 'Không đạt', 'Đủ điều kiện', 'approved') OR nv.trang_thai LIKE '%Đã duyệt%')
            ");
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
            $stmtC = $this->db->prepare("SELECT COUNT(DISTINCT nv.so_cccd) FROM nguyen_vong nv WHERE nv.dot_tuyen_sinh_id = ? AND (nv.trang_thai IN ('DaDuyet', 'Trúng tuyển', 'Không đạt', 'Đủ điều kiện', 'approved') OR nv.trang_thai LIKE '%Đã duyệt%')");
            $stmtC->execute([$sessionId]);
            $candidateCount = $stmtC->fetchColumn() ?: 0;

            // 5b. Tổng hồ sơ đã duyệt từ ho_so_xet_tuyen (bao gồm cả TS chưa đăng ký NV)
            $stmtHoso = $this->db->prepare("SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = ? AND (trang_thai IN ('Đã duyệt', 'approved', 'DaDuyet') OR trang_thai LIKE '%Đã duyệt%')");
            $stmtHoso->execute([$sessionId]);
            $totalApprovedHoso = $stmtHoso->fetchColumn() ?: 0;
            $noAspirationCount = max(0, $totalApprovedHoso - $candidateCount);

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
                    cs.ket_qua_bo_gd, cs.bi_loai_truong_khac, cs.ma_truong_trung_tuyen_bo,
                    bgd.ttnv_do as ttnv_do_bo, bgd.ma_truong_trung_tuyen as ma_truong_trung_tuyen_bgd,
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
                        $filteredChiTiet = [
                            'all_combinations' => $p['all_combinations'] ?? [],
                            'total_raw' => $p['total_raw'] ?? 0,
                            'priority_raw' => $p['priority_raw'] ?? 0,
                            'priority_converted' => $p['priority_converted'] ?? 0,
                            'threshold_note' => $p['threshold_note'] ?? ''
                        ];
                        // Truyền các môn học (có chứa base_scaled) xuống UI
                        $subjects = [];
                        foreach ($p as $k => $v) {
                            if (is_array($v) && isset($v['base_scaled'])) {
                                $v['mon_id'] = $k;
                                $subjects[] = $v;
                            }
                        }
                        $filteredChiTiet['subjects'] = $subjects;
                        
                        $row['chi_tiet_diem'] = json_encode($filteredChiTiet, JSON_UNESCAPED_UNICODE);
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
                'aspiration_count' => $recordsTotal,
                'total_approved_hoso' => intval($totalApprovedHoso),
                'no_aspiration_count' => intval($noAspirationCount)
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
        set_time_limit(600); // Cho phép tối đa 10 phút - an toàn cho dữ liệu lớn

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
                WHERE dot_tuyen_sinh_id = ? AND (trang_thai IN ('DaDuyet', 'Trúng tuyển', 'Không đạt', 'Đủ điều kiện') OR trang_thai LIKE '%Đã duyệt%')
            )
        ");
        $academicRows->execute([$sessionId]);
        $academicMap = [];
        while ($ar = $academicRows->fetch(PDO::FETCH_ASSOC)) {
            $academicMap[$ar['so_cccd']][$ar['lop']] = $ar;
        }

        // Pre-load SBD (số báo danh) for all candidates - tránh N+1 query
        $sbdStmt = $this->db->prepare("
            SELECT DISTINCT ON (so_cccd) so_cccd, sbd
            FROM diem_nang_khieu
            WHERE so_cccd IN (
                SELECT DISTINCT so_cccd FROM nguyen_vong
                WHERE dot_tuyen_sinh_id = ?
            )
            ORDER BY so_cccd, id ASC
        ");
        $sbdStmt->execute([$sessionId]);
        $sbdMap = [];
        while ($sbdRow = $sbdStmt->fetch(PDO::FETCH_ASSOC)) {
            $sbdMap[$sbdRow['so_cccd']] = $sbdRow['sbd'] ?? '';
        }

        // Pre-load raw certificates from chung_chi_thi_sinh
        $certRows = $this->db->prepare("
            SELECT so_cccd, loai_chung_chi, diem_chung_chi FROM chung_chi_thi_sinh 
            WHERE so_cccd IN (
                SELECT DISTINCT so_cccd FROM nguyen_vong 
                WHERE dot_tuyen_sinh_id = ? AND (trang_thai IN ('DaDuyet', 'Trúng tuyển', 'Không đạt', 'Đủ điều kiện') OR trang_thai LIKE '%Đã duyệt%')
            )
        ");
        $certRows->execute([$sessionId]);
        $certMap = [];
        while ($cr = $certRows->fetch(PDO::FETCH_ASSOC)) {
            $cccd = $cr['so_cccd'];
            $val = trim(($cr['loai_chung_chi'] ?? '') . ' ' . ($cr['diem_chung_chi'] ?? ''));
            if ($val !== '') {
                $certMap[$cccd][] = $val;
            }
        }

        // Pre-load converted/admin-imported certificates from diem_chung_chi
        $diemCertRows = $this->db->prepare("
            SELECT so_cccd, ghi_chu, diem FROM diem_chung_chi 
            WHERE dot_tuyen_sinh_id = ?
        ");
        $diemCertRows->execute([$sessionId]);
        $diemCertMap = [];
        while ($dcr = $diemCertRows->fetch(PDO::FETCH_ASSOC)) {
            $cccd = $dcr['so_cccd'];
            $val = $dcr['ghi_chu'] ? str_replace('_', ' ', $dcr['ghi_chu']) : $dcr['diem'];
            if ($val !== '') {
                $diemCertMap[$cccd][] = $val;
            }
        }

        // Xây dựng WHERE / ORDER BY / filename theo loại xuất
        $baseWhere = "WHERE nv.dot_tuyen_sinh_id = ? AND (nv.trang_thai IN ('DaDuyet', 'Trúng tuyển', 'Không đạt', 'Đủ điều kiện') OR nv.trang_thai LIKE '%Đã duyệt%')";
        $extraWhere = '';
        $orderBy    = 'ORDER BY nv.so_cccd, nv.thu_tu_nguyen_vong ASC';
        $params     = [$sessionId];
        $filename   = 'xet_tuyen_loc_ao_' . $sessionId . '.xls';

        switch ($type) {
            case 'admitted':
                // DS trúng tuyển nội bộ (TRƯỚC khi lọc Bộ GD&ĐT - để đối chiếu)
                $extraWhere = " AND cs.trang_thai_trung_tuyen = TRUE";
                $orderBy    = "ORDER BY nv.ma_nganh ASC, cs.diem_xet_tuyen DESC";
                $filename   = 'danh_sach_trung_tuyen_noi_bo_' . $sessionId . '.xls';
                break;
            case 'admitted_final':
                // DS trúng tuyển CHÍNH THỨC (đã loại thí sinh trúng trường khác theo Bộ GD&ĐT)
                $extraWhere = " AND cs.trang_thai_trung_tuyen = TRUE
                               AND (cs.bi_loai_truong_khac IS NULL OR cs.bi_loai_truong_khac = FALSE)";
                $orderBy    = "ORDER BY nv.ma_nganh ASC, cs.diem_xet_tuyen DESC";
                $filename   = 'danh_sach_trung_tuyen_chinh_thuc_' . $sessionId . '.xls';
                break;
            case 'eliminated_by_bgd':
                // DS thí sinh bị loại vì đã trúng tuyển trường khác (theo kết quả Bộ GD&ĐT)
                $extraWhere = " AND cs.trang_thai_trung_tuyen = TRUE
                               AND cs.bi_loai_truong_khac = TRUE";
                $orderBy    = "ORDER BY nv.ma_nganh ASC, cs.diem_xet_tuyen DESC";
                $filename   = 'danh_sach_bi_loai_truong_khac_' . $sessionId . '.xls';
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
                       ts.so_bao_danh, ts.gioi_tinh,
                       p.ten_tinh as ten_tinh_tt,
                       xa.ten_xa as ten_xa_tt,
                       truong.ten_truong as ten_truong_thpt,
                       nv.diem_uu_tien_goc, nv.diem_uu_tien_qd, nv.ten_nganh, nv.ghi_chu as nv_ghi_chu
                FROM nguyen_vong nv
                JOIN thi_sinh ts ON nv.so_cccd = ts.so_cccd
                LEFT JOIN dm_tinh p ON COALESCE(ts.ma_tinh_thuong_tru, ts.ma_tinh_ho_khau) = p.ma_tinh
                LEFT JOIN dm_xa xa ON ts.ma_xa_thuong_tru = xa.ma_xa
                LEFT JOIN dm_truong_thpt truong ON ts.ma_truong_lop_12 = truong.ma_truong AND truong.is_active = TRUE
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
            $sbd = (!empty($row['so_bao_danh'])) ? $row['so_bao_danh'] : ($sbdMap[$row['so_cccd']] ?? '');
            $toHopMax = isset($comboMap[$row['to_hop_toi_uu']]) ? $comboMap[$row['to_hop_toi_uu']] : ($row['to_hop_toi_uu'] ?: '-');
            $ptMax = isset($ptLabels[$row['phuong_thuc_toi_uu']]) ? $ptLabels[$row['phuong_thuc_toi_uu']] : ($row['phuong_thuc_toi_uu'] ?: '-');

            // Giải mã chi_tiet_diem để lấy điểm môn đã quy đổi (×0.95)
            $chiTietRaw = [];
            if (!empty($row['chi_tiet_diem'])) {
                $chiTietRaw = json_decode($row['chi_tiet_diem'], true) ?: [];
            }
            // Đọc điểm 3 môn đã quy đổi (x0.95) từ JSON mới có key mon_1/mon_2/mon_3
            // 'final' = điểm thực sự được dùng tính tổ hợp: max(học bạ×0.95, chứng chỉ×0.95)
            // Với TS02: final = base_scaled (học bạ×0.95)
            // Với TS03: môn ngoại ngữ dùng chứng chỉ nên final = cert×0.95 (cao hơn học bạ)
            $m1Score = $chiTietRaw['mon_1']['final'] ?? ($chiTietRaw['mon_1']['base_scaled'] ?? null);
            $m2Score = $chiTietRaw['mon_2']['final'] ?? ($chiTietRaw['mon_2']['base_scaled'] ?? null);
            $m3Score = $chiTietRaw['mon_3']['final'] ?? ($chiTietRaw['mon_3']['base_scaled'] ?? null);
            // Fallback: nếu JSON cũ không có mon_x key, dùng thứ tự iteration
            if ($m1Score === null) {
                $monEntries = [];
                foreach ($chiTietRaw as $k => $v) {
                    if (is_array($v) && isset($v['base_scaled'])) {
                        $monEntries[] = $v;
                    }
                }
                $m1Score = $monEntries[0]['base_scaled'] ?? null;
                $m2Score = $monEntries[1]['base_scaled'] ?? null;
                $m3Score = $monEntries[2]['base_scaled'] ?? null;
            }
            $m1 = $m1Score !== null ? round((float)$m1Score, 3) : 0.0;
            $m2 = $m2Score !== null ? round((float)$m2Score, 3) : 0.0;
            $m3 = $m3Score !== null ? round((float)$m3Score, 3) : 0.0;
            $diemToHop = $m1 + $m2 + $m3;


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
                
                // Ghi đè bằng điểm chứng chỉ gốc (chưa nhân hệ số) cho cả 3 năm nếu môn đó lấy từ chứng chỉ
                if (isset($chiTietRaw['mon_1']['source']) && $chiTietRaw['mon_1']['source'] === 'CERT') {
                    $m1_l10 = $m1_l11 = $m1_l12 = round((float)($chiTietRaw['diem_mon_1'] ?? 0), 3);
                }
                if (isset($chiTietRaw['mon_2']['source']) && $chiTietRaw['mon_2']['source'] === 'CERT') {
                    $m2_l10 = $m2_l11 = $m2_l12 = round((float)($chiTietRaw['diem_mon_2'] ?? 0), 3);
                }
                if (isset($chiTietRaw['mon_3']['source']) && $chiTietRaw['mon_3']['source'] === 'CERT') {
                    $m3_l10 = $m3_l11 = $m3_l12 = round((float)($chiTietRaw['diem_mon_3'] ?? 0), 3);
                }
            }

            $isTS01orTS04 = in_array($ptMax, ['TS01', 'TS04']) || in_array($row['phuong_thuc_toi_uu'], ['TS01', 'TS04', '100']);
            if ($isTS01orTS04) {
                $m1_l10 = $m1_l11 = $m1_l12 = '';
                $m2_l10 = $m2_l11 = $m2_l12 = '';
                $m3_l10 = $m3_l11 = $m3_l12 = '';
            }

            $diemChungChiVal = '';
            if (($ptMax === 'TS03') || ($row['phuong_thuc_toi_uu'] === 'TS03')) {
                if (!empty($certMap[$cccd])) {
                    $diemChungChiVal = implode(', ', array_unique($certMap[$cccd]));
                } elseif (!empty($diemCertMap[$cccd])) {
                    $diemChungChiVal = implode(', ', array_unique($diemCertMap[$cccd]));
                } else {
                    if (isset($chiTietRaw['diem_mon_1']) && isset($chiTietRaw['mon_1']['source']) && $chiTietRaw['mon_1']['source'] === 'CERT') {
                        $diemChungChiVal = $chiTietRaw['diem_mon_1'];
                    } elseif (isset($chiTietRaw['diem_mon_2']) && isset($chiTietRaw['mon_2']['source']) && $chiTietRaw['mon_2']['source'] === 'CERT') {
                        $diemChungChiVal = $chiTietRaw['diem_mon_2'];
                    } elseif (isset($chiTietRaw['diem_mon_3']) && isset($chiTietRaw['mon_3']['source']) && $chiTietRaw['mon_3']['source'] === 'CERT') {
                        $diemChungChiVal = $chiTietRaw['diem_mon_3'];
                    }
                }
            }

            if ($type === 'admitted') {
                $formattedNgaySinh = '';
                if (!empty($row['ngay_sinh'])) {
                    $formattedNgaySinh = date('d/m/Y', strtotime($row['ngay_sinh']));
                }
                
                $detail = $chiTietRaw;
                $diemUt = isset($detail['priority_raw']) ? (float)$detail['priority_raw'] : ($row['diem_uu_tien_goc'] !== null ? (float)$row['diem_uu_tien_goc'] : 0.0);
                $diemUtQd = isset($detail['priority_converted']) ? (float)$detail['priority_converted'] : ($row['diem_uu_tien_qd'] !== null ? (float)$row['diem_uu_tien_qd'] : 0.0);
                $ghiChu = $row['nv_ghi_chu'] ?: ($row['ts_ghi_chu'] ?: '');

                // Dùng tên cột chuẩn để ExportService tự động format thành Text
                $data[] = [
                    'CCCD'       => $row['so_cccd'],
                    'HOTEN'      => $row['ho_va_ten'],
                    'GIOITINH'   => $row['gioi_tinh'],
                    'NGAYSINH'   => $formattedNgaySinh,
                    'SBD'        => $sbd,
                    'TINH'       => $row['ten_tinh_tt'] ?: '',
                    'XA_PHUONG'  => $row['ten_xa_tt'] ?: '',
                    'TRUONG_THPT'=> $row['ten_truong_thpt'] ?: '',
                    'KV'         => $row['khu_vuc_uu_tien'] ?: '',
                    'DOITUONG'   => $row['doi_tuong_uu_tien'] ?: '',
                    'TOHOP'      => $toHopMax,
                    'DM1'        => $m1 > 0 ? $m1 : 0.0,
                    'DM2'        => $m2 > 0 ? $m2 : 0.0,
                    'DM3'        => $m3 > 0 ? $m3 : 0.0,
                    'DIEMTOHOP'  => $diemToHop > 0 ? $diemToHop : 0.0,
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
                    'M3 L12'     => $m3_l12,
                    'Điểm chứng chỉ' => $diemChungChiVal,
                    'Thứ tự nguyện vọng' => $row['thu_tu_nguyen_vong']
                ];
                continue;
            }

            $diemToHopText = $diemToHop > 0 ? $diemToHop : '-';

            $detail = $chiTietRaw;
            $diemUtQd = isset($detail['priority_converted']) ? (float)$detail['priority_converted'] : '-';
            $diemUt = isset($detail['priority_raw']) ? (float)$detail['priority_raw'] : '-';
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
                'SBD'               => $sbd,
                'Họ tên'            => $row['ho_va_ten'],
                'Ngành'             => $row['ma_nganh'],
                'Khu vực'           => $row['khu_vuc_uu_tien'] ?: '',
                'Đối tượng'         => $row['doi_tuong_uu_tien'] ?: '',
                'NV'                => $row['thu_tu_nguyen_vong'],
                'Tổ hợp max'        => $toHopMax,
                'PT max'            => $ptMax,
                'Điểm M1 (×0.95)'   => $m1 > 0 ? $m1 : '-',
                'Điểm M2 (×0.95)'   => $m2 > 0 ? $m2 : '-',
                'Điểm M3 (×0.95)'   => $m3 > 0 ? $m3 : '-',
                'Điểm tổ hợp'       => $diemToHopText,
                'Điểm UT gốc'       => $diemUt,
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
            $dataRow['Điểm chứng chỉ'] = $diemChungChiVal;
            $dataRow['Thứ tự nguyện vọng'] = $row['thu_tu_nguyen_vong'];

            $data[] = $dataRow;
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, $filename);
    }

    public function exportAdmittedFinal() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) die("Chưa chọn đợt xét tuyển.");
        $this->doExportWithFilter($sessionId, 'admitted_final');
    }

    public function exportEliminatedByBGD() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) die("Chưa chọn đợt xét tuyển.");
        $this->doExportWithFilter($sessionId, 'eliminated_by_bgd');
    }

    /**
     * POST /admin/api/vf/import-bgd
     * Nhận file Excel kết quả lọc ảo từ Bộ GD&ĐT và xử lý.
     */
    public function importBGDResult() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }

        $sessionId = intval($_POST['session_id'] ?? 0);
        if (!$sessionId) {
            $this->json(['success' => false, 'message' => 'Chưa chọn đợt tuyển sinh.']);
            return;
        }

        if (empty($_FILES['bgd_file']) || $_FILES['bgd_file']['error'] !== UPLOAD_ERR_OK) {
            $errorCode = $_FILES['bgd_file']['error'] ?? -1;
            $this->json(['success' => false, 'message' => 'Lỗi upload file (code: ' . $errorCode . '). Vui lòng thử lại.']);
            return;
        }

        $file = $_FILES['bgd_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            $this->json(['success' => false, 'message' => 'Chỉ chấp nhận file .xlsx hoặc .xls']);
            return;
        }

        // Lấy tên admin đang đăng nhập
        $adminName = $_SESSION['admin_name'] ?? $_SESSION['username'] ?? 'admin';

        // Import file lớn (~5000 dòng + remote DB) cần nhiều thời gian hơn default 120s
        set_time_limit(600);
        ini_set('memory_limit', '256M');

        try {
            $result = $this->bgdImportService->importFromFile(
                $file['tmp_name'],
                $sessionId,
                $file['name'],   // tên file gốc để lấy đúng extension (.xls / .xlsx)
                $adminName
            );

            if (isset($result['report'])) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['last_bgd_import_report'] = $result['report'];
                unset($result['report']);
                $result['report_ready'] = true;
            }

            $this->json($result);
        } catch (\Throwable $e) {
            error_log('importBGDResult Error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Lỗi xử lý: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /admin/api/vf/download-bgd-report
     * Xuất và tải xuống báo cáo import lọc ảo liên trường Bộ GD&ĐT (Thành công/Thất bại có lý do).
     */
    public function downloadImportReport() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $report = $_SESSION['last_bgd_import_report'] ?? [];
        if (empty($report)) {
            die("Không tìm thấy dữ liệu báo cáo import gần nhất. Vui lòng import file trước.");
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($report, 'bao_cao_ket_qua_import_loc_ao_bo.xls');
    }

    /**
     * GET /admin/api/vf/bgd-status?session_id=X
     * Trả về trạng thái import BGD hiện tại của đợt.
     */
    public function getBGDImportStatus() {
        $sessionId = intval($_GET['session_id'] ?? 0);
        if (!$sessionId) {
            $this->json(['success' => false, 'message' => 'Missing session_id']);
            return;
        }

        try {
            $status = $this->bgdImportService->getImportStatus($sessionId);
            $this->json(array_merge(['success' => true], $status));
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Xuất Excel thống kê ngành trúng tuyển dự kiến & sau lọc ảo Bộ GD&ĐT
     */
    public function exportStats() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) die("Chưa chọn đợt xét tuyển.");
        try {
            // Kiểm tra xem đã có dữ liệu lọc ảo của Bộ GD&ĐT hay chưa để xuất Excel tương ứng
            $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM ket_qua_loc_ao_bo_gd WHERE dot_tuyen_sinh_id = ?");
            $stmtCheck->execute([$sessionId]);
            $hasBgd = ((int)$stmtCheck->fetchColumn()) > 0;

            $doBoExpr = $hasBgd 
                ? "COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE AND cs.ket_qua_bo_gd = 'Đỗ' THEN 1 END)" 
                : "COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE THEN 1 END)";

            $majorStatsSql = "SELECT n.ma_nganh, n.ten_nganh, n.chi_tieu,
                                COALESCE(ab.diem_chuan, 0) as diem_chuan,
                                COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE THEN 1 END) as so_trung_tuyen,
                                COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE AND nv.thu_tu_nguyen_vong = 1 THEN 1 END) as nv1_admit,
                                $doBoExpr as so_luong_do_bo,
                                MAX(CASE WHEN cs.trang_thai_trung_tuyen = TRUE THEN cs.diem_xet_tuyen END) as diem_cao_nhat,
                                MIN(CASE WHEN cs.trang_thai_trung_tuyen = TRUE THEN cs.diem_xet_tuyen END) as diem_thap_nhat
                              FROM public.dm_nganh n
                              LEFT JOIN public.admission_benchmarks ab ON n.ma_nganh = ab.ma_nganh AND ab.session_id = ?
                              LEFT JOIN public.nguyen_vong nv ON n.ma_nganh = nv.ma_nganh AND nv.dot_tuyen_sinh_id = ?
                              LEFT JOIN public.v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                              GROUP BY n.ma_nganh, n.ten_nganh, n.chi_tieu, ab.diem_chuan
                              ORDER BY n.ma_nganh";
            $stmt = $this->db->prepare($majorStatsSql);
            $stmt->execute([$sessionId, $sessionId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            $totalChiTieu = 0;
            $totalTrungTuyen = 0;
            $totalNv1Admit = 0;
            $totalDoBo = 0;

            foreach ($rows as $row) {
                $ct = intval($row['chi_tieu']);
                $stt = intval($row['so_trung_tuyen']);
                $db = intval($row['so_luong_do_bo']);

                $totalChiTieu += $ct;
                $totalTrungTuyen += $stt;
                $totalNv1Admit += intval($row['nv1_admit']);
                $totalDoBo += $db;

                $pct = $ct > 0 ? round(($stt / $ct) * 100, 1) . '%' : '0%';
                $pctBo = $ct > 0 ? round(($db / $ct) * 100, 1) . '%' : '0%';

                $diemThap = $row['diem_thap_nhat'] && floatval($row['diem_thap_nhat']) > 0 ? number_format(floatval($row['diem_thap_nhat']), 2) : '-';
                $diemCao = $row['diem_cao_nhat'] && floatval($row['diem_cao_nhat']) > 0 ? number_format(floatval($row['diem_cao_nhat']), 2) : '-';
                $mucDiem = ($diemThap !== '-') ? $diemThap . ' - ' . $diemCao : '-';

                $data[] = [
                    'Mã ngành' => $row['ma_nganh'],
                    'Tên ngành' => $row['ten_nganh'],
                    'Chỉ tiêu' => $ct,
                    'Điểm chuẩn' => floatval($row['diem_chuan']) > 0 ? floatval($row['diem_chuan']) : '-',
                    'Dự kiến - Tổng đỗ' => $stt,
                    'Dự kiến - Đỗ NV1' => intval($row['nv1_admit']),
                    'Dự kiến - Tiến độ (%)' => $pct,
                    'Đỗ Bộ GD&ĐT - Tổng đỗ' => $db,
                    'Đỗ Bộ GD&ĐT - Tiến độ (%)' => $pctBo,
                    'Mức điểm (Thấp - Cao)' => $mucDiem
                ];
            }

            // Thêm dòng tổng cộng ở cuối
            $pctTotal = $totalChiTieu > 0 ? round(($totalTrungTuyen / $totalChiTieu) * 100, 1) . '%' : '0%';
            $pctBoTotal = $totalChiTieu > 0 ? round(($totalDoBo / $totalChiTieu) * 100, 1) . '%' : '0%';

            $data[] = [
                'Mã ngành' => 'TỔNG CỘNG',
                'Tên ngành' => '',
                'Chỉ tiêu' => $totalChiTieu,
                'Điểm chuẩn' => '',
                'Dự kiến - Tổng đỗ' => $totalTrungTuyen,
                'Dự kiến - Đỗ NV1' => $totalNv1Admit,
                'Dự kiến - Tiến độ (%)' => $pctTotal,
                'Đỗ Bộ GD&ĐT - Tổng đỗ' => $totalDoBo,
                'Đỗ Bộ GD&ĐT - Tiến độ (%)' => $pctBoTotal,
                'Mức điểm (Thấp - Cao)' => ''
            ];

            $filename = 'thong_ke_nganh_sau_loc_ao_' . $sessionId . '.xls';
            $exportService = new \App\Services\ExportService();
            $exportService->toExcel($data, $filename);

        } catch (\Throwable $e) {
            die("Lỗi xuất Excel: " . $e->getMessage());
        }
    }

    /**
     * Xuất Excel dữ liệu của các biểu đồ phân tích (dưới dạng các Sheet riêng biệt tương ứng từng biểu đồ)
     */
    public function exportChartData() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) die("Chưa chọn đợt xét tuyển.");

        try {
            // Kiểm tra xem đã có dữ liệu lọc ảo của Bộ GD&ĐT hay chưa
            $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM ket_qua_loc_ao_bo_gd WHERE dot_tuyen_sinh_id = ?");
            $stmtCheck->execute([$sessionId]);
            $hasBgd = ((int)$stmtCheck->fetchColumn()) > 0;

            $admitCond = $hasBgd 
                ? "cs.trang_thai_trung_tuyen = TRUE AND cs.ket_qua_bo_gd = 'Đỗ'" 
                : "cs.trang_thai_trung_tuyen = TRUE";

            // 1. Thống kê lấp đầy các ngành
            $doBoExpr = $hasBgd 
                ? "COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE AND cs.ket_qua_bo_gd = 'Đỗ' THEN 1 END)" 
                : "COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE THEN 1 END)";

            $majorSql = "SELECT n.ma_nganh, n.ten_nganh, n.chi_tieu,
                                COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE THEN 1 END) as so_trung_tuyen,
                                $doBoExpr as so_luong_do_bo
                         FROM public.dm_nganh n
                         LEFT JOIN public.nguyen_vong nv ON n.ma_nganh = nv.ma_nganh AND nv.dot_tuyen_sinh_id = ?
                         LEFT JOIN public.v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                         GROUP BY n.ma_nganh, n.ten_nganh, n.chi_tieu
                         ORDER BY n.ma_nganh";
            $stmt = $this->db->prepare($majorSql);
            $stmt->execute([$sessionId, $sessionId]);
            $majorRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Thống kê Nguyện vọng
            $statsSql = "SELECT 
                            COUNT(CASE WHEN $admitCond AND nv.thu_tu_nguyen_vong = 1 THEN 1 END) as nv1,
                            COUNT(CASE WHEN $admitCond AND nv.thu_tu_nguyen_vong = 2 THEN 1 END) as nv2,
                            COUNT(CASE WHEN $admitCond AND nv.thu_tu_nguyen_vong = 3 THEN 1 END) as nv3,
                            COUNT(CASE WHEN $admitCond AND nv.thu_tu_nguyen_vong > 3 THEN 1 END) as nv_khac
                         FROM public.nguyen_vong nv
                         LEFT JOIN public.v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                         WHERE nv.dot_tuyen_sinh_id = ?";
            $stmtStats = $this->db->prepare($statsSql);
            $stmtStats->execute([$sessionId]);
            $nvRow = $stmtStats->fetch(PDO::FETCH_ASSOC);

            // 3. Demographics
            $demoSql = "SELECT t.gioi_tinh, t.khu_vuc_uu_tien, t.doi_tuong_uu_tien, 
                               COALESCE(dt.ten_tinh, NULLIF(t.ma_tinh_lop_12, ''), NULLIF(t.ma_tinh_ho_khau, ''), SUBSTRING(t.ma_truong_lop_12, 1, 2), 'Khác') as ten_tinh, 
                               COALESCE(dthpt.ten_truong, t.ma_truong_lop_12, 'Khác') as ten_truong,
                               COALESCE(NULLIF(t.ma_tinh_lop_12, ''), NULLIF(t.ma_tinh_ho_khau, ''), SUBSTRING(t.ma_truong_lop_12, 1, 2)) as candidate_province
                        FROM public.nguyen_vong nv
                        JOIN public.v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                        JOIN public.thi_sinh t ON nv.so_cccd = t.so_cccd
                        LEFT JOIN public.dm_tinh dt ON COALESCE(NULLIF(t.ma_tinh_lop_12, ''), NULLIF(t.ma_tinh_ho_khau, ''), SUBSTRING(t.ma_truong_lop_12, 1, 2)) = dt.ma_tinh
                        LEFT JOIN public.dm_truong_thpt dthpt ON t.ma_truong_lop_12 = dthpt.ma_truong 
                             AND COALESCE(NULLIF(t.ma_tinh_lop_12, ''), NULLIF(t.ma_tinh_ho_khau, ''), SUBSTRING(t.ma_truong_lop_12, 1, 2)) = dthpt.ma_tinh 
                             AND dthpt.is_active = TRUE
                        WHERE nv.dot_tuyen_sinh_id = ? AND $admitCond";
            $stmtDemo = $this->db->prepare($demoSql);
            $stmtDemo->execute([$sessionId]);
            $demoRows = $stmtDemo->fetchAll(PDO::FETCH_ASSOC);

            $genders = [];
            $areas = [];
            $objects = [];
            $provinces = [];
            $schools = [];

            foreach ($demoRows as $row) {
                // Gender
                $gt = trim($row['gioi_tinh'] ?? '');
                if (strcasecmp($gt, 'Nam') === 0 || $gt === '1') $gt = 'Nam';
                elseif (strcasecmp($gt, 'Nữ') === 0 || strcasecmp($gt, 'Nu') === 0 || $gt === '0') $gt = 'Nữ';
                else $gt = 'Khác';
                $genders[$gt] = ($genders[$gt] ?? 0) + 1;

                // Area
                $a = $row['khu_vuc_uu_tien'] ?: 'Khác';
                $areas[$a] = ($areas[$a] ?? 0) + 1;

                // Object
                $o = $row['doi_tuong_uu_tien'] ?: 'Không';
                $objects[$o] = ($objects[$o] ?? 0) + 1;

                // Province
                $p = $row['ten_tinh'] ?: 'Khác';
                $provinces[$p] = ($provinces[$p] ?? 0) + 1;

                // School (for Phu Tho only)
                if ($row['candidate_province'] === '25') {
                    $s = $row['ten_truong'] ?: 'Khác';
                    $schools[$s] = ($schools[$s] ?? 0) + 1;
                }
            }

            arsort($provinces);
            arsort($schools);

            // Clean output buffer
            ob_clean();
            $filename = 'du_lieu_bieu_do_loc_ao_' . $sessionId . '.xls';
            header("Content-Type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Cache-Control: no-cache, no-store, must-revalidate");

            // Write SpreadsheetML XML
            echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
            echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
            
            echo ' <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office"><Author>Virtual Admission</Author></DocumentProperties>' . "\n";
            
            echo ' <Styles>' . "\n";
            echo '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="11" ss:Color="#334155"/></Style>' . "\n";
            echo '  <Style ss:ID="sTitle"><Alignment ss:Vertical="Center"/><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="14" ss:Bold="1" ss:Color="#1e293b"/></Style>' . "\n";
            echo '  <Style ss:ID="sHeader"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/></Borders><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="11" ss:Color="#475569" ss:Bold="1"/><Interior ss:Color="#f8fafc" ss:Pattern="Solid"/></Style>' . "\n";
            echo '  <Style ss:ID="sText"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/></Borders><NumberFormat ss:Format="@"/></Style>' . "\n";
            echo '  <Style ss:ID="sNum"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/></Borders><NumberFormat ss:Format="0"/></Style>' . "\n";
            echo '  <Style ss:ID="sPct"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/></Borders><NumberFormat ss:Format="0.0%"/></Style>' . "\n";
            echo ' </Styles>' . "\n";

            // Sheet 1: Tỷ lệ lấp đầy
            echo ' <Worksheet ss:Name="Tỷ lệ lấp đầy">' . "\n";
            echo '  <Table>' . "\n";
            echo '   <Column ss:Width="80"/>' . "\n";
            echo '   <Column ss:Width="250"/>' . "\n";
            echo '   <Column ss:Width="80"/>' . "\n";
            echo '   <Column ss:Width="120"/>' . "\n";
            echo '   <Column ss:Width="120"/>' . "\n";
            echo '   <Column ss:Width="120"/>' . "\n";
            echo '   <Column ss:Width="120"/>' . "\n";
            echo '   <Row ss:Height="30"><Cell ss:StyleID="sTitle" ss:MergeAcross="6"><Data ss:Type="String">THỐNG KÊ TỶ LỆ LẤP ĐẦY CHUYÊN NGÀNH</Data></Cell></Row>' . "\n";
            echo '   <Row ss:Height="25">' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Mã ngành</Data></Cell>' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Tên ngành</Data></Cell>' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Chỉ tiêu</Data></Cell>' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Dự kiến đỗ (Tổng)</Data></Cell>' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Đỗ Bộ GD&amp;ĐT</Data></Cell>' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Tiến độ dự kiến (%)</Data></Cell>' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Tiến độ thực tế (%)</Data></Cell>' . "\n";
            echo '   </Row>' . "\n";
            foreach ($majorRows as $mr) {
                $ct = intval($mr['chi_tieu']);
                $stt = intval($mr['so_trung_tuyen']);
                $db = intval($mr['so_luong_do_bo']);
                $pct = $ct > 0 ? ($stt / $ct) : 0;
                $pctBo = $ct > 0 ? ($db / $ct) : 0;
                echo '   <Row>' . "\n";
                echo '    <Cell ss:StyleID="sText"><Data ss:Type="String">' . htmlspecialchars($mr['ma_nganh']) . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="sText"><Data ss:Type="String">' . htmlspecialchars($mr['ten_nganh']) . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="sNum"><Data ss:Type="Number">' . $ct . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="sNum"><Data ss:Type="Number">' . $stt . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="sNum"><Data ss:Type="Number">' . $db . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="sPct"><Data ss:Type="Number">' . $pct . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="sPct"><Data ss:Type="Number">' . $pctBo . '</Data></Cell>' . "\n";
                echo '   </Row>' . "\n";
            }
            echo '  </Table>' . "\n";
            echo ' </Worksheet>' . "\n";

            // Sheet 2: Nguyện vọng
            echo ' <Worksheet ss:Name="Thứ tự nguyện vọng">' . "\n";
            echo '  <Table>' . "\n";
            echo '   <Column ss:Width="200"/>' . "\n";
            echo '   <Column ss:Width="150"/>' . "\n";
            echo '   <Row ss:Height="30"><Cell ss:StyleID="sTitle" ss:MergeAcross="1"><Data ss:Type="String">PHÂN BỐ TRÚNG TUYỂN THEO NGUYỆN VỌNG</Data></Cell></Row>' . "\n";
            echo '   <Row ss:Height="25">' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Thứ tự nguyện vọng</Data></Cell>' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Số lượng trúng tuyển</Data></Cell>' . "\n";
            echo '   </Row>' . "\n";
            echo '   <Row><Cell ss:StyleID="sText"><Data ss:Type="String">Nguyện vọng 1</Data></Cell><Cell ss:StyleID="sNum"><Data ss:Type="Number">' . intval($nvRow['nv1']) . '</Data></Cell></Row>' . "\n";
            echo '   <Row><Cell ss:StyleID="sText"><Data ss:Type="String">Nguyện vọng 2</Data></Cell><Cell ss:StyleID="sNum"><Data ss:Type="Number">' . intval($nvRow['nv2']) . '</Data></Cell></Row>' . "\n";
            echo '   <Row><Cell ss:StyleID="sText"><Data ss:Type="String">Nguyện vọng 3</Data></Cell><Cell ss:StyleID="sNum"><Data ss:Type="Number">' . intval($nvRow['nv3']) . '</Data></Cell></Row>' . "\n";
            echo '   <Row><Cell ss:StyleID="sText"><Data ss:Type="String">Nguyện vọng khác (&gt;3)</Data></Cell><Cell ss:StyleID="sNum"><Data ss:Type="Number">' . intval($nvRow['nv_khac']) . '</Data></Cell></Row>' . "\n";
            echo '  </Table>' . "\n";
            echo ' </Worksheet>' . "\n";

            // Sheet 3: Giới tính
            echo ' <Worksheet ss:Name="Giới tính">' . "\n";
            echo '  <Table>' . "\n";
            echo '   <Column ss:Width="150"/>' . "\n";
            echo '   <Column ss:Width="150"/>' . "\n";
            echo '   <Row ss:Height="30"><Cell ss:StyleID="sTitle" ss:MergeAcross="1"><Data ss:Type="String">PHÂN BỐ TRÚNG TUYỂN THEO GIỚI TÍNH</Data></Cell></Row>' . "\n";
            echo '   <Row ss:Height="25">' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Giới tính</Data></Cell>' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Số lượng trúng tuyển</Data></Cell>' . "\n";
            echo '   </Row>' . "\n";
            foreach ($genders as $g => $count) {
                echo '   <Row>' . "\n";
                echo '    <Cell ss:StyleID="sText"><Data ss:Type="String">' . htmlspecialchars($g) . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="sNum"><Data ss:Type="Number">' . $count . '</Data></Cell>' . "\n";
                echo '   </Row>' . "\n";
            }
            echo '  </Table>' . "\n";
            echo ' </Worksheet>' . "\n";

            // Sheet 4: Khu vực
            echo ' <Worksheet ss:Name="Khu vực">' . "\n";
            echo '  <Table>' . "\n";
            echo '   <Column ss:Width="150"/>' . "\n";
            echo '   <Column ss:Width="150"/>' . "\n";
            echo '   <Row ss:Height="30"><Cell ss:StyleID="sTitle" ss:MergeAcross="1"><Data ss:Type="String">PHÂN BỐ TRÚNG TUYỂN THEO KHU VỰC ƯU TIÊN</Data></Cell></Row>' . "\n";
            echo '   <Row ss:Height="25">' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Khu vực</Data></Cell>' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Số lượng trúng tuyển</Data></Cell>' . "\n";
            echo '   </Row>' . "\n";
            foreach ($areas as $a => $count) {
                echo '   <Row>' . "\n";
                echo '    <Cell ss:StyleID="sText"><Data ss:Type="String">' . htmlspecialchars($a) . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="sNum"><Data ss:Type="Number">' . $count . '</Data></Cell>' . "\n";
                echo '   </Row>' . "\n";
            }
            echo '  </Table>' . "\n";
            echo ' </Worksheet>' . "\n";

            // Sheet 5: Đối tượng
            echo ' <Worksheet ss:Name="Đối tượng ưu tiên">' . "\n";
            echo '  <Table>' . "\n";
            echo '   <Column ss:Width="150"/>' . "\n";
            echo '   <Column ss:Width="150"/>' . "\n";
            echo '   <Row ss:Height="30"><Cell ss:StyleID="sTitle" ss:MergeAcross="1"><Data ss:Type="String">PHÂN BỐ TRÚNG TUYỂN THEO ĐỐI TƯỢNG ƯU TIÊN</Data></Cell></Row>' . "\n";
            echo '   <Row ss:Height="25">' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Đối tượng</Data></Cell>' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Số lượng trúng tuyển</Data></Cell>' . "\n";
            echo '   </Row>' . "\n";
            foreach ($objects as $o => $count) {
                echo '   <Row>' . "\n";
                echo '    <Cell ss:StyleID="sText"><Data ss:Type="String">' . htmlspecialchars($o) . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="sNum"><Data ss:Type="Number">' . $count . '</Data></Cell>' . "\n";
                echo '   </Row>' . "\n";
            }
            echo '  </Table>' . "\n";
            echo ' </Worksheet>' . "\n";

            // Sheet 6: Tỉnh thành
            echo ' <Worksheet ss:Name="Tỉnh thành">' . "\n";
            echo '  <Table>' . "\n";
            echo '   <Column ss:Width="250"/>' . "\n";
            echo '   <Column ss:Width="150"/>' . "\n";
            echo '   <Row ss:Height="30"><Cell ss:StyleID="sTitle" ss:MergeAcross="1"><Data ss:Type="String">PHÂN BỐ TRÚNG TUYỂN THEO TỈNH THÀNH PHỐ</Data></Cell></Row>' . "\n";
            echo '   <Row ss:Height="25">' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Tỉnh / Thành phố</Data></Cell>' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Số lượng trúng tuyển</Data></Cell>' . "\n";
            echo '   </Row>' . "\n";
            foreach ($provinces as $p => $count) {
                echo '   <Row>' . "\n";
                echo '    <Cell ss:StyleID="sText"><Data ss:Type="String">' . htmlspecialchars($p) . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="sNum"><Data ss:Type="Number">' . $count . '</Data></Cell>' . "\n";
                echo '   </Row>' . "\n";
            }
            echo '  </Table>' . "\n";
            echo ' </Worksheet>' . "\n";

            // Sheet 7: Trường THPT Phú Thọ
            echo ' <Worksheet ss:Name="THPT Phú Thọ">' . "\n";
            echo '  <Table>' . "\n";
            echo '   <Column ss:Width="300"/>' . "\n";
            echo '   <Column ss:Width="150"/>' . "\n";
            echo '   <Row ss:Height="30"><Cell ss:StyleID="sTitle" ss:MergeAcross="1"><Data ss:Type="String">PHÂN BỐ TRÚNG TUYỂN THEO TRƯỜNG THPT PHÚ THỌ</Data></Cell></Row>' . "\n";
            echo '   <Row ss:Height="25">' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Trường THPT</Data></Cell>' . "\n";
            echo '    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Số lượng trúng tuyển</Data></Cell>' . "\n";
            echo '   </Row>' . "\n";
            foreach ($schools as $s => $count) {
                echo '   <Row>' . "\n";
                echo '    <Cell ss:StyleID="sText"><Data ss:Type="String">' . htmlspecialchars($s) . '</Data></Cell>' . "\n";
                echo '    <Cell ss:StyleID="sNum"><Data ss:Type="Number">' . $count . '</Data></Cell>' . "\n";
                echo '   </Row>' . "\n";
            }
            echo '  </Table>' . "\n";
            echo ' </Worksheet>' . "\n";

            echo '</Workbook>' . "\n";
            exit;

        } catch (\Throwable $e) {
            die("Lỗi xuất Excel Biểu đồ: " . $e->getMessage());
        }
    }

    public function exportVirtualFilterAdmitted() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) die("Chưa chọn đợt xét tuyển.");

        $sql = "SELECT nv.so_cccd, nv.thu_tu_nguyen_vong, nv.ma_nganh
                FROM nguyen_vong nv
                JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                WHERE nv.dot_tuyen_sinh_id = ? AND cs.trang_thai_trung_tuyen = TRUE
                ORDER BY nv.so_cccd ASC, nv.thu_tu_nguyen_vong ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);

        $data = [];
        $stt = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [
                'STT' => $stt++,
                'ĐDCN' => $row['so_cccd'],
                'Thứ tự nguyện vọng' => $row['thu_tu_nguyen_vong'],
                'Mã xét tuyển' => $row['ma_nganh']
            ];
        }

        $filename = 'xuat_ket_qua_loc_ao_' . $sessionId . '.xls';
        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, $filename);
    }

    public function getStats() {
        $sessionId = $_GET['session_id'] ?? $_POST['session_id'] ?? null;
        if (!$sessionId) {
            $this->json(['success' => false, 'message' => 'Chưa chọn đợt tuyển sinh.']);
            return;
        }

        // Kiểm tra xem đã có dữ liệu lọc ảo của Bộ GD&ĐT hay chưa
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM ket_qua_loc_ao_bo_gd WHERE dot_tuyen_sinh_id = ?");
        $stmtCheck->execute([$sessionId]);
        $hasBgd = ((int)$stmtCheck->fetchColumn()) > 0;

        $admitCond = $hasBgd 
            ? "cs.trang_thai_trung_tuyen = TRUE AND cs.ket_qua_bo_gd = 'Đỗ'" 
            : "cs.trang_thai_trung_tuyen = TRUE";

        $intSessionId = (int)$sessionId;

        // 1. Global Stats - Optimized by splitting into two fast index-based queries to avoid full Seq Scan on v_calc_summary
        $statsSqlA = "SELECT 
                        COUNT(DISTINCT so_cccd) as total_candidates,
                        COUNT(id) as total_wishes
                     FROM public.nguyen_vong 
                     WHERE dot_tuyen_sinh_id = $intSessionId";
        $statsStmtA = $this->db->query($statsSqlA);
        $statsA = $statsStmtA->fetch(PDO::FETCH_ASSOC) ?: ['total_candidates' => 0, 'total_wishes' => 0];

        $statsSqlB = "SELECT 
                        COUNT(CASE WHEN $admitCond THEN 1 END) as total_admitted,
                        COUNT(CASE WHEN $admitCond AND nv.thu_tu_nguyen_vong = 1 THEN 1 END) as nv1_admit,
                        COUNT(CASE WHEN $admitCond AND nv.thu_tu_nguyen_vong = 2 THEN 1 END) as nv2_admit,
                        COUNT(CASE WHEN $admitCond AND nv.thu_tu_nguyen_vong = 3 THEN 1 END) as nv3_admit
                     FROM public.nguyen_vong nv
                     JOIN public.v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                     WHERE nv.dot_tuyen_sinh_id = $intSessionId AND cs.dot_tuyen_sinh_id = $intSessionId AND cs.trang_thai_trung_tuyen = TRUE";
        $statsStmtB = $this->db->query($statsSqlB);
        $statsB = $statsStmtB->fetch(PDO::FETCH_ASSOC) ?: ['total_admitted' => 0, 'nv1_admit' => 0, 'nv2_admit' => 0, 'nv3_admit' => 0];

        $stats = array_merge($statsA, $statsB);

        // 2. Per-major stats (admitted vs chi_tieu)  
        $doBoExpr = $hasBgd 
            ? "COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE AND cs.ket_qua_bo_gd = 'Đỗ' THEN 1 END)" 
            : "COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE THEN 1 END)";

        $majorStatsSql = "SELECT n.ma_nganh, n.ten_nganh, n.chi_tieu, n.nhom_nganh,
                            COALESCE(ab.diem_chuan, 0) as diem_chuan,
                            COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE THEN 1 END) as so_trung_tuyen,
                            COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE AND nv.thu_tu_nguyen_vong = 1 THEN 1 END) as nv1_admit,
                            $doBoExpr as so_luong_do_bo,
                            MAX(CASE WHEN cs.trang_thai_trung_tuyen = TRUE THEN cs.diem_xet_tuyen END) as diem_cao_nhat,
                            MIN(CASE WHEN cs.trang_thai_trung_tuyen = TRUE THEN cs.diem_xet_tuyen END) as diem_thap_nhat
                          FROM public.dm_nganh n
                          LEFT JOIN public.admission_benchmarks ab ON n.ma_nganh = ab.ma_nganh AND ab.session_id = $intSessionId
                          LEFT JOIN public.nguyen_vong nv ON n.ma_nganh = nv.ma_nganh AND nv.dot_tuyen_sinh_id = $intSessionId
                          LEFT JOIN public.v_calc_summary cs ON nv.id = cs.nguyen_vong_id AND cs.dot_tuyen_sinh_id = $intSessionId
                          GROUP BY n.ma_nganh, n.ten_nganh, n.chi_tieu, n.nhom_nganh, ab.diem_chuan
                          ORDER BY n.ma_nganh";
        $majorStatsStmt = $this->db->query($majorStatsSql);
        $majorStats = $majorStatsStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Demographics for Charts - Lazy loaded
        $includeDemo = ($_GET['include_demo'] ?? $_POST['include_demo'] ?? 0) == 1;

        $chartDist = [
            'gender' => [],
            'area' => [],
            'object' => [],
            'province' => [],
            'school' => []
        ];

        if ($includeDemo) {
            // Load tinh map and school map to map IDs to names in PHP (saves massive JOIN execution time)
            $tinhMap = $this->db->query("SELECT ma_tinh, ten_tinh FROM dm_tinh")->fetchAll(PDO::FETCH_KEY_PAIR);
            $schoolStmt = $this->db->prepare("SELECT ma_truong, ten_truong FROM dm_truong_thpt WHERE ma_tinh = '25' AND is_active = TRUE");
            $schoolStmt->execute();
            $schoolMap = $schoolStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Optimized FLAT Query without slow dm_tinh and dm_truong_thpt joins
            $demoSql = "SELECT t.gioi_tinh, t.khu_vuc_uu_tien, t.doi_tuong_uu_tien, 
                               t.ma_tinh_lop_12, t.ma_tinh_ho_khau, t.ma_truong_lop_12
                        FROM public.nguyen_vong nv
                        JOIN public.v_calc_summary cs ON nv.id = cs.nguyen_vong_id AND cs.dot_tuyen_sinh_id = $intSessionId
                        JOIN public.thi_sinh t ON nv.so_cccd = t.so_cccd
                        WHERE nv.dot_tuyen_sinh_id = $intSessionId AND $admitCond";
            $demoStmt = $this->db->query($demoSql);
            $demoRows = $demoStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($demoRows as $row) {
                // Gender
                $gt = trim($row['gioi_tinh'] ?? '');
                if (strcasecmp($gt, 'Nam') === 0 || $gt === '1') $gt = 'Nam';
                elseif (strcasecmp($gt, 'Nữ') === 0 || strcasecmp($gt, 'Nu') === 0 || $gt === '0') $gt = 'Nữ';
                else $gt = 'Khác';
                $chartDist['gender'][$gt] = ($chartDist['gender'][$gt] ?? 0) + 1;

                // Area
                $a = $row['khu_vuc_uu_tien'] ?: 'Khác';
                $chartDist['area'][$a] = ($chartDist['area'][$a] ?? 0) + 1;

                // Object
                $o = $row['doi_tuong_uu_tien'] ?: 'Không';
                $chartDist['object'][$o] = ($chartDist['object'][$o] ?? 0) + 1;

                // Province mapping in PHP
                $maTinh12 = trim($row['ma_tinh_lop_12'] ?? '');
                $maTinhHK = trim($row['ma_tinh_ho_khau'] ?? '');
                $maTruong12 = trim($row['ma_truong_lop_12'] ?? '');

                $candidateProvince = '';
                if ($maTinh12 !== '') {
                    $candidateProvince = $maTinh12;
                } elseif ($maTinhHK !== '') {
                    $candidateProvince = $maTinhHK;
                } elseif (strlen($maTruong12) >= 2) {
                    $candidateProvince = substr($maTruong12, 0, 2);
                }

                $p = isset($tinhMap[$candidateProvince]) ? $tinhMap[$candidateProvince] : ($candidateProvince ?: 'Khác');
                $chartDist['province'][$p] = ($chartDist['province'][$p] ?? 0) + 1;

                // School mapping in PHP - strictly for Phu Tho candidates (ma_tinh = '25')
                if ($candidateProvince === '25') {
                    $s = isset($schoolMap[$maTruong12]) ? $schoolMap[$maTruong12] : ($maTruong12 ?: 'Khác');
                    $chartDist['school'][$s] = ($chartDist['school'][$s] ?? 0) + 1;
                }
            }

            // Sort sub-arrays desc
            arsort($chartDist['province']);
            arsort($chartDist['school']);
        }

        $this->json([
            'success' => true,
            'stats' => $stats,
            'majorStats' => $majorStats,
            'chartDist' => $chartDist
        ]);
    }
}
