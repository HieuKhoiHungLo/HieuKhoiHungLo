<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\DiemChungChi;
use App\Models\MasterData;

class CertificateScoreController extends Controller {
    protected $model;
    protected $masterData;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->model = new DiemChungChi();
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
            'total' => $db->query("SELECT COUNT(*) FROM diem_chung_chi" . ($sessionId ? " WHERE dot_tuyen_sinh_id = " . intval($sessionId) : " WHERE dot_tuyen_sinh_id IS NULL"))->fetchColumn(),
        ];
        
        $this->view('admin/certificate_scores/index', [
            'title' => 'Quản lý Điểm Chứng chỉ', 
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
        $fName = $_POST['f_name'] ?? '';
        $fCccd = $_POST['f_cccd'] ?? '';
        $fMaMon = $_POST['f_ma_mon'] ?? '';
        $fGhiChu = $_POST['f_ghi_chu'] ?? '';

        $sessionId = $_POST['session_id'] ?? $_GET['session_id'] ?? $_SESSION['admin_selected_session_id'] ?? null;
        if (!$sessionId) {
            $sessionModel = new \App\Models\AdmissionSession();
            $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestSession();
            $sessionId = $activeSession ? $activeSession['id'] : null;
        }

        $query = "SELECT c.id, c.so_cccd, c.ma_mon, c.diem, c.ghi_chu, ts.ho_va_ten 
                  FROM diem_chung_chi c
                  LEFT JOIN thi_sinh ts ON c.so_cccd = ts.so_cccd";
        $params = [];

        if ($sessionId) {
            $query .= " WHERE c.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        } else {
            $query .= " WHERE c.dot_tuyen_sinh_id IS NULL";
        }

        if (!empty($searchValue)) {
            $query .= " AND (c.so_cccd LIKE ? OR ts.ho_va_ten LIKE ?)";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
        }

        if (!empty($fName)) {
            $query .= " AND ts.ho_va_ten LIKE ?";
            $params[] = "%$fName%";
        }

        if (!empty($fCccd)) {
            $query .= " AND c.so_cccd LIKE ?";
            $params[] = "%$fCccd%";
        }

        if (!empty($fMaMon)) {
            $query .= " AND c.ma_mon LIKE ?";
            $params[] = "%$fMaMon%";
        }

        if (!empty($fGhiChu)) {
            $query .= " AND c.ghi_chu LIKE ?";
            $params[] = "%$fGhiChu%";
        }

        // Total count
        if ($sessionId) {
            $stmtTotal = $db->prepare("SELECT COUNT(*) FROM diem_chung_chi WHERE dot_tuyen_sinh_id = ?");
            $stmtTotal->execute([$sessionId]);
        } else {
            $stmtTotal = $db->prepare("SELECT COUNT(*) FROM diem_chung_chi WHERE dot_tuyen_sinh_id IS NULL");
            $stmtTotal->execute([]);
        }
        $recordsTotal = $stmtTotal->fetchColumn();

        // Filtered count
        $countQuery = preg_replace('/SELECT .* FROM/s', 'SELECT COUNT(*) FROM', $query);
        $stmtFiltered = $db->prepare($countQuery);
        $stmtFiltered->execute($params);
        $recordsFiltered = $stmtFiltered->fetchColumn();

        // Data
        $query .= " ORDER BY c.id DESC LIMIT " . intval($length) . " OFFSET " . intval($start);
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
        $maMon = $_POST['ma_mon'] ?? 'N1'; // Default to Tiếng Anh if not provided
        $diem = $_POST['diem'] ?? 0;
        $ghiChu = $_POST['ghi_chu'] ?? '';

        if (empty($cccd)) {
            $this->json(['success' => false, 'message' => 'Thiếu thông tin CCCD']);
            return;
        }

        $db = $this->model->getDb();
        try {
            if ($id) {
                $stmt = $db->prepare("UPDATE diem_chung_chi SET so_cccd = ?, ma_mon = ?, diem = ?, ghi_chu = ? WHERE id = ?");
                $stmt->execute([$cccd, $maMon, $diem, $ghiChu, $id]);
            } else {
                $sessionId = $_POST['session_id'] ?? $_SESSION['admin_selected_session_id'] ?? null;
                if (!$sessionId) {
                    $sessionModel = new \App\Models\AdmissionSession();
                    $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestSession();
                    $sessionId = $activeSession ? $activeSession['id'] : null;
                }

                // Check if already exists for this cccd, maMon and current session
                if ($sessionId) {
                    $stmtCheck = $db->prepare("SELECT id FROM diem_chung_chi WHERE so_cccd = ? AND ma_mon = ? AND dot_tuyen_sinh_id = ?");
                    $stmtCheck->execute([$cccd, $maMon, $sessionId]);
                } else {
                    $stmtCheck = $db->prepare("SELECT id FROM diem_chung_chi WHERE so_cccd = ? AND ma_mon = ? AND dot_tuyen_sinh_id IS NULL");
                    $stmtCheck->execute([$cccd, $maMon]);
                }

                if ($stmtCheck->fetch()) {
                    $this->json(['success' => false, 'message' => 'Thí sinh đã có điểm quy đổi cho môn này trong đợt tuyển sinh hiện tại. Vui lòng cập nhật bản ghi cũ.']);
                    return;
                }

                $stmt = $db->prepare("INSERT INTO diem_chung_chi (so_cccd, ma_mon, diem, ghi_chu, dot_tuyen_sinh_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$cccd, $maMon, $diem, $ghiChu, $sessionId]);
            }
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

        public function import() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(url('/admin/certificate-scores'));
        }

        $token = $_POST['import_token'] ?? '';
        $updateProgress = function($current, $total, $message = '') use ($token) {
            if (empty($token)) return;
            $progressDir = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir($progressDir)) mkdir($progressDir, 0777, true);
            
            $status = [
                'current' => $current,
                'total' => $total,
                'percent' => $total > 0 ? round(($current / $total) * 100) : 0,
                'message' => $message,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            file_put_contents($progressDir . "/import_progress_{$token}.json", json_encode($status));
        };

        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') 
               || !empty($token);

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file hợp lệ.']);
                exit;
            }
            $_SESSION['flash_error'] = "Vui lòng chọn file hợp lệ.";
            $this->redirect(url('/admin/certificate-scores'));
        }

        $sessionId = $_POST['session_id'] ?? $_SESSION['admin_selected_session_id'] ?? null;
        if (!$sessionId) {
            $sessionModel = new \App\Models\AdmissionSession();
            $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestSession();
            $sessionId = $activeSession ? $activeSession['id'] : null;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $extension = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
        $allowedExtensions = ['csv', 'xls', 'xlsx'];
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Vui lòng sử dụng định dạng file .xlsx, .xls hoặc .csv.']);
                exit;
            }
            $_SESSION['flash_error'] = "Vui lòng sử dụng định dạng file .xlsx, .xls hoặc .csv.";
            $this->redirect(url('/admin/certificate-scores' . ($sessionId ? '?session_id=' . $sessionId : '')));
        }

        $updateProgress(5, 100, 'Đang đọc dữ liệu từ file Excel...');
        $rows = $this->loadExcelOrCsv($file, $extension);
        if ($rows === null || empty($rows)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Không thể đọc dữ liệu hoặc file trống.']);
                exit;
            }
            $_SESSION['flash_error'] = "Không thể đọc dữ liệu hoặc file trống.";
            $this->redirect(url('/admin/certificate-scores'));
        }

        try {
            $successCount = 0;
            $errorCount = 0;
            $db = $this->model->getDb();

            $updateProgress(15, 100, 'Đang xử lý và chuẩn hóa dữ liệu...');

            // Extract all non-empty CCCDs from the Excel rows first
            $cccds = [];
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $cccd = $this->normalizeCCCD($row[0] ?? '');
                $hoTen = isset($row[1]) ? trim((string)$row[1]) : '';
                $rawDob = isset($row[2]) ? trim((string)$row[2]) : '';
                $maMon = isset($row[3]) ? trim((string)$row[3]) : '';
                $scoreRaw = isset($row[4]) ? trim((string)$row[4]) : '';
                
                if (empty($cccd) && empty($hoTen) && empty($rawDob) && empty($maMon) && empty($scoreRaw)) {
                    continue;
                }
                
                if (!empty($cccd)) {
                    $cccds[] = $cccd;
                }
            }
            $cccds = array_values(array_unique($cccds));

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

            // Fetch valid subject codes from dm_mon
            $stmtAllMon = $db->query("SELECT ma_mon FROM dm_mon");
            $validMons = [];
            while ($mCode = $stmtAllMon->fetchColumn()) {
                $validMons[strtoupper($mCode)] = true;
            }

            $importData = [];
            $validRows = [];

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                $cccd = $this->normalizeCCCD($row[0] ?? '');
                $hoTen = isset($row[1]) ? trim((string)$row[1]) : '';
                $rawDob = isset($row[2]) ? trim((string)$row[2]) : '';
                $maMon = isset($row[3]) ? trim((string)$row[3]) : '';
                $scoreRaw = isset($row[4]) ? trim((string)$row[4]) : '';
                $note = isset($row[5]) ? trim((string)$row[5]) : '';

                if (empty($cccd) && empty($hoTen) && empty($rawDob) && empty($maMon) && empty($scoreRaw)) {
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
                    if (!isset($validMons[strtoupper($maMon)])) {
                        $errorMsg = "Mã môn quy đổi không tồn tại trên hệ thống";
                    }
                }

                if (empty($errorMsg)) {
                    $scoreNormalized = str_replace(',', '.', $scoreRaw);
                    if ($scoreNormalized === '' || !is_numeric($scoreNormalized) || floatval($scoreNormalized) < 0 || floatval($scoreNormalized) > 10) {
                        $errorMsg = "Điểm quy đổi không hợp lệ (phải từ 0 đến 10)";
                    }
                }

                if (!empty($errorMsg)) {
                    $status = "Thất bại";
                    $reason = $errorMsg;
                    $errorCount++;
                } else {
                    $status = "Thành công";
                    $reason = "";
                    $successCount++;
                    $scoreVal = floatval(str_replace(',', '.', $scoreRaw));
                    $validRows[] = [
                        'so_cccd' => $cccd,
                        'ma_mon' => $maMon,
                        'diem' => $scoreVal,
                        'ghi_chu' => $note
                    ];
                }

                $importData[] = [
                    'CCCD' => $cccd, 
                    'Họ tên' => $hoTen, 
                    'Ngày sinh' => $rawDob,
                    'Mã môn' => $maMon, 
                    'Điểm quy đổi' => $scoreRaw, 
                    'Ghi chú' => $note, 
                    'Trạng thái' => $status, 
                    'Lý do' => $reason
                ];
            }

            $totalImport = count($validRows);
            $updateProgress(35, 100, "Đang chuẩn bị cập nhật CSDL cho $totalImport bản ghi...");

            if ($totalImport > 0) {
                $db->beginTransaction();

                // Chunk queries to avoid PostgreSQL parameter limits (e.g. max 1000 parameters per query)
                // Since each row in delete has 2 parameters (so_cccd, ma_mon), chunk by 300 rows (600 params)
                $chunks = array_chunk($validRows, 300);
                $totalChunks = count($chunks);

                foreach ($chunks as $chunkIndex => $chunk) {
                    $currentPercent = 35 + round(($chunkIndex / $totalChunks) * 55);
                    $updateProgress($currentPercent, 100, "Đang cập nhật CSDL: Cụm " . ($chunkIndex + 1) . "/$totalChunks...");

                    // 1. Batch delete matching rows in this session
                    $deleteConditions = [];
                    $deleteParams = [$sessionId];
                    foreach ($chunk as $r) {
                        $deleteConditions[] = "(so_cccd = ? AND ma_mon = ?)";
                        $deleteParams[] = $r['so_cccd'];
                        $deleteParams[] = $r['ma_mon'];
                    }
                    $sqlDel = "DELETE FROM diem_chung_chi WHERE dot_tuyen_sinh_id = ? AND (" . implode(" OR ", $deleteConditions) . ")";
                    $stmtDel = $db->prepare($sqlDel);
                    $stmtDel->execute($deleteParams);

                    // 2. Batch insert rows
                    $insertValues = [];
                    $insertParams = [];
                    foreach ($chunk as $r) {
                        $insertValues[] = "(?, ?, ?, ?, ?)";
                        $insertParams[] = $r['so_cccd'];
                        $insertParams[] = $r['ma_mon'];
                        $insertParams[] = $r['diem'];
                        $insertParams[] = $r['ghi_chu'];
                        $insertParams[] = $sessionId;
                    }
                    $sqlIns = "INSERT INTO diem_chung_chi (so_cccd, ma_mon, diem, ghi_chu, dot_tuyen_sinh_id) VALUES " . implode(", ", $insertValues);
                    $stmtIns = $db->prepare($sqlIns);
                    $stmtIns->execute($insertParams);
                }

                $db->commit();
            }

            $updateProgress(100, 100, "Hoàn tất! Đã nhập thành công $successCount bản ghi.");

            if ($errorCount > 0) {
                $_SESSION['flash_warning'] = "Đã import thành công $successCount bản ghi hợp lệ. Có $errorCount bản ghi bị lỗi thông tin đã được bỏ qua và tải về báo cáo lỗi.";
                $exportService = new \App\Services\ExportService();
                $exportService->toExcel($importData, 'ket_qua_import_diem_chung_chi_loi.xls');
                exit;
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "Import thành công toàn bộ $successCount bản ghi điểm chứng chỉ!",
                    'success_count' => $successCount,
                    'error_count' => $errorCount
                ]);
                exit;
            }

            $_SESSION['flash_success'] = "Import thành công toàn bộ $successCount bản ghi điểm chứng chỉ!";

        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $updateProgress(100, 100, "Lỗi: " . $e->getMessage());

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Lỗi xử lý: " . $e->getMessage()]);
                exit;
            }
            $_SESSION['flash_error'] = "Lỗi xử lý file: " . $e->getMessage();
        }

        $this->redirect(url('/admin/certificate-scores' . ($sessionId ? '?session_id=' . $sessionId : '')));
    }

    private function normalizeCCCD($cccd) {
        $cccd = trim((string)$cccd);
        // Strip Excel formatting like ="0123456"
        if (str_starts_with($cccd, '="') && str_ends_with($cccd, '"')) {
            $cccd = substr($cccd, 2, -1);
        }
        $cccd = preg_replace('/[^0-9a-zA-Z]/', '', $cccd); // Keep alphanumeric characters
        return $cccd;
    }

    private function loadExcelOrCsv($filePath, $extension) {
        $extension = strtolower($extension);
        $data = [];

        if ($extension === 'xls') {
            require_once __DIR__ . '/../Services/SimpleXLS.php';
            if ($xls = \Shuchkin\SimpleXLS::parse($filePath)) {
                return $xls->rows();
            }
        }
        
        if ($extension === 'xlsx') {
            require_once __DIR__ . '/../Services/SimpleXLSX.php';
            if ($xlsx = \Shuchkin\SimpleXLSX::parse($filePath)) {
                return $xlsx->rows();
            }
        }
        
        if ($extension === 'csv') {
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                // Skip BOM if present
                $bom = fread($handle, 3);
                if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
                    rewind($handle);
                }
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $data[] = $row;
                }
                fclose($handle);
            }
            return $data;
        }

        return null;
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Xóa hàng loạt mục đã chọn
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $this->model->getDb()->prepare("DELETE FROM diem_chung_chi WHERE id IN ($placeholders)");
                if ($stmt->execute($ids)) {
                    $this->json(['success' => true]);
                    return;
                }
            }
            
            // 2. Xóa một mục cụ thể
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt = $this->model->getDb()->prepare("DELETE FROM diem_chung_chi WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $this->json(['success' => true]);
                    return;
                }
            }
        }
        $this->json(['success' => false]);
    }

    public function export() {
        $searchValue = $_GET['search'] ?? '';
        $fName = $_GET['f_name'] ?? '';
        $fCccd = $_GET['f_cccd'] ?? '';
        $fMaMon = $_GET['f_ma_mon'] ?? '';
        $fGhiChu = $_GET['f_ghi_chu'] ?? '';
        $db = $this->model->getDb();

        $sessionId = $_GET['session_id'] ?? $_SESSION['admin_selected_session_id'] ?? null;
        if (!$sessionId) {
            $sessionModel = new \App\Models\AdmissionSession();
            $activeSession = $sessionModel->getActiveSession() ?? $sessionModel->getLatestSession();
            $sessionId = $activeSession ? $activeSession['id'] : null;
        }

        $query = "SELECT c.so_cccd, c.ma_mon, c.diem, c.ghi_chu, ts.ho_va_ten, ts.ngay_sinh
                  FROM diem_chung_chi c
                  LEFT JOIN thi_sinh ts ON c.so_cccd = ts.so_cccd
                  WHERE ";
        $params = [];

        if ($sessionId) {
            $query .= "c.dot_tuyen_sinh_id = ?";
            $params[] = $sessionId;
        } else {
            $query .= "c.dot_tuyen_sinh_id IS NULL";
        }
        
        if (!empty($searchValue)) {
            $query .= " AND (c.so_cccd LIKE ? OR ts.ho_va_ten LIKE ?)";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
        }

        if (!empty($fName)) {
            $query .= " AND ts.ho_va_ten LIKE ?";
            $params[] = "%$fName%";
        }

        if (!empty($fCccd)) {
            $query .= " AND c.so_cccd LIKE ?";
            $params[] = "%$fCccd%";
        }

        if (!empty($fMaMon)) {
            $query .= " AND c.ma_mon LIKE ?";
            $params[] = "%$fMaMon%";
        }

        if (!empty($fGhiChu)) {
            $query .= " AND c.ghi_chu LIKE ?";
            $params[] = "%$fGhiChu%";
        }

        $query .= " ORDER BY c.id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $dataRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $data = [];
        foreach ($dataRows as $row) {
            $data[] = [
                'CCCD (Bat buoc)' => "\t" . $row['so_cccd'],
                'Họ tên' => $row['ho_va_ten'],
                'Ngày sinh' => $row['ngay_sinh'] ? date('d/m/Y', strtotime($row['ngay_sinh'])) : '',
                'Ma mon (N1, N2, N3...)' => $row['ma_mon'],
                'Diem quy doi (Bat buoc)' => $row['diem'],
                'Ghi chu' => $row['ghi_chu']
            ];
        }

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'danh_sach_diem_chung_chi.xls');
    }

    public function template() {
        $data = [[
            'CCCD (Bat buoc)' => "\t026307006609",
            'Họ tên' => 'NGUYỄN QUỲNH TRANG',
            'Ngày sinh' => '04/06/2007',
            'Ma mon (N1, N2, N3...)' => 'N1',
            'Diem quy doi (Bat buoc)' => '8.00',
            'Ghi chu' => 'Tiếng Anh - IELTS - 5'
        ]];

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'mau_import_diem_chung_chi.xls');
    }

}
