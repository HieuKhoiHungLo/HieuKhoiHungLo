<?php ob_start(); ?>
<?php
$isEnabled = ($settings['backup_enabled'] ?? '0') === '1';
$lastRun = $settings['backup_last_run'] ?? '';
$lastStatus = $settings['backup_last_status'] ?? '';
$lastFile = $settings['backup_last_file'] ?? '';
$backupHour = $settings['backup_hour'] ?? '1';
$backupMinute = $settings['backup_minute'] ?? '0';
$totalLocal = count($localBackups ?? []);
$totalCloud = count($driveBackups ?? []);
$totalSize = 0;
foreach (($localBackups ?? []) as $b) { $totalSize += ($b['size_bytes'] ?? 0); }
$totalSizeMb = round($totalSize / 1048576, 1);
?>

<style>
.tab-btn { position:relative; transition: all 0.3s; }
.tab-btn.active { color: #4f46e5; }
.tab-btn.active::after { content:''; position:absolute; bottom:-1px; left:0; right:0; height:3px; background:linear-gradient(90deg,#6366f1,#818cf8); border-radius:3px 3px 0 0; }
.tab-panel { display:none; animation: tabFadeIn 0.3s ease; }
.tab-panel.active { display:block; }
@keyframes tabFadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
.stat-card { background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%); }
.backup-row:hover { background:#f8fafc; transform:translateX(2px); }
.backup-row { transition: all 0.15s ease; }
.glow-btn { box-shadow: 0 4px 14px rgba(99,102,241,0.25); }
.glow-btn:hover { box-shadow: 0 6px 20px rgba(99,102,241,0.35); transform:translateY(-1px); }
.pulse-dot { animation: pulse-dot 2s infinite; }
@keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:0.4} }
</style>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ tab: 'backup' }">

<!-- Header -->
<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-200">
                <i class="fas fa-shield-alt text-white text-sm"></i>
            </div>
            Quản lý Sao lưu
        </h2>
        <p class="text-sm text-slate-500 font-medium mt-1">Bảo vệ dữ liệu • <span class="text-indigo-500 font-bold"><?= htmlspecialchars($dbHost ?? '') ?></span></p>
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
            <div class="w-9 h-9 rounded-lg bg-sky-100 flex items-center justify-center"><i class="fab fa-google-drive text-sky-500 text-sm"></i></div>
            <div><p class="text-[10px] font-bold text-slate-400 uppercase">Cloud</p><p class="text-lg font-black text-slate-800"><?= $totalCloud ?></p></div>
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
                <a href="<?= url('/admin/system/backup/create') ?>" onclick="return confirm('Tạo bản sao lưu mới? Quá trình có thể mất vài phút.')"
                   class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all glow-btn">
                    <i class="fas fa-plus-circle mr-2"></i> Tạo bản Sao lưu mới
                </a>
                <a href="<?= url('/admin/system/backup/create?test=1') ?>" class="inline-flex items-center px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition-all border border-slate-200">
                    <i class="fas fa-flask mr-2 text-indigo-400"></i> Test (Mock)
                </a>
                <?php if (!empty($lastRun)): ?>
                <div class="ml-auto flex items-center gap-4 text-xs font-bold text-slate-500">
                    <span><i class="fas fa-history mr-1"></i> <?= htmlspecialchars($lastRun) ?></span>
                    <?php if (str_starts_with($lastStatus, 'success')): ?>
                        <span class="text-emerald-600"><i class="fas fa-check-circle"></i> OK</span>
                    <?php elseif (!empty($lastStatus)): ?>
                        <span class="text-rose-500"><i class="fas fa-times-circle"></i> Lỗi</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Local backups grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Local -->
                <div>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <i class="fas fa-server text-indigo-400"></i> Bộ nhớ Cục bộ (<?= $totalLocal ?>)
                    </h4>
                    <div class="space-y-2 max-h-[400px] overflow-auto pr-1">
                        <?php if (empty($localBackups)): ?>
                            <div class="text-center py-10 text-slate-300"><i class="fas fa-folder-open text-3xl mb-2 block"></i><span class="text-sm font-bold">Trống</span></div>
                        <?php else: ?>
                            <?php foreach ($localBackups as $b): ?>
                            <div class="backup-row flex items-center justify-between p-3 rounded-xl border border-slate-100 group">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-file-archive text-indigo-400 text-xs"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-slate-700 truncate"><?= $b['name'] ?></p>
                                        <p class="text-[10px] font-bold text-slate-400"><?= $b['date'] ?> • <?= $b['size'] ?></p>
                                    </div>
                                </div>
                                <a href="<?= url('/admin/system/backup/delete?type=local&name=' . urlencode($b['name'])) ?>" onclick="return confirm('Xóa bản sao lưu này?')"
                                   class="w-7 h-7 rounded-lg bg-rose-50 text-rose-400 hover:bg-rose-500 hover:text-white flex items-center justify-center transition-all opacity-0 group-hover:opacity-100" title="Xóa">
                                    <i class="fas fa-trash-alt text-[10px]"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Cloud -->
                <div>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <i class="fab fa-google-drive text-sky-400"></i> Google Drive (<?= $totalCloud ?>)
                    </h4>
                    <div class="space-y-2 max-h-[400px] overflow-auto pr-1">
                        <?php if (empty($driveBackups)): ?>
                            <div class="text-center py-10 text-slate-300"><i class="fab fa-google-drive text-3xl mb-2 block"></i><span class="text-sm font-bold">Trống</span></div>
                        <?php else: ?>
                            <?php foreach ($driveBackups as $b): ?>
                            <div class="backup-row flex items-center justify-between p-3 rounded-xl border border-slate-100 group">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-lg bg-sky-50 flex items-center justify-center flex-shrink-0">
                                        <i class="fab fa-google-drive text-sky-400 text-xs"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-slate-700 truncate"><?= $b['name'] ?></p>
                                        <p class="text-[10px] font-bold text-slate-400"><?= $b['date'] ?> • <?= $b['size'] ?></p>
                                    </div>
                                </div>
                                <a href="<?= url('/admin/system/backup/delete?type=cloud&id=' . urlencode($b['id'])) ?>" onclick="return confirm('Xóa file này trên Cloud?')"
                                   class="w-7 h-7 rounded-lg bg-rose-50 text-rose-400 hover:bg-rose-500 hover:text-white flex items-center justify-center transition-all opacity-0 group-hover:opacity-100">
                                    <i class="fas fa-trash-alt text-[10px]"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: KHÔI PHỤC -->
    <div x-show="tab==='restore'" x-cloak class="tab-panel" :class="tab==='restore' && 'active'">
        <div class="p-6">
            <div class="mb-6 p-4 bg-amber-50 border border-amber-100 rounded-2xl">
                <h4 class="text-sm font-black text-amber-800 flex items-center gap-2 mb-1"><i class="fas fa-exclamation-triangle"></i> Lưu ý quan trọng</h4>
                <p class="text-xs text-amber-700 font-medium leading-relaxed">
                    Khôi phục sẽ <strong>ghi đè toàn bộ dữ liệu</strong> trong database hiện tại
                    (<span class="font-black"><?= htmlspecialchars($currentDb) ?></span> @ <?= htmlspecialchars($dbHost) ?>).
                    Hãy chắc chắn bạn đã sao lưu dữ liệu hiện tại trước khi khôi phục.
                </p>
            </div>

            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Chọn bản sao lưu để khôi phục</h4>

            <?php if (empty($localBackups)): ?>
                <div class="text-center py-16 text-slate-300"><i class="fas fa-inbox text-4xl mb-3 block"></i><p class="font-bold">Không có bản sao lưu nào</p><p class="text-xs mt-1">Hãy tạo bản sao lưu trước</p></div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($localBackups as $i => $b): ?>
                    <div class="backup-row flex items-center justify-between p-4 rounded-xl border border-slate-100 group hover:border-indigo-200">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl <?= $i === 0 ? 'bg-emerald-50' : 'bg-slate-50' ?> flex items-center justify-center">
                                <i class="fas fa-file-archive <?= $i === 0 ? 'text-emerald-500' : 'text-slate-400' ?>"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-700"><?= $b['name'] ?> <?= $i === 0 ? '<span class="text-[10px] px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded-full font-black ml-1">MỚI NHẤT</span>' : '' ?></p>
                                <p class="text-xs font-bold text-slate-400"><?= $b['date'] ?> • <?= $b['size'] ?> • <?= $b['format'] === 'custom' ? 'Custom Format' : 'Legacy SQL' ?></p>
                            </div>
                        </div>
                        <button onclick="restoreBackup('<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>')"
                           class="px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white font-bold rounded-xl transition-all text-xs flex items-center gap-2">
                            <i class="fas fa-undo-alt"></i> Khôi phục
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
                    <?php if (!empty($lastRun)): ?>
                    <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-4 text-xs font-bold">
                        <span class="text-slate-500"><i class="fas fa-history mr-1"></i> Lần cuối: <?= htmlspecialchars($lastRun) ?></span>
                        <?php if (str_starts_with($lastStatus, 'success')): ?>
                            <span class="text-emerald-600"><i class="fas fa-check-circle"></i> Thành công</span>
                        <?php elseif (!empty($lastStatus)): ?>
                            <span class="text-rose-500"><i class="fas fa-times-circle"></i> <?= htmlspecialchars(mb_substr($lastStatus, 0, 80)) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($lastFile)): ?>
                            <span class="text-slate-400"><i class="fas fa-file"></i> <?= htmlspecialchars($lastFile) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <div class="p-3 bg-indigo-50/50 rounded-xl border border-indigo-100">
                            <p class="font-black text-indigo-700 mb-1"><i class="fas fa-windows mr-1"></i> Windows Task Scheduler</p>
                            <p class="font-mono text-[10px] text-indigo-600 select-all leading-relaxed">schtasks /create /tn "TS_Backup" /tr "powershell -NoProfile -Command Invoke-WebRequest -Uri '<?= htmlspecialchars($cronUrl) ?>' -UseBasicParsing" /sc HOURLY</p>
                        </div>
                        <div class="p-3 bg-sky-50/50 rounded-xl border border-sky-100">
                            <p class="font-black text-sky-700 mb-1"><i class="fas fa-globe mr-1"></i> Dịch vụ Online</p>
                            <p class="text-[10px] text-sky-600 font-medium">Dùng <a href="https://cron-job.org" target="_blank" class="underline font-bold">cron-job.org</a> hoặc <a href="https://uptimerobot.com" target="_blank" class="underline font-bold">Uptime Robot</a> để gọi URL trên mỗi giờ.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DB Info -->
            <div class="rounded-2xl border border-slate-100 overflow-hidden">
                <div class="p-4 bg-slate-50/50 border-b border-slate-100">
                    <h4 class="text-xs font-black text-slate-600 uppercase tracking-widest flex items-center gap-2"><i class="fas fa-database text-amber-400"></i> Thông tin Kết nối</h4>
                </div>
                <div class="p-5 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div><p class="font-black text-slate-400 uppercase mb-1">Backup Host</p><p class="font-bold text-slate-700"><?= htmlspecialchars($_ENV['DB_HOST'] ?? '') ?></p></div>
                    <div><p class="font-black text-slate-400 uppercase mb-1">Backup Port</p><p class="font-bold text-slate-700"><?= htmlspecialchars($_ENV['DB_PORT'] ?? '') ?></p></div>
                    <div><p class="font-black text-slate-400 uppercase mb-1">Restore Host</p><p class="font-bold text-slate-700"><?= htmlspecialchars($_ENV['DB_RESTORE_HOST'] ?? $_ENV['DB_HOST'] ?? '') ?></p></div>
                    <div><p class="font-black text-slate-400 uppercase mb-1">Restore Port</p><p class="font-bold text-slate-700"><?= htmlspecialchars($_ENV['DB_RESTORE_PORT'] ?? $_ENV['DB_PORT'] ?? '') ?></p></div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
function restoreBackup(filename) {
    const db = '<?= htmlspecialchars($currentDb ?? "postgres", ENT_QUOTES) ?>';
    const host = '<?= htmlspecialchars($dbHost ?? "", ENT_QUOTES) ?>';
    if (!confirm(`⚠️ CẢNH BÁO\n\nKhôi phục "${filename}" vào database "${db}" @ ${host}?\n\nDữ liệu hiện tại SẼ BỊ GHI ĐÈ.`)) return;
    if (!confirm('XÁC NHẬN LẦN CUỐI: Thao tác KHÔNG THỂ hoàn tác. Tiếp tục?')) return;
    window.location.href = `<?= url('/admin/system/backup/restore') ?>?name=${encodeURIComponent(filename)}`;
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>
