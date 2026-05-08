<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;

class EmailConfigController extends Controller {
    protected $masterData;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->masterData = new MasterData();
        
        // Generate CSRF token if not exists
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public function index() {
        $keys = [
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 
            'smtp_secure', 'email_from_name', 'email_from_address'
        ];
        
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = $this->masterData->getSetting($key);
        }

        $this->view('admin/settings/email', ['settings' => $settings]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            foreach ($data as $key => $val) {
                if (strpos($key, 'smtp_') === 0 || strpos($key, 'email_') === 0) {
                     $this->masterData->setSetting($key, $val);
                }
            }
            $this->redirect(url('/admin/settings/email?msg=saved'));
        }
    }
    
    public function test() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $to = $_POST['test_email'];
            $mailer = new \App\Services\MailerService();
            $result = $mailer->send($to, "Test Email from Admissions System", "<p>This is a test email to verify SMTP settings.</p>", true, 'system');
            
            if ($result === true) {
                $this->redirect(url('/admin/settings/email?msg=test_success'));
            } else {
                $this->redirect(url('/admin/settings/email?error=' . urlencode($result)));
            }
        }
    }
}
