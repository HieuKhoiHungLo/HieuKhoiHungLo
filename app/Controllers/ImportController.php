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
        
        $token = $_POST['import_token'] ?? ('imp_' . time());
        $progressDir = __DIR__ . "/../../storage/logs";
        if (!is_dir($progressDir)) mkdir($progressDir, 0777, true);
        $progressFile = $progressDir . "/import_progress_{$token}.json";

        // Reset and initialize progress file IMMEDIATELY before session close
        file_put_contents($progressFile, json_encode([
            'percent' => 0, 
            'message' => 'Đang kết nối máy chủ...',
            'updated_at' => date('Y-m-d H:i:s')
        ]));
        
        // Increase resources for large MOET files
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '3600');
        set_time_limit(3600);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['status' => false, 'message' => 'Invalid method']);
            return;
        }

        try {
            $type = $_POST['type'] ?? '';
            $batchId = $_POST['batch_id'] ?? '';

            // Release session lock so progress polling can work
            session_write_close();
            
            // Re-update to show spreadsheet loading started
            file_put_contents($progressFile, json_encode([
                'percent' => 0, 
                'message' => 'Đang nạp file Excel vào bộ nhớ (32k+ dòng)...',
                'updated_at' => date('Y-m-d H:i:s')
            ]));
            
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

            $result = ['status' => false, 'message' => 'Unknown type'];

            $batch = $this->importRepo->getActiveBatch();
            $year = $batch ? (int)$batch['nam_tuyen_sinh'] : (int)date('Y');

            if ($type === 'candidates') {
                $result = $this->importService->parseCandidates($filePath, $batchId, $token, $year);
            } elseif ($type === 'applications') {
                $result = $this->importService->parseApplications($filePath, $batchId, $token, 'THV');
            } elseif ($type === 'transcripts') {
                $result = $this->importService->parseTranscripts($filePath, $batchId, $token);
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

    public function progress() {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            $this->json(['percent' => 0, 'message' => 'Đang khởi tạo kết nối...']);
            return;
        }

        $progressFile = __DIR__ . "/../../storage/logs/import_progress_{$token}.json";
        
        if (file_exists($progressFile)) {
            $data = json_decode(file_get_contents($progressFile), true);
            $this->json($data);
        } else {
            $this->json(['percent' => 0, 'message' => 'Đang đợi dữ liệu nạp...']);
        }
    }

    public function createBatch() {
        $this->requireAdmin();
        $name = $_POST['name'] ?? '';
        $year = $_POST['year'] ?? date('Y');
        
        $this->importRepo->createBatch($name, $year);
        $this->redirect(url('/admin/master-data/sessions'));
    }
}
