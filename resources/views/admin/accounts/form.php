<?php ob_start(); ?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="<?= url('/admin/accounts') ?>" class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-[#0066FF] uppercase tracking-wider mb-2 transition">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách
        </a>
        <h2 class="text-2xl font-black text-slate-800 font-heading uppercase tracking-tight">
            <?= $account['id'] ?? null ? 'Cập nhật tài khoản' : 'Thêm tài khoản mới' ?>
        </h2>
    </div>

    <form action="<?= url($account['id'] ?? null ? '/admin/accounts/update' : '/admin/accounts/store') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
        <?php if (isset($account['id'])): ?>
            <input type="hidden" name="id" value="<?= $account['id'] ?>">
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="p-4 mb-6 bg-red-50 text-red-600 rounded-xl font-medium text-sm border border-red-100 flex items-start">
                <i class="fas fa-exclamation-triangle mt-0.5 mr-2"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 space-y-6">
            <!-- Account Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Tên đăng nhập</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($account['ten_dang_nhap'] ?? '') ?>" 
                           <?= isset($account['id']) ? 'disabled class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono text-slate-500 cursor-not-allowed"' : 'required class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition"' ?>>
                    <?php if(isset($account['id'])): ?><input type="hidden" name="username" value="<?= $account['ten_dang_nhap'] ?>"><?php endif; ?>
                </div>
                <div class="space-y-1">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Họ và tên</label>
                    <input type="text" name="fullname" value="<?= htmlspecialchars($account['ho_ten'] ?? '') ?>" required 
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    Mật khẩu <?= isset($account['id']) ? '<span class="normal-case font-medium text-slate-400">(Để trống nếu không đổi)</span>' : '' ?>
                </label>
                <input type="password" name="password" <?= isset($account['id']) ? '' : 'required' ?> 
                       class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>

            <!-- Roles -->
            <div class="space-y-1">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Vai trò (Role)</label>
                <select name="role_id" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition font-medium">
                    <option value="">-- Không có vai trò (Chỉ dùng quyền riêng) --</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>" <?= (isset($account['role_id']) && $account['role_id'] == $role['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['display_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[10px] text-slate-400 mt-1 italic">* Người dùng sẽ thừa hưởng tất cả quyền của Vai trò được chọn.</p>
            </div>

            <!-- Permissions -->
            <div class="pt-6 border-t border-slate-100">
                <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-4">Phân quyền chức năng</label>
                <?php 
                    $currentPerms = json_decode($account['permissions'] ?? '[]', true);
                    $modules = [
                        'all' => 'Toàn quyền hệ thống (Super Admin)',
                        'dashboard' => 'Được xem Dashboard',
                        'review' => 'Được xét duyệt hồ sơ',
                        'master_data' => 'Được quản lý danh mục dữ liệu',
                        'posts' => 'Được đăng quản lý tin tức',
                        'stats' => 'Được xem báo cáo thống kê',
                        'accounts' => 'Được quản lý tài khoản khác'
                    ];
                ?>
                <div class="grid grid-cols-1 gap-3">
                    <?php foreach($modules as $key => $label): ?>
                        <label class="flex items-center p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition group">
                            <input type="checkbox" name="permissions[]" value="<?= $key ?>" 
                                <?= in_array($key, $currentPerms) || (isset($account['id']) && $account['id'] == 1 && $key == 'all') ? 'checked' : '' ?>
                                class="w-4 h-4 rounded text-[#0066FF] border-slate-300 focus:ring-indigo-500">
                            <span class="ml-3 text-sm font-medium text-slate-600 group-hover:text-slate-900"><?= $label ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Status -->
            <div class="flex items-center pt-4">
                <div class="relative flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="is_active" id="is_active" value="1" 
                            <?= (!isset($account) || ($account['is_active'] ?? 1)) ? 'checked' : '' ?>
                            <?= (isset($account['id']) && ($account['id'] == 1 || $account['id'] == $_SESSION['admin_id'])) ? 'disabled' : '' ?>
                            class="focus:ring-indigo-500 h-4 w-4 text-[#0066FF] border-slate-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_active" class="font-medium text-slate-700">Kích hoạt tài khoản</label>
                        <p class="text-slate-500 text-xs">Cho phép tài khoản này đăng nhập vào hệ thống.</p>
                    </div>
                    <?php if (isset($account['id']) && ($account['id'] == 1 || $account['id'] == $_SESSION['admin_id'])): ?>
                        <input type="hidden" name="is_active" value="1">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="px-8 py-3 bg-[#0066FF] text-white font-bold rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition transform text-sm uppercase tracking-wider">
                Lưu thay đổi
            </button>
        </div>
    </form>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php'; 
?>
