<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\ImportService;
use App\Repositories\ImportRepository;

class ImportController extends Controller {
    protected $importService;
    protected $importRepo;

    public function __construct() {
        $this->importService = new ImportService();
        $this->importRepo = new ImportRepository();
    }

    public function index() {
        $this->requireAdmin();
        $batches = $this->importRepo->getAllBatches();
        $history = $this->importRepo->getImportHistory();
        $activeBatch = $this->importRepo->getActiveBatch();
        
        $this->view('admin/import/index', [
            'batches' => $batches, 
            'history' => $history,
            'activeBatch' => $activeBatch
        ]);
    }

    public function upload() {
        $this->requireAdmin();
        
        // Increase resources for large MOET files
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '600');
        set_time_limit(600);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['status' => false, 'message' => 'Invalid method']);
            return;
        }

        try {
            $type = $_POST['type'] ?? '';
            $batchId = $_POST['batch_id'] ?? '';
            
            if (empty($batchId) || empty($type)) {
                $this->json(['status' => false, 'message' => 'Vui lòng chọn hoặc tạo đợt tuyển sinh trước khi import.']);
                return;
            }

            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->json(['status' => false, 'message' => 'Lỗi upload file hoặc file quá lớn so với cấu hình máy chủ.']);
                return;
            }

            $storageDir = __DIR__ . '/../../storage';
            $uploadDir = $storageDir . '/imports/';
            
            if (!is_dir($storageDir)) mkdir($storageDir, 0777, true);
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($_FILES['file']['name']));
            $filePath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                $this->json(['status' => false, 'message' => 'Không thể lưu file vào thư mục storage. Vui lòng kiểm tra quyền ghi thư mục.']);
                return;
            }

            $adminId = $_SESSION['admin_id'] ?? 1;
            $result = ['status' => false, 'message' => 'Unknown type'];

            $batch = $this->importRepo->getActiveBatch();
            $year = $batch ? (int)$batch['nam_tuyen_sinh'] : (int)date('Y');

            if ($type === 'candidates') {
                $result = $this->importService->parseCandidates($filePath, $batchId, $adminId, $year);
            } elseif ($type === 'applications') {
                $result = $this->importService->parseApplications($filePath, $batchId, $adminId, 'THV');
            } elseif ($type === 'transcripts') {
                $result = $this->importService->parseTranscripts($filePath, $batchId, $adminId);
            }

            $this->json($result);
        } catch (\Throwable $e) {
            $this->json([
                'status' => false, 
                'message' => 'Lỗi hệ thống trong quá trình import: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
    
    public function createBatch() {
        $this->requireAdmin();
        $name = $_POST['name'] ?? '';
        $year = $_POST['year'] ?? date('Y');
        
        if (empty($name)) {
            $this->redirect('/admin/import?error=Name required');
        }
        
        $this->importRepo->createBatch($name, $year);
        $this->redirect('/admin/import?success=Batch created');
    }
}
