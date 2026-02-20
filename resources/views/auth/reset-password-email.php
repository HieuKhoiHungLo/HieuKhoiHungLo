<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại Mật khẩu - HVU Tuyển sinh</title>
    <link rel="stylesheet" href="<?= url('/public/assets/css/tailwind.min.css') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="<?= url('/public/assets/img/Logo.png') ?>" alt="HVU" class="h-16 mx-auto mb-4">
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Đặt lại Mật khẩu</h1>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if (!empty($expired)): ?>
                <!-- Expired Token -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Link đã hết hạn</h2>
                    <p class="text-gray-500 mb-6">Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn (1 giờ).</p>
                    <a href="<?= url('/forgot-password') ?>" 
                       class="inline-block px-6 py-3 bg-hvu-red text-white font-bold rounded-lg hover:bg-red-700 transition">
                        Yêu cầu link mới
                    </a>
                </div>
            <?php else: ?>
                <!-- Reset Form -->
                <?php if (!empty($error)): ?>
                    <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl font-bold border border-red-100 flex items-center text-sm">
                        <i class="fas fa-exclamation-triangle mr-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($email)): ?>
                    <p class="text-sm text-gray-500 mb-4 text-center">
                        Đặt mật khẩu mới cho: <strong class="text-gray-700"><?= htmlspecialchars($email) ?></strong>
                    </p>
                <?php endif; ?>

                <form action="<?= url('/reset-password-email') ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
                    
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Mật khẩu mới</label>
                        <div class="relative">
                            <input type="password" name="password" required minlength="6"
                                   class="w-full px-4 py-3 pl-11 border-2 rounded-xl focus:border-hvu-red focus:ring-4 focus:ring-red-100 outline-none transition"
                                   placeholder="Nhập mật khẩu mới">
                            <i class="fas fa-lock absolute left-4 top-4 text-gray-400"></i>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Xác nhận mật khẩu</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" required minlength="6"
                                   class="w-full px-4 py-3 pl-11 border-2 rounded-xl focus:border-hvu-red focus:ring-4 focus:ring-red-100 outline-none transition"
                                   placeholder="Nhập lại mật khẩu">
                            <i class="fas fa-lock absolute left-4 top-4 text-gray-400"></i>
                        </div>
                    </div>

                    <button type="submit" 
                            class="w-full py-4 bg-hvu-red text-white font-bold uppercase text-sm tracking-wider rounded-xl shadow-lg hover:bg-red-700 transition">
                        <i class="fas fa-save mr-2"></i> Đặt mật khẩu mới
                    </button>
                </form>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <a href="<?= url('/login') ?>" class="text-sm text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại Đăng nhập
                </a>
            </div>
        </div>
    </div>

</body>
</html>
