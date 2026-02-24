<?php ob_start(); ?>

<div class="mb-8 flex justify-between items-center">
    <div>
        <a href="<?= url('/admin/dashboard') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition block mb-2">&larr; Quay lại Bảng điều khiển</a>
        <h2 class="text-3xl font-black text-gray-900 uppercase">Tin tức & Thông báo</h2>
    </div>
    <a href="<?= url('/admin/posts/create') ?>" class="px-6 py-3 bg-[#0066FF] text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition flex items-center transform hover:-translate-y-1">
        <i class="fas fa-plus mr-2"></i> Viết bài mới
    </a>
</div>

<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                <th class="px-6 py-4">Tiêu đề</th>
                <th class="px-6 py-4">Chuyên mục</th>
                <th class="px-6 py-4">Trạng thái</th>
                <th class="px-6 py-4">Lượt xem</th>
                <th class="px-6 py-4">Ngày đăng</th>
                <th class="px-6 py-4 text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php if (empty($posts)): ?>
                <tr><td colspan="6" class="px-6 py-10 text-center text-gray-400 italic font-medium">Chưa có bài viết nào.</td></tr>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <?php if ($post['thumbnail']): ?>
                                    <img src="<?= filter_var($post['thumbnail'], FILTER_VALIDATE_URL) ? $post['thumbnail'] : url('/' . $post['thumbnail']) ?>" class="w-10 h-10 rounded-lg object-cover mr-3 shadow-sm border border-gray-100">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center mr-3 text-gray-300">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-900 line-clamp-1"><?= htmlspecialchars($post['title']) ?></span>
                                    <?php if ($post['is_featured']): ?>
                                        <span class="mt-1 w-fit text-[8px] bg-amber-100 text-amber-600 px-1 rounded-sm uppercase font-black">Nổi bật</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-gray-100 rounded text-[10px] font-black text-gray-500 uppercase tracking-tight"><?= $post['category'] ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($post['status'] === 'Published'): ?>
                                <span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded text-[10px] font-black uppercase tracking-tight">Đã đăng</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-amber-50 text-amber-600 rounded text-[10px] font-black uppercase tracking-tight">Nháp</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-xs font-bold text-gray-500"><?= number_format($post['view_count']) ?></td>
                        <td class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase"><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center space-x-2">
                                <a href="<?= url('/admin/posts/edit?id=' . $post['id']) ?>" class="p-2 text-[#0066FF] hover:bg-blue-50 rounded-lg transition" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= url('/admin/posts/delete?id=' . $post['id']) ?>" onclick="return confirm('Xác nhận xóa bài viết này?')" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Xóa">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php'; 
?>
