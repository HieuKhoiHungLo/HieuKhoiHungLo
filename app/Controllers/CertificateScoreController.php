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
        $stats = [
            'total' => $this->model->getDb()->query("SELECT COUNT(*) FROM diem_chung_chi")->fetchColumn(),
        ];
        $this->view('admin/certificate_scores/index', ['title' => 'Quản lý Điểm Chứng chỉ', 'stats' => $stats]);
    }

    public function apiList() {
        $db = $this->model->getDb();
        $draw = $_POST['draw'] ?? 1;
        $start = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;
        $searchValue = $_POST['search']['value'] ?? '';

        $query = "SELECT c.id, c.so_cccd, c.ma_mon, c.diem, c.ghi_chu, ts.ho_va_ten 
                  FROM diem_chung_chi c
                  LEFT JOIN thi_sinh ts ON c.so_cccd = ts.so_cccd
                  WHERE 1=1";
        $params = [];

        if (!empty($searchValue)) {
            $query .= " AND (c.so_cccd LIKE ? OR ts.ho_va_ten LIKE ?)";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
        }

        // Total count
        $stmtTotal = $db->query("SELECT COUNT(*) FROM diem_chung_chi");
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

                $stmt = $db->prepare("INSERT INTO diem_chung_chi (so_cccd, ma_mon, diem, ghi_chu) VALUES (?, ?, ?, ?)");
                $stmt->execute([$cccd, $maMon, $diem, $ghiChu]);
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

        $file = $_FILES['csv_file']['tmp_name'];
        $extension = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
        if (strtolower($extension) !== 'csv') {
            $_SESSION['flash_error'] = "Vui lòng sử dụng định dạng file .csv (UTF-8).";
            $this->redirect(url('/admin/certificate-scores'));
        }

        try {
            $successCount = 0;
            $errorCount = 0;
            $db = $this->model->getDb();
            $db->beginTransaction();

            if (($handle = fopen($file, "r")) !== FALSE) {
                // Skip BOM if present
                $bom = fread($handle, 3);
                if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
                    rewind($handle);
                }
                
                // Skip header
                fgetcsv($handle, 1000, ",");
                
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($row) < 3) {
                        $errorCount++;
                        continue;
                    }

                    $cccd = trim($row[0] ?? '');
                    $maMon = trim($row[1] ?? 'N1');
                    $score = trim($row[2] ?? 0);
                    $note = trim($row[3] ?? '');

                    if ($cccd && is_numeric($score)) {
                        // Delete old entry for this student and subject
                        $stmtDel = $db->prepare("DELETE FROM diem_chung_chi WHERE so_cccd = ? AND ma_mon = ?");
                        $stmtDel->execute([$cccd, $maMon]);
                        
                        // Insert new
                        $stmtIns = $db->prepare("INSERT INTO diem_chung_chi (so_cccd, ma_mon, diem, ghi_chu) VALUES (?, ?, ?, ?)");
                        $stmtIns->execute([$cccd, $maMon, $score, $note]);
                        
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
                fclose($handle);
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

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    public function template() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=mau_diem_quy_doi_chung_chi.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($output, ['CCCD (Bat buoc)', 'Ma mon (N1, N2, N3...)', 'Diem quy doi (Bat buoc)', 'Ghi chu']);
        fputcsv($output, ['012345678901', 'N1', '10.0', 'Quy doi IELTS 6.5']);
        fclose($output);
        exit;
    }
}
