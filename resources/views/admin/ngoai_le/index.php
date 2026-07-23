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
<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white font-heading flex items-center gap-2">
            <i class="fas fa-user-shield text-blue-600"></i> <?= $title ?>
        </h1>
        <p class="text-slate-500 text-sm mt-1">Quản lý và ép buộc trạng thái xét tuyển (xử lý ngoại lệ, loại bỏ thí sinh không đạt nguồn tuyển từ Bộ GD&ĐT).</p>
    </div>
    
    <div class="flex flex-wrap items-center gap-3">
        <!-- Đợt tuyển sinh -->
        <form id="sessionForm" method="GET" action="<?= url('/admin/admission/exceptions') ?>" class="flex items-center space-x-2">
            <label for="session_id" class="text-sm font-medium text-slate-600 dark:text-slate-300 whitespace-nowrap">Đợt:</label>
            <select name="session_id" id="session_id" onchange="document.getElementById('sessionForm').submit()" class="form-select border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white text-sm font-semibold">
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $s['id'] == $currentSessionId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['ten_dot'] . ' (' . $s['nam_tuyen_sinh'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Nút Import từ Bộ GD&ĐT -->
        <button onclick="openImportModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold py-2 px-4 rounded-lg shadow-md transition flex items-center gap-2">
            <i class="fas fa-file-excel text-base"></i> Import file từ Bộ GD&ĐT
        </button>

        <!-- Nút Xóa dữ liệu Bộ -->
        <button onclick="deleteBoGDData()" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:border-rose-800 dark:text-rose-300 text-sm font-bold py-2 px-3.5 rounded-lg transition flex items-center gap-1.5" title="Xóa tất cả ngoại lệ đã import từ Bộ GD&ĐT cho đợt này">
            <i class="fas fa-trash-alt"></i> Xóa dữ liệu Bộ GD&ĐT
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form Thêm mới -->
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-5 border border-slate-200 dark:border-gray-700">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center justify-between">
                <span><i class="fas fa-plus-circle text-blue-500 mr-2"></i> Thêm / Sửa Thủ Công</span>
            </h2>
            <form id="frmSaveException" onsubmit="saveException(event)">
                <input type="hidden" name="session_id" value="<?= $currentSessionId ?>">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Số CCCD *</label>
                    <input type="text" name="so_cccd" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" placeholder="Nhập số CCCD thí sinh (12 số)">
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
                        <option value="Truot" class="font-bold text-rose-600">Luôn Trượt (Loại bỏ khỏi nguồn tuyển)</option>
                        <option value="TrungTuyen" class="font-bold text-emerald-600">Luôn Đỗ (Trúng Tuyển / Tuyển thẳng)</option>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ghi chú / Lý do</label>
                    <textarea name="ghi_chu" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" placeholder="VD: Đạt giải HSG Quốc gia, Vi phạm điều kiện nguồn tuyển..."></textarea>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-md transition flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Lưu Cấu Hình
                </button>
            </form>
        </div>
    </div>

    <!-- Danh sách Ngoại lệ -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-5 border border-slate-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                <h2 class="text-lg font-bold text-slate-800 dark:text-white flex items-center">
                    <i class="fas fa-list text-emerald-500 mr-2"></i> Danh sách Ngoại lệ 
                    <span class="ml-2 text-xs font-normal px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300">
                        <?= count($exceptions) ?> bản ghi
                    </span>
                </h2>

                <!-- Filter tab -->
                <div class="flex items-center space-x-1 bg-slate-100 dark:bg-gray-700/60 p-1 rounded-lg text-xs font-medium">
                    <button onclick="filterSource('all')" id="btnFilterAll" class="px-3 py-1.5 rounded-md bg-white dark:bg-gray-800 shadow text-slate-800 dark:text-white font-bold">Tất cả</button>
                    <button onclick="filterSource('bogd')" id="btnFilterBoGD" class="px-3 py-1.5 rounded-md text-slate-600 dark:text-slate-300 hover:text-slate-900">Từ Bộ GD&ĐT</button>
                    <button onclick="filterSource('manual')" id="btnFilterManual" class="px-3 py-1.5 rounded-md text-slate-600 dark:text-slate-300 hover:text-slate-900">Thủ công</button>
                </div>
            </div>
            
            <?php if (empty($exceptions)): ?>
                <div class="text-center py-10 bg-slate-50 dark:bg-gray-700/50 rounded-lg border border-dashed border-slate-300 dark:border-gray-600">
                    <i class="fas fa-box-open text-4xl text-slate-400 mb-3"></i>
                    <p class="text-slate-500 dark:text-slate-400">Chưa có ngoại lệ nào được thiết lập cho đợt này.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="tblExceptions">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Thí sinh</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ngành Xét Tuyển</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nguồn / Loại</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Trạng thái</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ghi chú</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Xóa</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($exceptions as $ex): 
                                $isBoGD = (strpos($ex['ghi_chu'], '[Bộ GD&ĐT]') !== false);
                            ?>
                                <tr class="ex-row hover:bg-slate-50 dark:hover:bg-slate-700/50 transition" data-source="<?= $isBoGD ? 'bogd' : 'manual' ?>">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white font-mono"><?= htmlspecialchars($ex['so_cccd']) ?></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($ex['ho_va_ten'] ?? 'Chưa rõ họ tên') ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900 dark:text-white font-semibold font-mono"><?= htmlspecialchars($ex['ma_nganh']) ?></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs"><?= htmlspecialchars($ex['ten_nganh'] ?? '') ?></div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <?php if ($isBoGD): ?>
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-bold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                                <i class="fas fa-university mr-1 mt-0.5"></i> Bộ GD&ĐT
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-bold rounded-full bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-300">
                                                <i class="fas fa-user-edit mr-1 mt-0.5"></i> Thủ công
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <?php if ($ex['trang_thai_ep_buoc'] === 'TrungTuyen'): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400">
                                                <i class="fas fa-check-circle mr-1 mt-0.5"></i> Đỗ
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-400">
                                                <i class="fas fa-times-circle mr-1 mt-0.5"></i> Trượt (Loại)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 max-w-xs truncate" title="<?= htmlspecialchars($ex['ghi_chu']) ?>">
                                        <?= htmlspecialchars($ex['ghi_chu'] ?: '-') ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
                                        <button onclick="editException(<?= htmlspecialchars(json_encode($ex)) ?>)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-2" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteException(<?= $ex['id'] ?>)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="Xóa">
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

<!-- Modal Import từ Bộ GD&ĐT -->
<div id="importModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 dark:border-gray-700 transform transition-all">
        <div class="px-6 py-4 bg-slate-50 dark:bg-gray-700/50 border-b border-slate-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 dark:text-white text-base flex items-center gap-2">
                <i class="fas fa-file-excel text-emerald-600"></i> Import Danh Sách Vi Phạm Từ Bộ GD&ĐT
            </h3>
            <button onclick="closeImportModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <form id="frmImportBoGD" onsubmit="submitImportBoGD(event)" class="p-6">
            <input type="hidden" name="session_id" value="<?= $currentSessionId ?>">

            <div class="mb-4 text-xs text-slate-600 dark:text-slate-300 bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg border border-blue-100 dark:border-blue-800">
                <p class="font-bold mb-1 text-blue-800 dark:text-blue-300"><i class="fas fa-info-circle mr-1"></i> Quy định đọc file Excel/CSV từ Bộ GD&ĐT:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <li>Hệ thống tự nhận diện cột <strong>ĐDCN / Số CCCD</strong> và <strong>Mã xét tuyển / Mã ngành</strong>.</li>
                    <li>Tất cả nguyện vọng trong file sẽ được lưu trạng thái <strong>Luôn Trượt (Loại bỏ)</strong>.</li>
                    <li>Nếu nguyện vọng đã có trong hệ thống, dữ liệu sẽ tự động <strong>Ghi đè (Overwrite)</strong>.</li>
                </ul>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Chọn file Excel (.xlsx, .xls, .csv)</label>
                <input type="file" name="file" accept=".xlsx, .xls, .csv" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeImportModal()" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-gray-700 rounded-lg">
                    Hủy bỏ
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-md transition flex items-center gap-2">
                    <i class="fas fa-upload"></i> Thực Hiện Import
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openImportModal() {
        document.getElementById('importModal').classList.remove('hidden');
    }

    function closeImportModal() {
        document.getElementById('importModal').classList.add('hidden');
    }

    function filterSource(type) {
        const rows = document.querySelectorAll('.ex-row');
        const btnAll = document.getElementById('btnFilterAll');
        const btnBoGD = document.getElementById('btnFilterBoGD');
        const btnManual = document.getElementById('btnFilterManual');

        [btnAll, btnBoGD, btnManual].forEach(b => {
            b.className = "px-3 py-1.5 rounded-md text-slate-600 dark:text-slate-300 hover:text-slate-900";
        });

        if (type === 'all') {
            btnAll.className = "px-3 py-1.5 rounded-md bg-white dark:bg-gray-800 shadow text-slate-800 dark:text-white font-bold";
            rows.forEach(r => r.style.display = '');
        } else if (type === 'bogd') {
            btnBoGD.className = "px-3 py-1.5 rounded-md bg-white dark:bg-gray-800 shadow text-slate-800 dark:text-white font-bold";
            rows.forEach(r => r.style.display = r.dataset.source === 'bogd' ? '' : 'none');
        } else if (type === 'manual') {
            btnManual.className = "px-3 py-1.5 rounded-md bg-white dark:bg-gray-800 shadow text-slate-800 dark:text-white font-bold";
            rows.forEach(r => r.style.display = r.dataset.source === 'manual' ? '' : 'none');
        }
    }

    function submitImportBoGD(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        if (window.Loading) window.Loading.show();

        fetch('<?= url("/admin/admission/exceptions/import-bo-gd") ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (window.Loading) window.Loading.hide();
            if (data.success) {
                if (window.showToast) showToast(data.message, 'success');
                else alert(data.message);
                window.location.reload();
            } else {
                if (window.showToast) showToast(data.message || 'Lỗi import', 'error');
                else alert(data.message || 'Lỗi import');
            }
        })
        .catch(err => {
            if (window.Loading) window.Loading.hide();
            console.error(err);
            alert('Lỗi kết nối máy chủ');
        });
    }

    function deleteBoGDData() {
        if (!confirm('Bạn có chắc chắn muốn XÓA TẤT CẢ ngoại lệ từ Bộ GD&ĐT của đợt này để nạp lại file mới không?')) {
            return;
        }

        if (window.Loading) window.Loading.show();
        const formData = new FormData();
        formData.append('session_id', '<?= $currentSessionId ?>');

        fetch('<?= url("/admin/admission/exceptions/delete-bo-gd") ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (window.Loading) window.Loading.hide();
            if (data.success) {
                if (window.showToast) showToast(data.message, 'success');
                else alert(data.message);
                window.location.reload();
            } else {
                if (window.showToast) showToast(data.message || 'Không thể xóa dữ liệu', 'error');
                else alert(data.message || 'Không thể xóa dữ liệu');
            }
        })
        .catch(err => {
            if (window.Loading) window.Loading.hide();
            console.error(err);
            alert('Lỗi kết nối');
        });
    }

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
        
        // Loại bỏ tiền tố [Bộ GD&ĐT] khi load vào form nếu có
        let cleanNote = (ex.ghi_chu || '').replace(/^\[Bộ GD&ĐT\]\s*/, '');
        form.elements['ghi_chu'].value = cleanNote;
        
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
