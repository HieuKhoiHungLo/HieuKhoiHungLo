<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\ExportService;
use App\Services\PermissionService;
use App\Models\MasterData;
use App\Core\Database;

class ReportController extends Controller {
    protected $exportService;
    protected $permissionService;
    protected $masterData;
    protected $db;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->exportService = new ExportService();
        $this->permissionService = new PermissionService();
        $this->masterData = new MasterData();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index() {
        $majors   = $this->masterData->getMajors();
        $sessions = $this->masterData->getAll('dot_tuyen_sinh', 'nam_tuyen_sinh DESC, id DESC');
        
        // Find active session to determine initial filters
        $activeSession = null;
        foreach ($sessions as $s) {
            if (!empty($s['kich_hoat'])) { $activeSession = $s; break; }
        }

        // Determine current selected year
        $years = [];
        foreach ($sessions as $s) {
            $y = $s['nam_tuyen_sinh'] ?? null;
            if ($y && !in_array($y, $years)) $years[] = $y;
        }
        rsort($years);

        $selectedYear = $_GET['year'] ?? ($activeSession['nam_tuyen_sinh'] ?? ($years[0] ?? null));
        $yearSessions = array_values(array_filter($sessions, fn($s) => ($s['nam_tuyen_sinh'] ?? null) == $selectedYear));

        $selectedSessionId = $_GET['session_id'] ?? null;
        if (!$selectedSessionId) {
            if ($activeSession && ($activeSession['nam_tuyen_sinh'] ?? null) == $selectedYear) {
                $selectedSessionId = $activeSession['id'];
            } elseif (!empty($yearSessions)) {
                $selectedSessionId = $yearSessions[0]['id'];
            }
        }

        // Fetch stats filtered by the current selected session
        $stats = $this->exportService->getStatistics(['session_id' => $selectedSessionId]);

        $this->view('admin/reports/index', [
            'majors'            => $majors,
            'sessions'          => $sessions,
            'stats'             => $stats,
            'years'             => $years,
            'yearSessions'      => $yearSessions,
            'selectedYear'      => $selectedYear,
            'selectedSessionId' => $selectedSessionId,
            'allSessions'       => $sessions, // for JS filtering
        ]);
    }

    public function exportCandidates() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }

        $filters = [
            'status'     => $_GET['status'] ?? null,
            'session_id' => $_GET['session_id'] ?? null,
        ];

        $data = $this->exportService->exportCandidatesToCsv($filters);
        $this->exportService->toExcel($data, 'danh_sach_thi_sinh_' . date('Ymd') . '.xls');
    }

    public function exportAdmitted() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }

        $maNganh = $_GET['ma_nganh'] ?? null;
        if (!$maNganh) {
            die('Vui lòng chọn ngành.');
        }

        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];

        $data = $this->exportService->exportAdmittedByMajor($maNganh, $filters);
        $this->exportService->toExcel($data, 'trung_tuyen_' . $maNganh . '_' . date('Ymd') . '.xls');
    }

    public function exportCertificates() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }

        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];
        $data = $this->exportService->exportCertificatesFiltered($filters);
        $this->exportService->toExcel($data, 'danh_sach_chung_chi_nn_' . date('Ymd') . '.xls');
    }

    public function exportAptitudeList() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }

        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];
        $data = $this->exportService->exportAptitudeList($filters);
        $this->exportService->toExcel($data, 'danh_sach_thi_nang_khieu_' . date('Ymd') . '.xls');
    }

    public function statsApi() {
        header('Content-Type: application/json');
        $filters = [
            'session_id' => $_GET['session_id'] ?? null
        ];
        echo json_encode($this->exportService->getStatistics($filters));
        exit;
    }

    public function exportMoetInfo() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];
        $data = $this->exportService->exportMoetInfoCsv($filters);
        $this->exportService->toExcel($data, 'moet_thong_tin_diem_thpt_' . date('Ymd') . '.xls');
    }

    public function exportMoetWishes() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];
        $data = $this->exportService->exportMoetWishesCsv($filters);
        $this->exportService->toExcel($data, 'moet_nguyen_vong_' . date('Ymd') . '.xls');
    }

    public function exportMoetTranscripts() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
        ];
        $data = $this->exportService->exportMoetTranscriptsCsv($filters);
        $this->exportService->toExcel($data, 'moet_diem_hoc_ba_' . date('Ymd') . '.xls');
    }

    public function exportAdmissionReport() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
            'major'      => $_GET['major'] ?? null,
        ];
        $data = $this->exportService->exportAdmissionData($filters);
        $this->exportService->toExcel($data, 'du_lieu_xet_tuyen_' . date('Ymd') . '.xls');
    }

    public function exportAllAdmittedReport() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }
        $filters = [
            'session_id' => $_GET['session_id'] ?? null,
        ];
        $data = $this->exportService->exportAdmittedByMajor(null, $filters);
        $this->exportService->toExcel($data, 'danh_sach_trung_tuyen_toan_bo_' . date('Ymd') . '.xls');
    }
}
