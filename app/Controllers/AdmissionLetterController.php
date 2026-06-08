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
            'status'   => $_GET['status'] ?? '',
            'q'        => $_GET['q'] ?? '',
            'page'     => $_GET['page'] ?? 1,
            'limit'    => $_GET['limit'] ?? 10,
            'f_name'   => $_GET['f_name'] ?? '',
            'f_cccd'   => $_GET['f_cccd'] ?? '',
            'f_phone'  => $_GET['f_phone'] ?? '',
            'f_email'  => $_GET['f_email'] ?? '',
            'f_dob'    => $_GET['f_dob'] ?? '',
            'f_major'  => $_GET['f_major'] ?? '',
        ];

        $result = $this->service->getCandidates($filters);
        $templates = $this->service->getTemplates();
        $batches = $this->service->getBatches();
        
        $this->view('admin/admission_letters/index', [
            'candidates' => $result['items'],
            'pagination' => $result['pagination'],
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
            http_response_code(404);
            echo '<div style="text-align:center;padding:40px;color:#666;">Không tìm thấy thí sinh.</div>';
            exit;
        }

        if ($templateId) {
            $tplStmt = $db->prepare("SELECT * FROM email_templates WHERE id = ?");
            $tplStmt->execute([$templateId]);
            $template = $tplStmt->fetch(\PDO::FETCH_ASSOC);
        } else {
            $template = $this->service->getTemplate();
        }

        if (!$template) {
            http_response_code(404);
            echo '<div style="text-align:center;padding:40px;color:#666;">Không tìm thấy mẫu email.</div>';
            exit;
        }

        // CSP header to mitigate XSS from imported data
        header("Content-Security-Policy: script-src 'none'");

        $html = $this->service->renderTemplate($template['body'], $candidate);
        
        echo '<div style="max-width: 900px; margin: 20px auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); padding: 20px;">' . $html . '</div>';
        exit;
    }

    /**
     * Danh sách các tài khoản email gửi thư (Rotating SMTP)
     */
    public function senders() {
        $this->requireAdmin();
        $db = \App\Core\Database::getInstance()->getConnection();
        
        $stmt = $db->query("SELECT * FROM email_senders ORDER BY created_at DESC");
        $senders = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->view('admin/admission_letters/senders', [
            'title' => 'Cấu hình Email Gửi thư',
            'senders' => $senders
        ]);
    }

    /**
     * Lưu/Cập nhật tài khoản email
     */
    public function saveSender() {
        $this->requireAdmin();
        $this->validateCsrf();
        
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'smtp_host' => $_POST['smtp_host'] ?? 'smtp.gmail.com',
            'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
            'smtp_user' => $_POST['smtp_user'] ?? '',
            'smtp_pass' => $_POST['smtp_pass'] ?? '',
            'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
            'daily_limit' => (int)($_POST['daily_limit'] ?? 1500),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'category' => 'admission_letter'
        ];
        
        $db = \App\Core\Database::getInstance()->getConnection();
        
        if ($id > 0) {
            $sql = "UPDATE email_senders SET name=?, email=?, smtp_host=?, smtp_port=?, smtp_user=?, smtp_pass=?, smtp_encryption=?, daily_limit=?, is_active=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$data['name'], $data['email'], $data['smtp_host'], $data['smtp_port'], $data['smtp_user'], $data['smtp_pass'], $data['smtp_encryption'], $data['daily_limit'], $data['is_active'], $id]);
        } else {
            $sql = "INSERT INTO email_senders (name, email, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_encryption, daily_limit, is_active, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$data['name'], $data['email'], $data['smtp_host'], $data['smtp_port'], $data['smtp_user'], $data['smtp_pass'], $data['smtp_encryption'], $data['daily_limit'], $data['is_active'], $data['category']]);
        }
        
        $this->redirect(url('/admin/admission-letters/senders?success=1'));
    }

    /**
     * Xóa toàn bộ dữ liệu trong đợt hoặc toàn bộ bảng
     */
    public function deleteAll() {
        $this->requireAdmin();
        $this->validateCsrf();

        $batchId = trim($_POST['batch_id'] ?? '');

        try {
            if ($batchId) {
                $this->service->deleteBatch($batchId);
            } else {
                $this->service->deleteAll();
            }
            $this->redirect(url('/admin/admission-letters?success=1&msg=deleted_all'));
        } catch (\Exception $e) {
            $this->redirect(url('/admin/admission-letters?error=' . urlencode('Lỗi xóa: ' . $e->getMessage())));
        }
    }

    /**
     * Gửi test email
     */
    public function sendTest() {
        $this->requireAdmin();
        $this->validateCsrf();

        $email = trim($_POST['test_email'] ?? '');
        $templateId = (int)($_POST['template_id'] ?? 0);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect(url('/admin/admission-letters?error=' . urlencode('Email không hợp lệ')));
            return;
        }

        if (!$templateId) {
            $this->redirect(url('/admin/admission-letters?error=' . urlencode('Chưa chọn mẫu email')));
            return;
        }

        try {
            $this->service->sendTestEmail($email, $templateId);
            $this->redirect(url('/admin/admission-letters?success=1&msg=test_queued'));
        } catch (\Exception $e) {
            $this->redirect(url('/admin/admission-letters?error=' . urlencode('Lỗi gửi test: ' . $e->getMessage())));
        }
    }

    /**
     * Xóa tài khoản email
     */
    public function deleteSender() {
        $this->requireAdmin();
        $this->validateCsrf();
        
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $db = \App\Core\Database::getInstance()->getConnection();
            $db->prepare("DELETE FROM email_senders WHERE id = ?")->execute([$id]);
        }
        
        $this->redirect(url('/admin/admission-letters/senders?success=1'));
    }
}
