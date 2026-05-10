<?php
$title = 'Quản lý chuyên mục';
ob_start();
?>

<div class="bg-white rounded-lg shadow-sm p-4 lg:p-5">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-tags text-blue-500"></i> Chuyên mục bài viết
        </h1>
        <p class="text-gray-500 text-sm mt-1">Thêm mới, sửa tên hoặc ẩn các chuyên mục bài viết trên trang chủ.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Form Column (Left 4/12) -->
        <div class="lg:col-span-4">
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <?= isset($editCategory) ? 'Cập nhật chuyên mục' : 'Thêm chuyên mục mới' ?>
                </h3>
                
                <form action="<?= url('/admin/categories/save') ?>" method="POST" class="space-y-4">
                    <input type="hidden" name="id" value="<?= $editCategory['id'] ?? '' ?>">
                    <?= csrf_field() ?>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Tên chuyên mục</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm shadow-sm"
                            placeholder="Vd: Tin tức, Thông báo...">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Đường dẫn (Slug)</label>
                        <input type="text" name="slug" value="<?= htmlspecialchars($editCategory['slug'] ?? '') ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm shadow-sm bg-white"
                            placeholder="để trống để tự động tạo...">
                        <p class="text-[10px] text-gray-400 mt-1 italic">Đường dẫn dùng cho URL. Vd: tin-tuc</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Thứ tự hiển thị</label>
                        <input type="number" name="sort_order" value="<?= $editCategory['sort_order'] ?? 0 ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm shadow-sm">
                    </div>

                    <div class="flex items-center gap-2 py-2">
                        <input type="checkbox" name="is_active" id="is_active" value="1" 
                            <?= ($editCategory['is_active'] ?? true) ? 'checked' : '' ?>
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Kích hoạt (Hiển thị)</label>
                    </div>

                    <div class="pt-2 flex gap-2">
                        <button type="submit" class="flex-grow px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-bold text-sm shadow-md">
                            <?= isset($editCategory) ? 'Cập nhật' : 'Thêm mới' ?>
                        </button>
                        <?php if (isset($editCategory)): ?>
                            <a href="<?= url('/admin/categories') ?>" class="px-4 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-bold text-sm">Hủy</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Column (Right 8/12) -->
        <div class="lg:col-span-8">
            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider w-16 text-center">STT</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Tên chuyên mục</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Slug</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider text-center">Thứ tự</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider text-center">Trạng thái</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider w-24 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-gray-400 italic">Chưa có chuyên mục nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $index => $cat): ?>
                                <tr class="hover:bg-blue-50/30 transition-colors">
                                    <td class="px-4 py-3 text-center text-sm font-medium text-gray-400"><?= $index + 1 ?></td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($cat['name']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 font-mono"><?= htmlspecialchars($cat['slug']) ?></td>
                                    <td class="px-4 py-3 text-center text-sm font-bold text-gray-600"><?= $cat['sort_order'] ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($cat['is_active']): ?>
                                            <span class="px-2 py-1 bg-green-50 text-green-600 border border-green-200 rounded text-[10px] font-bold uppercase">Hiện</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-gray-100 text-gray-400 border border-gray-200 rounded text-[10px] font-bold uppercase">Ẩn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="<?= url('/admin/categories?id=' . $cat['id']) ?>" class="w-7 h-7 flex items-center justify-center bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition shadow-sm border border-blue-100" title="Sửa">
                                                <i class="fas fa-edit text-xs"></i>
                                            </a>
                                            <a href="<?= url('/admin/categories/delete?id=' . $cat['id']) ?>" onclick="return confirm('Xác nhận xóa chuyên mục này? Các bài viết thuộc chuyên mục này có thể bị ảnh hưởng.')" 
                                               class="w-7 h-7 flex items-center justify-center bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition shadow-sm border border-red-100" title="Xóa">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php'; 
?>
