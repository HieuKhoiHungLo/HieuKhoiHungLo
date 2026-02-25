<!DOCTYPE html>

<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#BE1E2D">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= isset($title) ? $title : 'Tuyển sinh Đại học Hùng Vương' ?></title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <link rel="icon" type="image/png" href="<?= url('/assets/img/icon-pwa.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('/assets/img/icon-pwa.png') ?>">
    
    <!-- Tailwind CSS (Local Build) -->
    <link rel="stylesheet" href="<?= url('/assets/css/tailwind.min.css') ?>">
    
    <!-- Optimized Fonts (display=swap for faster load) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        html, body {
            background-color: transparent !important;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .hvu-input {
            background: rgba(249, 250, 251, 0.8) !important;
            border: 1.5px solid #d1d5db !important;
            border-radius: 0.75rem !important;
            padding: 0.75rem 1rem !important;
            transition: all 0.2s ease-in-out !important;
            width: 100% !important;
            outline: none !important;
        }
        .hvu-input:focus {
            border-color: #BE1E2D !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(190, 30, 45, 0.1) !important;
        }
        .hvu-input-sm {
            background: rgba(249, 250, 251, 0.8) !important;
            border: 1.5px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            padding: 0.4rem 0.6rem !important;
            transition: all 0.2s ease-in-out !important;
            width: 100% !important;
            outline: none !important;
            font-size: 0.75rem !important;
        }
        .hvu-input-sm:focus {
            border-color: #BE1E2D !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(190, 30, 45, 0.1) !important;
        }
        .hvu-btn-primary {
            background-color: #BE1E2D;
            color: #ffffff;
            font-weight: 700;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(190, 30, 45, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .hvu-btn-primary:hover {
            background-color: #a01926;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(190, 30, 45, 0.3);
        }
        .hvu-btn-primary:active {
            transform: translateY(0);
        }
    </style>
</head>
<body class="text-gray-800 min-h-screen flex flex-col relative" style="background: transparent;">
    <canvas id="bg-canvas"></canvas>

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
                        <span class="text-gray-600 text-sm mr-2">Xin chào, <b><?= htmlspecialchars($_SESSION['user_name']) ?></b></span>
                        <!-- <a href="<?= url('/application/results') ?>" class="hvu-btn-primary hover:bg-red-700 text-white font-bold px-4 py-2 rounded-full shadow-md transition ml-2 text-sm">Tra cứu Kết quả</a> -->
                        <a href="<?= url('/application/index') ?>" class="hvu-btn-primary hover:bg-red-700 text-white font-bold px-4 py-2 rounded-full shadow-md transition ml-2 text-sm">Hồ sơ Xét tuyển</a>
                        <a href="<?= url('/profile/step1') ?>" class="text-gray-600 hover:text-hvu-red font-medium px-3 py-2 rounded-md hover:bg-gray-50 transition text-sm">Thông tin cá nhân</a>
                        <a href="<?= url('/logout') ?>" class="text-gray-500 hover:text-red-500 font-medium px-3 py-2 rounded-md hover:bg-gray-50 transition text-sm ml-1" title="Đăng xuất">
                            <i class="fas fa-sign-out-alt mr-1"></i> Đăng xuất
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
                                <div class="px-4 py-3 bg-gray-50 border-b flex justify-between items-center">
                                    <span class="font-bold text-gray-700"><i class="fas fa-bell mr-2"></i>Thông báo</span>
                                    <button id="mark-all-read" class="text-xs text-blue-600 hover:underline">Đánh dấu tất cả đã đọc</button>
                                </div>
                                <div id="notification-list" class="overflow-y-auto max-h-72">
                                    <div class="p-4 text-center text-gray-400 text-sm">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Đang tải...
                                    </div>
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
                    <a href="<?= url('/logout') ?>" class="block px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-lg">Đăng xuất</a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg text-center">Đăng nhập</a>
                    <a href="<?= url('/register') ?>" class="block px-4 py-3 bg-hvu-red text-white font-bold rounded-lg text-center shadow">Đăng ký ngay</a>
                <?php endif; ?>
            </div>
        </div>
    </header>



    <main class="flex-grow container mx-auto px-4 py-6 md:py-8">
