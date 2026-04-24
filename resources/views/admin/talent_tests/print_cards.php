<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Thẻ dự thi - <?= htmlspecialchars($session['session_name']) ?></title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            margin: 0;
            padding: 0;
            background: #f0f0f0;
        }
        .no-print-area {
            background: #fff;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .btn-print {
            padding: 10px 25px;
            background: #0066ff;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,102,255,0.3);
        }
        .page {
            width: 210mm;
            height: 297mm;
            padding: 5mm;
            margin: 10mm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
            box-sizing: border-box;
        }
        .card {
            width: 95mm;
            height: 135mm;
            border: 1px dashed #ccc;
            margin: 2mm;
            padding: 5mm;
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .header img {
            height: 40px;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 10pt;
            margin: 0;
            text-transform: uppercase;
            color: #d32f2f;
        }
        .header p {
            font-size: 8pt;
            margin: 2px 0;
            font-weight: bold;
        }
        .title {
            text-align: center;
            font-size: 14pt;
            font-bold;
            margin: 10px 0;
            text-transform: uppercase;
        }
        .content {
            display: flex;
            gap: 10px;
        }
        .photo-box {
            width: 30mm;
            height: 40mm;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
        }
        .photo-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        .info {
            flex: 1;
            font-size: 10pt;
        }
        .info table {
            width: 100%;
        }
        .info td {
            padding: 3px 0;
            vertical-align: top;
        }
        .label {
            width: 65px;
            font-style: italic;
        }
        .value {
            font-weight: bold;
        }
        .exam-number {
            margin-top: 15px;
            text-align: center;
            border: 2px solid #000;
            padding: 5px;
            font-size: 16pt;
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
        }
        .footer {
            margin-top: 15px;
            font-size: 8pt;
            display: flex;
            justify-content: space-between;
        }
        .footer .qr-placeholder {
            width: 50px;
            height: 50px;
            border: 1px solid #eee;
            background: #fcfcfc;
        }

        @media print {
            .no-print-area { display: none; }
            body { background: white; }
            .page { 
                margin: 0; 
                box-shadow: none;
                page-break-after: always;
            }
            .card { border: 1px solid #ddd; }
        }
    </style>
</head>
<body>

    <div class="no-print-area">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> XÁC NHẬN IN TẤT CẢ (<?= count($assignments) ?> THẺ)
        </button>
        <button class="btn-print" style="background: #666;" onclick="window.close()">ĐÓNG</button>
    </div>

    <?php 
    $perPage = 4;
    $chunks = array_chunk($assignments, $perPage);
    foreach ($chunks as $chunk): 
    ?>
    <div class="page">
        <?php foreach ($chunk as $a): ?>
        <div class="card">
            <div class="header">
                <img src="<?= url('/public/images/logo.png') ?>" alt="Logo">
                <h2>Trường Đại học Hùng Vương</h2>
                <p>HỘI ĐỒNG TUYỂN SINH NĂM <?= $session['year'] ?></p>
            </div>
            
            <div class="title">THẺ DỰ THI NĂNG KHIẾU</div>
            
            <div class="content">
                <div class="photo-box">
                    <?php 
                        $photoPath = "/public/uploads/candidates/" . $a['cccd'] . ".jpg";
                        // Kiểm tra ảnh thí sinh, nếu không có dùng ảnh mặc định
                    ?>
                    <img src="<?= url($photoPath) ?>" onerror="this.src='<?= url('/public/images/default-avatar.png') ?>'">
                </div>
                <div class="info">
                    <table>
                        <tr>
                            <td class="label">Họ tên:</td>
                            <td class="value"><?= htmlspecialchars($a['name']) ?></td>
                        </tr>
                        <tr>
                            <td class="label">Ngày sinh:</td>
                            <td class="value"><?= date('d/m/Y', strtotime($a['birth_date'] ?? 'now')) ?></td>
                        </tr>
                        <tr>
                            <td class="label">CCCD:</td>
                            <td class="value"><?= htmlspecialchars($a['cccd']) ?></td>
                        </tr>
                        <tr>
                            <td class="label">Ngành thi:</td>
                            <td class="value"><?= htmlspecialchars($a['subject_name']) ?></td>
                        </tr>
                        <tr>
                            <td class="label">Phòng thi:</td>
                            <td class="value"><?= htmlspecialchars($a['room_name'] ?: 'Chưa phân') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="exam-number">
                SBD: <?= htmlspecialchars($a['exam_number']) ?>
            </div>

            <div class="footer">
                <div>
                    <i>Ngày in: <?= date('d/m/Y') ?></i><br>
                    <b>Hội đồng tuyển sinh</b>
                </div>
                <div class="qr-placeholder">
                    <!-- QR code thí sinh nếu cần -->
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

</body>
</html>
