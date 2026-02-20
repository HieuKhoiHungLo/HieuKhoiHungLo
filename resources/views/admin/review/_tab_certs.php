<!-- TAB 3: CERTIFICATES -->
<div id="tab_certs" class="tab-content hidden transition-all duration-300">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        <!-- Left: List (8/12) -->
        <div class="md:col-span-8 space-y-6">
            <div class="flex justify-between items-center mb-2">
                <div>
                    <h3 class="font-black text-slate-800 text-xl uppercase tracking-tight flex items-center">
                        <i class="fas fa-certificate mr-3 text-sky-500"></i> Chứng chỉ quốc tế
                    </h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Danh mục chứng chỉ & Điểm quy đổi</p>
                </div>
                <div id="btn_group_certs">
                    <button type="button" onclick="toggleEdit('certs')" class="px-4 py-2 bg-[#0066FF] text-white font-bold rounded-xl shadow-md shadow-blue-200/50 hover:bg-blue-700 hover:-translate-y-0.5 transition transform text-sm flex items-center">
                        <i class="fas fa-edit mr-2"></i> Chỉnh sửa
                    </button>
                </div>
            </div>

            <?php include __DIR__ . '/certs/_view.php'; ?>
            <?php include __DIR__ . '/certs/_form.php'; ?>
            <?php include __DIR__ . '/certs/_status.php'; ?>
        </div>

        <!-- Right Column (4/12): Evidence Preview -->
        <div class="md:col-span-4 w-full">
            <?php include __DIR__ . '/certs/_evidence.php'; ?>
        </div>
    </div>
</div>
