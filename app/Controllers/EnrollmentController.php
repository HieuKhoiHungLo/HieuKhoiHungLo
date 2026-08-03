<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use PDO;

class EnrollmentController extends Controller {
    protected $masterData;
    protected $db;
    protected $currentUser;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }

        $adminModel = new \App\Models\QuanTriVien();
        $this->currentUser = $adminModel->find($_SESSION['admin_id']);

        if (!$this->currentUser || !$this->currentUser['is_active']) {
             session_destroy();
             $this->redirect(url('/admin/login'));
        }

        // Validate permissions
        if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, 'admission.view') &&
            !\App\Models\QuanTriVien::hasPermission($this->currentUser, 'admission.edit') &&
            !\App\Models\QuanTriVien::hasPermission($this->currentUser, 'enrollment.process')) {
            $this->redirect(url('/admin/dashboard'));
        }

        $this->masterData = new MasterData();
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }

    public function setup() {
        $sessions = $this->masterData->getSessions();
        $sessionId = $_GET['session_id'] ?? (count($sessions) > 0 ? $sessions[0]['id'] : 0);

        // Fetch setup for this session
        $stmt = $this->db->prepare("SELECT * FROM nhap_hoc_ho_so WHERE session_id = ? ORDER BY thu_tu ASC, id ASC");
        $stmt->execute([$sessionId]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/enrollment/setup', [
            'title' => 'Cấu hình Hồ sơ Nhập học',
            'sessions' => $sessions,
            'currentSessionId' => $sessionId,
            'documents' => $documents
        ]);
    }

    public function saveSetup() {
        file_put_contents(__DIR__.'/../../../debug_save.txt', date('Y-m-d H:i:s') . " POST: " . json_encode($_POST) . "\n", FILE_APPEND);
        if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, 'admission.edit')) {
            echo json_encode(['success' => false, 'message' => 'Không có quyền thực hiện.']);
            return;
        }

        $sessionId = $_POST['session_id'] ?? 0;
        $documents = $_POST['documents'] ?? []; // Array of {id, ten_ho_so, cac_gia_tri, gia_tri_mac_dinh, bat_buoc, thu_tu}

        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $this->db->beginTransaction();

            // Lấy danh sách ID hiện tại
            $stmt = $this->db->prepare("SELECT id FROM nhap_hoc_ho_so WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            $existingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $keepIds = [];

            $insertStmt = $this->db->prepare("INSERT INTO nhap_hoc_ho_so (session_id, ten_ho_so, cac_gia_tri, gia_tri_mac_dinh, bat_buoc, thu_tu) VALUES (?, ?, ?, ?, ?, ?)");
            $updateStmt = $this->db->prepare("UPDATE nhap_hoc_ho_so SET ten_ho_so = ?, cac_gia_tri = ?, gia_tri_mac_dinh = ?, bat_buoc = ?, thu_tu = ? WHERE id = ? AND session_id = ?");

            foreach ($documents as $doc) {
                $id = $doc['id'] ?? '';
                $cacGiaTri = is_array($doc['cac_gia_tri']) ? json_encode($doc['cac_gia_tri'], JSON_UNESCAPED_UNICODE) : $doc['cac_gia_tri'];
                $batBuoc = empty($doc['bat_buoc']) || $doc['bat_buoc'] === 'false' ? 'false' : 'true';
                $thuTu = intval($doc['thu_tu'] ?? 0);

                if (empty($id) || strpos($id, 'new') === 0) {
                    $insertStmt->execute([$sessionId, $doc['ten_ho_so'], $cacGiaTri, $doc['gia_tri_mac_dinh'], $batBuoc, $thuTu]);
                } else {
                    $keepIds[] = $id;
                    $updateStmt->execute([$doc['ten_ho_so'], $cacGiaTri, $doc['gia_tri_mac_dinh'], $batBuoc, $thuTu, $id, $sessionId]);
                }
            }

            // Xóa những hồ sơ bị loại bỏ
            $idsToDelete = array_diff($existingIds, $keepIds);
            if (!empty($idsToDelete)) {
                $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
                $delStmt = $this->db->prepare("DELETE FROM nhap_hoc_ho_so WHERE id IN ($placeholders)");
                $delStmt->execute(array_values(array_map('intval', $idsToDelete)));
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Lưu cấu hình thành công']);
        } catch (\Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    public function process() {
        $sessions = $this->masterData->getSessions();
        $sessionId = $_GET['session_id'] ?? (count($sessions) > 0 ? $sessions[0]['id'] : 0);

        // Config QR code parameters (can be moved to DB settings later)
        $qrConfig = [
            'bank_id' => 'VietinBank', // Tên ngân hàng hoặc BIN
            'account_no' => '113000000000',
            'account_name' => 'TRUONG DAI HOC HUNG VUONG',
            'amount' => '5000000', // Số tiền mặc định tạm thời
            'description_prefix' => 'NHAPHOC '
        ];

        $this->view('admin/enrollment/process', [
            'title' => 'Xử lý Nhập học',
            'sessions' => $sessions,
            'currentSessionId' => $sessionId,
            'qrConfig' => $qrConfig
        ]);
    }

        public function searchCandidate() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $sessionId = $_GET['session_id'] ?? 0;
        $keyword = trim($_GET['keyword'] ?? '');
        $type = $_GET['type'] ?? 'name'; // 'name' or 'cccd'

        if (empty($keyword)) {
            echo json_encode(['success' => true, 'data' => []]);
            return;
        }

        // Search in ket_qua_trung_tuyen JOIN thi_sinh
        $whereClause = "kq.so_cccd = ? OR kq.ho_ten ILIKE ?";
        $term = '%' . $keyword . '%';

        $stmt = $this->db->prepare("
            SELECT kq.id as ket_qua_id, kq.ho_ten, kq.so_cccd, kq.sbd as so_bao_danh, kq.ngay_sinh, 
                   kq.ma_nganh, n.ten_nganh, kq.diem_xt as diem_xet_tuyen, kq.sdt as dien_thoai_kq, kq.email as email_kq,
                   kq.khu_vuc as khu_vuc_kq, kq.doi_tuong as doi_tuong_kq, kq.to_hop, kq.diem_to_hop, kq.phuong_thuc,
                   ts.gioi_tinh, ts.dia_chi_chi_tiet, ts.nam_tot_nghiep, ts.dien_thoai as dien_thoai_ts, ts.email as email_ts, ts.ma_truong_lop_12,
                   nh.id as nhap_hoc_id, nh.trang_thai as trang_thai_nhap_hoc, nh.da_nop_tien, nh.ma_phieu, nh.ghi_chu_can_bo
            FROM ket_qua_trung_tuyen kq
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
            LEFT JOIN nhap_hoc nh ON kq.id = nh.ket_qua_id AND nh.session_id = ?
            WHERE kq.session_id = ? 
              AND ($whereClause)
            ORDER BY kq.ho_ten ASC
            LIMIT 20
        ");
        $stmt->execute([$sessionId, $sessionId, $keyword, $term]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get required documents config for this session
        $docStmt = $this->db->prepare("SELECT * FROM nhap_hoc_ho_so WHERE session_id = ? ORDER BY thu_tu ASC");
        $docStmt->execute([$sessionId]);
        $documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);

        // Process extra fields
        foreach ($candidates as &$candidate) {
            // Merge emails/phones
            $candidate['dien_thoai'] = $candidate['dien_thoai_ts'] ?: $candidate['dien_thoai_kq'];
            $candidate['email'] = $candidate['email_ts'] ?: $candidate['email_kq'];
            $candidate['nam_tot_nghiep'] = $candidate['nam_tot_nghiep'] ?: '';
            
            // Default extras
            $candidate['extra_info'] = [
                'ngay_cap_cccd' => '',
                'noi_cap_cccd' => '',
                'lop' => '',
                'dia_chi_lien_he' => $candidate['dia_chi_chi_tiet'] ?? '',
                'truong_thpt' => $candidate['ma_truong_lop_12'] ?? ''
            ];

            if (!empty($candidate['ghi_chu_can_bo'])) {
                $extra = json_decode($candidate['ghi_chu_can_bo'], true);
                if (is_array($extra)) {
                    $candidate['extra_info'] = array_merge($candidate['extra_info'], $extra);
                }
            }

            $candidate['documents'] = $documents;
            if ($candidate['nhap_hoc_id']) {
                $valStmt = $this->db->prepare("SELECT ho_so_id, gia_tri, ghi_chu FROM nhap_hoc_ho_so_gia_tri WHERE nhap_hoc_id = ?");
                $valStmt->execute([$candidate['nhap_hoc_id']]);
                $values = $valStmt->fetchAll(PDO::FETCH_ASSOC);
                $valMap = [];
                foreach ($values as $v) {
                    $valMap[$v['ho_so_id']] = $v;
                }
                foreach ($candidate['documents'] as &$doc) {
                    if (isset($valMap[$doc['id']])) {
                        $doc['selected_value'] = $valMap[$doc['id']]['gia_tri'];
                        $doc['ghi_chu_val'] = $valMap[$doc['id']]['ghi_chu'];
                    }
                }
            } else {
                foreach ($candidate['documents'] as &$doc) {
                    $doc['selected_value'] = $doc['gia_tri_mac_dinh'];
                    $doc['ghi_chu_val'] = '';
                }
            }
        }

        echo json_encode(['success' => true, 'data' => $candidates]);
    }

    public function submitEnrollment() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        
        if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, 'admission.edit') &&
            !\App\Models\QuanTriVien::hasPermission($this->currentUser, 'enrollment.process')) {
            echo json_encode(['success' => false, 'message' => 'Không có quyền thực hiện.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sessionId = $_POST['session_id'] ?? 0;
        $ketQuaId = $_POST['ket_qua_id'] ?? 0;
        $soCccd = $_POST['so_cccd'] ?? '';
        $action = $_POST['action'] ?? 'nhap_hoc'; // 'nhap_hoc', 'luu_tam', 'huy'
        $extraData = $_POST['extra'] ?? []; 
        $docs = $_POST['documents'] ?? []; 

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT id, ma_phieu FROM nhap_hoc WHERE ket_qua_id = ? AND session_id = ?");
            $stmt->execute([$ketQuaId, $sessionId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            $adminId = $_SESSION['admin_id'];

            $trangThai = 'da_nhap_hoc';
            if ($action === 'luu_tam') $trangThai = 'cho_xet_duyet';
            if ($action === 'huy') $trangThai = 'da_huy';

            $ghiChuCanBo = json_encode($extraData, JSON_UNESCAPED_UNICODE);

            if ($existing) {
                $nhapHocId = $existing['id'];
                $upd = $this->db->prepare("UPDATE nhap_hoc SET trang_thai = ?, nguoi_nhap = ?, ghi_chu_can_bo = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $upd->execute([$trangThai, $adminId, $ghiChuCanBo, $nhapHocId]);
                $del = $this->db->prepare("DELETE FROM nhap_hoc_ho_so_gia_tri WHERE nhap_hoc_id = ?");
                $del->execute([$nhapHocId]);
            } else {
                $year = date('Y');
                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM nhap_hoc WHERE session_id = ?");
                $countStmt->execute([$sessionId]);
                $count = $countStmt->fetchColumn() + 1;
                $maPhieu = "NH{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);

                $ins = $this->db->prepare("INSERT INTO nhap_hoc (ket_qua_id, session_id, so_cccd, nguoi_nhap, ma_phieu, trang_thai, ghi_chu_can_bo) VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id");
                $ins->execute([$ketQuaId, $sessionId, $soCccd, $adminId, $maPhieu, $trangThai, $ghiChuCanBo]);
                $nhapHocId = $ins->fetchColumn();
            }

            if (!empty($docs)) {
                $insVal = $this->db->prepare("INSERT INTO nhap_hoc_ho_so_gia_tri (nhap_hoc_id, ho_so_id, gia_tri, ghi_chu) VALUES (?, ?, ?, ?)");
                foreach ($docs as $hoSoId => $data) {
                    $giaTri = $data['gia_tri'] ?? '';
                    $ghiChu = $data['ghi_chu'] ?? '';
                    $insVal->execute([$nhapHocId, $hoSoId, $giaTri, $ghiChu]);
                }
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'nhap_hoc_id' => $nhapHocId, 'message' => 'Cập nhật thành công'], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }


    public function printReceipt() {
        $nhapHocId = $_GET['id'] ?? 0;
        
        $stmt = $this->db->prepare("
            SELECT nh.*, kq.ho_ten, kq.ngay_sinh, kq.so_cccd, kq.sdt as dien_thoai, kq.ma_nganh, n.ten_nganh,
                   qv.ho_ten as ten_can_bo
            FROM nhap_hoc nh
            JOIN ket_qua_trung_tuyen kq ON nh.ket_qua_id = kq.id
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            LEFT JOIN quan_tri_vien qv ON nh.nguoi_nhap = qv.id
            WHERE nh.id = ?
        ");
        $stmt->execute([$nhapHocId]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) {
            die("Không tìm thấy thông tin nhập học.");
        }

        $valStmt = $this->db->prepare("
            SELECT v.gia_tri, v.ghi_chu, h.ten_ho_so, h.thu_tu 
            FROM nhap_hoc_ho_so_gia_tri v
            JOIN nhap_hoc_ho_so h ON v.ho_so_id = h.id
            WHERE v.nhap_hoc_id = ?
            ORDER BY h.thu_tu ASC
        ");
        $valStmt->execute([$nhapHocId]);
        $documents = $valStmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/enrollment/print', [
            'title' => 'In Phiếu Nhập Học',
            'enrollment' => $enrollment,
            'documents' => $documents,
            'hideSidebar' => true
        ]);
    }

    public function stats() {
        $sessions = $this->masterData->getSessions();
        $sessionId = intval($_GET['session_id'] ?? (count($sessions) > 0 ? $sessions[0]['id'] : 0));

        // Get basic stats
        $stmtTT = $this->db->prepare("SELECT COUNT(*) FROM ket_qua_trung_tuyen WHERE session_id = ?");
        $stmtTT->execute([$sessionId]);
        $totalTrungTuyen = $stmtTT->fetchColumn();

        $stmtNH = $this->db->prepare("SELECT COUNT(*) FROM nhap_hoc WHERE session_id = ? AND trang_thai = 'da_nhap_hoc'");
        $stmtNH->execute([$sessionId]);
        $totalNhapHoc = $stmtNH->fetchColumn();
        
        $stmtMajor = $this->db->prepare("
            SELECT n.ten_nganh, n.ma_nganh, 
                   COUNT(kq.id) as trung_tuyen,
                   SUM(CASE WHEN nh.id IS NOT NULL THEN 1 ELSE 0 END) as nhap_hoc
            FROM ket_qua_trung_tuyen kq
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            LEFT JOIN nhap_hoc nh ON kq.id = nh.ket_qua_id
            WHERE kq.session_id = ?
            GROUP BY n.ten_nganh, n.ma_nganh
            ORDER BY nhap_hoc DESC
        ");
        $stmtMajor->execute([$sessionId]);
        $statsByMajor = $stmtMajor->fetchAll(PDO::FETCH_ASSOC);

        $stmtRecent = $this->db->prepare("
            SELECT nh.*, kq.ho_ten, kq.so_cccd, n.ten_nganh
            FROM nhap_hoc nh
            JOIN ket_qua_trung_tuyen kq ON nh.ket_qua_id = kq.id
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            WHERE nh.session_id = ?
            ORDER BY nh.ngay_nhap_hoc DESC
            LIMIT 10
        ");
        $stmtRecent->execute([$sessionId]);
        $recent = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/enrollment/stats', [
            'title' => 'Thống kê Nhập học',
            'sessions' => $sessions,
            'currentSessionId' => $sessionId,
            'totalTrungTuyen' => $totalTrungTuyen,
            'totalNhapHoc' => $totalNhapHoc,
            'statsByMajor' => $statsByMajor,
            'recent' => $recent
        ]);
    }
    public function apiStats() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $sessionId = intval($_GET['session_id'] ?? 0);
        if (!$sessionId) {
            echo json_encode(['success' => false, 'message' => 'Missing session_id']);
            return;
        }

        $stmtTT = $this->db->prepare("SELECT COUNT(*) FROM ket_qua_trung_tuyen WHERE session_id = ?");
        $stmtTT->execute([$sessionId]);
        $totalTrungTuyen = $stmtTT->fetchColumn();

        $stmtNH = $this->db->prepare("SELECT COUNT(*) FROM nhap_hoc WHERE session_id = ? AND trang_thai = 'da_nhap_hoc'");
        $stmtNH->execute([$sessionId]);
        $totalNhapHoc = $stmtNH->fetchColumn();

        $stmtCD = $this->db->prepare("SELECT COUNT(*) FROM nhap_hoc WHERE session_id = ? AND trang_thai = 'cho_xet_duyet'");
        $stmtCD->execute([$sessionId]);
        $choDuyet = $stmtCD->fetchColumn();

        $stmtDH = $this->db->prepare("SELECT COUNT(*) FROM nhap_hoc WHERE session_id = ? AND trang_thai = 'da_huy'");
        $stmtDH->execute([$sessionId]);
        $daHuy = $stmtDH->fetchColumn();
        
        $conChiTieu = max(0, $totalTrungTuyen - $totalNhapHoc);

        echo json_encode([
            'success' => true,
            'data' => [
                'tong_thi_sinh' => $totalTrungTuyen,
                'da_nhap_hoc' => $totalNhapHoc,
                'cho_xet_duyet' => $choDuyet,
                'da_huy' => $daHuy,
                'con_chi_tieu' => $conChiTieu
            ]
        ]);
    }

    public function apiListEnrolled() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $sessionId = $_GET['session_id'] ?? 0;
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $stmt = $this->db->prepare("
            SELECT nh.id as nhap_hoc_id, kq.ho_ten, kq.so_cccd, kq.ma_nganh, n.ten_nganh, nh.ngay_nhap_hoc, nh.trang_thai, nh.ket_qua_id
            FROM nhap_hoc nh
            JOIN ket_qua_trung_tuyen kq ON nh.ket_qua_id = kq.id
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            WHERE nh.session_id = ? 
            ORDER BY nh.updated_at DESC, nh.ngay_nhap_hoc DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$sessionId, $limit, $offset]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $docStmt = $this->db->prepare("SELECT id, ten_ho_so FROM nhap_hoc_ho_so WHERE session_id = ? ORDER BY thu_tu ASC");
        $docStmt->execute([$sessionId]);
        $requiredDocs = $docStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $nhIds = array_column($rows, 'nhap_hoc_id');
            $inQuery = implode(',', array_fill(0, count($nhIds), '?'));
            $valStmt = $this->db->prepare("SELECT nhap_hoc_id, ho_so_id, gia_tri FROM nhap_hoc_ho_so_gia_tri WHERE nhap_hoc_id IN ($inQuery)");
            $valStmt->execute($nhIds);
            $values = $valStmt->fetchAll(PDO::FETCH_ASSOC);

            $valMap = [];
            foreach ($values as $v) {
                if (!empty($v['gia_tri'])) {
                    $valMap[$v['nhap_hoc_id']][$v['ho_so_id']] = true;
                }
            }

            foreach ($rows as &$r) {
                $r['documents'] = [];
                foreach ($requiredDocs as $doc) {
                    $r['documents'][] = [
                        'id' => $doc['id'],
                        'ten_ho_so' => $doc['ten_ho_so'],
                        'submitted' => isset($valMap[$r['nhap_hoc_id']][$doc['id']])
                    ];
                }
            }
        }

        foreach($rows as &$r) {
            $r['ngay_nhap_hoc_format'] = date('d/m/Y', strtotime($r['ngay_nhap_hoc']));
        }

        $stmtTotal = $this->db->prepare("SELECT COUNT(*) FROM nhap_hoc WHERE session_id = ?");
        $stmtTotal->execute([$sessionId]);
        $total = $stmtTotal->fetchColumn();

        echo json_encode([
            'success' => true,
            'data' => $rows,
            'total' => $total,
            'last_page' => ceil($total / $limit)
        ]);
    }
}
