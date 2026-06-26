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
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) $this->redirect(url('/admin/talent-tests'));
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

        $stats = $this->service->getSessionStats($id);

        $this->view('admin/talent_tests/detail', [
            'title' => 'Chi tiết đợt thi: ' . $session['session_name'],
            'session' => $session,
            'subjects' => $subjects,
            'rooms' => $rooms,
            'stats' => $stats
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
        $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
        if ($sessionId <= 0) $this->redirect(url('/admin/talent-tests'));
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT a.*, c.ho_va_ten AS name, c.so_cccd AS cccd, c.ngay_sinh AS birth_date, s.subject_name, r.room_name
            FROM talent_test_assignments a
            JOIN thi_sinh c ON c.id = a.candidate_id
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
        $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
        if ($sessionId <= 0) $this->redirect(url('/admin/talent-tests'));
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT a.*, c.ho_va_ten AS name, c.so_cccd AS cccd, c.ngay_sinh AS birth_date, s.subject_name, r.room_name
            FROM talent_test_assignments a
            JOIN thi_sinh c ON c.id = a.candidate_id
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
        $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
        if ($sessionId <= 0) $this->redirect(url('/admin/talent-tests'));
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$session) $this->redirect(url('/admin/talent-tests'));

        $stmt = $db->prepare("
            SELECT a.id, c.ho_va_ten AS name, c.so_cccd AS cccd, s.subject_name, s.max_score,
                   r.room_name, a.exam_number, sc.score, sc.note
            FROM talent_test_assignments a
            JOIN thi_sinh c ON c.id = a.candidate_id
            JOIN talent_test_subjects s ON s.id = a.subject_id
            LEFT JOIN talent_test_rooms r ON r.id = a.room_id
            LEFT JOIN talent_test_scores sc ON sc.assignment_id = a.id
            WHERE s.session_id = ? AND a.is_eligible = TRUE
            ORDER BY s.id, a.exam_number
        ");
        $stmt->execute([$sessionId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/talent_tests/scores', [
            'title' => 'Quản lý điểm thi năng khiếu - ' . $session['session_name'],
            'session' => $session,
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
        $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
        if ($sessionId <= 0) $this->redirect(url('/admin/talent-tests'));
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT a.exam_number, c.ho_va_ten AS name, c.so_cccd AS cccd, s.major_code, s.subject_name, sc.score, sc.note
            FROM talent_test_assignments a
            JOIN thi_sinh c ON c.id = a.candidate_id
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
        $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
        if ($sessionId <= 0) $this->redirect(url('/admin/talent-tests'));
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

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

        $stmt = $db->prepare("
            SELECT s.subject_name, COUNT(a.id) as count
            FROM talent_test_subjects s
            LEFT JOIN talent_test_assignments a ON a.subject_id = s.id
            WHERE s.session_id = ?
            GROUP BY s.id, s.subject_name
        ");
        $stmt->execute([$sessionId]);
        $subjectStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    // =====================================================================
    // Phase 2: Danh sách xét tuyển
    // =====================================================================

    public function candidates()
    {
        $this->requireAdmin();
        $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
        if ($sessionId <= 0) $this->redirect(url('/admin/talent-tests'));

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$session) $this->redirect(url('/admin/talent-tests'));

        $stmt = $db->prepare("SELECT * FROM talent_test_subjects WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $eligible = $this->service->getEligibleCandidates($sessionId);
        $ineligible = $this->service->getIneligibleCandidates($sessionId);

        $this->view('admin/talent_tests/candidates', [
            'title' => 'Danh sách xét tuyển - ' . $session['session_name'],
            'session' => $session,
            'subjects' => $subjects,
            'eligible' => $eligible,
            'ineligible' => $ineligible
        ]);
    }

    public function toggleEligibility()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];
        $ids = $_POST['ids'] ?? [];
        $action = $_POST['action'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if (!is_array($ids)) $ids = [$ids];
        $ids = array_map('intval', $ids);

        if ($action === 'mark_ineligible' && !empty($ids)) {
            $count = $this->service->markIneligible($ids, $reason ?: 'Không đủ điều kiện dự thi');
        } elseif ($action === 'mark_eligible' && !empty($ids)) {
            $count = $this->service->markEligible($ids);
        } else {
            $count = 0;
        }

        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'count' => $count]);
            exit;
        }

        $this->redirect(url('/admin/talent-tests/candidates?session_id=' . $sessionId . '&updated=' . $count));
    }

    public function removeCandidate()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];
        $assignmentId = (int)$_POST['assignment_id'];

        $this->service->removeAssignment($assignmentId);
        $this->redirect(url('/admin/talent-tests/candidates?session_id=' . $sessionId . '&removed=1'));
    }

    // =====================================================================
    // Phase 3: Lập số báo danh
    // =====================================================================

    public function examNumbers()
    {
        $this->requireAdmin();
        $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
        if ($sessionId <= 0) $this->redirect(url('/admin/talent-tests'));

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$session) $this->redirect(url('/admin/talent-tests'));

        $eligible = $this->service->getEligibleCandidates($sessionId);
        $maxSbd = $this->service->getMaxExamNumber($sessionId);

        $prefix = $this->service->getConfig($sessionId, 'sbd_prefix', 'THV.M.');
        $length = (int)$this->service->getConfig($sessionId, 'sbd_length', '3');
        $startFrom = (int)$this->service->getConfig($sessionId, 'sbd_start', '1');

        $this->view('admin/talent_tests/exam_numbers', [
            'title' => 'Lập số báo danh - ' . $session['session_name'],
            'session' => $session,
            'candidates' => $eligible,
            'maxSbd' => $maxSbd,
            'prefix' => $prefix,
            'length' => $length ?: 3,
            'startFrom' => $startFrom ?: 1
        ]);
    }

    public function generateExamNumbers()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];
        $prefix = trim($_POST['prefix'] ?? 'THV.M.');
        $length = (int)($_POST['length'] ?? 3);
        $startFrom = (int)($_POST['start_from'] ?? 1);

        $count = $this->service->generateExamNumbers($sessionId, $prefix, $length, $startFrom);
        $this->redirect(url('/admin/talent-tests/exam-numbers?session_id=' . $sessionId . '&generated=' . $count));
    }

    public function clearExamNumbers()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];
        $count = $this->service->clearExamNumbers($sessionId);
        $this->redirect(url('/admin/talent-tests/exam-numbers?session_id=' . $sessionId . '&cleared=' . $count));
    }

    // =====================================================================
    // Phase 4: Phân phòng thi
    // =====================================================================

    public function roomAssignment()
    {
        $this->requireAdmin();
        $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
        if ($sessionId <= 0) $this->redirect(url('/admin/talent-tests'));

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$session) $this->redirect(url('/admin/talent-tests'));

        $rooms = $this->service->getRoomsWithCount($sessionId);
        $unassigned = $this->service->getUnassignedCandidates($sessionId);

        // Get all eligible candidates for the right panel
        $allCandidates = $this->service->getEligibleCandidates($sessionId);

        $this->view('admin/talent_tests/room_assignment', [
            'title' => 'Phân phòng thi - ' . $session['session_name'],
            'session' => $session,
            'rooms' => $rooms,
            'unassigned' => $unassigned,
            'allCandidates' => $allCandidates
        ]);
    }

    public function autoCreateRooms()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];
        $perRoom = (int)($_POST['per_room'] ?? 29);
        $startNum = (int)($_POST['start_num'] ?? 1);

        $count = $this->service->autoCreateRooms($sessionId, $perRoom, $startNum);
        $this->redirect(url('/admin/talent-tests/room-assignment?session_id=' . $sessionId . '&created_rooms=' . $count));
    }

    public function deleteAllRooms()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];
        $count = $this->service->deleteAllRooms($sessionId);
        $this->redirect(url('/admin/talent-tests/room-assignment?session_id=' . $sessionId . '&deleted_rooms=' . $count));
    }

    public function deleteRoomAction()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];
        $roomId = (int)$_POST['room_id'];
        $this->service->deleteRoom($roomId);
        $this->redirect(url('/admin/talent-tests/room-assignment?session_id=' . $sessionId . '&room_deleted=1'));
    }

    public function resetRoomAssignments()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];
        $count = $this->service->resetRoomAssignments($sessionId);
        $this->redirect(url('/admin/talent-tests/room-assignment?session_id=' . $sessionId . '&reset=' . $count));
    }

    public function getRoomCandidatesApi()
    {
        $this->requireAdmin();
        $roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
        header('Content-Type: application/json');
        if ($roomId <= 0) {
            echo json_encode(['candidates' => []]);
            exit;
        }
        $candidates = $this->service->getCandidatesByRoom($roomId);
        echo json_encode(['candidates' => $candidates]);
        exit;
    }

    public function moveCandidateRoom()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $assignmentId = (int)$_POST['assignment_id'];
        $roomId = !empty($_POST['room_id']) ? (int)$_POST['room_id'] : null;
        $this->service->moveCandidate($assignmentId, $roomId);

        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        $this->redirect($_SERVER['HTTP_REFERER'] ?? url('/admin/talent-tests'));
    }

    // =====================================================================
    // Phase 5: Tổ chức thi - Môn thi & In ấn
    // =====================================================================

    public function examConfig()
    {
        $this->requireAdmin();
        $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
        $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
        if ($sessionId <= 0) $this->redirect(url('/admin/talent-tests'));

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT * FROM talent_test_subjects WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $currentSubject = null;
        $subjectCandidates = [];
        $subjectRooms = [];

        if ($subjectId > 0) {
            $currentSubject = $this->service->getSubjectWithDetails($subjectId);
            $subjectCandidates = $this->service->getCandidatesBySubject($subjectId);
            $subjectRooms = $this->service->getRoomsBySubject($sessionId, $subjectId);
        } elseif (!empty($subjects)) {
            $subjectId = $subjects[0]['id'];
            $currentSubject = $this->service->getSubjectWithDetails($subjectId);
            $subjectCandidates = $this->service->getCandidatesBySubject($subjectId);
            $subjectRooms = $this->service->getRoomsBySubject($sessionId, $subjectId);
        }

        $this->view('admin/talent_tests/exam_config', [
            'title' => 'Tổ chức thi - Môn thi',
            'session' => $session,
            'subjects' => $subjects,
            'currentSubject' => $currentSubject,
            'subjectCandidates' => $subjectCandidates,
            'subjectRooms' => $subjectRooms
        ]);
    }

    public function saveExamConfig()
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $sessionId = (int)$_POST['session_id'];
        $subjectId = (int)$_POST['subject_id'];

        $this->service->updateSubjectExamConfig($subjectId, [
            'exam_type' => $_POST['exam_type'] ?? 'written',
            'duration_minutes' => (int)($_POST['duration_minutes'] ?? 120),
            'exam_date' => $_POST['exam_date'] ?? null,
            'exam_time' => $_POST['exam_time'] ?? null,
            'preparation_minutes' => (int)($_POST['preparation_minutes'] ?? 15),
        ]);

        $this->redirect(url('/admin/talent-tests/exam-config?session_id=' . $sessionId . '&subject_id=' . $subjectId . '&saved=1'));
    }

    public function printRoomList()
    {
        $this->requireAdmin();
        $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
        if ($sessionId <= 0) $this->redirect(url('/admin/talent-tests'));

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        $rooms = $this->service->getRoomsWithCount($sessionId);

        $this->view('admin/talent_tests/print_room_list', [
            'session' => $session,
            'rooms' => $rooms
        ], false);
    }

    public function printExamNotice()
    {
        $this->requireAdmin();
        $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
        if ($sessionId <= 0) $this->redirect(url('/admin/talent-tests'));

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM talent_test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT * FROM talent_test_subjects WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT a.*, c.ho_va_ten AS name, c.so_cccd AS cccd, c.ngay_sinh AS birth_date,
                   s.subject_name, s.exam_date, s.exam_time, s.duration_minutes, s.exam_type,
                   r.room_name
            FROM talent_test_assignments a
            JOIN thi_sinh c ON c.id = a.candidate_id
            JOIN talent_test_subjects s ON s.id = a.subject_id
            LEFT JOIN talent_test_rooms r ON r.id = a.room_id
            WHERE s.session_id = ? AND a.is_eligible = TRUE
            ORDER BY a.exam_number
        ");
        $stmt->execute([$sessionId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/talent_tests/print_exam_notice', [
            'session' => $session,
            'subjects' => $subjects,
            'assignments' => $assignments
        ], false);
    }
}

