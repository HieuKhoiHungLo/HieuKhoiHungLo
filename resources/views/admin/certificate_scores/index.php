<?php
$title = 'Điểm Chứng chỉ';
ob_start();
?>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Spreadsheet style for Certificate Scores Table matching review-management */
    #certTable {
        border-collapse: separate !important;
        border-spacing: 0;
        width: 100% !important;
        table-layout: fixed;
    }
    #certTable th, #certTable td {
        padding: 0.4rem 0.5rem !important;
        border: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        border-right: 1px solid #e2e8f0 !important;
        vertical-align: middle !important;
        font-size: 13px !important;
        background-clip: padding-box;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #certTable td {
        font-weight: 400 !important;
        text-transform: none !important;
        color: #000000 !important;
    }
    #certTable th:first-child, #certTable td:first-child {
        border-left: 1px solid #e2e8f0 !important;
    }
    #certTable thead tr:first-child th {
        border-top: 1px solid #e2e8f0 !important;
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    #certTable tbody tr:hover td {
        background-color: #f1f5f9 !important;
    }
</style>

<div class="p-4 h-full flex flex-col" x-data="certificateData()">
    <!-- Header Page Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-3">
        <div class="flex items-center gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-6 bg-indigo-600 rounded-full"></span>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Điểm Chứng chỉ</h1>
                </div>
                <div class="flex items-center gap-2 mt-1 ml-4">
                    <p class="text-slate-500 text-sm font-medium">Tổng cộng: <span class="text-indigo-600 font-bold"><?= number_format($stats['total'] ?? 0) ?></span> bản ghi điểm quy đổi</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2 ml-4">
                <!-- Filter by Year -->
                <select id="yearFilter" class="border-slate-300 rounded-lg text-sm bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-2 min-w-[100px]" x-model="selectedYear" @change="selectedSession = ''; sessionChanged()">
                    <option value="">-- Năm --</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= $year ?>"><?= $year ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Filter by Session -->
                <select id="sessionFilter" class="border-slate-300 rounded-lg text-sm bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-2 min-w-[180px]" x-model="selectedSession" @change="sessionChanged()">
                    <option value="">-- Chọn đợt xét tuyển --</option>
                    <template x-for="session in filteredSessions" :key="session.id">
                        <option :value="session.id" x-text="session.ten_dot || session.ten_dot_xet_tuyen" :selected="session.id == selectedSession"></option>
                    </template>
                </select>
            </div>
        </div>
        <div class="flex flex-wrap gap-4 w-full md:w-auto">
            <!-- Nút xóa hàng loạt -->
            <button id="btnDeleteSelected" @click="deleteSelected()" 
                class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-300 border border-rose-200 flex items-center gap-2 shadow-sm hover:shadow-rose-100/50 hover:scale-[1.02] active:scale-[0.98] hidden">
                <i class="fas fa-minus-circle text-rose-500"></i>
                <span>Xóa mục chọn (<span id="selectedCount">0</span>)</span>
            </button>
            
            <button @click="deleteAllScores()" 
                class="bg-red-50 hover:bg-red-100 text-red-600 px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-300 border border-red-200 flex items-center gap-2 shadow-sm hover:shadow-red-100/50 hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-trash-alt"></i>
                <span>Xóa tất cả</span>
            </button>

            <button @click="openAddModal()" 
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-300 shadow-lg shadow-indigo-100 flex items-center gap-2 hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-plus"></i>
                <span>Thêm điểm</span>
            </button>
            
            <button @click="openImportModal = true" 
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-300 shadow-lg shadow-emerald-100 flex items-center gap-2 hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-file-excel"></i>
                <span>Import Excel</span>
            </button>
            
            <button @click="exportData()" 
                class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-300 border border-indigo-200 flex items-center gap-2 shadow-sm hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-download"></i>
                <span>Xuất dữ liệu</span>
            </button>
            
            <a href="<?= url('/admin/certificate-scores/template') ?>" 
                class="bg-slate-50 hover:bg-slate-100 text-slate-700 px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-300 border border-slate-200 flex items-center gap-2 shadow-sm hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-file-csv"></i>
                <span>Mẫu Import</span>
            </a>
        </div>
    </div>

    <!-- Alert Messages (PHP Session Alerts) -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-2xl flex items-start shadow-sm animate-fade-in">
            <i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-3 text-lg"></i>
            <div>
                <h3 class="text-emerald-800 font-bold text-sm">Thành công!</h3>
                <p class="text-emerald-700 text-sm mt-1"><?= htmlspecialchars($_SESSION['flash_success']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-2xl flex items-start shadow-sm animate-fade-in">
            <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3 text-lg"></i>
            <div>
                <h3 class="text-red-800 font-bold text-sm">Lỗi!</h3>
                <p class="text-red-700 text-sm mt-1"><?= htmlspecialchars($_SESSION['flash_error']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Data Table Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex-1 flex flex-col overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h2 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-certificate text-indigo-500"></i>
                Danh sách điểm chứng chỉ quy đổi
            </h2>
        </div>
        <div class="flex-1 min-h-0 p-4 relative overflow-auto custom-scrollbar">
            <table id="certTable" class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 text-xs font-bold uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-4 rounded-tl-xl text-center w-[40px]">
                            <input type="checkbox" id="selectAll" class="border-slate-300 rounded text-[#0066FF] focus:ring-indigo-500 cursor-pointer">
                        </th>
                        <th class="py-3 px-4">Họ và tên</th>
                        <th class="py-3 px-4 w-[150px]">Số CCCD</th>
                        <th class="py-3 px-4 w-[100px] text-center">Mã Môn</th>
                        <th class="py-3 px-4 w-[120px] text-center">Điểm Quy đổi</th>
                        <th class="py-3 px-4">Ghi chú</th>
                        <th class="py-3 px-4 w-[120px] rounded-tr-xl text-center">Thao tác</th>
                    </tr>
                    <tr class="bg-gray-50/50">
                        <th class="bg-white text-center"></th>
                        <th class="bg-white px-2 py-1">
                            <input type="text" id="search_name" placeholder="Tên..." 
                                class="w-full px-2 py-1 text-[11px] border border-slate-200 rounded outline-none focus:border-blue-400 font-medium">
                        </th>
                        <th class="bg-white px-2 py-1">
                            <input type="text" id="search_cccd" placeholder="CCCD..." 
                                class="w-full px-2 py-1 text-[11px] border border-slate-200 rounded outline-none focus:border-blue-400 font-medium">
                        </th>
                        <th class="bg-white px-2 py-1">
                            <input type="text" id="search_ma_mon" placeholder="Mã môn..." 
                                class="w-full px-2 py-1 text-[11px] border border-slate-200 rounded outline-none focus:border-blue-400 font-medium text-center">
                        </th>
                        <th class="bg-white px-2 py-1"></th>
                        <th class="bg-white px-2 py-1">
                            <input type="text" id="search_ghi_chu" placeholder="Tìm ghi chú..." 
                                class="w-full px-2 py-1 text-[11px] border border-slate-200 rounded outline-none focus:border-blue-400 font-medium">
                        </th>
                        <th class="bg-white text-center"></th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 text-sm"></tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Add/Edit Score -->
    <div x-show="showEditModal" class="fixed z-50 inset-0 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" @click="showEditModal = false">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-white font-extrabold text-base tracking-tight" x-text="editMode ? 'Chỉnh sửa điểm chứng chỉ' : 'Thêm điểm chứng chỉ mới'"></h3>
                    <button type="button" @click="showEditModal = false" class="text-white/80 hover:text-white text-xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="saveScore" class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Số CCCD/CMND <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="formData.so_cccd" placeholder="Nhập 12 số CCCD..." required
                                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Mã môn xét tuyển <span class="text-rose-500">*</span></label>
                            <select x-model="formData.ma_mon" required
                                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold text-slate-700">
                                <option value="N1">N1 - Tiếng Anh</option>
                                <option value="N2">N2 - Tiếng Nga</option>
                                <option value="N3">N3 - Tiếng Pháp</option>
                                <option value="N4">N4 - Tiếng Trung Quốc</option>
                                <option value="N5">N5 - Tiếng Đức</option>
                                <option value="N6">N6 - Tiếng Nhật</option>
                                <option value="N7">N7 - Tiếng Hàn Quốc</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Điểm quy đổi (0.00 - 10.00) <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" min="0" max="10" x-model="formData.diem" required
                                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-bold text-indigo-600">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Ghi chú chứng chỉ</label>
                            <textarea x-model="formData.ghi_chu" rows="2" placeholder="Ví dụ: IELTS 6.5, HSK 4..."
                                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="showEditModal = false" 
                            class="px-4.5 py-2 text-sm font-bold text-slate-500 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Hủy</button>
                        <button type="submit" 
                            class="px-6 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md shadow-indigo-100 transition">Lưu dữ liệu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Import Excel with Premium Drag & Drop & Progress bar -->
    <div x-show="openImportModal" class="fixed z-50 inset-0 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" @click="if(!isLoading) openImportModal = false">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-white font-extrabold text-base tracking-tight"><i class="fas fa-file-excel mr-2"></i>Import Điểm Chứng chỉ quy đổi</h3>
                    <button type="button" @click="if(!isLoading) openImportModal = false" class="text-white/80 hover:text-white text-xl font-bold">&times;</button>
                </div>
                
                <form @submit.prevent="uploadExcel($event)" action="<?= url('/admin/certificate-scores/import') ?>" class="p-6">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="session_id" :value="selectedSession">
                    
                    <div class="space-y-4" x-show="!isLoading">
                        <div class="text-sm text-slate-500 space-y-1.5 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <p class="font-bold text-slate-700"><i class="fas fa-info-circle text-indigo-500 mr-1.5"></i>Yêu cầu file nạp:</p>
                            <p class="text-xs">File Excel (.xlsx, .xls, .csv) có các cột lần lượt là:</p>
                            <ol class="list-decimal ml-4 text-xs font-mono font-bold text-slate-600">
                                <li>Số CCCD/CMND (Bắt buộc)</li>
                                <li>Mã môn quy đổi (Vd: N1, N2...)</li>
                                <li>Điểm quy đổi (Từ 0.0 đến 10.0)</li>
                                <li>Ghi chú (Tên chứng chỉ, chi tiết quy đổi...)</li>
                            </ol>
                        </div>
                        
                        <!-- Drag & Drop Zone -->
                        <div class="border-2 border-dashed border-slate-200 hover:border-emerald-400 rounded-2xl p-6 transition-all duration-300 bg-slate-50/50 hover:bg-emerald-50/10 text-center relative group cursor-pointer"
                             @dragover.prevent="dragOver = true"
                             @dragleave.prevent="dragOver = false"
                             @drop.prevent="handleDrop($event)"
                             :class="dragOver ? 'border-emerald-500 bg-emerald-50/30 ring-4 ring-emerald-50' : ''">
                            
                            <input type="file" id="file_input" name="csv_file" class="hidden" accept=".xlsx, .xls, .csv" required @change="handleFileSelected">
                            <label for="file_input" class="cursor-pointer block">
                                <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center mx-auto text-emerald-500 mb-3 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-cloud-upload-alt text-xl"></i>
                                </div>
                                <p class="text-sm font-semibold text-slate-700 mb-0.5" x-text="fileName ? 'Tệp đã chọn: ' + fileName : 'Kéo thả tệp hoặc bấm để chọn'"></p>
                                <p class="text-xs text-slate-400" x-text="fileSize ? fileSize : 'Hỗ trợ .xlsx, .xls, .csv'"></p>
                            </label>
                        </div>
                    </div>

                    <!-- Progress Loading view inside modal during active upload -->
                    <div class="py-6 space-y-4" x-show="isLoading" x-cloak>
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 mb-3 animate-bounce">
                                <i class="fas fa-tasks text-lg"></i>
                            </div>
                            <h4 class="text-base font-bold text-slate-800">Hệ thống đang xử lý dữ liệu</h4>
                            <p class="text-xs text-slate-500 mt-1" x-text="currentLoadingMessage"></p>
                        </div>

                        <!-- Progress Bar -->
                        <div class="relative h-2.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-300 shadow-md shadow-emerald-200" 
                                 :style="`width: ${progress}%`">
                            </div>
                        </div>
                        <div class="flex justify-between text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                            <span x-text="progress + '%'"></span>
                            <span x-text="progress < 100 ? 'Vui lòng giữ kết nối...' : 'Hoàn thành!'"></span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100" x-show="!isLoading">
                        <button type="button" @click="openImportModal = false" 
                            class="px-4.5 py-2 text-sm font-medium text-slate-500 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Hủy</button>
                        <button type="submit" 
                            class="px-6 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-100 transition">
                            Bắt đầu Import
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
            selectedYear: '<?= $activeSession ? $activeSession['nam_tuyen_sinh'] : "" ?>',
            selectedSession: '<?= $activeSession ? $activeSession['id'] : "" ?>',
            allSessions: <?= json_encode($sessions) ?>,
            formData: {
                id: null,
                so_cccd: '',
                ma_mon: 'N1',
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
                    window.location.href = '<?= url("/admin/certificate-scores") ?>?session_id=' + this.selectedSession;
                } else {
                    window.location.href = '<?= url("/admin/certificate-scores") ?>';
                }
            },
            
            // Drag and Drop State
            dragOver: false,
            fileName: '',
            fileSize: '',

            // Loading & Progress State
            isLoading: false,
            progress: 0,
            currentLoadingMessage: '',

            handleFileSelected(e) {
                const file = e.target.files[0];
                if (file) {
                    this.fileName = file.name;
                    this.fileSize = (file.size / 1024).toFixed(1) + ' KB';
                }
            },

            handleDrop(e) {
                this.dragOver = false;
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const fileInput = document.getElementById('file_input');
                    fileInput.files = files;
                    this.fileName = files[0].name;
                    this.fileSize = (files[0].size / 1024).toFixed(1) + ' KB';
                }
            },

            openAddModal() {
                this.editMode = false;
                this.formData = { id: null, so_cccd: '', ma_mon: 'N1', diem: 0, ghi_chu: '', session_id: this.selectedSession };
                this.showEditModal = true;
            },

            exportData() {
                const name = $('#search_name').val() || '';
                const cccd = $('#search_cccd').val() || '';
                const maMon = $('#search_ma_mon').val() || '';
                const ghiChu = $('#search_ghi_chu').val() || '';
                window.location.href = '<?= url("/admin/certificate-scores/export") ?>?f_name=' + encodeURIComponent(name) + '&f_cccd=' + encodeURIComponent(cccd) + '&f_ma_mon=' + encodeURIComponent(maMon) + '&f_ghi_chu=' + encodeURIComponent(ghiChu) + '&session_id=' + this.selectedSession;
            },

            editScore(row) {
                this.editMode = true;
                this.formData = { ...row };
                this.showEditModal = true;
            },

            saveScore() {
                $.post('<?= url('/admin/certificate-scores/api-save') ?>', { ...this.formData, session_id: this.selectedSession, _csrf_token: '<?= csrf_token() ?>' }, (res) => {
                    if (res.success) {
                        this.showEditModal = false;
                        $('#certTable').DataTable().ajax.reload(null, false);
                    } else {
                        alert(res.message || 'Lỗi khi lưu dữ liệu!');
                    }
                }, 'json').fail(() => alert('Lỗi kết nối máy chủ!'));
            },

            async uploadExcel(event) {
                const form = event.target;
                const fileInput = document.getElementById('file_input');
                if (!fileInput.files || fileInput.files.length === 0) {
                    alert('Vui lòng chọn file Excel.');
                    return;
                }

                const importToken = 'imp_cc_score_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                const formData = new FormData(form);
                formData.append('import_token', importToken);
                formData.append('csv_file', fileInput.files[0]);

                this.isLoading = true;
                this.progress = 0;
                this.currentLoadingMessage = 'Đang tải file Excel lên máy chủ...';

                try {
                    let isPolling = true;
                    const pollProgress = async () => {
                        if (!isPolling) return;
                        try {
                            const res = await fetch('<?= url("/admin/import/progress") ?>?token=' + importToken + '&t=' + Date.now());
                            if (res.ok) {
                                const data = await res.json();
                                if (data.percent !== undefined) {
                                    const currentPercent = parseInt(data.percent);
                                    // Scale percent up to 95% before response finishes
                                    const scaledPercent = Math.round(currentPercent * 0.95);
                                    if (scaledPercent > this.progress || currentPercent === 0) {
                                        this.progress = scaledPercent;
                                        if (data.message) this.currentLoadingMessage = data.message;
                                    }
                                }
                            }
                        } catch (err) {
                            console.error('Progress polling error:', err);
                        }
                        if (isPolling) {
                            setTimeout(pollProgress, 600);
                        }
                    };

                    setTimeout(pollProgress, 400);

                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    isPolling = false;

                    if (!response.ok) {
                        throw new Error('Lỗi máy chủ (HTTP ' + response.status + ')');
                    }

                    const result = await response.json();
                    
                    this.progress = 100;
                    this.currentLoadingMessage = 'Hoàn tất xử lý!';
                    
                    setTimeout(() => {
                        this.isLoading = false;
                        this.openImportModal = false;
                        
                        // Clear file state
                        this.fileName = '';
                        this.fileSize = '';
                        fileInput.value = '';

                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Nhập dữ liệu thành công!',
                                text: result.message || 'Hồ sơ điểm chứng chỉ đã được cập nhật.',
                                confirmButtonColor: '#10B981'
                            });
                            $('#certTable').DataTable().ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Thất bại',
                                text: result.message || 'Lỗi không xác định khi nạp dữ liệu.',
                                confirmButtonColor: '#EF4444'
                            });
                        }
                    }, 500);

                } catch (e) {
                    isPolling = false;
                    this.isLoading = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Đã xảy ra lỗi',
                        text: e.message || 'Lỗi không kết nối được máy chủ.',
                        confirmButtonColor: '#EF4444'
                    });
                }
            },

            deleteSelected() {
                const ids = window.getSelectedIds();
                if (ids.length === 0) return;
                
                if (confirm(`Bạn có chắc chắn muốn xóa ${ids.length} bản ghi điểm quy đổi đã chọn?`)) {
                    $.post('<?= url('/admin/certificate-scores/delete') ?>', { 
                        ids: ids, 
                        session_id: this.selectedSession,
                        _csrf_token: '<?= csrf_token() ?>' 
                    }, (res) => {
                        if (res.success) {
                            $('#certTable').DataTable().ajax.reload(null, false);
                        } else {
                            alert('Lỗi khi xóa hàng loạt!');
                        }
                    }, 'json');
                }
            },

            deleteAllScores() {
                if (confirm('CẢNH BÁO: Hành động này sẽ xóa TOÀN BỘ dữ liệu điểm chứng chỉ quy đổi của đợt này. Bạn có chắc chắn muốn thực hiện?')) {
                    $.post('<?= url('/admin/certificate-scores/delete') ?>', { 
                        delete_all: true, 
                        session_id: this.selectedSession,
                        _csrf_token: '<?= csrf_token() ?>' 
                    }, (res) => {
                        if (res.success) {
                            $('#certTable').DataTable().ajax.reload();
                        } else {
                            alert('Lỗi khi xóa tất cả!');
                        }
                    }, 'json');
                }
            }
        }
    }

    $(document).ready(function() {
        const table = $('#certTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            ajax: { 
                url: '<?= url('/admin/certificate-scores/api-list') ?>', 
                type: 'POST',
                data: function(d) {
                    d._csrf_token = '<?= csrf_token() ?>';
                    d.session_id = '<?= $activeSession ? $activeSession['id'] : "" ?>';
                    d.f_name = $('#search_name').val();
                    d.f_cccd = $('#search_cccd').val();
                    d.f_ma_mon = $('#search_ma_mon').val();
                    d.f_ghi_chu = $('#search_ghi_chu').val();
                }
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    width: '40px',
                    className: 'text-center align-middle',
                    render: (data, type, row) => `<input type="checkbox" class="row-select border-slate-300 rounded text-[#0066FF] focus:ring-indigo-500 cursor-pointer" value="${row.id}">`
                },
                { 
                    data: 'ho_va_ten', 
                    defaultContent: '<span class="text-slate-400 italic">Chưa đăng ký HS</span>',
                    className: 'align-middle'
                },
                { 
                    data: 'so_cccd', 
                    className: 'align-middle font-mono w-[150px]'
                },
                { 
                    data: 'ma_mon', 
                    className: 'align-middle font-bold text-slate-700 text-center w-[100px]',
                    render: (data) => `<span class="px-2.5 py-0.5 bg-slate-100 text-slate-700 rounded text-xs font-bold border border-slate-200">${data}</span>`
                },
                { 
                    data: 'diem',
                    className: 'align-middle text-center w-[120px]',
                    render: (data) => `<span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg font-bold border border-emerald-100 shadow-sm">${data}</span>`
                },
                { data: 'ghi_chu', className: 'align-middle text-slate-600 whitespace-normal font-medium' },
                {
                    data: null,
                    orderable: false,
                    width: '120px',
                    className: 'text-center align-middle',
                    render: (data, type, row) => `
                        <div class="flex items-center justify-center gap-1.5">
                            <button onclick='window.certDataInstance.editScore(${JSON.stringify(row)})' 
                                class="px-2 py-0.5 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded font-extrabold text-[10px] uppercase border border-blue-200 transition-all">
                                SỬA
                            </button>
                            <button onclick="deleteScore(${row.id})" 
                                class="w-6 h-6 flex items-center justify-center text-rose-500 hover:bg-rose-50 rounded-lg transition-all" title="Xóa">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        </div>
                    `
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json' },
            dom: '<"flex justify-between items-center bg-white p-4 border-b border-slate-200"<"flex items-center gap-2"l>>rt<"flex justify-between items-center p-4 bg-slate-50/50 border-t border-slate-200"ip>',
            initComplete: function() {
                $('.dataTables_length select').addClass('border border-slate-200 rounded-xl text-sm py-1.5 pl-3 pr-8 bg-white outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer');
            }
        });

        // Custom column-based search events
        $('#search_name, #search_cccd, #search_ma_mon, #search_ghi_chu').on('keyup change clear', function() {
            table.draw();
        });
        
        // Select all / Deselect all
        $('#selectAll').on('change', function() {
            const checked = this.checked;
            $('.row-select').prop('checked', checked);
            updateDeleteSelectedButton();
        });

        // Individual row checkbox change
        $('#certTable tbody').on('change', '.row-select', function() {
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
