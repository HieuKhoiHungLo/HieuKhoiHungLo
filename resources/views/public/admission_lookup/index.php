<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="description" content="Tra cứu thông báo trúng tuyển Đại học Hùng Vương năm 2026. Nhập CCCD, số báo danh hoặc email để xem kết quả.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Outfit', sans-serif; }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #7f1d1d 100%);
            min-height: 100vh;
        }

        .glass {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .glass-white {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .input-glow:focus {
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.25);
            border-color: #dc2626;
        }

        .btn-primary {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            box-shadow: 0 8px 32px rgba(220, 38, 38, 0.4);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(220, 38, 38, 0.5);
        }
        .btn-primary:active { transform: translateY(0); }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-15px) rotate(1deg); }
            66% { transform: translateY(-8px) rotate(-1deg); }
        }
        .floating { animation: float 6s ease-in-out infinite; }

        @keyframes fade-up {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fade-up 0.7s ease forwards; }
        .delay-1 { animation-delay: 0.1s; opacity: 0; }
        .delay-2 { animation-delay: 0.2s; opacity: 0; }
        .delay-3 { animation-delay: 0.3s; opacity: 0; }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            pointer-events: none;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 overflow-x-hidden">

    <!-- Background orbs -->
    <div class="orb w-96 h-96 bg-red-500 top-0 left-0 -translate-x-1/2 -translate-y-1/2"></div>
    <div class="orb w-80 h-80 bg-indigo-600 bottom-0 right-0 translate-x-1/2 translate-y-1/2"></div>
    <div class="orb w-64 h-64 bg-rose-400 top-1/2 left-1/4"></div>

    <div class="relative z-10 w-full max-w-lg">

        <!-- Logo & Header -->
        <div class="text-center mb-8 fade-up">
            <div class="floating inline-block mb-5">
                <div class="w-20 h-20 mx-auto bg-white/10 rounded-3xl flex items-center justify-center border border-white/20 shadow-2xl">
                    <img src="<?= url('/assets/img/Logo.png') ?>" alt="Logo HVU" class="w-14 h-14 object-contain" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-university text-white text-3xl\'></i>'">
                </div>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-white leading-tight tracking-tight">
                TRA CỨU TRÚNG TUYỂN
            </h1>
            <p class="text-white/60 mt-2 font-medium text-sm md:text-base">
                Thông báo Ghi danh Tuyển sinh Đại học Hùng Vương 2026
            </p>
        </div>

        <!-- Error Message -->
        <?php if (isset($_GET['error'])): ?>
            <div class="mb-5 p-4 bg-rose-500/20 border border-rose-400/30 text-rose-200 rounded-2xl flex items-center gap-3 fade-up delay-1">
                <div class="w-10 h-10 bg-rose-500/30 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-rose-300"></i>
                </div>
                <div>
                    <?php if ($_GET['error'] === 'empty'): ?>
                        <div class="font-bold text-sm">Vui lòng nhập thông tin tra cứu!</div>
                    <?php else: ?>
                        <div class="font-bold text-sm">Không tìm thấy kết quả</div>
                        <div class="text-xs text-rose-300 mt-0.5">Kiểm tra lại CCCD, số báo danh hoặc email của bạn</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search Card -->
        <div class="glass-white rounded-3xl p-8 shadow-2xl fade-up delay-2">

            <form action="<?= url('/tra-cuu-trung-tuyen/search') ?>" method="POST" id="lookupForm">
                <?= csrf_field() ?>

                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                        Thông tin tra cứu
                    </label>

                    <!-- Tabs -->
                    <div class="flex gap-2 mb-4 p-1 bg-gray-100 rounded-2xl">
                        <button type="button" onclick="setPlaceholder('cccd')" id="tab-cccd"
                            class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all tab-btn active-tab">
                            <i class="fas fa-id-card mr-1"></i> CCCD
                        </button>
                        <button type="button" onclick="setPlaceholder('sbd')" id="tab-sbd"
                            class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all tab-btn">
                            <i class="fas fa-hashtag mr-1"></i> Số báo danh
                        </button>
                        <button type="button" onclick="setPlaceholder('email')" id="tab-email"
                            class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all tab-btn">
                            <i class="fas fa-envelope mr-1"></i> Email
                        </button>
                    </div>

                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400" id="inputIcon"></i>
                        </div>
                        <input type="text" name="keyword" id="keywordInput" required
                               placeholder="Nhập số CCCD của bạn..."
                               value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>"
                               class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl outline-none input-glow transition font-semibold text-gray-800 text-base">
                    </div>
                </div>

                <button type="submit"
                    class="btn-primary w-full py-4 text-white font-black rounded-2xl flex items-center justify-center gap-3 text-base">
                    <i class="fas fa-search"></i>
                    TRA CỨU KẾT QUẢ
                </button>
            </form>

            <!-- Divider -->
            <div class="flex items-center gap-3 my-6">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="text-xs text-gray-400 font-medium">Hướng dẫn</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

            <!-- Tips -->
            <div class="space-y-3">
                <div class="flex items-start gap-3 text-sm text-gray-500">
                    <div class="w-6 h-6 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-id-card text-blue-400 text-xs"></i>
                    </div>
                    <span>Nhập <strong class="text-gray-700">số CCCD/CMND</strong> (12 số) để tra cứu</span>
                </div>
                <div class="flex items-start gap-3 text-sm text-gray-500">
                    <div class="w-6 h-6 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-envelope text-green-400 text-xs"></i>
                    </div>
                    <span>Hoặc nhập <strong class="text-gray-700">địa chỉ email</strong> đã đăng ký</span>
                </div>
                <div class="flex items-start gap-3 text-sm text-gray-500">
                    <div class="w-6 h-6 rounded-lg bg-yellow-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-info text-yellow-400 text-xs"></i>
                    </div>
                    <span>Nếu không tìm thấy, liên hệ phòng Tuyển sinh: <strong class="text-gray-700">0866.993.468</strong></span>
                </div>
            </div>
        </div>

        <!-- Back link -->
        <div class="text-center mt-6 fade-up delay-3">
            <a href="<?= url('/') ?>" class="text-white/50 hover:text-white text-sm font-medium transition-colors inline-flex items-center gap-2">
                <i class="fas fa-arrow-left text-xs"></i> Quay lại trang chủ
            </a>
        </div>
    </div>

    <style>
        .tab-btn { color: #6b7280; background: transparent; }
        .active-tab { background: white; color: #dc2626; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    </style>
    <script>
        const tabs = { cccd: 'Nhập số CCCD của bạn...', sbd: 'Nhập số báo danh...', email: 'Nhập địa chỉ email...' };
        const icons = { cccd: 'fa-id-card', sbd: 'fa-hashtag', email: 'fa-envelope' };

        function setPlaceholder(type) {
            document.getElementById('keywordInput').placeholder = tabs[type];
            const icon = document.getElementById('inputIcon');
            icon.className = 'fas ' + icons[type] + ' text-gray-400';
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active-tab'));
            document.getElementById('tab-' + type).classList.add('active-tab');
        }

        // Prevent double submit
        document.getElementById('lookupForm').addEventListener('submit', function() {
            const btn = this.querySelector('button[type=submit]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Đang tra cứu...';
        });

        // Auto-submit nếu có ?q= trong URL (từ link dashboard/results)
        (function() {
            const params = new URLSearchParams(window.location.search);
            const q = params.get('q');
            if (q && q.trim().length > 0) {
                const input = document.getElementById('keywordInput');
                input.value = q.trim();
                // Small delay to show the filled input then submit
                setTimeout(function() {
                    document.getElementById('lookupForm').submit();
                }, 300);
            }
        })();
    </script>
</body>
</html>
