<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

class Menu extends Model
{
    protected $table = 'menus';
    protected $fillable = [
        'parent_id', 'title', 'url', 'position', 'visibility', 
        'permission_required', 'icon', 'css_class', 'order_index', 'is_active'
    ];

    /**
     * Get active menus by position, ordered by order_index
     */
    public function getActiveMenus($position)
    {
        $cacheKey = "menus_active_{$position}";
        return Cache::remember($cacheKey, 3600, function() use ($position) {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE position = ? AND is_active = true ORDER BY order_index ASC");
            $stmt->execute([$position]);
            $allMenus = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Build tree
            return $this->buildTree($allMenus);
        });
    }

    /**
     * Get all menus (for admin)
     */
    public function getAllMenus($position = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        if ($position) {
            $sql .= " WHERE position = ?";
            $params[] = $position;
        }
        $sql .= " ORDER BY position ASC, order_index ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $allMenus = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $this->buildTree($allMenus);
    }
    
    /**
     * Clear menu cache
     */
    public function clearCache()
    {
        Cache::forget("menus_active_header_public");
        Cache::forget("menus_active_admin_sidebar");
        Cache::forget("menus_active_footer");
        Cache::forget("menus_active_footer_quick");
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function update($id, $data)
    {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($values);
        $this->clearCache();
        return $result;
    }

    public function create($data)
    {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        
        $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(array_values($data));
        $this->clearCache();
        return $result;
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $result = $stmt->execute([$id]);
        $this->clearCache();
        return $result;
    }

    /**
     * Helper to build a tree structure from flat rows
     */
    protected function buildTree(array $elements, $parentId = null) {
        $branch = array();
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                } else {
                    $element['children'] = [];
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
}
