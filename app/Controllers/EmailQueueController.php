<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class EmailQueueController extends Controller {
    public function __construct() {
        $this->requireAdmin();
        
        // Generate CSRF token if not exists
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public function index() {
        $db = Database::getInstance()->getConnection();
        
        // Combined Stats (gộp 2 truy vấn thành 1 để tối ưu hiệu năng)
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                COUNT(CASE WHEN status = 'sent' AND sent_at > NOW() - INTERVAL '1 hour' THEN 1 END) as hour_count,
                COUNT(CASE WHEN status = 'sent' AND sent_at > CURRENT_DATE THEN 1 END) as today_count,
                COUNT(CASE WHEN status = 'sent' AND sent_at > NOW() - INTERVAL '24 hours' THEN 1 END) as last_24h_count,
                COUNT(CASE WHEN status = 'sent' AND sent_at > NOW() - INTERVAL '7 days' THEN 1 END) as week_count
            FROM email_queue
        ");
        $allStats = $stmt->fetch(\PDO::FETCH_ASSOC);

        $stats = [
            'total' => $allStats['total'],
            'pending' => $allStats['pending'],
            'sent' => $allStats['sent'],
            'failed' => $allStats['failed'],
        ];

        $advStats = [
            'hour_count' => $allStats['hour_count'],
            'today_count' => $allStats['today_count'],
            'last_24h_count' => $allStats['last_24h_count'],
            'week_count' => $allStats['week_count'],
        ];

        // Tab and Filter logic
        $tab = $_GET['tab'] ?? 'pending';
        if (!in_array($tab, ['pending', 'sent', 'failed'])) {
            $tab = 'pending';
        }
        
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $whereClause = "status = ?";
        $params = [$tab];

        if (!empty($search)) {
            $whereClause .= " AND (recipient ILIKE ? OR subject ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        // Count total for pagination
        $countStmt = $db->prepare("SELECT COUNT(*) FROM email_queue WHERE $whereClause");
        $countStmt->execute($params);
        $totalItems = $countStmt->fetchColumn();
        $totalPages = max(1, ceil($totalItems / $limit));

        // Fetch items (chỉ SELECT cột cần thiết, loại bỏ body HTML nặng)
        $orderBy = ($tab === 'sent') ? 'sent_at DESC' : 'created_at DESC';
        $itemsStmt = $db->prepare("SELECT id, recipient, subject, status, category, created_at, sent_at, attempts, error, last_error FROM email_queue WHERE $whereClause ORDER BY $orderBy LIMIT $limit OFFSET $offset");
        $itemsStmt->execute($params);
        $items = $itemsStmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('admin/system/email_queue', [
            'stats' => $stats,
            'advStats' => $advStats,
            'items' => $items,
            'currentTab' => $tab,
            'search' => $search,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * Gửi lại email lỗi (POST + CSRF)
     */
    public function retry() {
        $this->validateCsrf();
        
        $id = $_POST['id'] ?? null;
        $db = Database::getInstance()->getConnection();
        
        if ($id) {
            $stmt = $db->prepare("UPDATE email_queue SET status = 'pending', attempts = 0 WHERE id = ? AND status = 'failed'");
            $stmt->execute([(int)$id]);
        } else {
            // Retry all failed
            $db->query("UPDATE email_queue SET status = 'pending', attempts = 0 WHERE status = 'failed'");
        }
        
        $this->redirect(url('/admin/email-queue?tab=failed&msg=retrying'));
    }

    /**
     * Xóa 1 email (POST + CSRF)
     */
    public function delete() {
        $this->validateCsrf();
        
        $id = $_POST['id'] ?? null;
        $db = Database::getInstance()->getConnection();
        
        if ($id) {
            $stmt = $db->prepare("DELETE FROM email_queue WHERE id = ?");
            $stmt->execute([(int)$id]);
        }
        
        $this->redirect(url('/admin/email-queue?msg=deleted'));
    }

    /**
     * Xóa toàn bộ email đã gửi (POST + CSRF)
     */
    public function clearSent() {
        $this->validateCsrf();
        
        $db = Database::getInstance()->getConnection();
        $db->query("DELETE FROM email_queue WHERE status = 'sent'");
        $this->redirect(url('/admin/email-queue?msg=cleared'));
    }
}
