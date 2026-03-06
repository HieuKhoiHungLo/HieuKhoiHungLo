<!-- View Mode -->
<div id="view_certs" class="space-y-4">
    <?php if (empty($certificates)): ?>
        <div class="flex flex-col items-center justify-center p-12 bg-white rounded-[2rem] border-2 border-dashed border-slate-200">
            <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 mb-4">
                <i class="fas fa-certificate text-3xl"></i>
            </div>
            <p class="text-slate-400 font-bold text-sm uppercase tracking-widest">Chưa có chứng chỉ</p>
        </div>
    <?php else: ?>
        <?php foreach ($certificates as $cert): ?>
            <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex transition-all hover:shadow-md h-full">
                <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
                <div class="p-4 w-full flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600 font-black text-lg shadow-sm">
                            <?= mb_substr($cert['loai_chung_chi'], 0, 1) ?>
                        </div>
                        <div class="space-y-2">
                            <h4 class="font-bold text-slate-800 text-lg leading-tight"><?= $cert['loai_chung_chi'] ?></h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="w-5 h-5 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[8px]"><i class="fas fa-star"></i></span>
                                        <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Điểm chứng chỉ</span>
                                    </div>
                                    <span class="font-bold text-slate-800 text-lg"><?= $cert['diem_chung_chi'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end border-t sm:border-t-0 border-slate-100 pt-3 sm:pt-0 mt-2 sm:mt-0 px-4 sm:px-0">
                        <div class="text-right flex flex-col items-end">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="w-5 h-5 rounded-full bg-blue-100 text-[#0066FF] flex items-center justify-center text-[8px]"><i class="fas fa-exchange-alt"></i></span>
                                <span class="text-[11px] font-bold text-blue-400 uppercase tracking-wider">Quy đổi</span>
                            </div>
                            <span class="text-2xl font-black text-[#0066FF] tracking-tight"><?= $cert['diem_quy_doi'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>