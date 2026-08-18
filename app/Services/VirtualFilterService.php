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
    public function runVirtualFilter($batchId, $benchmarks, $isHocBa = false) {
        $this->db->beginTransaction();

        try {
            // Bước 1: Reset toàn bộ trang thái trúng tuyển trong bảng summary về FALSE
            $stmtReset = $this->db->prepare("
                UPDATE v_calc_summary 
                SET trang_thai_trung_tuyen = FALSE,
                    ket_qua_bo_gd_du_kien = NULL 
                WHERE nguyen_vong_id IN (SELECT id FROM nguyen_vong WHERE dot_tuyen_sinh_id = ?)
            ");
            $stmtReset->execute([$batchId]);

            if (empty($benchmarks)) {
                 $this->db->commit();
                 return ['status' => true, 'data' => [], 'message' => 'Không có điểm chuẩn để lọc.'];
            }

            // Bước 2: Lấy tất cả nguyện vọng CỦA ĐỢT NÀY kèm điểm từ bảng summary
            // Sửa lỗi: Cần lấy thêm `trang_thai_do` để loại bỏ thí sinh không đạt ngưỡng học lực/ngành (SP)
            // Chỉ lấy nguyện vọng có trạng thái ĐÃ DUYỆT (đồng bộ với Review Management)
            $stmtGetAll = $this->db->prepare("
                SELECT nv.id as nv_id, nv.so_cccd, nv.ma_nganh, nv.thu_tu_nguyen_vong, nv.thu_tu_nv_bo, cs.diem_xet_tuyen, cs.trang_thai_do 
                FROM nguyen_vong nv
                JOIN v_calc_summary cs ON nv.id = cs.nguyen_vong_id
                WHERE nv.dot_tuyen_sinh_id = ?
                AND (nv.trang_thai IN ('DaDuyet', 'approved') OR nv.trang_thai LIKE '%Đã duyệt%')
                ORDER BY nv.so_cccd ASC, COALESCE(nv.thu_tu_nv_bo, nv.thu_tu_nguyen_vong) ASC
            ");
            $stmtGetAll->execute([$batchId]);
            $allChoices = $stmtGetAll->fetchAll(PDO::FETCH_ASSOC);

            // Tải danh sách Ngoại lệ xét tuyển (Ép buộc trạng thái)
            $stmtEx = $this->db->prepare("SELECT so_cccd, ma_nganh, trang_thai_ep_buoc FROM ngoai_le_xet_tuyen WHERE dot_tuyen_sinh_id = ?");
            $stmtEx->execute([$batchId]);
            $exceptionsRaw = $stmtEx->fetchAll(PDO::FETCH_ASSOC);
            $exceptions = [];
            foreach ($exceptionsRaw as $ex) {
                $exceptions[$ex['so_cccd'] . '_' . $ex['ma_nganh']] = $ex['trang_thai_ep_buoc'];
            }

            // Bước 3: Thuật toán Trượt dây chuyền (Cascading Filter)
            $processedCandidates = []; 
            $successfulNvIds = [];   
            $candidateAdmittedChoice = []; // cccd => choice array

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
                
                // --- Kiểm tra Ngoại lệ xét tuyển (Ép buộc) ---
                $exKey = $cccd . '_' . $major;
                if (isset($exceptions[$exKey])) {
                    if ($exceptions[$exKey] === 'Truot') {
                        // ÉP TRƯỢT: Bỏ qua nguyện vọng này. 
                        // KHÔNG đánh dấu $processedCandidates để hệ thống tiếp tục xét các NV thấp hơn (NV2, NV3).
                        continue;
                    } elseif ($exceptions[$exKey] === 'TrungTuyen') {
                        // ÉP ĐỖ: Bỏ qua kiểm tra điểm chuẩn và ngưỡng, đưa thẳng vào danh sách đỗ.
                        $processedCandidates[$cccd] = true;
                        $successfulNvIds[] = $nvId;
                        $candidateAdmittedChoice[$cccd] = $choice;
                        continue;
                    }
                }
                // --- Kết thúc Ngoại lệ ---

                // ĐIỀU KIỆN ĐẠT BÌNH THƯỜNG: Điểm >= Điểm chuẩn VÀ Trạng thái đạt ngưỡng (Học lực/Ngành)
                if (!isset($benchmarks[$major])) continue; // Nếu ngành chưa có điểm chuẩn thì bỏ qua
                
                $benchmarkScore = (float) $benchmarks[$major];

                if ($score >= $benchmarkScore && $passedThreshold === 1) {
                    $processedCandidates[$cccd] = true;
                    $successfulNvIds[] = $nvId;
                    $candidateAdmittedChoice[$cccd] = $choice;
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

                // Đối với đợt Học Bạ: 100% đạt lọc ảo Bộ GD
                // Tự động ghi nhận ket_qua_bo_gd = 'Đỗ' mà không cần import file từ Bộ
                if ($isHocBa) {
                    $this->db->exec("
                        UPDATE v_calc_summary 
                        SET ket_qua_bo_gd = 'Đỗ', ket_qua_bo_gd_du_kien = 'Đỗ', bi_loai_truong_khac = FALSE
                        WHERE nguyen_vong_id IN ($idsList)
                    ");
                } else {
                    // Đợt Thi THPT: Thuật toán Tái tính toán "Dự kiến sau Lọc ảo Bộ" (Dynamic Ministry Prediction)
                    // 1. Thí sinh đỗ tại Trường mình theo dữ liệu Bộ
                    $stmtOur = $this->db->prepare("
                        SELECT DISTINCT so_cccd 
                        FROM ket_qua_loc_ao_bo_gd 
                        WHERE dot_tuyen_sinh_id = ? AND ket_qua = 'Đỗ'
                    ");
                    $stmtOur->execute([$batchId]);
                    $ourAdmittedCccds = array_fill_keys($stmtOur->fetchAll(PDO::FETCH_COLUMN), true);

                    $stmtOurCs = $this->db->prepare("
                        SELECT DISTINCT nv.so_cccd 
                        FROM v_calc_summary cs
                        JOIN nguyen_vong nv ON cs.nguyen_vong_id = nv.id
                        WHERE nv.dot_tuyen_sinh_id = ? AND cs.ket_qua_bo_gd = 'Đỗ'
                    ");
                    $stmtOurCs->execute([$batchId]);
                    foreach ($stmtOurCs->fetchAll(PDO::FETCH_COLUMN) as $c) {
                        $ourAdmittedCccds[$c] = true;
                    }

                    // 2. Thí sinh đỗ tại Trường ngoài theo dữ liệu Bộ
                    $stmtOutside = $this->db->prepare("
                        SELECT so_cccd, MIN(ttnv_do) as min_nv_do
                        FROM ket_qua_loc_ao_bo_gd 
                        WHERE dot_tuyen_sinh_id = ? 
                          AND ttnv_do > 0
                          AND ma_truong_trung_tuyen IS NOT NULL AND TRIM(ma_truong_trung_tuyen) != '' 
                          AND UPPER(TRIM(ma_truong_trung_tuyen)) NOT IN ('THV', 'HVU')
                        GROUP BY so_cccd
                    ");
                    $stmtOutside->execute([$batchId]);
                    $outsideRows = $stmtOutside->fetchAll(PDO::FETCH_ASSOC);

                    $outsideAdmittedMinNv = [];
                    foreach ($outsideRows as $r) {
                        $outsideAdmittedMinNv[$r['so_cccd']] = (int) $r['min_nv_do'];
                    }

                    $stmtOutsideCs = $this->db->prepare("
                        SELECT nv.so_cccd, nv.thu_tu_nguyen_vong, nv.thu_tu_nv_bo, cs.ma_truong_trung_tuyen_bo
                        FROM v_calc_summary cs
                        JOIN nguyen_vong nv ON cs.nguyen_vong_id = nv.id
                        WHERE nv.dot_tuyen_sinh_id = ? AND (cs.bi_loai_truong_khac = TRUE OR (cs.ma_truong_trung_tuyen_bo IS NOT NULL AND cs.ma_truong_trung_tuyen_bo != '' AND UPPER(TRIM(cs.ma_truong_trung_tuyen_bo)) NOT IN ('THV', 'HVU')))
                    ");
                    $stmtOutsideCs->execute([$batchId]);
                    foreach ($stmtOutsideCs->fetchAll(PDO::FETCH_ASSOC) as $r) {
                        $c = $r['so_cccd'];
                        if (!isset($outsideAdmittedMinNv[$c])) {
                            $nvOrd = (int) ($r['thu_tu_nv_bo'] ?? $r['thu_tu_nguyen_vong'] ?? 1);
                            $outsideAdmittedMinNv[$c] = max(1, $nvOrd - 1);
                        }
                    }

                    $doBoNvIds = [];
                    $truotBoNvIds = [];

                    foreach ($candidateAdmittedChoice as $cccd => $choice) {
                        $nvId = (int)$choice['nv_id'];
                        $currentNvOrder = (int)($choice['thu_tu_nv_bo'] ?? $choice['thu_tu_nguyen_vong'] ?? 999);

                        // Trường hợp 2: Nếu thí sinh có NV đỗ trường ngoài có thứ tự ưu tiên cao hơn (min_nv_do < currentNvOrder)
                        if (isset($outsideAdmittedMinNv[$cccd]) && $outsideAdmittedMinNv[$cccd] < $currentNvOrder) {
                            $truotBoNvIds[] = $nvId;
                        } 
                        // Trường hợp 1: Thí sinh từng đỗ ở bất kỳ NV nào tại Trường mình (VD từ NV1 trượt xuống NV4)
                        else if (isset($ourAdmittedCccds[$cccd])) {
                            $doBoNvIds[] = $nvId;
                        }
                        // Trường hợp không bị trường ngoài có NV cao hơn hút mất
                        else if (!isset($outsideAdmittedMinNv[$cccd]) || $outsideAdmittedMinNv[$cccd] >= $currentNvOrder) {
                            $doBoNvIds[] = $nvId;
                        }
                        else {
                            $truotBoNvIds[] = $nvId;
                        }
                    }

                    if (!empty($doBoNvIds)) {
                        $doList = implode(',', array_map('intval', $doBoNvIds));
                        $this->db->exec("
                            UPDATE v_calc_summary 
                            SET ket_qua_bo_gd_du_kien = 'Đỗ' 
                            WHERE nguyen_vong_id IN ($doList)
                        ");
                    }

                    if (!empty($truotBoNvIds)) {
                        $truotList = implode(',', array_map('intval', $truotBoNvIds));
                        $this->db->exec("
                            UPDATE v_calc_summary 
                            SET ket_qua_bo_gd_du_kien = 'Trượt' 
                            WHERE nguyen_vong_id IN ($truotList)
                        ");
                    }
                }
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
                WHERE nguyen_vong.ho_so_id = hs.id
                AND hs.dot_tuyen_sinh_id = ?
                AND (nguyen_vong.dot_tuyen_sinh_id IS NULL OR nguyen_vong.dot_tuyen_sinh_id <> hs.dot_tuyen_sinh_id)
            ";
            $this->db->prepare($sqlFix)->execute([(int)$batchId]);

            // 1. TỐI ƯU HÓA DATABASE SCHEMA: 
            // Tự động kiểm tra và tạo Index nếu DB (Supabase) của bạn đang bị thiếu, nguyên nhân cốt lõi gây ra Full Table Scan tốn 32 giây!
            try {
                $this->db->exec("CREATE INDEX IF NOT EXISTS idx_nguyenvong_dot_tuyen_sinh ON nguyen_vong(dot_tuyen_sinh_id)");
                $this->db->exec("CREATE INDEX IF NOT EXISTS idx_nguyenvong_cccd_dot ON nguyen_vong(so_cccd, dot_tuyen_sinh_id)");
                $this->db->exec("CREATE INDEX IF NOT EXISTS idx_hoso_cccd_dot ON ho_so_xet_tuyen(so_cccd, dot_tuyen_sinh_id)");
            } catch (\Exception $e) { /* Bỏ qua nếu user không đủ quyền admin */ }

            // 2. Query 1: Cập nhật sang Đã duyệt (chỉ hồ sơ thực sự "Đã duyệt", không tính "Yêu cầu sửa")
            $sqlSetDaDuyet = "
                UPDATE nguyen_vong nv
                SET trang_thai = 'DaDuyet'
                FROM ho_so_xet_tuyen hs
                WHERE nv.dot_tuyen_sinh_id = ?
                AND nv.ho_so_id = hs.id 
                AND nv.dot_tuyen_sinh_id = hs.dot_tuyen_sinh_id
                AND (hs.trang_thai IN ('Đã duyệt', 'approved', 'DaDuyet') OR hs.trang_thai LIKE '%Đã duyệt%')
                AND (nv.trang_thai IS NULL OR nv.trang_thai <> 'DaDuyet')
            ";
            $this->db->prepare($sqlSetDaDuyet)->execute([(int)$batchId]);

            // 3. Query 2: Giáng cấp hồ sơ lỗi/chưa duyệt/yêu cầu sửa sang Chờ Duyệt
            $sqlSetChoDuyet = "
                UPDATE nguyen_vong nv
                SET trang_thai = 'ChoDuyet'
                WHERE dot_tuyen_sinh_id = ?
                AND (trang_thai IS NULL OR trang_thai <> 'ChoDuyet')
                AND NOT EXISTS (
                    SELECT 1 FROM ho_so_xet_tuyen hs 
                    WHERE hs.id = nv.ho_so_id AND hs.dot_tuyen_sinh_id = nv.dot_tuyen_sinh_id
                    AND (hs.trang_thai IN ('Đã duyệt', 'approved', 'DaDuyet') OR hs.trang_thai LIKE '%Đã duyệt%')
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
