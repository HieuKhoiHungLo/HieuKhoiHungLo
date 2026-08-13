<?php
// results.php - Danh sách kết quả trúng tuyển (SSP + All 3 Phases)
$isReadOnly = $isReadOnly ?? false;
$title = $isReadOnly ? "Số liệu Trúng tuyển" : "Danh sách Trúng tuyển";
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
<script src="https://cdn.jsdelivr.net/npm/chart.js" async></script>

<style>
    .premium-table {
        border-collapse: collapse !important;
        width: 100%;
        table-layout: auto;
    }
    .premium-table th, .premium-table td {
        padding: 0.5rem 0.75rem !important;
        border: 1px solid #e2e8f0 !important;
        vertical-align: middle;
        font-size: 13px;
        color: #334155;
    }
    .premium-table th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        font-size: 11px !important;
        letter-spacing: 0.02em;
        text-align: center;
    }
    .premium-table tbody tr:hover td {
        background-color: #f8fafc !important;
    }
</style>

<div class="h-full flex flex-col p-4 lg:p-6 pb-24 bg-slate-50/50" id="resultsApp" x-data="{ activeTab: '<?= $isReadOnly ? 'stats' : 'list' ?>', initCharts() { setTimeout(() => { renderAdmissionCharts(); }, 100); } }" x-init="$watch('activeTab', value => { if(value === 'charts') initCharts() })">

    <!-- Header Row (Title & Filters) -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="fas <?= $isReadOnly ? 'fa-chart-bar' : 'fa-file-invoice' ?> text-indigo-600"></i> <?= $title ?>
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
        <?php if (!$isReadOnly): ?>
        <button @click="activeTab = 'list'"
            :class="activeTab === 'list' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'"
            class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs transition duration-200 uppercase tracking-wider">
            <i class="fas fa-list-ul mr-2"></i>DANH SÁCH TRÚNG TUYỂN
        </button>
        <?php endif; ?>
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

    <!-- Compact Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-3">
        <!-- Card 1: Total Candidates -->
        <div class="bg-white px-3.5 py-2.5 rounded-xl shadow-sm border border-slate-200 relative overflow-hidden group">
            <div class="flex justify-between items-center mb-0.5">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Thí sinh</p>
                <span class="text-[10px] text-blue-500 font-bold"><?= number_format($totalWishes) ?> NV</span>
            </div>
            <h3 class="text-lg font-black text-slate-800 leading-tight"><?= number_format($totalCandidates) ?></h3>
        </div>

        <!-- Card 2: Admitted -->
        <div class="bg-white px-3.5 py-2.5 rounded-xl shadow-sm border border-emerald-100 relative overflow-hidden group">
            <div class="flex justify-between items-center mb-0.5">
                <p class="text-[9px] font-black text-emerald-600 uppercase tracking-wider">Trúng tuyển</p>
                <span class="text-[10px] text-emerald-500 font-bold"><?= $admitRate ?>% đạt</span>
            </div>
            <h3 class="text-lg font-black text-emerald-700 leading-tight"><?= number_format($totalAdmitted) ?></h3>
        </div>

        <!-- Card 3: NV1 -->
        <div class="bg-white px-3.5 py-2.5 rounded-xl shadow-sm border border-slate-200 relative overflow-hidden group">
            <div class="flex justify-between items-center mb-0.5">
                <p class="text-[9px] font-black text-indigo-600 uppercase tracking-wider">NV1</p>
                <span class="text-[10px] text-slate-400 font-bold">NV2: <?= $nv2 ?> · NV3: <?= $nv3 ?></span>
            </div>
            <h3 class="text-lg font-black text-indigo-700 leading-tight"><?= number_format($nv1) ?></h3>
        </div>

        <!-- Card 4: Quota Progress -->
        <div class="bg-white px-3.5 py-2.5 rounded-xl shadow-sm border border-slate-200 relative overflow-hidden group">
            <?php $quotaPct = $totalChiTieu > 0 ? round(($totalSoTrungTuyen / $totalChiTieu) * 100) : 0; ?>
            <div class="flex justify-between items-center mb-0.5">
                <p class="text-[9px] font-black text-amber-600 uppercase tracking-wider">Chỉ tiêu</p>
                <span class="text-[10px] text-amber-600 font-bold"><?= $quotaPct ?>%</span>
            </div>
            <h3 class="text-lg font-black text-amber-700 leading-tight"><?= number_format($totalSoTrungTuyen) ?><span class="text-xs text-slate-400 font-medium">/<?= number_format($totalChiTieu) ?></span></h3>
        </div>

        <!-- Card 5: Under Quota Warning -->
        <div class="bg-white px-3.5 py-2.5 rounded-xl shadow-sm border <?= $underQuota > 0 ? 'border-rose-200' : 'border-slate-200' ?> relative overflow-hidden group">
            <div class="flex justify-between items-center mb-0.5">
                <p class="text-[9px] font-black <?= $underQuota > 0 ? 'text-rose-600' : 'text-slate-400' ?> uppercase tracking-wider">Chưa đủ CT</p>
                <span class="text-[10px] <?= $underQuota > 0 ? 'text-rose-500' : 'text-slate-400' ?> font-bold"><?= $underQuota > 0 ? 'cần bổ sung' : 'Đủ ✓' ?></span>
            </div>
            <h3 class="text-lg font-black <?= $underQuota > 0 ? 'text-rose-700' : 'text-slate-800' ?> leading-tight"><?= $underQuota ?> <span class="text-xs font-normal text-slate-400">ngành</span></h3>
        </div>
    </div>

    <!-- TAB: LIST (Danh sách trúng tuyển) -->
    <?php if (!$isReadOnly): ?>
    <div x-show="activeTab === 'list'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" class="flex flex-col flex-1" style="display: none;" x-init="$el.style.display = 'flex'">

    <!-- Filter & Search Toolbar -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 mb-4 flex flex-col lg:flex-row items-center gap-3">
        <!-- Search -->
        <div class="relative flex-grow w-full lg:w-auto">
            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="fas fa-search text-xs"></i></div>
            <input type="text" id="searchInput" aria-label="Tìm kiếm theo CCCD, họ tên, mã ngành, tên ngành" placeholder="Tìm CCCD, họ tên, mã ngành, tên ngành..."
                class="w-full bg-slate-50 border-none rounded-xl pl-10 pr-4 py-3 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-400 placeholder:text-slate-300">
        </div>

        <!-- Major Filter -->
        <div class="relative w-full lg:w-56">
            <select id="majorFilter" aria-label="Lọc theo ngành" onchange="reloadTable()"
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

        <!-- Column Configuration Dropdown (Alpine context) -->
        <div class="relative" x-data="{ openConfig: false }">
            <button type="button" @click="openConfig = !openConfig" 
                class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold text-xs py-2.5 px-3.5 rounded-xl shadow-sm transition flex items-center gap-1.5 whitespace-nowrap">
                <i class="fas fa-columns text-indigo-600"></i> Cấu hình bảng
            </button>
            <div x-show="openConfig" @click.away="openConfig = false" x-cloak 
                class="absolute right-0 top-full mt-2 w-64 bg-white border border-slate-200 rounded-2xl shadow-xl z-50 p-3 max-h-80 overflow-y-auto custom-scrollbar">
                <div class="text-[11px] font-black text-slate-400 uppercase tracking-widest px-2 mb-2 pb-1 border-b border-slate-100 flex justify-between items-center">
                    <span>Hiển thị cột</span>
                    <button type="button" onclick="resetColumnConfig()" class="text-indigo-600 hover:underline normal-case font-bold text-[11px]">Mặc định</button>
                </div>
                <div class="space-y-1" id="colConfigList">
                    <!-- Populated dynamically by JS -->
                </div>
            </div>
        </div>

        <!-- Export Excel Button -->
        <div x-data="{ open: false }" class="relative">
            <button type="button" @click="open = !open" @click.away="open = false"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl shadow-sm transition flex items-center gap-1.5 whitespace-nowrap">
                <i class="fas fa-file-excel text-sm"></i> Xuất Excel <i class="fas fa-chevron-down text-[10px] ml-1 opacity-70"></i>
            </button>
            <div x-show="open" style="display: none;"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-100 z-50 overflow-hidden">
                <button type="button" onclick="exportResultsExcel('full')" class="w-full text-left px-4 py-3 hover:bg-emerald-50 text-slate-700 text-[11px] font-bold border-b border-slate-50 transition-colors flex items-center gap-2">
                    <span class="w-5 h-5 rounded bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px]">1</span>
                    Xuất thông tin đầy đủ
                </button>
                <button type="button" onclick="exportResultsExcel('top_students')" class="w-full text-left px-4 py-3 hover:bg-emerald-50 text-slate-700 text-[11px] font-bold transition-colors flex items-center gap-2">
                    <span class="w-5 h-5 rounded bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px]">2</span>
                    Xuất danh sách thủ khoa
                </button>
            </div>
        </div>
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
                    <!-- Header Titles: Ordered STT, CCCD, Họ tên, Mã ngành, Tên ngành, Điểm XT... -->
                    <tr>
                        <th class="text-center w-8" data-col="cb">
                            <input type="checkbox" id="selectAllInline" aria-label="Chọn tất cả" onchange="toggleSelectAll(this.checked)" class="w-3.5 h-3.5">
                        </th>
                        <th class="text-center w-10" data-col="stt">STT</th>
                        <th data-col="cccd">CCCD</th>
                        <th data-col="ho_ten">Họ và Tên</th>
                        <th data-col="ma_nganh">Mã ngành</th>
                        <th data-col="ten_nganh">Tên ngành</th>
                        <th class="text-center" data-col="diem_xt">Điểm XT</th>
                        <th class="text-center" data-col="gioi_tinh">Giới tính</th>
                        <th class="text-center" data-col="dan_toc">Dân tộc</th>
                        <th data-col="ten_truong_thpt">Trường THPT</th>
                        <th data-col="ten_tinh">Tỉnh/Thành</th>
                        <th class="text-center" data-col="nam_tot_nghiep">Năm TN</th>
                        <th class="text-center" data-col="hoc_luc_12">Học lực 12</th>
                        <th class="text-center" data-col="hanh_kiem_12">Hạnh kiểm 12</th>
                        <th class="text-center" data-col="diem_tb_12">ĐTB 12</th>
                        <th data-col="dia_chi_chi_tiet">Địa chỉ</th>
                        <th class="text-center" data-col="so_giay_bao">Số GB</th>
                        <th class="text-center" data-col="file_giay_bao">File Giấy Báo</th>
                        <th class="text-center" data-col="thoi_gian_nhap">T/g Nhập học</th>
                        <th data-col="nganh_tt">Ngành in GB</th>
                        <th data-col="ten_khoa">Khoa</th>
                        <th class="text-center w-12" data-col="anh_the">Ảnh thẻ</th>
                        <th class="text-center" data-col="xn_bo">XN Bộ</th>
                        <th class="text-center" data-col="xn_truong">XN Trường</th>
                        <th class="text-center" data-col="khu_vuc">KV</th>
                        <th class="text-center" data-col="doi_tuong">ĐT UT</th>
                        <th data-col="to_hop">Tổ hợp</th>
                        <th data-col="phuong_thuc">Phương thức</th>
                        <th class="text-center" data-col="mon1">M1</th>
                        <th class="text-center" data-col="mon2">M2</th>
                        <th class="text-center" data-col="mon3">M3</th>
                        <th class="text-center" data-col="diem_ut">UT Thô</th>
                        <th class="text-center" data-col="ut_quy_doi">UTQĐ</th>
                        <th data-col="kinh_phi">Nội dung Kinh phí</th>
                        <th class="text-center w-20" data-col="ket_qua">Kết quả</th>
                        <th data-col="link_so_do" class="text-center">Sơ đồ</th>
                        <th data-col="ban_nhap_hoc" class="text-center">Bàn nhập học</th>
                        <th data-col="gvcn">GVCN</th>
                        <th data-col="ghi_chu">Ghi chú</th>
                    </tr>
                    <!-- Sub-Header Row: Per-column Quick Search & Filter Inputs -->
                    <tr class="bg-slate-50/90 border-t border-slate-200">
                        <td data-col="cb"></td>
                        <td data-col="stt"></td>
                        <td data-col="cccd" class="p-1"><input type="text" id="col_filter_cccd" aria-label="Lọc theo CCCD" placeholder="Tìm CCCD..." onkeyup="debouncedReloadTable()" class="col-filter-input"></td>
                        <td data-col="ho_ten" class="p-1"><input type="text" id="col_filter_name" aria-label="Lọc theo họ tên" placeholder="Tìm Tên..." onkeyup="debouncedReloadTable()" class="col-filter-input"></td>
                        <td data-col="ma_nganh" class="p-1"><input type="text" id="col_filter_ma" aria-label="Lọc theo mã ngành" placeholder="Mã..." onkeyup="debouncedReloadTable()" class="col-filter-input"></td>
                        <td data-col="ten_nganh" class="p-1"><input type="text" id="col_filter_ten_nganh" aria-label="Lọc theo tên ngành" placeholder="Tên ngành..." onkeyup="debouncedReloadTable()" class="col-filter-input"></td>
                        <td data-col="diem_xt" class="p-1"><input type="text" id="col_filter_diem" aria-label="Lọc theo điểm" placeholder="Điểm..." onkeyup="debouncedReloadTable()" class="col-filter-input"></td>
                        <td data-col="gioi_tinh"></td>
                        <td data-col="dan_toc"></td>
                        <td data-col="ten_truong_thpt"></td>
                        <td data-col="ten_tinh"></td>
                        <td data-col="nam_tot_nghiep"></td>
                        <td data-col="hoc_luc_12"></td>
                        <td data-col="hanh_kiem_12"></td>
                        <td data-col="diem_tb_12"></td>
                        <td data-col="dia_chi_chi_tiet"></td>
                        <td data-col="so_giay_bao" class="p-1"><input type="text" id="col_filter_gb" aria-label="Lọc theo số giấy báo" placeholder="Số GB..." onkeyup="debouncedReloadTable()" class="col-filter-input"></td>
                        <td data-col="file_giay_bao"></td>
                        <td data-col="thoi_gian_nhap"></td>
                        <td data-col="nganh_tt"></td>
                        <td data-col="ten_khoa"></td>
                        <td data-col="anh_the"></td>
                        <td data-col="xn_bo" class="p-1">
                            <select id="col_filter_xn_bo" onchange="reloadTable()" class="col-filter-select">
                                <option value="">Tất cả</option>
                                <option value="1">Đã XN</option>
                                <option value="0">Chưa XN</option>
                            </select>
                        </td>
                        <td data-col="xn_truong" class="p-1">
                            <select id="col_filter_xn_truong" onchange="reloadTable()" class="col-filter-select">
                                <option value="">Tất cả</option>
                                <option value="1">Đã XN</option>
                                <option value="0">Chưa XN</option>
                            </select>
                        </td>
                        <td data-col="khu_vuc"></td>
                        <td data-col="doi_tuong"></td>
                        <td data-col="to_hop"></td>
                        <td data-col="phuong_thuc"></td>
                        <td data-col="mon1"></td>
                        <td data-col="mon2"></td>
                        <td data-col="mon3"></td>
                        <td data-col="diem_ut"></td>
                        <td data-col="ut_quy_doi"></td>
                        <td data-col="kinh_phi"></td>
                        <td data-col="ket_qua"></td>
                        <td data-col="link_so_do"></td>
                        <td data-col="ban_nhap_hoc" class="p-1"><input type="text" id="col_filter_ban_nhap_hoc" aria-label="Lọc theo bàn nhập học" placeholder="Bàn..." onkeyup="debouncedReloadTable()" class="col-filter-input"></td>
                        <td data-col="gvcn" class="p-1"><input type="text" id="col_filter_gvcn" aria-label="Lọc theo GVCN" placeholder="GVCN..." onkeyup="debouncedReloadTable()" class="col-filter-input"></td>
                        <td data-col="ghi_chu" class="p-1"><input type="text" id="col_filter_note" aria-label="Lọc theo ghi chú" placeholder="Ghi chú..." onkeyup="debouncedReloadTable()" class="col-filter-input"></td>
                    </tr>
                </thead>
                <tbody id="tableBody" class="text-[11px]">
                    <tr><td colspan="26" class="py-16 text-center border-b border-slate-100"><i class="fas fa-spinner fa-spin text-slate-300 text-2xl"></i></td></tr>
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
    <?php endif; ?>
    
    <!-- TAB: STATS (Thống kê trúng tuyển) -->
    <div x-show="activeTab === 'stats'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" style="display: none;" class="space-y-6">
        <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
            <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs lg:text-sm flex items-center mb-6">
                <span class="w-1.5 h-4 bg-emerald-500 rounded-full mr-2"></span>
                Thống kê kết quả trúng tuyển theo ngành đợt tuyển sinh
            </h3>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="premium-table min-w-[800px] lg:min-w-full">
                    <thead class="sticky top-0 z-10 bg-slate-100">
                        <tr class="text-slate-600 uppercase tracking-wider text-[10px] text-center">
                            <th style="width: 80px" class="py-3 border-b-2 border-r border-slate-200 bg-slate-100" rowspan="2">Mã ngành</th>
                            <th class="py-3 border-b-2 border-r border-slate-200 bg-slate-100" rowspan="2">Tên ngành</th>
                            <th style="width: 80px" class="py-3 border-b-2 border-r border-slate-200 bg-slate-100" rowspan="2">Chỉ tiêu</th>
                            <th style="width: 100px" class="py-3 border-b-2 border-r border-slate-200 bg-slate-100" rowspan="2">Điểm chuẩn</th>
                            <th class="py-1 border-b border-r border-slate-200 bg-blue-50 text-blue-800" colspan="3">Trúng tuyển</th>
                            <th style="width: 150px" class="py-3 border-b-2 border-slate-200 bg-slate-100" rowspan="2">Mức điểm (Thấp-Cao)</th>
                        </tr>
                        <tr class="text-slate-600 uppercase tracking-wider text-[10px] text-center">
                            <th style="width: 80px" class="py-2 border-b-2 border-r border-slate-200 bg-blue-50 text-blue-800">Tổng</th>
                            <th style="width: 80px" class="py-2 border-b-2 border-r border-slate-200 bg-blue-50 text-blue-800">NV1</th>
                            <th style="width: 100px" class="py-2 border-b-2 border-r border-slate-200 bg-blue-50 text-blue-800">Tiến độ (%)</th>
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
                            <td class="text-center font-mono text-slate-600 font-bold"><?= $ms['ma_nganh'] ?></td>
                            <td class="font-bold text-slate-800 text-left"><?= htmlspecialchars($ms['ten_nganh'] ?? '') ?></td>
                            <td class="text-center font-bold text-slate-600 bg-slate-50/50"><?= $ct ?: '-' ?></td>
                            <td class="text-center font-bold text-amber-700 bg-amber-50/20"><?= isset($ms['diem_thap_nhat']) && floatval($ms['diem_thap_nhat']) > 0 ? number_format($ms['diem_thap_nhat'], 3) : '-' ?></td>
                            <td class="text-center font-black text-indigo-700"><?= $tt ?: '-' ?></td>
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
                            <td colspan="2" class="text-right uppercase font-bold text-slate-700">Tổng cộng:</td>
                            <td class="text-center bg-slate-100/50 text-slate-700 font-bold"><?= $totalCT ?></td>
                            <td class="bg-slate-50"></td>
                            <td class="text-center text-indigo-700 font-black"><?= $totalTT ?></td>
                            <td class="text-center text-slate-700 font-bold"><?= $totalHNV1 > 0 ? $totalHNV1 : '-' ?></td>
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
        
        <!-- Major Fill Chart -->
        <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-emerald-500">
            <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                Biểu đồ tỷ lệ lấp đầy chuyên ngành (Đầy đủ các ngành xét tuyển)
            </h3>
            <div class="relative h-96">
                <canvas id="majorFillChart"></canvas>
            </div>
        </div>

        <!-- Row 2: Four statistics charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
            <!-- Chart: Tỷ lệ theo nguyện vọng -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-indigo-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Phân bố trúng tuyển theo NV
                </h3>
                <div class="relative h-64">
                    <canvas id="nvChart"></canvas>
                </div>
            </div>

            <!-- Chart: Giới tính -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-pink-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Phân bố Giới tính
                </h3>
                <div class="relative h-64">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>

            <!-- Chart: Khu vực ưu tiên -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-sky-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Khu vực ưu tiên
                </h3>
                <div class="relative h-64">
                    <canvas id="areaChart"></canvas>
                </div>
            </div>

            <!-- Chart: Đối tượng ưu tiên -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-amber-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Đối tượng ưu tiên
                </h3>
                <div class="relative h-64">
                    <canvas id="objectChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 mt-6">
            <!-- Chart: Theo tỉnh -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-purple-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Tỉnh / Thành phố
                </h3>
                <div class="relative h-64">
                    <canvas id="provinceChart"></canvas>
                </div>
            </div>

            <!-- Chart: Trường THPT tại Phú Thọ -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-rose-500">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs">
                        Trường THPT tại Phú Thọ
                    </h3>
                    <button type="button" onclick="toggleShowAllSchools()" 
                            class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-bold transition cursor-pointer">
                        <span id="btnToggleSchoolsText">Xem thêm</span>
                    </button>
                </div>
                <div class="relative h-64">
                    <canvas id="schoolChart"></canvas>
                </div>
            </div>
        </div>
    </div> <!-- END TAB CHARTS -->

    <!-- Action Bar: Fixed sticky bottom, respects sidebar width (Exact Review Page Button Style) -->
    <style>
        #results-action-bar {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 9999 !important;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            border-top: 1px solid #e2e8f0 !important;
            padding: 16px 32px !important;
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 12px !important;
            box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.06) !important;
            transition: left 0.3s ease !important;
        }
        @media (min-width: 1024px) {
            #results-action-bar {
                left: 280px !important;
            }
            .main-content.expanded #results-action-bar,
            body.sidebar-collapsed #results-action-bar {
                left: 70px !important;
            }
        }
    </style>

    <?php if (empty($isReadOnly)): ?>
    <div id="results-action-bar">
        <!-- Selected Count Badge if any -->
        <span id="selectedCountBadge" class="text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-2 rounded-xl border border-emerald-200 hidden">
            <span id="selectedCountText">0</span> đã chọn
        </span>

        <!-- 1. Gửi Email -->
        <button type="button" onclick="bulkEmailSelected()" class="px-6 py-3.5 bg-[#0066FF] text-white font-medium text-sm rounded-xl shadow-md hover:bg-blue-700 transition-all whitespace-nowrap active:scale-95">
            Gửi email
        </button>

        <!-- 2. Đồng bộ Lọc Ảo -->
        <form action="<?= url('/admin/admission/results/sync-virtual') ?>" method="POST" class="inline-flex" onsubmit="return confirm('Bạn có chắc chắn muốn ĐỒNG BỘ lại toàn bộ từ Lọc Ảo đợt này?');">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= $sessionId ?>">
            <button type="submit" class="px-6 py-3.5 bg-amber-500 text-white font-medium text-sm rounded-xl shadow-md hover:bg-amber-600 transition-all whitespace-nowrap active:scale-95">
                Đồng bộ Lọc Ảo
            </button>
        </form>

        <!-- 3. Upload Excel -->
        <button type="button" onclick="openUploadExcelModal()" class="px-6 py-3.5 bg-[#0066FF] text-white font-medium text-sm rounded-xl shadow-md hover:bg-blue-700 transition-all whitespace-nowrap active:scale-95">
            Upload Excel
        </button>

        <!-- 4. Upload Ảnh Thẻ -->
        <button type="button" onclick="openUploadAvatarModal()" class="px-6 py-3.5 bg-[#0066FF] text-white font-medium text-sm rounded-xl shadow-md hover:bg-blue-700 transition-all whitespace-nowrap active:scale-95">
            Upload ảnh thẻ
        </button>

        <!-- 5. Mẫu Thông Báo Trúng Tuyển -->
        <button type="button" onclick="openTemplateModal()" class="px-6 py-3.5 bg-[#0066FF] text-white font-medium text-sm rounded-xl shadow-md hover:bg-blue-700 transition-all whitespace-nowrap active:scale-95">
            Mẫu thông báo
        </button>

        <!-- 6. In Giấy Báo -->
        <button type="button" onclick="openPrintGiayBaoWordModal()" class="px-6 py-3.5 bg-emerald-600 text-white font-medium text-sm rounded-xl shadow-md hover:bg-emerald-700 transition-all whitespace-nowrap active:scale-95">
            In giấy báo
        </button>

        <!-- 7. Công bố / Hủy công bố -->
        <form action="<?= url('/admin/admission/results/toggle-publish') ?>" method="POST" class="inline-flex">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= $sessionId ?>">
            <input type="hidden" name="status" value="<?= empty($activeSession['is_published_results']) ? '1' : '0' ?>">
            <button type="submit" class="px-6 py-3.5 <?= empty($activeSession['is_published_results']) ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-amber-500 hover:bg-amber-600' ?> text-white font-medium text-sm rounded-xl shadow-md transition-all whitespace-nowrap active:scale-95" onclick="return confirm('<?= empty($activeSession['is_published_results']) ? 'Bạn có chắc chắn muốn CÔNG BỐ kết quả đợt này?' : 'Bạn có chắc chắn muốn HỦY CÔNG BỐ kết quả đợt này?' ?>');">
                <?= empty($activeSession['is_published_results']) ? 'Công bố' : 'Hủy công bố' ?>
            </button>
        </form>

        <!-- 8. Xóa Đợt -->
        <form action="<?= url('/admin/admission/results/clear') ?>" method="POST" class="inline-flex" onsubmit="return confirm('Bạn có chắc chắn muốn xóa TOÀN BỘ kết quả đợt này?');">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= $sessionId ?>">
            <button type="submit" class="px-6 py-3.5 bg-rose-600 text-white font-medium text-sm rounded-xl shadow-md hover:bg-rose-700 transition-all whitespace-nowrap active:scale-95">
                Xóa đợt
            </button>
        </form>
    </div>
    <?php endif; ?>
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
    let chartInstances = {};
    let showAllSchoolsState = false;

    function toggleShowAllSchools() {
        showAllSchoolsState = !showAllSchoolsState;
        const btnText = document.getElementById('btnToggleSchoolsText');
        if (btnText) btnText.textContent = showAllSchoolsState ? 'Thu gọn' : 'Xem thêm';
        chartsRendered = false;
        renderAdmissionCharts();
    }

    function renderAdmissionCharts() {
        if (chartsRendered || typeof Chart === 'undefined') return;
        
        // Destroy existing chart instances
        Object.values(chartInstances).forEach(inst => { if (inst) inst.destroy(); });
        chartInstances = {};

        // Custom inline plugin for pie/doughnut datalabels
        const customDatalabelsPlugin = {
            id: 'customDatalabels',
            afterDraw: (chart) => {
                const ctx = chart.ctx;
                chart.data.datasets.forEach((dataset, i) => {
                    const meta = chart.getDatasetMeta(i);
                    if (meta.hidden) return;
                    
                    const total = dataset.data.reduce((sum, val) => sum + parseFloat(val || 0), 0);
                    if (total === 0) return;

                    meta.data.forEach((element, index) => {
                        const value = dataset.data[index];
                        if (!value || value <= 0) return;
                        
                        const percent = Math.round((value / total) * 100);
                        const midAngle = element.startAngle + (element.endAngle - element.startAngle) / 2;
                        const radius = element.innerRadius + (element.outerRadius - element.innerRadius) / 2;
                        
                        const x = element.x + Math.cos(midAngle) * radius;
                        const y = element.y + Math.sin(midAngle) * radius;
                        
                        ctx.save();
                        ctx.shadowColor = 'rgba(0, 0, 0, 0.4)';
                        ctx.shadowBlur = 3;
                        ctx.fillStyle = '#ffffff';
                        ctx.font = 'bold 9px sans-serif';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        
                        const angle = element.endAngle - element.startAngle;
                        if (angle > 0.15) {
                            ctx.fillText(`${value}`, x, y - 5);
                            ctx.fillText(`${percent}%`, x, y + 5);
                        }
                        ctx.restore();
                    });
                });
            }
        };

        // Custom plugin for major fill bar values
        const majorDatalabelsPlugin = {
            id: 'majorDatalabels',
            afterDraw: (chart) => {
                const ctx = chart.ctx;
                const dataset = chart.data.datasets[0];
                if (!dataset) return;

                const meta = chart.getDatasetMeta(0);
                if (meta.hidden) return;

                meta.data.forEach((element, index) => {
                    const value = dataset.data[index];
                    if (value === undefined || value === null || value <= 0) return;

                    ctx.save();
                    ctx.fillStyle = '#1e293b';
                    ctx.font = 'bold 9px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText(value, element.x, element.y - 3);
                    ctx.restore();
                });
            }
        };

        // Custom plugin for horizontal/vertical bar values
        const barDatalabelsPlugin = {
            id: 'barDatalabels',
            afterDraw: (chart) => {
                const ctx = chart.ctx;
                chart.data.datasets.forEach((dataset, i) => {
                    const meta = chart.getDatasetMeta(i);
                    if (meta.hidden) return;

                    meta.data.forEach((element, index) => {
                        const value = dataset.data[index];
                        if (value === undefined || value === null || value <= 0) return;
                        
                        ctx.save();
                        ctx.fillStyle = '#475569';
                        ctx.font = 'bold 9px sans-serif';
                        
                        if (chart.options.indexAxis === 'y') {
                            ctx.textAlign = 'left';
                            ctx.textBaseline = 'middle';
                            ctx.fillText(` ${value}`, element.x + 3, element.y);
                        } else {
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';
                            ctx.fillText(`${value}`, element.x, element.y - 3);
                        }
                        ctx.restore();
                    });
                });
            }
        };

        // 1. NGUYEN VONG CHART
        const ctxNv = document.getElementById('nvChart');
        if (ctxNv) {
            let nv1 = chartData.nv.nv1;
            let nv2 = chartData.nv.nv2;
            let other = Math.max(0, chartData.nv.other);
            chartInstances.nv = new Chart(ctxNv.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['NV1', 'NV2', 'NV còn lại'],
                    datasets: [{
                        data: [nv1, nv2, other],
                        backgroundColor: ['#4f46e5', '#10b981', '#94a3b8'],
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
                },
                plugins: [customDatalabelsPlugin]
            });
        }

        // 2. MAJOR FILL CHART (Tất cả các ngành)
        const ctxMajor = document.getElementById('majorFillChart');
        if (ctxMajor && chartData.majors && chartData.majors.length) {
            const sortedMajors = [...chartData.majors].sort((a,b) => a.ma_nganh.localeCompare(b.ma_nganh));
            chartInstances.major = new Chart(ctxMajor.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: sortedMajors.map(m => m.ma_nganh),
                    datasets: [
                        {
                            label: 'Trúng tuyển (Dự kiến)',
                            data: sortedMajors.map(m => m.so_trung_tuyen),
                            backgroundColor: '#10b981',
                            borderRadius: 4
                        },
                        {
                            label: 'Chỉ tiêu',
                            data: sortedMajors.map(m => m.chi_tieu),
                            backgroundColor: '#f59e0b',
                            borderRadius: 4
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
                        y: { beginAtZero: true, grace: '5%' }
                    }
                },
                plugins: [majorDatalabelsPlugin]
            });
        }
        
        // 3. GENDER CHART
        const ctxGender = document.getElementById('genderChart');
        if (ctxGender && chartData.demo && chartData.demo.gender) {
            chartInstances.gender = new Chart(ctxGender.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(chartData.demo.gender),
                    datasets: [{
                        data: Object.values(chartData.demo.gender),
                        backgroundColor: ['#ec4899', '#3b82f6', '#94a3b8'],
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
                },
                plugins: [customDatalabelsPlugin]
            });
        }

        // 4. AREA CHART
        const ctxArea = document.getElementById('areaChart');
        if (ctxArea && chartData.demo && chartData.demo.area) {
            chartInstances.area = new Chart(ctxArea.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(chartData.demo.area),
                    datasets: [{
                        data: Object.values(chartData.demo.area),
                        backgroundColor: ['#0ea5e9', '#f59e0b', '#8b5cf6', '#10b981', '#64748b', '#ec4899', '#14b8a6'],
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
                },
                plugins: [customDatalabelsPlugin]
            });
        }

        // 5. OBJECT CHART
        const ctxObject = document.getElementById('objectChart');
        if (ctxObject && chartData.demo && chartData.demo.object) {
            chartInstances.object = new Chart(ctxObject.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(chartData.demo.object),
                    datasets: [{
                        data: Object.values(chartData.demo.object),
                        backgroundColor: ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#f97316', '#14b8a6'],
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
                },
                plugins: [customDatalabelsPlugin]
            });
        }

        // 6. PROVINCE CHART
        const ctxProv = document.getElementById('provinceChart');
        if (ctxProv && chartData.demo && chartData.demo.province) {
            const provEntries = Object.entries(chartData.demo.province).sort((a, b) => b[1] - a[1]);
            const topProvKeys = provEntries.map(e => e[0]).slice(0, 20);
            const topProvVals = provEntries.map(e => e[1]).slice(0, 20);
            
            chartInstances.province = new Chart(ctxProv.getContext('2d'), {
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
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    return topProvKeys[context[0].dataIndex];
                                }
                            }
                        }
                    }, 
                    scales: { 
                        x: { 
                            ticks: { 
                                maxRotation: 45, 
                                minRotation: 45, 
                                font: { size: 9 },
                                callback: function(value, index) {
                                    const label = topProvKeys[index];
                                    if (!label) return '';
                                    return label.length > 15 ? label.substring(0, 15) + '...' : label;
                                }
                            } 
                        }, 
                        y: { beginAtZero: true, grace: '5%' } 
                    } 
                },
                plugins: [barDatalabelsPlugin]
            });
        }

        // 7. SCHOOL CHART
        const ctxSchool = document.getElementById('schoolChart');
        if (ctxSchool && chartData.demo && chartData.demo.school) {
            const schEntries = Object.entries(chartData.demo.school).sort((a, b) => b[1] - a[1]);
            const allSchoolsKeys = schEntries.map(e => e[0]);
            const allSchoolsVals = schEntries.map(e => e[1]);
            
            const topSchKeys = showAllSchoolsState ? allSchoolsKeys : allSchoolsKeys.slice(0, 20);
            const topSchVals = showAllSchoolsState ? allSchoolsVals : allSchoolsVals.slice(0, 20);

            const container = ctxSchool.parentElement;
            if (container) container.style.height = Math.max(256, (topSchKeys.length * 22 + 50)) + 'px';

            chartInstances.school = new Chart(ctxSchool.getContext('2d'), {
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

function toTitleCase(str) {
    if (!str) return '';
    return str.toLowerCase().replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); });
}

// Column Configuration Registry
const allCols = [
    { key: 'cb', label: 'Chọn', fixed: true },
    { key: 'stt', label: 'STT', fixed: true },
    { key: 'cccd', label: 'CCCD', fixed: true },
    { key: 'ho_ten', label: 'Họ và Tên', fixed: true },
    { key: 'ma_nganh', label: 'Mã ngành', fixed: true },
    { key: 'ten_nganh', label: 'Tên ngành', fixed: true },
    { key: 'diem_xt', label: 'Điểm XT', fixed: true },
    { key: 'gioi_tinh', label: 'Giới tính' },
    { key: 'dan_toc', label: 'Dân tộc' },
    { key: 'ten_truong_thpt', label: 'Trường THPT' },
    { key: 'ten_tinh', label: 'Tỉnh / Thành' },
    { key: 'nam_tot_nghiep', label: 'Năm TN' },
    { key: 'hoc_luc_12', label: 'Học lực 12' },
    { key: 'hanh_kiem_12', label: 'Hạnh kiểm 12' },
    { key: 'diem_tb_12', label: 'ĐTB Lớp 12' },
    { key: 'dia_chi_chi_tiet', label: 'Địa chỉ' },
    { key: 'so_giay_bao', label: 'Số GB' },
    { key: 'file_giay_bao', label: 'File Giấy Báo' },
    { key: 'thoi_gian_nhap', label: 'T/g Nhập học' },
    { key: 'nganh_tt', label: 'Ngành in GB' },
    { key: 'ten_khoa', label: 'Khoa' },
    { key: 'anh_the', label: 'Ảnh thẻ' },
    { key: 'xn_bo', label: 'Xác nhận Bộ' },
    { key: 'xn_truong', label: 'Xác nhận Trường' },
    { key: 'khu_vuc', label: 'Khu vực' },
    { key: 'doi_tuong', label: 'Đối tượng UT' },
    { key: 'to_hop', label: 'Tổ hợp' },
    { key: 'phuong_thuc', label: 'Phương thức' },
    { key: 'mon1', label: 'Môn 1' },
    { key: 'mon2', label: 'Môn 2' },
    { key: 'mon3', label: 'Môn 3' },
    { key: 'diem_ut', label: 'Điểm UT (Thô)' },
    { key: 'ut_quy_doi', label: 'Điểm UT Quy đổi' },
    { key: 'kinh_phi', label: 'Nội dung Kinh phí' },
    { key: 'ket_qua', label: 'Kết quả' },
    { key: 'link_so_do', label: 'Link sơ đồ' },
    { key: 'ban_nhap_hoc', label: 'Bàn nhập học' },
    { key: 'gvcn', label: 'GVCN' },
    { key: 'ghi_chu', label: 'Ghi chú' }
];

let colsConfig = JSON.parse(localStorage.getItem('results_cols_config_v3') || '{}');

function isColVisible(key) {
    if (colsConfig[key] !== undefined) return colsConfig[key];
    // Default hidden optional columns
    const defaultHidden = ['nganh_tt', 'ten_khoa', 'phuong_thuc', 'mon1', 'mon2', 'mon3', 'diem_ut', 'ut_quy_doi', 'kinh_phi', 'gioi_tinh', 'dan_toc', 'ten_truong_thpt', 'ten_tinh', 'nam_tot_nghiep', 'hoc_luc_12', 'hanh_kiem_12', 'diem_tb_12', 'dia_chi_chi_tiet'];
    return !defaultHidden.includes(key);
}

function toggleCol(key) {
    colsConfig[key] = !isColVisible(key);
    localStorage.setItem('results_cols_config_v3', JSON.stringify(colsConfig));
    applyColumnVisibility();
    renderColConfigUI();
}

function resetColumnConfig() {
    colsConfig = {};
    localStorage.removeItem('results_cols_config_v3');
    applyColumnVisibility();
    renderColConfigUI();
}

function renderColConfigUI() {
    const container = document.getElementById('colConfigList');
    if (!container) return;
    let html = '';
    allCols.forEach(c => {
        const checked = isColVisible(c.key) ? 'checked' : '';
        const disabled = c.fixed ? 'disabled' : '';
        html += `<label class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded-lg cursor-pointer select-none">
            <input type="checkbox" ${checked} ${disabled} onchange="toggleCol('${c.key}')" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-3.5 h-3.5">
            <span class="text-xs font-semibold ${c.fixed ? 'text-slate-400' : 'text-slate-700'}">${c.label} ${c.fixed ? '(Cố định)' : ''}</span>
        </label>`;
    });
    container.innerHTML = html;
}

function applyColumnVisibility() {
    allCols.forEach(c => {
        const visible = isColVisible(c.key);
        document.querySelectorAll(`[data-col="${c.key}"]`).forEach(el => {
            if (visible) {
                el.style.removeProperty('display');
            } else {
                el.style.setProperty('display', 'none', 'important');
            }
        });
    });
}

// Debounce helper for column search
let colSearchTimer = null;
function debouncedReloadTable() {
    clearTimeout(colSearchTimer);
    colSearchTimer = setTimeout(() => {
        currentPage = 0;
        reloadTable();
    }, 300);
}

function reloadTable() {
    drawCounter++;
    pageLength = parseInt(document.getElementById('pageLengthSelect').value) || 10;
    const search = document.getElementById('searchInput')?.value || '';
    const major = document.getElementById('majorFilter')?.value || '';
    const start = currentPage * pageLength;

    // Column search filters
    const colCccd = document.getElementById('col_filter_cccd')?.value || '';
    const colName = document.getElementById('col_filter_name')?.value || '';
    const colMa = document.getElementById('col_filter_ma')?.value || '';
    const colTenNganh = document.getElementById('col_filter_ten_nganh')?.value || '';
    const colDiem = document.getElementById('col_filter_diem')?.value || '';
    const colGb = document.getElementById('col_filter_gb')?.value || '';
    const colNote = document.getElementById('col_filter_note')?.value || '';
    const colXnBo = document.getElementById('col_filter_xn_bo')?.value || '';
    const colXnTruong = document.getElementById('col_filter_xn_truong')?.value || '';
    const colBanNhapHoc = document.getElementById('col_filter_ban_nhap_hoc')?.value || '';
    const colGvcn = document.getElementById('col_filter_gvcn')?.value || '';

    const params = new URLSearchParams({
        draw: drawCounter, start, length: pageLength, search,
        session_id: SESSION_ID, major,
        col_cccd: colCccd,
        col_name: colName,
        col_ma_nganh: colMa,
        col_ten_nganh: colTenNganh,
        col_diem: colDiem,
        col_gb: colGb,
        col_note: colNote,
        col_xn_bo: colXnBo,
        col_xn_truong: colXnTruong,
        col_ban_nhap_hoc: colBanNhapHoc,
        col_gvcn: colGvcn
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
            applyColumnVisibility();
        })
        .catch(err => {
            document.getElementById('tableBody').innerHTML = `<tr><td colspan="36" class="py-20 text-center text-rose-400 font-bold">Lỗi tải dữ liệu: ${err.message}</td></tr>`;
        });
}

function renderTable(rows, startIndex) {
    const tbody = document.getElementById('tableBody');
    if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="36" class="py-16 text-center border-b border-slate-100 text-slate-400 text-sm">Không có dữ liệu phù hợp</td></tr>`;
        return;
    }

    let html = '';
    rows.forEach((row, i) => {
        const isPass = row.is_pass;
        const details = row.chi_tiet_diem || {};
        const checked = selectedIds.has(row.id) ? 'checked' : '';
        const rowBg = isPass ? '' : 'bg-slate-50/50 text-slate-400';
        
        let avatarHtml = '<div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-300 text-[10px] font-bold mx-auto" title="Chưa có ảnh"><i class="fas fa-user"></i></div>';
        if (row.linkanh) {
            let imgUrl = row.linkanh.startsWith('http') ? row.linkanh : ('<?= url('/') ?>/' + (row.linkanh.startsWith('/') ? row.linkanh.substring(1) : row.linkanh));
            avatarHtml = `<a href="${imgUrl}" target="_blank" title="Click xem ảnh thẻ" class="block w-7 h-7 rounded-full overflow-hidden border border-slate-200 shadow-sm mx-auto group relative hover:scale-125 transition-transform bg-slate-100">
                <img src="${imgUrl}" class="w-full h-full object-cover" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\\'w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-[9px]\\'><i class=\\'fas fa-user-slash\\'></i></div>';">
            </a>`;
        }

        let fileGiayBaoHtml = '<span class="text-slate-300 text-[10px]">-</span>';
        if (row.file_giay_bao) {
            let fileUrl = row.file_giay_bao.startsWith('http') ? row.file_giay_bao : ('<?= url('/') ?>/' + (row.file_giay_bao.startsWith('/') ? row.file_giay_bao.substring(1) : row.file_giay_bao));
            fileGiayBaoHtml = `<a href="${fileUrl}" target="_blank" title="Xem file Giấy báo trúng tuyển" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-[10px] font-semibold bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                <i class="fas fa-file-pdf"></i> Xem File
            </a>`;
        }

        const xnBoText = (row.xac_nhan_bo == 1 || row.xac_nhan_bo === true || row.xac_nhan_bo === 'true' || row.xac_nhan_nhap_hoc == 1 || row.is_confirm) ? 'Đã XN' : 'Chưa';
        const xnTruongText = (row.xac_nhan_truong == 1 || row.xac_nhan_truong === true || row.xac_nhan_truong === 'true') ? 'Đã XN' : 'Chưa';

        // UNIFIED ORDER: cb, stt, cccd, ho_ten, ma_nganh, ten_nganh, diem_xt, gioi_tinh, dan_toc, ten_truong_thpt, ten_tinh, nam_tot_nghiep, hoc_luc_12, hanh_kiem_12, diem_tb_12, dia_chi_chi_tiet, so_giay_bao, file_giay_bao, thoi_gian_nhap, nganh_tt, ten_khoa, anh_the, xn_bo, xn_truong, khu_vuc, doi_tuong, to_hop, phuong_thuc, mon1, mon2, mon3, diem_ut, ut_quy_doi, kinh_phi, ket_qua, ghi_chu
        html += `<tr class="${rowBg} hover:bg-slate-50/80 transition-colors border-b border-slate-100">
            <td class="text-center" data-col="cb">
                <input type="checkbox" class="rowCheck w-3.5 h-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" data-id="${row.id}" ${checked} onchange="toggleRowSelect(${row.id}, this.checked)">
            </td>
            <td class="text-center text-slate-600 font-normal" data-col="stt">${startIndex + i + 1}</td>
            <td class="text-slate-600 font-normal text-center" data-col="cccd">${escHtml(row.so_cccd)}</td>
            <td class="text-slate-700 font-normal" data-col="ho_ten">${escHtml(toTitleCase(row.ho_ten))}</td>
            <td class="text-slate-600 font-normal text-center" data-col="ma_nganh">${escHtml(row.ma_nganh)}</td>
            <td class="max-w-[160px] text-slate-600 font-normal" data-col="ten_nganh">
                <span class="truncate block" title="${escHtml(row.ten_nganh)}">${escHtml(row.ten_nganh)}</span>
            </td>
            <td class="text-center text-slate-700 font-normal" data-col="diem_xt">${row.diem_xt != null ? parseFloat(row.diem_xt).toFixed(2) : '-'}</td>
            <td class="text-center text-slate-600 font-normal" data-col="gioi_tinh">${escHtml(row.gioi_tinh || '-')}</td>
            <td class="text-center text-slate-600 font-normal" data-col="dan_toc">${escHtml(row.dan_toc || '-')}</td>
            <td class="max-w-[160px] text-slate-600 font-normal" data-col="ten_truong_thpt">
                <span class="truncate block" title="${escHtml(row.ten_truong_thpt || row.ma_truong_lop_12 || '')}">${escHtml(row.ten_truong_thpt || row.ma_truong_lop_12 || '-')}</span>
            </td>
            <td class="max-w-[120px] text-slate-600 font-normal" data-col="ten_tinh">
                <span class="truncate block" title="${escHtml(row.ten_tinh || row.ma_tinh_lop_12 || '')}">${escHtml(row.ten_tinh || row.ma_tinh_lop_12 || '-')}</span>
            </td>
            <td class="text-center text-slate-600 font-normal" data-col="nam_tot_nghiep">${escHtml(row.nam_tot_nghiep || '-')}</td>
            <td class="text-center text-slate-600 font-normal" data-col="hoc_luc_12">${escHtml(row.hoc_luc_12 || '-')}</td>
            <td class="text-center text-slate-600 font-normal" data-col="hanh_kiem_12">${escHtml(row.hanh_kiem_12 || '-')}</td>
            <td class="text-center text-slate-600 font-normal" data-col="diem_tb_12">${row.diem_tb_12 != null ? parseFloat(row.diem_tb_12).toFixed(2) : '-'}</td>
            <td class="max-w-[180px] text-slate-600 font-normal" data-col="dia_chi_chi_tiet">
                <span class="truncate block" title="${escHtml(row.dia_chi_chi_tiet || '')}">${escHtml(row.dia_chi_chi_tiet || '-')}</span>
            </td>
            <td class="text-slate-600 font-normal text-center" data-col="so_giay_bao">${escHtml(row.so_giay_bao || '-')}</td>
            <td class="text-center" data-col="file_giay_bao">${fileGiayBaoHtml}</td>
            <td class="text-slate-600 font-normal text-center" data-col="thoi_gian_nhap">${escHtml(row.thoi_gian_nhap || '-')}</td>
            <td class="max-w-[140px] text-slate-600 font-normal" data-col="nganh_tt">
                <span class="truncate block" title="${escHtml(row.nganh_tt || '')}">${escHtml(row.nganh_tt || '-')}</span>
            </td>
            <td class="text-slate-600 font-normal" data-col="ten_khoa">${escHtml(row.ten_khoa || '-')}</td>
            <td class="text-center" data-col="anh_the">${avatarHtml}</td>
            <td class="text-center text-slate-600 font-normal" data-col="xn_bo">${xnBoText}</td>
            <td class="text-center text-slate-600 font-normal" data-col="xn_truong">${xnTruongText}</td>
            <td class="text-center text-slate-600 font-normal" data-col="khu_vuc">${escHtml(row.khu_vuc || '-')}</td>
            <td class="text-center text-slate-600 font-normal" data-col="doi_tuong">${escHtml(row.doi_tuong || '-')}</td>
            <td class="text-slate-600 font-normal text-center" data-col="to_hop">${escHtml(row.to_hop || '-')}</td>
            <td class="max-w-[120px] text-slate-600 font-normal" data-col="phuong_thuc">
                <span class="truncate block" title="${escHtml(row.phuong_thuc || '')}">${escHtml(row.phuong_thuc || '-')}</span>
            </td>
            <td class="text-center text-slate-600 font-normal" data-col="mon1">${fmt3(row.diem_mon_1)}</td>
            <td class="text-center text-slate-600 font-normal" data-col="mon2">${fmt3(row.diem_mon_2)}</td>
            <td class="text-center text-slate-600 font-normal" data-col="mon3">${fmt3(row.diem_mon_3)}</td>
            <td class="text-center text-slate-600 font-normal" data-col="diem_ut">${row.diem_ut != null && parseFloat(row.diem_ut) > 0 ? '+' + parseFloat(row.diem_ut).toFixed(2) : '-'}</td>
            <td class="text-center text-slate-600 font-normal" data-col="ut_quy_doi">${row.ut_quy_doi != null && parseFloat(row.ut_quy_doi) > 0 ? '+' + parseFloat(row.ut_quy_doi).toFixed(2) : '-'}</td>
            <td class="max-w-[150px] text-slate-600 font-normal truncate" data-col="kinh_phi" title="${escHtml(row.kinh_phi || '')}">${escHtml(row.kinh_phi || '-')}</td>
            <td class="text-center text-slate-600 font-normal" data-col="ket_qua">${isPass ? 'Đỗ' : 'Trượt'}</td>
            <td class="text-center" data-col="link_so_do">
                ${row.link_so_do ? `<a href="${escHtml(row.link_so_do)}" target="_blank" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-[10px] font-semibold bg-slate-50 px-2 py-0.5 rounded border border-slate-200"><i class="fas fa-map-marked-alt"></i> Sơ đồ</a>` : '-'}
            </td>
            <td class="text-slate-600 font-normal text-center" data-col="ban_nhap_hoc">
                ${escHtml(row.ban_nhap_hoc || '')} ${row.vi_tri_nhap_hoc ? `(${escHtml(row.vi_tri_nhap_hoc)})` : ''} ${(!row.ban_nhap_hoc && !row.vi_tri_nhap_hoc) ? '-' : ''}
            </td>
            <td class="text-slate-600 font-normal" data-col="gvcn">${escHtml(row.gvcn || '-')}</td>
            <td class="text-slate-600 font-normal max-w-[120px] truncate" data-col="ghi_chu">${escHtml(row.ghi_chu || '')}</td>
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
    const badgeBottom = document.getElementById('selectedCountBadgeBottom');
    const textBottom = document.getElementById('selectedCountTextBottom');

    const count = selectedIds.size;
    if (el) {
        if (count > 0) {
            el.textContent = `${count} đã chọn`;
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    }
    if (badgeBottom && textBottom) {
        textBottom.textContent = count;
        if (count > 0) {
            badgeBottom.classList.remove('hidden');
        } else {
            badgeBottom.classList.add('hidden');
        }
    }
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('hidden');
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
    
    // Fetch template & Library
    fetch('<?= url("/admin/admission/results/get-template") ?>?session_id=' + SESSION_ID)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Populate library select dropdown
            if (data.all_templates && Array.isArray(data.all_templates)) {
                libraryTemplates = data.all_templates;
                const sel = document.getElementById('template-library-select');
                if (sel) {
                    let html = '<option value="">-- Nạp từ Thư viện Mẫu có sẵn (Click để chọn) --</option>';
                    data.all_templates.forEach(t => {
                        const activeMark = (data.template && data.template.id == t.id) ? ' (Đang dùng)' : '';
                        html += `<option value="${t.id}">${t.subject || t.code}${activeMark}</option>`;
                    });
                    sel.innerHTML = html;
                }
            }

            if (data.template) {
                document.getElementById('template-subject').value = data.template.subject || '';
                if (data.template.id) {
                    const sel = document.getElementById('template-library-select');
                    if (sel) sel.value = data.template.id;
                }
                if (editorInstance) {
                    editorInstance.setData(data.template.body || '');
                } else {
                    document.getElementById('template-editor').value = data.template.body || '';
                }
            }
        }
    })
    .catch(err => console.error(err));
}

let libraryTemplates = [];

function loadLibraryTemplate(tplId) {
    if (!tplId) return;
    const found = libraryTemplates.find(t => t.id == tplId);
    if (found) {
        document.getElementById('template-subject').value = found.subject || '';
        const editor = (typeof CKEDITOR !== 'undefined') ? CKEDITOR.instances['template-editor'] : editorInstance;
        if (editor) {
            editor.setData(found.body || '');
        } else {
            document.getElementById('template-editor').value = found.body || '';
        }
    }
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

function openUploadExcelModal() {
    const modal = document.getElementById('upload-excel-modal');
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    modal.classList.remove('hidden');
}

function handleFileSelect(input) {
    const btn = document.getElementById('btnSubmitImport');
    const statusText = document.getElementById('upload-status-text');
    const fileInfo = document.getElementById('upload-file-info');
    
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        statusText.innerText = "Đã chọn file Excel:";
        statusText.classList.add('text-blue-600');
        fileInfo.innerText = file.name + " (" + (file.size / 1024).toFixed(1) + " KB)";
        btn.disabled = false;
    } else {
        statusText.innerText = "Kéo thả hoặc click để chọn file Excel";
        statusText.classList.remove('text-blue-600');
        fileInfo.innerText = "Chấp nhận định dạng .xlsx, .xls";
        btn.disabled = true;
    }
}

function submitModalImport() {
    const form = document.getElementById('modalImportForm');
    const btn = document.getElementById('btnSubmitImport');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải lên...';
    btn.disabled = true;
    form.submit();
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
                <i class="fas fa-file-alt text-purple-600"></i> Mẫu Thông Báo / Giấy Báo Trúng Tuyển (Công Bố & Tra Cứu)
            </h3>
            <button type="button" onclick="closeModal('template-modal')" class="text-slate-400 hover:text-slate-600 transition-colors bg-white rounded-full p-1.5 shadow-sm hover:shadow">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <!-- Body -->
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
            <div class="mb-4 p-3 bg-purple-50/80 border border-purple-100 rounded-xl text-xs text-purple-900 space-y-1">
                <p class="font-bold flex items-center gap-1.5"><i class="fas fa-bullhorn text-purple-600"></i> Mẫu thông tin dùng cho Công bố Trúng tuyển đợt này</p>
                <p class="text-purple-800 leading-relaxed">
                    Mẫu thông tin (giao diện, nội dung HTML) này được liên kết riêng với <b>Đợt tuyển sinh đang chọn</b> để hiển thị khi thí sinh tra cứu trúng tuyển trực tuyến và gửi thông báo.
                </p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1 flex items-center justify-between">
                    <span><i class="fas fa-layer-group text-purple-600 mr-1"></i> Chọn mẫu sẵn có từ Thư viện Mẫu</span>
                    <a href="<?= url('/admin/settings/email-templates') ?>" target="_blank" class="text-xs font-bold text-purple-600 hover:text-purple-800 hover:underline flex items-center gap-1 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                        <i class="fas fa-external-link-alt text-[10px]"></i> Quản lý Thư viện Mẫu
                    </a>
                </label>
                <select id="template-library-select" onchange="loadLibraryTemplate(this.value)" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 text-xs py-2 bg-slate-50 font-medium">
                    <option value="">-- Đang nạp danh sách mẫu... --</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="template-subject" class="block text-sm font-semibold text-slate-700 mb-1">Tiêu đề Giấy Báo / Thông báo</label>
                <input type="text" id="template-subject" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Các biến động (Click để chèn vào vị trí con trỏ)</label>
                <div class="space-y-2 bg-slate-50 p-3 rounded-xl border border-slate-200/80 text-xs">
                    <div>
                        <span class="font-bold text-slate-700 mr-2">👤 Cá nhân:</span>
                        <button type="button" onclick="insertTemplateVar('{{HOTEN}}')" class="px-2 py-0.5 bg-white border border-slate-200 rounded hover:bg-purple-50 hover:text-purple-700 font-mono">Họ tên {{HOTEN}}</button>
                        <button type="button" onclick="insertTemplateVar('{{CCCD}}')" class="px-2 py-0.5 bg-white border border-slate-200 rounded hover:bg-purple-50 hover:text-purple-700 font-mono">CCCD {{CCCD}}</button>
                        <button type="button" onclick="insertTemplateVar('{{NGAYSINH}}')" class="px-2 py-0.5 bg-white border border-slate-200 rounded hover:bg-purple-50 hover:text-purple-700 font-mono">Ngày sinh {{NGAYSINH}}</button>
                        <button type="button" onclick="insertTemplateVar('{{SBD}}')" class="px-2 py-0.5 bg-white border border-slate-200 rounded hover:bg-purple-50 hover:text-purple-700 font-mono">SBD {{SBD}}</button>
                        <button type="button" onclick="insertTemplateVar('{{EMAIL}}')" class="px-2 py-0.5 bg-white border border-slate-200 rounded hover:bg-purple-50 hover:text-purple-700 font-mono">Email {{EMAIL}}</button>
                        <button type="button" onclick="insertTemplateVar('{{SDT}}')" class="px-2 py-0.5 bg-white border border-slate-200 rounded hover:bg-purple-50 hover:text-purple-700 font-mono">SĐT {{SDT}}</button>
                    </div>
                    <div>
                        <span class="font-bold text-amber-800 mr-2">📜 Giấy Báo:</span>
                        <button type="button" onclick="insertTemplateVar('{{SOGIAYBAO}}')" class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded hover:bg-amber-100 font-mono">Số Giấy báo {{SOGIAYBAO}}</button>
                        <button type="button" onclick="insertTemplateVar('{{THOIGIANNHAP}}')" class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded hover:bg-amber-100 font-mono">T/g Nhập học {{THOIGIANNHAP}}</button>
                        <button type="button" onclick="insertTemplateVar('{{NGANH_TT}}')" class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded hover:bg-amber-100 font-mono">Ngành trúng tuyển {{NGANH_TT}}</button>
                        <button type="button" onclick="insertTemplateVar('{{KHOA}}')" class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded hover:bg-amber-100 font-mono">Khoa {{KHOA}}</button>
                        <button type="button" onclick="insertTemplateVar('{{KINHPHI}}')" class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded hover:bg-amber-100 font-mono">Nội dung thu {{KINHPHI}}</button>
                        <button type="button" onclick="insertTemplateVar('{{LINKGIAYBAO}}')" class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded hover:bg-amber-100 font-mono">Link Ảnh Giấy báo {{LINKGIAYBAO}}</button>
                    </div>
                    <div>
                        <span class="font-bold text-blue-800 mr-2">💳 Học phí & QR:</span>
                        <button type="button" onclick="insertTemplateVar('{{SOTK}}')" class="px-2 py-0.5 bg-blue-50 border border-blue-200 text-blue-800 rounded hover:bg-blue-100 font-mono">Số TK {{SOTK}}</button>
                        <button type="button" onclick="insertTemplateVar('{{NGANHANG}}')" class="px-2 py-0.5 bg-blue-50 border border-blue-200 text-blue-800 rounded hover:bg-blue-100 font-mono">Ngân hàng {{NGANHANG}}</button>
                        <button type="button" onclick="insertTemplateVar('{{SOTIEN}}')" class="px-2 py-0.5 bg-blue-50 border border-blue-200 text-blue-800 rounded hover:bg-blue-100 font-mono">Số tiền {{SOTIEN}}</button>
                        <button type="button" onclick="insertTemplateVar('{{NOIDUNG}}')" class="px-2 py-0.5 bg-blue-50 border border-blue-200 text-blue-800 rounded hover:bg-blue-100 font-mono">Cú pháp CK {{NOIDUNG}}</button>
                        <button type="button" onclick="insertTemplateVar('{{QR_ThanhToan}}')" class="px-2 py-0.5 bg-blue-100 border border-blue-300 text-blue-900 font-bold rounded hover:bg-blue-200"><i class="fas fa-qrcode"></i> QR Thanh Toán</button>
                    <div>
                        <span class="font-bold text-emerald-800 mr-2">🚀 Tiến Trình 6 Bước:</span>
                        <button type="button" onclick="insertTemplateVar('{{THANH_TIEN_DO_6_BUOC}}')" class="px-2.5 py-0.5 bg-emerald-100 border border-emerald-300 text-emerald-950 font-bold rounded hover:bg-emerald-200"><i class="fas fa-route"></i> Thanh Tiến Độ 6 Bước {{THANH_TIEN_DO_6_BUOC}}</button>
                    </div>
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

<!-- Upload Excel Modal -->
<div id="upload-excel-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal('upload-excel-modal')"></div>
    
    <!-- Modal Content -->
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-10 animate-fade-in-up">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-2xl">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-file-excel text-green-600"></i> Nhập Kết Quả Xét Tuyển (Excel)
            </h3>
            <button type="button" onclick="closeModal('upload-excel-modal')" class="text-slate-400 hover:text-slate-600 transition-colors bg-white rounded-full p-1.5 shadow-sm hover:shadow">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <!-- Body -->
        <div class="p-6">
            <!-- Download sample template section -->
            <div class="mb-6 p-4 bg-emerald-50/80 border border-emerald-100 rounded-xl flex items-start gap-3">
                <div class="p-2 bg-emerald-500 text-white rounded-lg mt-0.5">
                    <i class="fas fa-download text-sm"></i>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-emerald-800 mb-0.5">File Excel Mẫu Chuẩn</h4>
                    <p class="text-xs text-emerald-600/90 leading-relaxed mb-2.5">
                        Tải xuống file Excel mẫu chuẩn được thiết lập sẵn các cột bắt buộc và các cột bổ sung dùng để in giấy báo nhập học.
                    </p>
                    <a href="<?= url('/admin/admission/results/download-sample') ?>" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm hover:shadow transition-colors">
                        <i class="fas fa-file-download"></i>
                        Tải File Excel Mẫu
                    </a>
                </div>
            </div>

            <!-- Upload form -->
            <form action="<?= url('/admin/admission/results/import') ?>" method="POST" enctype="multipart/form-data" id="modalImportForm">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                <input type="hidden" name="update_existing" value="1">
                
                <div class="border-2 border-dashed border-slate-200 hover:border-blue-500 rounded-2xl p-6 text-center cursor-pointer transition-all hover:bg-blue-50/10 group relative"
                     onclick="document.getElementById('modal-excel-file').click();">
                    <input type="file" name="excel_file" id="modal-excel-file" aria-label="Chọn file Excel để nhập kết quả" class="hidden" accept=".xls,.xlsx" 
                           onchange="handleFileSelect(this)">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <i class="fas fa-cloud-upload-alt text-xl"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-700 mb-1" id="upload-status-text">Kéo thả hoặc click để chọn file Excel</p>
                        <p class="text-xs text-slate-400" id="upload-file-info">Chấp nhận định dạng .xlsx, .xls</p>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end gap-3 rounded-b-2xl">
            <button type="button" onclick="closeModal('upload-excel-modal')" class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors shadow-sm">
                Đóng
            </button>
            <button type="button" onclick="submitModalImport()" id="btnSubmitImport" disabled class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none rounded-xl shadow-sm shadow-blue-600/20 transition-all active:scale-95 flex items-center gap-2">
                <i class="fas fa-file-import"></i>
                <span>Tải lên dữ liệu</span>
            </button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: IMPORT & ĐỒNG BỘ ẢNH THẺ (ZIP / DRIVE) -->
<!-- ========================================================================= -->
<div id="upload-avatar-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal('upload-avatar-modal')"></div>
    
    <!-- Modal Content -->
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl relative z-10 animate-fade-in-up overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-images text-violet-600"></i> Import & Đồng Bộ Ảnh Thẻ Thí Sinh
            </h3>
            <button type="button" onclick="closeModal('upload-avatar-modal')" class="text-slate-400 hover:text-slate-600 transition-colors bg-white rounded-full p-1.5 shadow-sm hover:shadow">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6 space-y-5">
            <div class="p-3.5 bg-violet-50/80 border border-violet-100 rounded-xl text-xs text-violet-800 space-y-1">
                <p class="font-bold flex items-center gap-1.5"><i class="fas fa-info-circle"></i> Cơ chế đồng bộ Thư mục Google Drive của Thí sinh</p>
                <p class="text-violet-700/90 leading-relaxed">
                    Hệ thống giải nén file ZIP, tự động tối ưu hóa kích thước ảnh (3x4), lưu vào đúng <b>Thư mục Google Drive cá nhân của từng thí sinh</b> (<code class="bg-white px-1 py-0.5 rounded font-mono font-bold text-violet-900">&lt;Root&gt;/&lt;Đợt&gt;/&lt;CCCD&gt;/</code>) và cập nhật đường dẫn đồng bộ sang Hồ sơ thí sinh & Tài khoản đăng nhập.
                </p>
            </div>

            <!-- ZIP Upload Form -->
            <form action="<?= url('/admin/admission/results/import-avatars') ?>" method="POST" enctype="multipart/form-data" id="modalAvatarZipForm" onsubmit="document.getElementById('btnSubmitAvatarZip').disabled=true; document.getElementById('btnSubmitAvatarZip').innerHTML='<i class=\\'fas fa-spinner fa-spin\\'></i> Đang xử lý...';">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                
                <div class="mb-3">
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="overwrite" value="1" checked class="rounded border-slate-300 text-violet-600 focus:ring-violet-400">
                        <span>Ghi đè nếu thí sinh đã có ảnh thẻ trong hệ thống</span>
                    </label>
                </div>

                <div class="border-2 border-dashed border-slate-200 hover:border-violet-500 rounded-2xl p-5 text-center cursor-pointer transition-all hover:bg-violet-50/10 group relative mb-4"
                     onclick="document.getElementById('modal-zip-file').click();">
                    <input type="file" name="zip_file" id="modal-zip-file" aria-label="Chọn file ZIP ảnh thẻ" class="hidden" accept=".zip" 
                           onchange="handleAvatarZipSelect(this)">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-10 h-10 bg-violet-50 text-violet-500 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-file-archive text-lg"></i>
                        </div>
                        <p class="text-xs font-semibold text-slate-700 mb-1" id="zip-status-text">Kéo thả hoặc click để chọn file .ZIP ảnh thẻ từ Bộ</p>
                        <p class="text-[11px] text-slate-400" id="zip-file-info">Tên ảnh dạng &lt;CCCD&gt;.jpg (Chấp nhận định dạng .zip)</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('upload-avatar-modal')" class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">
                        Đóng
                    </button>
                    <button type="submit" id="btnSubmitAvatarZip" disabled class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:pointer-events-none rounded-xl shadow-sm shadow-indigo-600/20 transition-all active:scale-95 flex items-center gap-2">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span class="text-sm">BƯỚC 2: BẤM VÀO ĐÂY ĐỂ UPLOAD FILE ZIP</span>
                    </button>
                </div>
            </form>

            <!-- Drive Sync Form -->
            <div class="pt-4 border-t border-slate-100 mt-2">
                <div class="bg-indigo-50/60 border border-indigo-100 rounded-xl p-3.5 flex items-center justify-between gap-3">
                    <div>
                        <h5 class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                            <i class="fab fa-google-drive text-indigo-600"></i> Quét Tự Động Thư Mục Google Drive
                        </h5>
                        <p class="text-[11px] text-indigo-700/80 leading-tight mt-0.5">Tự động tìm file ảnh trong các thư mục hồ sơ thí sinh đã có sẵn trên Google Drive để quét và đồng bộ.</p>
                    </div>
                    <form action="<?= url('/admin/admission/results/sync-drive-avatars') ?>" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn quét và đồng bộ tự động từ thư mục Google Drive của từng thí sinh không? Quá trình này có thể mất nhiều thời gian.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                        <button type="submit" class="px-3.5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition-all active:scale-95 whitespace-nowrap flex items-center gap-1.5">
                            <i class="fas fa-sync-alt"></i>
                            <span>Đồng Bộ Drive</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Local to Drive Sync Form -->
            <div class="pt-4 border-t border-slate-100 mt-2">
                <div class="bg-amber-50/60 border border-amber-100 rounded-xl p-3.5 flex items-center justify-between gap-3">
                    <div>
                        <h5 class="text-xs font-bold text-amber-900 flex items-center gap-1.5">
                            <i class="fas fa-cloud-upload-alt text-amber-600"></i> Đẩy ảnh cục bộ lên Google Drive
                        </h5>
                        <p class="text-[11px] text-amber-700/80 leading-tight mt-0.5">Đẩy toàn bộ các ảnh thẻ đang lưu tạm trên máy chủ này lên Google Drive và chuyển đổi đường dẫn của thí sinh sang link Drive.</p>
                    </div>
                    <form action="<?= url('/admin/admission/results/sync-local-avatars') ?>" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn đẩy tất cả ảnh thẻ cục bộ lên Google Drive? Quá trình này sẽ tốn thời gian upload.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                        <button type="submit" class="px-3.5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-sm transition-all active:scale-95 whitespace-nowrap flex items-center gap-1.5">
                            <i class="fas fa-upload"></i>
                            <span>Đẩy lên Drive</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: IN GIẤY BÁO TRÚNG TUYỂN WORD -->
<!-- ========================================================================= -->
<div id="modal-print-giay-bao-word" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                <i class="fas fa-file-word text-emerald-600 text-lg"></i>
                <span>In Giấy Báo Trúng Tuyển (Word)</span>
            </h3>
            <button onclick="closeModal('modal-print-giay-bao-word')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <!-- Selected Info -->
            <div id="print-word-selected-info" class="p-3 bg-indigo-50 border border-indigo-100 rounded-xl text-xs text-indigo-700 font-medium flex items-center gap-2">
                <i class="fas fa-info-circle text-indigo-500"></i>
                <span id="print-word-selected-text">Chưa chọn thí sinh nào trong danh sách.</span>
            </div>

            <!-- Template selection -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Chọn mẫu file Word (.docx) *</label>
                <select id="select-giay-bao-template" class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">-- Đang tải danh sách mẫu... --</option>
                </select>
                <div class="mt-1 text-right">
                    <a href="<?= url('/admin/phieu/templates') ?>" target="_blank" class="text-xs text-emerald-600 hover:underline">Quản lý mẫu Word →</a>
                </div>
            </div>

            <!-- Option if no checkboxes selected -->
            <div id="print-word-scope-option" class="space-y-2">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Phạm vi in *</label>
                <div class="space-y-1.5 text-sm">
                    <label class="flex items-center gap-2 p-2 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="print_scope" value="selected" id="radio-scope-selected" class="text-emerald-600">
                        <span>Chỉ thí sinh được tích chọn (<strong id="count-selected-scope">0</strong>)</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="print_scope" value="page" id="radio-scope-page" checked class="text-emerald-600">
                        <span>Tất cả thí sinh đang hiển thị trang này</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end gap-3">
            <button type="button" onclick="closeModal('modal-print-giay-bao-word')" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50">
                Hủy
            </button>
            <button type="button" onclick="submitPrintGiayBaoWord()" class="px-5 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm shadow-emerald-600/20 flex items-center gap-2">
                <i class="fas fa-download"></i>
                <span>Xuất File Word</span>
            </button>
        </div>
    </div>
</div>

<?php 
if (session_status() == PHP_SESSION_NONE) session_start();
if (isset($_SESSION['avatar_import_result'])): 
    $importRes = $_SESSION['avatar_import_result'];
    unset($_SESSION['avatar_import_result']);
    
    $total = $importRes['total'] ?? 0;
    $inserted = $importRes['inserted'] ?? 0;
    $unmatched = $importRes['unmatched'] ?? [];
    $errors = $importRes['errors'] ?? [];
?>
<div id="avatar-import-result-modal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal('avatar-import-result-modal')"></div>
    
    <!-- Modal Content -->
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl relative z-10 animate-fade-in-up overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-info-circle text-violet-600"></i> Báo cáo kết quả Upload Ảnh Thẻ
            </h3>
            <button type="button" onclick="closeModal('avatar-import-result-modal')" class="text-slate-400 hover:text-slate-600 transition-colors bg-white rounded-full p-1.5 shadow-sm hover:shadow">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <!-- Body -->
        <div class="p-6">
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center">
                    <div class="text-slate-500 text-xs font-bold uppercase mb-1">Tổng ảnh xử lý</div>
                    <div class="text-2xl font-black text-slate-800"><?= number_format($total) ?></div>
                </div>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
                    <div class="text-emerald-600 text-xs font-bold uppercase mb-1">Cập nhật thành công</div>
                    <div class="text-2xl font-black text-emerald-700"><?= number_format($inserted) ?></div>
                </div>
            </div>

            <?php if (count($unmatched) > 0): ?>
            <div class="mb-4">
                <h4 class="text-sm font-bold text-amber-700 mb-2 flex items-center gap-1.5">
                    <i class="fas fa-exclamation-triangle"></i> <?= count($unmatched) ?> ảnh không tìm thấy sinh viên (sai CCCD hoặc chưa trúng tuyển)
                </h4>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800 max-h-32 overflow-y-auto font-mono">
                    <?= implode(", ", array_map('htmlspecialchars', $unmatched)) ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (count($errors) > 0): ?>
            <div>
                <h4 class="text-sm font-bold text-rose-700 mb-2 flex items-center gap-1.5">
                    <i class="fas fa-times-circle"></i> <?= count($errors) ?> lỗi phát sinh
                </h4>
                <div class="bg-rose-50 border border-rose-200 rounded-lg p-3 text-xs text-rose-800 max-h-32 overflow-y-auto space-y-1 font-mono">
                    <?php foreach ($errors as $err): ?>
                        <div>- <?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
            <button type="button" onclick="closeModal('avatar-import-result-modal')" class="px-5 py-2 text-sm font-bold text-white bg-slate-600 hover:bg-slate-700 rounded-xl shadow-sm">
                Đóng
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
async function openPrintGiayBaoWordModal() {
    const modal = document.getElementById('modal-print-giay-bao-word');
    modal.classList.remove('hidden');

    const selectedCount = selectedIds ? selectedIds.size : 0;
    const infoText = document.getElementById('print-word-selected-text');
    const radioSelected = document.getElementById('radio-scope-selected');
    const radioPage = document.getElementById('radio-scope-page');
    document.getElementById('count-selected-scope').textContent = selectedCount;

    if (selectedCount > 0) {
        infoText.innerHTML = `Đã chọn <strong>${selectedCount}</strong> thí sinh từ bảng.`;
        radioSelected.checked = true;
    } else {
        infoText.innerHTML = `Chưa tích chọn thí sinh nào. Sẽ in cho danh sách trang hiện tại.`;
        radioPage.checked = true;
    }

    // Load templates
    const select = document.getElementById('select-giay-bao-template');
    select.innerHTML = '<option value="">-- Đang tải danh sách mẫu... --</option>';
    try {
        const res = await fetch('<?= url("/admin/phieu/list") ?>?loai=giay_bao_trung_tuyen');
        const data = await res.json();
        if (data.success && data.data.length > 0) {
            select.innerHTML = data.data.map(t => `<option value="${t.id}">${t.ten_mau}</option>`).join('');
        } else {
            select.innerHTML = '<option value="">(Chưa có mẫu Giấy Báo Trúng Tuyển nào)</option>';
        }
    } catch (e) {
        select.innerHTML = '<option value="">Lỗi tải danh sách mẫu</option>';
    }
}

function submitPrintGiayBaoWord() {
    const templateId = document.getElementById('select-giay-bao-template').value;
    if (!templateId) {
        alert('Vui lòng chọn mẫu file Word!');
        return;
    }

    let ids = [];
    const scope = document.querySelector('input[name="print_scope"]:checked')?.value || 'page';

    if (scope === 'selected' && selectedIds && selectedIds.size > 0) {
        ids = Array.from(selectedIds);
    } else {
        // Collect current table page row IDs
        const checkboxes = document.querySelectorAll('#tableBody input[type="checkbox"]');
        checkboxes.forEach(cb => {
            if (cb.value) ids.push(parseInt(cb.value));
        });
    }

    if (ids.length === 0) {
        alert('Không có thí sinh nào để in!');
        return;
    }

    const downloadUrl = `<?= url("/admin/phieu/download") ?>?type=giay_bao&ids=${ids.join(',')}&template_id=${templateId}`;
    window.open(downloadUrl, '_blank');
    closeModal('modal-print-giay-bao-word');
}


function openUploadAvatarModal() {
    const modal = document.getElementById('upload-avatar-modal');
    if (modal) {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        modal.classList.remove('hidden');
    }
}

function handleAvatarZipSelect(input) {
    const btn = document.getElementById('btnSubmitAvatarZip');
    const statusText = document.getElementById('zip-status-text');
    const fileInfo = document.getElementById('zip-file-info');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        if (statusText) statusText.textContent = `Đã chọn: ${file.name}`;
        if (fileInfo) fileInfo.textContent = `Dung lượng file ZIP: ${sizeMb} MB`;
        if (btn) btn.disabled = false;
    } else {
        if (statusText) statusText.textContent = 'Kéo thả hoặc click để chọn file .ZIP ảnh thẻ từ Bộ';
        if (fileInfo) fileInfo.textContent = 'Tên ảnh dạng <CCCD>.jpg (Chấp nhận định dạng .zip)';
        if (btn) btn.disabled = true;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if (!$isReadOnly): ?>
    renderColConfigUI();
    applyColumnVisibility();
    reloadTable();
    document.getElementById('searchInput')?.addEventListener('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { currentPage = 0; reloadTable(); }, 350);
    });
    <?php endif; ?>
    
    // Auto download import result file if requested
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('download_result') === '1') {
        setTimeout(() => {
            window.location.href = '<?= url('/admin/admission/results/download-result-file') ?>';
        }, 800);
    }
});

function changeSession(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('session_id', val);
    window.location.href = url.toString();
}

function exportResultsExcel(type = 'full') {
    const sessionId = document.getElementById('sessionSelector')?.value || '<?= $sessionId ?>';
    const major = document.getElementById('majorFilter')?.value || '';
    const search = document.getElementById('searchInput')?.value || '';

    const colCccd = document.getElementById('col_filter_cccd')?.value || '';
    const colName = document.getElementById('col_filter_name')?.value || '';
    const colMa = document.getElementById('col_filter_ma')?.value || '';
    const colTenNganh = document.getElementById('col_filter_ten_nganh')?.value || '';
    const colDiem = document.getElementById('col_filter_diem')?.value || '';
    const colGb = document.getElementById('col_filter_gb')?.value || '';
    const colNote = document.getElementById('col_filter_note')?.value || '';
    const colXnBo = document.getElementById('col_filter_xn_bo')?.value || '';
    const colXnTruong = document.getElementById('col_filter_xn_truong')?.value || '';
    const colBanNhapHoc = document.getElementById('col_filter_ban_nhap_hoc')?.value || '';
    const colGvcn = document.getElementById('col_filter_gvcn')?.value || '';
    
    let exportUrl = '<?= url('/admin/admission/results/export') ?>?session_id=' + encodeURIComponent(sessionId) + '&export_type=' + encodeURIComponent(type);
    if (major) exportUrl += '&major=' + encodeURIComponent(major);
    if (search) exportUrl += '&search=' + encodeURIComponent(search);
    if (colCccd) exportUrl += '&col_cccd=' + encodeURIComponent(colCccd);
    if (colName) exportUrl += '&col_name=' + encodeURIComponent(colName);
    if (colMa) exportUrl += '&col_ma_nganh=' + encodeURIComponent(colMa);
    if (colTenNganh) exportUrl += '&col_ten_nganh=' + encodeURIComponent(colTenNganh);
    if (colDiem) exportUrl += '&col_diem=' + encodeURIComponent(colDiem);
    if (colGb) exportUrl += '&col_gb=' + encodeURIComponent(colGb);
    if (colNote) exportUrl += '&col_note=' + encodeURIComponent(colNote);
    if (colXnBo !== '') exportUrl += '&col_xn_bo=' + encodeURIComponent(colXnBo);
    if (colXnTruong !== '') exportUrl += '&col_xn_truong=' + encodeURIComponent(colXnTruong);
    if (colBanNhapHoc) exportUrl += '&col_ban_nhap_hoc=' + encodeURIComponent(colBanNhapHoc);
    if (colGvcn) exportUrl += '&col_gvcn=' + encodeURIComponent(colGvcn);
    
    window.location.href = exportUrl;
}
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.font-black { font-weight: 900 !important; }
.col-filter-input, .col-filter-select {
    width: 100%;
    font-size: 10px;
    font-weight: 600;
    padding: 3px 6px;
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    outline: none;
    color: #334155;
}
.col-filter-input:focus, .col-filter-select:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
}
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
