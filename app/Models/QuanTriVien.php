<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class QuanTriVien extends Model {
    protected $table = 'quan_tri_vien';

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (ten_dang_nhap, mat_khau, ho_ten, permissions, is_active, avatar, role_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['ten_dang_nhap'],
            $data['mat_khau'],
            $data['ho_ten'],
            $data['permissions'], // JSON string
            $data['is_active'] ?? true,
            $data['avatar'] ?? null,
            $data['role_id'] ?? null
        ]);
    }

    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        if (isset($data['mat_khau'])) {
            $fields[] = "mat_khau = ?";
            $params[] = $data['mat_khau'];
        }
        if (isset($data['ho_ten'])) {
            $fields[] = "ho_ten = ?";
            $params[] = $data['ho_ten'];
        }
        if (isset($data['permissions'])) {
            $fields[] = "permissions = ?";
            $params[] = $data['permissions'];
        }
        if (isset($data['is_active'])) {
            $fields[] = "is_active = ?";
            $params[] = $data['is_active'];
        }
        if (isset($data['avatar'])) {
            $fields[] = "avatar = ?";
            $params[] = $data['avatar'];
        }
        if (isset($data['role_id'])) {
            $fields[] = "role_id = ?";
            $params[] = $data['role_id'];
        }
        if (isset($data['remember_token'])) {
            $fields[] = "remember_token = ?";
            $params[] = $data['remember_token'];
        }

        if (empty($fields)) return true;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function timTheoTenDangNhap($ten_dang_nhap) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE ten_dang_nhap = ?");
        $stmt->execute([$ten_dang_nhap]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Helper to check permission on a User Array
    public static function hasPermission($user, $permissionKey) {
        // Delegate to PermissionService for consistent RBAC logic
        $service = new \App\Services\PermissionService();
        return $service->checkUserPermission($user, $permissionKey);
    }
}
