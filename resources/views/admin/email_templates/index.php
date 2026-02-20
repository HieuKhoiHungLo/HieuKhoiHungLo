<?php $title = 'Mẫu Email - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-6xl mx-auto">
    <header class="mb-6 flex justify-between items-center">
        <div>
            <a href="<?= url('/admin/settings/email') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition inline-flex items-center mb-2">
                <i class="fas fa-arrow-left mr-2"></i> Cấu hình Email
            </a>
            <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Quản lý Mẫu Email</h2>
        </div>
    </header>

    <?php if (!empty($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
        <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl font-bold border border-green-100 flex items-center text-sm">
            <i class="fas fa-check-circle mr-2"></i> Đã lưu mẫu email!
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-[#0066FF] to-blue-700 px-6 py-4">
            <h3 class="text-white font-bold flex items-center">
                <i class="fas fa-envelope-open-text mr-2"></i> Danh sách Mẫu Email
            </h3>
        </div>
        
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <?php foreach ($templates as $tpl): ?>
            <div class="p-5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-1">
                        <span class="px-2 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-bold rounded"><?= htmlspecialchars($tpl['slug']) ?></span>
                        <h4 class="font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($tpl['name']) ?></h4>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($tpl['subject']) ?></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        <i class="fas fa-code mr-1"></i> Biến: <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded"><?= htmlspecialchars($tpl['variables'] ?? '') ?></code>
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="<?= url('/admin/settings/email-templates/preview?slug=' . $tpl['slug']) ?>" target="_blank" 
                       class="px-3 py-2 text-xs font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                        <i class="fas fa-eye mr-1"></i> Xem thử
                    </a>
                    <a href="<?= url('/admin/settings/email-templates/edit?id=' . $tpl['id']) ?>" 
                       class="px-4 py-2 text-xs font-bold text-white bg-[#0066FF] hover:bg-blue-700 rounded-lg transition shadow">
                        <i class="fas fa-edit mr-1"></i> Chỉnh sửa
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($templates)): ?>
            <div class="p-10 text-center text-gray-400 dark:text-gray-500">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p>Chưa có mẫu email nào. Vui lòng chạy migration.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Help Card -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-5 border border-blue-100 dark:border-blue-900/30">
        <h4 class="font-bold text-blue-800 dark:text-blue-300 flex items-center mb-2">
            <i class="fas fa-info-circle mr-2"></i> Hướng dẫn
        </h4>
        <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
            <li>• Sử dụng <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">{{tên_biến}}</code> để chèn dữ liệu động vào email.</li>
            <li>• Xem danh sách biến có sẵn ở mỗi template.</li>
            <li>• Nhấn "Xem thử" để xem trước email với dữ liệu mẫu.</li>
        </ul>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
