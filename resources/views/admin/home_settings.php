<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Cài đặt trang chủ</h1>
        
        <?php if (isset($success) && $success): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">
                ✅ Đã lưu cài đặt thành công!
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-4">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= url('/admin/settings/home') ?>
    <?= csrf_field() ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <!-- Video Section -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4 text-gray-700">Video YouTube</h2>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">URL hoặc Video ID</label>
                    <input 
                        type="text" 
                        name="video_url" 
                        value="<?= htmlspecialchars($settings['video_url']) ?>"
                        class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        placeholder="https://www.youtube.com/watch?v=czCebfco6_g hoặc czCebfco6_g"
                    >
                    <p class="text-xs text-gray-500 mt-1">
                        Nhập URL đầy đủ hoặc chỉ Video ID (11 ký tự). Hệ thống sẽ tự động phát hiện.
                    </p>
                </div>
                
                <!-- Preview -->
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <p class="text-sm font-medium text-gray-700 mb-2">Xem trước:</p>
                    <div class="aspect-video bg-black rounded overflow-hidden">
                        <iframe 
                            id="preview-iframe"
                            class="w-full h-full"
                            src="https://www.youtube.com/embed/<?= htmlspecialchars($settings['video_url']) ?>?autoplay=0&mute=1&modestbranding=1"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen
                        ></iframe>
                    </div>
                </div>
            </div>
            
            <!-- Stats Section -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4 text-gray-700">Thông báo chạy (Marquee)</h2>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Nội dung thông báo</label>
                    <textarea 
                        name="announcement" 
                        class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-500"
                        rows="2"
                        placeholder="Ví dụ: Chào mừng bạn đến với Cổng thông tin Tuyển sinh Đại học Hùng Vương 2026..."
                    ><?= htmlspecialchars($settings['announcement']) ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        Dòng này sẽ hiện ngay dưới header và chạy từ phải qua trái.
                    </p>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4 text-gray-700">Thống kê hiển thị</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Số ngành</label>
                        <input 
                            type="text" 
                            name="stats_majors" 
                            value="<?= htmlspecialchars($settings['stats_majors']) ?>"
                            class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-500"
                            placeholder="27"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Chỉ tiêu</label>
                        <input 
                            type="text" 
                            name="stats_quota" 
                            value="<?= htmlspecialchars($settings['stats_quota']) ?>"
                            class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-500"
                            placeholder="3070"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Tỷ lệ có việc</label>
                        <input 
                            type="text" 
                            name="stats_employ" 
                            value="<?= htmlspecialchars($settings['stats_employ']) ?>"
                            class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-500"
                            placeholder="98%"
                        >
                    </div>
                </div>
            <!-- Countdown Section -->
            <div class="mb-8 border-t border-gray-100 pt-8">
                <h2 class="text-lg font-semibold mb-4 text-gray-700">Đồng hồ đếm ngược</h2>
                
                <div class="mb-6">
                    <label class="flex items-center cursor-pointer">
                        <div class="relative">
                            <input type="hidden" name="countdown_enabled" value="0">
                            <input 
                                type="checkbox" 
                                name="countdown_enabled" 
                                value="1" 
                                class="sr-only" 
                                id="countdown-toggle"
                                <?= ($settings['countdown_enabled'] ?? '0') == '1' ? 'checked' : '' ?>
                            >
                            <div class="block bg-gray-200 w-14 h-8 rounded-full shadow-inner transition"></div>
                            <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition shadow"></div>
                        </div>
                        <div class="ml-3 text-gray-700 font-medium">
                            Hiển thị đồng hồ đếm ngược trên trang chủ
                        </div>
                    </label>
                </div>

                <div id="countdown-fields" class="<?= ($settings['countdown_enabled'] ?? '0') == '1' ? '' : 'hidden' ?> space-y-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Tiêu đề countdown</label>
                        <input 
                            type="text" 
                            name="countdown_title" 
                            value="<?= htmlspecialchars($settings['countdown_title'] ?? '') ?>"
                            class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-500"
                            placeholder="Ví dụ: Thời hạn đăng ký hồ sơ ghi danh sớm"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Thời hạn (Deadline)</label>
                        <input 
                            type="datetime-local" 
                            name="countdown_deadline" 
                            value="<?= htmlspecialchars($settings['countdown_deadline'] ?? '') ?>"
                            class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-500"
                        >
                        <p class="text-xs text-gray-500 mt-1">Chọn mốc thời gian sẽ kết thúc việc đếm ngược.</p>
                    </div>
                </div>
            </div>

            <style>
                #countdown-toggle:checked ~ .block {
                    background-color: #ef4444; /* Tailwind red-500 */
                }
                #countdown-toggle:checked ~ .dot {
                    transform: translateX(100%);
                }
            </style>
            
            <!-- Actions -->
            <div class="flex gap-4">
                <button 
                    type="submit"
                    class="px-6 py-3 bg-red-600 text-white font-semibold rounded hover:bg-red-700 transition"
                >
                    Lưu cài đặt
                </button>
                
                <a 
                    href="<?= url('/admin/dashboard') ?>"
                    class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded hover:bg-gray-300 transition"
                >
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Live preview update
document.querySelector('input[name="video_url"]').addEventListener('input', function(e) {
    let input = e.target.value.trim();
    let videoId = input;
    
    // Try to extract ID from URL
    const patterns = [
        /youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/,
        /youtu\.be\/([a-zA-Z0-9_-]+)/,
        /youtube\.com\/embed\/([a-zA-Z0-9_-]+)/
    ];
    
    for (let pattern of patterns) {
        let match = input.match(pattern);
        if (match) {
            videoId = match[1];
            break;
        }
    }
    
    // Update preview
    if (videoId.length === 11) {
        document.getElementById('preview-iframe').src = 
            `https://www.youtube.com/embed/${videoId}?autoplay=0&mute=1&modestbranding=1`;
    }
    // Toggle countdown fields
    document.getElementById('countdown-toggle').addEventListener('change', function(e) {
        const fields = document.getElementById('countdown-fields');
        if (e.target.checked) {
            fields.classList.remove('hidden');
        } else {
            fields.classList.add('hidden');
        }
    });
});
</script>

<?php 
$content = ob_get_clean();
$title = 'Cài đặt trang chủ';
include __DIR__ . '/../layouts/admin.php'; 
?>
