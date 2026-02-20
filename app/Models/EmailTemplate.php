<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class EmailTemplate {
    private $db;
    protected $table = 'email_templates';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (code, subject, content, description) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $data['code'],
            $data['subject'],
            $data['content'],
            $data['description'] ?? ''
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET code = ?, subject = ?, content = ?, description = ? WHERE id = ?");
        return $stmt->execute([
            $data['code'],
            $data['subject'],
            $data['content'],
            $data['description'] ?? '',
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
