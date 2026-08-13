<?php
$isReadOnly = $isReadOnly ?? false;
$title = $isReadOnly ? "Số liệu Nhập học" : "Thống kê Nhập học";
ob_start();

$totalCandidates = $totalTrungTuyen;
$admitRate = $totalCandidates > 0 ? round(($totalNhapHoc / $totalCandidates) * 100, 1) : 0;
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

<div class="h-full flex flex-col p-4 lg:p-6 pb-24 bg-slate-50/50" id="statsApp" x-data="{ activeTab: 'stats', initCharts() { setTimeout(() => { renderEnrollmentCharts(); }, 100); } }" x-init="$watch('activeTab', value => { if(value === 'charts') initCharts() })">

    <!-- Header Row -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-chart-pie text-purple-600"></i> <?= $title ?>
            </h1>
        </div>
        
        <div class="flex items-center gap-2 w-full md:w-auto">
            <div class="relative flex-1 md:flex-none md:min-w-[220px]">
                <select id="sessionSelector" onchange="window.location.href='?session_id='+this.value"
                    class="w-full border border-slate-300 rounded-lg text-sm bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 text-slate-700 outline-none appearance-none cursor-pointer">
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($s['id'] == $activeSessionId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['ten_dot'] ?? ('Đợt #' . $s['id'])) ?> (<?= $s['nam_tuyen_sinh'] ?? '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
            </div>
            
            <!-- Excel Export Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" @click.away="open = false"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 px-4 rounded-lg shadow-sm transition flex items-center gap-1.5 whitespace-nowrap h-[38px]">
                    <i class="fas fa-file-excel text-sm"></i> Xuất Excel <i class="fas fa-chevron-down text-[10px] ml-1 opacity-70"></i>
                </button>
                <div x-show="open" style="display: none;"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-slate-200 z-50 overflow-hidden">
                    <a href="<?= url('/admin/enrollment/export-confirmed?session_id=' . $activeSessionId) ?>" class="block w-full text-left px-4 py-3 hover:bg-emerald-50 text-slate-700 text-xs font-semibold border-b border-slate-100 transition-colors flex items-center gap-2">
                        <span class="w-5 h-5 rounded bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px]">1</span>
                        Danh sách xác nhận nhập học
                    </a>
                    <a href="<?= url('/admin/enrollment/export-unconfirmed?session_id=' . $activeSessionId) ?>" class="block w-full text-left px-4 py-3 hover:bg-emerald-50 text-slate-700 text-xs font-semibold border-b border-slate-100 transition-colors flex items-center gap-2">
                        <span class="w-5 h-5 rounded bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px]">2</span>
                        Danh sách chưa xác nhận nhập học
                    </a>
                    <a href="<?= url('/admin/enrollment/export-enrolled?session_id=' . $activeSessionId) ?>" class="block w-full text-left px-4 py-3 hover:bg-emerald-50 text-slate-700 text-xs font-semibold transition-colors flex items-center gap-2">
                        <span class="w-5 h-5 rounded bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px]">3</span>
                        Danh sách nhập học
                    </a>
                </div>
            </div>

            <button onclick="window.location.reload()" class="bg-white hover:bg-slate-50 border border-slate-200 text-purple-600 px-3 py-2 rounded-lg transition-colors shadow-sm" title="Làm mới">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="flex bg-slate-50 p-1 rounded-xl mb-6 border border-slate-200 shadow-sm w-max">
        <button @click="activeTab = 'stats'"
            :class="activeTab === 'stats' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'"
            class="px-4 py-2.5 rounded-lg font-bold text-xs transition duration-200 uppercase tracking-wider flex items-center">
            <i class="fas fa-table mr-2"></i> THỐNG KÊ CHI TIẾT
        </button>
        <button @click="activeTab = 'charts'; initCharts();"
            :class="activeTab === 'charts' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'"
            class="px-4 py-2.5 rounded-lg font-bold text-xs transition duration-200 uppercase tracking-wider flex items-center">
            <i class="fas fa-chart-pie mr-2"></i> BIỂU ĐỒ PHÂN TÍCH
        </button>
    </div>

    <!-- Compact Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white px-3.5 py-2.5 rounded-xl shadow-sm border border-slate-200 relative overflow-hidden group">
            <div class="flex justify-between items-center mb-0.5">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Tổng trúng tuyển</p>
                <i class="fas fa-users text-slate-300"></i>
            </div>
            <h3 class="text-lg font-black text-slate-800 leading-tight"><?= number_format($totalTrungTuyen) ?></h3>
        </div>

        <div class="bg-white px-3.5 py-2.5 rounded-xl shadow-sm border border-emerald-100 relative overflow-hidden group">
            <div class="flex justify-between items-center mb-0.5">
                <p class="text-[9px] font-black text-emerald-600 uppercase tracking-wider">Đã nhập học</p>
                <span class="text-[10px] text-emerald-500 font-bold"><?= $admitRate ?>% đạt</span>
            </div>
            <h3 class="text-lg font-black text-emerald-700 leading-tight"><?= number_format($totalNhapHoc) ?></h3>
        </div>

        <div class="bg-white px-3.5 py-2.5 rounded-xl shadow-sm border border-amber-200 relative overflow-hidden group">
            <div class="flex justify-between items-center mb-0.5">
                <p class="text-[9px] font-black text-amber-600 uppercase tracking-wider">Chưa nhập học</p>
                <i class="fas fa-user-clock text-amber-300"></i>
            </div>
            <h3 class="text-lg font-black text-amber-700 leading-tight"><?= number_format($totalTrungTuyen - $totalNhapHoc) ?></h3>
        </div>

        <div class="bg-white px-3.5 py-2.5 rounded-xl shadow-sm border border-purple-100 relative overflow-hidden group">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[9px] font-black text-purple-500 uppercase tracking-wider">Thủ khoa trường</p>
                <i class="fas fa-crown text-purple-300"></i>
            </div>
            <h3 class="text-lg font-black text-purple-700 leading-tight truncate" title="<?= $topStudent ? htmlspecialchars($topStudent['ho_ten'] . ' - ' . $topStudent['ten_nganh']) : '' ?>">
                <?= $topStudent ? htmlspecialchars($topStudent['ho_ten']) : 'Chưa có' ?>
            </h3>
            <span class="text-[10px] text-purple-500 font-bold block truncate">
                <?= $topStudent ? $topStudent['diem_xt'] . ' điểm - ' . htmlspecialchars($topStudent['ten_nganh']) : '' ?>
                <?= $topStudent ? ' <br/><span class="inline-block mt-1 px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded font-black text-[9px] uppercase">' . htmlspecialchars($topStudent['tinh_trang'] ?? '') . '</span>' : '' ?>
            </span>
        </div>
    </div>

    <!-- 5 Hồ sơ mới nhập học gần nhất -->
    <div class="mb-6 bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-4">
            <i class="fas fa-history text-indigo-500 mr-2"></i> 5 Hồ sơ mới nhập học gần nhất
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 text-[10px] uppercase tracking-wider text-slate-500 bg-slate-50">
                        <th class="px-3 py-2 font-bold">Thí sinh</th>
                        <th class="px-3 py-2 font-bold">CCCD</th>
                        <th class="px-3 py-2 font-bold">Ngành</th>
                        <th class="px-3 py-2 font-bold">Thời gian</th>
                        <th class="px-3 py-2 font-bold">Bàn nhập học (Cán bộ)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <?php if (empty($recent)): ?>
                        <tr><td colspan="5" class="px-3 py-4 text-center text-slate-400 font-medium">Chưa có dữ liệu nhập học</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent as $r): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2.5 font-bold text-slate-700"><?= htmlspecialchars($r['ho_ten'] ?? '') ?></td>
                            <td class="px-3 py-2.5 font-mono text-slate-500"><?= htmlspecialchars($r['so_cccd'] ?? '') ?></td>
                            <td class="px-3 py-2.5 text-slate-600"><?= htmlspecialchars($r['ten_nganh'] ?? '') ?></td>
                            <td class="px-3 py-2.5 text-slate-500">
                                <?= date('d/m/Y H:i', strtotime($r['updated_at'] ?? $r['ngay_nhap_hoc'])) ?>
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-indigo-50 text-indigo-700 font-medium">
                                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($r['ten_can_bo'] ?? 'Hệ thống') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB: STATS (Thống kê chi tiết) -->
    <div x-show="activeTab === 'stats'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" style="display: none;" class="space-y-6">
        <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
            <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs lg:text-sm flex items-center mb-6">
                <span class="w-1.5 h-4 bg-emerald-500 rounded-full mr-2"></span>
                Thống kê tiến độ nhập học theo ngành
            </h3>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="premium-table min-w-[800px] lg:min-w-full">
                    <thead class="sticky top-0 z-10 bg-slate-100">
                        <tr class="text-slate-600 uppercase tracking-wider text-[10px] text-center">
                            <th style="width: 80px" class="py-3 border-b-2 border-r border-slate-200 bg-slate-100" rowspan="2">Mã ngành</th>
                            <th class="py-3 border-b-2 border-r border-slate-200 bg-slate-100" rowspan="2">Tên ngành</th>
                            <th style="width: 80px" class="py-3 border-b-2 border-r border-slate-200 bg-slate-100" rowspan="2">Chỉ tiêu</th>
                            <th class="py-1 border-b border-r border-slate-200 bg-blue-50 text-blue-800" colspan="3">Nhập học</th>
                            <th class="py-1 border-b border-r border-slate-200 bg-slate-100 text-slate-800" colspan="3">Xác nhận</th>
                            <th class="py-3 border-b-2 border-slate-200 bg-slate-100 text-slate-800 text-right" rowspan="2">
                                Kinh phí (đ)<br/>
                                <span class="text-[9px] font-bold text-slate-400">Thực tế / Dự kiến</span>
                            </th>
                        </tr>
                        <tr class="text-slate-600 uppercase tracking-wider text-[10px] text-center">
                            <th style="width: 80px" class="py-2 border-b-2 border-r border-slate-200 bg-blue-50 text-blue-800">Tổng TT</th>
                            <th style="width: 80px" class="py-2 border-b-2 border-r border-slate-200 bg-emerald-50 text-emerald-800">Đã nhập học</th>
                            <th style="width: 100px" class="py-2 border-b-2 border-r border-slate-200 bg-blue-50 text-blue-800">Tiến độ (%)</th>
                            <th style="width: 80px" class="py-2 border-b-2 border-r border-slate-200 bg-slate-100 text-slate-800">Bộ</th>
                            <th style="width: 80px" class="py-2 border-b-2 border-r border-slate-200 bg-slate-100 text-slate-800">Trường</th>
                            <th style="width: 80px" class="py-2 border-b-2 border-r border-slate-200 bg-slate-100 text-slate-800">K.Phí</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalCT = 0; $totalTT = 0; $totalNH = 0;
                        $totalXnBo = 0; $totalXnTruong = 0; $totalXnKinhPhi = 0; $totalKinhPhi = 0; $totalKinhPhiDuKien = 0;
                        foreach ($statsByMajor as $ms): 
                            $ct = intval($ms['chi_tieu'] ?? 0);
                            $tt = intval($ms['so_trung_tuyen'] ?? 0);
                            $nh = intval($ms['so_nhap_hoc'] ?? 0);
                            
                            $totalCT += $ct; $totalTT += $tt; $totalNH += $nh;
                            $totalXnBo += intval($ms['xac_nhan_bo']);
                            $totalXnTruong += intval($ms['xac_nhan_truong']);
                            $totalXnKinhPhi += intval($ms['xac_nhan_kinh_phi'] ?? 0);
                            $totalKinhPhi += floatval($ms['tong_kinh_phi']);
                            $totalKinhPhiDuKien += floatval($ms['tong_kinh_phi_du_kien'] ?? 0);
                            
                            $pct = $tt > 0 ? round(($nh / $tt) * 100, 1) : 0;
                            $barColor = '';
                            if ($pct >= 80) $barColor = 'bg-emerald-500';
                            elseif ($pct >= 50) $barColor = 'bg-indigo-500';
                            else $barColor = 'bg-amber-500';
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="text-center font-mono text-slate-600 font-bold"><?= $ms['ma_nganh'] ?></td>
                            <td class="font-bold text-slate-800 text-left"><?= htmlspecialchars($ms['ten_nganh'] ?? '') ?></td>
                            <td class="text-center font-bold text-slate-600 bg-slate-50/50"><?= $ct ?: '-' ?></td>
                            <td class="text-center font-black text-indigo-700"><?= $tt ?: '-' ?></td>
                            <td class="text-center font-black text-emerald-600 bg-emerald-50/30"><?= $nh ?: '-' ?></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full <?= $barColor ?> rounded-full" style="width: <?= min($pct, 100) ?>%"></div>
                                    </div>
                                    <span class="text-[10px] font-black w-8 text-right <?= $pct >= 80 ? 'text-emerald-600' : 'text-slate-500' ?>"><?= $pct ?>%</span>
                                </div>
                            </td>
                            <td class="text-center font-bold text-blue-600"><?= $ms['xac_nhan_bo'] ?: '-' ?></td>
                            <td class="text-center font-bold text-indigo-600"><?= $ms['xac_nhan_truong'] ?: '-' ?></td>
                            <td class="text-center font-bold text-teal-600"><?= ($ms['xac_nhan_kinh_phi'] ?? 0) ?: '-' ?></td>
                            <td class="text-right font-mono font-bold whitespace-nowrap">
                                <span class="text-emerald-600" title="Thực tế (đã thu)"><?= $ms['tong_kinh_phi'] > 0 ? number_format($ms['tong_kinh_phi']) : '0' ?></span>
                                <span class="text-slate-300 mx-0.5">/</span>
                                <span class="text-indigo-600" title="Dự kiến (đã xác nhận)"><?= $ms['tong_kinh_phi_du_kien'] > 0 ? number_format($ms['tong_kinh_phi_du_kien']) : '0' ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-slate-50 font-bold text-slate-800 border-t-2 border-slate-200">
                        <tr>
                            <td colspan="2" class="text-right uppercase font-bold text-slate-700">Tổng cộng:</td>
                            <td class="text-center bg-slate-100/50 text-slate-700 font-bold"><?= number_format($totalCT) ?></td>
                            <td class="text-center text-indigo-700 font-black"><?= number_format($totalTT) ?></td>
                            <td class="text-center text-emerald-700 font-black bg-emerald-50/30"><?= number_format($totalNH) ?></td>
                            <td>
                                <?php $totalPct = $totalTT > 0 ? round(($totalNH / $totalTT) * 100, 1) : 0; ?>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: <?= min($totalPct, 100) ?>%"></div>
                                    </div>
                                    <span class="text-[10px] font-black w-8 text-right text-emerald-600"><?= $totalPct ?>%</span>
                                </div>
                            </td>
                            <td class="text-center text-blue-600"><?= number_format($totalXnBo) ?></td>
                            <td class="text-center text-indigo-600"><?= number_format($totalXnTruong) ?></td>
                            <td class="text-center text-teal-600"><?= number_format($totalXnKinhPhi) ?></td>
                            <td class="text-right font-mono font-bold whitespace-nowrap">
                                <span class="text-emerald-700" title="Thực tế (đã thu)"><?= number_format($totalKinhPhi) ?></span>
                                <span class="text-slate-300 mx-0.5">/</span>
                                <span class="text-indigo-700" title="Dự kiến (đã xác nhận)"><?= number_format($totalKinhPhiDuKien) ?></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB: CHARTS (Biểu đồ phân tích) -->
    <div x-show="activeTab === 'charts'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" style="display: none;" class="space-y-6">
        
        <!-- Major Enrollment Fill Chart -->
        <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-emerald-500">
            <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                Biểu đồ tỷ lệ lấp đầy chuyên ngành (Theo số đã nhập học / Chỉ tiêu)
            </h3>
            <div class="relative h-96">
                <canvas id="majorFillChart"></canvas>
            </div>
        </div>

        <!-- Daily Enrollment Chart -->
        <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-indigo-500 mt-6">
            <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                <span class="w-1.5 h-4 bg-indigo-500 rounded-full mr-2"></span>
                Số thí sinh xác nhận nhập học theo ngày
            </h3>
            <div class="relative h-96">
                <canvas id="dailyEnrollmentChart"></canvas>
            </div>
        </div>

        <!-- Row 2: Four statistics charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-pink-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">Phân bố Giới tính</h3>
                <div class="relative h-64"><canvas id="genderChart"></canvas></div>
            </div>

            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-sky-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">Khu vực ưu tiên</h3>
                <div class="relative h-64"><canvas id="areaChart"></canvas></div>
            </div>

            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-amber-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">Đối tượng ưu tiên</h3>
                <div class="relative h-64"><canvas id="objectChart"></canvas></div>
            </div>
            
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-blue-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">Xác nhận Hệ thống Bộ</h3>
                <div class="relative h-64"><canvas id="xnBoChart"></canvas></div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-indigo-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">Theo Bàn nhập học (Cán bộ)</h3>
                <div class="relative h-64"><canvas id="userChart"></canvas></div>
            </div>

            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-green-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">Tình trạng Nộp Kinh phí</h3>
                <div class="relative h-64"><canvas id="feeChart"></canvas></div>
            </div>
            
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-purple-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">Tỉnh / Thành phố</h3>
                <div class="relative h-64"><canvas id="provinceChart"></canvas></div>
            </div>
        </div>

        <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-rose-500 mt-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs">Trường THPT</h3>
                <button type="button" onclick="toggleShowAllSchools()" 
                        class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-bold transition cursor-pointer">
                    <span id="btnToggleSchoolsText">Xem thêm</span>
                </button>
            </div>
            <div class="relative h-64"><canvas id="schoolChart"></canvas></div>
        </div>
    </div>
</div>

<?php 
// Chart Data preparation
$majorLabels = [];
$majorData = [];
$majorQuota = [];
foreach ($statsByMajor as $ms) {
    $majorLabels[] = $ms['ma_nganh'];
    $majorData[] = intval($ms['so_nhap_hoc'] ?? 0);
    $majorQuota[] = intval($ms['chi_tieu'] ?? 0);
}

$dailyLabels = [];
$dailyData = [];
if (!empty($chartDist['daily_enrollment'])) {
    foreach ($chartDist['daily_enrollment'] as $row) {
        $dailyLabels[] = date('d/m/Y', strtotime($row['date']));
        $dailyData[] = (int)$row['count'];
    }
}

$chartData = [
    'majors' => ['labels' => $majorLabels, 'data' => $majorData, 'quota' => $majorQuota],
    'gender' => ['labels' => array_keys($chartDist['gender']), 'data' => array_values($chartDist['gender'])],
    'area' => ['labels' => array_keys($chartDist['area']), 'data' => array_values($chartDist['area'])],
    'object' => ['labels' => array_keys($chartDist['object']), 'data' => array_values($chartDist['object'])],
    'xnBo' => ['labels' => array_keys($chartDist['xn_bo']), 'data' => array_values($chartDist['xn_bo'])],
    'kinhPhi' => ['labels' => array_keys($chartDist['kinh_phi']), 'data' => array_values($chartDist['kinh_phi'])],
    'users' => ['labels' => array_keys($chartDist['users'] ?? []), 'data' => array_values($chartDist['users'] ?? [])],
    'province' => ['labels' => array_keys(array_slice($chartDist['province'], 0, 15)), 'data' => array_values(array_slice($chartDist['province'], 0, 15))],
    'schoolTop20' => ['labels' => array_keys(array_slice($chartDist['school'], 0, 20)), 'data' => array_values(array_slice($chartDist['school'], 0, 20))],
    'schoolAll' => ['labels' => array_keys($chartDist['school']), 'data' => array_values($chartDist['school'])],
    'dailyEnrollment' => ['labels' => $dailyLabels, 'data' => $dailyData]
];
?>

<script>
const CHART_DATA = <?= json_encode($chartData) ?>;
let chartsInitialized = false;
let schoolChartInstance = null;
let showingAllSchools = false;

function renderEnrollmentCharts() {
    if (chartsInitialized) return;
    if (typeof Chart === 'undefined') {
        setTimeout(renderEnrollmentCharts, 200);
        return;
    }
    
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    
    // Major Fill Chart
    new Chart(document.getElementById('majorFillChart'), {
        type: 'bar',
        data: {
            labels: CHART_DATA.majors.labels,
            datasets: [
                {
                    label: 'Đã nhập học',
                    data: CHART_DATA.majors.data,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderRadius: 4,
                },
                {
                    label: 'Chỉ tiêu',
                    data: CHART_DATA.majors.quota,
                    backgroundColor: 'rgba(226, 232, 240, 0.6)',
                    borderWidth: 1,
                    borderColor: '#cbd5e1',
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [4, 4] } },
                x: { grid: { display: false } }
            },
            plugins: { legend: { position: 'top' } }
        }
    });

    // Daily Enrollment Chart
    new Chart(document.getElementById('dailyEnrollmentChart'), {
        type: 'line',
        data: {
            labels: CHART_DATA.dailyEnrollment.labels,
            datasets: [{
                label: 'Số thí sinh',
                data: CHART_DATA.dailyEnrollment.data,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#6366f1',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { borderDash: [4, 4] },
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                },
                x: { 
                    grid: { display: false },
                    ticks: { maxRotation: 45, minRotation: 45, font: { size: 9 } }
                }
            },
            plugins: {
                legend: { position: 'top' }
            }
        }
    });

    // Helper for Pie Charts
    const createPieChart = (elementId, dataObj, colors) => {
        new Chart(document.getElementById(elementId), {
            type: 'doughnut',
            data: {
                labels: dataObj.labels,
                datasets: [{
                    data: dataObj.data,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
                },
                cutout: '65%'
            }
        });
    };

    createPieChart('genderChart', CHART_DATA.gender, ['#3b82f6', '#ec4899', '#94a3b8']);
    createPieChart('areaChart', CHART_DATA.area, ['#0ea5e9', '#38bdf8', '#7dd3fc', '#e0f2fe', '#cbd5e1']);
    createPieChart('objectChart', CHART_DATA.object, ['#f59e0b', '#fbbf24', '#fcd34d', '#fef3c7', '#cbd5e1']);
    createPieChart('xnBoChart', CHART_DATA.xnBo, ['#3b82f6', '#cbd5e1']);
    createPieChart('feeChart', CHART_DATA.kinhPhi, ['#10b981', '#cbd5e1']);
    createPieChart('userChart', CHART_DATA.users, ['#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#f97316']);

    // Province Chart
    new Chart(document.getElementById('provinceChart'), {
        type: 'bar',
        data: {
            labels: CHART_DATA.province.labels,
            datasets: [{
                label: 'Số lượng nhập học',
                data: CHART_DATA.province.data,
                backgroundColor: 'rgba(168, 85, 247, 0.8)',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [4, 4] } },
                x: { grid: { display: false }, ticks: { maxRotation: 45, minRotation: 45, font: {size: 9} } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // School Chart Init
    renderSchoolChart(false);
    
    chartsInitialized = true;
}

function renderSchoolChart(showAll) {
    if (schoolChartInstance) schoolChartInstance.destroy();
    const dataObj = showAll ? CHART_DATA.schoolAll : CHART_DATA.schoolTop20;
    
    schoolChartInstance = new Chart(document.getElementById('schoolChart'), {
        type: 'bar',
        data: {
            labels: dataObj.labels,
            datasets: [{
                label: 'Thí sinh',
                data: dataObj.data,
                backgroundColor: 'rgba(244, 63, 94, 0.8)',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [4, 4] } },
                x: { grid: { display: false }, ticks: { maxRotation: 90, minRotation: 45, font: {size: 9} } }
            },
            plugins: { legend: { display: false } }
        }
    });
}

function toggleShowAllSchools() {
    showingAllSchools = !showingAllSchools;
    document.getElementById('btnToggleSchoolsText').innerText = showingAllSchools ? 'Thu gọn (Top 20)' : 'Xem thêm';
    renderSchoolChart(showingAllSchools);
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>
