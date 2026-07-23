<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;
use App\Models\AdmissionSession;
use App\Core\Database;
use PDO;

class AdmissionManagementController extends Controller {
    protected $db;
    protected $masterData;
    protected $sessionModel;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->db = Database::getInstance()->getConnection();
        $this->masterData = new MasterData();
        $this->sessionModel = new AdmissionSession();
    }

    public function index() {
        $years = $this->db->query("SELECT DISTINCT nam_tuyen_sinh FROM dot_tuyen_sinh ORDER BY nam_tuyen_sinh DESC")->fetchAll(PDO::FETCH_COLUMN);
        $activeSession = $this->sessionModel->getActiveSession() ?? $this->sessionModel->getLatestActiveSession();
        
        $this->view('admin/admission/management', [
            'title' => 'Thiết lập Điểm chuẩn',
            'years' => $years,
            'activeSession' => $activeSession
        ]);
    }

    public function apiGetSessions() {
        $year = $_GET['year'] ?? null;
        if (!$year) {
            $this->json(['status' => false, 'message' => 'Năm không hợp lệ']);
            return;
        }

        $stmt = $this->db->prepare("SELECT id, ten_dot, nam_tuyen_sinh, kich_hoat FROM dot_tuyen_sinh WHERE nam_tuyen_sinh = ? ORDER BY id DESC");
        $stmt->execute([$year]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->json(['status' => true, 'sessions' => $sessions]);
    }

    public function apiGetData() {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) {
            $this->json(['status' => false, 'message' => 'Đợt tuyển sinh không hợp lệ']);
            return;
        }

        // Lấy tất cả ngành - chi_tieu và kich_hoat từ dm_nganh (Source of Truth)
        $allMajors = $this->db->query("SELECT ma_nganh, ten_nganh, nhom_nganh, chi_tieu, nguong_hoc_luc, nguong_diem_thpt, COALESCE(kich_hoat, true) as kich_hoat FROM dm_nganh ORDER BY ma_nganh ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Lấy cấu hình điểm chuẩn cho đợt này bao gồm cả cột kich_hoat
        $stmt = $this->db->prepare("SELECT ma_nganh, diem_chuan, tieuchi_phu, kich_hoat FROM admission_benchmarks WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $benchmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $benchmarkMap = [];
        $hasSavedConfig = count($benchmarks) > 0;
        foreach ($benchmarks as $b) {
            $benchmarkMap[$b['ma_nganh']] = $b;
        }

        foreach ($allMajors as &$m) {
            $mCode = $m['ma_nganh'];
            $hasBenchmarkRecord = isset($benchmarkMap[$mCode]);
            
            // chi_tieu luôn từ dm_nganh (read-only trên UI)
            $m['chi_tieu'] = (int)($m['chi_tieu'] ?? 0);
            $m['diem_chuan'] = $hasBenchmarkRecord ? $benchmarkMap[$mCode]['diem_chuan'] : 0;
            $m['tieuchi_phu'] = $hasBenchmarkRecord ? $benchmarkMap[$mCode]['tieuchi_phu'] : '';
            
            if ($hasSavedConfig) {
                // Nếu đợt tuyển sinh này ĐÃ có cấu hình lưu trong DB: lấy theo cột kich_hoat của đợt này
                $m['kich_hoat'] = $hasBenchmarkRecord ? ($benchmarkMap[$mCode]['kich_hoat'] === true || $benchmarkMap[$mCode]['kich_hoat'] === 't' || $benchmarkMap[$mCode]['kich_hoat'] === '1' || $benchmarkMap[$mCode]['kich_hoat'] === 1 || $benchmarkMap[$mCode]['kich_hoat'] == 'true') : false;
                $m['has_benchmark'] = $m['kich_hoat'];
            } else {
                // Nếu là đợt tuyển sinh mới (chưa có cấu hình), mặc định theo dm_nganh.kich_hoat
                $m['kich_hoat'] = ($m['kich_hoat'] === true || $m['kich_hoat'] === 't' || $m['kich_hoat'] === '1' || $m['kich_hoat'] === 1 || $m['kich_hoat'] == 'true');
                $m['has_benchmark'] = $m['kich_hoat'];
            }
        }

        $this->json(['status' => true, 'data' => $allMajors]);
    }

    public function apiSave() {
        $sessionId = $_POST['session_id'] ?? null;
        $data = $_POST['data'] ?? [];

        if (!$sessionId) {
            $this->json(['status' => false, 'message' => 'Dữ liệu không hợp lệ']);
            return;
        }

        $this->db->beginTransaction();
        try {
            $stmtDel = $this->db->prepare("DELETE FROM admission_benchmarks WHERE session_id = ?");
            $stmtDel->execute([$sessionId]);

            $stmtIns = $this->db->prepare("INSERT INTO admission_benchmarks (session_id, ma_nganh, diem_chuan, tieuchi_phu, kich_hoat) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($data as $item) {
                $maNganh = $item['ma_nganh'];
                $isKichHoat = isset($item['has_benchmark']) && ($item['has_benchmark'] == 'true' || $item['has_benchmark'] === true || $item['has_benchmark'] == 1);
                
                // Nếu không kích hoạt trong đợt này, điểm chuẩn mặc định 0 và tiêu chí phụ null
                $diemChuan = $isKichHoat && isset($item['diem_chuan']) ? (float)$item['diem_chuan'] : 0.0;
                $tieuchiPhu = $isKichHoat && isset($item['tieuchi_phu']) && trim($item['tieuchi_phu']) !== '' ? $item['tieuchi_phu'] : null;

                $stmtIns->execute([
                    $sessionId,
                    $maNganh,
                    round($diemChuan, 3),
                    $tieuchiPhu,
                    $isKichHoat ? 1 : 0
                ]);
            }

            $this->db->commit();
            $this->json(['status' => true, 'message' => 'Lưu cấu hình thành công']);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['status' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }
    public function exportExcel() {
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(403);
            exit('Unauthorized');
        }

        $sessionId = $_GET['session_id'] ?? null;
        $sessionName = 'Tat_ca';

        // Fetch session info for filename
        if ($sessionId) {
            $stmtS = $this->db->prepare("SELECT ten_dot, nam_tuyen_sinh FROM dot_tuyen_sinh WHERE id = ?");
            $stmtS->execute([$sessionId]);
            $sessionInfo = $stmtS->fetch(PDO::FETCH_ASSOC);
            if ($sessionInfo) {
                $sessionName = $sessionInfo['nam_tuyen_sinh'] . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $sessionInfo['ten_dot']);
            }
        }

        // Fetch all majors
        $allMajors = $this->db->query(
            "SELECT ma_nganh, ten_nganh, nhom_nganh, chi_tieu, nguong_hoc_luc, nguong_diem_thpt,
                    COALESCE(kich_hoat, true) as kich_hoat
             FROM dm_nganh ORDER BY ma_nganh ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Fetch benchmarks for the session including column kich_hoat
        $benchmarkMap = [];
        $hasSavedConfig = false;
        if ($sessionId) {
            $stmtB = $this->db->prepare("SELECT ma_nganh, diem_chuan, tieuchi_phu, kich_hoat FROM admission_benchmarks WHERE session_id = ?");
            $stmtB->execute([$sessionId]);
            $benchmarks = $stmtB->fetchAll(PDO::FETCH_ASSOC);
            $hasSavedConfig = count($benchmarks) > 0;
            foreach ($benchmarks as $b) {
                $benchmarkMap[$b['ma_nganh']] = $b;
            }
        }

        // Merge data
        foreach ($allMajors as &$m) {
            $code = $m['ma_nganh'];
            $hasBenchmarkRecord = isset($benchmarkMap[$code]);
            
            $m['has_benchmark'] = $hasBenchmarkRecord && ($benchmarkMap[$code]['kich_hoat'] === true || $benchmarkMap[$code]['kich_hoat'] === 't' || $benchmarkMap[$code]['kich_hoat'] === '1' || $benchmarkMap[$code]['kich_hoat'] === 1 || $benchmarkMap[$code]['kich_hoat'] == 'true');
            $m['diem_chuan']   = $m['has_benchmark'] ? $benchmarkMap[$code]['diem_chuan'] : '';
            $m['tieuchi_phu']  = $m['has_benchmark'] ? ($benchmarkMap[$code]['tieuchi_phu'] ?? '') : '';
            
            if ($hasSavedConfig) {
                $m['kich_hoat'] = $m['has_benchmark'];
            } else {
                $m['kich_hoat'] = in_array($m['kich_hoat'], [true, 't', '1', 1], true) || $m['kich_hoat'] == 'true';
            }
        }
        unset($m);

        // --- Build Excel with PhpSpreadsheet ---
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Điểm chuẩn');

        // ---- Styles ----
        $headerFill  = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']];
        $greenFill   = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']];
        $redFill     = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']];
        $borderThin  = ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']];
        $allBorders  = ['allBorders' => $borderThin];

        // ---- Title row ----
        $sheet->mergeCells('A1:H1');
        $sessionLabel = $sessionId ? ($sessionInfo['ten_dot'] ?? 'Tất cả') : 'Tất cả đợt';
        $sheet->setCellValue('A1', 'DANH SÁCH NGÀNH & ĐIỂM CHUẨN — ' . strtoupper($sessionLabel));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2FF']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // ---- Sub-title ----
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Xuất ngày: ' . date('d/m/Y H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'color' => ['rgb' => '64748B'], 'size' => 10],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // ---- Header row (row 3) ----
        $headers = ['STT', 'Mã ngành', 'Tên ngành', 'Nhóm ngành', 'Chỉ tiêu', 'Ngưỡng HB/Sàn', 'Điểm chuẩn', 'Tiêu chí phụ'];
        $cols    = ['A',  'B',        'C',        'D',           'E',        'F',               'G',           'H'];

        foreach ($headers as $i => $h) {
            $cell = $cols[$i] . '3';
            $sheet->setCellValue($cell, $h);
        }
        $sheet->getStyle('A3:H3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => $headerFill,
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        // ---- Column widths ----
        $widths = [6, 14, 38, 22, 10, 18, 12, 28];
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimension($cols[$i])->setWidth($w);
        }

        // ---- Data rows (start row 4) ----
        $row = 4;
        $stt = 1;
        foreach ($allMajors as $m) {
            $nguong = trim(($m['nguong_hoc_luc'] ? 'HL: ' . $m['nguong_hoc_luc'] : '') . ($m['nguong_diem_thpt'] ? ' | Sàn: ' . $m['nguong_diem_thpt'] : ''), ' |');

            $sheet->setCellValue("A{$row}", $stt++);
            $sheet->setCellValue("B{$row}", $m['ma_nganh']);
            $sheet->setCellValue("C{$row}", $m['ten_nganh']);
            $sheet->setCellValue("D{$row}", $m['nhom_nganh'] ?? '');
            $sheet->setCellValue("E{$row}", (int)($m['chi_tieu'] ?? 0));
            $sheet->setCellValue("F{$row}", $nguong ?: '—');
            $sheet->setCellValue("G{$row}", $m['diem_chuan'] !== '' ? (float)$m['diem_chuan'] : '');
            $sheet->setCellValue("H{$row}", $m['tieuchi_phu'] ?? '');
            
            if ($m['diem_chuan'] !== '') {
                $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('0.000');
            }

            // Row fill: green = has benchmark, red = inactive
            $fillStyle = null;
            if (!$m['kich_hoat']) {
                $fillStyle = $redFill;
            } elseif ($m['has_benchmark']) {
                $fillStyle = $greenFill;
            }

            if ($fillStyle) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()->applyFromArray($fillStyle);
            }

            // Alignment
            $sheet->getStyle("A{$row}:H{$row}")->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;
        }

        // ---- Apply borders to all data ----
        if ($row > 4) {
            $sheet->getStyle("A3:H" . ($row - 1))->getBorders()->applyFromArray($allBorders);
        }

        // ---- Legend row ----
        $sheet->mergeCells("A{$row}:H{$row}");
        $sheet->setCellValue("A{$row}", '* Xanh = Đã có điểm chuẩn | Đỏ = Ngành ngưng tuyển | Trắng = Chưa có điểm chuẩn');
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
        ]);

        // ---- Freeze pane ----
        $sheet->freezePane('A4');

        // ---- Output ----
        $filename = 'DanhSach_Nganh_DiemChuan_' . $sessionName . '_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
