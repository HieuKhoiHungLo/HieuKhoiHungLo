<?php
$title = "Thống kê Nhập học";
ob_start();
?>

<div class="p-6 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-chart-pie text-purple-600"></i> Thống kê Nhập học
                </h1>
                <p class="text-sm text-slate-500 mt-1">Theo dõi tiến độ nhập học và nộp hồ sơ theo thời gian thực.</p>
            </div>
            
            <div class="flex items-center gap-3 bg-white p-2 rounded-xl shadow-sm border border-slate-200">
                <i class="fas fa-calendar-alt text-slate-400 ml-2"></i>
                <select id="session-selector" class="border-none text-sm font-medium focus:ring-0 text-slate-700 bg-transparent py-1 pr-8" onchange="window.location.href='?session_id='+this.value">
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $s['id'] == $currentSessionId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['ten_dot']) ?> (<?= $s['nam_tuyen_sinh'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button onclick="window.location.reload()" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors ml-2" title="Làm mới">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4">
                <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-2xl">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 uppercase">Tổng trúng tuyển</p>
                    <p class="text-3xl font-bold text-slate-800"><?= number_format($totalTrungTuyen) ?></p>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4 relative overflow-hidden">
                <div class="w-14 h-14 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-2xl z-10">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="z-10">
                    <p class="text-sm font-medium text-slate-500 uppercase">Đã nhập học</p>
                    <div class="flex items-end gap-2">
                        <p class="text-3xl font-bold text-slate-800"><?= number_format($totalNhapHoc) ?></p>
                        <?php $rate = $totalTrungTuyen > 0 ? round(($totalNhapHoc / $totalTrungTuyen) * 100, 1) : 0; ?>
                        <p class="text-sm font-semibold text-green-600 mb-1">(<?= $rate ?>%)</p>
                    </div>
                </div>
                <!-- Progress bar background -->
                <div class="absolute bottom-0 left-0 h-1.5 bg-green-500" style="width: <?= $rate ?>%"></div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4">
                <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center text-2xl">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 uppercase">Chưa nhập học</p>
                    <p class="text-3xl font-bold text-slate-800"><?= number_format($totalTrungTuyen - $totalNhapHoc) ?></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Stats by Major -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-semibold text-slate-700">Tiến độ theo Ngành học</h3>
                </div>
                <div class="p-0 overflow-x-auto max-h-[500px] overflow-y-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-white shadow-sm">
                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
                                <th class="px-4 py-3 font-semibold">Mã ngành</th>
                                <th class="px-4 py-3 font-semibold">Tên ngành</th>
                                <th class="px-4 py-3 font-semibold text-center">Trúng tuyển</th>
                                <th class="px-4 py-3 font-semibold text-center text-green-600">Đã nhập học</th>
                                <th class="px-4 py-3 font-semibold text-center">Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($statsByMajor as $stat): 
                                $tt = intval($stat['trung_tuyen']);
                                $nh = intval($stat['nhap_hoc']);
                                $pt = $tt > 0 ? round(($nh / $tt) * 100, 1) : 0;
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 text-sm font-medium text-slate-600"><?= $stat['ma_nganh'] ?></td>
                                <td class="px-4 py-3 text-sm text-slate-800"><?= $stat['ten_nganh'] ?></td>
                                <td class="px-4 py-3 text-sm text-center font-medium"><?= $tt ?></td>
                                <td class="px-4 py-3 text-sm text-center font-bold text-green-600"><?= $nh ?></td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-slate-200 rounded-full h-1.5">
                                            <div class="bg-purple-600 h-1.5 rounded-full" style="width: <?= $pt ?>%"></div>
                                        </div>
                                        <span class="text-xs font-semibold w-8 text-right"><?= $pt ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Recent Activity -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-semibold text-slate-700">Mới nhập học gần đây</h3>
                </div>
                <div class="p-5 flex-1 overflow-y-auto max-h-[500px]">
                    <div class="space-y-4">
                        <?php if (empty($recent)): ?>
                            <div class="text-center text-slate-500 py-8">
                                <i class="fas fa-box-open text-3xl mb-2 text-slate-300"></i>
                                <p class="text-sm">Chưa có giao dịch nhập học nào.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent as $item): ?>
                            <div class="relative pl-6 border-l-2 border-purple-200 last:border-transparent pb-4 last:pb-0">
                                <div class="absolute w-3 h-3 bg-purple-600 rounded-full -left-[7px] top-1 border-2 border-white"></div>
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 hover:border-purple-200 transition-colors">
                                    <div class="flex justify-between items-start mb-1">
                                        <h4 class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($item['ho_ten']) ?></h4>
                                        <span class="text-xs text-slate-500"><?= date('H:i d/m', strtotime($item['ngay_nhap_hoc'])) ?></span>
                                    </div>
                                    <div class="text-xs text-slate-500 space-y-1">
                                        <p><i class="fas fa-id-card w-4 text-slate-400"></i> <?= $item['so_cccd'] ?></p>
                                        <p><i class="fas fa-graduation-cap w-4 text-slate-400"></i> <?= $item['ten_nganh'] ?></p>
                                        <p><i class="fas fa-money-bill-wave w-4 text-slate-400"></i> <?= $item['da_nop_tien'] == 1 ? '<span class="text-green-600 font-semibold">Đã nộp tiền</span>' : '<span class="text-amber-600">Chưa nộp tiền</span>' ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>
