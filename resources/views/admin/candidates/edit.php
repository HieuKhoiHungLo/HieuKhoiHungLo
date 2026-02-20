<?php $title = "Chỉnh sửa Hồ sơ Thí sinh"; ?>
<?php ob_start(); ?>

<div class="ml-64 p-8 bg-gray-50 min-h-screen" x-data="{ activeTab: '<?= $_GET['tab'] ?? 'personal' ?>' }">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <a href="<?= url('/admin/review?cccd=' . $candidate['so_cccd']) ?>" class="text-gray-500 hover:text-[#0066FF] font-bold flex items-center mb-2">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại trang duyệt
                </a>
                <h1 class="text-3xl font-black text-slate-800">Chỉnh sửa Hồ sơ</h1>
                <p class="text-gray-500">Thí sinh: <span class="font-bold text-slate-700"><?= htmlspecialchars($candidate['ho_va_ten']) ?></span> - CCCD: <span class="font-mono font-bold text-[#0066FF]"><?= $candidate['so_cccd'] ?></span></p>
            </div>
            
             <?php if (isset($_GET['msg']) && $_GET['msg'] == 'update_success'): ?>
                <div class="bg-emerald-100 text-emerald-700 px-4 py-2 rounded-lg font-bold shadow-sm flex items-center animate-pulse">
                    <i class="fas fa-check-circle mr-2"></i> Lưu thành công!
                </div>
            <?php endif; ?>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-xl overflow-hidden" id="editForm">
            <?= csrf_field() ?>
            <input type="hidden" name="cccd" value="<?= $candidate['so_cccd'] ?>">
            <input type="hidden" name="active_tab" x-model="activeTab">

            <!-- ... (Tabs) ... -->

            <!-- Actions inside form -->
            <div class="p-6 bg-gray-50 border-t flex justify-between items-center sticky bottom-0 z-10">
                <div class="text-sm text-gray-500 italic">
                    <i class="fas fa-info-circle mr-1"></i> Kiểm tra kỹ thông tin trước khi lưu.
                </div>
                <div class="flex space-x-4">
                     <button type="button" onclick="location.href='<?= url('/admin/review?cccd=' . $candidate['so_cccd']) ?>'" class="px-6 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 font-bold hover:bg-gray-100 transition">
                        <i class="fas fa-times mr-2"></i> Hủy & Quay lại
                    </button>
                    <button type="button" onclick="submitForm()" class="px-8 py-2 bg-[#0066FF] text-white rounded-lg font-bold shadow-lg hover:bg-blue-700 transition transform hover:-translate-y-0.5">
                        <i class="fas fa-save mr-2"></i> Lưu Thay Đổi
                    </button>
                </div>
            </div>
        </form>

<script>
function submitForm() {
    const form = document.getElementById('editForm');
    
    // Check validity
    if (!form.checkValidity()) {
        const invalid = form.querySelector(':invalid');
        if (invalid) {
            // Find which tab this input belongs to
            const tabPane = invalid.closest('[x-show]');
            if (tabPane) {
                // Map tab pane to tab name
                // personal -> 'personal'
                // But x-show has condition "activeTab === 'name'"
                // Let's parse it or assign IDs to tabs
                const xShow = tabPane.getAttribute('x-show');
                const match = xShow.match(/'([^']+)'/);
                if (match && match[1]) {
                     // Alpine data is localized to the div wrapper.
                     // We can't access x-data scope easily from outside unless we use __x.
                     // Simpler: Dispatch event or set property if possible.
                     // Or just alert user.
                     
                     // Try to trigger Alpine update
                     const wrapper = invalid.closest('[x-data]');
                     if (wrapper) {
                         if (wrapper._x_dataStack) {
                             wrapper._x_dataStack[0].activeTab = match[1];
                         } else if (wrapper.__x) {
                            wrapper.__x.$data.activeTab = match[1];
                         }
                     }
                }
            }
            alert('Vui lòng kiểm tra lại thông tin nhập liệu (trường bắt buộc hoặc định dạng sai).');
            invalid.focus();
        }
        return;
    }
    form.submit();
}
</script>

            <!-- Tabs Navigation -->
            <div class="flex border-b border-gray-100 bg-gray-50/50">
                <button type="button" @click="activeTab = 'personal'" 
                    class="px-6 py-4 text-sm font-bold uppercase tracking-wider transition relative"
                    :class="activeTab === 'personal' ? 'text-[#0066FF] bg-white border-t-2 border-[#0066FF]' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'">
                    <i class="fas fa-user mr-2"></i> Cá nhân
                </button>
                <button type="button" @click="activeTab = 'academic'" 
                    class="px-6 py-4 text-sm font-bold uppercase tracking-wider transition relative"
                    :class="activeTab === 'academic' ? 'text-[#0066FF] bg-white border-t-2 border-[#0066FF]' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'">
                    <i class="fas fa-graduation-cap mr-2"></i> Học bạ
                </button>
                <button type="button" @click="activeTab = 'thpt'" 
                    class="px-6 py-4 text-sm font-bold uppercase tracking-wider transition relative"
                    :class="activeTab === 'thpt' ? 'text-[#0066FF] bg-white border-t-2 border-[#0066FF]' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'">
                    <i class="fas fa-poll-h mr-2"></i> Điểm THPT
                </button>
                 <button type="button" @click="activeTab = 'certs'" 
                    class="px-6 py-4 text-sm font-bold uppercase tracking-wider transition relative"
                    :class="activeTab === 'certs' ? 'text-[#0066FF] bg-white border-t-2 border-[#0066FF]' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'">
                    <i class="fas fa-certificate mr-2"></i> Chứng chỉ
                </button>
            </div>

            <div class="p-8">
                <!-- TAB 1: PERSONAL -->
                <!-- TAB 1: PERSONAL -->
                <div x-show="activeTab === 'personal'" class="space-y-6">
                     <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                        <!-- Left Column: Inputs -->
                        <div class="lg:col-span-2 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-user mr-1"></i> Họ và tên</label>
                                    <input type="text" name="ho_va_ten" value="<?= htmlspecialchars($candidate['ho_va_ten']) ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF] font-bold" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-id-card-alt mr-1"></i> Số CCCD</label>
                                    <input type="text" value="<?= htmlspecialchars($candidate['so_cccd']) ?>" class="w-full px-4 py-2 border rounded-lg bg-gray-50 cursor-not-allowed text-gray-400 font-mono" readonly>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-calendar-alt mr-1"></i> Ngày sinh</label>
                                    <input type="date" name="ngay_sinh" value="<?= $candidate['ngay_sinh'] ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-venus-mars mr-1"></i> Giới tính</label>
                                    <select name="gioi_tinh" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]">
                                        <option value="Nam" <?= $candidate['gioi_tinh'] == 'Nam' ? 'selected' : '' ?>>Nam</option>
                                        <option value="Nữ" <?= $candidate['gioi_tinh'] == 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-flag mr-1"></i> Dân tộc</label>
                                    <input type="text" name="dan_toc" value="<?= htmlspecialchars($candidate['dan_toc']) ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-phone mr-1"></i> Điện thoại</label>
                                    <input type="text" name="dien_thoai" value="<?= htmlspecialchars($candidate['dien_thoai']) ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF] font-mono" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-envelope mr-1"></i> Email</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($candidate['email']) ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]" required>
                                </div>
                            </div>
                            
                            <hr class="border-gray-100 my-4">
                            
                            <!-- Address -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-map-marker-alt mr-1"></i> Hộ khẩu (Tỉnh/TP)</label>
                                    <select name="ma_tinh_ho_khau" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]" required>
                                        <option value="">-- Chọn Tỉnh --</option>
                                        <?php foreach ($provinces as $p): ?>
                                            <option value="<?= $p['ma_tinh'] ?>" <?= ($candidate['ma_tinh_ho_khau'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-map mr-1"></i> Tỉnh Liên Lạc</label>
                                    <select name="ma_tinh_thuong_tru" id="province_contact" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]" required>
                                        <option value="">-- Chọn Tỉnh/TP --</option>
                                        <?php foreach ($provinces as $p): ?>
                                            <option value="<?= htmlspecialchars($p['ma_tinh']) ?>" 
                                                <?= ($candidate['ma_tinh_thuong_tru'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['ten_tinh']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-building mr-1"></i> Xã / Phường</label>
                                    <select name="ma_xa_thuong_tru" id="ward" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]" required>
                                        <!-- Loaded via JS -->
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-home mr-1"></i> Địa chỉ chi tiết</label>
                                    <input type="text" name="dia_chi_chi_tiet" value="<?= htmlspecialchars($candidate['dia_chi_chi_tiet'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]">
                                </div>
                            </div>

                             <hr class="border-gray-100 my-4">

                             <!-- High School & Priority -->
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-school mr-1"></i> Tỉnh Trường THPT</label>
                                    <select name="ma_tinh_lop_12" id="province_school" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]" required>
                                        <option value="">-- Chọn Tỉnh --</option>
                                        <?php foreach ($provinces as $p): ?>
                                            <option value="<?= $p['ma_tinh'] ?>" <?= ($candidate['ma_tinh_lop_12'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><i class="fas fa-graduation-cap mr-1"></i> Trường THPT</label>
                                    <select name="ma_truong_lop_12" id="school" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]" required>
                                        <!-- Loaded via JS -->
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Khu vực Ưu tiên</label>
                                    <select name="khu_vuc_uu_tien" id="kv_uu_tien" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]">
                                        <?php foreach ($priorityAreas as $kv => $score): ?>
                                            <option value="<?= $kv ?>" <?= ($candidate['khu_vuc_uu_tien'] ?? '') == $kv ? 'selected' : '' ?>><?= $kv ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Đối tượng Ưu tiên</label>
                                    <select name="doi_tuong_uu_tien" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]">
                                        <option value="" <?= empty($candidate['doi_tuong_uu_tien']) ? 'selected' : '' ?>>-- Không --</option>
                                        <?php foreach ($priorityObjects as $dt => $score): ?>
                                            <option value="<?= $dt ?>" <?= ($candidate['doi_tuong_uu_tien'] ?? '') == $dt ? 'selected' : '' ?>>Đối tượng <?= $dt ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                             </div>

                        </div>

                        <!-- Right Column: Avatar Upload -->
                        <div class="flex flex-col items-center justify-start pt-2">
                             <label class="block text-xs font-bold text-gray-500 uppercase mb-4">Ảnh thẻ (3x4)</label>
                             <div class="group relative w-40 h-52 border-2 border-dashed border-gray-300 rounded-xl flex flex-col justify-center items-center bg-gray-50 overflow-hidden transition-all hover:border-[#0066FF]">
                                <img id="preview_avatar" loading="lazy" src="<?= $candidate['anh_dai_dien'] ? url($candidate['anh_dai_dien']) : '' ?>" class="<?= !empty($candidate['anh_dai_dien']) ? 'block' : 'hidden' ?> w-full h-full object-cover">
                                <div class="<?= !empty($candidate['anh_dai_dien']) ? 'hidden' : 'flex' ?> flex-col items-center text-gray-400 text-center p-4">
                                    <i class="fas fa-camera text-3xl mb-2"></i>
                                    <span class="text-xs">Chưa có ảnh</span>
                                </div>
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                    <span class="text-white text-xs font-bold bg-[#0066FF]/80 px-3 py-1 rounded-full">Đổi ảnh</span>
                                </div>
                                <input type="file" name="avatar" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(this, 'preview_avatar')">
                             </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: ACADEMIC -->
                <div x-show="activeTab === 'academic'" class="space-y-6">
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg flex items-center mb-4">
                         <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                         <span class="text-sm text-blue-800">Nhập đầy đủ điểm TB các môn học.</span>
                    </div>

                    <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm">
                        <table class="w-full text-sm text-center border-collapse">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 uppercase font-bold text-xs">
                                    <th class="px-2 py-3 border whitespace-nowrap sticky left-0 bg-gray-100 z-20 shadow-sm" style="min-width: 150px;">Môn học</th>
                                    <th class="px-2 py-3 border bg-red-50 text-hvu-red" colspan="2">Lớp 10</th>
                                    <th class="px-2 py-3 border bg-blue-50 text-blue-700" colspan="2">Lớp 11</th>
                                    <th class="px-2 py-3 border bg-yellow-50 text-yellow-700" colspan="2">Lớp 12</th>
                                </tr>
                                <tr class="bg-gray-50 text-gray-600 font-semibold text-xs border-b">
                                    <th class="px-2 py-2 border sticky left-0 bg-gray-50 z-20"></th>
                                    <th class="px-1 py-2 border w-20">HK 1</th>
                                    <th class="px-1 py-2 border w-20">HK 2</th>
                                    <th class="px-1 py-2 border w-20">HK 1</th>
                                    <th class="px-1 py-2 border w-20">HK 2</th>
                                    <th class="px-1 py-2 border w-20">HK 1</th>
                                    <th class="px-1 py-2 border w-20">HK 2</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <?php 
                                // Organize records for easy access
                                $recMap = [];
                                foreach($academicRecords as $r) {
                                    $recMap[$r['lop']] = $r;
                                }
                                
                                $subjects = [
                                    'toan' => 'Toán', 'van' => 'Ngữ văn', 'ngoai_ngu' => 'Ngoại ngữ',
                                    'ly' => 'Vật lí', 'hoa' => 'Hóa học', 'sinh' => 'Sinh học',
                                    'su' => 'Lịch sử', 'dia' => 'Địa lí', 'gdcd' => 'GDKT & PL',
                                    'cong_nghe' => 'Công nghệ', 'tin_hoc' => 'Tin học'
                                ];
                                ?>
                                <?php foreach ($subjects as $key => $name): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-3 py-2 font-bold text-gray-800 text-left border-r sticky left-0 bg-white z-10 whitespace-nowrap"><?= $name ?></td>
                                    <?php foreach ([10, 11, 12] as $g): ?>
                                        <td class="p-1 border text-center">
                                            <input type="number" step="0.1" min="0" max="10" class="w-full text-center border-none focus:ring-0 p-1 rounded hover:bg-gray-50" 
                                                name="grade_<?= $g ?>_<?= $key ?>_hk1" 
                                                value="<?= $recMap[$g]["diem_{$key}_hk1"] ?? '' ?>">
                                        </td>
                                        <td class="p-1 border text-center">
                                            <input type="number" step="0.1" min="0" max="10" class="w-full text-center border-none focus:ring-0 p-1 rounded hover:bg-gray-50" 
                                                name="grade_<?= $g ?>_<?= $key ?>_hk2" 
                                                value="<?= $recMap[$g]["diem_{$key}_hk2"] ?? '' ?>">
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>

                                <!-- Diem TB -->
                                <tr class="bg-blue-50/50 border-t-2 border-blue-100 font-bold">
                                    <td class="px-3 py-3 text-blue-800 text-left border-r sticky left-0 bg-blue-50/50 z-10">Điểm TB</td>
                                    <?php foreach ([10, 11, 12] as $g): ?>
                                        <td class="p-1 border text-center">
                                            <input type="number" step="0.1" min="0" max="10" class="w-full text-center border-none focus:ring-0 p-1 bg-transparent font-bold text-blue-700" 
                                                name="grade_<?= $g ?>_diem_tb_hk1" 
                                                value="<?= $recMap[$g]["diem_tb_hk1"] ?? '' ?>">
                                        </td>
                                        <td class="p-1 border text-center">
                                            <input type="number" step="0.1" min="0" max="10" class="w-full text-center border-none focus:ring-0 p-1 bg-transparent font-bold text-blue-700" 
                                                name="grade_<?= $g ?>_diem_tb_hk2" 
                                                value="<?= $recMap[$g]["diem_tb_hk2"] ?? '' ?>">
                                        </td>
                                    <?php endforeach; ?>
                                </tr>

                                <!-- Hoc Luc -->
                                <tr class="bg-gray-50 border-t border-gray-200">
                                    <td class="px-3 py-2 text-gray-700 text-left border-r sticky left-0 bg-gray-50 z-10 font-medium">Học Lực</td>
                                    <?php foreach ([10, 11, 12] as $g): ?>
                                        <td class="p-1 border text-center">
                                            <select class="w-full text-center border-none focus:ring-0 p-1 bg-transparent text-xs font-bold" name="grade_<?= $g ?>_hoc_luc_hk1">
                                                <option value="">--</option>
                                                <?php foreach(['Giỏi', 'Kha' => 'Khá', 'TrungBinh' => 'TB', 'Yeu' => 'Yếu'] as $v => $l): $val = is_numeric($v) ? $l : $v; ?>
                                                    <option value="<?= $val ?>" <?= ($recMap[$g]["hoc_luc_hk1"] ?? '') == $val ? 'selected' : '' ?>><?= $l ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="p-1 border text-center">
                                            <select class="w-full text-center border-none focus:ring-0 p-1 bg-transparent text-xs font-bold" name="grade_<?= $g ?>_hoc_luc_hk2">
                                                <option value="">--</option>
                                                <?php foreach(['Giỏi', 'Kha' => 'Khá', 'TrungBinh' => 'TB', 'Yeu' => 'Yếu'] as $v => $l): $val = is_numeric($v) ? $l : $v; ?>
                                                    <option value="<?= $val ?>" <?= ($recMap[$g]["hoc_luc_hk2"] ?? '') == $val ? 'selected' : '' ?>><?= $l ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>

                                <!-- Hanh Kiem -->
                                <tr class="bg-white border-t border-gray-200">
                                    <td class="px-3 py-2 text-gray-700 text-left border-r sticky left-0 bg-white z-10 font-medium">Hạnh Kiểm</td>
                                    <?php foreach ([10, 11, 12] as $g): ?>
                                        <td class="p-1 border text-center">
                                            <select class="w-full text-center border-none focus:ring-0 p-1 bg-transparent text-xs font-bold" name="grade_<?= $g ?>_hanh_kiem_hk1">
                                                <option value="">--</option>
                                                <?php foreach(['Tot' => 'Tốt', 'Kha' => 'Khá', 'TrungBinh' => 'TB', 'Yeu' => 'Yếu'] as $v => $l): $val = is_numeric($v) ? $l : $v; ?>
                                                    <option value="<?= $val ?>" <?= ($recMap[$g]["hanh_kiem_hk1"] ?? '') == $val ? 'selected' : '' ?>><?= $l ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="p-1 border text-center">
                                             <select class="w-full text-center border-none focus:ring-0 p-1 bg-transparent text-xs font-bold" name="grade_<?= $g ?>_hanh_kiem_hk2">
                                                <option value="">--</option>
                                                <?php foreach(['Tot' => 'Tốt', 'Kha' => 'Khá', 'TrungBinh' => 'TB', 'Yeu' => 'Yếu'] as $v => $l): $val = is_numeric($v) ? $l : $v; ?>
                                                    <option value="<?= $val ?>" <?= ($recMap[$g]["hanh_kiem_hk2"] ?? '') == $val ? 'selected' : '' ?>><?= $l ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: THPT -->
                <!-- TAB 3: THPT -->
                <div x-show="activeTab === 'thpt'" class="space-y-6">
                    <div class="bg-orange-50 border border-orange-100 p-6 rounded-3xl">
                        <div class="flex items-center mb-6">
                             <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 mr-3">
                                <i class="fas fa-file-invoice-dollar"></i>
                             </div>
                             <div>
                                 <h3 class="font-bold text-lg text-orange-800">Điểm thi Tốt nghiệp THPT (<?= $diemThi['nam_thi'] ?? date('Y') ?>)</h3>
                                 <p class="text-xs text-orange-600">Nhập điểm các môn thi thí sinh đã tham dự.</p>
                             </div>
                        </div>

                         <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <?php 
                            $thptFields = [
                                'toan' => 'Toán', 'van' => 'Văn', 'ly' => 'Vật Lí', 'hoa' => 'Hóa Học', 'sinh' => 'Sinh Học',
                                'su' => 'Lịch Sử', 'dia' => 'Địa Lý', 'gdcd' => 'GDCD', 'tieng_anh' => 'Tiếng Anh',
                                'tieng_trung' => 'Tiếng Trung', 'tieng_nhat' => 'Tiếng Nhật', 'tieng_han' => 'Tiếng Hàn'
                            ];
                            ?>
                            <?php foreach($thptFields as $code => $label): ?>
                            <div class="bg-white p-3 rounded-xl border border-orange-100 shadow-sm">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?= $label ?></label>
                                <input type="number" step="0.01" name="<?= $code ?>" value="<?= $diemThi[$code] ?? '' ?>" class="w-full text-center font-black text-lg text-orange-700 bg-transparent border-0 border-b-2 border-orange-100 focus:border-orange-500 focus:ring-0 px-0 py-1 transition-colors" placeholder="-">
                            </div>
                            <?php endforeach; ?>
                         </div>
                    </div>
                </div>

                <!-- TAB 4: CERTIFICATES -->
                <div x-show="activeTab === 'certs'" class="space-y-6">
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg flex items-center mb-4">
                         <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                         <span class="text-sm text-blue-800">Chọn "Có chứng chỉ" để thêm mới. Nhấn "Lưu" để cập nhật.</span>
                    </div>

                    <!-- Option Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <label class="group relative flex items-center p-4 bg-white border border-gray-200 rounded-xl cursor-pointer transition-all hover:border-gray-300 has-[:checked]:border-gray-400 has-[:checked]:bg-gray-50">
                            <input type="radio" name="has_cert" value="0" <?= empty($certs) ? 'checked' : '' ?> onchange="toggleCertFields(false)" class="w-5 h-5 text-gray-600 border-gray-300 focus:ring-gray-500">
                            <div class="ml-3">
                                <span class="block text-base font-bold text-gray-900">Không có chứng chỉ</span>
                            </div>
                        </label>

                        <label class="group relative flex items-center p-4 bg-white border border-gray-200 rounded-xl cursor-pointer transition-all hover:border-[#0066FF] has-[:checked]:border-[#0066FF] has-[:checked]:bg-blue-50">
                            <input type="radio" name="has_cert" value="1" <?= !empty($certs) ? 'checked' : '' ?> onchange="toggleCertFields(true)" class="w-5 h-5 text-[#0066FF] border-gray-300 focus:ring-[#0066FF]">
                            <div class="ml-3">
                                <span class="block text-base font-bold text-gray-900 group-hover:text-[#0066FF] transition-colors">Có chứng chỉ quốc tế</span>
                            </div>
                        </label>
                    </div>

                    <!-- Dynamic Certifications List -->
                    <div id="cert_section" class="<?= !empty($certs) ? '' : 'hidden' ?> space-y-4 pt-4 border-t border-gray-100">
                        <div id="cert_list" class="space-y-4">
                            <?php if (!empty($certs)): ?>
                                <?php foreach ($certs as $index => $cert): ?>
                                    <div class="cert-item bg-gray-50 border border-gray-200 rounded-xl p-4 relative group/item">
                                        <button type="button" onclick="removeCert(this)" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition-colors">
                                            <i class="fas fa-times-circle text-lg"></i>
                                        </button>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Loại chứng chỉ</label>
                                                <select name="certs[<?= $index ?>][type]" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]">
                                                    <option value="">-- Chọn --</option>
                                                    <optgroup label="Tiếng Anh">
                                                        <option value="IELTS" <?= $cert['loai_chung_chi'] == 'IELTS' ? 'selected' : '' ?>>IELTS</option>
                                                        <option value="TOEFL iBT" <?= $cert['loai_chung_chi'] == 'TOEFL iBT' ? 'selected' : '' ?>>TOEFL iBT</option>
                                                        <option value="TOEIC" <?= $cert['loai_chung_chi'] == 'TOEIC' ? 'selected' : '' ?>>TOEIC</option>
                                                        <option value="VSTEP" <?= $cert['loai_chung_chi'] == 'VSTEP' ? 'selected' : '' ?>>VSTEP</option>
                                                    </optgroup>
                                                    <optgroup label="Ngoại ngữ khác">
                                                        <option value="HSK" <?= $cert['loai_chung_chi'] == 'HSK' ? 'selected' : '' ?>>HSK (Tiếng Trung)</option>
                                                        <option value="JLPT" <?= $cert['loai_chung_chi'] == 'JLPT' ? 'selected' : '' ?>>JLPT (Tiếng Nhật)</option>
                                                    </optgroup>
                                                    <optgroup label="Tin học">
                                                        <option value="IC3" <?= $cert['loai_chung_chi'] == 'IC3' ? 'selected' : '' ?>>IC3</option>
                                                        <option value="MOS" <?= $cert['loai_chung_chi'] == 'MOS' ? 'selected' : '' ?>>MOS</option>
                                                    </optgroup>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Điểm / Kết quả</label>
                                                <input type="text" name="certs[<?= $index ?>][score]" value="<?= htmlspecialchars($cert['diem_chung_chi'] ?? '') ?>" placeholder="VD: 6.5, 450, Đạt..." class="w-full px-3 py-2 border rounded-lg font-bold text-gray-700">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Minh chứng (Ảnh)</label>
                                                <div class="flex items-center space-x-4">
                                                    <label class="flex-grow cursor-pointer group/upload">
                                                        <div class="h-12 border border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-white hover:bg-gray-50 transition-all">
                                                            <i class="fas fa-upload mr-2 text-gray-400"></i>
                                                            <span class="text-xs font-bold text-gray-500 file-label">Chọn ảnh mới</span>
                                                            <input type="file" name="cert_files[<?= $index ?>]" accept="image/*" class="hidden" onchange="previewCert(this)">
                                                        </div>
                                                    </label>
                                                    <input type="hidden" name="certs[<?= $index ?>][existing_file]" value="<?= $cert['file_minh_chung_cc'] ?>">
                                                    <?php if (!empty($cert['file_minh_chung_cc'])): ?>
                                                        <a href="<?= url($cert['file_minh_chung_cc']) ?>" target="_blank" class="w-12 h-12 rounded border border-gray-200 overflow-hidden shadow-sm block flex-shrink-0">
                                                            <img loading="lazy" src="<?= url($cert['file_minh_chung_cc']) ?>" class="w-full h-full object-cover">
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <button type="button" onclick="addCertRow()" class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-gray-400 font-bold hover:bg-gray-50 hover:text-gray-600 transition-all">
                            <i class="fas fa-plus-circle mr-2"></i> Thêm chứng chỉ khác
                        </button>
                    </div>
                </div>

            </div>
            
            <!-- Actions -->
            <div class="p-6 bg-gray-50 border-t flex justify-between items-center sticky bottom-0 z-10">
                <div class="text-sm text-gray-500 italic">
                    <i class="fas fa-info-circle mr-1"></i> Kiểm tra kỹ thông tin trước khi lưu.
                </div>
                <div class="flex space-x-4">
                     <button type="button" onclick="location.href='<?= url('/admin/review?cccd=' . $candidate['so_cccd']) ?>'" class="px-6 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 font-bold hover:bg-gray-100 transition">
                        <i class="fas fa-times mr-2"></i> Hủy & Quay lại
                    </button>
                    <button type="submit" class="px-8 py-2 bg-[#0066FF] text-white rounded-lg font-bold shadow-lg hover:bg-blue-700 transition transform hover:-translate-y-0.5">
                        <i class="fas fa-save mr-2"></i> Lưu Thay Đổi
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Template for Dynamic Row -->
<template id="cert_template">
    <div class="cert-item bg-gray-50 border border-gray-200 rounded-xl p-4 relative group/item animate-fadeIn">
        <button type="button" onclick="removeCert(this)" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition-colors">
            <i class="fas fa-times-circle text-lg"></i>
        </button>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Loại chứng chỉ</label>
                <select name="certs[INDEX][type]" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-[#0066FF]" required>
                    <option value="">-- Chọn --</option>
                    <optgroup label="Tiếng Anh">
                        <option value="IELTS">IELTS</option>
                        <option value="TOEFL iBT">TOEFL iBT</option>
                        <option value="TOEIC">TOEIC</option>
                        <option value="VSTEP">VSTEP</option>
                    </optgroup>
                    <optgroup label="Ngoại ngữ khác">
                        <option value="HSK">HSK (Tiếng Trung)</option>
                        <option value="JLPT">JLPT (Tiếng Nhật)</option>
                    </optgroup>
                    <optgroup label="Tin học">
                        <option value="IC3">IC3</option>
                        <option value="MOS">MOS</option>
                    </optgroup>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Điểm / Kết quả</label>
                <input type="text" name="certs[INDEX][score]" placeholder="VD: 6.5, 450, Đạt..." class="w-full px-3 py-2 border rounded-lg font-bold text-gray-700" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Minh chứng (Ảnh)</label>
                <label class="block cursor-pointer group/upload">
                    <div class="h-12 border border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-white hover:bg-gray-50 transition-all">
                        <i class="fas fa-upload mr-2 text-gray-400"></i>
                        <span class="text-xs font-bold text-gray-500 file-label">Chọn ảnh minh chứng</span>
                        <input type="file" name="cert_files[INDEX]" accept="image/*" class="hidden" onchange="previewCert(this)" required>
                    </div>
                </label>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>

<script>
let certCount = <?= !empty($certs) ? count($certs) : 0 ?>;

function toggleCertFields(show) {
    const section = document.getElementById('cert_section');
    const inputs = section.querySelectorAll('input, select');
    if (show) {
        section.classList.remove('hidden');
        inputs.forEach(el => el.disabled = false);
        if (certCount === 0) addCertRow();
    } else {
        section.classList.add('hidden');
        inputs.forEach(el => el.disabled = true);
    }
}

function addCertRow() {
    const list = document.getElementById('cert_list');
    const template = document.getElementById('cert_template').innerHTML;
    const newRow = template.replace(/INDEX/g, certCount);
    
    const div = document.createElement('div');
    div.innerHTML = newRow;
    list.appendChild(div.firstElementChild);
    
    certCount++;
}

function removeCert(btn) {
    const item = btn.closest('.cert-item');
    item.classList.add('opacity-0', 'scale-95');
    setTimeout(() => {
        item.remove();
        if (document.querySelectorAll('.cert-item').length === 0) {
            document.querySelector('input[name="has_cert"][value="0"]').checked = true;
            toggleCertFields(false);
        }
    }, 200);
}

function previewCert(input) {
    const label = input.closest('label').querySelector('.file-label');
    if (input.files && input.files[0]) {
        label.textContent = 'Đã chọn: ' + input.files[0].name;
        label.classList.remove('text-gray-500');
        label.classList.add('text-[#0066FF]');
    }
}

// Dynamic Dropdowns Logic
document.addEventListener('DOMContentLoaded', function() {
    // 1. School Dropdown
    const provinceSchool = document.getElementById('province_school');
    const schoolSelect = document.getElementById('school');
    const kvSelect = document.getElementById('kv_uu_tien');
    const currentSchool = "<?= $candidate['ma_truong_lop_12'] ?? '' ?>";

    if(provinceSchool) {
        provinceSchool.addEventListener('change', function() {
            loadSchools(this.value);
        });
        // Initial load
        if(provinceSchool.value) loadSchools(provinceSchool.value, currentSchool);
    }

    if(schoolSelect) {
        schoolSelect.addEventListener('change', function() {
            // Auto-update Priority Area based on School
            const selected = this.options[this.selectedIndex];
            if(selected && selected.dataset.kv && kvSelect) {
                 kvSelect.value = selected.dataset.kv;
            }
        });
    }

    function loadSchools(provinceId, selectedId = null) {
        if (!provinceId) {
             schoolSelect.innerHTML = '<option value="">-- Chọn Tỉnh trước --</option>';
             return;
        }
        fetch(`<?= url('/api/schools') ?>?province_id=${provinceId}`)
            .then(res => res.json())
            .then(data => {
                let html = '<option value="">-- Chọn Trường --</option>';
                if(data.length === 0) html = '<option value="">-- Không có trường --</option>';
                
                data.forEach(item => {
                    const isSelected = (selectedId == item.ma_truong) ? 'selected' : '';
                    html += `<option value="${item.ma_truong}" data-kv="${item.khu_vuc}" ${isSelected}>${item.ten_truong}</option>`;
                });
                schoolSelect.innerHTML = html;
            });
    }

    // 2. Contact Ward Dropdown
    const provinceContact = document.getElementById('province_contact');
    const wardSelect = document.getElementById('ward');
    const currentWard = "<?= $candidate['ma_xa_thuong_tru'] ?? '' ?>";

    if(provinceContact) {
         provinceContact.addEventListener('change', function() {
            loadWards(this.value);
         });
         // Initial load
         if(provinceContact.value) loadWards(provinceContact.value, currentWard);
    }

    function loadWards(provinceId, selectedId = null) {
        if (!provinceId) {
            wardSelect.innerHTML = '<option value="">-- Chọn Tỉnh trước --</option>';
            return;
        }
         fetch(`<?= url('/api/wards') ?>?province_id=${provinceId}`)
            .then(res => res.json())
            .then(data => {
                let html = '<option value="">-- Chọn Xã/Phường --</option>';
                if(data.length === 0) html = '<option value="">-- Không có dữ liệu --</option>';
                
                data.forEach(item => {
                    const isSelected = (selectedId == item.ma_xa) ? 'selected' : '';
                    html += `<option value="${item.ma_xa}" ${isSelected}>${item.ten_xa}</option>`;
                });
                wardSelect.innerHTML = html;
            });
    }
});

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            if(preview.nextElementSibling) preview.nextElementSibling.classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php'; 
?>
