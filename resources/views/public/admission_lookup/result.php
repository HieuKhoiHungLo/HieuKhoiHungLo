<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Outfit', sans-serif; }

        body {
            background: linear-gradient(160deg, #f8fafc 0%, #eff6ff 40%, #fff1f2 100%);
            min-height: 100vh;
        }

        @keyframes fade-up {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fade-up 0.5s ease forwards; }

        @keyframes pulse-green {
            0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
            50%       { box-shadow: 0 0 0 12px rgba(34, 197, 94, 0); }
        }
        .pulse-admitted { animation: pulse-green 2s ease-in-out infinite; }

        .letter-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .letter-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 32px 80px rgba(0,0,0,0.12);
        }

        .email-body-wrapper {
            /* Wrapper cho nội dung HTML email */
            font-size: 14px;
            line-height: 1.7;
            color: #374151;
            overflow-x: auto;
        }

        /* Override email HTML nếu cần */
        .email-body-wrapper table { max-width: 100% !important; }
        .email-body-wrapper img { max-width: 100%; height: auto; }

        .info-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 700;
        }

        @media print {
            .no-print { display: none !important; }
            .letter-card { box-shadow: none !important; border: 1px solid #ccc; }
            body { background: white; }
        }
    </style>
</head>
<body class="p-4 md:p-8">

    <!-- Top Bar -->
    <div class="max-w-4xl mx-auto mb-6 no-print fade-up">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="<?= url('/tra-cuu-trung-tuyen') ?>"
                   class="w-10 h-10 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center justify-center text-gray-500 hover:text-red-600 hover:border-red-200 transition-all">
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <div class="text-xs text-gray-400 font-medium">Từ khóa tra cứu</div>
                    <div class="font-black text-gray-800"><?= htmlspecialchars($keyword) ?></div>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="window.print()" id="btnPrint"
                    class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:border-blue-300 hover:text-blue-600 transition-all shadow-sm">
                    <i class="fas fa-print"></i> In thông báo
                </button>
                <a href="<?= url('/tra-cuu-trung-tuyen') ?>"
                    class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700 transition-all shadow-sm">
                    <i class="fas fa-search"></i> Tra cứu khác
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto space-y-8">

        <?php foreach ($items as $idx => $item):
            $r  = $item['record'];
            $body = $item['body'];
            $hasEmail = !empty($body);

            // Status badge
            $status  = $r['status'] ?? 'pending';
            $isSent  = ($status === 'sent');
            $isQueued = in_array($status, ['queued', 'processing']);
        ?>

        <!-- Admission Letter Card -->
        <div class="letter-card fade-up" style="animation-delay: <?= $idx * 0.1 ?>s; opacity:0;">

            <!-- Card Header -->
            <div class="bg-gradient-to-r from-red-700 via-red-600 to-red-800 px-6 py-5 relative overflow-hidden">
                <!-- Decorative pattern -->
                <div class="absolute inset-0 opacity-10" style="background-image: repeating-linear-gradient(45deg, white 0, white 1px, transparent 0, transparent 50%); background-size: 12px 12px;"></div>

                <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center border border-white/30 flex-shrink-0">
                            <i class="fas fa-graduation-cap text-white text-xl"></i>
                        </div>
                        <div>
                            <div class="text-white/70 text-xs font-semibold uppercase tracking-widest mb-0.5">Trường Đại học Hùng Vương</div>
                            <div class="text-white font-black text-lg leading-tight">THÔNG BÁO TRÚNG TUYỂN 2026</div>
                        </div>
                    </div>
                    <!-- Status Badge -->
                    <div class="flex-shrink-0">
                        <?php if ($isSent): ?>
                            <span class="info-chip bg-green-400/20 text-green-100 border border-green-400/30 pulse-admitted">
                                <i class="fas fa-check-circle text-green-300"></i> Đã gửi email
                            </span>
                        <?php elseif ($isQueued): ?>
                            <span class="info-chip bg-yellow-400/20 text-yellow-100 border border-yellow-400/30">
                                <i class="fas fa-clock text-yellow-300"></i> Đang xử lý
                            </span>
                        <?php else: ?>
                            <span class="info-chip bg-white/10 text-white/80 border border-white/20">
                                <i class="fas fa-file-alt text-white/60"></i> Thông báo chính thức
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Info Strip -->
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-1">Họ và tên</div>
                        <div class="font-black text-gray-900 text-base leading-tight"><?= htmlspecialchars($r['ho_ten'] ?? '') ?></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-1">Số CCCD</div>
                        <div class="font-mono font-bold text-gray-800"><?= htmlspecialchars($r['so_cccd'] ?? '') ?></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-1">Ngày sinh</div>
                        <div class="font-bold text-gray-800"><?= htmlspecialchars($r['ngay_sinh'] ?? '') ?></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-1">Ngành trúng tuyển</div>
                        <div class="font-bold text-red-700 leading-tight"><?= htmlspecialchars($r['ten_nganh'] ?? '') ?></div>
                    </div>
                </div>
            </div>

            <!-- Score Info Strip -->
            <div class="px-6 py-4 bg-white border-b border-gray-100">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div class="bg-red-50 rounded-2xl p-3 text-center">
                        <div class="text-xs text-red-400 font-semibold mb-1">Điểm xét tuyển</div>
                        <div class="text-2xl font-black text-red-700"><?= htmlspecialchars(rtrim(rtrim(number_format((float)($r['diem_xt'] ?? 0), 3, '.', ''), '0'), '.')) ?></div>
                    </div>
                    <div class="bg-blue-50 rounded-2xl p-3 text-center">
                        <div class="text-xs text-blue-400 font-semibold mb-1">Tổ hợp</div>
                        <div class="text-2xl font-black text-blue-700"><?= htmlspecialchars($r['to_hop'] ?? '') ?></div>
                    </div>
                    <div class="bg-purple-50 rounded-2xl p-3 text-center">
                        <div class="text-xs text-purple-400 font-semibold mb-1">Khu vực</div>
                        <div class="text-2xl font-black text-purple-700"><?= htmlspecialchars($r['khu_vuc'] ?? '') ?></div>
                    </div>
                    <div class="bg-green-50 rounded-2xl p-3 text-center">
                        <div class="text-xs text-green-400 font-semibold mb-1">Mã ngành</div>
                        <div class="text-xl font-black text-green-700 font-mono"><?= htmlspecialchars($r['ma_nganh'] ?? '') ?></div>
                    </div>
                </div>
            </div>

            <?php if ($hasEmail): ?>
            <!-- Toggle Button -->
            <div class="px-6 py-4 bg-white border-b border-gray-100 no-print">
                <button onclick="toggleLetter(<?= $idx ?>)"
                    id="btn-letter-<?= $idx ?>"
                    class="w-full flex items-center justify-center gap-2 py-3 px-4 bg-gradient-to-r from-red-50 to-orange-50 border border-red-100 rounded-2xl text-red-700 font-bold text-sm hover:from-red-100 hover:to-orange-100 transition-all group">
                    <i class="fas fa-envelope-open-text text-red-500"></i>
                    <span id="btn-text-<?= $idx ?>">Xem nội dung thông báo đầy đủ</span>
                    <i class="fas fa-chevron-down text-red-400 group-hover:rotate-180 transition-transform" id="btn-icon-<?= $idx ?>"></i>
                </button>
            </div>

            <!-- Email Content (collapsible) -->
            <div id="letter-content-<?= $idx ?>" class="hidden px-6 py-6 bg-white">
                <div class="bg-gray-50 rounded-2xl p-1">
                    <div class="email-body-wrapper p-4 md:p-6 bg-white rounded-xl border border-gray-100">
                        <?= $body ?>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- No template configured -->
            <div class="px-6 py-8 text-center bg-gray-50">
                <i class="fas fa-file-alt text-4xl text-gray-200 mb-3"></i>
                <p class="text-gray-500 text-sm">Nội dung thông báo chưa được cấu hình.<br>Vui lòng liên hệ phòng Tuyển sinh để được hỗ trợ.</p>
            </div>
            <?php endif; ?>

            <!-- Card Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="text-xs text-gray-400 flex items-center gap-2">
                    <i class="fas fa-clock"></i>
                    Nhập học: <strong class="text-gray-600">Theo thông báo của Nhà trường</strong>
                </div>
                <?php if (!empty($r['email'])): ?>
                    <div class="text-xs text-gray-400 flex items-center gap-2">
                        <i class="fas fa-envelope"></i>
                        Email: <strong class="text-gray-600 font-mono"><?= htmlspecialchars($r['email']) ?></strong>
                        <?php if ($isSent): ?>
                            <span class="text-green-500 font-bold">(Đã gửi)</span>
                        <?php else: ?>
                            <span class="text-orange-500 font-bold">(Email có thể bị chặn, xem tại đây)</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php endforeach; ?>

        <!-- Contact Card -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 md:p-8 fade-up no-print">
            <div class="flex flex-col sm:flex-row items-center gap-6">
                <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-headset text-red-500 text-2xl"></i>
                </div>
                <div class="text-center sm:text-left">
                    <h3 class="font-black text-gray-800 text-lg">Cần hỗ trợ thêm?</h3>
                    <p class="text-gray-500 text-sm mt-1">
                        Phòng Tuyển sinh Trường Đại học Hùng Vương
                    </p>
                    <div class="flex flex-wrap gap-3 mt-3 justify-center sm:justify-start">
                        <a href="tel:0866993468" class="inline-flex items-center gap-2 px-4 py-2 bg-green-50 text-green-700 rounded-xl text-sm font-bold hover:bg-green-100 transition-all">
                            <i class="fas fa-phone"></i> 0866.993.468
                        </a>
                        <a href="<?= url('/application/results') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-xl text-sm font-bold hover:bg-blue-100 transition-all">
                            <i class="fas fa-user"></i> Đăng nhập tra cứu
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center pb-8 text-xs text-gray-400 no-print">
            <p>Trường Đại học Hùng Vương &bull; Hệ thống Tuyển sinh 2026</p>
            <p class="mt-1">Thông tin trên đây là chính thức. Vui lòng liên hệ phòng Tuyển sinh nếu có thắc mắc.</p>
        </div>
    </div>

    <script>
        function toggleLetter(idx) {
            const content = document.getElementById('letter-content-' + idx);
            const btnText = document.getElementById('btn-text-' + idx);
            const btnIcon = document.getElementById('btn-icon-' + idx);

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                content.style.animation = 'fade-up 0.3s ease forwards';
                btnText.textContent = 'Ẩn nội dung thông báo';
                btnIcon.style.transform = 'rotate(180deg)';
            } else {
                content.classList.add('hidden');
                btnText.textContent = 'Xem nội dung thông báo đầy đủ';
                btnIcon.style.transform = 'rotate(0deg)';
            }
        }

        // Auto expand if only 1 result
        <?php if (count($items) === 1 && !empty($items[0]['body'])): ?>
        window.addEventListener('DOMContentLoaded', function() {
            toggleLetter(0);
        });
        <?php endif; ?>
    </script>
</body>
</html>
