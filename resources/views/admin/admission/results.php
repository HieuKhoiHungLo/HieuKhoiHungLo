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

    <!-- Header -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-gradient-to-br from-indigo-600 to-blue-700 rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-200">
                <i class="fas fa-file-invoice text-white text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Danh sách Trúng tuyển</h1>
                <p class="text-xs text-slate-400 font-medium mt-0.5">Kết quả xét tuyển chính thức — Server-side processing</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Session Selector -->
            <div class="relative">
                <select id="sessionSelector" onchange="changeSession(this.value)"
                    class="bg-white border border-slate-200 rounded-xl pl-3 pr-8 py-2.5 text-xs font-bold text-slate-700 shadow-sm appearance-none cursor-pointer focus:ring-2 focus:ring-indigo-400">
                    <?php foreach ($allSessions as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($s['id'] == $sessionId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['ten_dot'] ?? ('Đợt #' . $s['id'])) ?> (<?= $s['nam_tuyen_sinh'] ?? '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-[9px] pointer-events-none"></i>
            </div>

            <div class="flex bg-white p-1 rounded-xl shadow-sm border border-slate-200">
                <a href="<?= url('/admin/reports/export-all-admitted?session_id=' . $sessionId) ?>"
                   class="px-3 py-2 text-xs font-bold text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all flex items-center gap-1.5">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </a>
                <div class="w-px h-6 bg-slate-200 my-auto"></div>
                <button onclick="bulkEmailSelected()" 
                    class="px-3 py-2 text-xs font-bold text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all flex items-center gap-1.5">
                    <i class="fas fa-paper-plane"></i> Gửi Email đã chọn
                </button>
            </div>

            <a href="<?= url('/admin/admission/virtual-filter') ?>"
               class="bg-slate-800 hover:bg-black text-white px-4 py-2.5 rounded-xl font-bold shadow-lg transition-all flex items-center gap-2 text-xs">
                <i class="fas fa-filter"></i> Lọc Ảo
            </a>
        </div>
    </div>

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
        <div class="px-4 py-2 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
            <span id="tableInfo" class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Đang tải...</span>
            <div class="flex items-center gap-2">
                <span id="selectedCount" class="text-[10px] font-bold text-indigo-600 hidden">0 đã chọn</span>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar flex-1">
            <table class="w-full text-left border-collapse whitespace-nowrap" id="resultsTable">
                <thead>
                    <tr class="bg-slate-100/80 text-slate-500 uppercase tracking-[0.15em] text-[10px] font-black border-b border-slate-200 sticky top-0 z-10">
                        <th class="py-4 px-3 text-center w-10"><input type="checkbox" class="hidden"></th>
                        <th class="py-4 px-3 text-center w-12">STT</th>
                        <th class="py-4 px-4">Ngành</th>
                        <th class="py-4 px-3 text-center w-10">NV</th>
                        <th class="py-4 px-3 text-center w-12">NV Bộ</th>
                        <th class="py-4 px-4">CCCD</th>
                        <th class="py-4 px-4">Họ và Tên</th>
                        <th class="py-4 px-3">KV/ĐT</th>
                        <th class="py-4 px-4">Tổ hợp / PT</th>
                        <th class="py-4 px-4">Điểm chi tiết</th>
                        <th class="py-4 px-3 text-center">Điểm XT</th>
                        <th class="py-4 px-3 text-center">Trạng thái</th>
                        <th class="py-4 px-3 text-center w-10"></th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-slate-100 text-[11px]">
                    <tr><td colspan="13" class="py-20 text-center"><i class="fas fa-spinner fa-spin text-slate-300 text-2xl"></i></td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase">Hiện</span>
                <select id="pageLengthSelect" onchange="reloadTable()"
                    class="bg-white border border-slate-200 rounded-lg px-2 py-1 text-[10px] font-bold text-slate-600">
                    <option value="25">25</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
                <span class="text-[10px] font-bold text-slate-400 uppercase">/ trang</span>
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
let pageLength = 50;
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
    pageLength = parseInt(document.getElementById('pageLengthSelect').value) || 50;
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
        tbody.innerHTML = `<tr><td colspan="13" class="py-20 text-center">
            <i class="fas fa-inbox text-slate-200 text-4xl block mb-3"></i>
            <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">Không có dữ liệu phù hợp</p>
        </td></tr>`;
        return;
    }

    let html = '';
    rows.forEach((row, i) => {
        const isPass = row.is_pass;
        const details = row.chi_tiet_diem || {};
        const checked = selectedIds.has(row.id) ? 'checked' : '';
        const nhomLabels = {SuPham:'SP', SuPhamDacThu:'SPĐT', DieuDuong:'ĐD'};
        const nhomBadge = nhomLabels[row.nhom_nganh] || '';

        html += `<tr class="hover:bg-indigo-50/30 transition-all group ${isPass ? '' : 'opacity-60'}">
            <td class="py-3 px-3 text-center">
                <input type="checkbox" class="rowCheck w-3.5 h-3.5 rounded border-slate-300 text-indigo-600" 
                    data-id="${row.id}" ${checked} onchange="toggleRowSelect(${row.id}, this.checked)">
            </td>
            <td class="py-3 px-3 text-center text-slate-300 font-black">${startIndex + i + 1}</td>
            <td class="py-3 px-4">
                <span class="text-indigo-600 font-black text-[10px]">${row.ma_nganh}</span>
                <p class="text-[9px] text-slate-400 font-medium truncate max-w-[140px]" title="${escHtml(row.ten_nganh)}">${escHtml(row.ten_nganh)}</p>
                ${nhomBadge ? `<span class="px-1 py-0.5 text-[7px] font-black rounded bg-indigo-50 text-indigo-500 border border-indigo-100">${nhomBadge}</span>` : ''}
            </td>
            <td class="py-3 px-3 text-center">
                <span class="w-6 h-6 inline-flex items-center justify-center rounded-md bg-slate-100 text-slate-600 font-black text-[10px] border border-slate-200">${row.thu_tu_nguyen_vong || '-'}</span>
            </td>
            <td class="py-3 px-3 text-center">
                ${row.thu_tu_nv_bo ? `<span class="w-6 h-6 inline-flex items-center justify-center rounded-md bg-indigo-50 text-indigo-600 font-black text-[10px] border border-indigo-100">${row.thu_tu_nv_bo}</span>` : '<span class="text-slate-300">-</span>'}
            </td>
            <td class="py-3 px-4">
                <a href="${REVIEW_URL}?cccd=${row.so_cccd}&tab=wishes" target="_blank" 
                   class="font-mono text-slate-600 font-bold hover:text-indigo-600 hover:underline transition-colors">${row.so_cccd}</a>
            </td>
            <td class="py-3 px-4">
                <a href="${REVIEW_URL}?cccd=${row.so_cccd}" target="_blank"
                   class="font-black text-slate-800 uppercase text-[10px] tracking-tight group-hover:text-indigo-600 transition-colors">${escHtml(row.ho_va_ten)}</a>
            </td>
            <td class="py-3 px-3">
                <div class="flex gap-1">
                    ${row.khu_vuc_uu_tien ? `<span class="px-1.5 py-0.5 text-[8px] font-black rounded bg-sky-50 text-sky-600 border border-sky-100">${row.khu_vuc_uu_tien}</span>` : ''}
                    ${row.doi_tuong_uu_tien ? `<span class="px-1.5 py-0.5 text-[8px] font-black rounded bg-amber-50 text-amber-600 border border-amber-100">${row.doi_tuong_uu_tien}</span>` : ''}
                </div>
            </td>
            <td class="py-3 px-4">
                <span class="font-black text-slate-700 text-[10px]">${escHtml(row.to_hop_toi_uu || '-')}</span>
                <p class="text-[8px] font-bold text-slate-400 uppercase">${escHtml(row.phuong_thuc_toi_uu || row.phuong_thuc_xet_tuyen || '')}</p>
            </td>
            <td class="py-3 px-4">
                <div class="flex items-center gap-1 font-bold">
                    <span class="w-8 text-center py-0.5 bg-slate-50 rounded text-slate-500 text-[10px]">${fmt(row.diem_mon_1)}</span>
                    <span class="w-8 text-center py-0.5 bg-slate-50 rounded text-slate-500 text-[10px]">${fmt(row.diem_mon_2)}</span>
                    <span class="w-8 text-center py-0.5 bg-slate-50 rounded text-slate-500 text-[10px]">${fmt(row.diem_mon_3)}</span>
                    ${(details.priority_raw || 0) > 0 ? `<span class="px-1 py-0.5 bg-amber-50 text-amber-600 rounded text-[8px] font-black border border-amber-100">+${fmt(details.priority_converted || 0)}</span>` : ''}
                </div>
            </td>
            <td class="py-3 px-3 text-center">
                <span class="text-base font-black ${isPass ? 'text-indigo-700' : 'text-slate-400'}">${row.diem_xet_tuyen != null ? parseFloat(row.diem_xet_tuyen).toFixed(2) : '-'}</span>
            </td>
            <td class="py-3 px-3 text-center">
                ${isPass 
                    ? '<span class="inline-flex items-center px-2 py-1 rounded-lg text-[8px] font-black bg-emerald-50 text-emerald-600 border border-emerald-100 uppercase"><i class="fas fa-check-circle mr-1"></i>ĐỖ</span>'
                    : '<span class="inline-flex items-center px-2 py-1 rounded-lg text-[8px] font-black bg-rose-50 text-rose-500 border border-rose-200 uppercase"><i class="fas fa-times-circle mr-1"></i>TRƯỢT</span>'}
            </td>
            <td class="py-3 px-3 text-center">
                <a href="${REVIEW_URL}?cccd=${row.so_cccd}" target="_blank" 
                   class="w-7 h-7 inline-flex items-center justify-center rounded-lg bg-slate-100 hover:bg-indigo-100 text-slate-400 hover:text-indigo-600 transition-all" title="Xem hồ sơ">
                    <i class="fas fa-external-link-alt text-[9px]"></i>
                </a>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function renderPagination() {
    const totalPages = Math.ceil(filteredRecords / pageLength);
    const container = document.getElementById('paginationControls');
    if (totalPages <= 1) { container.innerHTML = ''; return; }

    let html = '';
    html += `<button onclick="goPage(${currentPage - 1})" ${currentPage === 0 ? 'disabled' : ''} 
        class="w-8 h-8 rounded-lg text-[10px] font-black ${currentPage === 0 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-indigo-50 hover:text-indigo-600'} transition-all flex items-center justify-center">
        <i class="fas fa-chevron-left"></i></button>`;

    const maxBtns = 7;
    let startP = Math.max(0, currentPage - Math.floor(maxBtns / 2));
    let endP = Math.min(totalPages, startP + maxBtns);
    if (endP - startP < maxBtns) startP = Math.max(0, endP - maxBtns);

    if (startP > 0) html += `<button onclick="goPage(0)" class="w-8 h-8 rounded-lg text-[10px] font-black text-slate-600 hover:bg-indigo-50 transition-all">1</button><span class="text-slate-300 text-xs">…</span>`;
    
    for (let p = startP; p < endP; p++) {
        html += `<button onclick="goPage(${p})" class="w-8 h-8 rounded-lg text-[10px] font-black ${p === currentPage ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-indigo-50 hover:text-indigo-600'} transition-all">${p + 1}</button>`;
    }
    
    if (endP < totalPages) html += `<span class="text-slate-300 text-xs">…</span><button onclick="goPage(${totalPages - 1})" class="w-8 h-8 rounded-lg text-[10px] font-black text-slate-600 hover:bg-indigo-50 transition-all">${totalPages}</button>`;

    html += `<button onclick="goPage(${currentPage + 1})" ${currentPage >= totalPages - 1 ? 'disabled' : ''} 
        class="w-8 h-8 rounded-lg text-[10px] font-black ${currentPage >= totalPages - 1 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-indigo-50 hover:text-indigo-600'} transition-all flex items-center justify-center">
        <i class="fas fa-chevron-right"></i></button>`;

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

function bulkEmailSelected() {
    if (selectedIds.size === 0) { alert('Vui lòng chọn ít nhất 1 thí sinh.'); return; }
    if (!confirm(`Gửi email trúng tuyển cho ${selectedIds.size} thí sinh đã chọn?`)) return;

    fetch(BULK_EMAIL_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ids=${JSON.stringify([...selectedIds])}&session_id=${SESSION_ID}&csrf_token=${CSRF_TOKEN}`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || 'Hoàn thành');
        selectedIds.clear();
        updateSelectedCount();
    })
    .catch(err => alert('Lỗi: ' + err.message));
}

// Helpers
function escHtml(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function fmt(v) { return v != null ? parseFloat(v).toFixed(2) : '-'; }
</script>

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
