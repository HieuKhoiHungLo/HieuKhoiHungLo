<?php $title = 'Thêm Mẫu Email Mới - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-4xl mx-auto">
    <header class="mb-6">
        <a href="<?= url('/admin/settings/email-templates') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition inline-flex items-center mb-2">
            <i class="fas fa-arrow-left mr-2"></i> Danh sách Mẫu
        </a>
        <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Thêm Mẫu Email Mới</h2>
    </header>

    <form action="<?= url('/admin/settings/email-templates/store') ?>" method="POST" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        
        <div class="bg-gradient-to-r from-green-600 to-green-800 px-6 py-4">
            <h3 class="text-white font-bold flex items-center">
                <i class="fas fa-plus-circle mr-2"></i> Tạo mẫu email mới
            </h3>
        </div>
        
        <div class="p-6 space-y-5">
            <!-- Code and Variables -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Mã mẫu (Code/Slug) *</label>
                    <input type="text" name="code" placeholder="vd: custom_notification" 
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition text-sm font-medium dark:text-white"
                           pattern="^[a-z0-9_]+$" title="Chỉ sử dụng chữ thường, số và dấu gạch dưới" required>
                    <p class="text-xs text-gray-400 mt-1">Chỉ sử dụng chữ thường, số và dấu gạch dưới (a-z, 0-9, _).</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Biến có thể dùng</label>
                    <input type="text" name="variables" placeholder="vd: ho_ten, cccd, ma_nganh" 
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition text-sm font-medium dark:text-white">
                    <p class="text-xs text-gray-400 mt-1">Các biến ngăn cách nhau bởi dấu phẩy.</p>
                </div>
            </div>

            <!-- Subject -->
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Tiêu đề Email (Subject) *</label>
                <input type="text" name="subject" placeholder="Nhập tiêu đề email..." 
                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition text-sm font-medium dark:text-white"
                       required>
            </div>

            <!-- Body -->
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Nội dung Email (HTML) *</label>
                <textarea name="body" rows="15" placeholder="Nhập nội dung HTML..." 
                          class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition text-sm font-mono dark:text-white"
                          required><div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; padding: 20px;">
    <h2>Thông báo từ Trường Đại học Hùng Vương</h2>
    <p>Chào bạn <strong>{{ho_ten}}</strong>,</p>
    <p>...</p>
</div></textarea>
                <p class="text-xs text-gray-400 mt-2">
                    <i class="fas fa-info-circle mr-1"></i> Sử dụng HTML để định dạng email. Dùng cú pháp <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{{tên_biến}}</code> để chèn dữ liệu động.
                </p>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-100 dark:border-gray-600 flex justify-end items-center space-x-3">
            <a href="<?= url('/admin/settings/email-templates') ?>" 
               class="px-4 py-2 text-sm font-bold text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                Hủy bỏ
            </a>
            <button type="submit" class="px-6 py-2.5 bg-green-600 text-white font-bold uppercase text-sm tracking-wider rounded-lg shadow hover:bg-green-700 hover:shadow-lg transition-all">
                <i class="fas fa-save mr-2"></i> Tạo mẫu mới
            </button>
        </div>
    </form>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
