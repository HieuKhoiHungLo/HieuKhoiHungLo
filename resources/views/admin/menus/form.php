<?php
$isEdit = isset($menu);
$title = $isEdit ? 'Chỉnh sửa Menu' : 'Thêm mới Menu';
ob_start();

$currentPos = $isEdit ? $menu['position'] : $position;
function renderParentOptions($items, $level = 0, $selectedId = null, $excludeId = null) {
    $html = '';
    foreach ($items as $item) {
        if ($item['id'] == $excludeId) continue;
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        $selected = ($item['id'] == $selectedId) ? 'selected' : '';
        $html .= "<option value='{$item['id']}' {$selected}>{$indent}{$item['title']}</option>";
        if (!empty($item['children'])) {
            $html .= renderParentOptions($item['children'], $level + 1, $selectedId, $excludeId);
        }
    }
    return $html;
}
?>

<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center">
        <a href="<?= url("/admin/menus?position={$currentPos}") ?>" class="text-slate-400 hover:text-blue-600 transition mr-4">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white"><?= $title ?></h2>
            <p class="text-slate-500 text-sm mt-1">Cấu hình liên kết cho khu vực: <span class="font-bold text-blue-600"><?= $currentPos ?></span></p>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="bg-rose-50 border-l-4 border-rose-500 p-4 mb-6 rounded-r-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-rose-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-rose-700 font-bold"><?= $error ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
    <form action="<?= url($isEdit ? '/admin/menus/update' : '/admin/menus/store') ?>" method="POST">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $menu['id'] ?>">
        <?php endif; ?>
        <input type="hidden" name="position" value="<?= $currentPos ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="col-span-1">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Tên hiển thị <span class="text-rose-500">*</span></label>
                <input type="text" name="title" value="<?= $isEdit ? htmlspecialchars($menu['title']) : '' ?>" required 
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition">
            </div>

            <div class="col-span-1">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Đường dẫn (URL) <span class="text-rose-500">*</span></label>
                <input type="text" name="url" value="<?= $isEdit ? htmlspecialchars($menu['url']) : '' ?>" required placeholder="VD: /tinh-diem hoặc https://google.com"
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition">
            </div>

            <div class="col-span-1">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Menu Cha (Tùy chọn)</label>
                <select name="parent_id" class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition">
                    <option value="">-- Không có Menu Cha (Cấp cao nhất) --</option>
                    <?= renderParentOptions($parents, 0, $isEdit ? $menu['parent_id'] : null, $isEdit ? $menu['id'] : null) ?>
                </select>
                <p class="text-xs text-slate-500 mt-1">Dùng để tạo menu thả xuống hoặc nhóm menu.</p>
            </div>

            <div class="col-span-1">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Quy tắc hiển thị (Trạng thái Login)</label>
                <select name="visibility" class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition">
                    <option value="public" <?= ($isEdit && $menu['visibility'] == 'public') ? 'selected' : '' ?>>Tất cả mọi người</option>
                    <option value="guest_only" <?= ($isEdit && $menu['visibility'] == 'guest_only') ? 'selected' : '' ?>>Chỉ Khách (Chưa đăng nhập)</option>
                    <option value="auth_only" <?= ($isEdit && $menu['visibility'] == 'auth_only') ? 'selected' : '' ?>>Đã đăng nhập (Thí sinh)</option>
                    <option value="admin_only" <?= ($isEdit && $menu['visibility'] == 'admin_only') ? 'selected' : '' ?>>Chỉ Admin</option>
                </select>
            </div>

            <?php if ($currentPos == 'admin_sidebar'): ?>
            <div class="col-span-1">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Quyền Admin yêu cầu <span class="text-xs font-normal text-slate-400">(Tùy chọn)</span></label>
                <select name="permission_required" class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition">
                    <option value="">-- Bất kỳ Admin nào --</option>
                    <?php foreach ($permissions as $code => $label): ?>
                        <option value="<?= $code ?>" <?= ($isEdit && $menu['permission_required'] == $code) ? 'selected' : '' ?>><?= $code ?> (<?= $label ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-span-1">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Icon FontAwesome <span class="text-xs font-normal text-slate-400">(Tùy chọn)</span></label>
                <input type="text" name="icon" value="<?= $isEdit ? htmlspecialchars($menu['icon']) : '' ?>" placeholder="VD: fa-home, fa-user..."
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition">
                <p class="text-xs text-slate-500 mt-1">Lấy mã tại: <a href="https://fontawesome.com/v6/search?m=free" target="_blank" class="text-blue-500 hover:underline">FontAwesome 6 Free</a></p>
            </div>

            <div class="col-span-1">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">CSS Class đặc biệt <span class="text-xs font-normal text-slate-400">(Tùy chọn)</span></label>
                <input type="text" name="css_class" value="<?= $isEdit ? htmlspecialchars($menu['css_class']) : '' ?>" placeholder="VD: text-red-500 font-bold"
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition">
                <p class="text-xs text-slate-500 mt-1">Sử dụng Tailwind CSS classes để tùy biến màu sắc, kiểu dáng.</p>
            </div>

            <div class="col-span-1">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Số thứ tự hiển thị</label>
                <input type="number" name="order_index" value="<?= $isEdit ? $menu['order_index'] : '0' ?>"
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition">
                <p class="text-xs text-slate-500 mt-1">Số nhỏ xếp trước, số lớn xếp sau.</p>
            </div>

            <div class="col-span-1 md:col-span-2 mt-2">
                <label class="flex items-center cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" name="is_active" class="sr-only" <?= (!$isEdit || $menu['is_active']) ? 'checked' : '' ?>>
                        <div class="block bg-slate-200 dark:bg-slate-600 w-10 h-6 rounded-full transition-colors duration-300"></div>
                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform duration-300"></div>
                    </div>
                    <div class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-300">
                        Cho phép hiển thị (Kích hoạt)
                    </div>
                </label>
                <style>
                    input:checked ~ .block { background-color: #10B981; }
                    input:checked ~ .dot { transform: translateX(100%); }
                </style>
            </div>

        </div>

        <div class="mt-8 flex justify-end space-x-3 pt-6 border-t border-slate-100 dark:border-slate-700">
            <a href="<?= url("/admin/menus?position={$currentPos}") ?>" class="px-6 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-bold hover:bg-slate-50 transition">Hủy bỏ</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition flex items-center">
                <i class="fas fa-save mr-2"></i> Lưu Menu
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>
