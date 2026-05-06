<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\TalentTestService;
use App\Core\Database;
use PDO;

class TalentTestController extends Controller
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new TalentTestService();
    }

    /**
     * Danh sách các đợt thi năng khiếu
     */
    public function index()
    {
        $this->requireAdmin();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM talent_test_sessions ORDER BY year DESC, start_date DESC");
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/talent_tests/index', [
            'title' => 'Quản lý Thi năng khiếu',
            'sessions' => $sessions
        ]);
    }

    /**
     * Form tạo đợt thi mới
     */
    public function create()
    {
        $this->requireAdmin();
        $this->view('admin/talent_tests/form', [
            'title' => 'Tạo đợt thi năng khiếu mới',
            'session' => null
        ]);
    }

    /**
     * Lưu đợt thi mới
     */
    public function store()
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $data = [
            'year' => (int)$_POST['year'],
            'session_name' => $_POST['session_name'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'description' => $_POST['description']
        ];

        $sessionId = $this->service->createSession($data);

        // Lưu các môn thi (ngành)
        if (isset($_POST['majors']) && is_array($_POST['majors'])) {
            $majorNames = [
                '7140201' => 'Giáo dục mầm non',
                '7140206' => 'Giáo dục thể chất',
                '7140221' => 'Sư phạm Âm nhạc',
                '7140222' => 'Sư phạm Mỹ thuật'
            ];

            foreach ($_POST['majors'] as $code) {
                if (isset($majorNames[$code])) {
                    $this->service->addSubject($sessionId, [
                        'major_code' => $code,
                        'subject_name' => 'Thi năng khiếu ' . $majorNames[$code],
                        'max_score' => 100
                    ]);
                }
            }
        }

        $this->redirect(url('/admin/talent-tests?success=1'));
    }

    /**
     * Trang chi tiết đợt thi (Quản lý thí sinh, phòng, điểm)
     */
    public function edit()
    {
        $this->requireAdmin();
        $id = (int)$_GET['id'];
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            $this->redirect(url('/admin/talent-tests'));
        }

        $stmt = $db->prepare("SELECT * FROM talent_test_subjects WHERE session_id = ?");
        $stmt->execute([$id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT * FROM talent_test_rooms WHERE session_id = ?");
        $stmt->execute([$id]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/talent_tests/detail', [
            'title' => 'Chi tiết đợt thi: ' . $session['session_name'],
            'session' => $session,
            'subjects' => $subjects,
            'rooms' => $rooms
        ]);
    }

    /**
     * Đồng bộ thí sinh
     */
    public function sync()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT major_code FROM talent_test_subjects WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $majorCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($majorCodes)) {
            $count = $this->service->syncCandidates($sessionId, $majorCodes);
            $this->redirect(url('/admin/talent-tests/edit?id=' . $sessionId . '&synced=' . $count));
        } else {
            $this->redirect(url('/admin/talent-tests/edit?id=' . $sessionId . '&error=no_subjects'));
        }
    }

    /**
     * Công bố/Hủy công bố điểm
     */
    public function togglePublish()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];
        $status = (int)$_POST['status']; // 1: Publish, 0: Unpublish

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE talent_test_sessions SET is_published = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $sessionId]);

        $this->redirect(url('/admin/talent-tests/edit?id=' . $sessionId . '&published=' . $status));
    }

    /**
     * Thêm phòng thi
     */
    public function saveRoom()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];
        
        $this->service->addRoom($sessionId, [
            'room_name' => $_POST['room_name'],
            'capacity' => (int)$_POST['capacity']
        ]);

        $this->redirect(url('/admin/talent-tests/edit?id=' . $sessionId . '#rooms'));
    }

    /**
     * Phân phòng thi tự động
     */
    public function autoAssignRooms()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];

        $count = $this->service->autoAssignRooms($sessionId);
        $this->redirect(url('/admin/talent-tests/edit?id=' . $sessionId . '&assigned=' . $count));
    }

    /**
     * Đánh số túi bài thi
     */
    public function assignBags()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];
        $prefix = $_POST['prefix'] ?: 'TUI-';

        $count = $this->service->assignBagNumbers($sessionId, $prefix);
        $this->redirect(url('/admin/talent-tests/edit?id=' . $sessionId . '&bags=' . $count));
    }

    /**
     * In Thẻ dự thi
     */
    public function printCards()
    {
        $this->requireAdmin();
        $sessionId = (int)$_GET['session_id'];
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT a.*, c.name, c.cccd, c.birth_date, s.subject_name, r.room_name
            FROM talent_test_assignments a
            JOIN candidates c ON c.id = a.candidate_id
            JOIN talent_test_subjects s ON s.id = a.subject_id
            LEFT JOIN talent_test_rooms r ON r.id = a.room_id
            WHERE s.session_id = ?
            ORDER BY r.room_name, a.exam_number
        ");
        $stmt->execute([$sessionId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/talent_tests/print_cards', [
            'session' => $session,
            'assignments' => $assignments
        ], false);
    }

    /**
     * In Sổ ảnh
     */
    public function printPhotos()
    {
        $this->requireAdmin();
        $sessionId = (int)$_GET['session_id'];
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT a.*, c.name, c.cccd, c.birth_date, s.subject_name, r.room_name
            FROM talent_test_assignments a
            JOIN candidates c ON c.id = a.candidate_id
            JOIN talent_test_subjects s ON s.id = a.subject_id
            LEFT JOIN talent_test_rooms r ON r.id = a.room_id
            WHERE s.session_id = ?
            ORDER BY r.room_name, a.exam_number
        ");
        $stmt->execute([$sessionId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/talent_tests/print_photos', [
            'session' => $session,
            'assignments' => $assignments
        ], false);
    }

    /**
     * Quản lý điểm
     */
    public function scores()
    {
        $this->requireAdmin();
        $sessionId = (int)$_GET['session_id'];
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT a.id, c.name, c.cccd, s.subject_name, r.room_name, a.exam_number, sc.score, sc.note
            FROM talent_test_assignments a
            JOIN candidates c ON c.id = a.candidate_id
            JOIN talent_test_subjects s ON s.id = a.subject_id
            LEFT JOIN talent_test_rooms r ON r.id = a.room_id
            LEFT JOIN talent_test_scores sc ON sc.assignment_id = a.id
            WHERE s.session_id = ?
            ORDER BY s.id, a.exam_number
        ");
        $stmt->execute([$sessionId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/talent_tests/scores', [
            'title' => 'Quản lý điểm thi năng khiếu',
            'sessionId' => $sessionId,
            'assignments' => $assignments
        ]);
    }

    /**
     * Lưu điểm
     */
    public function saveScore()
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $assignmentId = (int)$_POST['assignment_id'];
        $score = (float)$_POST['score'];
        $note = $_POST['note'] ?? null;

        $this->service->saveScore($assignmentId, $score, $note);

        if (isset($_POST['ajax'])) {
            echo json_encode(['success' => true]);
            exit;
        }

        $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * Xuất file Excel điểm
     */
    public function exportExcel()
    {
        $this->requireAdmin();
        $sessionId = (int)$_GET['session_id'];
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT a.exam_number, c.name, c.cccd, s.major_code, s.subject_name, sc.score, sc.note
            FROM talent_test_assignments a
            JOIN candidates c ON c.id = a.candidate_id
            JOIN talent_test_subjects s ON s.id = a.subject_id
            LEFT JOIN talent_test_scores sc ON sc.assignment_id = a.id
            WHERE s.session_id = ?
            ORDER BY a.exam_number
        ");
        $stmt->execute([$sessionId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'SBD');
        $sheet->setCellValue('B1', 'Họ tên');
        $sheet->setCellValue('C1', 'CCCD');
        $sheet->setCellValue('D1', 'Mã ngành');
        $sheet->setCellValue('E1', 'Môn thi');
        $sheet->setCellValue('F1', 'Điểm');
        $sheet->setCellValue('G1', 'Ghi chú');

        // Prevent CSV Injection
        $sanitize = function($value) {
            if (is_string($value) && preg_match('/^[=\+\-@\t\r\n]/', $value)) {
                return "'" . $value;
            }
            return $value;
        };

        $row = 2;
        foreach ($data as $d) {
            $sheet->setCellValue('A' . $row, $d['exam_number']);
            $sheet->setCellValue('B' . $row, $sanitize($d['name']));
            $sheet->setCellValue('C' . $row, $d['cccd']);
            $sheet->setCellValue('D' . $row, $d['major_code']);
            $sheet->setCellValue('E' . $row, $d['subject_name']);
            $sheet->setCellValue('F' . $row, $d['score']);
            $sheet->setCellValue('G' . $row, $sanitize($d['note']));
            $row++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Diem_Nang_Khieu_' . $sessionId . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Dashboard thống kê đợt thi
     */
    public function dashboard()
    {
        $this->requireAdmin();
        $sessionId = (int)$_GET['session_id'];
        $db = Database::getInstance()->getConnection();

        // 1. Thông tin chung đợt thi
        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Thống kê tổng số thí sinh & Đã nhập điểm
        $stmt = $db->prepare("
            SELECT COUNT(*) as total, 
                   COUNT(sc.id) as graded
            FROM talent_test_assignments a
            JOIN talent_test_subjects s ON s.id = a.subject_id
            LEFT JOIN talent_test_scores sc ON sc.assignment_id = a.id
            WHERE s.session_id = ?
        ");
        $stmt->execute([$sessionId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Thống kê theo từng ngành (Subject)
        $stmt = $db->prepare("
            SELECT s.subject_name, COUNT(a.id) as count
            FROM talent_test_subjects s
            LEFT JOIN talent_test_assignments a ON a.subject_id = s.id
            WHERE s.session_id = ?
            GROUP BY s.id, s.subject_name
        ");
        $stmt->execute([$sessionId]);
        $subjectStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Phổ điểm (Range)
        $stmt = $db->prepare("
            SELECT 
                CASE 
                    WHEN sc.score < 5 THEN '< 5'
                    WHEN sc.score >= 5 AND sc.score < 7 THEN '5 - 7'
                    WHEN sc.score >= 7 AND sc.score < 9 THEN '7 - 9'
                    WHEN sc.score >= 9 THEN '>= 9'
                    ELSE 'Chưa chấm'
                END as range,
                COUNT(*) as count
            FROM talent_test_assignments a
            JOIN talent_test_subjects s ON s.id = a.subject_id
            LEFT JOIN talent_test_scores sc ON sc.assignment_id = a.id
            WHERE s.session_id = ?
            GROUP BY range
        ");
        $stmt->execute([$sessionId]);
        $scoreDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/talent_tests/dashboard', [
            'title' => 'Báo cáo thống kê: ' . $session['session_name'],
            'session' => $session,
            'stats' => $stats,
            'subjectStats' => $subjectStats,
            'scoreDistribution' => $scoreDistribution
        ]);
    }
}
