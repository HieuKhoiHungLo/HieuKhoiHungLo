<!-- Edit Form -->
<div id="form_certs" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-[2rem] p-8 border border-blue-100 shadow-xl shadow-blue-50/50">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#0066FF] shadow-sm">
                <i class="fas fa-certificate"></i>
            </div>
            <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Chỉnh sửa Chứng chỉ</h3>
        </div>

        <?php if(!empty($certificates)): ?>
            <div class="space-y-4">
            <?php foreach ($certificates as $index => $cert): ?>
            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 relative group hover:border-blue-200 transition-colors">
                <span class="absolute top-0 right-0 bg-white border-b border-l border-slate-200 text-slate-400 font-bold text-[10px] px-3 py-1 rounded-bl-xl rounded-tr-xl">#<?= $index + 1 ?></span>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-1">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Loại chứng chỉ</label>
                        <div class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl font-bold text-slate-600 italic text-sm">
                            <?= $cert['loai_chung_chi'] ?>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Điểm số</label>
                        <input type="text" name="cert_score_<?= $cert['id'] ?>" value="<?= $cert['diem_chung_chi'] ?>" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl font-bold text-sm text-slate-700 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all">
                    </div>
                    <div class="col-span-1 md:col-span-2 space-y-1">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Cập nhật ảnh minh chứng</label>
                        <input type="file" name="cert_file_<?= $cert['id'] ?>" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 transition-all cursor-pointer">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <div class="inline-block p-4 rounded-full bg-slate-50 text-slate-300 mb-3"><i class="fas fa-certificate text-2xl"></i></div>
                <p class="text-slate-400 font-bold text-sm">Chưa có chứng chỉ để chỉnh sửa.</p>
            </div>
        <?php endif; ?>
        
        <div class="flex justify-end gap-3 pt-6 border-t border-slate-100 mt-6">
            <button type="button" onclick="toggleEdit('certs')" class="px-6 py-3 text-slate-500 font-bold text-sm uppercase tracking-wider hover:bg-slate-50 rounded-xl transition-colors">Hủy bỏ </button>
            <button type="button" onclick="saveSection('certs')" class="px-6 py-3 bg-[#0066FF] text-white font-bold text-sm uppercase tracking-wider rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">Lưu thay đổi</button>
        </div>
    </div>

</div>
