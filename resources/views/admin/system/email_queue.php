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

    <!-- Tabs Header -->
    <div class="flex items-center gap-2 mb-6 bg-slate-100/50 p-1 rounded-2xl w-fit shadow-inner">
        <a href="<?= url('/admin/email-queue?tab=pending') ?>" 
           class="px-6 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 <?= $currentTab === 'pending' ? 'bg-white text-[#0066FF] shadow-md' : 'text-slate-500 hover:text-slate-700' ?>">
            <i class="fas fa-clock <?= $currentTab === 'pending' ? 'text-amber-500' : 'text-slate-400' ?>"></i>
            Đang chờ <span class="ml-1 px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[10px]"><?= number_format($stats['pending']) ?></span>
        </a>
        <a href="<?= url('/admin/email-queue?tab=failed') ?>" 
           class="px-6 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 <?= $currentTab === 'failed' ? 'bg-white text-rose-600 shadow-md' : 'text-slate-500 hover:text-slate-700' ?>">
            <i class="fas fa-exclamation-triangle <?= $currentTab === 'failed' ? 'text-rose-500' : 'text-slate-400' ?>"></i>
            Bị lỗi <span class="ml-1 px-1.5 py-0.5 rounded-md bg-rose-50 text-rose-600 text-[10px]"><?= number_format($stats['failed']) ?></span>
        </a>
        <a href="<?= url('/admin/email-queue?tab=sent') ?>" 
           class="px-6 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 <?= $currentTab === 'sent' ? 'bg-white text-emerald-600 shadow-md' : 'text-slate-500 hover:text-slate-700' ?>">
            <i class="fas fa-check-circle <?= $currentTab === 'sent' ? 'text-emerald-500' : 'text-slate-400' ?>"></i>
            Đã gửi <span class="ml-1 px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-600 text-[10px]"><?= number_format($stats['sent']) ?></span>
        </a>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="<?= url('/admin/email-queue') ?>" class="flex-1 min-w-[300px] relative group">
            <input type="hidden" name="tab" value="<?= $currentTab ?>">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="fas fa-search text-slate-400 group-focus-within:text-[#0066FF] transition-colors"></i>
            </div>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="Tìm kiếm theo email hoặc tiêu đề thư..." 
                   class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-100 rounded-xl text-sm focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 outline-none transition-all shadow-inner">
        </form>
        
        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">
            Hiển thị <span class="text-slate-800"><?= count($items) ?></span> / <?= number_format($pagination['total_items']) ?> bản ghi
        </div>
    </div>

    <!-- Main Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-10">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50/50 text-[10px] uppercase font-black text-slate-400 tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Thời gian</th>
                        <th class="px-6 py-4">Người nhận</th>
                        <th class="px-6 py-4">Tiêu đề</th>
                        <?php if ($currentTab === 'failed'): ?>
                            <th class="px-6 py-4">Lỗi / Lần thử</th>
                        <?php endif; ?>
                        <th class="px-6 py-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-300">
                                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-inbox text-3xl opacity-20"></i>
                                    </div>
                                    <p class="text-sm font-bold uppercase tracking-widest">Không có dữ liệu phù hợp</p>
                                    <p class="text-[10px] mt-1 font-medium italic">Thử thay đổi điều kiện tìm kiếm hoặc tab</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-slate-50/80 transition-all group">
                                <td class="px-6 py-4 text-xs text-slate-500 font-medium whitespace-nowrap">
                                    <?php 
                                        $time = ($currentTab === 'sent') ? $item['sent_at'] : $item['created_at'];
                                        echo date('d/m/Y H:i', strtotime($time));
                                    ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 text-sm group-hover:text-[#0066FF] transition-colors"><?= htmlspecialchars($item['recipient']) ?></div>
                                    <div class="text-[9px] text-slate-400 uppercase font-black mt-0.5 tracking-tighter">
                                        <?= htmlspecialchars($item['category'] ?? 'Hệ thống') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-slate-600 truncate max-w-xs font-medium group-hover:text-slate-900 transition-colors" title="<?= htmlspecialchars($item['subject']) ?>">
                                        <?= htmlspecialchars($item['subject']) ?>
                                    </div>
                                </td>
                                <?php if ($currentTab === 'failed'): ?>
                                    <td class="px-6 py-4">
                                        <div class="text-[10px] text-rose-600 font-bold leading-tight max-w-xs break-words">
                                            <?= htmlspecialchars($item['last_error'] ?: ($item['error'] ?: 'Lỗi không xác định')) ?>
                                        </div>
                                        <div class="text-[9px] text-slate-400 mt-1 font-medium">Lần thử: <span class="text-slate-600"><?= $item['attempts'] ?>/3</span></div>
                                    </td>
                                <?php endif; ?>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all translate-x-2 group-hover:translate-x-0">
                                        <?php if ($currentTab === 'failed'): ?>
                                            <a href="<?= url('/admin/email-queue/retry?id=' . $item['id']) ?>" 
                                               class="w-8 h-8 flex items-center justify-center bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Gửi lại">
                                                <i class="fas fa-redo-alt text-[10px]"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= url('/admin/email-queue/delete?id=' . $item['id']) ?>" 
                                           onclick="return confirm('Xác nhận xóa bản ghi này?')"
                                           class="w-8 h-8 flex items-center justify-center bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="Xóa">
                                            <i class="fas fa-trash-alt text-[10px]"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="px-6 py-5 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    Trang <span class="text-slate-700"><?= $pagination['current_page'] ?></span> / <?= $pagination['total_pages'] ?>
                </div>
                <div class="flex items-center gap-1.5">
                    <?php 
                    $start = max(1, $pagination['current_page'] - 2);
                    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                    
                    if ($pagination['current_page'] > 1): ?>
                        <a href="<?= url('/admin/email-queue?' . http_build_query(['tab' => $currentTab, 'search' => $search, 'page' => $pagination['current_page'] - 1])) ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all shadow-sm">
                            <i class="fas fa-chevron-left text-[10px]"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="<?= url('/admin/email-queue?' . http_build_query(['tab' => $currentTab, 'search' => $search, 'page' => $i])) ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-xl font-black text-xs transition-all <?= $i === $pagination['current_page'] ? 'bg-[#0066FF] text-white shadow-lg shadow-blue-200' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shadow-sm' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                        <a href="<?= url('/admin/email-queue?' . http_build_query(['tab' => $currentTab, 'search' => $search, 'page' => $pagination['current_page'] + 1])) ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all shadow-sm">
                            <i class="fas fa-chevron-right text-[10px]"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>
