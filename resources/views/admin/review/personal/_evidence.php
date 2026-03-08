<!-- Right: Avatar & CCCD (Evidence) -->
<div class="bg-white rounded-[2rem] p-4 border border-slate-100 shadow-xl shadow-slate-200/50 sticky top-24">

    <div class="w-1/3 mx-auto">
        <?php if (empty($user['anh_dai_dien'])): ?>
            <div class="aspect-[2/3] bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center text-slate-300 group hover:border-[#0066FF]/50 transition-colors">
                <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-user text-3xl text-slate-300 group-hover:text-[#0066FF]"></i>
                </div>
                <span class="text-[10px] font-black uppercase tracking-wider">Chưa có ảnh</span>
            </div>
        <?php else: ?>
            <div class="relative group">
                <div class="relative w-full overflow-hidden rounded-xl shadow-md border-2 border-slate-200 group/img">
                    <?php
                    $avatarSrc = strpos($user['anh_dai_dien'], 'http') === 0 ? google_drive_thumbnail_url($user['anh_dai_dien'], 'w400') : asset($user['anh_dai_dien']);
                    ?>
                    <img id="current_avatar" loading="lazy" src="<?= $avatarSrc ?>" ondblclick="window.open('<?= strpos($user['anh_dai_dien'], 'http') === 0 ? $user['anh_dai_dien'] : asset($user['anh_dai_dien']) ?>', '_blank')" class="w-full aspect-[2/3] object-cover transition-transform duration-300 group-hover/img:scale-[1.3] relative cursor-pointer" title="Double click to view full size">
                    <a href="<?= strpos($user['anh_dai_dien'], 'http') === 0 ? $user['anh_dai_dien'] : asset($user['anh_dai_dien']) ?>" target="_blank" class="absolute inset-0 z-10 flex items-center justify-center opacity-0 group-hover/img:opacity-100 bg-white/30 transition"><i class="fas fa-external-link-alt text-2xl text-slate-800"></i></a>
                </div>
                <button type="button" onclick="rotateEvidenceImage('<?= $user['anh_dai_dien'] ?>', 'current_avatar', this)" class="absolute top-2 right-2 z-50 w-8 h-8 bg-white hover:bg-[#0066FF] text-slate-700 hover:text-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform hover:scale-110" title="Xoay ảnh 90 độ">
                    <i class="fas fa-redo-alt text-sm"></i>
                </button>
                <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/80 via-black/40 to-transparent z-40 pointer-events-none rounded-b-xl overflow-hidden flex justify-center pb-2">
                    <span class="text-white text-[10px] uppercase font-bold px-2 py-0.5 whitespace-nowrap drop-shadow-sm">Ảnh thẻ 4x6</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-2 personal-edit-field hidden">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Thay ảnh thẻ</label>
            <input type="file" name="anh_dai_dien" accept="image/*" onchange="previewPersonalImg(this, 'preview_avatar')" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-[#0066FF] hover:file:bg-blue-100 transition-all cursor-pointer">
            <img id="preview_avatar" class="hidden mt-2 w-full rounded-lg border border-slate-200">
        </div>

    </div>


    <!-- CCCD Mặt trước -->
    <div class="mt-2">
        <?php if (!empty($user['anh_cccd_truoc'])): ?>
            <div class="relative group mb-3">
                <div class="relative w-full overflow-hidden rounded-xl shadow-md border-2 border-slate-200 group/img">
                    <?php $cccdFrontSrc = strpos($user['anh_cccd_truoc'], 'http') === 0 ? google_drive_thumbnail_url($user['anh_cccd_truoc'], 'w400') : asset($user['anh_cccd_truoc']); ?>
                    <img id="current_cccd_front" loading="lazy" src="<?= $cccdFrontSrc ?>" ondblclick="window.open('<?= strpos($user['anh_cccd_truoc'], 'http') === 0 ? $user['anh_cccd_truoc'] : asset($user['anh_cccd_truoc']) ?>', '_blank')" class="w-full aspect-[3/2] object-cover transition-transform duration-300 group-hover/img:scale-[1.3] relative cursor-pointer" title="Double click to view full size">
                    <a href="<?= strpos($user['anh_cccd_truoc'], 'http') === 0 ? $user['anh_cccd_truoc'] : asset($user['anh_cccd_truoc']) ?>" target="_blank" class="absolute inset-0 z-10 flex items-center justify-center opacity-0 group-hover/img:opacity-100 bg-white/30 transition"><i class="fas fa-external-link-alt text-2xl text-slate-800"></i></a>
                </div>
                <button type="button" onclick="rotateEvidenceImage('<?= $user['anh_cccd_truoc'] ?>', 'current_cccd_front', this)" class="absolute top-2 right-2 z-50 w-8 h-8 bg-white hover:bg-[#0066FF] text-slate-700 hover:text-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform hover:scale-110" title="Xoay ảnh 90 độ">
                    <i class="fas fa-redo-alt text-sm"></i>
                </button>
                <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/80 via-black/40 to-transparent z-40 pointer-events-none rounded-b-xl overflow-hidden flex justify-center pb-2">
                    <span class="text-white text-[10px] uppercase font-bold px-2 py-0.5 whitespace-nowrap drop-shadow-sm">CCCD Mặt trước</span>
                </div>
            </div>
        <?php else: ?>
            <div class="aspect-[3/2] bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center text-slate-300 mb-3">
                <i class="fas fa-id-card text-3xl mb-2"></i>
                <span class="text-[10px] font-black uppercase tracking-wider">Chưa có ảnh</span>
            </div>
        <?php endif; ?>
        <div class="personal-edit-field hidden">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Thay ảnh CCCD trước</label>
            <input type="file" name="anh_cccd_truoc" accept="image/*" onchange="previewPersonalImg(this, 'preview_cccd_front')" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-emerald-50 file:text-emerald-600 hover:file:bg-emerald-100 transition-all cursor-pointer">
            <img id="preview_cccd_front" class="hidden mt-2 w-full rounded-lg border border-slate-200">
        </div>
    </div>

    <!-- CCCD Mặt sau -->
    <div class="mt-2">
        <?php if (!empty($user['anh_cccd_sau'])): ?>
            <div class="relative group mb-3">
                <div class="relative w-full overflow-hidden rounded-xl shadow-md border-2 border-slate-200 group/img">
                    <?php $cccdBackSrc = strpos($user['anh_cccd_sau'], 'http') === 0 ? google_drive_thumbnail_url($user['anh_cccd_sau'], 'w400') : asset($user['anh_cccd_sau']); ?>
                    <img id="current_cccd_back" loading="lazy" src="<?= $cccdBackSrc ?>" ondblclick="window.open('<?= strpos($user['anh_cccd_sau'], 'http') === 0 ? $user['anh_cccd_sau'] : asset($user['anh_cccd_sau']) ?>', '_blank')" class="w-full aspect-[3/2] object-cover transition-transform duration-300 group-hover/img:scale-[1.3] relative cursor-pointer" title="Double click to view full size">
                    <a href="<?= strpos($user['anh_cccd_sau'], 'http') === 0 ? $user['anh_cccd_sau'] : asset($user['anh_cccd_sau']) ?>" target="_blank" class="absolute inset-0 z-10 flex items-center justify-center opacity-0 group-hover/img:opacity-100 bg-white/30 transition"><i class="fas fa-external-link-alt text-2xl text-slate-800"></i></a>
                </div>
                <button type="button" onclick="rotateEvidenceImage('<?= $user['anh_cccd_sau'] ?>', 'current_cccd_back', this)" class="absolute top-2 right-2 z-50 w-8 h-8 bg-white hover:bg-[#0066FF] text-slate-700 hover:text-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform hover:scale-110" title="Xoay ảnh 90 độ">
                    <i class="fas fa-redo-alt text-sm"></i>
                </button>
                <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/80 via-black/40 to-transparent z-40 pointer-events-none rounded-b-xl overflow-hidden flex justify-center pb-2">
                    <span class="text-white text-[10px] uppercase font-bold px-2 py-0.5 whitespace-nowrap drop-shadow-sm">CCCD Mặt sau</span>
                </div>
            </div>
        <?php else: ?>
            <div class="aspect-[3/2] bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center text-slate-300 mb-3">
                <i class="fas fa-id-card text-3xl mb-2"></i>
                <span class="text-[10px] font-black uppercase tracking-wider">Chưa có ảnh</span>
            </div>
        <?php endif; ?>
        <div class="personal-edit-field hidden">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Thay ảnh CCCD sau</label>
            <input type="file" name="anh_cccd_sau" accept="image/*" onchange="previewPersonalImg(this, 'preview_cccd_back')" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-amber-50 file:text-amber-600 hover:file:bg-amber-100 transition-all cursor-pointer">
            <img id="preview_cccd_back" class="hidden mt-2 w-full rounded-lg border border-slate-200">
        </div>
    </div>

    <!-- Minh chứng KV Ưu tiên -->
    <div class="mt-4 pt-4 border-t border-slate-100 personal-edit-field hidden">
        <label class="block text-[11px] font-black text-[#ff8800] uppercase tracking-widest mb-2">Minh chứng Khu vực</label>
        <?php if (!empty($user['file_minh_chung_kv'])): ?>
            <div class="text-[10px] text-slate-400 mb-2 truncate">Hiện có: <?= basename($user['file_minh_chung_kv']) ?></div>
        <?php endif; ?>
        <input type="file" name="kv_file" accept=".pdf,image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 transition-all cursor-pointer">
    </div>

    <!-- Minh chứng ĐT Ưu tiên -->
    <div class="mt-4 personal-edit-field hidden">
        <label class="block text-[11px] font-black text-blue-600 uppercase tracking-widest mb-2">Minh chứng Đối tượng</label>
        <?php if (!empty($user['file_minh_chung_dt'])): ?>
            <div class="text-[10px] text-slate-400 mb-2 truncate">Hiện có: <?= basename($user['file_minh_chung_dt']) ?></div>
        <?php endif; ?>
        <input type="file" name="dt_file" accept=".pdf,image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 transition-all cursor-pointer">
    </div>
</div>