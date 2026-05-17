<?php ob_start(); ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 font-heading uppercase tracking-tight">Quản lý Sao lưu Hệ thống</h2>
            <p class="text-sm text-slate-500 font-medium">Sao lưu & Khôi phục cơ sở dữ liệu <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-600 ml-1"><?= htmlspecialchars($dbHost ?? '') ?></span></p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= url('/admin/system/backup/create?test=1') ?>" 
               class="inline-flex items-center px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition-all group border border-slate-200">
                <i class="fas fa-flask mr-2 text-indigo-500"></i>
                TEST (MOCK)
            </a>
            <a href="<?= url('/admin/system/backup/create') ?>" 
               onclick="return confirm('Bạn có chắc chắn muốn tạo bản sao lưu mới? Quá trình này có thể mất vài phút.')"
               class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-indigo-200 group">
                <i class="fas fa-plus-circle mr-2 group-hover:rotate-90 transition-transform"></i>
                TẠO BẢN SAO LƯU MỚI
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center shadow-sm animate-fade-in">
            <i class="fas fa-check-circle mr-3 text-lg"></i>
            <span class="font-bold"><?= htmlspecialchars($_GET['success']) ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="mb-6 p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl flex items-center shadow-sm animate-fade-in">
            <i class="fas fa-exclamation-circle mr-3 text-lg"></i>
            <span class="font-bold"><?= htmlspecialchars($_GET['error']) ?></span>
        </div>
    <?php endif; ?>

    <!-- Schedule Settings Card -->
    <div class="mb-8 bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-50 bg-gradient-to-r from-indigo-50/50 to-sky-50/50 flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mr-4">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h3 class="font-black text-slate-800 uppercase tracking-wider text-sm">Sao lưu Tự động</h3>
                    <p class="text-xs text-slate-500 font-semibold italic">Thiết lập lịch sao lưu hàng ngày</p>
                </div>
            </div>
            <?php
                $isEnabled = ($settings['backup_enabled'] ?? '0') === '1';
                $lastRun = $settings['backup_last_run'] ?? '';
                $lastStatus = $settings['backup_last_status'] ?? '';
            ?>
            <div class="flex items-center gap-3">
                <?php if ($isEnabled): ?>
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[10px] font-black uppercase flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Đang bật
                    </span>
                <?php else: ?>
                    <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-full text-[10px] font-black uppercase">Đang tắt</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-6">
            <form action="<?= url('/admin/system/backup/settings') ?>" method="POST" class="flex flex-wrap items-end gap-6">
                <div class="flex items-center gap-3">
                    <label class="text-sm font-bold text-slate-700">Trạng thái:</label>
                    <select name="backup_enabled" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="0" <?= !$isEnabled ? 'selected' : '' ?>>Tắt</option>
                        <option value="1" <?= $isEnabled ? 'selected' : '' ?>>Bật</option>
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <label class="text-sm font-bold text-slate-700">Giờ sao lưu:</label>
                    <select name="backup_hour" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <?php for ($h = 0; $h < 24; $h++): ?>
                            <option value="<?= $h ?>" <?= ($settings['backup_hour'] ?? '1') == $h ? 'selected' : '' ?>>
                                <?= sprintf('%02d:00', $h) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all text-sm">
                    <i class="fas fa-save mr-1"></i> Lưu cài đặt
                </button>
            </form>

            <!-- Last backup status -->
            <?php if (!empty($lastRun)): ?>
            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-6 text-xs font-bold">
                <div class="text-slate-500">
                    <i class="fas fa-history mr-1"></i> Lần cuối: 
                    <span class="text-slate-700"><?= htmlspecialchars($lastRun) ?></span>
                </div>
                <div>
                    <?php if (str_starts_with($lastStatus, 'success')): ?>
                        <span class="text-emerald-600"><i class="fas fa-check-circle mr-1"></i> Thành công</span>
                    <?php else: ?>
                        <span class="text-rose-600"><i class="fas fa-times-circle mr-1"></i> <?= htmlspecialchars($lastStatus) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($settings['backup_last_file'])): ?>
                <div class="text-slate-500">
                    <i class="fas fa-file-archive mr-1"></i> <?= htmlspecialchars($settings['backup_last_file']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Local Backups -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mr-4">
                        <i class="fas fa-server"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-800 uppercase tracking-wider text-sm">Bộ nhớ Cục bộ (Server)</h3>
                        <p class="text-xs text-slate-500 font-semibold italic"><?= count($localBackups) ?> bản sao lưu</p>
                    </div>
                </div>
                <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-[10px] font-black uppercase">Local Storage</span>
            </div>
            
            <div class="flex-grow overflow-auto max-h-[500px]">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Tên File</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Dung lượng</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if (empty($localBackups)): ?>
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center">
                                    <i class="fas fa-folder-open text-slate-200 text-4xl mb-3 block"></i>
                                    <span class="text-slate-400 font-bold text-sm">Chưa có bản sao lưu nào</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($localBackups as $b): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-700"><?= $b['name'] ?></span>
                                            <span class="text-[10px] font-bold text-slate-400"><?= $b['date'] ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded-md text-[10px] font-black"><?= $b['size'] ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-right flex items-center justify-end gap-2">
                                        <button onclick="restoreBackup('<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>')" 
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-500 hover:bg-indigo-500 hover:text-white transition-all"
                                           title="Khôi phục vào database hiện tại (<?= htmlspecialchars($currentDb) ?>)">
                                            <i class="fas fa-undo-alt text-xs"></i>
                                        </button>
                                        <a href="<?= url('/admin/system/backup/delete?type=local&name=' . urlencode($b['name'])) ?>" 
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa bản sao lưu này?')"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all"
                                           title="Xóa">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cloud Backups (GDrive) -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-50 bg-indigo-50/10 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center mr-4">
                        <i class="fab fa-google-drive font-bold"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-800 uppercase tracking-wider text-sm">Google Drive Cloud</h3>
                        <p class="text-xs text-slate-500 font-semibold italic">An toàn tuyệt đối trên mây</p>
                    </div>
                </div>
                <span class="px-3 py-1 bg-sky-50 text-sky-600 rounded-full text-[10px] font-black uppercase">Google Cloud</span>
            </div>

            <div class="flex-grow overflow-auto max-h-[500px]">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Tên File</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Dung lượng</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if (empty($driveBackups)): ?>
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center">
                                    <i class="fab fa-google-drive text-slate-200 text-4xl mb-3 block"></i>
                                    <span class="text-slate-400 font-bold text-sm">Chưa có dữ liệu trên Cloud</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($driveBackups as $b): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-700"><?= $b['name'] ?></span>
                                            <span class="text-[10px] font-bold text-slate-400"><?= $b['date'] ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-1 bg-sky-50 text-sky-600 rounded-md text-[10px] font-black"><?= $b['size'] ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="<?= url('/admin/system/backup/delete?type=cloud&id=' . urlencode($b['id'])) ?>" 
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa bản sao lưu này trên Cloud?')"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Setup Instructions -->
    <div class="mt-8 bg-amber-50 rounded-2xl p-6 border border-amber-100">
        <h4 class="text-sm font-black text-amber-800 uppercase tracking-wider mb-3 flex items-center">
            <i class="fas fa-info-circle mr-2"></i>Hướng dẫn Sao lưu Tự động
        </h4>
        <p class="text-xs text-amber-700 leading-relaxed font-medium mb-3">
            Sử dụng một trong các cách sau để hệ thống tự động sao lưu theo lịch đã cấu hình ở trên:
        </p>

        <div class="space-y-3">
            <div>
                <span class="text-[10px] font-black text-amber-600 uppercase">Cách 1: Windows Task Scheduler (Khuyến nghị)</span>
                <div class="mt-1 p-3 bg-white/50 border border-amber-200 rounded-xl font-mono text-xs text-slate-700 select-all break-all">
                    schtasks /create /tn "TS_Backup_Daily" /tr "powershell -NoProfile -Command Invoke-WebRequest -Uri '<?= htmlspecialchars($cronUrl) ?>' -UseBasicParsing" /sc HOURLY /st 00:00
                </div>
            </div>

            <div>
                <span class="text-[10px] font-black text-amber-600 uppercase">Cách 2: Dịch vụ Cron Online (cron-job.org / Uptime Robot)</span>
                <div class="mt-1 p-3 bg-white/50 border border-amber-200 rounded-xl font-mono text-xs text-slate-700 break-all flex items-center gap-2">
                    <span class="select-all flex-1"><?= htmlspecialchars($cronUrl) ?></span>
                    <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($cronUrl, ENT_QUOTES) ?>'); this.innerHTML='<i class=\'fas fa-check\'></i>'; setTimeout(()=> this.innerHTML='<i class=\'fas fa-copy\'></i>', 2000)" 
                            class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg bg-amber-100 text-amber-600 hover:bg-amber-200 transition-all" title="Copy URL">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <p class="text-[10px] text-amber-600 font-medium mt-1 italic">
                    Thiết lập gọi URL này mỗi giờ. Hệ thống sẽ tự kiểm tra giờ cấu hình và chỉ backup đúng 1 lần/ngày.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.4s ease-out forwards;
}
</style>

<script>
function restoreBackup(filename) {
    const db = '<?= htmlspecialchars($currentDb, ENT_QUOTES) ?>';
    const host = '<?= htmlspecialchars($dbHost, ENT_QUOTES) ?>';

    let confirmMsg = `⚠️ CẢNH BÁO NGHIÊM TRỌNG\n\nBạn chuẩn bị khôi phục file:\n  "${filename}"\n\nvào database:\n  "${db}" @ ${host}\n\nDữ liệu hiện tại SẼ BỊ GHI ĐÈ.\n\nBạn có chắc chắn muốn tiếp tục?`;
    
    if (confirm(confirmMsg)) {
        if (confirm('XÁC NHẬN LẦN CUỐI: Thao tác này KHÔNG THỂ hoàn tác. Tiếp tục?')) {
            window.location.href = `<?= url('/admin/system/backup/restore') ?>?name=${encodeURIComponent(filename)}`;
        }
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>
