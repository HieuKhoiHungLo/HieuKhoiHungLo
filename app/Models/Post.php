<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class Post extends Model {
    protected $table = 'posts';

    public function getLatest($limit = 5, $category = null) {
        $sql = "SELECT p.* FROM {$this->table} p 
                JOIN post_categories c ON p.category = c.name 
                WHERE p.status = 'Published' AND c.is_active = true";
        $params = [];
        
        if ($category) {
            $sql .= " AND p.category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY p.is_featured DESC, p.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaginated($limit = 6, $offset = 0) {
        $sql = "SELECT p.* FROM {$this->table} p 
                JOIN post_categories c ON p.category = c.name 
                WHERE p.status = 'Published' AND c.is_active = true 
                ORDER BY p.is_featured DESC, p.created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaginatedByCategory($category, $limit = 6, $offset = 0) {
        $sql = "SELECT p.* FROM {$this->table} p 
                JOIN post_categories c ON p.category = c.name 
                WHERE p.status = 'Published' AND c.is_active = true AND p.category = ?
                ORDER BY p.is_featured DESC, p.created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $category, PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByCategory($category) {
        $sql = "SELECT COUNT(p.*) FROM {$this->table} p 
                JOIN post_categories c ON p.category = c.name 
                WHERE p.status = 'Published' AND c.is_active = true AND p.category = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$category]);
        return $stmt->fetchColumn();
    }

    public function countPublished() {
        $sql = "SELECT COUNT(p.*) FROM {$this->table} p 
                JOIN post_categories c ON p.category = c.name 
                WHERE p.status = 'Published' AND c.is_active = true";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function findBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = ? AND status = 'Published'");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function incrementView($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAllAdmin() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAllAdmin($search = '') {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];
        if ($search) {
            $sql .= " WHERE title ILIKE ?";
            $params[] = "%{$search}%";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getAllAdminPaginated($limit, $offset, $search = '') {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        if ($search) {
            $sql .= " WHERE title ILIKE ?";
            $params[] = "%{$search}%";
        }
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $i = 1;
        if ($search) {
            $stmt->bindValue($i++, "%{$search}%", PDO::PARAM_STR);
        }
        $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($i, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        
        $i = 1;
        foreach ($data as $value) {
            $type = is_int($value) ? PDO::PARAM_INT : (is_bool($value) ? PDO::PARAM_BOOL : PDO::PARAM_STR);
            $stmt->bindValue($i++, $value, $type);
        }
        
        return $stmt->execute();
    }

    public function update($id, $data) {
        unset($data['updated_at']);
        $fields = array_keys($data);
        $setClause = implode(', ', array_map(fn($f) => "{$f} = ?", $fields));
        $sql = "UPDATE {$this->table} SET {$setClause}, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        $i = 1;
        foreach ($data as $value) {
            $type = is_int($value) ? PDO::PARAM_INT : (is_bool($value) ? PDO::PARAM_BOOL : PDO::PARAM_STR);
            $stmt->bindValue($i++, $value, $type);
        }
        
        $stmt->bindValue($i, $id, is_int($id) ? PDO::PARAM_INT : PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
