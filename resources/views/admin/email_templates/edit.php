<?php $title = 'Chỉnh sửa Mẫu Email - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-4xl mx-auto">
    <header class="mb-6">
        <a href="<?= url('/admin/settings/email-templates') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition inline-flex items-center mb-2">
            <i class="fas fa-arrow-left mr-2"></i> Danh sách Mẫu
        </a>
        <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Chỉnh sửa: <?= htmlspecialchars($template['name']) ?></h2>
    </header>

    <form action="<?= url('/admin/settings/email-templates/save') ?>" method="POST" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="id" value="<?= $template['id'] ?>">
        
        <div class="bg-gradient-to-r from-[#0066FF] to-blue-700 px-6 py-4">
            <h3 class="text-white font-bold flex items-center">
                <i class="fas fa-edit mr-2"></i> Nội dung Email
            </h3>
        </div>
        
        <div class="p-6 space-y-5">
            <!-- Info -->
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Mã Template:</span>
                        <code class="ml-2 bg-gray-200 dark:bg-gray-600 dark:text-white px-2 py-1 rounded"><?= htmlspecialchars($template['slug']) ?></code>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Biến có thể dùng:</span>
                        <code class="ml-2 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 px-2 py-1 rounded text-xs"><?= htmlspecialchars($template['variables'] ?? '') ?></code>
                    </div>
                </div>
            </div>

            <!-- Subject -->
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Tiêu đề Email (Subject)</label>
                <input type="text" name="subject" value="<?= htmlspecialchars($template['subject']) ?>" 
                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition text-sm font-medium dark:text-white"
                       required>
            </div>

            <!-- Body -->
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Nội dung Email (HTML)</label>
                <textarea name="body" rows="20" 
                          class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition text-sm font-mono dark:text-white"
                          required><?= htmlspecialchars($template['body']) ?></textarea>
                <p class="text-xs text-gray-400 mt-2">
                    <i class="fas fa-info-circle mr-1"></i> Sử dụng HTML để định dạng. Dùng <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{{tên_biến}}</code> để chèn dữ liệu.
                </p>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-100 dark:border-gray-600 flex justify-between items-center">
            <a href="<?= url('/admin/settings/email-templates/preview?slug=' . $template['slug']) ?>" target="_blank" 
               class="px-4 py-2 text-sm font-bold text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                <i class="fas fa-eye mr-1"></i> Xem trước
            </a>
            <button type="submit" class="px-6 py-2.5 bg-[#0066FF] text-white font-bold uppercase text-sm tracking-wider rounded-lg shadow hover:bg-blue-700 hover:shadow-lg transition-all">
                <i class="fas fa-save mr-2"></i> Lưu Thay Đổi
            </button>
        </div>
    </form>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
