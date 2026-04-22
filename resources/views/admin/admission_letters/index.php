<?php $title = 'Quản lý Gửi thư trúng tuyển'; ?>
<?php ob_start(); ?>

<div class="max-w-7xl mx-auto">
    <header class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Danh sách Thí sinh Import</h2>
            <p class="text-sm text-gray-500 mt-1">Quản lý và gửi thư thông báo linh hoạt cho thí sinh</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('/admin/admission-letters/import') ?>" class="px-4 py-2 bg-[#0066FF] hover:bg-blue-700 text-white font-bold rounded-lg shadow transition">
                <i class="fas fa-file-import mr-2"></i> Import Mới
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

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-6">
        <form method="GET" action="<?= url('/admin/admission-letters') ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tìm kiếm</label>
                <input type="text" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Họ tên, CCCD, Email..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Đợt gửi</label>
                <select name="batch_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả các đợt --</option>
                    <?php foreach($batches as $b): ?>
                        <option value="<?= htmlspecialchars($b['batch_id']) ?>" <?= ($filters['batch_id'] ?? '') == $b['batch_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['batch_id']) ?> (<?= $b['total'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Trạng thái</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="pending" <?= ($filters['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Chờ gửi</option>
                    <option value="queued" <?= ($filters['status'] ?? '') == 'queued' ? 'selected' : '' ?>>Trong hàng đợi</option>
                    <option value="sent" <?= ($filters['status'] ?? '') == 'sent' ? 'selected' : '' ?>>Đã gửi</option>
                    <option value="failed" <?= ($filters['status'] ?? '') == 'failed' ? 'selected' : '' ?>>Lỗi gửi</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-slate-800 text-white font-bold rounded-lg hover:bg-slate-900 transition">
                    <i class="fas fa-filter mr-2"></i> Lọc dữ liệu
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk Action Toolbar -->
    <div id="bulk-toolbar" class="hidden bg-blue-600 text-white p-4 rounded-2xl shadow-lg mb-6 flex flex-wrap justify-between items-center animate-in slide-in-from-top duration-300">
        <div class="flex items-center mb-2 md:mb-0">
            <span class="bg-blue-500 px-3 py-1 rounded-full text-xs font-bold mr-3"><span id="selected-count">0</span> đã chọn</span>
            <div class="h-6 w-px bg-blue-400 mx-2"></div>
            <div class="flex items-center">
                <label class="text-xs font-bold uppercase mr-2 opacity-80">Chọn mẫu thư:</label>
                <select id="bulk-template-id" class="bg-blue-700 border-none rounded-lg text-sm px-3 py-1.5 focus:ring-2 focus:ring-white outline-none">
                    <option value="">-- Chọn mẫu email --</option>
                    <?php foreach($templates as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['subject']) ?> (<?= $t['code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex gap-2">
            <button onclick="handleBulkAction('send_email')" class="px-4 py-2 bg-white text-blue-600 font-bold rounded-lg hover:bg-blue-50 transition flex items-center">
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

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 w-10">
                                <input type="checkbox" id="select-all" class="rounded text-blue-600 focus:ring-blue-500 cursor-pointer">
                            </th>
                            <th class="px-6 py-4">Họ Tên / CCCD</th>
                            <th class="px-6 py-4">Đợt / Ngành</th>
                            <th class="px-6 py-4">Email / SĐT</th>
                            <th class="px-6 py-4">Trạng thái</th>
                            <th class="px-6 py-4 text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(empty($candidates)): ?>
                            <tr><td colspan="6" class="py-10 text-center text-slate-400">Không tìm thấy thí sinh nào. Hãy import thêm hồ sơ.</td></tr>
                        <?php else: ?>
                            <?php foreach($candidates as $c): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" name="ids[]" value="<?= $c['id'] ?>" class="item-checkbox rounded text-blue-600 focus:ring-blue-500 cursor-pointer">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($c['ho_ten']) ?></div>
                                        <div class="text-xs text-slate-500"><?= htmlspecialchars($c['so_cccd']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded inline-block mb-1"><?= htmlspecialchars($c['batch_id']) ?></div>
                                        <div class="font-bold text-[#0066FF] text-xs truncate w-48" title="<?= htmlspecialchars($c['ten_nganh']) ?>">
                                            <?= htmlspecialchars($c['ten_nganh']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-slate-600"><?= htmlspecialchars($c['email']) ?></div>
                                        <div class="text-xs text-slate-400"><?= htmlspecialchars($c['sdt']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if($c['status'] == 'pending'): ?>
                                            <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold uppercase rounded">Chờ gửi</span>
                                        <?php elseif($c['status'] == 'queued'): ?>
                                            <span class="px-2 py-1 bg-blue-100 text-blue-700 text-[10px] font-bold uppercase rounded"><i class="fas fa-spinner fa-spin mr-1"></i> Đang chờ</span>
                                        <?php elseif($c['status'] == 'sent'): ?>
                                            <span class="px-2 py-1 bg-green-100 text-green-700 text-[10px] font-bold uppercase rounded"><i class="fas fa-check mr-1"></i> Đã gửi</span>
                                        <?php elseif($c['status'] == 'failed'): ?>
                                            <span class="px-2 py-1 bg-red-100 text-red-700 text-[10px] font-bold uppercase rounded"><i class="fas fa-times mr-1"></i> Lỗi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="<?= url('/admin/admission-letters/preview?id=' . $c['id']) ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs p-2 rounded hover:bg-indigo-50 transition" title="Xem mẫu mặc định">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

<script>
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkToolbar = document.getElementById('bulk-toolbar');
    const selectedCount = document.getElementById('selected-count');
    const bulkForm = document.getElementById('bulk-form');

    function updateBulkUI() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        selectedCount.innerText = checked.length;
        if (checked.length > 0) {
            bulkToolbar.classList.remove('hidden');
            bulkToolbar.classList.add('flex');
        } else {
            bulkToolbar.classList.add('hidden');
            bulkToolbar.classList.remove('flex');
        }
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkUI();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkUI);
    });

    function handleBulkAction(action) {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        if (checked.length === 0) return;

        if (action === 'send_email') {
            const templateId = document.getElementById('bulk-template-id').value;
            if (!templateId) {
                alert('Vui lòng chọn một mẫu email trước khi gửi!');
                return;
            }
            if (!confirm(`Bạn có chắc chắn muốn đưa ${checked.length} thí sinh này vào hàng đợi gửi thư?`)) return;
            document.getElementById('form-template-id').value = templateId;
        }

        if (action === 'delete') {
            if (!confirm(`Bạn có chắc chắn muốn XÓA ${checked.length} thí sinh đã chọn khỏi danh sách import? Thao tác này không thể hoàn tác.`)) return;
        }

        document.getElementById('form-action').value = action;
        bulkForm.submit();
    }
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
