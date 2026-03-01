<!-- Bulk Actions & Table -->
<form action="<?= $mode === 'review' ? url('/admin/candidates/bulk-action') : '#' ?>" method="POST" id="bulk-form">
    <?= csrf_field() ?>
    <input type="hidden" name="redirect_to" value="<?= $_SERVER['REQUEST_URI'] ?>">
    
    <?php if ($mode === 'review'): ?>
    <div id="bulk-actions" class="hidden bg-indigo-50 border border-indigo-100 p-3 rounded-xl mb-4 flex items-center justify-between shadow-sm animate-fade-in-down">
        <div class="flex items-center space-x-3">
            <span class="font-bold text-indigo-700 text-sm"><span id="selected-count">0</span> đã chọn</span>
            
            <select name="action" id="bulk-action-select" onchange="toggleBulkOptions()" class="px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-sm font-bold text-indigo-700 outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Action...</option>
                <option value="update_status">Đổi Trạng thái</option>
                <option value="transfer">Chuyển đợt</option>
                <option value="send_email">Gửi thư</option>
                <option value="delete">Xóa hồ sơ</option>
            </select>

            <!-- Status Options -->
            <select name="status" id="bulk-status-opt" class="hidden px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-sm outline-none">
                <option value="Chờ duyệt">Về Chờ duyệt</option>
                <option value="Đã duyệt">Duyệt ngay</option>
                <option value="Từ chối">Từ chối</option>
            </select>
        </div>
        <button type="submit" onclick="return confirm('Xác nhận thực hiện hành động này?')" class="px-4 py-1.5 bg-[#0066FF] text-white text-sm font-bold rounded-lg hover:bg-indigo-700 shadow-md transition">Apply</button>
    </div>
    <?php endif; ?>

    <!-- Mobile Card View -->
    <div class="block md:hidden space-y-4">
        <?php if (empty($candidates)): ?>
            <div class="bg-white rounded-2xl p-8 text-center text-slate-400 border border-slate-100 italic">
                <i class="fas fa-search text-3xl mb-2 opacity-20"></i>
                <p>Không tìm thấy dữ liệu.</p>
            </div>
        <?php else: ?>
            <?php foreach ($candidates as $c): ?>
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 space-y-3 relative">
                    <div class="absolute top-4 right-4">
                        <input type="checkbox" name="ids[]" value="<?= $c['so_cccd'] ?>" data-session-id="<?= $c['dot_tuyen_sinh_id'] ?>" class="item-checkbox rounded border-gray-300 text-[#0066FF] focus:ring-indigo-600 w-5 h-5">
                    </div>
                    <div class="flex items-center gap-3">
                        <?php $avatar = !empty($c['anh_dai_dien']) ? (strpos($c['anh_dai_dien'], 'http') === 0 ? google_drive_thumbnail_url($c['anh_dai_dien'], 'w100') : asset($c['anh_dai_dien'])) : null; ?>
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 shrink-0 overflow-hidden shadow-sm">
                            <?php if ($avatar): ?>
                                <img src="<?= $avatar ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-slate-300 text-xl"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="font-bold text-slate-800 text-base line-clamp-1"><?= htmlspecialchars($c['ho_va_ten']) ?></p>
                            <p class="text-xs text-slate-400 font-mono"><?= htmlspecialchars($c['so_cccd']) ?></p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-slate-50 p-2 rounded-lg">
                            <span class="block text-slate-400 font-bold uppercase text-[10px]">Trạng thái</span>
                            <?php 
                            $statuses = array_unique(explode(', ', $c['statuses'] ?? ''));
                            foreach ($statuses as $st): 
                                $color = 'slate';
                                if ($st == 'Chờ duyệt') $color = 'amber';
                                if ($st == 'Đã duyệt') $color = 'emerald';
                                if ($st == 'Từ chối') $color = 'rose';
                            ?>
                                <span class="inline-block text-<?= $color ?>-600 font-bold"><?= htmlspecialchars($st ?: 'Mới') ?></span>
                            <?php endforeach; ?>
                        </div>
                         <div class="bg-slate-50 p-2 rounded-lg">
                            <span class="block text-slate-400 font-bold uppercase text-[10px]">Ngày nộp</span>
                            <span class="font-bold text-slate-700"><?= date('d/m/Y', strtotime($c['ngay_tao'])) ?></span>
                        </div>
                    </div>

                    <?php if ($c['nv1']): ?>
                        <div class="text-xs bg-indigo-50 p-2 rounded-lg text-indigo-700 font-medium">
                            <span class="font-bold uppercase text-[10px] text-indigo-400 block">NV1</span>
                            <?= htmlspecialchars($c['nv1']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="flex justify-between items-center pt-2 border-t border-slate-50">
                        <a href="tel:<?= htmlspecialchars($c['dien_thoai']) ?>" class="text-slate-500 hover:text-indigo-600 text-xs font-bold flex items-center">
                            <i class="fas fa-phone mr-1"></i> Gọi điện
                        </a>
                        <a href="<?= url('/admin/review?cccd=' . $c['so_cccd']) ?>" class="px-4 py-1.5 bg-[#0066FF] text-white text-xs font-bold rounded-lg shadow-sm hover:bg-indigo-700 transition">
                            Duyệt hồ sơ
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-slate-100 overflow-x-auto">
        <table class="w-full text-left border-collapse table-auto min-w-[1200px]">
            <thead class="bg-slate-50 border-b border-slate-100 text-[11px] uppercase font-bold text-slate-500">
                <!-- Row 1: Titles -->
                <tr class="divide-x divide-slate-100">
                    <th class="px-2 py-3 w-10 text-center"></th>
                    <th class="px-3 py-3 w-12 text-center text-slate-500">STT</th>
                    <th class="px-3 py-3 w-32 text-center text-slate-500">Trạng thái</th>
                    <th class="px-4 py-3 min-w-[200px]">Thí sinh</th>
                    <th x-show="showCols.phone" class="px-4 py-3 w-32">Điện thoại</th>
                    <th x-show="showCols.email" class="px-4 py-3 w-48">Email</th>
                    <th x-show="showCols.gender" class="px-4 py-3 w-24">Giới tính</th>
                    <th x-show="showCols.dob" class="px-4 py-3 w-32">Ngày sinh</th>
                    <th x-show="showCols.province" class="px-4 py-3 w-32">Hộ khẩu</th>
                    <th x-show="showCols.school" class="px-4 py-3 w-48">Trường THPT</th>
                    <th x-show="showCols.nv1" class="px-4 py-3 w-40">NV1</th>
                    <th class="px-4 py-3 w-40">Ghi chú</th>
                    <?php if ($mode === 'review'): ?>
                    <th class="px-4 py-3 w-32 text-center">Hành động</th>
                    <?php endif; ?>
                </tr>
                <!-- Row 2: Filters -->
                <tr class="bg-white border-b border-slate-100 divide-x divide-slate-100">
                    <th class="px-2 py-2 text-center">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-[#0066FF] focus:ring-indigo-600">
                    </th>
                    <th class="px-2 py-2"></th>
                    <th class="px-2 py-2">
                        <select onchange="window.location.href=this.value" class="w-full text-[10px] border-slate-200 rounded px-1 py-1 outline-none focus:border-blue-400">
                            <option value="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/dashboard') . '?' . http_build_query(array_merge($filters, ['status' => ''])) ?>">(All)</option>
                            <option value="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/dashboard') . '?' . http_build_query(array_merge($filters, ['status' => 'Chờ duyệt'])) ?>" <?= ($filters['status'] ?? '') == 'Chờ duyệt' ? 'selected' : '' ?>>Chờ duyệt</option>
                            <option value="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/dashboard') . '?' . http_build_query(array_merge($filters, ['status' => 'Đã duyệt'])) ?>" <?= ($filters['status'] ?? '') == 'Đã duyệt' ? 'selected' : '' ?>>Đã duyệt</option>
                            <option value="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/dashboard') . '?' . http_build_query(array_merge($filters, ['status' => 'Từ chối'])) ?>" <?= ($filters['status'] ?? '') == 'Từ chối' ? 'selected' : '' ?>>Từ chối</option>
                        </select>
                    </th>
                    <th class="px-3 py-2 relative flex items-center gap-1 min-w-[200px]">
                        <div class="relative flex-1">
                            <i class="fas fa-search absolute left-2 top-2.5 text-slate-300 text-[10px]"></i>
                            <input type="text" id="filter-search" data-filter-key="search" placeholder="Họ tên / CCCD / Email..." 
                                   value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                                   class="w-full pl-6 pr-2 py-1.5 text-[11px] border border-slate-100 rounded bg-slate-50/50 outline-none focus:bg-white focus:border-blue-200 transition">
                        </div>
                    </th>
                    <?php 
                        $filterUrl = url($mode === 'review' ? '/admin/review-management' : '/admin/dashboard');
                    ?>
                    <th x-show="showCols.phone" class="px-2 py-2">
                        <input type="text" id="filter-phone" data-filter-key="f_phone" placeholder="Tìm SĐT..." 
                               value="<?= htmlspecialchars($filters['f_phone'] ?? '') ?>"
                               class="w-full px-2 py-1.5 text-[10px] border border-slate-100 rounded bg-slate-50/50 outline-none focus:bg-white focus:border-blue-200">
                    </th>
                    <th x-show="showCols.email" class="px-2 py-2"></th>
                    <th x-show="showCols.gender" class="px-2 py-2"></th>
                    <th x-show="showCols.dob" class="px-2 py-2">
                        <input type="text" id="filter-dob" data-filter-key="f_dob" placeholder="Tìm ngày sinh..." 
                               value="<?= htmlspecialchars($filters['f_dob'] ?? '') ?>"
                               class="w-full px-2 py-1.5 text-[10px] border border-slate-100 rounded bg-slate-50/50 outline-none focus:bg-white focus:border-blue-200">
                    </th>
                    <th x-show="showCols.province" class="px-2 py-2">
                        <input type="text" id="filter-province" data-filter-key="f_province" placeholder="Tìm hộ khẩu..." 
                               value="<?= htmlspecialchars($filters['f_province'] ?? '') ?>"
                               class="w-full px-2 py-1.5 text-[10px] border border-slate-100 rounded bg-slate-50/50 outline-none focus:bg-white focus:border-blue-200">
                    </th>
                    <th x-show="showCols.school" class="px-2 py-2">
                        <input type="text" id="filter-school" data-filter-key="f_school" placeholder="Tìm trường..." 
                               value="<?= htmlspecialchars($filters['f_school'] ?? '') ?>"
                               class="w-full px-2 py-1.5 text-[10px] border border-slate-100 rounded bg-slate-50/50 outline-none focus:bg-white focus:border-blue-200">
                    </th>
                    <th x-show="showCols.nv1" class="px-2 py-2 text-center text-[10px] text-slate-300">
                        (Filters above)
                    </th>
                    <th class="px-3 py-2"></th>
                    <?php if ($mode === 'review'): ?>
                    <th class="px-2 py-2"></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php if (empty($candidates)): ?>
                    <tr>
                        <td colspan="20" class="px-6 py-12 text-center text-slate-400">
                            <i class="fas fa-search text-3xl mb-2 opacity-20"></i>
                            <p>Không tìm thấy dữ liệu.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $stt = ($pagination['current_page'] - 1) * 20 + 1;
                    foreach ($candidates as $c): 
                        $avatar = !empty($c['anh_dai_dien']) ? (strpos($c['anh_dai_dien'], 'http') === 0 ? google_drive_thumbnail_url($c['anh_dai_dien'], 'w100') : asset($c['anh_dai_dien'])) : null;
                    ?>
                        <tr class="hover:bg-slate-50 transition duration-150 group divide-x divide-slate-50">
                            <td class="px-2 py-3 text-center">
                                <input type="checkbox" name="ids[]" value="<?= $c['so_cccd'] ?>" data-session-id="<?= $c['dot_tuyen_sinh_id'] ?>" class="item-checkbox rounded border-gray-300 text-[#0066FF] focus:ring-indigo-600">
                            </td>
                            <td class="px-3 py-3 text-center text-slate-500 font-medium"><?= $stt++ ?></td>
                            <td class="px-3 py-3 text-center">
                                <?php 
                                $statuses = array_unique(explode(', ', $c['statuses'] ?? ''));
                                foreach ($statuses as $st): 
                                    $color = 'slate';
                                    if ($st == 'Chờ duyệt') $color = 'amber';
                                    if ($st == 'Đã duyệt') $color = 'emerald';
                                    if ($st == 'Từ chối') $color = 'rose';
                                ?>
                                    <div class="flex justify-center" title="<?= htmlspecialchars($st ?: 'Mới') ?>">
                                        <?php if ($st == 'Đã duyệt'): ?>
                                            <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                                        <?php elseif ($st == 'Từ chối'): ?>
                                            <i class="fas fa-times-circle text-rose-500 text-lg"></i>
                                        <?php else: ?>
                                            <i class="fas fa-clock text-amber-400 text-lg"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td class="px-4 py-3">
                                <a href="<?= url('/admin/review?cccd=' . $c['so_cccd']) ?>" class="flex items-center group">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 shrink-0 overflow-hidden shadow-sm group-hover:border-blue-400 transition-colors">
                                        <?php if ($avatar): ?>
                                            <img src="<?= $avatar ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-user text-slate-300 text-lg"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-3 overflow-hidden">
                                        <p class="font-bold text-blue-600 truncate group-hover:text-blue-800 transition text-[13px]" title="<?= htmlspecialchars($c['ho_va_ten']) ?>">
                                            <?= htmlspecialchars($c['ho_va_ten']) ?>
                                        </p>
                                        <p x-show="showCols.cccd" class="text-[11px] text-slate-400 font-mono"><?= htmlspecialchars($c['so_cccd']) ?></p>
                                    </div>
                                </a>
                            </td>
                            <td x-show="showCols.phone" class="px-4 py-3 text-slate-600 font-medium text-[13px]">
                                <?= htmlspecialchars($c['dien_thoai']) ?>
                            </td>
                            <td x-show="showCols.email" class="px-4 py-3 text-slate-500 truncate text-[12px]" title="<?= htmlspecialchars($c['email']) ?>">
                                <?= htmlspecialchars($c['email']) ?>
                            </td>
                            <td x-show="showCols.gender" class="px-4 py-3 text-slate-600 text-[13px]">
                                <?= htmlspecialchars($c['gioi_tinh'] ?: '-') ?>
                            </td>
                            <td x-show="showCols.dob" class="px-4 py-3 text-slate-600 text-[13px]">
                                <?= $c['ngay_sinh'] ? date('d/m/Y', strtotime($c['ngay_sinh'])) : '-' ?>
                            </td>
                            <td x-show="showCols.province" class="px-4 py-3 text-slate-600 text-[12px]">
                                <?= htmlspecialchars($c['province_name'] ?: '-') ?>
                            </td>
                            <td x-show="showCols.school" class="px-4 py-3 text-slate-600 text-[12px] leading-tight">
                                <?= htmlspecialchars($c['school_name'] ?: '-') ?>
                            </td>
                            <td x-show="showCols.nv1" class="px-4 py-3">
                                <?php if ($c['nv1']): ?>
                                    <span class="text-[12px] font-medium text-slate-700 truncate block" title="<?= htmlspecialchars($c['nv1']) ?>">
                                        <?= htmlspecialchars($c['nv1']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300 italic text-[11px]">Chưa ĐK</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-500 italic text-[11px] leading-snug">
                                <?= htmlspecialchars($c['ghi_chu'] ?: '') ?>
                                <?php if (!empty($c['has_edit_request'])): ?>
                                    <span class="block mt-1 text-purple-600 font-bold uppercase text-[9px] animate-pulse">
                                        [YC Sửa hồ sơ]
                                    </span>
                                <?php endif; ?>
                            </td>
                            <?php if ($mode === 'review'): ?>
                            <td class="px-3 py-3 text-center">
                                <a href="<?= url('/admin/review?cccd=' . $c['so_cccd']) ?>" 
                                   class="inline-flex items-center px-3 py-1.5 bg-[#0066FF] text-white text-[11px] font-bold rounded-lg shadow-sm hover:bg-blue-700 transition-all duration-150 whitespace-nowrap">
                                    <i class="fas fa-clipboard-check mr-1.5"></i>Duyệt
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- Preserve filters -->
        <input type="hidden" name="current_status" value="<?= htmlspecialchars($filters['status'] ?? '') ?>">
        <input type="hidden" name="current_search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        <input type="hidden" name="current_year" value="<?= htmlspecialchars($filters['year'] ?? '') ?>">
        <input type="hidden" name="current_session_id" value="<?= htmlspecialchars($filters['session_id'] ?? '') ?>">
    </div>

    <!-- Pagination -->
    <?php 
    $page = $pagination['current_page'];
    $totalPages = $pagination['total_pages'];
    if ($totalPages > 1): 
    ?>
    <div class="flex items-center justify-between mt-6">
        <div class="text-sm text-slate-500">
            Hiển thị trang <span class="font-bold text-slate-700"><?= $page ?></span> / <span class="font-bold text-slate-700"><?= $totalPages ?></span>
        </div>
        <div class="flex gap-2">
            <?php if($page > 1): ?>
                <a href="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/dashboard') . '?' . http_build_query(array_merge($filters, ['page' => $page - 1])) ?>" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 font-medium transition shadow-sm">Trước</a>
            <?php endif; ?>
            
            <div class="hidden md:flex gap-1">
                <?php for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/dashboard') . '?' . http_build_query(array_merge($filters, ['page' => $i])) ?>" class="w-10 h-10 flex items-center justify-center border rounded-lg font-bold transition shadow-sm <?= $i == $page ? 'bg-[#0066FF] border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>

            <?php if($page < $totalPages): ?>
                <a href="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/dashboard') . '?' . http_build_query(array_merge($filters, ['page' => $page + 1])) ?>" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 font-medium transition shadow-sm">Sau</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</form>

<script>
(function() {
    var baseUrl = '<?= url($mode === "review" ? "/admin/review-management" : "/admin/dashboard") ?>';
    var currentFilters = <?= json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    // Find all filter inputs by data attribute
    var filterInputs = document.querySelectorAll('[data-filter-key]');
    filterInputs.forEach(function(input) {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                var key = this.getAttribute('data-filter-key');
                var val = this.value.trim();
                
                // Clone filters and update the changed key
                var f = Object.assign({}, currentFilters);
                f[key] = val;
                f.page = 1;
                
                // Remove empty values to keep URL clean
                var params = new URLSearchParams();
                for (var k in f) {
                    if (f[k] !== '' && f[k] !== null && f[k] !== undefined) {
                        params.set(k, f[k]);
                    }
                }
                
                window.location.href = baseUrl + '?' + params.toString();
            }
        });
    });
})();
</script>
