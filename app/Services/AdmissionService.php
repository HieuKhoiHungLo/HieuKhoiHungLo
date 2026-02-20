<?php
namespace App\Services;

use App\Core\Database;
use App\Services\ScoreCalculationService;
use PDO;

class AdmissionService {
    protected $db;
    protected $scoreService;

    protected $thiSinhRepo;
    protected $nguyenVongRepo;
    protected $emailService;
    protected $auditService;

    public function __construct() {
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
    public function processReview($cccd, $reviewData, $candidateInfo = null) {
        // 1. Determine overall status
        $isRejected = false;
        $sections = [
            'personal' => 'Thông tin Cá nhân & Liên hệ',
            'academic' => 'Kết quả Học tập (Học bạ)',
            'thpt' => 'Điểm thi Tốt nghiệp THPT',
            'certs' => 'Chứng chỉ Ngoại ngữ & Khác',
            'wishes' => 'Nguyện vọng đăng ký'
        ];

        $reviewResults = [];
        $collectedNotes = [];

        foreach ($sections as $key => $label) {
            $status = $reviewData["status_$key"] ?? 'approved';
            $note = $reviewData["note_$key"] ?? '';
            
            if ($status === 'rejected') {
                $isRejected = true;
                if (!empty($note)) {
                    $collectedNotes[] = "$label: $note";
                }
            }
            
            $reviewResults[] = [
                'name' => $label,
                'status' => $status === 'approved' ? 'ok' : 'missing',
                'note' => $status === 'rejected' ? $note : ''
            ];
        }

        $finalStatus = $isRejected ? \App\Core\UserStatus::REJECTED : \App\Core\UserStatus::APPROVED;
        $dbNote = empty($collectedNotes) ? ($isRejected ? 'Cần xem lại hồ sơ.' : 'Đã duyệt.') : implode("\n", $collectedNotes);

        // 2. Update Database
        $this->thiSinhRepo->updateApplicationStatus($cccd, $finalStatus, $dbNote);

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
            $generalNote = $isRejected ? 'Hồ sơ của bạn có nội dung cần chỉnh sửa. Vui lòng xem chi tiết.' : 'Hồ sơ đã được duyệt hợp lệ.';
            
            $this->emailService->queueWithTemplate($email, 'application_reviewed', [
                'ho_ten' => $hoTen,
                'ket_qua_chi_tiet' => $resultHtml,
                'ghi_chu' => $generalNote
            ]);
        }

        // 4. Audit Log
        $action = $isRejected ? 'REVIEW_REJECTED' : 'REVIEW_APPROVED';
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


    public function calculateBatchScores($batchId) {
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
