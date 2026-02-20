<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Lỗi Hệ thống | Đại học Hùng Vương</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">

    <div class="text-center max-w-lg px-6">
        <div class="mb-8 text-red-100">
            <i class="fas fa-server text-9xl animate-pulse"></i>
        </div>
        
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Đã xảy ra lỗi hệ thống (500)</h2>
        <p class="text-gray-500 mb-8 leading-relaxed">
            Hệ thống đang gặp sự cố tạm thời. Đội ngũ kỹ thuật đã được thông báo.
            Vui lòng thử lại sau ít phút.
        </p>

        <div class="space-x-4">
            <button onclick="location.reload()" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-redo mr-2"></i> Thử lại
            </button>
            <a href="<?= App\Core\App::url('/') ?>" class="px-6 py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-home mr-2"></i> Trang chủ
            </a>
        </div>
    </div>

</body>
</html>
