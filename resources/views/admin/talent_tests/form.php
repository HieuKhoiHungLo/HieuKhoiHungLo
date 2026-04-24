<?php ob_start(); ?>

<div class="p-6 max-w-4xl mx-auto">
    <div class="mb-8 flex items-center gap-4">
        <a href="<?= url('/admin/talent-tests') ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Tạo đợt thi năng khiếu mới</h1>
            <p class="text-slate-500 text-sm">Thiết lập thông tin thời gian và các ngành có tổ chức thi.</p>
        </div>
    </div>

    <form action="<?= url('/admin/talent-tests/store') ?>" method="POST" class="space-y-6">
        <?= csrf_field() ?>
        
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
            <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mr-3 text-sm italic font-serif">1</span>
                Thông tin chung
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wider">Tên đợt thi</label>
                    <input type="text" name="session_name" required placeholder="Ví dụ: Thi năng khiếu Đợt 1 - 2024"
                           class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-bold text-lg">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wider">Năm tuyển sinh</label>
                    <input type="number" name="year" required value="<?= date('Y') ?>"
                           class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-bold">
                </div>
                
                <div></div> <!-- Spacer -->

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wider">Ngày bắt đầu</label>
                    <input type="date" name="start_date" required
                           class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-medium">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wider">Ngày kết thúc</label>
                    <input type="date" name="end_date" required
                           class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-medium">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wider">Ghi chú / Mô tả</label>
                    <textarea name="description" rows="3" placeholder="Thông tin bổ sung về đợt thi..."
                              class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-medium"></textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
            <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mr-3 text-sm italic font-serif">2</span>
                Cấu hình ngành & môn thi
            </h2>
            
            <p class="text-sm text-slate-500 mb-6 italic">Chọn các ngành sẽ tổ chức thi năng khiếu trong đợt này. Hệ thống sẽ tự động cấu hình các môn thi tương ứng.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php 
                $majors = [
                    '7140201' => 'Giáo dục mầm non',
                    '7140206' => 'Giáo dục thể chất',
                    '7140221' => 'Sư phạm Âm nhạc',
                    '7140222' => 'Sư phạm Mỹ thuật'
                ];
                foreach ($majors as $code => $name): 
                ?>
                <label class="relative flex items-center p-4 rounded-2xl border-2 border-slate-100 cursor-pointer hover:bg-blue-50/30 hover:border-blue-100 transition group">
                    <input type="checkbox" name="majors[]" value="<?= $code ?>" class="w-6 h-6 rounded-lg border-slate-300 text-blue-600 focus:ring-blue-500 transition">
                    <div class="ml-4">
                        <div class="font-bold text-slate-700 group-hover:text-blue-600 transition"><?= $name ?></div>
                        <div class="text-xs text-slate-400 font-mono"><?= $code ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="<?= url('/admin/talent-tests') ?>" class="px-8 py-4 text-slate-500 font-bold hover:text-slate-700 transition">Hủy bỏ</a>
            <button type="submit" class="px-10 py-4 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 transition shadow-xl shadow-blue-200 transform hover:-translate-y-1 active:translate-y-0">
                Lưu đợt thi & Cấu hình môn
            </button>
        </div>
    </form>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
