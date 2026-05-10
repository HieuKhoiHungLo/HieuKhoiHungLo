<?php
$title = 'Thư viện Media';
ob_start();
?>

<style>
    .media-table {
        border-collapse: separate !important;
        border-spacing: 0;
        width: 100%;
    }
    .media-table th, .media-table td {
        padding: 0.75rem 1rem;
        border: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        border-right: 1px solid #e2e8f0 !important;
        vertical-align: middle;
        font-size: 13px;
        color: #334155;
    }
    .media-table th:first-child, .media-table td:first-child {
        border-left: 1px solid #e2e8f0 !important;
    }
    .media-table thead tr:first-child th {
        border-top: 1px solid #e2e8f0 !important;
        background-color: #f8fafc;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        color: #64748b;
        letter-spacing: 0.05em;
    }
</style>

<div class="bg-white rounded-lg shadow-sm p-4 lg:p-5">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4 border-b border-gray-100 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-photo-video text-blue-500"></i> Thư viện Media
            </h1>
            <p class="text-gray-500 text-sm mt-1">Quản lý hình ảnh và tệp đính kèm (PDF, Word) cho bài viết.</p>
        </div>

        <div>
            <form action="<?= url('/admin/media/upload') ?>" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 bg-blue-50 p-3 rounded-2xl border border-blue-100 shadow-sm">
                <?= csrf_field() ?>
                <div class="relative group">
                    <input type="file" name="file" id="file_upload" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                    <div class="flex items-center gap-2 px-4 py-2 bg-white border border-blue-200 rounded-xl text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                        <i class="fas fa-file-import"></i>
                        <span id="file_name_display" class="text-xs font-bold truncate max-w-[150px]">Chọn tệp...</span>
                    </div>
                </div>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all font-bold text-sm shadow-lg shadow-blue-200 flex items-center gap-2">
                    <i class="fas fa-cloud-upload-alt"></i> Tải lên
                </button>
            </form>
        </div>
    </div>

    <?php if (empty($files)): ?>
        <div class="py-20 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                <i class="fas fa-folder-open text-4xl text-gray-300"></i>
            </div>
            <p class="text-gray-400 font-bold uppercase tracking-wider text-sm">Thư viện đang trống</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="media-table">
                <thead>
                    <tr>
                        <th class="w-20 text-center">Ảnh</th>
                        <th>Thông tin tệp</th>
                        <th>Đường dẫn (Link)</th>
                        <th class="w-32 text-center">Ngày tải</th>
                        <th class="w-20 text-center">Xóa</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php foreach ($files as $file): ?>
                        <?php 
                        $ext = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                        $fullUrl = url('/' . $file['path']);
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="text-center">
                                <div class="w-10 h-10 rounded border border-gray-200 overflow-hidden flex items-center justify-center mx-auto bg-gray-50">
                                    <?php if ($isImage): ?>
                                        <img src="<?= $fullUrl ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i class="fas fa-file-alt text-xl text-slate-400"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="font-medium text-slate-700"><?= htmlspecialchars($file['original_name']) ?></div>
                                <div class="text-[11px] text-slate-400 uppercase"><?= $ext ?> • <?= round($file['file_size'] / 1024, 1) ?> KB</div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <input type="text" readonly value="<?= $fullUrl ?>" 
                                        class="flex-grow bg-slate-50 border border-slate-200 rounded px-2 py-1 text-[11px] text-slate-500 font-mono outline-none focus:border-blue-400">
                                    <button onclick="copyToClipboard('<?= $fullUrl ?>')" 
                                        class="px-3 py-1 bg-white border border-slate-300 text-slate-600 rounded hover:bg-slate-50 transition-all text-[11px] font-bold shadow-sm whitespace-nowrap">
                                        <i class="fas fa-copy mr-1"></i> Copy
                                    </button>
                                </div>
                            </td>
                            <td class="text-center text-xs text-slate-500">
                                <?= date('d/m/Y', strtotime($file['created_at'])) ?>
                                <div class="text-[10px] opacity-60"><?= date('H:i', strtotime($file['created_at'])) ?></div>
                            </td>
                            <td class="text-center">
                                <a href="<?= url('/admin/media/delete?id=' . $file['id']) ?>" 
                                   onclick="return confirm('Xác nhận xóa tệp này?')" 
                                   class="text-slate-400 hover:text-red-500 transition-colors p-2">
                                    <i class="fas fa-trash-alt text-sm"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('file_upload').addEventListener('change', function(e) {
    const fileName = e.target.files[0] ? e.target.files[0].name : 'Chọn tệp...';
    document.getElementById('file_name_display').textContent = fileName;
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        if (typeof showToast === 'function') {
            showToast('Đã sao chép đường dẫn!', 'success');
        } else {
            alert('Đã sao chép đường dẫn!');
        }
    }).catch(err => {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        alert('Đã sao chép đường dẫn!');
    });
}
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php'; 
?>
