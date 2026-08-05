<?php
$title = 'Thùng rác - Hồ sơ đã xóa';
ob_start();
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-trash-alt text-red-500"></i> Thùng rác
            </h1>
            <p class="text-gray-500 text-sm mt-1">Danh sách hồ sơ đã bị xóa tạm thời. Bạn có thể khôi phục hoặc xóa vĩnh viễn.</p>
        </div>

        <div class="flex gap-2">
            <?php if (!empty($candidates)): ?>
                <button type="button" onclick="openEmptyTrashModal()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors font-medium flex items-center gap-2 shadow-md">
                    <i class="fas fa-trash-alt"></i> Xóa tất cả
                </button>
            <?php endif; ?>
            <a href="<?= url('/admin/dashboard') ?>" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại Dashboard
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex justify-between items-center animate-fade-in-down">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-xl mr-3"></i>
                <div>
                    <?php if ($_GET['success'] == 'restored'): ?>
                        <span class="font-bold">Khôi phục thành công!</span> Hồ sơ đã được đưa trở lại danh sách chính.
                    <?php elseif ($_GET['success'] == 'deleted_forever'): ?>
                        <span class="font-bold">Đã xóa vĩnh viễn!</span> Không thể khôi phục lại hồ sơ này.
                    <?php elseif ($_GET['success'] == 'empty_trash_success'): ?>
                        <span class="font-bold">Đã dọn sạch thùng rác!</span> Toàn bộ hồ sơ trong thùng rác đã bị xóa vĩnh viễn khỏi hệ thống.
                    <?php endif; ?>
                </div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm flex justify-between items-center animate-fade-in-down">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-xl mr-3"></i>
                <div>
                    <?php if ($_GET['error'] == 'invalid_password'): ?>
                        <span class="font-bold">Sai mật khẩu!</span> Mật khẩu xác nhận không chính xác. Toàn bộ hồ sơ trong thùng rác vẫn an toàn.
                    <?php elseif ($_GET['error'] == 'missing_password'): ?>
                        <span class="font-bold">Lỗi xác thực!</span> Vui lòng nhập mật khẩu xác nhận để thực hiện hành động này.
                    <?php elseif ($_GET['error'] == 'system_error'): ?>
                        <span class="font-bold">Lỗi hệ thống!</span> Đã xảy ra lỗi khi dọn sạch thùng rác. Vui lòng thử lại sau.
                    <?php else: ?>
                        <span class="font-bold">Lỗi!</span> Đã xảy ra lỗi không xác định.
                    <?php endif; ?>
                </div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <!-- Search/Filter -->
    <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-200">
        <form action="" method="GET" class="flex gap-3">
            <div class="relative flex-grow max-w-md">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>"
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                    placeholder="Tìm theo tên, CCCD...">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                Tìm kiếm
            </button>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="flex flex-col md:flex-row gap-4 mb-4 items-center justify-between border-b pb-4">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl">
                <input type="checkbox" id="select-all" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer">
                <label for="select-all" class="text-[10px] font-black text-slate-500 cursor-pointer select-none uppercase tracking-widest">Chọn tất cả</label>
            </div>
            
            <div class="h-6 w-px bg-slate-200"></div>

            <div class="flex items-center gap-2">
                <select id="bulk-action-select" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all shadow-sm">
                    <option value="">-- Hành động hàng loạt --</option>
                    <option value="restore" class="text-green-600">Khôi phục hồ sơ đã chọn</option>
                    <option value="force_delete" class="text-red-600">Xóa vĩnh viễn hồ sơ đã chọn</option>
                </select>
                <button type="button" onclick="executeBulkAction()" 
                    class="px-6 py-2 bg-[#0066FF] hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-md shadow-blue-100 flex items-center gap-2 active:scale-95 text-xs uppercase tracking-widest min-w-[120px] justify-center">
                    THỰC HIỆN <i class="fas fa-play text-[10px]"></i>
                </button>
            </div>
        </div>

        <?php if ($totalPages > 1 || !empty($candidates)): ?>
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                Tổng cộng: <?= count($candidates) ?> hồ sơ trong Thùng rác
            </div>
        <?php endif; ?>
    </div>

    <!-- Hidden Bulk Form -->
    <form id="bulk-form" method="POST" action="">
    <?= csrf_field() ?>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" id="bulk-action-input">
        <div id="bulk-candidates-ids"></div>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto rounded-xl shadow-sm bg-white border border-slate-200">
        <table class="w-full border-collapse">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wide border border-slate-200 w-12">
                        STT
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide border border-slate-200">
                        Họ và Tên
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide border border-slate-200">
                        CCCD
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide border border-slate-200">
                        Thời gian xóa
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wide border border-slate-200 w-40">
                        Thao tác
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php if (empty($candidates)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center text-gray-500 italic border border-slate-200">
                            <div class="flex flex-col items-center">
                                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-trash-restore text-4xl text-slate-200"></i>
                                </div>
                                <p class="text-sm">Thùng rác hiện đang trống!</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($candidates as $index => $c): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-center border border-slate-200">
                                <div class="flex items-center justify-center gap-3">
                                    <input type="checkbox" name="cccds[]" value="<?= $c['so_cccd'] ?>" class="item-checkbox w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer">
                                    <span class="text-[13px] text-slate-800"><?= ($currentPage - 1) * 20 + $index + 1 ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 border border-slate-200">
                                <div class="text-[13px] text-black"><?= htmlspecialchars($c['ho_va_ten']) ?></div>
                            </td>
                            <td class="px-4 py-3 border border-slate-200">
                                <span class="text-[13px] text-black font-mono">
                                    <?= htmlspecialchars($c['so_cccd']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm border border-slate-200">
                                <div class="flex items-center gap-2">
                                    <i class="far fa-clock text-slate-400 text-xs"></i>
                                    <span class="text-[13px] text-black">
                                        <?= date('H:i d/m/Y', strtotime($c['deleted_at'])) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center border border-slate-200">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Restore Button -->
                                    <form action="<?= url('/admin/candidates/restore') ?>" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn khôi phục hồ sơ này?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="cccd" value="<?= $c['so_cccd'] ?>">
                                        <button type="submit" class="w-7 h-7 flex items-center justify-center bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 rounded-lg transition-all border border-emerald-100" title="Khôi phục">
                                            <i class="fas fa-trash-restore text-[10px]"></i>
                                        </button>
                                    </form>

                                    <!-- Force Delete Button -->
                                    <form action="<?= url('/admin/candidates/force-delete') ?>" method="POST" class="inline-block" onsubmit="return confirm('CẢNH BÁO: Hành động này KHÔNG THỂ hoàn tác! Bạn có chắc chắn muốn xóa vĩnh viễn hồ sơ này?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="cccd" value="<?= $c['so_cccd'] ?>">
                                        <button type="submit" class="w-7 h-7 flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 rounded-lg transition-all border border-rose-100" title="Xóa vĩnh viễn">
                                            <i class="fas fa-times text-[10px]"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Scripts -->
    <script>
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        function executeBulkAction() {
            const action = document.getElementById('bulk-action-select').value;
            const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
            
            if (!action) {
                alert('Vui lòng chọn một hành động!');
                return;
            }
            
            if (checkedBoxes.length === 0) {
                alert('Vui lòng chọn ít nhất một hồ sơ!');
                return;
            }

            const confirmMsg = action === 'restore' 
                ? `Bạn có chắc chắn muốn khôi phục ${checkedBoxes.length} hồ sơ đã chọn?` 
                : `CẢNH BÁO: Bạn sắp xóa VĨNH VIỄN ${checkedBoxes.length} hồ sơ. Hành động này không thể hoàn tác! Bạn vẫn muốn tiếp tục?`;

            if (confirm(confirmMsg)) {
                const bulkForm = document.getElementById('bulk-form');
                const actionInput = document.getElementById('bulk-action-input');
                const idsContainer = document.getElementById('bulk-candidates-ids');
                
                // Set action URL and action value
                bulkForm.action = action === 'restore' ? "<?= url('/admin/candidates/restore') ?>" : "<?= url('/admin/candidates/force-delete') ?>";
                actionInput.value = action;
                
                // Clear previous IDs
                idsContainer.innerHTML = '';
                
                // Add selected IDs as hidden inputs
                checkedBoxes.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'cccds[]';
                    input.value = cb.value;
                    idsContainer.appendChild(input);
                });
                
                bulkForm.submit();
            }
        }

        function openEmptyTrashModal() {
            const modal = document.getElementById('empty-trash-modal');
            const passInput = document.getElementById('admin-password');
            const errorTip = document.getElementById('password-error-tip');
            
            errorTip.classList.add('hidden');
            passInput.value = '';
            modal.classList.remove('hidden');
            passInput.focus();
        }

        function closeEmptyTrashModal() {
            const modal = document.getElementById('empty-trash-modal');
            modal.classList.add('hidden');
        }

        function validatePasswordInput() {
            const passInput = document.getElementById('admin-password');
            const errorTip = document.getElementById('password-error-tip');
            if (!passInput.value.trim()) {
                errorTip.classList.remove('hidden');
                passInput.focus();
                return false;
            }
            return true;
        }
    </script>

    <!-- Pagination -->
    <?php if ($totalPages >= 1): ?>
        <div class="flex items-center justify-between mt-6">
            <div class="flex items-center gap-3 text-xs text-slate-500">
                <span>
                    Trang <span class="font-bold text-slate-700"><?= $currentPage ?></span> / <span class="font-bold text-slate-700"><?= $totalPages ?></span>
                    &nbsp;(<span class="font-medium"><?= isset($total) ? number_format($total) : count($candidates) ?></span> bản ghi)
                </span>
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="flex gap-1.5">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-xs font-bold transition">Trước</a>
                <?php endif; ?>

                <?php 
                $start = max(1, $currentPage - 2);
                $end = min($totalPages, $currentPage + 2);
                for ($i = $start; $i <= $end; $i++): 
                ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 flex items-center justify-center border rounded-lg font-bold text-xs transition <?= $i == $currentPage ? 'bg-[#0066FF] border-blue-600 text-white' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-xs font-bold transition">Sau</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <!-- Empty Trash Password Modal -->
    <div id="empty-trash-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <!-- Backdrop with backdrop-blur -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeEmptyTrashModal()"></div>
        
        <!-- Modal content card -->
        <div class="relative bg-white rounded-2xl shadow-xl border border-slate-100 max-w-md w-full mx-4 p-6 overflow-hidden z-10">
            <!-- Title -->
            <div class="flex items-center gap-3 mb-4 text-red-600">
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-lg">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Xác nhận dọn sạch thùng rác</h3>
            </div>
            
            <!-- Warning details -->
            <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded text-xs text-red-800 mb-5 leading-relaxed">
                <strong>CẢNH BÁO NGUY HIỂM:</strong> Hành động này sẽ xóa <strong>VĨNH VIỄN</strong> toàn bộ hồ sơ hiện tại trong thùng rác và tất cả các nguyện vọng, điểm số, chứng chỉ liên quan. Hành động này <strong>KHÔNG THỂ hoàn tác!</strong>
            </div>

            <!-- Form -->
            <form action="<?= url('/admin/candidates/empty-trash') ?>" method="POST" onsubmit="return validatePasswordInput()">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="mb-5">
                    <label for="admin-password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Nhập mật khẩu của bạn:</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="admin-password" required
                            class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all text-sm font-semibold text-slate-800"
                            placeholder="Mật khẩu tài khoản admin hiện tại">
                    </div>
                    <span id="password-error-tip" class="text-[11px] text-red-500 hidden mt-1"><i class="fas fa-info-circle mr-1"></i>Vui lòng nhập mật khẩu!</span>
                </div>

                <!-- Footer buttons -->
                <div class="flex justify-end gap-2.5">
                    <button type="button" onclick="closeEmptyTrashModal()" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs uppercase tracking-wider transition-all active:scale-95">
                        Hủy bỏ
                    </button>
                    <button type="submit" 
                        class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-md shadow-red-100 flex items-center gap-2 active:scale-95">
                        Xác nhận xóa vĩnh viễn <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>