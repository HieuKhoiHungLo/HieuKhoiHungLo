<?php $title = 'Quản lý Thông báo - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-6xl mx-auto">
    <header class="mb-6 flex justify-between items-center">
        <div>
            <a href="<?= url('/admin/dashboard') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition inline-flex items-center mb-2">
                <i class="fas fa-arrow-left mr-2"></i> Dashboard
            </a>
            <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Quản lý Thông báo</h2>
        </div>
        <a href="<?= url('/admin/notifications/create') ?>" class="px-5 py-2.5 bg-emerald-600 text-white font-bold rounded-xl shadow hover:bg-emerald-700 transition flex items-center">
            <i class="fas fa-plus mr-2"></i> Tạo Thông báo
        </a>
    </header>

    <?php if (!empty($_GET['msg'])): ?>
        <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl font-bold border border-green-100 flex items-center text-sm">
            <i class="fas fa-check-circle mr-2"></i>
            <?php if ($_GET['msg'] == 'created'): ?>Đã tạo thông báo mới!<?php endif; ?>
            <?php if ($_GET['msg'] == 'deleted'): ?>Đã xóa thông báo!<?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-[#0066FF] to-blue-700 px-6 py-4">
            <h3 class="text-white font-bold flex items-center">
                <i class="fas fa-bell mr-2"></i> Danh sách Thông báo đã gửi
            </h3>
        </div>
        
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <?php foreach ($notifications as $notif): ?>
            <div class="p-5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <?php 
                            $typeColors = [
                                'info' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
                                'warning' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300',
                                'success' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300',
                                'important' => 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300'
                            ];
                            $typeLabels = [
                                'info' => 'Thông tin',
                                'warning' => 'Cảnh báo',
                                'success' => 'Thành công',
                                'important' => 'Quan trọng'
                            ];
                            ?>
                            <span class="px-2 py-1 text-xs font-bold rounded <?= $typeColors[$notif['type']] ?? $typeColors['info'] ?>">
                                <?= $typeLabels[$notif['type']] ?? 'Thông tin' ?>
                            </span>
                            <?php
                            $targetLabels = [
                                'all' => '<i class="fas fa-globe text-gray-400 mr-1"></i> Tất cả',
                                'individual' => '<i class="fas fa-user text-indigo-400 mr-1"></i> ' . htmlspecialchars($notif['target_id'] ?? ''),
                                'session' => '<i class="fas fa-calendar text-green-400 mr-1"></i> Đợt ' . htmlspecialchars($notif['target_id'] ?? '')
                            ];
                            ?>
                            <span class="text-xs text-gray-500 dark:text-gray-400"><?= $targetLabels[$notif['target_type']] ?? '' ?></span>
                        </div>
                        <h4 class="font-bold text-gray-800 dark:text-white text-lg"><?= htmlspecialchars($notif['title']) ?></h4>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 line-clamp-2">
                            <?php 
                            $cleanContent = strip_tags(html_entity_decode($notif['content'], ENT_QUOTES, 'UTF-8'));
                            echo htmlspecialchars(mb_substr($cleanContent, 0, 200) . (mb_strlen($cleanContent) > 200 ? '...' : ''));
                            ?>
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                            <i class="fas fa-clock mr-1"></i> <?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?>
                            <?php if (!empty($notif['admin_name'])): ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-user mr-1"></i> <?= htmlspecialchars($notif['admin_name']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="<?= url('/admin/notifications/delete?id=' . $notif['id']) ?>" 
                       onclick="return confirm('Bạn có chắc muốn xóa thông báo này?')"
                       class="px-3 py-2 text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($notifications)): ?>
            <div class="p-10 text-center text-gray-400 dark:text-gray-500">
                <i class="fas fa-bell-slash text-4xl mb-3"></i>
                <p>Chưa có thông báo nào.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
