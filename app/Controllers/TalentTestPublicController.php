<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class TalentTestPublicController extends Controller
{
    /**
     * Trang chủ tra cứu
     */
    public function index()
    {
        $this->view('public/talent_tests/lookup', [
            'title' => 'Tra cứu kết quả thi Năng khiếu'
        ], false); // Sử dụng layout public riêng hoặc không layout
    }

    /**
     * Xử lý tra cứu
     */
    public function search()
    {
        $this->validateCsrf();
        $keyword = trim($_POST['keyword'] ?? '');
        
        if (empty($keyword)) {
            $this->redirect(url('/tra-cuu-nang-khieu?error=empty'));
        }

        $db = Database::getInstance()->getConnection();
        
        // Tìm kiếm theo CCCD hoặc SBD
        $stmt = $db->prepare("
            SELECT a.exam_number, c.ho_va_ten AS name, c.so_cccd AS cccd, c.ngay_sinh AS birth_date, 
                   s.subject_name, r.room_name, sc.score, sc.note,
                   sess.session_name, sess.year
            FROM talent_test_assignments a
            JOIN thi_sinh c ON c.id = a.candidate_id
            JOIN talent_test_subjects s ON s.id = a.subject_id
            JOIN talent_test_sessions sess ON sess.id = s.session_id
            LEFT JOIN talent_test_rooms r ON r.id = a.room_id
            LEFT JOIN talent_test_scores sc ON sc.assignment_id = a.id
            WHERE (c.so_cccd = ? OR a.exam_number = ?)
            ORDER BY sess.year DESC
            LIMIT 1
        ");
        $stmt->execute([$keyword, $keyword]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $this->redirect(url('/tra-cuu-nang-khieu?error=not_found'));
        }

        $this->view('public/talent_tests/result', [
            'title' => 'Kết quả tra cứu: ' . $result['name'],
            'data' => $result
        ], false);
    }
}
