<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Notification {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new notification
     */
    public function create(array $data): ?int {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (title, content, type, target_type, target_id, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
            RETURNING id
        ");
        
        $stmt->execute([
            $data['title'],
            $data['content'],
            $data['type'] ?? 'info',
            $data['target_type'] ?? 'all',
            $data['target_id'] ?? null,
            $data['created_by'] ?? null
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['id'] : null;
    }

    /**
     * Get all notifications (for admin)
     */
    public function getAll(int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare("
            SELECT n.*, q.ho_ten as admin_name
            FROM notifications n
            LEFT JOIN quan_tri_vien q ON n.created_by = q.id
            WHERE n.title NOT LIKE '[Email]%'
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get notifications for a specific user
     */
    public function getForUser(string $cccd, bool $onlyUnread = false): array {
        $unreadFilter = $onlyUnread ? " AND nr.id IS NULL " : "";
        
        $sql = "
            SELECT n.*, 
                   CASE WHEN nr.id IS NOT NULL THEN true ELSE false END as is_read,
                   nr.read_at
            FROM notifications n
            LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_cccd = ?
            WHERE n.title NOT LIKE '[Email]%'
              AND ((n.target_type = 'all')
                OR (n.target_type = 'individual' AND n.target_id = ?)
                OR (n.target_type = 'session' AND CAST(n.target_id AS varchar) IN (
                    SELECT CAST(dot_tuyen_sinh_id AS varchar) FROM ho_so_xet_tuyen WHERE so_cccd = ?
                )))
            {$unreadFilter}
            ORDER BY n.created_at DESC
            LIMIT 50
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cccd, $cccd, $cccd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count unread notifications for a user
     */
    public function countUnread(string $cccd, ?int $sessionId = null): int {
        $sql = "
            SELECT COUNT(n.id)
            FROM notifications n
            LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_cccd = ?
            WHERE nr.id IS NULL
              AND n.title NOT LIKE '[Email]%'
              AND ((n.target_type = 'all')
               OR (n.target_type = 'individual' AND n.target_id = ?)
               OR (n.target_type = 'session' AND CAST(n.target_id AS varchar) IN (
                   SELECT CAST(dot_tuyen_sinh_id AS varchar) FROM ho_so_xet_tuyen WHERE so_cccd = ?
               )))
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cccd, $cccd, $cccd]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, string $cccd): bool {
        $stmt = $this->db->prepare("
            INSERT INTO notification_reads (notification_id, user_cccd)
            VALUES (?, ?)
            ON CONFLICT (notification_id, user_cccd) DO NOTHING
        ");
        return $stmt->execute([$notificationId, $cccd]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(string $cccd, ?int $sessionId = null): bool {
        $sql = "
            INSERT INTO notification_reads (notification_id, user_cccd)
            SELECT n.id, ?
            FROM notifications n
            LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_cccd = ?
            WHERE nr.id IS NULL
              AND n.title NOT LIKE '[Email]%'
              AND ((n.target_type = 'all')
               OR (n.target_type = 'individual' AND n.target_id = ?)
               OR (n.target_type = 'session' AND CAST(n.target_id AS varchar) IN (
                   SELECT CAST(dot_tuyen_sinh_id AS varchar) FROM ho_so_xet_tuyen WHERE so_cccd = ?
               )))
            ON CONFLICT DO NOTHING
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$cccd, $cccd, $cccd, $cccd]);
    }

    /**
     * Delete a notification
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get single notification by ID
     */
    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
