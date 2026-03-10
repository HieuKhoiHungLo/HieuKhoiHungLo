<?php $title = 'Upload Học bạ & Bằng Tốt nghiệp'; ?>
<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-xl rounded-xl overflow-hidden">
        <!-- Header Section -->
        <div class="bg-hvu-red p-6 text-white text-center">
            <h2 class="text-2xl font-bold uppercase tracking-wide">Nộp Minh Chứng Học Bạ</h2>
            <p class="text-white/80 text-sm mt-1">Bước 4/5: Tải lên hình ảnh hoặc PDF của học bạ và bằng tốt nghiệp</p>
        </div>

        <!-- Wizard Navigation (Visual) -->
        <div class="bg-gray-100 px-6 py-4 border-b flex justify-between items-center text-xs md:text-sm font-semibold">
            <div class="text-green-600 flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></span>
                <span>Cá nhân</span>
            </div>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
            <div class="text-green-600 flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></span>
                <span>Liên hệ</span>
            </div>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
            <div class="text-green-600 flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></span>
                <span>Học bá (Nhập điểm)</span>
            </div>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-hvu-red"></div>
            <div class="text-hvu-red flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mb-1 border-2 border-hvu-red">4</span>
                <span>Minh chứng</span>
            </div>
             <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
            <div class="text-gray-400 flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1">5</span>
                <span>Nguyện vọng</span>
            </div>
        </div>

        <div class="p-8">
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-hvu-red text-red-700 p-4 rounded mb-6 flex items-start">
                     <svg class="w-6 h-6 mr-3 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                     <div><?= (string) ($error ?? "") ?></div>
                </div>
            <?php endif; ?>

            <form action="<?= url('/academic/documents') ?>" method="POST" enctype="multipart/form-data" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start">
                    <svg class="w-6 h-6 text-blue-500 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="text-sm text-blue-800">
                        <h4 class="font-bold mb-1">Quy định tải lên tài liệu</h4>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Định dạng cho phép: <strong>JPG, PNG, PDF</strong>.</li>
                            <li>Dung lượng tối đa: <strong>5MB/file</strong>.</li>
                            <li><strong>Học bạ:</strong> Chụp rõ nét các trang có điểm lớp 10, 11, 12 và trang thông tin học sinh (có thể ghép thành 1 file PDF hoặc ảnh dài).</li>
                            <li><strong>Bằng tốt nghiệp:</strong> Chụp mặt trước của bằng (hoặc giấy chứng nhận tốt nghiệp tạm thời).</li>
                        </ul>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Học bạ -->
                    <div class="group">
                        <label class="block text-sm font-bold text-gray-700 mb-2 group-hover:text-hvu-red transition-colors">
                            1. File Học bạ THPT <span class="text-red-500">*</span>
                        </label>
                        <div class="relative mt-1 flex justify-center px-6 pt-10 pb-10 border-2 border-gray-300 border-dashed rounded-xl hover:border-hvu-red hover:bg-red-50 transition-all cursor-pointer bg-white group-hover:shadow-md">
                            <div class="space-y-2 text-center">
                                <span class="mx-auto h-16 w-16 text-gray-400 group-hover:text-hvu-red transition-colors flex items-center justify-center rounded-full bg-gray-50 group-hover:bg-white">
                                    <svg class="h-10 w-10" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <div class="flex flex-col text-sm text-gray-600">
                                    <label for="hoc_ba" class="relative cursor-pointer rounded-md font-bold text-hvu-red hover:underline focus-within:outline-none">
                                        <span>Chọn file tải lên</span>
                                        <input id="hoc_ba" name="hoc_ba" type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png">
                                    </label>
                                    <p class="pl-1 mt-1 text-gray-500">hoặc kéo thả vào đây</p>
                                </div>
                                <p class="text-xs text-gray-400 file-name-display font-medium min-h-[1.5em]"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Bằng tốt nghiệp -->
                     <div class="group">
                        <label class="block text-sm font-bold text-gray-700 mb-2 group-hover:text-hvu-red transition-colors">
                            2. Bằng tốt nghiệp / Giấy CNTN <span class="text-red-500">*</span>
                        </label>
                        <div class="relative mt-1 flex justify-center px-6 pt-10 pb-10 border-2 border-gray-300 border-dashed rounded-xl hover:border-hvu-red hover:bg-red-50 transition-all cursor-pointer bg-white group-hover:shadow-md">
                            <div class="space-y-2 text-center">
                                <span class="mx-auto h-16 w-16 text-gray-400 group-hover:text-hvu-red transition-colors flex items-center justify-center rounded-full bg-gray-50 group-hover:bg-white">
                                     <svg class="h-10 w-10" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <div class="flex flex-col text-sm text-gray-600">
                                    <label for="bang_tot_nghiep" class="relative cursor-pointer rounded-md font-bold text-hvu-red hover:underline focus-within:outline-none">
                                        <span>Chọn file tải lên</span>
                                        <input id="bang_tot_nghiep" name="bang_tot_nghiep" type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png">
                                    </label>
                                    <p class="pl-1 mt-1 text-gray-500">hoặc kéo thả vào đây</p>
                                </div>
                                 <p class="text-xs text-gray-400 file-name-display font-medium min-h-[1.5em]"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-200 flex items-center justify-between">
                    <a href="<?= url('/academic/grade/12') ?>" class="flex items-center text-gray-600 hover:text-hvu-red font-bold px-4 py-2 rounded transition-colors group">
                        <svg class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        Quay lại
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center py-3 px-8 border border-transparent shadow-lg text-base font-bold rounded-lg text-white bg-hvu-red hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 transition-all transform hover:-translate-y-0.5">
                        Lưu hồ sơ & Tiếp tục
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Simple script to show selected filename
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', e => {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const container = e.target.closest('.space-y-2');
                let display = container.querySelector('.file-name-display');
                if (!display) {
                    display = document.createElement('p');
                    display.className = 'text-sm font-bold text-hvu-red mt-2 file-name-display';
                    container.parentNode.appendChild(display); // Append to the parent grid item
                } else {
                     display.textContent = 'Đã chọn: ' + fileName;
                }
            }
        });
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
