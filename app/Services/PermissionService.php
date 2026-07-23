<?php
namespace App\Services;

use App\Core\Database;

class PermissionService {
    protected $db;
    protected $userPermissions = null;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Centralized Permission Check (User + Role)
     */
    public function checkUserPermission($user, $permissionKey) {
        // 1. Super Admin always true
        if ($user['id'] == 1) return true;

        // 2. Check Direct Permissions
        $directPerms = json_decode($user['permissions'] ?? '[]', true);
        if (is_array($directPerms) && (in_array('all', $directPerms) || in_array($permissionKey, $directPerms))) {
            return true;
        }

        // 3. Check Role Permissions
        if (!empty($user['role_id'])) {
            $rolePerms = $this->getRolePermissions($user['role_id']);
            if (in_array('all', $rolePerms) || in_array($permissionKey, $rolePerms)) {
                return true;
            }
        }

        return false;
    }

    public function getRolePermissions($roleId) {
        // Session-level cache: avoids repeated DB round-trip to Supabase on every page load
        $sessionKey = '_role_perms_' . (int)$roleId;
        if (isset($_SESSION[$sessionKey])) {
            return $_SESSION[$sessionKey];
        }

        $stmt = $this->db->prepare("SELECT permissions FROM roles WHERE id = ?");
        $stmt->execute([$roleId]);
        $role = $stmt->fetch(\PDO::FETCH_ASSOC);
        $perms = $role ? (json_decode($role['permissions'], true) ?? []) : [];

        // Store in session for the duration of this login session
        $_SESSION[$sessionKey] = $perms;
        return $perms;
    }

    /**
     * Load permissions for current user
     */
    public function loadUserPermissions() {
        if ($this->userPermissions !== null) {
            return $this->userPermissions;
        }

        $adminId = $_SESSION['admin_id'] ?? null;
        if (!$adminId) {
            $this->userPermissions = [];
            return [];
        }

        // Super Admin ID 1 always has 'all' permission
        if ($adminId == 1) {
            $this->userPermissions = ['all'];
            return $this->userPermissions;
        }

        $sql = "SELECT r.permissions FROM quan_tri_vien q 
                LEFT JOIN roles r ON q.role_id = r.id 
                WHERE q.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$adminId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->userPermissions = $result ? (json_decode($result['permissions'], true) ?? []) : [];
        return $this->userPermissions;
    }

    /**
     * Check if current user has permission
     */
    public function can($permission) {
        $permissions = $this->loadUserPermissions();
        return in_array('all', $permissions) || in_array($permission, $permissions);
    }

    /**
     * Check multiple permissions (OR)
     */
    public function canAny(array $permissions) {
        foreach ($permissions as $p) {
            if ($this->can($p)) return true;
        }
        return false;
    }

    /**
     * Check multiple permissions (AND)
     */
    public function canAll(array $permissions) {
        foreach ($permissions as $p) {
            if (!$this->can($p)) return false;
        }
        return true;
    }

    /**
     * Get all roles
     */
    public function getAllRoles() {
        $stmt = $this->db->query("SELECT * FROM roles ORDER BY id");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get role by ID
     */
    public function getRole($id) {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Update role permissions
     */
    public function updateRole($id, $displayName, $permissions) {
        $sessionKey = '_role_perms_' . (int)$id;
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[$sessionKey])) {
            unset($_SESSION[$sessionKey]);
        }
        $sql = "UPDATE roles SET display_name = ?, permissions = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$displayName, json_encode($permissions), $id]);
    }

    /**
     * Get all available permissions
     */
    public static function getAvailablePermissions() {
        return [
            'Tổng quan' => [
                'dashboard'       => 'Xem Dashboard',
                'stats'           => 'Xem Báo cáo Thống kê',
            ],
            'Lọc ảo & Nhập học' => [
                'admission.view'  => 'Xem Tổng quan Lọc ảo & Thống kê Nhập học',
                'admission.edit'  => 'Thao tác Xét tuyển Lọc ảo & Nhập học',
            ],
            'Thí sinh' => [
                'candidate.view'  => 'Xem danh sách thí sinh',
                'candidate.edit'  => 'Chỉnh sửa thông tin thí sinh',
                'candidate.delete'=> 'Xóa thí sinh',
                'candidate.bulk'  => 'Thao tác hàng loạt',
            ],
            'Báo cáo' => [
                'report.view'     => 'Xem báo cáo',
                'report.export'   => 'Xuất báo cáo',
            ],
            'Điểm Năng khiếu' => [
                'aptitude.view'   => 'Xem/Nhập điểm năng khiếu',
            ],
            'Ngành đào tạo' => [
                'major.view'      => 'Xem danh mục ngành',
                'major.edit'      => 'Chỉnh sửa danh mục',
                'major.delete'    => 'Xóa danh mục',
            ],
            'Bài viết' => [
                'posts.view'      => 'Xem danh sách bài viết',
                'posts.edit'      => 'Chỉnh sửa bài viết',
                'posts.delete'    => 'Xóa bài viết',
                'posts.category'  => 'Quản lý chuyên mục',
            ],
            'Cài đặt' => [
                'settings.view'   => 'Xem cài đặt hệ thống',
                'settings.edit'   => 'Sửa cài đặt hệ thống',
            ],
            'Nhật ký' => [
                'audit.view'      => 'Xem nhật ký hoạt động',
            ],
            'Vai trò' => [
                'role.view'       => 'Xem vai trò',
                'role.edit'       => 'Chỉnh sửa vai trò',
            ],
        ];
    }
}
