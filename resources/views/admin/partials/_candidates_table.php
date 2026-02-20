<!-- Bulk Actions & Table -->
<form action="<?= url('/admin/candidates/bulk-action') ?>" method="POST" id="bulk-form">
    <?= csrf_field() ?>
    
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
                        <div class="w-12 h-12 rounded-full bg-indigo-50 text-[#0066FF] flex items-center justify-center font-bold border border-indigo-100 text-lg shrink-0">
                            <?= mb_substr($c['ho_va_ten'], 0, 1) ?>
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
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-100 text-xs uppercase font-bold text-slate-500">
                <tr>
                    <th class="px-4 py-3 w-10 text-center">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-[#0066FF] focus:ring-indigo-600">
                    </th>
                    <th class="px-4 py-3 min-w-[200px]">
                        <a href="<?= url('/admin/dashboard?' . http_build_query(array_merge($filters, ['sort' => 'name', 'dir' => ($filters['sort'] == 'name' && $filters['dir'] == 'asc') ? 'desc' : 'asc']))) ?>" class="flex items-center hover:text-[#0066FF] group">
                            Thí sinh
                            <span class="ml-1 text-[10px] opacity-50 group-hover:opacity-100">
                                <?php if($filters['sort'] == 'name'): ?>
                                    <i class="fas fa-sort-<?= $filters['dir'] == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort"></i>
                                <?php endif; ?>
                            </span>
                        </a>
                    </th>
                    <th class="px-4 py-3">Liên hệ</th>

                    <!-- Dynamic Columns -->
                    <th x-show="showCols.province" class="px-4 py-3">Hộ khẩu</th>
                    <th x-show="showCols.school" class="px-4 py-3">Trường THPT</th>
                    <th x-show="showCols.nv1" class="px-4 py-3">Nguyện vọng 1</th>

                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3 text-right">
                        <a href="<?= url('/admin/dashboard?' . http_build_query(array_merge($filters, ['sort' => 'created_at', 'dir' => ($filters['sort'] == 'created_at' && $filters['dir'] == 'asc') ? 'desc' : 'asc']))) ?>" class="flex items-center justify-end hover:text-[#0066FF] group">
                            Ngày nhận
                            <span class="ml-1 text-[10px] opacity-50 group-hover:opacity-100">
                                <?php if($filters['sort'] == 'created_at'): ?>
                                    <i class="fas fa-sort-<?= $filters['dir'] == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort"></i>
                                <?php endif; ?>
                            </span>
                        </a>
                    </th>
                    <th class="px-4 py-3 w-16"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php if (empty($candidates)): ?>
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-slate-400">
                            <i class="fas fa-search text-3xl mb-2 opacity-20"></i>
                            <p>Không tìm thấy dữ liệu.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($candidates as $c): ?>
                        <tr class="hover:bg-slate-50/80 transition duration-150 group">
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" name="ids[]" value="<?= $c['so_cccd'] ?>" data-session-id="<?= $c['dot_tuyen_sinh_id'] ?>" class="item-checkbox rounded border-gray-300 text-[#0066FF] focus:ring-indigo-600">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-indigo-50 text-[#0066FF] flex items-center justify-center font-bold border border-indigo-100 text-xs shrink-0">
                                        <?= mb_substr($c['ho_va_ten'], 0, 1) ?>
                                    </div>
                                    <div class="ml-3 overflow-hidden">
                                        <p class="font-bold text-slate-700 truncate group-hover:text-[#0066FF] transition" title="<?= htmlspecialchars($c['ho_va_ten']) ?>">
                                            <?= htmlspecialchars($c['ho_va_ten']) ?>
                                        </p>
                                        <p class="text-xs text-slate-400 font-mono"><?= htmlspecialchars($c['so_cccd']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-600 text-xs"><?= htmlspecialchars($c['dien_thoai']) ?></p>
                                <p class="text-xs text-slate-400 truncate max-w-[150px]" title="<?= htmlspecialchars($c['email']) ?>"><?= htmlspecialchars($c['email']) ?></p>
                            </td>

                            <!-- Dynamic Columns Data -->
                            <td x-show="showCols.province" class="px-4 py-3 text-slate-600">
                                <?= htmlspecialchars($c['province_name'] ?: '-') ?>
                            </td>
                            <td x-show="showCols.school" class="px-4 py-3 text-slate-600">
                                <?= htmlspecialchars($c['school_name'] ?: '-') ?>
                            </td>
                            <td x-show="showCols.nv1" class="px-4 py-3">
                                <?php if ($c['nv1']): ?>
                                    <span class="inline-block px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded text-xs font-bold border border-indigo-100 truncate max-w-[150px]" title="<?= htmlspecialchars($c['nv1']) ?>">
                                        <?= htmlspecialchars($c['nv1']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300 italic text-xs">Chưa ĐK</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <?php 
                                    $statuses = array_unique(explode(', ', $c['statuses'] ?? ''));
                                    foreach ($statuses as $st): 
                                        $color = 'slate';
                                        if ($st == 'Chờ duyệt') $color = 'amber';
                                        if ($st == 'Đã duyệt') $color = 'emerald';
                                        if ($st == 'Từ chối') $color = 'rose';
                                    ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-<?= $color ?>-50 text-<?= $color ?>-600 border border-<?= $color ?>-100">
                                            <?= htmlspecialchars($st ?: 'Mới') ?>
                                        </span>
                                    <?php endforeach; ?>
                                    
                                    <?php if ($c['da_du_6_ky']): ?>
                                        <span class="text-blue-500" title="Đủ học bạ 6 kỳ"><i class="fas fa-check-circle text-xs"></i></span>
                                    <?php endif; ?>

                                    <?php if (!empty($c['has_edit_request'])): ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700 animate-pulse">
                                            Sửa
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-slate-500">
                                <?= date('d/m/Y', strtotime($c['ngay_tao'])) ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="<?= url('/admin/review?cccd=' . $c['so_cccd']) ?>" class="flex items-center gap-1 px-3 py-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 rounded-lg text-xs font-bold transition shadow-sm border border-emerald-100" title="Duyệt & Chỉnh sửa hồ sơ">
                                        <i class="fas fa-file-signature"></i> Duyệt
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- Preserve filters -->
        <input type="hidden" name="current_status" value="<?= htmlspecialchars($filters['status'] ?? '') ?>">
        <input type="hidden" name="current_search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
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
                <a href="<?= url('/admin/dashboard?' . http_build_query(array_merge($filters, ['page' => $page - 1]))) ?>" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 font-medium transition shadow-sm">Trước</a>
            <?php endif; ?>
            
            <div class="hidden md:flex gap-1">
                <?php for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="<?= url('/admin/dashboard?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>" class="w-10 h-10 flex items-center justify-center border rounded-lg font-bold transition shadow-sm <?= $i == $page ? 'bg-[#0066FF] border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>

            <?php if($page < $totalPages): ?>
                <a href="<?= url('/admin/dashboard?' . http_build_query(array_merge($filters, ['page' => $page + 1]))) ?>" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 font-medium transition shadow-sm">Sau</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</form>
