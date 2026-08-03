<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="<?= url('/assets/img/Logo.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('/assets/img/Logo.png') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Be Vietnam Pro', system-ui, -apple-system, sans-serif; }

        html, body {
            height: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            padding: 0;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #7f1d1d 100%);
            background-attachment: fixed;
            background-size: cover;
        }

        @keyframes fade-up {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fade-up 0.5s ease forwards; }

        .letter-card {
            background: white;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body class="p-4 md:p-8 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-2xl mx-auto space-y-6">

        <!-- Top Navigation -->
        <div class="flex items-center justify-between no-print fade-up">
            <a href="<?= url('/') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold transition-all border border-white/20">
                <i class="fas fa-home"></i> Về trang chủ
            </a>
            <a href="<?= url('/tra-cuu-trung-tuyen') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold transition-all border border-white/20">
                <i class="fas fa-search"></i> Tra cứu khác
            </a>
        </div>

        <?php foreach ($items as $idx => $item):
            $r  = $item['record'];
            $isAdmitted = !empty($r['ten_nganh']);
            $sessionTitle = !empty($r['ten_dot']) ? $r['ten_dot'] : 'Kết quả xét tuyển';
            $sessionYear = !empty($r['nam_tuyen_sinh']) ? $r['nam_tuyen_sinh'] : date('Y');
        ?>

        <!-- Minimal Single Result Card -->
        <div class="letter-card fade-up">

            <!-- Compact Side-by-Side Card Header -->
            <div class="bg-gradient-to-r from-red-700 via-red-600 to-red-800 px-6 py-4 text-white relative overflow-hidden">
                <div class="relative z-10 flex items-center gap-4">
                    <!-- Logo on the left side -->
                    <div class="w-12 h-12 bg-white/15 rounded-2xl flex items-center justify-center border border-white/30 flex-shrink-0 shadow-inner">
                        <img src="<?= url('/assets/img/Logo.png') ?>" alt="Logo" class="w-8 h-8 object-contain" onerror="this.parentElement.innerHTML='<i class=\'fas fa-graduation-cap text-white text-xl\'></i>'">
                    </div>

                    <!-- Text on the right side -->
                    <div class="flex-1 min-w-0">
                        <div class="text-white/80 text-xs font-bold uppercase tracking-wider leading-none mb-1">TRƯỜNG ĐẠI HỌC HÙNG VƯƠNG</div>
                        <h2 class="text-base md:text-lg font-black uppercase text-white leading-tight truncate">
                            <?= htmlspecialchars($sessionTitle) ?> (Năm <?= htmlspecialchars($sessionYear) ?>)
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Card Body: Photo & Simple Label-Value Lines -->
            <div class="p-6 md:p-8 space-y-6">

                <!-- Candidate Photo & Label-Value Lines -->
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 p-6 bg-slate-50/80 rounded-3xl">
                    <!-- Photo 3x4 -->
                    <div class="flex-shrink-0">
                        <div class="w-32 h-40 rounded-2xl overflow-hidden border-4 border-white shadow-md bg-slate-200 relative">
                            <?php if (!empty($r['anh_dai_dien'])): ?>
                                <img src="<?= htmlspecialchars($r['anh_dai_dien']) ?>" alt="Ảnh thí sinh" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 bg-slate-100 p-2 text-center">
                                    <i class="fas fa-user-graduate text-4xl mb-1 text-slate-300"></i>
                                    <span class="text-[10px] font-bold text-slate-400">Ảnh 3x4</span>
                                </div>
                            <?php endif; ?>
                            <div class="absolute bottom-0 inset-x-0 bg-red-600 text-white text-[9px] font-extrabold py-0.5 text-center uppercase tracking-wider">
                                HVU <?= htmlspecialchars($sessionYear) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Simple Label-Value Grid (Aligned neatly with fixed label column width) -->
                    <div class="flex-1 self-center">
                        <div class="grid grid-cols-[135px_1fr] items-baseline gap-y-2.5 gap-x-3 text-base">
                            <span class="text-slate-900 font-bold whitespace-nowrap">Thí sinh:</span>
                            <span class="text-red-700 font-extrabold uppercase text-base"><?= htmlspecialchars($r['ho_ten'] ?? '') ?></span>

                            <span class="text-slate-900 font-bold whitespace-nowrap">Số CCCD:</span>
                            <span class="text-red-700 font-extrabold text-base"><?= htmlspecialchars($r['so_cccd'] ?? '') ?></span>

                            <?php if (!empty($r['sbd'])): ?>
                            <span class="text-slate-900 font-bold whitespace-nowrap">SBD thi THPT:</span>
                            <span class="text-red-700 font-extrabold text-base"><?= htmlspecialchars($r['sbd']) ?></span>
                            <?php endif; ?>

                            <span class="text-slate-900 font-bold whitespace-nowrap">Kết quả:</span>
                            <span class="text-red-700 font-extrabold text-base">
                                <?= $isAdmitted ? 'Đã trúng tuyển' : 'Chưa trúng tuyển' ?>
                            </span>

                            <?php if ($isAdmitted): ?>
                            <span class="text-slate-900 font-bold whitespace-nowrap">Ngành:</span>
                            <span class="text-red-700 font-extrabold text-base leading-tight">
                                <?= htmlspecialchars($r['ten_nganh']) ?>
                            </span>

                            <?php if (!empty($r['ma_nganh'])): ?>
                            <span class="text-slate-900 font-bold whitespace-nowrap">Mã ngành:</span>
                            <span class="text-red-700 font-extrabold text-base"><?= htmlspecialchars($r['ma_nganh']) ?></span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Action Button: Xem chi tiết (Căn giữa, không mũi tên, padding thoải mái) -->
                <div class="pt-3 text-center">
                    <a href="<?= url('/login?redirect=' . urlencode(url('/application/results'))) ?>"
                       class="inline-flex items-center justify-center py-3 px-10 bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 text-white font-bold text-sm md:text-base rounded-2xl shadow-lg hover:shadow-xl transition-all active:scale-95">
                        Xem chi tiết
                    </a>
                </div>

            </div>
        </div>

        <?php endforeach; ?>

        <!-- Footer -->
        <div class="text-center text-xs text-white/50 pb-6 no-print">
            <p>Trường Đại học Hùng Vương &bull; Hệ thống Tuyển sinh</p>
        </div>
    </div>

</body>
</html>
