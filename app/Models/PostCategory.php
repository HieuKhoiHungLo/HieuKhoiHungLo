<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class PostCategory extends Model {
    protected $table = 'post_categories';

    public function getAllActive() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE is_active = true ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (name, slug, is_active, sort_order) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['slug'],
            isset($data['is_active']) ? ($data['is_active'] ? 'true' : 'false') : 'true',
            $data['sort_order'] ?? 0
        ]);
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET name = ?, slug = ?, is_active = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['is_active'] ? 'true' : 'false',
            $data['sort_order'],
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
