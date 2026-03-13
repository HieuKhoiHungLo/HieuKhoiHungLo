<?php

namespace App\Repositories;

use App\Models\OnlineTracking;

class OnlineTrackingRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new OnlineTracking();
    }

    public function trackActivity($sessionId, $userId = null, $adminId = null, $ip = null, $userAgent = null)
    {
        // Periodic cleanup (1 in 50 requests)
        if (mt_rand(1, 50) === 1) {
            $this->model->cleanOldSessions(30);
        }

        return $this->model->upsertActivity($sessionId, $userId, $adminId, $ip, $userAgent);
    }

    public function getOnlineStats($minutes = 15)
    {
        $stats = $this->model->getOnlineCounts($minutes);
        
        // Ensure values are integers and handle nulls
        return [
            'total' => (int)($stats['total'] ?? 0),
            'users' => (int)($stats['logged_in_users'] ?? 0),
            'admins' => (int)($stats['logged_in_admins'] ?? 0),
            'guests' => (int)($stats['guests'] ?? 0)
        ];
    }
}
