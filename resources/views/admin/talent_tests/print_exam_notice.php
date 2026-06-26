<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giấy Báo Dự Thi Năng Khiếu</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 13pt; margin: 0; padding: 20px; background: #f1f5f9; }
        .page-break { page-break-after: always; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mb-4 { margin-bottom: 20px; }
        .mt-4 { margin-top: 20px; }
        
        .card { 
            width: 18cm; 
            height: 12cm; 
            margin: 0 auto 20px auto; 
            background: #fff; 
            border: 2px solid #1e3a8a; /* Dark blue border */
            padding: 20px; 
            box-sizing: border-box;
            position: relative;
        }
        
        .header-table { width: 100%; border: none; margin-bottom: 15px; }
        .header-table td { border: none; padding: 0; vertical-align: top; }
        
        .title { 
            text-align: center; 
            font-size: 18pt; 
            font-weight: bold; 
            color: #b91c1c; /* Red */
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 30%;
            padding: 5px 0;
            font-weight: bold;
        }
        .info-value {
            display: table-cell;
            width: 70%;
            padding: 5px 0;
            border-bottom: 1px dotted #94a3b8;
        }

        .exam-info {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
        }
        
        .signature-area {
            float: right;
            text-align: center;
            width: 40%;
            margin-top: 10px;
        }

        @media print {
            body { padding: 0; background: #fff; }
            .card { 
                margin: 0; 
                border: none; 
                box-shadow: none; 
                page-break-after: always;
                height: auto;
                min-height: 14cm;
            }
            button.print-btn { display: none; }
        }
        button.print-btn {
            position: fixed; top: 20px; right: 20px; padding: 10px 20px; 
            background: #2563eb; color: #fff; border: none; border-radius: 5px; 
            cursor: pointer; font-size: 16px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 100;
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨 In Giấy Báo</button>

    <?php if (empty($assignments)): ?>
        <h2 class="text-center mt-4">Chưa có thí sinh đủ điều kiện để in.</h2>
    <?php else: ?>
        <?php foreach ($assignments as $c): ?>
            <div class="card">
                <table class="header-table">
                    <tr>
                        <td style="width: 40%; text-align: center;">
                            <div class="uppercase text-sm" style="font-size: 11pt;">BỘ GIÁO DỤC VÀ ĐÀO TẠO</div>
                            <div class="font-bold" style="font-size: 12pt;">TRƯỜNG ĐẠI HỌC HÙNG VƯƠNG</div>
                            <div style="border-bottom: 1px solid #000; width: 60%; margin: 5px auto;"></div>
                        </td>
                        <td style="width: 60%; text-align: center;">
                            <div class="font-bold uppercase" style="font-size: 11pt;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                            <div class="font-bold" style="font-size: 12pt;">Độc lập - Tự do - Hạnh phúc</div>
                            <div style="border-bottom: 1px solid #000; width: 40%; margin: 5px auto;"></div>
                        </td>
                    </tr>
                </table>

                <div class="title">GIẤY BÁO DỰ THI NĂNG KHIẾU</div>

                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Họ và tên thí sinh:</div>
                        <div class="info-value font-bold uppercase"><?= htmlspecialchars($c['name']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Ngày sinh:</div>
                        <div class="info-value"><?= $c['birth_date'] ? date('d/m/Y', strtotime($c['birth_date'])) : '' ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Số CCCD:</div>
                        <div class="info-value"><?= htmlspecialchars($c['cccd']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Ngành dự thi:</div>
                        <div class="info-value font-bold text-blue-800"><?= htmlspecialchars($c['subject_name']) ?></div>
                    </div>
                </div>

                <div class="exam-info">
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td style="width: 50%;"><strong>Số Báo Danh:</strong> <span style="font-size: 14pt; color: red;" class="font-bold"><?= htmlspecialchars($c['exam_number'] ?? 'Chưa cấp') ?></span></td>
                            <td style="width: 50%;"><strong>Phòng thi:</strong> <span class="font-bold"><?= htmlspecialchars($c['room_name'] ?? 'Chưa xếp phòng') ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding-top: 10px;"><strong>Ngày thi:</strong> <?= $c['exam_date'] ? date('d/m/Y', strtotime($c['exam_date'])) : 'Chưa xếp lịch' ?></td>
                            <td style="padding-top: 10px;"><strong>Giờ có mặt:</strong> <?= $c['exam_time'] ? date('H:i', strtotime($c['exam_time'])) : 'Chưa xếp lịch' ?></td>
                        </tr>
                    </table>
                </div>

                <div class="signature-area">
                    <div class="mb-1" style="font-size: 11pt;"><em>Phú Thọ, ngày ...... tháng ...... năm <?= $session['year'] ?></em></div>
                    <div class="font-bold">CHỦ TỊCH HỘI ĐỒNG TUYỂN SINH</div>
                    <br><br><br>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
