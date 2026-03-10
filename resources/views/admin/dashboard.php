<?php ob_start(); ?>
<div id="dashboardRoot" x-data="{
    activeTab: 'overview',
    loadedTabs: [],
    initTab(tab) {
        if (!this.loadedTabs.includes(tab)) {
            window.fetchStats(tab);
            this.loadedTabs.push(tab);
        }
        $nextTick(() => { if (window.renderChartsByTab) window.renderChartsByTab(tab); });
    },
    resetTabs() {
        this.loadedTabs = [];
        this.initTab(this.activeTab);
    }
}" x-init="$watch('activeTab', tab => initTab(tab)); $nextTick(() => initTab(activeTab));">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl lg:text-2xl font-black text-slate-800 font-heading uppercase tracking-tight">Thống kê & Báo cáo</h2>
            <p class="text-xs lg:text-sm text-slate-500 font-medium">Tổng quan dữ liệu tuyển sinh</p>
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

    <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4">
        <!-- Overview Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-6 rounded-2xl shadow-lg shadow-indigo-200 text-white relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-indigo-100 text-[10px] font-bold uppercase tracking-wider mb-1">Tổng hồ sơ</p>
                    <p class="text-3xl lg:text-4xl font-black" id="statTotal"><?= $stats['total'] ?></p>
                    <div class="mt-3 flex flex-col sm:flex-row gap-2 sm:gap-4 text-[10px]">
                        <div class="flex items-center gap-1.5 text-indigo-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span>Hồ sơ đăng ký hôm nay: <strong id="recentToday" class="text-white">0</strong></span>
                        </div>
                        <div class="flex items-center gap-1.5 text-indigo-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                            <span>Hồ sơ đăng ký trong tuần: <strong id="recentWeek" class="text-white">0</strong></span>
                        </div>
                    </div>
                </div>
                <i class="fas fa-users absolute -bottom-4 -right-4 text-8xl text-blue-400 opacity-20 transition transform group-hover:scale-110"></i>
            </div>

            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 lg:block">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl lg:mb-4">
                    <i class="fas fa-check text-xl"></i>
                </div>
                <div>
                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Đã duyệt</p>
                    <p class="text-2xl lg:text-3xl font-black text-slate-800" id="statApproved"><?= $stats['approved'] ?></p>
                    <div class="mt-1 text-[10px] font-medium text-emerald-600" id="approvalRate">
                        <?= $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100, 1) : 0 ?>% tỷ lệ duyệt
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 lg:block">
                <div class="p-3 bg-amber-50 text-amber-600 rounded-xl lg:mb-4">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div>
                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Chờ duyệt</p>
                    <p class="text-2xl lg:text-3xl font-black text-slate-800" id="statPending"><?= $stats['pending'] ?></p>
                    <div class="mt-1 text-[10px] font-medium text-amber-600">Cần xử lý</div>
                </div>
            </div>

            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 lg:block">
                <div class="p-3 bg-rose-50 text-rose-600 rounded-xl lg:mb-4">
                    <i class="fas fa-times text-xl"></i>
                </div>
                <div>
                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Từ chối</p>
                    <p class="text-2xl lg:text-3xl font-black text-slate-800" id="statRejected"><?= $stats['rejected'] ?></p>
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
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-50">
                                <th class="pb-3 px-4 font-bold uppercase tracking-wider text-[11px]">Thí sinh</th>
                                <th class="pb-3 px-4 font-bold uppercase tracking-wider text-[11px]">CCCD</th>
                                <th class="pb-3 px-4 font-bold uppercase tracking-wider text-[11px]">Thời gian</th>
                                <th class="pb-3 px-4 font-bold uppercase tracking-wider text-[11px] text-right">Trạng thái</th>
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
                    <table class="w-full text-left text-xs lg:text-sm border-collapse min-w-[600px] lg:min-w-full">
                        <thead>
                            <tr class="text-slate-500 border-b-2 border-slate-100 bg-slate-50/50">
                                <th class="py-3 px-4 font-bold uppercase tracking-wider whitespace-nowrap" rowspan="2">Mã ngành</th>
                                <th class="py-3 px-4 font-bold uppercase tracking-wider whitespace-nowrap" rowspan="2">Tên ngành</th>
                                <th class="py-3 px-4 font-bold uppercase tracking-wider text-center whitespace-nowrap" rowspan="2">Chỉ tiêu</th>
                                <th class="py-2 px-4 font-bold uppercase tracking-wider text-center border-b border-slate-200" colspan="4">Thống kê NV</th>
                            </tr>
                            <tr class="text-slate-500 border-b-2 border-slate-100 bg-slate-50/50">
                                <th class="py-2 px-4 font-bold text-center border-l border-slate-200">Tổng</th>
                                <th class="py-2 px-4 font-bold text-center border-l border-slate-200">NV1</th>
                                <th class="py-2 px-4 font-bold text-center border-l border-slate-200">NV2</th>
                                <th class="py-2 px-4 font-bold text-center border-l border-slate-200">C.Lại</th>
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
                    <table class="w-full text-left text-xs lg:text-sm border-collapse min-w-[500px] lg:min-w-full">
                        <thead>
                            <tr class="text-slate-500 border-b-2 border-slate-100 bg-slate-50/50">
                                <th class="py-3 px-4 font-bold uppercase tracking-wider">Họ tên cán bộ</th>
                                <th class="py-3 px-4 font-bold uppercase tracking-wider">Tên đăng nhập</th>
                                <th class="py-3 px-4 font-bold uppercase tracking-wider text-right">Đã duyệt</th>
                            </tr>
                        </thead>
                        <tbody id="reviewerTableBody">
                            <tr>
                                <td colspan="3" class="py-10 text-center text-slate-400 font-medium text-xs">Đang tải dữ liệu...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div> <!-- End x-data -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                console.log(`Checking chart ${id}: ${canvas.offsetWidth}x${canvas.offsetHeight}`);
                if (canvas.offsetWidth === 0 || canvas.offsetHeight === 0) {
                    console.warn(`!!!! Warning: Chart ${id} has zero dimensions.`);
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

        console.log(`Updating ${canvasId} (Line) with data:`, data);
        if (!data || !Array.isArray(data) || data.length === 0) {
            console.warn(`No data for ${canvasId}`);
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

        console.log(`Updating ${canvasId} (Pie/Status) with data:`, overview);
        if (!overview || (parseInt(overview.approved) === 0 && parseInt(overview.pending) === 0 && parseInt(overview.rejected) === 0)) {
            console.warn(`No data for ${canvasId}`);
            return;
        }

        window.charts[canvasId] = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Đã duyệt', 'Chờ duyệt', 'Từ chối'],
                datasets: [{
                    data: [overview.approved, overview.pending, overview.rejected],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
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

        console.log(`Updating ${canvasId} (Bar) with data:`, data);
        if (!data || !Array.isArray(data) || data.length === 0) {
            console.warn(`No data for ${canvasId}`);
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
                                size: isMobile ? 9 : 11
                            },
                            maxRotation: isVertical ? 45 : 0
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

        console.log(`Updating ${canvasId} with data:`, data);
        if (!data || !Array.isArray(data) || data.length === 0) {
            console.warn(`No data for ${canvasId}`);
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
            console.error("Chart.js is not loaded yet!");
            return;
        }
        const data = window.lastStatsData;
        console.log(`Rendering charts for tab: ${tabName}`);

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
            console.log(`Charts for tab ${tabName} rendered successfully.`);
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

    window.fetchStats = function(type = 'overview') {
        if (isFetching) return;

        const btnFilter = document.getElementById('btnFilter');
        const buttonSpinner = document.getElementById('btnFilterStatsSpinner');
        const chartSpinner = document.getElementById('chartLoadingSpinner');

        // UI Loading State Start
        isFetching = true;
        if (typeof window.Loading !== 'undefined') window.Loading.show();
        if (buttonSpinner) buttonSpinner.style.display = 'inline-block';
        if (chartSpinner) chartSpinner.style.display = 'flex';
        if (btnFilter) btnFilter.disabled = true;

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 15000);

        const params = new URLSearchParams({
            type: type,
            year: document.getElementById('filterYear')?.value || '',
            session_id: document.getElementById('filterSession')?.value || '',
            start: document.getElementById('filterStart')?.value || '',
            end: document.getElementById('filterEnd')?.value || ''
        });

        fetch(`<?= url('/admin/stats/api?') ?>${params.toString()}`, {
                signal: controller.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                console.log(`Stats Data (${type}) Received:`, data);
                // Merge into global data storage
                window.lastStatsData = { ...window.lastStatsData, ...data };
                updateUI(data, type);

                // Re-render charts for THIS tab if active
                const root = document.getElementById('dashboardRoot');
                if (root && typeof Alpine !== 'undefined') {
                    const alpine = Alpine.$data(root);
                    if (alpine && alpine.activeTab === type) {
                        setTimeout(() => window.renderChartsByTab(type), 50);
                    }
                }
            })
            .catch(error => {
                console.error(`Dashboard Fetch Error (${type}):`, error);
                if (error.name !== 'AbortError') {
                    if (typeof showToast === 'function') {
                        showToast(`Không thể tải dữ liệu ${type}. Vui lòng thử lại.`, "error");
                    }
                }
            })
            .finally(() => {
                clearTimeout(timeoutId);
                isFetching = false;

                console.log("Stats fetch completed, hiding spinners...");

                // UI Loading State End
                if (typeof window.Loading !== 'undefined') window.Loading.hide();
                if (buttonSpinner) buttonSpinner.style.setProperty('display', 'none', 'important');
                if (chartSpinner) chartSpinner.style.setProperty('display', 'none', 'important');
                if (btnFilter) btnFilter.disabled = false;

                // Force a resize to ensure charts are correct on mobile
                setTimeout(() => window.dispatchEvent(new Event('resize')), 100);
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
                'statRejected': ov.rejected
            };
            for (const [id, val] of Object.entries(map)) {
                const el = document.getElementById(id);
                if (el) el.textContent = val ?? 0;
            }

            const rateEl = document.getElementById('approvalRate');
            if (rateEl) {
                const total = parseInt(ov.total) || 0;
                const approved = parseInt(ov.approved) || 0;
                rateEl.textContent = (total > 0 ? (approved / total * 100).toFixed(1) : 0) + '% tỷ lệ duyệt';
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
                            tr.className = 'border-b border-slate-50 hover:bg-slate-50/50 transition';
                            tr.innerHTML = `<td class="py-3 px-4 font-bold text-slate-700">${c.ho_ten}</td><td class="py-3 px-4 text-slate-500 font-mono text-xs">${c.so_cccd}</td><td class="py-3 px-4 text-slate-500 text-xs">${fDate}</td><td class="py-3 px-4 text-right">${stHtml}</td>`;
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
                        r.className = 'border-b border-slate-50 hover:bg-slate-50/50 transition';
                        r.innerHTML = `<td class="py-3 px-4 text-slate-800">${m.ma_nganh}</td><td class="py-3 px-4 text-slate-800">${m.ten_nganh}</td><td class="py-3 px-4 text-center text-slate-800">${t>0?t:'-'}</td><td class="py-3 px-4 text-center text-slate-800 border-l border-slate-100">${tn}</td><td class="py-3 px-4 text-center text-red-600 border-l border-slate-100">${nv1}</td><td class="py-3 px-4 text-center text-slate-800 border-l border-slate-100">${nv2}</td><td class="py-3 px-4 text-center text-slate-800 border-l border-slate-100">${c}</td>`;
                        db.appendChild(r);
                    });
                    df.innerHTML = `<tr><td class="py-3 px-4 text-right" colspan="2">TỔNG CỘNG</td><td class="py-3 px-4 text-center text-slate-800">${tt>0?tt:'-'}</td><td class="py-3 px-4 text-center text-slate-800 border-l border-slate-200">${ta}</td><td class="py-3 px-4 text-center text-red-600 border-l border-slate-200">${n1}</td><td class="py-3 px-4 text-center text-slate-800 border-l border-slate-200">${n2}</td><td class="py-3 px-4 text-center text-slate-800 border-l border-slate-200">${cl}</td></tr>`;
                } else {
                    db.innerHTML = '<tr><td colspan="7" class="py-6 text-center text-slate-400 font-medium">Chưa có dữ liệu.</td></tr>';
                    df.innerHTML = '';
                }
            }
        }

        if (type === 'demographics') {
            // Reviewer Stats Table Update
            const rb = document.getElementById('reviewerTableBody');
            if (rb && data.reviewers) {
                rb.innerHTML = '';
                if (data.reviewers.length > 0) {
                    data.reviewers.forEach(v => {
                        const r = document.createElement('tr');
                        r.className = 'border-b border-slate-50 hover:bg-slate-50/50 transition';
                        r.innerHTML = `
                        <td class="py-3 px-4 text-slate-800 font-medium">${v.ho_ten}</td>
                        <td class="py-3 px-4 text-slate-500 font-mono text-xs">${v.ten_dang_nhap}</td>
                        <td class="py-3 px-4 text-right font-bold text-indigo-600">${v.review_count}</td>
                    `;
                        rb.appendChild(r);
                    });
                    console.log(`Reviewer table updated with ${data.reviewers.length} rows.`);
                } else {
                    rb.innerHTML = '<tr><td colspan="3" class="py-6 text-center text-slate-400">Chưa có dữ liệu người duyệt.</td></tr>';
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
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin.php';
?>