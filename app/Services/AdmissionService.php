<?php

namespace App\Services;

use App\Core\Database;
use App\Services\ScoreCalculationService;
use PDO;

class AdmissionService
{
    protected $db;
    protected $scoreService;

    protected $thiSinhRepo;
    protected $nguyenVongRepo;
    protected $emailService;
    protected $auditService;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->scoreService = new ScoreCalculationService();
        $this->thiSinhRepo = new \App\Repositories\ThiSinhRepository();
        $this->nguyenVongRepo = new \App\Repositories\NguyenVongRepository();
        $this->emailService = new \App\Services\EmailTemplateService();
        $this->auditService = new \App\Services\AuditService();
    }

    /**
     * Process a candidate review from admin
     */
    public function processReview($cccd, $reviewData, $candidateInfo = null, $reviewerId = null)
    {
        $isRejected = false;
        $collectedNotes = [];
        $reviewResults = [];

        $sectionsToCheck = [
            'personal' => 'Thông tin cá nhân',
            'academic' => 'Học bạ THPT',
            'certs' => 'Chứng chỉ quốc tế',
            'thpt' => 'Điểm thi THPT',
            'wishes' => 'Nguyện vọng'
        ];

        foreach ($sectionsToCheck as $secKey => $secName) {
            $status = $reviewData['status_' . $secKey] ?? 'approved';
            $note = $reviewData['note_' . $secKey] ?? '';

            if ($status === 'rejected') {
                $isRejected = true;
            }

            $reviewResults[] = [
                'name' => $secName,
                'status' => $status === 'approved' ? 'ok' : ($status === 'rejected' ? 'missing' : 'warning'),
                'note' => $note
            ];
        }

        // Use explicit master status from UI or fallback to auto-calculated
        $finalStatus = $reviewData['master_status'] ?? ($isRejected ? \App\Core\UserStatus::REJECTED : \App\Core\UserStatus::APPROVED);
        $dbNote = $reviewData['master_note'] ?? '';

        // 2. Update Database
        $this->thiSinhRepo->updateApplicationStatus($cccd, $finalStatus, $dbNote, $reviewerId);

        // 3. Queue Notification Email
        $email = $candidateInfo['email'] ?? null;
        $hoTen = $candidateInfo['ho_va_ten'] ?? null;
        if (!$email && !$candidateInfo) {
            $candidate = $this->thiSinhRepo->findByCCCD($cccd);
            $email = $candidate['email'] ?? null;
            $hoTen = $candidate['ho_va_ten'] ?? null;
        }
        if (!empty($email)) {
            $resultHtml = $this->emailService->buildReviewResultHtml($reviewResults);
            $generalNote = '';
            if ($finalStatus === \App\Core\UserStatus::REJECTED) {
                $generalNote = 'Hồ sơ của bạn không đủ điều kiện trúng tuyển theo quy định.';
            } elseif ($finalStatus === \App\Core\UserStatus::REQUIRE_EDIT || $isRejected) {
                $generalNote = 'Hồ sơ của bạn có nội dung cần chỉnh sửa/bổ sung. Vui lòng xem chi tiết bên dưới và cập nhật lại sớm nhất.';
            } else {
                $generalNote = 'Hồ sơ đã được tiếp nhận và kiểm tra hợp lệ.';
            }

            $this->emailService->queueWithTemplate($email, 'application_reviewed', [
                'ho_ten' => $hoTen,
                'ket_qua_chi_tiet' => $resultHtml,
                'ghi_chu' => $generalNote
            ]);
        }

        // 4. Audit Log
        $action = 'REVIEW_PROCESSED';
        if ($finalStatus === \App\Core\UserStatus::REJECTED) $action = 'REVIEW_REJECTED';
        elseif ($finalStatus === \App\Core\UserStatus::APPROVED) $action = 'REVIEW_APPROVED';
        elseif ($finalStatus === \App\Core\UserStatus::REQUIRE_EDIT) $action = 'REVIEW_REQUIRE_EDIT';
        
        $this->auditService->log($action, 'candidate', $cccd, null, ['status' => $finalStatus]);

        // 5. Find next candidate
        $nextCccd = $this->thiSinhRepo->getNextPendingCandidate($cccd);

        return [
            'success' => true,
            'is_rejected' => $isRejected,
            'final_status' => $finalStatus,
            'next_cccd' => $nextCccd
        ];
    }


    public function calculateBatchScores($batchId)
    {
        // Get unique CCCDs in this batch
        $stmt = $this->db->prepare("SELECT DISTINCT so_cccd FROM nguyen_vong WHERE dot_tuyen_sinh_id = ?");
        $stmt->execute([$batchId]);
        $candidates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $count = 0;
        $total = count($candidates);
        $errors = [];

        foreach ($candidates as $cccd) {
            try {
                // Determine if we need to set specific params or just rely on DB?
                // ScoreCalculationService->calculate($cccd) does everything.
                $this->scoreService->calculate($cccd);
                $count++;
            } catch (\Throwable $e) {
                $errors[] = "CCCD $cccd: " . $e->getMessage();
                error_log("AdmissionService Error for $cccd: " . $e->getMessage());
            }

            // Optional: Progress Logging or Batch Commits?
            // ScoreCalculationService updates DB directly.
        }

        return [
            'status' => true,
            'processed' => $count,
            'total' => $total,
            'errors' => $errors
        ];
    }
}
