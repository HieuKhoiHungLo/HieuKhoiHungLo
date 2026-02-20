<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\ExportService;
use App\Services\PermissionService;
use App\Models\MasterData;

class ReportController extends Controller {
    protected $exportService;
    protected $permissionService;
    protected $masterData;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->exportService = new ExportService();
        $this->permissionService = new PermissionService();
        $this->masterData = new MasterData();
    }

    public function index() {
        $majors = $this->masterData->getMajors();
        $sessions = $this->masterData->getSessions();
        $stats = $this->exportService->getStatistics();

        $this->view('admin/reports/index', [
            'majors' => $majors,
            'sessions' => $sessions,
            'stats' => $stats
        ]);
    }

    public function exportCandidates() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }

        $filters = [
            'status' => $_GET['status'] ?? null,
            'session_id' => $_GET['session_id'] ?? null,
        ];

        $data = $this->exportService->exportCandidatesToCsv($filters);
        $this->exportService->toCsv($data, 'danh_sach_thi_sinh_' . date('Ymd') . '.csv');
    }

    public function exportAdmitted() {
        if (!$this->permissionService->can('report.export')) {
            die('Không có quyền xuất báo cáo.');
        }

        $maNganh = $_GET['ma_nganh'] ?? null;
        if (!$maNganh) {
            die('Vui lòng chọn ngành.');
        }

        $data = $this->exportService->exportAdmittedByMajor($maNganh);
        $this->exportService->toCsv($data, 'trung_tuyen_' . $maNganh . '_' . date('Ymd') . '.csv');
    }

    public function statsApi() {
        header('Content-Type: application/json');
        echo json_encode($this->exportService->getStatistics());
        exit;
    }
}
