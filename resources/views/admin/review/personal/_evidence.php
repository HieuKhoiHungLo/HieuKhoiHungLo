<?php
// Helper macro: render a single evidence image card with hover overlay
function render_admin_img_card($imgPath, $label, $imgId, $fileInputName, $previewId, $rotateEnable = true, $aspect = '3/2')
{
    $src = !empty($imgPath)
        ? (strpos($imgPath, 'http') === 0 ? google_drive_thumbnail_url($imgPath, 'w400') : asset($imgPath))
        : '';
    $rawSrc = !empty($imgPath)
        ? (strpos($imgPath, 'http') === 0 ? $imgPath : asset($imgPath))
        : '';
    $hasImg = !empty($imgPath);
    $uid = 'img_upload_' . $imgId;
    $previewTriggerId = $previewId;
?>
    <div class="space-y-0">
        <!-- Image with hover overlay -->
        <div class="relative group w-full" style="aspect-ratio:<?= $aspect ?>">
            <?php if ($hasImg): ?>
                <!-- Image -->
                <img id="<?= $imgId ?>" loading="lazy" src="<?= $src ?>"
                    class="w-full h-full object-cover rounded-2xl border-2 border-slate-200 shadow-sm transition-all duration-300 group-hover:brightness-75">

                <!-- Overlay: Đổi ảnh (always visible on hover) -->
                <label for="<?= $uid ?>" class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 cursor-pointer rounded-2xl">
                    <div class="bg-white/90 text-slate-800 px-4 py-2 rounded-full shadow-lg flex items-center gap-2 font-black text-xs uppercase tracking-wider hover:bg-[#0066FF] hover:text-white transition-all">
                        <i class="fas fa-camera"></i> Đổi ảnh
                    </div>
                </label>

                <!-- Rotate button (top right) -->
                <?php if ($rotateEnable): ?>
                    <button type="button"
                        onclick="rotateEvidenceImage('<?= $imgPath ?>', '<?= $imgId ?>', this)"
                        class="absolute top-2 right-2 z-30 w-8 h-8 bg-white hover:bg-[#0066FF] text-slate-700 hover:text-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300"
                        title="Xoay ảnh 90 độ">
                        <i class="fas fa-redo-alt text-xs"></i>
                    </button>
                <?php endif; ?>

                <!-- Label bottom -->
                <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/70 to-transparent rounded-b-2xl pointer-events-none flex justify-center">
                    <span class="text-white text-[10px] font-black uppercase tracking-wider"><?= $label ?></span>
                </div>

            <?php else: ?>
                <!-- Empty state — also clickable to upload -->
                <label for="<?= $uid ?>" class="flex flex-col items-center justify-center w-full h-full rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 text-slate-400 cursor-pointer hover:border-[#0066FF] hover:bg-blue-50/30 transition-all group/empty">
                    <i class="fas fa-camera text-2xl mb-2 group-hover/empty:text-[#0066FF] transition-colors"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest"><?= $label ?></span>
                    <span class="text-[9px] text-slate-300 mt-1">Nhấn để tải ảnh lên</span>
                </label>
            <?php endif; ?>

            <!-- Hidden file input (shared between modes) -->
            <input type="file"
                id="<?= $uid ?>"
                name="<?= $fileInputName ?>"
                accept="image/*"
                class="hidden personal-edit-file-trigger"
                onchange="handleAdminImgChange(this, '<?= $imgId ?>', '<?= $previewTriggerId ?>')">
        </div>

        <!-- Preview of newly chosen image -->
        <img id="<?= $previewTriggerId ?>" class="hidden mt-2 w-full rounded-xl border border-slate-200 shadow-sm">
    </div>
<?php
}
?>

<!-- Right: Avatar & CCCD Evidence Panel -->
<div class="bg-white rounded-[2rem] p-4 border border-slate-100 shadow-xl shadow-slate-200/50 sticky top-24">

    <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-50">
        <i class="fas fa-camera text-slate-400 text-sm"></i>
        <h4 class="font-black text-slate-600 text-xs uppercase tracking-widest">Ảnh hồ sơ</h4>
    </div>

    <!-- Avatar -->
    <div class="w-1/2 mx-auto mb-4">
        <?php render_admin_img_card(
            $user['anh_dai_dien'] ?? '',
            'Ảnh thẻ 3x4',
            'current_avatar',
            'avatar',
            'preview_avatar',
            true,
            '3/4'
        ); ?>
    </div>

    <!-- CCCD Front -->
    <div class="mb-3">
        <?php render_admin_img_card(
            $user['anh_cccd_truoc'] ?? '',
            'CCCD Mặt trước',
            'current_cccd_front',
            'cccd_front',
            'preview_cccd_front',
            true,
            '3/2'
        ); ?>
    </div>

    <!-- CCCD Back -->
    <div class="mb-3">
        <?php render_admin_img_card(
            $user['anh_cccd_sau'] ?? '',
            'CCCD Mặt sau',
            'current_cccd_back',
            'cccd_back',
            'preview_cccd_back',
            true,
            '3/2'
        ); ?>
    </div>

    <!-- Minh chứng KV Ưu tiên -->
    <div class="mt-4 pt-4 border-t border-slate-100 personal-edit-field hidden">
        <label class="block text-[10px] font-black text-[#ff8800] uppercase tracking-widest mb-2">Minh chứng Khu vực</label>
        <?php if (!empty($user['file_minh_chung_kv'])): ?>
            <div class="text-[10px] text-slate-400 mb-2 truncate">Hiện có: <?= basename($user['file_minh_chung_kv']) ?></div>
        <?php endif; ?>
        <input type="file" name="kv_file" accept=".pdf,image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 transition-all cursor-pointer">
    </div>

    <!-- Minh chứng ĐT Ưu tiên -->
    <div class="mt-4 personal-edit-field hidden">
        <label class="block text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2">Minh chứng Đối tượng</label>
        <?php if (!empty($user['file_minh_chung_dt'])): ?>
            <div class="text-[10px] text-slate-400 mb-2 truncate">Hiện có: <?= basename($user['file_minh_chung_dt']) ?></div>
        <?php endif; ?>
        <input type="file" name="dt_file" accept=".pdf,image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 transition-all cursor-pointer">
    </div>

</div>

<script>
    // Preview khi admin chọn ảnh mới (dùng cho cả 2 mode: hover overlay & edit field)
    function handleAdminImgChange(input, currentImgId, previewId) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            // Cập nhật ảnh hiện tại luôn
            const currentImg = document.getElementById(currentImgId);
            if (currentImg) {
                currentImg.src = e.target.result;
            }
            // Ẩn preview riêng (không cần nữa vì đã cập nhật ảnh chính)
            const preview = document.getElementById(previewId);
            if (preview) preview.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }
</script>