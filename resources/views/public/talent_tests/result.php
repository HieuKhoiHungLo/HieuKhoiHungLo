<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-2xl w-full animate-fade-in">
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-blue-100/50 overflow-hidden border border-slate-100">
            <!-- Header Section -->
            <div class="bg-blue-600 p-8 text-white text-center relative">
                <div class="absolute top-4 right-6 opacity-20 text-6xl">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h2 class="text-sm font-bold uppercase tracking-[0.2em] mb-2 opacity-80">Kết quả thi năng khiếu</h2>
                <h1 class="text-3xl font-black mb-1"><?= htmlspecialchars($data['name']) ?></h1>
                <p class="font-medium opacity-90"><?= htmlspecialchars($data['session_name']) ?></p>
            </div>

            <div class="p-8 md:p-12">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <!-- Left Side: Basic Info -->
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Số báo danh</label>
                            <div class="text-xl font-black text-blue-600 font-mono"><?= htmlspecialchars($data['exam_number']) ?></div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Ngày sinh</label>
                            <div class="text-lg font-bold text-slate-700"><?= date('d/m/Y', strtotime($data['birth_date'])) ?></div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Môn thi</label>
                            <div class="text-lg font-bold text-slate-700"><?= htmlspecialchars($data['subject_name']) ?></div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Phòng thi</label>
                            <div class="text-lg font-bold text-slate-700"><?= htmlspecialchars($data['room_name'] ?: 'Chưa phân') ?></div>
                        </div>
                    </div>

                    <!-- Right Side: Score Circle -->
                    <div class="flex flex-col items-center justify-center bg-slate-50 rounded-[2rem] p-8 border border-slate-100">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Điểm thi năng khiếu</label>
                        <?php if ($data['score'] !== null): ?>
                            <div class="relative w-32 h-32 flex items-center justify-center">
                                <svg class="absolute w-full h-full -rotate-90">
                                    <circle cx="64" cy="64" r="60" stroke="currentColor" stroke-width="8" fill="transparent" class="text-slate-200" />
                                    <circle cx="64" cy="64" r="60" stroke="currentColor" stroke-width="8" fill="transparent" class="text-blue-600" stroke-dasharray="<?= ($data['score'] / 10) * 377 ?> 377" />
                                </svg>
                                <span class="text-4xl font-black text-slate-900"><?= number_format($data['score'], 1) ?></span>
                            </div>
                            <div class="mt-4 text-xs font-bold text-emerald-600 bg-emerald-50 px-4 py-2 rounded-full">
                                <i class="fas fa-check-circle mr-1"></i> Đã công bố
                            </div>
                        <?php else: ?>
                            <div class="text-slate-300 text-5xl mb-4">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="text-sm font-bold text-slate-400">Chưa công bố điểm</div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($data['note']): ?>
                    <div class="mt-10 p-5 bg-amber-50 rounded-2xl border border-amber-100 flex items-start gap-4">
                        <i class="fas fa-comment-dots text-amber-400 mt-1"></i>
                        <div>
                            <div class="text-[10px] font-black text-amber-600 uppercase mb-1">Ghi chú từ hội đồng</div>
                            <p class="text-sm text-amber-700 italic"><?= htmlspecialchars($data['note']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-12 flex flex-col md:flex-row gap-4">
                    <button onclick="window.print()" class="flex-1 py-4 bg-slate-100 text-slate-600 font-bold rounded-2xl hover:bg-slate-200 transition flex items-center justify-center gap-2">
                        <i class="fas fa-print"></i> In kết quả
                    </button>
                    <a href="<?= url('/tra-cuu-nang-khieu') ?>" class="flex-1 py-4 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 transition flex items-center justify-center gap-2 shadow-xl shadow-blue-100">
                        <i class="fas fa-search"></i> Tra cứu mới
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-10 mb-10">
            <p class="text-slate-400 text-xs font-medium">© 2026 Trường Đại học Hùng Vương - Cổng thông tin tuyển sinh</p>
        </div>
    </div>

    <style>
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none; }
            .shadow-2xl { shadow: none; }
        }
    </style>
</body>
</html>
