<?php $title = 'Quản lý Môn học - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-7xl mx-auto p-8">
    <header class="mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-black text-gray-900 uppercase">Danh mục Môn học</h2>
            <p class="text-gray-500 text-sm mt-1">Quản lý các môn văn hóa và năng khiếu</p>
        </div>
        <div class="flex space-x-2">
            <a href="<?= url('/admin/master-data/subjects/export') ?>?csrf_token=<?= $this->csrfToken() ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-xl shadow transition flex items-center">
                <i class="fas fa-file-export mr-2"></i> Xuất Excel
            </a>
            <button onclick="openImportModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl shadow transition flex items-center">
                <i class="fas fa-file-import mr-2"></i> Nhập Excel
            </button>
            <button onclick="openModal()" class="bg-[#BE1E2D] hover:bg-[#9d1926] text-white font-black py-2 px-5 rounded-xl shadow-lg transform hover:scale-105 transition flex items-center">
                <i class="fas fa-plus mr-2"></i> Thêm môn mới
            </button>
        </div>
    </header>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Thành công!</strong>
            <span class="block sm:inline"><?= $_SESSION['success'] ?></span>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Lỗi!</strong>
            <span class="block sm:inline"><?= $_SESSION['error'] ?></span>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <form action="<?= url('/admin/master-data/subjects') ?>" method="POST" id="bulk-delete-form">
            <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
            <input type="hidden" name="action" value="bulk_delete">
            
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center hidden" id="bulk-actions">
                <span class="text-sm font-bold text-slate-600">Đã chọn <span id="selected-count">0</span> mục</span>
                <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa các mục đã chọn?')" class="text-red-600 hover:text-red-800 font-bold text-xs uppercase bg-red-50 hover:bg-red-100 py-2 px-4 rounded-lg transition">
                    <i class="fas fa-trash mr-1"></i> Xóa đã chọn
                </button>
            </div>

            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase tracking-wider">
                        <th class="px-6 py-3 w-10">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-[#0066FF] focus:ring-[#0066FF]">
                        </th>
                        <th class="px-6 py-3 font-heading">Mã môn</th>
                        <th class="px-6 py-3 font-heading">Tên môn</th>
                        <th class="px-6 py-3 font-heading">Loại môn</th>
                        <th class="px-6 py-3 font-heading">Cột điểm (DB)</th>
                        <th class="px-6 py-3 text-center font-heading">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($subjects as $sub): ?>
                        <tr class="hover:bg-red-50/40 transition duration-200 ease-in-out group">
                            <td class="px-6 py-3">
                                <input type="checkbox" name="ids[]" value="<?= $sub['id'] ?>" class="item-checkbox rounded border-gray-300 text-[#0066FF] focus:ring-[#0066FF]">
                            </td>
                            <td class="px-6 py-3 font-mono font-bold text-[#0066FF] text-sm"><?= htmlspecialchars($sub['ma_mon']) ?></td>
                            <td class="px-6 py-3 font-medium text-slate-700 text-sm"><?= htmlspecialchars($sub['ten_mon']) ?></td>
                            <td class="px-6 py-3">
                                <?php if ($sub['loai_mon'] === 'nang_khieu'): ?>
                                    <span class="text-sm text-purple-700 font-medium">Năng khiếu</span>
                                <?php else: ?>
                                    <span class="text-sm text-slate-600">Văn hóa</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-3 font-mono text-xs text-slate-500"><?= $sub['cot_diem'] ?: '<em class="text-slate-300">--</em>' ?></td>
                            <td class="px-6 py-3 text-center opacity-0 group-hover:opacity-100 transition">
                                <button type="button" onclick='editSubject(<?= json_encode($sub) ?>)' class="text-[#0066FF] hover:text-blue-800 font-bold text-xs uppercase mr-4">Sửa</button>
                                <!-- Single delete button is now separate form to avoid nesting -->
                                <button type="button" onclick="deleteSingle(<?= $sub['id'] ?>)" class="text-slate-400 hover:text-red-600 font-bold text-xs uppercase">Xóa</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>

<!-- Forms for single actions -->
<form id="single-delete-form" action="<?= url('/admin/master-data/subjects') ?>" method="POST" class="hidden">
    <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="single-delete-id">
</form>

<!-- Create/Edit Modal -->
<div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-8 w-full max-w-lg shadow-2xl transform transition-all">
        <h3 id="modal-title" class="text-xl font-black uppercase mb-6 border-b pb-4 text-gray-800">Thêm Môn học</h3>
        <form action="<?= url('/admin/master-data/subjects') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id" id="subject-id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Mã môn (Ví dụ: TOAN, NK_HAT)</label>
                    <input type="text" name="ma_mon" id="ma_mon" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-mono font-bold uppercase">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Tên môn</label>
                    <input type="text" name="ten_mon" id="ten_mon" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-bold">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Loại môn</label>
                        <select name="loai_mon" id="loai_mon" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-bold">
                            <option value="van_hoa">Văn hóa</option>
                            <option value="nang_khieu">Năng khiếu</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Cột điểm DB (Nếu có)</label>
                        <input type="text" name="cot_diem" id="cot_diem" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-mono text-sm placeholder-gray-300" placeholder="vd: toan">
                    </div>
                </div>
            </div>

            <div class="flex space-x-3 pt-8 mt-4">
                <button type="button" onclick="closeModal()" class="flex-grow py-3 bg-gray-100 text-gray-600 font-black uppercase text-xs tracking-widest rounded-xl hover:bg-gray-200 transition">Hủy</button>
                <button type="submit" class="flex-grow py-3 bg-[#BE1E2D] text-white font-black uppercase text-xs tracking-widest rounded-xl shadow-lg hover:shadow-xl hover:bg-[#9d1926] transition">Lưu dữ liệu</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl transform transition-all">
        <h3 class="text-xl font-black uppercase mb-6 border-b pb-4 text-gray-800">Nhập từ Excel (CSV)</h3>
        <form action="<?= url('/admin/master-data/subjects') ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
            <input type="hidden" name="action" value="import">
            
            <div class="space-y-4">
                <p class="text-sm text-gray-600 mb-4">Vui lòng tải lên file CSV (UTF-8) theo mẫu.</p>
                <div class="text-center">
                    <a href="<?= url('/admin/master-data/subjects/template') ?>" class="text-[#0066FF] hover:underline text-sm font-bold flex justify-center items-center">
                        <i class="fas fa-download mr-1"></i> Tải file mẫu
                    </a>
                </div>
                
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-50 transition cursor-pointer relative">
                    <input type="file" name="file" required accept=".csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                    <p class="text-sm font-bold text-gray-500">Kéo thả file hoặc click để chọn</p>
                </div>
            </div>

            <div class="flex space-x-3 pt-8 mt-4">
                <button type="button" onclick="closeImportModal()" class="flex-grow py-3 bg-gray-100 text-gray-600 font-black uppercase text-xs tracking-widest rounded-xl hover:bg-gray-200 transition">Hủy</button>
                <button type="submit" class="flex-grow py-3 bg-blue-600 text-white font-black uppercase text-xs tracking-widest rounded-xl shadow-lg hover:shadow-xl hover:bg-blue-700 transition">Upload</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Existing Modal logic...
    function openModal() {
        document.getElementById('modal').classList.remove('hidden');
        document.getElementById('modal').classList.add('flex');
        document.getElementById('modal-title').innerText = 'Thêm Môn học';
        document.getElementById('form-action').value = 'create';
        document.getElementById('subject-id').value = '';
        document.getElementById('ma_mon').value = '';
        document.getElementById('ten_mon').value = '';
        document.getElementById('loai_mon').value = 'van_hoa';
        document.getElementById('cot_diem').value = '';
    }
    function closeModal() {
        document.getElementById('modal').classList.add('hidden');
        document.getElementById('modal').classList.remove('flex');
    }
    function editSubject(s) {
        openModal();
        document.getElementById('modal-title').innerText = 'Cập nhật Môn học';
        document.getElementById('form-action').value = 'update';
        document.getElementById('subject-id').value = s.id;
        document.getElementById('ma_mon').value = s.ma_mon;
        document.getElementById('ten_mon').value = s.ten_mon;
        document.getElementById('loai_mon').value = s.loai_mon;
        document.getElementById('cot_diem').value = s.cot_diem;
    }

    // New Import Modal Logic
    function openImportModal() {
        document.getElementById('import-modal').classList.remove('hidden');
        document.getElementById('import-modal').classList.add('flex');
    }
    function closeImportModal() {
        document.getElementById('import-modal').classList.add('hidden');
        document.getElementById('import-modal').classList.remove('flex');
    }

    // Bulk Delete Logic
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');

    function updateBulkActions() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        if (checked.length > 0) {
            bulkActions.classList.remove('hidden');
            selectedCount.innerText = checked.length;
        } else {
            bulkActions.classList.add('hidden');
        }
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkActions();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });

    // Single Delete
    function deleteSingle(id) {
        if (confirm('Xóa môn này có thể ảnh hưởng đến các tổ hợp. Tiếp tục?')) {
            document.getElementById('single-delete-id').value = id;
            document.getElementById('single-delete-form').submit();
        }
    }
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
