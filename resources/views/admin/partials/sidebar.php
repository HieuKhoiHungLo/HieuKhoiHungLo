<!-- Sidebar -->
<aside class="w-64 bg-[#0066FF] text-white flex-shrink-0 hidden md:flex flex-col shadow-2xl">
    <div class="p-6 border-b border-white/10 uppercase italic">
        <h1 class="text-xl font-black tracking-wider">HVU Portal</h1>
        <p class="text-white/70 text-[10px] mt-1 font-bold">Hệ thống Tuyển sinh 2026</p>
    </div>
    <nav class="flex-grow p-4 space-y-2 mt-4">
        <?php if(\App\Models\QuanTriVien::hasPermission($user, 'dashboard')): ?>
        <a href="<?= url('/admin/dashboard') ?>" class="flex items-center space-x-3 p-3 <?= strpos($_SERVER['REQUEST_URI'], '/admin/dashboard') !== false ? 'bg-white/20 shadow-inner' : 'rounded-xl transition hover:bg-white/10 opacity-70 hover:opacity-100' ?>">
            <i class="fas fa-users text-sm"></i>
            <span class="font-bold text-sm">Danh sách hồ sơ</span>
        </a>
        <?php endif; ?>

        <?php if(\App\Models\QuanTriVien::hasPermission($user, 'master_data')): ?>
        <a href="<?= url('/admin/master-data') ?>" class="flex items-center space-x-3 p-3 <?= strpos($_SERVER['REQUEST_URI'], '/admin/master-data') !== false ? 'bg-white/20 shadow-inner' : 'rounded-xl transition hover:bg-white/10 opacity-70 hover:opacity-100' ?>">
            <i class="fas fa-database text-sm"></i>
            <span class="font-bold text-sm">Quản lý danh mục</span>
        </a>
        <?php endif; ?>

        <?php if(\App\Models\QuanTriVien::hasPermission($user, 'posts')): ?>
         <a href="<?= url('/admin/posts') ?>" class="flex items-center space-x-3 p-3 <?= strpos($_SERVER['REQUEST_URI'], '/admin/posts') !== false ? 'bg-white/20 shadow-inner' : 'rounded-xl transition hover:bg-white/10 opacity-70 hover:opacity-100' ?>">
            <i class="fas fa-newspaper text-sm"></i>
            <span class="font-bold text-sm">Tin tức & Thông báo</span>
        </a>
        <?php endif; ?>

        <?php if(\App\Models\QuanTriVien::hasPermission($user, 'stats')): ?>
        <a href="<?= url('/admin/stats') ?>" class="flex items-center space-x-3 p-3 <?= strpos($_SERVER['REQUEST_URI'], '/admin/stats') !== false ? 'bg-white/20 shadow-inner' : 'rounded-xl transition hover:bg-white/10 opacity-70 hover:opacity-100' ?>">
            <i class="fas fa-chart-pie text-sm"></i>
            <span class="font-bold text-sm">Thống kê báo cáo</span>
        </a>
        <?php endif; ?>

        <?php if(\App\Models\QuanTriVien::hasPermission($user, 'accounts')): ?>
        <a href="<?= url('/admin/accounts') ?>" class="flex items-center space-x-3 p-3 <?= strpos($_SERVER['REQUEST_URI'], '/admin/accounts') !== false ? 'bg-white/20 shadow-inner' : 'rounded-xl transition hover:bg-white/10 opacity-70 hover:opacity-100' ?>">
            <i class="fas fa-user-shield text-sm"></i>
            <span class="font-bold text-sm">Quản lý tài khoản</span>
        </a>
        <?php endif; ?>
    </nav>
    <div class="p-4 border-t border-indigo-900/50">
        <div class="flex items-center space-x-3 px-2 py-4">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_name'] ?? 'Admin') ?>&background=4f46e5&color=fff" class="w-10 h-10 rounded-lg">
            <div class="overflow-hidden">
                <p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></p>
                <p class="text-xs text-indigo-400 truncate"><?= htmlspecialchars($_SESSION['admin_role'] ?? 'Quản trị viên') ?></p>
            </div>
        </div>
        <a href="<?= url('/logout') ?>" class="block w-full text-center py-2 text-xs font-bold text-red-400 hover:text-red-300 transition uppercase tracking-widest mt-2">Đăng xuất</a>
    </div>
</aside>
