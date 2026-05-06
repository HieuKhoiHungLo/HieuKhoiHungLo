<?php ob_start(); ?>
<style>
    .premium-table {
        border-collapse: separate !important;
        border-spacing: 0;
        width: 100%;
        table-layout: fixed;
    }
    .premium-table th, .premium-table td {
        padding: 0.75rem 1rem;
        border: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        border-right: 1px solid #e2e8f0 !important;
        vertical-align: middle;
        font-size: 13px;
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
    .premium-table th:first-child, .premium-table td:first-child {
        border-left: 1px solid #e2e8f0 !important;
    }
    .premium-table thead tr:first-child th {
        border-top: 1px solid #e2e8f0 !important;
    }
    .premium-table thead tr:first-child th:first-child {
        border-top-left-radius: 1rem;
    }
    .premium-table thead tr:first-child th:last-child {
        border-top-right-radius: 1rem;
    }
    .premium-table tbody tr:last-child td:first-child {
        border-bottom-left-radius: 1rem;
    }
    .premium-table tbody tr:last-child td:last-child {
        border-bottom-right-radius: 1rem;
    }
</style>
<div id="dashboardRoot" x-data="{
    activeTab: 'overview',
    loadedTabs: [],
    initTab(tab, force = false) {
        if (force || !this.loadedTabs.includes(tab)) {
            window.fetchStats(tab, force);
            if (!this.loadedTabs.includes(tab)) this.loadedTabs.push(tab);
        }
        $nextTick(() => { if (window.renderChartsByTab) window.renderChartsByTab(tab); });
    },
    resetTabs() {
        this.loadedTabs = [];
        this.initTab(this.activeTab, true);
    }
}" x-init="$watch('activeTab', tab => initTab(tab)); $nextTick(() => initTab(activeTab));">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
            <div>
                <h2 class="text-xl lg:text-2xl font-black text-slate-800 font-heading uppercase tracking-tight">Thống kê & Báo cáo</h2>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 bg-indigo-50 border border-indigo-100 rounded-xl">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                <p class="text-[11px] lg:text-xs font-bold text-indigo-700">
                    Đang truy cập: <span id="statOnlineTotal">0</span> 
                    <span class="font-medium text-indigo-500 opacity-80">(Khách: <span id="statOnlineGuests">0</span>, Thí sinh: <span id="statOnlineUsers">0</span>, Quản trị: <span id="statOnlineAdmins">0</span>)</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl mb-6 overflow-x-auto no-scrollbar whitespace-nowrap">
        <button @click="activeTab = 'overview'"
            :class="activeTab === 'overview' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'"
            class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs transition duration-200 uppercase tracking-wider">
            <i class="fas fa-info-circle mr-2"></i>THÔNG TIN HỒ SƠ
        </button>
        <button @click="activeTab = 'majors'"
            :class="activeTab === 'majors' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'"
            class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs transition duration-200 uppercase tracking-wider">
            <i class="fas fa-graduation-cap mr-2"></i>THỐNG KÊ NGUYỆN VỌNG
        </button>
        <button @click="activeTab = 'demographics'"
            :class="activeTab === 'demographics' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'"
            class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs transition duration-200 uppercase tracking-wider">
            <i class="fas fa-chart-line mr-2"></i>BIỂU ĐỒ PHÂN TÍCH
        </button>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex flex-wrap gap-2 lg:gap-3 items-center">
            <select id="filterYear" class="w-full sm:w-auto px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm cursor-pointer">
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= ($selectedYear ?? '') == $y ? 'selected' : '' ?>>
                        Năm <?= $y ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="filterSession" class="w-full sm:w-auto px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm cursor-pointer">
                <option value="">-- Tất cả đợt --</option>
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= $s['id'] ?>" data-year="<?= $s['nam_tuyen_sinh'] ?>" <?= ($currentSessionId ?? '') == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(!empty($s['ma_dot']) ? $s['ma_dot'] : $s['ten_dot']) ?> - <?= $s['nam_tuyen_sinh'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="flex gap-2 w-full sm:w-auto">
                <input type="date" id="filterStart" value="<?= $startDate ?>" class="flex-1 sm:w-36 px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
                <input type="date" id="filterEnd" value="<?= $endDate ?>" class="flex-1 sm:w-36 px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <button id="btnFilter" @click="resetTabs()" class="w-full sm:w-auto px-6 py-2 bg-[#0066FF] text-white font-bold rounded-xl hover:bg-indigo-700 transition shadow-md shadow-blue-200">
                <i class="fas fa-filter mr-2"></i>Lọc
                <i class="fas fa-spinner fa-spin ml-2" id="btnFilterStatsSpinner" style="display: none;"></i>
            </button>
        </div>
    </div>

    <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" class="space-y-8">
        <!-- Unified Stats Cards - Branded HVU Blue -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
            <!-- Primary Card: Total (HVU Brand Blue) -->
            <div class="p-5 rounded-2xl shadow-xl text-white relative overflow-hidden group transition-all duration-300 hover:-translate-y-1" style="background: linear-gradient(135deg, #0066FF 0%, #003D99 100%) !important;">
                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-white/80 text-[10px] font-black uppercase tracking-widest font-heading">Tổng hồ sơ</p>
                            <div class="bg-white/20 p-2 rounded-xl backdrop-blur-md">
                                <i class="fas fa-users-viewfinder text-sm text-white"></i>
                            </div>
                        </div>
                        <p class="text-4xl lg:text-5xl font-black mb-4 tracking-tight font-heading" id="statTotal"><?= $stats['total'] ?></p>
                    </div>
                    <div class="flex flex-col gap-2.5 border-t border-white/20 pt-4 mt-auto">
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span class="text-blue-100 uppercase tracking-tighter">Hôm nay:</span>
                            <span id="recentToday" class="bg-white/20 text-white px-2 py-0.5 rounded-lg font-black backdrop-blur-sm">0</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span class="text-blue-100 uppercase tracking-tighter">Trong tuần:</span>
                            <span id="recentWeek" class="bg-white/20 text-white px-2 py-0.5 rounded-lg font-black backdrop-blur-sm">0</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span class="text-blue-100 uppercase tracking-tighter">Chưa nộp hồ sơ:</span>
                            <span id="statGhostCard" class="bg-white/20 text-white px-2 py-0.5 rounded-lg font-black backdrop-blur-sm"><?= $stats['ghost'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <!-- Brand Accent Decoration -->
                <div class="absolute -top-12 -right-12 w-32 h-32 bg-white/10 rounded-full blur-3xl transition-transform group-hover:scale-125"></div>
            </div>

            <!-- Card: Approved -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest font-heading mb-1">Đã duyệt</p>
                        <p class="text-3xl font-black text-slate-900 tracking-tight font-heading" id="statApproved"><?= $stats['approved'] ?></p>
                    </div>
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl border border-emerald-100/50">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between mb-1.5 px-0.5">
                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-wider">Tỷ lệ duyệt</span>
                        <span class="text-[11px] font-black text-emerald-600" id="approvalRate"><?= $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100, 1) : 0 ?>%</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div id="approvalRateBar" class="h-full bg-emerald-500 rounded-full transition-all duration-1000" style="width: <?= $stats['total'] > 0 ? ($stats['approved'] / $stats['total']) * 100 : 0 ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Card: Pending -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest font-heading mb-1">Chờ duyệt</p>
                        <p class="text-3xl font-black text-slate-900 tracking-tight font-heading" id="statPending"><?= $stats['pending'] ?></p>
                    </div>
                    <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl border border-amber-100/50">
                        <i class="fas fa-hourglass-half text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="inline-flex items-center px-2.5 py-1 bg-amber-50 text-amber-700 rounded-lg text-[9px] font-black uppercase tracking-wider border border-amber-200/50">
                        <i class="fas fa-bolt mr-1.5 opacity-70"></i> <span id="statEditRequests">0</span> Cần xử lý ngay
                    </span>
                </div>
            </div>

            <!-- Card: Require Edit -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest font-heading mb-1">Yêu cầu sửa</p>
                        <p class="text-3xl font-black text-slate-900 tracking-tight font-heading" id="statRequireEdit"><?= $stats['require_edit'] ?? 0 ?></p>
                    </div>
                    <div class="p-3 bg-orange-50 text-orange-600 rounded-2xl border border-orange-100/50">
                        <i class="fas fa-edit text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="w-full h-2 bg-slate-50 rounded-full border border-slate-100"></div>
                </div>
            </div>
        </div>

        <!-- Latest Candidates -->
        <div class="mb-8">
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs lg:text-sm flex items-center">
                        <span class="w-1.5 h-4 bg-indigo-600 rounded-full mr-2"></span>
                        5 Hồ sơ mới nhất
                    </h3>
                    <a href="<?= url('/admin/candidates') ?>" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 transition bg-indigo-50 px-3 py-1.5 rounded-lg flex items-center">
                        XEM TẤT CẢ <i class="fas fa-chevron-right ml-1.5 text-[8px]"></i>
                    </a>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th style="width: 40%">Thí sinh</th>
                                <th style="width: 25%">CCCD</th>
                                <th style="width: 15%">Thời gian</th>
                                <th style="width: 20%" class="text-right">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody id="latestCandidatesBody">
                            <!-- Dynamic Content -->
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div id="latestCandidatesCards" class="md:hidden space-y-3">
                    <!-- Dynamic Cards injected by JS -->
                    <div class="text-center py-4 text-slate-400 text-xs">Đang tải dữ liệu...</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Chart: Daily Registrations (Moved from demographics) -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-6 text-xs lg:text-sm flex items-center">
                    <span class="w-1.5 h-4 bg-indigo-500 rounded-full mr-2"></span>
                    Hồ sơ đăng ký theo ngày
                </h3>
                <div class="relative h-[300px] lg:h-96">
                    <canvas id="dailyRegistrationChart"></canvas>
                    <div id="chartLoadingSpinner" class="absolute inset-0 flex items-center justify-center bg-white/70 hidden z-10 rounded-xl backdrop-blur-sm">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-circle-notch fa-spin text-[#0066FF] text-3xl mb-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart: Admissions by Major (Shifted to 50% width or kept as grid) -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-6 text-xs lg:text-sm flex items-center">
                    <span class="w-1.5 h-4 bg-indigo-500 rounded-full mr-2"></span>
                    Hồ sơ theo ngành
                </h3>
                <div class="relative h-[300px] lg:h-96">
                    <canvas id="majorChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div x-show="activeTab === 'majors'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4">
        <div class="mb-8">
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs lg:text-sm flex items-center">
                        <span class="w-1.5 h-4 bg-emerald-500 rounded-full mr-2"></span>
                        Thống kê nguyện vọng theo ngành
                    </h3>
                </div>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="premium-table min-w-[600px] lg:min-w-full">
                        <thead>
                            <tr>
                                <th style="width: 120px; text-align: center !important;" rowspan="2">Mã ngành</th>
                                <th style="text-align: center !important;" rowspan="2">Tên ngành</th>
                                <th style="width: 100px; text-align: center !important;" rowspan="2">Chỉ tiêu</th>
                                <th style="text-align: center !important;" colspan="4">Thống kê nguyện vọng</th>
                            </tr>
                            <tr>
                                <th style="width: 80px; text-align: center !important;">Tổng</th>
                                <th style="width: 120px; text-align: center !important;">Nguyện vọng 1</th>
                                <th style="width: 120px; text-align: center !important;">Nguyện vọng 2</th>
                                <th style="width: 140px; text-align: center !important;">NV còn lại</th>
                            </tr>
                        </thead>
                        <tbody id="detailedMajorStatsBody">
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-400 font-medium">Đang tải dữ liệu...</td>
                            </tr>
                        </tbody>
                        <tfoot id="detailedMajorStatsFoot" class="bg-slate-50 font-bold text-slate-800 border-t-2 border-slate-200">
                            <!-- Total row will be injected here -->
                        </tfoot>
                    </table>
                </div>
                <div class="mt-4 text-[10px] text-slate-400 italic md:hidden">
                    <i class="fas fa-info-circle mr-1"></i> Vuốt ngang để xem đầy đủ bảng thống kê
                </div>
            </div>
        </div>
    </div>

    <div x-show="activeTab === 'demographics'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4">
        <div class="grid grid-cols-1 gap-6 mb-8">
            <!-- Chart: Top Province (Full Width) -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-6 text-xs lg:text-sm flex items-center">
                    <span class="w-1.5 h-4 bg-indigo-500 rounded-full mr-2"></span>
                    Top Tỉnh/Thành phố
                </h3>
                <div class="relative h-[350px] lg:h-96">
                    <canvas id="provinceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Status (25%) -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-4 text-xs">Trạng thái hồ sơ</h3>
                <div class="relative h-60">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            <!-- Gender (25%) -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-4 text-xs">Giới tính</h3>
                <div class="relative h-60">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>
            <!-- Area (25%) -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-4 text-xs">Khu vực</h3>
                <div class="relative h-60">
                    <canvas id="areaChart"></canvas>
                </div>
            </div>
            <!-- Object (25%) -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-4 text-xs">Đối tượng</h3>
                <div class="relative h-60">
                    <canvas id="objectChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 mb-8">
            <!-- Chart: Top High Schools (Full Width) -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-6 text-xs lg:text-sm flex items-center">
                    <span class="w-1.5 h-4 bg-indigo-500 rounded-full mr-2"></span>
                    Top Trường THPT
                </h3>
                <div class="relative h-[450px] lg:h-96">
                    <canvas id="schoolChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Thống kê hệ thống -->
        <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs lg:text-sm flex items-center">
                    <span class="w-1.5 h-4 bg-[#0066FF] rounded-full mr-2"></span>
                    Thống kê hệ thống
                </h3>
            </div>
            <div class="overflow-x-auto rounded-xl border border-slate-100">
                <table class="premium-table w-full text-left border-collapse">
                    <thead class="bg-[#F8FAFF] text-[10px] uppercase font-black text-slate-500 tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4" style="min-width: 300px;">Nội dung thống kê</th>
                            <th class="px-6 py-4 text-center" style="width: 150px;">Tổng số</th>
                            <th class="px-6 py-4 text-center" style="width: 150px;">Trong tuần</th>
                            <th class="px-6 py-4 text-center" style="width: 150px;">Trong ngày</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-slate-600">
                        <!-- Trang tính điểm -->
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <i class="fas fa-eye text-[10px]"></i>
                                    </div>
                                    <span class="text-xs font-medium">Lượng truy cập trang tính điểm xét tuyển</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-medium"><?= number_format($visitStats['total_visits'] ?? 0) ?></td>
                            <td class="px-6 py-4 text-center text-sm font-medium"><?= number_format($visitStats['weekly_visits'] ?? 0) ?></td>
                            <td class="px-6 py-4 text-center text-sm font-medium"><?= number_format($visitStats['daily_visits'] ?? 0) ?></td>
                        </tr>
                        <!-- Email gửi -->
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                        <i class="fas fa-paper-plane text-[10px]"></i>
                                    </div>
                                    <span class="text-xs font-medium">Số email gửi thành công</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-medium"><?= number_format($emailStats['total_sent'] ?? 0) ?></td>
                            <td class="px-6 py-4 text-center text-sm font-medium"><?= number_format($emailStats['weekly_sent'] ?? 0) ?></td>
                            <td class="px-6 py-4 text-center text-sm font-medium"><?= number_format($emailStats['daily_sent'] ?? 0) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mb-8">
            <!-- Table: Thống kê người duyệt -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs lg:text-sm flex items-center">
                        <span class="w-1.5 h-4 bg-indigo-500 rounded-full mr-2"></span>
                        Thống kê người duyệt
                    </h3>
                </div>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="premium-table min-w-[500px] lg:min-w-full">
                        <thead>
                            <tr>
                                <th>Họ tên cán bộ</th>
                                <th>Tên đăng nhập</th>
                                <th style="width: 120px" class="text-center">Đã duyệt</th>
                                <th style="width: 120px" class="text-center">Yêu cầu sửa</th>
                            </tr>
                        </thead>
                        <tbody id="reviewerTableBody">
                            <tr>
                                <td colspan="4" class="py-10 text-center text-slate-400 font-medium text-xs">Đang tải dữ liệu...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div> <!-- End x-data -->
<script src="https://cdn.jsdelivr.net/npm/chart.js" async></script>
<script>
    // Initialize global chart management
    window.lastStatsData = {};
    window.charts = {};
    let isFetching = false;

    // Global Chart Helper Functions
    function safeUpdateChart(id, fn) {
        try {
            const canvas = document.getElementById(id);
            if (canvas) {
            /* console.log(`Checking chart ${id}: ${canvas.offsetWidth}x${canvas.offsetHeight}`); */
            if (canvas) {
                if (canvas.offsetWidth === 0 || canvas.offsetHeight === 0) {
                    /* console.warn(`!!!! Warning: Chart ${id} has zero dimensions.`); */
                }
            }
            }
            fn();
        } catch (e) {
            console.error(`!!!! Error updating chart ${id}:`, e);
        }
    }

    function updateChart(canvasId, type, data, labelKey, valueKey, label) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (window.charts[canvasId]) window.charts[canvasId].destroy();

        /* console.log(`Updating ${canvasId} (Line) with data:`, data); */
        if (!data || !Array.isArray(data) || data.length === 0) {
            /* console.warn(`No data for ${canvasId}`); */
            return;
        }

        window.charts[canvasId] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => item[labelKey]),
                datasets: [{
                    label: label,
                    data: data.map(item => item[valueKey]),
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: window.innerWidth < 768 ? 10 : 12
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 9
                            },
                            maxRotation: 45
                        }
                    },
                    y: {
                        ticks: {
                            font: {
                                size: 9
                            }
                        }
                    }
                }
            }
        });
    }

    function updatePieChart(canvasId, overview) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (window.charts[canvasId]) window.charts[canvasId].destroy();

        /* console.log(`Updating ${canvasId} (Pie/Status) with data:`, overview); */
        if (!overview || (parseInt(overview.approved) === 0 && parseInt(overview.pending) === 0 && parseInt(overview.require_edit) === 0)) {
            /* console.warn(`No data for ${canvasId}`); */
            return;
        }

        window.charts[canvasId] = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Đã duyệt', 'Chờ duyệt', 'Yêu cầu sửa'],
                datasets: [{
                    data: [overview.approved, overview.pending, overview.require_edit],
                    backgroundColor: ['#10b981', '#f59e0b', '#f97316'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }

    function updateBarChart(canvasId, data, labelKey, valueKey, label) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (window.charts[canvasId]) window.charts[canvasId].destroy();

        /* console.log(`Updating ${canvasId} (Bar) with data:`, data); */
        if (!data || !Array.isArray(data) || data.length === 0) {
            /* console.warn(`No data for ${canvasId}`); */
            return;
        }

        const isMobile = window.innerWidth < 768;
        const isVertical = canvasId === 'majorChart' || canvasId === 'schoolChart';

        window.charts[canvasId] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item[labelKey]),
                datasets: [{
                    label: label,
                    data: data.map(item => item[valueKey]),
                    backgroundColor: '#6366f1',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: isVertical ? 'x' : 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: (canvasId === 'majorChart' || canvasId === 'schoolChart') ? 8 : (isMobile ? 9 : 11)
                            },
                            maxRotation: isVertical ? 45 : 0,
                            minRotation: isVertical ? 45 : 0,
                            autoSkip: false
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        ticks: {
                            font: {
                                size: 9
                            }
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function updatePieChartGeneric(canvasId, data, label) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (window.charts[canvasId]) window.charts[canvasId].destroy();

        /* console.log(`Updating ${canvasId} with data:`, data); */
        if (!data || !Array.isArray(data) || data.length === 0) {
            /* console.warn(`No data for ${canvasId}`); */
            return;
        }

        window.charts[canvasId] = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.map(item => item.label),
                datasets: [{
                    label: label,
                    data: data.map(item => item.count),
                    backgroundColor: ['#4f46e5', '#f43f5e', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6', '#f97316', '#64748b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    }

    // Global Tab Rendering
    window.renderChartsByTab = function(tabName) {
        if (!window.lastStatsData) return;
        if (typeof Chart === 'undefined') {
            /* console.error("Chart.js is not loaded yet!"); */
            return;
        }
        const data = window.lastStatsData;
        /* console.log(`Rendering charts for tab: ${tabName}`); */

        // Use a longer delay to ensure Alpine.js transition is complete
        setTimeout(() => {
            if (tabName === 'overview') {
                if (data.daily) safeUpdateChart('dailyRegistrationChart', () => updateChart('dailyRegistrationChart', 'line', data.daily, 'date', 'count', 'Số lượng hồ sơ'));
                if (data.major) safeUpdateChart('majorChart', () => updateBarChart('majorChart', data.major, 'ten_nganh', 'count', 'Hồ sơ theo ngành'));
            } else if (tabName === 'demographics') {
                if (data.overview) safeUpdateChart('statusChart', () => updatePieChart('statusChart', data.overview));
                if (data.province) safeUpdateChart('provinceChart', () => updateBarChart('provinceChart', data.province, 'label', 'count', 'Hồ sơ theo tỉnh'));
                if (data.school) safeUpdateChart('schoolChart', () => updateBarChart('schoolChart', data.school, 'label', 'count', 'Hồ sơ theo trường'));
                if (data.gender) safeUpdateChart('genderChart', () => updatePieChartGeneric('genderChart', data.gender, 'Giới tính'));
                if (data.area) safeUpdateChart('areaChart', () => updatePieChartGeneric('areaChart', data.area, 'Khu vực'));
                if (data.object) safeUpdateChart('objectChart', () => updatePieChartGeneric('objectChart', data.object, 'Đối tượng'));
            }

            // Trigger resize twice to handle fluid layout shifts
            window.dispatchEvent(new Event('resize'));
            setTimeout(() => window.dispatchEvent(new Event('resize')), 200);
            /* console.log(`Charts for tab ${tabName} rendered successfully.`); */
        }, 500);
    };

    window.refreshActiveTabCharts = function() {
        const root = document.getElementById('dashboardRoot');
        if (root && typeof Alpine !== 'undefined') {
            const data = Alpine.$data(root);
            if (data && data.activeTab) {
                window.renderChartsByTab(data.activeTab);
            }
        }
    };

    window.fetchStats = function(type = 'overview', forceRefresh = false) {
        if (isFetching) return;

        const btnFilter = document.getElementById('btnFilter');
        const buttonSpinner = document.getElementById('btnFilterStatsSpinner');
        const chartSpinner = document.getElementById('chartLoadingSpinner');

        // UI Loading State Start
        isFetching = true;
        if (buttonSpinner) buttonSpinner.style.display = 'inline-block';
        if (chartSpinner) chartSpinner.style.display = 'flex';
        if (btnFilter) btnFilter.disabled = true;

        const params = new URLSearchParams({
            type: type,
            year: document.getElementById('filterYear')?.value || '',
            session_id: document.getElementById('filterSession')?.value || '',
            start: document.getElementById('filterStart')?.value || '',
            end: document.getElementById('filterEnd')?.value || ''
        });

        if (forceRefresh) {
            params.append('refresh', '1');
        }

        // Use a 20s timeout for slow network/Supabase cold start
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 20000);

        fetch(`<?= url('/admin/stats/api?') ?>${params.toString()}`, {
                signal: controller.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                /* console.log(`Stats Data (${type}) Received:`, data); */
                // Merge into global persistent data
                window.lastStatsData = { ...window.lastStatsData, ...data };
                updateUI(data, type);

                // Re-render charts for CURRENT tab
                const root = document.getElementById('dashboardRoot');
                if (root && typeof Alpine !== 'undefined') {
                    const alpine = Alpine.$data(root);
                    if (alpine && alpine.activeTab === type) {
                        setTimeout(() => window.renderChartsByTab(type), 100);
                    }
                }
            })
            .catch(error => {
                console.error(`Dashboard Fetch Error (${type}):`, error);
                if (error.name !== 'AbortError' && typeof showToast === 'function') {
                    showToast(`Không thể tải dữ liệu ${type}. Vui lòng thử lại.`, "error");
                }
            })
            .finally(() => {
                clearTimeout(timeoutId);
                isFetching = false;
                if (buttonSpinner) buttonSpinner.style.display = 'none';
                if (chartSpinner) chartSpinner.style.display = 'none';
                if (btnFilter) btnFilter.disabled = false;
            });
    };

    function updateUI(data, type) {
        if (!data) return;

        if (type === 'overview') {
            // Stats Cards
            const ov = data.overview || {};
            const map = {
                'statTotal': ov.total,
                'statApproved': ov.approved,
                'statPending': ov.pending,
                'statRequireEdit': ov.require_edit,
                'statGhostCard': ov.ghost,
                'statEditRequests': ov.edit_requests
            };
            for (const [id, val] of Object.entries(map)) {
                const el = document.getElementById(id);
                if (el) el.textContent = val ?? 0;
            }

            // Online Stats
            if (data.online_stats) {
                const os = data.online_stats;
                if (document.getElementById('statOnlineTotal')) document.getElementById('statOnlineTotal').textContent = os.total;
                if (document.getElementById('statOnlineGuests')) document.getElementById('statOnlineGuests').textContent = os.guests;
                if (document.getElementById('statOnlineUsers')) document.getElementById('statOnlineUsers').textContent = os.users;
                if (document.getElementById('statOnlineAdmins')) document.getElementById('statOnlineAdmins').textContent = os.admins;
            }

            const rateEl = document.getElementById('approvalRate');
            const rateBar = document.getElementById('approvalRateBar');
            if (rateEl) {
                const total = parseInt(ov.total) || 0;
                const approved = parseInt(ov.approved) || 0;
                const percentage = total > 0 ? (approved / total * 100).toFixed(1) : 0;
                rateEl.textContent = percentage + '%';
                if (rateBar) {
                    rateBar.style.width = percentage + '%';
                }
            }

            if (document.getElementById('recentToday') && data.recent) document.getElementById('recentToday').textContent = data.recent.today;
            if (document.getElementById('recentWeek') && data.recent) document.getElementById('recentWeek').textContent = data.recent.this_week;

            // Build Latest Candidates
            const body = document.getElementById('latestCandidatesBody');
            const cards = document.getElementById('latestCandidatesCards');
            if ((body || cards) && data.latest) {
                if (body) body.innerHTML = '';
                if (cards) cards.innerHTML = '';

                if (data.latest.length > 0) {
                    data.latest.forEach(c => {
                        const d = new Date(c.created_at);
                        const fDate = (d.getDate() < 10 ? '0' : '') + d.getDate() + '/' + ((d.getMonth() + 1) < 10 ? '0' : '') + (d.getMonth() + 1) + '/' + d.getFullYear();

                        let stHtml = '',
                            stCls = '';
                        if (c.trang_thai === 'Đã duyệt') {
                            stHtml = '<span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold">Đã duyệt</span>';
                            stCls = 'border-emerald-200 bg-emerald-50/30';
                        } else if (c.trang_thai === 'Từ chối') {
                            stHtml = '<span class="px-2.5 py-1 bg-rose-100 text-rose-700 rounded-lg text-xs font-bold">Từ chối</span>';
                            stCls = 'border-rose-200 bg-rose-50/30';
                        } else {
                            stHtml = '<span class="px-2.5 py-1 bg-amber-100 text-amber-700 rounded-lg text-xs font-bold">Chờ duyệt</span>';
                            stCls = 'border-amber-200 bg-amber-50/30';
                        }

                        if (body) {
                            const tr = document.createElement('tr');
                            tr.className = 'hover:bg-slate-50/50 transition';
                            tr.innerHTML = `<td class="font-bold text-slate-700">${c.ho_ten}</td><td class="text-slate-500 font-mono text-xs">${c.so_cccd}</td><td class="text-slate-500 text-xs">${fDate}</td><td class="text-right">${stHtml}</td>`;
                            body.appendChild(tr);
                        }
                        if (cards) {
                            const div = document.createElement('div');
                            div.className = `p-4 rounded-xl border ${stCls} flex justify-between items-center transition active:scale-[0.98]`;
                            div.innerHTML = `<div class="space-y-1"><div class="font-bold text-slate-800 text-sm">${c.ho_ten}</div><div class="text-[10px] text-slate-500 flex items-center gap-2"><span class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-100">${c.so_cccd}</span><span>${fDate}</span></div></div><div>${stHtml}</div>`;
                            cards.appendChild(div);
                        }
                    });
                } else {
                    const msg = '<tr><td colspan="4" class="py-6 text-center text-slate-400 font-medium text-xs">Chưa có hồ sơ mới.</td></tr>';
                    if (body) body.innerHTML = msg;
                    if (cards) cards.innerHTML = `<div class="p-6 text-center text-slate-400 text-xs font-medium bg-slate-50 rounded-xl border border-dashed border-slate-200">Chưa có hồ sơ mới.</div>`;
                }
            }
        }

        if (type === 'majors') {
            // Detailed Major Stats
            const db = document.getElementById('detailedMajorStatsBody');
            const df = document.getElementById('detailedMajorStatsFoot');
            if (db && df && data.detailed_major_stats) {
                db.innerHTML = '';
                let tt = 0,
                    ta = 0,
                    n1 = 0,
                    n2 = 0,
                    cl = 0;
                if (data.detailed_major_stats.length > 0) {
                    data.detailed_major_stats.forEach(m => {
                        const r = document.createElement('tr');
                        const t = parseInt(m.chi_tieu) || 0,
                            tn = parseInt(m.tong_nv) || 0,
                            nv1 = parseInt(m.nv1) || 0,
                            nv2 = parseInt(m.nv2) || 0,
                            c = parseInt(m.nv_con_lai) || 0;
                        tt += t;
                        ta += tn;
                        n1 += nv1;
                        n2 += nv2;
                        cl += c;
                        r.className = 'hover:bg-slate-50/50 transition';
                        r.innerHTML = `<td class="text-center text-slate-800">${m.ma_nganh}</td><td class="text-slate-800">${m.ten_nganh}</td><td class="text-center text-slate-800">${t>0?t:'-'}</td><td class="text-center text-slate-800">${tn}</td><td class="text-center text-red-600 font-bold">${nv1}</td><td class="text-center text-slate-800">${nv2}</td><td class="text-center text-slate-800">${c}</td>`;
                        db.appendChild(r);
                    });
                    df.innerHTML = `<tr><td class="text-right font-black pr-4" colspan="2">TỔNG CỘNG</td><td class="text-center text-slate-800 font-black">${tt>0?tt:'-'}</td><td class="text-center text-slate-800 font-black">${ta}</td><td class="text-center text-red-600 font-black">${n1}</td><td class="text-center text-slate-800 font-black">${n2}</td><td class="text-center text-slate-800 font-black">${cl}</td></tr>`;
                } else {
                    db.innerHTML = '<tr><td colspan="7" class="py-6 text-center text-slate-400 font-medium">Chưa có dữ liệu.</td></tr>';
                    df.innerHTML = '';
                }
            }
        }

        if (type === 'demographics' || type === 'overview') {
            // Reviewer Stats Table Update
            const rb = document.getElementById('reviewerTableBody');
            if (rb && data.reviewers) {
                rb.innerHTML = '';
                if (data.reviewers.length > 0) {
                    data.reviewers.forEach(v => {
                        const r = document.createElement('tr');
                        r.className = 'hover:bg-slate-50/50 transition';
                        r.innerHTML = `
                        <td class="text-slate-700">${v.ho_ten}</td>
                        <td class="text-slate-500 text-xs">${v.ten_dang_nhap}</td>
                        <td class="text-center text-emerald-600">${v.approved_count}</td>
                        <td class="text-center text-orange-600">${v.edit_count}</td>
                    `;
                        rb.appendChild(r);
                    });
                    console.log(`Reviewer table updated with ${data.reviewers.length} rows.`);
                } else {
                    rb.innerHTML = '<tr><td colspan="4" class="py-6 text-center text-slate-400">Chưa có dữ liệu người duyệt.</td></tr>';
                }
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Event Listeners for dependent selects
        document.getElementById('filterYear').addEventListener('change', function() {
            const year = this.value;
            const sessionSelect = document.getElementById('filterSession');
            Array.from(sessionSelect.options).forEach(opt => {
                if (opt.value === "") return;
                const optYear = opt.getAttribute('data-year');
                opt.style.display = (optYear == year) ? 'block' : 'none';
            });
            const selectedOpt = sessionSelect.selectedOptions[0];
            if (selectedOpt && selectedOpt.value !== "" && selectedOpt.style.display === 'none') {
                sessionSelect.value = "";
            }
        });

        // Auto-refresh online stats every 30 seconds
        setInterval(function() {
            // Only refresh if 'overview' tab is active to save resources
            const root = document.getElementById('dashboardRoot');
            if (root && typeof Alpine !== 'undefined') {
                const alpine = Alpine.$data(root);
                if (alpine && alpine.activeTab === 'overview') {
                    // Fetch with small type to minimize impact
                    // For now, full overview fetch is fine as it's cached
                    window.fetchStats('overview');
                }
            }
        }, 60000); // Refresh every 60s (was 30s) to reduce Supabase load
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin.php';
?>