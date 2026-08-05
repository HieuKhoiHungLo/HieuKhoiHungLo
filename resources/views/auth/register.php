<?php $title = 'Đăng ký - Tuyển sinh HVU'; include __DIR__ . '/../layouts/header.php'; ?>

<div class="flex items-center justify-center py-12">
    <div class="glass-card p-10 rounded-3xl shadow-2xl w-full max-w-md border border-white/20">
        <h2 class="text-2xl font-bold text-center text-hvu-red mb-6">Đăng ký Hồ sơ</h2>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/register') ?>
    <?= csrf_field() ?>">
            <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
            
            <div class="mb-5">
                <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                    <i class="fas fa-id-card text-hvu-red mr-2"></i> Số CCCD/CMND <span class="text-red-500 ml-1">*</span>
                </label>
                <input type="text" name="cccd" value="<?= htmlspecialchars($old['cccd'] ?? '') ?>" required class="hvu-input" placeholder="Nhập số định danh cá nhân">
            </div>

            <div class="mb-5">
                <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                    <i class="fas fa-user text-hvu-red mr-2"></i> Họ và tên <span class="text-red-500 ml-1">*</span>
                </label>
                <input type="text" name="fullname" value="<?= htmlspecialchars($old['fullname'] ?? '') ?>" required class="hvu-input uppercase" placeholder="Nhập họ và tên đầy đủ" oninput="this.value = this.value.toUpperCase();">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                        <i class="fas fa-phone text-hvu-red mr-2"></i> SĐT
                    </label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>" class="hvu-input" placeholder="09xxx...">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                        <i class="fas fa-envelope text-hvu-red mr-2"></i> Email
                    </label>
                    <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" class="hvu-input" placeholder="example@...">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-8">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                        <i class="fas fa-key text-hvu-red mr-2"></i> Mật khẩu <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input type="password" name="password" required class="hvu-input" placeholder="Tạo mật khẩu">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                        <i class="fas fa-check-circle text-hvu-red mr-2"></i> Xác nhận
                    </label>
                    <input type="password" name="confirm_password" required class="hvu-input" placeholder="Nhập lại">
                </div>
            </div>

            <button type="submit" class="w-full hvu-btn-primary py-3">
                <i class="fas fa-user-plus mr-2"></i> Đăng ký ngay
            </button>
        </form>
        <p class="mt-6 text-center text-sm text-gray-600">Đã có tài khoản? <a href="<?= url('/login') ?>" class="text-hvu-red font-black hover:underline">Đăng nhập ngay</a></p>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
