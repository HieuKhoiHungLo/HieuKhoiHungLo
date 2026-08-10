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
        $activeSession = \App\Core\Cache::remember('active_lookup_session', 5, function() {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT id, ten_dot, nam_tuyen_sinh FROM dot_tuyen_sinh WHERE is_published_results IS TRUE ORDER BY id DESC LIMIT 1");
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        });

        if (empty($activeSession)) {
            $activeSession = \App\Core\Cache::remember('latest_lookup_session', 5, function() {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->query("SELECT id, ten_dot, nam_tuyen_sinh FROM dot_tuyen_sinh ORDER BY id DESC LIMIT 1");
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            });
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
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập thông tin tra cứu!']);
            return;
        }

        // Rate Limiting: Max 5 requests per 1 minute per IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown_ip';
        $rateLimitKey = 'search_admission_' . $ip;
        
        if (!\App\Core\RateLimiter::check($rateLimitKey, 5, 1)) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(429);
            echo json_encode([
                'success' => false, 
                'message' => 'Bạn đã tra cứu quá nhiều lần. Vui lòng thử lại sau 1 phút.'
            ]);
            return;
        }

        $activeSession = \App\Core\Cache::remember('active_lookup_session', 5, function() {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT id, ten_dot, nam_tuyen_sinh FROM dot_tuyen_sinh WHERE is_published_results IS TRUE ORDER BY id DESC LIMIT 1");
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        });

        if (empty($activeSession)) {
            $activeSession = \App\Core\Cache::remember('latest_lookup_session', 5, function() {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->query("SELECT id, ten_dot, nam_tuyen_sinh FROM dot_tuyen_sinh ORDER BY id DESC LIMIT 1");
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            });
        }

        $sessionId = $activeSession['id'] ?? 0;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT t.*,
                   ts.id as thi_sinh_id,
                   ts.anh_dai_dien
            FROM ket_qua_trung_tuyen t
            LEFT JOIN thi_sinh ts ON ts.so_cccd = t.so_cccd
            WHERE (
                t.so_cccd = ?
                OR t.sbd = ?
            )
            AND t.session_id = ?
            LIMIT 1
        ");
        $stmt->execute([$keyword, $keyword, $sessionId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        header('Content-Type: application/json; charset=utf-8');
        
        if (!$record) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy kết quả trúng tuyển. Kiểm tra lại CCCD hoặc SBD của bạn.']);
            return;
        }

        $targetUrl = url('/application/results');
        $cccdToPrefill = !empty($record['so_cccd']) ? $record['so_cccd'] : $keyword;
        $_SESSION['prefill_cccd'] = $cccdToPrefill;

        $loginUrl = url('/login?redirect=' . urlencode($targetUrl) . '&cccd=' . urlencode($cccdToPrefill));
        
        $ngaySinh = $record['ngay_sinh'] ?? '';
        if (!empty($ngaySinh)) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $ngaySinh);
            if ($dateObj) {
                $ngaySinh = $dateObj->format('d/m/Y');
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'ho_ten' => $record['ho_ten'] ?? 'Thí sinh',
                'so_cccd' => $record['so_cccd'] ?? '--',
                'ngay_sinh' => $ngaySinh,
                'ma_nganh' => $record['ma_nganh'] ?? '',
                'ten_nganh' => $record['ten_nganh'] ?? '',
                'anh_the' => !empty($record['anh_dai_dien']) ? (strpos($record['anh_dai_dien'], 'http') === 0 ? $record['anh_dai_dien'] : url($record['anh_dai_dien'])) : '',
                'login_url' => $loginUrl
            ]
        ]);
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
