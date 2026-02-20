<?php $title = 'Báo cáo & Xuất dữ liệu - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-7xl mx-auto">
    <header class="mb-8">
        <h2 class="text-3xl font-black text-gray-900 uppercase">Báo cáo & Thống kê</h2>
        <p class="text-gray-500 mt-1">Xem thống kê và xuất dữ liệu</p>
    </header>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500">
            <p class="text-sm font-bold text-gray-400 uppercase">Tổng thí sinh</p>
            <p class="text-4xl font-black text-gray-800 mt-2"><?= number_format($stats['total_candidates'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
            <p class="text-sm font-bold text-gray-400 uppercase">Đã duyệt</p>
            <p class="text-4xl font-black text-green-600 mt-2"><?= number_format($stats['total_approved'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-yellow-500">
            <p class="text-sm font-bold text-gray-400 uppercase">Chờ duyệt</p>
            <p class="text-4xl font-black text-yellow-600 mt-2"><?= number_format($stats['by_status']['Chờ duyệt'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-red-500">
            <p class="text-sm font-bold text-gray-400 uppercase">Từ chối</p>
            <p class="text-4xl font-black text-red-600 mt-2"><?= number_format($stats['by_status']['Từ chối'] ?? 0) ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Export Panel -->
        <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
            <h3 class="text-xl font-bold text-gray-800 mb-6 border-b pb-2">
                <i class="fas fa-file-export text-[#0066FF] mr-2"></i> Xuất dữ liệu
            </h3>
            
            <div class="space-y-6">
                <div class="p-4 bg-gray-50 rounded-xl">
                    <h4 class="font-bold text-gray-700 mb-3">Danh sách Thí sinh</h4>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <select id="export-status" class="px-3 py-2 border rounded-lg text-sm">
                            <option value="">Tất cả trạng thái</option>
                            <option value="Chờ duyệt">Chờ duyệt</option>
                            <option value="Đã duyệt">Đã duyệt</option>
                            <option value="Từ chối">Từ chối</option>
                        </select>
                        <select id="export-session" class="px-3 py-2 border rounded-lg text-sm">
                            <option value="">Tất cả đợt</option>
                            <?php foreach ($sessions as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['ten_dot'] ?? 'Đợt ' . $s['id']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button onclick="exportCandidates()" class="w-full py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-download mr-2"></i> Xuất CSV
                    </button>
                </div>

                <div class="p-4 bg-green-50 rounded-xl">
                    <h4 class="font-bold text-gray-700 mb-3">Danh sách Trúng tuyển</h4>
                    <select id="export-major" class="w-full px-3 py-2 border rounded-lg text-sm mb-4">
                        <option value="">-- Chọn ngành --</option>
                        <?php foreach ($majors as $m): ?>
                            <option value="<?= $m['ma_nganh'] ?>"><?= htmlspecialchars($m['ten_nganh']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="exportAdmitted()" class="w-full py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-graduation-cap mr-2"></i> Xuất Trúng tuyển
                    </button>
                </div>
            </div>
        </div>

        <!-- Chart Panel -->
        <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
            <h3 class="text-xl font-bold text-gray-800 mb-6 border-b pb-2">
                <i class="fas fa-chart-bar text-[#0066FF] mr-2"></i> Thống kê theo ngày
            </h3>
            <canvas id="dailyChart" height="200"></canvas>
        </div>
    </div>

    <!-- Major Stats -->
    <div class="mt-8 bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
        <h3 class="text-xl font-bold text-gray-800 mb-6 border-b pb-2">
            <i class="fas fa-list-ol text-[#0066FF] mr-2"></i> Top Ngành được đăng ký
        </h3>
        <div class="space-y-3">
            <?php foreach ($stats['by_major'] ?? [] as $index => $m): ?>
                <div class="flex items-center">
                    <span class="w-8 h-8 rounded-full bg-[#0066FF] text-white flex items-center justify-center font-bold text-sm mr-4"><?= $index + 1 ?></span>
                    <span class="flex-grow font-medium text-gray-700"><?= htmlspecialchars($m['ten_nganh']) ?></span>
                    <span class="font-bold text-[#0066FF]"><?= $m['count'] ?> hồ sơ</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function exportCandidates() {
    const status = document.getElementById('export-status').value;
    const session = document.getElementById('export-session').value;
    window.location.href = '<?= url('/admin/reports/export-candidates') ?>?status=' + encodeURIComponent(status) + '&session_id=' + session;
}

function exportAdmitted() {
    const major = document.getElementById('export-major').value;
    if (!major) { alert('Vui lòng chọn ngành'); return; }
    window.location.href = '<?= url('/admin/reports/export-admitted') ?>?ma_nganh=' + major;
}

// Daily Chart
const dailyData = <?= json_encode($stats['by_date'] ?? []) ?>;
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: dailyData.map(d => d.date),
        datasets: [{
            label: 'Hồ sơ mới',
            data: dailyData.map(d => d.count),
            backgroundColor: 'rgba(206, 27, 34, 0.7)',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
