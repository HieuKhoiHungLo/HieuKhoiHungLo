<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AdmissionResultService;
use PDO;

class AdmissionLookupController extends Controller
{
    /**
     * Trang tra cứu thông báo trúng tuyển (public, không cần đăng nhập)
     */
    public function index()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT id, ten_dot, nam_tuyen_sinh FROM dot_tuyen_sinh WHERE (kich_hoat IS TRUE OR is_published_results IS TRUE) ORDER BY id DESC LIMIT 1");
        $activeSession = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$activeSession) {
            $stmt = $db->query("SELECT id, ten_dot, nam_tuyen_sinh FROM dot_tuyen_sinh ORDER BY id DESC LIMIT 1");
            $activeSession = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $sessionName = !empty($activeSession['ten_dot']) ? $activeSession['ten_dot'] : 'Tuyển sinh Đại học Hùng Vương';
        $year = !empty($activeSession['nam_tuyen_sinh']) ? $activeSession['nam_tuyen_sinh'] : date('Y');

        $this->view('public/admission_lookup/index', [
            'title'       => 'Tra cứu Thông báo Trúng tuyển - ' . $sessionName,
            'sessionName' => $sessionName,
            'year'        => $year,
        ]);
    }

    /**
     * Xử lý tra cứu theo CCCD / SBD / Email
     */
    public function search()
    {
        $this->validateCsrf();

        $keyword = trim($_POST['keyword'] ?? '');

        if (empty($keyword)) {
            $this->redirect(url('/tra-cuu-trung-tuyen?error=empty'));
            return;
        }

        // Rate limit đơn giản: chỉ cho phép tra cứu, không cần session phức tạp
        $db = Database::getInstance()->getConnection();

        // Tìm kiếm theo CCCD, SBD hoặc Email trong bảng ket_qua_trung_tuyen (lấy 1 đợt mới nhất/kích hoạt nhất)
        $stmt = $db->prepare("
            SELECT t.*,
                   ts.id as thi_sinh_id
            FROM ket_qua_trung_tuyen t
            LEFT JOIN dot_tuyen_sinh d ON d.id = t.session_id
            LEFT JOIN thi_sinh ts ON ts.so_cccd = t.so_cccd
            WHERE (
                t.so_cccd = ?
                OR t.sbd = ?
            )
            ORDER BY d.kich_hoat DESC, d.is_published_results DESC, d.id DESC, t.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$keyword, $keyword]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            $this->redirect(url('/tra-cuu-trung-tuyen?error=not_found&q=' . urlencode($keyword)));
            return;
        }

        $targetUrl = url('/application/results');

        // If candidate is already logged in, redirect directly to /application/results
        if (!empty($_SESSION['user_id'])) {
            $this->redirect($targetUrl);
            return;
        }

        // If candidate is not logged in, prefill CCCD and redirect to login page with target redirect
        $cccdToPrefill = !empty($record['so_cccd']) ? $record['so_cccd'] : $keyword;
        $_SESSION['prefill_cccd'] = $cccdToPrefill;

        $loginUrl = url('/login?redirect=' . urlencode($targetUrl) . '&cccd=' . urlencode($cccdToPrefill));
        $this->redirect($loginUrl);
    }

    /**
     * Thí sinh Xác nhận nhập học trực tuyến tại Trường ĐH Hùng Vương (Bước 3)
     */
    public function confirmHvuAdmission()
    {
        $this->validateCsrf();

        $cccd = trim($_POST['cccd'] ?? '');
        $sessionId = (int)($_POST['session_id'] ?? 0);

        if (empty($cccd) || empty($sessionId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ.']);
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE ket_qua_trung_tuyen
            SET xac_nhan_truong = 1
            WHERE so_cccd = ? AND session_id = ?
        ");
        $stmt->execute([$cccd, $sessionId]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => 'Xác nhận nhập học thành công tại Trường Đại học Hùng Vương!'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
