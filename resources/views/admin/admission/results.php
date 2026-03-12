<?php
$title = "Kết quả Xét tuyển";
ob_start();
?>

<div class="h-full flex flex-col p-6 bg-slate-50" x-data="resultsApp()">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Kết quả Xét tuyển Dự kiến</h1>
            <p class="text-sm text-slate-500 mt-1">Danh sách thí sinh đủ điều kiện trúng tuyển dựa trên điểm chuẩn đã thiết lập.</p>
        </div>
        
        <div class="flex flex-wrap gap-3">
            <a href="<?= url('/admin/import') ?>" class="bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg font-medium border border-slate-200 shadow-sm transition-colors flex items-center gap-2">
                <i class="fas fa-arrow-left text-slate-400"></i>
                <span>Quay lại Import</span>
            </a>
            
            <form action="<?= url('/admin/admission/process') ?>" method="POST" @submit="confirmRecalculate($event)">
                <?= csrf_field() ?>
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-sync-alt"></i>
                    <span>Tính lại Điểm</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Message -->
    <?php if (isset($_GET['message'])): ?>
        <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-lg shadow-sm flex items-center gap-3 animate-fade-in-down">
            <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
            <p class="font-medium"><?= htmlspecialchars($_GET['message']) ?></p>
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
        <form action="" method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[300px]">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 px-1">Lọc theo Ngành</label>
                <select name="major" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium text-slate-700" onchange="this.form.submit()">
                    <option value="">-- Tất cả các ngành --</option>
                    <?php foreach ($majors as $m): ?>
                        <option value="<?= $m['ma_nganh'] ?>" <?= ($filterMajor == $m['ma_nganh']) ? 'selected' : '' ?>>
                             <?= htmlspecialchars($m['ma_nganh'] . ' - ' . $m['ten_nganh']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end self-end">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-bold shadow-md transition-all">
                    Lọc dữ liệu
                </button>
            </div>
        </form>
    </div>

    <?php if (empty($groupedResults)): ?>
        <div class="flex-1 flex flex-col items-center justify-center bg-white rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-folder-open text-slate-300 text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-700 mb-2">Chưa có dữ liệu xét tuyển</h3>
            <p class="text-slate-500 max-w-sm mb-6">Hệ thống chưa tìm thấy kết quả trúng tuyển nào. Vui lòng thiết lập điểm chuẩn và bấm "Tính lại điểm".</p>
        </div>
    <?php else: ?>
        <div class="space-y-8">
            <?php foreach ($groupedResults as $ma_nganh => $rows): ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h2 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                                <span class="bg-indigo-600 text-white text-[10px] px-2 py-0.5 rounded uppercase"><?= htmlspecialchars($ma_nganh) ?></span>
                                <?= htmlspecialchars($rows[0]['ten_nganh'] ?? 'Ngành ' . $ma_nganh) ?>
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Số lượng: <span class="font-bold text-indigo-600"><?= count($rows) ?></span> thí sinh trúng tuyển</p>
                        </div>
                        
                        <div class="flex gap-2">
                            <a href="<?= url('/admin/reports/export-admitted?ma_nganh=' . $ma_nganh) ?>" 
                               class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-emerald-100 transition-colors flex items-center gap-2">
                                <i class="fas fa-file-excel"></i> Xuất Excel (Mail Merge)
                            </a>
                            
                            <form action="<?= url('/admin/admission/notify') ?>" method="POST" @submit="confirmNotify($event, '<?= htmlspecialchars($ma_nganh) ?>')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="ma_nganh" value="<?= htmlspecialchars($ma_nganh) ?>">
                                <button type="submit" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-indigo-100 transition-colors flex items-center gap-2">
                                    <i class="fas fa-paper-plane"></i> Gửi Email thông báo
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/30 text-slate-400 uppercase tracking-wider text-[10px] font-bold border-b border-slate-100">
                                    <th class="py-3 px-6 w-12 text-center">STT</th>
                                    <th class="py-3 px-6 w-32">CCCD/CMND</th>
                                    <th class="py-3 px-6 w-56">Họ và Tên</th>
                                    <th class="py-3 px-6 text-center">Tổ hợp / Phương thức</th>
                                    <th class="py-3 px-6">Chi tiết Điểm</th>
                                    <th class="py-3 px-6 text-center w-28">Tổng điểm</th>
                                    <th class="py-3 px-6 text-center w-32">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($rows as $index => $row): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-6 text-center text-slate-400 font-medium"><?= $index + 1 ?></td>
                                        <td class="py-4 px-6 font-mono text-sm text-indigo-600 font-medium"><?= htmlspecialchars($row['so_cccd']) ?></td>
                                        <td class="py-4 px-6 font-bold text-slate-700"><?= htmlspecialchars($row['ho_va_ten']) ?></td>
                                        <td class="py-4 px-6 text-center">
                                            <div class="text-sm font-bold text-slate-600"><?= htmlspecialchars($row['to_hop_xet_tuyen_id'] ?? 'N/A') ?></div>
                                            <div class="text-[10px] text-slate-400 uppercase"><?= htmlspecialchars($row['phuong_thuc_xet_tuyen']) ?></div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex flex-wrap gap-1.5">
                                                <?php 
                                                $details = json_decode($row['chi_tiet_diem'], true);
                                                if ($details) {
                                                    foreach ($details as $k => $v) {
                                                        if (in_array($k, ['details', 'total_raw', 'all_combinations', 'combinations'])) continue;
                                                        $val = is_array($v) ? ($v['final'] ?? $v['raw'] ?? '-') : $v;
                                                        if ($val === '-') continue;
                                                        ?>
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 text-[10px] font-medium border border-slate-200">
                                                            <span class="font-bold mr-1"><?= strtoupper($k) ?>:</span> <?= $val ?>
                                                        </span>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <span class="text-lg font-black text-indigo-700"><?= number_format($row['diem_xet_tuyen'], 2) ?></span>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 shadow-sm leading-none">
                                                <i class="fas fa-check-circle mr-1"></i> TRÚNG TUYỂN
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function resultsApp() {
    return {
        confirmRecalculate(e) {
            if (!confirm('Bạn có chắc chắn muốn TÍNH LẠI ĐIỂM cho toàn bộ hệ thống?\nHành động này sẽ xóa các kết quả trúng tuyển hiện tại và chạy lại logic dựa trên Điểm chuẩn mới nhất.')) {
                e.preventDefault();
            }
        },
        confirmNotify(e, major) {
            if (!confirm(`Gửi email thông báo trúng tuyển cho tất cả thí sinh ngành [${major}]?\nQuá trình này có thể mất vài phút tuỳ thuộc vào số lượng hồ sơ.`)) {
                e.preventDefault();
            }
        }
    }
}
</script>

<style>
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-down {
    animation: fadeInDown 0.4s ease-out;
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>
