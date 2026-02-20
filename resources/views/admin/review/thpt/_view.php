<!-- View Mode -->
<div id="view_thpt">
    <?php if(empty($diemThi)): ?>
            <div class="flex flex-col items-center justify-center p-12 bg-white rounded-[2rem] border-2 border-dashed border-slate-200">
                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 mb-4">
                    <i class="fas fa-poll-h text-3xl"></i>
                </div>
                <p class="text-slate-400 font-bold text-sm uppercase tracking-widest">Không có dữ liệu điểm thi</p>
            </div>
    <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
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
                foreach($thptSubjects as $code => $label): 
                    $val = $diemThi[$code] ?? null;
                    if($val === null) continue;
            ?>
            <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex transition-all hover:shadow-md">
                <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
                <div class="p-4 w-full">
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[10px]"><i class="fas fa-edit"></i></span>
                        <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider"><?= $label ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-bold text-slate-800 tracking-tight"><?= $val ?></span>
                        <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center shadow-sm">
                            <i class="fas fa-check text-[10px]"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
