<?php ob_start(); ?>
<?php
$isEnabled = ($settings['backup_enabled'] ?? '0') === '1';
$lastRun = $settings['backup_last_run'] ?? '';
$lastStatus = $settings['backup_last_status'] ?? '';
$lastFile = $settings['backup_last_file'] ?? '';
$backupHour = $settings['backup_hour'] ?? '1';
$backupMinute = $settings['backup_minute'] ?? '0';
$totalLocal = count($localBackups ?? []);
$totalSize = 0;
foreach (($localBackups ?? []) as $b) { $totalSize += ($b['size_bytes'] ?? 0); }
$totalSizeMb = round($totalSize / 1048576, 1);

// Checking Google Drive sync state
$googleClientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? 'client_secret.json';
$googleTokenFile = $_ENV['GOOGLE_TOKEN_FILE'] ?? 'token.json';
$secretPath = dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . $googleClientSecret;
$tokenPath = dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . $googleTokenFile;
$isDriveActive = file_exists($secretPath) && file_exists($tokenPath);
?>

<style>
.tab-btn { position:relative; transition: all 0.3s; }
.tab-btn.active { color: #4f46e5; }
.tab-btn.active::after { content:''; position:absolute; bottom:-1px; left:0; right:0; height:3px; background:linear-gradient(90deg,#6366f1,#818cf8); border-radius:3px 3px 0 0; }
.tab-panel { display:none; animation: tabFadeIn 0.3s ease; }
.tab-panel.active { display:block; }
@keyframes tabFadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
.stat-card { background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%); }
.glow-btn { box-shadow: 0 4px 14px rgba(99,102,241,0.25); }
.glow-btn:hover { box-shadow: 0 6px 20px rgba(99,102,241,0.35); transform:translateY(-1px); }

/* Review-management style Backup Table */
.backup-table {
    border-collapse: separate !important;
    border-spacing: 0;
    width: 100%;
    table-layout: fixed;
    background-color: #ffffff;
}
.backup-table th, .backup-table td {
    padding: 0.5rem 0.75rem !important;
    border: none !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    vertical-align: middle;
    font-size: 13px !important;
    color: #000000 !important;
    font-weight: 400 !important;
    text-transform: none !important;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.backup-table th {
    background-color: #f8fafc !important;
    color: #000000 !important;
}
.backup-table th:first-child, .backup-table td:first-child {
    border-left: 1px solid #e2e8f0 !important;
}
.backup-table thead tr:first-child th {
    border-top: 1px solid #e2e8f0 !important;
}
.backup-table tbody tr:hover td {
    background-color: #f1f5f9 !important;
}

/* Premium Loading Modal styles matching import progress */
.shimmer-glare {
    background: linear-gradient(
        to right,
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 0.4) 50%,
        rgba(255, 255, 255, 0) 100%
    );
    animation: loading-shimmer 2s infinite linear;
}
@keyframes loading-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
@keyframes pulsing-slow {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.05); }
}
.animate-pulsing-slow {
    animation: pulsing-slow 3s infinite ease-in-out;
}
@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin-slow {
    animation: spin-slow 6s infinite linear;
}
[x-cloak] { display: none !important; }
</style>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="backupApp()">

    <!-- Premium Loading Modal -->
    <div x-cloak x-show="isLoading" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/40 backdrop-blur-md z-[100] flex items-center justify-center p-4">
        
        <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 w-full max-w-md p-8 text-center relative overflow-hidden">
            <!-- Decorative background shapes -->
            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-32 h-32 bg-purple-500/10 rounded-full blur-3xl"></div>

            <!-- Animated Logo Container -->
            <div class="relative w-28 h-28 mx-auto mb-6">
                <!-- Outer Pulse ring -->
                <div class="absolute inset-0 bg-indigo-500/20 rounded-full animate-pulsing-slow"></div>
                <!-- Dotted rotating ring -->
                <div class="absolute inset-1 border-2 border-indigo-200 border-dashed rounded-full animate-spin-slow"></div>
                <!-- Glassmorphism Circle with Logo -->
                <div class="absolute inset-4 bg-white rounded-full flex items-center justify-center shadow-xl border border-white/50 overflow-hidden">
                    <img src="<?= url('/assets/img/Logo.png') ?>" 
                         alt="Logo" 
                         class="w-full h-full object-contain p-2 relative z-10">
                    <!-- Internal Shimmer -->
                    <div class="shimmer-glare absolute inset-0 z-20 opacity-30"></div>
                </div>
            </div>

            <h3 class="text-xl font-bold text-slate-800 mb-6">Hệ thống đang sao lưu</h3>
            
            <!-- Progress container -->
            <div class="relative h-2 bg-slate-100 rounded-full overflow-hidden mb-2">
                <div class="absolute top-0 left-0 h-full bg-indigo-600 rounded-full transition-all duration-500 shadow-[0_0_10px_rgba(99,102,241,0.5)]" 
                     :style="`width: ${progress}%`"
                     id="loadingProgress">
                </div>
                <!-- Shimmering overlay -->
                <div class="shimmer-glare absolute inset-0"></div>
            </div>
            <div class="flex justify-between text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">
                <span x-text="progress + '%'"></span>
                <span x-text="progress < 100 ? 'Vui lòng không đóng trang' : 'Hoàn thành!'"></span>
            </div>
        </div>
    </div>

<!-- Header -->
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-200">
                <i class="fas fa-shield-alt text-white text-sm"></i>
            </div>
            Quản lý Sao lưu
        </h2>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card rounded-2xl p-4 border border-slate-100">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center"><i class="fas fa-server text-indigo-500 text-sm"></i></div>
            <div><p class="text-[10px] font-bold text-slate-400 uppercase">Cục bộ</p><p class="text-lg font-black text-slate-800"><?= $totalLocal ?></p></div>
        </div>
    </div>
    <div class="stat-card rounded-2xl p-4 border border-slate-100">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg <?= $isDriveActive ? 'bg-sky-100' : 'bg-slate-100' ?> flex items-center justify-center">
                <i class="fab fa-google-drive <?= $isDriveActive ? 'text-sky-500' : 'text-slate-400' ?> text-sm"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase">Đồng bộ Cloud</p>
                <p class="text-[12px] font-black <?= $isDriveActive ? 'text-sky-600' : 'text-slate-400' ?>">
                    <?= $isDriveActive ? '🟢 ĐANG BẬT' : '🔴 CHƯA BẬT' ?>
                </p>
            </div>
        </div>
    </div>
    <div class="stat-card rounded-2xl p-4 border border-slate-100">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center"><i class="fas fa-database text-amber-500 text-sm"></i></div>
            <div><p class="text-[10px] font-bold text-slate-400 uppercase">Dung lượng</p><p class="text-lg font-black text-slate-800"><?= $totalSizeMb ?> MB</p></div>
        </div>
    </div>
    <div class="stat-card rounded-2xl p-4 border border-slate-100">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg <?= $isEnabled ? 'bg-emerald-100' : 'bg-slate-100' ?> flex items-center justify-center">
                <i class="fas fa-clock <?= $isEnabled ? 'text-emerald-500' : 'text-slate-400' ?> text-sm"></i>
            </div>
            <div><p class="text-[10px] font-bold text-slate-400 uppercase">Tự động</p><p class="text-lg font-black <?= $isEnabled ? 'text-emerald-600' : 'text-slate-400' ?>"><?= $isEnabled ? sprintf('%02d:%02d', $backupHour, $backupMinute) : 'Tắt' ?></p></div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (isset($_GET['success'])): ?>
<div class="mb-4 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center shadow-sm"><i class="fas fa-check-circle mr-3 text-lg"></i><span class="font-bold text-sm"><?= htmlspecialchars($_GET['success']) ?></span></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="mb-4 p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl flex items-center shadow-sm"><i class="fas fa-exclamation-circle mr-3 text-lg"></i><span class="font-bold text-sm"><?= htmlspecialchars($_GET['error']) ?></span></div>
<?php endif; ?>

<!-- Tabs -->
<div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="border-b border-slate-100 px-6 flex gap-0">
        <button @click="tab='backup'" :class="tab==='backup' && 'active'" class="tab-btn px-5 py-4 text-sm font-black uppercase tracking-wider text-slate-400 hover:text-indigo-500 flex items-center gap-2">
            <i class="fas fa-cloud-upload-alt"></i> Sao lưu
        </button>
        <button @click="tab='restore'" :class="tab==='restore' && 'active'" class="tab-btn px-5 py-4 text-sm font-black uppercase tracking-wider text-slate-400 hover:text-indigo-500 flex items-center gap-2">
            <i class="fas fa-undo-alt"></i> Khôi phục
        </button>
        <button @click="tab='settings'" :class="tab==='settings' && 'active'" class="tab-btn px-5 py-4 text-sm font-black uppercase tracking-wider text-slate-400 hover:text-indigo-500 flex items-center gap-2">
            <i class="fas fa-cog"></i> Thiết lập
        </button>
    </div>

    <!-- TAB 1: SAO LƯU -->
    <div x-show="tab==='backup'" x-cloak class="tab-panel" :class="tab==='backup' && 'active'">
        <div class="p-6">
            <!-- Action buttons -->
            <div class="flex flex-wrap items-center gap-3 mb-6">
                <button @click="startBackup()"
                   class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all glow-btn cursor-pointer">
                    <i class="fas fa-plus-circle mr-2"></i> Tạo bản Sao lưu mới
                </button>
                <?php if (!empty($lastRun)): ?>
                <div class="ml-auto flex items-center gap-4 text-xs font-bold text-slate-500">
                    <span><i class="fas fa-history mr-1"></i> <?= htmlspecialchars($lastRun) ?></span>
                    <div class="flex items-center gap-1">
                        <?php if (str_starts_with($lastStatus, 'success')): ?>
                            <span class="text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full flex items-center gap-1"><i class="fas fa-check-circle text-[10px]"></i> Thành công</span>
                        <?php elseif (!empty($lastStatus)): ?>
                            <span class="text-rose-500 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded-full flex items-center gap-1"><i class="fas fa-times-circle text-[10px]"></i> Lỗi</span>
                        <?php endif; ?>
                        
                        <?php if (!empty($settings['backup_last_log'])): ?>
                            <button onclick="document.getElementById('backupLogModal').classList.remove('hidden')" class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-100 hover:bg-indigo-100 text-slate-500 hover:text-indigo-600 transition-colors" title="Xem chi tiết nhật ký">
                                <i class="fas fa-list-alt text-[11px]"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Local backups table (Premium Review Management Style) -->
            <div class="overflow-x-auto border border-slate-200 rounded-2xl shadow-sm">
                <table class="backup-table min-w-[920px]">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">STT</th>
                            <th style="width: 320px;">Tên bản sao lưu</th>
                            <th style="width: 135px;">Định dạng</th>
                            <th style="width: 110px;">Dung lượng</th>
                            <th style="width: 180px;">Ngày sao lưu</th>
                            <th style="width: 120px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($localBackups)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-20 text-slate-300">
                                    <i class="fas fa-folder-open text-4xl mb-3 block text-slate-200"></i>
                                    <span class="text-sm font-bold text-slate-400">Không có bản sao lưu cục bộ nào</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($localBackups as $index => $b): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <?= $index + 1 ?>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-file-archive text-slate-400"></i>
                                        <span class="truncate" title="<?= htmlspecialchars($b['name']) ?>">
                                            <?= htmlspecialchars($b['name']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?= $b['format'] === 'custom' ? 'tệp tin (.backup)' : 'tệp tin (.sql)' ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($b['size']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($b['date']) ?>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Download -->
                                        <a href="<?= url('/admin/system/backup/download?name=' . urlencode($b['name'])) ?>" 
                                           class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-500 hover:bg-indigo-600 hover:text-white flex items-center justify-center transition-all" 
                                           title="Tải xuống bản sao lưu">
                                            <i class="fas fa-download text-xs"></i>
                                        </a>
                                        <!-- Delete -->
                                        <a href="<?= url('/admin/system/backup/delete?type=local&name=' . urlencode($b['name'])) ?>" 
                                           onclick="return confirm('Xóa bản sao lưu này? Thao tác này không thể hoàn tác.')"
                                           class="w-7 h-7 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-all" 
                                           title="Xóa bản sao lưu">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: KHÔI PHỤC -->
    <div x-show="tab==='restore'" x-cloak class="tab-panel" :class="tab==='restore' && 'active'">
        <div class="p-6">
            <!-- Local backups table (Premium style matching Tab 1) -->
            <div class="overflow-x-auto border border-slate-200 rounded-2xl shadow-sm">
                <table class="backup-table min-w-[920px]">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">STT</th>
                            <th style="width: 320px;">Tên bản sao lưu</th>
                            <th style="width: 135px;">Định dạng</th>
                            <th style="width: 110px;">Dung lượng</th>
                            <th style="width: 180px;">Ngày sao lưu</th>
                            <th style="width: 120px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($localBackups)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-20 text-slate-300">
                                    <i class="fas fa-folder-open text-4xl mb-3 block text-slate-200"></i>
                                    <span class="text-sm font-bold text-slate-400">Không có bản sao lưu nào để khôi phục</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($localBackups as $index => $b): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <?= $index + 1 ?>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-file-archive text-slate-400"></i>
                                        <span class="truncate" title="<?= htmlspecialchars($b['name']) ?>">
                                            <?= htmlspecialchars($b['name']) ?>
                                        </span>
                                        <?php if ($index === 0): ?>
                                            <span class="text-[9px] px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded-full font-black">MỚI NHẤT</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?= $b['format'] === 'custom' ? 'tệp tin (.backup)' : 'tệp tin (.sql)' ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($b['size']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($b['date']) ?>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center">
                                        <button onclick="restoreBackup('<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>')"
                                           class="px-4 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white font-bold rounded-xl transition-all text-xs flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-undo-alt text-[10px]"></i> Khôi phục
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: THIẾT LẬP -->
    <div x-show="tab==='settings'" x-cloak class="tab-panel" :class="tab==='settings' && 'active'">
        <div class="p-6 space-y-6">
            <!-- Schedule -->
            <div class="rounded-2xl border border-slate-100 overflow-hidden">
                <div class="p-4 bg-slate-50/50 border-b border-slate-100">
                    <h4 class="text-xs font-black text-slate-600 uppercase tracking-widest flex items-center gap-2"><i class="fas fa-clock text-indigo-400"></i> Lịch sao lưu tự động</h4>
                </div>
                <form action="<?= url('/admin/system/backup/settings') ?>" method="POST" class="p-5">
                    <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
                    <div class="flex flex-wrap items-end gap-6">
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2">Trạng thái</label>
                            <select name="backup_enabled" class="px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none w-36">
                                <option value="0" <?= !$isEnabled ? 'selected' : '' ?>>🔴 Tắt</option>
                                <option value="1" <?= $isEnabled ? 'selected' : '' ?>>🟢 Bật</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase mb-2">Giờ sao lưu</label>
                                <select name="backup_hour" class="px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none w-24">
                                    <?php for ($h = 0; $h < 24; $h++): ?>
                                        <option value="<?= $h ?>" <?= $backupHour == $h ? 'selected' : '' ?>><?= sprintf('%02d', $h) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <span class="text-slate-400 font-bold self-end mb-2.5">:</span>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase mb-2">Phút</label>
                                <select name="backup_minute" class="px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none w-24">
                                    <?php for ($m = 0; $m < 60; $m += 5): ?>
                                        <option value="<?= $m ?>" <?= $backupMinute == $m ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all text-sm glow-btn">
                            <i class="fas fa-save mr-1"></i> Lưu
                        </button>
                    </div>
                </form>
            </div>

            <!-- Webhook URL -->
            <div class="rounded-2xl border border-slate-100 overflow-hidden">
                <div class="p-4 bg-slate-50/50 border-b border-slate-100">
                    <h4 class="text-xs font-black text-slate-600 uppercase tracking-widest flex items-center gap-2"><i class="fas fa-link text-sky-400"></i> Webhook URL (Cho Cron/Scheduler)</h4>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 p-3 bg-slate-50 border border-slate-200 rounded-xl font-mono text-xs text-slate-600 break-all select-all"><?= htmlspecialchars($cronUrl) ?></div>
                        <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($cronUrl, ENT_QUOTES) ?>');this.innerHTML='<i class=\'fas fa-check text-emerald-500\'></i>';setTimeout(()=>this.innerHTML='<i class=\'fas fa-copy\'></i>',2000)"
                                class="w-10 h-10 flex-shrink-0 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-indigo-100 text-slate-500 hover:text-indigo-600 transition-all" title="Copy"><i class="fas fa-copy"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Backup Log Modal -->
<div id="backupLogModal" class="fixed inset-0 z-50 hidden" style="z-index: 999999;">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col pointer-events-auto overflow-hidden animate-tabFadeIn">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-terminal text-slate-400"></i> Nhật ký quá trình sao lưu
                </h3>
                <button onclick="document.getElementById('backupLogModal').classList.add('hidden')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-5 flex-1 overflow-y-auto bg-slate-900 text-slate-300 font-mono text-[13px] leading-relaxed custom-scrollbar">
                <?php if (!empty($settings['backup_last_log'])): ?>
                    <?php foreach ($settings['backup_last_log'] as $logLine): ?>
                        <div class="mb-1.5 <?= str_contains($logLine, '[SUCCESS]') ? 'text-emerald-400' : (str_contains($logLine, '[WARNING]') || str_contains($logLine, '[ERROR]') ? 'text-rose-400' : (str_contains($logLine, '[INFO]') ? 'text-sky-400' : '')) ?>">
                            <?= htmlspecialchars($logLine) ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-slate-500 italic">Không có nhật ký nào được lưu.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function backupApp() {
    return {
        tab: 'backup',
        isLoading: false,
        progress: 0,
        currentLoadingMessage: '',
        
        async startBackup() {
            const confirmed = confirm('Tạo bản sao lưu mới? Quá trình có thể mất vài phút.');
            if (!confirmed) return;
            
            this.isLoading = true;
            this.progress = 0;
            this.currentLoadingMessage = 'Đang chuẩn bị kết nối cơ sở dữ liệu...';
            
            // Decelerating progress logic to look highly natural over ~80 seconds
            let progressInterval = setInterval(() => {
                if (this.progress < 98) {
                    let increment = 0;
                    if (this.progress < 30) {
                        increment = Math.random() * 2 + 1; // 1-3% at first
                    } else if (this.progress < 60) {
                        increment = Math.random() * 1 + 0.5; // 0.5-1.5%
                    } else if (this.progress < 85) {
                        increment = Math.random() * 0.5 + 0.2; // 0.2-0.7%
                    } else if (this.progress < 95) {
                        increment = Math.random() * 0.2 + 0.05; // 0.05-0.25%
                    } else {
                        increment = 0.02; // Very slow crawl near 98%
                    }
                    
                    this.progress = Math.min(98, parseFloat((this.progress + increment).toFixed(2)));
                    
                    // Update messages based on progress
                    if (this.progress < 20) {
                        this.currentLoadingMessage = 'Đang khởi tạo kết nối cơ sở dữ liệu Supabase...';
                    } else if (this.progress < 50) {
                        this.currentLoadingMessage = 'Đang kết xuất sơ đồ cấu trúc bảng (schema)...';
                    } else if (this.progress < 75) {
                        this.currentLoadingMessage = 'Đang trích xuất dữ liệu bản ghi và nén file Custom Format (.backup)...';
                    } else if (this.progress < 90) {
                        this.currentLoadingMessage = 'Đang lưu tệp tin sao lưu cục bộ vào storage...';
                    } else {
                        this.currentLoadingMessage = 'Đang truyền tải và đồng bộ hóa tệp lên Cloud Google Drive...';
                    }
                }
            }, 600);
            
            try {
                const response = await fetch('<?= url("/admin/system/backup/create?ajax=1") ?>', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                clearInterval(progressInterval);
                
                const result = await response.json();
                if (result.status) {
                    this.progress = 100;
                    this.currentLoadingMessage = 'Hoàn thành!';
                    setTimeout(() => {
                        this.isLoading = false;
                        window.location.href = '<?= url("/admin/system/backup?success=") ?>' + encodeURIComponent(result.message + ' (' + result.file + ')');
                    }, 500);
                } else {
                    this.isLoading = false;
                    alert('Lỗi: ' + result.message);
                }
            } catch (err) {
                clearInterval(progressInterval);
                this.isLoading = false;
                alert('Lỗi kết nối mạng: ' + err.message);
            }
        }
    };
}

function restoreBackup(filename) {
    const db = '<?= htmlspecialchars($currentDb ?? "postgres", ENT_QUOTES) ?>';
    const host = '<?= htmlspecialchars($dbHost ?? "", ENT_QUOTES) ?>';
    
    if (!confirm(`⚠️ CẢNH BÁO NGUY HIỂM\n\nBạn có chắc chắn muốn khôi phục bản sao lưu "${filename}" vào cơ sở dữ liệu "${db}" @ ${host}?\n\nToàn bộ dữ liệu hiện tại SẼ BỊ GHI ĐÈ và KHÔNG THỂ HOÀN TÁC.`)) return;
    
    const password = prompt('Vui lòng nhập mật khẩu tài khoản quản trị của bạn để xác nhận khôi phục:');
    if (password === null) return; // User cancelled
    if (password.trim() === '') {
        alert('Mật khẩu không được để trống.');
        return;
    }

    // Create dynamic form and POST securely
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url('/admin/system/backup/restore') ?>';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = '<?= $this->csrfToken() ?>';
    form.appendChild(csrfInput);

    const nameInput = document.createElement('input');
    nameInput.type = 'hidden';
    nameInput.name = 'name';
    nameInput.value = filename;
    form.appendChild(nameInput);

    const passInput = document.createElement('input');
    passInput.type = 'hidden';
    passInput.name = 'password';
    passInput.value = password;
    form.appendChild(passInput);

    document.body.appendChild(form);
    form.submit();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>
