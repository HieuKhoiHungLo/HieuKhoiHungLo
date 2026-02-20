<?php $title = 'Cấu hình Email - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-5xl mx-auto">
    <header class="mb-6">
        <a href="<?= url('/admin/dashboard') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition inline-flex items-center mb-2">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại Dashboard
        </a>
        <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Cấu hình Email (SMTP)</h2>
    </header>

    <?php if (!empty($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
        <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl font-bold border border-green-100 flex items-center text-sm">
            <i class="fas fa-check-circle mr-2"></i> Đã lưu cấu hình!
        </div>
    <?php endif; ?>
    
    <?php if (!empty($_GET['msg']) && $_GET['msg'] == 'test_success'): ?>
        <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl font-bold border border-green-100 flex items-center text-sm">
            <i class="fas fa-paper-plane mr-2"></i> Email kiểm tra đã được gửi thành công!
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl font-bold border border-red-100 flex items-center text-sm">
            <i class="fas fa-exclamation-triangle mr-2"></i> <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <form action="<?= url('/admin/settings/email/save') ?>" method="POST" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                
                <!-- Header -->
                <div class="bg-gradient-to-r from-[#0066FF] to-blue-700 px-6 py-4">
                    <h3 class="text-white font-bold flex items-center">
                        <i class="fas fa-envelope mr-2"></i> Thông tin SMTP
                    </h3>
                </div>
                
                <div class="p-6 space-y-5">
                    <!-- Sender Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tên người gửi</label>
                            <input type="text" name="email_from_name" value="<?= htmlspecialchars($settings['email_from_name']) ?>" 
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition text-sm font-medium"
                                   placeholder="Phòng Tuyển sinh HVU">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email gửi đi</label>
                            <input type="email" name="email_from_address" value="<?= htmlspecialchars($settings['email_from_address']) ?>" 
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition text-sm font-medium text-[#0066FF]"
                                   placeholder="tuyensinh@hvu.edu.vn">
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    <!-- SMTP Settings -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host']) ?>" 
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0066FF] outline-none transition text-sm font-mono"
                                   placeholder="smtp.gmail.com">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Port</label>
                            <input type="text" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port']) ?>" 
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0066FF] outline-none transition text-sm font-mono text-center"
                                   placeholder="587">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Mã hóa</label>
                            <select name="smtp_secure" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0066FF] outline-none transition text-sm font-medium">
                                <option value="tls" <?= $settings['smtp_secure'] == 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= $settings['smtp_secure'] == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="" <?= $settings['smtp_secure'] == '' ? 'selected' : '' ?>>Không</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Username</label>
                            <input type="text" name="smtp_user" value="<?= htmlspecialchars($settings['smtp_user']) ?>" 
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0066FF] outline-none transition text-sm font-mono"
                                   placeholder="user@gmail.com">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Password</label>
                            <input type="password" name="smtp_pass" value="<?= htmlspecialchars($settings['smtp_pass']) ?>" 
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0066FF] outline-none transition text-sm font-mono">
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t">
                    <button type="submit" class="w-full py-2.5 bg-[#0066FF] text-white font-bold uppercase text-sm tracking-wider rounded-lg shadow hover:bg-blue-700 hover:shadow-lg transition-all">
                        <i class="fas fa-save mr-2"></i> Lưu Cấu Hình
                    </button>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <!-- Test Card -->
            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-5 text-white shadow-lg">
                <h4 class="font-bold flex items-center mb-3">
                    <i class="fas fa-paper-plane mr-2"></i> Gửi Email Thử
                </h4>
                <form action="<?= url('/admin/settings/email/test') ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="email" name="test_email" required 
                           class="w-full px-3 py-2 bg-white/20 border border-white/30 rounded-lg text-white placeholder-white/70 text-sm mb-3 focus:bg-white/30 outline-none transition"
                           placeholder="your@email.com">
                    <button type="submit" class="w-full py-2 bg-white text-[#0066FF] font-bold text-sm rounded-lg hover:bg-indigo-50 transition">
                        Gửi Test
                    </button>
                </form>
            </div>

            <!-- Tips Card -->
            <div class="bg-amber-50 rounded-2xl p-5 border border-amber-100">
                <h4 class="font-bold text-amber-800 flex items-center mb-3">
                    <i class="fas fa-lightbulb text-amber-500 mr-2"></i> Hướng dẫn
                </h4>
                <ul class="text-xs text-amber-700 space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-check text-amber-500 mr-2 mt-0.5"></i>
                        <span><b>Gmail:</b> Cần tạo App Password</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-amber-500 mr-2 mt-0.5"></i>
                        <span><b>TLS:</b> Port 587</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-amber-500 mr-2 mt-0.5"></i>
                        <span><b>SSL:</b> Port 465</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
