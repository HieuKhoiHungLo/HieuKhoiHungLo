<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Sổ ảnh - <?= htmlspecialchars($session['session_name']) ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm 10mm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            margin: 0;
            padding: 0;
            background: #f4f4f4;
        }
        .no-print-area {
            background: #333;
            padding: 15px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .btn-print {
            padding: 10px 25px;
            background: #22c55e;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 10mm;
            margin: 10mm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 16pt;
            margin: 0;
            text-transform: uppercase;
        }
        .header p {
            font-size: 11pt;
            margin: 5px 0;
        }
        .room-info {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .student-item {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: center;
            box-sizing: border-box;
        }
        .photo {
            width: 35mm;
            height: 45mm;
            margin: 0 auto 5px;
            border: 1px solid #eee;
            background: #fafafa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        .sbd {
            font-family: monospace;
            font-weight: bold;
            font-size: 12pt;
            background: #f0f0f0;
            display: block;
            margin-bottom: 3px;
        }
        .name {
            font-weight: bold;
            font-size: 10pt;
            display: block;
            height: 2.4em;
            line-height: 1.2em;
            overflow: hidden;
        }
        .dob {
            font-size: 8pt;
            color: #666;
        }

        @media print {
            .no-print-area { display: none; }
            body { background: white; }
            .page { 
                margin: 0; 
                box-shadow: none;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-area">
        <button class="btn-print" onclick="window.print()">IN SỔ ẢNH THEO PHÒNG</button>
    </div>

    <?php 
    // Nhóm thí sinh theo phòng
    $rooms = [];
    foreach ($assignments as $a) {
        $rName = $a['room_name'] ?: 'Chưa phân phòng';
        $rooms[$rName][] = $a;
    }

    foreach ($rooms as $roomName => $students): 
    ?>
    <div class="page">
        <div class="header">
            <h1>SỔ ẢNH THÍ SINH DỰ THI NĂNG KHIẾU</h1>
            <p><?= htmlspecialchars($session['session_name']) ?></p>
        </div>

        <div class="room-info">
            PHÒNG THI: <?= htmlspecialchars($roomName) ?> (Tổng số: <?= count($students) ?> thí sinh)
        </div>

        <div class="photo-grid">
            <?php foreach ($students as $s): ?>
            <div class="student-item">
                <span class="sbd"><?= htmlspecialchars($s['exam_number']) ?></span>
                <div class="photo">
                    <img src="<?= url("/public/uploads/candidates/" . $s['cccd'] . ".jpg") ?>" onerror="this.src='<?= url('/public/images/default-avatar.png') ?>'">
                </div>
                <span class="name"><?= htmlspecialchars($s['name']) ?></span>
                <span class="dob"><?= date('d/m/Y', strtotime($s['birth_date'] ?? 'now')) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: right;">
            <p><i>Ngày .... tháng .... năm ....</i></p>
            <p style="margin-right: 50px;"><b>CÁN BỘ COI THI</b></p>
            <br><br><br>
            <p style="margin-right: 40px;">..........................................</p>
        </div>
    </div>
    <?php endforeach; ?>

</body>
</html>
