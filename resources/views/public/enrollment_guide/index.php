<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="description" content="Hệ thống hướng dẫn nhập học trực quan - Trường Đại học Hùng Vương.">
    <link rel="icon" type="image/png" href="<?= url('/assets/img/Logo.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('/assets/img/Logo.png') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            user-select: none;
            -webkit-user-drag: none;
        }

        body {
            background-color: #F5F7FB;
            color: #1e293b;
        }

        .primary-bg { background-color: #0D47A1; }
        .secondary-bg { background-color: #E53935; }
        .primary-text { color: #0D47A1; }
        .secondary-text { color: #E53935; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Laser Animation */
        @keyframes laser-scan {
            0% { top: 0%; opacity: 0.3; }
            50% { opacity: 1; }
            100% { top: 100%; opacity: 0.3; }
        }
        .laser-line {
            position: absolute;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, transparent, #22c55e, transparent);
            box-shadow: 0 0 10px 4px rgba(34, 197, 94, 0.7);
            animation: laser-scan 2.2s infinite ease-in-out;
            z-index: 15;
            pointer-events: none;
        }

        /* Pulsing Glow for Desk Num */
        @keyframes desk-pulse {
            0%, 100% { box-shadow: 0 0 5px rgba(229, 57, 53, 0.2); transform: scale(1); }
            50% { box-shadow: 0 0 25px rgba(229, 57, 53, 0.7); transform: scale(1.03); }
        }
        .desk-glow {
            animation: desk-pulse 1.8s infinite ease-in-out;
            border: 3px solid #E53935;
        }

        /* Default Typography Settings for PC */
        .txt-title { font-size: 16px; font-weight: 800; }
        .txt-body { font-size: 13px; }
        .txt-item-title { font-size: 14px; font-weight: 700; }
        .txt-desc { font-size: 12px; }
        .txt-candidate-name { font-size: 18px; font-weight: 800; }
        .txt-desk-num { font-size: 32px; font-weight: 900; }
        .txt-status-bar { font-size: 12px; }

        /* QR corners style */
        .qr-corner {
            position: absolute;
            width: 24px;
            height: 24px;
            border-color: #0D47A1;
            border-style: solid;
            z-index: 10;
        }
        .c-tl { top: 10px; left: 10px; border-width: 4px 0 0 4px; border-top-left-radius: 8px; }
        .c-tr { top: 10px; right: 10px; border-width: 4px 4px 0 0; border-top-right-radius: 8px; }
        .c-bl { bottom: 10px; left: 10px; border-width: 0 0 4px 4px; border-bottom-left-radius: 8px; }
        .c-br { bottom: 10px; right: 10px; border-width: 0 4px 4px 0; border-bottom-right-radius: 8px; }

        #qr-reader { border: none !important; }
        #qr-reader video {
            border-radius: 8px;
            object-fit: cover;
            width: 100%;
            height: 100%;
        }

        /* Portrait & Kiosk View Override */
        @media (max-width: 1024px), (orientation: portrait), .force-kiosk {
            .txt-title { font-size: 36px !important; }
            .txt-body { font-size: 20px !important; }
            .txt-item-title { font-size: 24px !important; }
            .txt-desc { font-size: 18px !important; }
            .txt-candidate-name { font-size: 38px !important; }
            .txt-desk-num { font-size: 72px !important; }
            .txt-status-bar { font-size: 22px !important; }
            
            /* Large Touch Elements */
            .touch-btn {
                min-height: 64px !important;
                font-size: 22px !important;
            }
            .touch-input {
                min-height: 64px !important;
                font-size: 22px !important;
            }
            
            /* Adjust layout padding on kiosk */
            .kiosk-padding {
                padding: 24px !important;
            }
            
            /* Hide status bar on Kiosk since it's tab-based */
            .kiosk-hide-status {
                display: none !important;
            }
        }
    </style>
</head>
<body class="flex flex-col h-[100vh] overflow-hidden bg-[#F5F7FB]">
    <canvas id="confetti-canvas" style="position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;"></canvas>

    <!-- Header -->
    <header class="primary-bg text-white shadow-md z-30 shrink-0">
        <div class="max-w-[1600px] mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-white rounded-full flex items-center justify-center p-1 shadow-md">
                    <img src="<?= url('/assets/img/Logo.png') ?>" alt="Logo HVU" class="w-full h-full object-contain">
                </div>
                <div>
                    <div class="text-[10px] md:text-xs font-bold uppercase tracking-wider text-blue-200">Trường Đại học Hùng Vương</div>
                    <h1 class="text-sm md:text-lg font-black tracking-wide leading-none mt-0.5">HỆ THỐNG HƯỚNG DẪN NHẬP HỌC</h1>
                </div>
            </div>
            
            <div class="flex items-center gap-6 text-xs md:text-sm text-blue-100">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-phone-volume text-red-400"></i>
                    <div>
                        <span class="opacity-75 hidden sm:inline text-xs">Hotline:</span>
                        <span class="font-bold text-white">0866 993 468</span>
                    </div>
                </div>
                <div class="w-px h-5 bg-white/20"></div>
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-clock text-amber-400"></i>
                    <div>
                        <span id="current-time" class="font-bold text-white">--:--:--</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Kiosk / Mobile Mode Tab Switcher (Only visible in Kiosk/Portrait View) -->
    <div id="kiosk-tabs" class="hidden shrink-0 bg-white border-b border-gray-200 p-2 grid grid-cols-2 gap-2 shadow-sm z-20">
        <button type="button" onclick="switchKioskTab(1)" id="k-tab-1" class="touch-btn py-3 rounded-xl font-bold flex items-center justify-center gap-2 text-white primary-bg transition-all">
            <i class="fa-solid fa-search"></i> <span>1. Tra cứu & Thí sinh</span>
        </button>
        <button type="button" onclick="switchKioskTab(2)" id="k-tab-2" class="touch-btn py-3 rounded-xl font-bold flex items-center justify-center gap-2 text-gray-500 bg-gray-100 transition-all">
            <i class="fa-solid fa-map-location-dot"></i> <span>2. Sơ đồ & Chỉ dẫn</span>
        </button>
    </div>

    <!-- Main Content Panel -->
    <main class="flex-1 w-full max-w-[1920px] mx-auto p-4 grid grid-cols-1 lg:grid-cols-8 gap-4 min-h-0 relative">

        <!-- COLUMN 1: Left 3 cols (Candidate Info) -->
        <div id="col-tra-cuu" class="col-span-1 lg:col-span-3 flex flex-col h-full min-h-0 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Header section matching screenshot: Teal background -->
            <div id="success-banner" class="bg-[#1e9a95] p-6 text-center text-white relative hidden">
                <!-- Inner border decoration -->
                <div class="absolute inset-2 border-2 border-[#54b8b4] rounded-lg pointer-events-none"></div>
                <h2 class="text-3xl font-black mb-2 mt-4 tracking-wider uppercase drop-shadow-md">CHÚC MỪNG!</h2>
                <p class="text-base font-bold mb-4 drop-shadow-sm">TÂN SINH VIÊN ĐẠI HỌC HÙNG VƯƠNG</p>
            </div>
            
            <!-- Candidate Data section -->
            <div class="p-6 flex-1 bg-white relative min-h-[300px]">
                <!-- Initial State (Waiting to scan) -->
                <div id="waiting-state" class="absolute inset-0 flex flex-col items-center justify-center bg-white z-10 text-slate-400 transition-opacity duration-300">
                    <i class="fa-solid fa-id-card text-6xl mb-4 text-slate-200"></i>
                    <p class="font-bold text-xl uppercase text-slate-300">THÔNG TIN THÍ SINH</p>
                    <p class="font-medium text-sm mt-2">Vui lòng quét QR hoặc nhập CCCD</p>
                </div>

                <!-- Result State (Hidden by default, shown via JS) -->
                <div id="result-state" class="flex flex-col items-center h-full opacity-0 pointer-events-none transition-opacity duration-300">
                    
                    <!-- Top: Avatar -->
                    <div class="w-[130px] shrink-0 flex flex-col items-center mb-5 relative">
                        <div class="w-full aspect-[3/4] bg-gray-100 overflow-hidden border-[6px] border-white shadow-[0_8px_20px_rgba(0,0,0,0.15)] ring-1 ring-slate-200/50 mb-2 relative z-10 rounded-sm">
                            <img id="cand-avatar" src="<?= url('/assets/img/default-avatar.png') ?>" alt="Avatar" class="w-full h-full object-cover">
                        </div>
                    </div>
                    
                    <!-- Bottom: Info -->
                    <div class="w-full flex-1 flex flex-col">
                        <h3 class="text-green-600 font-bold text-sm md:text-base mb-4 flex items-center gap-2 justify-center">
                            <i class="fa-solid fa-circle text-[6px]"></i> THÔNG TIN THÍ SINH
                        </h3>
                        
                        <div class="space-y-3 max-w-[420px] mx-auto w-full px-2">
                            <div class="flex items-start">
                                <span class="w-[135px] text-slate-500 text-sm shrink-0">Họ và tên:</span>
                                <span id="cand-name" class="font-black text-red-600 text-lg uppercase leading-tight">--</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-[135px] text-slate-500 text-sm shrink-0">Ngày sinh:</span>
                                <span id="cand-dob" class="font-bold text-red-600 text-base">--</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-[135px] text-slate-500 text-sm shrink-0">Số CCCD:</span>
                                <span id="cand-cccd" class="font-bold text-red-600 text-base">--</span>
                            </div>
                            <div class="flex items-start mt-3 pt-3 border-t border-slate-100">
                                <span class="w-[135px] text-slate-500 text-sm shrink-0">Ngành trúng tuyển:</span>
                                <span id="cand-major" class="font-black text-red-600 text-base md:text-lg leading-tight">--</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMN 2: Middle 2 cols (Scanner & Text Directory) -->
        <div class="col-span-1 lg:col-span-2 flex flex-col gap-4 h-full min-h-0">
            <!-- Top: Scanner -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex flex-col shrink-0 relative">
                <!-- Camera Button in top right corner -->
                <button type="button" id="btn-toggle-cam" onclick="toggleCamera()" class="absolute top-6 right-6 z-20 w-8 h-8 rounded-full bg-blue-600/80 hover:bg-blue-700 text-white flex items-center justify-center shadow-md backdrop-blur-sm transition-colors" title="Bật/Tắt Camera">
                    <i class="fa-solid fa-camera"></i>
                </button>

                <div class="relative w-full aspect-video bg-slate-900 rounded-xl overflow-hidden shadow-inner flex items-center justify-center shrink-0 mb-4">
                    <div id="laser" class="laser-line hidden"></div>
                    <div class="qr-corner c-tl"></div>
                    <div class="qr-corner c-tr"></div>
                    <div class="qr-corner c-bl"></div>
                    <div class="qr-corner c-br"></div>
                    
                    <div id="qr-reader" class="w-full h-full absolute inset-0 z-0 bg-transparent"></div>
                    
                    <div id="qr-placeholder" class="absolute inset-0 z-10 flex flex-col items-center justify-center p-4 text-center bg-slate-900/80 backdrop-blur-sm transition-opacity">
                        <div class="w-12 h-12 bg-blue-900/40 text-blue-400 border border-blue-500/30 rounded-full flex items-center justify-center mb-2 animate-pulse">
                            <i class="fa-solid fa-expand text-xl"></i>
                        </div>
                        <p class="text-white font-bold text-sm md:text-base mb-1">Đưa mã QR trên giấy báo vào khung</p>
                        <p class="text-slate-400 text-xs leading-snug">Hệ thống tự động quét</p>
                    </div>
                </div>
                
                <form id="manual-search-form" onsubmit="handleQuerySubmit(event, 'cccd-input')" class="flex gap-2">
                    <input type="text" id="cccd-input" class="flex-1 border border-slate-300 px-3 py-2 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm font-semibold" placeholder="Nhập Số CCCD / SBD">
                    <button id="search-btn" type="submit" class="primary-bg hover:bg-blue-800 text-white px-4 py-2 rounded-lg font-bold text-sm shadow-md transition whitespace-nowrap">
                        <i class="fa-solid fa-search"></i> Tra cứu
                    </button>
                </form>
                
                <!-- Alert message container -->
                <div id="error-alert" class="hidden mt-3 p-2 bg-red-50 border border-red-200 text-red-700 rounded-lg text-xs flex items-start gap-2 shrink-0 animate__animated animate__fadeIn">
                    <i class="fa-solid fa-circle-exclamation mt-0.5 text-sm"></i>
                    <div id="error-message" class="font-semibold leading-snug"></div>
                </div>
            </div>
            
            <!-- Bottom: Directory -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-3 flex-1 overflow-y-auto">
                <div class="space-y-3">
                    <div>
                        <div class="text-red-600 font-bold text-[11px] md:text-xs uppercase leading-tight">Bàn 1, Bàn 2, Bàn 3 (Hội trường trung tâm)</div>
                        <div class="text-slate-700 text-[11px] md:text-xs mt-0.5 leading-snug">Ngành Ngôn ngữ Trung quốc, Điều dưỡng, SP Toán, SP Khoa học tự nhiên</div>
                    </div>
                    <div>
                        <div class="text-red-600 font-bold text-[11px] md:text-xs uppercase leading-tight">Bàn 4, Bàn 5 (Giảng đường D)</div>
                        <div class="text-slate-700 text-[11px] md:text-xs mt-0.5 leading-snug">Ngành Sư phạm Ngữ Văn, SP Lịch sử - Địa lí, QTDV Du lịch – LH, Du lịch</div>
                    </div>
                    <div>
                        <div class="text-red-600 font-bold text-[11px] md:text-xs uppercase leading-tight">Bàn 6, Bàn 7 (Giảng đường E)</div>
                        <div class="text-slate-700 text-[11px] md:text-xs mt-0.5 leading-snug">Ngành Công nghệ thông tin, CNKT Điện – ĐT, CNKT Cơ khí</div>
                    </div>
                    <div>
                        <div class="text-red-600 font-bold text-[11px] md:text-xs uppercase leading-tight">Bàn 8, Bàn 9 (Góc văn hoá Hàn quốc – tầng 3)</div>
                        <div class="text-slate-700 text-[11px] md:text-xs mt-0.5 leading-snug">Ngành GDTH, GD Mầm non, GD Thể chất, SP Mỹ thuật, SP Âm nhạc, Chăn nuôi, KHCT, Thú y</div>
                    </div>
                    <div>
                        <div class="text-red-600 font-bold text-[11px] md:text-xs uppercase leading-tight">Bàn 10, Bàn 11, Bàn 12 (Hội trường tầng 3)</div>
                        <div class="text-slate-700 text-[11px] md:text-xs mt-0.5 leading-snug">Tiếp thí sinh mới, Nhập học không đúng thời gian quy định</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMN 3: Right 3 cols (Image Map) -->
        <div class="col-span-1 lg:col-span-3 flex flex-col h-full min-h-0 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <img id="map-image" src="<?= url('/assets/img/so_do_nhap_hoc.jpg') ?>" alt="Sơ đồ nhập học" class="w-full h-full object-contain bg-slate-50" onerror="this.src='https://placehold.co/1000x800/f8fafc/64748b?text=S%C6%A1+%C4%91%E1%BB%93+v%E1%BB%8B+tr%C3%AD+b%C3%A0n+nh%E1%BA%ADp+h%E1%BB%8Dc'">
        </div>

    </main>

    <!-- Attract Mode / Screensaver Overlay (Hidden normally, shown on Kiosk idle) -->
    <div id="attract-overlay" class="fixed inset-0 bg-[#0D47A1] z-50 flex flex-col items-center justify-center p-6 text-white text-center cursor-pointer transition-all duration-500 hidden" onclick="dismissAttractMode()">
        <div class="max-w-4xl flex flex-col items-center">
            <!-- Animated HVU logo circle -->
            <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center p-3 shadow-2xl mb-8 animate__animated animate__pulse animate__infinite">
                <img src="<?= url('/assets/img/Logo.png') ?>" alt="HVU Logo" class="w-full h-full object-contain">
            </div>
            
            <h2 class="text-3xl md:text-5xl font-black uppercase tracking-wider mb-2 animate__animated animate__fadeInDown whitespace-nowrap">CHÀO MỪNG TÂN SINH VIÊN</h2>
            <h3 class="text-lg md:text-2xl text-blue-200 font-bold uppercase tracking-widest mb-12 animate__animated animate__fadeInUp whitespace-nowrap">Trường Đại học Hùng Vương</h3>

            <div class="space-y-4 animate__animated animate__flash animate__infinite animate__slower">
                <div class="w-16 h-16 rounded-full border-2 border-white/60 flex items-center justify-center mx-auto">
                    <i class="fa-solid fa-hand-pointer text-2xl"></i>
                </div>
                <p class="text-xl md:text-2xl font-black uppercase tracking-widest text-yellow-300">Chạm vào màn hình để bắt đầu</p>
            </div>
            
            <p class="text-xs md:text-sm text-blue-300/80 absolute bottom-6">Hội đồng Tuyển sinh Trường Đại học Hùng Vương</p>
        </div>
    </div>

    <!-- Status Bar (Desktop Only, Hidden on Portrait) -->
    <footer class="kiosk-hide-status border-t border-slate-200 bg-white px-4 py-2 shrink-0 shadow-md z-20">
        <div class="max-w-[1600px] mx-auto flex items-center justify-between text-slate-500">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-info text-blue-700 animate-pulse"></i>
                <span id="status-bar-text" class="txt-status-bar font-bold uppercase tracking-wider text-slate-700">Vui lòng quét QR trên giấy báo hoặc nhập CCCD để tra cứu</span>
            </div>
            <div class="text-[10px] md:text-xs">
                © <?= date('Y') ?> ĐH Hùng Vương - Tuyển sinh
            </div>
        </div>
    </footer>

    <!-- Image Modal for Map zoom -->
    <div id="img-modal" class="fixed inset-0 z-50 bg-black/85 hidden items-center justify-center p-4 backdrop-blur-sm" onclick="closeImageModal()">
        <div class="relative max-w-5xl w-full max-h-full flex items-center justify-center animate__animated animate__zoomIn animate__fast" onclick="event.stopPropagation()">
            <button type="button" class="absolute -top-12 right-0 w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center text-xl transition-colors" onclick="closeImageModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <img id="img-modal-src" src="" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl">
        </div>
    </div>

    <!-- JavaScript logic -->
    <script>
        let html5QrCode = null;
        let isCameraActive = false;
        const defaultMapSrc = document.getElementById('res-sodo-img').src;
        let currentSearchMethod = 'scan'; // 'scan' or 'manual'
        
        // Timer configurations
        let idleTimer = null;
        let attractTimer = null;
        const IDLE_RESET_MS = 15000; // 15 seconds to reset query results
        const ATTRACT_TIMEOUT_MS = 60000; // 60 seconds to launch attract mode screensaver

        // Device display configuration check
        const isKioskMode = window.location.search.includes('mode=kiosk') || window.innerHeight > window.innerWidth;

        document.addEventListener('DOMContentLoaded', () => {
            initClock();
            checkLayoutMode();
            resetTimers();
            
            // Attach interaction listeners to reset idle and attract mode timer
            ['mousedown', 'mousemove', 'keypress', 'touchstart', 'scroll'].forEach(evt => {
                document.addEventListener(evt, resetTimers, true);
            });
            
            // Auto start camera in scan mode if kiosk parameter is set
            if (isKioskMode) {
                setTimeout(() => {
                    startScannerCamera();
                }, 800);
            }
        });

        // Live Clock
        function initClock() {
            function updateClock() {
                const now = new Date();
                const days = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
                const day = days[now.getDay()];
                const date = String(now.getDate()).padStart(2, '0');
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const year = now.getFullYear();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                
                const timeString = `${day}, ${date}/${month}/${year} ${hours}:${minutes}:${seconds}`;
                const clockEl = document.getElementById('current-time');
                if (clockEl) clockEl.innerText = timeString;
            }
            updateClock();
            setInterval(updateClock, 1000);
        }

        // Layout check on resize / orientation change
        window.addEventListener('resize', checkLayoutMode);
        
        function checkLayoutMode() {
            const isPortrait = window.innerHeight > window.innerWidth || window.location.search.includes('mode=kiosk');
            const colTraCuu = document.getElementById('col-tra-cuu');
            const colHuongDan = document.getElementById('col-huong-dan');
            const kioskTabs = document.getElementById('kiosk-tabs');
            
            if (isPortrait) {
                // Portrait / Kiosk mode
                document.body.classList.add('force-kiosk');
                kioskTabs.classList.remove('hidden');
                
                // Show current active tab
                switchKioskTab(1); // Default to tab 1
            } else {
                // Desktop mode
                document.body.classList.remove('force-kiosk');
                kioskTabs.classList.add('hidden');
                
                colTraCuu.style.display = 'flex';
                colHuongDan.style.display = 'flex';
                colTraCuu.className = "w-full lg:w-[420px] flex-shrink-0 flex flex-col h-full min-h-0";
                colHuongDan.className = "flex-1 flex flex-col h-full min-h-0";
            }
        }

        // Kiosk Portrait Tab Switcher
        function switchKioskTab(tabNum) {
            const colTraCuu = document.getElementById('col-tra-cuu');
            const colHuongDan = document.getElementById('col-huong-dan');
            const kTab1 = document.getElementById('k-tab-1');
            const kTab2 = document.getElementById('k-tab-2');
            
            if (tabNum === 1) {
                colTraCuu.style.display = 'flex';
                colHuongDan.style.display = 'none';
                
                kTab1.className = "touch-btn py-3 rounded-xl font-bold flex items-center justify-center gap-2 text-white primary-bg transition-all shadow-md";
                kTab2.className = "touch-btn py-3 rounded-xl font-bold flex items-center justify-center gap-2 text-slate-500 bg-slate-100 transition-all";
            } else {
                colTraCuu.style.display = 'none';
                colHuongDan.style.display = 'flex';
                
                kTab1.className = "touch-btn py-3 rounded-xl font-bold flex items-center justify-center gap-2 text-slate-500 bg-slate-100 transition-all";
                kTab2.className = "touch-btn py-3 rounded-xl font-bold flex items-center justify-center gap-2 text-white secondary-bg transition-all shadow-md";
            }
        }

        // Quick helper button on Result Card (Kiosk portrait) to slide to map tab
        function showKioskGuide() {
            switchKioskTab(2);
        }

        // Toggle Search Tabs: Scan QR or Keyboard Manual
        function setSearchMethod(method) {
            const tabScan = document.getElementById('tab-scan');
            const tabManual = document.getElementById('tab-manual');
            const methodScan = document.getElementById('method-scan');
            const methodManual = document.getElementById('method-manual');

            hideFormError();
            currentSearchMethod = method;

            if (method === 'scan') {
                tabScan.className = "touch-btn flex-1 py-2 px-3 rounded-lg font-bold text-xs transition-all primary-bg text-white flex items-center justify-center gap-2 shadow-sm";
                tabManual.className = "touch-btn flex-1 py-2 px-3 rounded-lg font-bold text-xs transition-all text-slate-600 hover:bg-slate-200 flex items-center justify-center gap-2";
                methodScan.classList.remove('hidden');
                methodManual.classList.add('hidden');
                startScannerCamera();
            } else {
                tabManual.className = "touch-btn flex-1 py-2 px-3 rounded-lg font-bold text-xs transition-all primary-bg text-white flex items-center justify-center gap-2 shadow-sm";
                tabScan.className = "touch-btn flex-1 py-2 px-3 rounded-lg font-bold text-xs transition-all text-slate-600 hover:bg-slate-200 flex items-center justify-center gap-2";
                methodManual.classList.remove('hidden');
                methodScan.classList.add('hidden');
                stopScannerCamera();
            }
        }

        // Camera functions
        function toggleCamera() {
            if (isCameraActive) {
                stopScannerCamera();
            } else {
                startScannerCamera();
            }
        }

        function startScannerCamera() {
            hideFormError();
            document.getElementById('qr-placeholder').classList.add('opacity-0');
            setTimeout(() => {
                document.getElementById('qr-placeholder').classList.add('hidden');
            }, 300);
            document.getElementById('laser').classList.remove('hidden');

            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("qr-reader");
            }

            const scanConfig = { fps: 15, qrbox: { width: 220, height: 220 } };

            html5QrCode.start({ facingMode: "environment" }, scanConfig, onScanSuccess, onScanError)
                .then(() => {
                    isCameraActive = true;
                    document.getElementById('cam-btn-text').innerText = "Tắt Camera";
                })
                .catch(err => {
                    html5QrCode.start({ facingMode: "user" }, scanConfig, onScanSuccess, onScanError)
                        .then(() => {
                            isCameraActive = true;
                            document.getElementById('cam-btn-text').innerText = "Tắt Camera";
                        })
                        .catch(err2 => handleScannerCameraFailure(err2));
                });
        }

        // Stop camera safely without throwing errors
        function stopScannerCamera() {
            try {
                if (html5QrCode && isCameraActive) {
                    html5QrCode.stop().then(() => {
                        isCameraActive = false;
                        document.getElementById('cam-btn-text').innerText = "Bật Camera Quét";
                        document.getElementById('qr-placeholder').classList.remove('hidden');
                        document.getElementById('qr-placeholder').classList.remove('opacity-0');
                        document.getElementById('laser').classList.add('hidden');
                    }).catch(err => console.error("Error stopping camera", err));
                }
            } catch (e) {
                console.error(e);
            }
        }

        function handleScannerCameraFailure(err) {
            document.getElementById('qr-placeholder').classList.remove('hidden');
            document.getElementById('qr-placeholder').classList.remove('opacity-0');
            document.getElementById('laser').classList.add('hidden');
            showFormError("Không tìm thấy camera hoặc thiếu quyền truy cập. Vui lòng nhập CCCD bằng phím.");
        }

        function onScanSuccess(decodedText) {
            let scanValue = decodedText.trim();
            // Match standard Vietnam CCCD raw QR fields if QR contains pipe delimiters
            if (scanValue.includes('|')) {
                const fields = scanValue.split('|');
                if (fields[0] && fields[0].length >= 9) {
                    scanValue = fields[0]; // Fetch CCCD number
                }
            }
            stopScannerCamera();
            const inputEl = document.getElementById('cccd-input');
            if (inputEl) inputEl.value = scanValue;
            executeSearchQuery(scanValue);
        }

        function onScanError(error) {
            // Keep scanner running quietly, scan failures occur on empty frames
        }

        // Form search actions
        function handleQuerySubmit(e, inputId) {
            e.preventDefault();
            const keyword = document.getElementById(inputId).value.trim();
            if (!keyword) {
                showFormError("Vui lòng nhập số CCCD hoặc Số báo danh.");
                return;
            }
            executeSearchQuery(keyword);
        }

        function executeSearchQuery(keyword) {
            hideFormError();
            
            // Show scanning state
            const btn = document.querySelector('button[type="submit"]:not(.hidden)');
            let originalText = "";
            if (btn) {
                originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Tra cứu...';
                btn.disabled = true;
            }

            const formData = new FormData();
            formData.append('keyword', keyword);
            formData.append('csrf_token', '<?= csrf_token() ?? '' ?>');

            fetch('<?= url('/huong-dan-nhap-hoc/search') ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    renderResult(res.data);
                } else {
                    showFormError(res.message || 'Không tìm thấy thông tin thí sinh khớp với dữ liệu.');
                }
            })
            .catch(err => {
                showFormError('Lỗi kết nối máy chủ hoặc sự cố mạng. Vui lòng thử lại.');
            })
            .finally(() => {
                if (btn) {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        }

        function renderResult(student) {
            const waitingState = document.getElementById('waiting-state');
            const resultState = document.getElementById('result-state');
            
            waitingState.classList.add('opacity-0', 'pointer-events-none');
            resultState.classList.remove('opacity-0', 'pointer-events-none');
            
            // Add animations
            resultState.classList.remove('animate__animated', 'animate__zoomIn');
            void resultState.offsetWidth; // trigger reflow
            resultState.classList.add('animate__animated', 'animate__zoomIn');

            const successBanner = document.getElementById('success-banner');
            if (successBanner) {
                successBanner.classList.remove('hidden', 'animate__animated', 'animate__fadeInDown');
                void successBanner.offsetWidth; // trigger reflow
                successBanner.classList.add('animate__animated', 'animate__fadeInDown');
            }

            // Set result contents
            document.getElementById('cand-name').innerText = student.ho_ten || 'Thí sinh';
            document.getElementById('cand-cccd').innerText = student.so_cccd || '--';
            document.getElementById('cand-dob').innerText = student.ngay_sinh || '--';
            
            const nganhEl = document.getElementById('cand-major');
            nganhEl.innerText = student.ten_nganh || '--';

            // Set avatar photo
            const imgEl = document.getElementById('cand-avatar');
            if (student.anh_the) {
                imgEl.src = student.anh_the;
            } else {
                imgEl.src = '<?= url("/assets/img/default-avatar.png") ?>';
            }

            // Map image updates based on DB link
            const mapImg = document.getElementById('map-image');
            if (student.link_so_do) {
                mapImg.src = student.link_so_do;
            } else {
                mapImg.src = '<?= url("/assets/img/so_do_nhap_hoc.jpg") ?>';
            }

            // Update status bar bottom (if visible)
            const statusBarText = document.getElementById('status-bar-text');
            if (statusBarText) {
                statusBarText.innerText = `THÍ SINH: ${student.ho_ten.toUpperCase()} ➔ ${student.ban_nhap_hoc || ''} (${student.vi_tri_nhap_hoc || 'Hội trường'})`;
            }
            
            // Trigger confetti
            try {
                var canvas = document.getElementById('confetti-canvas');
                var container = document.getElementById('col-tra-cuu');
                if (canvas && container) {
                    var rect = container.getBoundingClientRect();
                    canvas.width = rect.width;
                    canvas.height = rect.height;
                    var myConfetti = confetti.create(canvas, { resize: true, useWorker: false });
                    
                    var duration = 4000;
                    var end = Date.now() + duration;
                    (function frame() {
                        myConfetti({ particleCount: 3, angle: 60, spread: 55, origin: { x: 0, y: 0.8 }, colors: ['#dc2626','#f59e0b','#10b981','#3b82f6','#ec4899'] });
                        myConfetti({ particleCount: 3, angle: 120, spread: 55, origin: { x: 1, y: 0.8 }, colors: ['#dc2626','#f59e0b','#10b981','#3b82f6','#ec4899'] });
                        if (Date.now() < end) requestAnimationFrame(frame);
                    }());
                }
            } catch(e) { console.log('Confetti error:', e); }
        }

        // Reset forms back to standard state
        function resetSearchForm() {
            stopScannerCamera();
            
            const waitingState = document.getElementById('waiting-state');
            const resultState = document.getElementById('result-state');
            
            waitingState.classList.remove('opacity-0', 'pointer-events-none');
            resultState.classList.add('opacity-0', 'pointer-events-none');
            resultState.classList.remove('animate__animated', 'animate__zoomIn');
            
            const successBanner = document.getElementById('success-banner');
            if (successBanner) {
                successBanner.classList.add('hidden');
                successBanner.classList.remove('animate__animated', 'animate__fadeInDown');
            }

            // Clean input values
            const cccdInput = document.getElementById('cccd-input');
            if (cccdInput) cccdInput.value = '';
            
            const mapImg = document.getElementById('map-image');
            if (mapImg) mapImg.src = '<?= url("/assets/img/so_do_nhap_hoc.jpg") ?>';
            
            try {
                var canvas = document.getElementById('confetti-canvas');
                if(canvas) {
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }
            } catch(e){}

            // Reset bottom status bar
            const statusBarText = document.getElementById('status-bar-text');
            if (statusBarText) {
                statusBarText.innerText = "Vui lòng quét QR trên giấy báo hoặc nhập CCCD để tra cứu";
            }

            hideFormError();
            
            // Re-start camera scanning
            startScannerCamera();
        }

        // Error message display helpers
        function showFormError(msg) {
            const errBox = document.getElementById('error-alert');
            const errMsg = document.getElementById('error-message');
            errMsg.innerText = msg;
            errBox.classList.remove('hidden');
            errBox.classList.add('animate__shakeX');
            setTimeout(() => {
                errBox.classList.remove('animate__shakeX');
            }, 800);
        }

        function hideFormError() {
            document.getElementById('error-alert').classList.add('hidden');
        }

        // Modal triggers
        function zoomMap() {
            const mapSrc = document.getElementById('res-sodo-img').src;
            document.getElementById('img-modal-src').src = mapSrc;
            document.getElementById('img-modal').classList.remove('hidden');
            document.getElementById('img-modal').classList.add('flex');
        }

        function closeImageModal() {
            document.getElementById('img-modal').classList.add('hidden');
            document.getElementById('img-modal').classList.remove('flex');
        }

        // Active screensaver timers & Attract Mode logic
        function resetTimers() {
            // Clear existing timers
            clearTimeout(idleTimer);
            clearTimeout(attractTimer);

            // Rebuild timers
            idleTimer = setTimeout(() => {
                // If student result is active, clear it out after 45s of user idleness
                if (!document.getElementById('result-container').classList.contains('hidden')) {
                    resetSearchForm();
                }
            }, IDLE_RESET_MS);

            attractTimer = setTimeout(() => {
                // Launch Attract mode overlay screensaver
                const overlay = document.getElementById('attract-overlay');
                if (overlay) {
                    overlay.classList.remove('hidden');
                    overlay.classList.add('flex');
                    stopScannerCamera();
                }
            }, ATTRACT_TIMEOUT_MS);
        }

        function dismissAttractMode() {
            const overlay = document.getElementById('attract-overlay');
            if (overlay) {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
            }
            
            // Clean/Reset all search criteria
            resetSearchForm();
            resetTimers();
        }
    </script>
</body>
</html>
