<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\EmailTemplateService;

class EmailTemplateController extends Controller {
    protected $templateService;

    public function __construct() {
        $this->templateService = new EmailTemplateService();
    }

    /**
     * List all templates
     */
    public function index() {
        $this->requireAdmin();
        
        $templates = $this->templateService->getAllTemplates();
        
        $this->view('admin/email_templates/index', [
            'templates' => $templates
        ]);
    }

    /**
     * Edit template form
     */
    public function edit() {
        $this->requireAdmin();
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect(url('/admin/settings/email-templates'));
            return;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM email_templates WHERE id = ?");
        $stmt->execute([$id]);
        $template = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$template) {
            $this->redirect(url('/admin/settings/email-templates?error=not_found'));
            return;
        }

        $this->view('admin/email_templates/edit', [
            'template' => $template
        ]);
    }

    /**
     * Save template
     */
    public function save() {
        $this->requireAdmin();
        $this->validateCsrf();

        $id = $_POST['id'] ?? null;
        $subject = $_POST['subject'] ?? '';
        $body = $_POST['body'] ?? '';

        if (!$id || empty($subject)) {
            $this->redirect(url('/admin/settings/email-templates?error=invalid'));
            return;
        }

        $this->templateService->updateTemplate((int)$id, $subject, $body);
        
        $this->redirect(url('/admin/settings/email-templates?msg=saved'));
    }

    /**
     * Preview template
     */
    public function preview() {
        $this->requireAdmin();
        
        $slug = $_GET['slug'] ?? '';
        
        // Sample data for preview
        $sampleData = [
            'ho_ten' => 'Nguyễn Văn A',
            'cccd' => '012345678901',
            'mat_khau' => 'abc123***',
            'login_url' => url('/login'),
            'ket_qua_chi_tiet' => $this->templateService->buildReviewResultHtml([
                ['name' => 'Thông tin cá nhân', 'status' => 'ok', 'note' => ''],
                ['name' => 'Ảnh chân dung', 'status' => 'missing', 'note' => 'Chưa upload'],
                ['name' => 'Học bạ THPT', 'status' => 'ok', 'note' => ''],
            ]),
            'ghi_chu' => 'Vui lòng bổ sung ảnh chân dung để hoàn tất hồ sơ.'
        ];

        $rendered = $this->templateService->render($slug, $sampleData);
        
        if ($rendered) {
            echo $rendered['body'];
        } else {
            echo '<p>Template không tồn tại.</p>';
        }
        exit;
    }

    /**
     * Create template form
     */
    public function create() {
        $this->requireAdmin();
        $this->view('admin/email_templates/create');
    }

    /**
     * Store new template
     */
    public function store() {
        $this->requireAdmin();
        $this->validateCsrf();

        $code = trim($_POST['code'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body = $_POST['body'] ?? '';
        $variables = trim($_POST['variables'] ?? '');

        // Validation
        if (empty($code) || empty($subject) || empty($body)) {
            $this->redirect(url('/admin/settings/email-templates?error=invalid'));
            return;
        }

        // Validate code pattern (alphanumeric and underscores only)
        if (!preg_match('/^[a-z0-9_]+$/', $code)) {
            $this->redirect(url('/admin/settings/email-templates?error=invalid'));
            return;
        }

        // Check if code already exists
        $existing = $this->templateService->getTemplate($code);
        if ($existing) {
            $this->redirect(url('/admin/settings/email-templates?error=code_exists'));
            return;
        }

        $success = $this->templateService->createTemplate($code, $subject, $body, $variables, 'custom');
        if ($success) {
            $this->redirect(url('/admin/settings/email-templates?msg=saved'));
        } else {
            $this->redirect(url('/admin/settings/email-templates?error=invalid'));
        }
    }

    /**
     * Delete template
     */
    public function delete() {
        $this->requireAdmin();
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect(url('/admin/settings/email-templates'));
            return;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM email_templates WHERE id = ?");
        $stmt->execute([$id]);
        $template = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$template) {
            $this->redirect(url('/admin/settings/email-templates?error=not_found'));
            return;
        }

        // System templates cannot be deleted
        if (($template['type'] ?? 'system') === 'system') {
            $this->redirect(url('/admin/settings/email-templates?error=system_protected'));
            return;
        }

        $success = $this->templateService->deleteTemplate((int)$id);
        if ($success) {
            $this->redirect(url('/admin/settings/email-templates?msg=deleted'));
        } else {
            $this->redirect(url('/admin/settings/email-templates?error=invalid'));
        }
    }
}

