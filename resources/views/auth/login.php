<?php $title = 'Đăng nhập - Tuyển sinh HVU'; include __DIR__ . '/../layouts/header.php'; ?>

<div class="flex items-center justify-center py-12">
    <div class="glass-card p-10 rounded-3xl shadow-2xl w-full max-w-md border border-white/20">
        <div class="flex justify-center mb-6">
            <img loading="lazy" src="<?= url('/assets/img/Logo.png') ?>" alt="Logo HVU" class="h-16">
        </div>
        <h2 class="text-2xl font-bold text-center text-hvu-red mb-6">Đăng nhập Hệ thống</h2>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                Đăng ký thành công! Vui lòng đăng nhập.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['reset'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                Đổi mật khẩu thành công! Vui lòng đăng nhập với mật khẩu mới.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['timeout'])): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4 flex items-center">
                <i class="fas fa-clock mr-2"></i> Phiên đăng nhập đã hết hạn do không có thao tác. Vui lòng đăng nhập lại.
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/login') ?>
    <?= csrf_field() ?>">
            <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
            <?php if (!empty($_REQUEST['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_REQUEST['redirect']) ?>">
            <?php endif; ?>
            <div class="mb-5">
                <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                    <i class="fas fa-id-card text-hvu-red mr-2"></i> Số CCCD/CMND
                </label>
                <input type="text" name="cccd" value="<?= htmlspecialchars($old['cccd'] ?? ($_SESSION['prefill_cccd'] ?? ($_GET['cccd'] ?? ''))) ?>" required class="hvu-input" placeholder="Nhập số CCCD của bạn">
                <?php unset($_SESSION['prefill_cccd']); ?>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                    <i class="fas fa-lock text-hvu-red mr-2"></i> Mật khẩu
                </label>
                <input type="password" name="password" required class="hvu-input" placeholder="Nhập mật khẩu">
            </div>
            <div class="mb-8 flex items-center">
                <input type="checkbox" name="remember" id="remember" class="w-4 h-4 text-hvu-red bg-white border-gray-300 rounded focus:ring-hvu-red/50 focus:ring-2">
                <label for="remember" class="ml-2 text-sm text-gray-700 cursor-pointer">
                    Ghi nhớ đăng nhập
                </label>
            </div>
            <button type="submit" class="w-full hvu-btn-primary py-3 mb-4">
                <i class="fas fa-sign-in-alt mr-2"></i> Đăng nhập ngay
            </button>
        </form>
        <p class="mt-4 text-center text-sm text-gray-600">Chưa có tài khoản? <a href="<?= url('/register') ?>" class="text-hvu-red font-black hover:underline">Đăng ký mới</a></p>
        <p class="mt-2 text-center text-sm"><a href="<?= url('/forgot-password') ?>" class="text-gray-500 hover:text-gray-700">Quên mật khẩu?</a></p>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
