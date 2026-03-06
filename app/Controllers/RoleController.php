<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\PermissionService;
use App\Services\AuditService;

class RoleController extends Controller
{
    protected $permissionService;
    protected $auditService;

    public function __construct()
    {
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

    public function index()
    {
        $roles = $this->permissionService->getAllRoles();
        $allPermissions = PermissionService::getAvailablePermissions();

        $this->view('admin/roles/index', [
            'roles' => $roles,
            'allPermissions' => $allPermissions
        ]);
    }

    /**
     * Show edit form
     */
    public function edit()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) $this->redirect(url('/admin/roles'));

        $role = $this->permissionService->getRole($id);
        if (!$role) $this->redirect(url('/admin/roles'));

        $rolePermissions = $this->permissionService->getRolePermissions($id);
        $allPermissions = PermissionService::getAvailablePermissions();

        $this->view('admin/roles/edit', [
            'role' => $role,
            'rolePermissions' => $rolePermissions,
            'allPermissions' => $allPermissions
        ]);
    }

    /**
     * Update role data
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(url('/admin/roles'));
        }

        if (!$this->permissionService->can('role.edit')) {
            die('Không có quyền chỉnh sửa.');
        }

        $id = $_POST['id'] ?? null;
        $displayName = $_POST['display_name'] ?? '';
        $permissions = $_POST['permissions'] ?? [];

        if (!$id || empty($displayName)) {
            $this->redirect(url('/admin/roles/edit?id=' . $id . '&error=' . urlencode('Tên vai trò không được để trống')));
        }

        $oldRole = $this->permissionService->getRole($id);
        if ($this->permissionService->updateRole($id, $displayName, $permissions)) {
            // Log action
            $this->auditService->log('UPDATE_ROLE', 'roles', $id, [
                'display_name' => $oldRole['display_name'],
                'permissions' => json_decode($oldRole['permissions'], true)
            ], [
                'display_name' => $displayName,
                'permissions' => $permissions
            ]);

            $this->redirect(url('/admin/roles?msg=updated'));
        } else {
            $this->redirect(url('/admin/roles/edit?id=' . $id . '&error=' . urlencode('Lỗi cập nhật vai trò')));
        }
    }
}
