<?php 
$title = 'Bảng điều khiển';
include __DIR__ . '/../layouts/header.php'; 

// Helper to check step status
$isDone = function($step) use ($stepStatus) {
    return isset($stepStatus[$step]) && $stepStatus[$step];
};

$totalSteps = $enableTHPT ? 5 : 4;

$nextStep = 1;
for($i=1; $i<=$totalSteps; $i++) {
    if (!$isDone($i)) {
        $nextStep = $i;
        break;
    }
}
?>

<div class="max-w-6xl mx-auto space-y-10 pb-20">
    
    <!-- Header Greeting -->
    <?php include __DIR__ . '/partials/header_profile.php'; ?>

    <!-- Roadmap Section -->
    <?php include __DIR__ . '/partials/roadmap.php'; ?>

    <!-- Applications History Section -->
    <?php if (!empty($applications)): ?>
    <div class="mt-10">
        <h2 class="text-xl font-black text-gray-800 mb-4 uppercase flex items-center">
            <i class="fas fa-history text-hvu-red mr-2"></i> Lịch sử hồ sơ xét tuyển
        </h2>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-700 uppercase leading-normal text-xs font-bold border-b border-gray-100">
                        <tr>
                            <th class="py-4 px-6 text-center w-16">STT</th>
                            <th class="py-4 px-6">Đợt xét tuyển</th>
                            <th class="py-4 px-6 text-center">Trạng thái</th>
                            <th class="py-4 px-6 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm">
                        <?php foreach ($applications as $index => $app): ?>
                            <tr class="border-b border-gray-50 hover:bg-red-50/30 transition-colors">
                                <td class="py-4 px-6 text-center font-bold text-gray-400"><?= $index + 1 ?></td>
                                <td class="py-4 px-6">
                                    <div class="font-bold text-gray-800"><?= htmlspecialchars($app->ten_dot ?? '') ?></div>
                                    <div class="text-xs text-gray-500 mt-1">Năm tuyển sinh: <span class="font-bold text-gray-600"><?= htmlspecialchars($app->nam_tuyen_sinh ?? '') ?></span></div>
                                    <div class="text-xs text-gray-400 mt-0.5">Ngày nộp: <?= date('d/m/Y H:i', strtotime($app->created_at)) ?></div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php 
                                        $statusClass = 'bg-blue-50 text-blue-600 border-blue-200';
                                        if ($app->trang_thai == 'Đã duyệt' || $app->trang_thai == 'approved' || $app->trang_thai == 'DaDuyet') {
                                            $statusClass = 'bg-green-50 text-green-600 border-green-200';
                                        } elseif ($app->trang_thai == 'Yêu cầu chỉnh sửa' || $app->trang_thai == 'require_edit' || $app->trang_thai == 'Yêu cầu sửa') {
                                            $statusClass = 'bg-red-50 text-red-600 border-red-200';
                                        }
                                        $displayStatus = $app->trang_thai ?? 'Chờ duyệt';
                                        if ($displayStatus == 'approved' || $displayStatus == 'DaDuyet') $displayStatus = 'Đã duyệt';
                                        if ($displayStatus == 'pending' || $displayStatus == 'ChoDuyet') $displayStatus = 'Chờ duyệt';
                                        if ($displayStatus == 'require_edit') $displayStatus = 'Yêu cầu chỉnh sửa';
                                    ?>
                                    <span class="inline-block px-3 py-1 rounded-full text-[11px] font-bold border <?= $statusClass ?>">
                                        <?= htmlspecialchars($displayStatus) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <a href="<?= url('/profile/step5?id=' . $app->id) ?>" class="inline-flex items-center px-4 py-2 bg-white text-hvu-red border border-red-100 font-bold text-xs rounded-lg hover:bg-hvu-red hover:text-white transition-all shadow-sm group">
                                        <i class="fas fa-eye mr-1.5 group-hover:scale-110 transition-transform"></i> Chi tiết
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
