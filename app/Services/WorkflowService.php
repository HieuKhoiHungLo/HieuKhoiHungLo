<?php

namespace App\Services;

use App\Core\Database;

class WorkflowService
{
    protected $db;

    // Application statuses in order
    const STATUS_DRAFT = 'Nháp';
    const STATUS_SUBMITTED = 'Đã nộp';
    const STATUS_VERIFYING = 'Đang xác minh';
    const STATUS_VERIFIED = 'Đã xác minh';
    const STATUS_SCORING = 'Đang tính điểm';
    const STATUS_QUALIFIED = 'Đủ điều kiện';
    const STATUS_ADMITTED = 'Trúng tuyển';
    const STATUS_REJECTED = 'Không trúng tuyển';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_VERIFYING,
        self::STATUS_VERIFIED,
        self::STATUS_SCORING,
        self::STATUS_QUALIFIED,
        self::STATUS_ADMITTED,
        self::STATUS_REJECTED
    ];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all statuses with their order
     */
    public function getStatuses()
    {
        return self::STATUSES;
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor($status)
    {
        return match ($status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_SUBMITTED => 'blue',
            self::STATUS_VERIFYING => 'yellow',
            self::STATUS_VERIFIED => 'indigo',
            self::STATUS_SCORING => 'emerald',
            self::STATUS_QUALIFIED => 'cyan',
            self::STATUS_ADMITTED => 'green',
            self::STATUS_REJECTED => 'red',
            default => 'gray'
        };
    }

    /**
     * Transition application to next status
     */
    public function transition($soCccd, $newStatus, $adminId = null)
    {
        if (!in_array($newStatus, self::STATUSES)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE ho_so_xet_tuyen SET trang_thai = ?, updated_at = NOW() WHERE so_cccd = ?");
        $result = $stmt->execute([$newStatus, $soCccd]);

        // Log transition
        if ($result && $adminId) {
            $this->logTransition($soCccd, $newStatus, $adminId);
        }

        return $result;
    }

    /**
     * Batch transition for multiple applications
     */
    public function batchTransition($soCccdList, $newStatus, $adminId = null)
    {
        $count = 0;
        foreach ($soCccdList as $soCccd) {
            if ($this->transition($soCccd, $newStatus, $adminId)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get allowed next statuses from current status
     */
    public function getAllowedTransitions($currentStatus)
    {
        $allowed = [];

        switch ($currentStatus) {
            case self::STATUS_DRAFT:
                $allowed = [self::STATUS_SUBMITTED];
                break;
            case self::STATUS_SUBMITTED:
                $allowed = [self::STATUS_VERIFYING, self::STATUS_REJECTED];
                break;
            case self::STATUS_VERIFYING:
                $allowed = [self::STATUS_VERIFIED, self::STATUS_SUBMITTED];
                break;
            case self::STATUS_VERIFIED:
                $allowed = [self::STATUS_SCORING];
                break;
            case self::STATUS_SCORING:
                $allowed = [self::STATUS_QUALIFIED, self::STATUS_REJECTED];
                break;
            case self::STATUS_QUALIFIED:
                $allowed = [self::STATUS_ADMITTED, self::STATUS_REJECTED];
                break;
        }

        return $allowed;
    }

    /**
     * Get statistics by status
     */
    public function getStatusStats()
    {
        $stmt = $this->db->query("SELECT trang_thai, COUNT(*) as count FROM ho_so_xet_tuyen GROUP BY trang_thai");
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * Log status transition
     */
    protected function logTransition($soCccd, $newStatus, $adminId)
    {
        $sql = "INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address) VALUES (?, 'TRANSITION', 'ho_so', ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$adminId, $soCccd, json_encode(['status' => $newStatus]), $_SERVER['REMOTE_ADDR'] ?? '']);
    }
}
