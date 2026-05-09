<?php
namespace App\Controllers;

use App\Core\Controller;

class EmailSenderController extends Controller {
    
    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
    }

    public function index() {
        $db = \App\Core\Database::getInstance()->getConnection();
        
        // Cache sender list for 5 minutes (except when clear success msg is present)
        $cacheKey = 'email_senders_list';
        if (isset($_GET['success'])) {
            \App\Core\Cache::forget($cacheKey);
        }
        
        $senders = \App\Core\Cache::remember($cacheKey, 5, function() use ($db) {
            $stmt = $db->query("SELECT * FROM email_senders ORDER BY is_default DESC, created_at DESC");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
        
        $this->view('admin/settings/email_senders', [
            'title' => 'Cấu hình Email SMTP Rotating',
            'senders' => $senders,
            'needsDataTables' => true
        ]);
    }

    public function save() {
        $this->validateCsrf();
        
        $id = (int)($_POST['id'] ?? 0);
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        
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
            'is_default' => $isDefault,
            'category' => $_POST['category'] ?? 'all'
        ];
        
        $db = \App\Core\Database::getInstance()->getConnection();
        
        // If this is set as default, unset other defaults
        if ($isDefault) {
            $db->exec("UPDATE email_senders SET is_default = FALSE");
        }
        
        if ($id > 0) {
            $sql = "UPDATE email_senders SET name=?, email=?, smtp_host=?, smtp_port=?, smtp_user=?, smtp_pass=?, smtp_encryption=?, daily_limit=?, is_active=?, is_default=?, category=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['name'], $data['email'], $data['smtp_host'], $data['smtp_port'], 
                $data['smtp_user'], $data['smtp_pass'], $data['smtp_encryption'], 
                $data['daily_limit'], $data['is_active'], $data['is_default'], 
                $data['category'], $id
            ]);
        } else {
            $sql = "INSERT INTO email_senders (name, email, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_encryption, daily_limit, is_active, is_default, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['name'], $data['email'], $data['smtp_host'], $data['smtp_port'], 
                $data['smtp_user'], $data['smtp_pass'], $data['smtp_encryption'], 
                $data['daily_limit'], $data['is_active'], $data['is_default'], 
                $data['category']
            ]);
        }
        
        $this->redirect(url('/admin/settings/email-senders?success=1'));
    }

    public function delete() {
        $this->validateCsrf();
        
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $db = \App\Core\Database::getInstance()->getConnection();
            $db->prepare("DELETE FROM email_senders WHERE id = ?")->execute([$id]);
        }
        
        $this->redirect(url('/admin/settings/email-senders?success=1'));
    }

    public function test() {
        $this->requireAdmin();
        
        $id = (int)($_POST['id'] ?? 0);
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM email_senders WHERE id = ?");
        $stmt->execute([$id]);
        $sender = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$sender) {
            return $this->json(['success' => false, 'message' => 'Không tìm thấy tài khoản']);
        }

        $mailer = new \App\Services\MailerService();
        $testEmail = $_POST['test_email'] ?? $sender['email']; 
        
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['success' => false, 'message' => 'Địa chỉ email không hợp lệ']);
        }
        
        // Use the specific config to send
        $result = $mailer->sendSmtpWithConfig(
            $testEmail, 
            "Test Connection: " . $sender['name'], 
            "<p>Xin chào, đây là thư kiểm tra kết nối SMTP cho tài khoản <b>" . $sender['email'] . "</b>.</p><p>Nếu bạn nhận được thư này, cấu hình đã hoạt động chính xác!</p>",
            true,
            $sender
        );

        if ($result === true) {
            return $this->json(['success' => true, 'message' => 'Kết nối thành công! Thư thử nghiệm đã được gửi tới ' . $testEmail]);
        } else {
            return $this->json(['success' => false, 'message' => 'Lỗi kết nối: ' . $result]);
        }
    }
}
