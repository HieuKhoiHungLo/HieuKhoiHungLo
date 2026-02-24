<div class="bg-white rounded-[2.5rem] shadow-2xl shadow-red-900/5 p-8 md:p-12 border border-gray-100 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-red-50/50 rounded-full blur-3xl -mr-32 -mt-32"></div>
    
    <div class="flex items-center mb-12 relative z-10">
        <div class="w-12 h-12 bg-hvu-red rounded-2xl flex items-center justify-center text-white font-black text-2xl shadow-lg shadow-red-200 mr-4">1</div>
        <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Lộ trình xét tuyển của bạn</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 <?= $totalSteps == 5 ? 'lg:grid-cols-5' : 'lg:grid-cols-4' ?> gap-4 relative z-10">
        <!-- Step 1: Personal Info -->
        <?php 
        $done1 = $isDone(1);
        $active1 = ($nextStep == 1);
        ?>
        <div class="group relative p-6 rounded-3xl border-2 transition-all duration-300 <?= $done1 ? 'bg-green-50/30 border-green-100' : ($active1 ? 'bg-white border-hvu-red shadow-xl shadow-red-100 scale-[1.02]' : 'bg-gray-50 border-gray-100 opacity-60') ?>">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl <?= $done1 ? 'bg-green-500 text-white' : ($active1 ? 'bg-hvu-red text-white' : 'bg-gray-200 text-gray-400') ?>">
                    <i class="fas fa-user-circle"></i>
                </div>
                <?php if ($done1): ?>
                    <span class="text-[10px] font-black text-green-600 uppercase tracking-widest bg-green-100 px-2 py-1 rounded-lg">Đã xong</span>
                <?php elseif ($active1): ?>
                    <span class="text-[10px] font-black text-hvu-red uppercase tracking-widest bg-red-100 px-2 py-1 rounded-lg">Đang thực hiện</span>
                <?php endif; ?>
            </div>
            <h3 class="font-black text-gray-800 mb-2">Thông tin cá nhân</h3>
            <p class="text-xs text-gray-500 mb-6 leading-relaxed">Cập nhật CCCD, địa chỉ và ảnh minh chứng diện ưu tiên.</p>
            <div class="mt-auto">
                <?php if ($done1): ?>
                    <a href="<?= url('/profile/step1') ?>" class="text-green-600 font-black text-[10px] flex items-center hover:translate-x-1 transition-transform uppercase tracking-widest">CHỈNH SỬA</a>
                <?php elseif ($active1): ?>
                    <a href="<?= url('/profile/step1') ?>" class="inline-block w-full text-center py-3 bg-hvu-red text-white font-black text-[10px] rounded-xl shadow-lg shadow-red-200 hover:bg-red-700 transition-colors uppercase tracking-widest">Nhập liệu ngay</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 2: Academic Records -->
        <?php 
        $done2 = $isDone(2);
        $active2 = ($nextStep == 2);
        ?>
        <div class="group relative p-6 rounded-3xl border-2 transition-all duration-300 <?= $done2 ? 'bg-green-50/30 border-green-100' : ($active2 ? 'bg-white border-hvu-red shadow-xl shadow-red-100 scale-[1.02]' : 'bg-gray-50 border-gray-100 ' . ($nextStep < 2 ? 'opacity-60' : '')) ?>">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl <?= $done2 ? 'bg-green-500 text-white' : ($active2 ? 'bg-hvu-red text-white' : 'bg-gray-200 text-gray-400') ?>">
                    <i class="fas fa-book-open"></i>
                </div>
                <?php if ($done2): ?>
                    <span class="text-[10px] font-black text-green-600 uppercase tracking-widest bg-green-100 px-2 py-1 rounded-lg">Đã xong</span>
                <?php elseif ($active2): ?>
                    <span class="text-[10px] font-black text-hvu-red uppercase tracking-widest bg-red-100 px-2 py-1 rounded-lg">Đang thực hiện</span>
                <?php endif; ?>
            </div>
            <h3 class="font-black text-gray-800 mb-2">Học bạ điện tử</h3>
            <p class="text-xs text-gray-500 mb-6 leading-relaxed">Nhập điểm trung bình các môn Lớp 10, 11, 12 để tính điểm tổ hợp.</p>
            <div class="mt-auto">
                <?php if ($done2): ?>
                    <a href="<?= url('/profile/step2') ?>" class="text-green-600 font-black text-[10px] flex items-center hover:translate-x-1 transition-transform uppercase tracking-widest">CHỈNH SỬA</a>
                <?php elseif ($active2): ?>
                    <a href="<?= url('/profile/step2') ?>" class="inline-block w-full text-center py-3 bg-hvu-red text-white font-black text-[10px] rounded-xl shadow-lg shadow-red-200 hover:bg-red-700 transition-colors uppercase tracking-widest">Nhập liệu ngay</a>
                <?php else: ?>
                    <span class="text-gray-400 font-black text-[10px] italic tracking-widest uppercase">Đang khóa</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 3: Certifications -->
        <?php 
        $done3 = $isDone(3);
        $active3 = ($nextStep == 3);
        ?>
        <div class="group relative p-6 rounded-3xl border-2 transition-all duration-300 <?= $done3 ? 'bg-green-50/30 border-green-100' : ($active3 ? 'bg-white border-hvu-red shadow-xl shadow-red-100 scale-[1.02]' : 'bg-gray-50 border-gray-100 ' . ($nextStep < 3 ? 'opacity-60' : '')) ?>">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl <?= $done3 ? 'bg-green-500 text-white' : ($active3 ? 'bg-hvu-red text-white' : 'bg-gray-200 text-gray-400') ?>">
                    <i class="fas fa-certificate"></i>
                </div>
                <?php if ($done3): ?>
                    <span class="text-[10px] font-black text-green-600 uppercase tracking-widest bg-green-100 px-2 py-1 rounded-lg">Đã xong</span>
                <?php elseif ($active3): ?>
                    <span class="text-[10px] font-black text-hvu-red uppercase tracking-widest bg-red-100 px-2 py-1 rounded-lg">Đang thực hiện</span>
                <?php endif; ?>
            </div>
            <h3 class="font-black text-gray-800 mb-2">Chứng chỉ quốc tế</h3>
            <p class="text-xs text-gray-500 mb-6 leading-relaxed">Khai báo IELTS, HSK... để được cộng điểm ưu tiên hoặc quy đổi điểm.</p>
            <div class="mt-auto">
                <?php if ($done3): ?>
                    <a href="<?= url('/profile/step3') ?>" class="text-green-600 font-black text-[10px] flex items-center hover:translate-x-1 transition-transform uppercase tracking-widest">CHỈNH SỬA</a>
                <?php elseif ($active3): ?>
                    <a href="<?= url('/profile/step3') ?>" class="inline-block w-full text-center py-3 bg-hvu-red text-white font-black text-[10px] rounded-xl shadow-lg shadow-red-200 hover:bg-red-700 transition-colors uppercase tracking-widest">Nhập liệu ngay</a>
                <?php else: ?>
                    <span class="text-gray-400 font-black text-[10px] italic tracking-widest uppercase">Đang chờ</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 4: THPT Scores (Conditional) -->
        <?php if ($enableTHPT): ?>
            <?php 
            $done4 = $isDone(4);
            $active4 = ($nextStep == 4);
            ?>
            <div class="group relative p-6 rounded-3xl border-2 transition-all duration-300 <?= $done4 ? 'bg-green-50/30 border-green-100' : ($active4 ? 'bg-white border-hvu-red shadow-xl shadow-red-100 scale-[1.02]' : 'bg-gray-50 border-gray-100 ' . ($nextStep < 4 ? 'opacity-60' : '')) ?>">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl <?= $done4 ? 'bg-green-500 text-white' : ($active4 ? 'bg-hvu-red text-white' : 'bg-gray-200 text-gray-400') ?>">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <?php if ($done4): ?>
                        <span class="text-[10px] font-black text-green-600 uppercase tracking-widest bg-green-100 px-2 py-1 rounded-lg">Đã xong</span>
                    <?php elseif ($active4): ?>
                        <span class="text-[10px] font-black text-hvu-red uppercase tracking-widest bg-red-100 px-2 py-1 rounded-lg">Đang thực hiện</span>
                    <?php endif; ?>
                </div>
                <h3 class="font-black text-gray-800 mb-2">Điểm thi 2026</h3>
                <p class="text-xs text-gray-500 mb-6 leading-relaxed">Cập nhật điểm thi THPT năm 2026 nếu đã có kết quả chính thức.</p>
                <div class="mt-auto">
                    <?php if ($done4): ?>
                        <a href="<?= url('/profile/step4') ?>" class="text-green-600 font-black text-[10px] flex items-center hover:translate-x-1 transition-transform uppercase tracking-widest">CHỈNH SỬA</a>
                    <?php elseif ($active4): ?>
                        <a href="<?= url('/profile/step4') ?>" class="inline-block w-full text-center py-3 bg-hvu-red text-white font-black text-[10px] rounded-xl shadow-lg shadow-red-200 hover:bg-red-700 transition-colors uppercase tracking-widest">Nhập liệu ngay</a>
                    <?php else: ?>
                        <span class="text-gray-400 font-black text-[10px] italic tracking-widest uppercase">Đang chờ</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Step 5: Choices (Step 4 if disabled) -->
        <?php 
        $choiceStepNum = $enableTHPT ? 5 : 4;
        $doneChoice = $isDone($enableTHPT ? 5 : 4);
        $activeChoice = ($nextStep == $choiceStepNum);
        ?>
        <div class="group relative p-6 rounded-3xl border-2 transition-all duration-300 <?= $doneChoice ? 'bg-green-50/30 border-green-100' : ($activeChoice ? 'bg-white border-hvu-red shadow-xl shadow-red-100 scale-[1.02]' : 'bg-gray-50 border-gray-100 ' . ($nextStep < $choiceStepNum ? 'opacity-60' : '')) ?>">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl <?= $doneChoice ? 'bg-green-500 text-white' : ($activeChoice ? 'bg-hvu-red text-white' : 'bg-gray-200 text-gray-400') ?>">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <?php if ($doneChoice): ?>
                    <span class="text-[10px] font-black text-green-600 uppercase tracking-widest bg-green-100 px-2 py-1 rounded-lg">Đã xong</span>
                <?php elseif ($activeChoice): ?>
                    <span class="text-[10px] font-black text-hvu-red uppercase tracking-widest bg-red-100 px-2 py-1 rounded-lg">Đang thực hiện</span>
                <?php endif; ?>
            </div>
            <h3 class="font-black text-gray-800 mb-2">Nguyện vọng</h3>
            <p class="text-xs text-gray-500 mb-6 leading-relaxed">Chọn ngành học và phương thức xét tuyển mong muốn của bạn.</p>
            <div class="mt-auto">
                <?php if ($doneChoice): ?>
                    <a href="<?= url('/profile/step5') ?>" class="text-green-600 font-black text-[10px] flex items-center hover:translate-x-1 transition-transform uppercase tracking-widest">CHỈNH SỬA</a>
                <?php elseif ($activeChoice): ?>
                    <a href="<?= url('/profile/step5') ?>" class="inline-block w-full text-center py-3 bg-hvu-red text-white font-black text-[10px] rounded-xl shadow-lg shadow-red-200 hover:bg-red-700 transition-colors uppercase tracking-widest">Nhập liệu ngay</a>
                <?php else: ?>
                    <span class="text-gray-400 font-black text-[10px] italic tracking-widest uppercase">Chưa khả dụng</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Caution Alert -->
    <div class="mt-12 bg-orange-50/50 border border-orange-100 rounded-3xl p-6 flex items-start">
        <div class="w-10 h-10 bg-orange-100 rounded-xl flex-shrink-0 flex items-center justify-center text-orange-600 mr-4">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
            <h4 class="font-black text-orange-800 text-sm uppercase tracking-wide mb-1">Lưu ý quan trọng:</h4>
            <p class="text-xs text-orange-700 leading-relaxed font-medium">Bạn cần hoàn thành việc nhập điểm Học bạ trước khi tiến hành chọn Nguyện vọng xét tuyển. Hệ thống sẽ tự động gợi ý tổ hợp môn tối ưu dựa trên điểm số của bạn.</p>
        </div>
    </div>
</div>
