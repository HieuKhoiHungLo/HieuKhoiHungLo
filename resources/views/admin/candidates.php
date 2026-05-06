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
</style>

<div x-data="{ 
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
<div id="modal-bulk-transcript" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-500 to-indigo-500 px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-bold text-lg"><i class="fas fa-file-excel mr-2"></i>Cập nhật điểm học bạ</h3>
            <button onclick="document.getElementById('modal-bulk-transcript').classList.add('hidden')" class="text-white/80 hover:text-white text-xl">&times;</button>
        </div>
        <form action="<?= url('/admin/review/bulk-update-transcript') ?>" method="POST" enctype="multipart/form-data" class="p-6">
            <input type="hidden" id="bulk_transcript_csrf" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <div class="mb-5">
                <div class="flex justify-between items-center mb-3">
                    <p class="text-sm text-slate-600">Upload file Excel (.xlsx/.xls) chứa điểm cả năm theo mẫu. Mỗi dòng = 1 thí sinh × 1 lớp.</p>
                    <a href="<?= url('/admin/review/download-transcript-template') ?>" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100 transition">
                        <i class="fas fa-download mr-1"></i> Tải file mẫu
                    </a>
                </div>
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 overflow-x-auto">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Cấu trúc file (19 cột):</p>
                    <table class="w-full text-xs whitespace-nowrap">
                        <thead><tr class="text-left text-slate-500 border-b border-slate-200">
                            <th class="py-1 px-1">A</th><th class="py-1 px-1 font-bold text-slate-800">B</th><th class="py-1 px-1 font-bold text-slate-800">C</th>
                            <th class="py-1 px-1">D</th><th class="py-1 px-1">E</th><th class="py-1 px-1">F</th><th class="py-1 px-1">G</th><th class="py-1 px-1">H</th>
                            <th class="py-1 px-1">I</th><th class="py-1 px-1">J</th><th class="py-1 px-1">K</th><th class="py-1 px-1">L</th>
                            <th class="py-1 px-1">M</th><th class="py-1 px-1">N</th><th class="py-1 px-1">O</th>
                            <th class="py-1 px-1">P</th><th class="py-1 px-1">Q</th><th class="py-1 px-1">R</th><th class="py-1 px-1 font-bold text-blue-600">S</th>
                        </tr></thead>
                        <tbody class="text-slate-600">
                            <tr class="border-b border-slate-100 font-medium text-[10px]">
                                <td class="py-1 px-1 text-slate-400">STT</td><td class="py-1 px-1 font-bold">CCCD</td><td class="py-1 px-1 font-bold">Lớp</td>
                                <td class="py-1 px-1">Toán</td><td class="py-1 px-1">Văn</td><td class="py-1 px-1">NN</td><td class="py-1 px-1">Lý</td><td class="py-1 px-1">Hóa</td>
                                <td class="py-1 px-1">Sinh</td><td class="py-1 px-1">Sử</td><td class="py-1 px-1">Địa</td><td class="py-1 px-1">GDCD</td>
                                <td class="py-1 px-1">Tin</td><td class="py-1 px-1">CN</td><td class="py-1 px-1">KTPL</td>
                                <td class="py-1 px-1">ĐTB</td><td class="py-1 px-1">HL</td><td class="py-1 px-1">HK</td><td class="py-1 px-1 font-semibold text-blue-600">Ghi chú</td>
                            </tr>
                            <tr class="text-[10px]">
                                <td class="py-1 px-1 text-slate-400">1</td><td class="py-1 px-1 font-mono">00123...</td><td class="py-1 px-1">12</td>
                                <td class="py-1 px-1">8.5</td><td class="py-1 px-1">7.0</td><td class="py-1 px-1">8.0</td><td class="py-1 px-1">7.5</td><td class="py-1 px-1">8.0</td>
                                <td class="py-1 px-1">6.5</td><td class="py-1 px-1">7.0</td><td class="py-1 px-1">7.5</td><td class="py-1 px-1">8.0</td>
                                <td class="py-1 px-1">9.0</td><td class="py-1 px-1">7.0</td><td class="py-1 px-1"></td>
                                <td class="py-1 px-1">7.7</td><td class="py-1 px-1">Khá</td><td class="py-1 px-1">Tốt</td><td class="py-1 px-1 text-blue-500 italic">Dữ liệu mẫu</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
<?php endif; ?>


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
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin.php'; 
?>
