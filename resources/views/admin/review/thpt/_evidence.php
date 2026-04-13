<!-- Evidence Preview Sticky Sidebar -->
<div class="bg-white rounded-[2rem] border border-blue-100 p-6 shadow-sm sticky top-24 max-h-[calc(100vh-120px)] overflow-y-auto custom-scrollbar">
    <div class="space-y-4">
        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 px-1 flex items-center justify-between">
            <span>Phiếu báo điểm THPT</span>
            <!-- Edit Icon Trigger -->
            <button type="button" 
                onclick="document.getElementById('thpt_file_evidence').click()" 
                class="thpt-edit-field hidden w-7 h-7 bg-blue-50 text-[#0066FF] rounded-lg border border-blue-100 flex items-center justify-center hover:bg-[#0066FF] hover:text-white transition-all shadow-sm"
                title="Thay đổi minh chứng">
                <i class="fas fa-camera text-xs"></i>
            </button>
        </label>

        <div class="relative group rounded-2xl overflow-hidden border border-slate-100 shadow-sm">
            <?= render_evidence_item($diemThi['file_chung_nhan'] ?? '', 'Phiếu báo điểm THPT', 'img_ev_thpt', 'calc(100vh - 250px)') ?>
            
            <!-- Hidden File Input -->
            <input type="file" id="thpt_file_evidence" name="thpt_file_evidence" accept="image/*" 
                onchange="previewThptCert(this)" class="hidden">
        </div>
    </div>
</div>

<script>
function previewThptCert(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('img_ev_thpt');
            if (img) {
                img.src = e.target.result;
                img.classList.remove('hidden');
                // Hide placeholder if any
                const parent = img.closest('.relative');
                if (parent) {
                    const placeholder = parent.querySelector('.flex.flex-col');
                    if (placeholder) placeholder.style.display = 'none';
                }
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
