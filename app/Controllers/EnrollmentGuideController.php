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
                ts.anh_the,
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

        // Format response data
        return $this->json([
            'success' => true,
            'data' => [
                'ho_ten'         => $record['ho_ten'] ?? '',
                'so_cccd'        => $record['so_cccd'] ?? '',
                'sbd'            => $record['sbd'] ?? '',
                'ngay_sinh'      => $record['ngay_sinh'] ?? '',
                'ma_nganh'       => $record['ma_nganh'] ?? '',
                'ten_nganh'      => !empty($record['nganh_tt']) ? $record['nganh_tt'] : ($record['ten_nganh'] ?? ''),
                'ten_khoa'       => $record['ten_khoa'] ?? '',
                'so_giay_bao'    => $record['so_giay_bao'] ?? '',
                'thoi_gian_nhap' => $record['thoi_gian_nhap'] ?? '',
                'ban_nhap_hoc'   => $record['ban_nhap_hoc'] ?? '',
                'vi_tri_nhap_hoc'=> $record['vi_tri_nhap_hoc'] ?? '',
                'link_so_do'     => $record['link_so_do'] ?? '',
                'gvcn'           => $record['gvcn'] ?? '',
                'kinh_phi'       => $record['kinh_phi'] ?? '',
                'anh_the'        => $record['anh_the'] ?? '',
            ]
        ]);
    }
}
