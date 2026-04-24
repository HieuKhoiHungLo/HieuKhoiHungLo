<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MasterData;

class ApiController extends Controller
{

    protected $masterData;

    public function __construct()
    {
        \App\Core\Database::getInstance()->setSystemRole('admin');
        $this->masterData = new MasterData();
    }

    public function getWards()
    {
        $provinceId = $_GET['province_id'] ?? '';
        if (!$provinceId) {
            echo json_encode([]);
            return;
        }
        $wards = $this->masterData->getWards($provinceId);
        header('Content-Type: application/json');
        echo json_encode($wards);
    }

    public function getSchools()
    {
        $provinceId = $_GET['province_id'] ?? '';
        if (!$provinceId) {
            echo json_encode([]);
            return;
        }
        $schools = $this->masterData->getSchools($provinceId);
        header('Content-Type: application/json');
        echo json_encode($schools);
    }

    public function getSchoolDetails()
    {
        $schoolId = $_GET['school_id'] ?? '';
        if (!$schoolId) {
            echo json_encode(null);
            return;
        }
        $school = $this->masterData->findSchool($schoolId);
        header('Content-Type: application/json');
        echo json_encode($school);
    }

    /**
     * API Process Email Queue
     * Relocated from cron/process_email_queue.php
     * Can be triggered by Web Admin Layout or System Crontab
     */
    public function processEmailQueue()
    {
        // Simple security for web access
        $key = $_GET['key'] ?? '';
        if ($key !== 'hvu_cron_2024') {
            header("HTTP/1.1 403 Forbidden");
            $this->json(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        $mailer = new \App\Services\MailerService();

        // --- AUTOMATED AUDIT PURGE (Throttle: Once per day) ---
        $today = date('Y-m-d');
        if ($this->masterData->getSetting('last_audit_purge') !== $today) {
            (new \App\Services\AuditService())->purgeOldRecords(20);
            $this->masterData->setSetting('last_audit_purge', $today);
        }

        // Fetch pending emails (limit 10 per run to avoid timeout)
        $stmt = $db->prepare("SELECT * FROM email_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 10");
        $stmt->execute();
        $emails = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $processed = 0;
        $failed = 0;

        foreach ($emails as $email) {
            $id = $email['id'];

            // Mark as processing
            $db->prepare("UPDATE email_queue SET status = 'processing' WHERE id = ?")->execute([$id]);

            $result = $mailer->send($email['recipient'], $email['subject'], $email['body'], true, $email['category'] ?? 'system');

            if ($result === true) {
                $db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$id]);
                $processed++;
            } else {
                // Increment retry or mark failed
                $attempts = ($email['attempts'] ?? 0) + 1;
                $maxAttempts = 3;

                if ($attempts >= $maxAttempts) {
                    $db->prepare("UPDATE email_queue SET status = 'failed', error = ?, attempts = ? WHERE id = ?")->execute([(string)$result, $attempts, $id]);
                    $failed++;
                } else {
                    $db->prepare("UPDATE email_queue SET status = 'pending', error = ?, attempts = ? WHERE id = ?")->execute([(string)$result, $attempts, $id]);
                }
            }
        }

        $msg = date('Y-m-d H:i:s') . " - Processed: $processed, Failed: $failed, Total: " . count($emails);

        $this->json(['success' => true, 'message' => $msg]);
    }
}
