<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full animate-fade-in-up">
        <div class="text-center mb-8">
            <img src="<?= url('/public/images/logo.png') ?>" alt="Logo" class="h-20 mx-auto mb-6">
            <h1 class="text-3xl font-black text-slate-900 mb-2">TRA CỨU ĐIỂM THI</h1>
            <p class="text-slate-500 font-medium">Nhập số CCCD hoặc Số báo danh để xem kết quả thi năng khiếu</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-100 text-rose-600 rounded-2xl flex items-center shadow-sm">
                <i class="fas fa-exclamation-circle mr-3"></i>
                <span class="text-sm font-bold">
                    <?= $_GET['error'] == 'empty' ? 'Vui lòng nhập thông tin tra cứu!' : 'Không tìm thấy thông tin thí sinh này.' ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="glass-card p-8 rounded-[2rem] shadow-2xl shadow-blue-100/50">
            <form action="<?= url('/tra-cuu-nang-khieu/search') ?>" method="POST" class="space-y-6">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Thông tin định danh</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <input type="text" name="keyword" required 
                               placeholder="Ví dụ: 00120400..."
                               class="w-full pl-11 pr-4 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-50 transition font-bold text-slate-700">
                    </div>
                </div>

                <button type="submit" class="w-full py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 transition transform hover:-translate-y-1 active:scale-95 shadow-xl shadow-blue-200 flex items-center justify-center gap-3">
                    <i class="fas fa-search"></i> TRA CỨU KẾT QUẢ
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-slate-100">
                <div class="flex items-start gap-4 text-slate-400">
                    <i class="fas fa-info-circle mt-1"></i>
                    <p class="text-xs leading-relaxed italic">
                        Lưu ý: Kết quả tra cứu chỉ mang tính tham khảo. Kết quả chính thức sẽ được gửi về địa chỉ thí sinh qua thư báo.
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center mt-10">
            <a href="<?= url('/') ?>" class="text-sm font-bold text-slate-400 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại trang chủ
            </a>
        </div>
    </div>

</body>
</html>
