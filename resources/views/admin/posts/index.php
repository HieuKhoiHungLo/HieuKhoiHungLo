<?php
$title = 'Tin tức & Thông báo';
ob_start();
?>

<div class="bg-white rounded-lg shadow-sm p-4 lg:p-5">
    <div class="flex flex-wrap justify-between items-center mb-4 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-newspaper text-blue-500"></i> Tin tức & Thông báo
            </h1>
            <p class="text-gray-500 text-sm mt-1">Quản lý các bài viết, tin tức và thông báo trên trang chủ.</p>
        </div>

        <div class="flex items-center gap-2 ml-auto">
            <!-- Search Form -->
            <form action="" method="GET" class="flex items-center gap-2">
                <div class="relative w-48 sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>"
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                        placeholder="Tìm tiêu đề...">
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors whitespace-nowrap shadow-sm">
                    Tìm kiếm
                </button>
            </form>
            
            <!-- Create Button -->
            <a href="<?= url('/admin/posts/create') ?>" class="px-4 py-2 bg-[#0066FF] text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm flex items-center justify-center shadow-sm whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i> Thêm mới
            </a>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded-xl shadow-sm bg-white border border-slate-200">
        <table class="w-full border-collapse">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wide border border-slate-200 w-12">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide border border-slate-200">Tiêu đề</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide border border-slate-200">Chuyên mục</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wide border border-slate-200">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wide border border-slate-200">Lượt xem</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wide border border-slate-200">Ngày đăng</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wide border border-slate-200 w-24">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-20 text-center text-gray-500 italic border border-slate-200">
                            <i class="fas fa-newspaper text-3xl mb-2 text-slate-300"></i>
                            <p class="text-sm">Chưa có bài viết nào phù hợp.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $index => $post): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-center border border-slate-200 text-[13px] text-slate-800">
                                <?= ($currentPage - 1) * 5 + $index + 1 ?>
                            </td>
                            <td class="px-4 py-3 border border-slate-200">
                                <div class="flex items-center">
                                    <?php if ($post['thumbnail']): ?>
                                        <img src="<?= filter_var($post['thumbnail'], FILTER_VALIDATE_URL) ? $post['thumbnail'] : url('/' . $post['thumbnail']) ?>" class="w-10 h-10 rounded object-cover mr-3 border border-slate-200">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded bg-slate-100 flex items-center justify-center mr-3 text-slate-300 border border-slate-200">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex flex-col">
                                        <?php 
                                        // Viết hoa chữ cái đầu tiên, các chữ khác viết thường (Sentence Case) cho dễ đọc
                                        $titleStr = mb_strtolower($post['title'], 'UTF-8');
                                        $titleStr = mb_strtoupper(mb_substr($titleStr, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($titleStr, 1, null, 'UTF-8');
                                        ?>
                                        <span class="text-[13px] text-black font-medium line-clamp-2" title="<?= htmlspecialchars($post['title']) ?>">
                                            <?= htmlspecialchars($titleStr) ?>
                                        </span>
                                        <?php if ($post['is_featured']): ?>
                                            <span class="mt-1 w-fit text-[10px] bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded border border-amber-200 uppercase font-semibold">Nổi bật</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 border border-slate-200 text-[13px] text-black">
                                <?= htmlspecialchars($post['category']) ?>
                            </td>
                            <td class="px-4 py-3 text-center border border-slate-200">
                                <?php if ($post['status'] === 'Published'): ?>
                                    <span class="px-2 py-1 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded text-[11px] font-semibold">Đã đăng</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-slate-100 border border-slate-200 text-slate-600 rounded text-[11px] font-semibold">Nháp</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center border border-slate-200 text-[13px] text-black">
                                <?= number_format($post['view_count']) ?>
                            </td>
                            <td class="px-4 py-3 text-center border border-slate-200 text-[13px] text-slate-600 whitespace-nowrap">
                                <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
                            </td>
                            <td class="px-4 py-3 text-center border border-slate-200">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="<?= url('/admin/posts/edit?id=' . $post['id']) ?>" class="w-7 h-7 flex items-center justify-center bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 rounded transition-all border border-blue-100" title="Sửa">
                                        <i class="fas fa-edit text-[11px]"></i>
                                    </a>
                                    <a href="<?= url('/admin/posts/delete?id=' . $post['id']) ?>" onclick="return confirm('Xác nhận xóa bài viết này?')" class="w-7 h-7 flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 rounded transition-all border border-rose-100" title="Xóa">
                                        <i class="fas fa-trash-alt text-[11px]"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (isset($totalPages) && $totalPages >= 1): ?>
        <div class="flex items-center justify-between mt-4">
            <div class="flex items-center gap-3 text-xs text-slate-500">
                <span>
                    Trang <span class="font-semibold text-slate-700"><?= $currentPage ?></span> / <span class="font-semibold text-slate-700"><?= $totalPages ?></span>
                    &nbsp;(<span class="font-medium"><?= number_format($total ?? 0) ?></span> bản ghi)
                </span>
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="flex gap-1.5">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search ?? '') ?>" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-xs font-semibold transition">Trước</a>
                <?php endif; ?>

                <?php 
                $start = max(1, $currentPage - 2);
                $end = min($totalPages, $currentPage + 2);
                for ($i = $start; $i <= $end; $i++): 
                ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>" class="w-8 h-8 flex items-center justify-center border rounded-lg font-semibold text-xs transition <?= $i == $currentPage ? 'bg-[#0066FF] border-blue-600 text-white' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search ?? '') ?>" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-xs font-semibold transition">Sau</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php'; 
?>
