<?php $title = 'Quản lý Vai trò - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-5xl mx-auto">
    <header class="mb-8">
        <h2 class="text-3xl font-black text-gray-900 uppercase">Quản lý Vai trò</h2>
        <p class="text-gray-500 mt-1">Phân quyền cho các nhóm quản trị viên</p>
    </header>

    <?php if (!empty($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
        <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-xl font-bold border border-green-100">
            <i class="fas fa-check-circle mr-2"></i> Đã cập nhật vai trò!
        </div>
    <?php endif; ?>

    <div class="grid gap-6">
        <?php foreach ($roles as $role): ?>
            <?php $perms = json_decode($role['permissions'], true) ?? []; ?>
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($role['display_name']) ?></h3>
                        <p class="text-sm text-gray-400 font-mono"><?= htmlspecialchars($role['name']) ?></p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <?php foreach ($perms as $p): ?>
                                <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded"><?= htmlspecialchars($p) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <a href="<?= url('/admin/roles/edit?id=' . $role['id']) ?>" class="px-4 py-2 bg-[#0066FF] text-white font-bold rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-edit mr-1"></i> Sửa
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
