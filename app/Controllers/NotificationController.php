<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notification;

class NotificationController extends Controller {
    protected $notificationModel;

    public function __construct() {
        $this->notificationModel = new Notification();
    }

    /**
     * Get notifications for current user (API endpoint)
     */
    public function getNotifications() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['cccd'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }
        
        $cccd = $_SESSION['cccd'];
        
        $notifications = $this->notificationModel->getForUser($cccd);
        $unreadCount = $this->notificationModel->countUnread($cccd);
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markRead() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['cccd'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing id']);
            return;
        }
        
        $this->notificationModel->markAsRead((int)$id, $_SESSION['cccd']);
        
        echo json_encode(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllRead() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['cccd'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }
        
        $cccd = $_SESSION['cccd'];
        
        $this->notificationModel->markAllAsRead($cccd);
        
        echo json_encode(['success' => true]);
    }

    /**
     * Get unread count (for header badge)
     */
    public function getUnreadCount() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['cccd'])) {
            echo json_encode(['count' => 0]);
            return;
        }
        
        $cccd = $_SESSION['cccd'];
        
        $count = $this->notificationModel->countUnread($cccd);
        
        echo json_encode(['count' => $count]);
    }
}
