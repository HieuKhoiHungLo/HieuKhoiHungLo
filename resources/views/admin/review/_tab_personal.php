<!-- TAB 1: PERSONAL -->
<div id="tab_personal" class="tab-content transition-opacity duration-300">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        <!-- Left: Info (2/3) -->
        <div class="md:col-span-8 space-y-4">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 text-lg uppercase">Thông tin Đăng ký</h3>
                <div id="btn_group_personal">
                    <button type="button" onclick="toggleEdit('personal')" class="px-4 py-2 bg-[#0066FF] text-white font-bold rounded-xl shadow-md shadow-blue-200/50 hover:bg-blue-700 hover:-translate-y-0.5 transition transform text-sm"><i class="fas fa-edit mr-1.5"></i> Sửa thông tin</button>
                </div>
            </div>

            <?php include __DIR__ . '/personal/_view.php'; ?>
            <?php include __DIR__ . '/personal/_form.php'; ?>
            <?php include __DIR__ . '/personal/_status.php'; ?>
        </div>

        <!-- Right: Avatar (1/3) -->
        <div class="md:col-span-4 w-full">
            <?php include __DIR__ . '/personal/_evidence.php'; ?>
        </div>
    </div>
</div>
