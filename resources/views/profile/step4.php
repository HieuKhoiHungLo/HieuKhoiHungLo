<?php 
$title = 'Điểm thi THPT';
include __DIR__ . '/../layouts/header.php'; 
?>

<div class="max-w-6xl mx-auto pb-20 px-4 sm:px-6">
    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-red-900/5 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-hvu-red to-red-700 px-6 py-4 border-b border-red-800">
            <h2 class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-file-invoice-doll mr-2"></i>
                Điểm thi THPT năm 2026
            </h2>
        </div>

        <!-- Wizard Navigation -->
        <div class="bg-gray-100 px-6 py-4 border-b flex justify-between items-center text-xs md:text-sm font-semibold overflow-x-auto">
           <a href="<?= url('/profile/step1') ?>" class="text-green-600 flex flex-col items-center min-w-fit px-2">
               <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><i class="fas fa-check"></i></span>
               <span class="hidden sm:block">Hồ sơ</span>
           </a>
           <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
           <a href="<?= url('/profile/step2') ?>" class="text-green-600 flex flex-col items-center min-w-fit px-2">
                 <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><i class="fas fa-check"></i></span>
               <span class="hidden sm:block">Học bạ</span>
           </a>
           <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
           <a href="<?= url('/profile/step3') ?>" class="text-green-600 flex flex-col items-center min-w-fit px-2">
                 <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><i class="fas fa-check"></i></span>
                <span class="hidden sm:block">Chứng chỉ quốc tế</span>
           </a>
           <div class="text-gray-300 mx-2 flex-1 border-t-2 border-hvu-red"></div>
           <div class="text-hvu-red flex flex-col items-center min-w-fit px-2">
               <span class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mb-1 border-2 border-hvu-red font-bold">4</span>
               <span class="hidden sm:block">Điểm thi</span>
           </div>
           <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
           <a href="<?= url('/profile/step5') ?>" class="text-gray-400 flex flex-col items-center min-w-fit px-2 hover:text-hvu-red transition-colors">
                <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1">5</span>
               <span class="hidden sm:block">Nguyện vọng</span>
           </a>
        </div>

        <div class="p-8">
            <?php if (!empty($error)): ?>
                <div class="bg-red-50 border-l-4 border-hvu-red text-red-700 p-4 mb-6 rounded shadow-sm">
                    <p class="font-bold">Lỗi:</p>
                    <p><?= $error ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/profile/step4') ?>" id="scoresForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
                
                <?php if (!empty($isLocked)): ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6" role="alert">
                        <div class="flex">
                            <i class="fas fa-lock text-yellow-400 flex-shrink-0 mt-1"></i>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700 font-bold">Hồ sơ đã được duyệt. Bạn không thể chỉnh sửa thông tin.</p>
                                <?php if (!empty($editRequestPending)): ?>
                                    <p class="text-xs text-yellow-600 mt-1"><i class="fas fa-clock mr-1"></i> Đã gửi yêu cầu chỉnh sửa, vui lòng chờ Quản trị viên xử lý.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <fieldset <?= (!empty($isLocked)) ? 'disabled' : '' ?> class="contents group/locked">
                
                <div class="max-w-4xl mx-auto space-y-10">
                    <!-- Choice Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <label class="relative group cursor-pointer">
                            <input type="radio" name="has_scores" value="0" class="peer hidden" <?= (!isset($scores['da_co_diem']) || !$scores['da_co_diem']) ? 'checked' : '' ?>>
                            <div class="p-6 rounded-3xl border-2 border-gray-100 bg-gray-50 transition-all peer-checked:border-hvu-red peer-checked:bg-white peer-checked:shadow-xl group-hover:bg-white">
                                <div class="w-12 h-12 rounded-2xl bg-gray-200 flex items-center justify-center text-gray-500 mb-4 peer-checked:bg-red-50 peer-checked:text-hvu-red">
                                    <i class="fas fa-clock text-xl"></i>
                                </div>
                                <h3 class="font-black text-gray-800 mb-2">Chưa có kết quả thi</h3>
                                <p class="text-xs text-gray-500">Tôi chưa dự thi hoặc chưa được cấp điểm thi THPT năm 2026.</p>
                            </div>
                        </label>

                        <label class="relative group cursor-pointer">
                            <input type="radio" name="has_scores" value="1" class="peer hidden" <?= (isset($scores['da_co_diem']) && $scores['da_co_diem']) ? 'checked' : '' ?>>
                            <div class="p-6 rounded-3xl border-2 border-gray-100 bg-gray-50 transition-all peer-checked:border-hvu-red peer-checked:bg-white peer-checked:shadow-xl group-hover:bg-white">
                                <div class="w-12 h-12 rounded-2xl bg-gray-200 flex items-center justify-center text-gray-500 mb-4 peer-checked:bg-red-50 peer-checked:text-hvu-red">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                                <h3 class="font-black text-gray-800 mb-2">Đã có kết quả thi 2026</h3>
                                <p class="text-xs text-gray-500">Tôi đã biết điểm thi và muốn cập nhật ngay để xét tuyển.</p>
                            </div>
                        </label>
                    </div>

                    <!-- Scores Input Section (Hidden by primary choice) -->
                    <div id="scores_input_area" class="<?= (isset($scores['da_co_diem']) && $scores['da_co_diem']) ? '' : 'hidden' ?> space-y-8 animate-fadeIn">
                        <div class="bg-blue-50 border-1 border-blue-100 p-6 rounded-3xl text-blue-800 flex items-start">
                            <i class="fas fa-info-circle mt-1 mr-4 text-xl"></i>
                            <div>
                                <h4 class="font-bold uppercase tracking-tight text-sm mb-1">Hướng dẫn nhập điểm:</h4>
                                <p class="text-xs leading-relaxed">Nhập điểm các môn thi bạn đã tham dự. Chừa trống hoặc nhập 0 cho các môn không thi.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                            <?php 
                            $subjects = [
                                'toan' => 'Toán', 'van' => 'Văn', 'ly' => 'Lí', 'hoa' => 'Hóa', 'sinh' => 'Sinh',
                                'su' => 'Sử', 'dia' => 'Địa', 'gdcd' => 'GDCD', 'tieng_anh' => 'Tiếng Anh', 'tieng_trung' => 'Tiếng Trung',
                                'ktpl' => 'Kinh tế PL', 'tin_hoc' => 'Tin học', 'cnnn' => 'CN Nông nghiệp'
                            ];
                            foreach ($subjects as $key => $name): ?>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block px-2"><?= $name ?></label>
                                    <input type="number" step="0.01" min="0" max="10" name="<?= $key ?>" 
                                        value="<?= isset($scores[$key]) ? $scores[$key] : '' ?>" 
                                        class="hvu-input font-black text-lg text-center h-14" placeholder="0.0">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Certificate Upload -->
                        <div class="border-t border-gray-100 pt-8 mt-8">
                            <label class="label text-center text-hvu-red mb-4">
                                <i class="fas fa-file-certificate"></i> Giấy chứng nhận kết quả th (Nếu có)
                            </label>
                            <div class="group relative w-full h-64 border-2 border-dashed border-red-200 rounded-3xl flex flex-col justify-center items-center bg-red-50/30 overflow-hidden transition-all hover:border-hvu-red/50">
                                <img id="preview_cert" loading="lazy" src="<?= !empty($scores['file_chung_nhan']) ? url($scores['file_chung_nhan']) : '' ?>" class="<?= !empty($scores['file_chung_nhan']) ? 'block' : 'hidden' ?> w-full h-full object-contain">
                                <div class="<?= !empty($scores['file_chung_nhan']) ? 'hidden' : 'flex' ?> flex-col items-center text-red-300">
                                    <i class="fas fa-cloud-upload-alt text-5xl mb-3"></i>
                                    <span class="text-xs font-black uppercase tracking-widest">Tải lên giấy chứng nhận</span>
                                    <span class="text-[10px] mt-2 font-semibold">Ảnh chụp rõ nét (JPG, PNG)</span>
                                </div>
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                    <span class="text-white text-xs font-bold bg-hvu-red px-6 py-2 rounded-full shadow-lg">Thay đổi file</span>
                                </div>
                                <input type="file" name="file_chung_nhan" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(this, 'preview_cert')">
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0 border-t border-gray-100 pt-10">
                        <a href="<?= url('/profile/step3') ?>" class="text-gray-600 hover:text-gray-900 font-bold flex items-center transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i> Quay lại
                        </a>
                        <button type="submit" class="w-full md:w-auto px-12 py-4 bg-hvu-red border-b-4 border-red-800 text-white font-black text-lg rounded-2xl shadow-xl hover:bg-red-700 hover:border-red-900 active:border-b-0 active:translate-y-1 transition-all">
                            Lưu thông tin và tiếp tục <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="has_scores"]');
    const inputArea = document.getElementById('scores_input_area');

    radios.forEach(r => {
        r.addEventListener('change', function() {
            if (this.value === '1') {
                inputArea.classList.remove('hidden');
            } else {
                inputArea.classList.add('hidden');
            }
        });
    });
});

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            if(preview.nextElementSibling) preview.nextElementSibling.classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn 0.4s ease-out forwards;
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
