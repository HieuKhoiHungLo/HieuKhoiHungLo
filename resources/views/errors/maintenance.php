<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống đang bảo trì | Đại học Hùng Vương</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-text {
            background: linear-gradient(to right, #BE1E2D, #FF6B6B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .gears-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .gear {
            animation: spin 4s linear infinite;
        }
        .gear-reverse {
            animation: spin-reverse 4s linear infinite;
        }
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        @keyframes spin-reverse {
            100% { transform: rotate(-360deg); }
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">

    <div class="text-center max-w-lg px-6">
        <div class="mb-8 relative">
            <h1 class="text-8xl font-black gradient-text opacity-20 select-none">503</h1>
            <div class="absolute inset-0 flex items-center justify-center gears-container text-red-500 text-5xl">
                <i class="fas fa-cog gear"></i>
                <i class="fas fa-cog gear-reverse text-4xl mt-6"></i>
            </div>
        </div>
        
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Hệ thống đang bảo trì</h2>
        <p class="text-gray-500 mb-8 leading-relaxed">
            Hệ thống hiện đang gặp sự cố kết nối hoặc đang trong quá trình bảo trì định kỳ để nâng cấp dịch vụ. 
            Vui lòng quay lại sau ít phút. Chúng tôi xin lỗi vì sự bất tiện này.
        </p>

        <button onclick="window.location.reload();" class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-bold rounded-lg shadow-lg hover:bg-red-700 transition transform hover:-translate-y-1">
            <i class="fas fa-sync-alt mr-2"></i> Thử lại
        </button>
    </div>

</body>
</html>
