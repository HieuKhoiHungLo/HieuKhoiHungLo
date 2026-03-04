<!-- Academic Evidence Sticky Sidebar -->
<div class="bg-white rounded-[2rem] border border-blue-100 p-6 shadow-sm sticky top-24" x-data="{ activeGrade: '10' }">
    <div class="flex items-center justify-between mb-5 pb-2 border-b border-slate-50">
        <h4 class="font-black text-slate-800 text-sm uppercase tracking-widest flex items-center">
            <i class="fas fa-eye mr-2 text-sky-500"></i> Minh chứng Học bạ
        </h4>
    </div>

    <!-- Grade Tabs -->
    <div class="flex p-1.5 bg-slate-100/50 rounded-2xl mb-6 overflow-x-auto shadow-inner border border-slate-100 gap-1">
        <?php foreach(['10'=>'Lớp 10', '11'=>'Lớp 11', '12'=>'Lớp 12', 'KV'=>'KVUT', 'DT'=>'ĐTUT'] as $key => $label): ?>
        <button type="button"
                @click="activeGrade = '<?= $key ?>'"
                :class="activeGrade === '<?= $key ?>' ? 'bg-[#0066FF] text-white shadow-lg shadow-blue-200 scale-105' : 'text-slate-400 hover:text-slate-600 hover:bg-white/50'"
                class="flex-1 py-2.5 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all duration-300 whitespace-nowrap min-w-[68px]">
            <?= $label ?>
        </button>
        <?php endforeach; ?>
    </div>

    <div class="space-y-6">
        <!-- Academic Transcripts -->
        <?php foreach([10 => 'Lớp 10', 11 => 'Lớp 11', 12 => 'Lớp 12'] as $g => $label): ?>
            <div x-show="activeGrade === '<?= $g ?>'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="space-y-4">
                <div class="flex flex-col gap-4">
                    <?php
                    $img1 = $rowsByGrade[$g]['file_minh_chung_1'] ?? '';
                    $img2 = $rowsByGrade[$g]['file_minh_chung_2'] ?? '';
                    ?>
                    <?php if (!empty($img1) || !empty($img2)): ?>
                        <?= render_evidence_item($img1, 'Ảnh minh chứng 1', "img_ev_{$g}_1") ?>
                        <?php if(!empty($img2)): ?>
                            <?= render_evidence_item($img2, 'Ảnh minh chứng 2', "img_ev_{$g}_2") ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center p-12 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-center">
                            <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center text-slate-200 mb-4 shadow-sm">
                                <i class="fas fa-image text-2xl"></i>
                            </div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Không có ảnh minh chứng</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Priority Area (KV) -->
        <div x-show="activeGrade === 'KV'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            <?php if(!empty($user['file_minh_chung_kv'])): ?>
                <?= render_evidence_item($user['file_minh_chung_kv'], 'Minh chứng Khu vực Ưu tiên', 'img_ev_kv') ?>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center p-12 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-center">
                    <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center text-slate-200 mb-4 shadow-sm">
                        <i class="fas fa-map-marker-alt text-2xl"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Không có ảnh minh chứng KV</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Priority Object (DT) -->
        <div x-show="activeGrade === 'DT'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            <?php if(!empty($user['file_minh_chung_dt'])): ?>
                <?= render_evidence_item($user['file_minh_chung_dt'], 'Minh chứng Đối tượng Ưu tiên', 'img_ev_dt') ?>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center p-12 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-center">
                    <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center text-slate-200 mb-4 shadow-sm">
                        <i class="fas fa-user-tag text-2xl"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Không có ảnh minh chứng ĐT</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
