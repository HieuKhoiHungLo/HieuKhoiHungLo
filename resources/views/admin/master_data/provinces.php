<?php $title = 'Quản lý Danh mục Tỉnh - Admin'; ?>
<?php ob_start(); ?>

<?php
function sortUrl($field, $currentSort, $currentDir, $filters) {
    $dir = 'ASC';
    if ($currentSort === $field) {
        $dir = $currentDir === 'ASC' ? 'DESC' : 'ASC';
    }
    
    $query = $_GET;
    $query['sort'] = $field;
    $query['dir'] = $dir;
    
    return url('/admin/master-data/provinces') . '?' . http_build_query($query);
}

function sortIcon($field, $currentSort, $currentDir) {
    if ($currentSort !== $field) {
        return '<i class="fas fa-sort text-slate-400 ml-1"></i>';
    }
    return $currentDir === 'ASC' 
        ? '<i class="fas fa-sort-up text-[#0066FF] ml-1"></i>' 
        : '<i class="fas fa-sort-down text-[#0066FF] ml-1"></i>';
}

function pageUrl($page, $filters) {
    $query = $_GET;
    $query['page'] = $page;
    return url('/admin/master-data/provinces') . '?' . http_build_query($query);
}
?>

<style>
    main {
        padding: 1.25rem !important;
    }
    .province-table-container {
        max-height: calc(100vh - 280px);
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        background: #fff;
    }
    .province-table {
        border-collapse: separate !important;
        border-spacing: 0;
        width: 100%;
    }
    .province-table th, .province-table td {
        padding: 0.6rem 0.75rem;
        border: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        border-right: 1px solid #e2e8f0 !important;
        vertical-align: middle;
        font-size: 13px;
        color: #334155;
        background-clip: padding-box;
    }
    .province-table th:last-child, .province-table td:last-child {
        border-right: none !important;
    }
    .province-table th:first-child, .province-table td:first-child {
        border-left: 1px solid #e2e8f0 !important;
    }
    .province-table thead tr:first-child th {
        border-top: 1px solid #e2e8f0 !important;
    }

    /* Header Specifics & Sticky Header */
    .province-table thead th {
        position: sticky;
        top: 0;
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        z-index: 10;
        box-shadow: inset 0 -1px 0 #e2e8f0;
    }

    /* Hover State */
    .province-table tbody tr:hover td {
        background-color: #f1f5f9 !important;
    }
    
    /* Sort icon styling */
    .sort-trigger {
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        width: 100%;
        color: inherit;
    }
    
    /* Search input padding fix to prevent icon overlap */
    .search-input {
        padding-left: 2.25rem !important;
    }
</style>

<div class="max-w-7xl mx-auto px-4 py-2">
    <header class="mb-4 flex justify-between items-center">
        <div>
            <h2 class="text-xl font-extrabold text-gray-900 uppercase">Danh mục Tỉnh</h2>
        </div>
        <div class="flex space-x-2">
            <a href="<?= url('/admin/master-data/provinces/export') ?>?csrf_token=<?= (string) $this->csrfToken() ?>&<?= http_build_query($filters) ?>" class="text-white font-bold py-1.5 px-3 rounded-xl shadow transition flex items-center text-xs" style="background-color: #10B981;" onmouseover="this.style.backgroundColor='#059669'" onmouseout="this.style.backgroundColor='#10B981'">
                <i class="fas fa-file-export mr-1.5"></i> Xuất Excel
            </a>
            <button onclick="openImportModal()" class="text-white font-bold py-1.5 px-3 rounded-xl shadow transition flex items-center text-xs" style="background-color: #0066FF;" onmouseover="this.style.backgroundColor='#0050CC'" onmouseout="this.style.backgroundColor='#0066FF'">
                <i class="fas fa-file-import mr-1.5"></i> Nhập Excel
            </button>
            <button onclick="openModal()" class="text-white font-extrabold py-1.5 px-4 rounded-xl shadow-lg transform hover:scale-105 transition flex items-center text-xs" style="background-color: #BE1E2D;" onmouseover="this.style.backgroundColor='#9d1926'" onmouseout="this.style.backgroundColor='#BE1E2D'">
                <i class="fas fa-plus mr-1.5"></i> Thêm tỉnh mới
            </button>
        </div>
    </header>

    <!-- Success/Error Alert messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl text-xs font-medium flex items-center">
            <i class="fas fa-check-circle mr-2 text-emerald-500 text-base"></i>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-100 text-red-700 rounded-xl text-xs font-medium flex items-center">
            <i class="fas fa-exclamation-circle mr-2 text-red-500 text-base"></i>
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['warning'])): ?>
        <div class="mb-4 p-3 bg-amber-50 border border-amber-100 text-amber-700 rounded-xl text-xs font-medium flex items-center">
            <i class="fas fa-exclamation-triangle mr-2 text-amber-500 text-base"></i>
            <?= $_SESSION['warning']; unset($_SESSION['warning']); ?>
        </div>
    <?php endif; ?>

    <!-- Filters Bar (Single Row) -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 px-4 py-2.5 mb-4">
        <form method="GET" action="<?= url('/admin/master-data/provinces') ?>" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center space-x-2 flex-grow min-w-[200px]">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Tìm kiếm:</span>
                <div class="relative flex-grow">
                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Tìm tên hoặc mã tỉnh..." class="w-full search-input pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-[#0066FF] text-xs text-slate-700">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                        <i class="fas fa-search text-[10px]"></i>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="text-white font-bold py-1.5 px-4 rounded-xl shadow-md transition text-xs flex items-center" style="background-color: #0066FF;" onmouseover="this.style.backgroundColor='#0050CC'" onmouseout="this.style.backgroundColor='#0066FF'">
                    <i class="fas fa-filter mr-1.5"></i> Lọc
                </button>
                <a href="<?= url('/admin/master-data/provinces') ?>" class="font-bold py-1.5 px-4 rounded-xl transition text-xs flex items-center" style="background-color: #F1F5F9; color: #475569;" onmouseover="this.style.backgroundColor='#E2E8F0'" onmouseout="this.style.backgroundColor='#F1F5F9'">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden province-table-container">
        <form action="<?= url('/admin/master-data/provinces/actions') ?>" method="POST" id="bulk-delete-form">
            <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
            <input type="hidden" name="action" value="bulk_delete">
            
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center hidden" id="bulk-actions">
                <span class="text-sm font-bold text-slate-600">Đã chọn <span id="selected-count">0</span> mục</span>
                <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa các mục đã chọn?')" class="text-red-600 hover:text-red-800 font-bold text-xs uppercase bg-red-50 hover:bg-red-100 py-2 px-4 rounded-lg transition">
                    <i class="fas fa-trash mr-1"></i> Xóa đã chọn
                </button>
            </div>

            <table class="w-full text-left border-collapse province-table">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase tracking-wider">
                        <th class="px-6 py-3 w-10">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-[#0066FF] focus:ring-[#0066FF]">
                        </th>
                        <th class="px-6 py-3 font-heading">
                            <a href="<?= sortUrl('ma_tinh', $filters['sort'], $filters['dir'], $filters) ?>" class="sort-trigger">
                                Mã Tỉnh <?= sortIcon('ma_tinh', $filters['sort'], $filters['dir']) ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 font-heading">
                            <a href="<?= sortUrl('ten_tinh', $filters['sort'], $filters['dir'], $filters) ?>" class="sort-trigger">
                                Tên Tỉnh <?= sortIcon('ten_tinh', $filters['sort'], $filters['dir']) ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-center font-heading w-32">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($provinces)): ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400 italic">Chưa có dữ liệu tỉnh/thành phố.</td></tr>
                    <?php else: ?>
                        <?php foreach ($provinces as $p): ?>
                            <tr class="hover:bg-slate-50 transition duration-150 ease-in-out">
                                <td class="px-6 py-3">
                                    <input type="checkbox" name="ids[]" value="<?= $p['ma_tinh'] ?>" class="item-checkbox rounded border-gray-300 text-[#0066FF] focus:ring-[#0066FF]">
                                </td>
                                <td class="px-6 py-3 text-slate-600 font-mono text-sm"><?= htmlspecialchars($p['ma_tinh']) ?></td>
                                <td class="px-6 py-3 text-slate-700 text-sm"><?= htmlspecialchars($p['ten_tinh']) ?></td>
                                <td class="px-6 py-3 text-center">
                                    <button type="button" onclick='editProvince(<?= json_encode($p) ?>)' class="text-[#0066FF] hover:text-blue-800 font-bold text-xs uppercase">Sửa</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>

        <!-- Pagination UI -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-widest">
                    Hiển thị <span class="text-slate-800"><?= count($provinces) ?></span> / <span class="text-slate-800"><?= $totalRecords ?></span> tỉnh/TP
                </div>

                <nav class="flex items-center space-x-1">
                    <!-- Previous Page -->
                    <?php if ($currentPage > 1): ?>
                        <a href="<?= pageUrl($currentPage - 1, $filters) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:border-[#0066FF] hover:text-[#0066FF] transition shadow-sm">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </a>
                    <?php else: ?>
                        <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 border border-slate-100 text-slate-300 cursor-not-allowed">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </div>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <div class="flex items-center bg-white border border-slate-200 rounded-xl px-1 shadow-sm">
                        <?php
                        $start = max(1, $currentPage - 2);
                        $end = min($totalPages, $currentPage + 2);

                        if ($start > 1): ?>
                            <a href="<?= pageUrl(1, $filters) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold text-slate-500 hover:text-[#0066FF]">1</a>
                            <?php if ($start > 2): ?><span class="text-slate-300 text-xs px-1">...</span><?php endif; ?>
                        <?php endif;

                        for ($i = $start; $i <= $end; $i++): ?>
                            <a href="<?= pageUrl($i, $filters) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition <?= $i === $currentPage ? 'bg-[#0066FF] text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-[#0066FF]' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor;

                        if ($end < $totalPages): ?>
                            <?php if ($end < $totalPages - 1): ?><span class="text-slate-300 text-xs px-1">...</span><?php endif; ?>
                            <a href="<?= pageUrl($totalPages, $filters) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold text-slate-500 hover:text-[#0066FF]"><?= $totalPages ?></a>
                        <?php endif; ?>
                    </div>

                    <!-- Next Page -->
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= pageUrl($currentPage + 1, $filters) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:border-[#0066FF] hover:text-[#0066FF] transition shadow-sm">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </a>
                    <?php else: ?>
                        <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 border border-slate-100 text-slate-300 cursor-not-allowed">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </div>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Save Modal -->
<div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl">
        <h3 id="modal-title" class="text-xl font-black uppercase mb-6 text-slate-800">Tỉnh / Thành Phố</h3>
        <form action="<?= url('/admin/master-data/provinces') ?>" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
            <input type="hidden" name="old_ma" id="old_ma">
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Mã Tỉnh</label>
                <input type="text" name="ma_tinh" id="ma_tinh" required placeholder="Ví dụ: 17" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none text-slate-800 font-semibold focus:border-[#0066FF] transition">
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Tên Tỉnh</label>
                <input type="text" name="ten_tinh" id="ten_tinh" required placeholder="Ví dụ: Phú Thọ" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none text-slate-800 font-semibold focus:border-[#0066FF] transition">
            </div>
            <div class="flex space-x-3 pt-4">
                <button type="button" onclick="closeModal()" class="flex-grow py-3 text-slate-600 font-black uppercase text-xs tracking-widest rounded-xl transition" style="background-color: #F1F5F9;" onmouseover="this.style.backgroundColor='#E2E8F0'" onmouseout="this.style.backgroundColor='#F1F5F9'">Hủy</button>
                <button type="submit" class="flex-grow py-3 text-white font-black uppercase text-xs tracking-widest rounded-xl shadow-lg transition" style="background-color: #BE1E2D;" onmouseover="this.style.backgroundColor='#9d1926'" onmouseout="this.style.backgroundColor='#BE1E2D'">Lưu dữ liệu</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl">
        <h3 class="text-xl font-black uppercase mb-2 text-slate-800">Nhập danh mục từ Excel</h3>
        <p class="text-slate-500 text-xs mb-6 leading-relaxed">Chọn file Excel để tải dữ liệu danh mục Tỉnh lên hệ thống. Tải file mẫu bên dưới nếu chưa có.</p>
        
        <form action="<?= url('/admin/master-data/provinces/actions') ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
            <input type="hidden" name="action" value="import">
            
            <div class="border-2 border-dashed border-slate-200 hover:border-[#0066FF] transition rounded-2xl p-6 text-center cursor-pointer relative" onclick="document.getElementById('file-input').click()">
                <i class="fas fa-cloud-upload-alt text-3xl text-slate-400 mb-2"></i>
                <p class="text-xs font-bold text-slate-500" id="file-label">Kéo thả hoặc click để chọn file Excel (.xlsx, .xls, .csv)</p>
                <input type="file" name="file" id="file-input" required class="hidden" onchange="fileSelected(this)">
            </div>
            
            <div class="flex items-center justify-between text-xs pt-2">
                <a href="<?= url('/admin/master-data/provinces/template') ?>" class="text-[#0066FF] hover:underline font-bold transition flex items-center">
                    <i class="fas fa-download mr-1.5"></i> Tải file Excel mẫu
                </a>
            </div>
            
            <div class="flex space-x-3 pt-6">
                <button type="button" onclick="closeImportModal()" class="flex-grow py-3 text-slate-600 font-black uppercase text-xs tracking-widest rounded-xl transition" style="background-color: #F1F5F9;" onmouseover="this.style.backgroundColor='#E2E8F0'" onmouseout="this.style.backgroundColor='#F1F5F9'">Hủy</button>
                <button type="submit" class="flex-grow py-3 text-white font-black uppercase text-xs tracking-widest rounded-xl shadow-lg transition" style="background-color: #0066FF;" onmouseover="this.style.backgroundColor='#0050CC'" onmouseout="this.style.backgroundColor='#0066FF'">Bắt đầu nhập</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modal-title').innerText = "Thêm tỉnh mới";
        document.getElementById('old_ma').value = "";
        document.getElementById('ma_tinh').value = "";
        document.getElementById('ma_tinh').removeAttribute('readonly');
        document.getElementById('ten_tinh').value = "";
        
        document.getElementById('modal').classList.remove('hidden');
        document.getElementById('modal').classList.add('flex');
    }

    function editProvince(province) {
        document.getElementById('modal-title').innerText = "Chỉnh sửa tỉnh";
        document.getElementById('old_ma').value = province.ma_tinh;
        document.getElementById('ma_tinh').value = province.ma_tinh;
        // Block editing of code if it is already referenced
        document.getElementById('ma_tinh').setAttribute('readonly', 'true');
        document.getElementById('ten_tinh').value = province.ten_tinh;
        
        document.getElementById('modal').classList.remove('hidden');
        document.getElementById('modal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('modal').classList.remove('flex');
        document.getElementById('modal').classList.add('hidden');
    }

    function openImportModal() {
        document.getElementById('import-modal').classList.remove('hidden');
        document.getElementById('import-modal').classList.add('flex');
    }

    function closeImportModal() {
        document.getElementById('import-modal').classList.remove('flex');
        document.getElementById('import-modal').classList.add('hidden');
        document.getElementById('file-label').innerText = "Kéo thả hoặc click để chọn file Excel (.xlsx, .xls, .csv)";
        document.getElementById('file-input').value = "";
    }

    function fileSelected(input) {
        if (input.files && input.files.length > 0) {
            document.getElementById('file-label').innerText = "Đã chọn: " + input.files[0].name;
            document.getElementById('file-label').classList.add('text-[#0066FF]');
        }
    }

    // Bulk selection logic
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');

    function updateBulkActions() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        if (checked.length > 0) {
            selectedCount.innerText = checked.length;
            bulkActions.classList.remove('hidden');
        } else {
            bulkActions.classList.add('hidden');
        }
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => {
            cb.checked = selectAll.checked;
        });
        updateBulkActions();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
