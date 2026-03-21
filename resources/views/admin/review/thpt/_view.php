<!-- View Mode -->
<div id="view_thpt" class="space-y-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60 rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#0066FF]/10 text-[#0066FF] flex items-center justify-center">
                    <i class="fas fa-poll-h text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Kết quả kỳ thi</p>
                    <p class="text-sm font-bold text-slate-700">Điểm thi tốt nghiệp THPT</p>
                </div>
            </div>
            <div id="btn_group_thpt">
                <button type="button" onclick="toggleEdit('thpt')" 
                    class="px-5 py-2.5 bg-[#0066FF] text-white font-bold rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center gap-2 text-sm">
                    <i class="fas fa-edit"></i> Chỉnh sửa
                </button>
            </div>
        </div>

        <div class="p-6">
            <?php if(empty($diemThi) || !($diemThi['da_co_diem'] ?? false)): ?>
                <div class="flex flex-col items-center justify-center p-12 bg-slate-50 border-2 border-dashed border-slate-100 rounded-2xl">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-slate-200 mb-4 shadow-sm">
                        <i class="fas fa-ghost text-3xl"></i>
                    </div>
                    <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Không có dữ liệu điểm thi</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse border border-slate-100">
                        <thead>
                            <tr class="bg-slate-100/50">
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest w-16 text-center border border-slate-100">STT</th>
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest border border-slate-100">Môn học</th>
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest w-40 border border-slate-100">Điểm số</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $thptSubjects = [
                                    'toan'=>'Toán học', 'van'=>'Ngữ văn', 'ly'=>'Vật lý', 'hoa'=>'Hóa học', 
                                    'sinh'=>'Sinh học', 'su'=>'Lịch sử', 'dia'=>'Địa lý', 'gdcd'=>'GDCD',
                                    'tieng_anh'=>'Tiếng Anh', 'tieng_trung'=>'Tiếng Trung', 'ktpl'=>'KTPL',
                                    'tin_hoc'=>'Tin học', 'cnnn'=>'CN-NN'
                                ];
                                $rowIdx = 1;
                                foreach($thptSubjects as $code => $label): 
                                    $val = $diemThi[$code] ?? null;
                                    if($val === null || $val === '') continue;
                            ?>
                                <tr class="<?= $rowIdx % 2 !== 0 ? 'bg-white' : 'bg-slate-50/30' ?> hover:bg-blue-50/50 transition-colors">
                                    <td class="px-4 py-4 text-center border border-slate-100">
                                        <span class="text-xs font-mono font-bold text-slate-400"><?= $rowIdx++ ?></span>
                                    </td>
                                    <td class="px-4 py-4 border border-slate-100">
                                        <span class="font-bold text-slate-700 text-sm"><?= $label ?></span>
                                    </td>
                                    <td class="px-4 py-4 border border-slate-100">
                                        <div class="flex items-center justify-between">
                                            <span class="font-black text-slate-800 text-base leading-none"><?= $val ?></span>
                                            <div class="w-6 h-6 rounded-full bg-blue-50 text-[#0066FF] flex items-center justify-center shadow-sm">
                                                <i class="fas fa-check text-[8px]"></i>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if(!empty($diemThi['file_chung_nhan'])): ?>
                    <div class="mt-8 pt-8 border-t border-slate-100">
                        <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                             <i class="fas fa-image text-[#0066FF]"></i> Minh chứng Giấy chứng nhận
                        </h4>
                        <div class="max-w-md bg-slate-50 p-2 rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                             <img src="<?= url($diemThi['file_chung_nhan']) ?>" class="w-full h-auto rounded-xl object-contain hover:scale-[1.02] transition-transform duration-500">
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
