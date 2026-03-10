<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\ImportService;
use App\Repositories\ImportRepository;

class ImportController extends Controller {
    protected $importService;
    protected $importRepo;

    public function __construct() {
        parent::__construct();
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['status' => false, 'message' => 'Invalid method']);
            return;
        }

        $type = $_POST['type'] ?? '';
        $batchId = $_POST['batch_id'] ?? '';
        
        if (empty($batchId) || empty($type)) {
            $this->json(['status' => false, 'message' => 'Missing parameters']);
            return;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['status' => false, 'message' => 'Upload failed']);
            return;
        }

        $uploadDir = __DIR__ . '/../../storage/imports/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($_FILES['file']['name']));
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            $this->json(['status' => false, 'message' => 'Failed to save file']);
            return;
        }

        $adminId = $_SESSION['admin_id'] ?? 1; // Fallback
        $result = ['status' => false, 'message' => 'Unknown type'];

        if ($type === 'candidates') {
            $result = $this->importService->parseCandidates($filePath, $batchId, $adminId);
        } elseif ($type === 'applications') {
            // Need target school code? Config or Param?
            // Assume we grab it from settings or hardcode 'HVU' (Example) or pass in form.
            // Let's assume passed in env or hardcoded for now, or fetch from SettingsRepo.
            // Using placeholder 'CHECK_DB' for service to validate if needed or just pass empty if checks done inside.
            // Service Logic: if ($schoolCode !== $target) continue.
            // Let's rely on user to upload correct file for now.
            // Or get school code from Settings.
            $result = $this->importService->parseApplications($filePath, $batchId, $adminId, 'HVU'); // Default placeholder
        } elseif ($type === 'transcripts') {
            $result = $this->importService->parseTranscripts($filePath, $batchId, $adminId);
        }

        $this->json($result);
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
