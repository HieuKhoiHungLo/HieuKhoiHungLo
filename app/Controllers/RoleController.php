<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\PermissionService;
use App\Services\AuditService;

class RoleController extends Controller {
    protected $permissionService;
    protected $auditService;

    public function __construct() {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect(url('/admin/login'));
        }
        $this->permissionService = new PermissionService();
        $this->auditService = new AuditService();
        
        // Check permission
        if (!$this->permissionService->can('role.view')) {
            die('Bạn không có quyền truy cập chức năng này.');
        }
    }

    public function index() {
        $roles = $this->permissionService->getAllRoles();
        $allPermissions = PermissionService::getAvailablePermissions();

        $this->view('admin/roles/index', [
            'roles' => $roles,
            'allPermissions' => $allPermissions
        ]);
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect(url('/admin/roles'));
        }

        $role = $this->permissionService->getRole($id);
        if (!$role) {
            die('Role not found');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->permissionService->can('role.edit')) {
                die('Không có quyền chỉnh sửa.');
            }

            $displayName = $_POST['display_name'];
            $permissions = $_POST['permissions'] ?? [];

            $oldValue = $role;
            $this->permissionService->updateRole($id, $displayName, $permissions);
            
            $this->auditService->log('UPDATE', 'role', $id, $oldValue, ['display_name' => $displayName, 'permissions' => $permissions]);

            $this->redirect(url('/admin/roles?msg=updated'));
        }

        $allPermissions = PermissionService::getAvailablePermissions();
        $rolePermissions = json_decode($role['permissions'], true) ?? [];

        $this->view('admin/roles/edit', [
            'role' => $role,
            'rolePermissions' => $rolePermissions,
            'allPermissions' => $allPermissions
        ]);
    }
}
