<?php
namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Service layer for Talent Test (Năng Khiếu) module.
 * Handles creation of sessions, subjects, rooms, candidate synchronization,
 * automatic exam number generation and score management.
 *
 * This service does NOT modify any existing tables – it works only with the
 * newly created `talent_test_*` tables.
 */
class TalentTestService
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ---------------------------------------------------------------------
    // Session management
    // ---------------------------------------------------------------------
    public function createSession(array $data)
    {
        $sql = "INSERT INTO talent_test_sessions (year, session_name, start_date, end_date, description, created_at, updated_at)
                VALUES (:year, :name, :start, :end, :desc, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':year' => $data['year'],
            ':name' => $data['session_name'],
            ':start' => $data['start_date'],
            ':end' => $data['end_date'],
            ':desc' => $data['description'] ?? null,
        ]);
        return $this->db->lastInsertId();
    }

    public function updateSession(int $id, array $data)
    {
        $sql = "UPDATE talent_test_sessions SET year=:year, session_name=:name, start_date=:start, end_date=:end, description=:desc, updated_at=NOW() WHERE id=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':year' => $data['year'],
            ':name' => $data['session_name'],
            ':start' => $data['start_date'],
            ':end' => $data['end_date'],
            ':desc' => $data['description'] ?? null,
            ':id' => $id,
        ]);
        return $stmt->rowCount();
    }

    // ---------------------------------------------------------------------
    // Subject (môn thi) management
    // ---------------------------------------------------------------------
    public function addSubject(int $sessionId, array $data)
    {
        $sql = "INSERT INTO talent_test_subjects (session_id, major_code, subject_name, max_score, created_at, updated_at)
                VALUES (:sid, :code, :name, :max, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':sid' => $sessionId,
            ':code' => $data['major_code'],
            ':name' => $data['subject_name'],
            ':max' => $data['max_score'] ?? 100,
        ]);
        return $this->db->lastInsertId();
    }

    // ---------------------------------------------------------------------
    // Room management
    // ---------------------------------------------------------------------
    public function addRoom(int $sessionId, array $data)
    {
        $sql = "INSERT INTO talent_test_rooms (session_id, room_name, capacity, created_at, updated_at)
                VALUES (:sid, :name, :cap, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':sid' => $sessionId,
            ':name' => $data['room_name'],
            ':cap' => $data['capacity'],
        ]);
        return $this->db->lastInsertId();
    }

    // ---------------------------------------------------------------------
    // Candidate synchronization (đồng bộ thí sinh đã duyệt)
    // ---------------------------------------------------------------------
    /**
     * Pull candidates that have been approved and whose desired major is among
     * the given $majorCodes (array of strings, e.g. ['7140201','7140206']).
     * Creates an assignment record for each candidate and generates an exam
     * number following the pattern TNH-{year}-{majorCode}-{seq} where seq is
     * incremental per subject.
     */
    public function syncCandidates(int $sessionId, array $majorCodes)
    {
        if (empty($majorCodes)) return 0;
        
        // 1. Get subjects for this session
        $subStmt = $this->db->prepare("SELECT id, major_code FROM talent_test_subjects WHERE session_id = ?");
        $subStmt->execute([$sessionId]);
        $subjects = [];
        foreach ($subStmt->fetchAll(PDO::FETCH_ASSOC) as $sub) {
            $subjects[$sub['major_code']] = $sub['id'];
        }

        if (empty($subjects)) return 0;

        // 2. Get current sequence number for each subject to generate exam_number
        $seqMap = [];
        foreach ($subjects as $majorCode => $subjectId) {
            $cntStmt = $this->db->prepare("SELECT COUNT(*) FROM talent_test_assignments WHERE subject_id = ?");
            $cntStmt->execute([$subjectId]);
            $seqMap[$subjectId] = (int)$cntStmt->fetchColumn();
        }

        // 3. Get approved candidates matching the major codes
        $placeholders = implode(',', array_fill(0, count($majorCodes), '?'));
        $sql = "SELECT t.id, t.ho_va_ten AS name, t.email, nv.ma_nganh AS major_code 
                FROM thi_sinh t
                JOIN ho_so_xet_tuyen hs ON hs.so_cccd = t.so_cccd
                JOIN nguyen_vong nv ON nv.ho_so_id = hs.id
                WHERE (nv.trang_thai = 'Đã duyệt' OR hs.trang_thai = 'Đã duyệt') 
                AND nv.ma_nganh IN ($placeholders)
                AND t.deleted_at IS NULL AND hs.deleted_at IS NULL AND nv.deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($majorCodes);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Get candidates already synced to avoid duplicates
        $existingStmt = $this->db->prepare("
            SELECT a.candidate_id, s.major_code 
            FROM talent_test_assignments a
            JOIN talent_test_subjects s ON s.id = a.subject_id
            WHERE s.session_id = ?
        ");
        $existingStmt->execute([$sessionId]);
        $existing = [];
        foreach ($existingStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existing[$row['candidate_id'] . '_' . $row['major_code']] = true;
        }

        $this->db->beginTransaction();
        try {
            $insSql = "INSERT INTO talent_test_assignments (candidate_id, subject_id, room_id, exam_number, status, created_at, updated_at)
                       VALUES (:cid, :sid, NULL, :ex, 'not_taken', NOW(), NOW())";
            $insStmt = $this->db->prepare($insSql);

            $syncedCount = 0;
            $year = date('Y');

            foreach ($candidates as $cand) {
                $majorCode = $cand['major_code'];
                $subjectId = $subjects[$majorCode] ?? null;

                if (!$subjectId) continue;
                
                // Skip if already synced
                if (isset($existing[$cand['id'] . '_' . $majorCode])) continue;

                $seqMap[$subjectId]++;
                $seq = $seqMap[$subjectId];
                $examNumber = sprintf('TNH-%s-%s-%04d', $year, $majorCode, $seq);

                $insStmt->execute([
                    ':cid' => $cand['id'],
                    ':sid' => $subjectId,
                    ':ex' => $examNumber,
                ]);
                
                $syncedCount++;
            }
            $this->db->commit();
            return $syncedCount;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ---------------------------------------------------------------------
    // Score handling
    // ---------------------------------------------------------------------
    public function saveScore(int $assignmentId, float $score, ?string $note = null)
    {
        $checkStmt = $this->db->prepare("SELECT id FROM talent_test_scores WHERE assignment_id = ?");
        $checkStmt->execute([$assignmentId]);
        if ($checkStmt->fetchColumn()) {
            $sql = "UPDATE talent_test_scores SET score=:sc, note=:nt, updated_at=NOW() WHERE assignment_id=:aid";
        } else {
            $sql = "INSERT INTO talent_test_scores (assignment_id, score, note, created_at, updated_at)
                    VALUES (:aid, :sc, :nt, NOW(), NOW())";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':aid' => $assignmentId,
            ':sc' => $score,
            ':nt' => $note,
        ]);
        return $stmt->rowCount();
    }

    /**
     * Phân phòng thi tự động cho tất cả thí sinh chưa có phòng trong đợt thi
     */
    public function autoAssignRooms(int $sessionId)
    {
        // 1. Lấy danh sách các phòng thi của đợt này và sức chứa hiện tại
        $sqlRooms = "SELECT r.id, r.capacity, 
                    (SELECT COUNT(*) FROM talent_test_assignments a WHERE a.room_id = r.id) as current_count
                    FROM talent_test_rooms r 
                    WHERE r.session_id = ? 
                    ORDER BY r.id ASC";
        $stmtRooms = $this->db->prepare($sqlRooms);
        $stmtRooms->execute([$sessionId]);
        $rooms = $stmtRooms->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rooms)) return 0;

        // 2. Lấy danh sách thí sinh chưa được phân phòng trong đợt này
        $sqlCandidates = "SELECT a.id 
                         FROM talent_test_assignments a
                         JOIN talent_test_subjects s ON s.id = a.subject_id
                         WHERE s.session_id = ? AND a.room_id IS NULL
                         ORDER BY a.exam_number ASC";
        $stmtCandidates = $this->db->prepare($sqlCandidates);
        $stmtCandidates->execute([$sessionId]);
        $assignments = $stmtCandidates->fetchAll(PDO::FETCH_ASSOC);

        $assignedCount = 0;
        $roomIndex = 0;

        foreach ($assignments as $a) {
            // Tìm phòng còn chỗ
            while ($roomIndex < count($rooms) && $rooms[$roomIndex]['current_count'] >= $rooms[$roomIndex]['capacity']) {
                $roomIndex++;
            }

            if ($roomIndex >= count($rooms)) break; // Hết phòng còn chỗ

            // Gán phòng cho thí sinh
            $updSql = "UPDATE talent_test_assignments SET room_id = ?, updated_at = NOW() WHERE id = ?";
            $updStmt = $this->db->prepare($updSql);
            $updStmt->execute([$rooms[$roomIndex]['id'], $a['id']]);

            $rooms[$roomIndex]['current_count']++;
            $assignedCount++;
        }

        return $assignedCount;
    }

    /**
     * Đánh số túi bài thi tự động theo phòng
     */
    public function assignBagNumbers(int $sessionId, string $prefix = 'TUI-')
    {
        // 1. Lấy danh sách các phòng thi
        $sqlRooms = "SELECT id, room_name FROM talent_test_rooms WHERE session_id = ? ORDER BY id ASC";
        $stmtRooms = $this->db->prepare($sqlRooms);
        $stmtRooms->execute([$sessionId]);
        $rooms = $stmtRooms->fetchAll(PDO::FETCH_ASSOC);

        $totalUpdated = 0;
        foreach ($rooms as $index => $room) {
            $bagNumber = $prefix . sprintf('%03d', $index + 1);
            
            $updSql = "UPDATE talent_test_assignments
                       SET bag_number = ?, updated_at = NOW()
                       WHERE room_id = ?
                       AND subject_id IN (SELECT id FROM talent_test_subjects WHERE session_id = ?)";
            $updStmt = $this->db->prepare($updSql);
            $updStmt->execute([$bagNumber, $room['id'], $sessionId]);
            $totalUpdated += $updStmt->rowCount();
        }

        return $totalUpdated;
    }

    public function listAssignments(int $sessionId)
    {
        $sql = "SELECT a.id, c.ho_va_ten AS candidate_name, s.subject_name, r.room_name, a.exam_number, a.status
                FROM talent_test_assignments a
                JOIN thi_sinh c ON c.id = a.candidate_id
                JOIN talent_test_subjects s ON s.id = a.subject_id
                LEFT JOIN talent_test_rooms r ON r.id = a.room_id
                WHERE s.session_id = ?
                ORDER BY a.exam_number";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
