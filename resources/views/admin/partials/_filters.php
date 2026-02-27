<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <h3 class="text-lg font-bold text-slate-800 uppercase tracking-wide">
            <?= $mode === 'review' ? 'Xét duyệt Hồ sơ' : 'Danh sách Thí sinh' ?>
        </h3>
        
        <!-- Column Config Dropdown (Alpine context from parent) -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:text-[#0066FF] hover:border-indigo-300 transition shadow-sm">
                <i class="fas fa-columns"></i> Cấu hình bảng
            </button>
            <div x-show="open" @click.away="open = false" x-cloak class="absolute top-full left-0 mt-2 w-48 bg-white border border-slate-100 rounded-xl shadow-lg z-20 p-2">
                <template x-for="key in Object.keys(showCols)">
                    <label class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 rounded-lg cursor-pointer">
                        <input type="checkbox" :checked="showCols[key]" @change="toggleCol(key)" class="rounded border-gray-300 text-[#0066FF] focus:ring-indigo-500">
                        <span class="text-sm text-slate-700 font-medium" x-text="colLabel(key)"></span>
                    </label>
                </template>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <form action="<?= url('/admin/calculate-scores') ?>" method="POST" onsubmit="if(confirm('Hệ thống sẽ tính toán lại điểm xét tuyển cho TOÀN BỘ thí sinh. Quá trình này có thể mất vài phút. Bạn có chắc chắn không?')) { Loading.show(); return true; } return false;">
            <?= csrf_field() ?>
            <button type="submit" class="flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-rose-500 to-orange-500 text-white border border-transparent rounded-lg text-sm font-bold hover:shadow-lg hover:from-rose-600 hover:to-orange-600 transition shadow-md">
                <i class="fas fa-calculator animate-pulse"></i> Tính điểm
            </button>
        </form>

        <a href="<?= url('/admin/candidates/trash') ?>" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:text-red-600 hover:border-red-200 transition shadow-sm" title="Xem hồ sơ đã xóa">
            <i class="fas fa-trash-alt"></i> Thùng rác
        </a>
    </div>
    
    <form action="<?= $mode === 'review' ? url('/admin/review-management') : url('/admin/dashboard') ?>" method="GET" class="flex flex-wrap gap-3">
        <!-- Persist Sort Params -->
        <input type="hidden" name="sort" value="<?= $filters['sort'] ?>">
        <input type="hidden" name="dir" value="<?= $filters['dir'] ?>">

        <div class="relative">
            <input type="text" name="search" placeholder="Tìm CCCD / Tiêu đề..." 
                   value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                   class="pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none w-56 shadow-sm">
            <i class="fas fa-search absolute left-3.5 top-2.5 text-slate-400 text-xs"></i>
        </div>
        
        <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm cursor-pointer">
            <option value="">-- Trạng thái --</option>
            <option value="Chờ duyệt" <?= ($filters['status'] ?? '') == 'Chờ duyệt' ? 'selected' : '' ?>>Chờ duyệt</option>
            <option value="Đã duyệt" <?= ($filters['status'] ?? '') == 'Đã duyệt' ? 'selected' : '' ?>>Đã duyệt</option>
            <option value="Từ chối" <?= ($filters['status'] ?? '') == 'Từ chối' ? 'selected' : '' ?>>Từ chối</option>
        </select>

        <select name="year" onchange="this.form.submit()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm cursor-pointer min-w-[100px]">
            <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= ($filters['year'] ?? '') == $y ? 'selected' : '' ?>>Năm <?= $y ?></option>
            <?php endforeach; ?>
        </select>

        <select name="session_id" onchange="this.form.submit()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm cursor-pointer max-w-[200px]">
            <option value="">-- Tất cả đợt --</option>
            <?php foreach ($yearSessions as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ($filters['session_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars(!empty($s['ten_dot']) ? $s['ten_dot'] : (!empty($s['ma_dot']) ? $s['ma_dot'] : 'Đợt ' . $s['id'] . ' - ' . date('d/m/Y', strtotime($s['ngay_bat_dau'])))) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="w-9 h-9 flex items-center justify-center bg-[#0066FF] text-white rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition">
            <i class="fas fa-filter text-xs"></i>
        </button>
    </form>
</div>
