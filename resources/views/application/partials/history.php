<?php if (count($applications) > 1 || (!$currentApp && !empty($applications))): ?>
<div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 overflow-hidden">
    <div class="px-8 py-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
        <h3 class="text-lg font-black text-gray-800 uppercase tracking-tight flex items-center">
            <i class="fas fa-history mr-3 text-hvu-red"></i> Lịch sử đăng ký khác
        </h3>
    </div>
    <div class="p-0 overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-100/50 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                <tr>
                    <th class="px-8 py-4">Năm học</th>
                    <th class="px-8 py-4">Đợt tuyển sinh</th>
                    <th class="px-8 py-4">Trạng thái</th>
                    <th class="px-8 py-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($applications as $app): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-8 py-4 font-bold text-gray-700"><?= $app->nam_tuyen_sinh ?></td>
                        <td class="px-8 py-4 text-sm text-gray-600 font-medium"><?= htmlspecialchars($app->ten_dot) ?></td>
                        <td class="px-8 py-4">
                            <span class="px-3 py-1 bg-white border border-gray-100 rounded-full text-[10px] font-black text-gray-500 shadow-sm">
                                <?= $app->trang_thai ?>
                            </span>
                        </td>
                        <td class="px-8 py-4 text-right">
                            <a href="<?= url('/profile/step5?id=' . $app->id) ?>" class="text-hvu-red font-black text-[10px] uppercase hover:underline">Chi tiết</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
