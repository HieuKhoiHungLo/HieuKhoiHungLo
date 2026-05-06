<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class EmailQueueController extends Controller {
    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        
        // Generate CSRF token if not exists
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public function index() {
        $db = Database::getInstance()->getConnection();
        
        // General Stats
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed
            FROM email_queue
        ");
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Advanced Stats
        $stmtAdv = $db->query("
            SELECT 
                COUNT(CASE WHEN sent_at > NOW() - INTERVAL '1 hour' THEN 1 END) as hour_count,
                COUNT(CASE WHEN sent_at > CURRENT_DATE THEN 1 END) as today_count,
                COUNT(CASE WHEN sent_at > NOW() - INTERVAL '24 hours' THEN 1 END) as last_24h_count,
                COUNT(CASE WHEN sent_at > NOW() - INTERVAL '7 days' THEN 1 END) as week_count
            FROM email_queue 
            WHERE status = 'sent'
        ");
        $advStats = $stmtAdv->fetch(\PDO::FETCH_ASSOC);

        // Tab and Filter logic
        $tab = $_GET['tab'] ?? 'pending';
        if (!in_array($tab, ['pending', 'sent', 'failed'])) {
            $tab = 'pending';
        }
        
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
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
        $totalPages = ceil($totalItems / $limit);

        // Fetch items
        $orderBy = ($tab === 'pending') ? 'created_at ASC' : ($tab === 'sent' ? 'sent_at DESC' : 'created_at DESC');
        $itemsStmt = $db->prepare("SELECT * FROM email_queue WHERE $whereClause ORDER BY $orderBy LIMIT $limit OFFSET $offset");
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

    public function retry() {
        $id = $_GET['id'] ?? null;
        $db = Database::getInstance()->getConnection();
        
        if ($id) {
            $stmt = $db->prepare("UPDATE email_queue SET status = 'pending', attempts = 0 WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            // Retry all failed
            $db->query("UPDATE email_queue SET status = 'pending', attempts = 0 WHERE status = 'failed'");
        }
        
        $this->redirect(url('/admin/email-queue?msg=retrying'));
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        $db = Database::getInstance()->getConnection();
        
        if ($id) {
            $stmt = $db->prepare("DELETE FROM email_queue WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        $this->redirect(url('/admin/email-queue?msg=deleted'));
    }

    public function clearSent() {
        $db = Database::getInstance()->getConnection();
        $db->query("DELETE FROM email_queue WHERE status = 'sent'");
        $this->redirect(url('/admin/email-queue?msg=cleared'));
    }
}
