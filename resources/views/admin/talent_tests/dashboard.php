<div class="mb-8 flex justify-between items-end">
    <div>
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3 text-slate-500 text-sm font-medium">
                <li class="inline-flex items-center">
                    <a href="<?= url('/admin/talent-tests') ?>" class="hover:text-blue-600 transition">Thi năng khiếu</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-[10px] mx-2"></i>
                        <a href="<?= url('/admin/talent-tests/edit?id=' . $session['id']) ?>" class="hover:text-blue-600 transition">Chi tiết đợt thi</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-[10px] mx-2"></i>
                        <span>Báo cáo thống kê</span>
                    </div>
                </li>
            </ol>
        </nav>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Thống kê đợt thi: <?= htmlspecialchars($session['session_name']) ?></h1>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mr-4">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <div class="text-sm font-medium text-slate-500">Tổng thí sinh</div>
            <div class="text-2xl font-black text-slate-900"><?= number_format($stats['total']) ?></div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center">
        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mr-4">
            <i class="fas fa-check-circle"></i>
        </div>
        <div>
            <div class="text-sm font-medium text-slate-500">Đã có điểm</div>
            <div class="text-2xl font-black text-slate-900"><?= number_format($stats['graded']) ?></div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center">
        <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl mr-4">
            <i class="fas fa-spinner"></i>
        </div>
        <div>
            <div class="text-sm font-medium text-slate-500">Tỷ lệ hoàn thành</div>
            <div class="text-2xl font-black text-slate-900"><?= $stats['total'] > 0 ? round(($stats['graded'] / $stats['total']) * 100, 1) : 0 ?>%</div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center text-2xl mr-4">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div>
            <div class="text-sm font-medium text-slate-500">Năm tuyển sinh</div>
            <div class="text-2xl font-black text-slate-900"><?= $session['year'] ?></div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-lg font-bold text-slate-800 flex items-center">
                <i class="fas fa-chart-pie mr-3 text-slate-400"></i> Phân bổ thí sinh theo ngành
            </h2>
        </div>
        <div class="p-8">
            <div class="space-y-6">
                <?php foreach ($subjectStats as $sub): ?>
                <?php $percent = $stats['total'] > 0 ? ($sub['count'] / $stats['total']) * 100 : 0; ?>
                <div>
                    <div class="flex justify-between items-end mb-2">
                        <span class="font-bold text-slate-700"><?= htmlspecialchars($sub['subject_name']) ?></span>
                        <span class="text-sm font-medium text-slate-500"><?= $sub['count'] ?> TS (<?= round($percent, 1) ?>%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3">
                        <div class="bg-blue-600 h-3 rounded-full" style="width: <?= $percent ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-lg font-bold text-slate-800 flex items-center">
                <i class="fas fa-chart-bar mr-3 text-slate-400"></i> Thống kê phổ điểm
            </h2>
        </div>
        <div class="p-8">
            <div class="space-y-6">
                <?php 
                $maxDist = 0;
                foreach ($scoreDistribution as $d) if ($d['count'] > $maxDist) $maxDist = $d['count'];
                
                foreach ($scoreDistribution as $dist): 
                    $p = $maxDist > 0 ? ($dist['count'] / $maxDist) * 100 : 0;
                    $color = 'bg-slate-400';
                    if ($dist['range'] == '>= 9') $color = 'bg-emerald-500';
                    if ($dist['range'] == '7 - 9') $color = 'bg-blue-500';
                    if ($dist['range'] == '5 - 7') $color = 'bg-amber-500';
                    if ($dist['range'] == '< 5') $color = 'bg-rose-500';
                ?>
                <div class="flex items-center gap-4">
                    <div class="w-20 text-sm font-bold text-slate-600"><?= $dist['range'] ?></div>
                    <div class="flex-1 bg-slate-100 rounded-full h-8 overflow-hidden relative">
                        <div class="<?= $color ?> h-full" style="width: <?= $p ?>%"></div>
                        <div class="absolute inset-0 flex items-center px-4 font-bold text-xs <?= $p > 50 ? 'text-white' : 'text-slate-700' ?>">
                            <?= $dist['count'] ?> bài thi
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-8 pt-6 border-t border-slate-100 text-sm text-slate-400 italic">
                * Thống kê dựa trên các thí sinh đã được nhập điểm vào hệ thống.
            </div>
        </div>
    </div>
</div>

<div class="flex justify-center">
    <a href="<?= url('/admin/talent-tests/edit?id=' . $session['id']) ?>" class="px-8 py-4 bg-slate-900 text-white font-bold rounded-2xl hover:bg-slate-800 transition flex items-center shadow-xl shadow-slate-200">
        <i class="fas fa-arrow-left mr-3"></i> QUAY LẠI TRANG CHI TIẾT
    </a>
</div>
