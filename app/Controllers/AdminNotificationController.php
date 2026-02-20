<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notification;
use App\Models\AdmissionSession;

class AdminNotificationController extends Controller {
    protected $notificationModel;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->notificationModel = new Notification();
    }

    /**
     * List all notifications
     */
    public function index() {
        $notifications = $this->notificationModel->getAll();
        
        $this->view('admin/notifications/index', [
            'notifications' => $notifications
        ]);
    }

    /**
     * Create notification form
     */
    public function create() {
        $sessionModel = new AdmissionSession();
        $sessions = $sessionModel->getAll();
        
        $this->view('admin/notifications/create', [
            'sessions' => $sessions
        ]);
    }

    /**
     * Store new notification
     */
    public function store() {
        $this->validateCsrf();
        
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = $_POST['type'] ?? 'info';
        $targetType = $_POST['target_type'] ?? 'all';
        $targetId = $_POST['target_id'] ?? null;
        
        if (empty($title) || empty($content)) {
            $this->redirect(url('/admin/notifications/create?error=missing_fields'));
            return;
        }

        $id = $this->notificationModel->create([
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'target_type' => $targetType,
            'target_id' => $targetId ?: null,
            'created_by' => $_SESSION['admin_id'] ?? null
        ]);

        if ($id) {
            $this->redirect(url('/admin/notifications?msg=created'));
        } else {
            $this->redirect(url('/admin/notifications/create?error=failed'));
        }
    }

    /**
     * Delete notification
     */
    public function delete() {
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->notificationModel->delete((int)$id);
        }
        
        $this->redirect(url('/admin/notifications?msg=deleted'));
    }
}
