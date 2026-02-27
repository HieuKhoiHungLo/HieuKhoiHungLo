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
        $filter = $_GET['filter'] ?? 'all';
        $onlyUnread = ($filter === 'unread');
        
        try {
            $notifications = $this->notificationModel->getForUser($cccd, $onlyUnread);
            $unreadCount = $this->notificationModel->countUnread($cccd);
            
            error_log("Notif Debug: CCCD={$cccd}, Filter={$filter}, Count=" . count($notifications));
            
            return $this->json([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            error_log("Notif Error: " . $e->getMessage());
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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
