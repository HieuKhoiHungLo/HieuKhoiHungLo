<?php $pageTitle = ($post ? 'Cập nhật bài viết' : 'Viết bài mới'); ?>
<?php ob_start(); ?>

<style>
    /* Clean UI Input Styles */
    .ui-input {
        display: block;
        width: 100%;
        background-color: #f8fafc;
        /* slate-50 */
        border: 1px solid #e2e8f0;
        /* slate-200 */
        border-radius: 0.75rem;
        /* rounded-xl */
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        /* text-sm */
        color: #1e293b;
        /* slate-800 */
        transition: all 0.2s ease;
        outline: none;
    }

    .ui-input:focus {
        background-color: #ffffff;
        border-color: #3b82f6;
        /* blue-500 */
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .ui-label {
        display: block;
        font-size: 0.75rem;
        /* text-xs */
        font-weight: 700;
        color: #64748b;
        /* slate-500 */
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }

    /* Toggle Switch Style */
    .toggle-checkbox:checked {
        right: 0;
        border-color: #10b981;
        /* emerald-500 */
    }

    .toggle-checkbox:checked+.toggle-label {
        background-color: #10b981;
        /* emerald-500 */
    }

    .toggle-checkbox {
        right: 0;
        z-index: 1;
        border-color: #e2e8f0;
        transition: all 0.3s;
    }

    .toggle-label {
        width: 3rem;
        height: 1.5rem;
        background-color: #cbd5e1;
        border-radius: 9999px;
        transition: all 0.3s;
    }

    .toggle-switch-handle {
        width: 1.125rem;
        height: 1.125rem;
        background-color: white;
        border-radius: 50%;
        position: absolute;
        top: 0.1875rem;
        left: 0.1875rem;
        transition: all 0.3s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .toggle-checkbox:checked~.toggle-switch-handle {
        transform: translateX(1.5rem);
    }
</style>

<div class="mb-6 xl:mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <a href="<?= url('/admin/posts') ?>" class="text-slate-500 hover:text-blue-600 text-xs font-bold uppercase tracking-wider transition-colors inline-flex items-center mb-2">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại
        </a>
        <h2 class="text-2xl xl:text-3xl font-black text-slate-800 uppercase tracking-tight font-heading"><?= $pageTitle ?></h2>
    </div>
</div>

<form action="<?= url('/admin/posts/save') ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
    <input type="hidden" name="id" value="<?= $post['id'] ?? '' ?>">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 xl:gap-8 items-start">

        <!-- Main Content Area (Left: 8 cols) -->
        <div class="lg:col-span-8 space-y-6">
            <div class="bg-white p-6 xl:p-8 rounded-2xl shadow-sm border border-slate-200">
                <div class="space-y-6">

                    <!-- Tiêu đề -->
                    <div>
                        <label class="ui-label">Tiêu đề bài viết <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required
                            class="ui-input font-bold text-lg placeholder-slate-400" placeholder="Nhập tiêu đề sinh động...">
                    </div>

                    <!-- Slug -->
                    <div>
                        <label class="ui-label">Đường dẫn tĩnh (Slug)</label>
                        <div class="relative flex items-center">
                            <span class="absolute left-4 text-slate-400 font-mono text-xs select-none">/post/</span>
                            <input type="text" name="slug" value="<?= htmlspecialchars($post['slug'] ?? '') ?>"
                                class="ui-input font-mono text-sm pl-16 text-blue-600" placeholder="tu-dong-tao-tu-tieu-de">
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500 font-medium"><i class="fas fa-info-circle mr-1"></i>Để trống để hệ thống tự động tạo đường dẫn từ tiêu đề.</p>
                    </div>

                    <!-- Tóm tắt -->
                    <div>
                        <label class="ui-label">Tóm tắt nội dung</label>
                        <textarea name="summary" rows="3" class="ui-input font-medium text-sm leading-relaxed resize-y"
                            placeholder="Viết một đoạn ngắn gọn giới thiệu nội dung bài viết..."><?= htmlspecialchars($post['summary'] ?? '') ?></textarea>
                    </div>

                    <!-- Nội dung chi tiết -->
                    <div>
                        <label class="ui-label">Nội dung chi tiết <span class="text-red-500">*</span></label>
                        <textarea name="content" rows="20" required class="ui-input font-normal leading-relaxed resize-y"
                            placeholder="Soạn thảo nội dung bài viết chi tiết tại đây..."><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                        <div class="mt-2 text-xs text-slate-500 font-medium">
                            <i class="fab fa-markdown mr-1 text-slate-400"></i> Hỗ trợ định dạng HTML cơ bản cho bài viết.
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Sidebar / Config Area (Right: 4 cols) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Publishing & Status -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-5 flex items-center border-b border-slate-100 pb-3">
                    <i class="fas fa-sliders-h text-blue-500 mr-2"></i> Thiết lập bài viết
                </h3>

                <div class="space-y-5">
                    <!-- Category -->
                    <div>
                        <label class="ui-label">Chuyên mục</label>
                        <div class="relative">
                            <select name="category" class="ui-input appearance-none font-medium text-slate-700 cursor-pointer pr-10">
                                <option value="Tin tức" <?= ($post['category'] ?? '') === 'Tin tức' ? 'selected' : '' ?>>📰 Tin tức tuyển sinh</option>
                                <option value="Thông báo" <?= ($post['category'] ?? '') === 'Thông báo' ? 'selected' : '' ?>>🔔 Thông báo quan trọng</option>
                                <option value="Hướng dẫn" <?= ($post['category'] ?? '') === 'Hướng dẫn' ? 'selected' : '' ?>>💡 Hướng dẫn nhập học</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="ui-label">Trạng thái xuất bản</label>
                        <div class="relative">
                            <select name="status" class="ui-input appearance-none font-medium text-slate-700 cursor-pointer pr-10">
                                <option value="Draft" <?= ($post['status'] ?? '') === 'Draft' ? 'selected' : '' ?>>📄 Lưu nháp</option>
                                <option value="Published" <?= ($post['status'] ?? '') === 'Published' ? 'selected' : '' ?>>🚀 Xuất bản ngay</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Toggle -->
                    <div class="pt-2">
                        <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50">
                            <div>
                                <span class="font-bold text-sm text-slate-800 block">Đánh dấu nổi bật</span>
                                <span class="text-xs text-slate-500 mt-0.5 block">Ghim bài lên đầu trang chủ</span>
                            </div>
                            <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in cursor-pointer">
                                <input type="checkbox" name="is_featured" id="is_featured" <?= ($post['is_featured'] ?? false) ? 'checked' : '' ?> class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer opacity-0" />
                                <label for="is_featured" class="toggle-label block overflow-hidden cursor-pointer"></label>
                                <span class="toggle-switch-handle pointer-events-none"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Media / Thumbnail -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-5 flex items-center border-b border-slate-100 pb-3">
                    <i class="fas fa-image text-emerald-500 mr-2"></i> Ảnh đại diện (Thumbnail)
                </h3>

                <div class="space-y-4">
                    <!-- Upload Area -->
                    <div class="relative group">
                        <input type="file" name="thumbnail_file" id="thumbnail_input" accept="image/*"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                            onchange="if(this.files[0]) { document.getElementById('preview_msg').innerText = this.files[0].name; document.getElementById('preview_msg').classList.add('text-blue-600', 'font-bold'); }">
                        <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 hover:bg-blue-50/50 hover:border-blue-400 transition-colors">
                            <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center text-slate-400 group-hover:text-blue-500 group-hover:scale-110 transition-transform mb-3">
                                <i class="fas fa-cloud-upload-alt text-xl"></i>
                            </div>
                            <p id="preview_msg" class="text-xs font-medium text-slate-500 text-center">
                                Trượt và Thả ảnh vào đây<br>hoặc <span class="text-blue-500">Tải lên từ máy tính</span>
                            </p>
                            <p class="text-[10px] text-slate-400 mt-2">Định dạng hỗ trợ: JPG, PNG, WEBP</p>
                        </div>
                    </div>

                    <div class="relative flex items-center">
                        <div class="flex-grow border-t border-slate-200"></div>
                        <span class="flex-shrink-0 mx-4 text-slate-400 text-xs font-bold uppercase">Hoặc dán URL</span>
                        <div class="flex-grow border-t border-slate-200"></div>
                    </div>

                    <!-- URL Input -->
                    <div>
                        <input type="text" name="thumbnail" value="<?= htmlspecialchars($post['thumbnail'] ?? '') ?>"
                            class="ui-input text-xs font-mono" placeholder="https://example.com/image.jpg">
                    </div>

                    <!-- Preview if existing -->
                    <?php if (!empty($post['thumbnail'])): ?>
                        <div class="mt-4 border border-slate-200 rounded-xl p-2 bg-slate-50">
                            <label class="ui-label !mb-2 !text-[10px]">Ảnh hiện tại</label>
                            <img src="<?= filter_var($post['thumbnail'], FILTER_VALIDATE_URL) ? $post['thumbnail'] : url('/' . $post['thumbnail']) ?>"
                                class="rounded-lg w-full h-32 object-cover border border-slate-200 shadow-sm" alt="Thumbnail Preview">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col gap-3 sticky top-24">
                <button type="submit" class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-[0_4px_12px_rgba(37,99,235,0.2)] hover:shadow-[0_8px_20px_rgba(37,99,235,0.3)] transition-all flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Lưu bài viết
                </button>
                <a href="<?= url('/admin/posts') ?>" class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition-colors flex items-center justify-center">
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