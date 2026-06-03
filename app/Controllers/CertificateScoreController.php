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

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
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
            $_SESSION['flash_error'] = "Vui lòng sử dụng định dạng file .xlsx, .xls hoặc .csv.";
            $this->redirect(url('/admin/certificate-scores'));
        }

        $rows = $this->loadExcelOrCsv($file, $extension);
        if ($rows === null || empty($rows)) {
            $_SESSION['flash_error'] = "Không thể đọc dữ liệu hoặc file trống.";
            $this->redirect(url('/admin/certificate-scores'));
        }

        try {
            $successCount = 0;
            $errorCount = 0;
            $db = $this->model->getDb();
            $db->beginTransaction();

            $headerSkipped = false;
            foreach ($rows as $row) {
                if (!$headerSkipped) {
                    $headerSkipped = true;
                    continue;
                }

                if (count($row) < 3) {
                    $errorCount++;
                    continue;
                }

                $cccd = trim((string)($row[0] ?? ''));
                $maMon = trim((string)($row[1] ?? 'N1'));
                $score = trim((string)($row[2] ?? 0));
                $note = trim((string)($row[3] ?? ''));

                if ($cccd && is_numeric($score)) {
                    // Delete old entry for this student and subject in THIS session
                    $stmtDel = $db->prepare("DELETE FROM diem_chung_chi WHERE so_cccd = ? AND ma_mon = ? AND dot_tuyen_sinh_id = ?");
                    $stmtDel->execute([$cccd, $maMon, $sessionId]);
                    
                    // Insert new
                    $stmtIns = $db->prepare("INSERT INTO diem_chung_chi (so_cccd, ma_mon, diem, ghi_chu, dot_tuyen_sinh_id) VALUES (?, ?, ?, ?, ?)");
                    $stmtIns->execute([$cccd, $maMon, $score, $note, $sessionId]);
                    
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }

            $db->commit();
            $_SESSION['flash_success'] = "Import thành công $successCount bản ghi. Lỗi/Bỏ qua: $errorCount bản ghi.";
        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = "Lỗi xử lý file: " . $e->getMessage();
        }

        $this->redirect(url('/admin/certificate-scores'));
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
