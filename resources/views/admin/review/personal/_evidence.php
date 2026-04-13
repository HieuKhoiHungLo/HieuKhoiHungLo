<style>
    .evidence-card {
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.4s ease, z-index 0s;
        z-index: 1;
        position: relative;
    }
    .evidence-card .evidence-img {
        transition: brightness 0.4s ease;
    }
    .evidence-card:hover {
        transform: scale(1.3); /* Phóng to cả khung hình lên 30% */
        z-index: 50;
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    }
    .evidence-card:hover .evidence-img {
        filter: brightness(0.9);
    }
    .evidence-card .hover-overlay {
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .evidence-card:hover .hover-overlay {
        opacity: 1;
    }
</style>

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
    <div class="relative group w-full border-2 border-slate-200 rounded-2xl overflow-hidden bg-slate-900 shadow-sm transition-all" style="aspect-ratio:<?= $aspect ?>;">
        <?php if ($hasImg): ?>
            <!-- IMAGE (Always zoomed behind toolbar) -->
            <div class="w-full h-full cursor-pointer transition-transform duration-500 hover:scale-[1.3] z-10">
                <img id="<?= $imgId ?>" loading="lazy" src="<?= $src ?>"
                    class="w-full h-full object-contain relative transition-all duration-500"
                    title="Di chuột để phóng to, nhấn nút xoay phía trên để xoay ảnh">
            </div>

            <!-- STABLE TOOLBAR (Always on Top) -->
            <div class="absolute top-0 left-0 right-0 h-9 bg-white/95 backdrop-blur-md border-b border-slate-200 z-[100] flex items-center justify-between px-3 opacity-0 group-hover:opacity-100 transition-all duration-300 pointer-events-auto">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest truncate mr-2"><?= $label ?></span>
                <div class="flex items-center gap-1.5">
                    <?php if ($rotateEnable): ?>
                        <button type="button"
                            onclick="rotateEvidenceImage('<?= $imgPath ?>', '<?= $imgId ?>', this)"
                            class="w-7 h-7 bg-slate-50 hover:bg-[#0066FF] text-slate-600 hover:text-white rounded-lg flex items-center justify-center transition-all shadow-sm"
                            title="Xoay ảnh 90 độ">
                            <i class="fas fa-redo-alt text-[10px]"></i>
                        </button>
                    <?php endif; ?>
                    <a href="<?= $rawSrc ?>" target="_blank"
                        class="w-7 h-7 bg-slate-50 hover:bg-[#0066FF] text-slate-600 hover:text-white rounded-lg flex items-center justify-center transition-all shadow-sm"
                        title="Xem ảnh gốc">
                        <i class="fas fa-external-link-alt text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- Label bottom (Overlay) -->
            <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/80 to-transparent z-[20] pointer-events-none flex justify-center">
                <span class="text-white text-[9px] font-black uppercase tracking-widest drop-shadow-sm"><?= $label ?></span>
            </div>

        <?php else: ?>
            <!-- Empty state -->
            <label for="<?= $uid ?>" class="flex flex-col items-center justify-center w-full h-full bg-slate-50 text-slate-400 cursor-pointer hover:border-[#0066FF] hover:bg-blue-50/30 transition-all group/empty">
                <i class="fas fa-camera text-2xl mb-2 group-hover/empty:text-[#0066FF] transition-colors"></i>
                <span class="text-[10px] font-black uppercase tracking-widest"><?= $label ?></span>
                <span class="text-[9px] text-slate-300 mt-1">Nhấn để tải ảnh lên</span>
            </label>
        <?php endif; ?>

        <!-- Hidden file inputs & Logic -->
        <input type="file" id="<?= $uid ?>" name="<?= $fileInputName ?>" accept="image/*" class="hidden personal-edit-file-trigger" onchange="handleAdminImgChange(this, '<?= $imgId ?>', '<?= $previewId ?>')">
    </div>

    <!-- Edit Button (Always outside the card) -->
    <div class="personal-edit-field hidden mt-2">
        <input type="file" id="<?= $uid ?>_btn" name="<?= $fileInputName ?>" accept="image/*" 
            class="block w-full text-[10px] text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-[#0066FF] hover:file:bg-blue-100 transition-all cursor-pointer"
            onchange="handleAdminImgChange(this, '<?= $imgId ?>', '<?= $previewId ?>')">
    </div>

    <img id="<?= $previewId ?>" class="hidden mt-2 w-full rounded-xl border border-slate-200 shadow-sm">
<?php
}
?>

<!-- Right: Avatar & CCCD Evidence Panel -->
<div class="bg-white rounded-[2rem] p-4 border border-slate-100 shadow-xl shadow-slate-200/50 sticky top-24">

    <!-- Avatar (Small, 1/4 width) -->
    <div style="width: 25%; margin: 0 auto 1rem auto;">
        <?php render_admin_img_card(
            $user['anh_dai_dien'] ?? '',
            'Ảnh thẻ',
            'current_avatar',
            'avatar',
            'preview_avatar',
            true,
            '3/4'
        ); ?>
    </div>

    <!-- CCCD Evidence (Focus on Front) -->
    <div class="w-full space-y-3 mb-3">
        <!-- CCCD Front (100% width) -->
        <div class="w-full">
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

        <!-- CCCD Back (Hidden per user request) -->
        <div class="hidden">
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