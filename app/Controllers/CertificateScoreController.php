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
        $activeSession = $sessionModel->getActiveSession();
        
        $stats = [
            'total' => $this->model->getDb()->query("SELECT COUNT(*) FROM diem_chung_chi" . ($activeSession ? " WHERE dot_tuyen_sinh_id = " . $activeSession['id'] : ""))->fetchColumn(),
        ];
        $this->view('admin/certificate_scores/index', [
            'title' => 'Quản lý Điểm Chứng chỉ', 
            'stats' => $stats,
            'activeSession' => $activeSession,
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

        $sessionModel = new \App\Models\AdmissionSession();
        $activeSession = $sessionModel->getActiveSession();

        $query = "SELECT c.id, c.so_cccd, c.ma_mon, c.diem, c.ghi_chu, ts.ho_va_ten 
                  FROM diem_chung_chi c
                  LEFT JOIN thi_sinh ts ON c.so_cccd = ts.so_cccd";
        $params = [];

        if ($activeSession) {
            $query .= " WHERE c.dot_tuyen_sinh_id = ?";
            $params[] = $activeSession['id'];
        } else {
            $query .= " WHERE 1=1";
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
        if ($activeSession) {
            $stmtTotal = $db->prepare("SELECT COUNT(*) FROM diem_chung_chi WHERE dot_tuyen_sinh_id = ?");
            $stmtTotal->execute([$activeSession['id']]);
        } else {
            $stmtTotal = $db->prepare("SELECT COUNT(*) FROM diem_chung_chi");
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
                // Check if already exists for this cccd and maMon
                $stmtCheck = $db->prepare("SELECT id FROM diem_chung_chi WHERE so_cccd = ? AND ma_mon = ?");
                $stmtCheck->execute([$cccd, $maMon]);
                if ($stmtCheck->fetch()) {
                    $this->json(['success' => false, 'message' => 'Thí sinh đã có điểm quy đổi cho môn này. Vui lòng cập nhật bản ghi cũ.']);
                    return;
                }

                $sessionModel = new \App\Models\AdmissionSession();
                $activeSession = $sessionModel->getActiveSession();
                $sessionId = $activeSession ? $activeSession['id'] : null;

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

        $sessionModel = new \App\Models\AdmissionSession();
        $activeSession = $sessionModel->getActiveSession();
        $sessionId = $activeSession ? $activeSession['id'] : null;

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
            $this->redirect(url('/admin/certificate-scores'));
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

            // Pre-process and validate rows in memory to deduplicate and validate
            $validRows = [];
            $headerSkipped = false;
            foreach ($rows as $index => $row) {
                if (!$headerSkipped) {
                    $headerSkipped = true;
                    continue;
                }

                if (count($row) < 3) {
                    $errorCount++;
                    continue;
                }

                // SimpleXLSX returns 0-indexed values
                $cccd = $this->normalizeCCCD($row[0] ?? '');
                $maMon = trim((string)($row[1] ?? 'N1'));
                $score = trim((string)($row[2] ?? 0));
                $note = trim((string)($row[3] ?? ''));

                if ($cccd !== '' && is_numeric($score)) {
                    $scoreVal = (float)$score;
                    if ($scoreVal < 0) $scoreVal = 0.0;
                    if ($scoreVal > 10) $scoreVal = 10.0;

                    $key = $cccd . '_' . $maMon;
                    $validRows[$key] = [
                        'so_cccd' => $cccd,
                        'ma_mon' => $maMon,
                        'diem' => $scoreVal,
                        'ghi_chu' => $note
                    ];
                } else {
                    $errorCount++;
                }
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
                        $successCount++;
                    }
                    $sqlIns = "INSERT INTO diem_chung_chi (so_cccd, ma_mon, diem, ghi_chu, dot_tuyen_sinh_id) VALUES " . implode(", ", $insertValues);
                    $stmtIns = $db->prepare($sqlIns);
                    $stmtIns->execute($insertParams);
                }

                $db->commit();
            }

            $updateProgress(100, 100, "Hoàn tất! Đã nhập thành công $successCount bản ghi.");

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "Import thành công $successCount bản ghi. Lỗi/Bỏ qua: $errorCount bản ghi.",
                    'success_count' => $successCount,
                    'error_count' => $errorCount
                ]);
                exit;
            }

            $_SESSION['flash_success'] = "Import thành công $successCount bản ghi. Lỗi/Bỏ qua: $errorCount bản ghi.";

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

        $this->redirect(url('/admin/certificate-scores'));
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
            
            // 3. Xóa tất cả bản ghi thuộc đợt tuyển sinh đang kích hoạt
            $deleteAll = $_POST['delete_all'] ?? false;
            if ($deleteAll) {
                $sessionModel = new \App\Models\AdmissionSession();
                $activeSession = $sessionModel->getActiveSession();
                
                if ($activeSession) {
                    $stmt = $this->model->getDb()->prepare("DELETE FROM diem_chung_chi WHERE dot_tuyen_sinh_id = ?");
                    $success = $stmt->execute([$activeSession['id']]);
                } else {
                    $stmt = $this->model->getDb()->prepare("DELETE FROM diem_chung_chi");
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
        $fName = $_GET['f_name'] ?? '';
        $fCccd = $_GET['f_cccd'] ?? '';
        $fMaMon = $_GET['f_ma_mon'] ?? '';
        $fGhiChu = $_GET['f_ghi_chu'] ?? '';
        $db = $this->model->getDb();

        $query = "SELECT c.so_cccd, c.ma_mon, c.diem, c.ghi_chu, ts.ho_va_ten 
                  FROM diem_chung_chi c
                  LEFT JOIN thi_sinh ts ON c.so_cccd = ts.so_cccd
                  WHERE 1=1";
        $params = [];

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
            'CCCD (Bat buoc)' => "\t123456789",
            'Ma mon (N1, N2, N3...)' => 'N1',
            'Diem quy doi (Bat buoc)' => '10.0',
            'Ghi chu' => 'Vi du mau'
        ]];

        $exportService = new \App\Services\ExportService();
        $exportService->toExcel($data, 'mau_import_diem_chung_chi.xls');
    }
}
