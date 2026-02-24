<?php $pageTitle = ($post ? 'Cập nhật bài viết' : 'Viết bài mới'); ?>
<?php ob_start(); ?>

<style>
    .admin-input {
        background: #f9fafb !important;
        border: 2px solid #f3f4f6 !important;
        border-radius: 1.25rem !important;
        padding: 0.85rem 1.5rem !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        width: 100% !important;
        outline: none !important;
        font-family: inherit !important;
    }
    .admin-input:focus {
        border-color: #0066FF !important;
        background: #ffffff !important;
        box-shadow: 0 0 0 4px rgba(0, 102, 255, 0.1) !important;
        transform: translateY(-1px);
    }
    .label-caps {
        font-size: 10px;
        font-weight: 900;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 0.5rem;
        display: block;
    }
</style>

<div class="mb-8">
    <a href="<?= url('/admin/posts') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition inline-flex items-center mb-4">
        <i class="fas fa-arrow-left mr-2 text-[10px]"></i> Quay lại danh sách
    </a>
    <h2 class="text-3xl font-black text-gray-900 uppercase tracking-tight"><?= $pageTitle ?></h2>
</div>

<form action="<?= url('/admin/posts/save') ?>" method="POST" class="space-y-8" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
    <input type="hidden" name="id" value="<?= $post['id'] ?? '' ?>">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <!-- Main Content (Left) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-10 rounded-[2.5rem] shadow-sm border border-gray-100">
                <div class="grid grid-cols-1 gap-8">
                    <div>
                        <label class="label-caps">Tiêu đề bài viết</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required 
                               class="admin-input font-bold text-xl placeholder-gray-300" placeholder="Nhập tiêu đề sinh động...">
                    </div>
                    
                    <div>
                        <label class="label-caps">Đường dẫn tĩnh (Slug)</label>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-300 font-mono text-xs">/post/</span>
                            <input type="text" name="slug" value="<?= htmlspecialchars($post['slug'] ?? '') ?>" 
                                   class="admin-input font-mono text-sm pl-16 text-blue-600 bg-blue-50/30" placeholder="duong-dan-bai-viet">
                        </div>
                    </div>

                    <div>
                        <label class="label-caps">Tóm tắt nội dung</label>
                        <textarea name="summary" rows="4" class="admin-input font-medium text-sm leading-relaxed" 
                                placeholder="Viết một đoạn ngắn giới thiệu bài viết để thu hút người đọc..."><?= htmlspecialchars($post['summary'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="label-caps">Nội dung chi tiết</label>
                        <textarea name="content" rows="18" required class="admin-input font-medium leading-relaxed" 
                                placeholder="Chia sẻ câu chuyện của bạn ở đây..."><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                        <div class="mt-3 flex items-center text-[10px] text-gray-400 font-medium italic">
                            <i class="fas fa-info-circle mr-2 text-[#0066FF]"></i>
                            <span>Bạn có thể sử dụng các thẻ HTML cơ bản để định dạng văn bản.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Config (Right) -->
        <div class="space-y-6 sticky top-8">
            <div class="bg-gray-900 p-8 rounded-[2.5rem] shadow-xl text-white">
                <div class="mb-8">
                    <label class="label-caps !text-gray-500">Chuyên mục</label>
                    <select name="category" class="admin-input !bg-gray-800 !border-gray-700 !text-white !rounded-2xl font-bold">
                        <option value="Tin tức" <?= ($post['category'] ?? '') === 'Tin tức' ? 'selected' : '' ?>>📰 Tin tức</option>
                        <option value="Thông báo" <?= ($post['category'] ?? '') === 'Thông báo' ? 'selected' : '' ?>>🔔 Thông báo</option>
                        <option value="Hướng dẫn" <?= ($post['category'] ?? '') === 'Hướng dẫn' ? 'selected' : '' ?>>💡 Hướng dẫn</option>
                    </select>
                </div>

                <div class="mb-8">
                    <label class="label-caps !text-gray-500">Trạng thái xuất bản</label>
                    <select name="status" class="admin-input !bg-gray-800 !border-gray-700 !text-white !rounded-2xl font-bold">
                        <option value="Draft" <?= ($post['status'] ?? '') === 'Draft' ? 'selected' : '' ?>>📄 Lưu nháp</option>
                        <option value="Published" <?= ($post['status'] ?? '') === 'Published' ? 'selected' : '' ?>>🚀 Xuất bản ngay</option>
                    </select>
                </div>

                <div class="mb-8 p-4 bg-gray-800/50 rounded-2xl border border-gray-700 flex items-center justify-between cursor-pointer hover:bg-gray-800 transition group" onclick="document.getElementById('is_featured').click()">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center mr-3 group-hover:scale-110 transition">
                            <i class="fas fa-star text-amber-500"></i>
                        </div>
                        <span class="text-sm font-bold">Nổi bật</span>
                    </div>
                    <input type="checkbox" name="is_featured" id="is_featured" <?= ($post['is_featured'] ?? false) ? 'checked' : '' ?> 
                           class="w-6 h-6 rounded-lg text-[#0066FF] border-gray-600 bg-gray-700 focus:ring-offset-gray-900">
                </div>

                <div class="space-y-4">
                    <label class="label-caps !text-gray-500">Ảnh đại diện (Thumbnail)</label>
                    
                    <div class="relative group cursor-pointer overflow-hidden rounded-2xl border-2 border-dashed border-gray-700 hover:border-[#0066FF] transition bg-gray-800/30">
                        <input type="file" name="thumbnail_file" id="thumbnail_input" accept="image/*" 
                               class="absolute inset-0 opacity-0 cursor-pointer z-10"
                               onchange="if(this.files[0]) document.getElementById('preview_msg').innerText = this.files[0].name">
                        <div class="p-6 text-center">
                            <i class="fas fa-cloud-upload-alt text-2xl text-gray-500 mb-2 group-hover:text-[#0066FF] transition"></i>
                            <p id="preview_msg" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tải ảnh lên</p>
                        </div>
                    </div>

                    <div class="relative">
                        <input type="text" name="thumbnail" value="<?= htmlspecialchars($post['thumbnail'] ?? '') ?>" 
                               class="admin-input !bg-gray-800 !border-gray-700 !text-white !text-[10px] !rounded-xl !py-2 font-mono" 
                               placeholder="Hoặc nhập URL ảnh...">
                    </div>
                </div>

                <?php if ($post['thumbnail'] ?? ''): ?>
                    <div class="mt-8">
                        <label class="label-caps !text-gray-500">Xem trước</label>
                        <img src="<?= filter_var($post['thumbnail'], FILTER_VALIDATE_URL) ? $post['thumbnail'] : url('/' . $post['thumbnail']) ?>" 
                             class="rounded-3xl w-full h-40 object-cover border border-gray-700 shadow-2xl brightness-90">
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex flex-col gap-4">
                <button type="submit" class="w-full py-5 bg-[#0066FF] text-white font-black uppercase tracking-[0.2em] rounded-[2rem] shadow-[0_20px_40px_rgba(0,102,255,0.3)] hover:shadow-[0_25px_50px_rgba(0,102,255,0.4)] transition transform hover:-translate-y-1 active:scale-95">
                    Lưu bài viết
                </button>
                <a href="<?= url('/admin/posts') ?>" class="block w-full py-4 bg-gray-100 text-gray-500 text-center font-black uppercase tracking-widest rounded-[2rem] hover:bg-gray-200 transition">
                    Hủy bỏ
                </a>
            </div>
        </div>
    </div>
</form>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php'; 
?>
