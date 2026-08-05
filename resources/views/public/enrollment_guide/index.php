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
    <main class="flex-1 w-full max-w-[1600px] mx-auto p-3 flex gap-3 min-h-0 relative">

        <!-- COLUMN 1: Tra cứu & Thông tin thí sinh (Desktop: Left Column, Kiosk: Tab 1) -->
        <div id="col-tra-cuu" class="w-full lg:w-[420px] flex-shrink-0 flex flex-col h-full min-h-0 transition-all duration-300">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5 flex-1 flex flex-col min-h-0 overflow-y-auto">
                
                <!-- Section Header -->
                <div class="flex items-center gap-2.5 mb-4 shrink-0">
                    <div class="w-8 h-8 rounded-full primary-bg text-white font-black flex items-center justify-center shadow-md">1</div>
                    <h2 class="txt-title primary-text uppercase">Tra cứu thông tin</h2>
                </div>

                <!-- Input/Scanner Selection Area -->
                <div id="search-container" class="flex flex-col flex-1 min-h-0">
                    <!-- Tab toggle: scan/manual -->
                    <div class="flex p-1 bg-slate-100 border border-slate-200 rounded-xl mb-4 shrink-0">
                        <button type="button" id="tab-scan" onclick="setSearchMethod('scan')" class="touch-btn flex-1 py-2 px-3 rounded-lg font-bold text-xs transition-all primary-bg text-white flex items-center justify-center gap-2 shadow-sm">
                            <i class="fa-solid fa-qrcode"></i> Quét mã QR
                        </button>
                        <button type="button" id="tab-manual" onclick="setSearchMethod('manual')" class="touch-btn flex-1 py-2 px-3 rounded-lg font-bold text-xs transition-all text-slate-600 hover:bg-slate-200 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-keyboard"></i> Nhập số CCCD
                        </button>
                    </div>

                    <!-- Method 1: QR Scanning -->
                    <div id="method-scan" class="flex-1 flex flex-col min-h-0">
                        <div class="relative w-full h-[200px] md:h-[220px] bg-slate-900 rounded-xl overflow-hidden shadow-inner flex items-center justify-center shrink-0 mb-4">
                            <!-- Laser scan line animation -->
                            <div id="laser" class="laser-line hidden"></div>
                            
                            <!-- Scanner corners decoration -->
                            <div class="qr-corner c-tl"></div>
                            <div class="qr-corner c-tr"></div>
                            <div class="qr-corner c-bl"></div>
                            <div class="qr-corner c-br"></div>
                            
                            <!-- Video target element -->
                            <div id="qr-reader" class="w-full h-full absolute inset-0 z-0 bg-transparent"></div>
                            
                            <!-- Attract placeholder -->
                            <div id="qr-placeholder" class="absolute inset-0 z-10 flex flex-col items-center justify-center p-4 text-center bg-slate-950/80 backdrop-blur-sm transition-opacity">
                                <div class="w-14 h-14 bg-blue-900/40 text-blue-400 border border-blue-500/30 rounded-full flex items-center justify-center mb-3 animate-pulse">
                                    <i class="fa-solid fa-expand text-2xl"></i>
                                </div>
                                <p class="text-white font-bold txt-item-title mb-1">Đưa mã QR trên giấy báo vào khung</p>
                                <p class="text-slate-400 txt-desc leading-snug">Hệ thống sẽ tự động quét và hiện thông tin</p>
                            </div>
                        </div>

                        <!-- Manual fallback inside scan tab -->
                        <div class="flex gap-2 shrink-0">
                            <form onsubmit="handleQuerySubmit(event, 'input-cccd-inline')" class="flex-1 flex gap-2">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-id-card text-xs md:text-sm"></i>
                                    </div>
                                    <input type="text" id="input-cccd-inline" placeholder="Nhập Số CCCD / Số báo danh" 
                                           class="touch-input w-full pl-9 pr-3 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-600 focus:border-transparent text-xs md:text-sm font-semibold text-slate-800 outline-none transition-all">
                                </div>
                                <button type="submit" class="touch-btn px-4 py-2.5 rounded-lg primary-bg hover:bg-blue-800 text-white font-bold text-xs md:text-sm flex items-center gap-1.5 shrink-0 transition-colors shadow-sm">
                                    <i class="fa-solid fa-magnifying-glass"></i> Tra cứu
                                </button>
                            </form>
                        </div>

                        <div class="flex justify-between items-center mt-4 shrink-0">
                            <button type="button" id="btn-toggle-cam" onclick="toggleCamera()" class="touch-btn text-xs md:text-sm font-bold text-blue-700 hover:text-blue-950 flex items-center gap-2 transition-colors">
                                <i class="fa-solid fa-camera"></i> <span id="cam-btn-text">Bật Camera Quét</span>
                            </button>
                        </div>
                    </div>

                    <!-- Method 2: Manual Input -->
                    <div id="method-manual" class="flex-1 hidden flex-col pt-3 min-h-0">
                        <form onsubmit="handleQuerySubmit(event, 'input-cccd-full')" class="space-y-4">
                            <div>
                                <label class="block txt-body font-bold text-slate-700 mb-2">Số Căn cước công dân hoặc SBD của thí sinh:</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-id-card text-base"></i>
                                    </div>
                                    <input type="text" id="input-cccd-full" placeholder="Nhập đúng số CCCD (12 số) hoặc SBD" 
                                           class="touch-input w-full pl-10 pr-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-600 focus:border-transparent text-sm md:text-base font-semibold text-slate-800 outline-none transition-all">
                                </div>
                            </div>
                            <button type="submit" class="touch-btn w-full py-3.5 rounded-lg primary-bg hover:bg-blue-800 text-white font-bold text-sm md:text-base flex items-center justify-center gap-2 transition-all shadow-md">
                                <i class="fa-solid fa-magnifying-glass"></i> Tra cứu ngay
                            </button>
                        </form>
                    </div>

                    <!-- Alert message container -->
                    <div id="error-alert" class="hidden mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-xs md:text-sm flex items-start gap-2 shrink-0 animate__animated animate__fadeIn">
                        <i class="fa-solid fa-circle-exclamation mt-0.5 text-base"></i>
                        <div id="error-message" class="font-semibold leading-snug"></div>
                    </div>
                </div>

                <!-- Result Card Info Section (Hidden until query hits success) -->
                <div id="result-container" class="hidden flex-1 flex-col pt-3 border-t border-slate-100 min-h-0">
                    <div class="flex items-center justify-between mb-4 shrink-0">
                        <h3 class="txt-item-title primary-text uppercase">Thông tin thí sinh</h3>
                        <span class="text-[10px] md:text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-check"></i> ĐÃ TRÚNG TUYỂN
                        </span>
                    </div>

                    <!-- Layout: Image & Info -->
                    <div class="flex gap-4 mb-4 shrink-0 items-start">
                        <div class="w-[90px] h-[120px] bg-slate-100 rounded-lg border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center shadow-sm relative">
                            <img id="res-avatar" src="" alt="Avatar" class="w-full h-full object-cover hidden">
                            <i id="res-avatar-icon" class="fa-solid fa-user text-4xl text-slate-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 id="res-name" class="txt-candidate-name text-blue-800 uppercase mb-2 truncate">--</h4>
                            <div class="space-y-1 md:space-y-1.5 text-slate-600 txt-body">
                                <div class="flex justify-between py-0.5 border-b border-slate-50"><span class="font-medium text-slate-400">Ngày sinh:</span><strong id="res-dob" class="text-slate-800">--</strong></div>
                                <div class="flex justify-between py-0.5 border-b border-slate-50"><span class="font-medium text-slate-400">Số CCCD:</span><strong id="res-cccd" class="text-slate-800">--</strong></div>
                                <div class="flex justify-between py-0.5 border-b border-slate-50"><span class="font-medium text-slate-400">Mã hồ sơ:</span><strong id="res-sbd" class="text-slate-800">--</strong></div>
                                <div class="flex justify-between py-0.5 border-b border-slate-50"><span class="font-medium text-slate-400">Ngành:</span><strong id="res-nganh" class="text-slate-800 truncate pl-2 max-w-[180px]" title="">--</strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1 text-slate-600 txt-body mb-4 shrink-0">
                        <div class="flex justify-between py-1 border-b border-slate-50"><span class="font-medium text-slate-400">Khoa quản lý:</span><strong id="res-khoa" class="text-slate-800">--</strong></div>
                        <div class="flex justify-between py-1 border-b border-slate-50"><span class="font-medium text-slate-400">Giáo viên chủ nhiệm:</span><strong id="res-gvcn" class="text-slate-800">--</strong></div>
                    </div>

                    <!-- Target table pointer (Huge visual indicator) -->
                    <div id="desk-card" class="bg-red-50 border-2 border-red-200 rounded-xl p-4 text-center shrink-0 mb-4 transition-all">
                        <div class="txt-desc font-bold text-red-600 uppercase tracking-widest mb-1">VUI LÒNG DI CHUYỂN ĐẾN</div>
                        <div id="res-desk" class="txt-desk-num text-red-600 leading-none">--</div>
                        <div id="res-vi-tri" class="txt-desc font-bold text-slate-600 mt-2">--</div>
                    </div>

                    <!-- Action items -->
                    <div class="mt-auto grid grid-cols-2 gap-3 shrink-0">
                        <button type="button" onclick="showKioskGuide()" id="btn-kiosk-guide" class="hidden touch-btn py-3 px-2 rounded-xl primary-bg text-white font-bold text-sm flex items-center justify-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-arrow-right"></i> Xem đường đi
                        </button>
                        <button type="button" onclick="resetSearchForm()" class="touch-btn py-3 px-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm flex items-center justify-center gap-1.5 transition-colors">
                            <i class="fa-solid fa-arrow-rotate-left"></i> Tra cứu lại
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- COLUMN 2: Sơ đồ & Chỉ dẫn (Desktop: Right Column, Kiosk: Tab 2) -->
        <div id="col-huong-dan" class="flex-1 flex flex-col h-full min-h-0 transition-all duration-300">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5 flex-1 flex flex-col min-h-0 overflow-y-auto">
                
                <!-- Section Header -->
                <div class="flex items-center gap-2.5 mb-4 shrink-0">
                    <div class="w-8 h-8 rounded-full secondary-bg text-white font-black flex items-center justify-center shadow-md">2</div>
                    <h2 class="txt-title secondary-text uppercase">Hướng dẫn & Chỉ dẫn chi tiết</h2>
                </div>

                <div class="flex-1 flex flex-col gap-4 min-h-0">
                    
                    <!-- Card 1: Sơ đồ khu vực làm thủ tục -->
                    <div class="bg-slate-50 rounded-xl border border-slate-200/80 p-3 md:p-4 shrink-0 relative">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="txt-item-title text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-map-location-dot text-blue-600"></i> Sơ đồ khu vực
                            </h3>
                            <button type="button" onclick="zoomMap()" class="touch-btn text-xs font-bold text-blue-700 flex items-center gap-1">
                                <i class="fa-solid fa-magnifying-glass-plus"></i> Phóng to
                            </button>
                        </div>
                        <div class="border border-slate-200 rounded-lg overflow-hidden bg-white max-h-[220px] md:max-h-[260px] flex justify-center items-center shadow-inner relative">
                            <img id="res-sodo-img" src="<?= url('/assets/img/sodo-nhaphoc.png') ?>" alt="Sơ đồ khu vực nhập học" onerror="this.src='https://placehold.co/1000x400/f8fafc/64748b?text=S%C6%A1+%C4%91%E1%BB%93+H%C6%B0%E1%BB%9Bng+d%E1%BA%ABn+Nh%E1%BA%ADp+h%E1%BB%8Dc'" class="w-full h-auto max-h-full object-contain cursor-pointer transition-transform hover:scale-[1.01]" onclick="zoomMap()">
                        </div>
                        
                        <!-- Visual Step path details text below map -->
                        <div class="mt-3 bg-white border border-slate-100 rounded-lg p-2.5 flex items-center justify-center gap-2 flex-wrap">
                            <span class="txt-desc font-bold text-slate-500">HÀNH TRÌNH:</span>
                            <span class="px-2 py-0.5 bg-slate-100 rounded text-[11px] font-bold text-slate-700">CỔNG CHÍNH</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
                            <span id="route-step-1" class="px-2 py-0.5 bg-slate-100 rounded text-[11px] font-bold text-slate-700">BÀN 1</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
                            <span id="route-step-2" class="px-2 py-0.5 bg-slate-100 rounded text-[11px] font-bold text-slate-700">BÀN 2</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
                            <span id="route-step-3" class="px-2 py-0.5 bg-slate-100 rounded text-[11px] font-bold text-slate-700">BÀN 3</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
                            <span id="route-step-4" class="px-2 py-0.5 bg-slate-100 rounded text-[11px] font-bold text-slate-700">BÀN 4</span>
                            <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
                            <span id="route-step-5" class="px-2 py-0.5 bg-slate-100 rounded text-[11px] font-bold text-slate-700">BÀN 5</span>
                        </div>
                    </div>

                    <!-- Cards Split Area -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 flex-1 min-h-0">
                        
                        <!-- Card 2: Các bước nhập học (Timeline) -->
                        <div class="md:col-span-2 flex flex-col min-h-0 bg-slate-50 rounded-xl border border-slate-200/80 p-3 md:p-4">
                            <h3 class="txt-item-title text-slate-800 mb-3 flex items-center gap-2 shrink-0">
                                <i class="fa-solid fa-route text-rose-600"></i> Quy trình thực hiện
                            </h3>
                            <div class="flex-1 overflow-y-auto space-y-2.5 pr-1" id="timeline-container">
                                <!-- Step 1 -->
                                <div id="timeline-step-1" class="flex items-center gap-3 bg-white border border-slate-100 p-2.5 rounded-lg shadow-sm transition-all duration-300">
                                    <div id="timeline-circle-1" class="w-8 h-8 rounded-full bg-slate-400 text-white font-bold flex items-center justify-center shrink-0">1</div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="txt-desc font-bold text-slate-800 uppercase leading-none">BÀN 1: Xác nhận hồ sơ</h4>
                                        <p class="text-[10px] md:text-xs text-slate-500 mt-1">Kiểm tra thông tin tuyển sinh, đối chiếu các giấy tờ gốc</p>
                                    </div>
                                </div>
                                <!-- Step 2 -->
                                <div id="timeline-step-2" class="flex items-center gap-3 bg-white border border-slate-100 p-2.5 rounded-lg shadow-sm transition-all duration-300">
                                    <div id="timeline-circle-2" class="w-8 h-8 rounded-full bg-slate-400 text-white font-bold flex items-center justify-center shrink-0">2</div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="txt-desc font-bold text-slate-800 uppercase leading-none">BÀN 2: Kiểm tra hồ sơ</h4>
                                        <p class="text-[10px] md:text-xs text-slate-500 mt-1">Nộp học bạ THPT, Bằng tốt nghiệp và Giấy khai sinh</p>
                                    </div>
                                </div>
                                <!-- Step 3 -->
                                <div id="timeline-step-3" class="flex items-center gap-3 bg-white border border-slate-100 p-2.5 rounded-lg shadow-sm transition-all duration-300">
                                    <div id="timeline-circle-3" class="w-8 h-8 rounded-full bg-slate-400 text-white font-bold flex items-center justify-center shrink-0">3</div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="txt-desc font-bold text-slate-800 uppercase leading-none">BÀN 3: Thu học phí</h4>
                                        <p class="text-[10px] md:text-xs text-slate-500 mt-1">Hoàn thành học phí kỳ 1 và bảo hiểm y tế bắt buộc</p>
                                    </div>
                                </div>
                                <!-- Step 4 -->
                                <div id="timeline-step-4" class="flex items-center gap-3 bg-white border border-slate-100 p-2.5 rounded-lg shadow-sm transition-all duration-300">
                                    <div id="timeline-circle-4" class="w-8 h-8 rounded-full bg-slate-400 text-white font-bold flex items-center justify-center shrink-0">4</div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="txt-desc font-bold text-slate-800 uppercase leading-none">BÀN 4: Nhận thẻ sinh viên</h4>
                                        <p class="text-[10px] md:text-xs text-slate-500 mt-1">Chụp ảnh thẻ, nhận thẻ SV tạm thời, đăng ký đồng phục</p>
                                    </div>
                                </div>
                                <!-- Step 5 -->
                                <div id="timeline-step-5" class="flex items-center gap-3 bg-white border border-slate-100 p-2.5 rounded-lg shadow-sm transition-all duration-300">
                                    <div id="timeline-circle-5" class="w-8 h-8 rounded-full bg-slate-400 text-white font-bold flex items-center justify-center shrink-0">5</div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="txt-desc font-bold text-slate-800 uppercase leading-none">BÀN 5: Ban Hỗ trợ ký túc xá</h4>
                                        <p class="text-[10px] md:text-xs text-slate-500 mt-1">Làm đơn xin nội trú ký túc xá, tìm phòng trọ giá rẻ ngoại khu</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Giấy tờ & Card 4: Hotline -->
                        <div class="flex flex-col gap-3 min-h-0">
                            <!-- Card 3: Giấy tờ cần chuẩn bị -->
                            <div class="bg-amber-50/50 border border-amber-200 rounded-xl p-3 flex-1 overflow-y-auto">
                                <h3 class="txt-desc font-bold text-amber-800 uppercase mb-2 flex items-center gap-1.5">
                                    <i class="fa-solid fa-file-invoice text-amber-600"></i> Giấy tờ cần có
                                </h3>
                                <ul class="space-y-1.5 txt-desc text-slate-700">
                                    <li class="flex items-start gap-1.5">
                                        <i class="fa-solid fa-circle-check text-amber-600 mt-1 text-[10px]"></i>
                                        <span>Giấy báo trúng tuyển bản chính</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <i class="fa-solid fa-circle-check text-amber-600 mt-1 text-[10px]"></i>
                                        <span>Học bạ THPT (Bản chính + Bản sao)</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <i class="fa-solid fa-circle-check text-amber-600 mt-1 text-[10px]"></i>
                                        <span>Căn cước công dân (Bản công chứng)</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <i class="fa-solid fa-circle-check text-amber-600 mt-1 text-[10px]"></i>
                                        <span>Bằng tốt nghiệp (Hoặc CN tạm thời)</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <i class="fa-solid fa-circle-check text-amber-600 mt-1 text-[10px]"></i>
                                        <span>Giấy khai sinh (Bản sao hợp lệ)</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <i class="fa-solid fa-circle-check text-amber-600 mt-1 text-[10px]"></i>
                                        <span>4 ảnh 3x4 (Chụp không quá 6 tháng)</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Card 4: Thông tin hỗ trợ -->
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 shrink-0 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-lg shrink-0">
                                    <i class="fa-solid fa-headset"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[9px] font-bold text-blue-800 uppercase">Liên hệ hỗ trợ</div>
                                    <a href="tel:0866993468" class="text-sm md:text-base font-black text-blue-700 hover:underline">0866 993 468</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Attract Mode / Screensaver Overlay (Hidden normally, shown on Kiosk idle) -->
    <div id="attract-overlay" class="fixed inset-0 bg-[#0D47A1] z-50 flex flex-col items-center justify-center p-6 text-white text-center cursor-pointer transition-all duration-500 hidden" onclick="dismissAttractMode()">
        <div class="max-w-2xl flex flex-col items-center">
            <!-- Animated HVU logo circle -->
            <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center p-3 shadow-2xl mb-8 animate__animated animate__pulse animate__infinite">
                <img src="<?= url('/assets/img/Logo.png') ?>" alt="HVU Logo" class="w-full h-full object-contain">
            </div>
            
            <h2 class="text-3xl md:text-5xl font-black uppercase tracking-wider mb-2 animate__animated animate__fadeInDown">CHÀO MỪNG TÂN SINH VIÊN</h2>
            <h3 class="text-lg md:text-2xl text-blue-200 font-bold uppercase tracking-widest mb-12 animate__animated animate__fadeInUp">Trường Đại học Hùng Vương</h3>

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
            document.getElementById('input-cccd-inline').value = scanValue;
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
            // Transition effect: Hide search, show results card
            const searchSection = document.getElementById('search-container');
            const resultSection = document.getElementById('result-container');
            
            searchSection.classList.add('hidden');
            resultSection.classList.remove('hidden');
            resultSection.classList.add('flex', 'animate__animated', 'animate__fadeIn');

            // Set result contents
            document.getElementById('res-name').innerText = student.ho_ten || 'Thí sinh';
            document.getElementById('res-cccd').innerText = student.so_cccd || '--';
            document.getElementById('res-sbd').innerText = student.sbd || '--';
            document.getElementById('res-dob').innerText = student.ngay_sinh || '--';
            document.getElementById('res-khoa').innerText = student.ten_khoa || '--';
            document.getElementById('res-gvcn').innerText = student.gvcn || 'Chưa cập nhật';
            
            const nganhEl = document.getElementById('res-nganh');
            nganhEl.innerText = student.ten_nganh || '--';
            nganhEl.title = student.ten_nganh || '--';

            // Set avatar photo
            const imgEl = document.getElementById('res-avatar');
            const iconEl = document.getElementById('res-avatar-icon');
            if (student.anh_the) {
                imgEl.src = student.anh_the;
                imgEl.classList.remove('hidden');
                iconEl.classList.add('hidden');
            } else {
                imgEl.classList.add('hidden');
                iconEl.classList.remove('hidden');
            }

            // Target desk styling
            const deskEl = document.getElementById('res-desk');
            const deskCard = document.getElementById('desk-card');
            const viTriEl = document.getElementById('res-vi-tri');
            const deskName = student.ban_nhap_hoc ? student.ban_nhap_hoc.toUpperCase() : 'BÀN 1';
            
            deskEl.innerText = deskName;
            viTriEl.innerText = student.vi_tri_nhap_hoc || 'Khu vực Hội trường trung tâm';
            
            // Add Pulsing desk glow class to highlight the card
            deskCard.className = "desk-glow rounded-xl p-4 text-center shrink-0 mb-4 transition-all bg-red-50/70";

            // Map image updates based on DB link
            const sodoImg = document.getElementById('res-sodo-img');
            if (student.link_so_do) {
                sodoImg.src = student.link_so_do;
            } else {
                sodoImg.src = defaultMapSrc;
            }

            // Highlight corresponding timeline desk steps
            highlightTimeline(deskName);

            // Update status bar bottom (if visible)
            const statusBarText = document.getElementById('status-bar-text');
            if (statusBarText) {
                statusBarText.innerText = `THÍ SINH: ${student.ho_ten.toUpperCase()} ➔ ${deskName} (${student.vi_tri_nhap_hoc || 'Hội trường'})`;
            }

            // If we are in kiosk/portrait view, reveal the navigation guide tab button
            const btnKioskGuide = document.getElementById('btn-kiosk-guide');
            if (window.innerHeight > window.innerWidth || window.location.search.includes('mode=kiosk')) {
                btnKioskGuide.classList.remove('hidden');
                // Auto switch kiosk view to map guide section after a short delay
                setTimeout(() => {
                    switchKioskTab(2);
                }, 2200);
            }
        }

        // Highlight Active Timeline Steps in vertical step indicator
        function highlightTimeline(deskName) {
            let activeNumber = 1;
            const match = deskName.match(/\d+/);
            if (match) {
                activeNumber = parseInt(match[0]);
            }

            // Highlight steps up to activeNumber, mute steps above it
            for (let i = 1; i <= 5; i++) {
                const stepRow = document.getElementById(`timeline-step-${i}`);
                const stepCircle = document.getElementById(`timeline-circle-${i}`);
                const routePill = document.getElementById(`route-step-${i}`);
                
                if (i <= activeNumber) {
                    // Highlight Active
                    stepRow.classList.remove('opacity-40', 'grayscale');
                    stepRow.classList.add('border-emerald-200', 'bg-emerald-50/40');
                    stepCircle.className = "w-8 h-8 rounded-full bg-emerald-600 text-white font-bold flex items-center justify-center shrink-0 shadow-sm";
                    
                    if (routePill) {
                        routePill.className = "px-2 py-0.5 bg-emerald-600 rounded text-[11px] font-bold text-white shadow-sm";
                    }
                } else {
                    // Muted/De-emphasized
                    stepRow.classList.add('opacity-40', 'grayscale');
                    stepRow.classList.remove('border-emerald-200', 'bg-emerald-50/40');
                    stepCircle.className = "w-8 h-8 rounded-full bg-slate-400 text-white font-bold flex items-center justify-center shrink-0";
                    
                    if (routePill) {
                        routePill.className = "px-2 py-0.5 bg-slate-100 rounded text-[11px] font-bold text-slate-400";
                    }
                }
            }
        }

        // Reset forms back to standard state
        function resetSearchForm() {
            stopScannerCamera();
            
            // Toggle container display back
            const searchSection = document.getElementById('search-container');
            const resultSection = document.getElementById('result-container');
            
            searchSection.classList.remove('hidden');
            resultSection.classList.add('hidden');
            resultSection.classList.remove('flex');

            // Clean input values
            document.getElementById('input-cccd-inline').value = '';
            document.getElementById('input-cccd-full').value = '';
            document.getElementById('res-sodo-img').src = defaultMapSrc;

            // Reset Timeline highlights to default Bàn 1
            highlightTimeline('BÀN 1');

            // Reset bottom status bar
            const statusBarText = document.getElementById('status-bar-text');
            if (statusBarText) {
                statusBarText.innerText = "Vui lòng quét QR trên giấy báo hoặc nhập CCCD để tra cứu";
            }

            // Restore Kiosk guide button hide
            const btnKioskGuide = document.getElementById('btn-kiosk-guide');
            if (btnKioskGuide) {
                btnKioskGuide.classList.add('hidden');
            }

            hideFormError();

            // Auto-switch to Search tab if in Kiosk view
            if (window.innerHeight > window.innerWidth || window.location.search.includes('mode=kiosk')) {
                switchKioskTab(1);
            }

            // Trigger scanner again in scan view
            if (currentSearchMethod === 'scan') {
                startScannerCamera();
            }
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
