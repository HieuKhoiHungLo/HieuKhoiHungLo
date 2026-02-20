<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Không tìm thấy trang | Đại học Hùng Vương</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-text {
            background: linear-gradient(to right, #BE1E2D, #FF6B6B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">

    <div class="text-center max-w-lg px-6">
        <div class="mb-8 relative">
            <h1 class="text-9xl font-black gradient-text opacity-20 select-none">404</h1>
            <div class="absolute inset-0 flex items-center justify-center">
                <i class="fas fa-search-location text-6xl text-red-500 animate-bounce"></i>
            </div>
        </div>
        
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Ối! Trang bạn tìm không tồn tại.</h2>
        <p class="text-gray-500 mb-8 leading-relaxed">
            Có vẻ như đường dẫn bạn truy cập đã bị thay đổi hoặc không còn tồn tại. 
            Hãy thử quay lại trang chủ hoặc kiểm tra lại đường dẫn nhé.
        </p>

        <a href="<?= App\Core\App::url('/') ?>" class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-bold rounded-lg shadow-lg hover:bg-red-700 transition transform hover:-translate-y-1">
            <i class="fas fa-home mr-2"></i> Quay về Trang chủ
        </a>
    </div>

</body>
</html>
