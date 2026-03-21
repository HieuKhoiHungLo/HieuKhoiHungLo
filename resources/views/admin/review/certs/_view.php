<!-- View Mode -->
<div id="view_certs" class="space-y-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60 rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#0066FF]/10 text-[#0066FF] flex items-center justify-center">
                    <i class="fas fa-certificate text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Danh sách minh chứng</p>
                    <p class="text-sm font-bold text-slate-700">Chứng chỉ ngoại ngữ & Tin học</p>
                </div>
            </div>
            <div id="btn_group_certs">
                <button type="button" onclick="toggleEdit('certs')" 
                    class="px-5 py-2.5 bg-[#0066FF] text-white font-bold rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center gap-2 text-sm">
                    <i class="fas fa-edit"></i> Chỉnh sửa
                </button>
            </div>
        </div>

        <div class="p-6">
            <?php if (empty($certificates)): ?>
                <div class="flex flex-col items-center justify-center p-12 bg-slate-50 border-2 border-dashed border-slate-100 rounded-2xl">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-slate-200 mb-4 shadow-sm">
                        <i class="fas fa-award text-3xl"></i>
                    </div>
                    <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Chưa có chứng chỉ</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse border border-slate-100">
                        <thead>
                            <tr class="bg-slate-100/50">
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest w-16 text-center border border-slate-100">STT</th>
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest border border-slate-100">Loại chứng chỉ</th>
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest w-40 border border-slate-100">Điểm số</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $index => $cert): ?>
                                <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/30' ?> hover:bg-blue-50/50 transition-colors">
                                    <td class="px-4 py-4 text-center border border-slate-100">
                                        <span class="text-xs font-mono font-bold text-slate-400"><?= $index + 1 ?></span>
                                    </td>
                                    <td class="px-4 py-4 border border-slate-100">
                                        <span class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($cert['loai_chung_chi']) ?></span>
                                    </td>
                                    <td class="px-4 py-4 border border-slate-100">
                                        <span class="font-black text-slate-800 text-base leading-none"><?= htmlspecialchars($cert['diem_chung_chi']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>