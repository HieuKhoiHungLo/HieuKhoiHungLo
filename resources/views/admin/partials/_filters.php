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

    <!-- Hàng dưới: Các nút chức năng đặc quyền của Quản trị hệ thống (Fixed Bottom Action Bar) -->
    <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin'): ?>
        <div id="review-management-action-bar" style="
            position: fixed;
            bottom: 0;
            left: var(--sidebar-width);
            right: 0;
            z-index: 40;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid #e2e8f0;
            padding: 16px 32px;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            transition: left 0.3s ease;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
        ">
            <div class="flex items-center gap-3 flex-wrap">
                <?php if (isset($mode) && $mode === 'review'): ?>
                <button onclick="document.getElementById('modal-bulk-approve').classList.remove('hidden')" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:-translate-y-0.5 hover:shadow-lg">
                    Duyệt theo file
                </button>
                <?php if (!empty($filters['session_id'])): ?>
                <button onclick="document.getElementById('modal-bulk-approve-all').classList.remove('hidden')" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:-translate-y-0.5 hover:shadow-lg">
                    Duyệt tất cả
                </button>
                <button onclick="document.getElementById('modal-bulk-unapprove-all').classList.remove('hidden')" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:-translate-y-0.5 hover:shadow-lg">
                    Hủy duyệt tất cả
                </button>
                <button type="button" onclick="openSendEmailToAllModal()" class="px-5 py-2.5 bg-[#0066FF] hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:-translate-y-0.5 hover:shadow-lg">
                    Gửi thư tất cả
                </button>
                <button type="button" onclick="document.getElementById('modal-bulk-reset-password-default').classList.remove('hidden')" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:-translate-y-0.5 hover:shadow-lg">
                    Mật khẩu mặc định
                </button>
                <?php endif; ?>
                <button onclick="document.getElementById('modal-bulk-transcript').classList.remove('hidden')" class="px-5 py-2.5 bg-[#0066FF] hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:-translate-y-0.5 hover:shadow-lg">
                    Cập nhật học bạ
                </button>
                <button onclick="document.getElementById('modal-bulk-candidate-info').classList.remove('hidden')" class="px-5 py-2.5 bg-[#0066FF] hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:-translate-y-0.5 hover:shadow-lg">
                    Cập nhật thông tin
                </button>
                <?php endif; ?>

                <?php if (isset($mode) && $mode === 'all'): ?>
                <a href="<?= url('/admin/candidate-management/export') ?>?<?= http_build_query($_GET) ?>" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:-translate-y-0.5 hover:shadow-lg">
                    Xuất dữ liệu
                </a>
                <button type="button" onclick="openSendEmailToAllModal()" class="px-5 py-2.5 bg-[#0066FF] hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all shadow-md hover:-translate-y-0.5 hover:shadow-lg">
                    Gửi thư tất cả
                </button>
                <?php endif; ?>
            </div>

            <div>
                <a href="<?= url('/admin/candidates/trash') ?>" class="px-5 py-2.5 bg-slate-100 border border-slate-200 text-slate-600 hover:text-red-600 hover:bg-red-50 hover:border-red-200 text-xs font-bold rounded-xl transition-all shadow-sm flex items-center justify-center">
                    Thùng rác
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>