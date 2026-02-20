<?php $title = 'Quản lý Trường THPT - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-7xl mx-auto p-8">
    <header class="mb-8 flex justify-between items-center">
        <div>
            <a href="<?= url('/admin/master-data') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition block mb-2">&larr; Quay lại danh mục</a>
            <h2 class="text-3xl font-black text-gray-900 uppercase">Trường THPT</h2>
            <p class="text-slate-500 text-sm mt-1">Quản lý danh sách trường và khu vực ưu tiên</p>
        </div>
        <div class="flex space-x-2">
            <a href="<?= url('/admin/master-data/schools/export') ?>?csrf_token=<?= $this->csrfToken() ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-xl shadow transition flex items-center">
                <i class="fas fa-file-export mr-2"></i> Xuất Excel
            </a>
            <button onclick="openImportModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl shadow transition flex items-center">
                <i class="fas fa-file-import mr-2"></i> Nhập Excel
            </button>
            <button onclick="openModal()" class="bg-[#BE1E2D] hover:bg-[#9d1926] text-white font-black py-2 px-5 rounded-xl shadow-lg transform hover:scale-105 transition flex items-center">
                <i class="fas fa-plus mr-2"></i> Thêm trường mới
            </button>
        </div>
    </header>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <form action="<?= url('/admin/master-data/schools/actions') ?>" method="POST" id="bulk-delete-form">
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
                        <th class="px-6 py-3 font-heading">Mã trường</th>
                        <th class="px-6 py-3 font-heading">Tên trường</th>
                        <th class="px-6 py-3 font-heading">Khu vực</th>
                        <th class="px-6 py-3 font-heading">Tỉnh/Thành</th>
                        <th class="px-6 py-3 text-center font-heading">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($schools)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-slate-400 italic">Chưa có dữ liệu trường học.</td></tr>
                    <?php else: ?>
                        <?php foreach ($schools as $school): ?>
                            <tr class="hover:bg-red-50/40 transition duration-200 ease-in-out">
                                <td class="px-6 py-3">
                                    <input type="checkbox" name="ids[]" value="<?= $school['ma_truong'] ?>" class="item-checkbox rounded border-gray-300 text-[#0066FF] focus:ring-[#0066FF]">
                                </td>
                                <td class="px-6 py-3 font-mono font-bold text-[#0066FF] text-sm"><?= htmlspecialchars($school['ma_truong']) ?></td>
                                <td class="px-6 py-3 font-medium text-slate-700 text-sm"><?= htmlspecialchars($school['ten_truong']) ?></td>
                                <td class="px-6 py-3"><span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-md text-xs font-bold border border-emerald-100"><?= htmlspecialchars($school['khu_vuc']) ?></span></td>
                                <td class="px-6 py-3 text-sm text-slate-600"><?= htmlspecialchars($school['ten_tinh'] ?? $school['ma_tinh']) ?></td>
                                <td class="px-6 py-3 text-center">
                                    <button type="button" onclick='editSchool(<?= json_encode($school) ?>)' class="text-[#0066FF] hover:text-blue-800 font-bold text-xs uppercase mr-4">Sửa</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>

    <!-- Modal -->
    <div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl">
            <h3 id="modal-title" class="text-xl font-black uppercase mb-6">Trường THPT</h3>
            <form action="<?= url('/admin/master-data/schools') ?>" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
                <input type="hidden" name="old_ma" id="old_ma">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Mã trường</label>
                    <input type="text" name="ma_truong" id="ma_truong" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Tên trường</label>
                    <input type="text" name="ten_truong" id="ten_truong" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Khu vực</label>
                        <select name="khu_vuc" id="khu_vuc" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none">
                            <option value="KV1">KV1</option>
                            <option value="KV2">KV2</option>
                            <option value="KV2-NT">KV2-NT</option>
                            <option value="KV3">KV3</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Tỉnh/Thành</label>
                        <select name="ma_tinh" id="ma_tinh" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none">
                            <?php foreach ($provinces as $p): ?>
                                <option value="<?= $p['ma_tinh'] ?>"><?= htmlspecialchars($p['ten_tinh'] ?: $p['ma_tinh']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal()" class="flex-grow py-3 bg-gray-100 text-gray-600 font-black uppercase text-xs tracking-widest rounded-xl hover:bg-gray-200 transition">Hủy</button>
                    <button type="submit" class="flex-grow py-3 bg-[#BE1E2D] text-white font-black uppercase text-xs tracking-widest rounded-xl shadow-lg hover:shadow-xl hover:bg-[#9d1926] transition">Lưu dữ liệu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="import-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl transform transition-all">
            <h3 class="text-xl font-black uppercase mb-6 border-b pb-4 text-gray-800">Nhập Trường THPT từ Excel</h3>
            <form action="<?= url('/admin/master-data/schools/actions') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
                <input type="hidden" name="action" value="import">
                
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 mb-4">Vui lòng tải lên file CSV (UTF-8) theo mẫu.</p>
                    <div class="text-center">
                        <a href="<?= url('/admin/master-data/schools/template') ?>" class="text-[#0066FF] hover:underline text-sm font-bold flex justify-center items-center">
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
        function openModal() {
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('modal').classList.add('flex');
            document.getElementById('modal-title').innerText = 'Thêm Trường mới';
            document.getElementById('old_ma').value = '';
            document.getElementById('ma_truong').value = '';
            document.getElementById('ten_truong').value = '';
            document.getElementById('khu_vuc').value = 'KV2-NT';
        }
        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
            document.getElementById('modal').classList.remove('flex');
        }
        function editSchool(s) {
            openModal();
            document.getElementById('modal-title').innerText = 'Sửa Trường THPT';
            document.getElementById('old_ma').value = s.ma_truong;
            document.getElementById('ma_truong').value = s.ma_truong;
            document.getElementById('ten_truong').value = s.ten_truong;
            document.getElementById('khu_vuc').value = s.khu_vuc;
            document.getElementById('ma_tinh').value = s.ma_tinh;
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
    </script>
    </script>
<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
