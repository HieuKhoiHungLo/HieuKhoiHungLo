<?php
// Màn hình in phiếu khổ A4, không có layout
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page { size: A4; margin: 20mm; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background-color: white !important; }
            .no-print { display: none !important; }
        }
        body { font-family: 'Times New Roman', Times, serif; font-size: 14pt; line-height: 1.5; color: black; background-color: #f1f5f9; }
        .a4-container { max-w-4xl mx-auto bg-white p-[20mm] shadow-lg my-8; width: 210mm; min-height: 297mm; }
        .text-justify { text-align: justify; }
    </style>
</head>
<body>
    
    <div class="fixed top-4 right-4 no-print flex gap-2">
        <button onclick="window.close()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded shadow text-sm font-sans">Đóng</button>
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow text-sm font-sans">In Phiếu</button>
    </div>

    <div class="a4-container mx-auto bg-white p-[20mm] shadow-lg my-8" style="width: 210mm; min-height: 297mm; box-sizing: border-box;">
        <!-- Header -->
        <div class="flex justify-between items-start mb-6">
            <div class="text-center w-5/12">
                <p class="font-bold text-sm uppercase">UBND TỈNH PHÚ THỌ</p>
                <p class="font-bold text-sm uppercase border-b border-black inline-block pb-1 mb-1">TRƯỜNG ĐẠI HỌC HÙNG VƯƠNG</p>
                <p class="text-xs">Số: <?= $enrollment['ma_phieu'] ?? '......' ?>/PNH-ĐHHV</p>
            </div>
            <div class="text-center w-7/12">
                <p class="font-bold text-sm uppercase">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
                <p class="font-bold text-sm border-b border-black inline-block pb-1 mb-1">Độc lập - Tự do - Hạnh phúc</p>
            </div>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold uppercase mb-2">PHIẾU TIẾP NHẬN HỒ SƠ NHẬP HỌC</h1>
            <p class="italic">Năm tuyển sinh <?= date('Y') ?></p>
        </div>

        <!-- Student Info -->
        <div class="mb-6">
            <p>Trường Đại học Hùng Vương đã tiếp nhận hồ sơ nhập học của sinh viên:</p>
            <div class="grid grid-cols-2 gap-2 mt-2 ml-4">
                <p>Họ và tên: <span class="font-bold uppercase"><?= htmlspecialchars($enrollment['ho_ten'] ?? '') ?></span></p>
                <p>Ngày sinh: <?= date('d/m/Y', strtotime($enrollment['ngay_sinh'] ?? '')) ?></p>
                <p>Số CCCD: <?= htmlspecialchars($enrollment['so_cccd'] ?? '') ?></p>
                <p>Điện thoại: <?= htmlspecialchars($enrollment['dien_thoai'] ?? '') ?></p>
                <p class="col-span-2">Ngành trúng tuyển: <span class="font-bold"><?= htmlspecialchars($enrollment['ten_nganh'] ?? '') ?></span></p>
            </div>
        </div>

        <!-- Documents -->
        <div class="mb-6">
            <p class="font-bold mb-2">Danh mục hồ sơ sinh viên đã nộp:</p>
            <table class="w-full border-collapse border border-black text-sm">
                <thead>
                    <tr>
                        <th class="border border-black p-2 w-12 text-center">STT</th>
                        <th class="border border-black p-2 text-center">Loại Giấy Tờ</th>
                        <th class="border border-black p-2 w-32 text-center">Tình Trạng</th>
                        <th class="border border-black p-2 w-48 text-center">Ghi Chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                        <tr><td colspan="4" class="border border-black p-2 text-center italic">Chưa có thông tin hồ sơ</td></tr>
                    <?php else: ?>
                        <?php foreach ($documents as $i => $doc): ?>
                            <tr>
                                <td class="border border-black p-2 text-center"><?= $i + 1 ?></td>
                                <td class="border border-black p-2"><?= htmlspecialchars($doc['ten_ho_so'] ?? '') ?></td>
                                <td class="border border-black p-2 text-center"><?= htmlspecialchars($doc['gia_tri'] ?? '') ?></td>
                                <td class="border border-black p-2"><?= htmlspecialchars($doc['ghi_chu'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Payment Info -->
        <div class="mb-6">
            <p class="font-bold mb-1">Tình trạng tài chính:</p>
            <p class="ml-4">
                - Học phí, lệ phí nhập học: 
                <span class="font-bold"><?= ($enrollment['da_nop_tien'] ?? 0) == 1 ? 'Đã thanh toán đủ' : 'Chưa thanh toán' ?></span>
            </p>
        </div>

        <!-- Signatures -->
        <div class="flex justify-between mt-12 text-center">
            <div class="w-1/2">
                <p class="font-bold">Sinh viên nộp hồ sơ</p>
                <p class="italic text-sm">(Ký và ghi rõ họ tên)</p>
                <div class="h-24"></div>
                <p class="font-bold"><?= htmlspecialchars($enrollment['ho_ten'] ?? '') ?></p>
            </div>
            <div class="w-1/2">
                <p class="italic mb-1">Phú Thọ, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></p>
                <p class="font-bold">Cán bộ tiếp nhận</p>
                <p class="italic text-sm">(Ký và ghi rõ họ tên)</p>
                <div class="h-24"></div>
                <p class="font-bold"><?= htmlspecialchars($enrollment['ten_can_bo'] ?? '') ?></p>
            </div>
        </div>

        <div class="mt-16 text-xs text-center italic">
            <p>Phiếu này được in trực tiếp từ Hệ thống Quản lý Tuyển sinh Trường Đại học Hùng Vương.</p>
        </div>
    </div>
    
    <script>
        // Tự động mở hộp thoại in nếu người dùng muốn
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
