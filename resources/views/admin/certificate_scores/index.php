<?php
$title = 'Quản lý Điểm Chứng chỉ (Quy đổi)';
ob_start();
?>
<div class="p-6 h-full flex flex-col" x-data="certificateData()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Quản lý Điểm Chứng chỉ</h1>
            <div class="flex items-center gap-2 mt-1">
                <p class="text-slate-500 text-sm">Tổng cộng: <?= number_format($stats['total'] ?? 0) ?> bản ghi điểm quy đổi</p>
                <?php if ($activeSession): ?>
                    <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded text-[10px] font-bold uppercase border border-emerald-100">
                        Đợt: <?= htmlspecialchars($activeSession['ten_dot']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex gap-2">
            <button @click="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>Thêm điểm</span>
            </button>
            <button @click="openImportModal = true" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm flex items-center gap-2">
                <i class="fas fa-file-excel"></i>
                <span>Import Excel</span>
            </button>
            <button @click="exportData()" class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-4 py-2 rounded-lg font-medium transition-colors border border-indigo-200 flex items-center gap-2">
                <i class="fas fa-download"></i>
                <span>Xuất dữ liệu</span>
            </button>
            <a href="<?= url('/admin/certificate-scores/template') ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg font-medium transition-colors border border-slate-200 flex items-center gap-2">
                <i class="fas fa-file-csv"></i>
                <span>Mẫu Import</span>
            </a>
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

    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-sm text-amber-800 flex items-start gap-3">
        <i class="fas fa-info-circle mt-0.5"></i>
        <div>
            <p class="font-bold mb-1">Lưu ý quan trọng:</p>
            <p>Đây là bảng lưu **Điểm đã quy đổi** (thang điểm 10) từ chứng chỉ ngoại ngữ của thí sinh. Hệ thống sẽ sử dụng điểm này để xét tuyển cho các tổ hợp có môn Ngoại ngữ trong phương thức <b>Xét học bạ (200)</b>.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 flex-1 flex flex-col overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fas fa-certificate text-slate-400"></i>
                Danh sách điểm chứng chỉ quy đổi
            </h2>
        </div>
        <div class="flex-1 min-h-0 p-4 relative overflow-auto custom-scrollbar">
            <table id="certTable" class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold border-b border-slate-200 rounded-tl-lg">ID</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">CCCD/CMND</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Họ và tên</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Mã Môn</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Điểm Quy đổi</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Ghi chú</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200 rounded-tr-lg text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 text-sm divide-y divide-slate-100"></tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div x-show="showEditModal" class="fixed z-50 inset-0 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" @click="showEditModal = false">
                <div class="absolute inset-0 bg-slate-900 opacity-75 backdrop-blur-sm"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form @submit.prevent="saveScore">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-bold text-slate-900 mb-4" x-text="editMode ? 'Chỉnh sửa điểm chứng chỉ' : 'Thêm điểm chứng chỉ mới'"></h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Số CCCD/CMND <span class="text-red-500">*</span></label>
                                <input type="text" x-model="formData.so_cccd" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Mã môn (Vd: N1, N2...) <span class="text-red-500">*</span></label>
                                <input type="text" x-model="formData.ma_mon" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500" placeholder="N1 = Tiếng Anh, N2 = Tiếng Nga..." required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Điểm quy đổi <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" min="0" max="10" x-model="formData.diem" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Ghi chú (Tên chứng chỉ, mức đạt...)</label>
                                <textarea x-model="formData.ghi_chu" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500" rows="2" placeholder="Ví dụ: IELTS 6.5 quy đổi 10 điểm"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-2">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:w-auto sm:text-sm">
                            Lưu dữ liệu
                        </button>
                        <button type="button" @click="showEditModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div x-show="openImportModal" class="fixed z-50 inset-0 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" @click="openImportModal = false">
                <div class="absolute inset-0 bg-slate-900 opacity-75 backdrop-blur-sm"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form action="<?= url('/admin/certificate-scores/import') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-file-excel text-emerald-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-bold text-slate-900">Import Điểm Chứng chỉ</h3>
                                <div class="mt-2 text-sm text-slate-500 space-y-2">
                                    <p>Tải lên file Excel (.xlsx hoặc .csv) có định dạng cột:</p>
                                    <ol class="list-decimal ml-4 text-xs font-mono bg-slate-50 p-3 rounded-md border border-slate-200">
                                        <li>CCCD/CMND</li>
                                        <li>Mã môn (Vd: N1, N2)</li>
                                        <li>Điểm quy đổi (0-10)</li>
                                        <li>Ghi chú</li>
                                    </ol>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Chọn file</label>
                                    <input type="file" name="csv_file" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" accept=".xlsx, .xls, .csv" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-2">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 sm:w-auto sm:text-sm">
                            Tiến hành Import
                        </button>
                        <button type="button" @click="openImportModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function certificateData() {
        return {
            openImportModal: false,
            showEditModal: false,
            editMode: false,
            formData: {
                id: null,
                so_cccd: '',
                ma_mon: 'N1',
                diem: 0,
                ghi_chu: ''
            },
            openAddModal() {
                this.editMode = false;
                this.formData = { id: null, so_cccd: '', ma_mon: 'N1', diem: 0, ghi_chu: '' };
                this.showEditModal = true;
            },
            exportData() {
                const search = $('#certTable').DataTable().search();
                window.location.href = '<?= url("/admin/certificate-scores/export") ?>?search=' + encodeURIComponent(search);
            },
            editScore(row) {
                this.editMode = true;
                this.formData = { ...row };
                this.showEditModal = true;
            },
            saveScore() {
                $.post('<?= url('/admin/certificate-scores/api-save') ?>', { ...this.formData, _csrf_token: '<?= csrf_token() ?>' }, (res) => {
                    if (res.success) {
                        this.showEditModal = false;
                        $('#certTable').DataTable().ajax.reload(null, false);
                    } else {
                        alert(res.message || 'Lỗi khi lưu dữ liệu!');
                    }
                }, 'json').fail(() => alert('Lỗi kết nối máy chủ!'));
            }
        }
    }

    $(document).ready(function() {
        const table = $('#certTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: { 
                url: '<?= url('/admin/certificate-scores/api-list') ?>', 
                type: 'POST',
                data: function(d) {
                    d._csrf_token = '<?= csrf_token() ?>';
                }
            },
            columns: [
                { data: 'id', width: '60px', className: 'text-slate-500 font-mono text-xs' },
                { data: 'so_cccd', className: 'font-medium font-mono text-indigo-600' },
                { data: 'ho_va_ten', defaultContent: '<span class="text-slate-400 italic">Chưa đăng ký HS</span>' },
                { data: 'ma_mon' },
                { 
                    data: 'diem',
                    render: (data) => `<span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-md font-bold">${data}</span>`
                },
                { data: 'ghi_chu' },
                {
                    data: null,
                    orderable: false,
                    className: 'text-right',
                    render: (data, type, row) => `
                        <div class="flex justify-end gap-1">
                            <button onclick='window.certDataInstance.editScore(${JSON.stringify(row)})' class="p-1 px-2 text-indigo-600 hover:bg-indigo-50 rounded transition-colors">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteScore(${row.id})" class="p-1 px-2 text-red-600 hover:bg-red-50 rounded transition-colors">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json' },
            dom: '<"flex justify-between items-center bg-white p-4 border-b border-slate-200"<"flex items-center gap-2"l><"search-box"f>>rt<"flex justify-between items-center p-4 bg-slate-50"ip>',
            initComplete: function() {
                $('.dataTables_filter input').addClass('w-64 pl-4 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all').attr('placeholder', 'Tìm CCCD, Tên...');
                $('.dataTables_length select').addClass('border border-slate-300 rounded-md text-sm py-1 pl-2 pr-8 bg-white outline-none focus:ring-2 focus:ring-indigo-500');
            }
        });
        
        // Expose Alpine instance to outside (Alpine v3)
        window.certDataInstance = Alpine.$data(document.querySelector('[x-data]'));
    });

    function deleteScore(id) {
        if (confirm('Bạn có chắc chắn muốn xóa bản ghi điểm quy đổi này?')) {
            $.post('<?= url('/admin/certificate-scores/delete') ?>', { id: id, _csrf_token: '<?= csrf_token() ?>' }, (res) => {
                if (res.success) $('#certTable').DataTable().ajax.reload(null, false);
                else alert('Lỗi khi xóa!');
            }, 'json');
        }
    }
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>
