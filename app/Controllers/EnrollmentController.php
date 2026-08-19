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
        $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (strpos($currentUri, '/admin/enrollment/overview-stats') !== false) {
             if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, 'stats')) {
                 $this->redirect(url('/admin/dashboard'));
             }
        } else {
            if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, 'admission.view') &&
                !\App\Models\QuanTriVien::hasPermission($this->currentUser, 'admission.edit') &&
                !\App\Models\QuanTriVien::hasPermission($this->currentUser, 'enrollment.process')) {
                $this->redirect(url('/admin/dashboard'));
            }
        }

        $this->masterData = new MasterData();
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }

    private function getDefaultSession($sessions) {
        if (empty($sessions)) return 0;
        foreach ($sessions as $s) {
            if (stripos($s['ten_dot'], 'đợt 1') !== false) {
                return $s['id'];
            }
        }
        return $sessions[0]['id'];
    }

    public function setup() {
        $sessions = $this->masterData->getSessions();
        $sessionId = $_GET['session_id'] ?? $this->getDefaultSession($sessions);

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
        $sessionId = $_GET['session_id'] ?? $this->getDefaultSession($sessions);

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
            'qrConfig' => $qrConfig,
            'currentUser' => $this->currentUser
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
                   kq.khu_vuc as khu_vuc_kq, kq.doi_tuong as doi_tuong_kq, kq.to_hop, kq.diem_to_hop, kq.phuong_thuc, kq.xac_nhan_bo, kq.xac_nhan_truong, kq.xac_nhan_kinh_phi,
                   kq.so_tai_khoan, kq.ngan_hang, kq.so_tien, kq.noi_dung_ck,
                   ts.gioi_tinh, ts.dan_toc, ts.anh_dai_dien, ts.dia_chi_chi_tiet, ts.nam_tot_nghiep, ts.dien_thoai as dien_thoai_ts, ts.email as email_ts, ts.ma_truong_lop_12,
                   tr.ten_truong as ten_truong_thpt,
                   m1.ma_mon as m1, m2.ma_mon as m2, m3.ma_mon as m3,
                   nh.id as nhap_hoc_id, nh.trang_thai as trang_thai_nhap_hoc, nh.da_nop_tien, nh.ma_phieu, nh.ghi_chu_can_bo,
                   qv.ho_ten as ten_can_bo_nhap, nh.updated_at as thoi_gian_nhap_hoc
            FROM ket_qua_trung_tuyen kq
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
            LEFT JOIN dm_truong_thpt tr ON ts.ma_truong_lop_12 = tr.ma_truong
            LEFT JOIN dm_to_hop th ON kq.to_hop = th.ma_to_hop
            LEFT JOIN dm_mon m1 ON th.mon_1_id = m1.id
            LEFT JOIN dm_mon m2 ON th.mon_2_id = m2.id
            LEFT JOIN dm_mon m3 ON th.mon_3_id = m3.id
            LEFT JOIN nhap_hoc nh ON kq.id = nh.ket_qua_id AND nh.session_id = ?
            LEFT JOIN quan_tri_vien qv ON nh.nguoi_nhap = qv.id
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
        foreach ($documents as &$d) {
            if (strpos($d['cac_gia_tri'], '[') === 0) {
                $arr = json_decode($d['cac_gia_tri'], true);
                if (is_array($arr)) {
                    $d['cac_gia_tri'] = implode(', ', $arr);
                }
            }
        }

        // Process extra fields
        foreach ($candidates as &$candidate) {
            // Standardize booleans for AlpineJS
            $candidate['xac_nhan_bo'] = in_array($candidate['xac_nhan_bo'], [true, 1, '1', 'true', 't', 'T'], true) || strtolower($candidate['xac_nhan_bo']) === 'true' || strtolower($candidate['xac_nhan_bo']) === 't';
            $candidate['xac_nhan_truong'] = in_array($candidate['xac_nhan_truong'], [true, 1, '1', 'true', 't', 'T'], true) || strtolower($candidate['xac_nhan_truong']) === 'true' || strtolower($candidate['xac_nhan_truong']) === 't';
            $candidate['xac_nhan_kinh_phi'] = in_array($candidate['xac_nhan_kinh_phi'], [true, 1, '1', 'true', 't', 'T'], true) || strtolower($candidate['xac_nhan_kinh_phi']) === 'true' || strtolower($candidate['xac_nhan_kinh_phi']) === 't';

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
                    } else {
                        $doc['selected_value'] = $doc['gia_tri_mac_dinh'];
                        $doc['ghi_chu_val'] = '';
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
        
        $kqBo = isset($_POST['xac_nhan_bo']) ? (int)$_POST['xac_nhan_bo'] : 0;
        $kqTruong = isset($_POST['xac_nhan_truong']) ? (int)$_POST['xac_nhan_truong'] : 0;
        $kqKinhPhi = isset($_POST['xac_nhan_kinh_phi']) ? (int)$_POST['xac_nhan_kinh_phi'] : 0;

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

            $daNopTien = $kqKinhPhi === 1 ? 'true' : 'false';

            if ($existing) {
                $nhapHocId = $existing['id'];
                $upd = $this->db->prepare("UPDATE nhap_hoc SET trang_thai = ?, nguoi_nhap = ?, ghi_chu_can_bo = ?, updated_at = CURRENT_TIMESTAMP, da_nop_tien = ? WHERE id = ?");
                $upd->execute([$trangThai, $adminId, $ghiChuCanBo, $daNopTien, $nhapHocId]);
                $del = $this->db->prepare("DELETE FROM nhap_hoc_ho_so_gia_tri WHERE nhap_hoc_id = ?");
                $del->execute([$nhapHocId]);
            } else {
                $year = date('Y');
                
                // Prevent race conditions when generating ma_phieu by locking the table for concurrent inserts
                $this->db->exec("LOCK TABLE nhap_hoc IN SHARE ROW EXCLUSIVE MODE");
                
                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM nhap_hoc WHERE session_id = ?");
                $countStmt->execute([$sessionId]);
                $count = $countStmt->fetchColumn() + 1;
                $maPhieu = "NH{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);

                $ins = $this->db->prepare("INSERT INTO nhap_hoc (ket_qua_id, session_id, so_cccd, nguoi_nhap, ma_phieu, trang_thai, ghi_chu_can_bo, da_nop_tien) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id");
                $ins->execute([$ketQuaId, $sessionId, $soCccd, $adminId, $maPhieu, $trangThai, $ghiChuCanBo, $daNopTien]);
                $nhapHocId = $ins->fetchColumn();
            }

            // Update ket_qua_trung_tuyen booleans
            if ($kqKinhPhi === 0) {
                $updKq = $this->db->prepare("UPDATE ket_qua_trung_tuyen SET xac_nhan_bo = ?, xac_nhan_truong = ?, xac_nhan_kinh_phi = ?, so_tien = 0 WHERE id = ?");
                $updKq->execute([
                    $kqBo === 1 ? 'true' : 'false', 
                    $kqTruong === 1 ? 'true' : 'false', 
                    $kqKinhPhi === 1 ? 'true' : 'false', 
                    $ketQuaId
                ]);
            } else {
                $updKq = $this->db->prepare("UPDATE ket_qua_trung_tuyen SET xac_nhan_bo = ?, xac_nhan_truong = ?, xac_nhan_kinh_phi = ? WHERE id = ?");
                $updKq->execute([
                    $kqBo === 1 ? 'true' : 'false', 
                    $kqTruong === 1 ? 'true' : 'false', 
                    $kqKinhPhi === 1 ? 'true' : 'false', 
                    $ketQuaId
                ]);
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
            SELECT nh.*, kq.ho_ten, kq.ngay_sinh, kq.so_cccd, kq.sdt as dien_thoai, kq.ma_nganh, n.ten_nganh, n.thong_tin_gv_ho_tro, kq.sbd, kq.to_hop, kq.diem_xt, kq.xac_nhan_bo, kq.xac_nhan_truong, kq.xac_nhan_kinh_phi, kq.so_giay_bao, kq.gvcn,
                   qv.ho_ten as ten_can_bo, ts.anh_dai_dien
            FROM nhap_hoc nh
            JOIN ket_qua_trung_tuyen kq ON nh.ket_qua_id = kq.id
            LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            LEFT JOIN quan_tri_vien qv ON nh.nguoi_nhap = qv.id
            WHERE nh.id = ?
        ");
        $stmt->execute([$nhapHocId]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) {
            die("Không tìm thấy thông tin nhập học.");
        }

        // Calculate GT from CCCD
        $gioiTinh = '';
        if (!empty($enrollment['so_cccd']) && strlen($enrollment['so_cccd']) == 12) {
            $genderCode = (int)substr($enrollment['so_cccd'], 3, 1);
            $gioiTinh = ($genderCode % 2 == 0) ? 'Nam' : 'Nữ';
        }

        // Fetch documents for the template
        $valStmt = $this->db->prepare("
            SELECT v.gia_tri, v.ghi_chu, h.ten_ho_so, h.thu_tu 
            FROM nhap_hoc_ho_so_gia_tri v
            JOIN nhap_hoc_ho_so h ON v.ho_so_id = h.id
            WHERE v.nhap_hoc_id = ?
            ORDER BY h.thu_tu ASC
        ");
        $valStmt->execute([$nhapHocId]);
        $documents = $valStmt->fetchAll(PDO::FETCH_ASSOC);

        $hs_giay_cn = 'Chưa nộp';
        $hs_hoc_ba = 'Chưa nộp';
        foreach ($documents as $doc) {
            $docName = mb_strtolower($doc['ten_ho_so'], 'UTF-8');
            if (strpos($docName, 'giấy chứng nhận') !== false || strpos($docName, 'giay chung nhan') !== false || strpos($docName, 'giấy cn') !== false || strpos($docName, 'giay cn') !== false) {
                $hs_giay_cn = $doc['gia_tri'];
            }
            if (strpos($docName, 'học bạ') !== false || strpos($docName, 'hoc ba') !== false) {
                $hs_hoc_ba = $doc['gia_tri'];
            }
        }

        // Check if there is an uploaded Word template in mau_in (priority 1)
        $filePath = null;
        try {
            $stmtTplOld = $this->db->prepare("SELECT file_path FROM mau_in WHERE ma_mau = 'PHIEU_NHAP_HOC'");
            $stmtTplOld->execute();
            $filePath = $stmtTplOld->fetchColumn();
        } catch (\PDOException $e) {
            $filePath = null;
        }

        // Fallback to mau_phieu if mau_in is empty
        if (!$filePath) {
            try {
                $stmtTpl = $this->db->prepare("SELECT ten_file FROM mau_phieu WHERE loai_mau = 'phieu_nhap_hoc' AND is_active = TRUE ORDER BY created_at DESC LIMIT 1");
                $stmtTpl->execute();
                $filePath = $stmtTpl->fetchColumn();
            } catch (\PDOException $e) {
                // Table mau_phieu might not exist yet
                $filePath = null;
            }
        }

        $type = $_GET['type'] ?? 'html';

        if ($type === 'word') {
            if (!$filePath) {
                die("Debug Local: Bạn chưa cấu hình mẫu in Word (Bảng mau_in và mau_phieu đều trống). Vui lòng upload file mẫu lên Local.");
            }

            if ($filePath) {
                $templatePath = __DIR__ . '/../../storage/templates/' . $filePath;
                if (!file_exists($templatePath)) {
                    die("Debug Local: File $templatePath không tồn tại trên ổ cứng. Vui lòng upload lại file mẫu lên Local.");
                }
                
                try {
                    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

                    $templateProcessor->setValue('hoten', htmlspecialchars($enrollment['ho_ten'] ?? ''));
                    
                    // Fix ngay_sinh format (handle both Y-m-d and d/m/Y)
                    $ngaySinh = trim($enrollment['ngay_sinh'] ?? '');
                    $ngaySinhStr = '';
                    if (!empty($ngaySinh)) {
                        if (strpos($ngaySinh, '/') !== false) {
                            $parsedDate = strtotime(str_replace('/', '-', $ngaySinh));
                            $ngaySinhStr = $parsedDate ? date('d/m/Y', $parsedDate) : htmlspecialchars($ngaySinh);
                        } else {
                            $parsedDate = strtotime($ngaySinh);
                            $ngaySinhStr = $parsedDate ? date('d/m/Y', $parsedDate) : htmlspecialchars($ngaySinh);
                        }
                    }
                    $templateProcessor->setValue('ngay_sinh', $ngaySinhStr);
                    
                    $templateProcessor->setValue('gioi_tinh', $gioiTinh);
                    $templateProcessor->setValue('so_cccd', htmlspecialchars($enrollment['so_cccd'] ?? ''));
                    $templateProcessor->setValue('dien_thoai', htmlspecialchars($enrollment['dien_thoai'] ?? ''));
                    $templateProcessor->setValue('sbd', htmlspecialchars($enrollment['sbd'] ?? ''));
                    $templateProcessor->setValue('nganh', htmlspecialchars($enrollment['ten_nganh'] ?? ''));
                    $templateProcessor->setValue('ma_nganh', htmlspecialchars($enrollment['ma_nganh'] ?? ''));
                    $templateProcessor->setValue('khoi', htmlspecialchars($enrollment['to_hop'] ?? ''));
                    $templateProcessor->setValue('diem_tong', htmlspecialchars($enrollment['diem_xt'] ?? ''));
                    $templateProcessor->setValue('gvcn', htmlspecialchars($enrollment['gvcn'] ?? ''));
                    
                    $isBo = in_array($enrollment['xac_nhan_bo'], [true, 1, '1', 'true', 't', 'T'], true) || strtolower($enrollment['xac_nhan_bo']) === 'true' || strtolower($enrollment['xac_nhan_bo']) === 't';
                    $isTruong = in_array($enrollment['xac_nhan_truong'], [true, 1, '1', 'true', 't', 'T'], true) || strtolower($enrollment['xac_nhan_truong']) === 'true' || strtolower($enrollment['xac_nhan_truong']) === 't';
                    $isNopTien = in_array($enrollment['da_nop_tien'] ?? $enrollment['xac_nhan_kinh_phi'], [true, 1, '1', 'true', 't', 'T'], true) || strtolower((string)($enrollment['da_nop_tien'] ?? $enrollment['xac_nhan_kinh_phi'])) === 'true' || strtolower((string)($enrollment['da_nop_tien'] ?? $enrollment['xac_nhan_kinh_phi'])) === 't';

                    $templateProcessor->setValue('xac_nhan_bo', $isBo ? 'Đã xác nhận' : 'Chưa xác nhận');
                    $templateProcessor->setValue('xac_nhan_truong', $isTruong ? 'Đã xác nhận' : 'Chưa xác nhận');
                    $templateProcessor->setValue('nop_kinh_phi', $isNopTien ? 'Đã nộp' : 'Chưa nộp');
                    $templateProcessor->setValue('so_giay_bao', htmlspecialchars($enrollment['so_giay_bao'] ?? ''));
                    
                    $templateProcessor->setValue('hs_giay_cn', htmlspecialchars($hs_giay_cn));
                    $templateProcessor->setValue('hs_hoc_ba', htmlspecialchars($hs_hoc_ba));

                    // Các biến về thời gian in
                    $templateProcessor->setValue('ngay_in', date('d/m/Y'));
                    $templateProcessor->setValue('ngay', date('d'));
                    $templateProcessor->setValue('thang', date('m'));
                    $templateProcessor->setValue('nam', date('Y'));

                    // Process Avatar
                    $avatarTempFile = '';
                    $avatarPath = $enrollment['anh_dai_dien'] ?? '';
                    if (!empty($avatarPath)) {
                        try {
                            if (strpos($avatarPath, 'http') === 0) {
                                $avatarData = @file_get_contents($avatarPath);
                                if ($avatarData) {
                                    $avatarTempFile = tempnam(sys_get_temp_dir(), 'AVT_') . '.png';
                                    file_put_contents($avatarTempFile, $avatarData);
                                    $templateProcessor->setImageValue('anh_the', array('path' => $avatarTempFile, 'width' => 90, 'height' => 120, 'ratio' => false));
                                } else {
                                    $templateProcessor->setValue('anh_the', '');
                                }
                            } else {
                                // Local file
                                $localPath = dirname(__DIR__, 2) . '/' . ltrim($avatarPath, '/');
                                if (file_exists($localPath)) {
                                    $templateProcessor->setImageValue('anh_the', array('path' => $localPath, 'width' => 90, 'height' => 120, 'ratio' => false));
                                } else {
                                    $templateProcessor->setValue('anh_the', '');
                                }
                            }
                        } catch (\Exception $e) {
                            $templateProcessor->setValue('anh_the', '');
                        }
                    } else {
                        $templateProcessor->setValue('anh_the', '');
                    }

                    // Process QR Code
                    $qrTempFile = '';
                    try {
                        $cccdStr = !empty($enrollment['so_cccd']) ? $enrollment['so_cccd'] : 'NO_CCCD';
                        $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($cccdStr);
                        $qrData = @file_get_contents($qrApiUrl);
                        if ($qrData) {
                            $qrTempFile = tempnam(sys_get_temp_dir(), 'QR_') . '.png';
                            file_put_contents($qrTempFile, $qrData);
                            $templateProcessor->setImageValue('qr_cccd', array('path' => $qrTempFile, 'width' => 75, 'height' => 75, 'ratio' => false));
                        } else {
                            $templateProcessor->setValue('qr_cccd', '');
                        }
                    } catch (\Exception $e) {
                        $templateProcessor->setValue('qr_cccd', '');
                    }

                    // Save temp file and download
                    $tempFile = tempnam(sys_get_temp_dir(), 'PHIEU_');
                    $templateProcessor->saveAs($tempFile);

                    $downloadName = 'PhieuNhapHoc_' . str_replace(' ', '', $enrollment['so_cccd'] ?? $nhapHocId) . '.docx';

                    if (ob_get_length()) ob_clean();
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($tempFile));
                    readfile($tempFile);
                    unlink($tempFile);
                    if (!empty($qrTempFile) && file_exists($qrTempFile)) {
                        unlink($qrTempFile);
                    }
                    if (!empty($avatarTempFile) && file_exists($avatarTempFile)) {
                        unlink($avatarTempFile);
                    }
                    exit;
                } catch (\Throwable $e) {
                    die("Lỗi khi tạo file Word: " . $e->getMessage() . ". Vui lòng kiểm tra lại file mẫu xem có hợp lệ không (có thể là file .doc đổi đuôi, hoặc lỗi biến trong file).");
                }
            }
        }

        // Fallback to HTML
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

    public function overviewStats() {
        return $this->stats(true);
    }

    public function stats($isReadOnly = false) {
        $sessions = $this->masterData->getSessions();
        $sessionId = intval($_GET['session_id'] ?? $this->getDefaultSession($sessions));

        // Get basic stats
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM ket_qua_loc_ao_bo_gd WHERE dot_tuyen_sinh_id = ?");
        $stmtCheck->execute([$sessionId]);
        $hasBgd = ((int)$stmtCheck->fetchColumn()) > 0;

        $admitCond = $hasBgd 
            ? "cs.trang_thai_trung_tuyen = TRUE AND COALESCE(cs.ket_qua_bo_gd_du_kien, cs.ket_qua_bo_gd) = 'Đỗ'" 
            : "cs.trang_thai_trung_tuyen = TRUE";

        $doBoExpr = $hasBgd 
            ? "COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE AND COALESCE(cs.ket_qua_bo_gd_du_kien, cs.ket_qua_bo_gd) = 'Đỗ' THEN 1 END)" 
            : "COUNT(CASE WHEN cs.trang_thai_trung_tuyen = TRUE THEN 1 END)";

        $stmtTT = $this->db->prepare("SELECT COUNT(*) FROM ket_qua_trung_tuyen WHERE session_id = ?");
        $stmtTT->execute([$sessionId]);
        $totalTrungTuyen = $stmtTT->fetchColumn();

        $stmtNH = $this->db->prepare("SELECT COUNT(*) FROM nhap_hoc WHERE session_id = ? AND trang_thai = 'da_nhap_hoc'");
        $stmtNH->execute([$sessionId]);
        $totalNhapHoc = $stmtNH->fetchColumn();
        
        // 1. Lấy danh sách ngành
        $stmtMajor = $this->db->prepare("
            SELECT n.ten_nganh, n.ma_nganh, n.chi_tieu,
                   (
                       SELECT COUNT(*)
                       FROM ket_qua_trung_tuyen k
                       WHERE k.ma_nganh = n.ma_nganh AND k.session_id = ?
                   ) as so_trung_tuyen,
                   COALESCE(kq_stats.xac_nhan_bo, 0) as xac_nhan_bo,
                   COALESCE(kq_stats.xac_nhan_truong, 0) as xac_nhan_truong,
                   COALESCE(kq_stats.xac_nhan_kinh_phi, 0) as xac_nhan_kinh_phi,
                   COALESCE(kq_stats.tong_kinh_phi_du_kien, 0) as tong_kinh_phi_du_kien,
                   COALESCE(nh_stats.so_nhap_hoc, 0) as so_nhap_hoc,
                   nh_stats.thu_khoa_nganh,
                   COALESCE(nh_stats.tong_kinh_phi, 0) as tong_kinh_phi
            FROM dm_nganh n
            LEFT JOIN (
                SELECT kq.ma_nganh,
                       COUNT(nh.id) as so_nhap_hoc,
                       MAX(kq.diem_xt) as thu_khoa_nganh,
                       SUM(nh.so_tien_da_nop) as tong_kinh_phi
                FROM ket_qua_trung_tuyen kq
                JOIN nhap_hoc nh ON kq.id = nh.ket_qua_id
                WHERE kq.session_id = ? AND nh.trang_thai = 'da_nhap_hoc'
                GROUP BY kq.ma_nganh
            ) nh_stats ON n.ma_nganh = nh_stats.ma_nganh
            LEFT JOIN (
                SELECT ma_nganh,
                       SUM(CASE WHEN xac_nhan_bo = true OR xac_nhan_bo::text = '1' THEN 1 ELSE 0 END) as xac_nhan_bo,
                       SUM(CASE WHEN xac_nhan_truong = true OR xac_nhan_truong::text = '1' THEN 1 ELSE 0 END) as xac_nhan_truong,
                       SUM(CASE WHEN xac_nhan_kinh_phi = true OR xac_nhan_kinh_phi::text = '1' THEN 1 ELSE 0 END) as xac_nhan_kinh_phi,
                       SUM(CASE WHEN xac_nhan_truong = true OR xac_nhan_kinh_phi = true THEN COALESCE(so_tien, 0) ELSE 0 END) as tong_kinh_phi_du_kien
                FROM ket_qua_trung_tuyen
                WHERE session_id = ?
                GROUP BY ma_nganh
            ) kq_stats ON n.ma_nganh = kq_stats.ma_nganh
            ORDER BY nh_stats.so_nhap_hoc DESC NULLS LAST, so_trung_tuyen DESC
        ");
        $stmtMajor->execute([$sessionId, $sessionId, $sessionId]);
        $statsByMajor = $stmtMajor->fetchAll(PDO::FETCH_ASSOC);

        // Lấy Thủ khoa trường (Bất kể trạng thái)
        $stmtTop = $this->db->prepare("
            SELECT kq.ho_ten, kq.diem_xt, n.ten_nganh,
                   CASE 
                       WHEN nh.trang_thai = 'da_nhap_hoc' THEN 'Đã nhập học'
                       WHEN nh.trang_thai = 'cho_xet_duyet' THEN 'Chờ xét duyệt'
                       WHEN nh.trang_thai = 'da_huy' THEN 'Đã hủy'
                       ELSE 'Chưa nhập học'
                   END as tinh_trang
            FROM ket_qua_trung_tuyen kq
            LEFT JOIN nhap_hoc nh ON kq.id = nh.ket_qua_id
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            WHERE kq.session_id = ?
            ORDER BY kq.diem_xt DESC
            LIMIT 1
        ");
        $stmtTop->execute([$sessionId]);
        $topStudent = $stmtTop->fetch(PDO::FETCH_ASSOC);

        // 2. Dữ liệu cho biểu đồ (Chỉ lấy thí sinh đã nhập học)
        $demoSql = "SELECT t.gioi_tinh, t.khu_vuc_uu_tien, t.doi_tuong_uu_tien, 
                           COALESCE(dt.ten_tinh, t.ma_tinh_lop_12) as ten_tinh, 
                           COALESCE(dthpt.ten_truong, t.ma_truong_lop_12) as ten_truong,
                           k.xac_nhan_bo, nh.da_nop_tien
                    FROM nhap_hoc nh
                    JOIN ket_qua_trung_tuyen k ON nh.ket_qua_id = k.id
                    JOIN thi_sinh t ON k.so_cccd = t.so_cccd
                    LEFT JOIN dm_tinh dt ON t.ma_tinh_lop_12 = dt.ma_tinh
                    LEFT JOIN dm_truong_thpt dthpt ON t.ma_truong_lop_12 = dthpt.ma_truong AND dthpt.is_active = TRUE
                    WHERE nh.session_id = ? AND nh.trang_thai = 'da_nhap_hoc'";
        $demoStmt = $this->db->prepare($demoSql);
        $demoStmt->execute([$sessionId]);
        $demoRows = $demoStmt->fetchAll(PDO::FETCH_ASSOC);

        $chartDist = [
            'gender' => [],
            'area' => [],
            'object' => [],
            'province' => [],
            'school' => [],
            'xn_bo' => ['Đã xác nhận' => 0, 'Chưa xác nhận' => 0],
            'kinh_phi' => ['Đã nộp' => 0, 'Chưa nộp' => 0]
        ];

        foreach ($demoRows as $r) {
            $gt = trim($r['gioi_tinh'] ?? '');
            if (strcasecmp($gt, 'Nam') === 0 || $gt === '1') $gt = 'Nam';
            elseif (strcasecmp($gt, 'Nữ') === 0 || strcasecmp($gt, 'Nu') === 0 || $gt === '0') $gt = 'Nữ';
            else $gt = 'Khác';
            $chartDist['gender'][$gt] = ($chartDist['gender'][$gt] ?? 0) + 1;
            
            $ar = $r['khu_vuc_uu_tien'] ?: 'Khác';
            $chartDist['area'][$ar] = ($chartDist['area'][$ar] ?? 0) + 1;

            $obj = $r['doi_tuong_uu_tien'] ?: 'Không';
            $chartDist['object'][$obj] = ($chartDist['object'][$obj] ?? 0) + 1;

            $prov = $r['ten_tinh'] ?: 'Khác';
            $chartDist['province'][$prov] = ($chartDist['province'][$prov] ?? 0) + 1;

            $sch = $r['ten_truong'] ?: 'Khác';
            $chartDist['school'][$sch] = ($chartDist['school'][$sch] ?? 0) + 1;

            if (isset($r['xac_nhan_bo']) && $r['xac_nhan_bo'] == 1) {
                $chartDist['xn_bo']['Đã xác nhận']++;
            } else {
                $chartDist['xn_bo']['Chưa xác nhận']++;
            }

            if (isset($r['da_nop_tien']) && $r['da_nop_tien'] == 1) {
                $chartDist['kinh_phi']['Đã nộp']++;
            } else {
                $chartDist['kinh_phi']['Chưa nộp']++;
            }
        }

        arsort($chartDist['province']);
        arsort($chartDist['school']);

        $chartDist['users'] = [];
        $stmtUsers = $this->db->prepare("
            SELECT qv.ho_ten, COUNT(nh.id) as so_luong
            FROM nhap_hoc nh
            JOIN quan_tri_vien qv ON nh.nguoi_nhap = qv.id
            WHERE nh.session_id = ? AND nh.trang_thai = 'da_nhap_hoc'
            GROUP BY qv.ho_ten
            ORDER BY so_luong DESC
        ");
        $stmtUsers->execute([$sessionId]);
        $userRows = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
        foreach ($userRows as $r) {
            $chartDist['users'][$r['ho_ten']] = (int)$r['so_luong'];
        }

        // 3. Dữ liệu thí sinh nhập học theo ngày
        $stmtDaily = $this->db->prepare("
            SELECT DATE(ngay_nhap_hoc) as date, COUNT(*) as count
            FROM nhap_hoc
            WHERE session_id = ? AND trang_thai = 'da_nhap_hoc'
            GROUP BY DATE(ngay_nhap_hoc)
            ORDER BY date ASC
        ");
        $stmtDaily->execute([$sessionId]);
        $dailyRows = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);
        
        $chartDist['daily_enrollment'] = $dailyRows;

        $stmtHourly = $this->db->prepare("
            SELECT TO_CHAR(ngay_nhap_hoc, 'HH24:00') as hour, COUNT(*) as count
            FROM nhap_hoc
            WHERE session_id = ? AND trang_thai = 'da_nhap_hoc'
            GROUP BY TO_CHAR(ngay_nhap_hoc, 'HH24:00')
            ORDER BY hour ASC
        ");
        $stmtHourly->execute([$sessionId]);
        $chartDist['hourly_enrollment'] = $stmtHourly->fetchAll(PDO::FETCH_ASSOC);

        // Gần đây (5 hồ sơ)
        $stmtRecent = $this->db->prepare("
            SELECT nh.*, kq.ho_ten, kq.so_cccd, n.ten_nganh, qv.ho_ten as ten_can_bo
            FROM nhap_hoc nh
            JOIN ket_qua_trung_tuyen kq ON nh.ket_qua_id = kq.id
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            LEFT JOIN quan_tri_vien qv ON nh.nguoi_nhap = qv.id
            WHERE nh.session_id = ? AND nh.trang_thai = 'da_nhap_hoc'
            ORDER BY nh.updated_at DESC
            LIMIT 5
        ");
        $stmtRecent->execute([$sessionId]);
        $recent = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

        // Fetch kiosk search count from the database
        $this->db->exec("CREATE TABLE IF NOT EXISTS kiosk_lookups (
            id SERIAL PRIMARY KEY,
            kiosk_id VARCHAR(100) NOT NULL,
            so_cccd VARCHAR(50) NOT NULL,
            session_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $stmtKiosk = $this->db->prepare("SELECT COUNT(*) FROM kiosk_lookups WHERE session_id = ?");
        $stmtKiosk->execute([$sessionId]);
        $kioskSearchTotal = (int)$stmtKiosk->fetchColumn();

        // Thống kê theo Cán bộ
        $stmtOfficer = $this->db->prepare("
            SELECT 
                qv.id as can_bo_id,
                qv.ho_ten as ten_can_bo,
                qv.ten_dang_nhap,
                COUNT(nh.id) as so_luong_nhap,
                SUM(CASE WHEN kq.xac_nhan_bo = true OR kq.xac_nhan_bo::text = '1' THEN 1 ELSE 0 END) as xn_bo,
                SUM(CASE WHEN kq.xac_nhan_truong = true OR kq.xac_nhan_truong::text = '1' THEN 1 ELSE 0 END) as xn_truong,
                SUM(CASE WHEN kq.xac_nhan_kinh_phi = true OR kq.xac_nhan_kinh_phi::text = '1' THEN 1 ELSE 0 END) as xn_kinh_phi
            FROM quan_tri_vien qv
            LEFT JOIN nhap_hoc nh ON qv.id = nh.nguoi_nhap AND nh.session_id = ? AND nh.trang_thai = 'da_nhap_hoc'
            LEFT JOIN ket_qua_trung_tuyen kq ON nh.ket_qua_id = kq.id
            WHERE qv.role_id = 4 OR qv.id IN (SELECT DISTINCT nguoi_nhap FROM nhap_hoc WHERE session_id = ? AND trang_thai = 'da_nhap_hoc')
            GROUP BY qv.id, qv.ho_ten, qv.ten_dang_nhap
            ORDER BY qv.ten_dang_nhap ASC
        ");
        $stmtOfficer->execute([$sessionId, $sessionId]);
        $statsByOfficer = $stmtOfficer->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/enrollment/stats', [
            'isReadOnly' => $isReadOnly,
            'title' => 'Thống kê nhập học',
            'totalTrungTuyen' => $totalTrungTuyen,
            'totalNhapHoc' => $totalNhapHoc,
            'statsByMajor' => $statsByMajor,
            'statsByOfficer' => $statsByOfficer,
            'topStudent' => $topStudent,
            'chartDist' => $chartDist,
            'sessions' => $sessions,
            'activeSessionId' => $sessionId,
            'recent' => $recent,
            'kioskSearchTotal' => $kioskSearchTotal,
        ]);
    }

    public function resetKioskLookups() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $roleId = intval($this->currentUser['role_id'] ?? 0);
        if ($roleId === 3) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
            return;
        }

        $sessionId = intval($_POST['session_id'] ?? 0);
        if (!$sessionId) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID đợt tuyển sinh.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM kiosk_lookups WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            echo json_encode(['success' => true, 'message' => 'Đã reset số lượt tra cứu về 0 thành công.']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
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

        $stmtUsers = $this->db->prepare("
            SELECT qv.ho_ten, COUNT(nh.id) as so_luong
            FROM nhap_hoc nh
            JOIN quan_tri_vien qv ON nh.nguoi_nhap = qv.id
            WHERE nh.session_id = ? AND nh.trang_thai = 'da_nhap_hoc'
            GROUP BY qv.ho_ten
            ORDER BY so_luong DESC
        ");
        $stmtUsers->execute([$sessionId]);
        $statsByUser = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        $stmtHourly = $this->db->prepare("
            SELECT TO_CHAR(ngay_nhap_hoc, 'HH24:00') as hour, COUNT(*) as count
            FROM nhap_hoc
            WHERE session_id = ? AND trang_thai = 'da_nhap_hoc'
            GROUP BY TO_CHAR(ngay_nhap_hoc, 'HH24:00')
            ORDER BY hour ASC
        ");
        $stmtHourly->execute([$sessionId]);
        $hourlyStats = $stmtHourly->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'tong_thi_sinh' => $totalTrungTuyen,
                'da_nhap_hoc' => $totalNhapHoc,
                'cho_xet_duyet' => $choDuyet,
                'da_huy' => $daHuy,
                'con_chi_tieu' => $conChiTieu,
                'stats_by_user' => $statsByUser,
                'hourly_stats' => $hourlyStats
            ]
        ]);
    }

    public function apiListEnrolled() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $sessionId = $_GET['session_id'] ?? 0;
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 8;
        $offset = ($page - 1) * $limit;
        
        $adminId = $_SESSION['admin_id'] ?? 0;
        
        $hasViewAll = \App\Models\QuanTriVien::hasPermission($this->currentUser, 'admission.view_all');
        $userCondition = $hasViewAll ? "" : " AND nh.nguoi_nhap = " . intval($adminId);

        $stmt = $this->db->prepare("
            SELECT nh.id as nhap_hoc_id, kq.ho_ten, kq.so_cccd, kq.ma_nganh, n.ten_nganh, nh.ngay_nhap_hoc, nh.trang_thai, nh.ket_qua_id
            FROM nhap_hoc nh
            JOIN ket_qua_trung_tuyen kq ON nh.ket_qua_id = kq.id
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            WHERE nh.session_id = ? AND nh.trang_thai NOT IN ('chua_nhap_hoc', 'da_huy') AND nh.trang_thai IS NOT NULL
            $userCondition
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
                $valMap[$v['nhap_hoc_id']][$v['ho_so_id']] = $v['gia_tri'];
            }

            foreach ($rows as &$r) {
                $r['documents'] = [];
                foreach ($requiredDocs as $doc) {
                    $val = $valMap[$r['nhap_hoc_id']][$doc['id']] ?? 'Chưa nộp';
                    if (empty($val)) $val = 'Chưa nộp';
                    
                    $isSubmitted = !in_array($val, ['Chưa nộp', 'Không có', 'Thiếu']);
                    
                    $r['documents'][] = [
                        'id' => $doc['id'],
                        'ten_ho_so' => $doc['ten_ho_so'],
                        'gia_tri' => $val,
                        'submitted' => $isSubmitted
                    ];
                }
            }
        }

        foreach($rows as &$r) {
            $r['ngay_nhap_hoc_format'] = date('d/m/Y', strtotime($r['ngay_nhap_hoc']));
        }

        $stmtTotal = $this->db->prepare("SELECT COUNT(*) FROM nhap_hoc nh WHERE nh.session_id = ? AND nh.trang_thai NOT IN ('chua_nhap_hoc', 'da_huy') AND nh.trang_thai IS NOT NULL $userCondition");
        $stmtTotal->execute([$sessionId]);
        $total = $stmtTotal->fetchColumn();

        echo json_encode([
            'success' => true,
            'data' => $rows,
            'total' => $total,
            'last_page' => ceil($total / $limit)
        ]);
    }

    public function exportConfirmed() {
        $sessions = $this->masterData->getSessions();
        $sessionId = intval($_GET['session_id'] ?? (count($sessions) > 0 ? $sessions[0]['id'] : 0));

        $stmt = $this->db->prepare("
            SELECT kq.so_cccd, kq.ho_ten, kq.sbd as so_bao_danh, kq.ngay_sinh, 
                   kq.ma_nganh, n.ten_nganh, kq.diem_xt as diem_xet_tuyen, kq.sdt as dien_thoai_kq, kq.email as email_kq,
                   kq.khu_vuc as khu_vuc_kq, kq.doi_tuong as doi_tuong_kq, kq.to_hop, 
                   kq.xac_nhan_bo, kq.xac_nhan_truong, kq.xac_nhan_kinh_phi,
                   ts.gioi_tinh, ts.dan_toc, ts.dia_chi_chi_tiet, ts.dien_thoai as dien_thoai_ts, ts.email as email_ts,
                   tr.ten_truong as ten_truong_thpt
            FROM ket_qua_trung_tuyen kq
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
            LEFT JOIN dm_truong_thpt tr ON ts.ma_truong_lop_12 = tr.ma_truong
            WHERE kq.session_id = ? 
              AND (kq.xac_nhan_bo = true OR kq.xac_nhan_bo::text = '1'
                   OR kq.xac_nhan_truong = true OR kq.xac_nhan_truong::text = '1'
                   OR kq.xac_nhan_kinh_phi = true OR kq.xac_nhan_kinh_phi::text = '1')
            ORDER BY n.ten_nganh ASC, kq.ho_ten ASC
        ");
        $stmt->execute([$sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        $stt = 1;
        foreach ($rows as $r) {
            $ngaySinh = '';
            if (!empty($r['ngay_sinh'])) {
                $time = strtotime($r['ngay_sinh']);
                $ngaySinh = $time ? date('d/m/Y', $time) : $r['ngay_sinh'];
            }

            $data[] = [
                'STT' => $stt++,
                'Số CCCD' => $r['so_cccd'],
                'Họ và tên' => mb_strtoupper($r['ho_ten'] ?? '', 'UTF-8'),
                'Số báo danh' => $r['so_bao_danh'] ?? '',
                'Ngày sinh' => $ngaySinh,
                'Giới tính' => $r['gioi_tinh'] ?? '',
                'Dân tộc' => $r['dan_toc'] ?? '',
                'Điện thoại' => !empty($r['dien_thoai_ts']) ? $r['dien_thoai_ts'] : ($r['dien_thoai_kq'] ?? ''),
                'Email' => !empty($r['email_ts']) ? $r['email_ts'] : ($r['email_kq'] ?? ''),
                'Địa chỉ liên hệ' => $r['dia_chi_chi_tiet'] ?? '',
                'Trường THPT' => $r['ten_truong_thpt'] ?? '',
                'Khu vực' => $r['khu_vuc_kq'] ?? '',
                'Đối tượng' => $r['doi_tuong_kq'] ?? '',
                'Mã ngành trúng tuyển' => $r['ma_nganh'] ?? '',
                'Tên ngành' => $r['ten_nganh'] ?? '',
                'Tổ hợp xét tuyển' => $r['to_hop'] ?? '',
                'Điểm xét tuyển' => floatval($r['diem_xet_tuyen'] ?? 0),
                'Xác nhận Bộ' => (!empty($r['xac_nhan_bo']) && $r['xac_nhan_bo'] !== 'false' && $r['xac_nhan_bo'] != '0') ? 'Đã XN' : 'Chưa XN',
                'Xác nhận Trường' => (!empty($r['xac_nhan_truong']) && $r['xac_nhan_truong'] !== 'false' && $r['xac_nhan_truong'] != '0') ? 'Đã XN' : 'Chưa XN',
                'Xác nhận Kinh phí' => (!empty($r['xac_nhan_kinh_phi']) && $r['xac_nhan_kinh_phi'] !== 'false' && $r['xac_nhan_kinh_phi'] != '0') ? 'Đã XN' : 'Chưa XN',
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'danh_sach_xac_nhan_nhap_hoc_' . $sessionId . '.xls', true);
    }

    public function exportEnrolled() {
        $sessions = $this->masterData->getSessions();
        $sessionId = intval($_GET['session_id'] ?? (count($sessions) > 0 ? $sessions[0]['id'] : 0));

        // Fetch required documents configured for this session
        $docStmt = $this->db->prepare("SELECT id, ten_ho_so FROM nhap_hoc_ho_so WHERE session_id = ? ORDER BY thu_tu ASC");
        $docStmt->execute([$sessionId]);
        $sessionDocs = $docStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all document values for enrolled students in this session
        $valStmt = $this->db->prepare("
            SELECT v.nhap_hoc_id, v.ho_so_id, v.gia_tri, v.ghi_chu
            FROM nhap_hoc_ho_so_gia_tri v
            JOIN nhap_hoc nh ON v.nhap_hoc_id = nh.id
            WHERE nh.session_id = ?
        ");
        $valStmt->execute([$sessionId]);
        $docValues = [];
        while ($v = $valStmt->fetch(PDO::FETCH_ASSOC)) {
            $valText = $v['gia_tri'];
            if (!empty($v['ghi_chu'])) {
                $valText .= " ({$v['ghi_chu']})";
            }
            $docValues[$v['nhap_hoc_id']][$v['ho_so_id']] = $valText;
        }

        $stmt = $this->db->prepare("
            SELECT nh.id as nhap_hoc_id, nh.ma_phieu, nh.ngay_nhap_hoc, nh.da_nop_tien, nh.so_tien_da_nop, nh.ghi_chu_can_bo,
                   kq.so_cccd, kq.ho_ten, kq.sbd as so_bao_danh, kq.ngay_sinh, 
                   kq.ma_nganh, n.ten_nganh, kq.diem_xt as diem_xet_tuyen, kq.sdt as dien_thoai_kq, kq.email as email_kq,
                   kq.khu_vuc as khu_vuc_kq, kq.doi_tuong as doi_tuong_kq, kq.to_hop, 
                   ts.gioi_tinh, ts.dan_toc, ts.dia_chi_chi_tiet, ts.dien_thoai as dien_thoai_ts, ts.email as email_ts,
                   tr.ten_truong as ten_truong_thpt,
                   qv.ho_ten as ten_can_bo
            FROM nhap_hoc nh
            JOIN ket_qua_trung_tuyen kq ON nh.ket_qua_id = kq.id
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
            LEFT JOIN dm_truong_thpt tr ON ts.ma_truong_lop_12 = tr.ma_truong
            LEFT JOIN quan_tri_vien qv ON nh.nguoi_nhap = qv.id
            WHERE nh.session_id = ? 
              AND nh.trang_thai = 'da_nhap_hoc'
            ORDER BY n.ten_nganh ASC, nh.ngay_nhap_hoc DESC, kq.ho_ten ASC
        ");
        $stmt->execute([$sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        $stt = 1;
        foreach ($rows as $r) {
            $extra = [];
            if (!empty($r['ghi_chu_can_bo'])) {
                $extra = json_decode($r['ghi_chu_can_bo'], true) ?: [];
            }
            $lop = $extra['lop'] ?? '';
            $ngayCap = $extra['ngay_cap_cccd'] ?? '';
            $noiCap = $extra['noi_cap_cccd'] ?? '';
            $ghiChuKhac = $extra['ghi_chu_khac'] ?? ($extra['ghi_chu'] ?? '');

            $ngaySinh = '';
            if (!empty($r['ngay_sinh'])) {
                $time = strtotime($r['ngay_sinh']);
                $ngaySinh = $time ? date('d/m/Y', $time) : $r['ngay_sinh'];
            }

            $ngayNhapHoc = '';
            if (!empty($r['ngay_nhap_hoc'])) {
                $time = strtotime($r['ngay_nhap_hoc']);
                $ngayNhapHoc = $time ? date('d/m/Y H:i', $time) : $r['ngay_nhap_hoc'];
            }

            $rowItem = [
                'STT' => $stt++,
                'Mã phiếu nhập học' => $r['ma_phieu'] ?? '',
                'Số CCCD' => $r['so_cccd'],
                'Họ và tên' => mb_strtoupper($r['ho_ten'] ?? '', 'UTF-8'),
                'Số báo danh' => $r['so_bao_danh'] ?? '',
                'Ngày sinh' => $ngaySinh,
                'Giới tính' => $r['gioi_tinh'] ?? '',
                'Dân tộc' => $r['dan_toc'] ?? '',
                'Điện thoại' => !empty($r['dien_thoai_ts']) ? $r['dien_thoai_ts'] : ($r['dien_thoai_kq'] ?? ''),
                'Email' => !empty($r['email_ts']) ? $r['email_ts'] : ($r['email_kq'] ?? ''),
                'Địa chỉ liên hệ' => $r['dia_chi_chi_tiet'] ?? '',
                'Trường THPT' => $r['ten_truong_thpt'] ?? '',
                'Khu vực' => $r['khu_vuc_kq'] ?? '',
                'Đối tượng' => $r['doi_tuong_kq'] ?? '',
                'Mã ngành trúng tuyển' => $r['ma_nganh'] ?? '',
                'Tên ngành' => $r['ten_nganh'] ?? '',
                'Tổ hợp xét tuyển' => $r['to_hop'] ?? '',
                'Điểm xét tuyển' => floatval($r['diem_xet_tuyen'] ?? 0),
                'Lớp' => $lop,
                'Ngày cấp CCCD' => $ngayCap,
                'Nơi cấp CCCD' => $noiCap,
                'Ngày nhập học' => $ngayNhapHoc,
                'Đã nộp kinh phí' => (!empty($r['da_nop_tien']) && $r['da_nop_tien'] !== 'false' && $r['da_nop_tien'] != '0') ? 'Đã nộp' : 'Chưa nộp',
                'Số tiền đã nộp' => floatval($r['so_tien_da_nop'] ?? 0),
            ];

            // Add document columns
            foreach ($sessionDocs as $sd) {
                $rowItem['Hồ sơ: ' . $sd['ten_ho_so']] = $docValues[$r['nhap_hoc_id']][$sd['id']] ?? 'Chưa nộp';
            }

            $rowItem['Cán bộ tiếp nhận'] = $r['ten_can_bo'] ?? '';
            $rowItem['Ghi chú'] = $ghiChuKhac;

            $data[] = $rowItem;
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'danh_sach_nhap_hoc_' . $sessionId . '.xls', true);
    }

    public function exportUnconfirmed() {
        $sessions = $this->masterData->getSessions();
        $sessionId = intval($_GET['session_id'] ?? (count($sessions) > 0 ? $sessions[0]['id'] : 0));

        $stmt = $this->db->prepare("
            SELECT kq.so_cccd, kq.ho_ten, kq.sbd as so_bao_danh, kq.ngay_sinh, 
                   kq.ma_nganh, n.ten_nganh, kq.diem_xt as diem_xet_tuyen, kq.sdt as dien_thoai_kq, kq.email as email_kq,
                   kq.khu_vuc as khu_vuc_kq, kq.doi_tuong as doi_tuong_kq, kq.to_hop, 
                   kq.xac_nhan_bo, kq.xac_nhan_truong, kq.xac_nhan_kinh_phi,
                   ts.gioi_tinh, ts.dan_toc, ts.dia_chi_chi_tiet, ts.dien_thoai as dien_thoai_ts, ts.email as email_ts,
                   tr.ten_truong as ten_truong_thpt
            FROM ket_qua_trung_tuyen kq
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
            LEFT JOIN dm_truong_thpt tr ON ts.ma_truong_lop_12 = tr.ma_truong
            WHERE kq.session_id = ? 
              AND NOT (kq.xac_nhan_bo = true OR kq.xac_nhan_bo::text = '1'
                   OR kq.xac_nhan_truong = true OR kq.xac_nhan_truong::text = '1'
                   OR kq.xac_nhan_kinh_phi = true OR kq.xac_nhan_kinh_phi::text = '1')
            ORDER BY n.ten_nganh ASC, kq.ho_ten ASC
        ");
        $stmt->execute([$sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        $stt = 1;
        foreach ($rows as $r) {
            $ngaySinh = '';
            if (!empty($r['ngay_sinh'])) {
                $time = strtotime($r['ngay_sinh']);
                $ngaySinh = $time ? date('d/m/Y', $time) : $r['ngay_sinh'];
            }

            $data[] = [
                'STT' => $stt++,
                'Số CCCD' => $r['so_cccd'],
                'Họ và tên' => mb_strtoupper($r['ho_ten'] ?? '', 'UTF-8'),
                'Số báo danh' => $r['so_bao_danh'] ?? '',
                'Ngày sinh' => $ngaySinh,
                'Giới tính' => $r['gioi_tinh'] ?? '',
                'Dân tộc' => $r['dan_toc'] ?? '',
                'Điện thoại' => !empty($r['dien_thoai_ts']) ? $r['dien_thoai_ts'] : ($r['dien_thoai_kq'] ?? ''),
                'Email' => !empty($r['email_ts']) ? $r['email_ts'] : ($r['email_kq'] ?? ''),
                'Địa chỉ liên hệ' => $r['dia_chi_chi_tiet'] ?? '',
                'Trường THPT' => $r['ten_truong_thpt'] ?? '',
                'Khu vực' => $r['khu_vuc_kq'] ?? '',
                'Đối tượng' => $r['doi_tuong_kq'] ?? '',
                'Mã ngành trúng tuyển' => $r['ma_nganh'] ?? '',
                'Tên ngành' => $r['ten_nganh'] ?? '',
                'Tổ hợp xét tuyển' => $r['to_hop'] ?? '',
                'Điểm xét tuyển' => floatval($r['diem_xet_tuyen'] ?? 0),
                'Xác nhận Bộ' => (!empty($r['xac_nhan_bo']) && $r['xac_nhan_bo'] !== 'false' && $r['xac_nhan_bo'] != '0') ? 'Đã XN' : 'Chưa XN',
                'Xác nhận Trường' => (!empty($r['xac_nhan_truong']) && $r['xac_nhan_truong'] !== 'false' && $r['xac_nhan_truong'] != '0') ? 'Đã XN' : 'Chưa XN',
                'Xác nhận Kinh phí' => (!empty($r['xac_nhan_kinh_phi']) && $r['xac_nhan_kinh_phi'] !== 'false' && $r['xac_nhan_kinh_phi'] != '0') ? 'Đã XN' : 'Chưa XN',
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'danh_sach_chua_xac_nhan_nhap_hoc_' . $sessionId . '.xls', true);
    }

    public function exportUnenrolled() {
        $sessions = $this->masterData->getSessions();
        $sessionId = intval($_GET['session_id'] ?? (count($sessions) > 0 ? $sessions[0]['id'] : 0));

        $stmt = $this->db->prepare("
            SELECT kq.so_cccd, kq.ho_ten, kq.sbd as so_bao_danh, kq.ngay_sinh, 
                   kq.ma_nganh, n.ten_nganh, kq.diem_xt as diem_xet_tuyen, kq.sdt as dien_thoai_kq, kq.email as email_kq,
                   kq.khu_vuc as khu_vuc_kq, kq.doi_tuong as doi_tuong_kq, kq.to_hop, 
                   kq.xac_nhan_bo, kq.xac_nhan_truong, kq.xac_nhan_kinh_phi,
                   ts.gioi_tinh, ts.dan_toc, ts.dia_chi_chi_tiet, ts.dien_thoai as dien_thoai_ts, ts.email as email_ts,
                   tr.ten_truong as ten_truong_thpt,
                   nh.trang_thai as trang_thai_nhap_hoc
            FROM ket_qua_trung_tuyen kq
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
            LEFT JOIN dm_truong_thpt tr ON ts.ma_truong_lop_12 = tr.ma_truong
            LEFT JOIN nhap_hoc nh ON kq.id = nh.ket_qua_id AND nh.session_id = kq.session_id
            WHERE kq.session_id = ? 
              AND (nh.id IS NULL OR nh.trang_thai != 'da_nhap_hoc')
            ORDER BY n.ten_nganh ASC, kq.ho_ten ASC
        ");
        $stmt->execute([$sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        $stt = 1;
        foreach ($rows as $r) {
            $ngaySinh = '';
            if (!empty($r['ngay_sinh'])) {
                $time = strtotime($r['ngay_sinh']);
                $ngaySinh = $time ? date('d/m/Y', $time) : $r['ngay_sinh'];
            }

            $trangThaiTxt = 'Chưa làm thủ tục';
            if (($r['trang_thai_nhap_hoc'] ?? '') === 'cho_xet_duyet') {
                $trangThaiTxt = 'Chờ xét duyệt';
            } elseif (($r['trang_thai_nhap_hoc'] ?? '') === 'da_huy') {
                $trangThaiTxt = 'Đã hủy';
            }

            $data[] = [
                'STT' => $stt++,
                'Số CCCD' => $r['so_cccd'],
                'Họ và tên' => mb_strtoupper($r['ho_ten'] ?? '', 'UTF-8'),
                'Số báo danh' => $r['so_bao_danh'] ?? '',
                'Ngày sinh' => $ngaySinh,
                'Giới tính' => $r['gioi_tinh'] ?? '',
                'Dân tộc' => $r['dan_toc'] ?? '',
                'Điện thoại' => !empty($r['dien_thoai_ts']) ? $r['dien_thoai_ts'] : ($r['dien_thoai_kq'] ?? ''),
                'Email' => !empty($r['email_ts']) ? $r['email_ts'] : ($r['email_kq'] ?? ''),
                'Địa chỉ liên hệ' => $r['dia_chi_chi_tiet'] ?? '',
                'Trường THPT' => $r['ten_truong_thpt'] ?? '',
                'Khu vực' => $r['khu_vuc_kq'] ?? '',
                'Đối tượng' => $r['doi_tuong_kq'] ?? '',
                'Mã ngành trúng tuyển' => $r['ma_nganh'] ?? '',
                'Tên ngành' => $r['ten_nganh'] ?? '',
                'Tổ hợp xét tuyển' => $r['to_hop'] ?? '',
                'Điểm xét tuyển' => floatval($r['diem_xet_tuyen'] ?? 0),
                'Xác nhận Bộ' => (!empty($r['xac_nhan_bo']) && $r['xac_nhan_bo'] !== 'false' && $r['xac_nhan_bo'] != '0') ? 'Đã XN' : 'Chưa XN',
                'Xác nhận Trường' => (!empty($r['xac_nhan_truong']) && $r['xac_nhan_truong'] !== 'false' && $r['xac_nhan_truong'] != '0') ? 'Đã XN' : 'Chưa XN',
                'Xác nhận Kinh phí' => (!empty($r['xac_nhan_kinh_phi']) && $r['xac_nhan_kinh_phi'] !== 'false' && $r['xac_nhan_kinh_phi'] != '0') ? 'Đã XN' : 'Chưa XN',
                'Trạng thái nhập học' => $trangThaiTxt,
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'danh_sach_chua_nhap_hoc_' . $sessionId . '.xls', true);
    }

    /**
     * Xuất dữ liệu làm thẻ ngân hàng (17 cột tiêu chuẩn)
     * Cột yêu cầu: STT, Họ Và Tên, CCCD, NGÀY CẤP, NGÀY HẾT HẠN, NƠI CẤP, GIỚI TÍNH, NGÀY SINH, ĐỊA CHỈ, SĐT, EMAIL, MÃ NGÀNH, TÊN NGÀNH, KHÓA HỌC, MÃ SỐ SV, LỚP, KHOA
     */
    public function exportBankCards() {
        $sessions = $this->masterData->getSessions();
        $sessionId = intval($_GET['session_id'] ?? (count($sessions) > 0 ? $sessions[0]['id'] : 0));

        // 1. Thử lấy danh sách thí sinh đã hoàn tất nhập học
        $stmt = $this->db->prepare("
            SELECT nh.id as nhap_hoc_id, nh.ma_phieu, nh.ngay_nhap_hoc, nh.ghi_chu_can_bo,
                   kq.so_cccd, kq.ho_ten, kq.sbd as so_bao_danh, kq.ngay_sinh, 
                   kq.ma_nganh, COALESCE(NULLIF(n.ten_nganh, ''), kq.ten_nganh) as ten_nganh, n.nhom_nganh,
                   kq.ten_khoa, kq.email as email_kq,
                   ts.ho_va_ten, ts.gioi_tinh, ts.dan_toc, ts.dia_chi_chi_tiet, ts.email as email_ts,
                   ts.dien_thoai as dien_thoai_ts, kq.sdt as dien_thoai_kq,
                   p.ten_tinh, x.ten_xa,
                   COALESCE(dt.nam_tuyen_sinh, dt.dm_nam_tuyen_sinh_nam) as nam_tuyen_sinh
            FROM nhap_hoc nh
            JOIN ket_qua_trung_tuyen kq ON nh.ket_qua_id = kq.id
            LEFT JOIN dot_tuyen_sinh dt ON nh.session_id = dt.id
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
            LEFT JOIN dm_tinh p ON ts.ma_tinh_thuong_tru = p.ma_tinh
            LEFT JOIN dm_xa x ON ts.ma_xa_thuong_tru = x.ma_xa
            WHERE nh.session_id = ? 
              AND nh.trang_thai = 'da_nhap_hoc'
            ORDER BY n.ten_nganh ASC, kq.ho_ten ASC
        ");
        $stmt->execute([$sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Nếu đợt chưa có ai bấm hoàn tất nhập học, lấy từ danh sách trúng tuyển đã xác nhận
        if (empty($rows)) {
            $stmtFallback = $this->db->prepare("
                SELECT NULL as nhap_hoc_id, NULL as ma_phieu, NULL as ngay_nhap_hoc, NULL as ghi_chu_can_bo,
                       kq.so_cccd, kq.ho_ten, kq.sbd as so_bao_danh, kq.ngay_sinh, 
                       kq.ma_nganh, COALESCE(NULLIF(n.ten_nganh, ''), kq.ten_nganh) as ten_nganh, n.nhom_nganh,
                       kq.ten_khoa, kq.email as email_kq,
                       ts.ho_va_ten, ts.gioi_tinh, ts.dan_toc, ts.dia_chi_chi_tiet, ts.email as email_ts,
                       ts.dien_thoai as dien_thoai_ts, kq.sdt as dien_thoai_kq,
                       p.ten_tinh, x.ten_xa,
                       COALESCE(dt.nam_tuyen_sinh, dt.dm_nam_tuyen_sinh_nam) as nam_tuyen_sinh
                FROM ket_qua_trung_tuyen kq
                LEFT JOIN dot_tuyen_sinh dt ON kq.session_id = dt.id
                LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
                LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
                LEFT JOIN dm_tinh p ON ts.ma_tinh_thuong_tru = p.ma_tinh
                LEFT JOIN dm_xa x ON ts.ma_xa_thuong_tru = x.ma_xa
                WHERE kq.session_id = ? 
                  AND (kq.xac_nhan_bo = true OR kq.xac_nhan_bo::text = '1'
                       OR kq.xac_nhan_truong = true OR kq.xac_nhan_truong::text = '1'
                       OR kq.xac_nhan_kinh_phi = true OR kq.xac_nhan_kinh_phi::text = '1')
                ORDER BY n.ten_nganh ASC, kq.ho_ten ASC
            ");
            $stmtFallback->execute([$sessionId]);
            $rows = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
        }

        $data = [];
        $stt = 1;
        foreach ($rows as $r) {
            $extra = [];
            if (!empty($r['ghi_chu_can_bo'])) {
                $extra = json_decode($r['ghi_chu_can_bo'], true) ?: [];
            }

            // Họ Và Tên
            $hoTen = mb_strtoupper(trim($r['ho_ten'] ?: ($r['ho_va_ten'] ?? '')), 'UTF-8');

            // Số CCCD
            $soCccd = (string)($r['so_cccd'] ?? '');

            // Ngày cấp
            $ngayCap = $extra['ngay_cap_cccd'] ?? ($extra['ngay_cap'] ?? '');
            if (!empty($ngayCap) && strpos($ngayCap, '-') !== false) {
                $time = strtotime($ngayCap);
                if ($time) $ngayCap = date('d/m/Y', $time);
            }

            // Ngày hết hạn
            $ngayHetHan = $extra['ngay_het_han_cccd'] ?? ($extra['ngay_het_han'] ?? '');
            if (!empty($ngayHetHan) && strpos($ngayHetHan, '-') !== false) {
                $time = strtotime($ngayHetHan);
                if ($time) $ngayHetHan = date('d/m/Y', $time);
            }

            // Nơi cấp
            $noiCap = $extra['noi_cap_cccd'] ?? ($extra['noi_cap'] ?? '');

            // Giới tính
            $gt = $r['gioi_tinh'] ?? '';
            if ($gt === '1' || strcasecmp($gt, 'nam') === 0) {
                $gioiTinh = 'Nam';
            } elseif ($gt === '0' || strcasecmp($gt, 'nữ') === 0 || strcasecmp($gt, 'nu') === 0) {
                $gioiTinh = 'Nữ';
            } else {
                $gioiTinh = $gt;
            }

            // Ngày sinh
            $ngaySinh = '';
            if (!empty($r['ngay_sinh'])) {
                $time = strtotime($r['ngay_sinh']);
                $ngaySinh = $time ? date('d/m/Y', $time) : $r['ngay_sinh'];
            }

            // Địa chỉ
            $diaChi = $r['dia_chi_chi_tiet'] ?? '';
            if (empty($diaChi)) {
                $parts = array_filter([$r['ten_xa'] ?? '', $r['ten_tinh'] ?? '']);
                $diaChi = implode(', ', $parts);
            }

            // SĐT
            $sdt = !empty($r['dien_thoai_ts']) ? (string)$r['dien_thoai_ts'] : (string)($r['dien_thoai_kq'] ?? '');

            // Email (chỉ lấy email cá nhân thực tế)
            $email = !empty($r['email_ts']) ? (string)$r['email_ts'] : (string)($r['email_kq'] ?? '');
            if (strpos($email, '@student.hvu.edu.vn') !== false) {
                $email = '';
            }

            // Mã ngành & Tên ngành
            $maNganh = (string)($r['ma_nganh'] ?? '');
            $tenNganh = (string)($r['ten_nganh'] ?? '');

            // Khóa học (K24, 2026-2030...)
            $namTuyenSinh = intval($r['nam_tuyen_sinh'] ?? 0);
            $kPrefix = ($namTuyenSinh > 2002) ? ('K' . ($namTuyenSinh - 2002)) : '';
            $khoaHoc = $extra['khoa_hoc'] ?? (!empty($r['ten_khoa']) ? ($kPrefix ? "$kPrefix ({$r['ten_khoa']})" : $r['ten_khoa']) : $kPrefix);

            // Mã số SV
            $mssv = $extra['ma_sinh_vien'] ?? ($extra['mssv'] ?? ($extra['ma_sv'] ?? ''));

            // Lớp
            $lop = $extra['lop'] ?? ($extra['ten_lop'] ?? '');

            // Khoa
            $khoa = $r['nhom_nganh'] ?? '';

            $data[] = [
                'STT'           => $stt++,
                'Họ Và Tên'     => $hoTen,
                'CCCD'          => $soCccd,
                'NGÀY CẤP'      => $ngayCap,
                'NGÀY HẾT HẠN'  => $ngayHetHan,
                'NƠI CẤP'       => $noiCap,
                'GIỚI TÍNH'     => $gioiTinh,
                'NGÀY SINH'     => $ngaySinh,
                'ĐỊA CHỈ'       => $diaChi,
                'SĐT'           => $sdt,
                'EMAIL'         => $email,
                'MÃ NGÀNH'      => $maNganh,
                'TÊN NGÀNH'     => $tenNganh,
                'KHÓA HỌC'      => $khoaHoc,
                'MÃ SỐ SV'      => $mssv,
                'LỚP'           => $lop,
                'KHOA'          => $khoa,
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'du_lieu_lam_the_ngan_hang_' . $sessionId . '.xls', true);
    }

    /**
     * Xuất danh sách Edusoft (34 cột chuẩn Edusoft + Mã ngành, Tên ngành)
     * Sắp xếp tăng dần theo mã ngành, trong cùng 1 ngành sắp xếp theo TenSV - HoLotSV chuẩn tiếng Việt
     */
    public function exportEdusoft() {
        $sessions = $this->masterData->getSessions();
        $sessionId = intval($_GET['session_id'] ?? (count($sessions) > 0 ? $sessions[0]['id'] : 0));

        // 1. Lấy danh sách thí sinh đã nhập học
        $stmt = $this->db->prepare("
            SELECT nh.id as nhap_hoc_id, nh.ma_phieu, nh.ngay_nhap_hoc, nh.ghi_chu_can_bo,
                   kq.so_cccd, kq.ho_ten, kq.sbd as so_bao_danh, kq.ngay_sinh, 
                   kq.ma_nganh, COALESCE(NULLIF(n.ten_nganh, ''), kq.ten_nganh) as ten_nganh, n.nhom_nganh,
                   kq.ten_khoa, kq.email as email_kq,
                   ts.ho_va_ten, ts.gioi_tinh, ts.dan_toc, ts.dia_chi_chi_tiet, ts.email as email_ts,
                   ts.dien_thoai as dien_thoai_ts, kq.sdt as dien_thoai_kq,
                   COALESCE(NULLIF(p_tt.ten_tinh, ''), NULLIF(p_hk.ten_tinh, ''), NULLIF(p_12.ten_tinh, '')) as ten_tinh,
                   x.ten_xa,
                   COALESCE(dt.nam_tuyen_sinh, dt.dm_nam_tuyen_sinh_nam) as nam_tuyen_sinh
            FROM nhap_hoc nh
            JOIN ket_qua_trung_tuyen kq ON nh.ket_qua_id = kq.id
            LEFT JOIN dot_tuyen_sinh dt ON nh.session_id = dt.id
            LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
            LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
            LEFT JOIN dm_tinh p_tt ON ts.ma_tinh_thuong_tru = p_tt.ma_tinh
            LEFT JOIN dm_tinh p_hk ON ts.ma_tinh_ho_khau = p_hk.ma_tinh
            LEFT JOIN dm_tinh p_12 ON ts.ma_tinh_lop_12 = p_12.ma_tinh
            LEFT JOIN dm_xa x ON ts.ma_xa_thuong_tru = x.ma_xa
            WHERE nh.session_id = ? 
              AND nh.trang_thai = 'da_nhap_hoc'
        ");
        $stmt->execute([$sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Nếu đợt chưa có ai bấm hoàn tất nhập học, lấy từ danh sách trúng tuyển đã xác nhận
        if (empty($rows)) {
            $stmtFallback = $this->db->prepare("
                SELECT NULL as nhap_hoc_id, NULL as ma_phieu, NULL as ngay_nhap_hoc, NULL as ghi_chu_can_bo,
                       kq.so_cccd, kq.ho_ten, kq.sbd as so_bao_danh, kq.ngay_sinh, 
                       kq.ma_nganh, COALESCE(NULLIF(n.ten_nganh, ''), kq.ten_nganh) as ten_nganh, n.nhom_nganh,
                       kq.ten_khoa, kq.email as email_kq,
                       ts.ho_va_ten, ts.gioi_tinh, ts.dan_toc, ts.dia_chi_chi_tiet, ts.email as email_ts,
                       ts.dien_thoai as dien_thoai_ts, kq.sdt as dien_thoai_kq,
                       COALESCE(NULLIF(p_tt.ten_tinh, ''), NULLIF(p_hk.ten_tinh, ''), NULLIF(p_12.ten_tinh, '')) as ten_tinh,
                       x.ten_xa,
                       COALESCE(dt.nam_tuyen_sinh, dt.dm_nam_tuyen_sinh_nam) as nam_tuyen_sinh
                FROM ket_qua_trung_tuyen kq
                LEFT JOIN dot_tuyen_sinh dt ON kq.session_id = dt.id
                LEFT JOIN dm_nganh n ON kq.ma_nganh = n.ma_nganh
                LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
                LEFT JOIN dm_tinh p_tt ON ts.ma_tinh_thuong_tru = p_tt.ma_tinh
                LEFT JOIN dm_tinh p_hk ON ts.ma_tinh_ho_khau = p_hk.ma_tinh
                LEFT JOIN dm_tinh p_12 ON ts.ma_tinh_lop_12 = p_12.ma_tinh
                LEFT JOIN dm_xa x ON ts.ma_xa_thuong_tru = x.ma_xa
                WHERE kq.session_id = ? 
                  AND (kq.xac_nhan_bo = true OR kq.xac_nhan_bo::text = '1'
                       OR kq.xac_nhan_truong = true OR kq.xac_nhan_truong::text = '1'
                       OR kq.xac_nhan_kinh_phi = true OR kq.xac_nhan_kinh_phi::text = '1')
            ");
            $stmtFallback->execute([$sessionId]);
            $rows = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
        }

        $candidates = [];
        foreach ($rows as $r) {
            $extra = [];
            if (!empty($r['ghi_chu_can_bo'])) {
                $extra = json_decode($r['ghi_chu_can_bo'], true) ?: [];
            }

            // Tách Họ lót và Tên
            $hoTenRaw = trim($r['ho_ten'] ?: ($r['ho_va_ten'] ?? ''));
            $hoTenTitle = mb_convert_case($hoTenRaw, MB_CASE_TITLE, 'UTF-8');
            $parts = preg_split('/\s+/u', trim($hoTenTitle));
            if (count($parts) > 1) {
                $tenSV = array_pop($parts);
                $hoLotSV = implode(' ', $parts);
            } else {
                $tenSV = $parts[0] ?? '';
                $hoLotSV = '';
            }

            // Ngày sinh (dd/mm/yyyy)
            $ngaySinh = '';
            $rawDob = trim($r['ngay_sinh'] ?? '');
            if (!empty($rawDob)) {
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $rawDob, $m)) {
                    $ngaySinh = sprintf('%02d/%02d/%04d', $m[1], $m[2], $m[3]);
                } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $rawDob, $m)) {
                    $ngaySinh = sprintf('%02d/%02d/%04d', $m[3], $m[2], $m[1]);
                } else {
                    $ts = strtotime($rawDob);
                    if ($ts) $ngaySinh = date('d/m/Y', $ts);
                }
            }

            // Giới tính (Phai: Nam = 0, Nữ = 1)
            $gt = trim($r['gioi_tinh'] ?? '');
            if (strcasecmp($gt, 'nữ') === 0 || strcasecmp($gt, 'nu') === 0 || $gt === '1') {
                $phai = '1';
            } elseif (strcasecmp($gt, 'nam') === 0 || $gt === '0') {
                $phai = '0';
            } else {
                $phai = '0';
            }

            // Nơi sinh (bỏ tiền tố Tỉnh/Thành phố nếu có)
            $noiSinh = $r['noi_sinh'] ?? '';
            if (empty($noiSinh)) {
                $noiSinh = $r['ten_tinh'] ?? '';
            }
            $noiSinh = preg_replace('/^(Tỉnh|Thành phố|TP\.?)\s+/ui', '', trim($noiSinh));

            $maSV = (string)($extra['ma_sinh_vien'] ?? ($extra['mssv'] ?? ($extra['ma_sv'] ?? '')));
            $maLop = (string)($extra['ma_lop'] ?? ($extra['lop'] ?? ($extra['ten_lop'] ?? '')));
            $tenLop = (string)($extra['ten_lop'] ?? ($extra['lop'] ?? ''));
            $maBH = 'D_1';
            $maKhoa = (string)($extra['ma_khoa'] ?? '');
            $maNgChng = (string)($extra['ma_chuyen_nganh'] ?? ($extra['ma_nganh_chuyen_nganh'] ?? ''));
            $tenKhoa = (string)($extra['khoa'] ?? ($extra['ten_khoa'] ?? ($r['nhom_nganh'] ?? '')));
            $tenNgChng = (string)($r['ten_nganh'] ?? '');
            $tenBH = 'Đại học, chính quy';
            $maNganh = (string)($r['ma_nganh'] ?? '');

            $candidates[] = [
                'ma_nganh'   => $maNganh,
                'ten_sv'     => $tenSV,
                'ho_lot_sv'  => $hoLotSV,
                'row_data'   => [
                    $maSV,
                    $hoLotSV,
                    $tenSV,
                    $ngaySinh,
                    $phai,
                    $noiSinh,
                    '0',
                    $maLop,
                    $maBH,
                    $maKhoa,
                    $maNgChng,
                    '0',
                    $tenLop,
                    '0',
                    '0',
                    '0',
                    '0',
                    '0',
                    $tenKhoa,
                    '0',
                    $tenNgChng,
                    '0',
                    '0',
                    '0',
                    $tenBH,
                    '0',
                    '0',
                    '0',
                    '0',
                    '0',
                    '0',
                    '0',
                    $maNganh,
                    $tenNgChng
                ]
            ];
        }

        // Sắp xếp: tăng dần theo mã ngành -> Tên tiếng Việt -> Họ lót tiếng Việt
        $collator = class_exists('\Collator') ? new \Collator('vi_VN') : null;

        usort($candidates, function($a, $b) use ($collator) {
            $cmpMajor = strcmp($a['ma_nganh'], $b['ma_nganh']);
            if ($cmpMajor !== 0) return $cmpMajor;

            if ($collator) {
                $cmpTen = $collator->compare($a['ten_sv'], $b['ten_sv']);
            } else {
                $cmpTen = strcmp($a['ten_sv'], $b['ten_sv']);
            }
            if ($cmpTen !== 0) return $cmpTen;

            if ($collator) {
                return $collator->compare($a['ho_lot_sv'], $b['ho_lot_sv']);
            }
            return strcmp($a['ho_lot_sv'], $b['ho_lot_sv']);
        });

        $headers = [
            'MaSV', 'HoLotSV', 'TenSV', 'NgaySinhC', 'Phai', 'NoiSinh', 'DC_DT1HK', 
            'MaLop', 'MaBH', 'MaKhoa', 'MaNgChng', 'IsDuThinh', 'TenLop', 'TenLopEg', 
            'TenLopEg2', 'SoQDKHDT', 'SoQDCTDT', 'SoTinChiCTDT', 'TenKhoa', 'TenKhoaEg', 
            'TenNgChng', 'TenNgChngEg', 'MaNgChngBo', 'MaVVFast', 'TenBH', 'TenBHEg', 
            '0', '0', '0', '0', '0', '0',
            'Mã ngành', 'Tên ngành'
        ];

        $exportRows = array_column($candidates, 'row_data');

        $exportService = new \App\Services\ExportService();
        $exportService->exportEdusoftXml($headers, $exportRows, 'danh_sach_edusoft_dot_' . $sessionId . '.xls');
    }

    /**
     * Xuất file ZIP chứa ảnh CCCD mặt trước + mặt sau của thí sinh đã có trong hệ thống
     */
    public function exportCccdPhotos() {
        if (!class_exists('\ZipArchive')) {
            die('Máy chủ chưa cài đặt tiện ích mở rộng ZipArchive PHP.');
        }

        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '1024M');

        $sessions = $this->masterData->getSessions();
        $sessionId = intval($_GET['session_id'] ?? (count($sessions) > 0 ? $sessions[0]['id'] : 0));

        // 1. Lấy danh sách thí sinh trong đợt kèm ảnh CCCD mặt trước + sau
        $stmt = $this->db->prepare("
            SELECT kq.so_cccd, kq.ho_ten, 
                   COALESCE(NULLIF(ts.anh_cccd_truoc, ''), NULLIF(ts_alt.anh_cccd_truoc, '')) as anh_cccd_truoc,
                   COALESCE(NULLIF(ts.anh_cccd_sau, ''), NULLIF(ts_alt.anh_cccd_sau, '')) as anh_cccd_sau
            FROM ket_qua_trung_tuyen kq
            LEFT JOIN thi_sinh ts ON kq.so_cccd = ts.so_cccd
            LEFT JOIN (
                SELECT DISTINCT ON (so_cccd) so_cccd, anh_cccd_truoc, anh_cccd_sau
                FROM thi_sinh
                WHERE (anh_cccd_truoc IS NOT NULL AND anh_cccd_truoc != '')
                   OR (anh_cccd_sau IS NOT NULL AND anh_cccd_sau != '')
                ORDER BY so_cccd, id DESC
            ) ts_alt ON kq.so_cccd = ts_alt.so_cccd
            WHERE kq.session_id = ?
              AND (
                  EXISTS (SELECT 1 FROM nhap_hoc nh WHERE nh.session_id = kq.session_id AND nh.so_cccd = kq.so_cccd AND nh.trang_thai = 'da_nhap_hoc')
                  OR kq.xac_nhan_bo = true OR kq.xac_nhan_bo::text = '1'
                  OR kq.xac_nhan_truong = true OR kq.xac_nhan_truong::text = '1'
                  OR kq.xac_nhan_kinh_phi = true OR kq.xac_nhan_kinh_phi::text = '1'
              )
            ORDER BY kq.ho_ten ASC
        ");
        $stmt->execute([$sessionId]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($candidates)) {
            die('Không có dữ liệu thí sinh trong đợt xét tuyển này.');
        }

        // Lọc các thí sinh có ảnh CCCD (mặt trước hoặc mặt sau)
        $itemsWithPhoto = [];
        foreach ($candidates as $c) {
            $urlTruoc = trim($c['anh_cccd_truoc'] ?? '');
            $urlSau   = trim($c['anh_cccd_sau'] ?? '');

            if (!empty($urlTruoc)) {
                $itemsWithPhoto[] = [
                    'so_cccd' => $c['so_cccd'],
                    'ho_ten'  => $c['ho_ten'],
                    'url'     => $urlTruoc,
                    'side'    => 'mat_truoc'
                ];
            }
            if (!empty($urlSau)) {
                $itemsWithPhoto[] = [
                    'so_cccd' => $c['so_cccd'],
                    'ho_ten'  => $c['ho_ten'],
                    'url'     => $urlSau,
                    'side'    => 'mat_sau'
                ];
            }
        }

        if (empty($itemsWithPhoto)) {
            die('Không tìm thấy ảnh CCCD của thí sinh nào trong đợt này.');
        }

        $zipFileName = 'anh_cccd_dot_' . $sessionId . '_' . date('Ymd_His') . '.zip';
        $zipFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName;

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            die('Không thể tạo tệp nén ZIP trên máy chủ.');
        }

        // Tạo 2 thư mục con trong ZIP
        $zip->addEmptyDir('mat_truoc');
        $zip->addEmptyDir('mat_sau');

        $publicPath = __DIR__ . '/../../public';
        $addedFiles = 0;
        $remoteItems = [];
        $localItems = [];

        foreach ($itemsWithPhoto as $item) {
            $path = $item['url'];
            if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
                $remoteItems[] = $item;
            } else {
                $localItems[] = $item;
            }
        }

        // Xử lý tệp cục bộ
        foreach ($localItems as $item) {
            $fullPath = $publicPath . (strpos($item['url'], '/') === 0 ? '' : '/') . $item['url'];
            if (file_exists($fullPath) && is_file($fullPath)) {
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'jpg');
                $zipName = "{$item['side']}/{$item['so_cccd']}.{$ext}";
                $zip->addFile($fullPath, $zipName);
                $addedFiles++;
            }
        }

        // Xử lý tệp từ xa (Google Drive / HTTP) bằng curl_multi tải nhanh theo lô
        if (!empty($remoteItems)) {
            $batchSize = 80;
            $chunks = array_chunk($remoteItems, $batchSize);

            foreach ($chunks as $batchIdx => $chunk) {
                set_time_limit(120);
                $urls = [];
                foreach ($chunk as $idx => $it) {
                    $urls[$idx] = $this->getCccdFastDownloadUrl($it['url']);
                }

                $contents = $this->fetchCccdUrlsParallel($urls);
                foreach ($contents as $idx => $content) {
                    if (!$content) continue;
                    $it = $chunk[$idx];
                    $zipName = "{$it['side']}/{$it['so_cccd']}.jpg";
                    $zip->addFromString($zipName, $content);
                    $addedFiles++;
                }
            }
        }

        $zip->close();

        if ($addedFiles === 0) {
            @unlink($zipFilePath);
            die('Không thể tải hoặc nén được tệp ảnh CCCD nào.');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($zipFilePath));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($zipFilePath);
        @unlink($zipFilePath);
        exit;
    }

    /**
     * Chuyển link Google Drive sang định dạng tải nhanh chất lượng cao
     */
    private function getCccdFastDownloadUrl($originalUrl): string {
        if (strpos($originalUrl, 'drive.google.com') !== false) {
            $id = '';
            if (preg_match('/d\/([a-zA-Z0-9_-]+)/', $originalUrl, $matches)) {
                $id = $matches[1];
            } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $originalUrl, $matches)) {
                $id = $matches[1];
            }
            if ($id) {
                return 'https://drive.google.com/thumbnail?id=' . $id . '&sz=w800';
            }
        }
        return $originalUrl;
    }

    /**
     * Tải song song nhiều ảnh từ xa
     */
    private function fetchCccdUrlsParallel(array $urls): array {
        if (!function_exists('curl_multi_init')) {
            $results = [];
            foreach ($urls as $key => $url) {
                $results[$key] = @file_get_contents($url);
            }
            return $results;
        }

        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        foreach ($urls as $key => $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AdmissionsPortal/1.0');
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) === -1) {
                usleep(10000);
            }
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        foreach ($handles as $key => $ch) {
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code === 200) {
                $results[$key] = curl_multi_getcontent($ch);
            } else {
                $results[$key] = null;
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $results;
    }
}
