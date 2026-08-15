<?php
// Màn hình in phiếu khổ A4, không có layout
$advisorInfo = $enrollment['thong_tin_gv_ho_tro'] ?? 'Vui lòng liên hệ Hotline: 0866993468 để được hỗ trợ.';
$isNhapHocLanDau = ($enrollment['trang_thai'] === 'da_nhap_hoc' && empty($enrollment['ngay_nhap_hoc_bo_sung'])); 

// Fetch template
$db = \App\Core\Database::getInstance()->getConnection();
$stmtTpl = $db->prepare("SELECT noi_dung_html FROM mau_in WHERE ma_mau = 'PHIEU_NHAP_HOC'");
$stmtTpl->execute();
$tplHtml = $stmtTpl->fetchColumn();

// Xử lý các biến nếu có template
if (!empty(trim(strip_tags($tplHtml ?? '')))) {
    // Generate QR
    $qrData = urlencode($enrollment['so_cccd'] ?? '');
    $qrImg = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . $qrData . '" style="width:100px;height:100px;" />';
    
    // Replace
    $search = [
        '{{hoten}}' => htmlspecialchars($enrollment['ho_ten'] ?? ''),
        '{{ngay_sinh}}' => !empty($enrollment['ngay_sinh']) ? htmlspecialchars(date('d/m/Y', strtotime($enrollment['ngay_sinh']))) : '',
        '{{so_cccd}}' => htmlspecialchars($enrollment['so_cccd'] ?? ''),
        '{{sbd}}' => htmlspecialchars($enrollment['sbd'] ?? ''),
        '{{nganh}}' => htmlspecialchars($enrollment['ten_nganh'] ?? ''),
        '{{ma_nganh}}' => htmlspecialchars($enrollment['ma_nganh'] ?? ''),
        '{{khoi}}' => htmlspecialchars($enrollment['to_hop'] ?? ''),
        '{{diem_tong}}' => htmlspecialchars($enrollment['diem_xt'] ?? ''),
        '{{qr_cccd}}' => $qrImg,
        '{{qr_thanh_toan}}' => '', // Tích hợp vietqr sau nếu cần
    ];
    $finalHtml = strtr($tplHtml, $search);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Times+New+Roman&display=swap');
        @media print {
            @page { size: A4; margin: 15mm; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background-color: white !important; }
            .no-print { display: none !important; }
            .a4-container { margin: 0 !important; padding: 0 !important; box-shadow: none !important; border: none !important; min-height: auto !important; }
        }
        body { font-family: 'Times New Roman', Times, serif; font-size: 13pt; line-height: 1.2; color: #000; background-color: #f1f5f9; margin: 0; }
        .a4-container { max-width: 210mm; margin: 20px auto; background: white; padding: 15mm; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid #ccc; position: relative; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .italic { font-style: italic; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 15px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { text-align: center; font-weight: bold; }
        .text-red { color: red; }
        .row-flex { display: flex; justify-content: space-between; }
        .section-title { font-weight: bold; text-transform: uppercase; margin-top: 15px; margin-bottom: 5px; }
        .checkbox-box { display: inline-block; width: 12px; height: 12px; border: 1px solid #000; text-align: center; line-height: 12px; font-size: 10px; margin-right: 5px; }
        .flex-cols { display: flex; }
        .col-half { flex: 1; }
        .doc-list { list-style: none; padding: 0; margin: 0; }
        .doc-list li { margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center; }
        .doc-list li span.dots { flex-grow: 1; border-bottom: 1px dotted #000; margin-left: 10px; margin-right: 10px; height: 1em; }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">
            🖨 In Phiếu
        </button>
    </div>

    <?php for ($copy = 0; $copy < 2; $copy++): ?>
    <div class="a4-container" <?= $copy === 0 ? 'style="page-break-after: always;"' : '' ?>>
        <?php if (!empty(trim(strip_tags($tplHtml ?? '')))): ?>
            <?= $finalHtml ?>
        <?php else: ?>
            <!-- FALLBACK HARDCODED TEMPLATE -->
            <div class="row-flex">
                <div class="text-center" style="flex: 1;">
                    <div class="bold">TRƯỜNG ĐẠI HỌC HÙNG VƯƠNG</div>
                    <div class="bold" style="text-decoration: underline;">MÃ TRƯỜNG: THV</div>
                    <div>Số: 10/PNH</div>
                </div>
                <div class="text-center" style="flex: 1;">
                    <div class="bold">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                    <div class="bold" style="text-decoration: underline;">Độc lập - Tự do - Hạnh phúc</div>
                </div>
            </div>

            <div class="text-center" style="margin-top: 20px;">
                <div class="bold" style="font-size: 16pt;">PHIẾU NHẬP HỌC</div>
                <div class="bold" style="font-size: 14pt;">ĐẠI HỌC HỆ CHÍNH QUY NĂM 2026</div>
                <div style="color: red; margin-top: 5px;" class="bold">
                    <span class="checkbox-box"><?= $isNhapHocLanDau ? '✓' : '' ?></span> Nhập học lần đầu &nbsp;&nbsp;&nbsp;&nbsp;
                    <span class="checkbox-box"><?= !$isNhapHocLanDau ? '✓' : '' ?></span> Nộp bổ sung hồ sơ
                </div>
            </div>

            <div style="position: absolute; top: 110px; right: 20mm; width: 3cm; height: 4cm; border: 1px solid #000; text-align: center; line-height: 4cm; color: red;">
                Ảnh
            </div>

            <div class="section-title">1. THÔNG TIN CÁ NHÂN</div>
            <div class="flex-cols">
                <div class="col-half">
                    Họ và tên: <span class="bold text-red" style="text-transform: uppercase;"><?= htmlspecialchars($enrollment['ho_ten'] ?? '') ?></span><br>
                    Số CCCD: <span class="bold text-red"><?= htmlspecialchars($enrollment['so_cccd'] ?? '') ?></span>
                </div>
                <div class="col-half">
                    Ngày sinh: <span class="bold text-red"><?= !empty($enrollment['ngay_sinh']) ? date('d/m/Y', strtotime($enrollment['ngay_sinh'])) : '' ?></span><br>
                    Điện thoại: <span class="bold text-red"><?= htmlspecialchars($enrollment['dien_thoai'] ?? '') ?></span>
                </div>
            </div>
            <div>Ngành trúng tuyển: <span class="bold text-red"><?= htmlspecialchars($enrollment['ten_nganh'] ?? '') ?> (<?= htmlspecialchars($enrollment['ma_nganh'] ?? '') ?>)</span></div>

            <div class="section-title">2. THỰC HIỆN CÁC QUI ĐỊNH KHI NHẬP HỌC</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">TT</th>
                        <th>Nội dung thực hiện</th>
                        <th style="width: 150px;">Hồ sơ nộp</th>
                        <th style="width: 100px;">Cán bộ thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $tt = 1; 
                    foreach ($documents as $doc): 
                        if (!in_array($doc['ten_ho_so'], ['Giấy chứng nhận kết quả thi tốt nghiệp THPT năm 2026', 'Học bạ Trung học phổ thông', 'Học bạ THPT'])) continue;
                    ?>
                    <tr>
                        <td class="text-center"><?= $tt++ ?></td>
                        <td><?= htmlspecialchars($doc['ten_ho_so']) ?></td>
                        <td class="text-center text-red"><?= htmlspecialchars($doc['gia_tri'] ?? '') ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="section-title">3. TÌNH TRẠNG XÁC NHẬN HỒ SƠ VÀ KINH PHÍ <span style="font-weight: normal; font-style: italic;">(Thí sinh tự kê khai)</span></div>
            <div class="flex-cols">
                <div class="col-half">
                    <?php 
                    $isBo = in_array($enrollment['xac_nhan_bo'], [true, 1, '1', 'true', 't', 'T'], true) || strtolower($enrollment['xac_nhan_bo'] ?? '') === 'true' || strtolower($enrollment['xac_nhan_bo'] ?? '') === 't';
                    $isTruong = in_array($enrollment['xac_nhan_truong'], [true, 1, '1', 'true', 't', 'T'], true) || strtolower($enrollment['xac_nhan_truong'] ?? '') === 'true' || strtolower($enrollment['xac_nhan_truong'] ?? '') === 't';
                    $isKinhPhi = in_array($enrollment['xac_nhan_kinh_phi'], [true, 1, '1', 'true', 't', 'T'], true) || strtolower($enrollment['xac_nhan_kinh_phi'] ?? '') === 'true' || strtolower($enrollment['xac_nhan_kinh_phi'] ?? '') === 't';
                    ?>
                    Xác nhận hệ thống Bộ GD&ĐT: <span class="text-red bold"><span class="checkbox-box"><?= $isBo ? '✓' : '' ?></span></span><br>
                    Xác nhận nộp kinh phí: <span class="bold text-red"><?= ($isKinhPhi || floatval($enrollment['so_tien'] ?? 0) > 0) ? 'Đã nộp' : 'Chưa nộp' ?></span>
                </div>
                <div class="col-half">
                    Xác nhận trên hệ thống trường: <span class="text-red bold"><span class="checkbox-box"><?= $isTruong ? '✓' : '' ?></span></span>
                </div>
            </div>

            <div class="section-title">4. HỒ SƠ CẦN HOÀN THIỆN <span style="font-weight: normal; font-style: italic;">(Nộp sau ngày nhập học)</span></div>
            <ul class="doc-list" style="padding-left: 20px;">
                <li>- Giấy báo trúng tuyển <span class="checkbox-box"></span> <span class="dots"></span></li>
                <li>- Chứng chỉ ngoại ngữ quốc tế (nếu có) <span class="checkbox-box"></span> <span class="dots"></span></li>
                <li>- Bằng tốt nghiệp THPT (bản sao chứng thực) <span class="checkbox-box"></span> <span class="dots"></span></li>
                <li>- Giấy khai sinh (bản sao chứng thực) <span class="checkbox-box"></span> <span class="dots"></span></li>
                <li>- Lý lịch sinh viên <i>(theo mẫu)</i> <span class="checkbox-box"></span> <span class="dots"></span></li>
                <li>- Căn cước (02 bản sao) <span class="checkbox-box"></span> <span class="dots"></span></li>
                <li>- Ảnh 3x4 (04 ảnh) <span class="checkbox-box"></span> <span class="dots"></span></li>
                <li>- Giấy tờ chứng nhận đối tượng ưu tiên (nếu có) <span class="checkbox-box"></span> <span class="dots"></span></li>
                <li>- Giấy di chuyển nghĩa vụ quân sự (đối với nam) <span class="checkbox-box"></span> <span class="dots"></span></li>
            </ul>

            <div class="section-title">5. THÔNG TIN HỖ TRỢ SINH VIÊN</div>
            <div style="text-align: justify;">
                Nhằm hỗ trợ sinh viên trong quá trình làm thủ tục và làm quen với môi trường đại học, Nhà trường cử cán bộ tư vấn riêng cho sinh viên ngành <span class="bold text-red"><?= htmlspecialchars($enrollment['ten_nganh'] ?? '') ?></span>.<br>
                Sinh viên có thắc mắc vui lòng liên hệ: <span class="bold text-red"><?= nl2br(htmlspecialchars($advisorInfo)) ?></span>
            </div>
            
            <div style="display: flex; margin-top: 10px; font-size: 11pt;">
                <div style="width: 50px;" class="italic bold">Hỗ trợ:</div>
                <div style="flex: 1;">
                    Tuyển sinh: 0866993468 (Hotline)<br>
                    Hồ sơ nhập học: 0868198724 (C. Hằng)<br>
                    Ký túc xá: 0969297437 (T. Tiến)
                </div>
                <div style="flex: 1;">
                    Đào tạo: 0977896453 (C. Quyền)<br>
                    Học phí: 0978722288 (T. Dũng)<br>
                    BCH Quân sự: 0988483979 (T. Thắng)
                </div>
            </div>

            <div style="display: flex; margin-top: 15px;">
                <div style="width: 120px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($enrollment['so_cccd'] ?? '') ?>" style="width: 100px; height: 100px; border: 1px solid #000;" />
                    <div class="text-center text-red bold" style="font-size: 9pt; margin-top: 2px;">Mã QR</div>
                </div>
                <div style="flex: 1; text-align: center;">
                    <div class="italic">Phú Thọ, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></div>
                    <div class="bold">Thí sinh ký và ghi rõ họ tên</div>
                    <br><br><br><br>
                    <div class="bold text-red"><?= htmlspecialchars($enrollment['ho_ten'] ?? '') ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endfor; ?>
</body>
</html>
