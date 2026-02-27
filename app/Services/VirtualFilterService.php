<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class VirtualFilterService {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Chạy thuật toán Lọc Ảo Nội Bộ (Virtual Filtering)
     * Thí sinh đậu NV ưu tiên cao (số nhỏ) sẽ tự rớt các NV thấp.
     * 
     * @param int $batchId ID đợt tuyển sinh
     * @param array $benchmarks Mảng associative: ['ma_nganh' => diem_chuan, ...]
     * @return array Thống kê kết quả
     */
    public function runVirtualFilter($batchId, $benchmarks) {
        $this->db->beginTransaction();

        try {
            // Bước 1: Reset toàn bộ trang thái trúng tuyển của đợt này về FALSE để tính lại từ đầu
            $stmtReset = $this->db->prepare("UPDATE nguyen_vong SET trang_thai_trung_tuyen = FALSE WHERE dot_tuyen_sinh_id = ?");
            $stmtReset->execute([$batchId]);

            if (empty($benchmarks)) {
                 $this->db->commit();
                 return ['status' => true, 'data' => []];
            }

            // Bước 2: Lấy tất cả nguyện vọng CỦA ĐỢT NÀY, sắp xếp theo thí sinh và ƯU TIÊN (ASC)
            $stmtGetAll = $this->db->prepare("
                SELECT so_cccd, ma_nganh, thu_tu_nguyen_vong, diem_xet_tuyen 
                FROM nguyen_vong 
                WHERE dot_tuyen_sinh_id = ?
                ORDER BY so_cccd ASC, thu_tu_nguyen_vong ASC
            ");
            $stmtGetAll->execute([$batchId]);
            $allChoices = $stmtGetAll->fetchAll(PDO::FETCH_ASSOC);

            // Bước 3: Thuật toán Trượt dây chuyền (Cascading Filter)
            $processedCandidates = []; // Lưu các thí sinh ĐÃ ĐẬU 1 nguyện vọng (bất kỳ)
            $successfulChoices = [];   // Lưu các nguyện vọng sẽ được đánh dấu TRUE

            foreach ($allChoices as $choice) {
                $cccd = $choice['so_cccd'];
                $major = $choice['ma_nganh'];
                $score = (float) $choice['diem_xet_tuyen'];

                // Nếu thí sinh này đã đậu 1 nguyện vọng ưu tiên cao hơn trước đó -> Bỏ qua toàn bộ NV dưới
                if (isset($processedCandidates[$cccd])) {
                    continue;
                }

                // Ngành này có nằm trong danh sách điểm chuẩn truyền vào không?
                if (!isset($benchmarks[$major])) {
                    continue;
                }

                $benchmarkScore = (float) $benchmarks[$major];

                // Kiểm tra Đậu/Rớt
                if ($score >= $benchmarkScore) {
                    // 1. Chốt: Thí sinh này ĐÃ ĐẬU. Khóa danh sách xét duyệt của họ lại.
                    $processedCandidates[$cccd] = true;
                    // 2. Đưa Nguyện vọng này vào rổ Thành công
                    $successfulChoices[] = [
                        'so_cccd' => $cccd,
                        'ma_nganh' => $major
                    ];
                }
            }

            // Bước 4: Batch Update cho các Nguyện vọng đậu
            // Dùng Prepared Statement lặp lại (an toàn, nhanh với lượng dữ liệu vừa vài nghìn dòng)
            if (!empty($successfulChoices)) {
                $updateStmt = $this->db->prepare("
                    UPDATE nguyen_vong 
                    SET trang_thai_trung_tuyen = TRUE 
                    WHERE dot_tuyen_sinh_id = ? AND so_cccd = ? AND ma_nganh = ?
                ");
                foreach ($successfulChoices as $win) {
                    $updateStmt->execute([$batchId, $win['so_cccd'], $win['ma_nganh']]);
                }
            }

            $this->db->commit();

            // Trả về thống kê số lượng đỗ thực tế cho Giao diện
            return $this->getFilterStats($batchId);

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("VirtualFilter Error: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Lưu lại các thông số Điểm Chuẩn và Chỉ Tiêu Dự Kiến mà Quản trị viên vừa test
     */
    public function saveExpectedBenchmarks($batchId, $benchmarks, $quotas) {
        $this->db->beginTransaction();
        try {
            // Xóa điểm chuẩn nháp cũ
            $stmtDelete = $this->db->prepare("DELETE FROM diem_chuan_du_kien WHERE dot_tuyen_sinh_id = ?");
            $stmtDelete->execute([$batchId]);

            $stmtInsert = $this->db->prepare("
                INSERT INTO diem_chuan_du_kien (dot_tuyen_sinh_id, ma_nganh, diem_chuan, chi_tieu_du_kien)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($benchmarks as $major => $score) {
                $quota = intval($quotas[$major] ?? 0);
                $stmtInsert->execute([$batchId, $major, (float)$score, $quota]);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("VirtualFilter Save Benchmarks Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy lại mốc điểm chuẩn lần cuối cùng Admin kéo thả để hiển thị
     */
    public function getExpectedBenchmarks($batchId) {
        $stmt = $this->db->prepare("SELECT ma_nganh, diem_chuan, chi_tieu_du_kien FROM diem_chuan_du_kien WHERE dot_tuyen_sinh_id = ?");
        $stmt->execute([$batchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Thống kê số lượng Trúng Tuyển mỗi ngành dựa trên Cờ TRUE của Lọc ảo
     */
    public function getFilterStats($batchId) {
        $stmt = $this->db->prepare("
            SELECT ma_nganh, COUNT(*) as so_luong_dat 
            FROM nguyen_vong 
            WHERE dot_tuyen_sinh_id = ? AND trang_thai_trung_tuyen = TRUE
            GROUP BY ma_nganh
        ");
        $stmt->execute([$batchId]);
        
        $stats = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['ma_nganh']] = $row['so_luong_dat'];
        }
        
        return ['status' => true, 'data' => $stats];
    }
}
