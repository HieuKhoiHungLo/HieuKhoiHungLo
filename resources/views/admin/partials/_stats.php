<!-- Unified Stats Cards - Branded HVU Blue -->
<?php $isReviewMode = isset($mode) && $mode === 'review'; ?>

<?php if ($isReviewMode): ?>
<!-- Ultra-compact single line cards for Review Mode -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-2">
    <!-- Primary Card: Total -->
    <div class="px-3 py-1.5 rounded-xl shadow-sm text-white flex items-center justify-between" style="background: linear-gradient(135deg, #0066FF 0%, #003D99 100%) !important;">
        <div class="flex items-center gap-2.5">
            <div class="bg-white/20 w-6 h-6 flex items-center justify-center rounded-lg backdrop-blur-md">
                <i class="fas fa-users-viewfinder text-[10px] text-white"></i>
            </div>
            <p class="text-white/90 text-[10px] font-black uppercase tracking-widest font-heading">Tổng hồ sơ</p>
        </div>
        <p class="text-lg font-black tracking-tight font-heading"><?= number_format($stats['total']) ?></p>
    </div>

    <!-- Approved -->
    <div class="bg-white px-3 py-1.5 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="w-6 h-6 flex items-center justify-center bg-emerald-50 text-emerald-600 rounded-lg border border-emerald-100/50">
                <i class="fas fa-check-circle text-[10px]"></i>
            </div>
            <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest font-heading">Đã duyệt</p>
        </div>
        <p class="text-lg font-black text-slate-900 tracking-tight font-heading"><?= number_format($stats['approved']) ?></p>
    </div>

    <!-- Pending -->
    <div class="bg-white px-3 py-1.5 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="w-6 h-6 flex items-center justify-center bg-amber-50 text-amber-600 rounded-lg border border-amber-100/50">
                <i class="fas fa-hourglass-half text-[10px]"></i>
            </div>
            <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest font-heading">Chờ duyệt</p>
        </div>
        <p class="text-lg font-black text-slate-900 tracking-tight font-heading"><?= number_format($stats['pending']) ?></p>
    </div>

    <!-- Require Edit -->
    <div class="bg-white px-3 py-1.5 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="w-6 h-6 flex items-center justify-center bg-orange-50 text-orange-600 rounded-lg border border-orange-100/50">
                <i class="fas fa-edit text-[10px]"></i>
            </div>
            <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest font-heading">Yêu cầu sửa</p>
        </div>
        <p class="text-lg font-black text-slate-900 tracking-tight font-heading"><?= number_format($stats['require_edit'] ?? 0) ?></p>
    </div>
</div>
<?php else: ?>
<!-- Standard cards for Dashboard -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
    <!-- Primary Card: Total (HVU Brand Blue) -->
    <div class="p-5 rounded-2xl shadow-xl text-white relative overflow-hidden group transition-all duration-300 hover:-translate-y-1" style="background: linear-gradient(135deg, #0066FF 0%, #003D99 100%) !important;">
        <div class="relative z-10 flex flex-col justify-between h-full">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-white/80 text-[10px] font-black uppercase tracking-widest font-heading">Tổng hồ sơ</p>
                    <div class="bg-white/20 p-2 rounded-xl backdrop-blur-md">
                        <i class="fas fa-users-viewfinder text-sm text-white"></i>
                    </div>
                </div>
                <p class="text-4xl lg:text-5xl font-black mb-4 tracking-tight font-heading"><?= number_format($stats['total']) ?></p>
            </div>
            
            <div class="flex flex-col gap-2.5 border-t border-white/20 pt-4 mt-auto">
                <div class="flex items-center justify-between text-[11px] font-bold">
                    <span class="text-blue-100 uppercase tracking-tighter">Hôm nay:</span>
                    <span class="bg-white/20 text-white px-2 py-0.5 rounded-lg font-black backdrop-blur-sm"><?= number_format($stats['today'] ?? 0) ?></span>
                </div>
                <div class="flex items-center justify-between text-[11px] font-bold">
                    <span class="text-blue-100 uppercase tracking-tighter">Tuần này:</span>
                    <span class="bg-white/20 text-white px-2 py-0.5 rounded-lg font-black backdrop-blur-sm"><?= number_format($stats['this_week'] ?? 0) ?></span>
                </div>
            </div>
        </div>
        <div class="absolute -top-12 -right-12 w-32 h-32 bg-white/10 rounded-full blur-3xl transition-transform group-hover:scale-125"></div>
    </div>

    <!-- Card: Approved -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between transition-all duration-300 hover:shadow-md hover:-translate-y-1">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest font-heading mb-1">Đã duyệt</p>
                <p class="text-3xl mt-2 font-black text-slate-900 tracking-tight font-heading"><?= number_format($stats['approved']) ?></p>
            </div>
            <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100/50">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
        </div>
        
        <div class="mt-4">
            <div class="flex items-center justify-between mb-1.5 px-0.5">
                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-wider">Tỷ lệ duyệt</span>
                <span class="text-[11px] font-black text-emerald-600"><?= $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100, 1) : 0 ?>%</span>
            </div>
            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000" style="width: <?= $stats['total'] > 0 ? ($stats['approved'] / $stats['total']) * 100 : 0 ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Card: Pending -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between transition-all duration-300 hover:shadow-md hover:-translate-y-1">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest font-heading mb-1">Chờ duyệt</p>
                <p class="text-3xl mt-2 font-black text-slate-900 tracking-tight font-heading"><?= number_format($stats['pending']) ?></p>
            </div>
            <div class="p-3 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100/50">
                <i class="fas fa-hourglass-half text-xl"></i>
            </div>
        </div>
        
        <div class="mt-4">
            <span class="inline-flex items-center px-2.5 py-1 bg-amber-50 text-amber-700 rounded-lg text-[9px] font-black uppercase tracking-wider border border-amber-200/50">
                <i class="fas fa-bolt mr-1.5 opacity-70"></i> Cần xử lý ngay
            </span>
        </div>
    </div>

    <!-- Card: Require Edit -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between transition-all duration-300 hover:shadow-md hover:-translate-y-1">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest font-heading mb-1">Yêu cầu sửa</p>
                <p class="text-3xl mt-2 font-black text-slate-900 tracking-tight font-heading"><?= number_format($stats['require_edit'] ?? 0) ?></p>
            </div>
            <div class="p-3 rounded-2xl bg-orange-50 text-orange-600 border border-orange-100/50">
                <i class="fas fa-edit text-xl"></i>
            </div>
        </div>
        
        <div class="mt-4">
            <div class="w-full h-2 bg-slate-50 rounded-full border border-slate-100"></div>
        </div>
    </div>
</div>
<?php endif; ?>
