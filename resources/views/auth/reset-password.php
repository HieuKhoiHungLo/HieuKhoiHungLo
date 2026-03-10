<?php $title = 'Đặt lại mật khẩu - Tuyển sinh HVU'; include __DIR__ . '/../layouts/header.php'; ?>

<div class="flex items-center justify-center py-12">
    <div class="glass-card p-10 rounded-3xl shadow-2xl w-full max-w-md border border-white/20">
        <h2 class="text-2xl font-bold text-center text-hvu-red mb-6">Đặt lại Mật khẩu</h2>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="mb-6 p-4 bg-gray-50/50 rounded-xl border border-dashed border-gray-200 text-sm text-gray-600 text-center">
            Đặt lại mật khẩu cho tài khoản:<br>
            <strong class="text-hvu-red"><?= htmlspecialchars($_SESSION['reset_email'] ?? '') ?></strong>
        </div>

        <form method="POST" action="<?= url('/reset-password') ?>">
            <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
            <div class="mb-5">
                <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                    <i class="fas fa-key text-hvu-red mr-2"></i> Mật khẩu mới
                </label>
                <input type="password" name="password" required class="hvu-input" placeholder="Nhập mật khẩu mới">
            </div>
            <div class="mb-8">
                <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                    <i class="fas fa-check-circle text-hvu-red mr-2"></i> Xác nhận mật khẩu
                </label>
                <input type="password" name="confirm_password" required class="hvu-input" placeholder="Nhập lại mật khẩu mới">
            </div>
            <button type="submit" class="w-full hvu-btn-primary py-3">
                <i class="fas fa-sync-alt mr-2"></i> Cập nhật mật khẩu
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
