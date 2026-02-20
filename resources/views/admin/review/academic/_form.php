<!-- Edit Form -->
<div id="form_academic" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-[2rem] p-8 border border-blue-100 shadow-xl shadow-blue-50/50">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#0066FF] shadow-sm">
                <i class="fas fa-school"></i>
            </div>
            <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Chỉnh sửa Kết quả Học tập</h3>
        </div>

        <!-- Priority & School Form -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tỉnh/TP Trường THPT</label>
                <div class="relative">
                    <select name="ma_tinh_lop_12" id="ma_tinh_lop_12_academic" 
                            onchange="loadSchools(this.value, 'ma_truong_lop_12_academic')"
                            class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-[#0066FF]">
                        <option value="">-- Chọn Tỉnh/TP --</option>
                        <?php foreach($provinces as $p): ?>
                            <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_lop_12'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Trường THPT Lớp 12</label>
                <div class="relative">
                    <select name="ma_truong_lop_12" id="ma_truong_lop_12_academic" 
                            data-selected="<?= $user['ma_truong_lop_12'] ?? '' ?>"
                            class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-[#0066FF]">
                        <option value="">-- Chọn Trường --</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Năm Tốt Nghiệp</label>
                <select name="nam_tot_nghiep" class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-[#0066FF]">
                    <option value="">-- Chọn Năm --</option>
                    <?php 
                    $currentYear = date('Y');
                    for ($y = $currentYear; $y >= 1990; $y--): ?>
                        <option value="<?= $y ?>" <?= ($user['nam_tot_nghiep'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Khu vực</label>
                    <select name="khu_vuc_uu_tien" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-[#0066FF]">
                        <option value="KV1" <?= ($user['khu_vuc_uu_tien'] ?? '') == 'KV1' ? 'selected' : '' ?>>KV1</option>
                        <option value="KV2-NT" <?= ($user['khu_vuc_uu_tien'] ?? '') == 'KV2-NT' ? 'selected' : '' ?>>KV2-NT</option>
                        <option value="KV2" <?= ($user['khu_vuc_uu_tien'] ?? '') == 'KV2' ? 'selected' : '' ?>>KV2</option>
                        <option value="KV3" <?= ($user['khu_vuc_uu_tien'] ?? '') == 'KV3' ? 'selected' : '' ?>>KV3</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Đối tượng</label>
                    <select name="doi_tuong_uu_tien" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:bg-white focus:border-[#0066FF]">
                        <option value="00" <?= ($user['doi_tuong_uu_tien'] ?? '') == '00' ? 'selected' : '' ?>>00</option>
                        <option value="01" <?= ($user['doi_tuong_uu_tien'] ?? '') == '01' ? 'selected' : '' ?>>01</option>
                        <option value="02" <?= ($user['doi_tuong_uu_tien'] ?? '') == '02' ? 'selected' : '' ?>>02</option>
                        <option value="03" <?= ($user['doi_tuong_uu_tien'] ?? '') == '03' ? 'selected' : '' ?>>03</option>
                        <option value="04" <?= ($user['doi_tuong_uu_tien'] ?? '') == '04' ? 'selected' : '' ?>>04</option>
                        <option value="05" <?= ($user['doi_tuong_uu_tien'] ?? '') == '05' ? 'selected' : '' ?>>05</option>
                        <option value="06" <?= ($user['doi_tuong_uu_tien'] ?? '') == '06' ? 'selected' : '' ?>>06</option>
                        <option value="07" <?= ($user['doi_tuong_uu_tien'] ?? '') == '07' ? 'selected' : '' ?>>07</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Detailed Scores Form (Dynamic Tabs) -->
        <div x-data="{ editGrade: '10' }" class="space-y-6">
            <div class="flex p-1 bg-slate-100 rounded-xl gap-1">
                <?php foreach([10, 11, 12] as $g): ?>
                    <button type="button" @click="editGrade = '<?= $g ?>'" 
                            :class="editGrade === '<?= $g ?>' ? 'bg-white text-[#0066FF] shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            class="flex-1 py-2 text-xs font-black uppercase tracking-wider rounded-lg transition-all">
                        Lớp <?= $g ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach([10, 11, 12] as $g): ?>
            <div x-show="editGrade === '<?= $g ?>'" class="space-y-4 animate-in fade-in slide-in-from-left-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                    <?php 
                    $gradeRow = $rowsByGrade[$g] ?? [];
                    foreach ($subjects as $code => $name): 
                    ?>
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors">
                        <span class="text-xs font-bold text-slate-600"><?= $name ?></span>
                        <div class="flex gap-2">
                            <input type="number" step="0.01" name="scores[<?= $g ?>][diem_<?= $code ?>_hk1]" value="<?= $gradeRow["diem_{$code}_hk1"] ?? '' ?>" placeholder="HK1" class="w-16 px-2 py-1 text-center bg-white border border-slate-200 rounded-lg text-xs font-bold focus:ring-2 focus:ring-blue-400 outline-none">
                            <input type="number" step="0.01" name="scores[<?= $g ?>][diem_<?= $code ?>_hk2]" value="<?= $gradeRow["diem_{$code}_hk2"] ?? '' ?>" placeholder="HK2" class="w-16 px-2 py-1 text-center bg-white border border-slate-200 rounded-lg text-xs font-bold focus:ring-2 focus:ring-blue-400 outline-none">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="grid grid-cols-3 gap-4 pt-4 border-t border-slate-100">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5">GPA HK1/HK2</label>
                        <div class="flex gap-2">
                            <input type="number" step="0.01" name="scores[<?= $g ?>][diem_tb_hk1]" value="<?= $gradeRow['diem_tb_hk1'] ?? '' ?>" class="w-full px-3 py-2 bg-blue-50/50 border border-blue-100 rounded-lg text-xs font-black text-blue-700 focus:bg-white outline-none" placeholder="TB HK1">
                            <input type="number" step="0.01" name="scores[<?= $g ?>][diem_tb_hk2]" value="<?= $gradeRow['diem_tb_hk2'] ?? '' ?>" class="w-full px-3 py-2 bg-blue-50/50 border border-blue-100 rounded-lg text-xs font-black text-blue-700 focus:bg-white outline-none" placeholder="TB HK2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5">Học lực HK1/HK2</label>
                        <div class="flex gap-2">
                            <select name="scores[<?= $g ?>][hoc_luc_hk1]" class="w-full px-2 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold outline-none">
                                <option value="">- HK1 -</option>
                                <?php foreach(['Gioi', 'Kha', 'Trung binh', 'Yeu'] as $v): ?>
                                    <option value="<?= $v ?>" <?= ($gradeRow['hoc_luc_hk1'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="scores[<?= $g ?>][hoc_luc_hk2]" class="w-full px-2 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold outline-none">
                                <option value="">- HK2 -</option>
                                <?php foreach(['Gioi', 'Kha', 'Trung binh', 'Yeu'] as $v): ?>
                                    <option value="<?= $v ?>" <?= ($gradeRow['hoc_luc_hk2'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5">Hạnh kiểm HK1/HK2</label>
                        <div class="flex gap-2">
                            <select name="scores[<?= $g ?>][hanh_kiem_hk1]" class="w-full px-2 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold outline-none">
                                <option value="">- HK1 -</option>
                                <?php foreach(['Tot', 'Kha', 'Trung binh', 'Yeu'] as $v): ?>
                                    <option value="<?= $v ?>" <?= ($gradeRow['hanh_kiem_hk1'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="scores[<?= $g ?>][hanh_kiem_hk2]" class="w-full px-2 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold outline-none">
                                <option value="">- HK2 -</option>
                                <?php foreach(['Tot', 'Kha', 'Trung binh', 'Yeu'] as $v): ?>
                                    <option value="<?= $v ?>" <?= ($gradeRow['hanh_kiem_hk2'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-end gap-4 pt-8 mt-8 border-t border-slate-100">
            <button type="button" onclick="toggleEdit('academic')" class="px-6 py-3.5 rounded-xl text-slate-500 font-bold text-sm hover:bg-slate-50 hover:text-slate-700 transition-colors uppercase tracking-wider">Hủy bỏ</button>
            <button type="button" onclick="saveSection('academic')" class="px-8 py-3.5 bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-black text-sm uppercase tracking-wider rounded-xl shadow-lg shadow-blue-200 hover:shadow-xl hover:shadow-blue-300 hover:-translate-y-1 active:translate-y-0 transition-all flex items-center">
                <i class="fas fa-save mr-2"></i> Lưu thay đổi
            </button>
        </div>
    </div>

</div>
