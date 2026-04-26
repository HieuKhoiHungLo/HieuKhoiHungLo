<?php ob_start(); ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight flex items-center gap-3">
                <i class="fas fa-mail-bulk text-[#0066FF]"></i>
                Hàng đợi Email
            </h2>
            <p class="text-slate-500 text-sm font-medium mt-1">Theo dõi trạng thái và hiệu năng gửi thư tự động của hệ thống.</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.location.reload()" class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 transition-all shadow-sm">
                <i class="fas fa-sync-alt"></i>
            </button>
            <a href="<?= url('/admin/email-queue/clear-sent') ?>" onclick="return confirm('Xóa toàn bộ các thư đã gửi thành công để làm sạch hàng đợi?')" class="px-4 py-2.5 bg-slate-100 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-200 transition-all uppercase tracking-wider">
                Làm sạch hàng đợi
            </a>
            <a href="<?= url('/admin/email-queue/retry') ?>" onclick="return confirm('Gửi lại toàn bộ các thư đang bị lỗi?')" class="px-4 py-2.5 bg-[#0066FF] text-white font-bold text-xs rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 uppercase tracking-wider">
                Gửi lại tất cả lỗi
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                <i class="fas fa-inbox text-5xl"></i>
            </div>
            <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-1">Tổng cộng</p>
            <h3 class="text-3xl font-black text-slate-800"><?= number_format($stats['total']) ?></h3>
            <p class="text-slate-400 text-xs mt-2 font-medium">Bản ghi trong hàng đợi</p>
        </div>

        <!-- Pending -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform text-amber-500">
                <i class="fas fa-clock text-5xl"></i>
            </div>
            <p class="text-amber-500 text-[10px] font-black uppercase tracking-widest mb-1">Đang chờ</p>
            <h3 class="text-3xl font-black text-slate-800"><?= number_format($stats['pending']) ?></h3>
            <p class="text-slate-400 text-xs mt-2 font-medium">Sắp được gửi đi</p>
        </div>

        <!-- Sent -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group border-l-4 border-l-emerald-500">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform text-emerald-500">
                <i class="fas fa-check-circle text-5xl"></i>
            </div>
            <p class="text-emerald-600 text-[10px] font-black uppercase tracking-widest mb-1">Đã gửi</p>
            <h3 class="text-3xl font-black text-slate-800"><?= number_format($stats['sent']) ?></h3>
            <div class="mt-2 space-y-1">
                <p class="text-emerald-600/70 text-[10px] font-bold flex items-center gap-1">
                    <i class="fas fa-bolt w-3"></i> Tốc độ: <?= number_format($advStats['hour_count']) ?> thư/giờ
                </p>
                <p class="text-slate-500 text-[10px] font-medium flex items-center gap-1">
                    <i class="fas fa-calendar-day w-3"></i> Hôm nay: <?= number_format($advStats['today_count']) ?>
                </p>
                <p class="text-slate-500 text-[10px] font-medium flex items-center gap-1">
                    <i class="fas fa-calendar-week w-3"></i> Tuần này: <?= number_format($advStats['week_count']) ?>
                </p>
            </div>
        </div>

        <!-- Failed -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group border-l-4 border-l-rose-500">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform text-rose-500">
                <i class="fas fa-exclamation-triangle text-5xl"></i>
            </div>
            <p class="text-rose-600 text-[10px] font-black uppercase tracking-widest mb-1">Bị lỗi</p>
            <h3 class="text-3xl font-black text-slate-800"><?= number_format($stats['failed']) ?></h3>
            <p class="text-rose-600/70 text-xs mt-2 font-medium">Cần kiểm tra danh sách dưới</p>
        </div>
    </div>

    <!-- Failed Emails Table -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden">
        <div class="p-6 border-b border-slate-50 flex items-center justify-between bg-slate-50/50">
            <h4 class="font-black text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                <i class="fas fa-bug text-rose-500"></i>
                Danh sách thư lỗi gần đây
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Thời gian</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Người nhận</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Tiêu đề</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Lỗi cuối</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($failedEmails)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-300">
                                    <i class="fas fa-check-double text-4xl mb-3"></i>
                                    <p class="text-sm font-bold uppercase tracking-widest">Tuyệt vời! Không có thư lỗi nào.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($failedEmails as $email): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-xs text-slate-500 font-medium whitespace-nowrap">
                                    <?= date('d/m/Y H:i:s', strtotime($email['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold text-slate-800"><?= htmlspecialchars($email['recipient']) ?></span>
                                    <div class="text-[9px] text-slate-400 uppercase font-black mt-0.5"><?= $email['category'] ?></div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 truncate max-w-xs">
                                    <?= htmlspecialchars($email['subject']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-[10px] text-rose-600 font-bold leading-tight max-w-xs break-words">
                                        <?= htmlspecialchars($email['last_error'] ?: ($email['error'] ?: 'Lỗi không xác định')) ?>
                                    </div>
                                    <div class="text-[9px] text-slate-400 mt-1">Số lần thử: <?= $email['attempts'] ?>/3</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="<?= url('/admin/email-queue/retry?id=' . $email['id']) ?>" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Gửi lại">
                                            <i class="fas fa-redo-alt"></i>
                                        </a>
                                        <a href="<?= url('/admin/email-queue/delete?id=' . $email['id']) ?>" onclick="return confirm('Xóa thư này khỏi hàng đợi?')" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="Xóa">
                                            <i class="fas fa-trash-alt"></i>
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

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>
