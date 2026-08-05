<?php
$title = 'Cấu hình Quy đổi Chứng chỉ';
ob_start();
?>
<div class="p-6 h-full flex flex-col" x-data="certificateRuleData()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Cấu hình Quy đổi Chứng chỉ</h1>
            <p class="text-slate-500 text-sm mt-1">Thiết lập mức điểm quy đổi từ Chứng chỉ quốc tế sang điểm môn học.</p>
        </div>
        <div class="flex gap-2">
            <button @click="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>Thêm quy tắc mới</span>
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg flex items-start shadow-sm">
            <i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-3"></i>
            <div>
                <h3 class="text-emerald-800 font-bold text-sm">Thành công!</h3>
                <p class="text-emerald-700 text-sm mt-1"><?= htmlspecialchars($_SESSION['flash_success']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg flex items-start shadow-sm">
            <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
            <div>
                <h3 class="text-red-800 font-bold text-sm">Lỗi!</h3>
                <p class="text-red-700 text-sm mt-1"><?= htmlspecialchars($_SESSION['flash_error']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 flex-1 flex flex-col overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fas fa-list text-slate-400"></i>
                Danh sách quy tắc quy đổi
            </h2>
        </div>
        <div class="flex-1 min-h-0 p-4 relative overflow-auto custom-scrollbar">
            <table id="rulesTable" class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">ID</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Loại chứng chỉ</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Mức điểm từ</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Mức điểm đến</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Điểm quy đổi</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Môn học quy đổi</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                    <?php foreach ($rules as $r): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-mono text-xs text-slate-400"><?= $r['id'] ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded-md font-bold text-xs">
                                <?= htmlspecialchars($r['loai_chung_chi']) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 font-medium text-slate-700"><?= number_format($r['muc_diem_tu'], 1) ?></td>
                        <td class="py-3 px-4 text-slate-500"><?= $r['muc_diem_den'] ? number_format($r['muc_diem_den'], 1) : '<span class="italic">Không giới hạn</span>' ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-lg font-bold">
                                <?= number_format($r['diem_quy_doi'], 2) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($r['ten_mon']) ?></td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex justify-end gap-1">
                                <button @click="openEditModal(<?= htmlspecialchars(json_encode($r)) ?>)" class="p-1 px-2 text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteRule(<?= $r['id'] ?>)" class="p-1 px-2 text-red-600 hover:bg-red-50 rounded transition-colors" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div x-show="showModal" class="fixed z-50 inset-0 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="absolute inset-0 bg-slate-900 opacity-75 backdrop-blur-sm"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full"
                 x-show="showModal"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 @click.away="showModal = false">
                 
                <form :action="isEdit ? '/admin/certificate-rules/update' : '/admin/certificate-rules/store'" method="POST">
    <?= csrf_field() ?>
                    <input type="hidden" name="id" x-model="formData.id">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-slate-100">
                        <h3 class="text-lg leading-6 font-bold text-slate-900 mb-4" x-text="isEdit ? 'Cập nhật quy tắc' : 'Thêm quy tắc mới'"></h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Loại chứng chỉ</label>
                                <select name="loai_chung_chi" x-model="formData.loai_chung_chi" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                                    <option value="IELTS">IELTS</option>
                                    <option value="TOEFL iBT">TOEFL iBT</option>
                                    <option value="TOEIC">TOEIC</option>
                                    <option value="VSTEP">VSTEP</option>
                                    <option value="TOPIK">TOPIK</option>
                                    <option value="HSK">HSK</option>
                                    <option value="DELF">DELF</option>
                                </select>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Điểm từ</label>
                                    <input type="number" step="0.5" name="muc_diem_tu" x-model="formData.muc_diem_tu" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Điểm đến (nếu có)</label>
                                    <input type="number" step="0.5" name="muc_diem_den" x-model="formData.muc_diem_den" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Để trống nếu >=">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Điểm quy đổi (Thang 10)</label>
                                <input type="number" step="0.1" name="diem_quy_doi" x-model="formData.diem_quy_doi" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-emerald-50 font-bold border-emerald-200" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Môn học áp dụng</label>
                                <select name="mon_id" x-model="formData.mon_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['ten_mon']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm">
                            <span x-text="isEdit ? 'Cập nhật' : 'Lưu quy tắc'"></span>
                        </button>
                        <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function certificateRuleData() {
        return {
            showModal: false,
            isEdit: false,
            formData: {
                id: '',
                loai_chung_chi: 'IELTS',
                muc_diem_tu: '',
                muc_diem_den: '',
                diem_quy_doi: '',
                mon_id: 9 // Default to Ngoai ngu
            },
            openAddModal() {
                this.isEdit = false;
                this.formData = {
                    id: '',
                    loai_chung_chi: 'IELTS',
                    muc_diem_tu: '',
                    muc_diem_den: '',
                    diem_quy_doi: '',
                    mon_id: 9
                };
                this.showModal = true;
            },
            openEditModal(rule) {
                this.isEdit = true;
                this.formData = {
                    id: rule.id,
                    loai_chung_chi: rule.loai_chung_chi,
                    muc_diem_tu: rule.muc_diem_tu,
                    muc_diem_den: rule.muc_diem_den || '',
                    diem_quy_doi: rule.diem_quy_doi,
                    mon_id: rule.mon_id
                };
                this.showModal = true;
            }
        }
    }

    $(document).ready(function() {
        $('#rulesTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json',
            },
            dom: '<"p-4 border-b border-slate-200 flex justify-between"<"row-info"i><"search-box"f>>rt<"p-4 bg-slate-50 border-t border-slate-200"p>',
            pageLength: 25
        });
    });

    function deleteRule(id) {
        if (confirm('Bạn có chắc chắn muốn xóa quy tắc này?')) {
            $.post('/admin/certificate-rules/delete', { id: id }, function(response) {
                const res = JSON.parse(response);
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Lỗi khi xóa!');
                }
            });
        }
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>
