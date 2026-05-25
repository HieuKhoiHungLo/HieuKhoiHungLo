<?php $title = 'Quản lý Trường THPT - Admin'; ?>
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
    
    return url('/admin/master-data/schools') . '?' . http_build_query($query);
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
    return url('/admin/master-data/schools') . '?' . http_build_query($query);
}
?>

<style>
    main {
        padding: 1.25rem !important;
    }
    .school-table-container {
        max-height: calc(100vh - 280px);
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        background: #fff;
    }
    .school-table {
        border-collapse: separate !important;
        border-spacing: 0;
        width: 100%;
    }
    .school-table th, .school-table td {
        padding: 0.6rem 0.75rem;
        border: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        border-right: 1px solid #e2e8f0 !important;
        vertical-align: middle;
        font-size: 13px;
        color: #334155;
        background-clip: padding-box;
    }
    .school-table th:last-child, .school-table td:last-child {
        border-right: none !important;
    }
    .school-table th:first-child, .school-table td:first-child {
        border-left: 1px solid #e2e8f0 !important;
    }
    .school-table thead tr:first-child th {
        border-top: 1px solid #e2e8f0 !important;
    }

    /* Header Specifics & Sticky Header */
    .school-table thead th {
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
    .school-table tbody tr:hover td {
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

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out forwards;
    }
</style>

<div class="max-w-7xl mx-auto px-4 py-2">
    <header class="mb-4 flex justify-between items-center">
        <div>
            <h2 class="text-xl font-extrabold text-gray-900 uppercase">Danh mục Trường THPT</h2>
        </div>
        <div class="flex space-x-2">
            <a href="<?= url('/admin/master-data/schools/export') ?>?csrf_token=<?= (string) $this->csrfToken() ?>&<?= http_build_query($filters) ?>" class="text-white font-bold py-1.5 px-3 rounded-xl shadow transition flex items-center text-xs" style="background-color: #10B981;" onmouseover="this.style.backgroundColor='#059669'" onmouseout="this.style.backgroundColor='#10B981'">
                <i class="fas fa-file-export mr-1.5"></i> Xuất Excel
            </a>
            <button onclick="openImportModal()" class="text-white font-bold py-1.5 px-3 rounded-xl shadow transition flex items-center text-xs" style="background-color: #0066FF;" onmouseover="this.style.backgroundColor='#0050CC'" onmouseout="this.style.backgroundColor='#0066FF'">
                <i class="fas fa-file-import mr-1.5"></i> Nhập Excel
            </button>
            <button onclick="openModal()" class="text-white font-extrabold py-1.5 px-4 rounded-xl shadow-lg transform hover:scale-105 transition flex items-center text-xs" style="background-color: #BE1E2D;" onmouseover="this.style.backgroundColor='#9d1926'" onmouseout="this.style.backgroundColor='#BE1E2D'">
                <i class="fas fa-plus mr-1.5"></i> Thêm trường mới
            </button>
        </div>
    </header>

    <!-- Sessions Flash Notification (Success / Warning / Error) -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl text-xs font-medium flex items-center">
            <i class="fas fa-check-circle mr-2 text-emerald-500 text-base"></i>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['warning'])): ?>
        <div class="mb-4 p-3 bg-amber-50 border border-amber-100 text-amber-700 rounded-xl text-xs font-medium flex items-center">
            <i class="fas fa-exclamation-triangle mr-2 text-amber-500 text-base"></i>
            <?= $_SESSION['warning']; unset($_SESSION['warning']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-100 text-red-700 rounded-xl text-xs font-medium flex items-center">
            <i class="fas fa-exclamation-circle mr-2 text-red-500 text-base"></i>
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Filters Bar (Single Row) -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 px-4 py-2.5 mb-4">
        <form method="GET" action="<?= url('/admin/master-data/schools') ?>" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center space-x-2 min-w-[180px]">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Tỉnh / Thành phố:</span>
                <select name="ma_tinh" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-[#0066FF] text-xs text-slate-700 font-medium">
                    <option value="">Tất cả tỉnh thành</option>
                    <?php foreach ($provinces as $p): ?>
                        <option value="<?= $p['ma_tinh'] ?>" <?= ($filters['ma_tinh'] ?? '') === $p['ma_tinh'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['ma_tinh']) ?> - <?= htmlspecialchars($p['ten_tinh']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center space-x-2 min-w-[150px]">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Khu vực:</span>
                <select name="khu_vuc" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-[#0066FF] text-xs text-slate-700 font-medium">
                    <option value="">Tất cả khu vực</option>
                    <option value="KV1" <?= ($filters['khu_vuc'] ?? '') === 'KV1' ? 'selected' : '' ?>>KV1</option>
                    <option value="KV2" <?= ($filters['khu_vuc'] ?? '') === 'KV2' ? 'selected' : '' ?>>KV2</option>
                    <option value="KV2-NT" <?= ($filters['khu_vuc'] ?? '') === 'KV2-NT' ? 'selected' : '' ?>>KV2-NT</option>
                    <option value="KV3" <?= ($filters['khu_vuc'] ?? '') === 'KV3' ? 'selected' : '' ?>>KV3</option>
                </select>
            </div>
            
            <div class="flex items-center space-x-2 flex-grow min-w-[200px]">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Tìm kiếm:</span>
                <div class="relative flex-grow">
                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Tìm tên/mã trường..." class="w-full search-input pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-[#0066FF] text-xs text-slate-700">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                        <i class="fas fa-search text-[10px]"></i>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="text-white font-bold py-1.5 px-4 rounded-xl shadow-md transition text-xs flex items-center" style="background-color: #0066FF;" onmouseover="this.style.backgroundColor='#0050CC'" onmouseout="this.style.backgroundColor='#0066FF'">
                    <i class="fas fa-filter mr-1.5"></i> Lọc
                </button>
                <a href="<?= url('/admin/master-data/schools') ?>" class="font-bold py-1.5 px-4 rounded-xl transition text-xs flex items-center" style="background-color: #F1F5F9; color: #475569;" onmouseover="this.style.backgroundColor='#E2E8F0'" onmouseout="this.style.backgroundColor='#F1F5F9'">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden school-table-container">
        <form action="<?= url('/admin/master-data/schools/actions') ?>" method="POST" id="bulk-delete-form">
            <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
            <input type="hidden" name="action" value="bulk_delete">
            
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center hidden" id="bulk-actions">
                <span class="text-sm font-bold text-slate-600">Đã chọn <span id="selected-count" class="text-[#0066FF]">0</span> mục danh sách</span>
                <button type="submit" onclick="return confirm('Bạn có chắc chắn muốn xóa các trường đã chọn khỏi hệ thống?')" class="text-red-600 hover:text-red-800 font-bold text-xs uppercase bg-red-50 hover:bg-red-100 py-2 px-4 rounded-lg transition">
                    <i class="fas fa-trash mr-1"></i> Xóa đã chọn
                </button>
            </div>

            <table class="w-full text-left border-collapse school-table">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase tracking-wider">
                        <th class="px-6 py-4 w-10 text-center">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-[#0066FF] focus:ring-[#0066FF] cursor-pointer">
                        </th>
                        <th class="px-6 py-4 font-heading">
                            <a href="<?= sortUrl('ma_truong', $filters['sort'], $filters['dir'], $filters) ?>" class="sort-trigger">
                                Mã trường <?= sortIcon('ma_truong', $filters['sort'], $filters['dir']) ?>
                            </a>
                        </th>
                        <th class="px-6 py-4 font-heading">
                            <a href="<?= sortUrl('ten_truong', $filters['sort'], $filters['dir'], $filters) ?>" class="sort-trigger">
                                Tên trường THPT <?= sortIcon('ten_truong', $filters['sort'], $filters['dir']) ?>
                            </a>
                        </th>
                        <th class="px-6 py-4 font-heading">
                            <a href="<?= sortUrl('khu_vuc', $filters['sort'], $filters['dir'], $filters) ?>" class="sort-trigger">
                                Khu vực <?= sortIcon('khu_vuc', $filters['sort'], $filters['dir']) ?>
                            </a>
                        </th>
                        <th class="px-6 py-4 font-heading">
                            <a href="<?= sortUrl('ten_tinh', $filters['sort'], $filters['dir'], $filters) ?>" class="sort-trigger">
                                Tỉnh / Thành phố <?= sortIcon('ten_tinh', $filters['sort'], $filters['dir']) ?>
                            </a>
                        </th>
                        <th class="px-6 py-4 text-center font-heading">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($schools)): ?>
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">Chưa có dữ liệu trường học THPT phù hợp với bộ lọc.</td></tr>
                    <?php else: ?>
                        <?php foreach ($schools as $school): ?>
                            <tr class="transition duration-150 ease-in-out">
                                <td class="px-6 py-3 text-center">
                                    <input type="checkbox" name="ids[]" value="<?= $school['ma_truong'] ?>" class="item-checkbox rounded border-gray-300 text-[#0066FF] focus:ring-[#0066FF] cursor-pointer">
                                </td>
                                <td class="px-6 py-3 text-slate-600 font-mono text-sm"><?= htmlspecialchars($school['ma_truong']) ?></td>
                                <td class="px-6 py-3 font-normal text-slate-700 text-sm"><?= htmlspecialchars($school['ten_truong']) ?></td>
                                <td class="px-6 py-3">
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-md text-xs font-semibold border border-emerald-100"><?= htmlspecialchars($school['khu_vuc']) ?></span>
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600"><?= htmlspecialchars($school['ten_tinh'] ?? $school['ma_tinh']) ?></td>
                                <td class="px-6 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <button type="button" onclick='editSchool(<?= json_encode($school) ?>)' class="text-[#0066FF] hover:text-blue-800 font-bold text-xs uppercase">Sửa</button>
                                        <span class="text-slate-300">|</span>
                                        <button type="button" onclick="deleteSingleSchool('<?= $school['ma_truong'] ?>')" class="text-rose-600 hover:text-rose-800 font-bold text-xs uppercase">Xóa</button>
                                    </div>
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
                    Hiển thị <span class="text-slate-800"><?= count($schools) ?></span> / <span class="text-slate-800"><?= $totalRecords ?></span> trường THPT
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

    <!-- Add/Edit Modal -->
    <div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl">
            <h3 id="modal-title" class="text-xl font-black uppercase mb-6 text-slate-800">Trường THPT</h3>
            <form action="<?= url('/admin/master-data/schools') ?>" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
                <input type="hidden" name="old_ma" id="old_ma">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Mã trường</label>
                    <input type="text" name="ma_truong" id="ma_truong" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-[#0066FF] text-sm text-slate-700">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tên trường</label>
                    <input type="text" name="ten_truong" id="ten_truong" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-[#0066FF] text-sm text-slate-700">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Khu vực</label>
                        <select name="khu_vuc" id="khu_vuc" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-[#0066FF] text-sm text-slate-700">
                            <option value="KV1">KV1</option>
                            <option value="KV2">KV2</option>
                            <option value="KV2-NT">KV2-NT</option>
                            <option value="KV3">KV3</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tỉnh / Thành</label>
                        <select name="ma_tinh" id="ma_tinh" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-[#0066FF] text-sm text-slate-700">
                            <?php foreach ($provinces as $p): ?>
                                <option value="<?= $p['ma_tinh'] ?>"><?= htmlspecialchars($p['ma_tinh']) ?> - <?= htmlspecialchars($p['ten_tinh']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal()" class="flex-grow py-3 text-slate-600 font-bold uppercase text-xs tracking-widest rounded-xl transition" style="background-color: #F1F5F9; color: #475569;" onmouseover="this.style.backgroundColor='#E2E8F0'" onmouseout="this.style.backgroundColor='#F1F5F9'">Hủy</button>
                    <button type="submit" class="flex-grow py-3 text-white font-bold uppercase text-xs tracking-widest rounded-xl shadow-lg transition" style="background-color: #BE1E2D;" onmouseover="this.style.backgroundColor='#9d1926'" onmouseout="this.style.backgroundColor='#BE1E2D'">Lưu dữ liệu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="import-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl transform transition-all">
            <h3 class="text-xl font-black uppercase mb-6 border-b pb-4 text-gray-800">Nhập danh mục từ Excel</h3>
            <form action="<?= url('/admin/master-data/schools/actions') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
                <input type="hidden" name="action" value="import">
                
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 mb-4">Hỗ trợ tải lên file dữ liệu Excel (.xlsx, .xls) hoặc file CSV (UTF-8) chuẩn của Bộ.</p>
                    <div class="text-center">
                        <a href="<?= url('/admin/master-data/schools/template') ?>" class="text-[#0066FF] hover:underline text-sm font-bold flex justify-center items-center">
                            <i class="fas fa-download mr-1"></i> Tải file mẫu nhập (.xls)
                        </a>
                    </div>
                    
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-50 transition cursor-pointer relative" id="drop-zone">
                        <input type="file" name="file" id="file-input" required accept=".xlsx,.xls,.csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                        <p class="text-sm font-bold text-gray-500" id="file-label">Kéo thả file hoặc click để chọn</p>
                        <p class="text-[10px] text-gray-400 mt-1">Chấp nhận .xlsx, .xls, .csv</p>
                    </div>
                </div>

                <div class="flex space-x-3 pt-8 mt-4">
                    <button type="button" onclick="closeImportModal()" class="flex-grow py-3 text-slate-600 font-bold uppercase text-xs tracking-widest rounded-xl transition" style="background-color: #F1F5F9; color: #475569;" onmouseover="this.style.backgroundColor='#E2E8F0'" onmouseout="this.style.backgroundColor='#F1F5F9'">Hủy</button>
                    <button type="submit" class="flex-grow py-3 text-white font-bold uppercase text-xs tracking-widest rounded-xl shadow-lg transition" style="background-color: #0066FF;" onmouseover="this.style.backgroundColor='#0050CC'" onmouseout="this.style.backgroundColor='#0066FF'">Upload nạp danh mục</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('modal').classList.add('flex');
            document.getElementById('modal-title').innerText = 'Thêm Trường mới';
            document.getElementById('old_ma').value = '';
            document.getElementById('ma_truong').value = '';
            document.getElementById('ten_truong').value = '';
            document.getElementById('khu_vuc').value = 'KV2-NT';
        }
        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
            document.getElementById('modal').classList.remove('flex');
        }
        function editSchool(s) {
            openModal();
            document.getElementById('modal-title').innerText = 'Sửa Trường THPT';
            document.getElementById('old_ma').value = s.ma_truong;
            document.getElementById('ma_truong').value = s.ma_truong;
            document.getElementById('ten_truong').value = s.ten_truong;
            document.getElementById('khu_vuc').value = s.khu_vuc;
            document.getElementById('ma_tinh').value = s.ma_tinh;
        }

        // New Import Modal Logic
        function openImportModal() {
            document.getElementById('import-modal').classList.remove('hidden');
            document.getElementById('import-modal').classList.add('flex');
        }
        function closeImportModal() {
            document.getElementById('import-modal').classList.add('hidden');
            document.getElementById('import-modal').classList.remove('flex');
        }

        // Show selected file name in import modal
        const fileInput = document.getElementById('file-input');
        const fileLabel = document.getElementById('file-label');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files.length > 0) {
                    fileLabel.innerText = this.files[0].name;
                    fileLabel.style.color = '#0066FF';
                }
            });
        }

        // Single Delete Helper
        function deleteSingleSchool(id) {
            if (confirm('Bạn có chắc chắn muốn xóa trường THPT này? Cảnh báo: Hệ thống sẽ ngăn chặn nếu có thí sinh đang liên kết.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= url('/admin/master-data/schools/actions') ?>';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= (string) $this->csrfToken() ?>';
                form.appendChild(csrfInput);
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'bulk_delete';
                form.appendChild(actionInput);
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'ids[]';
                idInput.value = id;
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Bulk Delete Logic
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const bulkActions = document.getElementById('bulk-actions');
        const selectedCount = document.getElementById('selected-count');

        function updateBulkActions() {
            const checked = document.querySelectorAll('.item-checkbox:checked');
            if (checked.length > 0) {
                bulkActions.classList.remove('hidden');
                selectedCount.innerText = checked.length;
            } else {
                bulkActions.classList.add('hidden');
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateBulkActions();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkActions);
        });
    </script>
<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>

