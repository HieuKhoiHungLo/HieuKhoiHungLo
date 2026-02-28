<?php $title = 'Quên mật khẩu - Tuyển sinh HVU'; include __DIR__ . '/../layouts/header.php'; ?>

<div class="flex items-center justify-center py-12">
    <div class="glass-card p-10 rounded-3xl shadow-2xl w-full max-w-md border border-white/20">
        <h2 class="text-2xl font-bold text-center text-hvu-red mb-6">Khôi phục Mật khẩu</h2>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 p-5 rounded-xl mb-6 text-sm flex items-start shadow-sm">
                <i class="fas fa-check-circle text-green-500 text-xl mr-3 mt-0.5"></i>
                <div>
                    <strong class="block text-green-900 mb-1 lg:text-base">Đã gửi yêu cầu thành công!</strong>
                    <?= nl2br(htmlspecialchars($success)) ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/forgot-password') ?>">
            <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                    <i class="fas fa-id-card text-hvu-red mr-2"></i> Số CCCD
                </label>
                <input type="text" name="cccd" required class="hvu-input" placeholder="Nhập số CCCD của bạn">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                    <i class="fas fa-envelope text-hvu-red mr-2"></i> Email đăng ký
                </label>
                <input type="email" name="email" required class="hvu-input" placeholder="Nhập email của bạn">
            </div>
            <button type="submit" class="w-full hvu-btn-primary py-3">
                Tiếp tục &rarr;
            </button>
        </form>
        <p class="mt-6 text-center text-sm font-bold"><a href="<?= url('/login') ?>" class="text-gray-500 hover:text-hvu-red transition">Quay lại Đăng nhập</a></p>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
