<!DOCTYPE html>
<html lang="vi" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true' }" 
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0066FF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= url('/assets/img/Logo.png') ?>">
    <title><?= $title ?? 'Admin Portal - HVU' ?></title>
    
    <!-- Dependencies -->
    <!-- Dependencies -->
    <link rel="stylesheet" href="<?= url('/assets/css/tailwind.min.css') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Design System -->
    <style>
        :root {
            --hvu-primary: #0066FF; --hvu-primary-dark: #0050CC;
            --hvu-accent: #E11D48;
            --sidebar-width: 280px;
            --sidebar-collapsed: 70px;
        }
        body { font-family: 'Inter', sans-serif; }
        .font-heading { font-family: 'Montserrat', sans-serif; }
        
        .admin-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #003D99 0%, #001A4D 100%);
            border-right: 1px solid rgba(255,255,255,0.1);
            transition: width 0.3s ease;
        }
        .admin-sidebar.collapsed { width: var(--sidebar-collapsed); }
        .admin-sidebar.collapsed .sidebar-text { display: none; }
        .admin-sidebar.collapsed .sidebar-section { display: none; }
        
        .main-content { 
            margin-left: var(--sidebar-width); 
            transition: margin-left 0.3s ease; 
        }
        .main-content.expanded { margin-left: var(--sidebar-collapsed); }
        
        /* Dark Mode */
        .dark body { background-color: #1a1a2e; color: #e0e0e0; }
        .dark .bg-white { background-color: #16213e !important; }
        .dark .bg-slate-50, .dark .bg-gray-50 { background-color: #1a1a2e !important; }
        .dark .text-gray-900, .dark .text-slate-800, .dark .text-gray-800 { color: #e0e0e0 !important; }
        .dark .text-gray-700, .dark .text-slate-700 { color: #b0b0b0 !important; }
        .dark .text-gray-500, .dark .text-slate-500, .dark .text-gray-400 { color: #888 !important; }
        .dark .border-gray-100, .dark .border-slate-100, .dark .border-gray-200, .dark .border-slate-200 { border-color: #2d3a5f !important; }
        .dark input, .dark select, .dark textarea { background-color: #0f3460 !important; color: #e0e0e0 !important; border-color: #2d3a5f !important; }
        .dark .bg-gray-100, .dark .bg-slate-100 { background-color: #0f3460 !important; }
        
        /* Toast */
        .toast { animation: slideIn 0.3s ease, fadeOut 0.5s ease 2.5s forwards; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { to { opacity: 0; transform: translateX(100%); } }
        
        /* Loading Overlay */
        #global-loading {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(4px);
            z-index: 9999;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .dark #global-loading { background: rgba(15, 23, 42, 0.7); }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--hvu-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="text-slate-800 antialiased bg-slate-50 min-h-screen" :class="{ 'dark': darkMode }">

    <!-- Sidebar -->
    <aside class="admin-sidebar fixed left-0 top-0 h-full text-white flex flex-col z-50 shadow-2xl"
           :class="{ 'collapsed': sidebarCollapsed }">
        <!-- Brand -->
        <div class="h-20 flex items-center px-6 border-b border-white/10 bg-black/10">
            <div class="flex items-center justify-center">
                <img src="<?= url('/assets/img/Logo.png') ?>" alt="HVU Logo" class="h-10 w-auto object-contain">
            </div>
            <div class="ml-4 sidebar-text">
                <h1 class="font-black text-[10px] tracking-wider text-white font-heading uppercase leading-tight">QUẢN TRỊ HỆ THỐNG</h1>
                <p class="text-[14px] text-sky-200 uppercase tracking-widest font-bold">TUYỂN SINH</p>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-grow py-6 px-3 space-y-1 overflow-y-auto custom-scrollbar">
            <?php
            $currentUri = $_SERVER['REQUEST_URI'];
            $menu = [
                ['section' => 'TỔNG QUAN'],
                ['url' => '/admin/dashboard', 'icon' => 'fa-chart-line', 'label' => 'Dashboard',            'perm' => 'dashboard'],
                ['url' => '/admin/stats',     'icon' => 'fa-chart-pie',  'label' => 'Báo cáo Thống kê',    'perm' => 'stats'],
                
                ['section' => 'QUẢN LÝ TUYỂN SINH'],
                ['url' => '/admin/review',             'icon' => 'fa-user-graduate', 'label' => 'Xét duyệt Hồ sơ',    'perm' => 'candidate.view'],
                ['url' => '/admin/notifications',       'icon' => 'fa-bell',          'label' => 'Gửi Thông báo',      'perm' => 'candidate.view'],
                ['url' => '/admin/admission/results',   'icon' => 'fa-list-ol',       'label' => 'Kết quả Trúng tuyển','perm' => 'candidate.view'],
                ['url' => '/admin/reports',             'icon' => 'fa-file-export',   'label' => 'Xuất dữ liệu',       'perm' => 'report.export'],
                ['url' => '/admin/aptitude-scores',     'icon' => 'fa-music',         'label' => 'Điểm Năng khiếu',    'perm' => 'aptitude.view'],

                ['section' => 'CẤU HÌNH TUYỂN SINH'],
                ['url' => '/admin/master-data/sessions',   'icon' => 'fa-calendar-alt', 'label' => 'Đợt tuyển sinh',     'perm' => 'settings.edit'],
                ['url' => '/admin/admission/benchmarks',   'icon' => 'fa-sliders-h',    'label' => 'Thiết lập Điểm chuẩn','perm' => 'settings.edit'],
                ['url' => '#', 'icon' => 'fa-gavel', 'label' => 'Điều kiện & Quy tắc', 'perm' => 'settings.edit', 'submenu' => [
                     ['url' => '/admin/rules',                'label' => 'Điều kiện Xét tuyển'],
                     ['url' => '/admin/master-data/zones',    'label' => 'Cấu hình Vùng (Sư phạm)'],
                     ['url' => '/admin/settings/scoring',     'label' => 'Cấu hình Điểm'],
                ]],

                ['section' => 'DỮ LIỆU ĐÀO TẠO'],
                ['url' => '/admin/master-data/majors',       'icon' => 'fa-graduation-cap', 'label' => 'Ngành đào tạo',      'perm' => 'major.view'],
                ['url' => '/admin/master-data/combinations', 'icon' => 'fa-layer-group',    'label' => 'Tổ hợp xét tuyển',  'perm' => 'major.view'],
                ['url' => '/admin/master-data/subjects',     'icon' => 'fa-book',           'label' => 'Môn học',            'perm' => 'major.view'],

                ['section' => 'NỘI DUNG & TRƯỜNG'],
                ['url' => '/admin/posts',                'icon' => 'fa-newspaper', 'label' => 'Tin tức & Bài viết', 'perm' => 'posts.view'],
                ['url' => '/admin/master-data/schools',  'icon' => 'fa-school',    'label' => 'Trường THPT',        'perm' => 'major.view'],
                
                ['section' => 'HỆ THỐNG'],
                ['url' => '#', 'icon' => 'fa-users-cog', 'label' => 'Tài khoản & Phân quyền', 'perm' => 'role.view', 'submenu' => [
                     ['url' => '/admin/accounts', 'label' => 'Tài khoản Admin'],
                     ['url' => '/admin/roles',    'label' => 'Quản lý Vai trò'],
                ]],
                ['url' => '#', 'icon' => 'fa-cogs', 'label' => 'Cấu hình Hệ thống', 'perm' => 'settings.edit', 'submenu' => [
                     ['url' => '/admin/master-data/settings',    'label' => 'Cấu hình Chung'],
                     ['url' => '/admin/settings/email',          'label' => 'Cấu hình Email'],
                     ['url' => '/admin/settings/email-templates','label' => 'Mẫu Email'],
                ]],
                ['url' => '/admin/audit', 'icon' => 'fa-history', 'label' => 'Nhật ký Hoạt động', 'perm' => 'audit.view'],
            ];

            // Load current user once for sidebar permission checks
            static $_sidebarUser = null;
            if ($_sidebarUser === null && !empty($_SESSION['admin_id'])) {
                $_sidebarUser = (new \App\Models\QuanTriVien())->find($_SESSION['admin_id']);
            }

            // Closure: can this user see the given permission key?
            $canSee = function(string $perm) use ($_sidebarUser): bool {
                if (!$_sidebarUser) return false;
                return \App\Models\QuanTriVien::hasPermission($_sidebarUser, $perm);
            };

            // Render menu — only print a section header when at least one visible item follows
            $pendingSection = null;
            foreach ($menu as $item):
                if (isset($item['section'])):
                    $pendingSection = $item['section'];
                    continue;
                endif;

                // Permission gate
                $perm = $item['perm'] ?? null;
                if ($perm !== null && !$canSee($perm)) continue;

                // Now we have a visible item — flush the pending section header
                if ($pendingSection !== null): ?>
                    <div class="px-4 mt-6 mb-2 sidebar-section">
                        <p class="text-[10px] font-black text-sky-400 uppercase tracking-widest"><?= $pendingSection ?></p>
                    </div>
                <?php $pendingSection = null; endif;

                $isActive = strpos($currentUri, $item['url']) !== false && $item['url'] !== '#';
                if ($item['url'] === '/admin/dashboard' && $currentUri === '/TS/admin/dashboard') $isActive = true;
            ?>
                <?php if (isset($item['submenu'])): ?>
                    <div x-data="{ open: false }" class="mb-1">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 text-sky-100 hover:bg-white/5 hover:text-white group">
                            <div class="flex items-center">
                                <span class="w-6 text-center"><i class="fas <?= $item['icon'] ?> text-sky-400 group-hover:text-white transition-colors"></i></span>
                                <span class="ml-3 font-semibold sidebar-text"><?= $item['label'] ?></span>
                            </div>
                            <i class="fas fa-chevron-right text-[10px] transition-transform duration-200 text-sky-400 sidebar-text" :class="{'rotate-90': open}"></i>
                        </button>
                        <div x-show="open" x-cloak class="pl-11 pr-2 py-1 space-y-1 mt-1 sidebar-text">
                            <?php foreach ($item['submenu'] as $sub):
                                $isSubActive = strpos($currentUri, $sub['url']) !== false;
                            ?>
                                <a href="<?= url($sub['url']) ?>" class="block px-3 py-2 rounded-lg text-xs font-semibold transition-colors <?= $isSubActive ? 'bg-sky-500 text-white shadow-md' : 'text-sky-300 hover:text-white hover:bg-white/5' ?>">
                                    <?= $sub['label'] ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= url($item['url']) ?>" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 group mb-1 <?= $isActive ? 'bg-sky-500 text-white shadow-lg shadow-sky-900/50' : 'text-sky-100 hover:bg-white/5 hover:text-white' ?>">
                        <span class="w-6 text-center"><i class="fas <?= $item['icon'] ?> transition-colors <?= $isActive ? 'text-white' : 'text-sky-400 group-hover:text-white' ?>"></i></span>
                        <span class="ml-3 font-semibold sidebar-text"><?= $item['label'] ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <!-- Bottom Actions -->
        <div class="p-4 border-t border-white/10 bg-black/20 backdrop-blur-sm">
            <button @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)" 
                    class="w-full mb-2 py-2 text-xs font-bold text-sky-300 hover:text-white rounded-lg transition flex items-center justify-center">
                <i class="fas" :class="sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
                <span class="ml-2 sidebar-text">Thu gọn</span>
            </button>
            <a href="<?= url('/admin/logout') ?>" class="flex items-center justify-center w-full px-4 py-3 text-xs font-bold text-blue-200 bg-blue-900/20 hover:bg-blue-600 hover:text-white rounded-xl transition-all duration-300 border border-blue-900/30 group">
                <i class="fas fa-sign-out-alt mr-2 group-hover:-translate-x-1 transition-transform"></i> 
                <span class="sidebar-text">ĐĂNG XUẤT</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-content min-h-screen flex flex-col" :class="{ 'expanded': sidebarCollapsed }">
        
        <!-- Top Header -->
        <header class="h-16 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-slate-200 dark:border-gray-700 sticky top-0 z-30 px-8 flex justify-between items-center">
            <h2 class="text-xl font-bold text-slate-800 dark:text-white font-heading tracking-tight uppercase">Trường Đại học Hùng Vương (Mã trường THV)</h2>
            
            <div class="flex items-center space-x-4">
                <!-- Dark Mode Toggle -->
                <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" 
                        class="w-9 h-9 rounded-full flex items-center justify-center text-slate-500 dark:text-yellow-400 hover:bg-slate-100 dark:hover:bg-gray-700 transition">
                    <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
                </button>
                
                <!-- Notifications -->
                <button class="relative text-slate-400 hover:text-slate-600 dark:hover:text-white transition">
                    <i class="fas fa-bell text-lg"></i>
                    <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
                
                <div class="flex items-center space-x-3 pl-4 border-l border-slate-200 dark:border-gray-600">
                    <div class="text-right hidden md:block">
                        <p class="text-sm font-bold text-slate-700 dark:text-white leading-none"><?= $_SESSION['admin_name'] ?? 'Admin' ?></p>
                        <p class="text-[10px] uppercase font-bold text-slate-400 mt-1"><?= $_SESSION['admin_role'] ?? 'Staff' ?></p>
                    </div>
                    
                    <?php if (!empty($_SESSION['admin_avatar']) && file_exists($_SESSION['admin_avatar'])): ?>
                        <div class="w-9 h-9 rounded-full border border-slate-200 dark:border-gray-600 shadow-sm overflow-hidden">
                            <img src="<?= url('/' . $_SESSION['admin_avatar']) ?>" alt="Avatar" class="w-full h-full object-cover">
                        </div>
                    <?php else: ?>
                        <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-gray-700 flex items-center justify-center text-slate-600 dark:text-white font-bold border border-slate-200 dark:border-gray-600 shadow-sm">
                            <?= mb_substr($_SESSION['admin_name'] ?? 'A', 0, 1) ?>
                        </div>
                    <?php endif; ?>

                    <div class="relative group" x-data="{ open: false }">
                        <button @click="open = !open" class="ml-2 text-slate-400 hover:text-slate-600 dark:hover:text-white transition">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <!-- Dropdown -->
                        <div x-show="open" @click.away="open = false" x-cloak 
                             class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 py-2">
                            <a href="<?= url('/admin/profile') ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-user-circle mr-2 text-blue-500"></i> Hồ sơ Cá nhân
                            </a>
                            <a href="<?= url('/admin/profile') ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-key mr-2 text-emerald-500"></i> Đổi Mật khẩu
                            </a>
                            <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                            <a href="<?= url('/admin/logout') ?>" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                <i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dynamic Content -->
        <main class="flex-grow p-8 relative z-0">
            <?= $content ?? '' ?>
        </main>

        <!-- Footer -->
        <footer class="py-6 text-center text-xs font-medium text-slate-400 border-t border-slate-100 dark:border-gray-700 bg-white dark:bg-gray-900">
            <?php $v = include __DIR__ . '/../../../config/version.php'; ?>
            &copy; <?= date('Y') ?> <?= $v['name'] ?> (V <?= $v['version'] ?>)
        </footer>
    </div>

    <!-- Loading Overlay -->
    <div id="global-loading">
        <div class="spinner mb-4"></div>
        <p class="text-slate-800 dark:text-white font-bold animate-pulse text-lg uppercase tracking-widest">Đang xử lý dữ liệu...</p>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-20 right-4 z-[9999] space-y-2"></div>
    
    <script>
    // Toast Function
    function showToast(message, type = 'success', duration = 4000) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const colors = {
            success: 'bg-emerald-500 shadow-emerald-200/50',
            error: 'bg-rose-500 shadow-rose-200/50',
            warning: 'bg-amber-500 shadow-amber-200/50',
            info: 'bg-sky-500 shadow-sky-200/50'
        };
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        const toast = document.createElement('div');
        toast.className = `toast flex items-center px-6 py-4 rounded-2xl text-white font-bold text-sm shadow-xl ${colors[type]} transform translate-x-full transition-all duration-300`;
        toast.innerHTML = `<i class="fas ${icons[type]} mr-3 text-lg"></i> <span class="flex-grow">${message}</span>`;
        
        container.appendChild(toast);
        
        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full');
        });

        // Auto remove
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => toast.remove(), 500);
        }, duration);
    }

    // Global Loading Helpers
    const Loading = {
        show: () => {
            document.getElementById('global-loading').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        },
        hide: () => {
            document.getElementById('global-loading').style.display = 'none';
            document.body.style.overflow = '';
        }
    };
    
    // Show toast from URL params
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('msg') === 'saved') showToast('Đã lưu dữ liệu thành công!', 'success');
    if (urlParams.get('msg') === 'deleted') showToast('Đã xóa dữ liệu thành công!', 'info');
    if (urlParams.get('msg') === 'bulk_success') showToast(`Cập nhật thành công ${urlParams.get('count') || ''} hồ sơ!`, 'success');
    if (urlParams.get('error')) {
        showToast(decodeURIComponent(urlParams.get('error')), 'error');
    }

    // Auto-process email queue in background (non-blocking)
    fetch('<?= url("/cron/process_email_queue.php?key=hvu_cron_2024") ?>', { method: 'GET' }).catch(() => {});
    </script>

</body>
<script>
// Register Service Worker for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= url("/sw.js") ?>')
            .then(reg => console.log('SW Registered!', reg.scope))
            .catch(err => console.log('SW Failed', err));
    });
}
</script>
</html>
