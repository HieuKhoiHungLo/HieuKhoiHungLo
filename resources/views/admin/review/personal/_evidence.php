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
            <?php 
                $avatarSrc = strpos($user['anh_dai_dien'], 'http') === 0 ? google_drive_thumbnail_url($user['anh_dai_dien'], 'w400') : asset($user['anh_dai_dien']);
            ?>
            <img src="<?= $avatarSrc ?>" ondblclick="window.open('<?= strpos($user['anh_dai_dien'], 'http') === 0 ? $user['anh_dai_dien'] : asset($user['anh_dai_dien']) ?>', '_blank')" class="w-full aspect-[2/3] object-cover rounded-xl shadow-md border-2 border-slate-200 transition-transform duration-300 group-hover:scale-[1.3] group-hover:z-50 relative cursor-pointer" title="Double click to view full size">
            <a href="<?= strpos($user['anh_dai_dien'], 'http') === 0 ? $user['anh_dai_dien'] : asset($user['anh_dai_dien']) ?>" target="_blank" class="absolute inset-0 z-10 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-white/30 rounded-xl transition"><i class="fas fa-external-link-alt text-2xl text-slate-800"></i></a>
            <span class="absolute bottom-2 left-1/2 -translate-x-1/2 bg-black/60 text-white text-[10px] uppercase font-bold px-2 py-0.5 rounded whitespace-nowrap">Ảnh thẻ 4x6</span>
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
                <?php $cccdFrontSrc = strpos($user['anh_cccd_truoc'], 'http') === 0 ? google_drive_thumbnail_url($user['anh_cccd_truoc'], 'w400') : asset($user['anh_cccd_truoc']); ?>
                <img id="current_cccd_front" src="<?= $cccdFrontSrc ?>" ondblclick="window.open('<?= strpos($user['anh_cccd_truoc'], 'http') === 0 ? $user['anh_cccd_truoc'] : asset($user['anh_cccd_truoc']) ?>', '_blank')" class="w-full aspect-[3/2] object-cover rounded-xl shadow-md border-2 border-slate-200 transition-transform duration-300 group-hover:scale-[1.3] group-hover:z-50 relative cursor-pointer" title="Double click to view full size">
                <a href="<?= strpos($user['anh_cccd_truoc'], 'http') === 0 ? $user['anh_cccd_truoc'] : asset($user['anh_cccd_truoc']) ?>" target="_blank" class="absolute inset-0 z-10 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-white/30 rounded-xl transition"><i class="fas fa-external-link-alt text-2xl text-slate-800"></i></a>
                <span class="absolute bottom-2 left-1/2 -translate-x-1/2 bg-black/60 text-white text-[10px] uppercase font-bold px-2 py-0.5 rounded whitespace-nowrap">CCCD Mặt trước</span>
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
                <?php $cccdBackSrc = strpos($user['anh_cccd_sau'], 'http') === 0 ? google_drive_thumbnail_url($user['anh_cccd_sau'], 'w400') : asset($user['anh_cccd_sau']); ?>
                <img id="current_cccd_back" src="<?= $cccdBackSrc ?>" ondblclick="window.open('<?= strpos($user['anh_cccd_sau'], 'http') === 0 ? $user['anh_cccd_sau'] : asset($user['anh_cccd_sau']) ?>', '_blank')" class="w-full aspect-[3/2] object-cover rounded-xl shadow-md border-2 border-slate-200 transition-transform duration-300 group-hover:scale-[1.3] group-hover:z-50 relative cursor-pointer" title="Double click to view full size">
                <a href="<?= strpos($user['anh_cccd_sau'], 'http') === 0 ? $user['anh_cccd_sau'] : asset($user['anh_cccd_sau']) ?>" target="_blank" class="absolute inset-0 z-10 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-white/30 rounded-xl transition"><i class="fas fa-external-link-alt text-2xl text-slate-800"></i></a>
                <span class="absolute bottom-2 left-1/2 -translate-x-1/2 bg-black/60 text-white text-[10px] uppercase font-bold px-2 py-0.5 rounded whitespace-nowrap">CCCD Mặt sau</span>
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
</div>
