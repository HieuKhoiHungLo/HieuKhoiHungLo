<!DOCTYPE html>
<?php
// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực 2 lớp - HVU Admin</title>
    <link rel="stylesheet" href="<?= url('/assets/css/tailwind.min.css') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="<?= url('/assets/img/Logo.png') ?>" alt="HVU" class="h-16 mx-auto mb-4">
            <h1 class="text-2xl font-black text-white uppercase tracking-tight">Xác thực 2 lớp</h1>
            <p class="text-slate-400 text-sm mt-1">Nhập mã từ ứng dụng Google Authenticator</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl font-bold border border-red-100 flex items-center text-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="<?= url('/admin/2fa/verify') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                
                <!-- Icon -->
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto">
                        <i class="fas fa-shield-alt text-blue-600 text-3xl"></i>
                    </div>
                </div>

                <!-- Code Input -->
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2 text-center">Mã xác thực 6 số</label>
                    <input type="text" name="code" required maxlength="6" pattern="[0-9A-Za-z]{6,8}"
                           class="w-full px-4 py-4 text-center text-3xl font-mono tracking-[0.5em] border-2 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition"
                           placeholder="••••••" autocomplete="off" autofocus>
                </div>

                <!-- Submit -->
                <button type="submit" class="w-full py-4 bg-[#0066FF] text-white font-bold uppercase text-sm tracking-wider rounded-xl shadow-lg hover:bg-blue-700 transition">
                    <i class="fas fa-check-circle mr-2"></i> Xác nhận
                </button>
            </form>

            <!-- Backup Code Option -->
            <div class="mt-6 pt-6 border-t text-center">
                <details class="text-left">
                    <summary class="text-blue-600 text-sm cursor-pointer hover:underline">Sử dụng mã dự phòng</summary>
                    <form action="<?= url('/admin/2fa/verify') ?>" method="POST" class="mt-4">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="use_backup" value="1">
                        <input type="text" name="code" required 
                               class="w-full px-4 py-3 text-center font-mono uppercase tracking-wider border rounded-lg mb-3"
                               placeholder="Mã dự phòng 8 ký tự">
                        <button type="submit" class="w-full py-3 bg-gray-600 text-white font-bold rounded-lg hover:bg-gray-700 transition text-sm">
                            Dùng mã dự phòng
                        </button>
                    </form>
                </details>
            </div>

            <!-- Cancel -->
            <div class="mt-4 text-center">
                <a href="<?= url('/admin/login') ?>" class="text-sm text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại đăng nhập
                </a>
            </div>
        </div>
    </div>

</body>
</html>
