<?php $pageTitle = ($post ? 'Cập nhật bài viết' : 'Thêm mới bài viết'); ?>
<?php ob_start(); ?>

<style>
    main { padding-top: 0.25rem !important; padding-bottom: 0.25rem !important; }
    footer { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
    .media-item:hover { border-color: #0066FF; background-color: #f0f7ff; }
    .we-visual { min-height: 80px; outline: none; padding: 10px; font-size: 13px; line-height: 1.7; background: #fafafa; }
    .we-visual:focus { background: #fff; }
    .we-visual img { max-width: 100%; height: auto; margin: 6px 0; }
    .we-visual a { color: #2563eb; text-decoration: underline; }
    .we-visual h2 { font-size: 1.3em; font-weight: 700; margin: 0.6em 0 0.3em; }
    .we-visual h3 { font-size: 1.15em; font-weight: 700; margin: 0.5em 0 0.3em; }
    .we-visual h4 { font-size: 1.05em; font-weight: 700; margin: 0.4em 0 0.2em; }
    .we-visual ul, .we-visual ol { padding-left: 1.5em; margin: 0.4em 0; }
    .we-visual li { margin-bottom: 2px; }
    .we-source { display: none; width: 100%; min-height: 80px; font-family: monospace; font-size: 12px; padding: 10px; border: none; outline: none; resize: vertical; background: #1e293b; color: #e2e8f0; }
    .tb-btn { padding: 2px 8px; cursor: pointer; border: none; background: transparent; }
    .tb-btn:hover { background: #e5e5e5; }
    .color-pick { width: 20px; height: 20px; border: 1px solid #ccc; cursor: pointer; padding: 0; vertical-align: middle; border-radius: 2px; }
</style>

<form id="postForm" action="<?= url('/admin/posts/save') ?>" method="POST" enctype="multipart/form-data" class="text-[13px] text-black font-sans">
    <div class="flex flex-wrap justify-between items-center mb-2 gap-2">
        <div>
            <h1 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-edit text-blue-500"></i> <?= $pageTitle ?>
            </h1>
        </div>
        <div class="flex items-center gap-2 ml-auto">
            <a href="<?= url('/admin/posts') ?>" class="px-3 py-1.5 border border-[#ccc] text-black bg-white hover:bg-[#f7f7f7] rounded shadow-sm text-[12px] flex items-center">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
            <button type="submit" class="px-5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded text-[13px] shadow-sm border border-blue-700 transition-colors">
                <i class="fas fa-check mr-1"></i> <?= $post ? 'Cập nhật' : 'Đăng bài' ?>
            </button>
        </div>
    </div>

    <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
    <input type="hidden" name="id" value="<?= $post['id'] ?? '' ?>">
    <input type="hidden" id="hidden-summary" name="summary" value="">
    <input type="hidden" id="hidden-content" name="content" value="">

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">
        <!-- Main Content Column -->
        <div class="lg:col-span-4 flex flex-col gap-3">
            <!-- Title -->
            <div>
                <input type="text" name="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required 
                       class="w-full px-3 py-1.5 text-[13px] font-bold border border-[#ccc] bg-white focus:bg-[#fafafa] rounded shadow-inner outline-none focus:border-[#999] placeholder-gray-400" 
                       placeholder="Nhập tiêu đề tại đây">
            </div>

            <!-- Permalink -->
            <div class="flex items-center gap-2 text-[12px] text-gray-600 bg-white border border-[#ccc] px-2 py-1 shadow-sm rounded">
                <span class="font-bold flex-shrink-0">Đường dẫn (URL):</span>
                <span><?= url('/news/detail?slug=') ?></span>
                <input type="text" name="slug" value="<?= htmlspecialchars($post['slug'] ?? '') ?>" 
                       class="flex-grow px-2 py-0.5 border border-[#ccc] bg-[#fafafa] outline-none focus:bg-white text-blue-600 font-mono" 
                       placeholder="de-trong-tu-dong-tao">
            </div>

            <!-- ===== SUMMARY EDITOR (WYSIWYG) ===== -->
            <div class="border border-[#ccc] bg-white shadow-sm overflow-hidden mt-1">
                <div class="px-2 py-1 border-b border-[#ccc] bg-[#f5f5f5] flex items-center justify-between gap-2">
                    <span class="font-bold text-[13px] text-black whitespace-nowrap">Trích dẫn</span>
                    <div class="flex gap-1 items-center flex-wrap">
                        <?php $eid = 'summary-visual'; ?>
                        <div class="flex border border-[#ccc] rounded bg-white overflow-hidden text-[13px] shadow-sm">
                            <button type="button" onclick="execCmd('<?=$eid?>','bold')" class="tb-btn font-bold" title="In đậm (Ctrl+B)">B</button>
                            <button type="button" onclick="execCmd('<?=$eid?>','italic')" class="tb-btn italic font-serif border-l border-[#ccc]" title="In nghiêng (Ctrl+I)">I</button>
                        </div>
                        <div class="flex border border-[#ccc] rounded bg-white overflow-hidden text-[13px] shadow-sm">
                            <button type="button" onclick="execCmd('<?=$eid?>','justifyLeft')" class="tb-btn" title="Căn trái"><i class="fas fa-align-left text-[#555]"></i></button>
                            <button type="button" onclick="execCmd('<?=$eid?>','justifyCenter')" class="tb-btn border-l border-[#ccc]" title="Căn giữa"><i class="fas fa-align-center text-[#555]"></i></button>
                            <button type="button" onclick="execCmd('<?=$eid?>','justifyRight')" class="tb-btn border-l border-[#ccc]" title="Căn phải"><i class="fas fa-align-right text-[#555]"></i></button>
                            <button type="button" onclick="execCmd('<?=$eid?>','justifyFull')" class="tb-btn border-l border-[#ccc]" title="Căn đều 2 bên"><i class="fas fa-align-justify text-[#555]"></i></button>
                        </div>
                        <div class="flex border border-[#ccc] rounded bg-white overflow-hidden text-[13px] shadow-sm">
                            <button type="button" id="src-btn-<?=$eid?>" onclick="toggleSource('<?=$eid?>')" class="tb-btn" title="Xem mã HTML"><i class="fas fa-code text-[#555]"></i></button>
                        </div>
                    </div>
                </div>
                <div id="<?=$eid?>" contenteditable="true" class="we-visual" style="min-height:60px;" data-placeholder="Trích dẫn ngắn giới thiệu bài viết..."><?= $post['summary'] ?? '' ?></div>
                <textarea id="summary-source" class="we-source" style="min-height:60px;"></textarea>
            </div>

            <!-- ===== CONTENT EDITOR (WYSIWYG) ===== -->
            <div class="border border-[#ccc] bg-white shadow-sm overflow-hidden">
                <div class="px-2 py-1 border-b border-[#ccc] bg-[#f5f5f5] flex items-center justify-between flex-wrap gap-2">
                    <span class="font-bold text-[13px] text-black whitespace-nowrap">Nội dung chi tiết</span>
                    <div class="flex gap-1 items-center flex-wrap">
                        <?php $eid = 'content-visual'; ?>
                        <!-- Bold / Italic -->
                        <div class="flex border border-[#ccc] rounded bg-white overflow-hidden text-[13px] shadow-sm">
                            <button type="button" onclick="execCmd('<?=$eid?>','bold')" class="tb-btn font-bold" title="In đậm">B</button>
                            <button type="button" onclick="execCmd('<?=$eid?>','italic')" class="tb-btn italic font-serif border-l border-[#ccc]" title="In nghiêng">I</button>
                        </div>
                        <!-- Heading -->
                        <div class="flex border border-[#ccc] rounded bg-white overflow-hidden text-[13px] shadow-sm">
                            <button type="button" onclick="execHeading('<?=$eid?>')" class="tb-btn font-bold text-[11px]" title="Heading (H2→H3→H4→P)">H</button>
                        </div>
                        <!-- Alignment -->
                        <div class="flex border border-[#ccc] rounded bg-white overflow-hidden text-[13px] shadow-sm">
                            <button type="button" onclick="execCmd('<?=$eid?>','justifyLeft')" class="tb-btn" title="Căn trái"><i class="fas fa-align-left text-[#555]"></i></button>
                            <button type="button" onclick="execCmd('<?=$eid?>','justifyCenter')" class="tb-btn border-l border-[#ccc]" title="Căn giữa"><i class="fas fa-align-center text-[#555]"></i></button>
                            <button type="button" onclick="execCmd('<?=$eid?>','justifyRight')" class="tb-btn border-l border-[#ccc]" title="Căn phải"><i class="fas fa-align-right text-[#555]"></i></button>
                            <button type="button" onclick="execCmd('<?=$eid?>','justifyFull')" class="tb-btn border-l border-[#ccc]" title="Căn đều 2 bên"><i class="fas fa-align-justify text-[#555]"></i></button>
                        </div>
                        <!-- Lists -->
                        <div class="flex border border-[#ccc] rounded bg-white overflow-hidden text-[13px] shadow-sm">
                            <button type="button" onclick="execCmd('<?=$eid?>','insertUnorderedList')" class="tb-btn" title="Danh sách"><i class="fas fa-list-ul text-[#555]"></i></button>
                            <button type="button" onclick="execCmd('<?=$eid?>','insertOrderedList')" class="tb-btn border-l border-[#ccc]" title="Danh sách số"><i class="fas fa-list-ol text-[#555]"></i></button>
                        </div>
                        <!-- Colors -->
                        <div class="flex border border-[#ccc] rounded bg-white overflow-hidden text-[13px] shadow-sm items-center">
                            <label class="tb-btn flex items-center gap-1 cursor-pointer" title="Màu chữ">
                                <i class="fas fa-font text-[#555]" style="font-size:11px;"></i>
                                <input type="color" id="fc-<?=$eid?>" value="#000000" class="color-pick" onchange="execForeColor('<?=$eid?>')">
                            </label>
                            <label class="tb-btn flex items-center gap-1 cursor-pointer border-l border-[#ccc]" title="Màu nền chữ">
                                <i class="fas fa-highlighter text-[#555]" style="font-size:11px;"></i>
                                <input type="color" id="bc-<?=$eid?>" value="#ffff00" class="color-pick" onchange="execBackColor('<?=$eid?>')">
                            </label>
                        </div>
                        <!-- Link & Image -->
                        <div class="flex border border-[#ccc] rounded bg-white overflow-hidden text-[13px] shadow-sm">
                            <button type="button" onclick="execLink('<?=$eid?>')" class="tb-btn" title="Chèn liên kết"><i class="fas fa-link text-[#555]"></i></button>
                            <button type="button" onclick="openMediaModal('content')" class="tb-btn border-l border-[#ccc]" title="Chèn ảnh từ thư viện"><i class="fas fa-image text-[#555]"></i></button>
                        </div>
                        <!-- Source -->
                        <div class="flex border border-[#ccc] rounded bg-white overflow-hidden text-[13px] shadow-sm">
                            <button type="button" id="src-btn-<?=$eid?>" onclick="toggleSource('<?=$eid?>')" class="tb-btn" title="Xem mã HTML"><i class="fas fa-code text-[#555]"></i></button>
                        </div>
                    </div>
                </div>
                <div id="<?=$eid?>" contenteditable="true" class="we-visual" style="min-height:300px;"><?= $post['content'] ?? '' ?></div>
                <textarea id="content-source" class="we-source" style="min-height:300px;"></textarea>
                <div class="border-t border-[#ccc] bg-[#f5f5f5] px-3 py-1 text-[11px] text-[#666] flex justify-end">
                    <span>Words: <span id="word-count">0</span></span>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="lg:col-span-1 flex flex-col gap-3">
            <!-- Publish Box -->
            <div class="border border-[#ccc] bg-white rounded shadow-sm">
                <div class="px-3 py-2 border-b border-[#ccc] bg-[#f5f5f5] font-bold text-[13px] text-black">Thiết lập đăng bài</div>
                <div class="p-3 space-y-3 bg-[#fafafa]">
                    <div class="flex items-center justify-between">
                        <span class="text-[#666] font-medium">Trạng thái:</span>
                        <select name="status" class="border border-[#ccc] bg-white px-1 py-0.5 outline-none text-[12px] rounded shadow-sm">
                            <option value="Published" <?= ($post['status'] ?? '') === 'Published' ? 'selected' : '' ?>>Xuất bản</option>
                            <option value="Draft" <?= ($post['status'] ?? '') === 'Draft' ? 'selected' : '' ?>>Lưu nháp</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[#666] font-medium">Nổi bật:</span>
                        <div class="flex items-center">
                            <input type="checkbox" name="is_featured" id="is_featured" value="1" <?= !empty($post['is_featured']) ? 'checked' : '' ?> class="mr-1">
                            <label for="is_featured" class="cursor-pointer text-[12px]">Ghim bài</label>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 border-t border-[#e5e5e5] pt-3">
                        <span class="text-[#666] font-medium whitespace-nowrap text-[12px]">Ngày:</span>
                        <input type="datetime-local" name="created_at" 
                               value="<?= !empty($post['created_at']) ? date('Y-m-d\TH:i', strtotime($post['created_at'])) : date('Y-m-d\TH:i') ?>" 
                               class="flex-1 min-w-0 border border-[#ccc] bg-white px-1 py-0.5 outline-none text-[11px] shadow-inner rounded">
                    </div>
                </div>
            </div>
            <!-- Categories Box -->
            <div class="border border-[#ccc] bg-white rounded shadow-sm">
                <div class="px-3 py-2 border-b border-[#ccc] bg-[#f5f5f5] font-bold text-[13px] text-black">Chuyên mục</div>
                <div class="p-3">
                    <div class="space-y-2 max-h-[120px] overflow-y-auto pr-2">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $index => $cat): ?>
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="radio" name="category" value="<?= htmlspecialchars($cat['name']) ?>" class="w-4 h-4 border-gray-300" 
                                           <?= ($post['category'] ?? ($index === 0 ? $cat['name'] : '')) === $cat['name'] ? 'checked' : '' ?>>
                                    <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($cat['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-xs text-gray-400 italic">Chưa có chuyên mục.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Featured Image Box -->
            <div class="border border-[#ccc] bg-white rounded shadow-sm">
                <div class="px-3 py-2 border-b border-[#ccc] bg-[#f5f5f5] font-bold text-[13px] text-black">Ảnh đại diện</div>
                <div class="p-3 bg-[#fafafa]">
                    <div id="thumbnail-preview-container" class="<?= empty($post['thumbnail']) ? 'hidden' : '' ?> mb-3">
                        <img id="thumbnail-preview" src="<?= empty($post['thumbnail']) ? '' : (filter_var($post['thumbnail'], FILTER_VALIDATE_URL) ? $post['thumbnail'] : url('/' . $post['thumbnail'])) ?>" 
                             class="w-full h-auto object-cover border border-[#ccc] p-0.5 bg-white shadow-sm">
                    </div>
                    <div class="space-y-3">
                        <div class="flex gap-1">
                            <label class="flex-grow flex items-center cursor-pointer group">
                                <span class="px-2 py-1 border border-[#ccc] bg-white group-hover:bg-[#e5e5e5] text-[10px] text-black shadow-sm whitespace-nowrap font-bold">
                                    <i class="fas fa-upload mr-1"></i> Tải lên
                                </span>
                                <input type="file" name="thumbnail_file" accept="image/*" class="hidden" onchange="document.getElementById('file-name-sidebar').innerText = this.files[0] ? this.files[0].name : ''">
                            </label>
                            <button type="button" onclick="openMediaModal('thumbnail')" class="px-2 py-1 border border-[#ccc] bg-white hover:bg-[#e5e5e5] text-[10px] text-black shadow-sm font-bold">
                                <i class="fas fa-images mr-1"></i> Thư viện
                            </button>
                        </div>
                        <div id="file-name-sidebar" class="text-[10px] text-blue-600 truncate font-medium h-3"></div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-[#666] font-bold uppercase whitespace-nowrap">Hoặc URL:</span>
                            <input type="text" id="thumbnail-url-input" name="thumbnail" value="<?= htmlspecialchars($post['thumbnail'] ?? '') ?>" 
                                   class="flex-grow w-full px-2 py-1 border border-[#ccc] bg-white outline-none shadow-inner text-[12px]" 
                                   placeholder="Link ảnh..." oninput="updateThumbnailPreview(this.value)">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Media Library Modal -->
<div id="mediaModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
        <div class="bg-gray-100 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-images text-blue-500"></i> Thư viện Media</h3>
            <button onclick="closeMediaModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        <div class="flex-grow overflow-y-auto p-6" id="media-list-container">
            <div class="flex justify-center py-20"><i class="fas fa-spinner fa-spin text-4xl text-blue-500"></i></div>
        </div>
        <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end">
            <button onclick="closeMediaModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-bold text-xs hover:bg-gray-300">Đóng</button>
        </div>
    </div>
</div>

<script src="<?= url('/assets/js/wysiwyg.js') ?>"></script>
<script>
let activeMediaTarget = 'thumbnail';

document.addEventListener('DOMContentLoaded', function() {
    initEditor('summary-visual', 'hidden-summary', 'summary-source');
    initEditor('content-visual', 'hidden-content', 'content-source');
});

// Sync before submit
document.getElementById('postForm').addEventListener('submit', function() {
    document.getElementById('hidden-summary').value = document.getElementById('summary-visual').innerHTML;
    document.getElementById('hidden-content').value = document.getElementById('content-visual').innerHTML;
});

function updateThumbnailPreview(url) {
    const container = document.getElementById('thumbnail-preview-container');
    const img = document.getElementById('thumbnail-preview');
    if (url) {
        img.src = url.startsWith('http') ? url : '<?= url("/") ?>' + url;
        container.classList.remove('hidden');
    } else { container.classList.add('hidden'); }
}

function openMediaModal(target) {
    activeMediaTarget = target;
    document.getElementById('mediaModal').classList.remove('hidden');
    loadMediaImages();
}

function closeMediaModal() {
    document.getElementById('mediaModal').classList.add('hidden');
}

async function loadMediaImages() {
    const container = document.getElementById('media-list-container');
    try {
        const res = await fetch('<?= url("/admin/media/api?type=image") ?>');
        const files = await res.json();
        if (files.length === 0) { container.innerHTML = '<div class="text-center py-20 text-gray-400 italic">Chưa có ảnh nào.</div>'; return; }
        let html = '<div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">';
        files.forEach(f => {
            html += `<div class="media-item border border-gray-200 rounded-lg overflow-hidden cursor-pointer p-1 bg-white" onclick="selectMedia('${f.path}','${f.full_url}')">
                <div class="aspect-square bg-gray-50 rounded overflow-hidden"><img src="${f.full_url}" class="w-full h-full object-cover"></div>
                <p class="text-[9px] text-gray-500 truncate text-center mt-1">${f.original_name}</p></div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    } catch (e) { container.innerHTML = '<div class="text-center py-20 text-red-500 font-bold">Lỗi tải thư viện!</div>'; }
}

function selectMedia(path, fullUrl) {
    if (activeMediaTarget === 'thumbnail') {
        document.getElementById('thumbnail-url-input').value = path;
        updateThumbnailPreview(path);
    } else {
        execInsertImage('content-visual', fullUrl);
    }
    closeMediaModal();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>