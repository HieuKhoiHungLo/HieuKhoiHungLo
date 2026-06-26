<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách phòng thi</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 13pt; margin: 0; padding: 20px; }
        .page-break { page-break-after: always; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mb-4 { margin-bottom: 20px; }
        .mt-4 { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #000; padding: 6px 4px; text-align: left; }
        th { text-align: center; font-weight: bold; }
        .w-5 { width: 5%; }
        .w-10 { width: 10%; }
        .w-15 { width: 15%; }
        .w-20 { width: 20%; }
        .w-25 { width: 25%; }
        .w-30 { width: 30%; }
        .header-table { width: 100%; border: none; margin-bottom: 20px; }
        .header-table td { border: none; padding: 0; vertical-align: top; }
        .signature-table { width: 100%; border: none; margin-top: 30px; }
        .signature-table td { border: none; padding: 0; text-align: center; }
        @media print {
            body { padding: 0; }
            button.print-btn { display: none; }
        }
        button.print-btn {
            position: fixed; top: 20px; right: 20px; padding: 10px 20px; 
            background: #2563eb; color: #fff; border: none; border-radius: 5px; 
            cursor: pointer; font-size: 16px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨 In Danh Sách</button>

    <?php if (empty($rooms)): ?>
        <h2 class="text-center mt-4">Chưa có phòng thi nào được tạo.</h2>
    <?php else: ?>
        <?php foreach ($rooms as $index => $room): ?>
            <?php 
                $db = App\Core\Database::getInstance()->getConnection();
                $stmt = $db->prepare("
                    SELECT a.*, c.ho_va_ten AS name, c.ngay_sinh AS birth_date, c.so_cccd AS cccd, 
                           s.subject_name, s.major_code
                    FROM talent_test_assignments a
                    JOIN thi_sinh c ON c.id = a.candidate_id
                    JOIN talent_test_subjects s ON s.id = a.subject_id
                    WHERE a.room_id = ? AND a.is_eligible = TRUE
                    ORDER BY a.exam_number ASC
                ");
                $stmt->execute([$room['id']]);
                $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="<?= $index < count($rooms) - 1 ? 'page-break' : '' ?>">
                <table class="header-table">
                    <tr>
                        <td style="width: 40%; text-align: center;">
                            <div class="uppercase">TRƯỜNG ĐẠI HỌC HÙNG VƯƠNG</div>
                            <div class="font-bold">HỘI ĐỒNG TUYỂN SINH NĂM <?= $session['year'] ?></div>
                            <div style="border-bottom: 1px solid #000; width: 60%; margin: 5px auto;"></div>
                        </td>
                        <td style="width: 60%; text-align: center;">
                            <div class="font-bold uppercase">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                            <div class="font-bold">Độc lập - Tự do - Hạnh phúc</div>
                            <div style="border-bottom: 1px solid #000; width: 40%; margin: 5px auto;"></div>
                        </td>
                    </tr>
                </table>

                <h2 class="text-center uppercase font-bold mb-1">DANH SÁCH THÍ SINH DỰ THI NĂNG KHIẾU</h2>
                <div class="text-center font-bold mb-4">PHÒNG THI SỐ: <?= htmlspecialchars($room['room_name']) ?></div>
                <div class="mb-2"><strong>Đợt thi:</strong> <?= htmlspecialchars($session['session_name']) ?></div>
                <div class="mb-2"><strong>Số lượng thí sinh:</strong> <?= count($candidates) ?> thí sinh</div>

                <table>
                    <thead>
                        <tr>
                            <th class="w-5">STT</th>
                            <th class="w-10">SBD</th>
                            <th class="w-20">Họ và tên</th>
                            <th class="w-10">Ngày sinh</th>
                            <th class="w-15">Số CCCD</th>
                            <th class="w-20">Ngành dự thi</th>
                            <th class="w-10">Ký tên</th>
                            <th class="w-10">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($candidates)): ?>
                            <tr><td colspan="8" class="text-center">Không có thí sinh trong phòng này</td></tr>
                        <?php else: ?>
                            <?php foreach ($candidates as $i => $c): ?>
                                <tr>
                                    <td class="text-center"><?= $i + 1 ?></td>
                                    <td class="text-center font-bold"><?= htmlspecialchars($c['exam_number'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($c['name']) ?></td>
                                    <td class="text-center"><?= $c['birth_date'] ? date('d/m/Y', strtotime($c['birth_date'])) : '' ?></td>
                                    <td class="text-center"><?= htmlspecialchars($c['cccd']) ?></td>
                                    <td><?= htmlspecialchars($c['subject_name']) ?></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <table class="signature-table mt-4">
                    <tr>
                        <td style="width: 50%;"></td>
                        <td style="width: 50%;">
                            <div class="mb-1"><em>Phú Thọ, ngày ...... tháng ...... năm <?= $session['year'] ?></em></div>
                            <div class="font-bold mb-4">CÁN BỘ COI THI<br>(Ký và ghi rõ họ tên)</div>
                            <br><br><br>
                        </td>
                    </tr>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
