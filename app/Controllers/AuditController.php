<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuditService;

class AuditController extends Controller {
    protected $auditService;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->auditService = new AuditService();
        
        // Only super_admin can view audit logs (will be enforced via PermissionMiddleware later)
    }

    public function index() {
        $filters = [
            'admin_id' => $_GET['admin_id'] ?? null,
            'action' => $_GET['action'] ?? null,
            'entity_type' => $_GET['entity_type'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
        ];

        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $logs = $this->auditService->getLogs($limit, $offset, $filters);
        $stats = $this->auditService->getStats();

        // Get distinct values for filters
        $db = \App\Core\Database::getInstance()->getConnection();
        $actions = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(\PDO::FETCH_COLUMN);
        $entityTypes = $db->query("SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type")->fetchAll(\PDO::FETCH_COLUMN);

        $this->view('admin/audit/index', [
            'logs' => $logs,
            'stats' => $stats,
            'actions' => $actions,
            'entityTypes' => $entityTypes,
            'filters' => $filters,
            'page' => $page
        ]);
    }

    public function purge() {
        if (!isset($_SESSION['admin_id'])) {
            $this->json(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $days = intval($_POST['days'] ?? 20);
        if ($days < 0) {
            $this->json(['success' => false, 'error' => 'Invalid days']);
            return;
        }

        try {
            if ($days === 0) {
                $this->auditService->clearAll();
                $this->auditService->log('CLEAR_ALL_LOGS', 'audit_logs', null, null, ['status' => 'success']);
            } else {
                $this->auditService->purgeOldRecords($days);
                $this->auditService->log('PURGE_LOGS', 'audit_logs', null, null, ['days' => $days]);
            }
            
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
