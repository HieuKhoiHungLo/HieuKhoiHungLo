<!DOCTYPE html>

<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="theme-color" content="#BE1E2D">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= isset($title) ? $title : 'Tuyển sinh Đại học Hùng Vương' ?></title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <link rel="icon" type="image/png" href="<?= url('/assets/img/icon-pwa.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('/assets/img/icon-pwa.png') ?>">
    
    <!-- Critical CSS (Local) -->
    <?php $tailwindPath = __DIR__ . '/../../../public/assets/css/tailwind.min.css'; ?>
    <link rel="stylesheet" href="<?= url('/assets/css/tailwind.min.css' . (file_exists($tailwindPath) ? '?v=' . filemtime($tailwindPath) : '')) ?>">
    
    <!-- HVU Components CSS (extracted from inline for browser caching) -->
    <?php $hvuCssPath = __DIR__ . '/../../../public/assets/css/hvu-components.css'; ?>
    <link rel="stylesheet" href="<?= url('/assets/css/hvu-components.css' . (file_exists($hvuCssPath) ? '?v=' . filemtime($hvuCssPath) : '')) ?>">
    
    <!-- DNS Prefetch + Preconnect for external resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    
    <!-- Google Fonts (non-render-blocking with media swap) -->
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&family=Inter:wght@400;600&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&family=Inter:wght@400;600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    
    <!-- Font Awesome (preloaded for faster icon rendering) -->
    <link rel="preload" as="style" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    
    <!-- Fallback for browsers with JS disabled -->
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </noscript>
</head>
<body class="text-gray-800 min-h-screen flex flex-col relative" style="background: transparent;">

    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <a href="<?= url('/') ?>" class="flex items-center space-x-3">
                    <img loading="lazy" src="<?= url('/assets/img/Logo.png') ?>" alt="Logo HVU" class="h-10 md:h-12">
                    <div class="hidden md:block">
                        <h1 class="text-lg md:text-xl font-bold text-hvu-red uppercase leading-tight">Trường Đại học Hùng Vương - Mã trường THV</h1>
                        <p class="text-xs md:text-sm text-gray-600">Cổng Thông tin tuyển sinh</p>
                    </div>
                </a>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center space-x-1 ml-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- <a href="<?= url('/application/results') ?>" class="hvu-btn-primary hover:bg-red-700 text-white font-bold px-4 py-2 rounded-full shadow-md transition ml-2 text-sm">Tra cứu Kết quả</a> -->
                        <a href="<?= url('/application/index') ?>" class="hvu-btn-primary hover:bg-red-700 text-white font-bold px-4 py-2 rounded-full shadow-md transition ml-2 text-sm">
                            <i class="fas fa-file-alt mr-1.5 opacity-80"></i> Hồ sơ Xét tuyển
                        </a>
                    <?php else: ?>
                        <a href="<?= url('/login') ?>" class="text-gray-700 hover:text-hvu-red font-medium px-4 py-2">Đăng nhập</a>
                        <a href="<?= url('/register') ?>" class="bg-hvu-red text-white px-5 py-2 rounded-full hover:bg-red-700 transition shadow-md font-medium">Đăng ký ngay</a>
                    <?php endif; ?>
                </nav>

                <!-- Action Icons (Notification & Mobile Menu) -->
                <div class="flex items-center space-x-2 md:ml-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Notification Bell (Visible on All Devices) -->
                        <div class="relative" id="notification-container">
                            <button id="notification-bell" class="relative text-gray-500 hover:text-hvu-red px-2 py-2 rounded-md hover:bg-gray-50 transition text-xl">
                                <i class="fas fa-bell"></i>
                                <span id="notification-badge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 flex items-center justify-center rounded-full">0</span>
                            </button>
                            <!-- Dropdown -->
                            <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-100 z-50 max-h-96 overflow-hidden">
                                <div class="px-4 py-3 bg-white border-b">
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="text-xl font-bold text-gray-900 tracking-tight">Thông báo</span>
                                        <button id="mark-all-read" class="text-xs text-blue-600 hover:text-blue-800 font-semibold transition">Đánh dấu tất cả đã đọc</button>
                                    </div>
                                    <!-- Tabs Facebook-style -->
                                    <div class="flex space-x-2">
                                        <button id="tab-all" onclick="switchNotifTab('all')" class="notif-tab active px-3 py-1.5 text-sm font-semibold rounded-full transition-all">
                                            Tất cả
                                        </button>
                                        <button id="tab-unread" onclick="switchNotifTab('unread')" class="notif-tab px-3 py-1.5 text-sm font-semibold rounded-full transition-all">
                                            Chưa đọc
                                        </button>
                                    </div>
                                </div>
                                <style>
                                    .notif-tab {
                                        color: #65676B;
                                    }
                                    .notif-tab:hover {
                                        background-color: #F2F3F5;
                                    }
                                    .notif-tab.active {
                                        background-color: #E7F3FF;
                                        color: #1877F2;
                                    }
                                    #notification-list::-webkit-scrollbar {
                                        width: 6px;
                                    }
                                    #notification-list::-webkit-scrollbar-track {
                                        background: transparent;
                                    }
                                    #notification-list::-webkit-scrollbar-thumb {
                                        background: #d1d5db;
                                        border-radius: 10px;
                                    }
                                </style>
                                <div id="notification-list" class="overflow-y-auto max-h-72">
                                    <div class="p-4 text-center text-gray-400 text-sm">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Đang tải...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Avatar Menu (Desktop Only) -->
                        <div class="relative hidden md:block ml-2" id="user-menu-container">
                            <?php 
                            // Lấy thông tin người dùng hiện tại để lấy ảnh thẻ
                            $currentAvatar = '';
                            if (isset($_SESSION['user_id'])) {
                                try {
                                    $__db = \App\Core\Database::getInstance()->getConnection();
                                    $__stmt = $__db->prepare("SELECT anh_dai_dien FROM thi_sinh WHERE id = ?");
                                    $__stmt->execute([$_SESSION['user_id']]);
                                    $currentAvatar = $__stmt->fetchColumn() ?: '';
                                } catch (\Exception $e) { }
                            }
                            $avatarUrl = $currentAvatar ? url('/' . $currentAvatar) : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['user_name']).'&background=E5E7EB&color=374151&bold=true';
                            // Đối với Admin
                            if (isset($_SESSION['admin_id'])) {
                                $avatarUrl = !empty($_SESSION['admin_avatar']) && file_exists($_SESSION['admin_avatar']) ? url('/' . $_SESSION['admin_avatar']) : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['admin_name'] ?? 'Admin').'&background=4f46e5&color=fff';
                            }
                            ?>
                            <!-- Trigger Button -->
                            <button id="user-avatar-btn" class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition focus:outline-none focus:ring-2 focus:ring-hvu-red/30 relative">
                                <img src="<?= $avatarUrl ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border border-gray-200" onerror="this.src='<?= url('/assets/img/default-avatar.png') ?>'">
                                <!-- Small arrow at bottom right -->
                                <div class="absolute -bottom-1 -right-1 bg-gray-200 rounded-full w-4 h-4 flex items-center justify-center shadow-sm border-2 border-white">
                                    <i class="fas fa-chevron-down text-[8px] text-gray-700"></i>
                                </div>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div id="user-menu-dropdown" class="hidden absolute right-0 mt-3 w-[350px] bg-white rounded-xl shadow-[0_4px_25px_rgba(0,0,0,0.15)] border border-gray-100 z-50 overflow-hidden p-3" style="font-family: 'Inter', sans-serif;">
                                <!-- User Header Card -->
                                <div class="bg-white rounded-xl shadow-[0_2px_12px_rgba(0,0,0,0.06)] border border-gray-100 mb-2 p-1.5 relative">
                                    <div class="flex items-center space-x-3 p-2.5 rounded-lg hover:bg-gray-50 transition cursor-pointer" onclick="window.location.href='<?= url('/profile/step1') ?>'">
                                        <div class="w-10 h-10 rounded-full flex-shrink-0 border border-gray-200 overflow-hidden shadow-sm">
                                            <img src="<?= $avatarUrl ?>" alt="Avatar" class="w-full h-full object-cover" onerror="this.src='<?= url('/assets/img/default-avatar.png') ?>'">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-bold text-gray-900 text-[16px] truncate leading-tight"><?= htmlspecialchars($_SESSION['user_name']) ?></h3>
                                        </div>
                                    </div>
                                    <div class="px-2 pb-2 mt-1">
                                        <hr class="border-gray-200 mb-3">
                                        <a href="<?= url('/profile/step1') ?>" class="block w-full py-1.5 text-center bg-gray-100 hover:bg-gray-200 text-blue-600 font-semibold text-[14px] rounded-lg transition duration-200">
                                            Xem hồ sơ cá nhân
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Menu Items -->
                                <div class="space-y-1 mt-3">
                                    <a href="<?= url('/profile/step1') ?>" class="flex items-center px-2 py-2 rounded-lg hover:bg-gray-100 transition duration-200 text-gray-900 font-medium text-[15px] group">
                                        <div class="w-9 h-9 rounded-full bg-gray-200 group-hover:bg-gray-300 flex items-center justify-center mr-3 transition duration-200">
                                            <i class="fas fa-cog text-[16px]"></i>
                                        </div>
                                        <span class="flex-1">Thông tin cá nhân</span>
                                        <i class="fas fa-chevron-right text-gray-400 text-[13px]"></i>
                                    </a>
                                    
                                    <a href="<?= url('/profile/change-password') ?>" class="flex items-center px-2 py-2 rounded-lg hover:bg-gray-100 transition duration-200 text-gray-900 font-medium text-[15px] group">
                                        <div class="w-9 h-9 rounded-full bg-gray-200 group-hover:bg-gray-300 flex items-center justify-center mr-3 transition duration-200">
                                            <i class="fas fa-shield-alt text-[16px]"></i>
                                        </div>
                                        <span class="flex-1">Đổi mật khẩu</span>
                                        <i class="fas fa-chevron-right text-gray-400 text-[13px]"></i>
                                    </a>

                                    <a href="<?= url('/logout') ?>" class="flex items-center px-2 py-2 rounded-lg hover:bg-gray-100 transition duration-200 text-gray-900 font-medium text-[15px] group">
                                        <div class="w-9 h-9 rounded-full bg-gray-200 group-hover:bg-gray-300 flex items-center justify-center mr-3 transition duration-200">
                                            <i class="fas fa-sign-out-alt text-[16px]"></i>
                                        </div>
                                        <span class="flex-1">Đăng xuất</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-btn" class="md:hidden text-gray-600 hover:text-hvu-red focus:outline-none ml-2">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu Panel -->
            <div id="mobile-menu" class="hidden md:hidden mt-4 pb-4 border-t border-gray-100 flex flex-col space-y-2">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="py-3 px-2 text-gray-500 text-sm border-b border-gray-100 mb-2">
                        Xin chào, <b class="text-gray-800"><?= htmlspecialchars($_SESSION['user_name']) ?></b>
                    </div>
                    <!-- <a href="<?= url('/application/results') ?>" class="block px-4 py-3 bg-red-50 text-hvu-red font-bold rounded-lg mb-1">Tra cứu Kết quả</a> -->
                    <a href="<?= url('/application/index') ?>" class="block px-4 py-3 bg-red-50 text-hvu-red font-bold rounded-lg mb-1">Hồ sơ Xét tuyển</a>
                    <a href="<?= url('/profile/step1') ?>" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">Thông tin cá nhân</a>
                    <a href="<?= url('/profile/change-password') ?>" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg font-medium text-blue-600 bg-blue-50">Đổi mật khẩu</a>
                    <a href="<?= url('/logout') ?>" class="block px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg">Đăng xuất</a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg text-center">Đăng nhập</a>
                    <a href="<?= url('/register') ?>" class="block px-4 py-3 bg-hvu-red text-white font-bold rounded-lg text-center shadow">Đăng ký ngay</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle Dropdown Menu Avatar
        const userBtn = document.getElementById('user-avatar-btn');
        const userDropdown = document.getElementById('user-menu-dropdown');
        const notifDropdown = document.getElementById('notification-dropdown');
        
        if (userBtn && userDropdown) {
            userBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                // Close notification if it's open
                if (notifDropdown && !notifDropdown.classList.contains('hidden')) {
                    notifDropdown.classList.add('hidden');
                }
                userDropdown.classList.toggle('hidden');
            });
            
            // Close dropdowns on outside click
            document.addEventListener('click', function(e) {
                if (!userDropdown.contains(e.target) && e.target !== userBtn) {
                    userDropdown.classList.add('hidden');
                }
            });
        }
    });
    </script>

    <main class="flex-grow container mx-auto px-4 py-6 md:py-8">
