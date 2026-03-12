<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ChungChiThiSinh;
use App\Models\MasterData;

class CertificateScoreController extends Controller {
    protected $model;
    protected $masterData;

    public function __construct() {
        $this->model = new ChungChiThiSinh();
        $this->masterData = new MasterData();
    }

    public function index() {
        $stats = [
            'total' => $this->model->getDb()->query("SELECT COUNT(*) FROM chung_chi_thi_sinh")->fetchColumn(),
        ];
        $this->view('admin/certificate_scores/index', ['title' => 'Điểm Chứng Chỉ', 'stats' => $stats]);
    }

    public function apiList() {
        $db = $this->model->getDb();
        $draw = $_POST['draw'] ?? 1;
        $start = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;
        $searchValue = $_POST['search']['value'] ?? '';

        $query = "SELECT c.id, c.so_cccd, c.loai_chung_chi, c.diem_chung_chi, c.file_minh_chung_cc, ts.ho_va_ten 
                  FROM chung_chi_thi_sinh c
                  LEFT JOIN thi_sinh ts ON c.so_cccd = ts.so_cccd
                  WHERE 1=1";
        $params = [];

        if (!empty($searchValue)) {
            $query .= " AND (c.so_cccd LIKE ? OR c.loai_chung_chi LIKE ? OR ts.ho_va_ten LIKE ?)";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
            $params[] = "%$searchValue%";
        }

        // Total count
        $stmtTotal = $db->query("SELECT COUNT(*) FROM chung_chi_thi_sinh");
        $recordsTotal = $stmtTotal->fetchColumn();

        // Filtered count
        $countQuery = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) FROM', $query);
        $stmtFiltered = $db->prepare($countQuery);
        $stmtFiltered->execute($params);
        $recordsFiltered = $stmtFiltered->fetchColumn();

        // Data
        $query .= " ORDER BY c.id DESC LIMIT " . intval($length) . " OFFSET " . intval($start);
        $stmtData = $db->prepare($query);
        $stmtData->execute($params);
        $data = $stmtData->fetchAll(\PDO::FETCH_ASSOC);

        echo json_encode([
            "draw" => intval($draw),
            "recordsTotal" => intval($recordsTotal),
            "recordsFiltered" => intval($recordsFiltered),
            "data" => $data
        ]);
        exit;
    }

    public function import() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/certificate-scores');
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = "Vui lòng chọn file hợp lệ.";
            $this->redirect('/admin/certificate-scores');
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $extension = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
        if (strtolower($extension) !== 'csv') {
            $_SESSION['flash_error'] = "Vui lòng sử dụng định dạng file .csv (UTF-8).";
            $this->redirect('/admin/certificate-scores');
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
                    if (count($row) < 3) {
                        $errorCount++;
                        continue;
                    }
                    if (empty($row[0]) || empty($row[1])) continue; // Missing CCCD or cert type

                    $cccd = trim($row[0] ?? '');
                    $certType = trim($row[1] ?? 'IELTS');
                    $score = trim($row[2] ?? 0);
                    // $note = trim($row[3] ?? '');

                    if ($cccd && $certType) {
                        // Delete old cert of this type for this student
                        $stmtDel = $db->prepare("DELETE FROM chung_chi_thi_sinh WHERE so_cccd = ? AND loai_chung_chi = ?");
                        $stmtDel->execute([$cccd, $certType]);
                        
                        // Insert new
                        $stmtIns = $db->prepare("INSERT INTO chung_chi_thi_sinh (so_cccd, loai_chung_chi, diem_chung_chi) VALUES (?, ?, ?)");
                        $stmtIns->execute([$cccd, $certType, $score]);
                        
                        // Cập nhật co_chung_chi_qt
                        $stmtUpdate = $db->prepare("UPDATE thi_sinh SET co_chung_chi_qt = 'true' WHERE so_cccd = ?");
                        $stmtUpdate->execute([$cccd]);
                        
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

        $this->redirect('/admin/certificate-scores');
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt = $this->model->getDb()->prepare("DELETE FROM chung_chi_thi_sinh WHERE id = ?");
                if ($stmt->execute([$id])) {
                    echo json_encode(['success' => true]);
                    exit;
                }
            }
        }
        echo json_encode(['success' => false]);
    }
}
