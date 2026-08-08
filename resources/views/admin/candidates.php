<?php ob_start(); ?>

<?php include __DIR__ . '/partials/_stats.php'; ?>

<!-- Main Content Area with AlpineJS context -->
<!-- Custom Table Styles -->
<style>
    .candidate-table-container {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        background: #fff;
    }
    .candidate-table {
        border-collapse: separate !important;
        border-spacing: 0;
        width: 100%;
        table-layout: fixed; /* Crucial for sticky alignment reliability */
    }
    .candidate-table th, .candidate-table td {
        padding: 0.4rem 0.5rem;
        border: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        border-right: 1px solid #e2e8f0 !important;
        vertical-align: middle;
        font-size: 13px;
        color: #334155;
        background-clip: padding-box;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .candidate-table th:first-child, .candidate-table td:first-child {
        border-left: 1px solid #e2e8f0 !important;
    }
    .candidate-table thead tr:first-child th {
        border-top: 1px solid #e2e8f0 !important;
    }

    /* Header Specifics */
    .candidate-table thead th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        z-index: 5;
    }
    .candidate-table thead tr:first-child th {
        top: 0;
        position: sticky;
        z-index: 30 !important;
    }

    /* Dynamic Sticky Columns Calculation */
    <?php
    $offset0 = 0;
    $width0 = 45; // Checkbox
    
    // Cột Thao tác hiện lại cho trang xét duyệt (review)
    $widthAction = ($mode === 'review') ? 130 : 0;
    $offsetAction = $offset0 + $width0;

    $offsetTrangThai = $offsetAction + $widthAction; // Trạng thái
    $widthTrangThai = 45;

    $offset3 = $offsetTrangThai + $widthTrangThai; // Họ tên
    $width3 = 240;
    ?>

    .sticky-col {
        position: sticky !important;
        background-color: #fff !important;
        z-index: 10;
    }
    thead th.sticky-col {
        z-index: 40 !important;
        background-color: #f8fafc !important;
    }

    .sticky-col-left-0 { left: <?= $offset0 ?>px; width: <?= $width0 ?>px; min-width: <?= $width0 ?>px; max-width: <?= $width0 ?>px; }
    .sticky-col-action { left: <?= $offsetAction ?>px; width: <?= $widthAction ?>px; min-width: <?= $widthAction ?>px; max-width: <?= $widthAction ?>px; }
    .sticky-col-trangthai { left: <?= $offsetTrangThai ?>px; width: <?= $widthTrangThai ?>px; min-width: <?= $widthTrangThai ?>px; max-width: <?= $widthTrangThai ?>px; }
    .sticky-col-left-3 { 
        left: <?= $offset3 ?>px; 
        width: <?= $width3 ?>px; 
        min-width: <?= $width3 ?>px; 
        max-width: <?= $width3 ?>px; 
        box-shadow: 2px 0 5px -2px rgba(0,0,0,0.1); 
        clip-path: inset(-20px -20px -20px 0px); /* Allow shadow on right, but hide on top/left/bottom */
    }

    /* Hover State */
    .candidate-table tbody tr:hover td {
        background-color: #f1f5f9 !important;
    }
    
    /* Sort icon styling */
    .sort-trigger {
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: inherit;
    }
    .sort-link {
        color: #94a3b8;
        transition: color 0.15s;
        font-size: 10px;
    }
    .sort-link.active {
        color: #0066FF;
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

    [x-cloak] { display: none !important; }
</style>

<div class="pb-24" x-data="{ 
    showCols: (function() {
        const defaults = { 
            cccd: true, phone: true, email: true, province: false, school: false, nv1: true,
            gender: false, dob: false, ethnicity: false, area: false, object: false, grad_year: false, transcript_status: true, reviewer_name: true,
            graduation_score: true, tb_chung_12: false, hoc_luc_12: false, hanh_kiem_12: false
        };
        const stored = JSON.parse(localStorage.getItem('admin_cols')) || {};
        const cols = { ...defaults, ...stored };
        // Enforce fixed columns
        cols.cccd = true;
        cols.ho_va_ten = true;
        cols.dob = true;
        cols.phone = true;
        cols.nv1 = true;
        return cols;
    })(),
    fixedCols: ['cccd', 'ho_va_ten', 'dob', 'phone', 'nv1'],
    toggleCol(col) {
        if (this.fixedCols.includes(col)) return;
        this.showCols[col] = !this.showCols[col];
        localStorage.setItem('admin_cols', JSON.stringify(this.showCols));
    },
    colLabel(col) {
        const labels = { 
            cccd: 'Số CCCD',
            ho_va_ten: 'Họ tên',
            phone: 'Điện thoại',
            email: 'Email',
            province: 'Hộ khẩu', 
            school: 'Trường THPT', 
            nv1: 'NV1',
            gender: 'Giới tính',
            dob: 'Ngày sinh',
            ethnicity: 'Dân tộc',
            area: 'Khu vực ƯT',
            object: 'Đối tượng ƯT',
            grad_year: 'Năm TN',
            transcript_status: 'Học bạ',
            reviewer_name: 'Người duyệt',
            graduation_score: 'Điểm tốt nghiệp',
            tb_chung_12: 'TB chung L12',
            hoc_luc_12: 'Học lực L12',
            hanh_kiem_12: 'Hạnh kiểm L12'
        };
        return labels[col] || col;
    }
}">

    <?php include __DIR__ . '/partials/_filters.php'; ?>

    <?php include __DIR__ . '/partials/_candidates_table.php'; ?>
</div>

<?php if (isset($mode) && $mode === 'review'): ?>
<!-- Modal: Duyệt hồ sơ theo file -->
<div id="modal-bulk-approve" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-bold text-lg"><i class="fas fa-file-check mr-2"></i>Duyệt hồ sơ theo file</h3>
            <button onclick="document.getElementById('modal-bulk-approve').classList.add('hidden')" class="text-white/80 hover:text-white text-xl">&times;</button>
        </div>
        <form action="<?= url('/admin/review/bulk-approve-file') ?>" method="POST" enctype="multipart/form-data" class="p-6">
            <input type="hidden" id="bulk_approve_csrf" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="session_id" value="<?= $filters['session_id'] ?? '' ?>">
            
            <div class="mb-5">
                <div class="flex justify-between items-center mb-3">
                    <p class="text-sm text-slate-600">Upload file Excel (.xlsx/.xls) chứa danh sách CCCD cần duyệt.</p>
                    <a href="<?= url('/admin/review/download-approve-template') ?>" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-100 transition">
                        <i class="fas fa-download mr-1"></i> Tải file mẫu
                    </a>
                </div>
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Cấu trúc file yêu cầu:</p>
                    <table class="w-full text-xs">
                        <thead><tr class="text-left text-slate-500 border-b border-slate-200">
                            <th class="py-1 px-2">Cột A</th><th class="py-1 px-2">Cột B</th><th class="py-1 px-2">Cột C</th>
                        </tr></thead>
                        <tbody class="text-slate-700">
                            <tr class="border-b border-slate-100"><td class="py-1 px-2 text-slate-400">STT</td><td class="py-1 px-2 font-semibold">CCCD</td><td class="py-1 px-2">Ghi chú</td></tr>
                            <tr class="border-b border-slate-100"><td class="py-1 px-2 text-slate-400">1</td><td class="py-1 px-2 font-mono">001234567890</td><td class="py-1 px-2">Đã xác minh</td></tr>
                            <tr><td class="py-1 px-2 text-slate-400">2</td><td class="py-1 px-2 font-mono">009876543210</td><td class="py-1 px-2 text-slate-400 italic">Để trống = giữ ghi chú cũ</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Chọn file:</label>
                <input type="file" id="approve_file_input" name="approve_file" accept=".xlsx,.xls,.csv" required
                    class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer border border-slate-200 rounded-xl">
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-bulk-approve').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Hủy</button>
                <button type="submit" class="px-6 py-2 text-sm font-bold text-white bg-gradient-to-r from-emerald-500 to-teal-500 rounded-xl hover:from-emerald-600 hover:to-teal-600 shadow-lg shadow-emerald-200 transition">
                    <i class="fas fa-check-double mr-1"></i> Duyệt tất cả
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Cập nhật điểm học bạ theo file -->
<div x-data="bulkTranscriptApp()">
    <div id="modal-bulk-transcript" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-500 px-6 py-4 flex justify-between items-center">
                <h3 class="text-white font-bold text-lg"><i class="fas fa-file-excel mr-2"></i>Cập nhật điểm học bạ</h3>
                <button type="button" onclick="document.getElementById('modal-bulk-transcript').classList.add('hidden')" class="text-white/80 hover:text-white text-xl">&times;</button>
            </div>
            <form action="<?= url('/admin/review/bulk-update-transcript') ?>" method="POST" enctype="multipart/form-data" class="p-6" @submit.prevent="upload($event)">
                <input type="hidden" id="bulk_transcript_csrf" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                
                <div class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-blue-50/50 border border-blue-100/80 rounded-2xl p-4">
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-slate-700 mb-1">Cập nhật điểm học bạ hàng loạt</p>
                        <p class="text-xs text-slate-500 leading-relaxed">Vui lòng tải về file mẫu chuẩn MoET Bảng 9, điền thông tin điểm số học bạ và tải lên hệ thống.</p>
                    </div>
                    <a href="<?= url('/admin/review/download-transcript-template') ?>" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 shadow-lg shadow-blue-100 transition whitespace-nowrap">
                        <i class="fas fa-download"></i> Tải file mẫu Bảng 9
                    </a>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Chọn file:</label>
                    <input type="file" id="transcript_file_input" name="transcript_file" accept=".xlsx,.xls,.csv" required
                        class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-200 rounded-xl">
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-bulk-transcript').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Hủy</button>
                    <button type="submit" class="px-6 py-2 text-sm font-bold text-white bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl hover:from-blue-600 hover:to-indigo-600 shadow-lg shadow-blue-200 transition">
                        <i class="fas fa-upload mr-1"></i> Cập nhật điểm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Premium Loading Modal -->
    <div x-cloak x-show="isLoading" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/40 backdrop-blur-md z-[100] flex items-center justify-center p-4">
        
        <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 w-full max-w-md p-8 text-center relative overflow-hidden">
            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl"></div>

            <div class="relative w-28 h-28 mx-auto mb-6">
                <div class="absolute inset-0 bg-blue-500/20 rounded-full animate-pulsing-slow"></div>
                <div class="absolute inset-1 border-2 border-blue-200 border-dashed rounded-full animate-spin-slow"></div>
                <div class="absolute inset-4 bg-white rounded-full flex items-center justify-center shadow-xl border border-white/50 overflow-hidden">
                    <img src="<?= url('/assets/img/Logo.png') ?>" 
                         alt="Logo" 
                         class="w-full h-full object-contain p-2 relative z-10">
                    <div class="shimmer-glare absolute inset-0 z-20 opacity-30"></div>
                </div>
            </div>

            <h3 class="text-xl font-bold text-slate-800 mb-2">Hệ thống đang xử lý</h3>
            <p class="text-slate-500 text-sm mb-6 px-4" x-text="currentLoadingMessage"></p>
            
            <div class="relative h-2 bg-slate-100 rounded-full overflow-hidden mb-2">
                <div class="absolute top-0 left-0 h-full bg-blue-600 rounded-full transition-all duration-500 shadow-[0_0_10px_rgba(37,99,235,0.5)]" 
                     :style="`width: ${progress}%`"
                     id="loadingProgress">
                </div>
                <div class="shimmer-glare absolute inset-0"></div>
            </div>
            <div class="flex justify-between text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">
                <span x-text="progress + '%'"></span>
                <span x-text="progress < 100 ? 'Vui lòng không đóng trang' : 'Hoàn thành!'"></span>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Cập nhật thông tin thí sinh theo file -->
<div x-data="bulkCandidateInfoApp()">
    <div id="modal-bulk-candidate-info" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-500 px-6 py-4 flex justify-between items-center">
                <h3 class="text-white font-bold text-lg"><i class="fas fa-user-edit mr-2"></i>Cập nhật thông tin thí sinh</h3>
                <button type="button" onclick="document.getElementById('modal-bulk-candidate-info').classList.add('hidden')" class="text-white/80 hover:text-white text-xl">&times;</button>
            </div>
            <form action="<?= url('/admin/review/bulk-update-candidate-info') ?>" method="POST" enctype="multipart/form-data" class="p-6" @submit.prevent="upload($event)">
                <input type="hidden" id="bulk_candidate_csrf" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                
                <div class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-indigo-50/50 border border-indigo-100/80 rounded-2xl p-4">
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-slate-700 mb-1">Cập nhật thông tin hàng loạt</p>
                        <p class="text-xs text-slate-500 leading-relaxed">Vui lòng tải về file mẫu cập nhật, điền thông tin họ tên, ngày sinh, giới tính chính xác theo Số ĐDCN và tải lên hệ thống.</p>
                    </div>
                    <a href="<?= url('/admin/review/download-candidate-update-template') ?>" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition whitespace-nowrap">
                        <i class="fas fa-download"></i> Tải file mẫu cập nhật
                    </a>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Chọn file:</label>
                    <input type="file" id="candidate_file_input" name="candidate_file" accept=".xlsx,.xls,.csv" required
                        class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-slate-200 rounded-xl">
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-bulk-candidate-info').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Hủy</button>
                    <button type="submit" class="px-6 py-2 text-sm font-bold text-white bg-gradient-to-r from-indigo-500 to-purple-500 rounded-xl hover:from-indigo-600 hover:to-purple-600 shadow-lg shadow-indigo-200 transition">
                        <i class="fas fa-upload mr-1"></i> Cập nhật thông tin
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Premium Loading Modal -->
    <div x-cloak x-show="isLoading" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/40 backdrop-blur-md z-[100] flex items-center justify-center p-4">
        
        <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 w-full max-w-md p-8 text-center relative overflow-hidden">
            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-32 h-32 bg-purple-500/10 rounded-full blur-3xl"></div>

            <div class="relative w-28 h-28 mx-auto mb-6">
                <div class="absolute inset-0 bg-indigo-500/20 rounded-full animate-pulsing-slow"></div>
                <div class="absolute inset-1 border-2 border-indigo-200 border-dashed rounded-full animate-spin-slow"></div>
                <div class="absolute inset-4 bg-white rounded-full flex items-center justify-center shadow-xl border border-white/50 overflow-hidden">
                    <img src="<?= url('/assets/img/Logo.png') ?>" 
                         alt="Logo" 
                         class="w-full h-full object-contain p-2 relative z-10">
                    <div class="shimmer-glare absolute inset-0 z-20 opacity-30"></div>
                </div>
            </div>

            <h3 class="text-xl font-bold text-slate-800 mb-2">Hệ thống đang xử lý</h3>
            <p class="text-slate-500 text-sm mb-6 px-4" x-text="currentLoadingMessage"></p>
            
            <div class="relative h-2 bg-slate-100 rounded-full overflow-hidden mb-2">
                <div class="absolute top-0 left-0 h-full bg-indigo-600 rounded-full transition-all duration-500 shadow-[0_0_10px_rgba(79,70,229,0.5)]" 
                     :style="`width: ${progress}%`"
                     id="candidateLoadingProgress">
                </div>
                <div class="shimmer-glare absolute inset-0"></div>
            </div>
            <div class="flex justify-between text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">
                <span x-text="progress + '%'"></span>
                <span x-text="progress < 100 ? 'Vui lòng không đóng trang' : 'Hoàn thành!'"></span>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Duyệt tất cả hồ sơ trong đợt -->
<div id="modal-bulk-approve-all" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-bold text-lg"><i class="fas fa-exclamation-triangle mr-2"></i>Xác nhận duyệt tất cả</h3>
            <button onclick="document.getElementById('modal-bulk-approve-all').classList.add('hidden')" class="text-white/80 hover:text-white text-xl">&times;</button>
        </div>
        <div class="p-6">
            <p class="text-slate-600 mb-6">Bạn có chắc chắn muốn duyệt **TẤT CẢ** hồ sơ chưa duyệt trong đợt này không? Hành động này không thể hoàn tác.</p>
            <form action="<?= url('/admin/review/bulk-approve-all') ?>" method="POST">
                <input type="hidden" id="bulk_approve_all_csrf" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="session_id" value="<?= $filters['session_id'] ?? '' ?>">
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-bulk-approve-all').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Hủy</button>
                    <button type="submit" class="px-6 py-2 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow shadow-blue-200 transition">
                        Xác nhận duyệt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal: Hủy duyệt tất cả hồ sơ trong đợt -->
<div id="modal-bulk-unapprove-all" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-red-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-bold text-lg"><i class="fas fa-exclamation-triangle mr-2"></i>Xác nhận hủy duyệt tất cả</h3>
            <button onclick="document.getElementById('modal-bulk-unapprove-all').classList.add('hidden')" class="text-white/80 hover:text-white text-xl">&times;</button>
        </div>
        <div class="p-6">
            <p class="text-slate-600 mb-6">Bạn có chắc chắn muốn **HỦY DUYỆT TẤT CẢ** hồ sơ đã duyệt trong đợt này không? Trạng thái sẽ được chuyển về "Chờ duyệt".</p>
            <form action="<?= url('/admin/review/bulk-unapprove-all') ?>" method="POST">
                <input type="hidden" id="bulk_unapprove_all_csrf" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="session_id" value="<?= $filters['session_id'] ?? '' ?>">
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-bulk-unapprove-all').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Hủy</button>
                    <button type="submit" class="px-6 py-2 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 shadow shadow-red-200 transition">
                        Xác nhận hủy duyệt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal: Reset mật khẩu tất cả thí sinh trong đợt về ddmmyyyy -->
<div id="modal-bulk-reset-password-default" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-amber-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-bold text-lg"><i class="fas fa-exclamation-triangle mr-2"></i>Xác nhận khôi phục mật khẩu</h3>
            <button onclick="document.getElementById('modal-bulk-reset-password-default').classList.add('hidden')" class="text-white/80 hover:text-white text-xl">&times;</button>
        </div>
        <div class="p-6">
            <p class="text-slate-600 mb-6">Bạn có chắc chắn muốn **KHÔI PHỤC MẬT KHẨU MẶC ĐỊNH (ddmmyyyy)** cho tất cả thí sinh trong đợt này không? Hành động này sẽ cập nhật mật khẩu của tất cả thí sinh về định dạng ngày tháng năm sinh (ví dụ: ngày sinh 05/10/2005 thành mật khẩu 05102005).</p>
            <form action="<?= url('/admin/review/bulk-reset-password-default') ?>" method="POST">
                <input type="hidden" id="bulk_reset_password_csrf" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="session_id" value="<?= $filters['session_id'] ?? '' ?>">
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-bulk-reset-password-default').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Hủy</button>
                    <button type="submit" class="px-6 py-2 text-sm font-bold text-white bg-amber-600 rounded-xl hover:bg-amber-700 shadow shadow-amber-200 transition">
                        Xác nhận đặt lại
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Checkbox Listeners
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked);
            updateBulkUI();
        });
    }

    // Use event delegation for checkboxes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-checkbox')) {
            updateBulkUI();
        }
    });

    // Header Search logic is now centralized in _candidates_table.php

    // Modal Functions
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        bulkActionSelect.value = ''; // Reset select
    }

    function bulkTranscriptApp() {
        return {
            isLoading: false,
            progress: 0,
            currentLoadingMessage: '',
            
            async upload(event) {
                const form = event.target;
                const importToken = 'imp_trans_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                const formData = new FormData(form);
                formData.append('import_token', importToken);
                
                // Hide file select modal first
                document.getElementById('modal-bulk-transcript').classList.add('hidden');
                
                this.isLoading = true;
                this.progress = 0;
                this.currentLoadingMessage = 'Đang tải file lên máy chủ...';

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
                                    const scaledPercent = 10 + Math.round(currentPercent * 0.9);
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
                            setTimeout(pollProgress, 1000);
                        }
                    };
                    
                    setTimeout(pollProgress, 1000);

                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData
                    });
                    
                    isPolling = false;

                    if (!response.ok) {
                        throw new Error('Lỗi máy chủ (HTTP ' + response.status + ')');
                    }

                    if (response.redirected) {
                        this.isLoading = false;
                        const urlObj = new URL(response.url);
                        const errorMsg = urlObj.searchParams.get('error') || 'Lỗi không xác định';
                        Swal.fire({
                            icon: 'error',
                            title: 'Có lỗi xảy ra',
                            text: decodeURIComponent(errorMsg),
                            confirmButtonColor: '#3B82F6'
                        });
                        return;
                    }

                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        const result = await response.json();
                        this.isLoading = false;
                        
                        if (result.status === true) {
                            // Import successful!
                            let htmlContent = `<div class="text-left text-sm space-y-2">
                                <p><i class="fas fa-check-circle text-emerald-500 mr-1.5"></i>Đã xử lý xong: <b>${result.total}</b> dòng.</p>
                                <p><i class="fas fa-check-double text-blue-500 mr-1.5"></i>Cập nhật thành công: <b>${result.success}</b> dòng học bạ.</p>
                                <p><i class="fas fa-info-circle text-slate-500 mr-1.5"></i>Số dòng không đổi / dòng trống: <b>${result.total - result.success - result.skipped}</b> dòng.</p>
                            `;
                            
                            if (result.skipped > 0) {
                                htmlContent += `
                                    <p class="text-amber-600 font-semibold"><i class="fas fa-exclamation-triangle mr-1.5"></i>Phát hiện: <b>${result.skipped}</b> dòng dữ liệu bị lỗi.</p>
                                    <p class="text-xs text-slate-500 leading-relaxed mt-2 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                        Hệ thống đã tự động xuất danh sách các dòng bị lỗi. Vui lòng tải về file đối chiếu bên dưới để sửa và import lại.
                                    </p>
                                `;
                            }
                            htmlContent += `</div>`;
                            
                            if (result.error_file_url) {
                                const isError = result.skipped > 0;
                                Swal.fire({
                                    icon: isError ? 'warning' : 'success',
                                    title: isError ? 'Hoàn tất nạp dữ liệu!' : 'Cập nhật học bạ thành công!',
                                    html: htmlContent,
                                    showCancelButton: true,
                                    confirmButtonText: '<i class="fas fa-download mr-1"></i> Tải lịch sử cập nhật',
                                    cancelButtonText: 'Đóng',
                                    confirmButtonColor: isError ? '#F59E0B' : '#3B82F6',
                                    cancelButtonColor: '#6B7280'
                                }).then((action) => {
                                    if (action.isConfirmed) {
                                        // Trigger download
                                        const a = document.createElement('a');
                                        a.href = result.error_file_url;
                                        a.download = result.error_file_url.split('/').pop();
                                        document.body.appendChild(a);
                                        a.click();
                                        a.remove();
                                    }
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Cập nhật học bạ thành công!',
                                    html: htmlContent,
                                    confirmButtonColor: '#3B82F6'
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Có lỗi xảy ra',
                                text: result.message || 'Lỗi không xác định',
                                confirmButtonColor: '#3B82F6'
                            });
                        }
                    } else if (contentType.includes('text/html')) {
                        this.isLoading = false;
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi hệ thống',
                            text: 'Đã xảy ra lỗi trong quá trình xử lý học bạ.',
                            confirmButtonColor: '#3B82F6'
                        });
                    } else {
                        // Fallback in case spreadsheet is directly returned
                        const blob = await response.blob();
                        this.progress = 100;
                        this.currentLoadingMessage = 'Hoàn thành!';
                        
                        setTimeout(() => {
                            this.isLoading = false;
                            
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'Ket_Qua_Cap_Nhat_Hoc_Ba.xlsx';
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            window.URL.revokeObjectURL(url);

                            Swal.fire({
                                icon: 'success',
                                title: 'Hoàn tất cập nhật học bạ!',
                                text: 'Đã cập nhật điểm học bạ thành công và tải về file kết quả.',
                                confirmButtonColor: '#3B82F6'
                            }).then(() => {
                                location.reload();
                            });
                        }, 500);
                    }
                } catch (error) {
                    isPolling = false;
                    this.isLoading = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi Kết Nối',
                        text: error.message,
                        confirmButtonColor: '#3B82F6'
                    });
                }
            }
        };
    }

    function bulkCandidateInfoApp() {
        return {
            isLoading: false,
            progress: 0,
            currentLoadingMessage: '',
            
            async upload(event) {
                const form = event.target;
                const importToken = 'imp_cand_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                const formData = new FormData(form);
                formData.append('import_token', importToken);
                
                // Hide file select modal
                document.getElementById('modal-bulk-candidate-info').classList.add('hidden');
                
                this.isLoading = true;
                this.progress = 0;
                this.currentLoadingMessage = 'Đang tải file lên máy chủ...';

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
                                    const scaledPercent = 10 + Math.round(currentPercent * 0.9);
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
                            setTimeout(pollProgress, 1000);
                        }
                    };
                    
                    setTimeout(pollProgress, 1000);

                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData
                    });
                    
                    isPolling = false;

                    if (!response.ok) {
                        throw new Error('Lỗi máy chủ (HTTP ' + response.status + ')');
                    }

                    if (response.redirected) {
                        this.isLoading = false;
                        const urlObj = new URL(response.url);
                        const errorMsg = urlObj.searchParams.get('error') || 'Lỗi không xác định';
                        Swal.fire({
                            icon: 'error',
                            title: 'Có lỗi xảy ra',
                            text: decodeURIComponent(errorMsg),
                            confirmButtonColor: '#4F46E5'
                        });
                        return;
                    }

                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        const result = await response.json();
                        this.isLoading = false;
                        
                        if (result.status === true) {
                            let htmlContent = `<div class="text-left text-sm space-y-2">
                                <p><i class="fas fa-check-circle text-emerald-500 mr-1.5"></i>Đã xử lý xong: <b>${result.total}</b> dòng.</p>
                                <p><i class="fas fa-check-double text-indigo-500 mr-1.5"></i>Cập nhật thông tin thành công: <b>${result.success}</b> thí sinh.</p>
                                <p><i class="fas fa-info-circle text-slate-500 mr-1.5"></i>Số dòng không đổi / dòng trống: <b>${result.total - result.success - result.skipped}</b> dòng.</p>
                            `;
                            
                            if (result.skipped > 0) {
                                htmlContent += `
                                    <p class="text-amber-600 font-semibold"><i class="fas fa-exclamation-triangle mr-1.5"></i>Phát hiện: <b>${result.skipped}</b> dòng bị bỏ qua / lỗi.</p>
                                    <p class="text-xs text-slate-500 leading-relaxed mt-2 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                        Hệ thống đã tự động xuất danh sách kết quả đối chiếu. Vui lòng tải về file đối chiếu bên dưới để kiểm tra.
                                    </p>
                                `;
                            }
                            htmlContent += `</div>`;
                            
                            if (result.error_file_url) {
                                const isError = result.skipped > 0;
                                Swal.fire({
                                    icon: isError ? 'warning' : 'success',
                                    title: isError ? 'Hoàn tất nạp dữ liệu!' : 'Cập nhật thông tin thành công!',
                                    html: htmlContent,
                                    showCancelButton: true,
                                    confirmButtonText: '<i class="fas fa-download mr-1"></i> Tải lịch sử cập nhật',
                                    cancelButtonText: 'Đóng',
                                    confirmButtonColor: isError ? '#F59E0B' : '#4F46E5',
                                    cancelButtonColor: '#6B7280'
                                }).then((action) => {
                                    if (action.isConfirmed) {
                                        const a = document.createElement('a');
                                        a.href = result.error_file_url;
                                        a.download = result.error_file_url.split('/').pop();
                                        document.body.appendChild(a);
                                        a.click();
                                        a.remove();
                                    }
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Cập nhật thông tin thành công!',
                                    html: htmlContent,
                                    confirmButtonColor: '#4F46E5'
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Có lỗi xảy ra',
                                text: result.message || 'Lỗi không xác định',
                                confirmButtonColor: '#4F46E5'
                            });
                        }
                    } else {
                        this.isLoading = false;
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi hệ thống',
                            text: 'Đã xảy ra lỗi trong quá trình cập nhật thông tin.',
                            confirmButtonColor: '#4F46E5'
                        });
                    }
                } catch (error) {
                    isPolling = false;
                    this.isLoading = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi Kết Nối',
                        text: error.message,
                        confirmButtonColor: '#4F46E5'
                    });
                }
            }
        };
    }
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin.php'; 
?>
