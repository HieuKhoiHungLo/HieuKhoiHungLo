<?php $title = ($post ? 'Sửa bài viết' : 'Viết bài mới') . ' - Admin'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/tailwind.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --hvu-primary: #0066FF; --hvu-gold: #FFC000; }
        .bg-[#0066FF] { background-color: var(--hvu-primary); }
        .text-[#0066FF] { color: var(--hvu-primary); }
        .admin-input {
            background: #f9fafb !important;
            border: 2px solid #f3f4f6 !important;
            border-radius: 1rem !important;
            padding: 0.75rem 1.25rem !important;
            transition: all 0.2s ease !important;
            width: 100% !important;
            outline: none !important;
        }
        .admin-input:focus {
            border-color: var(--hvu-primary) !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(190, 30, 45, 0.1) !important;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">
    <div class="max-w-5xl mx-auto p-8">
        <header class="mb-8">
            <a href="<?= url('/admin/posts') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition block mb-2">&larr; Quay lại danh sách</a>
            <h2 class="text-3xl font-black text-gray-900 uppercase"><?= $post ? 'Cập nhật bài viết' : 'Viết bài mới' ?></h2>
        </header>

        <form action="<?= url('/admin/posts/save') ?>" method="POST" class="space-y-6" enctype="multipart/form-data">

            <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
            <input type="hidden" name="id" value="<?= $post['id'] ?? '' ?>">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Nội dung chính -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                        <div class="mb-6">
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 tracking-widest">Tiêu đề bài viết</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required class="admin-input font-bold text-xl" placeholder="Nhập tiêu đề...">
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 tracking-widest">Slug (Tùy chọn)</label>
                            <input type="text" name="slug" value="<?= htmlspecialchars($post['slug'] ?? '') ?>" class="admin-input font-mono text-sm" placeholder="thanh-duong-dan-bai-viet">
                        </div>

                        <div class="mb-6">
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 tracking-widest">Mô tả ngắn (Summary)</label>
                            <textarea name="summary" rows="3" class="admin-input font-medium text-sm" placeholder="Tóm tắt nội dung bài viết..."><?= htmlspecialchars($post['summary'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 tracking-widest">Nội dung chi tiết</label>
                            <textarea name="content" rows="15" required class="admin-input font-medium" placeholder="Nhập nội dung bài viết ở đây..."><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                            <p class="text-[10px] text-gray-400 mt-2 italic">* Bạn có thể sử dụng HTML cơ bản hoặc văn bản thuần túy.</p>
                        </div>
                    </div>
                </div>

                <!-- Cấu hình Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                        <div class="mb-6">
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 tracking-widest">Chuyên mục</label>
                            <select name="category" class="admin-input font-bold">
                                <option value="Tin tức" <?= ($post['category'] ?? '') === 'Tin tức' ? 'selected' : '' ?>>Tin tức</option>
                                <option value="Thông báo" <?= ($post['category'] ?? '') === 'Thông báo' ? 'selected' : '' ?>>Thông báo</option>
                                <option value="Hướng dẫn" <?= ($post['category'] ?? '') === 'Hướng dẫn' ? 'selected' : '' ?>>Hướng dẫn</option>
                            </select>
                        </div>

                        <div class="mb-6">
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 tracking-widest">Trạng thái</label>
                            <select name="status" class="admin-input font-bold">
                                <option value="Draft" <?= ($post['status'] ?? '') === 'Draft' ? 'selected' : '' ?>>Bản nháp</option>
                                <option value="Published" <?= ($post['status'] ?? '') === 'Published' ? 'selected' : '' ?>>Xuất bản</option>
                            </select>
                        </div>

                        <div class="mb-6 flex items-center">
                            <input type="checkbox" name="is_featured" id="is_featured" <?= ($post['is_featured'] ?? false) ? 'checked' : '' ?> class="w-5 h-5 rounded text-[#0066FF] border-gray-300 focus:ring-[#0066FF]">
                            <label for="is_featured" class="ml-3 text-sm font-bold text-gray-700">Đánh dấu Nổi bật</label>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 tracking-widest">Ảnh thumbnail</label>
                            
                            <!-- File Upload -->
                            <input type="file" name="thumbnail_file" accept="image/*" class="admin-input text-xs mb-2">
                            
                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1 tracking-widest text-center">- HOẶC NHẬP URL -</p>

                            <!-- URL Input -->
                            <input type="text" name="thumbnail" value="<?= htmlspecialchars($post['thumbnail'] ?? '') ?>" class="admin-input text-xs font-mono" placeholder="https://path-to-image.jpg">
                            
                            <?php if ($post['thumbnail'] ?? ''): ?>
                                <div class="mt-4">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Ảnh hiện tại:</p>
                                    <img src="<?= filter_var($post['thumbnail'], FILTER_VALIDATE_URL) ? $post['thumbnail'] : url('/' . $post['thumbnail']) ?>" class="rounded-xl w-full h-32 object-cover border border-gray-100 shadow-sm">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-4 bg-[#0066FF] text-white font-black uppercase tracking-widest rounded-2xl shadow-xl hover:shadow-2xl transition transform hover:-translate-y-1">
                        Lưu bài viết
                    </button>
                    <a href="<?= url('/admin/posts') ?>" class="block w-full py-4 bg-gray-100 text-gray-600 text-center font-black uppercase tracking-widest rounded-2xl hover:bg-gray-200 transition">
                        Hủy bỏ
                    </a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
