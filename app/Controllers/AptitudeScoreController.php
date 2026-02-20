<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\DiemNangKhieu;
use App\Models\QuanTriVien;

class AptitudeScoreController extends Controller {

    protected $diemNangKhieuModel;
    protected $currentUser;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        
        $this->diemNangKhieuModel = new DiemNangKhieu();
        
        $adminModel = new QuanTriVien();
        $this->currentUser = $adminModel->find($_SESSION['admin_id']);
        
        if (!$this->currentUser || !$this->currentUser['is_active']) {
            session_destroy();
            $this->redirect(url('/admin/login'));
        }
    }

    public function index() {
        // Basic permission check
        // if (!QuanTriVien::hasPermission($this->currentUser, 'manage_candidates')) { ... }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';

        // Query Builder for Scores
        // Since we don't have a full repository for this yet, we'll do raw SQL or simple model calls
        // Let's assume DiemNangKhieu has a basic getAll or we build it here.
        
        $sql = "SELECT d.*, t.ho_va_ten 
                FROM diem_nang_khieu d
                LEFT JOIN thi_sinh t ON d.so_cccd = t.so_cccd
                WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (d.so_cccd LIKE ? OR d.sbd LIKE ? OR t.ho_va_ten LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%"; // Search by name too
        }
        
        // Count
        $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as sub";
        $stmtCount = $this->diemNangKhieuModel->getDb()->prepare($countSql);
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();
        
        // Data
        $sql .= " ORDER BY d.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $this->diemNangKhieuModel->getDb()->prepare($sql);
        $stmt->execute($params);
        $scores = $stmt->fetchAll();
        
        $totalPages = ceil($total / $limit);

        $this->view('admin/aptitude_scores/index', [
            'scores' => $scores,
            'pagination' => ['current_page' => $page, 'total_pages' => $totalPages],
            'search' => $search,
            'user' => $this->currentUser
        ]);
    }

    public function import() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Check file upload
            if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] == 0) {
                $file = $_FILES['import_file']['tmp_name'];
                $handle = fopen($file, "r");
                
                $successCount = 0;
                $errors = [];
                $row = 0;
                
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $row++;
                    // Skip header if it looks like header
                    if ($row == 1 && !is_numeric($data[0]) && !is_numeric($data[2])) {
                        continue;
                    }
                    
                    // Format: CCCD, SBD, Score, Note, Subject Code(Optional)
                    $cccd = trim($data[0] ?? '');
                    $sbd = trim($data[1] ?? '');
                    $score = trim($data[2] ?? '');
                    $note = trim($data[3] ?? '');
                    $maMon = trim($data[4] ?? 'NK1');
                    
                    if (!$cccd || $score === '') {
                        $errors[] = "Row $row: Missing CCCD or Score";
                        continue;
                    }
                    
                    if (!is_numeric($score)) {
                        $errors[] = "Row $row: Score must be numeric";
                        continue;
                    }
                    
                    if ($this->diemNangKhieuModel->saveScore($cccd, $sbd, $score, $note, $maMon)) {
                        $successCount++;
                    } else {
                        $errors[] = "Row $row: DB Error for $cccd";
                    }
                }
                
                fclose($handle);
                
                $msg = "Imported $successCount records.";
                if (!empty($errors)) {
                    $msg .= " Errors: " . implode("; ", array_slice($errors, 0, 5)) . (count($errors)>5 ? "..." : "");
                }
                
                $this->redirect(url('/admin/aptitude-scores?msg=' . urlencode($msg)));
            } else {
                 // Check Manual Input
                 $cccd = $_POST['cccd'] ?? '';
                 $sbd = $_POST['sbd'] ?? '';
                 $score = $_POST['score'] ?? '';
                 $note = $_POST['note'] ?? '';
                 $maMon = $_POST['ma_mon'] ?? 'NK1';
                 
                 if ($cccd && $score !== '') {
                     if ($this->diemNangKhieuModel->saveScore($cccd, $sbd, $score, $note, $maMon)) {
                         $this->redirect(url('/admin/aptitude-scores?success=1'));
                     } else {
                         $this->redirect(url('/admin/aptitude-scores?error=Save failed'));
                     }
                 } else {
                     $this->redirect(url('/admin/aptitude-scores?error=Missing input'));
                 }
            }
        }
    }
    
    public function template() {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="aptitude_scores_template.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['CCCD', 'SBD (Optional)', 'Score', 'Note (Optional)']);
        fputcsv($output, ['001234567890', 'nk001', '8.5', 'Hat hay']);
        fclose($output);
        exit;
    }
}
