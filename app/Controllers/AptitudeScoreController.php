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
        // We'll use DataTables logic to render server-side
        $sessionModel = new \App\Models\AdmissionSession();
        $activeSession = $sessionModel->getActiveSession();
        
        $stats = [
            'total' => $this->model->getDb()->query("SELECT COUNT(*) FROM diem_nang_khieu" . ($activeSession ? " WHERE dot_tuyen_sinh_id = " . $activeSession['id'] : ""))->fetchColumn(),
        ];
        $this->view('admin/aptitude_scores/index', [
            'title' => 'Điểm Năng Khiếu', 
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
        $sessionId = $activeSession ? $activeSession['id'] : 0;

        $query = "SELECT d.id, d.so_cccd, d.sbd, d.diem, d.ghi_chu, d.ma_mon, ts.ho_va_ten, m.ten_mon 
                  FROM diem_nang_khieu d
                  LEFT JOIN thi_sinh ts ON d.so_cccd = ts.so_cccd
                  LEFT JOIN dm_mon m ON UPPER(d.ma_mon) = UPPER(m.ma_mon)
                  WHERE d.dot_tuyen_sinh_id = ?";
        $params = [$sessionId];

        if (!empty($searchValue)) {
            $query .= " AND (d.so_cccd LIKE ? OR d.sbd LIKE ? OR ts.ho_va_ten LIKE ?)";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
        }

        // Total count
        $stmtTotal = $db->prepare("SELECT COUNT(*) FROM diem_nang_khieu WHERE dot_tuyen_sinh_id = ?");
        $stmtTotal->execute([$sessionId]);
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
                // Check if already exists for this cccd and maMon
                $stmtCheck = $db->prepare("SELECT id FROM diem_nang_khieu WHERE so_cccd = ? AND ma_mon = ?");
                $stmtCheck->execute([$cccd, $maMon]);
                if ($stmtCheck->fetch()) {
                    $this->json(['success' => false, 'message' => 'Thí sinh đã có điểm cho môn này. Vui lòng cập nhật bản ghi cũ.']);
                    return;
                }

                $sessionModel = new \App\Models\AdmissionSession();
                $activeSession = $sessionModel->getActiveSession();
                $sessionId = $activeSession ? $activeSession['id'] : null;

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

        $sessionModel = new \App\Models\AdmissionSession();
        $activeSession = $sessionModel->getActiveSession();
        $sessionId = $activeSession ? $activeSession['id'] : null;

        $file = $_FILES['excel_file']['tmp_name'];
        $extension = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ['csv'])) {
            $_SESSION['flash_error'] = "Vui lòng sử dụng định dạng file .csv (UTF-8).";
            $this->redirect('/admin/aptitude-scores');
        }

        try {
            $successCount = 0;
            $errorCount = 0;
            $db = $this->model->getDb();
            $db->beginTransaction();
            
            if (($handle = fopen($file, "r")) !== FALSE) {
                // Skip header
                fgetcsv($handle, 1000, ",");
                
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($row) < 4) {
                        $errorCount++;
                        continue;
                    }
                    if (empty($row[0]) || empty($row[3])) continue; // Missing CCCD or Score

                    $cccd = trim($row[0] ?? '');
                    $sbd = trim($row[1] ?? '');
                    $maMon = trim($row[2] ?? 'NK1');
                    $score = trim($row[3] ?? 0);
                    $note = trim($row[4] ?? '');

                    if ($cccd && is_numeric($score)) {
                        $stmtDel = $db->prepare("DELETE FROM diem_nang_khieu WHERE so_cccd = ? AND ma_mon = ? AND dot_tuyen_sinh_id = ?");
                        $stmtDel->execute([$cccd, $maMon, $sessionId]);
                        
                        $stmtIns = $db->prepare("INSERT INTO diem_nang_khieu (so_cccd, sbd, ma_mon, diem, ghi_chu, dot_tuyen_sinh_id) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmtIns->execute([$cccd, $sbd, $maMon, $score, $note, $sessionId]);
                        
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
                fclose($handle);
            }

            $db->commit();
            $_SESSION['flash_success'] = "Import thành công $successCount bản ghi. Lỗi: $errorCount bản ghi.";
        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = "Lỗi xử lý file: " . $e->getMessage();
        }

        $this->redirect('/admin/aptitude-scores');
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt = $this->model->getDb()->prepare("DELETE FROM diem_nang_khieu WHERE id = ?");
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
        $db = $this->model->getDb();

        $query = "SELECT d.so_cccd, d.sbd, d.ma_mon, d.diem, d.ghi_chu, ts.ho_va_ten 
                  FROM diem_nang_khieu d
                  LEFT JOIN thi_sinh ts ON d.so_cccd = ts.so_cccd
                  WHERE 1=1";
        $params = [];

        if (!empty($searchValue)) {
            $query .= " AND (d.so_cccd LIKE ? OR d.sbd LIKE ? OR ts.ho_va_ten LIKE ?)";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
        }

        $query .= " ORDER BY d.id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=danh_sach_diem_nang_khieu.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        // Headers matching the template exactly for easy re-import
        fputcsv($output, ['CCCD (Bat buoc)', 'SBD', 'Ma mon (NK1, NK2, NK3, NK4)', 'Diem (Bat buoc)', 'Ghi chu']);

        foreach ($data as $row) {
            fputcsv($output, [
                $row['so_cccd'],
                $row['sbd'],
                $row['ma_mon'],
                $row['diem'],
                $row['ghi_chu']
            ]);
        }

        fclose($output);
        exit;
    }

    public function template() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=mau_import_diem_nang_khieu.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($output, ['CCCD (Bat buoc)', 'SBD', 'Ma mon (NK1, NK2, NK3, NK4)', 'Diem (Bat buoc)', 'Ghi chu']);
        fputcsv($output, ['123456789', 'NK2024001', 'NK1', '9.5', 'Vi du mau']);
        fclose($output);
        exit;
    }
}
