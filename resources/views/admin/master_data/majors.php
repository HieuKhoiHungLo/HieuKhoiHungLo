<?php $title = 'Quản lý Ngành học - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-7xl mx-auto p-8">
        <header class="mb-8 flex justify-between items-center">
            <div>
                <a href="<?= url('/admin/master-data') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition block mb-2">&larr; Quay lại danh mục</a>
                <h2 class="text-3xl font-black text-gray-900 uppercase">Quản lý Ngành học</h2>
            </div>
            <div class="flex space-x-2">
                <a href="<?= url('/admin/master-data/majors/export') ?>?csrf_token=<?= $this->csrfToken() ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-xl shadow transition flex items-center">
                    <i class="fas fa-file-export mr-2"></i> Xuất Excel
                </a>
                <button onclick="openImportModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl shadow transition flex items-center">
                    <i class="fas fa-file-import mr-2"></i> Nhập Excel
                </button>
                <button onclick="openModal()" class="bg-[#BE1E2D] hover:bg-[#9d1926] text-white font-black py-2 px-5 rounded-xl shadow-lg transform hover:scale-105 transition flex items-center">
                    <i class="fas fa-plus mr-2"></i> Thêm ngành mới
                </button>
            </div>
        </header>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <form action="<?= url('/admin/master-data/majors/actions') ?>" method="POST" id="bulk-delete-form">
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
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-6 py-4 w-10">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-[#0066FF] focus:ring-[#0066FF]">
                            </th>
                            <th class="px-6 py-4">Mã ngành</th>
                            <th class="px-6 py-4">Tên ngành</th>
                            <th class="px-6 py-4 text-center">Chỉ tiêu</th>
                            <th class="px-6 py-4 text-center">Khối xét tuyển</th>
                            <th class="px-6 py-4 text-center">Điểm 2025</th>
                            <th class="px-6 py-4 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($majors as $major): ?>
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4">
                                    <input type="checkbox" name="ids[]" value="<?= $major['ma_nganh'] ?>" class="item-checkbox rounded border-gray-300 text-[#0066FF] focus:ring-[#0066FF]">
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-[#0066FF]"><?= $major['ma_nganh'] ?></td>
                                <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($major['ten_nganh']) ?></td>
                                <td class="px-6 py-4 text-center font-bold text-slate-500"><?= $major['chi_tieu'] ?: '--' ?></td>
                                <td class="px-6 py-4 text-center"><span class="px-2.5 py-1 bg-slate-100 rounded text-xs font-bold text-[#0066FF] border border-slate-200"><?= $major['khoi_xet_tuyen'] ?></span></td>
                                <td class="px-6 py-4 text-center font-black text-amber-600"><?= $major['diem_nam_truoc'] ?: '--' ?></td>
                                <td class="px-6 py-3 text-center flex items-center justify-center space-x-2">
                                    <button type="button" onclick='editMajor(<?= json_encode($major) ?>)' class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-[#0066FF] hover:bg-[#0066FF] hover:text-white transition" title="Sửa"><i class="fas fa-edit text-xs"></i></button>
                                    <button type="button" onclick="deleteSingle('<?= $major['ma_nganh'] ?>')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition" title="Xóa"><i class="fas fa-trash-alt text-xs"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>

    <!-- Single Delete Form -->
    <form id="single-delete-form" action="<?= url('/admin/master-data/majors/delete') ?>" method="POST" class="hidden">
        <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
        <input type="hidden" name="ma" id="single-delete-id">
    </form>

    <!-- Modal -->
    <div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-3xl p-8 w-full max-w-2xl shadow-2xl">
            <h3 id="modal-title" class="text-xl font-black uppercase mb-8 border-b pb-4">Thêm Ngành học</h3>
            <form action="<?= url('/admin/master-data/majors') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="old_ma" id="old_ma">
                
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Mã ngành</label>
                        <input type="text" name="ma_nganh" id="ma_nganh" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-mono font-bold">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Khối xét tuyển</label>
                        <div class="grid grid-cols-4 gap-2 max-h-40 overflow-y-auto p-2 border border-gray-100 rounded-xl">
                            <?php foreach ($combinations as $c): ?>
                            <label class="flex items-center space-x-2 bg-gray-50 p-2 rounded hover:bg-[#0066FF]/10 cursor-pointer">
                                <input type="checkbox" name="combinations[]" value="<?= $c['ma_to_hop'] ?>" class="form-checkbox text-[#0066FF] rounded focus:ring-[#0066FF]">
                                <span class="font-bold text-sm text-gray-700"><?= $c['ma_to_hop'] ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Tên ngành</label>
                        <input type="text" name="ten_nganh" id="ten_nganh" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-bold text-lg">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Chỉ tiêu (CT)</label>
                        <input type="number" name="chi_tieu" id="chi_tieu" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Điểm năm ngoái (2025)</label>
                        <input type="number" step="0.01" name="diem_nam_truoc" id="diem_nam_truoc" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-bold">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Ghi chú / Tham khảo</label>
                        <textarea name="ghi_chu" id="ghi_chu" rows="2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-medium text-sm"></textarea>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Khu vực tuyển sinh (Giới hạn nơi thường trú)</label>
                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2 max-h-40 overflow-y-auto p-2 border border-gray-100 rounded-xl">
                            <?php foreach ($provinces as $p): ?>
                            <label class="flex items-center space-x-2 bg-gray-50 p-2 rounded hover:bg-[#0066FF]/10 cursor-pointer">
                                <input type="checkbox" name="provinces[]" value="<?= $p['ma_tinh'] ?>" class="form-checkbox text-[#0066FF] rounded focus:ring-[#0066FF]">
                                <span class="font-bold text-xs text-gray-700"><?= $p['ten_tinh'] ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1 italic">* Bỏ chọn tất cả để tuyển sinh toàn quốc</p>
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
            <h3 class="text-xl font-black uppercase mb-6 border-b pb-4 text-gray-800">Nhập Ngành từ Excel</h3>
            <form action="<?= url('/admin/master-data/majors/actions') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
                <input type="hidden" name="action" value="import">
                
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 mb-4">Vui lòng tải lên file CSV (UTF-8) theo mẫu.</p>
                    <div class="text-center">
                        <a href="<?= url('/admin/master-data/majors/template') ?>" class="text-[#0066FF] hover:underline text-sm font-bold flex justify-center items-center">
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
        // Existing modal functions
        function openModal() {
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('modal').classList.add('flex');
            document.getElementById('modal-title').innerText = 'Thêm Ngành học mới';
            document.getElementById('form-action').value = 'create';
            document.getElementById('old_ma').value = '';
            document.getElementById('ma_nganh').value = '';
            document.getElementById('ten_nganh').value = '';
            document.getElementById('chi_tieu').value = '';
            document.getElementById('diem_nam_truoc').value = '';
            document.getElementById('ghi_chu').value = '';
            
            // Reset checkboxes
            document.querySelectorAll('input[name="combinations[]"]').forEach(cb => cb.checked = false);
            document.querySelectorAll('input[name="provinces[]"]').forEach(cb => cb.checked = false);
        }
        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
            document.getElementById('modal').classList.remove('flex');
        }
        function editMajor(m) {
            openModal();
            document.getElementById('modal-title').innerText = 'Cập nhật Ngành học';
            document.getElementById('form-action').value = 'update';
            document.getElementById('old_ma').value = m.ma_nganh;
            document.getElementById('ma_nganh').value = m.ma_nganh;
            document.getElementById('ten_nganh').value = m.ten_nganh;
            document.getElementById('chi_tieu').value = m.chi_tieu;
            document.getElementById('diem_nam_truoc').value = m.diem_nam_truoc;
            document.getElementById('ghi_chu').value = m.ghi_chu;

             // Check checkboxes based on m.combination_ids
             document.querySelectorAll('input[name="combinations[]"]').forEach(cb => {
                cb.checked = m.combination_ids && m.combination_ids.includes(cb.value);
            });

            // Check provinces
            document.querySelectorAll('input[name="provinces[]"]').forEach(cb => {
                cb.checked = false; 
                if (m.khu_vuc_tuyen_sinh) {
                    const allowed = m.khu_vuc_tuyen_sinh.split(',');
                    if (allowed.includes(cb.value)) cb.checked = true;
                }
            });
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

        // Single delete
        function deleteSingle(ma) {
            if (confirm('Xác nhận xóa ngành này? Hành động không thể hoàn tác.')) {
                document.getElementById('single-delete-id').value = ma;
                document.getElementById('single-delete-form').submit();
            }
        }
    </script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
    </script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
