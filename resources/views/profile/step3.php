<?php $title = 'Chứng chỉ Quốc tế - HVU'; ?>
<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-6xl mx-auto pb-20 px-4 sm:px-6">
    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-red-900/5 border border-gray-100 overflow-hidden">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-hvu-red to-red-700 p-6 text-white text-center">
            <h2 class="text-2xl font-bold uppercase tracking-wide">Chứng chỉ Quốc tế</h2>
            <p class="text-white/80 text-sm mt-1">Bước 3/<?= $totalStepsCount ?>: Khai báo các chứng chỉ quốc tế bạn đang sở hữu</p>
        </div>

        <!-- Wizard Navigation -->
        <div class="bg-gray-100 px-6 py-4 border-b flex justify-between items-center text-xs md:text-sm font-semibold overflow-x-auto">
            <a href="<?= url('/profile/step1') ?>" class="text-green-600 flex flex-col items-center min-w-fit px-2 hover:text-green-700 transition-colors">
                <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg></span>
                <span class="hidden sm:block">Hồ sơ</span>
            </a>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
            <a href="<?= url('/profile/step2') ?>" class="text-green-600 flex flex-col items-center min-w-fit px-2 hover:text-green-700 transition-colors">
                <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg></span>
                <span class="hidden sm:block">Học bạ</span>
            </a>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
            <div class="text-hvu-red flex flex-col items-center min-w-fit px-2">
                <span class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mb-1 border-2 border-hvu-red">3</span>
                <span class="hidden sm:block">Chứng chỉ quốc tế</span>
            </div>

            <?php if ($enableTHPTSetting): ?>
                <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
                <a href="<?= url('/profile/step4') ?>" class="text-gray-400 flex flex-col items-center min-w-fit px-2 hover:text-hvu-red transition-colors">
                    <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1">4</span>
                    <span class="hidden sm:block">Điểm thi</span>
                </a>
            <?php endif; ?>

            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
            <a href="<?= url('/profile/step5') ?>" class="text-gray-400 flex flex-col items-center min-w-fit px-2 hover:text-hvu-red transition-colors">
                <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1"><?= $enableTHPTSetting ? 5 : 4 ?></span>
                <span class="hidden sm:block">Nguyện vọng</span>
            </a>
        </div>

        <div class="p-8">
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-hvu-red text-red-700 p-4 rounded mb-6">
                    <p class="font-bold">Lỗi:</p>
                    <p><?= $error ?></p>
                </div>
            <?php endif; ?>

            <form action="<?= url('/profile/step3') ?>" method="POST" enctype="multipart/form-data">
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

                <fieldset <?= (!empty($isLocked)) ? 'disabled' : '' ?> class="space-y-8 group/locked contents">

                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                        <p class="text-blue-800 text-sm font-medium leading-relaxed">
                            <i class="fas fa-info-circle mr-2"></i> Thí sinh có thể thêm nhiều loại chứng chỉ khác nhau để tăng cơ hội xét tuyển.
                            <strong>Nếu không có, vui lòng chọn "Không có chứng chỉ quốc tế" để tiếp tục.</strong>
                        </p>
                    </div>

                    <!-- Option Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <label class="group relative flex items-center p-6 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer transition-all hover:border-gray-200 has-[:checked]:border-gray-400 has-[:checked]:bg-gray-50">
                            <input type="radio" name="has_cert" value="0" <?= empty($certs) ? 'checked' : '' ?> onchange="toggleCertFields(false)" class="w-6 h-6 text-gray-600 border-gray-300 focus:ring-gray-500">
                            <div class="ml-4">
                                <span class="block text-lg font-black text-gray-900">Không có chứng chỉ</span>
                                <span class="block text-sm text-gray-500">Tôi không có chứng chỉ quốc tế nào.</span>
                            </div>
                        </label>

                        <label class="group relative flex items-center p-6 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer transition-all hover:border-hvu-red/20 has-[:checked]:border-hvu-red has-[:checked]:bg-red-50/30">
                            <input type="radio" name="has_cert" value="1" <?= !empty($certs) ? 'checked' : '' ?> onchange="toggleCertFields(true)" class="w-6 h-6 text-hvu-red border-gray-300 focus:ring-hvu-red">
                            <div class="ml-4">
                                <span class="block text-lg font-black text-gray-900 group-hover:text-hvu-red transition-colors">Có chứng chỉ quốc tế</span>
                                <span class="block text-sm text-gray-500">Tôi có chứng chỉ quốc tế Tiếng Anh, Tiếng Trung...</span>
                            </div>
                        </label>
                    </div>

                    <!-- Dynamic Certifications List -->
                    <div id="cert_section" class="<?= !empty($certs) ? '' : 'hidden' ?> space-y-4 pt-6 border-t border-gray-100">
                        <div id="cert_list" class="space-y-6">
                            <?php if (!empty($certs)): ?>
                                <?php foreach ($certs as $index => $cert): ?>
                                    <div class="cert-item bg-gray-50/50 border border-gray-100 rounded-2xl p-6 relative group/item">
                                        <button type="button" onclick="removeCert(this)" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition-colors z-10">
                                            <i class="fas fa-times-circle text-xl"></i>
                                        </button>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                            <!-- Col 1: Cert Info (2/3) -->
                                            <div class="md:col-span-2 space-y-4">
                                                <div>
                                                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Loại chứng chỉ</label>
                                                    <select name="certs[<?= $index ?>][type]" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 focus:ring-2 focus:ring-hvu-red outline-none">
                                                        <option value="">-- Chọn --</option>
                                                        <optgroup label="Tiếng Anh">
                                                            <option value="IELTS" <?= $cert['loai_chung_chi'] == 'IELTS' ? 'selected' : '' ?>>IELTS</option>
                                                            <option value="TOEFL iBT" <?= $cert['loai_chung_chi'] == 'TOEFL iBT' ? 'selected' : '' ?>>TOEFL iBT</option>
                                                            <option value="TOEIC" <?= $cert['loai_chung_chi'] == 'TOEIC' ? 'selected' : '' ?>>TOEIC (4 kỹ năng)</option>
                                                            <option value="TOEFL Paper" <?= $cert['loai_chung_chi'] == 'TOEFL Paper' ? 'selected' : '' ?>>TOEFL Paper</option>
                                                            <option value="B2 Cambridge" <?= $cert['loai_chung_chi'] == 'B2 Cambridge' ? 'selected' : '' ?>>B2 Cambridge</option>
                                                        </optgroup>
                                                        <optgroup label="Tiếng Trung Quốc">
                                                            <option value="HSK 3" <?= $cert['loai_chung_chi'] == 'HSK 3' ? 'selected' : '' ?>>HSK 3</option>
                                                            <option value="HSK 4" <?= $cert['loai_chung_chi'] == 'HSK 4' ? 'selected' : '' ?>>HSK 4</option>
                                                            <option value="HSK 5" <?= $cert['loai_chung_chi'] == 'HSK 5' ? 'selected' : '' ?>>HSK 5</option>
                                                        </optgroup>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Điểm / Kết quả</label>
                                                    <input type="text" name="certs[<?= $index ?>][score]" value="<?= htmlspecialchars($cert['diem_chung_chi']) ?>" placeholder="VD: 6.5, 450, Đạt..." class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 focus:ring-2 focus:ring-hvu-red outline-none">
                                                </div>
                                            </div>
                                            <!-- Col 2: Evidence Image (1/3) -->
                                            <div class="flex flex-col">
                                                <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Minh chứng (Ảnh)</label>
                                                <input type="hidden" name="certs[<?= $index ?>][existing_file]" value="<?= $cert['file_minh_chung_cc'] ?>">
                                                <?php if (!empty($cert['file_minh_chung_cc'])): ?>
                                                    <?php
                                                    $certUrl = $cert['file_minh_chung_cc'];
                                                    $certIsExt = strpos($certUrl, 'http') === 0;
                                                    $certFullUrl = $certIsExt ? $certUrl : url($certUrl);
                                                    $certThumbUrl = $certIsExt ? google_drive_thumbnail_url($certUrl, 'w300') : $certFullUrl;
                                                    ?>
                                                    <div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow aspect-[4/3] mb-2">
                                                        <a href="<?= $certFullUrl ?>" target="_blank" class="block w-full h-full">
                                                            <img loading="lazy" src="<?= $certThumbUrl ?>" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.15]" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-gray-100 text-gray-400\'><i class=\'fas fa-image text-2xl\'></i></div>'">
                                                        </a>
                                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                                            <span class="text-white text-xs font-bold bg-hvu-red/80 px-4 py-2 rounded-full shadow-lg scale-75 group-hover:scale-100 transition-transform duration-300"><i class="fas fa-search-plus mr-1"></i> Xem lớn</span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div id="no_image_<?= $index ?>" class="aspect-[4/3] rounded-xl border-2 border-dashed border-gray-200 bg-white flex flex-col items-center justify-center text-gray-300 mb-2">
                                                        <i class="fas fa-image text-3xl mb-2"></i>
                                                        <span class="text-xs font-medium">Chưa có ảnh</span>
                                                    </div>
                                                <?php endif; ?>
                                                <div id="preview_<?= $index ?>" class="w-full empty:hidden mb-2"></div>
                                                <label class="group cursor-pointer block">
                                                    <div class="flex items-center justify-center py-2 px-3 rounded-xl border-2 border-dashed border-gray-200 bg-white hover:border-hvu-red/40 hover:bg-red-50/30 transition-all">
                                                        <i class="fas fa-cloud-upload-alt text-gray-400 group-hover:text-hvu-red mr-2 transition-colors text-sm"></i>
                                                        <span class="text-[11px] font-bold text-gray-500 group-hover:text-hvu-red transition-colors"><?= !empty($cert['file_minh_chung_cc']) ? 'Thay đổi ảnh' : 'Tải ảnh lên' ?></span>
                                                    </div>
                                                    <input type="file" name="cert_files[<?= $index ?>]" accept="image/*" class="hidden" onchange="previewCert(this, 'preview_<?= $index ?>', 'no_image_<?= $index ?>')">
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <button type="button" onclick="addCertRow()" class="w-full py-4 border-2 border-dashed border-gray-200 rounded-2xl text-gray-400 font-bold hover:bg-gray-50 hover:border-gray-300 hover:text-gray-600 transition-all">
                            <i class="fas fa-plus-circle mr-2"></i> Thêm chứng chỉ khác
                        </button>
                    </div>

                    <div class="pt-8 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                        <a href="<?= url('/profile/step2') ?>" class="text-gray-600 hover:text-gray-900 font-bold flex items-center transition-colors md:order-1 order-2">
                            <i class="fas fa-arrow-left mr-2"></i> Quay lại
                        </a>
                        <button type="submit" class="w-full md:w-auto px-12 py-4 bg-gradient-to-r from-hvu-red to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black text-lg rounded-2xl shadow-xl hover:shadow-red-500/30 transform hover:-translate-y-1 transition-all md:order-2 order-1">
                            Lưu thông tin và tiếp tục <i class="fas fa-chevron-right ml-2"></i>
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>

<!-- Template for Dynamic Row -->
<template id="cert_template">
    <div class="cert-item bg-gray-50/50 border border-gray-100 rounded-2xl p-6 relative group/item animate-fadeIn">
        <button type="button" onclick="removeCert(this)" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition-colors z-10">
            <i class="fas fa-times-circle text-xl"></i>
        </button>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Col 1: Cert Info (2/3) -->
            <div class="md:col-span-2 space-y-4">
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Loại chứng chỉ</label>
                    <select name="certs[INDEX][type]" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 focus:ring-2 focus:ring-hvu-red outline-none" required>
                        <option value="">-- Chọn --</option>
                        <optgroup label="Tiếng Anh">
                            <option value="IELTS">IELTS</option>
                            <option value="TOEFL iBT">TOEFL iBT</option>
                            <option value="TOEIC">TOEIC (4 kỹ năng)</option>
                            <option value="TOEFL Paper">TOEFL Paper</option>
                            <option value="B2 Cambridge">B2 Cambridge</option>
                        </optgroup>
                        <optgroup label="Tiếng Trung Quốc">
                            <option value="HSK 3">HSK 3</option>
                            <option value="HSK 4">HSK 4</option>
                            <option value="HSK 5">HSK 5</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Điểm / Kết quả</label>
                    <input type="text" name="certs[INDEX][score]" placeholder="VD: 6.5, 450, Đạt..." class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 focus:ring-2 focus:ring-hvu-red outline-none">
                </div>
            </div>
            <!-- Col 2: Evidence Image (1/3) -->
            <div class="flex flex-col">
                <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Minh chứng (Ảnh)</label>
                <div id="no_image_INDEX" class="aspect-[4/3] rounded-xl border-2 border-dashed border-gray-200 bg-white flex flex-col items-center justify-center text-gray-300 mb-2">
                    <i class="fas fa-image text-3xl mb-2"></i>
                    <span class="text-xs font-medium">Chưa có ảnh</span>
                </div>
                <div id="preview_INDEX" class="w-full empty:hidden mb-2"></div>
                <label class="group cursor-pointer block">
                    <div class="flex items-center justify-center py-2 px-3 rounded-xl border-2 border-dashed border-gray-200 bg-white hover:border-hvu-red/40 hover:bg-red-50/30 transition-all">
                        <i class="fas fa-cloud-upload-alt text-gray-400 group-hover:text-hvu-red mr-2 transition-colors text-sm"></i>
                        <span class="text-[11px] font-bold text-gray-500 group-hover:text-hvu-red transition-colors">Tải ảnh lên</span>
                    </div>
                    <input type="file" name="cert_files[INDEX]" accept="image/*" class="hidden" onchange="previewCert(this, 'preview_INDEX', 'no_image_INDEX')" required>
                </label>
            </div>
        </div>
    </div>
</template>

<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out forwards;
    }
</style>

<script>
    let certCount = <?= !empty($certs) ? count($certs) : 0 ?>;

    function toggleCertFields(show) {
        const section = document.getElementById('cert_section');
        if (show) {
            section.classList.remove('hidden');
            if (certCount === 0) addCertRow();
        } else {
            section.classList.add('hidden');
        }
    }

    function addCertRow() {
        const list = document.getElementById('cert_list');
        const template = document.getElementById('cert_template').innerHTML;
        const newRow = template.replace(/INDEX/g, certCount);

        const div = document.createElement('div');
        div.innerHTML = newRow;
        list.appendChild(div.firstElementChild);

        certCount++;
    }

    function removeCert(btn) {
        const item = btn.closest('.cert-item');
        item.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            item.remove();
            if (document.querySelectorAll('.cert-item').length === 0) {
                document.querySelector('input[name="has_cert"][value="0"]').checked = true;
                toggleCertFields(false);
            }
        }, 200);
    }

    function previewCert(input, previewId, placeholderId) {
        const previewContainer = document.getElementById(previewId);
        if (previewContainer) previewContainer.innerHTML = '';

        const placeholder = document.getElementById(placeholderId);
        if (input.files && input.files.length > 0) {
            if (placeholder) placeholder.style.display = 'none';
        } else {
            if (placeholder) placeholder.style.display = 'flex';
        }

        const label = input.closest('label').querySelector('span');

        if (input.files && input.files[0]) {
            label.textContent = 'Đã chọn: ' + input.files[0].name;
            label.classList.remove('text-gray-400');
            label.classList.add('text-hvu-red');

            if (input.files[0].type.startsWith('image/') && previewContainer) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'group relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow aspect-[4/3]';

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-full h-full object-cover transition-transform duration-300 hover:scale-[1.15]';

                    imgContainer.appendChild(img);
                    previewContainer.appendChild(imgContainer);
                }
                reader.readAsDataURL(input.files[0]);
            }
        } else {
            label.textContent = 'Tải ảnh lên';
            label.classList.remove('text-hvu-red');
            label.classList.add('text-gray-500');
        }
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>