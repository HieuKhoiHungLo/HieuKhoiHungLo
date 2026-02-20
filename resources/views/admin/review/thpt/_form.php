<!-- Edit Form -->
<div id="form_thpt" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-[2rem] p-8 border border-blue-100 shadow-xl shadow-blue-50/50">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#0066FF] shadow-sm">
                <i class="fas fa-poll-h"></i>
            </div>
            <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Chỉnh sửa Điểm thi</h3>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <?php 
            $thptSubjects = [
                'toan'=>'Toán học', 
                'van'=>'Ngữ văn', 
                'ly'=>'Vật lý', 
                'hoa'=>'Hóa học', 
                'sinh'=>'Sinh học', 
                'su'=>'Lịch sử', 
                'dia'=>'Địa lý', 
                'gdcd'=>'GDCD',
                'tieng_anh'=>'Tiếng Anh',
                'tieng_trung'=>'Tiếng Trung',
                'ktpl'=>'KTPL',
                'tin_hoc'=>'Tin học',
                'cnnn'=>'CN-NN'
            ];
            foreach($thptSubjects as $code => $label): ?>
            <div class="space-y-1">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1"><?= $label ?></label>
                <input type="text" name="thpt_<?= $code ?>" value="<?= $diemThi[$code] ?? '' ?>" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl font-bold text-slate-700 text-center focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all placeholder:font-normal placeholder:text-slate-300" placeholder="-">
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 pt-6 border-t border-slate-100">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2">Cập nhật Minh chứng Điểm thi</label>
                <div class="flex items-center gap-4">
                    <div class="flex-1 relative">
                        <input type="file" name="thpt_file_evidence" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 transition-all cursor-pointer">
                    </div>
                </div>
        </div>
        

        <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-slate-100">
            <button type="button" onclick="toggleEdit('thpt')" class="px-6 py-3 text-slate-500 font-bold text-sm uppercase tracking-wider hover:bg-slate-50 rounded-xl transition-colors">Hủy bỏ </button>
            <button type="button" onclick="saveSection('thpt')" class="px-6 py-3 bg-[#0066FF] text-white font-bold text-sm uppercase tracking-wider rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">Lưu thay đổi</button>
        </div>
    </div>

</div>
