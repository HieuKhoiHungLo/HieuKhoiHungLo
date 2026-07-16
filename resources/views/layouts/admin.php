<!DOCTYPE html>
<html lang="vi" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true', mobileMenuOpen: false }"
    :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0066FF">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <link rel="icon" type="image/png" href="<?= url('/assets/img/Logo.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('/assets/img/Logo.png') ?>">
    <title><?= $title ?? 'Admin Portal - HVU' ?></title>

    <!-- Preconnect to external domains to reduce latency -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

    <!-- Critical CSS — Tailwind (local, high priority) -->
    <?php
    $tailwindFile = __DIR__ . '/../../../public/assets/css/tailwind.min.css';
    $tailwindVer = file_exists($tailwindFile) ? filemtime($tailwindFile) : time();
    ?>
    <link rel="stylesheet" href="<?= url('/assets/css/tailwind.min.css?v=' . $tailwindVer) ?>" fetchpriority="high">

    <!-- Google Fonts — font-display=swap prevents FOIT -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome — non-blocking (render won't wait for icons) -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"></noscript>

    <!-- Alpine.js self-hosted v3.14.9 — tránh phụ thuộc CDN bên ngoài -->
    <script defer src="<?= url('/assets/js/alpine-collapse.min.js') ?>"></script>
    <script defer src="<?= url('/assets/js/alpine.min.js') ?>"></script>

    <!-- jQuery & DataTables — Chỉ load khi view yêu cầu (giảm tải cho trang review, dashboard) -->
    <?php if (isset($needsDataTables) && $needsDataTables === true): ?>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <?php endif; ?>

    <!-- Prefetch next candidate for instant navigation -->
    <?php if (isset($nextCCCD) && !empty($nextCCCD)): ?>
        <link rel="prefetch" href="<?= url('/admin/review?cccd=' . $nextCCCD) ?>">
    <?php endif; ?>

    <!-- Design System -->
    <style>
        :root {
            --hvu-primary: #0066FF;
            --hvu-primary-dark: #0050CC;
            --hvu-accent: #E11D48;
            --sidebar-width: 280px;
            --sidebar-collapsed: 70px;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        .font-heading {
            font-family: 'Montserrat', sans-serif;
        }

        .admin-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #003D99 0%, #001A4D 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Sidebar desktop collapses */
        @media (min-width: 1024px) {
            .admin-sidebar.collapsed {
                width: var(--sidebar-collapsed);
            }

            .admin-sidebar.collapsed .sidebar-text {
                display: none;
            }

            .admin-sidebar.collapsed .sidebar-section {
                display: none;
            }

            .main-content {
                margin-left: var(--sidebar-width);
                transition: margin-left 0.3s ease;
            }

            .main-content.expanded {
                margin-left: var(--sidebar-collapsed);
            }
        }

        /* Mobile specific sidebar */
        @media (max-width: 1023px) {
            .admin-sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                bottom: 0;
                z-index: 100;
                width: 280px;
            }

            .admin-sidebar.mobile-open {
                left: 0;
            }

            .main-content {
                margin-left: 0 !important;
            }

            .sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 90;
                backdrop-filter: blur(2px);
            }
        }

        /* Dark Mode */
        .dark body {
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .dark .bg-white {
            background-color: #1e293b !important;
        }

        .dark .bg-slate-50,
        .dark .bg-gray-50 {
            background-color: #0f172a !important;
        }

        .dark .text-slate-800 {
            color: #f8fafc !important;
        }

        .dark .border-slate-100,
        .dark .border-slate-200 {
            border-color: #334155 !important;
        }

        /* Custom Scrollbar for Sidebar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Global Loading Overlay */
        #global-loading {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(4px);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .dark #global-loading {
            background: rgba(15, 23, 42, 0.8);
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(0, 102, 255, 0.1);
            border-top: 3px solid var(--hvu-primary);
            border-radius: 50%;
            animation: hvu-spin 0.8s linear infinite;
        }

        @keyframes hvu-spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="text-slate-800 antialiased bg-slate-50 min-h-screen" :class="{ 'dark': darkMode }">

    <!-- Mobile Overlay -->
    <div x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileMenuOpen = false"
        class="sidebar-overlay lg:hidden" x-cloak></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar fixed left-0 top-0 h-full text-white flex flex-col z-[100] shadow-2xl"
        :class="{ 'collapsed': sidebarCollapsed, 'mobile-open': mobileMenuOpen }">
        <!-- Brand -->
        <div class="h-16 flex items-center px-5 border-b border-white/10 bg-black/10 flex-shrink-0">
            <a href="<?= url('/') ?>" class="flex items-center group/brand overflow-hidden">
                <div class="flex items-center justify-center flex-shrink-0 transition-transform group-hover/brand:scale-105 duration-300">
                    <img src="<?= url('/assets/img/Logo.png') ?>" alt="HVU Logo" class="h-9 w-auto object-contain">
                </div>
                <div class="ml-3 sidebar-text min-w-0 transition-opacity group-hover/brand:opacity-80">
                    <h1 class="font-black text-[9px] tracking-wider text-white/70 font-heading uppercase leading-none">QUẢN TRỊ HỆ THỐNG</h1>
                    <p class="text-[13px] text-sky-200 uppercase tracking-widest font-bold leading-tight mt-0.5">TUYỂN SINH</p>
                </div>
            </a>
            <!-- Mobile Close Button -->
            <button @click="mobileMenuOpen = false" class="lg:hidden ml-auto text-white/50 hover:text-white">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-grow py-3 px-3 overflow-y-auto custom-scrollbar">
            <?php
            $currentUri = $_SERVER['REQUEST_URI'];
            // ... (rest of menu structure remains same)
            ?>
            <?php
            // Lấy menu động từ Database
            $dbMenus = (new \App\Models\Menu())->getActiveMenus('admin_sidebar');
            $menuGroups = [];
            
            if (!empty($dbMenus)) {
                foreach ($dbMenus as $mGroup) {
                    $items = [];
                    foreach ($mGroup['children'] ?? [] as $mItem) {
                        $items[] = [
                            'url' => $mItem['url'],
                            'icon' => $mItem['icon'] ?: 'fa-circle',
                            'label' => $mItem['title'],
                            'perm' => $mItem['permission_required']
                        ];
                    }
                    
                    // Nếu là menu cha không có con, tự thêm chính nó làm 1 item
                    if (empty($items) && !empty($mGroup['url']) && $mGroup['url'] !== '#') {
                        $items[] = [
                            'url' => $mGroup['url'],
                            'icon' => $mGroup['icon'] ?: 'fa-circle',
                            'label' => $mGroup['title'],
                            'perm' => $mGroup['permission_required']
                        ];
                    }

                    $menuGroups[] = [
                        'group' => mb_strtoupper($mGroup['title'], 'UTF-8'),
                        'icon' => $mGroup['icon'] ?: 'fa-folder',
                        'items' => $items,
                        'perm' => $mGroup['permission_required']
                    ];
                }
            } else {
                // Fallback nếu chưa có dữ liệu trong DB
                $menuGroups = [
                    [
                        'group' => 'HỆ THỐNG (Chưa có Menu động)',
                        'icon'  => 'fa-exclamation-triangle',
                        'items' => [
                            ['url' => '/admin/menus?position=admin_sidebar', 'icon' => 'fa-list', 'label' => 'Cấu hình Menu Sidebar', 'perm' => 'settings.edit'],
                            ['url' => '/admin/dashboard', 'icon' => 'fa-home', 'label' => 'Về Dashboard', 'perm' => 'dashboard'],
                        ]
                    ]
                ];
            }

            // Load current user once for sidebar permission checks - Prioritize cached session
            static $_sidebarUser = null;
            if ($_sidebarUser === null && !empty($_SESSION['admin_id'])) {
                if (isset($_SESSION['_cached_admin_user_' . $_SESSION['admin_id']])) {
                    $_sidebarUser = $_SESSION['_cached_admin_user_' . $_SESSION['admin_id']];
                } else {
                    $_sidebarUser = (new \App\Models\QuanTriVien())->find($_SESSION['admin_id']);
                    if ($_sidebarUser) {
                        $_SESSION['_cached_admin_user_' . $_SESSION['admin_id']] = $_sidebarUser;
                    }
                }
            }
            $canSee = function (string $perm) use ($_sidebarUser): bool {
                if (!$_sidebarUser) return false;
                return \App\Models\QuanTriVien::hasPermission($_sidebarUser, $perm);
            };

            foreach ($menuGroups as $gi => $group):
                $userRole = mb_strtolower(trim($_SESSION['admin_role'] ?? ''), 'UTF-8');
                $userRoleId = $_SESSION['admin_role_id'] ?? 0;
                $isCanBo = ($userRoleId == 2 || in_array($userRole, ['cán bộ xét tuyển', 'can bo xet tuyen']));

                if ($isCanBo && in_array(mb_strtoupper($group['group'], 'UTF-8'), [
                    'XÉT TUYỂN LỌC ẢO', 
                    'QUẢN LÝ THI NĂNG KHIẾU', 
                    'TIN TỨC & THÔNG BÁO'
                ])) {
                    continue;
                }

                // Filter items by permission (Group level permission check first)
                if (!empty($group['perm']) && !$canSee($group['perm'])) {
                    continue;
                }

                // Filter items by permission
                $visibleItems = array_filter($group['items'], function ($item) use ($canSee, $userRole, $userRoleId) {
                    $perm = $item['perm'] ?? null;
                    $isVisible = $perm === null || $canSee($perm);
                    
                    if (($userRoleId == 2 || in_array($userRole, ['cán bộ xét tuyển', 'can bo xet tuyen'])) && isset($item['label']) && $item['label'] === 'Xét tuyển Lọc ảo') {
                        return false;
                    }
                    
                    return $isVisible;
                });
                if (empty($visibleItems)) continue;

                // Check if any item in this group is active
                $groupActive = false;
                foreach ($visibleItems as $item) {
                    $itemPath = parse_url(url($item['url']), PHP_URL_PATH);
                    $reqPath = parse_url($currentUri, PHP_URL_PATH);
                    
                    if ($reqPath === $itemPath) {
                        $groupActive = true;
                        break;
                    }
                }
            ?>
                <div x-data="{ open: <?= $groupActive ? 'true' : 'false' ?> }" class="mb-1">
                    <!-- Group Header -->
                    <button @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-[11px] font-black uppercase tracking-wider rounded-xl transition-all duration-200 group
                                   <?= $groupActive ? 'bg-white/5 text-white font-bold' : 'text-sky-300 hover:bg-white/5 hover:text-white' ?>">
                        <div class="flex items-center">
                            <span class="w-5 text-center"><i class="fas <?= $group['icon'] ?> text-xs <?= $groupActive ? 'text-white drop-shadow-[0_0_8px_rgba(255,255,255,0.5)]' : 'text-sky-400/70 group-hover:text-sky-300' ?> transition-colors"></i></span>
                            <span class="ml-3 sidebar-text"><?= $group['group'] ?></span>
                        </div>
                        <i class="fas fa-chevron-down text-[8px] transition-transform duration-200 sidebar-text <?= $groupActive ? 'text-sky-300' : 'text-sky-400/50' ?>" :class="{'rotate-180': open}"></i>
                    </button>

                    <!-- Group Items -->
                    <div x-show="open" x-collapse x-cloak class="mt-0.5 space-y-0.5 sidebar-text">
                        <?php foreach ($visibleItems as $item):
                            $itemPath = parse_url(url($item['url']), PHP_URL_PATH);
                            $reqPath = parse_url($currentUri, PHP_URL_PATH);
                            $isActive = ($reqPath === $itemPath);
                        ?>
                            <a href="<?= url($item['url']) ?>"
                                class="flex items-center pl-10 pr-4 py-2 text-[13px] font-medium rounded-lg transition-all duration-150
                                      <?= $isActive
                                            ? 'bg-sky-500/90 text-white font-bold shadow-lg shadow-sky-900/20'
                                            : 'text-sky-100/80 hover:bg-white/5 hover:text-white' ?>">
                                <span class="w-5 text-center mr-2"><i class="fas <?= $item['icon'] ?> text-[11px] <?= $isActive ? 'text-white' : 'text-sky-400/60' ?>"></i></span>
                                <?= $item['label'] ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>

        <!-- Bottom Actions -->
        <div class="p-3 border-t border-white/10 bg-black/20 flex-shrink-0">
            <button @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)"
                class="w-full mb-2 py-2 text-xs font-bold text-sky-300 hover:text-white rounded-lg transition hidden lg:flex items-center justify-center">
                <i class="fas" :class="sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
                <span class="ml-2 sidebar-text">Thu gọn</span>
            </button>
            <a href="<?= url('/admin/logout') ?>" class="flex items-center justify-center w-full px-4 py-2.5 text-xs font-bold text-blue-200 bg-blue-900/20 hover:bg-blue-600 hover:text-white rounded-xl transition-all duration-300 border border-blue-900/30 group">
                <i class="fas fa-sign-out-alt mr-2 group-hover:-translate-x-1 transition-transform"></i>
                <span class="sidebar-text">ĐĂNG XUẤT</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-content min-h-screen flex flex-col" :class="{ 'expanded': sidebarCollapsed }">

        <!-- Top Header -->
        <header class="h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 px-4 lg:px-8 flex justify-between items-center">
            <div class="flex items-center overflow-hidden">
                <!-- Hamburger Menu Component -->
                <button @click="mobileMenuOpen = true" class="block lg:hidden mr-4 text-slate-500 hover:text-blue-600 transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="text-sm lg:text-xl font-bold text-slate-800 dark:text-white font-heading tracking-tight uppercase truncate">TRANG QUẢN TRỊ HỆ THỐNG TUYỂN SINH</h2>
            </div>

            <div class="flex items-center space-x-2 lg:space-x-4">
                <!-- Dark Mode Toggle -->
                <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                    class="w-9 h-9 rounded-full flex items-center justify-center text-slate-500 dark:text-yellow-400 hover:bg-slate-100 dark:hover:bg-gray-700 transition">
                    <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
                </button>

                <!-- Notifications -->
                <div class="relative" id="admin-notif-container">
                    <button id="admin-notif-bell" class="relative text-slate-400 hover:text-slate-600 dark:hover:text-white transition">
                        <i class="fas fa-bell text-lg"></i>
                        <span id="admin-notif-badge" class="hidden absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">0</span>
                    </button>
                    <!-- Dropdown -->
                    <div id="admin-notif-dropdown" class="hidden absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 z-50 max-h-[500px] overflow-hidden">
                        <div class="px-5 py-3 bg-gradient-to-r from-[#0066FF] to-blue-700 flex justify-between items-center rounded-t-2xl">
                            <span class="font-bold text-white text-sm"><i class="fas fa-bell mr-2"></i>Thông báo đã gửi</span>
                            <a href="<?= url('/admin/notifications') ?>" class="text-[10px] text-sky-200 hover:text-white font-bold uppercase tracking-wider">Xem tất cả</a>
                        </div>
                        <div id="admin-notif-list" class="overflow-y-auto max-h-80">
                            <div class="p-4 text-center text-gray-400 text-sm">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Đang tải...
                            </div>
                        </div>
                    </div>
                </div>

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
        <main class="flex-grow <?= (isset($mode) && $mode === 'review') ? 'p-4 pt-1' : 'p-4 lg:p-6 pt-1 lg:pt-2' ?> relative z-0">
            <?= $content ?? '' ?>
        </main>

        <!-- Footer -->
        <footer class="py-2.5 text-center text-[10px] font-medium text-slate-400 border-t border-slate-100 dark:border-gray-700 bg-white dark:bg-gray-900">
            <?php $v = include __DIR__ . '/../../../config/version.php'; ?>
            &copy; <?= date('Y') ?> <?= $v['name'] ?> (V <?= $v['version'] ?>)
        </footer>
    </div>

    <!-- Loading Overlay -->
    <div id="global-loading" style="display: none;">
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
        window.Loading = {
            show: () => {
                const overlay = document.getElementById('global-loading');
                if (overlay) {
                    overlay.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            },
            hide: () => {
                const overlay = document.getElementById('global-loading');
                if (overlay) {
                    overlay.style.display = 'none';
                    document.body.style.overflow = '';
                }
            }
        };

        // Show toast from URL params
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'saved') showToast('Đã lưu dữ liệu thành công!', 'success');
        if (urlParams.get('msg') === 'deleted') showToast('Đã xóa hồ sơ thành công!', 'info');
        if (urlParams.get('msg') === 'bulk_success') showToast(`Cập nhật thành công ${urlParams.get('count') || ''} hồ sơ!`, 'success');
        
        // Handle common success patterns
        if (urlParams.get('success') === 'password_changed') {
            showToast('Đổi mật khẩu thành công! Thông báo đã được gửi đến email thí sinh.', 'success');
        } else if (urlParams.get('success') && urlParams.get('msg') === 'queued') {
            const count = urlParams.get('count') || '0';
            showToast(`Đã đưa ${count} email vào hàng đợi gửi thư trúng tuyển.`, 'success');
        } else if (urlParams.get('success')) {
            const successVal = urlParams.get('success');
            // Nếu success=1 thì hiển thị thông báo mặc định, nếu là text thì hiển thị text đó
            const message = successVal === '1' ? 'Thao tác thành công!' : decodeURIComponent(successVal);
            showToast(message, 'success');
        }
        
        if (urlParams.get('error')) {
            showToast(decodeURIComponent(urlParams.get('error')), 'error');
        }

        // Admin Notification Dropdown Logic
        const adminBell = document.getElementById('admin-notif-bell');
        const adminDropdown = document.getElementById('admin-notif-dropdown');
        const adminBadge = document.getElementById('admin-notif-badge');
        const adminNotifList = document.getElementById('admin-notif-list');

        if (adminBell && adminDropdown) {
            // Toggle dropdown
            adminBell.addEventListener('click', (e) => {
                e.stopPropagation();
                const isHidden = adminDropdown.classList.contains('hidden');
                adminDropdown.classList.toggle('hidden');

                if (isHidden) {
                    fetchAdminNotifications();
                }
            });

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!adminDropdown.contains(e.target) && e.target !== adminBell && !adminBell.contains(e.target)) {
                    adminDropdown.classList.add('hidden');
                }
            });

            // Fetch notifications
            function fetchAdminNotifications() {
                adminNotifList.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i> Đang tải...</div>';

                fetch('<?= url("/admin/notifications/api") ?>')
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success || !data.notifications || data.notifications.length === 0) {
                            adminNotifList.innerHTML = '<div class="p-6 text-center text-gray-400 text-sm"><i class="fas fa-bell-slash text-2xl mb-2"></i><p>Chưa có thông báo nào được gửi.</p></div>';
                            adminBadge.classList.add('hidden');
                            return;
                        }

                        if (data.total > 0) {
                            adminBadge.textContent = data.total > 9 ? '9+' : data.total;
                            adminBadge.classList.remove('hidden');
                        }

                        const typeIcons = {
                            'info': '<i class="fas fa-info text-blue-500"></i>',
                            'warning': '<i class="fas fa-exclamation-triangle text-yellow-500"></i>',
                            'success': '<i class="fas fa-check text-emerald-500"></i>',
                            'important': '<i class="fas fa-fire text-red-500"></i>'
                        };

                        adminNotifList.innerHTML = data.notifications.map(n => {
                            const icon = typeIcons[n.type] || typeIcons.info;
                            const date = new Date(n.created_at).toLocaleDateString('vi-VN', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                            let targetLabel = '';
                            if (n.target_type === 'all') targetLabel = '<span class="text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-500 px-2 py-0.5 rounded ml-2">Tất cả</span>';
                            else if (n.target_type === 'individual') targetLabel = `<span class="text-[10px] bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 px-2 py-0.5 rounded ml-2">Thí sinh: ${n.target_id}</span>`;
                            else if (n.target_type === 'session') targetLabel = `<span class="text-[10px] bg-sky-50 dark:bg-sky-900/40 text-sky-600 px-2 py-0.5 rounded ml-2">Đợt: ${n.target_id}</span>`;

                            return `
                        <div class="px-5 py-3 border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <div class="flex items-start">
                                <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mr-3 flex-shrink-0 mt-0.5">
                                    ${icon}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start mb-1">
                                        <h4 class="font-bold text-slate-800 dark:text-white text-sm truncate pr-2">${n.title}</h4>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed mb-1">${n.content.replace(/<[^>]*>/g, '')}</p>
                                    <div class="flex items-center text-[10px] text-slate-400 mt-1">
                                        <i class="fas fa-clock mr-1"></i> ${date} 
                                        ${targetLabel}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                        }).join('');
                    })
                    .catch(err => {
                        adminNotifList.innerHTML = '<div class="p-4 text-center text-red-400 text-sm">Lỗi kết nối.</div>';
                    });
            }

            // DO NOT auto-fetch on load -> prevents useless HTTP request for every admin page view.
            // Badge is updated only when user clicks bell or explicitly reloaded.
            // fetchAdminNotifications(); 
        }
        // Auto-process email queue in background (non-blocking) via API Route
        // Only 1 tab (leader) is active to prevent redundant/parallel requests.
        const LEADER_KEY = 'email_queue_leader_id';
        const LEADER_PING_KEY = 'email_queue_leader_ping';
        const TAB_ID = Math.random().toString(36).substring(2, 15);

        function checkLeadership() {
            const now = Date.now();
            const leaderId = localStorage.getItem(LEADER_KEY);
            const leaderPing = parseInt(localStorage.getItem(LEADER_PING_KEY) || '0', 10);

            // If no leader, leader ping is stale (> 12 seconds), or current tab is leader, claim/renew leadership
            if (!leaderId || (now - leaderPing > 12000) || leaderId === TAB_ID) {
                localStorage.setItem(LEADER_KEY, TAB_ID);
                localStorage.setItem(LEADER_PING_KEY, now.toString());
                return true;
            }
            return false;
        }

        function triggerEmailQueue() {
            if (!checkLeadership()) {
                // Not the leader? Check leadership status again in 15 seconds
                setTimeout(triggerEmailQueue, 15000);
                return;
            }

            fetch('<?= url("/api/cron/process_email_queue?key=" . ($_ENV["CRON_SECRET_KEY"] ?? "")) ?>', {
                method: 'GET',
                keepalive: true
            })
            .then(response => response.json())
            .then(data => {
                // Renew leadership ping on successful response to keep control
                localStorage.setItem(LEADER_PING_KEY, Date.now().toString());

                // If remaining pending emails exist, run again in 15 seconds.
                // Otherwise check again in 10 minutes.
                let nextRun = (data.remaining > 0) ? 15000 : 600000;
                setTimeout(triggerEmailQueue, nextRun);
            })
            .catch(() => {
                // On error, check again in 1 minute
                setTimeout(triggerEmailQueue, 60000);
            });
        }

        // Initial delay
        setTimeout(triggerEmailQueue, 3000);
        // Auto-trigger scheduled backup (non-blocking, runs once per page load)
        // The API checks: is backup enabled? correct hour? already ran today?
        setTimeout(() => {
            fetch('<?= url("/api/cron/backup?key=" . ($_ENV["CRON_SECRET_KEY"] ?? "")) ?>', {
                method: 'GET', keepalive: true
            }).catch(() => {});
        }, 10000);
    </script>
    <!-- Global Modals -->
    <?php include __DIR__ . '/../admin/partials/_modals.php'; ?>
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