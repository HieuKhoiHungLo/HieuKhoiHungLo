<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
        <div>
            <p class="text-xs text-slate-400 uppercase font-bold mb-1">Tổng hồ sơ</p>
            <p class="text-3xl font-black text-slate-700"><?= $stats['total'] ?></p>
        </div>
        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
            <i class="fas fa-users text-xl"></i>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
        <div>
            <p class="text-xs text-amber-500 uppercase font-bold mb-1">Chờ duyệt</p>
            <p class="text-3xl font-black text-amber-500"><?= $stats['pending'] ?></p>
        </div>
        <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center text-amber-500">
            <i class="fas fa-hourglass-half text-xl"></i>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
        <div>
            <p class="text-xs text-emerald-500 uppercase font-bold mb-1">Đã duyệt</p>
            <p class="text-3xl font-black text-emerald-500"><?= $stats['approved'] ?></p>
        </div>
        <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-500">
            <i class="fas fa-check-circle text-xl"></i>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
        <div>
            <p class="text-xs text-rose-500 uppercase font-bold mb-1">Từ chối</p>
            <p class="text-3xl font-black text-rose-500"><?= $stats['rejected'] ?></p>
        </div>
        <div class="w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center text-rose-500">
            <i class="fas fa-times-circle text-xl"></i>
        </div>
    </div>
</div>
