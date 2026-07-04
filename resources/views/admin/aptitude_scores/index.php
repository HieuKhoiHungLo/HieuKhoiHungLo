<?php
$title = 'Quản lý Điểm năng khiếu';
ob_start();
?>
<div class="p-6 h-full flex flex-col" x-data="aptitudeData()">
    <!-- Row 1: Header & Filters -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Điểm năng khiếu</h1>
            <p class="text-slate-500 text-sm mt-1">Tổng cộng: <span class="font-semibold text-slate-700"><?= number_format($stats['total']) ?></span> bản ghi</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3">
            <label class="text-sm font-medium text-slate-600">Đợt tuyển sinh:</label>
            <!-- Filter by Year -->
            <select id="yearFilter" class="border border-slate-300 rounded-lg text-sm bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 min-w-[100px]" x-model="selectedYear" @change="selectedSession = ''; sessionChanged()">
                <option value="">-- Năm --</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>"><?= $year ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Filter by Session -->
            <select id="sessionFilter" class="border border-slate-300 rounded-lg text-sm bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 min-w-[250px]" x-model="selectedSession" @change="sessionChanged()">
                <option value="">-- Chọn đợt xét tuyển --</option>
                <template x-for="session in filteredSessions" :key="session.id">
                    <option :value="session.id" x-text="session.ten_dot || session.ten_dot_xet_tuyen" :selected="session.id == selectedSession"></option>
                </template>
            </select>
        </div>
    </div>

    <!-- Row 2: Action Buttons (Constructive left, Destructive right) -->
    <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 mb-6 bg-slate-50 p-4 rounded-xl border border-slate-200">
        <!-- Left side: Constructive actions -->
        <div class="flex flex-wrap items-center gap-2">
            <button @click="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg font-semibold transition-colors shadow-sm flex items-center gap-2 text-sm">
                <i class="fas fa-plus"></i>
                <span>Thêm mới</span>
            </button>
            <button @click="openImportModal = true" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg font-semibold transition-colors shadow-sm flex items-center gap-2 text-sm">
                <i class="fas fa-file-excel"></i>
                <span>Import Excel</span>
            </button>
            <button @click="exportData()" class="bg-white hover:bg-slate-50 text-slate-700 px-4 py-2.5 rounded-lg font-semibold transition-colors border border-slate-300 flex items-center gap-2 text-sm shadow-sm">
                <i class="fas fa-download text-slate-500"></i>
                <span>Xuất dữ liệu</span>
            </button>
            <a href="<?= url('/admin/aptitude-scores/template') ?>" class="bg-white hover:bg-slate-50 text-slate-700 px-4 py-2.5 rounded-lg font-semibold transition-colors border border-slate-300 flex items-center gap-2 text-sm shadow-sm">
                <i class="fas fa-file-csv text-slate-500"></i>
                <span>Mẫu Import</span>
            </a>
        </div>

        <!-- Right side: Destructive actions -->
        <div class="flex flex-wrap items-center gap-2 justify-end">
            <!-- Nút xóa hàng loạt -->
            <button id="btnDeleteSelected" @click="deleteSelected()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-lg font-semibold transition-all shadow-sm flex items-center gap-2 text-sm hidden">
                <i class="fas fa-minus-circle"></i>
                <span>Xóa mục chọn (<span id="selectedCount">0</span>)</span>
            </button>
            <button @click="deleteAllScores()" class="bg-red-50 hover:bg-red-100 text-red-600 px-4 py-2.5 rounded-lg font-semibold transition-colors border border-red-200 flex items-center gap-2 text-sm shadow-sm">
                <i class="fas fa-trash-alt"></i>
                <span>Xóa tất cả</span>
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

    <?php if (isset($_SESSION['flash_warning'])): ?>
        <div class="mb-4 bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg flex items-start shadow-sm">
            <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 mr-3"></i>
            <div>
                <h3 class="text-amber-800 font-bold text-sm">Cảnh báo!</h3>
                <p class="text-amber-700 text-sm mt-1"><?= htmlspecialchars($_SESSION['flash_warning']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['flash_warning']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 flex-1 flex flex-col overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h2 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fas fa-table text-slate-400"></i>
                Danh sách điểm thi tại trường
            </h2>
        </div>
        <div class="flex-1 min-h-0 p-4 relative overflow-auto custom-scrollbar">
            <table id="aptitudeTable" class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold border-b border-slate-200 rounded-tl-lg text-center w-10">
                            <input type="checkbox" id="selectAll" class="border-slate-300 rounded text-indigo-600 focus:ring-indigo-500">
                        </th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">ID</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">CCCD/CMND</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">SBD</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Họ và tên</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Mã Môn</th>
                        <th class="py-3 px-4 font-semibold border-b border-slate-200">Điểm</th>
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
                        <h3 class="text-lg leading-6 font-bold text-slate-900 mb-4" x-text="editMode ? 'Chỉnh sửa điểm' : 'Thêm điểm mới'"></h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Số CCCD/CMND <span class="text-red-500">*</span></label>
                                <input type="text" x-model="formData.so_cccd" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Số báo danh (SBD)</label>
                                <input type="text" x-model="formData.sbd" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Mã môn năng khiếu <span class="text-red-500">*</span></label>
                                <select x-model="formData.ma_mon" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" required>
                                    <option value="NK1">NK1: Năng khiếu GDMN</option>
                                    <option value="NK2">NK2: Năng khiếu Âm nhạc</option>
                                    <option value="NK3">NK3: Năng khiếu Mỹ thuật</option>
                                    <option value="NK4">NK4: Năng khiếu Giáo dục thể chất</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Điểm thi <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" min="0" max="10" x-model="formData.diem" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Ghi chú</label>
                                <textarea x-model="formData.ghi_chu" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-2">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:w-auto sm:text-sm">
                            Lưu thay đổi
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
                <form @submit.prevent="submitImport($event)" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="session_id" :value="selectedSession">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-file-excel text-emerald-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-bold text-slate-900">Import Điểm Năng khiếu</h3>
                                <div class="mt-2 text-sm text-slate-500 space-y-2">
                                     <ol class="list-decimal ml-4 text-xs font-mono bg-slate-50 p-3 rounded-md border border-slate-200">
                                         <li>STT</li>
                                         <li>CMND</li>
                                         <li>Họ tên</li>
                                         <li>Ngày sinh</li>
                                         <li>Mã môn NK</li>
                                         <li>Điểm</li>
                                     </ol>
                                    <div class="mt-2">
                                        <a href="<?= url('/admin/aptitude-scores/template') ?>" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                            <i class="fas fa-download mr-1"></i> Tải file mẫu (.csv)
                                        </a>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Chọn file</label>
                                    <input type="file" name="excel_file" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" accept=".xlsx, .xls, .csv" required>
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
    function aptitudeData() {
        return {
            openImportModal: false,
            showEditModal: false,
            editMode: false,
            isLoading: false,
            currentLoadingMessage: '',
            selectedYear: '<?= $activeSession ? $activeSession['nam_tuyen_sinh'] : "" ?>',
            selectedSession: '<?= $activeSession ? $activeSession['id'] : "" ?>',
            allSessions: <?= json_encode($sessions) ?>,
            formData: {
                id: null,
                so_cccd: '',
                sbd: '',
                ma_mon: 'NK1',
                diem: 0,
                ghi_chu: '',
                session_id: '<?= $activeSession ? $activeSession['id'] : "" ?>'
            },
            get filteredSessions() {
                if (!this.selectedYear) return this.allSessions;
                return this.allSessions.filter(s => s.nam_tuyen_sinh == this.selectedYear);
            },
            sessionChanged() {
                if (this.selectedSession) {
                    window.location.href = '<?= url("/admin/aptitude-scores") ?>?session_id=' + this.selectedSession;
                } else {
                    window.location.href = '<?= url("/admin/aptitude-scores") ?>';
                }
            },
            submitImport(event) {
                const form = event.target;
                const formData = new FormData(form);
                
                this.openImportModal = false;
                this.isLoading = true;
                this.currentLoadingMessage = 'Đang tải tệp tin và đối chiếu thông tin thí sinh...';
                
                fetch('<?= url('/admin/aptitude-scores/import') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/vnd.ms-excel')) {
                        // Trả về file báo cáo lỗi (validation failure)
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'ket_qua_import_diem_nang_khieu_loi.xls';
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            window.URL.revokeObjectURL(url);
                            
                            this.isLoading = false;
                            Swal.fire({
                                icon: 'warning',
                                title: 'Phát hiện dữ liệu không khớp!',
                                text: 'Một số dòng dữ liệu bị lỗi thông tin (CCCD, Họ tên hoặc Ngày sinh). Hệ thống đã hủy import và tự động tải về file Excel báo cáo lỗi chi tiết.',
                                confirmButtonColor: '#3B82F6',
                                confirmButtonText: 'Đóng'
                            }).then(() => {
                                location.reload();
                            });
                        });
                    } else {
                        // Thành công, reload lại để hiển thị flash message
                        location.reload();
                    }
                })
                .catch(error => {
                    this.isLoading = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi kết nối máy chủ',
                        text: error.message || 'Không thể gửi dữ liệu lên máy chủ.'
                    });
                });
            },
            openAddModal() {
                this.editMode = false;
                this.formData = { id: null, so_cccd: '', sbd: '', ma_mon: 'NK1', diem: 0, ghi_chu: '', session_id: this.selectedSession };
                this.showEditModal = true;
            },
            exportData() {
                const search = $('#aptitudeTable').DataTable().search();
                window.location.href = '<?= url("/admin/aptitude-scores/export") ?>?search=' + encodeURIComponent(search) + '&session_id=' + this.selectedSession;
            },
            editScore(row) {
                this.editMode = true;
                this.formData = { ...row };
                this.showEditModal = true;
            },
            saveScore() {
                $.post('<?= url('/admin/aptitude-scores/api-save') ?>', { ...this.formData, session_id: this.selectedSession, _csrf_token: '<?= csrf_token() ?>' }, (res) => {
                    if (res.success) {
                        this.showEditModal = false;
                        $('#aptitudeTable').DataTable().ajax.reload(null, false);
                    } else {
                        alert(res.message || 'Lỗi khi lưu dữ liệu!');
                    }
                }, 'json').fail(() => alert('Lỗi kết nối máy chủ!'));
            },
            deleteSelected() {
                const ids = window.getSelectedIds();
                if (ids.length === 0) return;
                
                if (confirm(`Bạn có chắc chắn muốn xóa ${ids.length} bản ghi điểm năng khiếu đã chọn?`)) {
                    $.post('<?= url('/admin/aptitude-scores/delete') ?>', { 
                        ids: ids, 
                        session_id: this.selectedSession,
                        _csrf_token: '<?= csrf_token() ?>' 
                    }, (res) => {
                        if (res.success) {
                            $('#aptitudeTable').DataTable().ajax.reload(null, false);
                        } else {
                            alert('Lỗi khi xóa hàng loạt!');
                        }
                    }, 'json');
                }
            },
            deleteAllScores() {
                if (confirm('CẢNH BÁO: Hành động này sẽ xóa TOÀN BỘ dữ liệu điểm năng khiếu của đợt này. Bạn có chắc chắn muốn thực hiện?')) {
                    $.post('<?= url('/admin/aptitude-scores/delete') ?>', { 
                        delete_all: true, 
                        session_id: this.selectedSession,
                        _csrf_token: '<?= csrf_token() ?>' 
                    }, (res) => {
                        if (res.success) {
                            $('#aptitudeTable').DataTable().ajax.reload();
                        } else {
                            alert('Lỗi khi xóa tất cả!');
                        }
                    }, 'json');
                }
            }
        }
    }

    $(document).ready(function() {
        const table = $('#aptitudeTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: { 
                url: '<?= url('/admin/aptitude-scores/api-list') ?>', 
                type: 'POST',
                data: function(d) {
                    d._csrf_token = '<?= csrf_token() ?>';
                    d.session_id = '<?= $activeSession ? $activeSession['id'] : "" ?>';
                }
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    width: '40px',
                    className: 'text-center',
                    render: (data, type, row) => `<input type="checkbox" class="row-select border-slate-300 rounded text-indigo-600 focus:ring-indigo-500" value="${row.id}">`
                },
                { data: 'id', width: '60px', className: 'text-slate-500 font-mono text-xs' },
                { data: 'so_cccd', className: 'font-medium font-mono text-indigo-600' },
                { data: 'sbd' },
                { data: 'ho_va_ten', defaultContent: '<span class="text-slate-400 italic">Chưa đăng ký HS</span>' },
                { 
                    data: 'ma_mon',
                    render: (data, type, row) => row.ten_mon ? `${data}: ${row.ten_mon}` : data
                },
                { 
                    data: 'diem',
                    render: (data) => `<span class="px-2 py-1 bg-sky-100 text-sky-700 rounded-md font-bold">${data}</span>`
                },
                { data: 'ghi_chu' },
                {
                    data: null,
                    orderable: false,
                    className: 'text-right',
                    render: (data, type, row) => `
                        <div class="flex justify-end gap-1">
                            <button onclick='window.aptitudeDataInstance.editScore(${JSON.stringify(row)})' class="p-1 px-2 text-indigo-600 hover:bg-indigo-50 rounded transition-colors">
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
                $('.dataTables_filter input').addClass('w-64 pl-4 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all').attr('placeholder', 'Tìm CCCD, SBD...');
                $('.dataTables_length select').addClass('border border-slate-300 rounded-md text-sm py-1 pl-2 pr-8 bg-white outline-none focus:ring-2 focus:ring-indigo-500');
            }
        });
        
        // Select all / Deselect all
        $('#selectAll').on('change', function() {
            const checked = this.checked;
            $('.row-select').prop('checked', checked);
            updateDeleteSelectedButton();
        });

        // Individual row checkbox change
        $('#aptitudeTable tbody').on('change', '.row-select', function() {
            const total = $('.row-select').length;
            const checked = $('.row-select:checked').length;
            $('#selectAll').prop('checked', total === checked && total > 0);
            updateDeleteSelectedButton();
        });

        // Uncheck all when changing pages/searching
        table.on('draw', function() {
            $('#selectAll').prop('checked', false);
            updateDeleteSelectedButton();
        });

        function updateDeleteSelectedButton() {
            const selectedIds = getSelectedIds();
            const count = selectedIds.length;
            if (count > 0) {
                $('#selectedCount').text(count);
                $('#btnDeleteSelected').removeClass('hidden');
            } else {
                $('#btnDeleteSelected').addClass('hidden');
            }
        }

        function getSelectedIds() {
            const ids = [];
            $('.row-select:checked').each(function() {
                ids.push($(this).val());
            });
            return ids;
        }

        window.getSelectedIds = getSelectedIds;

        // Expose Alpine instance to outside (Alpine v3)
        window.aptitudeDataInstance = Alpine.$data(document.querySelector('[x-data]'));
    });

    function deleteScore(id) {
        if (confirm('Bạn có chắc chắn muốn xóa bản ghi điểm này?')) {
            $.post('<?= url('/admin/aptitude-scores/delete') ?>', { id: id, _csrf_token: '<?= csrf_token() ?>' }, (res) => {
                if (res.success) $('#aptitudeTable').DataTable().ajax.reload(null, false);
                else alert('Lỗi khi xóa!');
            }, 'json');
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.shimmer-glare {
    background: linear-gradient(
        to right,
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 0.4) 50%,
        rgba(255, 255, 255, 0) 100%
    );
    animation: loading-shimmer 2s infinite linear;
}

@keyframes loading-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

@keyframes pulsing-slow {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.05); }
}

.animate-pulsing-slow {
    animation: pulsing-slow 3s infinite ease-in-out;
}

@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-spin-slow {
    animation: spin-slow 6s infinite linear;
}

@keyframes indeterminate {
    0% { left: -50%; }
    100% { left: 100%; }
}
.animate-indeterminate {
    animation: indeterminate 1.5s infinite linear;
}

[x-cloak] { display: none !important; }
</style>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>
