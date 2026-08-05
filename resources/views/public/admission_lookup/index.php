<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="description" content="Tra cứu thông báo trúng tuyển Đại học Hùng Vương năm 2026. Nhập CCCD, số báo danh hoặc email để xem kết quả.">
    <link rel="icon" type="image/png" href="<?= url('/assets/img/Logo.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('/assets/img/Logo.png') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Be Vietnam Pro', system-ui, -apple-system, sans-serif; }

        html {
            height: 100vh;
            height: 100dvh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            overflow-y: hidden;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #7f1d1d 100%);
            background-attachment: fixed;
            background-size: cover;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .glass-white {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .input-glow:focus {
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.25);
            border-color: #dc2626;
        }

        .btn-primary {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(220, 38, 38, 0.5);
        }
        .btn-primary:active { transform: translateY(0); }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-8px) rotate(1deg); }
            66% { transform: translateY(-4px) rotate(-1deg); }
        }
        .floating { animation: float 6s ease-in-out infinite; }

        @keyframes fade-up {
            from { opacity: 0; transform: translateY(15px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fade-up 0.5s ease forwards; }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            pointer-events: none;
        }

        .tab-btn { color: #6b7280; background: transparent; }
        .active-tab { background: white; color: #dc2626; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }

        /* Premium result card styles */
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        @keyframes slideInRow {
            from { opacity: 0; transform: translateX(-12px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.85); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes goldenPulse {
            0%, 100% { filter: drop-shadow(0 0 6px rgba(250,204,21,0.4)); }
            50% { filter: drop-shadow(0 0 16px rgba(250,204,21,0.7)); }
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .result-header {
            background: linear-gradient(135deg, #059669, #0d9488, #0891b2, #059669);
            background-size: 300% 300%;
            animation: gradientShift 6s ease infinite;
        }
        .result-shimmer::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }
        .info-row { animation: slideInRow 0.4s ease forwards; opacity: 0; }
        .info-row:nth-child(1) { animation-delay: 0.1s; }
        .info-row:nth-child(2) { animation-delay: 0.2s; }
        .info-row:nth-child(3) { animation-delay: 0.3s; }
        .info-row:nth-child(4) { animation-delay: 0.4s; }
        .trophy-glow { animation: goldenPulse 2s ease-in-out infinite; }
        .avatar-frame {
            position: relative;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
        }
        .avatar-frame::after {
            content: '\\f058';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            bottom: -6px; right: -6px;
            width: 22px; height: 22px;
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(5,150,105,0.4);
            animation: scaleIn 0.4s ease 0.5s forwards;
            opacity: 0;
        }
    </style>
</head>
<body class="p-4">

    <!-- Background orbs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="orb w-96 h-96 bg-red-500 top-0 left-0 -translate-x-1/2 -translate-y-1/2"></div>
        <div class="orb w-80 h-80 bg-indigo-600 bottom-0 right-0 translate-x-1/2 translate-y-1/2"></div>
        <div class="orb w-64 h-64 bg-rose-400 top-1/2 left-1/4"></div>
    </div>

    <!-- Confetti canvas -->
    <canvas id="confetti-canvas" style="position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;"></canvas>

    <div class="relative z-10 w-full max-w-lg mx-auto">

        <!-- Logo & Header -->
        <div class="text-center mb-8 fade-up">
            <div class="floating inline-block mb-3">
                <div class="w-24 h-24 mx-auto bg-white/10 rounded-3xl flex items-center justify-center border border-white/20 shadow-2xl">
                    <img src="<?= url('/assets/img/Logo.png') ?>" alt="Logo HVU" class="w-16 h-16 object-contain" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-university text-white text-3xl\'></i>'">
                </div>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white leading-tight tracking-tight">
                TRA CỨU TRÚNG TUYỂN
            </h1>
            <p class="text-white/60 mt-2 font-medium text-xs sm:text-sm">
                <?= htmlspecialchars($sessionName) ?> (Năm <?= htmlspecialchars($year) ?>)
            </p>
        </div>

        <!-- Error Message -->
        <?php if (isset($_GET['error'])): ?>
            <div class="mb-4 p-3.5 bg-rose-500/20 border border-rose-400/30 text-rose-200 rounded-xl flex items-center gap-2.5 fade-up delay-1">
                <div class="w-8 h-8 bg-rose-500/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-rose-300 text-xs"></i>
                </div>
                <div>
                    <?php if ($_GET['error'] === 'empty'): ?>
                        <div class="font-bold text-xs">Vui lòng nhập thông tin tra cứu!</div>
                    <?php else: ?>
                        <div class="font-bold text-xs">Không tìm thấy kết quả</div>
                        <div class="text-[11px] text-rose-300 mt-0.5">Kiểm tra lại CCCD hoặc số báo danh thi THPT của bạn</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search Card -->
        <div id="search-section" class="glass-white rounded-3xl p-5 sm:p-6 shadow-2xl fade-up">

            <form id="lookupForm" onsubmit="handleSearch(event)">
                <?= csrf_field() ?>

                <div class="mb-4">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">
                        Thông tin tra cứu
                    </label>

                    <!-- Tabs -->
                    <div class="flex gap-2 mb-3 p-1 bg-gray-100 rounded-2xl">
                        <button type="button" onclick="setPlaceholder('cccd')" id="tab-cccd"
                            class="flex-1 py-1.5 px-3 rounded-xl text-xs font-bold transition-all tab-btn active-tab">
                            <i class="fas fa-id-card mr-1"></i> CCCD
                        </button>
                        <button type="button" onclick="setPlaceholder('sbd')" id="tab-sbd"
                            class="flex-1 py-1.5 px-3 rounded-xl text-xs font-bold transition-all tab-btn">
                            <i class="fas fa-hashtag mr-1"></i> Số báo danh
                        </button>
                    </div>

                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm" id="inputIcon"></i>
                        </div>
                        <input type="text" name="keyword" id="keywordInput" required
                               placeholder="Nhập số CCCD của bạn..."
                               value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>"
                               class="w-full pl-10 pr-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl outline-none input-glow transition font-semibold text-gray-800 text-sm">
                    </div>
                </div>

                <button type="submit" id="btnSubmit"
                    class="btn-primary w-full py-3 text-white font-black rounded-xl flex items-center justify-center gap-3 text-sm">
                    <i class="fas fa-search"></i>
                    <span>TRA CỨU KẾT QUẢ</span>
                </button>
            </form>

            <!-- Divider -->
            <div class="flex items-center gap-3 my-4">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="text-[10px] text-gray-400 font-medium">Hướng dẫn</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

            <!-- Tips -->
            <div class="space-y-2">
                <div class="flex items-start gap-2.5 text-xs text-gray-500">
                    <div class="w-5 h-5 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-id-card text-blue-400 text-[10px]"></i>
                    </div>
                    <span>Nhập <strong class="text-gray-700">số CCCD/CMND</strong> (12 số) để tra cứu</span>
                </div>
                <div class="flex items-start gap-2.5 text-xs text-gray-500">
                    <div class="w-5 h-5 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-hashtag text-red-500 text-[10px]"></i>
                    </div>
                    <span>Hoặc nhập <strong class="text-gray-700">Số báo danh thi THPT</strong> của bạn</span>
                </div>
                <div class="flex items-start gap-2.5 text-xs text-gray-500">
                    <div class="w-5 h-5 rounded-lg bg-yellow-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-info text-yellow-500 text-[10px]"></i>
                    </div>
                    <span>Nếu không tìm thấy, liên hệ hotline: <strong class="text-gray-700">0866.993.468</strong></span>
                </div>
            </div>
        </div>

        <!-- Result Card (Hidden by default) -->
        <div id="result-section" class="hidden glass-white rounded-3xl p-0 shadow-2xl fade-up relative overflow-hidden">
            <!-- Navigation buttons at the top sides (matching the red circles location) -->
            <a href="<?= url('/') ?>" class="absolute left-1.5 sm:left-[-40px] top-[-44px] bg-white/10 hover:bg-white/20 border border-white/15 text-white hover:text-white rounded-full px-2.5 py-1.5 text-[9px] sm:text-[10px] font-black tracking-wider uppercase transition-all shadow-lg flex items-center gap-1 backdrop-blur-sm">
                <i class="fas fa-home text-indigo-200"></i> <span class="hidden min-[360px]:inline">Về trang chủ</span>
            </a>
            <button type="button" onclick="resetSearch()" class="absolute right-1.5 sm:right-[-40px] top-[-44px] bg-white/10 hover:bg-white/20 border border-white/15 text-white rounded-full px-2.5 py-1.5 text-[9px] sm:text-[10px] font-black tracking-wider uppercase transition-all shadow-lg flex items-center gap-1 backdrop-blur-sm">
                <i class="fas fa-search text-emerald-200"></i> <span class="hidden min-[360px]:inline">Tra cứu khác</span>
            </button>
            <div id="res-content"></div>
        </div>

        <!-- Bottom links -->
        <div class="text-center mt-4 fade-up flex items-center justify-center gap-3">
            <a href="<?= url('/') ?>" class="text-white/50 hover:text-white text-xs font-medium transition-colors inline-flex items-center gap-1.5">
                <i class="fas fa-arrow-left text-[10px]"></i> Quay lại trang chủ
            </a>
            <span class="text-white/20 text-[10px]">|</span>
            <button type="button" onclick="resetSearch()" class="text-white/50 hover:text-white text-xs font-medium transition-colors inline-flex items-center gap-1.5">
                <i class="fas fa-search text-[10px]"></i> Tra cứu thí sinh khác
            </button>
        </div>
    </div>

    <script>
        const sessionName = '<?= htmlspecialchars($sessionName) ?> (Năm <?= htmlspecialchars($year) ?>)';
        const tabs = { cccd: 'Nhập số CCCD của bạn...', sbd: 'Nhập số báo danh thi THPT của bạn...' };
        const icons = { cccd: 'fa-id-card', sbd: 'fa-hashtag' };

        function setPlaceholder(type) {
            document.getElementById('keywordInput').placeholder = tabs[type];
            const icon = document.getElementById('inputIcon');
            icon.className = 'fas ' + icons[type] + ' text-gray-400 text-base';
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active-tab'));
            document.getElementById('tab-' + type).classList.add('active-tab');
        }

        async function handleSearch(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Đang tra cứu...';

            const formData = new FormData(document.getElementById('lookupForm'));
            
            try {
                const res = await fetch('<?= url('/tra-cuu-trung-tuyen/search') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const text = await res.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    alert('Lỗi phản hồi từ máy chủ: ' + text.substring(0, 100));
                    return;
                }
                
                const resultSection = document.getElementById('result-section');
                const searchSection = document.getElementById('search-section');
                
                searchSection.classList.add('hidden');
                resultSection.classList.remove('hidden');

                if (result.success && result.data) {
                    const data = result.data;
                    document.getElementById('res-content').innerHTML = `
                        <!-- Premium Celebratory Header -->
                        <div class="result-header result-shimmer text-white px-5 py-5 sm:py-6 text-center relative overflow-hidden">
                            <!-- Decorative circles -->
                            <div class="absolute top-[-20px] left-[-20px] w-24 h-24 bg-white/5 rounded-full pointer-events-none"></div>
                            <div class="absolute bottom-[-15px] right-[-15px] w-20 h-20 bg-white/5 rounded-full pointer-events-none"></div>
                            <h3 class="text-xl sm:text-2xl font-black tracking-wider uppercase drop-shadow-md">CHÚC MỪNG!</h3>
                            <p class="text-xs sm:text-sm font-semibold opacity-90 mt-0.5">Bạn đã trúng tuyển vào Đại học Hùng Vương</p>
                        </div>
                        
                        <!-- Content Area -->
                        <div class="p-5 sm:p-6">
                            <div class="flex flex-col sm:flex-row gap-4 items-center sm:items-start">
                                <!-- Avatar with verified badge -->
                                <div class="avatar-frame w-[88px] h-[120px] rounded-2xl bg-gray-100 overflow-hidden flex-shrink-0 border-2 border-gray-100">
                                    ${data.anh_the ? '<img src="' + data.anh_the + '" class="w-full h-full object-cover">' : '<div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gradient-to-b from-gray-50 to-gray-100"><i class="fas fa-user-circle text-3xl mb-1"></i><span class="text-[9px]">Không có ảnh</span></div>'}
                                </div>
                                <!-- Student Details -->
                                <div class="flex-1 w-full text-left">
                                    <h4 class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-[0.15em] mb-2.5 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full inline-block"></span>
                                        Thông tin thí sinh
                                    </h4>
                                    <div class="space-y-2 text-sm text-gray-700">
                                        <div class="info-row flex justify-between sm:justify-start items-baseline border-b border-gray-100 pb-1.5">
                                            <span class="w-[110px] text-gray-400 font-medium text-xs flex-shrink-0">Họ và tên:</span>
                                            <strong class="text-red-600 font-extrabold uppercase text-right sm:text-left tracking-wide">${data.ho_ten}</strong>
                                        </div>
                                        <div class="info-row flex justify-between sm:justify-start items-baseline border-b border-gray-100 pb-1.5">
                                            <span class="w-[110px] text-gray-400 font-medium text-xs flex-shrink-0">Ngày sinh:</span>
                                            <strong class="text-red-600 font-extrabold text-right sm:text-left">${data.ngay_sinh || '--/--/----'}</strong>
                                        </div>
                                        <div class="info-row flex justify-between sm:justify-start items-baseline border-b border-gray-100 pb-1.5">
                                            <span class="w-[110px] text-gray-400 font-medium text-xs flex-shrink-0">Số CCCD:</span>
                                            <strong class="text-red-600 font-extrabold text-right sm:text-left tracking-wider">${data.so_cccd}</strong>
                                        </div>
                                        <div class="info-row flex justify-between sm:justify-start items-baseline pb-0.5">
                                            <span class="w-[110px] text-gray-400 font-medium text-xs flex-shrink-0">Ngành trúng tuyển:</span>
                                            <strong class="text-red-600 font-extrabold text-right sm:text-left">${data.ten_nganh}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <div class="max-w-[300px] mx-auto mt-5">
                                <a href="${data.login_url}" class="btn-primary w-full py-3 text-white font-black rounded-2xl flex items-center justify-center gap-2.5 text-sm tracking-wide">
                                    Xem thông báo chi tiết <i class="fas fa-arrow-right text-xs"></i>
                                </a>
                            </div>

                            <!-- Hotline Footer -->
                            <div class="border-t border-gray-100 pt-3 mt-5 text-center text-[11px] text-gray-400">
                                <i class="fas fa-headset text-emerald-400 mr-1"></i> Mọi thắc mắc liên hệ hotline: <a href="tel:0866993468" class="text-red-600 font-bold hover:underline">0866.993.468</a>
                            </div>
                        </div>
                    `;
                    
                    // Trigger confetti on dedicated canvas
                    try {
                        var canvas = document.getElementById('confetti-canvas');
                        canvas.width = window.innerWidth;
                        canvas.height = window.innerHeight;
                        var myConfetti = confetti.create(canvas, { resize: true, useWorker: false });
                        
                        var duration = 4000;
                        var end = Date.now() + duration;
                        (function frame() {
                            myConfetti({ particleCount: 4, angle: 60, spread: 65, origin: { x: 0, y: 0.75 }, colors: ['#dc2626','#f59e0b','#10b981','#3b82f6','#ec4899'] });
                            myConfetti({ particleCount: 4, angle: 120, spread: 65, origin: { x: 1, y: 0.75 }, colors: ['#dc2626','#f59e0b','#10b981','#3b82f6','#ec4899'] });
                            if (Date.now() < end) requestAnimationFrame(frame);
                        }());
                    } catch(e) { console.log('Confetti error:', e); }
                } else {
                    document.getElementById('res-content').innerHTML = `
                        <!-- Full-width Header - Not Found -->
                        <div class="text-white px-5 py-5 sm:py-6 text-center relative overflow-hidden" style="background: linear-gradient(135deg, #d97706, #b45309, #92400e, #d97706); background-size: 300% 300%; animation: gradientShift 6s ease infinite;">
                            <div class="absolute top-[-20px] left-[-20px] w-24 h-24 bg-white/5 rounded-full pointer-events-none"></div>
                            <div class="absolute bottom-[-15px] right-[-15px] w-20 h-20 bg-white/5 rounded-full pointer-events-none"></div>
                            <h3 class="text-xl sm:text-2xl font-black tracking-wider uppercase drop-shadow-md">THÔNG BÁO</h3>
                        </div>
                        
                        <!-- Content Area -->
                        <div class="p-5 sm:p-6">
                            <!-- Status message -->
                            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/60 rounded-2xl p-5 mb-4 text-center">
                                <p class="font-black text-amber-900 text-base sm:text-lg mb-1.5">
                                    Không tìm thấy thông tin trúng tuyển
                                </p>
                                <p class="text-sm text-amber-700 font-medium">
                                    trong đợt xét tuyển:
                                </p>
                                <p class="font-extrabold text-red-600 text-sm sm:text-base uppercase mt-2">
                                    ${sessionName}
                                </p>
                            </div>

                            <!-- Encouragement message -->
                            <div class="bg-blue-50/60 border border-blue-100/60 rounded-2xl p-4 mb-4 text-center">
                                <p class="text-xs text-gray-600 leading-relaxed">
                                    <i class="fas fa-lightbulb text-blue-400 mr-1"></i>
                                    Bạn vẫn có thể <strong class="text-blue-700">đăng ký xét tuyển</strong> vào Đại học Hùng Vương qua các đợt xét tuyển tiếp theo!
                                </p>
                            </div>

                            <!-- CTA Button - Đăng ký ngay -->
                            <div class="max-w-[300px] mx-auto">
                                <a href="<?= url('/dang-ky') ?>" class="w-full py-3 text-white font-black rounded-2xl flex items-center justify-center gap-2.5 text-sm tracking-wide shadow-lg hover:shadow-xl transition-all hover:-translate-y-0.5" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); box-shadow: 0 8px 25px rgba(37,99,235,0.4);">
                                    <i class="fas fa-pen-to-square"></i> Đăng ký xét tuyển ngay
                                </a>
                            </div>

                            <!-- Hotline Footer -->
                            <div class="border-t border-gray-100 pt-3 mt-5 text-center text-[11px] text-gray-400">
                                <i class="fas fa-headset text-amber-400 mr-1"></i> Mọi thắc mắc liên hệ hotline: <a href="tel:0866993468" class="text-red-600 font-bold hover:underline">0866.993.468</a>
                            </div>
                        </div>
                    `;
                }
                
            } catch (err) {
                alert('Có lỗi xảy ra, vui lòng thử lại.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }

        function resetSearch() {
            document.getElementById('result-section').classList.add('hidden');
            document.getElementById('search-section').classList.remove('hidden');
            document.getElementById('keywordInput').value = '';
            document.getElementById('keywordInput').focus();
        }

        (function() {
            const params = new URLSearchParams(window.location.search);
            const q = params.get('q');
            if (q && q.trim().length > 0) {
                document.getElementById('keywordInput').value = q.trim();
                setTimeout(() => { document.getElementById('lookupForm').dispatchEvent(new Event('submit', { cancelable: true, bubbles: true })); }, 300);
            }
        })();
    </script>
</body>
</html>
