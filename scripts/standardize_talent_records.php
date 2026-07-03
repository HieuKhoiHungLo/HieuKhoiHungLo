<?php
/**
 * Script Chuẩn hoá dữ liệu hồ sơ năng khiếu sang đợt Ghi danh sớm
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
echo "   TIẾN TRÌNH CHUẨN HOÁ DỮ LIỆU HỒ SƠ NĂNG KHIẾU (LOCALHOST/SUPABASE)\n";
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

// ── 2. Xác định ID các đợt tuyển sinh ───────────────────────────────────────
$sessionA_Name = 'Bổ sung hồ sơ năng khiếu';
$sessionB_Name = 'Ghi danh sớm';

$stmtSession = $db->prepare("SELECT id, ten_dot FROM dot_tuyen_sinh WHERE ten_dot = ?");
$stmtSession->execute([$sessionA_Name]);
$sessionA = $stmtSession->fetch(PDO::FETCH_ASSOC);

$stmtSession->execute([$sessionB_Name]);
$sessionB = $stmtSession->fetch(PDO::FETCH_ASSOC);

if (!$sessionA) {
    echo CLR_RED . "❌ Lỗi: Không tìm thấy đợt tuyển sinh nguồn '$sessionA_Name' trong DB!\n" . CLR_RESET;
    exit(1);
}
if (!$sessionB) {
    echo CLR_RED . "❌ Lỗi: Không tìm thấy đợt tuyển sinh đích '$sessionB_Name' trong DB!\n" . CLR_RESET;
    exit(1);
}

$sessionAId = (int)$sessionA['id'];
$sessionBId = (int)$sessionB['id'];

echo CLR_BOLD . "🎯 Đợt nguồn (A): ID $sessionAId - {$sessionA['ten_dot']}\n";
echo "🎯 Đợt đích (B): ID $sessionBId - {$sessionB['ten_dot']}\n\n" . CLR_RESET;

// Các mã ngành năng khiếu
$talentMajors = ['7140201', '7140206', '7140221', '7140222'];

// ── 3. Quét toàn bộ hồ sơ đợt nguồn A ───────────────────────────────────────
$stmtAppsA = $db->prepare("
    SELECT hs.id, hs.so_cccd, ts.ho_va_ten, hs.trang_thai 
    FROM ho_so_xet_tuyen hs
    JOIN thi_sinh ts ON hs.so_cccd = ts.so_cccd
    WHERE hs.dot_tuyen_sinh_id = ?
");
$stmtAppsA->execute([$sessionAId]);
$appsA = $stmtAppsA->fetchAll(PDO::FETCH_ASSOC);

$totalApps = count($appsA);
echo CLR_BOLD . "Tìm thấy $totalApps hồ sơ cần đối chiếu.\n" . CLR_RESET;
echo str_repeat('=', 80) . "\n";

$stats = [
    'case1' => 0, // Chuyển đợt
    'case2' => 0, // Chuyển NV xuống cuối
    'case3' => 0, // Xoá hồ sơ nguồn (đã có đủ NV năng khiếu ở đích)
    'skipped' => 0
];

try {
    $db->beginTransaction();

    // Chuẩn bị các câu lệnh SQL tối ưu
    $stmtCheckB = $db->prepare("SELECT id, trang_thai FROM ho_so_xet_tuyen WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
    $stmtCheckTalentWishesB = $db->prepare("
        SELECT COUNT(*) FROM nguyen_vong 
        WHERE ho_so_id = ? 
          AND ma_nganh IN ('7140201', '7140206', '7140221', '7140222')
    ");
    $stmtMaxOrderB = $db->prepare("SELECT COALESCE(MAX(thu_tu_nguyen_vong), 0) FROM nguyen_vong WHERE ho_so_id = ?");
    $stmtWishesA = $db->prepare("SELECT * FROM nguyen_vong WHERE ho_so_id = ? ORDER BY thu_tu_nguyen_vong ASC");
    
    // SQL cập nhật
    $stmtUpdateAppSession = $db->prepare("UPDATE ho_so_xet_tuyen SET dot_tuyen_sinh_id = ?, updated_at = NOW() WHERE id = ?");
    $stmtUpdateWishSession = $db->prepare("UPDATE nguyen_vong SET dot_tuyen_sinh_id = ? WHERE ho_so_id = ?");
    
    $stmtCheckDupWishB = $db->prepare("
        SELECT id FROM nguyen_vong 
        WHERE ho_so_id = ? 
          AND ma_nganh = ? 
          AND ma_phuong_thuc = ? 
          AND to_hop_mon = ?
    ");
    $stmtMoveWishToB = $db->prepare("
        UPDATE nguyen_vong 
        SET ho_so_id = ?, 
            dot_tuyen_sinh_id = ?, 
            thu_tu_nguyen_vong = ? 
        WHERE id = ?
    ");
    $stmtDeleteWish = $db->prepare("DELETE FROM nguyen_vong WHERE id = ?");
    $stmtDeleteApp = $db->prepare("DELETE FROM ho_so_xet_tuyen WHERE id = ?");

    foreach ($appsA as $idx => $appA) {
        $cccd = $appA['so_cccd'];
        $hoTen = $appA['ho_va_ten'];
        $hosoAId = (int)$appA['id'];
        $stt = $idx + 1;

        // Kiểm tra xem thí sinh đã có hồ sơ trong đợt B chưa
        $stmtCheckB->execute([$cccd, $sessionBId]);
        $rowB = $stmtCheckB->fetch(PDO::FETCH_ASSOC);

        if (!$rowB) {
            // ─────────────────────────────────────────────────────────────────
            // TRƯỜNG HỢP 1: Chưa có hồ sơ trong đợt Ghi danh sớm -> CHUYỂN ĐỢT
            // ─────────────────────────────────────────────────────────────────
            echo CLR_GREEN . "[$stt/$totalApps] Thí sinh: $hoTen ($cccd) -> [TRƯỜNG HỢP 1] Chuyển đợt sang 'Ghi danh sớm'\n" . CLR_RESET;
            
            if (!$dryRun) {
                // 1. Cập nhật hồ sơ xét tuyển
                $stmtUpdateAppSession->execute([$sessionBId, $hosoAId]);
                // 2. Cập nhật các nguyện vọng
                $stmtUpdateWishSession->execute([$sessionBId, $hosoAId]);
                // 3. Chuyển điểm năng khiếu & chứng chỉ (nếu có)
                $db->prepare("UPDATE diem_nang_khieu SET dot_tuyen_sinh_id = ? WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?")
                   ->execute([$sessionBId, $cccd, $sessionAId]);
                $db->prepare("UPDATE diem_chung_chi SET dot_tuyen_sinh_id = ? WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?")
                   ->execute([$sessionBId, $cccd, $sessionAId]);
            }
            $stats['case1']++;

        } else {
            $hosoBId = (int)$rowB['id'];

            // Kiểm tra xem đợt B đã có nguyện vọng năng khiếu nào chưa
            $stmtCheckTalentWishesB->execute([$hosoBId]);
            $hasTalentB = $stmtCheckTalentWishesB->fetchColumn() > 0;

            if (!$hasTalentB) {
                // ─────────────────────────────────────────────────────────────────
                // TRƯỜNG HỢP 2: Đã có hồ sơ đợt B nhưng CHƯA CÓ NV năng khiếu -> CHUYỂN NV
                // ─────────────────────────────────────────────────────────────────
                echo CLR_YELLOW . "[$stt/$totalApps] Thí sinh: $hoTen ($cccd) -> [TRƯỜNG HỢP 2] Gộp nguyện vọng năng khiếu vào cuối đợt 'Ghi danh sớm'\n" . CLR_RESET;
                
                // Lấy thứ tự nguyện vọng lớn nhất ở đợt B
                $stmtMaxOrderB->execute([$hosoBId]);
                $maxOrderB = (int)$stmtMaxOrderB->fetchColumn();

                // Lấy các nguyện vọng ở đợt nguồn A
                $stmtWishesA->execute([$hosoAId]);
                $wishesA = $stmtWishesA->fetchAll(PDO::FETCH_ASSOC);

                foreach ($wishesA as $wishA) {
                    $wishId = $wishA['id'];
                    $maNganh = $wishA['ma_nganh'];
                    $maPT = $wishA['ma_phuong_thuc'];
                    $toHop = $wishA['to_hop_mon'];

                    // Kiểm tra xem đã có nguyện vọng trùng khít ở đợt B chưa
                    $stmtCheckDupWishB->execute([$hosoBId, $maNganh, $maPT, $toHop]);
                    $dupWishId = $stmtCheckDupWishB->fetchColumn();

                    if ($dupWishId) {
                        // Nếu trùng khít, chỉ việc xoá nguyện vọng ở đợt A
                        echo "    - Trùng NV ngành $maNganh ở đợt đích. Xoá NV trùng ở đợt nguồn.\n";
                        if (!$dryRun) {
                            $stmtDeleteWish->execute([$wishId]);
                        }
                    } else {
                        // Nếu chưa trùng, chuyển và xếp xuống cuối
                        $maxOrderB++;
                        echo "    + Chuyển NV ngành $maNganh thành NV thứ $maxOrderB của đợt đích.\n";
                        if (!$dryRun) {
                            $stmtMoveWishToB->execute([$hosoBId, $sessionBId, $maxOrderB, $wishId]);
                        }
                    }
                }

                // Gộp điểm năng khiếu & chứng chỉ từ đợt A sang đợt B
                mergeScores($db, $cccd, $sessionAId, $sessionBId, $dryRun);

                // Xoá hồ sơ xét tuyển ở đợt nguồn A
                if (!$dryRun) {
                    $stmtDeleteApp->execute([$hosoAId]);
                }
                $stats['case2']++;

            } else {
                // ─────────────────────────────────────────────────────────────────
                // TRƯỜNG HỢP 3: Đã có hồ sơ đợt B VÀ ĐÃ CÓ NV năng khiếu ở đợt B -> XOÁ HS NGUỒN
                // ─────────────────────────────────────────────────────────────────
                echo CLR_RED . "[$stt/$totalApps] Thí sinh: $hoTen ($cccd) -> [TRƯỜNG HỢP 3] Đã có NV năng khiếu ở đợt đích. Xoá hồ sơ đợt nguồn A\n" . CLR_RESET;
                
                // Gộp điểm năng khiếu & chứng chỉ (nếu có) từ đợt A sang đợt B
                mergeScores($db, $cccd, $sessionAId, $sessionBId, $dryRun);

                // Xoá hồ sơ xét tuyển ở đợt nguồn A (các nguyện vọng con tự động bị xoá theo CASCADE)
                if (!$dryRun) {
                    $stmtDeleteApp->execute([$hosoAId]);
                }
                $stats['case3']++;
            }
        }
    }

    if ($dryRun) {
        $db->rollBack();
        echo "\n" . CLR_YELLOW . CLR_BOLD . "⚠️ Chạy ở chế độ DRY-RUN: Đã rollback toàn bộ thay đổi. CSDL an toàn.\n" . CLR_RESET;
    } else {
        $db->commit();
        echo "\n" . CLR_GREEN . CLR_BOLD . "✅ ĐÃ LƯU THAY ĐỔI VÀO CƠ SỞ DỮ LIỆU THÀNH CÔNG!\n" . CLR_RESET;
    }

} catch (Exception $e) {
    $db->rollBack();
    echo "\n" . CLR_RED . CLR_BOLD . "❌ ĐÃ XẢY RA LỖI. ĐÃ ROLLBACK TOÀN BỘ GIAO DỊCH: " . $e->getMessage() . "\n" . CLR_RESET;
    exit(1);
}

// ── 4. Hàm bổ trợ gộp điểm ──────────────────────────────────────────────────
function mergeScores(PDO $db, string $cccd, int $sessionAId, int $sessionBId, bool $dryRun): void {
    // 1. Điểm năng khiếu
    $stmtScoresA = $db->prepare("SELECT * FROM diem_nang_khieu WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
    $stmtScoresA->execute([$cccd, $sessionAId]);
    $scoresA = $stmtScoresA->fetchAll(PDO::FETCH_ASSOC);

    $stmtCheckScoreB = $db->prepare("SELECT id, diem FROM diem_nang_khieu WHERE so_cccd = ? AND ma_mon = ? AND dot_tuyen_sinh_id = ?");
    $stmtUpdateScoreB = $db->prepare("UPDATE diem_nang_khieu SET diem = ?, updated_at = NOW() WHERE id = ?");
    $stmtDeleteScoreA = $db->prepare("DELETE FROM diem_nang_khieu WHERE id = ?");
    $stmtMoveScoreToB = $db->prepare("UPDATE diem_nang_khieu SET dot_tuyen_sinh_id = ?, updated_at = NOW() WHERE id = ?");

    foreach ($scoresA as $scoreA) {
        $maMon = $scoreA['ma_mon'];
        $diemA = (float)$scoreA['diem'];

        $stmtCheckScoreB->execute([$cccd, $maMon, $sessionBId]);
        $existingB = $stmtCheckScoreB->fetch(PDO::FETCH_ASSOC);

        if ($existingB) {
            $diemB = (float)$existingB['diem'];
            if ($diemA > $diemB) {
                echo "    + Cập nhật điểm môn $maMon cao hơn ở đợt đích ($diemA > $diemB)\n";
                if (!$dryRun) $stmtUpdateScoreB->execute([$diemA, $existingB['id']]);
            }
            if (!$dryRun) $stmtDeleteScoreA->execute([$scoreA['id']]);
        } else {
            echo "    + Di chuyển điểm môn $maMon sang đợt đích\n";
            if (!$dryRun) $stmtMoveScoreToB->execute([$sessionBId, $scoreA['id']]);
        }
    }

    // 2. Điểm chứng chỉ ngoại ngữ
    $stmtCertsA = $db->prepare("SELECT * FROM diem_chung_chi WHERE so_cccd = ? AND dot_tuyen_sinh_id = ?");
    $stmtCertsA->execute([$cccd, $sessionAId]);
    $certsA = $stmtCertsA->fetchAll(PDO::FETCH_ASSOC);

    $stmtCheckCertB = $db->prepare("SELECT id, diem FROM diem_chung_chi WHERE so_cccd = ? AND ma_mon = ? AND dot_tuyen_sinh_id = ?");
    $stmtUpdateCertB = $db->prepare("UPDATE diem_chung_chi SET diem = ?, updated_at = NOW() WHERE id = ?");
    $stmtDeleteCertA = $db->prepare("DELETE FROM diem_chung_chi WHERE id = ?");
    $stmtMoveCertToB = $db->prepare("UPDATE diem_chung_chi SET dot_tuyen_sinh_id = ?, updated_at = NOW() WHERE id = ?");

    foreach ($certsA as $certA) {
        $maMon = $certA['ma_mon'];
        $diemA = (float)$certA['diem'];

        $stmtCheckCertB->execute([$cccd, $maMon, $sessionBId]);
        $existingB = $stmtCheckCertB->fetch(PDO::FETCH_ASSOC);

        if ($existingB) {
            $diemB = (float)$existingB['diem'];
            if ($diemA > $diemB) {
                echo "    + Cập nhật điểm chứng chỉ $maMon cao hơn ở đợt đích ($diemA > $diemB)\n";
                if (!$dryRun) $stmtUpdateCertB->execute([$diemA, $existingB['id']]);
            }
            if (!$dryRun) $stmtDeleteCertA->execute([$certA['id']]);
        } else {
            echo "    + Di chuyển điểm chứng chỉ $maMon sang đợt đích\n";
            if (!$dryRun) $stmtMoveCertToB->execute([$sessionBId, $certA['id']]);
        }
    }
}

// ── 5. Thống kê kết quả ─────────────────────────────────────────────────────
echo str_repeat('=', 80) . "\n";
echo CLR_BOLD . CLR_CYAN . "KẾT QUẢ TIẾN TRÌNH CHUẨN HOÁ:\n" . CLR_RESET;
echo "  - Tổng hồ sơ đã quét                : $totalApps\n";
echo "  - Số hồ sơ chuyển đợt (Trường hợp 1) : " . CLR_GREEN . CLR_BOLD . $stats['case1'] . CLR_RESET . "\n";
echo "  - Số hồ sơ gộp NV (Trường hợp 2)     : " . CLR_YELLOW . CLR_BOLD . $stats['case2'] . CLR_RESET . "\n";
echo "  - Số hồ sơ xoá trùng (Trường hợp 3)  : " . CLR_RED . CLR_BOLD . $stats['case3'] . CLR_RESET . "\n";
echo "============================================================\n";
