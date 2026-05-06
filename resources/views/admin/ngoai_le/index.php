<?php ob_start();
/**
 * @var string $title
 * @var array $sessions
 * @var int $currentSessionId
 * @var array $exceptions
 * @var array $majors
 */
?>

<!-- Header -->
<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white font-heading"><?= $title ?></h1>
        <p class="text-slate-500 text-sm mt-1">Quản lý và ép buộc trạng thái xét tuyển cho từng nguyện vọng cụ thể.</p>
    </div>
    <div class="mt-4 md:mt-0">
        <form id="sessionForm" method="GET" action="<?= url('/admin/admission/exceptions') ?>" class="flex items-center space-x-2">
            <label for="session_id" class="text-sm font-medium text-slate-600 dark:text-slate-300 whitespace-nowrap">Đợt tuyển sinh:</label>
            <select name="session_id" id="session_id" onchange="document.getElementById('sessionForm').submit()" class="form-select w-48 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm">
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $s['id'] == $currentSessionId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['ten_dot'] . ' (' . $s['nam_tuyen_sinh'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form Thêm mới -->
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-5 border border-slate-200 dark:border-gray-700">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4"><i class="fas fa-plus-circle text-blue-500 mr-2"></i> Thêm/Sửa Ngoại Lệ</h2>
            <form id="frmSaveException" onsubmit="saveException(event)">
                <input type="hidden" name="session_id" value="<?= $currentSessionId ?>">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Số CCCD *</label>
                    <input type="text" name="so_cccd" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" placeholder="Nhập số CCCD thí sinh">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ngành xét tuyển *</label>
                    <select name="ma_nganh" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                        <option value="">-- Chọn ngành --</option>
                        <?php foreach ($majors as $m): ?>
                            <option value="<?= $m['ma_nganh'] ?>"><?= htmlspecialchars($m['ma_nganh'] . ' - ' . $m['ten_nganh']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trạng thái ép buộc *</label>
                    <select name="trang_thai_ep_buoc" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                        <option value="TrungTuyen" class="font-bold text-emerald-600">Luôn Đỗ (Trúng Tuyển)</option>
                        <option value="Truot" class="font-bold text-rose-600">Luôn Trượt (Loại bỏ)</option>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ghi chú / Lý do</label>
                    <textarea name="ghi_chu" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" placeholder="VD: Đạt giải HSG Quốc gia, Tuyển thẳng..."></textarea>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-md transition-colors flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Lưu Cấu Hình
                </button>
            </form>
        </div>
    </div>

    <!-- Danh sách Ngoại lệ -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-5 border border-slate-200 dark:border-gray-700">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4"><i class="fas fa-list text-emerald-500 mr-2"></i> Danh sách Ngoại lệ (Đợt hiện tại)</h2>
            
            <?php if (empty($exceptions)): ?>
                <div class="text-center py-10 bg-slate-50 dark:bg-gray-700/50 rounded-lg border border-dashed border-slate-300 dark:border-gray-600">
                    <i class="fas fa-box-open text-4xl text-slate-400 mb-3"></i>
                    <p class="text-slate-500 dark:text-slate-400">Chưa có ngoại lệ nào được thiết lập cho đợt này.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Thí sinh</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ngành Xét Tuyển</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ép Buộc</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ghi chú</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($exceptions as $ex): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($ex['so_cccd']) ?></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($ex['ho_va_ten'] ?? 'N/A') ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900 dark:text-white font-medium"><?= htmlspecialchars($ex['ma_nganh']) ?></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs"><?= htmlspecialchars($ex['ten_nganh'] ?? '') ?></div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <?php if ($ex['trang_thai_ep_buoc'] === 'TrungTuyen'): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400">
                                                <i class="fas fa-check-circle mr-1 mt-0.5"></i> Trúng Tuyển
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-400">
                                                <i class="fas fa-times-circle mr-1 mt-0.5"></i> Trượt
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate" title="<?= htmlspecialchars($ex['ghi_chu']) ?>">
                                        <?= htmlspecialchars($ex['ghi_chu'] ?: '-') ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
                                        <button onclick="editException(<?= htmlspecialchars(json_encode($ex)) ?>)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteException(<?= $ex['id'] ?>)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function saveException(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        if (window.Loading) window.Loading.show();
        
        fetch('<?= url("/admin/admission/exceptions/save") ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (window.Loading) window.Loading.hide();
            if (data.success) {
                window.location.reload();
            } else {
                if (window.showToast) showToast(data.message || 'Có lỗi xảy ra', 'error');
                else alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            if (window.Loading) window.Loading.hide();
            console.error('Error:', error);
            alert('Lỗi kết nối');
        });
    }

    function editException(ex) {
        const form = document.getElementById('frmSaveException');
        form.elements['so_cccd'].value = ex.so_cccd;
        form.elements['ma_nganh'].value = ex.ma_nganh;
        form.elements['trang_thai_ep_buoc'].value = ex.trang_thai_ep_buoc;
        form.elements['ghi_chu'].value = ex.ghi_chu || '';
        
        // Cuộn lên form
        form.scrollIntoView({ behavior: 'smooth' });
    }

    function deleteException(id) {
        if (!confirm('Bạn có chắc chắn muốn xoá cấu hình ngoại lệ này?')) {
            return;
        }

        if (window.Loading) window.Loading.show();
        const formData = new FormData();
        formData.append('id', id);

        fetch('<?= url("/admin/admission/exceptions/delete") ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (window.Loading) window.Loading.hide();
            if (data.success) {
                window.location.reload();
            } else {
                if (window.showToast) showToast(data.message || 'Có lỗi xảy ra', 'error');
                else alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            if (window.Loading) window.Loading.hide();
            console.error('Error:', error);
            alert('Lỗi kết nối');
        });
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>
