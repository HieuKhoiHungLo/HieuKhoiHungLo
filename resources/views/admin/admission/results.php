<?php
// results.php - Danh sách kết quả trúng tuyển chính thức (Grid View v2)
$title = "Danh sách Trúng tuyển";
ob_start();

$totalCandidates = $stats['total_candidates'] ?? 0;
$totalAdmitted = $stats['total_admitted'] ?? 0;
$nv1 = $stats['nv1_admit'] ?? 0;
$nv2 = $stats['nv2_admit'] ?? 0;
$nv3 = $stats['nv3_admit'] ?? 0;
$others = $totalAdmitted - ($nv1 + $nv2 + $nv3);
$admitRate = $totalCandidates > 0 ? round(($totalAdmitted / $totalCandidates) * 100, 1) : 0;
?>

<!-- Tailwind & Assets -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>

<div class="h-full flex flex-col p-4 lg:p-8 bg-slate-50/50" x-data="resultsApp()">
    
    <!-- Top Progress/Action Bar -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 bg-gradient-to-br from-indigo-600 to-blue-700 rounded-3xl flex items-center justify-center shadow-2xl shadow-indigo-200">
                <i class="fas fa-file-invoice text-white text-3xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight leading-tight uppercase">DANH SÁCH TRÚNG TUYỂN</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-700 uppercase leading-none border border-emerald-200">GRID VIEW V2</span>
                    <p class="text-xs text-slate-400 font-medium tracking-wide">Báo cáo tổng hợp kết quả trúng tuyển chính thức.</p>
                </div>
            </div>
        </div>
        
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex bg-white p-1 rounded-2xl shadow-sm border border-slate-200">
                <a href="<?= url('/admin/reports/export-all-admitted?session_id=' . ($activeSession['id'] ?? '')) ?>" 
                   class="px-4 py-2.5 text-xs font-bold text-emerald-600 hover:bg-emerald-50 rounded-xl transition-all flex items-center gap-2 group">
                    <i class="fas fa-file-excel"></i> Xuất Excel Trúng Tuyển
                </a>
                <div class="w-px h-6 bg-slate-200 my-auto"></div>
                <form action="<?= url('/admin/admission/notify') ?>" method="POST" @submit="confirmNotifyAll($event)">
                    <?= csrf_field() ?>
                    <input type="hidden" name="major" value="<?= htmlspecialchars($filterMajor) ?>">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
                    <input type="hidden" name="show_all" value="<?= $showAll ? '1' : '0' ?>">
                    <button type="submit" class="px-4 py-2.5 text-xs font-bold text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all flex items-center gap-2 group">
                        <i class="fas fa-paper-plane"></i> Gửi Thông Báo (Danh sách hiện tại)
                    </button>
                </form>
            </div>
            
            <a href="<?= url('/admin/admission/virtual-filter') ?>" 
               class="bg-slate-800 hover:bg-black text-white px-6 py-3 rounded-2xl font-black shadow-xl transition-all flex items-center gap-3 group">
                <i class="fas fa-filter"></i>
                <span class="tracking-wide uppercase">Lọc Ảo</span>
            </a>
        </div>
    </div>

    <!-- Premium Stats Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1: Total -->
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
            <div class="relative">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-3">Thí sinh xét tuyển</p>
                <div class="flex items-end gap-2">
                    <h3 class="text-3xl font-black text-slate-800 leading-none"><?= number_format($totalCandidates) ?></h3>
                    <span class="text-xs font-bold text-blue-600 mb-1">Hồ sơ</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Admitted -->
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
            <div class="relative">
                <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest leading-none mb-3">Thí sinh trúng tuyển</p>
                <div class="flex items-end gap-2 text-emerald-700">
                    <h3 class="text-3xl font-black leading-none"><?= number_format($totalAdmitted) ?></h3>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black leading-none mb-0.5">Tỉ lệ đạt</span>
                        <span class="text-xs font-bold"><?= $admitRate ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: NV1 -->
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
            <div class="relative">
                <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest leading-none mb-3">Trúng tuyển NV1</p>
                <div class="flex items-end gap-2 text-indigo-700">
                    <h3 class="text-3xl font-black leading-none"><?= number_format($nv1) ?></h3>
                    <span class="text-xs font-bold mb-1">Ưu tiên 1</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Other NV -->
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
            <div class="relative">
                <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest leading-none mb-3">NV2, NV3 & Khác</p>
                <div class="flex items-center gap-4">
                    <div>
                        <span class="text-[10px] font-black text-slate-400 block uppercase">NV2/3</span>
                        <span class="text-lg font-black text-amber-600"><?= $nv2 + $nv3 ?></span>
                    </div>
                    <div class="w-px h-6 bg-slate-100"></div>
                    <div>
                        <span class="text-[10px] font-black text-slate-400 block uppercase">Còn lại</span>
                        <span class="text-lg font-black text-slate-600"><?= $others ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Grid Toolbar -->
    <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-200 p-2 mb-8 flex flex-col lg:flex-row items-center gap-4">
        <form action="" method="GET" class="flex-grow flex flex-col lg:flex-row items-center gap-3 w-full">
            <!-- Major Filter -->
            <div class="relative flex-grow group w-full lg:w-auto">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <i class="fas fa-university text-xs"></i>
                </div>
                <select name="major" class="w-full bg-slate-50 border-none rounded-2xl pl-12 pr-4 py-4 text-xs font-black text-slate-700 focus:ring-2 focus:ring-indigo-500 appearance-none" onchange="this.form.submit()">
                    <option value="">TẤT CẢ NGÀNH</option>
                    <?php foreach ($majors as $m): ?>
                        <option value="<?= $m['ma_nganh'] ?>" <?= ($filterMajor == $m['ma_nganh']) ? 'selected' : '' ?>>
                             <?= strtoupper(htmlspecialchars($m['ma_nganh'] . ' - ' . $m['ten_nganh'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-[10px] pointer-events-none"></i>
            </div>
            
            <!-- Status Filter -->
            <div class="relative w-full lg:w-64 group">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <i class="fas fa-tag text-xs"></i>
                </div>
                <select name="status" class="w-full bg-slate-50 border-none rounded-2xl pl-12 pr-4 py-4 text-xs font-black text-slate-700 focus:ring-2 focus:ring-indigo-500 appearance-none" onchange="this.form.submit()">
                    <option value="">LỌC TRẠNG THÁI</option>
                    <option value="Trung tuyen" <?= ($filterStatus == 'Trung tuyen') ? 'selected' : '' ?>>TRÚNG TUYỂN</option>
                    <option value="Truot" <?= ($filterStatus == 'Truot') ? 'selected' : '' ?>>KHÔNG ĐẠT</option>
                </select>
                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-[10px] pointer-events-none"></i>
            </div>

            <!-- Show All Checkbox -->
            <label class="flex items-center gap-3 px-4 py-2 border-r border-slate-100 cursor-pointer select-none">
                <input type="checkbox" name="show_all" value="1" <?= $showAll ? 'checked' : '' ?> onchange="this.form.submit()" class="w-5 h-5 rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-[11px] font-black text-slate-500 uppercase tracking-tight">Hiện cả thí sinh không đạt</span>
            </label>

            <button type="submit" class="bg-black text-white px-8 py-4 rounded-2xl font-black text-xs tracking-widest shadow-lg flex items-center justify-center gap-2 group">
                <i class="fas fa-sync-alt group-hover:rotate-180 transition-transform"></i> TẢI LẠI
            </button>
        </form>
    </div>

    <!-- The Unified Grid (Candidate Table) -->
    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/40 border border-slate-200 overflow-hidden flex flex-col flex-1">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-100/80 text-slate-500 uppercase tracking-[0.2em] text-[10px] font-black border-b border-slate-200 sticky top-0 z-10">
                        <th class="py-5 px-6 text-center w-16">STT</th>
                        <th class="py-5 px-6">MÃ NGÀNH</th>
                        <th class="py-5 px-6 text-center w-16">NV</th>
                        <th class="py-5 px-6 text-center w-16">NV BỘ</th>
                        <th class="py-5 px-6">CCCD / CMND</th>
                        <th class="py-5 px-6">HỌ VÀ TÊN</th>
                        <th class="py-5 px-6">TỔ HỢP / PHƯƠNG THỨC</th>
                        <th class="py-5 px-6">ĐIỂM CHI TIẾT (M1-M2-M3)</th>
                        <th class="py-5 px-6 text-center">ĐIỂM XT</th>
                        <th class="py-5 px-6 text-center">TRẠNG THÁI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-[11px]">
                    <?php if (empty($results)): ?>
                        <tr>
                            <td colspan="9" class="py-32 text-center">
                                <i class="fas fa-inbox text-slate-200 text-5xl block mb-4"></i>
                                <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">Không có dữ liệu phù hợp</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($results as $index => $row): 
                            $details = json_decode($row['chi_tiet_diem'], true);
                            $isPass = ($row['trang_thai'] == 'Trung tuyen' || $row['trang_thai'] == 'Trúng tuyển' || ($row['trang_thai_trung_tuyen'] ?? false));
                        ?>
                            <tr class="hover:bg-indigo-50/30 transition-all group">
                                <td class="py-4 px-6 text-center text-slate-300 font-black"><?= $index + 1 ?></td>
                                <td class="py-4 px-6">
                                    <span class="text-indigo-600 font-black tracking-tight" title="<?= htmlspecialchars($row['ten_nganh']) ?>"><?= $row['ma_nganh'] ?></span>
                                </td>
                                    <span class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 font-black border border-slate-200" title="Thứ tự nguyện vọng nội bộ">
                                        <?= $row['thu_tu_nguyen_vong'] ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php if ($row['thu_tu_nv_bo']): ?>
                                        <span class="w-7 h-7 flex items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 font-black border border-indigo-100" title="Thứ tự nguyện vọng của Bộ GD">
                                            <?= $row['thu_tu_nv_bo'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-300">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-mono text-slate-500 font-bold"><?= htmlspecialchars($row['so_cccd']) ?></span>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-black text-slate-800 uppercase tracking-tight group-hover:text-indigo-600 transition-colors"><?= htmlspecialchars($row['ho_va_ten']) ?></p>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="font-black text-slate-700"><?= htmlspecialchars($row['cs_to_hop'] ?: '-') ?></span>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase">
                                            <?php
                                                $majorArr = [
                                                    'co_diem_nangkhieu_thpt' => $row['co_diem_nangkhieu_thpt'] ?? false,
                                                    'co_xet_chung_chi' => $row['co_xet_chung_chi'] ?? false,
                                                    'co_diem_nangkhieu_hochba' => $row['co_diem_nangkhieu_hochba'] ?? false
                                                ];
                                                $methodCode = (string)($row['cs_phuong_thuc'] ?: $row['phuong_thuc_xet_tuyen'] ?: '');
                                                echo \App\Helpers\AdmissionMethodHelper::resolvePhuongThuc($methodCode, $majorArr);
                                            ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-1.5 font-bold">
                                        <span class="w-9 text-center py-0.5 bg-slate-50 rounded text-slate-500"><?= number_format($details['diem_mon_1'] ?? 0, 3) ?></span>
                                        <span class="w-9 text-center py-0.5 bg-slate-50 rounded text-slate-500"><?= number_format($details['diem_mon_2'] ?? 0, 3) ?></span>
                                        <span class="w-9 text-center py-0.5 bg-slate-50 rounded text-slate-500"><?= number_format($details['diem_mon_3'] ?? 0, 3) ?></span>
                                        <?php if (($details['priority_raw'] ?? 0) > 0): ?>
                                            <span class="px-1.5 py-0.5 bg-amber-50 text-amber-600 rounded border border-amber-100 text-[9px]">+<?= number_format($details['priority_converted'] ?? 0, 3) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="text-lg font-black text-indigo-700"><?= $row['cs_diem_xet_tuyen'] !== null ? number_format($row['cs_diem_xet_tuyen'], 3) : '-' ?></span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php if ($isPass): ?>
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[9px] font-black bg-emerald-50 text-emerald-600 border border-emerald-100 uppercase">
                                            <i class="fas fa-check-circle mr-1.5"></i> ĐỖ
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[9px] font-black bg-rose-50 text-rose-500 border border-rose-200 uppercase opacity-60">
                                            <i class="fas fa-times-circle mr-1.5"></i> KHÔNG ĐẠT
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function resultsApp() {
    return {
        confirmNotifyAll(e) {
            const count = <?= count($results) ?>;
            if (count === 0) {
                alert("Danh sách hiện tại trống, không có gì để gửi.");
                e.preventDefault();
                return;
            }
            if (!confirm(`Hệ thống sẽ gửi EMAIL THÔNG BÁO cho tất cả ${count} thí sinh trong danh sách đang hiển thị. Bạn có chắc chắn?`)) {
                e.preventDefault();
            }
        }
    }
}
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.font-black { font-weight: 900 !important; }
</style>

<?php
$content = ob_get_clean();
$layoutPath = realpath(__DIR__ . '/../../layouts/admin.php');
if ($layoutPath && file_exists($layoutPath)) {
    require_once $layoutPath;
} else {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head><meta charset="UTF-8"><title>Results Grid Fallback</title><script src="https://cdn.tailwindcss.com"></script></head>
    <body class="bg-slate-50"><?= $content ?></body>
    </html>
    <?php
}
?>
