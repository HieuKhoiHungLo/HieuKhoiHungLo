<?php
// results.php - Danh sách kết quả trúng tuyển (SSP + All 3 Phases)
$title = "Danh sách Trúng tuyển";
ob_start();

$totalCandidates = $stats['total_candidates'] ?? 0;
$totalWishes = $stats['total_wishes'] ?? 0;
$totalAdmitted = $stats['total_admitted'] ?? 0;
$nv1 = $stats['nv1_admit'] ?? 0;
$nv2 = $stats['nv2_admit'] ?? 0;
$nv3 = $stats['nv3_admit'] ?? 0;
$others = $totalAdmitted - ($nv1 + $nv2 + $nv3);
$admitRate = $totalCandidates > 0 ? round(($totalAdmitted / $totalCandidates) * 100, 1) : 0;

$totalChiTieu = 0;
$totalSoTrungTuyen = 0;
$underQuota = 0;
foreach ($majorStats as $ms) {
    $totalChiTieu += intval($ms['chi_tieu'] ?? 0);
    $totalSoTrungTuyen += intval($ms['so_trung_tuyen'] ?? 0);
    if (intval($ms['so_trung_tuyen'] ?? 0) < intval($ms['chi_tieu'] ?? 0) && intval($ms['chi_tieu'] ?? 0) > 0) {
        $underQuota++;
    }
}

$sessionId = $activeSession['id'] ?? 0;
$baseUrl = url('/admin/admission/results');
$isSessionActive = !empty($activeSession) && !empty($activeSession['kich_hoat']);
?>

<!-- Assets -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js" async></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
    .premium-table {
        border-collapse: separate !important;
        border-spacing: 0;
        width: 100%;
        table-layout: auto;
    }
    .premium-table th, .premium-table td {
        padding: 0.75rem 1rem;
        border: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        border-right: 1px solid #e2e8f0 !important;
        vertical-align: middle;
        font-size: 11px;
        color: #334155;
        background-clip: padding-box;
    }
    .premium-table th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        text-align: left;
    }
    .premium-table th:first-child, .premium-table td:first-child { border-left: 1px solid #e2e8f0 !important; }
    .premium-table thead tr:first-child th { border-top: 1px solid #e2e8f0 !important; }
    .premium-table thead tr:first-child th:first-child { border-top-left-radius: 1rem; }
    .premium-table thead tr:first-child th:last-child { border-top-right-radius: 1rem; }
    .premium-table tbody tr:last-child td:first-child { border-bottom-left-radius: 1rem; }
    .premium-table tbody tr:last-child td:last-child { border-bottom-right-radius: 1rem; }
</style>

<div class="h-full flex flex-col p-4 lg:p-6 bg-slate-50/50" id="resultsApp" x-data="{ activeTab: 'list', initCharts() { setTimeout(() => { renderAdmissionCharts(); }, 100); } }" x-init="$watch('activeTab', value => { if(value === 'charts') initCharts() })">

    <!-- Header Row (Title & Filters) -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-file-invoice text-indigo-600"></i> Danh Sách Trúng Tuyển
            </h1>
        </div>
        
        <!-- Year & Session Dropdowns -->
        <div class="flex items-center gap-2 w-full md:w-auto">
            <!-- Session Selector -->
            <div class="relative flex-1 md:flex-none md:min-w-[220px]">
                <select id="sessionSelector" onchange="changeSession(this.value)"
                    class="w-full border border-slate-300 rounded-lg text-sm bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 text-slate-700 outline-none appearance-none cursor-pointer">
                    <?php foreach ($allSessions as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($s['id'] == $sessionId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['ten_dot'] ?? ('Đợt #' . $s['id'])) ?> (<?= $s['nam_tuyen_sinh'] ?? '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
            </div>

        </div>
    </div>

    <!-- Action Toolbar (clean design like virtual filter) -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-6 shadow-sm flex flex-wrap justify-between items-center gap-4">
        <!-- Left: Action buttons -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- 1. Send Bulk Email Button -->
            <button onclick="bulkEmailSelected()" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-2">
                <i class="fas fa-paper-plane"></i>
                <span>Gửi Email Đã Chọn</span>
            </button>

            <!-- 2. Sync from Virtual Filter Button -->
            <form action="<?= url('/admin/admission/results/sync-virtual') ?>" method="POST" class="flex items-center" onsubmit="return confirm('Bạn có chắc chắn muốn XÓA kết quả hiện tại và ĐỒNG BỘ lại toàn bộ từ Lọc Ảo đợt này? Hành động này không thể hoàn tác.');">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-2">
                    <i class="fas fa-sync-alt"></i>
                    <span>Đồng bộ từ Lọc Ảo</span>
                </button>
            </form>

            <!-- 3. Upload Results Excel Form Button -->
            <form action="<?= url('/admin/admission/results/import') ?>" method="POST" enctype="multipart/form-data" class="flex items-center gap-2" id="importForm">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                <label class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-2 cursor-pointer">
                    <i class="fas fa-file-import"></i>
                    <span>Upload Kết Quả (Excel)</span>
                    <input type="file" name="excel_file" class="hidden" accept=".xls,.xlsx" onchange="document.getElementById('importForm').submit();">
                </label>
            </form>

            <!-- 4. Nút Công bố / Hủy công bố -->
            <form action="<?= url('/admin/admission/results/toggle-publish') ?>" method="POST" class="flex items-center">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                <input type="hidden" name="status" value="<?= empty($activeSession['is_published_results']) ? '1' : '0' ?>">
                <button type="submit" class="<?= empty($activeSession['is_published_results']) ? 'bg-teal-600 hover:bg-teal-700 text-white' : 'bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200' ?> px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-2" onclick="return confirm('<?= empty($activeSession['is_published_results']) ? 'Bạn có chắc chắn muốn CÔNG BỐ kết quả xét tuyển đợt này lên cổng thông tin cho thí sinh tra cứu không?' : 'Bạn có chắc chắn muốn HỦY CÔNG BỐ kết quả đợt này không?' ?>');">
                    <i class="fas <?= empty($activeSession['is_published_results']) ? 'fa-bullhorn' : 'fa-eye-slash' ?>"></i>
                    <span>
                        <?= empty($activeSession['is_published_results']) ? 'Công bố kết quả' : 'Hủy công bố' ?>
                    </span>
                </button>
            </form>
            
            <!-- 5. Soạn Giấy Báo Trúng Tuyển -->
            <button type="button" onclick="openTemplateModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-2">
                <i class="fas fa-edit"></i>
                <span>Mẫu Giấy Báo</span>
            </button>
        </div>

        <!-- Right: Destructive clear action -->
        <div>
            <form action="<?= url('/admin/admission/results/clear') ?>" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa TOÀN BỘ kết quả của đợt này?');">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-2">
                    <i class="fas fa-trash-alt"></i>
                    <span>Xóa Đợt Này</span>
                </button>
            </form>
        </div>
    </div>

    <?php if (!$isSessionActive && $sessionId > 0): ?>
    <!-- Session Status Banner -->
    <div class="mb-4 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-3">
        <i class="fas fa-exclamation-triangle text-amber-500"></i>
        <div class="flex-1">
            <span class="text-xs font-bold text-amber-800">Đang xem kết quả của đợt <strong><?= htmlspecialchars($activeSession['ten_dot'] ?? '') ?></strong> (<?= $activeSession['nam_tuyen_sinh'] ?? '' ?>).</span>
            <span class="text-xs text-amber-600 ml-1">Đợt này không còn là đợt tuyển sinh đang hoạt động. Dữ liệu hiển thị là kết quả lọc ảo gần nhất.</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl mb-6 overflow-x-auto no-scrollbar whitespace-nowrap">
        <button @click="activeTab = 'list'"
            :class="activeTab === 'list' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'"
            class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs transition duration-200 uppercase tracking-wider">
            <i class="fas fa-list-ul mr-2"></i>DANH SÁCH TRÚNG TUYỂN
        </button>
        <button @click="activeTab = 'stats'"
            :class="activeTab === 'stats' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'"
            class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs transition duration-200 uppercase tracking-wider">
            <i class="fas fa-chart-bar mr-2"></i>THỐNG KÊ TRÚNG TUYỂN
        </button>
        <button @click="activeTab = 'charts'; initCharts();"
            :class="activeTab === 'charts' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'"
            class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs transition duration-200 uppercase tracking-wider">
            <i class="fas fa-chart-pie mr-2"></i>BIỂU ĐỒ PHÂN TÍCH
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Candidates -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group">
            <div class="absolute -right-3 -top-3 w-16 h-16 bg-blue-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Thí sinh</p>
            <h3 class="text-2xl font-black text-slate-800"><?= number_format($totalCandidates) ?></h3>
            <span class="text-[10px] text-blue-500 font-bold"><?= number_format($totalWishes) ?> nguyện vọng</span>
        </div>

        <!-- Card 2: Admitted -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-emerald-100 relative overflow-hidden group">
            <div class="absolute -right-3 -top-3 w-16 h-16 bg-emerald-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
            <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-2">Trúng tuyển</p>
            <h3 class="text-2xl font-black text-emerald-700"><?= number_format($totalAdmitted) ?></h3>
            <span class="text-[10px] text-emerald-500 font-bold"><?= $admitRate ?>% tỉ lệ đạt</span>
        </div>

        <!-- Card 3: NV1 -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group">
            <div class="absolute -right-3 -top-3 w-16 h-16 bg-indigo-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
            <p class="text-[9px] font-black text-indigo-600 uppercase tracking-widest mb-2">NV1</p>
            <h3 class="text-2xl font-black text-indigo-700"><?= number_format($nv1) ?></h3>
            <span class="text-[10px] text-slate-400 font-bold">NV2: <?= $nv2 ?> · NV3: <?= $nv3 ?></span>
        </div>

        <!-- Card 4: Quota Progress -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group">
            <div class="absolute -right-3 -top-3 w-16 h-16 bg-amber-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
            <p class="text-[9px] font-black text-amber-600 uppercase tracking-widest mb-2">Chỉ tiêu</p>
            <h3 class="text-2xl font-black text-amber-700"><?= number_format($totalSoTrungTuyen) ?><span class="text-sm text-slate-400">/<?= number_format($totalChiTieu) ?></span></h3>
            <?php $quotaPct = $totalChiTieu > 0 ? round(($totalSoTrungTuyen / $totalChiTieu) * 100) : 0; ?>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1.5">
                <div class="bg-amber-500 h-1.5 rounded-full transition-all" style="width: <?= min($quotaPct, 100) ?>%"></div>
            </div>
        </div>

        <!-- Card 5: Under Quota Warning -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border <?= $underQuota > 0 ? 'border-rose-200' : 'border-slate-200' ?> relative overflow-hidden group">
            <div class="absolute -right-3 -top-3 w-16 h-16 <?= $underQuota > 0 ? 'bg-rose-50' : 'bg-slate-50' ?> rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
            <p class="text-[9px] font-black <?= $underQuota > 0 ? 'text-rose-600' : 'text-slate-400' ?> uppercase tracking-widest mb-2">Chưa đủ CT</p>
            <h3 class="text-2xl font-black <?= $underQuota > 0 ? 'text-rose-700' : 'text-slate-800' ?>"><?= $underQuota ?></h3>
            <span class="text-[10px] <?= $underQuota > 0 ? 'text-rose-500' : 'text-slate-400' ?> font-bold"><?= $underQuota > 0 ? 'ngành cần bổ sung' : 'Đủ chỉ tiêu ✓' ?></span>
        </div>
    </div>

    <!-- TAB: LIST (Danh sách trúng tuyển) -->
    <div x-show="activeTab === 'list'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" class="flex flex-col flex-1" style="display: none;" x-init="$el.style.display = 'flex'">

    <!-- Filter & Search Toolbar -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 mb-4 flex flex-col lg:flex-row items-center gap-3">
        <!-- Search -->
        <div class="relative flex-grow w-full lg:w-auto">
            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="fas fa-search text-xs"></i></div>
            <input type="text" id="searchInput" placeholder="Tìm CCCD, họ tên, mã ngành, tên ngành..."
                class="w-full bg-slate-50 border-none rounded-xl pl-10 pr-4 py-3 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-400 placeholder:text-slate-300">
        </div>

        <!-- Major Filter -->
        <div class="relative w-full lg:w-56">
            <select id="majorFilter" onchange="reloadTable()"
                class="w-full bg-slate-50 border-none rounded-xl pl-3 pr-8 py-3 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-400 appearance-none">
                <option value="">Tất cả ngành</option>
                <?php foreach ($majors as $m): ?>
                    <option value="<?= $m['ma_nganh'] ?>" <?= ($filterMajor == $m['ma_nganh']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['ma_nganh'] . ' - ' . $m['ten_nganh']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-[9px] pointer-events-none"></i>
        </div>

        <!-- Status Filter -->
        <div class="relative w-full lg:w-44">
            <select id="statusFilter" onchange="reloadTable()"
                class="w-full bg-slate-50 border-none rounded-xl pl-3 pr-8 py-3 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-400 appearance-none">
                <option value="" <?= ($filterStatus == '') ? 'selected' : '' ?>>Chỉ trúng tuyển</option>
                <option value="Trung tuyen" <?= ($filterStatus == 'Trung tuyen') ? 'selected' : '' ?>>Trúng tuyển</option>
                <option value="Truot" <?= ($filterStatus == 'Truot') ? 'selected' : '' ?>>Không đạt</option>
            </select>
            <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-[9px] pointer-events-none"></i>
        </div>

        <!-- Show All -->
        <label class="flex items-center gap-2 px-3 py-2 cursor-pointer select-none whitespace-nowrap">
            <input type="checkbox" id="showAllCheck" <?= $showAll ? 'checked' : '' ?> onchange="reloadTable()"
                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-[10px] font-black text-slate-500 uppercase">Hiện tất cả</span>
        </label>

        <!-- Select All for Bulk -->
        <label class="flex items-center gap-2 px-3 py-2 cursor-pointer select-none whitespace-nowrap border-l border-slate-100">
            <input type="checkbox" id="selectAllCheck" onchange="toggleSelectAll(this.checked)"
                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-[10px] font-black text-slate-500 uppercase">Chọn tất cả</span>
        </label>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/40 border border-slate-200 overflow-hidden flex flex-col flex-1">
        <!-- Table info bar -->
        <div class="px-4 py-2.5 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
            <span id="tableInfo" class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Đang tải...</span>
            <div class="flex items-center gap-2">
                <span id="selectedCount" class="text-[10px] font-bold text-indigo-600 hidden">0 đã chọn</span>
            </div>
        </div>

        <div class="overflow-x-auto flex-1">
            <table class="premium-table min-w-full text-left whitespace-nowrap" id="resultsTable">
                <thead>
                    <tr>
                        <th class="text-center w-8">
                            <input type="checkbox" id="selectAllInline" onchange="toggleSelectAll(this.checked)" class="w-3.5 h-3.5">
                        </th>
                        <th class="text-center w-10">STT</th>
                        <th>Mã ngành</th>
                        <th>Tên ngành</th>
                        <th>CCCD</th>
                        <th>Họ và Tên</th>
                        <th class="text-center">KV</th>
                        <th class="text-center">ĐT UT</th>
                        <th>Tổ hợp</th>
                        <th>Phương thức</th>
                        <th class="text-center">M1</th>
                        <th class="text-center">M2</th>
                        <th class="text-center">M3</th>
                        <th class="text-center">UTQ</th>
                        <th class="text-center">Điểm XT</th>
                        <th class="text-center w-20">Kết quả</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="text-[11px]">
                    <tr><td colspan="19" class="py-16 text-center border-b border-slate-100"><i class="fas fa-spinner fa-spin text-slate-300 text-2xl"></i></td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-2 bg-white border-t border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs text-gray-500">
                Hiện
                <select id="pageLengthSelect" onchange="reloadTable()"
                    class="border border-slate-300 rounded px-2 py-1 text-xs text-gray-700 bg-white">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                / trang
            </div>
            <div id="paginationControls" class="flex items-center gap-1"></div>
        </div>
        </div>
    </div> <!-- END TAB LIST -->
    
    <!-- TAB: STATS (Thống kê trúng tuyển) -->
    <div x-show="activeTab === 'stats'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" style="display: none;" class="space-y-6">
        <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
            <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs lg:text-sm flex items-center mb-6">
                <span class="w-1.5 h-4 bg-emerald-500 rounded-full mr-2"></span>
                Thống kê kết quả trúng tuyển theo ngành đợt tuyển sinh
            </h3>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="premium-table min-w-[800px] lg:min-w-full">
                    <thead>
                        <tr>
                            <th style="width: 80px" class="text-center" rowspan="2">Mã ngành</th>
                            <th rowspan="2">Tên ngành</th>
                            <th style="width: 80px" class="text-center" rowspan="2">Chỉ tiêu</th>
                            <th class="text-center" colspan="3">Trúng tuyển</th>
                            <th style="width: 150px" class="text-center" rowspan="2">Mức điểm (Thấp-Cao)</th>
                        </tr>
                        <tr>
                            <th style="width: 80px" class="text-center">Tổng</th>
                            <th style="width: 80px" class="text-center">NV1</th>
                            <th style="width: 100px" class="text-center">Tiến độ (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalCT = 0; $totalTT = 0; $totalHNV1 = 0;
                        $sortedMajorStats = $majorStats;
                        usort($sortedMajorStats, function($a, $b) {
                            $pctA = intval($a['chi_tieu'] ?? 0) > 0 ? (intval($a['so_trung_tuyen'] ?? 0) / intval($a['chi_tieu'])) : 0;
                            $pctB = intval($b['chi_tieu'] ?? 0) > 0 ? (intval($b['so_trung_tuyen'] ?? 0) / intval($b['chi_tieu'])) : 0;
                            return $pctB <=> $pctA;
                        });
                        foreach ($sortedMajorStats as $ms): 
                            $ct = intval($ms['chi_tieu'] ?? 0);
                            $tt = intval($ms['so_trung_tuyen'] ?? 0);
                            $hnv1 = intval($ms['nv1_admit'] ?? 0); 
                            
                            $totalCT += $ct; $totalTT += $tt; $totalHNV1 += $hnv1;
                            
                            $pct = $ct > 0 ? round(($tt / $ct) * 100, 1) : 0;
                            $barColor = '';
                            if ($pct >= 100) $barColor = 'bg-emerald-500';
                            elseif ($pct >= 80) $barColor = 'bg-indigo-500';
                            else $barColor = 'bg-amber-500';
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="text-center font-mono text-slate-500 font-bold"><?= $ms['ma_nganh'] ?></td>
                            <td class="font-bold text-slate-800"><?= htmlspecialchars($ms['ten_nganh'] ?? '') ?></td>
                            <td class="text-center font-bold text-slate-600 bg-slate-50/50"><?= $ct ?: '-' ?></td>
                            <td class="text-center font-black text-indigo-600"><?= $tt ?: '-' ?></td>
                            <td class="text-center font-bold text-slate-500"><?= isset($ms['nv1_admit']) ? $hnv1 : '-' ?></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full <?= $barColor ?> rounded-full" style="width: <?= min($pct, 100) ?>%"></div>
                                    </div>
                                    <span class="text-[10px] font-black w-8 text-right <?= $pct >= 100 ? 'text-emerald-600' : 'text-slate-500' ?>"><?= $pct ?>%</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if (isset($ms['diem_thap_nhat']) && floatval($ms['diem_thap_nhat']) > 0): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-slate-50 border border-slate-100 text-[10px] font-bold text-slate-600">
                                        <span class="text-rose-500"><?= number_format($ms['diem_thap_nhat'], 2) ?></span>
                                        <span class="text-slate-300">-</span>
                                        <span class="text-emerald-600"><?= number_format($ms['diem_cao_nhat'], 2) ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-slate-50 font-bold text-slate-800 border-t-2 border-slate-200">
                        <tr>
                            <td colspan="2" class="text-right uppercase">Tổng cộng:</td>
                            <td class="text-center bg-slate-100/50 text-slate-700"><?= $totalCT ?></td>
                            <td class="text-center text-indigo-700"><?= $totalTT ?></td>
                            <td class="text-center text-slate-700"><?= $totalHNV1 > 0 ? $totalHNV1 : '-' ?></td>
                            <td>
                                <?php $totalPct = $totalCT > 0 ? round(($totalTT / $totalCT) * 100, 1) : 0; ?>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500 rounded-full" style="width: <?= min($totalPct, 100) ?>%"></div>
                                    </div>
                                    <span class="text-[10px] font-black w-8 text-right text-indigo-600"><?= $totalPct ?>%</span>
                                </div>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div> <!-- END TAB STATS -->

    <!-- TAB: CHARTS (Biểu đồ phân tích) -->
    <div x-show="activeTab === 'charts'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" style="display: none;" class="space-y-6">
        
        <!-- Visit Stats Table -->
        <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100 border-t-4 border-t-blue-500 mb-6">
            <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                <i class="fas fa-eye text-blue-500 mr-2"></i> Thống kê truy cập trang Tính điểm (http://localhost/TS/tinh-diem-xet-tuyen)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex flex-col items-center justify-center">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Tổng số lượt truy cập</span>
                    <span class="text-3xl font-black text-blue-600"><?= number_format($visitStats['total_visits'] ?? 0) ?></span>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex flex-col items-center justify-center">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Số lượt trong tuần</span>
                    <span class="text-3xl font-black text-indigo-600"><?= number_format($visitStats['weekly_visits'] ?? 0) ?></span>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex flex-col items-center justify-center">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Số lượt trong ngày</span>
                    <span class="text-3xl font-black text-emerald-600"><?= number_format($visitStats['daily_visits'] ?? 0) ?></span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Chart: Tỷ lệ theo nguyện vọng -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100 lg:col-span-1 border-t-4 border-t-indigo-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Phân bố trúng tuyển theo NV
                </h3>
                <div class="relative h-64">
                    <canvas id="nvChart"></canvas>
                </div>
            </div>

            <!-- Chart: Top Ngành Trúng Tuyển -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100 lg:col-span-2 border-t-4 border-t-emerald-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Biểu đồ tỷ lệ lấp đầy chuyên ngành (Top 15)
                </h3>
                <div class="relative h-64">
                    <canvas id="majorFillChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
            <!-- Chart: Giới tính -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100 border-t-4 border-t-pink-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Phân bố Giới tính
                </h3>
                <div class="relative h-64">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>

            <!-- Chart: Khu vực ưu tiên -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100 border-t-4 border-t-sky-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Khu vực ưu tiên
                </h3>
                <div class="relative h-64">
                    <canvas id="areaChart"></canvas>
                </div>
            </div>

            <!-- Chart: Đối tượng ưu tiên -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100 border-t-4 border-t-amber-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Đối tượng ưu tiên
                </h3>
                <div class="relative h-64">
                    <canvas id="objectChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <!-- Chart: Theo tỉnh (Top 10) -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100 border-t-4 border-t-purple-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Tỉnh / Thành phố (Top 10)
                </h3>
                <div class="relative h-64">
                    <canvas id="provinceChart"></canvas>
                </div>
            </div>

            <!-- Chart: Trường THPT (Top 10) -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100 border-t-4 border-t-rose-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Trường THPT (Top 10)
                </h3>
                <div class="relative h-64">
                    <canvas id="schoolChart"></canvas>
                </div>
            </div>
        </div>
    </div> <!-- END TAB CHARTS -->
</div>

<script>
    // Embed data for charts
    const chartData = {
        nv: {
            nv1: <?= intval($stats['nv1_admit'] ?? 0) ?>,
            nv2: <?= intval($stats['nv2_admit'] ?? 0) ?>,
            nv3: <?= intval($stats['nv3_admit'] ?? 0) ?>,
            other: <?= max(0, intval($stats['total_admitted'] ?? 0) - (intval($stats['nv1_admit'] ?? 0) + intval($stats['nv2_admit'] ?? 0) + intval($stats['nv3_admit'] ?? 0))) ?>
        },
        majors: <?= json_encode(array_values($majorStats)) ?>,
        demo: <?= isset($chartDist) ? json_encode($chartDist) : '{}' ?>
    };

    let chartsRendered = false;
    let nvChartInst = null;
    let majorFillChartInst = null;

    function renderAdmissionCharts() {
        if (chartsRendered || typeof Chart === 'undefined') return;
        
        // 1. NGUYEN VONG CHART
        const ctxNv = document.getElementById('nvChart');
        if (ctxNv) {
            nvChartInst = new Chart(ctxNv.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['NV1', 'NV2', 'NV3', 'Khác'],
                    datasets: [{
                        data: [chartData.nv.nv1, chartData.nv.nv2, chartData.nv.nv3, chartData.nv.other],
                        backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#94a3b8'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
                    }
                }
            });
        }

        // 2. MAJOR FILL CHART
        const ctxMajor = document.getElementById('majorFillChart');
        if (ctxMajor) {
            // Sort to get top filled
            const sortedMajors = [...chartData.majors].sort((a,b) => {
                const aPct = (a.chi_tieu > 0) ? (a.so_trung_tuyen / a.chi_tieu) : 0;
                const bPct = (b.chi_tieu > 0) ? (b.so_trung_tuyen / b.chi_tieu) : 0;
                return bPct - aPct;
            }).slice(0, 15); // limit 15
            
            majorFillChartInst = new Chart(ctxMajor.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: sortedMajors.map(m => m.ma_nganh),
                    datasets: [
                        {
                            label: 'Số Trúng tuyển',
                            data: sortedMajors.map(m => m.so_trung_tuyen),
                            backgroundColor: '#4f46e5',
                            borderRadius: 4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Chỉ tiêu',
                            data: sortedMajors.map(m => m.chi_tieu),
                            backgroundColor: '#cbd5e1',
                            borderRadius: 4,
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: (ctx) => {
                                    const index = ctx[0].dataIndex;
                                    return sortedMajors[index].ten_nganh;
                                }
                            }
                        },
                        legend: { position: 'top', labels: { boxWidth: 12, font: { size: 10 } } }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 45, minRotation: 45 } },
                        y: { beginAtZero: true }
                    }
                }
            });
        }
        
        // 3. GENDER CHART
        const ctxGender = document.getElementById('genderChart');
        if (ctxGender && chartData.demo.gender) {
            new Chart(ctxGender.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: Object.keys(chartData.demo.gender),
                    datasets: [{
                        data: Object.values(chartData.demo.gender),
                        backgroundColor: ['#ec4899', '#3b82f6', '#94a3b8'],
                        borderWidth: 0,
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
            });
        }

        // 4. AREA CHART
        const ctxArea = document.getElementById('areaChart');
        if (ctxArea && chartData.demo.area) {
            new Chart(ctxArea.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(chartData.demo.area),
                    datasets: [{
                        data: Object.values(chartData.demo.area),
                        backgroundColor: ['#0ea5e9', '#f59e0b', '#8b5cf6', '#10b981', '#64748b'],
                        borderWidth: 0,
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '50%', plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } } } }
            });
        }

        // 5. OBJECT CHART
        const ctxObject = document.getElementById('objectChart');
        if (ctxObject && chartData.demo.object) {
            new Chart(ctxObject.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: Object.keys(chartData.demo.object),
                    datasets: [{
                        label: 'Số lượng',
                        data: Object.values(chartData.demo.object),
                        backgroundColor: '#f59e0b',
                        borderRadius: 4,
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
        }

        // 6. PROVINCE CHART
        const ctxProv = document.getElementById('provinceChart');
        if (ctxProv && chartData.demo.province) {
            const topProvKeys = Object.keys(chartData.demo.province).slice(0, 10);
            const topProvVals = Object.values(chartData.demo.province).slice(0, 10);
            new Chart(ctxProv.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: topProvKeys,
                    datasets: [{
                        label: 'Số lượng',
                        data: topProvVals,
                        backgroundColor: '#a855f7',
                        borderRadius: 4,
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { maxRotation: 45, minRotation: 45, font: { size: 9 } } }, y: { beginAtZero: true } } }
            });
        }

        // 7. SCHOOL CHART
        const ctxSchool = document.getElementById('schoolChart');
        if (ctxSchool && chartData.demo.school) {
            const topSchKeys = Object.keys(chartData.demo.school).slice(0, 10);
            const topSchVals = Object.values(chartData.demo.school).slice(0, 10);
            new Chart(ctxSchool.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: topSchKeys,
                    datasets: [{
                        label: 'Số lượng',
                        data: topSchVals,
                        backgroundColor: '#f43f5e',
                        borderRadius: 4,
                    }]
                },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { ticks: { font: { size: 9 } } }, x: { beginAtZero: true } } }
            });
        }
        
        chartsRendered = true;
    }
</script>

<script>
// State
let currentPage = 0;
let pageLength = 10;
let totalRecords = 0;
let filteredRecords = 0;
let drawCounter = 0;
let searchTimer = null;
let selectedIds = new Set();
const SESSION_ID = <?= $sessionId ?>;
const API_URL = '<?= url("/admin/admission/results/api") ?>';
const REVIEW_URL = '<?= url("/admin/review") ?>';
const BULK_EMAIL_URL = '<?= url("/admin/admission/results/bulk-email") ?>';
const CSRF_TOKEN = '<?= csrf_token() ?>';

// Init
document.addEventListener('DOMContentLoaded', function() {
    reloadTable();
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { currentPage = 0; reloadTable(); }, 350);
    });
});

function changeSession(sessionId) {
    window.location.href = '<?= $baseUrl ?>?session_id=' + sessionId;
}

function filterByMajor(code) {
    document.getElementById('majorFilter').value = code;
    currentPage = 0;
    reloadTable();
    // Scroll to table
    document.getElementById('resultsTable').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function toggleMajorStats() {
    const panel = document.getElementById('majorStatsPanel');
    const icon = document.getElementById('majorStatsIcon');
    panel.classList.toggle('hidden');
    icon.style.transform = panel.classList.contains('hidden') ? '' : 'rotate(180deg)';
}

function reloadTable() {
    drawCounter++;
    pageLength = parseInt(document.getElementById('pageLengthSelect').value) || 10;
    const search = document.getElementById('searchInput').value;
    const major = document.getElementById('majorFilter').value;
    const status = document.getElementById('statusFilter').value;
    const showAll = document.getElementById('showAllCheck').checked ? '1' : '0';
    const start = currentPage * pageLength;

    const params = new URLSearchParams({
        draw: drawCounter, start, length: pageLength, search,
        session_id: SESSION_ID, major, status, show_all: showAll
    });

    document.getElementById('tableInfo').textContent = 'Đang tải...';
    
    fetch(API_URL + '?' + params.toString())
        .then(r => r.json())
        .then(data => {
            totalRecords = data.recordsTotal;
            filteredRecords = data.recordsFiltered;
            renderTable(data.data, start);
            renderPagination();
            updateTableInfo(start);
        })
        .catch(err => {
            document.getElementById('tableBody').innerHTML = `<tr><td colspan="13" class="py-20 text-center text-rose-400 font-bold">Lỗi tải dữ liệu: ${err.message}</td></tr>`;
        });
}

function renderTable(rows, startIndex) {
    const tbody = document.getElementById('tableBody');
    if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="19" class="py-16 text-center border-b border-slate-100 text-slate-400 text-sm">Không có dữ liệu phù hợp</td></tr>`;
        return;
    }

    let html = '';
    rows.forEach((row, i) => {
        const isPass = row.is_pass;
        const details = row.chi_tiet_diem || {};
        const checked = selectedIds.has(row.id) ? 'checked' : '';
        const rowBg = isPass ? '' : 'bg-slate-50/50 text-slate-400';

        html += `<tr class="${rowBg} hover:bg-slate-50 transition-colors">
            <td class="text-center">
                <input type="checkbox" class="rowCheck w-3.5 h-3.5"
                    data-id="${row.id}" ${checked} onchange="toggleRowSelect(${row.id}, this.checked)">
            </td>
            <td class="text-center text-slate-400">${startIndex + i + 1}</td>
            <td class="font-mono font-bold">${row.ma_nganh}</td>
            <td class="max-w-[180px]">
                <span class="truncate block font-semibold text-slate-700" title="${escHtml(row.ten_nganh)}">${escHtml(row.ten_nganh)}</span>
            </td>
            <td class="font-mono text-slate-500">${row.so_cccd}</td>
            <td class="font-bold text-slate-800">${escHtml(row.ho_ten)}</td>
            <td class="text-center">${row.khu_vuc || '-'}</td>
            <td class="text-center">${row.doi_tuong || '-'}</td>
            <td>${escHtml(row.to_hop || '-')}</td>
            <td class="max-w-[120px]">
                <span class="truncate block text-slate-500 text-[10px]" title="${escHtml(row.phuong_thuc || '')}">${escHtml(row.phuong_thuc || '-')}</span>
            </td>
            <td class="text-center">${fmt3(row.diem_mon_1)}</td>
            <td class="text-center">${fmt3(row.diem_mon_2)}</td>
            <td class="text-center">${fmt3(row.diem_mon_3)}</td>
            <td class="text-center font-medium">${row.ut_quy_doi != null && parseFloat(row.ut_quy_doi) > 0 ? '+' + parseFloat(row.ut_quy_doi).toFixed(2) : '-'}</td>
            <td class="text-center font-black text-emerald-600 bg-emerald-50/20">${row.diem_xt != null ? parseFloat(row.diem_xt).toFixed(2) : '-'}</td>
            <td class="text-center">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">Đỗ</span>
            </td>
            <td>
                ${escHtml(row.ghi_chu || '')}
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function renderPagination() {
    const totalPages = Math.ceil(filteredRecords / pageLength);
    const container = document.getElementById('paginationControls');
    if (totalPages <= 1) { container.innerHTML = ''; return; }

    const btn = (label, page, active = false, disabled = false) => {
        if (disabled) return `<button disabled class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-300 cursor-not-allowed bg-slate-50">${label}</button>`;
        if (active)  return `<button class="px-3 py-1.5 text-xs border border-indigo-600 rounded-lg bg-indigo-600 text-white font-bold shadow-sm shadow-indigo-100">${label}</button>`;
        return `<button onclick="goPage(${page})" class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white text-slate-600 hover:bg-slate-50 transition-all font-semibold hover:border-slate-300">${label}</button>`;
    };

    let html = btn('«', currentPage - 1, false, currentPage === 0);
    html += btn('‹', currentPage - 1, false, currentPage === 0);

    const maxBtns = 5;
    let startP = Math.max(0, currentPage - Math.floor(maxBtns / 2));
    let endP = Math.min(totalPages, startP + maxBtns);
    if (endP - startP < maxBtns) startP = Math.max(0, endP - maxBtns);

    if (startP > 0) html += `<span class="px-1 text-gray-400 text-xs">…</span>`;
    for (let p = startP; p < endP; p++) html += btn(p + 1, p, p === currentPage);
    if (endP < totalPages) html += `<span class="px-1 text-gray-400 text-xs">…</span>`;

    html += btn('›', currentPage + 1, false, currentPage >= totalPages - 1);
    html += btn('»', totalPages - 1, false, currentPage >= totalPages - 1);

    container.innerHTML = html;
}

function goPage(p) {
    const totalPages = Math.ceil(filteredRecords / pageLength);
    if (p < 0 || p >= totalPages) return;
    currentPage = p;
    reloadTable();
}

function updateTableInfo(start) {
    const end = Math.min(start + pageLength, filteredRecords);
    let text = `Hiển thị ${filteredRecords > 0 ? start + 1 : 0}–${end} / ${filteredRecords.toLocaleString()} kết quả`;
    if (filteredRecords !== totalRecords) text += ` (lọc từ ${totalRecords.toLocaleString()})`;
    document.getElementById('tableInfo').textContent = text;
}

// Bulk selection
function toggleRowSelect(id, checked) {
    if (checked) selectedIds.add(id); else selectedIds.delete(id);
    updateSelectedCount();
}

function toggleSelectAll(checked) {
    document.querySelectorAll('.rowCheck').forEach(cb => {
        cb.checked = checked;
        const id = parseInt(cb.dataset.id);
        if (checked) selectedIds.add(id); else selectedIds.delete(id);
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const el = document.getElementById('selectedCount');
    if (selectedIds.size > 0) {
        el.textContent = `${selectedIds.size} đã chọn`;
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('hidden');
}

function bulkEmailSelected() {
    if (selectedIds.size === 0) { alert('Vui lòng chọn ít nhất 1 thí sinh.'); return; }
    
    // Set target count in email-modal (ID: 'email-target-count')
    const countEl = document.getElementById('email-target-count');
    if (countEl) countEl.innerText = selectedIds.size;
    
    // Clear/Reset modal input fields
    const tplSelect = document.getElementById('email-template-select');
    if (tplSelect) tplSelect.value = '';
    const subjectInput = document.getElementById('email-modal-subject');
    if (subjectInput) subjectInput.value = '';
    const editor = document.getElementById('email-editor');
    if (editor) editor.innerHTML = '';
    const noteEl = document.getElementById('email-modal-internal-note');
    if (noteEl) noteEl.value = '';
    
    // Show email modal
    const emailModal = document.getElementById('email-modal');
    if (emailModal) {
        emailModal.classList.remove('hidden');
    } else {
        alert('Không tìm thấy giao diện Email Modal.');
    }
}

function confirmSendEmail() {
    const templateId = document.getElementById('email-template-select').value;
    const subject = document.getElementById('email-modal-subject').value;
    const content = document.getElementById('email-editor').innerHTML;
    const internalNote = document.getElementById('email-modal-internal-note').value;
    
    if (!templateId && (!subject || !content || content.trim() === '')) {
        alert('Vui lòng nhập tiêu đề và nội dung hoặc chọn mẫu thư.');
        return;
    }
    
    // Close modal
    closeModal('email-modal');
    
    // Post to BULK_EMAIL_URL
    fetch(BULK_EMAIL_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ids=${JSON.stringify([...selectedIds])}&session_id=${SESSION_ID}&csrf_token=${CSRF_TOKEN}&template_id=${templateId}&email_subject=${encodeURIComponent(subject)}&email_content=${encodeURIComponent(content)}&internal_note=${encodeURIComponent(internalNote)}`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || 'Gửi email hoàn thành');
        selectedIds.clear();
        updateSelectedCount();
        reloadTable();
    })
    .catch(err => {
        alert('Lỗi: ' + err.message);
    });
}

// Helpers
function escHtml(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function fmt(v) { return v != null ? parseFloat(v).toFixed(2) : '-'; }
function fmt3(v) { return v != null ? parseFloat(v).toFixed(3) : '-'; }

// Template Modal Logic
let editorInstance = null;

function openTemplateModal() {
    const modal = document.getElementById('template-modal');
    // Move to body to avoid layout z-index issues
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    modal.classList.remove('hidden');
    
    // Hàm hỗ trợ tải CKEditor
    const initEditor = () => {
        if (!editorInstance) {
            if (typeof CKEDITOR !== 'undefined') {
                if (CKEDITOR.instances['template-editor']) {
                    CKEDITOR.instances['template-editor'].destroy(true);
                }
                editorInstance = CKEDITOR.replace('template-editor', {
                    height: 400,
                    allowedContent: true // Cho phép chèn HTML tuỳ ý để không mất CSS
                });
            } else {
                console.error("CKEDITOR is undefined.");
            }
        }
    };

    if (typeof CKEDITOR === 'undefined') {
        const btn = document.getElementById('template-modal').querySelector('button[onclick="saveTemplate()"]');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải trình soạn thảo...';
        btn.disabled = true;

        const script = document.createElement('script');
        // Load locally instead of CDN to avoid all firewall/adblock issues
        script.src = '<?= url("/ckeditor/ckeditor.js") ?>';
        script.onload = () => {
            btn.innerHTML = oldText;
            btn.disabled = false;
            setTimeout(initEditor, 100);
        };
        script.onerror = () => {
            btn.innerHTML = oldText;
            btn.disabled = false;
            console.error("Lỗi tải CKEditor từ Local. Sẽ sử dụng Textarea mặc định.");
            initEditor(); 
        };
        document.head.appendChild(script);
    } else {
        setTimeout(initEditor, 100);
    }
    
    // Fetch template
        fetch('<?= url("/admin/admission/results/get-template") ?>?session_id=' + SESSION_ID)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.template) {
                document.getElementById('template-subject').value = data.template.subject || '';
                if (editorInstance) {
                    editorInstance.setData(data.template.body || '');
                } else {
                    document.getElementById('template-editor').value = data.template.body || '';
                }
            }
        })
        .catch(err => console.error(err));
}

function insertTemplateVar(variable) {
    const editor = (typeof CKEDITOR !== 'undefined') ? CKEDITOR.instances['template-editor'] : editorInstance;
    if (editor) {
        editor.focus();
        editor.insertHtml(variable);
    } else {
        const textarea = document.getElementById('template-editor');
        if (textarea) textarea.value += variable;
    }
}

function saveTemplate() {
    const subject = document.getElementById('template-subject').value;
    const editor = (typeof CKEDITOR !== 'undefined') ? CKEDITOR.instances['template-editor'] : editorInstance;
    const body = editor ? editor.getData() : document.getElementById('template-editor').value;
    
    fetch('<?= url("/admin/admission/results/save-template") ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `session_id=${SESSION_ID}&subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Lưu mẫu giấy báo thành công!');
            closeModal('template-modal');
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(err => alert('Lỗi kết nối: ' + err.message));
}
</script>

<!-- Template Editor Modal -->
<div id="template-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal('template-modal')"></div>
    
    <!-- Modal Content -->
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col relative z-10 animate-fade-in-up">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-2xl">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-file-signature text-purple-500"></i> Mẫu Giấy Báo Trúng Tuyển
            </h3>
            <button type="button" onclick="closeModal('template-modal')" class="text-slate-400 hover:text-slate-600 transition-colors bg-white rounded-full p-1.5 shadow-sm hover:shadow">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <!-- Body -->
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Tiêu đề (Email / Thông báo)</label>
                <input type="text" id="template-subject" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Các biến (Click để chèn)</label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="insertTemplateVar('{{HOTEN}}')" class="px-2 py-1 bg-slate-100 border border-slate-200 rounded text-xs hover:bg-slate-200">Họ tên</button>
                    <button type="button" onclick="insertTemplateVar('{{CCCD}}')" class="px-2 py-1 bg-slate-100 border border-slate-200 rounded text-xs hover:bg-slate-200">CCCD</button>
                    <button type="button" onclick="insertTemplateVar('{{NGAYSINH}}')" class="px-2 py-1 bg-slate-100 border border-slate-200 rounded text-xs hover:bg-slate-200">Ngày sinh</button>
                    <button type="button" onclick="insertTemplateVar('{{SBD}}')" class="px-2 py-1 bg-slate-100 border border-slate-200 rounded text-xs hover:bg-slate-200">SBD</button>
                    <button type="button" onclick="insertTemplateVar('{{NGANH}}')" class="px-2 py-1 bg-slate-100 border border-slate-200 rounded text-xs hover:bg-slate-200">Ngành trúng tuyển</button>
                    <button type="button" onclick="insertTemplateVar('{{MANGANH}}')" class="px-2 py-1 bg-slate-100 border border-slate-200 rounded text-xs hover:bg-slate-200">Mã ngành</button>
                    <button type="button" onclick="insertTemplateVar('{{DIEMXT}}')" class="px-2 py-1 bg-slate-100 border border-slate-200 rounded text-xs hover:bg-slate-200">Điểm xét tuyển</button>
                    <button type="button" onclick="insertTemplateVar('{{QR_ThanhToan}}')" class="px-2 py-1 bg-blue-50 border border-blue-200 text-blue-700 rounded text-xs hover:bg-blue-100"><i class="fas fa-qrcode"></i> Mã QR Thanh Toán</button>
                    <button type="button" onclick="insertTemplateVar('{{QR_CCCD}}')" class="px-2 py-1 bg-purple-50 border border-purple-200 text-purple-700 rounded text-xs hover:bg-purple-100"><i class="fas fa-qrcode"></i> Mã QR CCCD</button>
                </div>
            </div>
            
            <div class="mb-2">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Nội dung Giấy báo (HTML)</label>
                <textarea id="template-editor" class="w-full border-slate-300 rounded-lg shadow-sm" rows="10"></textarea>
            </div>
            <p class="text-xs text-slate-500 italic">Bạn có thể chèn ảnh, bảng và thiết kế tự do bằng công cụ trình soạn thảo. Mẫu này sẽ hiển thị khi thí sinh tra cứu kết quả trực tuyến.</p>
        </div>
        
        <!-- Footer -->
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end gap-3 rounded-b-2xl">
            <button type="button" onclick="closeModal('template-modal')" class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors shadow-sm">
                Hủy bỏ
            </button>
            <button type="button" onclick="saveTemplate()" class="px-5 py-2.5 text-sm font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded-xl shadow-sm shadow-purple-600/20 transition-all active:scale-95 flex items-center gap-2">
                <i class="fas fa-save"></i>
                <span>Lưu thay đổi</span>
            </button>
        </div>
    </div>
</div>


<style>
.custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.font-black { font-weight: 900 !important; }
</style>

<?php
$content = ob_get_clean();
$layoutPath = realpath(__DIR__ . '/../../layouts/admin.php');
if ($layoutPath && file_exists($layoutPath)) {
    require_once $layoutPath;
} else {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head><meta charset="UTF-8"><title>Results Grid</title><script src="https://cdn.tailwindcss.com"></script></head>
    <body class="bg-slate-50"><?= $content ?></body>
    </html>
    <?php
}
?>
