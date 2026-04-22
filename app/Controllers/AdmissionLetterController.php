<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\AdmissionLetterService;

class AdmissionLetterController extends Controller {
    protected $service;

    public function __construct() {
        $this->service = new AdmissionLetterService();
    }

    /**
     * Danh sách thí sinh import
     */
    public function index() {
        $this->requireAdmin();
        
        $filters = [
            'batch_id' => $_GET['batch_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'q' => $_GET['q'] ?? ''
        ];

        $candidates = $this->service->getCandidates($filters);
        $templates = $this->service->getTemplates();
        $batches = $this->service->getBatches();
        
        $this->view('admin/admission_letters/index', [
            'candidates' => $candidates,
            'templates' => $templates,
            'batches' => $batches,
            'filters' => $filters
        ]);
    }

    /**
     * Form import excel
     */
    public function importForm() {
        $this->requireAdmin();
        $this->view('admin/admission_letters/import', []);
    }

    /**
     * Xử lý import
     */
    public function import() {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $batchId = trim($_POST['batch_id'] ?? '');
            
            if (empty($batchId)) {
                $batchId = 'Đợt ' . date('Ymd_His');
            }

            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->redirect(url('/admin/admission-letters/import?error=File+load+error'));
                return;
            }

            $filepath = $_FILES['file']['tmp_name'];
            
            try {
                $result = $this->service->importFromExcel($filepath, $batchId);
                $this->redirect(url('/admin/admission-letters?success=1&imported=' . $result['imported'] . '&ignored=' . $result['ignored']));
            } catch (\Exception $e) {
                $this->redirect(url('/admin/admission-letters/import?error=' . urlencode($e->getMessage())));
            }
        }
    }

    /**
     * Xử lý thao tác hàng loạt
     */
    public function bulkAction() {
        $this->requireAdmin();
        $this->validateCsrf();

        $action = $_POST['action'] ?? '';
        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            $this->redirect(url('/admin/admission-letters?error=Vui+lòng+chọn+ít+nhất+1+thí+sinh'));
            return;
        }

        try {
            if ($action === 'send_email') {
                $templateId = (int)($_POST['template_id'] ?? 0);
                if (!$templateId) throw new \Exception("Chưa chọn mẫu email.");
                
                $count = $this->service->enqueueSelected($ids, $templateId);
                $this->redirect(url("/admin/admission-letters?success=1&msg=queued&count=$count"));
            } elseif ($action === 'delete') {
                $this->service->deleteSelected($ids);
                $this->redirect(url('/admin/admission-letters?success=1&msg=deleted'));
            }
        } catch (\Exception $e) {
            $this->redirect(url('/admin/admission-letters?error=' . urlencode($e->getMessage())));
        }
    }

    /**
     * Trang xem màn hình mẫu email
     */
    public function template() {
        $this->requireAdmin();
        // Since the template logic is already in email_templates, 
        // we can just forward user to edit the ADMISSION_LETTER template.
        
        $template = $this->service->getTemplate();
        if ($template) {
            $this->redirect(url('/admin/settings/email-templates/edit?id=' . $template['id']));
        } else {
             $this->redirect(url('/admin/settings/email-templates'));
        }
    }

    /**
     * Preview 1 thí sinh cụ thể
     */
    public function preview() {
        $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $templateId = (int)($_GET['tpl'] ?? 0);

        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM thu_trung_tuyen WHERE id = ?");
        $stmt->execute([$id]);
        $candidate = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$candidate) {
            die("Không tìm thấy thí sinh.");
        }

        if ($templateId) {
            $tplStmt = $db->prepare("SELECT * FROM email_templates WHERE id = ?");
            $tplStmt->execute([$templateId]);
            $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);
        } else {
            $template = $this->service->getTemplate();
        }

        if (!$template) {
            die("Không tìm thấy mẫu email.");
        }

        $html = $this->service->renderTemplate($template['body'], $candidate);
        
        echo '<div style="max-width: 900px; margin: 20px auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); padding: 20px;">' . $html . '</div>';
        exit;
    }

}
