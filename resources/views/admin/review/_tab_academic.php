<!-- TAB 2: ACADEMIC -->
<div id="tab_academic" class="tab-content hidden transition-all duration-300">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left: Info (6/12) -->
        <div class="lg:col-span-6 space-y-6 min-w-0">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 text-lg uppercase">Thông tin Đăng ký</h3>
                <div id="btn_group_academic">
                    <button type="button" onclick="toggleEdit('academic')" class="px-4 py-2 bg-[#0066FF] text-white font-bold rounded-xl shadow-md shadow-blue-200/50 hover:bg-blue-700 hover:-translate-y-0.5 transition transform text-sm"><i class="fas fa-edit mr-1.5"></i> Sửa thông tin</button>
                </div>
            </div>

            <?php include __DIR__ . '/academic/_view.php'; ?>
            <?php include __DIR__ . '/academic/_form.php'; ?>
            <?php include __DIR__ . '/academic/_status.php'; ?>
        </div>

        <!-- Right: Evidence (6/12) -->
        <div class="lg:col-span-6 space-y-6 min-w-0">
            <?php include __DIR__ . '/academic/_evidence.php'; ?>
        </div>
    </div>
</div>