<!-- TAB 2: ACADEMIC -->
<div id="tab_academic" class="tab-content hidden transition-all duration-300">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        <!-- Left: Info (8/12) -->
        <div class="md:col-span-8 space-y-6">
            <div class="flex justify-between items-center mb-2">
                <div>
                    <h3 class="font-black text-slate-800 text-xl uppercase tracking-tight flex items-center">
                        <i class="fas fa-graduation-cap mr-3 text-sky-500"></i> Kết quả Học tập (Học bạ)
                    </h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Thông tin học tập & Đối tượng Ưu tiên</p>
                </div>
                <div id="btn_group_academic">
                        <button type="button" onclick="toggleEdit('academic')" class="px-4 py-2 bg-[#0066FF] text-white font-bold rounded-xl shadow-md shadow-blue-200/50 hover:bg-blue-700 hover:-translate-y-0.5 transition transform text-sm"><i class="fas fa-edit mr-1.5"></i> Sửa thông tin</button>
                </div>
            </div>

            <?php include __DIR__ . '/academic/_view.php'; ?>
            <?php include __DIR__ . '/academic/_form.php'; ?>
            <?php include __DIR__ . '/academic/_status.php'; ?>
        </div>

        <!-- Right: Evidence (4/12) -->
        <div class="md:col-span-4 space-y-6">
            <?php include __DIR__ . '/academic/_evidence.php'; ?>
        </div>
    </div>
</div>
