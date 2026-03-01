<?php ob_start(); ?>

<div class="mb-8">
    <h2 class="text-2xl font-black text-slate-800 font-heading uppercase tracking-tight">Thống kê & Báo cáo</h2>
    <p class="text-sm text-slate-500 font-medium">Tổng quan dữ liệu tuyển sinh</p>
</div>

<!-- Filters -->
<!-- Filters -->
<div class="mb-6 flex flex-wrap gap-3 items-center bg-white p-4 rounded-xl shadow-sm border border-slate-100">
    <select id="filterYear" class="px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm cursor-pointer">
        <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= ($selectedYear ?? '') == $y ? 'selected' : '' ?>>
                Năm <?= $y ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select id="filterSession" class="px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm cursor-pointer">
        <option value="">-- Tất cả đợt --</option>
        <?php foreach ($sessions as $s): ?>
            <option value="<?= $s['id'] ?>" data-year="<?= $s['nam_tuyen_sinh'] ?>" <?= ($currentSessionId ?? '') == $s['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars(!empty($s['ma_dot']) ? $s['ma_dot'] : $s['ten_dot']) ?> - <?= $s['nam_tuyen_sinh'] ?>
            </option>
        <?php endforeach; ?>
    </select>
    
    <input type="date" id="filterStart" value="<?= $startDate ?>" class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
    <input type="date" id="filterEnd" value="<?= $endDate ?>" class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
    
    <button id="btnFilter" class="px-4 py-2.5 bg-[#0066FF] text-white font-semibold rounded-xl hover:bg-indigo-700 transition shadow-sm">
        <i class="fas fa-filter mr-2"></i>Lọc
        <i class="fas fa-spinner fa-spin hidden ml-2" id="loadingSpinner"></i>
    </button>
</div>

<!-- Overview Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-6 rounded-2xl shadow-lg shadow-indigo-200 text-white relative overflow-hidden group">
        <div class="relative z-10">
            <p class="text-indigo-100 text-xs font-bold uppercase tracking-wider mb-2">Tổng hồ sơ</p>
            <p class="text-4xl font-black"><?= $stats['total'] ?></p>
        </div>
        <i class="fas fa-users absolute -bottom-4 -right-4 text-9xl text-blue-400 opacity-20 group-hover:opacity-30 transition transform group-hover:scale-110"></i>
    </div>
    
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-4">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">Đã duyệt</p>
            <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg"><i class="fas fa-check"></i></span>
        </div>
        <p class="text-3xl font-black text-slate-800"><?= $stats['approved'] ?></p>
         <div class="mt-2 text-xs font-medium text-emerald-600">
            <?= $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100, 1) : 0 ?>% tỷ lệ duyệt
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-4">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">Chờ duyệt</p>
            <span class="p-2 bg-amber-50 text-amber-600 rounded-lg"><i class="fas fa-clock"></i></span>
        </div>
        <p class="text-3xl font-black text-slate-800"><?= $stats['pending'] ?></p>
         <div class="mt-2 text-xs font-medium text-amber-600">
             Cần xử lý gấp
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-4">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">Từ chối</p>
            <span class="p-2 bg-rose-50 text-rose-600 rounded-lg"><i class="fas fa-times"></i></span>
        </div>
        <p class="text-3xl font-black text-slate-800"><?= $stats['rejected'] ?></p>
    </div>
</div>

<!-- Recent Registrations & Latest Candidates -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Left: Today & Week Cards -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-gradient-to-r from-sky-500 to-sky-600 p-6 rounded-2xl shadow-lg shadow-sky-200 text-white relative overflow-hidden group">
            <div class="relative z-10">
                <p class="text-sky-100 text-xs font-bold uppercase tracking-wider mb-2">Đăng ký hôm nay</p>
                <div class="flex items-baseline">
                    <p class="text-4xl font-black" id="recentToday">0</p>
                    <span class="ml-2 text-sm text-sky-200 font-medium">hồ sơ</span>
                </div>
            </div>
            <i class="fas fa-calendar-day absolute -bottom-4 -right-4 text-8xl text-sky-400 opacity-20 group-hover:opacity-30 transition transform group-hover:scale-110"></i>
        </div>
        <div class="bg-gradient-to-r from-teal-500 to-teal-600 p-6 rounded-2xl shadow-lg shadow-teal-200 text-white relative overflow-hidden group">
            <div class="relative z-10">
                <p class="text-teal-100 text-xs font-bold uppercase tracking-wider mb-2">Đăng ký tuần này</p>
                <div class="flex items-baseline">
                    <p class="text-4xl font-black" id="recentWeek">0</p>
                    <span class="ml-2 text-sm text-teal-200 font-medium">hồ sơ</span>
                </div>
            </div>
            <i class="fas fa-calendar-week absolute -bottom-4 -right-4 text-8xl text-teal-400 opacity-20 group-hover:opacity-30 transition transform group-hover:scale-110"></i>
        </div>
    </div>
    <!-- Right: Latest 5 Candidates Table -->
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-slate-800 tracking-tight uppercase text-sm">5 Hồ sơ mới đăng ký gần nhất</h3>
            <a href="<?= url('/admin/applications') ?>" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">Xem tất cả &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-100">
                        <th class="py-3 px-4 font-bold uppercase tracking-wider">Thí sinh</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider">CCCD</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider">Thời gian</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider text-right">Trạng thái</th>
                    </tr>
                </thead>
                <tbody id="latestCandidatesBody">
                    <!-- Dynamic Content -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-8 mb-8">
    <!-- Chart: Admissions by Major (Full Width) -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-4">Hồ sơ theo ngành</h3>
        <div class="relative h-96">
            <canvas id="majorChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Chart: Daily Registrations (50%) -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-4">Hồ sơ theo ngày</h3>
        <div class="relative h-96">
             <canvas id="dailyRegistrationChart"></canvas>
             <div id="loadingSpinner" class="absolute inset-0 flex items-center justify-center bg-white/70 hidden z-10 rounded-xl backdrop-blur-sm transition-all duration-300">
                <div class="flex flex-col items-center">
                    <i class="fas fa-circle-notch fa-spin text-[#0066FF] text-4xl mb-3"></i>
                    <span class="text-slate-600 font-medium text-sm">Đang tải dữ liệu...</span>
                </div>
             </div>
        </div>
    </div>

    <!-- Chart: Top Province (50%) -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-4">Top Tỉnh/Thành phố</h3>
        <div class="relative h-96">
            <canvas id="provinceChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
    <!-- Status (25%) -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-4">Trạng thái hồ sơ</h3>
        <div class="relative h-64">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
    <!-- Gender (25%) -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-4">Giới tính</h3>
        <div class="relative h-64">
            <canvas id="genderChart"></canvas>
        </div>
    </div>
    <!-- Area (25%) -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-4">Khu vực</h3>
        <div class="relative h-64">
            <canvas id="areaChart"></canvas>
        </div>
    </div>
    <!-- Object (25%) -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-4">Đối tượng ưu tiên</h3>
        <div class="relative h-64">
            <canvas id="objectChart"></canvas>
        </div>
    </div>
</div>

    <!-- Chart: Top High Schools (Full Width) -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-4">Top Trường THPT</h3>
        <div class="relative h-96">
            <canvas id="schoolChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-8 mb-8">
    <!-- Reviewer Stats Table -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-4 tracking-tight uppercase text-sm">Thống kê duyệt hồ sơ theo cán bộ</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-100">
                        <th class="py-3 px-4 font-bold uppercase tracking-wider">Cán bộ</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider">Tên đăng nhập</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider text-right">Số hồ sơ đã duyệt</th>
                    </tr>
                </thead>
                <tbody id="reviewerTableBody">
                    <!-- Dynamic Content -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let charts = {};

    // Initial Load
    fetchStats();

    // Event Listeners
    document.getElementById('btnFilter').addEventListener('click', fetchStats);
    
    document.getElementById('filterYear').addEventListener('change', function() {
        const year = this.value;
        const sessionSelect = document.getElementById('filterSession');
        
        // Filter sessions by year
        Array.from(sessionSelect.options).forEach(opt => {
            if (opt.value === "") return;
            const optYear = opt.getAttribute('data-year');
            opt.style.display = (optYear == year) ? 'block' : 'none';
        });
        
        // Reset session if hidden
        const selectedOpt = sessionSelect.selectedOptions[0];
        if (selectedOpt && selectedOpt.value !== "" && selectedOpt.style.display === 'none') {
            sessionSelect.value = "";
        }
    });

    function fetchStats() {
        const year = document.getElementById('filterYear').value;
        const session = document.getElementById('filterSession').value;
        const start = document.getElementById('filterStart').value;
        const end = document.getElementById('filterEnd').value;
        const spinner = document.getElementById('loadingSpinner');

        // Show spinner
        if(spinner) spinner.classList.remove('hidden');

        const params = new URLSearchParams({
            year: year,
            session_id: session,
            start: start,
            end: end
        });

        fetch(`<?= url('/admin/stats/api') ?>?${params.toString()}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                updateUI(data);
            })
            .catch(error => {
                console.error('Error:', error);
                
            })
            .finally(() => {
                if(spinner) spinner.classList.add('hidden');
            });
    }

    function updateUI(data) {
        // Update Overview Numbers
        const overview = data.overview;
        const totalEl = document.querySelector('.text-4xl.font-black');
        if(totalEl) totalEl.textContent = overview.total;
        
        const StatCards = document.querySelectorAll('.bg-white.p-6.rounded-2xl');
        if(StatCards.length >= 3) {
            // Approved
            StatCards[0].querySelector('.text-3xl').textContent = overview.approved;
            // Pending
            StatCards[1].querySelector('.text-3xl').textContent = overview.pending;
            // Rejected
            StatCards[2].querySelector('.text-3xl').textContent = overview.rejected;
        }

        // Update Recent Stats
        const todayEl = document.getElementById('recentToday');
        if (todayEl && data.recent) todayEl.textContent = data.recent.today;
        
        const weekEl = document.getElementById('recentWeek');
        if (weekEl && data.recent) weekEl.textContent = data.recent.this_week;

        // Update Latest Candidates Table
        const latestBody = document.getElementById('latestCandidatesBody');
        if (latestBody && data.latest) {
            latestBody.innerHTML = '';
            if (data.latest.length > 0) {
                data.latest.forEach(cand => {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b border-slate-50 hover:bg-slate-50/50 transition';
                    
                    // Format date to DD/MM/YYYY HH:mm
                    const d = new Date(cand.created_at);
                    const formattedDate = (d.getDate() < 10 ? '0' : '') + d.getDate() + '/' + 
                                          ((d.getMonth() + 1) < 10 ? '0' : '') + (d.getMonth() + 1) + '/' + 
                                          d.getFullYear() + ' ' + 
                                          (d.getHours() < 10 ? '0' : '') + d.getHours() + ':' + 
                                          (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();
                    
                    let statusHtml = '';
                    switch(cand.trang_thai) {
                        case 'Đã duyệt': statusHtml = '<span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold">Đã duyệt</span>'; break;
                        case 'Từ chối': statusHtml = '<span class="px-2.5 py-1 bg-rose-100 text-rose-700 rounded-lg text-xs font-bold">Từ chối</span>'; break;
                        default: statusHtml = '<span class="px-2.5 py-1 bg-amber-100 text-amber-700 rounded-lg text-xs font-bold">Chờ duyệt</span>'; break;
                    }

                    tr.innerHTML = `
                        <td class="py-3 px-4 font-bold text-slate-700">${cand.ho_ten}</td>
                        <td class="py-3 px-4 text-slate-500 font-mono text-xs">${cand.so_cccd}</td>
                        <td class="py-3 px-4 text-slate-500 text-xs">${formattedDate}</td>
                        <td class="py-3 px-4 text-right">${statusHtml}</td>
                    `;
                    latestBody.appendChild(tr);
                });
            } else {
                latestBody.innerHTML = '<tr><td colspan="4" class="py-6 text-center text-slate-400 font-medium">Chưa có hồ sơ mới.</td></tr>';
            }
        }


        // Update Charts
        updateChart('dailyRegistrationChart', 'daily', data.daily, 'date', 'count', 'Số lượng hồ sơ');
        updatePieChart('statusChart', data.overview);
        updateBarChart('majorChart', data.major, 'ten_nganh', 'count', 'Hồ sơ theo ngành');
        updateBarChart('provinceChart', data.province, 'label', 'count', 'Hồ sơ theo tỉnh');
        updateBarChart('schoolChart', data.school, 'label', 'count', 'Hồ sơ theo trường');
        updatePieChartGeneric('genderChart', data.gender, 'Giới tính');
        updatePieChartGeneric('areaChart', data.area, 'Khu vực');
        updatePieChartGeneric('objectChart', data.object, 'Đối tượng');

        // Update Reviewer Table
        const tableBody = document.getElementById('reviewerTableBody');
        if (tableBody) {
            tableBody.innerHTML = '';
            if (data.reviewers && data.reviewers.length > 0) {
                data.reviewers.forEach(rev => {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b border-slate-50 hover:bg-slate-50/50 transition';
                    tr.innerHTML = `
                        <td class="py-3 px-4 font-bold text-slate-700">${rev.ho_ten}</td>
                        <td class="py-3 px-4 text-slate-500">${rev.ten_dang_nhap}</td>
                        <td class="py-3 px-4 font-black text-[#0066FF] text-right text-lg">${rev.review_count}</td>
                    `;
                    tableBody.appendChild(tr);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="3" class="py-8 text-center text-slate-400 font-medium">Chưa có dữ liệu duyệt hồ sơ.</td></tr>';
            }
        }
    }

    function updateChart(canvasId, type, data, labelKey, valueKey, label) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        if (charts[canvasId]) {
            charts[canvasId].destroy();
        }

        charts[canvasId] = new Chart(ctx, {
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
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    function updatePieChart(canvasId, overview) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (charts[canvasId]) charts[canvasId].destroy();

        charts[canvasId] = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Đã duyệt', 'Chờ duyệt', 'Từ chối'],
                datasets: [{
                    data: [overview.approved, overview.pending, overview.rejected],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    function updateBarChart(canvasId, data, labelKey, valueKey, label) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (charts[canvasId]) charts[canvasId].destroy();

        // Check if it's the major or school chart to apply specific vertical styling
        const isMajorChart = canvasId === 'majorChart' || canvasId === 'schoolChart';

        charts[canvasId] = new Chart(ctx, {
            type: 'bar', // Default is vertical
            data: {
                labels: data.map(item => item[labelKey]),
                datasets: [{
                    label: label,
                    data: data.map(item => item[valueKey]),
                    backgroundColor: '#6366f1',
                    borderRadius: 4
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                indexAxis: isMajorChart ? 'x' : 'y', // Major chart = vertical (x), others (like province) = horizontal (y)
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: false, // Show all labels
                            maxRotation: 90,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }
    
    function updatePieChartGeneric(canvasId, data, label) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (charts[canvasId]) charts[canvasId].destroy();

        charts[canvasId] = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.map(item => item.label),
                datasets: [{
                    label: label,
                    data: data.map(item => item.count),
                     backgroundColor: [
                        '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
                        '#ec4899', '#6366f1', '#14b8a6', '#f97316', '#64748b'
                    ]
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }
});
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin.php'; 
?>
