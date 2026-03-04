<!-- Tab 5 Header -->
<div class="flex justify-between items-center mb-2">
    <div>
        <h3 class="font-black text-slate-800 text-xl uppercase tracking-tight flex items-center">
            <i class="fas fa-list-ol mr-3 text-[#0066FF]"></i> Danh sách Nguyện vọng
        </h3>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Thứ tự ưu tiên tính từ NV1 · Tổng: <?= count($choices) ?> nguyện vọng</p>
    </div>
    <div id="btn_group_wishes">
        <button type="button" onclick="toggleEdit('wishes')" class="px-4 py-2 bg-[#0066FF] text-white font-bold rounded-xl shadow-md shadow-blue-200/50 hover:bg-blue-700 hover:-translate-y-0.5 transition transform text-sm">
            <i class="fas fa-edit mr-1.5"></i> Sửa thông tin
        </button>
    </div>
</div>
