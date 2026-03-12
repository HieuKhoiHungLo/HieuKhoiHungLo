<?php
$title = "Thiết lập Điểm chuẩn";
ob_start();
?>

<div class="h-full flex flex-col p-6 bg-slate-50" x-data="benchmarkApp()">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Thiết lập Điểm chuẩn</h1>
            <p class="text-sm text-slate-500 mt-1">
                Nhập điểm cho đợt: <span class="font-semibold text-indigo-600"><?= htmlspecialchars($activeSession['ten_dot'] ?? 'N/A') ?></span>
            </p>
        </div>
        
        <div class="flex gap-3">
            <a href="<?= url('/admin/admission/results') ?>" class="bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg font-medium border border-slate-200 shadow-sm transition-colors flex items-center gap-2">
                <i class="fas fa-arrow-left text-slate-400"></i>
                <span>Xem Kết quả</span>
            </a>
        </div>
    </div>

    <!-- Notifications -->
    <?php if (isset($_GET['status'])): ?>
        <div class="mb-6 <?= $_GET['status'] == 'success' ? 'bg-emerald-50 border-emerald-500 text-emerald-800' : 'bg-red-50 border-red-500 text-red-800' ?> border-l-4 p-4 rounded-r-lg shadow-sm flex items-center gap-3 animate-fade-in-down">
            <i class="fas <?= $_GET['status'] == 'success' ? 'fa-check-circle text-emerald-500' : 'fa-exclamation-circle text-red-500' ?> text-xl"></i>
            <p class="font-medium"><?= $_GET['status'] == 'success' ? 'Đã lưu điểm chuẩn thành công.' : 'Lỗi khi lưu điểm chuẩn.' ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 flex-1 min-h-0">
        <!-- Main Form Section -->
        <div class="xl:col-span-2 flex flex-col min-h-0">
            <form action="<?= url('/admin/admission/benchmarks') ?>" method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col flex-1 overflow-hidden">
                <?= csrf_field() ?>
                
                <div class="px-6 py-4 border-b border-slate-100 bg-white sticky top-0 z-10 flex justify-between items-center">
                    <h2 class="font-bold text-slate-700 flex items-center gap-2">
                        <i class="fas fa-pencil-alt text-indigo-500"></i>
                        Nhập điểm theo ngành
                    </h2>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-bold shadow-md transition-all flex items-center gap-2 transform hover:scale-105 active:scale-95">
                        <i class="fas fa-save"></i>
                        Lưu Điểm chuẩn
                    </button>
                </div>

                <div class="flex-1 overflow-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 sticky top-0 z-10">
                            <tr class="text-slate-600 uppercase tracking-wider text-xs font-bold">
                                <th class="py-3 px-6 border-b border-slate-100">Ngành</th>
                                <th class="py-3 px-6 border-b border-slate-100 text-center">Chỉ tiêu</th>
                                <th class="py-3 px-6 border-b border-slate-100 w-40">Điểm chuẩn</th>
                                <th class="py-3 px-6 border-b border-slate-100 w-40">Tiêu chí phụ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($majors as $major): ?>
                                <?php 
                                    $code = $major['ma_nganh'];
                                    $val = $benchmarks[$code] ?? [];
                                ?>
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    <td class="py-4 px-6">
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($major['ten_nganh']) ?></div>
                                        <div class="text-xs font-mono text-slate-400 mt-0.5"><?= htmlspecialchars($code) ?></div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200">
                                            <?= htmlspecialchars($major['chi_tieu'] ?? 0) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="relative">
                                            <input type="number" step="0.01" 
                                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white transition-all font-bold text-indigo-600" 
                                                   name="benchmarks[<?= $code ?>][score]" 
                                                   value="<?= $val['diem_chuan'] ?? '' ?>" 
                                                   placeholder="0.00">
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <input type="number" step="0.01" 
                                               class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400 focus:bg-white transition-all text-slate-600" 
                                               name="benchmarks[<?= $code ?>][sub_score]" 
                                               value="<?= $val['tieuchi_phu'] ?? '' ?>" 
                                               placeholder="0.00">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>

        <!-- Sidebar Actions Section -->
        <div class="space-y-6">
            <!-- Finalize Section -->
            <div class="bg-white rounded-xl shadow-sm border border-amber-200 overflow-hidden">
                <div class="bg-amber-50 px-6 py-4 border-b border-amber-100">
                    <h3 class="font-bold text-amber-800 flex items-center gap-2">
                        <i class="fas fa-gavel"></i>
                        Công bố Kết quả
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-slate-600 leading-relaxed mb-6">
                        Sau khi đã hài lòng với mức điểm chuẩn, hãy chọn nút dưới đây để hệ thống tự động quét danh sách và xác định trạng thái <span class="font-bold text-emerald-600">Trúng tuyển/Trượt</span> cho từng hồ sơ.
                    </p>
                    
                    <form action="<?= url('/admin/admission/finalize') ?>" method="POST" 
                          @submit="confirmFinalize($event)">
                        <?= csrf_field() ?>
                        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-amber-200 transition-all flex items-center justify-center gap-2 group">
                            <i class="fas fa-check-circle transition-transform group-hover:scale-110"></i>
                            Xác định Trúng tuyển
                        </button>
                    </form>
                    
                    <div class="mt-4 p-3 bg-red-50 border border-red-100 rounded-lg">
                        <p class="text-[10px] text-red-600 uppercase font-bold tracking-wider mb-1">Cảnh báo quan trọng</p>
                        <p class="text-xs text-red-500">Hành động này sẽ cập nhật dữ liệu vào hồ sơ gốc của thí sinh. Bạn nên thực hiện Lọc ảo ở trang "Xét tuyển Lọc ảo" trước để kiểm tra số lượng.</p>
                    </div>
                </div>
            </div>

            <!-- Stats/Info Card -->
            <div class="bg-indigo-900 rounded-xl shadow-xl p-6 text-white relative overflow-hidden">
                <i class="fas fa-university absolute -right-4 -bottom-4 text-7xl text-white/10 rotate-12"></i>
                <h3 class="font-bold text-lg mb-2">Hướng dẫn nhanh</h3>
                <ul class="text-sm text-indigo-100 space-y-3 relative z-10">
                    <li class="flex gap-2">
                        <i class="fas fa-info-circle mt-1 text-indigo-300"></i>
                        <span>Điểm chuẩn là mức điểm thấp nhất để trúng tuyển vào ngành.</span>
                    </li>
                    <li class="flex gap-2">
                        <i class="fas fa-info-circle mt-1 text-indigo-300"></i>
                        <span>Tiêu chí phụ dùng để xét trường hợp thí sinh bằng điểm ở cuối danh sách.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function benchmarkApp() {
    return {
        confirmFinalize(e) {
            if (!confirm('Bạn có chắc chắn muốn CHỐT danh sách trúng tuyển theo điểm chuẩn này không?\nHành động này sẽ cập nhật trạng thái Đậu/Trượt chính thức cho thí sinh.')) {
                e.preventDefault();
            }
        }
    }
}
</script>

<style>
/* Smooth fade in animation */
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
