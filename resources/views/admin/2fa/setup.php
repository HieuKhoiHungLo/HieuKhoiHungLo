<?php $title = 'Cài đặt Xác thực 2 lớp - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-2xl mx-auto">
    <header class="mb-6">
        <a href="<?= url('/admin/dashboard') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition inline-flex items-center mb-2">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại Dashboard
        </a>
        <h2 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Xác thực 2 lớp (2FA)</h2>
        <p class="text-gray-500 mt-1">Bảo vệ tài khoản bằng Google Authenticator</p>
    </header>

    <?php if (!empty($_GET['success'])): ?>
        <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-xl font-bold border border-green-100 flex items-center">
            <i class="fas fa-check-circle mr-2"></i> 
            <?= $_GET['success'] === 'enabled' ? 'Đã kích hoạt 2FA thành công!' : 'Đã tắt 2FA!' ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-xl font-bold border border-red-100 flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i> 
            <?php
            $errors = [
                'invalid_code' => 'Mã xác thực không đúng. Vui lòng thử lại.',
                'wrong_password' => 'Mật khẩu không chính xác.',
                'invalid_session' => 'Phiên làm việc hết hạn. Vui lòng thử lại.',
                'db_error' => 'Lỗi hệ thống. Vui lòng thử lại.'
            ];
            echo $errors[$_GET['error']] ?? 'Có lỗi xảy ra.';
            ?>
        </div>
    <?php endif; ?>

    <?php if ($isEnabled): ?>
        <!-- 2FA is Enabled -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-shield-alt text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white">2FA đang BẬT</h3>
                        <p class="text-sm text-gray-500">Tài khoản được bảo vệ 2 lớp</p>
                    </div>
                </div>
                <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full uppercase">Active</span>
            </div>

            <hr class="border-gray-100 dark:border-gray-700 mb-6">

            <form action="<?= url('/admin/2fa/disable') ?>" method="POST" onsubmit="return confirm('Bạn chắc chắn muốn tắt 2FA?');">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Để tắt 2FA, nhập mật khẩu tài khoản:</p>
                <input type="password" name="password" required 
                       class="w-full px-4 py-3 border rounded-lg mb-4 dark:bg-gray-700 dark:border-gray-600 dark:text-white" 
                       placeholder="Mật khẩu">
                <button type="submit" class="w-full py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-power-off mr-2"></i> Tắt 2FA
                </button>
            </form>
        </div>

    <?php else: ?>
        <!-- 2FA Setup -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-4">
                <h3 class="text-white font-bold flex items-center">
                    <i class="fas fa-mobile-alt mr-2"></i> Thiết lập Google Authenticator
                </h3>
            </div>

            <div class="p-6">
                <!-- Step 1: Install App -->
                <div class="mb-6">
                    <h4 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center">
                        <span class="w-6 h-6 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center mr-2">1</span>
                        Cài đặt ứng dụng
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 ml-8">
                        Tải <b>Google Authenticator</b> từ App Store hoặc Google Play.
                    </p>
                </div>

                <!-- Step 2: Scan QR -->
                <div class="mb-6">
                    <h4 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center">
                        <span class="w-6 h-6 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center mr-2">2</span>
                        Quét mã QR
                    </h4>
                    <div class="ml-8">
                        <div class="bg-white p-4 rounded-xl border inline-block mb-3">
                            <img src="<?= htmlspecialchars($qrCode) ?>" alt="QR Code" class="w-48 h-48">
                        </div>
                        <p class="text-xs text-gray-500 mb-2">Hoặc nhập mã thủ công:</p>
                        <code class="bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded text-sm font-mono tracking-wider"><?= htmlspecialchars($secret) ?></code>
                    </div>
                </div>

                <!-- Step 3: Enter Code -->
                <div class="mb-6">
                    <h4 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center">
                        <span class="w-6 h-6 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center mr-2">3</span>
                        Nhập mã xác thực
                    </h4>
                    <form action="<?= url('/admin/2fa/enable') ?>" method="POST" class="ml-8">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="text" name="code" required maxlength="6" pattern="[0-9]{6}"
                               class="w-40 px-4 py-3 text-center text-2xl font-mono tracking-widest border-2 rounded-lg focus:border-blue-500 outline-none dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                               placeholder="000000" autocomplete="off">
                        <button type="submit" class="ml-2 px-6 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition">
                            Kích hoạt 2FA
                        </button>
                    </form>
                </div>

                <!-- Backup Codes -->
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 mt-6">
                    <h4 class="font-bold text-amber-800 dark:text-amber-400 mb-2 flex items-center">
                        <i class="fas fa-key mr-2"></i> Mã dự phòng (Lưu lại!)
                    </h4>
                    <p class="text-xs text-amber-700 dark:text-amber-500 mb-3">Dùng khi mất điện thoại. Mỗi mã chỉ dùng 1 lần.</p>
                    <div class="grid grid-cols-4 gap-2">
                        <?php foreach ($backupCodes as $code): ?>
                            <code class="bg-white dark:bg-gray-800 px-2 py-1 rounded text-xs font-mono text-center"><?= $code ?></code>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
