<!-- Overview Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-6 rounded-2xl shadow-lg shadow-indigo-200 text-white relative overflow-hidden group">
        <div class="relative z-10">
            <p class="text-indigo-100 text-[10px] font-bold uppercase tracking-wider mb-2">Tổng hồ sơ</p>
            <p class="text-4xl font-black"><?= number_format($stats['total']) ?></p>
            <div class="mt-3 space-y-1 text-[11px]">
                <div class="flex items-center gap-2 text-indigo-100">
                    <i class="fas fa-calendar-day opacity-70"></i>
                    <span>Hôm nay: <strong class="text-white"><?= $stats['today'] ?? 0 ?></strong></span>
                </div>
                <div class="flex items-center gap-2 text-indigo-100">
                    <i class="fas fa-calendar-week opacity-70"></i>
                    <span>Tuần này: <strong class="text-white"><?= $stats['this_week'] ?? 0 ?></strong></span>
                </div>
            </div>
        </div>
        <i class="fas fa-users absolute -bottom-4 -right-4 text-9xl text-blue-400 opacity-20 group-hover:opacity-30 transition transform group-hover:scale-110"></i>
    </div>
    
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-4">
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Đã duyệt</p>
            <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg"><i class="fas fa-check"></i></span>
        </div>
        <p class="text-3xl font-black text-slate-800"><?= number_format($stats['approved']) ?></p>
         <div class="mt-2 text-[11px] font-medium text-emerald-600">
            <?= $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100, 1) : 0 ?>% tỷ lệ duyệt
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-4">
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Chờ duyệt</p>
            <span class="p-2 bg-amber-50 text-amber-600 rounded-lg"><i class="fas fa-clock"></i></span>
        </div>
        <p class="text-3xl font-black text-slate-800"><?= number_format($stats['pending']) ?></p>
         <div class="mt-2 text-[11px] font-medium text-amber-600">
             Cần xử lý gấp
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-4">
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Từ chối</p>
            <span class="p-2 bg-rose-50 text-rose-600 rounded-lg"><i class="fas fa-times"></i></span>
        </div>
        <p class="text-3xl font-black text-slate-800"><?= number_format($stats['rejected']) ?></p>
         <div class="mt-2 text-[11px] font-medium text-rose-600">
             Hồ sơ không đạt
        </div>
    </div>
</div>
