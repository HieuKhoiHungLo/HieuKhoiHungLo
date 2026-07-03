<?php
/**
 * Script Chuẩn hoá dữ liệu hồ sơ năng khiếu sang đợt Ghi danh sớm (Bản tối ưu hiệu năng cao)
 * 
 * Cách chạy:
 *   - Chạy thử nghiệm (không lưu vào DB): php scripts/standardize_talent_records.php --dry-run
 *   - Chạy chính thức: php scripts/standardize_talent_records.php
 */

if (php_sapi_name() !== 'cli') {
    die("Script này chỉ có thể chạy từ môi trường dòng lệnh (CLI).\n");
}

mb_internal_encoding('UTF-8');

define('CLR_RESET', "\033[0m");
define('CLR_RED', "\033[31m");
define('CLR_GREEN', "\033[32m");
define('CLR_YELLOW', "\033[33m");
define('CLR_CYAN', "\033[36m");
define('CLR_BOLD', "\033[1m");

echo CLR_BOLD . CLR_CYAN . "============================================================\n";
echo "   TIẾN TRÌNH CHUẨN HOÁ DỮ LIỆU HỒ SƠ NĂNG KHIẾU (TỐI ƯU PL/PGSQL)\n";
echo "============================================================\n" . CLR_RESET;

// ── 1. Khởi tạo cấu hình và kết nối ──────────────────────────────────────────
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}

require_once __DIR__ . '/../app/Core/Database.php';

$dryRun = in_array('--dry-run', $argv);
if ($dryRun) {
    echo CLR_YELLOW . CLR_BOLD . "⚠️ ĐANG CHẠY Ở CHẾ ĐỘ THỬ NGHIỆM (--dry-run). SẼ KHÔNG CÓ THAY ĐỔI NÀO ĐƯỢC LƯU VÀO CSDL!\n\n" . CLR_RESET;
}

try {
    $dbInstance = \App\Core\Database::getInstance();
    $dbInstance->setSystemRole('admin'); // Bypass RLS
    $db = $dbInstance->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Kết nối CSDL thành công tới host: " . CLR_GREEN . ($_ENV['DB_HOST'] ?? '127.0.0.1') . CLR_RESET . "\n\n";
} catch (PDOException $e) {
    echo CLR_RED . "❌ Lỗi kết nối CSDL: " . $e->getMessage() . "\n" . CLR_RESET;
    exit(1);
}

// ── 2. Đếm thống kê trước khi chạy (Dùng SQL gộp cực nhanh) ─────────────────
try {
    $sessionAId = 5;
    $sessionBId = 3;

    // Đếm tổng hồ sơ nguồn A
    $totalA = $db->query("SELECT COUNT(*) FROM ho_so_xet_tuyen WHERE dot_tuyen_sinh_id = $sessionAId AND deleted_at IS NULL")->fetchColumn();
    
    // Đếm Case 1
    $case1Count = $db->query("
        SELECT COUNT(*) FROM ho_so_xet_tuyen hs 
        WHERE hs.dot_tuyen_sinh_id = $sessionAId AND hs.deleted_at IS NULL
          AND NOT EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = hs.so_cccd AND hs2.dot_tuyen_sinh_id = $sessionBId AND hs2.deleted_at IS NULL)
    ")->fetchColumn();

    // Đếm Case 2
    $case2Count = $db->query("
        SELECT COUNT(*) FROM ho_so_xet_tuyen hs 
        WHERE hs.dot_tuyen_sinh_id = $sessionAId AND hs.deleted_at IS NULL
          AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = hs.so_cccd AND hs2.dot_tuyen_sinh_id = $sessionBId AND hs2.deleted_at IS NULL)
          AND NOT EXISTS (
              SELECT 1 FROM nguyen_vong nv 
              WHERE nv.ho_so_id = (SELECT id FROM ho_so_xet_tuyen WHERE so_cccd = hs.so_cccd AND dot_tuyen_sinh_id = $sessionBId AND deleted_at IS NULL LIMIT 1)
                AND nv.ma_nganh IN ('7140201', '7140206', '7140221', '7140222')
          )
    ")->fetchColumn();

    // Đếm Case 3
    $case3Count = $db->query("
        SELECT COUNT(*) FROM ho_so_xet_tuyen hs 
        WHERE hs.dot_tuyen_sinh_id = $sessionAId AND hs.deleted_at IS NULL
          AND EXISTS (SELECT 1 FROM ho_so_xet_tuyen hs2 WHERE hs2.so_cccd = hs.so_cccd AND hs2.dot_tuyen_sinh_id = $sessionBId AND hs2.deleted_at IS NULL)
          AND EXISTS (
              SELECT 1 FROM nguyen_vong nv 
              WHERE nv.ho_so_id = (SELECT id FROM ho_so_xet_tuyen WHERE so_cccd = hs.so_cccd AND dot_tuyen_sinh_id = $sessionBId AND deleted_at IS NULL LIMIT 1)
                AND nv.ma_nganh IN ('7140201', '7140206', '7140221', '7140222')
          )
    ")->fetchColumn();

    echo "Thống kê hồ sơ cần xử lý:\n";
    echo "  - Tổng hồ sơ đợt Năng khiếu: " . CLR_BOLD . $totalA . CLR_RESET . "\n";
    echo "  - Số hồ sơ chuyển đợt (Trường hợp 1) : " . CLR_GREEN . $case1Count . CLR_RESET . "\n";
    echo "  - Số hồ sơ gộp NV (Trường hợp 2)     : " . CLR_YELLOW . $case2Count . CLR_RESET . "\n";
    echo "  - Số hồ sơ xoá trùng (Trường hợp 3)  : " . CLR_RED . $case3Count . CLR_RESET . "\n";
    echo str_repeat('=', 80) . "\n";

    if ($totalA == 0) {
        echo CLR_GREEN . "Không có hồ sơ nào cần chuyển đổi. Hệ thống đã sạch!\n" . CLR_RESET;
        exit(0);
    }

    echo "Đang bắt đầu thực thi giao dịch SQL trên Supabase...\n";
    $startTime = microtime(true);

    $db->beginTransaction();

    // PL/pgSQL Code Block chạy trực tiếp trong DB
    $plpgsql = "
    DO $$
    DECLARE
        rec RECORD;
        hosoB_id INT;
        maxOrderB INT;
        wish RECORD;
        dupWishId INT;
        score RECORD;
        existingScoreB RECORD;
        cert RECORD;
        existingCertB RECORD;
        hasTalentB BOOLEAN;
    BEGIN
        -- Loop through all active applications in session A (5)
        FOR rec IN 
            SELECT hs.id AS hosoA_id, hs.so_cccd 
            FROM ho_so_xet_tuyen hs
            WHERE hs.dot_tuyen_sinh_id = 5
              AND hs.deleted_at IS NULL
        LOOP
            -- Check if candidate has an active application in session B (3)
            SELECT id INTO hosoB_id FROM ho_so_xet_tuyen WHERE so_cccd = rec.so_cccd AND dot_tuyen_sinh_id = 3 AND deleted_at IS NULL LIMIT 1;
            
            IF hosoB_id IS NULL THEN
                -- CASE 1: No application in session B -> Move session
                UPDATE ho_so_xet_tuyen SET dot_tuyen_sinh_id = 3, updated_at = NOW() WHERE id = rec.hosoA_id;
                UPDATE nguyen_vong SET dot_tuyen_sinh_id = 3 WHERE ho_so_id = rec.hosoA_id;
                UPDATE diem_nang_khieu SET dot_tuyen_sinh_id = 3, updated_at = NOW() WHERE so_cccd = rec.so_cccd AND dot_tuyen_sinh_id = 5;
                UPDATE diem_chung_chi SET dot_tuyen_sinh_id = 3, updated_at = NOW() WHERE so_cccd = rec.so_cccd AND dot_tuyen_sinh_id = 5;
                
            ELSE
                -- Candidate has application in B. Check if B has any talent wishes
                SELECT EXISTS (
                    SELECT 1 FROM nguyen_vong 
                    WHERE ho_so_id = hosoB_id 
                      AND ma_nganh IN ('7140201', '7140206', '7140221', '7140222')
                ) INTO hasTalentB;
                
                IF NOT hasTalentB THEN
                    -- CASE 2: Has B application but no talent wishes -> Move wishes and merge scores
                    SELECT COALESCE(MAX(thu_tu_nguyen_vong), 0) INTO maxOrderB FROM nguyen_vong WHERE ho_so_id = hosoB_id;
                    
                    FOR wish IN SELECT * FROM nguyen_vong WHERE ho_so_id = rec.hosoA_id ORDER BY thu_tu_nguyen_vong ASC LOOP
                        -- Check if duplicate wish exists in B
                        SELECT id INTO dupWishId FROM nguyen_vong 
                        WHERE ho_so_id = hosoB_id 
                          AND ma_nganh = wish.ma_nganh 
                          AND ma_phuong_thuc = wish.ma_phuong_thuc 
                          AND to_hop_mon = wish.to_hop_mon
                        LIMIT 1;
                          
                        IF dupWishId IS NOT NULL THEN
                            -- Delete duplicate wish from A
                            DELETE FROM nguyen_vong WHERE id = wish.id;
                        ELSE
                            maxOrderB := maxOrderB + 1;
                            UPDATE nguyen_vong 
                            SET ho_so_id = hosoB_id, 
                                dot_tuyen_sinh_id = 3, 
                                thu_tu_nguyen_vong = maxOrderB 
                            WHERE id = wish.id;
                        END IF;
                    END LOOP;
                    
                    -- Merge scores
                    -- 1. Năng khiếu
                    FOR score IN SELECT * FROM diem_nang_khieu WHERE so_cccd = rec.so_cccd AND dot_tuyen_sinh_id = 5 LOOP
                        SELECT id, diem INTO existingScoreB FROM diem_nang_khieu WHERE so_cccd = rec.so_cccd AND ma_mon = score.ma_mon AND dot_tuyen_sinh_id = 3 LIMIT 1;
                        IF FOUND THEN
                            IF score.diem > existingScoreB.diem THEN
                                UPDATE diem_nang_khieu SET diem = score.diem, updated_at = NOW() WHERE id = existingScoreB.id;
                            END IF;
                            DELETE FROM diem_nang_khieu WHERE id = score.id;
                        ELSE
                            UPDATE diem_nang_khieu SET dot_tuyen_sinh_id = 3, updated_at = NOW() WHERE id = score.id;
                        END IF;
                    END LOOP;
                    
                    -- 2. Chứng chỉ
                    FOR cert IN SELECT * FROM diem_chung_chi WHERE so_cccd = rec.so_cccd AND dot_tuyen_sinh_id = 5 LOOP
                        SELECT id, diem INTO existingCertB FROM diem_chung_chi WHERE so_cccd = rec.so_cccd AND ma_mon = cert.ma_mon AND dot_tuyen_sinh_id = 3 LIMIT 1;
                        IF FOUND THEN
                            IF cert.diem > existingCertB.diem THEN
                                UPDATE diem_chung_chi SET diem = cert.diem, updated_at = NOW() WHERE id = existingCertB.id;
                            END IF;
                            DELETE FROM diem_chung_chi WHERE id = cert.id;
                        ELSE
                            UPDATE diem_chung_chi SET dot_tuyen_sinh_id = 3, updated_at = NOW() WHERE id = cert.id;
                        END IF;
                    END LOOP;
                    
                    -- Delete application A
                    DELETE FROM ho_so_xet_tuyen WHERE id = rec.hosoA_id;
                    
                ELSE
                    -- CASE 3: Has B application and already has talent wishes -> Merge scores and delete A application
                    -- Merge scores
                    -- 1. Năng khiếu
                    FOR score IN SELECT * FROM diem_nang_khieu WHERE so_cccd = rec.so_cccd AND dot_tuyen_sinh_id = 5 LOOP
                        SELECT id, diem INTO existingScoreB FROM diem_nang_khieu WHERE so_cccd = rec.so_cccd AND ma_mon = score.ma_mon AND dot_tuyen_sinh_id = 3 LIMIT 1;
                        IF FOUND THEN
                            IF score.diem > existingScoreB.diem THEN
                                UPDATE diem_nang_khieu SET diem = score.diem, updated_at = NOW() WHERE id = existingScoreB.id;
                            END IF;
                            DELETE FROM diem_nang_khieu WHERE id = score.id;
                        ELSE
                            UPDATE diem_nang_khieu SET dot_tuyen_sinh_id = 3, updated_at = NOW() WHERE id = score.id;
                        END IF;
                    END LOOP;
                    
                    -- 2. Chứng chỉ
                    FOR cert IN SELECT * FROM diem_chung_chi WHERE so_cccd = rec.so_cccd AND dot_tuyen_sinh_id = 5 LOOP
                        SELECT id, diem INTO existingCertB FROM diem_chung_chi WHERE so_cccd = rec.so_cccd AND ma_mon = cert.ma_mon AND dot_tuyen_sinh_id = 3 LIMIT 1;
                        IF FOUND THEN
                            IF cert.diem > existingCertB.diem THEN
                                UPDATE diem_chung_chi SET diem = cert.diem, updated_at = NOW() WHERE id = existingCertB.id;
                            END IF;
                            DELETE FROM diem_chung_chi WHERE id = cert.id;
                        ELSE
                            UPDATE diem_chung_chi SET dot_tuyen_sinh_id = 3, updated_at = NOW() WHERE id = cert.id;
                        END IF;
                    END LOOP;
                    
                    -- Delete application A (wishes deleted by foreign key cascade)
                    DELETE FROM ho_so_xet_tuyen WHERE id = rec.hosoA_id;
                    
                END IF;
            END IF;
        END LOOP;
    END $$;
    ";

    $db->exec($plpgsql);

    if ($dryRun) {
        $db->rollBack();
        $elapsed = round(microtime(true) - $startTime, 4);
        echo "\n" . CLR_YELLOW . CLR_BOLD . "⚠️ Chạy ở chế độ DRY-RUN: Đã thực thi giả lập trong $elapsed giây và rollback toàn bộ thay đổi thành công. CSDL an toàn!\n" . CLR_RESET;
    } else {
        $db->commit();
        $elapsed = round(microtime(true) - $startTime, 4);
        echo "\n" . CLR_GREEN . CLR_BOLD . "✅ ĐÃ LƯU THAY ĐỔI VÀO CƠ SỞ DỮ LIỆU THÀNH CÔNG! Thời gian chạy: $elapsed giây.\n" . CLR_RESET;
    }

} catch (Exception $e) {
    $db->rollBack();
    echo "\n" . CLR_RED . CLR_BOLD . "❌ ĐÃ XẢY RA LỖI. ĐÃ ROLLBACK TOÀN BỘ GIAO DỊCH: " . $e->getMessage() . "\n" . CLR_RESET;
    exit(1);
}
