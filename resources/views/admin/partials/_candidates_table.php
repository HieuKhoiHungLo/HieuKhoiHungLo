<?php
// helpers to render sort icons
function sort_icon($field, $currentSort, $currentDir) {
    if ($field !== $currentSort) {
        return '<i class="fas fa-sort sort-link opacity-20 ml-1 cursor-pointer"></i>';
    }
    return $currentDir === 'ASC' 
        ? '<i class="fas fa-sort-up sort-link active ml-1 cursor-pointer"></i>' 
        : '<i class="fas fa-sort-down sort-link active ml-1 cursor-pointer"></i>';
}

function sort_url($field, $currentSort, $currentDir, $baseUrl, $filters) {
    $dir = ($field === $currentSort && $currentDir === 'ASC') ? 'DESC' : 'ASC';
    return $baseUrl . '?' . http_build_query(array_merge($filters, ['sort' => $field, 'dir' => $dir, 'page' => 1]));
}
?>

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

                <select name="status" id="bulk-status-opt" class="hidden px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-sm outline-none">
                    <option value="Chờ duyệt">Về Chờ duyệt</option>
                    <option value="Đã duyệt">Duyệt ngay</option>
                    <option value="Từ chối">Từ chối</option>
                </select>

                <select name="target_session_id" id="bulk-transfer-opt" class="hidden px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-sm outline-none">
                    <option value="">-- Chọn đợt tuyển sinh --</option>
                    <?php foreach ($sessions ?? [] as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['ten_dot'] ?? ('Đợt ' . $s['id'])) ?> (<?= $s['nam_tuyen_sinh'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" onclick="return confirm('Xác nhận thực hiện hành động này?')" class="px-4 py-1.5 bg-[#0066FF] text-white text-sm font-bold rounded-lg hover:bg-indigo-700 shadow-md transition">Apply</button>
        </div>
    <?php endif; ?>

    <!-- Table Container -->
    <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-slate-100 overflow-x-auto candidate-table-container">
        <table class="w-full text-left border-collapse candidate-table min-w-[1400px]">
            <thead class="bg-slate-50 border-b border-slate-100 text-[10px] uppercase font-bold text-slate-500">
                <!-- Row 1: Titles and Sorting -->
                <tr class="divide-x divide-slate-100">
                    <th class="sticky-col sticky-col-left-0 w-10 text-center">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-[#0066FF] focus:ring-indigo-600">
                    </th>
                    <th class="sticky-col sticky-col-left-1 w-12 text-center">STT</th>
                    
                    <?php if ($mode === 'review'): ?>
                        <th class="sticky-col sticky-col-left-2 w-24 text-center">Duyệt</th>
                    <?php endif; ?>

                    <th class="w-32 text-center">Trạng thái</th>
                    
                    <th class="sticky-col sticky-col-left-3 min-w-[220px]">
                        <div class="flex items-center justify-between">
                            <span>Thí sinh (CCCD)</span>
                            <a href="<?= sort_url('ho_va_ten', $sort, $dir, $baseUrl, $filters) ?>" class="sort-trigger">
                                <?= sort_icon('ho_va_ten', $sort, $dir) ?>
                            </a>
                        </div>
                    </th>

                    <th class="w-32">
                        <div class="flex items-center justify-between">
                            <span>Ngày sinh</span>
                            <a href="<?= sort_url('ngay_sinh', $sort, $dir, $baseUrl, $filters) ?>">
                                <?= sort_icon('ngay_sinh', $sort, $dir) ?>
                            </a>
                        </div>
                    </th>

                    <th x-show="showCols.phone" class="w-32">
                        <div class="flex items-center justify-between">
                            <span>Điện thoại</span>
                            <a href="<?= sort_url('dien_thoai', $sort, $dir, $baseUrl, $filters) ?>">
                                <?= sort_icon('dien_thoai', $sort, $dir) ?>
                            </a>
                        </div>
                    </th>

                    <th x-show="showCols.email" class="w-48">Email</th>
                    <th x-show="showCols.province" class="w-32">Hộ khẩu</th>
                    <th x-show="showCols.school" class="w-48">Trường THPT</th>
                    <th x-show="showCols.nv1" class="w-40">NV1</th>
                    <th x-show="showCols.gender" class="w-24">Giới tính</th>
                    <th x-show="showCols.ethnicity" class="w-24">Dân tộc</th>
                    <th x-show="showCols.area" class="w-24">Khu vực ƯT</th>
                    <th x-show="showCols.object" class="w-28">Đối tượng ƯT</th>
                    <th x-show="showCols.grad_year" class="w-24">Năm TN</th>
                    <th class="w-40">Ghi chú</th>
                </tr>

                <!-- Row 2: Search Filters -->
                <tr class="bg-white border-b border-slate-100 divide-x divide-slate-100">
                    <th class="sticky-col sticky-col-left-0 bg-white"></th>
                    <th class="sticky-col sticky-col-left-1 bg-white"></th>
                    
                    <?php if ($mode === 'review'): ?>
                        <th class="sticky-col sticky-col-left-2 bg-white"></th>
                    <?php endif; ?>

                    <th class="px-2 py-1 bg-slate-50/30">
                        <select onchange="window.location.href=this.value" class="w-full text-[9px] border border-slate-200 rounded px-1 py-1 outline-none focus:border-blue-400 bg-white">
                            <option value="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/candidates') . '?' . http_build_query(array_merge($filters, ['status' => '', 'page' => 1])) ?>">(Trạng thái)</option>
                            <option value="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/candidates') . '?' . http_build_query(array_merge($filters, ['status' => 'Chờ duyệt', 'page' => 1])) ?>" <?= ($filters['status'] ?? '') == 'Chờ duyệt' ? 'selected' : '' ?>>Chờ duyệt</option>
                            <option value="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/candidates') . '?' . http_build_query(array_merge($filters, ['status' => 'Đã duyệt', 'page' => 1])) ?>" <?= ($filters['status'] ?? '') == 'Đã duyệt' ? 'selected' : '' ?>>Đã duyệt</option>
                            <option value="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/candidates') . '?' . http_build_query(array_merge($filters, ['status' => 'Từ chối', 'page' => 1])) ?>" <?= ($filters['status'] ?? '') == 'Từ chối' ? 'selected' : '' ?>>Từ chối</option>
                        </select>
                    </th>

                    <th class="sticky-col sticky-col-left-3 bg-white px-2 py-1">
                        <input type="text" data-filter-key="q" placeholder="Tên / CCCD..."
                            value="<?= htmlspecialchars($filters['q'] ?? $filters['search'] ?? '') ?>"
                            class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400">
                    </th>

                    <th class="px-2 py-1">
                        <input type="text" data-filter-key="f_dob" placeholder="Ngày sinh..."
                            value="<?= htmlspecialchars($filters['f_dob'] ?? '') ?>"
                            class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400">
                    </th>

                    <th x-show="showCols.phone" class="px-2 py-1">
                        <input type="text" data-filter-key="f_phone" placeholder="Số ĐT..."
                            value="<?= htmlspecialchars($filters['f_phone'] ?? '') ?>"
                            class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400">
                    </th>

                    <th x-show="showCols.email" class="bg-slate-50/10"></th>

                    <th x-show="showCols.province" class="px-2 py-1">
                        <input type="text" data-filter-key="f_province" placeholder="Tỉnh/Tp..."
                            value="<?= htmlspecialchars($filters['f_province'] ?? '') ?>"
                            class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400">
                    </th>

                    <th x-show="showCols.school" class="px-2 py-1">
                        <input type="text" data-filter-key="f_school" placeholder="Tên trường..."
                            value="<?= htmlspecialchars($filters['f_school'] ?? '') ?>"
                            class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400">
                    </th>

                    <th x-show="showCols.nv1" class="px-2 py-1">
                        <input type="text" data-filter-key="f_nv1" placeholder="Ngành NV1..."
                            value="<?= htmlspecialchars($filters['f_nv1'] ?? '') ?>"
                            class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400">
                    </th>

                    <th x-show="showCols.gender" class="bg-slate-50/10 px-2 py-1">
                        <input type="text" data-filter-key="f_gender" placeholder="Giới tính..."
                            value="<?= htmlspecialchars($filters['f_gender'] ?? '') ?>"
                            class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400">
                    </th>
                    <th x-show="showCols.ethnicity" class="bg-slate-50/10 px-2 py-1">
                        <input type="text" data-filter-key="f_ethnicity" placeholder="Dân tộc..."
                            value="<?= htmlspecialchars($filters['f_ethnicity'] ?? '') ?>"
                            class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400">
                    </th>
                    <th x-show="showCols.area" class="bg-slate-50/10 px-2 py-1">
                        <input type="text" data-filter-key="f_area" placeholder="Khu vực..."
                            value="<?= htmlspecialchars($filters['f_area'] ?? '') ?>"
                            class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400">
                    </th>
                    <th x-show="showCols.object" class="bg-slate-50/10 px-2 py-1">
                        <input type="text" data-filter-key="f_object" placeholder="Đối tượng..."
                            value="<?= htmlspecialchars($filters['f_object'] ?? '') ?>"
                            class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400">
                    </th>
                    <th x-show="showCols.grad_year" class="bg-slate-50/10 px-2 py-1">
                        <input type="text" data-filter-key="f_grad_year" placeholder="Năm TN..."
                            value="<?= htmlspecialchars($filters['f_grad_year'] ?? '') ?>"
                            class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400">
                    </th>

                    <th class="bg-slate-50/10"></th>
                </tr>
            </thead>
            <tbody class="text-[12px]">
                <?php if (empty($candidates)): ?>
                    <tr>
                        <td colspan="20" class="px-6 py-12 text-center text-slate-400 italic">
                            <i class="fas fa-search text-2xl mb-2 opacity-10"></i>
                            <p>Không tìm thấy dữ liệu phù hợp.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $stt = ($pagination['current_page'] - 1) * 20 + 1;
                    foreach ($candidates as $c):
                        $avatar = !empty($c['anh_dai_dien']) ? (strpos($c['anh_dai_dien'], 'http') === 0 ? google_drive_thumbnail_url($c['anh_dai_dien'], 'w100') : asset($c['anh_dai_dien'])) : null;
                    ?>
                        <tr class="divide-x divide-slate-100 group">
                            <td class="sticky-col sticky-col-left-0 text-center">
                                <input type="checkbox" name="ids[]" value="<?= $c['so_cccd'] ?>" class="item-checkbox rounded border-gray-300 text-[#0066FF] focus:ring-indigo-600">
                            </td>
                            <td class="sticky-col sticky-col-left-1 text-center text-slate-400 font-mono"><?= $stt++ ?></td>
                            
                            <?php if ($mode === 'review'): ?>
                                <td class="sticky-col sticky-col-left-2 text-center">
                                    <a href="<?= url('/admin/review?cccd=' . $c['so_cccd']) ?>"
                                        class="inline-flex items-center justify-center p-1.5 bg-[#0066FF] text-white rounded shadow-sm hover:shadow hover:bg-blue-600 transition min-w-[60px]"
                                        title="Duyệt hồ sơ">
                                        <i class="fas fa-check-double scale-90"></i>
                                        <span class="ml-1 text-[10px] font-bold">Duyệt</span>
                                    </a>
                                </td>
                            <?php endif; ?>

                            <td class="text-center">
                                <?php
                                $statuses = array_unique(explode(', ', $c['statuses'] ?? ''));
                                foreach ($statuses as $st):
                                    $icon = '<i class="fas fa-clock text-amber-400"></i>';
                                    if ($st == 'Đã duyệt') $icon = '<i class="fas fa-check-circle text-emerald-500"></i>';
                                    if ($st == 'Từ chối') $icon = '<i class="fas fa-times-circle text-rose-500"></i>';
                                ?>
                                    <div class="inline-block px-1" title="<?= htmlspecialchars($st ?: 'Mới') ?>">
                                        <?= $icon ?>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            
                            <td class="sticky-col sticky-col-left-3">
                                <a href="<?= url('/admin/review?cccd=' . $c['so_cccd']) ?>" class="flex items-center py-1">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 shrink-0 overflow-hidden shadow-xs">
                                        <?php if ($avatar): ?>
                                            <img src="<?= $avatar ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-user text-slate-300 scale-75"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-2 overflow-hidden leading-tight">
                                        <p class="font-bold text-slate-700 truncate line-clamp-1"><?= htmlspecialchars($c['ho_va_ten']) ?></p>
                                        <p class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($c['so_cccd']) ?></p>
                                    </div>
                                </a>
                            </td>

                            <td class="font-medium text-slate-600">
                                <?= $c['ngay_sinh'] ? date('d/m/Y', strtotime($c['ngay_sinh'])) : '-' ?>
                            </td>

                            <td x-show="showCols.phone" class="font-bold text-slate-600">
                                <?= htmlspecialchars($c['dien_thoai']) ?>
                            </td>

                            <td x-show="showCols.email" class="truncate text-slate-500 text-[11px]" title="<?= htmlspecialchars($c['email']) ?>">
                                <?= htmlspecialchars($c['email']) ?>
                            </td>

                            <td x-show="showCols.province" class="text-slate-600">
                                <?= htmlspecialchars($c['province_name'] ?: '-') ?>
                            </td>

                            <td x-show="showCols.school" class="text-slate-500 leading-tight">
                                <?= htmlspecialchars($c['school_name'] ?: '-') ?>
                            </td>

                            <td x-show="showCols.nv1">
                                <?php if ($c['nv1']): ?>
                                    <span class="font-medium text-slate-700 truncate block" title="<?= htmlspecialchars($c['nv1']) ?>">
                                        <?= htmlspecialchars($c['nv1']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300 italic">Chưa ĐK</span>
                                <?php endif; ?>
                            </td>

                            <td x-show="showCols.gender" class="text-slate-600">
                                <?= htmlspecialchars($c['gioi_tinh'] ?? '') ?>
                            </td>

                            <td x-show="showCols.ethnicity" class="text-slate-600">
                                <?= htmlspecialchars($c['dan_toc'] ?? '') ?>
                            </td>

                            <td x-show="showCols.area" class="text-slate-600">
                                <?= htmlspecialchars($c['khu_vuc_uu_tien'] ?? '') ?>
                            </td>

                            <td x-show="showCols.object" class="text-slate-600">
                                <?= htmlspecialchars($c['doi_tuong_uu_tien'] ?? '') ?>
                            </td>

                            <td x-show="showCols.grad_year" class="text-slate-600">
                                <?= htmlspecialchars($c['nam_tot_nghiep'] ?? '') ?>
                            </td>

                            <td class="text-slate-400 italic text-[11px] leading-snug">
                                <?= htmlspecialchars($c['ghi_chu'] ?? '') ?>
                                <?php if (!empty($c['has_edit_request'])): ?>
                                    <span class="block text-emerald-600 font-bold uppercase text-[8px] animate-pulse">[Yêu cầu sửa]</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View (Keep similar to before but more compact) -->
    <div class="block md:hidden space-y-3">
        <?php if (empty($candidates)): ?>
             <div class="bg-white rounded-xl p-6 text-center text-slate-400 italic">Không tìm thấy dữ liệu.</div>
        <?php else: ?>
            <?php foreach ($candidates as $c): ?>
                <div class="bg-white rounded-xl p-3 shadow-sm border border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-8 h-8 rounded-full bg-slate-50 border border-slate-100 overflow-hidden shrink-0 flex items-center justify-center font-bold text-slate-300 text-lg">
                             <?php if (!empty($c['anh_dai_dien'])): ?>
                                <img src="<?= strpos($c['anh_dai_dien'], 'http') === 0 ? google_drive_thumbnail_url($c['anh_dai_dien'], 'w100') : asset($c['anh_dai_dien']) ?>" class="w-full h-full object-cover">
                             <?php else: ?><i class="fas fa-user scale-75"></i><?php endif; ?>
                        </div>
                        <div class="overflow-hidden">
                             <p class="font-bold text-sm text-slate-800 truncate"><?= htmlspecialchars($c['ho_va_ten']) ?></p>
                             <div class="flex items-center gap-2 mt-0.5">
                                 <span class="text-[10px] py-0.5 px-1.5 rounded-md bg-blue-50 text-blue-600 font-bold"><?= htmlspecialchars($c['so_cccd']) ?></span>
                                 <span class="text-[10px] text-slate-400"><?= $c['ngay_sinh'] ? date('d/m/Y', strtotime($c['ngay_sinh'])) : '' ?></span>
                             </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <a href="<?= url('/admin/review?cccd=' . $c['so_cccd']) ?>" class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center shadow-lg"><i class="fas fa-check scale-90"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php
    $page = $pagination['current_page'];
    $totalPages = $pagination['total_pages'];
    if ($totalPages > 1):
    ?>
        <div class="flex items-center justify-between mt-6">
            <div class="text-xs text-slate-500">
                Trang <span class="font-bold text-slate-700"><?= $page ?></span> / <span class="font-bold text-slate-700"><?= $totalPages ?></span> 
                (<span class="font-medium"><?= number_format($pagination['total_items'] ?? $totalCandidates ?? 0) ?></span> bản ghi)
            </div>
            <div class="flex gap-1.5">
                <?php if ($page > 1): ?>
                    <a href="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/candidates') . '?' . http_build_query(array_merge($filters, ['page' => $page - 1])) ?>" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-xs font-bold transition">Trước</a>
                <?php endif; ?>

                <?php 
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++): 
                ?>
                    <a href="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/candidates') . '?' . http_build_query(array_merge($filters, ['page' => $i])) ?>" class="w-8 h-8 flex items-center justify-center border rounded-lg font-bold text-xs transition <?= $i == $page ? 'bg-[#0066FF] border-blue-600 text-white' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= url($mode === 'review' ? '/admin/review-management' : '/admin/candidates') . '?' . http_build_query(array_merge($filters, ['page' => $page + 1])) ?>" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-xs font-bold transition">Sau</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</form>

<script>
    (function() {
        var baseUrl = '<?= url($mode === "review" ? "/admin/review-management" : "/admin/candidates") ?>';
        var currentFilters = <?= json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        // Header Search logic
        var filterInputs = document.querySelectorAll('[data-filter-key]');
        filterInputs.forEach(function(input) {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    var key = this.getAttribute('data-filter-key');
                    var val = this.value.trim();

                    var f = Object.assign({}, currentFilters);
                    f[key] = val;
                    f.page = 1;

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
        
        // --- Bulk Selection ---
        var selectAll = document.getElementById('select-all');
        var bulkActionsBar = document.getElementById('bulk-actions');
        var selectedCount = document.getElementById('selected-count');

        function updateBulkBar() {
            var checked = document.querySelectorAll('.item-checkbox:checked');
            if (selectedCount) selectedCount.textContent = checked.length;
            if (bulkActionsBar) {
                if (checked.length > 0) bulkActionsBar.classList.remove('hidden');
                else bulkActionsBar.classList.add('hidden');
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = selectAll.checked);
                updateBulkBar();
            });
        }

        document.querySelectorAll('.item-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkBar);
        });
    })();
</script>