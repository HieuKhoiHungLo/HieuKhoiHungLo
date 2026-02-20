<!-- Evidence Preview Sticky Sidebar -->
<div class="bg-white rounded-[2rem] border border-blue-100 p-6 shadow-sm sticky top-24">
    <div class="flex items-center justify-between mb-6 pb-2 border-b border-slate-50">
        <h4 class="font-black text-slate-800 text-sm uppercase tracking-widest flex items-center">
            <i class="fas fa-eye mr-2 text-sky-500"></i> Minh chứng Điểm thi
        </h4>
    </div>

    <div class="space-y-4">
        <?= render_evidence_item($diemThi['file_chung_nhan'] ?? '', 'Phiếu báo điểm THPT', 'img_ev_thpt') ?>
    </div>
</div>
