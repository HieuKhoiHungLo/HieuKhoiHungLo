<?php
// Màn hình in phiếu khổ A4, không có layout
$advisorInfo = $enrollment['thong_tin_gv_ho_tro'] ?? 'Vui lòng liên hệ Hotline: 0866993468 để được hỗ trợ.';
$isNhapHocLanDau = ($enrollment['trang_thai'] === 'da_nhap_hoc' && empty($enrollment['ngay_nhap_hoc_bo_sung'])); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Times+New+Roman&display=swap');
        
        @media print {
            @page { size: A4; margin: 20mm 20mm 20mm 30mm; }
            body { 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
                background-color: white !important;
            }
            .no-print { display: none !important; }
            .a4-container { 
                margin: 0 !important; 
                padding: 0 !important; 
                box-shadow: none !important; 
                min-height: auto !important;
            }
        }
        
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 13pt; 
            line-height: 1.15; 
            color: #000; 
            background-color: #f1f5f9; 
        }
        
        .a4-container { 
            max-width: 210mm; 
            margin: 20px auto; 
            background: white; 
            padding: 20mm 20mm 20mm 30mm; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); 
            min-height: 297mm;
            position: relative;
        }
        
        /* Chữ ký số / mộc */
        .stamp {
            color: #dc2626;
            font-weight: bold;
            text-transform: uppercase;
            border: 2px solid #dc2626;
            border-radius: 50%;
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 10px;
            transform: rotate(-15deg);
            opacity: 0.8;
            position: absolute;
            bottom: 60px;
            right: 40px;
            pointer-events: none;
        }

        .section-title {
            font-weight: bold;
            font-size: 13pt;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }
        
        .doc-table th, .doc-table td {
            border: 1px solid #000;
            padding: 6px 10px;
        }
        
        .doc-table th {
            text-align: center;
            font-weight: bold;
        }
        
        /* Custom checkbox for printing */
        .print-checkbox {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            text-align: center;
            line-height: 14px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 6px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    
    <div class="fixed top-4 right-4 no-print flex gap-2 z-50">
        <button onclick="window.close()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded shadow text-sm font-sans font-bold transition-colors">
            <i class="fas fa-times mr-1"></i> Đóng
        </button>
        <button onclick="window.print()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow text-sm font-sans font-bold transition-colors">
            <i class="fas fa-print mr-1"></i> In Phiếu
        </button>
    </div>

    <div class="a4-container">
        <!-- HEADER -->
        <div class="flex justify-between items-start mb-6">
            <div class="text-center w-5/12 flex flex-col items-center">
                <div class="text-[13pt] uppercase mb-1">TRƯỜNG ĐẠI HỌC HÙNG VƯƠNG</div>
                <div class="font-bold text-[13pt] uppercase border-b-2 border-black pb-0.5 inline-block">HỘI ĐỒNG TUYỂN SINH</div>
                <div class="mt-2 text-[13pt]">Số: <?= $enrollment['ma_phieu'] ?? '......' ?>/PNH</div>
            </div>
            <div class="text-center w-7/12">
                <div class="font-bold text-[13pt] uppercase mb-1">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                <div class="font-bold text-[13pt] border-b-2 border-black pb-0.5 inline-block">Độc lập - Tự do - Hạnh phúc</div>
                <div class="mt-2 italic text-[13pt]">Phú Thọ, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></div>
            </div>
        </div>

        <!-- TITLE & QR -->
        <div class="text-center mb-6 relative">
            <h1 class="text-[14pt] font-bold uppercase mb-1">PHIẾU TIẾP NHẬN HỒ SƠ & NHẬP HỌC</h1>
            <p class="text-[13pt] italic">Đại học hệ chính quy năm 2026</p>
            
            <!-- Mục đích phiếu -->
            <div class="mt-3 flex justify-center gap-8 text-[13pt]">
                <div><span class="print-checkbox">✓</span> Nhập học lần đầu</div>
                <div><span class="print-checkbox"> </span> Nộp bổ sung hồ sơ</div>
            </div>

            <!-- QR Code Góc phải -->
            <div class="absolute top-0 right-0 p-1 border border-gray-300">
                <img src="https://chart.googleapis.com/chart?chs=80x80&cht=qr&chl=HVU-<?= htmlspecialchars($enrollment['so_cccd']) ?>" alt="QR Code" width="80" height="80" />
            </div>
        </div>

        <!-- I. THÔNG TIN THÍ SINH -->
        <div class="section-title">I. THÔNG TIN THÍ SINH</div>
        <div class="flex gap-6 mb-4">
            <!-- Khung ảnh 3x4 -->
            <div class="w-[3cm] h-[4cm] border border-dashed border-gray-400 flex items-center justify-center text-gray-500 text-[10pt] italic">
                Ảnh 3x4
            </div>
            
            <div class="flex-1 text-[13pt] leading-relaxed">
                <div class="grid grid-cols-5 gap-x-2">
                    <div class="col-span-3">Họ và tên: <span class="font-bold uppercase text-[14pt]"><?= htmlspecialchars($enrollment['ho_ten'] ?? '') ?></span></div>
                    <div class="col-span-2">Ngày sinh: <span class="font-bold"><?= date('d/m/Y', strtotime($enrollment['ngay_sinh'] ?? '')) ?></span></div>
                    
                    <div class="col-span-3">Số CCCD: <span class="font-bold"><?= htmlspecialchars($enrollment['so_cccd'] ?? '') ?></span></div>
                    <div class="col-span-2">Điện thoại: <span class="font-bold"><?= htmlspecialchars($enrollment['dien_thoai'] ?? '') ?></span></div>
                    
                    <div class="col-span-5">Ngành trúng tuyển: <span class="font-bold text-[14pt]"><?= htmlspecialchars($enrollment['ten_nganh'] ?? '') ?></span> (Mã ngành: <?= htmlspecialchars($enrollment['ma_nganh'] ?? '') ?>)</div>
                </div>
            </div>
        </div>

        <!-- II. TÌNH TRẠNG HỒ SƠ & TÀI CHÍNH -->
        <div class="section-title">II. TÌNH TRẠNG HỒ SƠ & TÀI CHÍNH</div>
        
        <div class="font-bold mb-2">1. Danh mục hồ sơ:</div>
        <table class="w-full doc-table mb-4 text-[13pt]">
            <thead>
                <tr>
                    <th class="w-10">TT</th>
                    <th>Nội dung hồ sơ</th>
                    <th class="w-48">Tình trạng</th>
                    <th class="w-48">Ghi chú / Hạn nộp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                    <tr><td colspan="4" class="text-center italic text-gray-500">Chưa cấu hình hồ sơ</td></tr>
                <?php else: ?>
                    <?php foreach ($documents as $i => $doc): 
                        $val = trim($doc['gia_tri'] ?? '');
                        if (empty($val)) $val = 'Chưa nộp';
                        $isMissing = in_array(strtolower($val), ['chưa nộp', 'thiếu', 'không có']);
                    ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($doc['ten_ho_so'] ?? '') ?></td>
                            <td class="text-center whitespace-nowrap">
                                <?php if ($isMissing): ?>
                                    <span class="print-checkbox"> </span> Chưa nộp
                                <?php else: ?>
                                    <span class="print-checkbox">✓</span> <?= htmlspecialchars($val) ?>
                                <?php endif; ?>
                            </td>
                            <td class="italic text-[12pt]"><?= htmlspecialchars($doc['ghi_chu'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="italic text-[11pt] text-gray-700 mb-4 pl-4">
            - Sinh viên có trách nhiệm nộp bổ sung các giấy tờ "Chưa nộp" về phòng Công tác Sinh viên chậm nhất sau 30 ngày kể từ ngày nhập học.<br/>
            - Nhà trường chỉ tiếp nhận những thí sinh có đủ điều kiện trúng tuyển theo quy chế.
        </div>

        <div class="font-bold mb-1">2. Tình trạng tài chính:</div>
        <div class="pl-4 mb-6">
            - Học phí, lệ phí nhập học: 
            <span class="font-bold ml-2">
                <span class="print-checkbox"><?= ($enrollment['da_nop_tien'] ?? 0) == 1 ? '✓' : ' ' ?></span> Đã thu
                <span class="print-checkbox ml-6"><?= ($enrollment['da_nop_tien'] ?? 0) != 1 ? '✓' : ' ' ?></span> Chưa nộp
            </span>
        </div>

        <!-- III. HỖ TRỢ SINH VIÊN -->
        <div class="section-title">III. THÔNG TIN HỖ TRỢ SINH VIÊN</div>
        <div class="bg-gray-100 p-3 border border-gray-400 rounded-sm italic text-[12pt] mb-8 leading-relaxed">
            Nhằm hỗ trợ sinh viên trong quá trình làm thủ tục và làm quen với môi trường đại học, Nhà trường cử cán bộ tư vấn riêng cho sinh viên ngành <strong><?= htmlspecialchars($enrollment['ten_nganh'] ?? '') ?></strong>.<br/>
            Sinh viên có thắc mắc vui lòng liên hệ: <strong><?= htmlspecialchars($advisorInfo) ?></strong>
        </div>

        <!-- CHỮ KÝ -->
        <div class="flex justify-between mt-8 text-center text-[13pt]">
            <div class="w-1/2">
                <p class="font-bold uppercase">SINH VIÊN NỘP HỒ SƠ</p>
                <p class="italic text-[11pt]">(Ký và ghi rõ họ tên)</p>
                <div class="h-28"></div>
                <p class="font-bold"><?= htmlspecialchars($enrollment['ho_ten'] ?? '') ?></p>
            </div>
            <div class="w-1/2 relative">
                <p class="font-bold uppercase">CÁN BỘ TIẾP NHẬN</p>
                <p class="italic text-[11pt]">(Ký và ghi rõ họ tên)</p>
                <div class="h-28"></div>
                <p class="font-bold"><?= htmlspecialchars($enrollment['ten_can_bo'] ?? '') ?></p>

                <!-- Stamp (Chỉ hiển thị khi đã nộp hồ sơ hợp lệ) -->
                <?php if($enrollment['trang_thai'] === 'da_nhap_hoc'): ?>
                <div class="stamp">
                    ĐÃ TIẾP NHẬN<br/><br/>
                    <?= date('d/m/Y') ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="mt-16 pt-4 border-t border-gray-400 text-[11pt] text-center italic text-gray-600 flex justify-between">
            <span>Mã hồ sơ: <?= htmlspecialchars($enrollment['id'] ?? '') ?>-<?= htmlspecialchars($enrollment['so_cccd'] ?? '') ?></span>
            <span>Sinh viên giữ phiếu này để đối chiếu khi cần thiết.</span>
        </div>
    </div>
    
    <script>
        // Optional: Auto print
        // window.onload = function() { setTimeout(() => window.print(), 500); }
    </script>
</body>
</html>
