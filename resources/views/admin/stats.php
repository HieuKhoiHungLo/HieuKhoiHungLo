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

<div class="grid grid-cols-1 gap-8 mb-8">
    <!-- Chart: Top High Schools (Full Width) -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-4">Top Trường THPT</h3>
        <div class="relative h-96">
            <canvas id="schoolChart"></canvas>
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

        // Update Charts
        updateChart('dailyRegistrationChart', 'daily', data.daily, 'date', 'count', 'Số lượng hồ sơ');
        updatePieChart('statusChart', data.overview);
        updateBarChart('majorChart', data.major, 'ten_nganh', 'count', 'Hồ sơ theo ngành');
        updateBarChart('provinceChart', data.province, 'label', 'count', 'Hồ sơ theo tỉnh');
        updateBarChart('schoolChart', data.school, 'label', 'count', 'Hồ sơ theo trường');
        updatePieChartGeneric('genderChart', data.gender, 'Giới tính');
        updatePieChartGeneric('areaChart', data.area, 'Khu vực');
        updatePieChartGeneric('objectChart', data.object, 'Đối tượng');
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
