<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="description" content="Tra cứu chỉ dẫn bàn nhập học, vị trí hội trường, sơ đồ hướng dẫn và giáo viên chủ nhiệm - Đại học Hùng Vương.">
    <link rel="icon" type="image/png" href="<?= url('/assets/img/Logo.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('/assets/img/Logo.png') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
    <!-- Thư viện quét QR Code từ Camera -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <style>
        * { font-family: 'Be Vietnam Pro', system-ui, -apple-system, sans-serif; }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #831843 100%);
            background-attachment: fixed;
            background-size: cover;
            min-height: 100vh;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(220, 38, 38, 0.5);
        }

        .card-highlight {
            background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
            border: 1px solid #fecdd3;
        }

        #qr-reader video {
            border-radius: 12px;
            object-fit: cover;
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 0.4; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }
        .pulse-subtle { animation: pulse-ring 3s infinite ease-in-out; }
    </style>
</head>
<body class="py-6 px-4 sm:px-6 lg:px-8 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-2xl mx-auto">
        <!-- Header logo -->
        <div class="text-center mb-6">
            <a href="<?= url('/') ?>" class="inline-flex items-center gap-3 group">
                <img src="<?= url('/assets/img/Logo.png') ?>" alt="Logo HVU" class="h-16 w-auto drop-shadow-md group-hover:scale-105 transition-transform duration-300">
                <div class="text-left">
                    <span class="block text-xs font-bold uppercase tracking-wider text-red-300">Trường Đại học Hùng Vương</span>
                    <span class="block text-lg font-black text-white">HƯỚNG DẪN NHẬP HỌC</span>
                </div>
            </a>
        </div>

        <!-- Main Card Container -->
        <div class="glass-panel rounded-3xl p-6 sm:p-8">
            
            <!-- SEARCH / SCAN SECTION -->
            <div id="search-section">
                <div class="text-center mb-6">
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 mb-2">Tra cứu Chỉ dẫn Nhập học</h1>
                    <p class="text-sm text-gray-600">Quét mã QR trên Căn cước công dân hoặc nhập Số CCCD để tìm bàn thủ tục và vị trí hội trường</p>
                </div>

                <!-- Tabs: Scan QR vs Manual Input -->
                <div class="flex p-1.5 bg-gray-100/80 rounded-2xl mb-6">
                    <button type="button" id="tab-scan" onclick="switchTab('scan')" class="flex-1 py-2.5 px-4 rounded-xl text-sm font-bold transition-all duration-200 bg-white text-red-600 shadow-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-qrcode text-base"></i>
                        Quét QR CCCD
                    </button>
                    <button type="button" id="tab-manual" onclick="switchTab('manual')" class="flex-1 py-2.5 px-4 rounded-xl text-sm font-bold transition-all duration-200 text-gray-600 hover:text-gray-900 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-keyboard text-base"></i>
                        Nhập tay CCCD
                    </button>
                </div>

                <!-- Tab 1: QR Scanner View -->
                <div id="view-scan" class="space-y-4">
                    <div class="relative bg-black rounded-2xl overflow-hidden min-h-[260px] flex items-center justify-center border-2 border-dashed border-red-300">
                        <div id="qr-reader" class="w-full"></div>
                        <div id="qr-placeholder" class="text-center p-6 text-white">
                            <div class="w-16 h-16 bg-red-600/20 text-red-400 rounded-full flex items-center justify-center mx-auto mb-3 pulse-subtle">
                                <i class="fa-solid fa-camera text-2xl"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-200">Nhấn nút bên dưới để bật Camera quét mã QR trên CCCD</p>
                        </div>
                    </div>
                    <button type="button" id="btn-toggle-cam" onclick="toggleCamera()" class="w-full py-3.5 px-6 rounded-2xl btn-gradient text-white font-bold text-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-camera"></i>
                        <span id="cam-btn-text">Mở Camera Quét Mã QR</span>
                    </button>
                </div>

                <!-- Tab 2: Manual Input View -->
                <div id="view-manual" class="space-y-4 hidden">
                    <form onsubmit="handleManualSubmit(event)">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                <i class="fa-solid fa-id-card text-lg"></i>
                            </div>
                            <input type="text" id="input-cccd" placeholder="Nhập số CCCD (12 chữ số) hoặc SBD..." 
                                   class="w-full pl-12 pr-4 py-4 rounded-2xl border-2 border-gray-200 focus:border-red-500 focus:ring-4 focus:ring-red-100 text-lg font-bold text-gray-800 placeholder-gray-400 transition-all outline-none">
                        </div>
                        <button type="submit" class="w-full mt-4 py-3.5 px-6 rounded-2xl btn-gradient text-white font-bold text-sm flex items-center justify-center gap-2">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            Tra cứu vị trí nhập học
                        </button>
                    </form>
                </div>

                <!-- Error alert message -->
                <div id="error-alert" class="hidden mt-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 text-lg mt-0.5"></i>
                    <div id="error-message" class="font-medium"></div>
                </div>
            </div>

            <!-- RESULT DISPLAY SECTION -->
            <div id="result-section" class="hidden space-y-6">
                <!-- Header Status -->
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">Đã sẵn sàng nhập học</span>
                    </div>
                    <button type="button" onclick="resetSearch()" class="text-xs font-bold text-gray-500 hover:text-red-600 transition-colors flex items-center gap-1.5 bg-gray-100 hover:bg-red-50 px-3 py-1.5 rounded-xl">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                        Tra cứu lại
                    </button>
                </div>

                <!-- Candidate Info Card -->
                <div class="flex flex-col sm:flex-row items-center gap-4 bg-gray-50/80 p-4 rounded-2xl border border-gray-100">
                    <div id="res-avatar-container" class="shrink-0">
                        <div class="w-20 h-24 rounded-xl bg-gray-200 overflow-hidden flex items-center justify-center border border-gray-300 shadow-sm">
                            <img id="res-avatar" src="" alt="Ảnh thí sinh" class="w-full h-full object-cover hidden">
                            <i id="res-avatar-icon" class="fa-solid fa-user text-3xl text-gray-400"></i>
                        </div>
                    </div>
                    <div class="text-center sm:text-left space-y-1 flex-1">
                        <h2 id="res-name" class="text-xl font-black text-gray-900">--</h2>
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-x-4 gap-y-1 text-xs text-gray-600 font-medium">
                            <span><i class="fa-solid fa-id-card text-red-500 mr-1"></i>CCCD: <strong id="res-cccd" class="text-gray-900">--</strong></span>
                            <span><i class="fa-solid fa-hashtag text-red-500 mr-1"></i>SBD: <strong id="res-sbd" class="text-gray-900">--</strong></span>
                        </div>
                        <div class="text-sm font-bold text-red-700 pt-1">
                            <i class="fa-solid fa-graduation-cap mr-1"></i><span id="res-nganh">--</span>
                        </div>
                    </div>
                </div>

                <!-- Desk & Location Highlight Card -->
                <div class="card-highlight p-5 rounded-2xl space-y-3">
                    <div class="flex items-center gap-3 text-red-800">
                        <div class="w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center text-lg font-bold shadow-md">
                            <i class="fa-solid fa-map-pin"></i>
                        </div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-red-600 block">Bàn thủ tục phân công</span>
                            <h3 id="res-ban" class="text-xl font-black text-red-950">Chưa xếp bàn</h3>
                        </div>
                    </div>

                    <div class="space-y-2 pt-2 border-t border-red-200/60 text-sm text-gray-800">
                        <div class="flex items-start gap-2.5">
                            <i class="fa-solid fa-building text-red-600 mt-1"></i>
                            <div>
                                <span class="font-bold text-gray-900 block">Vị trí nhập học:</span>
                                <span id="res-vitri" class="font-medium text-gray-700">Liên hệ ban tổ chức tại hội trường</span>
                            </div>
                        </div>
                        <div id="res-thoigian-wrapper" class="flex items-start gap-2.5">
                            <i class="fa-solid fa-clock text-red-600 mt-1"></i>
                            <div>
                                <span class="font-bold text-gray-900 block">Thời gian nhập học:</span>
                                <span id="res-thoigian" class="font-medium text-gray-700">--</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map / Guide Diagram Image -->
                <div id="res-sodo-wrapper" class="space-y-2 hidden">
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-500 flex items-center gap-2">
                        <i class="fa-solid fa-map text-red-600"></i>
                        Sơ đồ chỉ dẫn đường đi
                    </h4>
                    <div class="rounded-2xl border border-gray-200 overflow-hidden bg-gray-100">
                        <img id="res-sodo-img" src="" alt="Sơ đồ chỉ dẫn" class="w-full h-auto max-h-[380px] object-contain mx-auto cursor-pointer hover:opacity-95 transition-opacity" onclick="openImageModal(this.src)">
                    </div>
                </div>

                <!-- Advisor / Homeroom Teacher (GVCN) -->
                <div id="res-gvcn-wrapper" class="bg-blue-50/80 border border-blue-100 p-4 rounded-2xl space-y-2 hidden">
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-blue-700 flex items-center gap-2">
                        <i class="fa-solid fa-user-tie text-blue-600"></i>
                        Thông tin Cán bộ / Giáo viên chủ nhiệm
                    </h4>
                    <p id="res-gvcn" class="text-base font-bold text-blue-950 flex items-center gap-2">
                        --
                    </p>
                </div>

                <!-- Bottom Button -->
                <button type="button" onclick="resetSearch()" class="w-full py-3.5 px-6 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-sm transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-qrcode"></i>
                    Quét hoặc Nhập tra cứu cho thí sinh khác
                </button>
            </div>

        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-red-200/80 mt-6">
            © 2026 Trường Đại học Hùng Vương - Hội đồng Tuyển sinh & Nhập học
        </p>
    </div>

    <!-- Image Modal for Fullscreen Map View -->
    <div id="img-modal" class="fixed inset-0 z-50 bg-black/90 hidden items-center justify-center p-4 backdrop-blur-sm" onclick="closeImageModal()">
        <div class="relative max-w-4xl w-full max-h-full">
            <button type="button" class="absolute -top-10 right-0 text-white text-2xl font-bold hover:text-red-400" onclick="closeImageModal()">&times; Đóng</button>
            <img id="img-modal-src" src="" class="w-full h-auto max-h-[85vh] object-contain rounded-xl shadow-2xl mx-auto">
        </div>
    </div>

    <!-- JavaScript Logic -->
    <script>
        let html5QrCode = null;
        let isCamScanning = false;

        function switchTab(tab) {
            const btnScan = document.getElementById('tab-scan');
            const btnManual = document.getElementById('tab-manual');
            const viewScan = document.getElementById('view-scan');
            const viewManual = document.getElementById('view-manual');

            hideError();

            if (tab === 'scan') {
                btnScan.className = "flex-1 py-2.5 px-4 rounded-xl text-sm font-bold transition-all duration-200 bg-white text-red-600 shadow-sm flex items-center justify-center gap-2";
                btnManual.className = "flex-1 py-2.5 px-4 rounded-xl text-sm font-bold transition-all duration-200 text-gray-600 hover:text-gray-900 flex items-center justify-center gap-2";
                viewScan.classList.remove('hidden');
                viewManual.classList.add('hidden');
            } else {
                btnManual.className = "flex-1 py-2.5 px-4 rounded-xl text-sm font-bold transition-all duration-200 bg-white text-red-600 shadow-sm flex items-center justify-center gap-2";
                btnScan.className = "flex-1 py-2.5 px-4 rounded-xl text-sm font-bold transition-all duration-200 text-gray-600 hover:text-gray-900 flex items-center justify-center gap-2";
                viewManual.classList.remove('hidden');
                viewScan.classList.add('hidden');
                stopCamera();
            }
        }

        function toggleCamera() {
            if (isCamScanning) {
                stopCamera();
            } else {
                startCamera();
            }
        }

        function startCamera() {
            hideError();
            document.getElementById('qr-placeholder').classList.add('hidden');

            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("qr-reader");
            }

            const config = { fps: 10, qrbox: { width: 250, height: 250 } };

            html5QrCode.start(
                { facingMode: "environment" },
                config,
                onScanSuccess,
                onScanError
            ).then(() => {
                isCamScanning = true;
                document.getElementById('cam-btn-text').innerText = "Tắt Camera";
            }).catch(err => {
                showError("Không thể mở Camera: " + (err.message || err));
                document.getElementById('qr-placeholder').classList.remove('hidden');
            });
        }

        function stopCamera() {
            if (html5QrCode && isCamScanning) {
                html5QrCode.stop().then(() => {
                    isCamScanning = false;
                    document.getElementById('cam-btn-text').innerText = "Mở Camera Quét Mã QR";
                    document.getElementById('qr-placeholder').classList.remove('hidden');
                }).catch(err => console.log(err));
            }
        }

        function onScanSuccess(decodedText) {
            // Mã QR trên CCCD Việt Nam định dạng: CCCD|CMND|Họ Tên|Ngày Sinh|Giới Tính|Địa Chỉ|Ngày Cấp
            // Ví dụ: 001095012345|123456789|Nguyễn Văn A|15081995|Nam|...
            let cccd = decodedText.trim();
            if (cccd.includes('|')) {
                const parts = cccd.split('|');
                if (parts[0] && parts[0].length >= 9) {
                    cccd = parts[0];
                }
            }

            stopCamera();
            executeSearch(cccd);
        }

        function onScanError(error) {
            // Ignore frame scan errors
        }

        function handleManualSubmit(e) {
            e.preventDefault();
            const val = document.getElementById('input-cccd').value.trim();
            if (!val) {
                showError("Vui lòng nhập số CCCD hoặc SBD.");
                return;
            }
            executeSearch(val);
        }

        function executeSearch(keyword) {
            hideError();

            const formData = new FormData();
            formData.append('keyword', keyword);

            fetch('<?= url('/huong-dan-nhap-hoc/search') ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    renderResult(res.data);
                } else {
                    showError(res.message || 'Không tìm thấy thông tin thí sinh.');
                }
            })
            .catch(err => {
                showError('Có lỗi xảy ra khi kết nối máy chủ. Vui lòng thử lại.');
            });
        }

        function renderResult(data) {
            document.getElementById('search-section').classList.add('hidden');
            document.getElementById('result-section').classList.remove('hidden');

            document.getElementById('res-name').innerText = data.ho_ten || 'Thí sinh';
            document.getElementById('res-cccd').innerText = data.so_cccd || '--';
            document.getElementById('res-sbd').innerText = data.sbd || '--';
            document.getElementById('res-nganh').innerText = (data.ten_nganh ? data.ten_nganh : '') + (data.ten_khoa ? ' - Khoa ' + data.ten_khoa : '');

            // Avatar
            const imgEl = document.getElementById('res-avatar');
            const iconEl = document.getElementById('res-avatar-icon');
            if (data.anh_the) {
                imgEl.src = data.anh_the;
                imgEl.classList.remove('hidden');
                iconEl.classList.add('hidden');
            } else {
                imgEl.classList.add('hidden');
                iconEl.classList.remove('hidden');
            }

            // Desk & Location
            document.getElementById('res-ban').innerText = data.ban_nhap_hoc || 'Bàn tiếp đón chung (Vui lòng hỏi ban tổ chức)';
            document.getElementById('res-vitri').innerText = data.vi_tri_nhap_hoc || 'Chưa cập nhật vị trí chi tiết';

            if (data.thoi_gian_nhap) {
                document.getElementById('res-thoigian-wrapper').classList.remove('hidden');
                document.getElementById('res-thoigian').innerText = data.thoi_gian_nhap;
            } else {
                document.getElementById('res-thoigian-wrapper').classList.add('hidden');
            }

            // Map / Diagram Image
            const sodoWrapper = document.getElementById('res-sodo-wrapper');
            const sodoImg = document.getElementById('res-sodo-img');
            if (data.link_so_do) {
                sodoImg.src = data.link_so_do;
                sodoWrapper.classList.remove('hidden');
            } else {
                sodoWrapper.classList.add('hidden');
            }

            // Homeroom Teacher / Advisor (GVCN)
            const gvcnWrapper = document.getElementById('res-gvcn-wrapper');
            const gvcnEl = document.getElementById('res-gvcn');
            if (data.gvcn) {
                gvcnEl.innerHTML = `<i class="fa-solid fa-address-book text-blue-600"></i> ${escapeHtml(data.gvcn)}`;
                gvcnWrapper.classList.remove('hidden');
            } else {
                gvcnWrapper.classList.add('hidden');
            }
        }

        function resetSearch() {
            document.getElementById('result-section').classList.add('hidden');
            document.getElementById('search-section').classList.remove('hidden');
            document.getElementById('input-cccd').value = '';
            hideError();
        }

        function showError(msg) {
            const errBox = document.getElementById('error-alert');
            const errMsg = document.getElementById('error-message');
            errMsg.innerText = msg;
            errBox.classList.remove('hidden');
        }

        function hideError() {
            document.getElementById('error-alert').classList.add('hidden');
        }

        function escapeHtml(text) {
            return String(text).replace(/[&<>"']/g, function(m) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
            });
        }

        function openImageModal(src) {
            document.getElementById('img-modal-src').src = src;
            document.getElementById('img-modal').classList.remove('hidden');
            document.getElementById('img-modal').classList.add('flex');
        }

        function closeImageModal() {
            document.getElementById('img-modal').classList.add('hidden');
            document.getElementById('img-modal').classList.remove('flex');
        }
    </script>

</body>
</html>
