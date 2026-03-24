<?php $title = 'Xuất dữ liệu hồ sơ - Admin'; ?>
<?php ob_start(); ?>

<?php
// Build session map for JS (all sessions as JSON)
$allSessionsJson = json_encode(array_values(array_map(fn($s) => [
    'id'               => $s['id'],
    'ten_dot'          => $s['ten_dot'] ?? '',
    'nam_tuyen_sinh'   => $s['nam_tuyen_sinh'] ?? '',
    'kich_hoat'        => !empty($s['kich_hoat']),
], $allSessions ?? [])));
?>

<style>
.export-table th,
.export-table td {
    padding: 0.75rem 1.25rem;
    text-align: left;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.export-table thead th {
    background: #f8fafc;
    font-size: 0.7rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #64748b;
}
.export-table tbody tr:hover td { background: #f0f7ff; }
.export-table .tt-cell { width: 48px; text-align: center; font-weight: 900; color: #94a3b8; }
.download-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 8px;
    background: #0066FF; color: #fff;
    font-size: 0.78rem; font-weight: 700;
    transition: background 0.15s, transform 0.1s;
    white-space: nowrap;
}
.download-btn:hover { background: #0050CC; transform: translateY(-1px); }
.download-btn:active { transform: translateY(0); }
.badge-dev {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 8px;
    background: #f1f5f9; color: #94a3b8;
    font-size: 0.75rem; font-weight: 700;
    border: 1px dashed #cbd5e1;
    white-space: nowrap;
}
.session-filter-bar {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    box-shadow: 0 1px 4px rgba(0,102,255,0.04);
}
</style>

<div class="max-w-5xl mx-auto">
    <header class="mb-6">
        <h2 class="text-3xl font-black text-gray-900 uppercase">Xuất dữ liệu hồ sơ</h2>
        <p class="text-gray-500 mt-1 text-sm">Chọn năm tuyển sinh và đợt, sau đó tải xuống dữ liệu theo từng chức năng.</p>
    </header>

    <!-- ===== SESSION FILTER BAR ===== -->
    <div class="session-filter-bar">
        <span class="text-xs font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">
            <i class="fas fa-filter text-[#0066FF] mr-1"></i> Phạm vi dữ liệu:
        </span>

        <div class="flex items-center gap-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Khóa</label>
            <select id="sel-year" onchange="onYearChange(this.value)"
                class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-400 outline-none shadow-sm cursor-pointer min-w-[110px]">
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>>Khóa <?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Đợt tuyển sinh</label>
            <select id="sel-session"
                class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-400 outline-none shadow-sm cursor-pointer max-w-[280px]">
                <?php foreach ($yearSessions as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $selectedSessionId == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(!empty($s['ten_dot']) ? $s['ten_dot'] : 'Đợt ' . $s['id']) ?>
                        <?php if (!empty($s['kich_hoat'])): ?> ✓<?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (empty($years)): ?>
            <span class="text-xs text-orange-500 font-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Chưa có đợt tuyển sinh nào.</span>
        <?php endif; ?>
    </div>

    <!-- ===== EXPORT TABLE ===== -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
            <i class="fas fa-file-export text-[#0066FF] text-lg"></i>
            <h3 class="text-base font-black text-slate-800 uppercase tracking-wide">Danh sách chức năng xuất dữ liệu</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="export-table w-full">
                <thead>
                    <tr>
                        <th class="tt-cell">TT</th>
                        <th>Nội dung</th>
                        <th class="text-center" style="width:160px">Xuất dữ liệu</th>
                    </tr>
                </thead>
                <tbody>
 
                    <!-- Row 0: General Candidates -->
                    <tr>
                        <td class="tt-cell">0</td>
                        <td>
                            <div class="font-semibold text-blue-700 text-sm">Danh sách hồ sơ đăng ký (Tổng hợp)</div>
                            <div class="text-xs text-slate-400 mt-0.5">Xuất toàn bộ thông tin thí sinh đăng ký trong đợt tuyển sinh này</div>
                        </td>
                        <td class="text-center">
                            <button onclick="doExport('<?= url('/admin/reports/export-candidates') ?>')" class="download-btn !background-[#00C853] hover:!bg-[#00A844]">
                                <i class="fas fa-download text-xs"></i> Download
                            </button>
                        </td>
                    </tr>

                    <!-- Row 1: MOET Info (Điểm THPT) -->
                    <tr>
                        <td class="tt-cell">1</td>
                        <td>
                            <div class="font-semibold text-slate-800 text-sm">Dữ liệu điểm thi THPT</div>
                            <div class="text-xs text-slate-400 mt-0.5">Cấu trúc của Bộ GD&ĐT — Thông tin thí sinh + điểm thi THPT quốc gia</div>
                        </td>
                        <td class="text-center">
                            <button onclick="doExport('<?= url('/admin/reports/export-moet-info') ?>')" class="download-btn">
                                <i class="fas fa-download text-xs"></i> Download
                            </button>
                        </td>
                    </tr>

                    <!-- Row 2: MOET Wishes (Nguyện vọng) -->
                    <tr>
                        <td class="tt-cell">2</td>
                        <td>
                            <div class="font-semibold text-slate-800 text-sm">Dữ liệu nguyện vọng</div>
                            <div class="text-xs text-slate-400 mt-0.5">Cấu trúc của Bộ GD&ĐT — Danh sách nguyện vọng đăng ký</div>
                        </td>
                        <td class="text-center">
                            <button onclick="doExport('<?= url('/admin/reports/export-moet-wishes') ?>')" class="download-btn">
                                <i class="fas fa-download text-xs"></i> Download
                            </button>
                        </td>
                    </tr>

                    <!-- Row 3: MOET Transcripts (Học bạ) -->
                    <tr>
                        <td class="tt-cell">3</td>
                        <td>
                            <div class="font-semibold text-slate-800 text-sm">Dữ liệu điểm học bạ</div>
                            <div class="text-xs text-slate-400 mt-0.5">Cấu trúc của Bộ GD&ĐT — Điểm từng môn theo từng học kỳ lớp 12</div>
                        </td>
                        <td class="text-center">
                            <button onclick="doExport('<?= url('/admin/reports/export-moet-transcripts') ?>')" class="download-btn">
                                <i class="fas fa-download text-xs"></i> Download
                            </button>
                        </td>
                    </tr>

                    <!-- Row 4: Language Certificates -->
                    <tr>
                        <td class="tt-cell">4</td>
                        <td>
                            <div class="font-semibold text-slate-800 text-sm">Danh sách thí sinh có chứng chỉ ngoại ngữ</div>
                            <div class="text-xs text-slate-400 mt-0.5">IELTS, TOEIC, TOEFL, HSK, JLPT... — chứng chỉ đăng ký xét tuyển</div>
                        </td>
                        <td class="text-center">
                            <button onclick="doExport('<?= url('/admin/reports/export-certificates') ?>')" class="download-btn">
                                <i class="fas fa-download text-xs"></i> Download
                            </button>
                        </td>
                    </tr>

                    <!-- Row 5: Aptitude Test -->
                    <tr>
                        <td class="tt-cell">5</td>
                        <td>
                            <div class="font-semibold text-slate-800 text-sm">Danh sách thí sinh đăng ký thi năng khiếu</div>
                            <div class="text-xs text-slate-400 mt-0.5">Các thí sinh có nguyện vọng thuộc ngành yêu cầu thi năng khiếu</div>
                        </td>
                        <td class="text-center">
                            <button onclick="doExport('<?= url('/admin/reports/export-aptitude-list') ?>')" class="download-btn">
                                <i class="fas fa-download text-xs"></i> Download
                            </button>
                        </td>
                    </tr>

                    <!-- Row 6: Photo images (planned) -->
                    <tr class="opacity-60">
                        <td class="tt-cell">6</td>
                        <td>
                            <div class="font-semibold text-slate-600 text-sm">Download file ảnh thẻ</div>
                            <div class="text-xs text-slate-400 mt-0.5">Tải toàn bộ ảnh thẻ của thí sinh theo đợt (định dạng ZIP)</div>
                        </td>
                        <td class="text-center">
                            <span class="badge-dev">
                                <i class="fas fa-wrench text-[10px]"></i> Đang phát triển
                            </span>
                        </td>
                    </tr>

                    <!-- Row 7: Certificate images (planned) -->
                    <tr class="opacity-60">
                        <td class="tt-cell">7</td>
                        <td>
                            <div class="font-semibold text-slate-600 text-sm">Download file ảnh chứng chỉ ngoại ngữ</div>
                            <div class="text-xs text-slate-400 mt-0.5">Tải minh chứng ảnh chứng chỉ ngoại ngữ của thí sinh (định dạng ZIP)</div>
                        </td>
                        <td class="text-center">
                            <span class="badge-dev">
                                <i class="fas fa-wrench text-[10px]"></i> Đang phát triển
                            </span>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 text-xs text-slate-400 flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-400"></i>
            Dữ liệu xuất ra thuộc <strong class="text-slate-600">đợt đang chọn</strong> ở bộ lọc phía trên. Cột số CCCD/ĐDCN được định dạng text để không mất số 0 đầu khi mở bằng Excel.
        </div>
    </div>

    <!-- ===== Stats Grid (compact) ===== -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border border-slate-100 border-l-4 border-l-blue-500">
            <p class="text-xs font-bold text-gray-400 uppercase">Tổng hồ sơ</p>
            <p class="text-3xl font-black text-gray-800 mt-1"><?= number_format($stats['total_candidates'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-slate-100 border-l-4 border-l-green-500">
            <p class="text-xs font-bold text-gray-400 uppercase">Đã duyệt</p>
            <p class="text-3xl font-black text-green-600 mt-1"><?= number_format($stats['total_approved'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-slate-100 border-l-4 border-l-yellow-500">
            <p class="text-xs font-bold text-gray-400 uppercase">Chờ duyệt</p>
            <p class="text-3xl font-black text-yellow-600 mt-1"><?= number_format($stats['by_status']['Chờ duyệt'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-slate-100 border-l-4 border-l-orange-500">
            <p class="text-xs font-bold text-gray-400 uppercase">Yêu cầu sửa</p>
            <p class="text-3xl font-black text-orange-600 mt-1"><?= number_format($stats['by_status']['Yêu cầu sửa'] ?? 0) ?></p>
        </div>
    </div>

    <!-- ===== Chart ===== -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 mt-6">
        <h3 class="text-base font-bold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-chart-bar text-[#0066FF] mr-2"></i> Thống kê hồ sơ mới theo ngày (14 ngày gần nhất)
        </h3>
        <canvas id="dailyChart" height="160"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// All sessions data from PHP
const allSessions = <?= $allSessionsJson ?>;

// Trigger export with current session_id and status
function doExport(baseUrl) {
    const selSession = document.getElementById('sel-session');
    const sessionId = selSession ? selSession.value : '';
    const chkApproved = document.getElementById('chk-approved');
    const isApprovedOnly = chkApproved ? chkApproved.checked : false;
    
    if (!sessionId) {
        alert('Vui lòng chọn đợt tuyển sinh trước khi tải xuống.');
        return;
    }
    
    let url = baseUrl + '?session_id=' + encodeURIComponent(sessionId);
    if (isApprovedOnly) {
        url += '&status=' + encodeURIComponent('Đã duyệt');
    }
    
    window.location.href = url;
}

function updateLinks() {
    // Optional: could visual feedback when checkbox is toggled
}

// When year changes: repopulate session dropdown
function onYearChange(year) {
    const sel = document.getElementById('sel-session');
    sel.innerHTML = '';
    const filtered = allSessions.filter(s => String(s.nam_tuyen_sinh) === String(year));
    if (filtered.length === 0) {
        sel.innerHTML = '<option value="">-- Không có đợt nào --</option>';
        return;
    }
    filtered.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = (s.ten_dot || 'Đợt ' + s.id) + (s.kich_hoat ? ' ✓' : '');
        sel.appendChild(opt);
    });
    // Auto-select the active session in the filtered list
    const activeSess = filtered.find(s => s.kich_hoat);
    if (activeSess) sel.value = activeSess.id;
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
            backgroundColor: 'rgba(0, 102, 255, 0.65)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>
