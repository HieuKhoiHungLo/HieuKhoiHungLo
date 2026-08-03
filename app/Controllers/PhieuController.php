<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Services\PhieuPrinter;
use PDO;

/**
 * PhieuController - Quản lý mẫu phiếu và xuất phiếu in Word
 * Routes:
 *  GET  /admin/phieu/templates          → Trang quản lý mẫu
 *  POST /admin/phieu/templates/upload   → Upload mẫu .docx
 *  POST /admin/phieu/templates/delete   → Xóa mẫu
 *  GET  /admin/phieu/download           → Tải phiếu (1 hoặc batch)
 *  GET  /admin/phieu/list               → API: danh sách mẫu (JSON)
 */
class PhieuController extends Controller {

    protected $db;
    protected $currentUser;
    protected $printer;

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

        if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, 'admission.view')) {
            $this->redirect(url('/admin/dashboard'));
        }

        $this->db = \App\Core\Database::getInstance()->getConnection();
        $this->printer = new PhieuPrinter($this->db);
    }

    // =========================================================================
    //  TEMPLATE MANAGER
    // =========================================================================

    /** Trang quản lý mẫu phiếu */
    public function templates() {
        $this->ensureTableExists();

        $sessions = (new MasterData())->getSessions();
        $sessionId = $_GET['session_id'] ?? ($sessions[0]['id'] ?? 0);

        $stmt = $this->db->prepare("SELECT * FROM mau_phieu ORDER BY loai_mau, created_at DESC");
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/phieu/templates', [
            'title'     => 'Quản lý Mẫu Phiếu In',
            'templates' => $templates,
            'sessions'  => $sessions,
            'currentSessionId' => $sessionId,
        ]);
    }

    /** Upload mẫu .docx */
    public function uploadTemplate() {
        header('Content-Type: application/json; charset=utf-8');
        if (!$_FILES['template_file'] ?? false) {
            echo json_encode(['success' => false, 'message' => 'Không có file được gửi lên.']); return;
        }

        $file = $_FILES['template_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'docx') {
            echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file .docx']); return;
        }

        if ($file['size'] > 10 * 1024 * 1024) { // 10MB
            echo json_encode(['success' => false, 'message' => 'File quá lớn (tối đa 10MB)']); return;
        }

        $this->ensureTableExists();

        $tenMau    = trim($_POST['ten_mau'] ?? '') ?: pathinfo($file['name'], PATHINFO_FILENAME);
        $loaiMau   = $_POST['loai_mau'] ?? 'phieu_nhap_hoc';
        $mota      = trim($_POST['mo_ta'] ?? '');
        $sessionId = $_POST['session_id'] ? (int)$_POST['session_id'] : null;

        // Lưu file
        $destDir = $this->printer->getTemplateDir();
        $safeName = preg_replace('/[^a-z0-9_-]/i', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $fileName = $loaiMau . '_' . $safeName . '_' . time() . '.docx';
        $destPath = $destDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            echo json_encode(['success' => false, 'message' => 'Lỗi lưu file lên server']); return;
        }

        // Lưu DB
        $stmt = $this->db->prepare("
            INSERT INTO mau_phieu (ten_mau, loai_mau, ten_file, mo_ta, session_id, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tenMau, $loaiMau, $fileName, $mota, $sessionId, $_SESSION['admin_id']]);

        echo json_encode(['success' => true, 'message' => 'Upload mẫu thành công!']);
    }

    /** Xóa mẫu */
    public function deleteTemplate() {
        header('Content-Type: application/json; charset=utf-8');
        if (!\App\Models\QuanTriVien::hasPermission($this->currentUser, 'admission.edit')) {
            echo json_encode(['success' => false, 'message' => 'Không có quyền']); return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $stmt = $this->db->prepare("SELECT ten_file FROM mau_phieu WHERE id = ?");
        $stmt->execute([$id]);
        $tpl = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tpl) { echo json_encode(['success' => false, 'message' => 'Không tìm thấy mẫu']); return; }

        $filePath = $this->printer->getTemplateDir() . $tpl['ten_file'];
        if (file_exists($filePath)) @unlink($filePath);

        $this->db->prepare("DELETE FROM mau_phieu WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Đã xóa mẫu']);
    }

    /** API: Danh sách mẫu JSON (dùng cho các trang khác) */
    public function listTemplates() {
        header('Content-Type: application/json; charset=utf-8');
        $this->ensureTableExists();

        $loai = $_GET['loai'] ?? '';
        if ($loai) {
            $stmt = $this->db->prepare("SELECT id, ten_mau, loai_mau, ten_file, mo_ta FROM mau_phieu WHERE loai_mau = ? AND is_active = TRUE ORDER BY created_at DESC");
            $stmt->execute([$loai]);
        } else {
            $stmt = $this->db->prepare("SELECT id, ten_mau, loai_mau, ten_file, mo_ta FROM mau_phieu WHERE is_active = TRUE ORDER BY loai_mau, created_at DESC");
            $stmt->execute();
        }
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // =========================================================================
    //  DOWNLOAD / GENERATE
    // =========================================================================

    /**
     * Xuất phiếu và stream về cho người dùng download
     * GET /admin/phieu/download?type=nhap_hoc&ids=1,2,3&template_id=5
     * GET /admin/phieu/download?type=giay_bao&ids=10,11&template_id=3
     */
    public function download() {
        $type       = $_GET['type'] ?? 'nhap_hoc';
        $idsRaw     = trim($_GET['ids'] ?? '');
        $templateId = (int)($_GET['template_id'] ?? 0);

        if (empty($idsRaw) || !$templateId) {
            http_response_code(400);
            echo "Thiếu tham số: ids, template_id";
            return;
        }

        $ids = array_filter(array_map('intval', explode(',', $idsRaw)));
        if (empty($ids)) { http_response_code(400); echo "IDs không hợp lệ"; return; }

        $this->ensureTableExists();

        // Lấy thông tin mẫu
        $stmt = $this->db->prepare("SELECT ten_file, ten_mau FROM mau_phieu WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$templateId]);
        $tpl = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tpl) { http_response_code(404); echo "Không tìm thấy mẫu phiếu"; return; }

        try {
            $this->printer->cleanTemp();

            if (count($ids) === 1) {
                // Single file → download .docx
                $id = $ids[0];
                if ($type === 'nhap_hoc') {
                    $outFile  = $this->printer->generatePhieuNhapHoc($id, $tpl['ten_file']);
                    $fileName = 'Phieu_Nhap_Hoc_' . $id . '.docx';
                } else {
                    $outFile  = $this->printer->generateGiayBao($id, $tpl['ten_file']);
                    $fileName = 'Giay_Bao_TrungTuyen_' . $id . '.docx';
                }
                $this->streamFile($outFile, $fileName, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

            } else {
                // Batch → download .zip
                if ($type === 'nhap_hoc') {
                    $outFile  = $this->printer->batchPhieuNhapHoc($ids, $tpl['ten_file']);
                } else {
                    $outFile  = $this->printer->batchGiayBao($ids, $tpl['ten_file']);
                }
                $label    = $type === 'nhap_hoc' ? 'Phieu_Nhap_Hoc' : 'Giay_Bao_TrungTuyen';
                $fileName = $label . '_batch_' . date('Ymd_His') . '.zip';
                $this->streamFile($outFile, $fileName, 'application/zip');
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo "Lỗi: " . $e->getMessage();
        }
    }

    // =========================================================================
    //  HELPERS
    // =========================================================================

    private function streamFile(string $path, string $downloadName, string $mime): void {
        if (!file_exists($path)) { http_response_code(500); echo "File không tồn tại"; return; }

        // Clear output buffer
        while (ob_get_level()) ob_end_clean();

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($path);
        @unlink($path); // Xóa file tạm sau khi stream
        exit;
    }

    private function ensureTableExists(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS mau_phieu (
                id SERIAL PRIMARY KEY,
                ten_mau VARCHAR(200) NOT NULL,
                loai_mau VARCHAR(50) NOT NULL DEFAULT 'phieu_nhap_hoc',
                ten_file VARCHAR(500),
                mo_ta TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                session_id INTEGER,
                created_by INTEGER,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Seed default templates if empty
        $count = $this->db->query("SELECT COUNT(*) FROM mau_phieu")->fetchColumn();
        if ((int)$count === 0) {
            $ins = $this->db->prepare("INSERT INTO mau_phieu (ten_mau, loai_mau, ten_file, mo_ta) VALUES (?, ?, ?, ?)");
            $ins->execute(['Mẫu Giấy Báo Trúng Tuyển và Nhập Học 2025', 'giay_bao_trung_tuyen', 'sample_giay_bao_2025.docx', 'Mẫu giấy báo trúng tuyển chuẩn năm 2025 có tích hợp QR thanh toán VietQR']);
            $ins->execute(['Mẫu Phiếu Tiếp Nhận Hồ Sơ Nhập Học', 'phieu_nhap_hoc', 'sample_phieu_nhap_hoc.docx', 'Mẫu phiếu tiếp nhận hồ sơ nhập học tại quầy']);
        }
    }
}
