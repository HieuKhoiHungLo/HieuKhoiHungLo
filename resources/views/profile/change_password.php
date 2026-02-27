<?php include __DIR__ . '/../layouts/header.php'; ?>

<style>
    /* Bắt buộc Padding trái phải lớn hơn để không đè vào Icon */
    .password-input {
        padding-left: 2.875rem !important; 
        padding-right: 2.75rem !important;
    }
    .password-card {
        box-shadow: 0 12px 40px -12px rgba(0,0,0,0.1);
    }
</style>

<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <!-- Breadcrumb -->
    <div class="mb-6 flex items-center justify-between">
        <a href="<?= url('/profile/step1') ?>" class="text-gray-500 hover:text-hvu-red transition flex items-center text-[15px] font-semibold group rounded-lg focus:outline-none">
            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center mr-2 group-hover:bg-red-50 group-hover:text-hvu-red transition-colors">
                <i class="fas fa-arrow-left"></i> 
            </div>
            Quay lại thông tin cá nhân
        </a>
    </div>

    <div class="bg-white rounded-3xl password-card border border-gray-100 overflow-hidden relative">
        <!-- Top Banner / Header -->
        <div class="h-28 md:h-32 bg-gradient-to-r from-red-600 via-red-500 to-red-700 relative overflow-hidden">
            <!-- Decorative overlay -->
            <div class="absolute inset-0 bg-black/10"></div>
            <!-- Icon Avatar -->
            <div class="absolute -bottom-8 left-6 md:left-10 w-20 h-20 bg-white rounded-[1.25rem] shadow-lg flex items-center justify-center border-4 border-white z-10">
                <div class="w-full h-full bg-red-50 rounded-xl flex items-center justify-center text-red-600 text-3xl">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
        </div>

        <div class="pt-14 px-6 md:px-10 pb-8 relative z-0">
            <h2 class="text-2xl font-bold text-gray-900 mb-1">Thiết lập bảo mật</h2>
            <p class="text-gray-500 text-[15px] mb-8">Thay đổi mật khẩu tài khoản để tăng cường an toàn</p>

            <form action="<?= url('/profile/change-password') ?>" method="POST">
                <?= csrf_field() ?>

                <!-- Status Messages -->
                <?php if (isset($error)): ?>
                    <div class="mb-8 p-4 bg-red-50 border border-red-100 rounded-xl flex items-start animate-fade-in shadow-sm">
                        <div class="text-red-500 mt-0.5 mr-3 text-lg"><i class="fas fa-exclamation-circle"></i></div>
                        <p class="font-medium text-red-700 text-[15px] leading-relaxed"><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="mb-8 p-4 bg-emerald-50 border border-emerald-100 rounded-xl flex items-start animate-fade-in shadow-sm">
                        <div class="text-emerald-500 mt-0.5 mr-3 text-lg"><i class="fas fa-check-circle"></i></div>
                        <p class="font-medium text-emerald-800 text-[15px] leading-relaxed"><?= htmlspecialchars($success) ?></p>
                    </div>
                <?php endif; ?>

                <div class="space-y-6">
                    <!-- Current Password -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Mật khẩu hiện tại <span class="text-red-500">*</span></label>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 pl-4 w-11 flex items-center justify-center text-gray-400 group-focus-within:text-red-500 transition-colors pointer-events-none z-10">
                                <i class="fas fa-lock text-[15px]"></i>
                            </span>
                            <input type="password" name="current_password" required id="current_password"
                                   class="w-full bg-slate-50 border border-slate-200 text-gray-900 rounded-xl focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-red-400 block py-3.5 password-input outline-none transition-all font-medium text-[15px]" 
                                   placeholder="Nhập mật khẩu bạn đang dùng">
                            <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center justify-center w-12 text-gray-400 hover:text-gray-600 toggle-password focus:outline-none z-10" data-target="current_password">
                                <i class="fas fa-eye text-[15px]"></i>
                            </button>
                        </div>
                    </div>

                    <div class="h-px w-full bg-gray-100 my-4"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                        <!-- New Password -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Mật khẩu mới <span class="text-red-500">*</span></label>
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 pl-4 w-11 flex items-center justify-center text-gray-400 group-focus-within:text-red-500 transition-colors pointer-events-none z-10">
                                    <i class="fas fa-key text-[15px]"></i>
                                </span>
                                <input type="password" name="new_password" required id="new_password"
                                       class="w-full bg-slate-50 border border-slate-200 text-gray-900 rounded-xl focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-red-400 block py-3.5 password-input outline-none transition-all font-medium text-[15px]" 
                                       placeholder="Tạo mật khẩu mới">
                                <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center justify-center w-12 text-gray-400 hover:text-gray-600 toggle-password focus:outline-none z-10" data-target="new_password">
                                    <i class="fas fa-eye text-[15px]"></i>
                                </button>
                            </div>
                            <div class="mt-2 text-[13px] text-gray-500 flex items-start gap-1.5 font-medium">
                                <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                                Tối thiểu 6 ký tự. Nên có chữ số và chữ cái.
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Xác nhận mật khẩu mới <span class="text-red-500">*</span></label>
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 pl-4 w-11 flex items-center justify-center text-gray-400 group-focus-within:text-red-500 transition-colors pointer-events-none z-10">
                                    <i class="fas fa-check-double text-[15px]"></i>
                                </span>
                                <input type="password" name="confirm_password" required id="confirm_password"
                                       class="w-full bg-slate-50 border border-slate-200 text-gray-900 rounded-xl focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-red-400 block py-3.5 password-input outline-none transition-all font-medium text-[15px]" 
                                       placeholder="Nhập lại mật khẩu mới">
                                <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center justify-center w-12 text-gray-400 hover:text-gray-600 toggle-password focus:outline-none z-10" data-target="confirm_password">
                                    <i class="fas fa-eye text-[15px]"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="mt-10 pt-6 border-t border-gray-100 flex flex-col-reverse sm:flex-row gap-3 items-center justify-end">
                    <a href="<?= url('/profile/step1') ?>" class="w-full sm:w-auto px-6 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 hover:text-gray-900 transition-colors text-center text-[15px]">
                        Hủy thay đổi
                    </a>
                    <button type="submit" class="w-full sm:w-[220px] bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl shadow-[0_4px_14px_rgba(220,38,38,0.3)] hover:shadow-[0_6px_20px_rgba(220,38,38,0.4)] transition-all flex items-center justify-center gap-2 text-[15px]">
                        <i class="fas fa-save"></i>
                        Cập nhật mật khẩu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Security Tips -->
    <div class="mt-8 bg-blue-50/50 border border-blue-100/80 rounded-2xl p-6 flex flex-col md:flex-row items-start gap-4">
        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center shrink-0">
            <i class="fas fa-lightbulb text-xl"></i>
        </div>
        <div>
            <h4 class="font-bold text-blue-900 text-[15px] mb-1.5">Mẹo bảo mật tài khoản</h4>
            <p class="text-blue-800/80 text-[14px] leading-relaxed">
                Tuyệt đối không sử dụng chung một mật khẩu cho nhiều dịch vụ khác nhau. Nhà trường sẽ không bao giờ yêu cầu bạn cung cấp mật khẩu qua tin nhắn, email hay điện thoại. Hãy thay đổi mật khẩu định kỳ 3-6 tháng một lần.
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logic for toggling password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                icon.classList.add('text-red-500'); // Highlight when visible
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.remove('text-red-500');
                icon.classList.add('fa-eye');
            }
            
            // Focus back to input naturally
            input.focus();
        });
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
