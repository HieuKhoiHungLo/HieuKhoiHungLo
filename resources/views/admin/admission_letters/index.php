<?php $title = 'Quản lý Gửi thư trúng tuyển'; ?>
<?php ob_start(); ?>

<div class="max-w-7xl mx-auto">
    <header class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Danh sách Thí sinh Import</h2>
            <p class="text-sm text-gray-500 mt-1">Quản lý và gửi thư thông báo linh hoạt cho thí sinh</p>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="openTestEmailModal()" class="px-4 py-2 bg-amber-500 text-white font-bold rounded-xl hover:bg-amber-600 transition shadow-lg shadow-amber-200 flex items-center">
                <i class="fas fa-paper-plane mr-2"></i> Gửi test
            </button>
            <a href="<?= url('/admin/admission-letters/senders') ?>" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition flex items-center">
                <i class="fas fa-envelope-open-text mr-2"></i> Cấu hình Email
            </a>
            <a href="<?= url('/admin/admission-letters/import') ?>" class="px-4 py-2 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center">
                <i class="fas fa-file-import mr-2"></i> Nhập dữ liệu
            </a>
        </div>
    </header>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
            <i class="fas fa-check-circle mr-2 text-xl"></i>
            <div>
                <strong>Thao tác thành công!</strong>
                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'queued'): ?>
                    Đã đưa <?= htmlspecialchars($_GET['count'] ?? 0) ?> email vào hàng đợi gửi.
                <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
                    Đã xóa các hồ sơ được chọn.
                <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted_all'): ?>
                    Đã xóa toàn bộ dữ liệu.
                <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'test_queued'): ?>
                    Đã gửi email test thành công. Vui lòng kiểm tra hòm thư của bạn!
                <?php elseif (isset($_GET['imported'])): ?>
                    Đã thêm <?= htmlspecialchars($_GET['imported'] ?? 0) ?> thí sinh từ file Excel.
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
            <i class="fas fa-exclamation-triangle mr-2 text-xl"></i>
            <div><?= htmlspecialchars($_GET['error']) ?></div>
        </div>
    <?php endif; ?>

    <!-- Toolbar & Pagination Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500 uppercase flex items-center">
                <i class="fas fa-cog mr-2"></i> Chức năng:
            </span>
            <form action="<?= url('/admin/admission-letters/delete-all') ?>" method="POST" onsubmit="return confirm('Bạn có CHẮC CHẮN muốn xóa TOÀN BỘ danh sách thư báo trúng tuyển? Thao tác này không thể hoàn tác!')" class="inline">
                <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
                <?php if (!empty($filters['batch_id'])): ?>
                    <input type="hidden" name="batch_id" value="<?= htmlspecialchars($filters['batch_id']) ?>">
                <?php endif; ?>
                <button type="submit" class="px-4 py-2 bg-white border border-red-200 rounded-xl text-red-600 text-xs font-bold hover:bg-red-50 shadow-sm transition flex items-center mr-1">
                    <i class="fas fa-trash mr-2"></i> Xóa toàn bộ <?= !empty($filters['batch_id']) ? 'đợt này' : '' ?>
                </button>
            </form>
            <button type="button" onclick="window.location.href='<?= url('/admin/admission-letters/trash') ?>'" 
                class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-slate-700 text-xs font-bold hover:bg-slate-50 shadow-sm transition flex items-center">
                <i class="fas fa-trash-alt mr-2 text-slate-400"></i> Thùng rác
            </button>
        </div>

        <?php if (isset($pagination)): ?>
            <div class="flex items-center gap-4">
                <div class="text-xs text-slate-500">
                    Trang <span class="font-bold text-slate-700"><?= $pagination['current_page'] ?></span> / <span class="font-bold text-slate-700"><?= $pagination['total_pages'] ?></span> 
                    &nbsp;(<span class="font-medium"><?= number_format($pagination['total_items']) ?> bản ghi</span>)
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-[10px] text-slate-400 uppercase font-bold mr-1">Hiển thị:</span>
                    <?php foreach ([10, 15, 20, 50, 100] as $opt): ?>
                        <a href="<?= url('/admin/admission-letters?' . http_build_query(array_merge($filters, ['limit' => $opt, 'page' => 1]))) ?>"
                           class="px-2 py-1 rounded text-[10px] font-bold border transition
                                  <?= $opt == ($filters['limit'] ?? 10)
                                      ? 'bg-blue-600 border-blue-600 text-white shadow-sm'
                                      : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                            <?= $opt ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bulk Action Toolbar -->
    <div id="bulk-toolbar" class="hidden bg-blue-600 text-white p-4 rounded-2xl shadow-lg mb-6 flex flex-wrap justify-between items-center animate-in slide-in-from-top duration-300">
        <div class="flex items-center mb-2 md:mb-0">
            <span class="bg-blue-500 px-3 py-1 rounded-full text-xs font-bold mr-3"><span id="selected-count">0</span> đã chọn</span>
            <div class="h-6 w-px bg-blue-400 mx-2"></div>
            <div class="flex items-center">
                <label class="text-xs font-bold uppercase mr-2 opacity-80">Chọn mẫu thư:</label>
                <select id="bulk-template-id" class="bg-white border border-blue-200 rounded-lg text-sm font-medium px-3 py-1.5 focus:ring-2 focus:ring-white outline-none cursor-pointer" style="color: #1e293b !important;">
                    <option value="" style="color: #1e293b !important;">-- Chọn mẫu email --</option>
                    <?php foreach($templates as $t): ?>
                        <option value="<?= $t['id'] ?>" style="color: #1e293b !important;"><?= htmlspecialchars($t['subject']) ?> (<?= $t['code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="handleBulkAction('send')" class="px-4 py-2 bg-white text-blue-600 font-bold rounded-lg hover:bg-blue-50 transition flex items-center">
                <i class="fas fa-paper-plane mr-2"></i> Gửi Thư
            </button>
            <button onclick="handleBulkAction('delete')" class="px-4 py-2 bg-red-500 text-white font-bold rounded-lg hover:bg-red-600 transition flex items-center">
                <i class="fas fa-trash-alt mr-2"></i> Xóa hồ sơ
            </button>
        </div>
    </div>

    <form id="bulk-form" action="<?= url('/admin/admission-letters/bulk-action') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
        <input type="hidden" name="action" id="form-action" value="">
        <input type="hidden" name="template_id" id="form-template-id" value="">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse min-w-[1100px]">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 text-[10px] uppercase tracking-wider">
                            <th class="px-4 py-3 w-10 text-center border-r border-slate-200 sticky left-0 bg-slate-50 z-10">
                                <input type="checkbox" id="select-all" class="rounded text-blue-600 focus:ring-blue-500 cursor-pointer">
                            </th>
                            <th class="px-4 py-3 w-10 text-center border-r border-slate-200">
                                <i class="fas fa-info-circle" title="Trạng thái"></i>
                            </th>
                            <th class="px-4 py-3 border-r border-slate-200 min-w-[200px]">Họ Tên / CCCD</th>
                            <th class="px-4 py-3 border-r border-slate-200 w-32 text-center">Ghi chú</th>
                            <th class="px-4 py-3 border-r border-slate-200 w-32 text-center">Ngày sinh</th>
                            <th class="px-4 py-3 border-r border-slate-200 w-36">Điện thoại</th>
                            <th class="px-4 py-3 border-r border-slate-200 min-w-[200px]">Email</th>
                            <th class="px-4 py-3 border-r border-slate-200 min-w-[200px]">Ngành Xét Tuyển</th>
                            <th class="px-4 py-3 text-center w-20 sticky right-0 bg-slate-50 z-10">Xem</th>
                        </tr>
                        <!-- Row 2: Search Filters -->
                        <tr class="bg-slate-50/50 border-b border-slate-200">
                            <th class="px-4 py-1 border-r border-slate-200 sticky left-0 bg-slate-50"></th>
                            <th class="px-4 py-1 border-r border-slate-200">
                                <select data-filter-key="status" onchange="applyFilters()" class="w-full text-[9px] border border-slate-200 rounded px-1 py-1 outline-none focus:border-blue-400 bg-white font-normal">
                                    <option value="">(Tất cả)</option>
                                    <option value="pending" <?= ($filters['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Chờ gửi</option>
                                    <option value="queued" <?= ($filters['status'] ?? '') == 'queued' ? 'selected' : '' ?>>Hàng đợi</option>
                                    <option value="sent" <?= ($filters['status'] ?? '') == 'sent' ? 'selected' : '' ?>>Đã gửi</option>
                                    <option value="failed" <?= ($filters['status'] ?? '') == 'failed' ? 'selected' : '' ?>>Lỗi</option>
                                </select>
                            </th>
                            <th class="px-4 py-1 border-r border-slate-200">
                                <input type="text" data-filter-key="f_name" placeholder="Tên / CCCD..." value="<?= htmlspecialchars($filters['f_name'] ?? '') ?>"
                                    class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400 font-normal">
                            </th>
                            <th class="px-4 py-1 border-r border-slate-200"></th>
                            <th class="px-4 py-1 border-r border-slate-200">
                                <input type="text" data-filter-key="f_dob" placeholder="Ngày sinh..." value="<?= htmlspecialchars($filters['f_dob'] ?? '') ?>"
                                    class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400 font-normal text-center">
                            </th>
                            <th class="px-4 py-1 border-r border-slate-200">
                                <input type="text" data-filter-key="f_phone" placeholder="Số ĐT..." value="<?= htmlspecialchars($filters['f_phone'] ?? '') ?>"
                                    class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400 font-normal">
                            </th>
                            <th class="px-4 py-1 border-r border-slate-200">
                                <input type="text" data-filter-key="f_email" placeholder="Email..." value="<?= htmlspecialchars($filters['f_email'] ?? '') ?>"
                                    class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400 font-normal">
                            </th>
                            <th class="px-4 py-1 border-r border-slate-200">
                                <input type="text" data-filter-key="f_major" placeholder="Ngành học..." value="<?= htmlspecialchars($filters['f_major'] ?? '') ?>"
                                    class="w-full px-2 py-1 text-[10px] border border-slate-200 rounded outline-none focus:border-blue-400 font-normal">
                            </th>
                            <th class="px-4 py-1 sticky right-0 bg-slate-50"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(empty($candidates)): ?>
                            <tr><td colspan="9" class="py-12 text-center text-slate-400 bg-slate-50/50 italic">Không tìm thấy thí sinh nào phù hợp.</td></tr>
                        <?php else: ?>
                            <?php foreach($candidates as $c): ?>
                                <tr class="hover:bg-blue-50/30 transition-colors group">
                                    <td class="px-4 py-3 text-center border-r border-slate-50 sticky left-0 bg-white group-hover:bg-blue-50/30 transition-colors">
                                        <input type="checkbox" name="ids[]" value="<?= $c['id'] ?>" class="item-checkbox rounded text-blue-600 focus:ring-blue-500 cursor-pointer">
                                    </td>
                                    <td class="px-4 py-3 text-center border-r border-slate-50">
                                        <?php if($c['status'] == 'pending'): ?>
                                            <i class="fas fa-clock text-amber-500" title="Chờ gửi"></i>
                                        <?php elseif($c['status'] == 'queued'): ?>
                                            <i class="fas fa-spinner fa-spin text-blue-500" title="Trong hàng đợi"></i>
                                        <?php elseif($c['status'] == 'sent'): ?>
                                            <i class="fas fa-check-circle text-emerald-500" title="Đã gửi"></i>
                                        <?php elseif($c['status'] == 'failed'): ?>
                                            <i class="fas fa-exclamation-circle text-rose-500" title="Lỗi"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-50">
                                        <div class="flex items-center">
                                            <div class="w-7 h-7 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-[10px] font-bold text-slate-400 mr-3 overflow-hidden shrink-0">
                                                <?= mb_substr($c['ho_ten'], 0, 1) ?>
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="font-bold text-slate-800 leading-tight uppercase text-[12px] truncate"><?= htmlspecialchars($c['ho_ten']) ?></div>
                                                <div class="text-[11px] font-medium text-slate-400 mt-0.5"><?= htmlspecialchars($c['so_cccd']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-50 text-center text-slate-600 font-medium">
                                        <?= htmlspecialchars($c['ghi_chu'] ?: '-') ?>
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-50 text-center text-slate-600 font-medium">
                                        <?= htmlspecialchars($c['ngay_sinh'] ?: '-') ?>
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-50 text-slate-700 font-bold">
                                        <?= htmlspecialchars($c['sdt']) ?>
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-50 text-slate-500 text-[11px] truncate" title="<?= htmlspecialchars($c['email']) ?>">
                                        <?= htmlspecialchars($c['email']) ?>
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-50">
                                        <div class="font-semibold text-slate-700 text-xs truncate w-56" title="<?= htmlspecialchars($c['ten_nganh']) ?>">
                                            <?= htmlspecialchars($c['ten_nganh']) ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center sticky right-0 bg-white group-hover:bg-blue-50/30 transition-colors">
                                        <a href="<?= url('/admin/admission-letters/preview?id=' . $c['id']) ?>" target="_blank" 
                                           class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-white transition-all shadow-sm border border-transparent hover:border-slate-200" title="Xem trước">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bottom Pagination -->
        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
            <div class="flex justify-center mt-6 gap-1">
                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="<?= url('/admin/admission-letters?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] - 1]))) ?>" 
                       class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-slate-600 text-xs font-bold hover:bg-slate-50 transition shadow-sm">Trước</a>
                <?php endif; ?>

                <?php 
                $start = max(1, $pagination['current_page'] - 2);
                $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                for ($i = $start; $i <= $end; $i++): 
                ?>
                    <a href="<?= url('/admin/admission-letters?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>" 
                       class="w-9 h-9 flex items-center justify-center border rounded-xl font-bold text-xs transition <?= $i == $pagination['current_page'] ? 'bg-blue-600 border-blue-600 text-white shadow-md' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a href="<?= url('/admin/admission-letters?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] + 1]))) ?>" 
                       class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-slate-600 text-xs font-bold hover:bg-slate-50 transition shadow-sm">Sau</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </form>
</div>

<!-- Test Email Modal -->
<div id="testEmailModal" class="hidden fixed inset-0 bg-slate-900/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-200">
        <form action="<?= url('/admin/admission-letters/send-test') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div>
                    <h3 class="text-lg font-black text-slate-800">Gửi Test Email</h3>
                    <p class="text-xs text-slate-500 mt-1">Gửi 1 email thử nghiệm để kiểm tra giao diện và kết nối.</p>
                </div>
                <button type="button" onclick="closeTestEmailModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 text-slate-500 hover:bg-slate-300 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Chọn mẫu email <span class="text-red-500">*</span></label>
                    <select name="template_id" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition text-sm" required>
                        <option value="">-- Chọn mẫu --</option>
                        <?php foreach($templates as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['subject']) ?> (<?= $t['code'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Email người nhận <span class="text-red-500">*</span></label>
                    <input type="email" name="test_email" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition text-sm" placeholder="example@gmail.com">
                </div>
            </div>
            <div class="p-5 border-t border-slate-100 bg-white flex justify-end gap-3">
                <button type="button" onclick="closeTestEmailModal()" class="px-5 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition text-sm">Hủy</button>
                <button type="submit" onclick="if(typeof Loading !== 'undefined') Loading.show();" class="px-5 py-2 bg-amber-500 text-white font-bold rounded-xl shadow-lg shadow-amber-200 hover:bg-amber-600 transition flex items-center text-sm">
                    <i class="fas fa-paper-plane mr-2"></i> Gửi Ngay
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openTestEmailModal() {
        document.getElementById('testEmailModal').classList.remove('hidden');
    }

    function closeTestEmailModal() {
        document.getElementById('testEmailModal').classList.add('hidden');
    }

    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkToolbar = document.getElementById('bulk-toolbar');
    const selectedCount = document.getElementById('selected-count');

    function updateBulkUI() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        selectedCount.innerText = checked.length;
        if (checked.length > 0) {
            bulkToolbar.classList.remove('hidden');
        } else {
            bulkToolbar.classList.add('hidden');
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkUI();
        });
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateBulkUI));

    function handleBulkAction(action) {
        const templateId = document.getElementById('bulk-template-id').value;
        const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;

        if (action === 'send') {
            if (!templateId) {
                alert('Vui lòng chọn mẫu thư muốn gửi!');
                return;
            }
            if (confirm(`Bạn có chắc chắn muốn gửi thư trúng tuyển cho ${checkedCount} thí sinh đã chọn?`)) {
                document.getElementById('form-action').value = 'send_email';
                document.getElementById('form-template-id').value = templateId;
                if (typeof Loading !== 'undefined') Loading.show();
                document.getElementById('bulk-form').submit();
            }
        } else if (action === 'delete') {
            if (confirm(`Hành động này sẽ XÓA ${checkedCount} thí sinh đã chọn. Bạn có chắc chắn?`)) {
                document.getElementById('form-action').value = 'delete';
                if (typeof Loading !== 'undefined') Loading.show();
                document.getElementById('bulk-form').submit();
            }
        }
    }

    function applyFilters() {
        const filters = {};
        document.querySelectorAll('[data-filter-key]').forEach(i => {
            if (i.value) filters[i.getAttribute('data-filter-key')] = i.value;
        });
        
        const currentUrl = new URL(window.location.href);
        const params = new URLSearchParams();
        
        // Keep persistent params
        if (currentUrl.searchParams.has('batch_id')) params.set('batch_id', currentUrl.searchParams.get('batch_id'));
        if (currentUrl.searchParams.has('limit')) params.set('limit', currentUrl.searchParams.get('limit'));
        
        for (const [key, val] of Object.entries(filters)) {
            params.set(key, val);
        }
        
        window.location.href = window.location.pathname + '?' + params.toString();
    }

    document.querySelectorAll('input[data-filter-key]').forEach(input => {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') applyFilters();
        });
    });
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
