<?php $title = 'Cấu hình Điểm Ưu tiên - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-4xl mx-auto p-8">
    <header class="mb-8">
        <a href="<?= url('/admin/dashboard') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition block mb-2">&larr; Quay lại Dashboard</a>
        <h2 class="text-3xl font-black text-gray-900 uppercase">Cấu hình Điểm Ưu tiên</h2>
        <p class="text-gray-500 mt-2">Thiết lập mức điểm cộng ưu tiên và công thức quy đổi.</p>
    </header>

    <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
        <?php if (!empty($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
            <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-xl font-bold border border-green-100 flex items-center">
                <i class="fas fa-check-circle mr-2 text-xl"></i> Đã lưu cấu hình!
            </div>
        <?php endif; ?>

        <form action="<?= url('/admin/settings/scoring/save') ?>" method="POST" class="space-y-8">
            
            <!-- Area Priority -->
            <div>
                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">1. Điểm ưu tiên theo Khu vực</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Khu vực 1 (KV1)</label>
                        <input type="number" step="0.01" name="score_priority_kv1" value="<?= $settings['score_priority_kv1'] ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-center font-bold text-[#0066FF]">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nông thôn (KV2-NT)</label>
                        <input type="number" step="0.01" name="score_priority_kv2_nt" value="<?= $settings['score_priority_kv2_nt'] ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-center font-bold text-[#0066FF]">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Khu vực 2 (KV2)</label>
                        <input type="number" step="0.01" name="score_priority_kv2" value="<?= $settings['score_priority_kv2'] ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-center font-bold text-[#0066FF]">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Khu vực 3 (KV3)</label>
                        <input type="number" step="0.01" name="score_priority_kv3" value="<?= $settings['score_priority_kv3'] ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-center font-bold text-[#0066FF]">
                    </div>
                </div>
            </div>

            <!-- Object Priority -->
            <div>
                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">2. Điểm ưu tiên theo Đối tượng</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nhóm ƯT 1 (01-04)</label>
                        <input type="number" step="0.01" name="score_priority_ut1" value="<?= $settings['score_priority_ut1'] ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg font-bold text-[#0066FF]">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nhóm ƯT 2 (05-07)</label>
                        <input type="number" step="0.01" name="score_priority_ut2" value="<?= $settings['score_priority_ut2'] ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg font-bold text-[#0066FF]">
                    </div>
                </div>
            </div>

            <!-- Formula -->
            <div>
                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">3. Công thức tính điểm ưu tiên (Quy đổi)</h3>
                <div class="bg-yellow-50 p-4 rounded-xl border border-yellow-100 mb-4">
                    <p class="font-mono text-sm text-yellow-800">
                        ĐƯT quy đổi = ((30 - Tổng điểm) / <span class="font-bold">Divisor</span>) × Tổng điểm ưu tiên quy định
                    </p>
                    <p class="text-xs text-yellow-600 mt-1">* Chỉ áp dụng khi Tổng điểm (3 môn) >= <span class="font-bold">Threshold</span></p>
                </div>
                
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Ngưỡng điểm áp dụng (Threshold)</label>
                        <input type="number" step="0.1" name="score_threshold_dampening" value="<?= $settings['score_threshold_dampening'] ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg font-bold">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Hệ số chia (Divisor)</label>
                        <input type="number" step="0.1" name="score_dampening_divisor" value="<?= $settings['score_dampening_divisor'] ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg font-bold">
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t">
                <button type="submit" class="w-full py-3 bg-[#0066FF] text-white font-black uppercase tracking-widest rounded-xl shadow-lg hover:shadow-xl hover:bg-blue-700 transition">
                    <i class="fas fa-calculator mr-2"></i> Lưu Cấu Hình
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
