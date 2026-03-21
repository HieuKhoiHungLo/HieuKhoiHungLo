<!-- Tab 5 Header -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="font-black text-slate-800 text-xl uppercase tracking-tight flex items-center">
            <i class="fas fa-list-ol mr-3 text-[#0066FF]"></i> Danh sách Nguyện vọng
        </h3>
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-1.5 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-blue-100 flex items-center justify-center text-[6px] text-[#0066FF]"><i class="fas fa-info"></i></span>
            Thứ tự ưu tiên tính từ NV1 · Tổng: <span class="text-slate-700"><?= count($choices) ?></span> nguyện vọng
        </p>
    </div>
    <div id="btn_group_wishes">
        <button type="button" onclick="toggleEdit('wishes')" 
            class="px-5 py-2.5 bg-[#0066FF] text-white font-bold rounded-xl shadow-lg shadow-blue-100 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center gap-2 text-sm uppercase tracking-wider">
            <i class="fas fa-edit text-xs"></i> Sửa thông tin
        </button>
    </div>
</div>
