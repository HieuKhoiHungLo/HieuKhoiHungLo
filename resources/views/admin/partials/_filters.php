<div class="flex flex-col gap-3 <?= isset($mode) && $mode === 'review' ? 'mb-2' : 'mb-6' ?>">
    <!-- Hàng trên: Tiêu đề + Cấu hình bản & Bộ lọc form -->
    <div class="flex flex-wrap items-center justify-between gap-4 w-full">
        <div class="flex items-center gap-4">
            <h3 class="<?= isset($mode) && $mode === 'review' ? 'text-base' : 'text-lg' ?> font-bold text-slate-800 uppercase tracking-wide">
                <?php
                if ($mode === 'review') echo 'Xét duyệt Hồ sơ';
                elseif ($mode === 'all') echo 'Thí sinh chưa nhập hồ sơ (' . ($total ?? 0) . ')';
                else echo 'Danh sách Hồ sơ';
                ?>
            </h3>

            <!-- Column Config Dropdown (Alpine context from parent) -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 <?= isset($mode) && $mode === 'review' ? 'px-2 py-1' : 'px-3 py-2' ?> bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:text-[#0066FF] hover:border-indigo-300 transition shadow-sm">
                    <i class="fas fa-columns"></i> Cấu hình bảng
                </button>
                <div x-show="open" @click.away="open = false" x-cloak class="absolute top-full left-0 mt-2 w-48 bg-white border border-slate-100 rounded-xl shadow-lg z-50 p-2">
                    <template x-for="key in Object.keys(showCols)">
                        <label x-show="!fixedCols.includes(key)" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 rounded-lg cursor-pointer">
                            <input type="checkbox" :checked="showCols[key]" @change="toggleCol(key)" class="rounded border-gray-300 text-[#0066FF] focus:ring-indigo-500" :disabled="fixedCols.includes(key)">
                            <span class="text-sm text-slate-700 font-medium" x-text="colLabel(key)"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>

        <form action="<?= $baseUrl ?>" method="GET" class="flex flex-wrap gap-3">
            <!-- Persist Sort & Search Params -->
            <input type="hidden" name="sort" value="<?= $filters['sort'] ?? '' ?>">
            <input type="hidden" name="dir" value="<?= $filters['dir'] ?? '' ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filters['status'] ?? '') ?>">
            <input type="hidden" name="hoc_ba_status" value="<?= htmlspecialchars($filters['hoc_ba_status'] ?? '') ?>">
            <input type="hidden" name="limit" value="<?= htmlspecialchars($filters['limit'] ?? 10) ?>">
            <input type="hidden" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            
            <?php 
            // Persist extra filters (f_*)
            if (isset($filters) && is_array($filters)) {
                foreach ($filters as $k => $v) {
                    if (str_starts_with($k, 'f_') && !empty($v)) {
                        echo '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars($v) . '">';
                    }
                }
            }
            ?>

            <script>
            window.applyCandidateFilters = function() {
                if (typeof Loading !== 'undefined') Loading.show();
                
                // IGNORE current URL params entirely to prevent "stale" values leaking through
                const params = new URLSearchParams();
                
                // 1. Mandatory state from form (defaults)
                const filterForm = document.querySelector('form[action*="<?= $baseUrl ?>"]');
                
                // Add static filters that aren't in elements
                params.set('page', '1');
                params.set('sort', '<?= $filters['sort'] ?? 'ngay_tao' ?>');
                params.set('dir', '<?= $filters['dir'] ?? 'DESC' ?>');
                params.set('limit', '<?= $filters['limit'] ?? (isset($mode) && $mode === 'review' ? 8 : 10) ?>');
                if ('<?= $appStatusFilter ?? '' ?>') params.set('app_status', '<?= $appStatusFilter ?? '' ?>');

                // 2. Collection from Header Search Inputs (CCCD, Name, f_note, etc.)
                document.querySelectorAll('[data-filter-key]').forEach(el => {
                    const key = el.getAttribute('data-filter-key');
                    const val = el.value.trim();
                    if (val) params.set(key, val);
                });

                // 3. Collection from Form Selects
                if (filterForm) {
                    filterForm.querySelectorAll('select[name]').forEach(s => {
                        if (s.value) params.set(s.name, s.value);
                    });
                    
                    // Sync specific overrides
                    const limitSelect = filterForm.querySelector('[name="limit"]');
                    if (limitSelect && limitSelect.value) params.set('limit', limitSelect.value);
                }

                // 4. Cache-buster to force Hard Reload
                params.set('_t', new Date().getTime());

                const finalUrl = '<?= $baseUrl ?>' + '?' + params.toString();
                console.log('Force-Clean Filter Apply:', finalUrl);
                window.location.href = finalUrl;
            };
            </script>

            <!-- Nút Refresh nằm kề Cấu hình bảng (?) Thực ra form float mảng phải nên nó sẽ nằm ở đầu do flex mặc định form HTML hoặc cuối -->
            <button type="button" onclick="window.applyCandidateFilters()" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-xl hover:text-blue-600 hover:border-blue-200 transition shadow-sm">
                <i class="fas fa-sync-alt text-sm"></i>
            </button>

            <select name="year" id="filter_year" aria-label="Năm tuyển sinh" onchange="window.applyCandidateFilters()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-600 focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm cursor-pointer min-w-[100px]">
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= ($filters['year'] ?? '') == $y ? 'selected' : '' ?>>Khóa <?= $y ?></option>
                <?php endforeach; ?>
            </select>

            <select name="session_id" id="filter_session" aria-label="Đợt tuyển sinh" onchange="window.applyCandidateFilters()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-600 focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm cursor-pointer max-w-[250px]">
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
        </form>
    </div>

    <!-- Hàng dưới: Các nút chức năng đặc quyền của Quản trị hệ thống -->
    <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin'): ?>
        <div class="flex items-center flex-wrap gap-3 pt-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-widest mr-2 flex items-center gap-1">
                <i class="fas fa-cogs"></i> Chức năng:
            </span>
            
            <?php if (isset($mode) && $mode === 'review'): ?>
            <button onclick="document.getElementById('modal-bulk-approve').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2 bg-emerald-50 border border-emerald-200 rounded-xl text-sm font-semibold text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300 transition shadow-sm">
                <i class="fas fa-file-check"></i> Duyệt theo file
            </button>
            <?php if (!empty($filters['session_id'])): ?>
            <button onclick="document.getElementById('modal-bulk-approve-all').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2 bg-amber-50 border border-amber-200 rounded-xl text-sm font-semibold text-amber-700 hover:bg-amber-100 hover:border-amber-300 transition shadow-sm">
                <i class="fas fa-check-double"></i> Duyệt tất cả
            </button>
            <button onclick="document.getElementById('modal-bulk-unapprove-all').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2 bg-red-50 border border-red-200 rounded-xl text-sm font-semibold text-red-700 hover:bg-red-100 hover:border-red-300 transition shadow-sm">
                <i class="fas fa-undo"></i> Hủy duyệt tất cả
            </button>
            <?php endif; ?>
            <button onclick="document.getElementById('modal-bulk-transcript').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2 bg-blue-50 border border-blue-200 rounded-xl text-sm font-semibold text-blue-700 hover:bg-blue-100 hover:border-blue-300 transition shadow-sm">
                <i class="fas fa-file-excel"></i> Cập nhật học bạ
            </button>
            <?php endif; ?>

            <?php if (isset($mode) && $mode === 'all'): ?>
            <a href="<?= url('/admin/candidate-management/export') ?>?<?= http_build_query($_GET) ?>" class="flex items-center gap-2 px-4 py-2 bg-emerald-50 border border-emerald-200 rounded-xl text-sm font-semibold text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300 transition shadow-sm">
                <i class="fas fa-file-excel"></i> Xuất dữ liệu
            </a>
            <?php endif; ?>

            <a href="<?= url('/admin/candidates/trash') ?>" class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 hover:text-red-600 hover:border-red-200 transition shadow-sm" title="Xem hồ sơ đã xóa">
                <i class="fas fa-trash-alt"></i> Thùng rác
            </a>
        </div>
    <?php endif; ?>
</div>