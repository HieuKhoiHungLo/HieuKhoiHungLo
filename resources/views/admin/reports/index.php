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
    padding: 0.4rem 0.75rem;
    text-align: left;
    vertical-align: middle;
    border-bottom: 1px solid #e2e8f0;
    border-right: 1px solid #e2e8f0;
    font-size: 13px;
    color: #334155;
}
.export-table th:first-child, .export-table td:first-child {
    border-left: 1px solid #e2e8f0;
}
.export-table thead th {
    background: #f8fafc;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    color: #475569;
    border-top: 1px solid #e2e8f0;
}
.export-table tbody tr:hover td { background: #f8fafc; }
.export-table .tt-cell { width: 45px; text-align: center; font-weight: 700; color: #64748b; background: #f8fafc; }
.download-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 6px;
    background: #0066FF; color: #fff;
    font-size: 0.72rem; font-weight: 700;
    transition: background 0.15s, transform 0.1s;
    white-space: nowrap;
}
.download-btn:hover { background: #0050CC; transform: translateY(-1px); }
.download-btn:active { transform: translateY(0); }
.badge-dev {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 6px;
    background: #f1f5f9; color: #94a3b8;
    font-size: 0.7rem; font-weight: 700;
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
    <!-- Header removed as per user request -->

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
            <select id="sel-session" onchange="onSessionChange(this.value)"
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

    <!-- ===== Stats Grid (compact) ===== -->
    <div id="stats-container" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-sm p-4 border border-[#e2e8f0] border-l-4 border-l-blue-500">
            <p class="text-xs font-bold text-gray-400 uppercase">Tổng hồ sơ</p>
            <p id="stat-total" class="text-3xl font-black text-gray-800 mt-1"><?= number_format($stats['total_candidates'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-[#e2e8f0] border-l-4 border-l-green-500">
            <p class="text-xs font-bold text-gray-400 uppercase">Đã duyệt</p>
            <p id="stat-approved" class="text-3xl font-black text-green-600 mt-1"><?= number_format($stats['total_approved'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-[#e2e8f0] border-l-4 border-l-yellow-500">
            <p class="text-xs font-bold text-gray-400 uppercase">Chờ duyệt</p>
            <p id="stat-pending" class="text-3xl font-black text-yellow-600 mt-1"><?= number_format($stats['by_status']['Chờ duyệt'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-[#e2e8f0] border-l-4 border-l-orange-500">
            <p class="text-xs font-bold text-gray-400 uppercase">Yêu cầu sửa</p>
            <p id="stat-edit" class="text-3xl font-black text-orange-600 mt-1"><?= number_format($stats['by_status']['Yêu cầu sửa'] ?? 0) ?></p>
        </div>
    </div>

    <!-- ===== EXPORT TABLE ===== -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-6">

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

                    <!-- Row 6: All Candidates Photo images -->
                    <tr>
                        <td class="tt-cell">6</td>
                        <td>
                            <div class="font-semibold text-slate-800 text-sm">Download file ảnh thẻ toàn bộ thí sinh</div>
                            <div class="text-xs text-slate-400 mt-0.5">Tải toàn bộ ảnh thẻ của tất cả thí sinh có hồ sơ trong đợt xét tuyển (định dạng ZIP)</div>
                        </td>
                        <td class="text-center">
                            <button onclick="doExport('<?= url('/admin/reports/download-photos-all') ?>')" class="download-btn !background-[#0066FF] hover:!bg-[#0050CC]">
                                <i class="fas fa-file-archive text-xs"></i> Download ZIP
                            </button>
                        </td>
                    </tr>

                    <!-- Row 7: Aptitude Candidates Photo images -->
                    <tr>
                        <td class="tt-cell">7</td>
                        <td>
                            <div class="font-semibold text-slate-800 text-sm">Download file ảnh thẻ thí sinh Năng khiếu</div>
                            <div class="text-xs text-slate-400 mt-0.5">Tải toàn bộ ảnh thẻ của thí sinh đăng ký dự thi năng khiếu theo đợt (định dạng ZIP)</div>
                        </td>
                        <td class="text-center">
                            <button onclick="doExport('<?= url('/admin/reports/download-photos-aptitude') ?>')" class="download-btn !background-[#0066FF] hover:!bg-[#0050CC]">
                                <i class="fas fa-file-archive text-xs"></i> Download ZIP
                            </button>
                        </td>
                    </tr>

                    <!-- Row 8: Certificate images -->
                    <tr>
                        <td class="tt-cell">8</td>
                        <td>
                            <div class="font-semibold text-slate-800 text-sm">Download file ảnh chứng chỉ ngoại ngữ</div>
                            <div class="text-xs text-slate-400 mt-0.5">Tải minh chứng ảnh chứng chỉ ngoại ngữ của thí sinh (định dạng ZIP)</div>
                        </td>
                        <td class="text-center">
                            <button onclick="doExport('<?= url('/admin/reports/download-certs') ?>')" class="download-btn !background-[#0066FF] hover:!bg-[#0050CC]">
                                <i class="fas fa-file-archive text-xs"></i> Download ZIP
                            </button>
                        </td>
                    </tr>

                    <!-- Row 9: Data Audit -->
                    <tr class="bg-slate-50/50">
                        <td class="tt-cell">9</td>
                        <td>
                            <div class="font-semibold text-slate-700 text-sm">Kiểm tra dữ liệu hồ sơ</div>
                            <div class="text-xs text-slate-400 mt-1 mb-2">Chọn tiêu chí để lọc danh sách các thí sinh cần kiểm tra lại thông tin</div>
                            <div class="flex items-center gap-2">
                                <select id="sel-audit-type" class="px-2 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-600 outline-none focus:ring-1 focus:ring-blue-400 w-full max-w-[400px]">
                                    <option value="">-- Chọn danh sách cần kiểm tra --</option>
                                    <option value="comprehensive">Tổng hợp hồ sơ cần rà soát (Đầy đủ lỗi rà soát + Ghi chú)</option>
                                    <option value="dob">Thí sinh cần kiểm tra lại ngày sinh (Trống hoặc 01/01/2008)</option>
                                    <option value="wishes">Thí sinh chưa có nguyện vọng xét tuyển</option>
                                    <option value="contact">Thí sinh thiếu thông tin liên hệ (Email/SĐT)</option>
                                    <option value="priority">Thí sinh thiếu thông tin ưu tiên (Đối tượng/Khu vực/Trường THPT)</option>
                                    <option value="free">Thí sinh tự do (Sinh năm 2007 trở về trước)</option>
                                    <option value="scores">Thí sinh chưa có điểm học bạ</option>
                                </select>
                            </div>
                        </td>
                        <td class="text-center">
                            <button onclick="doAuditExport()" class="download-btn !bg-orange-500 hover:!bg-orange-600 shadow-sm">
                                <i class="fas fa-file-excel"></i> Tải xuống
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="px-6 py-3 bg-slate-50 border-t border-[#e2e8f0] text-xs text-slate-400 flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-400"></i>
            Dữ liệu xuất ra thuộc <strong class="text-slate-600">đợt đang chọn</strong> ở bộ lọc phía trên. Cột số CCCD/ĐDCN được định dạng text để không mất số 0 đầu khi mở bằng Excel.
        </div>
</div>

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

function doAuditExport() {
    const selSession = document.getElementById('sel-session');
    const sessionId = selSession ? selSession.value : '';
    const selAudit = document.getElementById('sel-audit-type');
    const auditType = selAudit ? selAudit.value : '';
    
    if (!sessionId) {
        alert('Vui lòng chọn đợt tuyển sinh.');
        return;
    }
    if (!auditType) {
        alert('Vui lòng chọn loại danh sách cần kiểm tra.');
        return;
    }
    
    window.location.href = '<?= url('/admin/reports/download-data-audit') ?>?session_id=' + sessionId + '&type=' + auditType;
}

function updateLinks() {
    // Optional: could visual feedback when checkbox is toggled
}

// When session changes: Refresh stats via AJAX
function onSessionChange(sessionId) {
    if (!sessionId) return;
    
    const container = document.getElementById('stats-container');
    container.style.opacity = '0.5';
    
    fetch('<?= url('/admin/reports/stats-api') ?>?session_id=' + sessionId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('stat-total').textContent = (data.total_candidates || 0).toLocaleString();
            document.getElementById('stat-approved').textContent = (data.total_approved || 0).toLocaleString();
            document.getElementById('stat-pending').textContent = (data.by_status['Chờ duyệt'] || 0).toLocaleString();
            document.getElementById('stat-edit').textContent = (data.by_status['Yêu cầu sửa'] || 0).toLocaleString();
            container.style.opacity = '1';
        })
        .catch(err => {
            console.error('Lỗi tải thống kê:', err);
            container.style.opacity = '1';
        });
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
    if (activeSess) {
        sel.value = activeSess.id;
    } else {
        sel.value = filtered[0].id;
    }
    
    // Trigger stats update for the new selected session
    onSessionChange(sel.value);
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>
