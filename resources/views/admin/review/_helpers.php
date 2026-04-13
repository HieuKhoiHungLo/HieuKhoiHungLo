<?php
/**
 * Helper mapping for grades/conduct (shared across review tabs)
 */
if (!isset($mapDisplay)) {
    $mapDisplay = function ($val) {
        $map = [
            'Gioi' => 'Giỏi',
            'Kha' => 'Khá',
            'Trung binh' => 'Trung bình',
            'TrungBinh' => 'Trung bình',
            'Yeu' => 'Yếu',
            'Tot' => 'Tốt'
        ];
        return $map[$val] ?? $val;
    };
}

/**
 * Consistent evidence item rendering (shared across review tabs)
 */
if (!function_exists('render_evidence_item')) {
    function render_evidence_item($path, $label, $imgId, $maxHeight = 'calc(100vh - 250px)')
    {
        ob_start(); ?>
        <div class="relative group w-full">
            <?php if (!empty($path)): ?>
                <?php
                $src = strpos($path, 'http') === 0 ? google_drive_thumbnail_url($path, 'w400') : asset($path);
                $link = strpos($path, 'http') === 0 ? $path : asset($path);
                ?>
                <div class="relative group w-full border-2 border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm transition-all" style="min-height: 120px;">
                    <!-- IMAGE (Always zoomed behind toolbar) -->
                    <div class="w-full h-full cursor-pointer transition-transform duration-500 hover:scale-[1.3] z-10">
                        <img id="<?= $imgId ?>" loading="lazy" src="<?= $src ?>"
                            class="w-full h-auto object-contain bg-slate-50"
                            style="min-height: 120px; max-height: <?= $maxHeight ?> !important;"
                            title="Di chuột để phóng to, nhấn nút xoay phía trên để xoay ảnh">
                    </div>

                    <!-- STABLE TOOLBAR (Always on Top) -->
                    <div class="absolute top-0 left-0 right-0 h-9 bg-white/95 backdrop-blur-md border-b border-slate-100 z-[100] flex items-center justify-between px-3 opacity-0 group-hover:opacity-100 transition-all duration-300 pointer-events-auto">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest truncate mr-2"><?= $label ?></span>
                        <div class="flex items-center gap-1.5">
                             <button type="button" onclick="rotateEvidenceImage('<?= $path ?>', '<?= $imgId ?>', this)"
                                class="w-7 h-7 bg-slate-50 hover:bg-[#0066FF] text-slate-600 hover:text-white rounded-lg flex items-center justify-center transition-all shadow-sm"
                                title="Xoay ảnh 90 độ">
                                <i class="fas fa-redo-alt text-[10px]"></i>
                            </button>
                            <a href="<?= $link ?>" target="_blank"
                                class="w-7 h-7 bg-slate-50 hover:bg-[#0066FF] text-slate-600 hover:text-white rounded-lg flex items-center justify-center transition-all shadow-sm"
                                title="Xem ảnh gốc">
                                <i class="fas fa-external-link-alt text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center aspect-[3/4] text-slate-300 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    <i class="fas fa-image text-4xl mb-2"></i>
                    <span class="text-[10px] uppercase font-bold tracking-wider">Không có ảnh minh chứng</span>
                    <img id="<?= $imgId ?>" loading="lazy" class="hidden w-full h-full object-cover absolute inset-0">
                </div>
            <?php endif; ?>
            <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/80 via-black/40 to-transparent z-[70] pointer-events-none rounded-b-xl overflow-hidden">
                <span class="text-[9px] font-black text-white uppercase tracking-widest drop-shadow-sm"><?= $label ?></span>
            </div>
        </div>
<?php return ob_get_clean();
    }
}
