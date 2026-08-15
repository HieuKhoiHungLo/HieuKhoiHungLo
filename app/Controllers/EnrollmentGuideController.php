<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class EnrollmentGuideController extends Controller
{
    /**
     * Trang tra cứu Hướng dẫn nhập học công khai (không cần đăng nhập)
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

        $this->view('public/enrollment_guide/index', [
            'title'       => 'Hướng dẫn Nhập học - ' . $sessionName,
            'sessionName' => $sessionName,
            'year'        => $year,
        ]);
    }

    public function webIndex()
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

        $this->view('public/enrollment_guide/web', [
            'title'       => 'Hướng dẫn Nhập học - ' . $sessionName,
            'sessionName' => $sessionName,
            'year'        => $year,
        ]);
    }

    /**
     * API tra cứu thông tin hướng dẫn nhập học theo CCCD / SBD
     */
    public function search()
    {
        // Accept both POST (AJAX) or GET
        $keyword = trim($_REQUEST['keyword'] ?? $_REQUEST['cccd'] ?? '');

        if (empty($keyword)) {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập số CCCD hoặc Số báo danh.']);
        }

        $db = Database::getInstance()->getConnection();

        // Search in ket_qua_trung_tuyen JOIN thi_sinh
        $stmt = $db->prepare("
            SELECT 
                kq.id as ket_qua_id,
                kq.ho_ten,
                kq.so_cccd,
                kq.sbd,
                kq.ngay_sinh,
                kq.ma_nganh,
                kq.ten_nganh,
                kq.nganh_tt,
                kq.ten_khoa,
                kq.so_giay_bao,
                kq.thoi_gian_nhap,
                kq.ban_nhap_hoc,
                kq.vi_tri_nhap_hoc,
                kq.link_so_do,
                kq.gvcn,
                kq.kinh_phi,
                ts.anh_dai_dien,
                ts.gioi_tinh,
                ts.dien_thoai as sdt_ts,
                ts.email as email_ts
            FROM ket_qua_trung_tuyen kq
            LEFT JOIN dot_tuyen_sinh d ON d.id = kq.session_id
            LEFT JOIN thi_sinh ts ON ts.so_cccd = kq.so_cccd
            WHERE (kq.so_cccd = ? OR kq.sbd = ?)
            ORDER BY d.kich_hoat DESC, d.is_published_results DESC, d.id DESC, kq.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$keyword, $keyword]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin thí sinh với số CCCD / SBD: ' . htmlspecialchars($keyword)
            ]);
        }

        $banNhapHoc = $record['ban_nhap_hoc'] ?? '';
        $viTriNhapHoc = $record['vi_tri_nhap_hoc'] ?? '';
        $linkSoDo = $record['link_so_do'] ?? '';

        // Override logic for different schedule on 16/8/2026
        $thoiGianNhap = $record['thoi_gian_nhap'] ?? '';
        
        // Determine session time (Morning vs Afternoon) based on absolute boundary: 12:00 on 16/8/2026
        // Check if test_hour query param is passed
        $testHour = isset($_REQUEST['test_hour']) ? (int)$_REQUEST['test_hour'] : null;
        
        if ($testHour !== null) {
            $isAfternoonSession = ($testHour >= 12);
        } else {
            // Real absolute system time check
            $boundaryTimestamp = strtotime('2026-08-16 12:00:00');
            $currentTimestamp = time();
            $isAfternoonSession = ($currentTimestamp >= $boundaryTimestamp);
        }

        // Apply override conditions
        if (strpos($thoiGianNhap, '16/8/2026') !== false) {
            if (!$isAfternoonSession && strpos($thoiGianNhap, '13h30') !== false) {
                // Morning lookup, but afternoon scheduled -> wrong schedule!
                $banNhapHoc = 'Bàn 10, Bàn 11, Bàn 12';
                $viTriNhapHoc = 'Hội trường Tầng 3';
                $linkSoDo = '/uploads/media/1786629203_6a7dcc5318a94.jpg'; // Specific map S_vt5.jpg
            } else if ($isAfternoonSession && strpos($thoiGianNhap, '7h30') !== false) {
                // Afternoon lookup, but morning scheduled -> wrong schedule!
                $banNhapHoc = 'Bàn 10, Bàn 11, Bàn 12';
                $viTriNhapHoc = 'Hội trường Tầng 3';
                $linkSoDo = '/uploads/media/1786629203_6a7dcc5318a94.jpg'; // Specific map S_vt5.jpg
            }
        }

        // Format response data
        return $this->json([
            'success' => true,
            'data' => [
                'ho_ten'         => $record['ho_ten'] ?? '',
                'so_cccd'        => $record['so_cccd'] ?? '',
                'sbd'            => $record['sbd'] ?? '',
                'ngay_sinh'      => !empty($record['ngay_sinh']) && strtotime($record['ngay_sinh']) ? date('d/m/Y', strtotime($record['ngay_sinh'])) : ($record['ngay_sinh'] ?? ''),
                'ma_nganh'       => $record['ma_nganh'] ?? '',
                'ten_nganh'      => !empty($record['nganh_tt']) ? $record['nganh_tt'] : ($record['ten_nganh'] ?? ''),
                'ten_khoa'       => $record['ten_khoa'] ?? '',
                'so_giay_bao'    => $record['so_giay_bao'] ?? '',
                'thoi_gian_nhap' => $record['thoi_gian_nhap'] ?? '',
                'ban_nhap_hoc'   => $banNhapHoc,
                'vi_tri_nhap_hoc'=> $viTriNhapHoc,
                'link_so_do'     => !empty($linkSoDo) ? (strpos($linkSoDo, 'http') === 0 ? $linkSoDo : 'https://tuyensinh.hvu.edu.vn/' . ltrim($linkSoDo, '/')) : '',
                'gvcn'           => $record['gvcn'] ?? '',
                'kinh_phi'       => $record['kinh_phi'] ?? '',
                'anh_the'        => !empty($record['anh_dai_dien']) ? (strpos($record['anh_dai_dien'], 'http') === 0 ? $record['anh_dai_dien'] : 'https://tuyensinh.hvu.edu.vn/' . ltrim($record['anh_dai_dien'], '/')) : '',
            ]
        ]);
    }

    /**
     * API đồng bộ số lượng tra cứu từ các máy Kiosk
     */
    public function kioskSync()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['kiosk_id'])) {
            return $this->json(['success' => false]);
        }

        $kioskId = trim($data['kiosk_id']);
        $count = isset($data['count']) ? (int)$data['count'] : 0;

        $filePath = __DIR__ . '/../../storage/kiosk_counters.json';
        
        $counters = [];
        if (file_exists($filePath)) {
            $counters = json_decode(file_get_contents($filePath), true) ?: [];
        }

        $counters[$kioskId] = $count;
        file_put_contents($filePath, json_encode($counters));

        return $this->json(['success' => true]);
    }
}
