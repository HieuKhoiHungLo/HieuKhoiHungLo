<?php $title = 'Chứng chỉ Quốc tế - HVU'; ?>
<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-hvu-red to-red-800 p-8 text-white text-center relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-3xl font-black uppercase tracking-widest mb-2">Chứng chỉ Quốc tế</h2>
                <p class="text-white/80 text-sm font-bold italic">Bước 3/4: Khai báo chứng chỉ Tiếng Anh hoặc Tin học quốc tế</p>
            </div>
            <div class="absolute top-0 right-0 -mt-4 -mr-4 opacity-10">
                <i class="fas fa-certificate text-[120px]"></i>
            </div>
        </div>

        <!-- Wizard Navigation -->
        <div class="bg-gray-100 px-6 py-4 border-b flex justify-between items-center text-xs md:text-sm font-semibold">
            <div class="text-green-600 flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg></span>
                <span>Hồ sơ</span>
            </div>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
            <div class="text-green-600 flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg></span>
                <span>Học bạ</span>
            </div>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
            <div class="text-hvu-red flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mb-1 border-2 border-hvu-red">3</span>
                <span>Chứng chỉ ngoại ngữ quốc tế</span>
            </div>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
            <div class="text-gray-400 flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1">4</span>
                <span>Nguyện vọng</span>
            </div>
        </div>

        <div class="p-8">
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-hvu-red text-red-700 p-4 rounded mb-6">
                    <p class="font-bold">Lỗi:</p>
                    <p><?= (string) ($error ?? "") ?></p>
                </div>
            <?php endif; ?>

            <form action="<?= url('/academic/certification') ?>" method="POST" enctype="multipart/form-data" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                    <p class="text-blue-800 text-sm font-medium leading-relaxed">
                        <i class="fas fa-info-circle mr-2"></i> Thí sinh có chứng chỉ quốc tế sẽ được ưu tiên cộng điểm hoặc xét tuyển thẳng theo quy định của Nhà trường.
                        <strong>Nếu không có, vui lòng chọn "Không có chứng chỉ quốc tế" để tiếp tục.</strong>
                    </p>
                </div>

                <!-- Option Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <label class="group relative flex items-center p-6 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer transition-all hover:border-gray-200 has-[:checked]:border-gray-400 has-[:checked]:bg-gray-50">
                        <input type="radio" name="has_cert" value="0" <?= !($user['co_chung_chi_qt'] ?? false) ? 'checked' : '' ?> onchange="toggleCertFields(false)" class="w-6 h-6 text-gray-600 border-gray-300 focus:ring-gray-500">
                        <div class="ml-4">
                            <span class="block text-lg font-black text-gray-900">Không có chứng chỉ</span>
                            <span class="block text-sm text-gray-500">Tôi không có chứng chỉ quốc tế nào.</span>
                        </div>
                    </label>

                    <label class="group relative flex items-center p-6 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer transition-all hover:border-hvu-red/20 has-[:checked]:border-hvu-red has-[:checked]:bg-red-50/30">
                        <input type="radio" name="has_cert" value="1" <?= ($user['co_chung_chi_qt'] ?? false) ? 'checked' : '' ?> onchange="toggleCertFields(true)" class="w-6 h-6 text-hvu-red border-gray-300 focus:ring-hvu-red">
                        <div class="ml-4">
                            <span class="block text-lg font-black text-gray-900 group-hover:text-hvu-red transition-colors">Có chứng chỉ quốc tế</span>
                            <span class="block text-sm text-gray-500">Tôi có IELTS, HSK, IC3, MOS...</span>
                        </div>
                    </label>
                </div>

                <!-- Conditional Fields -->
                <div id="cert_fields" class="<?= ($user['co_chung_chi_qt'] ?? false) ? '' : 'hidden' ?> space-y-6 pt-6 border-t border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-sm font-black text-gray-700 uppercase tracking-widest mb-2">Loại chứng chỉ</label>
                            <select name="cert_type" class="w-full h-12 bg-gray-50 border border-gray-200 rounded-xl px-4 focus:ring-2 focus:ring-hvu-red outline-none">
                                <option value="">-- Chọn chứng chỉ --</option>
                                <optgroup label="Tiếng Anh">
                                    <option value="IELTS" <?= ($user['loai_chung_chi'] ?? '') == 'IELTS' ? 'selected' : '' ?>>IELTS</option>
                                    <option value="TOEFL iBT" <?= ($user['loai_chung_chi'] ?? '') == 'TOEFL iBT' ? 'selected' : '' ?>>TOEFL iBT</option>
                                    <option value="TOEIC 4-Skills" <?= ($user['loai_chung_chi'] ?? '') == 'TOEIC 4-Skills' ? 'selected' : '' ?>>TOEIC (4 kỹ năng)</option>
                                    <option value="TOEFL Paper" <?= ($user['loai_chung_chi'] ?? '') == 'TOEFL Paper' ? 'selected' : '' ?>>TOEFL Paper</option>
                                    <option value="B2 Cambridge" <?= ($user['loai_chung_chi'] ?? '') == 'B2 Cambridge' ? 'selected' : '' ?>>B2 Cambridge</option>
                                </optgroup>
                                <optgroup label="Tiếng Trung">
                                    <option value="HSK 3" <?= ($user['loai_chung_chi'] ?? '') == 'HSK 3' ? 'selected' : '' ?>>HSK 3</option>
                                    <option value="HSK 4" <?= ($user['loai_chung_chi'] ?? '') == 'HSK 4' ? 'selected' : '' ?>>HSK 4</option>
                                    <option value="HSK 5" <?= ($user['loai_chung_chi'] ?? '') == 'HSK 5' ? 'selected' : '' ?>>HSK 5</option>
                                </optgroup>
                                <optgroup label="Tin học">
                                    <option value="IC3" <?= ($user['loai_chung_chi'] ?? '') == 'IC3' ? 'selected' : '' ?>>IC3</option>
                                    <option value="MOS" <?= ($user['loai_chung_chi'] ?? '') == 'MOS' ? 'selected' : '' ?>>MOS</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-black text-gray-700 uppercase tracking-widest mb-2">Điểm / Kết quả</label>
                            <input type="text" name="cert_score" value="<?= htmlspecialchars($user['diem_chung_chi'] ?? '') ?>" placeholder="VD: 6.5, 450, Đạt..." class="w-full h-12 bg-gray-50 border border-gray-200 rounded-xl px-4 focus:ring-2 focus:ring-hvu-red outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-black text-gray-700 uppercase tracking-widest mb-2">Minh chứng bài thi (Ảnh)</label>
                        <div class="flex items-center space-x-4">
                            <label class="flex-grow cursor-pointer group">
                                <div class="h-24 border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center bg-gray-50 group-hover:bg-red-50 group-hover:border-hvu-red transition-all duration-300">
                                    <i class="fas fa-image text-2xl text-gray-400 group-hover:text-hvu-red mb-1"></i>
                                    <span class="text-xs font-bold text-gray-500 group-hover:text-hvu-red" id="file_status">Bấm để tải ảnh minh chứng</span>
                                    <input type="file" name="cert_file" accept="image/*" class="hidden" onchange="updateFileStatus(this)">
                                </div>
                            </label>
                            <?php if (!empty($user['file_minh_chung_cc'])): ?>
                                <div class="flex-shrink-0 w-24 h-24 rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                                    <img src="<?= url($user['file_minh_chung_cc']) ?>" class="w-full h-full object-cover">
                                </div>
                            <?php endif; ?>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-2 italic">* Chỉ cho phép file ảnh (JPG, PNG). Dung lượng tối đa 5MB.</p>
                    </div>
                </div>

                <div class="pt-8 border-t border-gray-100 flex flex-col items-center">
                    <button type="submit" class="w-full md:w-auto px-16 py-4 bg-gradient-to-r from-hvu-red to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black text-lg rounded-2xl shadow-xl hover:shadow-red-500/30 transform hover:-translate-y-1 transition-all">
                        Hoàn thành & Tiếp tục <i class="fas fa-chevron-right ml-2"></i>
                    </button>
                    <a href="<?= url('/academic') ?>" class="mt-4 text-sm font-bold text-gray-500 hover:text-hvu-red transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i> Quay lại
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleCertFields(show) {
        const fields = document.getElementById('cert_fields');
        if (show) {
            fields.classList.remove('hidden');
        } else {
            fields.classList.add('hidden');
        }
    }

    function updateFileStatus(input) {
        const status = document.getElementById('file_status');
        if (input.files && input.files[0]) {
            status.textContent = 'Đã chọn: ' + input.files[0].name;
            status.classList.remove('text-gray-500');
            status.classList.add('text-hvu-red');
        }
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>