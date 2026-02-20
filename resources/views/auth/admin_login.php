<?php $title = 'Đăng nhập Quản trị - HVU'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= url('/public/assets/css/tailwind.min.css') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            background-image: url('<?= url('/assets/img/hero-bg.jpg') ?>'); /* Fallback or specific bg */
            background-size: cover;
            background-position: center;
        }
        .font-heading { font-family: 'Montserrat', sans-serif; }
        
        /* Glassmorphism Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.85); /* Increased opacity for better readability */
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        /* HVU Colors */
        .text-hvu-red { color: #ce1b22; }
        .bg-hvu-red { background-color: #ce1b22; }
        
        /* Input Styles */
        .hvu-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem; /* Adjusted padding for icon */
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        .hvu-input:focus {
            outline: none;
            border-color: #ce1b22;
            ring: 3px solid rgba(206, 27, 34, 0.1);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(206, 27, 34, 0.1);
        }

        /* Button Styles */
        .hvu-btn-primary {
            background: linear-gradient(135deg, #ce1b22 0%, #a91219 100%);
            color: white;
            font-weight: 700;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 6px -1px rgba(206, 27, 34, 0.4), 0 2px 4px -1px rgba(206, 27, 34, 0.2);
        }
        .hvu-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(206, 27, 34, 0.5), 0 4px 6px -2px rgba(206, 27, 34, 0.3);
            background: linear-gradient(135deg, #e0242c 0%, #b8141c 100%);
        }
        
        .floating-label {
             position: absolute;
             left: 2.5rem;
             top: 0.75rem;
             pointer-events: none;
             transition: 0.2s ease all;
             color: #9ca3af;
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <!-- Abstract Background Elements -->
    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute top-0 right-0 w-2/3 h-full bg-gradient-to-l from-red-50/50 to-transparent"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-red-100 rounded-full blur-3xl opacity-40 mix-blend-multiply"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-100 rounded-full blur-3xl opacity-40 mix-blend-multiply"></div>
    </div>

    <div class="glass-card p-8 md:p-10 rounded-2xl shadow-2xl w-full max-w-md relative z-10 animate-fade-in border-t-4 border-t-[#ce1b22]">
        <div class="flex flex-col items-center mb-8">
            <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center shadow-md mb-4 p-2 transition-transform hover:scale-105 duration-300">
                <img loading="lazy" src="<?= url('/assets/img/Logo.png') ?>" alt="Logo HVU" class="h-full w-full object-contain">
            </div>
            <h1 class="text-2xl font-black font-heading text-gray-800 uppercase text-center tracking-tight leading-tight">
                Cổng Quản Trị
            </h1>
            <p class="text-gray-500 text-sm font-medium mt-1">Trường Đại học Hùng Vương</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-md mb-6 text-sm flex items-start shadow-sm animate-pulse">
                <i class="fas fa-exclamation-circle mt-0.5 mr-2 text-red-600"></i>
                <span class="font-medium"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['timeout'])): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 px-4 py-3 rounded-md mb-6 text-sm flex items-start shadow-sm">
                <i class="fas fa-clock mt-0.5 mr-2 text-yellow-500"></i>
                <span class="font-medium">Phiên đăng nhập đã hết hạn do không có thao tác. Vui lòng đăng nhập lại.</span>
            </div>
        <?php endif; ?>

        <form action="<?= url('/admin/login') ?>" method="POST" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
            
            <div class="group">
                <label class="block text-gray-700 text-xs font-bold uppercase tracking-wider mb-2 ml-1 group-focus-within:text-[#ce1b22] transition-colors">
                    Tên đăng nhập
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user-shield text-gray-400 group-focus-within:text-[#ce1b22] transition-colors"></i>
                    </div>
                    <input type="text" name="username" required 
                           class="hvu-input" 
                           placeholder="Nhập tài khoản quản trị" autocomplete="username">
                </div>
            </div>

            <div class="group">
                <label class="block text-gray-700 text-xs font-bold uppercase tracking-wider mb-2 ml-1 group-focus-within:text-[#ce1b22] transition-colors">
                    Mật khẩu
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400 group-focus-within:text-[#ce1b22] transition-colors"></i>
                    </div>
                    <input type="password" name="password" required 
                           class="hvu-input" 
                           placeholder="••••••••" autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="w-full hvu-btn-primary py-3.5 flex items-center justify-center group mt-6">
                <span class="mr-2">Đăng nhập hệ thống</span>
                <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
            </button>
        </form>

        <div class="text-center pt-8 border-t border-gray-100 mt-6">
            <a href="<?= url('/login') ?>" class="inline-flex items-center text-sm font-semibold text-gray-500 hover:text-[#ce1b22] transition-colors group">
                <i class="fas fa-chevron-left mr-2 text-xs transform group-hover:-translate-x-1 transition-transform"></i> 
                Quay lại cổng thí sinh
            </a>
        </div>
    </div>
    
    <div class="fixed bottom-4 text-center w-full z-0 text-gray-400 text-xs">
        &copy; <?= date('Y') ?> Hung Vuong University. All rights reserved.
    </div>
</body>
</html>
