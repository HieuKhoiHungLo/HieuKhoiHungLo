<?php $title = 'Chỉnh sửa Vai trò - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-3xl mx-auto">
    <header class="mb-8">
        <a href="<?= url('/admin/roles') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline block mb-2">&larr; Quay lại</a>
        <h2 class="text-3xl font-black text-gray-900 uppercase">Chỉnh sửa: <?= htmlspecialchars($role['display_name']) ?></h2>
    </header>

    <form method="POST" class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Tên hiển thị</label>
            <input type="text" name="display_name" value="<?= htmlspecialchars($role['display_name']) ?>" class="w-full px-4 py-3 border rounded-xl font-bold focus:ring-2 focus:ring-[#0066FF]">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-4">Quyền hạn</label>
            
            <?php foreach ($allPermissions as $group => $perms): ?>
                <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                    <h4 class="font-bold text-gray-800 uppercase text-sm mb-3 border-b pb-2"><?= ucfirst($group) ?></h4>
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach ($perms as $key => $label): ?>
                            <label class="flex items-center space-x-2 cursor-pointer hover:bg-white p-2 rounded transition">
                                <input type="checkbox" name="permissions[]" value="<?= $key ?>" 
                                    <?= in_array($key, $rolePermissions) ? 'checked' : '' ?>
                                    class="form-checkbox text-[#0066FF] rounded focus:ring-[#0066FF]">
                                <span class="text-sm text-gray-700"><?= $label ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="w-full py-3 bg-[#0066FF] text-white font-black uppercase rounded-xl shadow-lg hover:bg-blue-700 transition">
            <i class="fas fa-save mr-2"></i> Lưu thay đổi
        </button>
    </form>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
