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
        
        // Release session lock early for read-only API requests to prevent blocking concurrent fetch calls
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
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

        // --- AUTOMATED AUDIT PURGE & EMAIL QUEUE PURGE (Throttle: Once per day) ---
        $today = date('Y-m-d');
        if ($this->masterData->getSetting('last_audit_purge') !== $today) {
            (new \App\Services\AuditService())->purgeOldRecords(20);
            
            // Purge sent emails older than configured retention days
            try {
                $retentionDays = (int)($this->masterData->getSetting('email_retention_days') ?: 10);
                
                // Count sent emails older than retentionDays
                $countStmt = $db->prepare("SELECT COUNT(*) FROM email_queue WHERE status = 'sent' AND sent_at < NOW() - ? * INTERVAL '1 day'");
                $countStmt->execute([$retentionDays]);
                $count = (int)$countStmt->fetchColumn();
                
                if ($count > 0) {
                    $delStmt = $db->prepare("DELETE FROM email_queue WHERE status = 'sent' AND sent_at < NOW() - ? * INTERVAL '1 day'");
                    $delStmt->execute([$retentionDays]);
                    
                    // Increment cleared_sent_total in stats table
                    $stmtStats = $db->prepare("SELECT value FROM email_queue_stats WHERE key = 'cleared_sent_total'");
                    $stmtStats->execute();
                    $exists = $stmtStats->fetchColumn() !== false;
                    
                    if ($exists) {
                        $db->prepare("UPDATE email_queue_stats SET value = value + ? WHERE key = 'cleared_sent_total'")->execute([$count]);
                    } else {
                        $db->prepare("INSERT INTO email_queue_stats (key, value) VALUES ('cleared_sent_total', ?)")->execute([$count]);
                    }
                    
                    // Clear summary stats cache
                    \App\Core\Cache::forget('email_queue_summary_stats');
                }
            } catch (\Exception $ex) {
                error_log("Failed to auto purge old email queue: " . $ex->getMessage());
            }
            
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

        // Count remaining pending emails
        $remaining = (int)$db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'")->fetchColumn();

        $msg = date('Y-m-d H:i:s') . " - Processed: $processed, Failed: $failed, Total: " . count($emails);
        $this->json(['success' => true, 'message' => $msg, 'remaining' => $remaining]);
    }

    /**
     * API Scheduled Backup
     * Triggered by Windows Task Scheduler, cron-job.org, or Uptime Robot
     */
    public function scheduledBackup()
    {
        // Security: verify cron key
        $key = $_GET['key'] ?? '';
        $expectedKey = $_ENV['CRON_SECRET_KEY'] ?? '';
        if (empty($expectedKey) || !hash_equals($expectedKey, $key)) {
            header("HTTP/1.1 403 Forbidden");
            $this->json(['success' => false, 'error' => 'Forbidden']);
            return;
        }

        // Check if backup is enabled
        if ($this->masterData->getSetting('backup_enabled') !== '1') {
            $this->json(['success' => true, 'message' => 'Sao lưu tự động chưa được bật.']);
            return;
        }

        // Check if current time is >= configured backup time (allow bypass via force=1)
        $backupHour = (int)($this->masterData->getSetting('backup_hour') ?? 1);
        $backupMinute = (int)($this->masterData->getSetting('backup_minute') ?? 0);
        
        $currentHour = (int)date('G');
        $currentMinute = (int)date('i');
        
        $currentTimeVal = $currentHour * 60 + $currentMinute;
        $backupTimeVal = $backupHour * 60 + $backupMinute;

        // Construct the trigger key for the currently configured schedule on today's date (e.g. "2026-05-17 23:20")
        $scheduledTimeKey = date('Y-m-d') . ' ' . sprintf('%02d:%02d', $backupHour, $backupMinute);
        $lastScheduledTrigger = $this->masterData->getSetting('backup_last_scheduled_trigger') ?? '';
        $force = isset($_GET['force']) && $_GET['force'] == '1';

        // Check if this specific scheduled time has already been successfully triggered today
        if (!$force && $lastScheduledTrigger === $scheduledTimeKey) {
            $this->json(['success' => true, 'message' => "Bản sao lưu tự động cho thời gian {$scheduledTimeKey} đã được chạy trước đó. Bỏ qua."]);
            return;
        }

        // Check if current time has reached or passed the scheduled time
        if (!$force && $currentTimeVal < $backupTimeVal) {
            $formattedTime = sprintf('%02d:%02d', $backupHour, $backupMinute);
            $formattedCurrent = sprintf('%02d:%02d', $currentHour, $currentMinute);
            $this->json(['success' => true, 'message' => "Chưa đến giờ sao lưu (cấu hình: {$formattedTime}, hiện tại: {$formattedCurrent})."]);
            return;
        }

        // --- CONCURRENCY LOCK: Prevent multiple simultaneous backup processes ---
        // When multiple admin pages load at the same time (e.g., 11:15), each page
        // fires a fetch() to this endpoint. Without locking, all requests pass the
        // dedup check before any of them writes the "completed" flag, causing
        // multiple pg_dump processes to run simultaneously and exhaust connections.
        $lockFile = __DIR__ . '/../../storage/backup_cron.lock';
        if (!file_exists(dirname($lockFile))) mkdir(dirname($lockFile), 0777, true);
        $fp = fopen($lockFile, 'w+');
        if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
            // Another backup process is already running — skip immediately
            if ($fp) fclose($fp);
            $this->json(['success' => true, 'message' => 'Một tiến trình sao lưu khác đang chạy. Bỏ qua.']);
            return;
        }

        // Pre-mark this trigger as completed IMMEDIATELY (inside the lock)
        // to prevent any subsequent requests that arrive after lock release
        $this->masterData->setSetting('backup_last_scheduled_trigger', $scheduledTimeKey);

        // Run backup
        try {
            ignore_user_abort(true);
            set_time_limit(0);

            $service = new \App\Services\BackupService();
            $result = $service->run(false);

            $this->masterData->setSetting('backup_last_run', date('Y-m-d H:i:s'));
            $this->masterData->setSetting('backup_last_status', 'success');
            $this->masterData->setSetting('backup_last_file', $result['file'] ?? '');
            $this->masterData->setSetting('backup_last_log', json_encode($result['log'] ?? []));

            // Release lock
            flock($fp, LOCK_UN);
            fclose($fp);

            $this->json(['success' => true, 'message' => 'Sao lưu tự động thành công: ' . ($result['file'] ?? ''), 'log' => $result['log'] ?? []]);
        } catch (\Exception $e) {
            $this->masterData->setSetting('backup_last_run', date('Y-m-d H:i:s'));
            $this->masterData->setSetting('backup_last_status', 'failed: ' . $e->getMessage());

            // Release lock
            flock($fp, LOCK_UN);
            fclose($fp);

            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
