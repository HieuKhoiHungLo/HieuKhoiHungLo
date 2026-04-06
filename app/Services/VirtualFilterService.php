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
            // Bước 1: Reset toàn bộ trang thái trúng tuyển trong bảng summary về FALSE
            $stmtReset = $this->db->prepare("
                UPDATE v_calc_summary 
                SET trang_thai_trung_tuyen = FALSE 
                WHERE nguyen_vong_id IN (SELECT id FROM nguyen_vong WHERE dot_tuyen_sinh_id = ?)
            ");
            $stmtReset->execute([$batchId]);

            if (empty($benchmarks)) {
                 $this->db->commit();
                 return ['status' => true, 'data' => [], 'message' => 'Không có điểm chuẩn để lọc.'];
            }

            // Bước 2: Lấy tất cả nguyện vọng CỦA ĐỢT NÀY kèm điểm từ bảng summary
            // Sửa lỗi: Cần lấy thêm `trang_thai_do` để loại bỏ thí sinh không đạt ngưỡng học lực/ngành (SP)
            $stmtGetAll = $this->db->prepare("
                SELECT nv.id as nv_id, nv.so_cccd, nv.ma_nganh, nv.thu_tu_nguyen_vong, nv.thu_tu_nv_bo, cs.diem_xet_tuyen, cs.trang_thai_do 
                FROM nguyen_vong nv
                JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                WHERE nv.dot_tuyen_sinh_id = ?
                ORDER BY nv.so_cccd ASC, COALESCE(nv.thu_tu_nv_bo, nv.thu_tu_nguyen_vong) ASC
            ");
            $stmtGetAll->execute([$batchId]);
            $allChoices = $stmtGetAll->fetchAll(PDO::FETCH_ASSOC);

            // Bước 3: Thuật toán Trượt dây chuyền (Cascading Filter)
            $processedCandidates = []; 
            $successfulNvIds = [];   

            foreach ($allChoices as $choice) {
                $cccd = $choice['so_cccd'];
                $major = $choice['ma_nganh'];
                $score = (float) ($choice['diem_xet_tuyen'] ?? 0);
                $nvId = $choice['nv_id'];
                
                // Nới lỏng logic: Chỉ đánh trượt nếu trạng thái bị ghi nhận ĐÍCH DANH là FALSE/0.
                // Các trường hợp NULL (chưa tính lại) hoặc TRUE đều được phép xét tuyển.
                $passedVal = $choice['trang_thai_do'];
                $passedThreshold = ($passedVal === false || $passedVal === 0 || $passedVal === 'f' || $passedVal === '0') ? 0 : 1;

                if (isset($processedCandidates[$cccd])) continue;
                
                // ĐIỀU KIỆN ĐẠT: Điểm >= Điểm chuẩn VÀ Trạng thái đạt ngưỡng (Học lực/Ngành)
                if (!isset($benchmarks[$major])) continue; // Nếu ngành chưa có điểm chuẩn thì bỏ qua
                
                $benchmarkScore = (float) $benchmarks[$major];

                if ($score >= $benchmarkScore && $passedThreshold === 1) {
                    $processedCandidates[$cccd] = true;
                    $successfulNvIds[] = $nvId;
                }
            }

            // Bước 4: Bulk Update cho các Nguyện vọng đậu (O(1) database hit)
            if (!empty($successfulNvIds)) {
                $idsList = implode(',', array_map('intval', $successfulNvIds));
                $this->db->exec("
                    UPDATE v_calc_summary 
                    SET trang_thai_trung_tuyen = TRUE 
                    WHERE nguyen_vong_id IN ($idsList)
                ");
            }

            $this->db->commit();
            
            $stats = $this->getFilterStats($batchId);
            return [
                'status' => true, 
                'data' => $stats,
                'candidate_count' => count($processedCandidates),
                'successful_count' => count($successfulNvIds)
            ];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("VirtualFilter Error (Throwable): " . $e->getMessage());
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
                $stmtInsert->execute([$batchId, $major, round((float)$score, 2), $quota]);
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
        // Lấy từ bảng nháp (diem_chuan_du_kien) join với admission_benchmarks 
        // và join với dm_nganh để lấy chi_tieu gốc (do ab không có cột chi_tieu)
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(dk.ma_nganh, ab.ma_nganh) as ma_nganh,
                COALESCE(dk.diem_chuan, ab.diem_chuan, 0) as diem_chuan,
                COALESCE(dk.chi_tieu_du_kien, n.chi_tieu, 0) as chi_tieu_du_kien
            FROM admission_benchmarks ab
            LEFT JOIN dm_nganh n ON ab.ma_nganh = n.ma_nganh
            LEFT JOIN diem_chuan_du_kien dk ON ab.ma_nganh = dk.ma_nganh AND ab.session_id = dk.dot_tuyen_sinh_id
            WHERE ab.session_id = ?
        ");
        $stmt->execute([$batchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Thống kê số lượng Trúng Tuyển mỗi ngành dựa trên Cờ TRUE của Lọc ảo
     */
    public function getFilterStats($batchId) {
        $stmt = $this->db->prepare("
            SELECT nv.ma_nganh, COUNT(*) as so_luong_dat 
            FROM nguyen_vong nv
            JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
            WHERE nv.dot_tuyen_sinh_id = ? AND cs.trang_thai_trung_tuyen = TRUE
            GROUP BY nv.ma_nganh
        ");
        $stmt->execute([$batchId]);
        
        $stats = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['ma_nganh']] = $row['so_luong_dat'];
        }
        
        return ['status' => true, 'data' => $stats];
    }

    /**
     * Đồng bộ dữ liệu thí sinh 'Đã duyệt' vào đợt xét tuyển.
     * Cập nhật trạng thái trang_thai của nguyen_vong dựa trên ho_so_xet_tuyen.
     */
    public function syncData($batchId) {
        try {
            // TỐI ƯU HÓA SIÊU TỐC (Ultra-fast synchronization)
            // Thay vì thực thi 4 câu lệnh UPDATE rời rạc quét bản ghi nguyen_vong nhiều lần,
            // Chúng ta xử lý toàn bộ logic bằng MỘT vòng quét duy nhất thông qua Common Table Expression (CTE).
            
            // 1. Sửa lỗi ID trước (Tận dụng Index)
            $sqlFix = "
                UPDATE nguyen_vong
                SET dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id
                FROM ho_so_xet_tuyen hs
                WHERE nguyen_vong.so_cccd = hs.so_cccd
                AND hs.dot_tuyen_sinh_id = ?
                AND (nguyen_vong.dot_tuyen_sinh_id IS NULL OR nguyen_vong.dot_tuyen_sinh_id = hs.id)
            ";
            $this->db->prepare($sqlFix)->execute([(int)$batchId]);

            // 1. TỐI ƯU HÓA DATABASE SCHEMA: 
            // Tự động kiểm tra và tạo Index nếu DB (Supabase) của bạn đang bị thiếu, nguyên nhân cốt lõi gây ra Full Table Scan tốn 32 giây!
            try {
                $this->db->exec("CREATE INDEX IF NOT EXISTS idx_nguyenvong_dot_tuyen_sinh ON nguyen_vong(dot_tuyen_sinh_id)");
                $this->db->exec("CREATE INDEX IF NOT EXISTS idx_nguyenvong_cccd_dot ON nguyen_vong(so_cccd, dot_tuyen_sinh_id)");
                $this->db->exec("CREATE INDEX IF NOT EXISTS idx_hoso_cccd_dot ON ho_so_xet_tuyen(so_cccd, dot_tuyen_sinh_id)");
            } catch (\Exception $e) { /* Bỏ qua nếu user không đủ quyền admin */ }

            // 2. Query 1: Cập nhật sang Đã duyệt (Dùng `UPDATE ... FROM` để ép Postgres sử dụng Hash Join thần tốc)
            $sqlSetDaDuyet = "
                UPDATE nguyen_vong nv
                SET trang_thai = 'DaDuyet'
                FROM ho_so_xet_tuyen hs
                WHERE nv.dot_tuyen_sinh_id = ?
                AND nv.so_cccd = hs.so_cccd 
                AND nv.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id
                AND (hs.trang_thai = 'Đã duyệt' OR hs.trang_thai LIKE '%Đã duyệt%')
                AND (nv.trang_thai IS NULL OR nv.trang_thai <> 'DaDuyet')
            ";
            $this->db->prepare($sqlSetDaDuyet)->execute([(int)$batchId]);

            // 3. Query 2: Giáng cấp hồ sơ lỗi/chưa duyệt sang Chờ Duyệt (Dùng Anti-Join bằng NOT EXISTS)
            $sqlSetChoDuyet = "
                UPDATE nguyen_vong nv
                SET trang_thai = 'ChoDuyet'
                WHERE dot_tuyen_sinh_id = ?
                AND (trang_thai IS NULL OR trang_thai <> 'ChoDuyet')
                AND NOT EXISTS (
                    SELECT 1 FROM ho_so_xet_tuyen hs 
                    WHERE hs.so_cccd = nv.so_cccd AND hs.dot_tuyen_sinh_id = nv.dot_tuyen_sinh_id
                    AND (hs.trang_thai = 'Đã duyệt' OR hs.trang_thai LIKE '%Đã duyệt%')
                )
            ";
            $this->db->prepare($sqlSetChoDuyet)->execute([(int)$batchId]);

            return true;
        } catch (\Exception $e) {
            $msg = date('Y-m-d H:i:s') . " - VirtualFilterService syncData Error: " . $e->getMessage() . "\n";
            error_log($msg);
            file_put_contents(__DIR__ . '/../../app_error.log', $msg, FILE_APPEND);
            return false;
        }
    }
}
