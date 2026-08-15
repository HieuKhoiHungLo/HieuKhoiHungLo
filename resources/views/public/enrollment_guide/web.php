<?php
$testHour = isset($_GET['test_hour']) ? (int)$_GET['test_hour'] : null;
$defaultSession = 'morning';
if ($testHour !== null) {
    $defaultSession = ($testHour >= 12) ? 'afternoon' : 'morning';
} else {
    // Real absolute system time check: boundary is 12:00 on August 16, 2026
    $boundaryTimestamp = strtotime('2026-08-16 12:00:00');
    $currentTimestamp = time();
    $defaultSession = ($currentTimestamp >= $boundaryTimestamp) ? 'afternoon' : 'morning';
}
?>
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

                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-clock text-amber-400"></i>
                    <div>
                        <span id="current-time" class="font-bold text-white">--:--:--</span>
                    </div>
                </div>

            </div>
        </div>
    </header>



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
                <div id="waiting-state" class="absolute inset-0 z-10 transition-opacity duration-300 bg-white flex items-center justify-center p-4 md:p-8">
                    <img id="img-idle-left" src="<?= url("/uploads") ?>/media/1786762752_6a7fd600ab089.jpg" alt="Hướng dẫn nhập học" class="max-w-full max-h-full object-contain rounded-xl shadow-sm border border-slate-100">
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
                        
                        <div class="grid grid-cols-[150px_1fr] gap-x-4 gap-y-3 max-w-[420px] mx-auto w-full px-2">
                            <div class="text-slate-500 text-sm flex items-center">Họ và tên:</div>
                            <div id="cand-name" class="font-black text-red-600 text-lg uppercase leading-tight flex items-center">--</div>

                            <div class="text-slate-500 text-sm flex items-center">Ngày sinh:</div>
                            <div id="cand-dob" class="font-bold text-red-600 text-base flex items-center">--</div>

                            <div class="text-slate-500 text-sm flex items-center">Số CCCD:</div>
                            <div id="cand-cccd" class="font-bold text-red-600 text-base flex items-center">--</div>

                            <div class="col-span-2 border-t border-slate-100 my-1"></div>

                            <div class="text-slate-500 text-sm flex items-start">Ngành trúng tuyển:</div>
                            <div id="cand-major" class="font-black text-red-600 text-base leading-tight flex items-start">--</div>
                        </div>

                        <!-- Hidden fields to prevent JavaScript errors -->
                        <div style="display: none;">
                            <span id="cand-desk"></span>
                            <span id="cand-location"></span>
                            <span id="cand-gvcn"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMN 2: Middle 2 cols (Scanner & Text Directory) -->
        <div class="col-span-1 lg:col-span-2 flex flex-col gap-4 h-full min-h-0">
            <!-- Top: Scanner -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex flex-col shrink-0 relative">

                
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
            
            <!-- Bottom: Directory (Morning / Afternoon Tabs) -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-2 pb-1.5 flex-1 overflow-hidden flex flex-col min-h-0">
                <!-- Tab Headers -->
                <div class="flex border-b border-slate-200 mb-2 text-xs font-bold text-center shrink-0">
                    <button type="button" onclick="switchDirectoryTab('morning')" id="dir-tab-morning" class="flex-1 pb-1 border-b-2 border-blue-600 text-blue-600 cursor-pointer">Sáng (Từ 7h30)</button>
                    <button type="button" onclick="switchDirectoryTab('afternoon')" id="dir-tab-afternoon" class="flex-1 pb-1 border-b-2 border-transparent text-slate-400 hover:text-slate-600 cursor-pointer">Chiều (Từ 13h30)</button>
                </div>

                <!-- Tab Contents Container -->
                <div class="flex-1 overflow-y-auto pr-0.5">
                    <!-- Morning panel -->
                    <div id="dir-panel-morning" class="space-y-1.5">
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

                    <!-- Afternoon panel -->
                    <div id="dir-panel-afternoon" class="space-y-1.5 hidden">
                        <div>
                            <div class="text-red-600 font-bold text-[11px] md:text-xs uppercase leading-tight">Bàn 1, BÀN 2 (Hội trường trung tâm)</div>
                            <div class="text-slate-700 text-[11px] md:text-xs mt-0.5 leading-snug">Ngành Kế toán, Quản trị kinh doanh, Tài chính ngân hàng, Kinh tế</div>
                        </div>
                        <div>
                            <div class="text-red-600 font-bold text-[11px] md:text-xs uppercase leading-tight">Bàn 3, BÀN 4 (Hội trường trung tâm)</div>
                            <div class="text-slate-700 text-[11px] md:text-xs mt-0.5 leading-snug">Ngành Tâm lý, Công tác xã hội</div>
                        </div>
                        <div>
                            <div class="text-red-600 font-bold text-[11px] md:text-xs uppercase leading-tight">Bàn 6, BÀN 8 (Góc văn hoá Hàn Quốc – Tầng 3)</div>
                            <div class="text-slate-700 text-[11px] md:text-xs mt-0.5 leading-snug">Sư phạm Tiếng Anh, Ngôn ngữ Anh</div>
                        </div>
                        <div>
                            <div class="text-red-600 font-bold text-[11px] md:text-xs uppercase leading-tight">Bàn 10, BÀN 11, BÀN 12 (Hội trường tầng 3)</div>
                            <div class="text-slate-700 text-[11px] md:text-xs mt-0.5 leading-snug">Tiếp thí sinh mới, Nhập học không đúng thời gian quy định</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMN 3: Right 3 cols (Image Map & Guide Details) -->
        <div id="col-so-do" class="col-span-1 lg:col-span-3 flex flex-col h-full min-h-0 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Initial State (Waiting to scan) -->
            <div id="map-waiting-state" class="flex-1 transition-opacity duration-300 bg-white flex items-center justify-center p-4 md:p-8">
                <img id="img-idle-right" src="<?= url("/uploads") ?>/media/1786762025_6a7fd329ed68b.jpg" alt="Sơ đồ vị trí bàn nhập học" class="max-w-[80%] max-h-[300px] object-contain rounded-xl shadow-sm border border-slate-100">
            </div>

            <!-- Result State (Shown when student data is loaded) -->
            <div id="map-result-state" class="hidden flex flex-col h-full p-6 min-h-0">
                <!-- Top Header: Invitation -->
                <div class="text-center mb-4 shrink-0">
                    <h3 class="text-xs md:text-sm font-black text-slate-400 uppercase tracking-widest">MỜI BẠN LÀM THỦ TỤC TẠI:</h3>
                    <div id="res-location-name" class="text-2xl md:text-3xl font-black text-red-600 mt-2 leading-tight">--</div>
                    <div id="res-desk-num" class="text-lg md:text-xl font-black text-blue-900 mt-1 leading-tight">--</div>
                </div>

                <!-- Middle: Map Image -->
                <div class="flex-1 min-h-0 w-full rounded-xl overflow-hidden border border-slate-200 shadow-sm relative group cursor-pointer" onclick="zoomMap()">
                    <img id="map-image" src="<?= url("/assets") ?>/img/so_do_nhap_hoc.jpg" alt="Sơ đồ nhập học" class="w-full h-full object-contain bg-slate-50">
                    <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                        <span class="bg-black/60 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-magnifying-glass-plus"></i> Phóng to sơ đồ
                        </span>
                    </div>
                </div>

                <!-- Bottom: GVCN Info -->
                <div class="mt-4 p-4 bg-slate-50 rounded-xl border border-slate-200 flex items-start gap-3 shrink-0">
                    <div class="p-2 bg-blue-600 text-white rounded-lg shrink-0 mt-0.5 shadow-sm">
                        <i class="fa-solid fa-user-tie text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Giáo viên chủ nhiệm (GVCN)</div>
                        <div id="res-gvcn-info" class="text-sm font-bold text-slate-800 mt-0.5 leading-snug break-words">--</div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Attract Mode / Screensaver Overlay (Hidden normally, shown on Kiosk idle) -->
    <div id="attract-overlay" class="fixed inset-0 z-50 cursor-pointer transition-all duration-500 hidden bg-[#0D47A1]" onclick="dismissAttractMode()">
        <img src="<?= url("/uploads") ?>/media/1786768072_6a7feac885b63.jpg" alt="Chào mừng Tân Sinh viên" class="w-full h-full object-fill">
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

    <!-- Kiosk Settings Modal -->
    <div id="kioskSettingsModal" class="hidden fixed inset-0 bg-slate-900/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-in zoom-in-95 duration-200 border border-slate-100">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-gears text-blue-600 text-lg"></i>
                    <h3 class="text-base font-bold text-slate-800">Cấu hình Thiết bị Kiosk</h3>
                </div>
                <button type="button" onclick="closeKioskSettingsModal()" class="w-7 h-7 flex items-center justify-center rounded-full bg-slate-200 text-slate-500 hover:bg-slate-300 transition">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Thời gian hiển thị kết quả (giây)</label>
                    <input type="number" id="kiosk_display_seconds_input" min="3" max="180" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <p class="text-[10px] text-slate-400 mt-1">Sau số giây này, màn hình sẽ tự động xóa thông tin để quét thí sinh mới.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Thời gian chờ về màn hình quảng cáo (phút)</label>
                    <input type="number" id="kiosk_idle_minutes_input" min="1" max="60" class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <p class="text-[10px] text-slate-400 mt-1">Khi thiết bị không tương tác sau số phút này, màn hình quảng cáo sẽ hiện lên.</p>
                </div>
                <div class="pt-2 border-t border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer mt-2">
                        <input type="checkbox" id="kiosk_tts_enabled_input" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Phát âm thanh chào mừng (Giọng đọc)</span>
                    </label>
                    <p class="text-[10px] text-slate-400 mt-1">Tự động đọc lời chào "Xin chào [Tên]". (Mặc định: Tắt)</p>
                </div>
            </div>
            <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-between items-center gap-2">
                <button type="button" onclick="resetKioskCounter()" class="px-4 py-2 rounded-xl bg-red-100 text-red-600 hover:bg-red-200 text-xs font-bold transition flex items-center gap-2"><i class="fa-solid fa-rotate-right"></i> Reset Số Đếm</button>
                <div class="flex gap-2">
                    <button type="button" onclick="closeKioskSettingsModal()" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-500 hover:bg-slate-100 transition">Hủy</button>
                    <button type="button" onclick="saveKioskSettings()" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-bold text-white shadow-md transition">Lưu cấu hình</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript logic -->
    <script>
        // Minimal Javascript for Web Search
        function switchDirectoryTab(session) {
            const morningTab = document.getElementById('dir-tab-morning');
            const afternoonTab = document.getElementById('dir-tab-afternoon');
            const morningList = document.getElementById('dir-list-morning');
            const afternoonList = document.getElementById('dir-list-afternoon');

            if (session === 'morning') {
                if(morningTab) { morningTab.classList.replace('border-transparent', 'border-blue-600'); morningTab.classList.replace('text-slate-400', 'text-blue-600'); }
                if(afternoonTab) { afternoonTab.classList.replace('border-blue-600', 'border-transparent'); afternoonTab.classList.replace('text-blue-600', 'text-slate-400'); }
                if(morningList) morningList.classList.remove('hidden');
                if(afternoonList) afternoonList.classList.add('hidden');
            } else {
                if(afternoonTab) { afternoonTab.classList.replace('border-transparent', 'border-blue-600'); afternoonTab.classList.replace('text-slate-400', 'text-blue-600'); }
                if(morningTab) { morningTab.classList.replace('border-blue-600', 'border-transparent'); morningTab.classList.replace('text-blue-600', 'text-slate-400'); }
                if(afternoonList) afternoonList.classList.remove('hidden');
                if(morningList) morningList.classList.add('hidden');
            }
        }

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
            
            const btn = document.querySelector('button[type="submit"]:not(.hidden)');
            let originalText = "";
            if (btn) {
                originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Đang tìm...';
                btn.disabled = true;
            }

            const formData = new FormData();
            formData.append('keyword', keyword);
            formData.append('csrf_token', '<?= csrf_token() ?? '' ?>');

            fetch('<?= url("/huong-dan-nhap-hoc/search") ?>', {
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
            
            if(waitingState) waitingState.classList.add('opacity-0', 'pointer-events-none');
            if(resultState) resultState.classList.remove('opacity-0', 'pointer-events-none');

            const mapWaitingState = document.getElementById('map-waiting-state');
            const mapResultState = document.getElementById('map-result-state');
            if (mapWaitingState) mapWaitingState.classList.add('hidden');
            if (mapResultState) mapResultState.classList.remove('hidden');

            const resDesk = document.getElementById('res-desk-num');
            if (resDesk) resDesk.innerText = student.ban_nhap_hoc ? "BÀN " + student.ban_nhap_hoc : '--';
            const resLoc = document.getElementById('res-location-name');
            if (resLoc) resLoc.innerText = student.vi_tri_nhap_hoc || student.dia_diem || '--';
            const resGvcn = document.getElementById('res-gvcn-info');
            if (resGvcn) resGvcn.innerText = student.gvcn || '--';

            const nameEl = document.getElementById('cand-name');
            if (nameEl) nameEl.innerText = student.ho_ten || '';
            const dobEl = document.getElementById('cand-dob');
            if (dobEl) dobEl.innerText = student.ngay_sinh || '';
            const cccdEl = document.getElementById('cand-cccd');
            if (cccdEl) cccdEl.innerText = student.so_cccd || '';
            const majorEl = document.getElementById('cand-major');
            if (majorEl) majorEl.innerText = student.nganh_trung_tuyen || '';

            const avatarEl = document.getElementById('cand-avatar');
            if (avatarEl) {
                avatarEl.src = student.anh_the || '<?= url("/assets/img/default-avatar.png") ?>';
            }

            const mapImage = document.getElementById('map-image');
            if (mapImage) {
                if (student.link_so_do) {
                    mapImage.src = student.link_so_do;
                } else {
                    mapImage.src = '<?= url("/assets/img/so_do_nhap_hoc.jpg") ?>';
                }
            }

            document.querySelectorAll('.map-highlight').forEach(el => el.classList.remove('map-highlight'));
            if (student.ban_nhap_hoc) {
                const mapRowId = 'map-row-' + String(student.ban_nhap_hoc).trim();
                const mapRow = document.getElementById(mapRowId);
                if (mapRow) {
                    mapRow.classList.add('map-highlight');
                    mapRow.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
                }
            }
        }

        function resetSearchForm() {
            const waitingState = document.getElementById('waiting-state');
            const resultState = document.getElementById('result-state');
            if(waitingState) waitingState.classList.remove('opacity-0', 'pointer-events-none');
            if(resultState) resultState.classList.add('opacity-0', 'pointer-events-none');

            const mapWaitingState = document.getElementById('map-waiting-state');
            const mapResultState = document.getElementById('map-result-state');
            if (mapWaitingState) mapWaitingState.classList.remove('hidden');
            if (mapResultState) mapResultState.classList.add('hidden');

            const inputEl = document.getElementById('cccd-input');
            if (inputEl) inputEl.value = '';
            
            const avatarEl = document.getElementById('cand-avatar');
            if (avatarEl) {
                avatarEl.src = '<?= url("/assets/img/default-avatar.png") ?>';
            }
            
            const mapImage = document.getElementById('map-image');
            if (mapImage) {
                mapImage.src = '<?= url("/assets/img/so_do_nhap_hoc.jpg") ?>';
            }
            
            document.querySelectorAll('.map-highlight').forEach(el => el.classList.remove('map-highlight'));
            hideFormError();
        }

        function showFormError(msg) {
            const errAlert = document.getElementById('error-alert');
            const errMsg = document.getElementById('error-message');
            if (errAlert && errMsg) {
                errMsg.innerText = msg;
                errAlert.classList.remove('hidden');
                errAlert.classList.add('animate__fadeIn');
            } else {
                alert(msg);
            }
        }

        function hideFormError() {
            const errAlert = document.getElementById('error-alert');
            if (errAlert) {
                errAlert.classList.add('hidden');
                errAlert.classList.remove('animate__fadeIn');
            }
        }

        function zoomMap() {
            const mapImage = document.getElementById('map-image');
            const modalImg = document.getElementById('modal-img');
            const modal = document.getElementById('img-modal');
            if (mapImage && modalImg && modal) {
                modalImg.src = mapImage.src;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeImageModal() {
            const modal = document.getElementById('img-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto';
            }
        }

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
                const timeEl = document.getElementById('current-time');
                if (timeEl) {
                    timeEl.innerText = timeString;
                }
            }
            updateClock();
            setInterval(updateClock, 1000);
        }

        document.addEventListener('DOMContentLoaded', () => {
            initClock();
            switchDirectoryTab('<?= $defaultSession ?? "morning" ?>');
        });
    </script>
</body>
</html>
