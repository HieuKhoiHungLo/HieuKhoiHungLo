<!-- Evidence Preview Sticky Sidebar -->
<div class="bg-white rounded-[2rem] border border-blue-100 p-6 shadow-sm sticky top-24">


    <div class="space-y-6" id="cert_evidence_container">
        <?php if(empty($certificates)): ?>
        <div class="aspect-[3/4] bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center text-slate-300">
            <i class="fas fa-images text-5xl mb-4"></i>
            <span class="text-[10px] font-black uppercase tracking-widest">Không có chứng chỉ</span>
        </div>
        <?php else: ?>
            <?php foreach ($certificates as $idx => $cert): ?>
                <div class="space-y-3 animate-in fade-in duration-500">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-5 h-5 rounded-full bg-blue-100 text-[#0066FF] flex items-center justify-center text-[8px] font-bold">#<?= $idx + 1 ?></span>
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?= $cert['loai_chung_chi'] ?></span>
                    </div>
                    <?= render_evidence_item($cert['file_minh_chung_cc'] ?? '', "Chứng chỉ: " . $cert['loai_chung_chi'], "img_cert_" . $cert['id']) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
