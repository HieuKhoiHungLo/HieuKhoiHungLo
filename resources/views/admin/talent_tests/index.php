<?php ob_start(); ?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Quản lý Thi năng khiếu</h1>
            <p class="text-slate-500 text-sm">Tổ chức các đợt thi năng khiếu cho thí sinh đăng ký vào các ngành đặc thù.</p>
        </div>
        <a href="<?= url('/admin/talent-tests/create') ?>" class="px-4 py-2 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center">
            <i class="fas fa-plus mr-2"></i> Tạo đợt thi mới
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center shadow-sm animate-fade-in">
            <i class="fas fa-check-circle mr-3 text-lg"></i>
            <span class="font-medium">Thao tác thành công!</span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($sessions)): ?>
            <div class="col-span-full py-20 text-center bg-white rounded-3xl border border-dashed border-slate-300">
                <div class="text-slate-300 mb-4 text-5xl">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <p class="text-slate-400 italic">Chưa có đợt thi nào được tạo.</p>
                <a href="<?= url('/admin/talent-tests/create') ?>" class="mt-4 inline-block text-blue-600 font-bold hover:underline">Tạo ngay đợt thi đầu tiên</a>
            </div>
        <?php else: ?>
            <?php foreach ($sessions as $s): ?>
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-xl hover:border-blue-200 transition-all group">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <span class="px-3 py-1 bg-blue-50 text-blue-600 text-xs font-bold rounded-lg uppercase tracking-wider">
                                Năm <?= $s['year'] ?>
                            </span>
                            <div class="text-slate-300 group-hover:text-blue-400 transition">
                                <i class="fas fa-music text-xl"></i>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-blue-600 transition tracking-tight">
                            <?= htmlspecialchars($s['session_name']) ?>
                        </h3>
                        <div class="space-y-2 mb-6">
                            <div class="flex items-center text-sm text-slate-500">
                                <i class="far fa-calendar-alt w-5"></i>
                                <span><?= date('d/m/Y', strtotime($s['start_date'])) ?> - <?= date('d/m/Y', strtotime($s['end_date'])) ?></span>
                            </div>
                            <div class="flex items-center text-sm text-slate-500">
                                <i class="fas fa-info-circle w-5"></i>
                                <span class="truncate"><?= htmlspecialchars($s['description'] ?: 'Không có mô tả') ?></span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?= url('/admin/talent-tests/edit?id=' . $s['id']) ?>" class="flex-1 px-4 py-2 bg-slate-100 text-slate-600 text-center font-bold rounded-xl hover:bg-slate-200 transition">
                                Chi tiết & Đồng bộ
                            </a>
                            <a href="<?= url('/admin/talent-tests/scores?session_id=' . $s['id']) ?>" class="flex-1 px-4 py-2 bg-blue-600 text-white text-center font-bold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                                Quản lý điểm
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
