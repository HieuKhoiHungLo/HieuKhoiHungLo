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
        
        // Lấy số lượng đã xóa lịch sử
        $historyStmt = $db->query("SELECT value FROM email_queue_stats WHERE key = 'cleared_sent_total'");
        $clearedTotal = (int)($historyStmt->fetchColumn() ?: 0);

        // Combined Stats with 2-minute caching to reduce DB load
        $allStats = \App\Core\Cache::remember('email_queue_summary_stats', 2, function() use ($db) {
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
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        });

        $stats = [
            'total' => $allStats['total'] + $clearedTotal,
            'pending' => $allStats['pending'],
            'sent' => $allStats['sent'] + $clearedTotal,
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
            'isPaused' => (new \App\Models\MasterData())->getSetting('email_queue_paused') === '1',
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
        
        $ids = $_POST['ids'] ?? $_POST['id'] ?? null;
        $db = Database::getInstance()->getConnection();
        
        if ($ids) {
            if (!is_array($ids)) {
                $ids = explode(',', $ids);
            }
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $stmt = $db->prepare("DELETE FROM email_queue WHERE id IN ($placeholders)");
            $stmt->execute($ids);
        }
        
        $this->redirect(url('/admin/email-queue?msg=deleted'));
    }

    /**
     * Tạm dừng/Tiếp tục hàng đợi (POST + CSRF)
     */
    public function togglePause() {
        $this->validateCsrf();
        
        $master = new \App\Models\MasterData();
        $isPaused = $master->getSetting('email_queue_paused') === '1';
        
        $master->setSetting('email_queue_paused', $isPaused ? '0' : '1');
        
        $this->redirect(url('/admin/email-queue?msg=' . ($isPaused ? 'resumed' : 'paused')));
    }

    /**
     * Làm sạch hàng đợi (Xóa các thư chưa gửi hoặc bị lỗi) - Batch delete to avoid timeout
     */
    public function clearQueue() {
        $this->validateCsrf();
        
        $db = Database::getInstance()->getConnection();
        
        // Batch delete 1000 rows at a time to avoid timeout on remote DB
        $batchSize = 1000;
        do {
            $stmt = $db->prepare("DELETE FROM email_queue WHERE id IN (SELECT id FROM email_queue WHERE status IN ('pending', 'failed') LIMIT ?)");
            $stmt->execute([$batchSize]);
            $deleted = $stmt->rowCount();
        } while ($deleted >= $batchSize);
        
        // Clear cache so stats reflect immediately
        \App\Core\Cache::forget('email_queue_summary_stats');
        
        $this->redirect(url('/admin/email-queue?msg=cleared'));
    }

    /**
     * Purge sent emails older than X days or clear all sent entirely (POST + CSRF) - Maintain stats
     */
    public function purgeOldEmails() {
        $this->validateCsrf();
        
        $all = isset($_POST['all']) && $_POST['all'] == '1';
        $master = new \App\Models\MasterData();
        $retentionDays = (int)($master->getSetting('email_retention_days') ?: 10);
        
        $db = Database::getInstance()->getConnection();
        
        if ($all) {
            // Count all sent emails to be cleared
            $stmt = $db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'sent'");
            $count = (int)$stmt->fetchColumn();
            
            if ($count > 0) {
                // Delete all sent
                $db->query("DELETE FROM email_queue WHERE status = 'sent'");
            }
            $msg = 'purged_all';
        } else {
            // Count sent emails older than retentionDays
            $stmt = $db->prepare("SELECT COUNT(*) FROM email_queue WHERE status = 'sent' AND sent_at < NOW() - ? * INTERVAL '1 day'");
            $stmt->execute([$retentionDays]);
            $count = (int)$stmt->fetchColumn();
            
            if ($count > 0) {
                // Delete sent emails older than retentionDays
                $delStmt = $db->prepare("DELETE FROM email_queue WHERE status = 'sent' AND sent_at < NOW() - ? * INTERVAL '1 day'");
                $delStmt->execute([$retentionDays]);
            }
            $msg = 'purged_old';
        }
        
        if ($count > 0) {
            // Update cleared_sent_total in stats table
            $stmtStats = $db->prepare("SELECT value FROM email_queue_stats WHERE key = 'cleared_sent_total'");
            $stmtStats->execute();
            $exists = $stmtStats->fetchColumn() !== false;
            
            if ($exists) {
                $db->prepare("UPDATE email_queue_stats SET value = value + ? WHERE key = 'cleared_sent_total'")->execute([$count]);
            } else {
                $db->prepare("INSERT INTO email_queue_stats (key, value) VALUES ('cleared_sent_total', ?)")->execute([$count]);
            }
            
            // Clear summary stats cache so count changes take effect immediately
            \App\Core\Cache::forget('email_queue_summary_stats');
        }
        
        $this->redirect(url('/admin/email-queue?msg=' . $msg));
    }
}
