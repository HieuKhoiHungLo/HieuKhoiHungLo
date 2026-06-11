<div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-l-8 border-hvu-red pl-6 py-2">
    <div>
        <h1 class="text-4xl font-black text-gray-900 tracking-tight">CHÀO MỪNG, <span class="text-hvu-red"><?= htmlspecialchars($user['ho_va_ten'] ?? 'Thí sinh') ?></span>!</h1>
        <?php if ($activeSession): ?>
            <p class="text-gray-500 mt-2 font-medium">Hệ thống đang mở nhận hồ sơ xét tuyển <span class="text-hvu-red font-bold text-lg"><?= htmlspecialchars($activeSession['ten_dot']) ?></span> năm <?= $activeSession['nam_tuyen_sinh'] ?>.</p>
        <?php else: ?>
        <?php endif; ?>
        
        <?php if (!$currentApp && $activeSession): ?>
            <div class="mt-6">
                 <a href="<?= url('/application/register?id=' . $activeSession['id']) ?>" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white font-black text-sm rounded-xl shadow-lg shadow-green-200 hover:shadow-green-300 hover:-translate-y-1 transition-all uppercase tracking-widest">
                    <i class="fas fa-plus-circle mr-2 text-lg"></i> Đăng ký hồ sơ đợt này
                 </a>
            </div>
        <?php elseif ($isDone($totalSteps)): ?>
            <div class="mt-6">
                 <a href="<?= url('/application/results') ?>" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-hvu-red to-red-600 text-white font-black text-sm rounded-xl shadow-lg shadow-red-200 hover:shadow-red-300 hover:-translate-y-1 transition-all uppercase tracking-widest">
                    <i class="fas fa-chart-bar mr-2 text-lg"></i> Tra cứu kết quả
                 </a>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($currentApp): ?>
        <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border-2 border-dashed border-red-100 flex items-center justify-between">
            <div class="flex items-center">
                <div class="mr-4">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Trạng thái hồ sơ</span>
                    <span class="text-lg font-black text-gray-800">
                        <?php 
                        $rawStatus = $currentApp->trang_thai ?? '';
                        $displayStatus = $rawStatus;
                        
                        // Mapping for legacy or English values
                        $statusMap = [
                            'approved' => 'Đã duyệt',
                            'DaDuyet' => 'Đã duyệt',
                            'pending' => 'Chờ duyệt',
                            'ChoDuyet' => 'Chờ duyệt',
                            'rejected' => 'Từ chối',
                            'require_edit' => 'Yêu cầu chỉnh sửa'
                        ];
                        
                        if (isset($statusMap[$rawStatus])) {
                            $displayStatus = $statusMap[$rawStatus];
                        }

                        $statusClass = 'text-blue-600';
                        if ($displayStatus == 'Đã duyệt') $statusClass = 'text-green-600';
                        if ($displayStatus == 'Yêu cầu chỉnh sửa' || $displayStatus == 'Yêu cầu sửa') $statusClass = 'text-red-600';
                        ?>
                        <span class="<?= $statusClass ?>"><?= htmlspecialchars($displayStatus) ?></span>
                    </span>
                </div>
                <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-hvu-red">
                    <i class="fas fa-file-signature text-xl"></i>
                </div>
            </div>

            <?php if (!empty($isLocked)): ?>
                <div class="ml-4 border-l pl-4 border-gray-100">
                    <?php if (!empty($editRequestPending)): ?>
                        <div class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-lg text-xs font-bold flex items-center whitespace-nowrap" title="Đã gửi yêu cầu lúc <?= $currentApp->updated_at ?>">
                            <i class="fas fa-clock mr-2"></i> Chờ duyệt sửa
                        </div>
                    <?php else: ?>
                        <form action="<?= url('/application/requestEdit') ?>" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn gửi yêu cầu chỉnh sửa? Hồ sơ sẽ cần chờ Quản trị viên duyệt lại trạng thái.');">
                            <input type="hidden" name="id" value="<?= $currentApp->id ?>">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <button type="submit" class="px-3 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-bold transition-colors whitespace-nowrap flex items-center">
                                <i class="fas fa-edit mr-1"></i> Đề xuất chỉnh sửa
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
