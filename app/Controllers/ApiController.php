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
        // Security: verify cron key from .env (timing-safe comparison)
        $key = $_GET['key'] ?? '';
        $expectedKey = $_ENV['CRON_SECRET_KEY'] ?? '';
        if (empty($expectedKey) || !hash_equals($expectedKey, $key)) {
            header("HTTP/1.1 403 Forbidden");
            $this->json(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        // Check if queue is paused
        if ($this->masterData->getSetting('email_queue_paused') === '1') {
            $this->json(['success' => true, 'message' => 'Hàng đợi đang tạm dừng.']);
            return;
        }

        // --- CONCURRENCY LOCKING ---
        // Use a file-based lock to prevent overlapping runs from multiple admin page loads.
        // This is crucial to avoid "Too many login attempts" from SMTP providers like Google.
        $lockFile = __DIR__ . '/../../storage/email_queue.lock';
        if (!file_exists(dirname($lockFile))) mkdir(dirname($lockFile), 0777, true);
        $fp = fopen($lockFile, 'w+');
        if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
            $this->json(['success' => true, 'message' => 'Một tiến trình khác đang chạy. Bỏ qua.']);
            if ($fp) fclose($fp);
            return;
        }

        // Release session lock immediately. 
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        $mailer = new \App\Services\MailerService();

        // --- STUCK JOBS CLEANUP ---
        // If emails are in 'processing' for more than 15 minutes, they likely crashed.
        // Reset them to 'pending' so they can be retried.
        $db->query("UPDATE email_queue SET status = 'pending' WHERE status = 'processing' AND (sent_at IS NULL OR sent_at < NOW() - INTERVAL '15 minutes')");

        // --- AUTOMATED AUDIT PURGE (Throttle: Once per day) ---
        $today = date('Y-m-d');
        if ($this->masterData->getSetting('last_audit_purge') !== $today) {
            (new \App\Services\AuditService())->purgeOldRecords(20);
            $this->masterData->setSetting('last_audit_purge', $today);
        }

        // --- ATOMIC QUEUE FETCH ---
        // Use "FOR UPDATE SKIP LOCKED" (Postgres) to atomically pick jobs.
        // This ensures no two processes ever try to send the same email.
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                UPDATE email_queue 
                SET status = 'processing' 
                WHERE id IN (
                    SELECT id FROM email_queue 
                    WHERE status = 'pending' 
                    ORDER BY created_at ASC 
                    LIMIT 20 
                    FOR UPDATE SKIP LOCKED
                )
                RETURNING *
            ");
            $stmt->execute();
            $emails = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            flock($fp, LOCK_UN);
            fclose($fp);
            $this->json(['success' => false, 'error' => $e->getMessage()]);
            return;
        }

        $processed = 0;
        $failed = 0;

        foreach ($emails as $email) {
            $id = $email['id'];

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

            // Throttling: Add 1s delay between emails to avoid being flagged by Gmail/SMTP
            sleep(1);
        }

        // Release lock
        flock($fp, LOCK_UN);
        fclose($fp);

        $msg = date('Y-m-d H:i:s') . " - Processed: $processed, Failed: $failed, Total: " . count($emails);
        $this->json(['success' => true, 'message' => $msg]);
    }
}
