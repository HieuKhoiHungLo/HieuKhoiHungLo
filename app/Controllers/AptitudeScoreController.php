<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\DiemNangKhieu;
use App\Models\MasterData;
use App\Core\Auth;

class AptitudeScoreController extends Controller {
    protected $model;
    protected $masterData;

    public function __construct() {
        $this->model = new DiemNangKhieu();
        $this->masterData = new MasterData();
    }

    public function index() {
        $sessionModel = new \App\Models\AdmissionSession();
        $sessions = $sessionModel->getAll();
        
        $db = $this->model->getDb();
        $years = $db->query("SELECT DISTINCT nam_tuyen_sinh FROM dot_tuyen_sinh ORDER BY nam_tuyen_sinh DESC")->fetchAll(\PDO::FETCH_COLUMN);

        $sessionId = $_GET['session_id'] ?? $_POST['session_id'] ?? $_SESSION['admin_selected_session_id'] ?? null;
        
        $selectedSession = null;
        if ($sessionId) {
            $selectedSession = $sessionModel->find($sessionId);
        }
        
        if (!$selectedSession) {
            $selectedSession = $sessionModel->getActiveSession();
            if (!$selectedSession) {
                $selectedSession = $sessionModel->getLatestSession();
            }
        }
        
        $sessionId = $selectedSession ? $selectedSession['id'] : null;
        if ($sessionId) {
            $_SESSION['admin_selected_session_id'] = $sessionId;
        }
        
        $stats = [
            'total' => $db->query("SELECT COUNT(*) FROM diem_nang_khieu" . ($sessionId ? " WHERE dot_tuyen_sinh_id = " . intval($sessionId) : " WHERE dot_tuyen_sinh_id IS NULL"))->fetchColumn(),
        ];
        
        $this->view('admin/aptitude_scores/index', [
            'title' => 'Điểm Năng Khiếu', 
            'stats' => $stats,
            'activeSession' => $selectedSession,
            'sessions' => $sessions,
            'years' => $years,
            'needsDataTables' => true
        ]);
    }

    public function apiList() {
        $db = $this->model->getDb();
        $draw = $_POST['draw'] ?? 1;
        $start = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;
        $searchValue = $_POST['search']['value'] ?? '';

        $sessionId = $_POST['session_id'] ?? $_GET['session_id'] ?? $_SESSION['admin_selected_session_id'] ?? null;
        if (!$sessionId) {
            $sessionModel = new \App\Models\AdmissionSession();
            $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestSession();
            $sessionId = $activeSession ? $activeSession['id'] : null;
        }

        $query = "SELECT d.id, d.so_cccd, d.sbd, d.diem, d.ghi_chu, d.ma_mon, ts.ho_va_ten, m.ten_mon 
                  FROM diem_nang_khieu d
                  LEFT JOIN thi_sinh ts ON d.so_cccd = ts.so_cccd
                  LEFT JOIN dm_mon m ON UPPER(d.ma_mon) = UPPER(m.ma_mon)";
        $params = [];

        if ($sessionId) {
            $query .= " WHERE d.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        } else {
            $query .= " WHERE d.dot_tuyen_sinh_id IS NULL";
        }

        if (!empty($searchValue)) {
            $query .= " AND (d.so_cccd LIKE ? OR d.sbd LIKE ? OR ts.ho_va_ten LIKE ?)";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
        }

        // Total count
        if ($sessionId) {
            $stmtTotal = $db->prepare("SELECT COUNT(*) FROM diem_nang_khieu WHERE dot_tuyen_sinh_id = ?");
            $stmtTotal->execute([$sessionId]);
        } else {
            $stmtTotal = $db->prepare("SELECT COUNT(*) FROM diem_nang_khieu WHERE dot_tuyen_sinh_id IS NULL");
            $stmtTotal->execute([]);
        }
        $recordsTotal = $stmtTotal->fetchColumn();

        // Filtered count
        $countQuery = preg_replace('/SELECT .* FROM/s', 'SELECT COUNT(*) FROM', $query);
        $stmtFiltered = $db->prepare($countQuery);
        $stmtFiltered->execute($params);
        $recordsFiltered = $stmtFiltered->fetchColumn();

        // Data
        $query .= " ORDER BY d.id DESC LIMIT " . intval($length) . " OFFSET " . intval($start);
        $stmtData = $db->prepare($query);
        $stmtData->execute($params);
        $data = $stmtData->fetchAll(\PDO::FETCH_ASSOC);

        $this->json([
            "draw" => intval($draw),
            "recordsTotal" => intval($recordsTotal),
            "recordsFiltered" => intval($recordsFiltered),
            "data" => $data
        ]);
    }

    public function apiSave() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid method']);
            return;
        }

        $id = $_POST['id'] ?? null;
        $cccd = $_POST['so_cccd'] ?? '';
        $sbd = $_POST['sbd'] ?? '';
        $maMon = $_POST['ma_mon'] ?? '';
        $diem = $_POST['diem'] ?? 0;
        $ghiChu = $_POST['ghi_chu'] ?? '';

        if (empty($cccd) || empty($maMon)) {
            $this->json(['success' => false, 'message' => 'Thiếu thông tin bắt buộc (CCCD, Môn)']);
            return;
        }

        $db = $this->model->getDb();
        try {
            if ($id) {
                $stmt = $db->prepare("UPDATE diem_nang_khieu SET so_cccd = ?, sbd = ?, ma_mon = ?, diem = ?, ghi_chu = ? WHERE id = ?");
                $stmt->execute([$cccd, $sbd, $maMon, $diem, $ghiChu, $id]);
            } else {
                $sessionId = $_POST['session_id'] ?? $_SESSION['admin_selected_session_id'] ?? null;
                if (!$sessionId) {
                    $sessionModel = new \App\Models\AdmissionSession();
                    $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestSession();
                    $sessionId = $activeSession ? $activeSession['id'] : null;
                }

                // Check if already exists for this cccd, maMon and current session
                if ($sessionId) {
                    $stmtCheck = $db->prepare("SELECT id FROM diem_nang_khieu WHERE so_cccd = ? AND ma_mon = ? AND dot_tuyen_sinh_id = ?");
                    $stmtCheck->execute([$cccd, $maMon, $sessionId]);
                } else {
                    $stmtCheck = $db->prepare("SELECT id FROM diem_nang_khieu WHERE so_cccd = ? AND ma_mon = ? AND dot_tuyen_sinh_id IS NULL");
                    $stmtCheck->execute([$cccd, $maMon]);
                }

                if ($stmtCheck->fetch()) {
                    $this->json(['success' => false, 'message' => 'Thí sinh đã có điểm cho môn này trong đợt tuyển sinh hiện tại. Vui lòng cập nhật bản ghi cũ.']);
                    return;
                }

                $stmt = $db->prepare("INSERT INTO diem_nang_khieu (so_cccd, sbd, ma_mon, diem, ghi_chu, dot_tuyen_sinh_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$cccd, $sbd, $maMon, $diem, $ghiChu, $sessionId]);
            }
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    public function import() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/aptitude-scores');
        }

        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = "Vui lòng chọn file hợp lệ.";
            $this->redirect('/admin/aptitude-scores');
        }

        $sessionId = $_POST['session_id'] ?? $_SESSION['admin_selected_session_id'] ?? null;
        if (!$sessionId) {
            $sessionModel = new \App\Models\AdmissionSession();
            $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestSession();
            $sessionId = $activeSession ? $activeSession['id'] : null;
        }

        $file = $_FILES['excel_file']['tmp_name'];
        $extension = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ['csv', 'xls', 'xlsx'])) {
            $_SESSION['flash_error'] = "Vui lòng sử dụng định dạng file .csv, .xls hoặc .xlsx.";
            $this->redirect('/admin/aptitude-scores' . ($sessionId ? '?session_id=' . $sessionId : ''));
        }

        try {
            $db = $this->model->getDb();
            $db->beginTransaction();

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $rows = array_values($spreadsheet->getActiveSheet()->toArray(null, true, true, true));
            
            if (count($rows) <= 1) {
                throw new \Exception("File không chứa dữ liệu hoặc chỉ có dòng tiêu đề.");
            }

            // Extract all non-empty CCCDs from the Excel rows first
            $cccds = [];
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $cccd = isset($row['B']) ? trim((string)$row['B']) : '';
                
                $hoTen = isset($row['C']) ? trim((string)$row['C']) : '';
                $rawDob = isset($row['D']) ? trim((string)$row['D']) : '';
                $scoreRaw = isset($row['F']) ? trim((string)$row['F']) : '';
                if (empty($cccd) && empty($hoTen) && empty($rawDob) && empty($scoreRaw)) {
                    continue;
                }
                
                if (!empty($cccd)) {
                    $cccds[] = $cccd;
                }
            }
            $cccds = array_unique($cccds);

            $candidatesMap = [];
            if (!empty($cccds)) {
                $placeholders = implode(',', array_fill(0, count($cccds), '?'));
                $stmtTs = $db->prepare("SELECT so_cccd, ho_va_ten, ngay_sinh FROM thi_sinh WHERE so_cccd IN ($placeholders) AND deleted_at IS NULL");
                $stmtTs->execute($cccds);
                $tsRows = $stmtTs->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($tsRows as $tsRow) {
                    $candidatesMap[$tsRow['so_cccd']] = $tsRow;
                }
            }

            $applicationsMap = [];
            if (!empty($cccds)) {
                $placeholders = implode(',', array_fill(0, count($cccds), '?'));
                $stmtHs = $db->prepare("SELECT so_cccd FROM ho_so_xet_tuyen WHERE so_cccd IN ($placeholders) AND dot_tuyen_sinh_id = ? AND deleted_at IS NULL");
                $stmtHs->execute(array_merge($cccds, [$sessionId]));
                $hsRows = $stmtHs->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($hsRows as $hsRow) {
                    $applicationsMap[$hsRow['so_cccd']] = true;
                }
            }

            $successCount = 0;
            $errorCount = 0;
            $hasErrors = false;
            $importData = [];

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                $stt = isset($row['A']) ? trim((string)$row['A']) : '';
                $cccd = isset($row['B']) ? trim((string)$row['B']) : '';
                $hoTen = isset($row['C']) ? trim((string)$row['C']) : '';
                $rawDob = isset($row['D']) ? trim((string)$row['D']) : '';
                $maMon = isset($row['E']) ? trim((string)$row['E']) : '';
                $scoreRaw = isset($row['F']) ? trim((string)$row['F']) : '';

                if (empty($cccd) && empty($hoTen) && empty($rawDob) && empty($scoreRaw)) {
                    continue;
                }

                $errorMsg = '';
                if (empty($cccd)) {
                    $errorMsg = "Số CCCD không được để trống";
                }

                if (empty($errorMsg)) {
                    $candidate = $candidatesMap[$cccd] ?? null;
                    if (!$candidate) {
                        $errorMsg = "Số ĐDCN, Họ tên, Ngày sinh không khớp trên hệ thống";
                    } else {
                        $dbName = mb_strtoupper(preg_replace('/\s+/', ' ', trim($candidate['ho_va_ten'])), 'UTF-8');
                        $excelName = mb_strtoupper(preg_replace('/\s+/', ' ', trim($hoTen)), 'UTF-8');
                        $dbDob = $candidate['ngay_sinh'];
                        $excelDob = '';
                        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $rawDob, $matches)) {
                            $excelDob = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                        } elseif (is_numeric($rawDob)) {
                            try {
                                $dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDob);
                                $excelDob = $dateValue->format('Y-m-d');
                            } catch (\Exception $e) {}
                        } else {
                            $ts = strtotime(str_replace('/', '-', $rawDob));
                            if ($ts) $excelDob = date('Y-m-d', $ts);
                        }
                        $dobMatch = ($dbDob && $excelDob && date('Y-m-d', strtotime($dbDob)) === $excelDob);
                        if ($dbName !== $excelName || !$dobMatch) {
                            $errorMsg = "Số ĐDCN, Họ tên, Ngày sinh không khớp trên hệ thống";
                        } else {
                            $hasApp = $applicationsMap[$cccd] ?? false;
                            if (!$hasApp) {
                                $errorMsg = "Thí sinh chưa đăng ký hồ sơ trong đợt tuyển sinh này";
                            }
                        }
                    }
                }

                if (empty($errorMsg)) {
                    if (!in_array($maMon, ['NK1', 'NK2', 'NK3', 'NK4'])) {
                        $errorMsg = "Mã môn năng khiếu không hợp lệ (phải là NK1, NK2, NK3, NK4)";
                    }
                }

                if (empty($errorMsg)) {
                    $scoreNormalized = str_replace(',', '.', $scoreRaw);
                    if ($scoreNormalized === '' || !is_numeric($scoreNormalized) || floatval($scoreNormalized) < 0 || floatval($scoreNormalized) > 10) {
                        $errorMsg = "Điểm thi không hợp lệ (phải từ 0 đến 10)";
                    }
                }

                if (!empty($errorMsg)) {
                    $hasErrors = true;
                    $status = "Thất bại";
                    $reason = $errorMsg;
                    $errorCount++;
                } else {
                    $status = "Thành công";
                    $reason = "";
                    $successCount++;
                }

                $importData[] = [
                    'STT' => $stt, 'CMND' => $cccd, 'Họ tên' => $hoTen, 'Ngày sinh' => $rawDob,
                    'Mã môn NK' => $maMon, 'Điểm' => $scoreRaw, 'Kết quả' => $status, 'GHI CHÚ' => $reason
                ];
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            if ($hasErrors) {
                $db->rollBack();
                $exportService = new \App\Services\ExportService();
                $exportService->toExcel($importData, 'ket_qua_import_diem_nang_khieu_loi.xls');
                exit;
            }

            $sbdMap = [];
            if (!empty($cccds)) {
                $placeholders = implode(',', array_fill(0, count($cccds), '?'));
                $stmtSbd = $db->prepare("SELECT so_cccd, sbd FROM ho_so_xet_tuyen WHERE so_cccd IN ($placeholders) AND dot_tuyen_sinh_id = ? AND deleted_at IS NULL");
                $stmtSbd->execute(array_merge($cccds, [$sessionId]));
                $sbdRows = $stmtSbd->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($sbdRows as $sbdRow) {
                    $sbdMap[$sbdRow['so_cccd']] = $sbdRow['sbd'];
                }
            }

            $deleteOrs = [];
            $deleteParams = [];
            foreach ($importData as $row) {
                $deleteOrs[] = "(so_cccd = ? AND ma_mon = ? AND dot_tuyen_sinh_id = ?)";
                $deleteParams[] = $row['CMND'];
                $deleteParams[] = $row['Mã môn NK'];
                $deleteParams[] = $sessionId;
            }
            if (!empty($deleteOrs)) {
                $deleteChunks = array_chunk($deleteOrs, 100);
                $paramChunks = array_chunk($deleteParams, 300);
                for ($k = 0; $k < count($deleteChunks); $k++) {
                    $sqlDel = "DELETE FROM diem_nang_khieu WHERE " . implode(" OR ", $deleteChunks[$k]);
                    $stmtDel = $db->prepare($sqlDel);
                    $stmtDel->execute($paramChunks[$k]);
                }
            }

            $insertValues = [];
            $insertParams = [];
            foreach ($importData as $row) {
                $cccd = $row['CMND'];
                $maMon = $row['Mã môn NK'];
                $score = floatval(str_replace(',', '.', $row['Điểm']));
                $sbd = $sbdMap[$cccd] ?? '';
                $insertValues[] = "(?, ?, ?, ?, ?, ?)";
                $insertParams[] = $cccd; $insertParams[] = $sbd; $insertParams[] = $maMon;
                $insertParams[] = $score; $insertParams[] = 'Import Excel'; $insertParams[] = $sessionId;
            }
            if (!empty($insertValues)) {
                $valueChunks = array_chunk($insertValues, 100);
                $paramChunks = array_chunk($insertParams, 600);
                for ($k = 0; $k < count($valueChunks); $k++) {
                    $sqlIns = "INSERT INTO diem_nang_khieu (so_cccd, sbd, ma_mon, diem, ghi_chu, dot_tuyen_sinh_id) VALUES " . implode(", ", $valueChunks[$k]);
                    $stmtIns = $db->prepare($sqlIns);
                    $stmtIns->execute($paramChunks[$k]);
                }
            }

            $db->commit();
            $_SESSION['flash_success'] = "Import thành công $successCount bản ghi điểm năng khiếu!";
        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = "Lỗi xử lý file: " . $e->getMessage();
        }

        $this->redirect('/admin/aptitude-scores' . ($sessionId ? '?session_id=' . $sessionId : ''));
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Xóa hàng loạt mục đã chọn
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $this->model->getDb()->prepare("DELETE FROM diem_nang_khieu WHERE id IN ($placeholders)");
                if ($stmt->execute($ids)) {
                    $this->json(['success' => true]);
                    return;
                }
            }
            
            // 2. Xóa một mục cụ thể
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt = $this->model->getDb()->prepare("DELETE FROM diem_nang_khieu WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $this->json(['success' => true]);
                    return;
                }
            }
            
            // 3. Xóa tất cả bản ghi thuộc đợt tuyển sinh đang được chọn
            $deleteAll = $_POST['delete_all'] ?? false;
            if ($deleteAll) {
                $sessionId = $_POST['session_id'] ?? $_SESSION['admin_selected_session_id'] ?? null;
                if (!$sessionId) {
                    $sessionModel = new \App\Models\AdmissionSession();
                    $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestSession();
                    $sessionId = $activeSession ? $activeSession['id'] : null;
                }
                
                if ($sessionId) {
                    $stmt = $this->model->getDb()->prepare("DELETE FROM diem_nang_khieu WHERE dot_tuyen_sinh_id = ?");
                    $success = $stmt->execute([$sessionId]);
                } else {
                    $stmt = $this->model->getDb()->prepare("DELETE FROM diem_nang_khieu WHERE dot_tuyen_sinh_id IS NULL");
                    $success = $stmt->execute([]);
                }
                
                if ($success) {
                    $this->json(['success' => true]);
                    return;
                }
            }
        }
        $this->json(['success' => false]);
    }

    public function export() {
        $searchValue = $_GET['search'] ?? '';
        $db = $this->model->getDb();

        $sessionId = $_GET['session_id'] ?? $_SESSION['admin_selected_session_id'] ?? null;
        if (!$sessionId) {
            $sessionModel = new \App\Models\AdmissionSession();
            $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestSession();
            $sessionId = $activeSession ? $activeSession['id'] : null;
        }

        $query = "SELECT d.so_cccd, d.sbd, d.ma_mon, d.diem, d.ghi_chu, ts.ho_va_ten 
                  FROM diem_nang_khieu d
                  LEFT JOIN thi_sinh ts ON d.so_cccd = ts.so_cccd
                  WHERE ";
        $params = [];

        if ($sessionId) {
            $query .= "d.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        } else {
            $query .= "d.dot_tuyen_sinh_id IS NULL";
        }

        if (!empty($searchValue)) {
            $query .= " AND (d.so_cccd LIKE ? OR d.sbd LIKE ? OR ts.ho_va_ten LIKE ?)";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
        }

        $query .= " ORDER BY d.id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $dataRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $data = [];
        foreach ($dataRows as $row) {
            $data[] = [
                'CCCD (Bat buoc)' => "\t" . $row['so_cccd'],
                'SBD' => $row['sbd'],
                'Ma mon (NK1, NK2, NK3, NK4)' => $row['ma_mon'],
                'Diem (Bat buoc)' => $row['diem'],
                'Ghi chu' => $row['ghi_chu']
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'danh_sach_diem_nang_khieu.xls');
    }

    public function template() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=mau_import_diem_nang_khieu.csv');
        $output = fopen('php://output', 'w');
        
        // Thêm BOM để Excel đọc đúng tiếng Việt (UTF-8)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Ghi dòng tiêu đề
        fputcsv($output, ['STT', 'CMND', 'Họ tên', 'Ngày sinh', 'Mã môn NK', 'Điểm']);
        
        // Ghi dữ liệu mẫu
        fputcsv($output, ['1', '025308004384', 'LÊ THỊ HOÀI AN', '08/12/2008', 'NK1', '7,75']);
        
        fclose($output);
        exit;
    }
}
