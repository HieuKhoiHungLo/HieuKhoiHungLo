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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Be Vietnam Pro', system-ui, -apple-system, sans-serif; }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        @media (min-width: 640px) {
            html, body {
                height: 100vh;
                height: 100dvh;
                overflow: hidden !important;
            }
        }

        @media (max-width: 639px) {
            html, body {
                min-height: 100vh;
                min-height: 100dvh;
                overflow-y: auto;
            }
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #7f1d1d 100%);
            background-attachment: fixed;
            background-size: cover;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
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
    </style>
</head>
<body class="p-3 sm:p-4">

    <!-- Background orbs -->
    <div class="orb w-96 h-96 bg-red-500 top-0 left-0 -translate-x-1/2 -translate-y-1/2"></div>
    <div class="orb w-80 h-80 bg-indigo-600 bottom-0 right-0 translate-x-1/2 translate-y-1/2"></div>
    <div class="orb w-64 h-64 bg-rose-400 top-1/2 left-1/4"></div>

    <div class="relative z-10 w-full max-w-sm sm:max-w-md mx-auto my-auto">

        <!-- Logo & Header -->
        <div class="text-center mb-4 sm:mb-5 fade-up">
            <div class="floating inline-block mb-2 sm:mb-3">
                <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto bg-white/10 rounded-2xl sm:rounded-3xl flex items-center justify-center border border-white/20 shadow-2xl">
                    <img src="<?= url('/assets/img/Logo.png') ?>" alt="Logo HVU" class="w-10 h-10 sm:w-12 sm:h-12 object-contain" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-university text-white text-xl\'></i>'">
                </div>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-white leading-tight tracking-normal">
                TRA CỨU TRÚNG TUYỂN
            </h1>
            <p class="text-white/80 mt-1 font-semibold text-xs sm:text-sm">
                <?= htmlspecialchars($sessionName) ?> (Năm <?= htmlspecialchars($year) ?>)
            </p>
        </div>

        <!-- Error Message -->
        <?php if (isset($_GET['error'])): ?>
            <div class="mb-3.5 p-3 bg-rose-500/20 border border-rose-400/30 text-rose-200 rounded-xl flex items-center gap-2.5 fade-up">
                <div class="w-8 h-8 bg-rose-500/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-rose-300 text-xs"></i>
                </div>
                <div>
                    <?php if ($_GET['error'] === 'empty'): ?>
                        <div class="font-bold text-xs">Vui lòng nhập thông tin tra cứu!</div>
                    <?php else: ?>
                        <div class="font-bold text-xs">Không tìm thấy kết quả</div>
                        <div class="text-[11px] text-rose-300">Kiểm tra lại CCCD hoặc số báo danh thi THPT của bạn</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search Card -->
        <div class="glass-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-2xl fade-up">

            <form action="<?= url('/tra-cuu-trung-tuyen/search') ?>" method="POST" id="lookupForm">
                <?= csrf_field() ?>

                <div class="mb-3.5">
                    <label class="block text-[10px] sm:text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">
                        Thông tin tra cứu
                    </label>

                    <!-- Tabs -->
                    <div class="flex gap-1.5 mb-3 p-1 bg-gray-100/90 rounded-xl">
                        <button type="button" onclick="setPlaceholder('cccd')" id="tab-cccd"
                            class="flex-1 py-1.5 px-3 rounded-lg text-[11px] font-bold transition-all tab-btn active-tab">
                            <i class="fas fa-id-card mr-1.5 text-[10px]"></i> CCCD
                        </button>
                        <button type="button" onclick="setPlaceholder('sbd')" id="tab-sbd"
                            class="flex-1 py-1.5 px-3 rounded-lg text-[11px] font-bold transition-all tab-btn">
                            <i class="fas fa-hashtag mr-1.5 text-[10px]"></i> SBD thi THPT
                        </button>
                    </div>

                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-xs" id="inputIcon"></i>
                        </div>
                        <input type="text" name="keyword" id="keywordInput" required
                               placeholder="Nhập số CCCD của bạn..."
                               value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>"
                               class="w-full pl-9 pr-3 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl outline-none input-glow transition font-semibold text-gray-800 text-xs sm:text-sm">
                    </div>
                </div>

                <button type="submit"
                    class="btn-primary w-full py-3 text-white font-black rounded-xl flex items-center justify-center gap-2 text-xs sm:text-sm tracking-wide">
                    <i class="fas fa-search text-xs"></i>
                    <span>TRA CỨU KẾT QUẢ</span>
                </button>
            </form>

            <!-- Support note -->
            <div class="mt-3.5 pt-2.5 border-t border-gray-100 text-center text-[11px] text-gray-400">
                Hỗ trợ phòng Tuyển sinh: <strong class="text-gray-600 font-bold">0866.993.468</strong>
            </div>
        </div>

        <!-- Back link -->
        <div class="text-center mt-3.5 fade-up">
            <a href="<?= url('/') ?>" class="text-white/60 hover:text-white text-xs font-medium transition-colors inline-flex items-center gap-1.5">
                <i class="fas fa-arrow-left text-[10px]"></i> Quay lại trang chủ
            </a>
        </div>
    </div>

    <script>
        const tabs = { cccd: 'Nhập số CCCD của bạn...', sbd: 'Nhập số báo danh thi THPT của bạn...' };
        const icons = { cccd: 'fa-id-card', sbd: 'fa-hashtag' };

        function setPlaceholder(type) {
            document.getElementById('keywordInput').placeholder = tabs[type];
            const icon = document.getElementById('inputIcon');
            icon.className = 'fas ' + icons[type] + ' text-gray-400 text-xs';
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active-tab'));
            document.getElementById('tab-' + type).classList.add('active-tab');
        }

        // Prevent double submit
        document.getElementById('lookupForm').addEventListener('submit', function() {
            const btn = this.querySelector('button[type=submit]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin text-xs"></i> Đang tra cứu...';
        });

        // Auto-submit chỉ khi có ?q= và KHÔNG có lỗi error= (tránh vòng lặp vô tận)
        (function() {
            const params = new URLSearchParams(window.location.search);
            const q = params.get('q');
            const hasError = params.has('error');

            if (q && q.trim().length > 0 && !hasError) {
                const input = document.getElementById('keywordInput');
                input.value = q.trim();
                setTimeout(function() {
                    const form = document.getElementById('lookupForm');
                    if (form) form.submit();
                }, 300);
            }
        })();
    </script>
</body>
</html>
