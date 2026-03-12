<?php
$title = 'Quản lý Điểm chứng chỉ';
ob_start();
?>
<div class="p-6 h-full flex flex-col" x-data="certificateData()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Cập nhật Điểm chứng chỉ</h1>
            <p class="text-slate-500 text-sm mt-1">Tổng cộng: <?= number_format($stats['total']) ?> bản ghi</p>
        </div>
        <div class="flex gap-2">
            <button @click="openImportModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm flex items-center gap-2">
                <i class="fas fa-file-csv hidden sm:inline"></i>
                <span>Import CSV</span>
            </button>
        </div>
    </div>

    <!-- Alert Messages (handled by flash session) -->
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
                <i class="fas fa-table text-slate-400"></i>
                Danh sách chứng chỉ
            </h2>
        </div>
        <!-- Table wrapper -->
        <div class="flex-1 min-h-0 p-4 relative overflow-auto custom-scrollbar">
            <table id="certificateTable" class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold border-b border-slate-200 rounded-tl-lg">ID</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">CCCD/CMND</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Họ và tên</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Loại CC</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Điểm CC</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200 rounded-tr-lg text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                    <!-- Data populated by DataTables -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Import Modal -->
    <div x-show="openImportModal" class="fixed z-50 inset-0 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" x-show="openImportModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="absolute inset-0 bg-slate-900 opacity-75 backdrop-blur-sm"></div>
            </div>

            <!-- Modal Panel -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full"
                 x-show="openImportModal"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 @click.away="openImportModal = false">
                 
                <form action="/admin/certificate-scores/import" method="POST" enctype="multipart/form-data">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-file-csv text-indigo-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-bold text-slate-900">Import Điểm Chứng chỉ</h3>
                                <div class="mt-2 text-sm text-slate-500 space-y-2">
                                    <p>Tải lên file định dạng CSV (.csv, UTF-8) có các cột theo thứ tự:</p>
                                    <ol class="list-decimal ml-4 text-xs font-mono bg-slate-50 p-3 rounded-md border border-slate-200">
                                        <li>CCCD/CMND (Bắt buộc)</li>
                                        <li>Loại chứng chỉ (Vd: IELTS, TOEFL...)</li>
                                        <li>Điểm chứng chỉ (Bắt buộc)</li>
                                    </ol>
                                    <p class="text-xs text-amber-600 font-medium"><i class="fas fa-exclamation-triangle mr-1"></i> Hệ thống sẽ bỏ qua dòng đầu tiên (Header).</p>
                                    <p class="text-xs text-amber-600 font-medium"><i class="fas fa-exclamation-triangle mr-1"></i> Nếu thí sinh đã có chứng chỉ CÙNG LOẠI, hệ thống sẽ <b>GHI ĐÈ</b> bản ghi cũ.</p>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Chọn file CSV</label>
                                    <div class="flex items-center justify-center w-full">
                                        <label class="flex flex-col border-2 border-dashed border-slate-300 hover:border-indigo-400 hover:bg-indigo-50 rounded-lg p-6 w-full cursor-pointer transition-colors text-center group">
                                            <i class="fas fa-cloud-upload-alt text-3xl text-slate-400 group-hover:text-indigo-500 mb-2 transition-colors"></i>
                                            <span class="text-sm font-medium text-slate-600 group-hover:text-indigo-600" id="fileNameDisplay">Nhấp để chọn file</span>
                                            <span class="text-xs text-slate-400 mt-1">Hỗ trợ: .csv</span>
                                            <input type="file" name="csv_file" class="hidden" accept=".csv" @change="updateFileName($event)">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-2">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm transition-colors">
                            <i class="fas fa-upload mr-2 mt-0.5"></i> Tiến hành Import
                        </button>
                        <button type="button" @click="openImportModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm transition-colors">
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
            updateFileName(e) {
                const file = e.target.files[0];
                if (file) {
                    document.getElementById('fileNameDisplay').textContent = file.name;
                    document.getElementById('fileNameDisplay').classList.add('text-indigo-600', 'font-bold');
                }
            }
        }
    }

    $(document).ready(function() {
        var table = $('#certificateTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/certificate-scores/api-list',
                type: 'POST'
            },
            columns: [
                { data: 'id', width: '60px', className: 'text-slate-500 font-mono text-xs' },
                { data: 'so_cccd', className: 'font-medium font-mono text-indigo-600' },
                { data: 'ho_va_ten' },
                { 
                    data: 'loai_chung_chi',
                    render: function(data) {
                        return '<span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-md font-bold text-xs">' + data + '</span>';
                    }
                },
                { 
                    data: 'diem_chung_chi',
                    render: function(data) {
                        return '<span class="font-bold text-slate-700">' + data + '</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-right',
                    render: function(data, type, row) {
                        return `
                            <button onclick="deleteCert(${row.id})" class="p-1 px-2 text-red-600 hover:bg-red-50 hover:text-red-700 rounded transition-colors" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                    }
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json',
            },
            dom: '<"flex flex-col sm:flex-row justify-between items-center bg-white p-4 border-b border-slate-200 gap-4"<"flex items-center gap-2"l><"flex-1 w-full sm:max-w-xs relative search-box"f>>rt<"flex flex-col sm:flex-row justify-between items-center p-4 bg-slate-50 gap-4"ip>',
            initComplete: function() {
                 $('.dataTables_filter input').addClass('w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all').attr('placeholder', 'Tìm CCCD, Tên...');
                 $('.dataTables_length select').addClass('border border-slate-300 rounded-md text-sm py-1.5 pl-3 pr-8 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500');
            }
        });
    });

    function deleteCert(id) {
        if (confirm('Bạn có chắc chắn muốn xóa bản ghi chứng chỉ này?')) {
            $.post('/admin/certificate-scores/delete', { id: id }, function(response) {
                var res = JSON.parse(response);
                if (res.success) {
                    $('#certificateTable').DataTable().ajax.reload(null, false);
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
