<?php 
$title = 'Phương thức Tuyển sinh';
ob_start();
?>

<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
    <!-- Page header -->
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-gray-800 font-bold">Danh mục Phương thức Tuyển sinh ✨</h1>
            <p class="text-sm text-gray-500 mt-1">Quản lý và thiết lập trạng thái các phương thức xét tuyển.</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r-lg shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <p class="text-sm text-green-700 font-medium"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <p class="text-sm text-red-700 font-medium"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Table Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="dataTable">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider w-16 text-center">TT</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Mã PT</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-left">Tên Phương Thức</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Mã Nội Bộ</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Cờ (Flag) Ngành</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Trạng Thái</th>
                        <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($phuongThucList as $row): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-3 px-6 text-center text-sm text-gray-500 font-medium"><?= $row['thu_tu'] ?></td>
                            <td class="py-3 px-6 text-sm font-bold text-gray-900"><?= $row['ma_phuong_thuc'] ?></td>
                            <td class="py-3 px-6 text-sm text-gray-800 font-medium"><?= htmlspecialchars($row['ten_phuong_thuc']) ?></td>
                            <td class="py-3 px-6 text-sm text-center">
                                <span class="px-2.5 py-1 rounded border <?= $row['ma_noi_bo'] == '100' ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-purple-50 border-purple-200 text-purple-700' ?> text-xs font-bold">
                                    <?= $row['ma_noi_bo'] ?>
                                </span>
                            </td>
                            <td class="py-3 px-6 text-sm text-center">
                                <?php if($row['flag_nganh']): ?>
                                    <code class="px-2 py-0.5 bg-gray-100 rounded text-gray-600 font-mono text-xs"><?= $row['flag_nganh'] ?></code>
                                <?php else: ?>
                                    <span class="text-gray-400 italic text-xs">Mặc định</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-6 text-center">
                                <?php if ($row['is_active']): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Hiển thị
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Đã ẩn
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex items-center justify-center">
                                    <button type="button" 
                                            onclick="openEditModal('<?= $row['ma_phuong_thuc'] ?>', '<?= htmlspecialchars($row['ten_phuong_thuc']) ?>', <?= $row['is_active'] ? 'true' : 'false' ?>)"
                                            class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 p-1.5 rounded-lg transition-colors border border-transparent hover:border-blue-100" title="Chỉnh sửa trạng thái">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Update -->
<div id="editModal" class="fixed inset-0 z-50 hidden bg-gray-900/50 backdrop-blur-sm overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-edit mr-3 opacity-80"></i>
                    Cập nhật Phương thức
                </h3>
            </div>
            
            <form action="<?= url('/admin/master-data/phuong-thuc/save') ?>" method="POST" id="editForm">
                <?= csrf_field() ?>
                <input type="hidden" name="ma_phuong_thuc" id="edit_ma">
                
                <div class="px-6 py-5 space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Mã Phương thức</label>
                        <input type="text" id="disp_ma" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-gray-500 font-medium" disabled>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tên Phương thức</label>
                        <input type="text" id="disp_ten" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-gray-500 font-medium" disabled>
                        <p class="text-xs text-gray-500 mt-1 italic">* Tên và mã nội bộ chỉ đọc. Chỉnh sửa code nếu cần thay đổi logic hệ thống.</p>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center cursor-pointer p-4 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="relative">
                                <input type="checkbox" name="is_active" id="edit_active" class="sr-only">
                                <div class="block bg-gray-300 w-12 h-6 rounded-full transition-colors duration-300 toggle-bg"></div>
                                <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-300"></div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-bold text-gray-900">Hiển thị cho thí sinh</div>
                                <div class="text-xs text-gray-500 mt-0.5">Bật để phương thức này có thể được sử dụng</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 sm:flex sm:flex-row-reverse rounded-b-2xl">
                    <button type="submit" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent px-5 py-2.5 bg-blue-600 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                        <i class="fas fa-save mr-2"></i> Lưu thay đổi
                    </button>
                    <button type="button" onclick="closeModal('editModal')" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-300 px-5 py-2.5 bg-white text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Toggle switch styles */
input:checked ~ .dot { transform: translateX(1.5rem); }
input:checked ~ .toggle-bg { background-color: #10B981; }
</style>

<script>
function openEditModal(ma, ten, isActive) {
    document.getElementById('edit_ma').value = ma;
    document.getElementById('disp_ma').value = ma;
    document.getElementById('disp_ten').value = ten;
    document.getElementById('edit_active').checked = isActive;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

// Close on backdrop click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal('editModal');
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>
