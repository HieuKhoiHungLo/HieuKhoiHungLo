<?php $title = 'Chỉnh sửa Vai trò - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-4xl mx-auto">
    <header class="mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-black text-gray-900 uppercase">Sửa Vai trò</h2>
            <p class="text-gray-500 mt-1">Thiết lập quyền hạn cho nhóm người dùng</p>
        </div>
        <a href="<?= url('/admin/roles') ?>" class="text-sm font-bold text-gray-500 hover:text-gray-800 transition">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl font-bold border border-red-100 italic">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <form action="<?= url('/admin/roles/edit') ?>" method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="id" value="<?= $role['id'] ?>">

        <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
            <div class="mb-8">
                <label class="block text-sm font-black text-gray-700 uppercase tracking-wider mb-2">Tên hiển thị</label>
                <input type="text" name="display_name" value="<?= htmlspecialchars($role['display_name']) ?>"
                    class="w-full px-5 py-4 bg-gray-50 border-2 border-transparent focus:border-[#0066FF] focus:bg-white rounded-2xl transition-all outline-none font-bold text-lg" required>
            </div>

            <h3 class="text-sm font-black text-gray-700 uppercase tracking-wider mb-4 border-b pb-2">Phân bổ Quyền hạn</h3>

            <div class="grid md:grid-cols-2 gap-8">
                <?php foreach ($allPermissions as $group => $perms): ?>
                    <div class="space-y-3">
                        <h4 class="font-bold text-[#0066FF] flex items-center">
                            <i class="fas fa-folder-open mr-2 text-xs"></i> <?= $group ?>
                        </h4>
                        <div class="bg-gray-50 rounded-2xl p-4 space-y-2">
                            <?php foreach ($perms as $key => $label): ?>
                                <label class="flex items-center group cursor-pointer p-2 hover:bg-white rounded-xl transition-colors border border-transparent hover:border-gray-100">
                                    <div class="relative flex items-center justify-center">
                                        <input type="checkbox" name="permissions[]" value="<?= $key ?>"
                                            <?= is_array($rolePermissions) && in_array($key, $rolePermissions) ? 'checked' : '' ?>
                                            class="peer h-6 w-6 cursor-pointer appearance-none rounded-lg border-2 border-gray-400 transition-all checked:border-[#0066FF] checked:bg-[#0066FF] hover:border-[#0066FF]">
                                        <div class="absolute scale-0 peer-checked:scale-100 text-white transition-transform pointer-events-none flex items-center justify-center inset-0">
                                            <i class="fas fa-check text-[12px]"></i>
                                        </div>
                                    </div>
                                    <span class="ml-3 text-sm font-bold text-gray-600 group-hover:text-gray-900 transition"><?= $label ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-10 pt-8 border-t flex gap-4">
                <button type="submit" class="flex-1 py-4 bg-[#0066FF] text-white font-black rounded-2xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-1 transition-all active:scale-95 uppercase tracking-widest">
                    <i class="fas fa-save mr-2"></i> Lưu thay đổi
                </button>
            </div>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>