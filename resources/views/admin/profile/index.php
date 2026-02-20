<?php $title = 'Hồ sơ Cá nhân - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-4xl mx-auto">
    <header class="mb-6">
        <h2 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Hồ sơ Cá nhân</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Quản lý thông tin tài khoản và bảo mật</p>
    </header>

    <?php if (!empty($success)): ?>
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-xl font-bold border border-green-100 dark:border-green-900/50 flex items-center shadow-sm">
            <i class="fas fa-check-circle mr-3 text-lg"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-xl font-bold border border-red-100 dark:border-red-900/50 flex items-center shadow-sm">
            <i class="fas fa-exclamation-circle mr-3 text-lg"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Basic Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Profile Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-4">
                    <h3 class="text-white font-bold flex items-center">
                        <i class="fas fa-user-edit mr-2"></i> Thông tin Cơ bản
                    </h3>
                </div>
                
                <form action="<?= url('/admin/profile/update') ?>" method="POST" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    
                    <div class="flex items-start space-x-6">
                        <!-- Avatar Preview & Upload -->
                        <div class="flex-shrink-0 text-center">
                            <div class="relative w-24 h-24 rounded-full overflow-hidden border-4 border-gray-100 dark:border-gray-700 shadow-inner group">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="<?= url('/' . $user['avatar']) ?>" alt="Avatar" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500 text-3xl font-bold">
                                        <?= mb_substr($user['ho_ten'], 0, 1) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <label for="avatar_upload" class="absolute inset-0 bg-black/50 flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>
                            <input type="file" name="avatar" id="avatar_upload" class="hidden" accept="image/*" onchange="document.getElementById('avatar_form_btn').classList.remove('hidden')">
                            <p class="text-xs text-gray-400 mt-2">Max 2MB</p>
                        </div>

                        <!-- Info Fields -->
                        <div class="flex-grow space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Tên đăng nhập</label>
                                <input type="text" value="<?= htmlspecialchars($user['ten_dang_nhap']) ?>" disabled
                                       class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-500 dark:text-gray-400 font-mono text-sm">
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Họ và Tên</label>
                                <input type="text" name="fullname" value="<?= htmlspecialchars($user['ho_ten']) ?>" required
                                       class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm font-medium dark:text-white">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" id="avatar_form_btn" class="px-6 py-2 bg-[#0066FF] text-white font-bold rounded-xl shadow hover:bg-indigo-700 transition text-sm">
                            Lưu Thông Tin
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-gray-800 dark:text-white font-bold flex items-center">
                        <i class="fas fa-key mr-2 text-[#0066FF]"></i> Đổi Mật Khẩu
                    </h3>
                </div>
                
                <form action="<?= url('/admin/profile/change-password') ?>" method="POST" class="p-6 space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Mật khẩu hiện tại</label>
                        <input type="password" name="current_password" required
                               class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm dark:text-white">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Mật khẩu mới</label>
                            <input type="password" name="new_password" required minlength="6"
                                   class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Xác nhận mật khẩu mới</label>
                            <input type="password" name="confirm_password" required minlength="6"
                                   class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm dark:text-white">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-6 py-2 bg-gray-800 dark:bg-gray-600 text-white font-bold rounded-xl shadow hover:bg-gray-900 dark:hover:bg-gray-500 transition text-sm">
                            Cập nhật Mật khẩu
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Security Status -->
        <div class="lg:col-span-1 space-y-6">
            <!-- 2FA Status Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
                    <h3 class="text-white font-bold flex items-center">
                        <i class="fas fa-shield-alt mr-2"></i> Bảo mật 2 lớp
                    </h3>
                </div>
                
                <div class="p-6 text-center">
                    <?php if ($user['two_factor_enabled']): ?>
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 mb-4">
                            <i class="fas fa-lock text-3xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-emerald-600 dark:text-emerald-400 mb-2">Đang BẬT</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Tài khoản của bạn được bảo vệ bằng xác thực 2 bước.</p>
                        
                        <a href="<?= url('/admin/2fa/setup') ?>" class="block w-full py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition text-sm">
                            <i class="fas fa-cog mr-2"></i> Quản lý 2FA
                        </a>
                    <?php else: ?>
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-400 mb-4">
                            <i class="fas fa-unlock text-3xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-500 dark:text-gray-400 mb-2">Đang TẮT</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Bật tính năng này để tăng cường bảo mật cho tài khoản.</p>
                        
                        <a href="<?= url('/admin/2fa/setup') ?>" class="block w-full py-2 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition text-sm">
                            <i class="fas fa-shield-alt mr-2"></i> Thiết lập 2FA
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Roles -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
                 <h3 class="text-xs font-bold text-gray-400 uppercase mb-3">Vai trò & Quyền hạn</h3>
                 <div class="flex flex-wrap gap-2">
                     <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs font-bold rounded-lg border border-purple-200 dark:border-purple-800">
                         <?= htmlspecialchars($user['vai_tro'] ?? 'Admin') ?>
                     </span>
                     <?php 
                     $perms = json_decode($user['permissions'] ?? '[]', true);
                     foreach($perms as $p): 
                     ?>
                     <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-bold rounded-lg border border-gray-200 dark:border-gray-600">
                         <?= htmlspecialchars($p) ?>
                     </span>
                     <?php endforeach; ?>
                 </div>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
