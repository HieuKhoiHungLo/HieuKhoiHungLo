<?php $title = 'Quản lý Tin tức - Admin'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= url('/public/assets/css/tailwind.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --hvu-primary: #0066FF; --hvu-gold: #FFC000; }
        .bg-[#0066FF] { background-color: var(--hvu-primary); }
        .text-[#0066FF] { color: var(--hvu-primary); }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">
    <div class="max-w-7xl mx-auto p-8">
        <header class="mb-8 flex justify-between items-center">
            <div>
                <a href="<?= url('/admin/dashboard') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition block mb-2">&larr; Quay lại Bảng điều khiển</a>
                <h2 class="text-3xl font-black text-gray-900 uppercase">Tin tức & Thông báo</h2>
            </div>
            <a href="<?= url('/admin/posts/create') ?>" class="px-6 py-3 bg-[#0066FF] text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition flex items-center">
                <i class="fas fa-plus mr-2"></i> Viết bài mới
            </a>
        </header>

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
                                            <img src="<?= $post['thumbnail'] ?>" class="w-10 h-10 rounded-lg object-cover mr-3 shadow-sm">
                                        <?php endif; ?>
                                        <span class="font-bold text-gray-900"><?= htmlspecialchars($post['title']) ?></span>
                                        <?php if ($post['is_featured']): ?>
                                            <span class="ml-2 text-[8px] bg-amber-100 text-amber-600 px-1 rounded-sm uppercase font-black">Nổi bật</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-gray-100 rounded text-[10px] font-black text-gray-500"><?= $post['category'] ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($post['status'] === 'Published'): ?>
                                        <span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded text-[10px] font-black">Đã đăng</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-amber-50 text-amber-600 rounded text-[10px] font-black">Nháp</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs font-bold text-gray-500"><?= number_format($post['view_count']) ?></td>
                                <td class="px-6 py-4 text-[10px] font-bold text-gray-400"><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <a href="<?= url('/admin/posts/edit?id=' . $post['id']) ?>" class="text-[#0066FF] hover:underline font-black text-[10px] uppercase mr-4">Sửa</a>
                                    <a href="<?= url('/admin/posts/delete?id=' . $post['id']) ?>" onclick="return confirm('Xác nhận xóa bài viết này?')" class="text-gray-400 hover:text-red-600 font-black text-[10px] uppercase">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
