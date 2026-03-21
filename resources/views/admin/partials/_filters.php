<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <h3 class="text-lg font-bold text-slate-800 uppercase tracking-wide">
            <?php
            if ($mode === 'review') echo 'Xét duyệt Hồ sơ';
            elseif ($mode === 'all') echo 'Thí sinh chưa nhập hồ sơ (' . ($total ?? 0) . ')';
            else echo 'Danh sách Hồ sơ';
            ?>
        </h3>

        <!-- Column Config Dropdown (Alpine context from parent) -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:text-[#0066FF] hover:border-indigo-300 transition shadow-sm">
                <i class="fas fa-columns"></i> Cấu hình bảng
            </button>
            <div x-show="open" @click.away="open = false" x-cloak class="absolute top-full left-0 mt-2 w-48 bg-white border border-slate-100 rounded-xl shadow-lg z-20 p-2">
                <template x-for="key in Object.keys(showCols)">
                    <label x-show="!fixedCols.includes(key)" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 rounded-lg cursor-pointer">
                        <input type="checkbox" :checked="showCols[key]" @change="toggleCol(key)" class="rounded border-gray-300 text-[#0066FF] focus:ring-indigo-500" :disabled="fixedCols.includes(key)">
                        <span class="text-sm text-slate-700 font-medium" x-text="colLabel(key)"></span>
                    </label>
                </template>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['admin_role_id']) && $_SESSION['admin_role_id'] == 1): ?>
        <div class="flex items-center gap-3">
            <a href="<?= url('/admin/candidates/trash') ?>" class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:text-red-600 hover:border-red-200 transition shadow-sm" title="Xem hồ sơ đã xóa">
                <i class="fas fa-trash-alt"></i> Thùng rác
            </a>
        </div>
    <?php endif; ?>


    <form action="<?= $baseUrl ?>" method="GET" class="flex flex-wrap gap-3">
        <!-- Persist Sort Params -->
        <input type="hidden" name="sort" value="<?= $filters['sort'] ?>">
        <input type="hidden" name="dir" value="<?= $filters['dir'] ?>">

        <select name="year" onchange="this.form.submit()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-600 focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm cursor-pointer min-w-[100px]">
            <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= ($filters['year'] ?? '') == $y ? 'selected' : '' ?>>Khóa <?= $y ?></option>
            <?php endforeach; ?>
        </select>

        <select name="session_id" onchange="this.form.submit()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-600 focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm cursor-pointer max-w-[250px]">
            <option value="">-- Tất cả các đợt --</option>
            <?php foreach ($yearSessions as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ($filters['session_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars(!empty($s['ten_dot']) ? $s['ten_dot'] : (!empty($s['ma_dot']) ? $s['ma_dot'] : 'Đợt ' . $s['id'] . ' - ' . date('d/m/Y', strtotime($s['ngay_bat_dau'])))) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($mode === 'all'): ?>
        <input type="hidden" name="app_status" value="ghost">
        <?php endif; ?>

        <button type="submit" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-xl hover:text-blue-600 hover:border-blue-200 transition shadow-sm">
            <i class="fas fa-sync-alt text-sm"></i>
        </button>
    </form>
</div>