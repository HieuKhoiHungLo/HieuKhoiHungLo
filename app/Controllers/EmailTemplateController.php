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
}
