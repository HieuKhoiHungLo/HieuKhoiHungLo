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

    <div class="mb-4">
        <?php if (!empty($groupedResults) && $filterMajor): ?>
            <div class="flex flex-wrap gap-2 justify-end">
                <a href="<?= url('/admin/reports/export-admitted?ma_nganh=' . $filterMajor) ?>" 
                   class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-emerald-100 shadow-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-file-excel"></i> Xuất Excel DS Đỗ (ngành <?= htmlspecialchars($filterMajor) ?>)
                </a>
                
                <form action="<?= url('/admin/admission/notify') ?>" method="POST" @submit="confirmNotify($event, '<?= htmlspecialchars($filterMajor) ?>')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="ma_nganh" value="<?= htmlspecialchars($filterMajor) ?>">
                    <button type="submit" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-indigo-100 shadow-sm transition-colors flex items-center gap-2">
                        <i class="fas fa-paper-plane"></i> Gửi Email thông báo đỗ
                    </button>
                </form>
            </div>
        <?php elseif(empty($filterMajor) && !empty($groupedResults)): ?>
             <p class="text-xs text-right text-slate-500 italic"><i class="fas fa-info-circle"></i> Vui lòng chọn lọc 1 ngành cụ thể để Xuất Excel hoặc Gửi Email tự động.</p>
        <?php endif; ?>
    </div>

    <?php require __DIR__ . '/components/virtual_grid.php'; ?>
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
