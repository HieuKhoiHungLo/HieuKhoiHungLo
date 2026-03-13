<?php
// PHP Mapping functions/logic
$mapDisplay = function($v) {
    return [
        'Gioi' => 'Giỏi', 'Kha' => 'Khá', 'TrungBinh' => 'T.Bình', 'Yeu' => 'Yếu',
        'Tot' => 'Tốt'
    ][$v] ?? $v;
};
?>
<div id="form_academic" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-[2rem] p-8 border border-blue-100 shadow-xl shadow-blue-50/50 overflow-visible">
 
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-10">
            <!-- Row 1: Tỉnh/TP (1/3) và Trường (2/3) -->
            <div class="md:col-span-4">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tỉnh/TP</label>
                <div class="relative">
                    <i class="fas fa-map-marker-alt absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <select name="ma_tinh_lop_12"
                        onchange="window.dispatchEvent(new CustomEvent('province-school-change', {detail: this.value}))"
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none">
                        <option value="">-- Chọn Tỉnh/TP --</option>
                        <?php foreach ($provinces as $p): ?>
                            <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_lop_12'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                </div>
            </div>

            <!-- Trường THPT -->
            <div class="md:col-span-8">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Trường THPT</label>
                <div x-data="schoolSearch('<?= $user['ma_tinh_lop_12'] ?? '' ?>', '<?= $user['ma_truong_lop_12'] ?? '' ?>')"
                    @province-school-change.window="handleProvinceChange($event.detail)"
                    class="relative">
                    <input type="hidden" name="ma_truong_lop_12" :value="selectedCode">
                    <i class="fas fa-university absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" x-model="search" @focus="open = true" @click.away="open = false" placeholder="-- Nhập tên trường để tìm --"
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                    <div x-show="open" class="absolute z-[100] w-full mt-2 bg-white border border-blue-100 rounded-xl shadow-2xl max-h-60 overflow-y-auto custom-scrollbar" style="top: 100%; left: 0;">
                        <template x-for="school in filteredSchools" :key="school.ma_truong">
                            <div @click="select(school)" class="px-4 py-3 hover:bg-blue-50 cursor-pointer text-sm font-medium text-slate-700 border-b border-slate-50 last:border-0 transition-colors">
                                <div class="flex flex-col">
                                    <span x-text="school.ten_truong" class="font-bold"></span>
                                    <span x-text="'Mã: ' + school.ma_truong" class="text-[10px] text-slate-400"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Row 2: Năm tốt nghiệp, KV ưu tiên, ĐT ưu tiên -->
            <div x-data="{ 
                kv: '<?= $user['khu_vuc_uu_tien'] ?? '' ?>', 
                isCustomKv: <?= ($user['is_custom_kv'] ?? 0) ? 'true' : 'false' ?>,
                dt: '<?= $user['doi_tuong_uu_tien'] ?? '' ?>',
                isCustomDt: <?= ($user['is_custom_dt'] ?? 0) ? 'true' : 'false' ?>
            }"
                @school-selected.window="if(!isCustomKv) kv = $event.detail.ma_kv"
                class="col-span-1 md:col-span-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Năm tốt nghiệp</label>
                    <div class="relative">
                        <i class="fas fa-calendar-check absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <select name="nam_tot_nghiep" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none">
                            <?php
                            $currentYear = date('Y');
                            for ($y = $currentYear; $y >= $currentYear - 10; $y--): ?>
                                <option value="<?= $y ?>" <?= ($user['nam_tot_nghiep'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">KV ưu tiên</label>
                        <label class="flex items-center gap-1.5 cursor-pointer group">
                            <input type="checkbox" name="is_custom_kv" value="1" x-model="isCustomKv" class="w-3.5 h-3.5 rounded border-slate-300 text-orange-500 focus:ring-orange-200">
                            <span class="text-[9px] font-black text-slate-400 group-hover:text-orange-500 transition-colors uppercase flex items-center gap-1">
                                <span x-show="isCustomKv" class="text-orange-600">Thí sinh tự chọn</span>
                                <span x-show="!isCustomKv">Tùy chỉnh</span>
                            </span>
                        </label>
                    </div>
                    <div class="relative">
                        <i class="fas fa-star absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <select name="kv_uu_tien" x-model="kv" :disabled="!isCustomKv" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed">
                            <option value="">-- Chọn --</option>
                            <?php foreach ($priorityAreas as $ma_kv => $diem): ?>
                                <option value="<?= $ma_kv ?>"><?= $ma_kv ?> (+<?= $diem ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                    </div>
                    <input type="hidden" name="kv_uu_tien" :value="kv" x-show="!isCustomKv">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider text-blue-600">ĐT ưu tiên</label>
                        <label class="flex items-center gap-1.5 cursor-pointer group">
                            <input type="checkbox" name="is_custom_dt" value="1" x-model="isCustomDt" class="w-3.5 h-3.5 rounded border-slate-300 text-blue-500 focus:ring-blue-200">
                            <span class="text-[9px] font-bold text-slate-400 group-hover:text-blue-500 transition-colors uppercase">Tùy chỉnh</span>
                        </label>
                    </div>
                    <div class="relative">
                        <i class="fas fa-user-shield absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <select name="dt_uu_tien" x-model="dt" :disabled="!isCustomDt" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed">
                            <option value="">-- Không ưu tiên --</option>
                            <?php foreach ($priorityObjects as $ma_dt => $diem): ?>
                                <option value="<?= $ma_dt ?>"><?= $ma_dt ?> (+<?= $diem ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                    </div>
                    <input type="hidden" name="dt_uu_tien" :value="dt" x-show="!isCustomDt">
                </div>
            </div>
        </div>

        <!-- Scores Table (Merged) -->
        <div class="bg-slate-50/30 rounded-2xl border border-slate-100 overflow-hidden mb-10">
            <div class="bg-white px-5 py-3 border-b border-slate-100 flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-blue-50 text-[#0066FF] flex items-center justify-center text-xs shadow-sm"><i class="fas fa-keyboard"></i></span>
                <span class="block text-[11px] font-black text-slate-400 uppercase tracking-widest leading-none">Chỉnh sửa điểm học bạ</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-700 uppercase font-bold text-xs tracking-widest">
                            <th class="px-5 py-2 border-b border-r sticky left-0 bg-slate-50 z-10 w-48 text-left">Môn học</th>
                            <th class="px-2 py-2 border-b border-r bg-slate-50 text-center">Lớp 10</th>
                            <th class="px-2 py-2 border-b border-r bg-slate-50 text-center">Lớp 11</th>
                            <th class="px-2 py-2 border-b bg-slate-50 text-center">Lớp 12</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-xs text-slate-600">
                        <?php foreach ($subjects as $code => $name): ?>
                            <tr class="border-t border-slate-50 even:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-1.5 text-slate-600 border-r border-slate-50 font-medium sticky left-0 bg-inherit z-0 flex items-center gap-2 tracking-tighter">
                                    <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                    <?= $name ?>
                                </td>
                                <?php foreach ([10, 11, 12] as $g): 
                                    $gradeRow = $rowsByGrade[$g] ?? [];
                                ?>
                                    <td class="px-2 py-1 text-center border-r border-slate-50">
                                        <input type="number" step="0.1" name="scores[<?= $g ?>][diem_<?= $code ?>_cn]" 
                                            value="<?= $gradeRow["diem_{$code}_cn"] ?? '' ?>" 
                                            class="w-16 px-1 py-1 text-center bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 focus:ring-4 focus:ring-blue-50 focus:border-blue-400 outline-none transition-all shadow-sm">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="bg-blue-50/30 text-slate-700 border-t-2 border-slate-200 font-bold">
                            <td class="px-5 py-2 border-r sticky left-0 bg-[#f8fafc] z-0 border-slate-200 uppercase tracking-widest text-[#0066FF]">ĐTB chung</td>
                            <?php foreach ([10, 11, 12] as $g): 
                                $gradeRow = $rowsByGrade[$g] ?? [];
                            ?>
                                <td class="px-2 py-1 text-center border-r border-slate-200">
                                    <input type="number" step="0.01" name="scores[<?= $g ?>][diem_tb_ca_nam]" 
                                        value="<?= $gradeRow['diem_tb_ca_nam'] ?? '' ?>" 
                                        class="w-16 px-1 py-1 text-center bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 shadow-sm focus:ring-4 focus:ring-blue-50 focus:border-blue-400 outline-none transition-all">
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <tr class="bg-white border-t border-slate-100 text-slate-700">
                            <td class="px-5 py-1.5 text-slate-600 border-r sticky left-0 bg-inherit border-slate-100 tracking-tighter uppercase font-medium">Học lực</td>
                            <?php foreach ([10, 11, 12] as $g): 
                                $gradeRow = $rowsByGrade[$g] ?? [];
                            ?>
                                <td class="px-2 py-1 text-center border-r border-slate-50">
                                    <select name="scores[<?= $g ?>][hoc_luc_ca_nam]" class="w-20 px-1 py-1 text-center bg-white border border-slate-200 rounded-lg text-[10px] font-medium text-slate-600 outline-none shadow-sm focus:ring-4 focus:ring-blue-50 focus:border-blue-400 transition-all appearance-none">
                                        <option value="">-- Chọn --</option>
                                        <?php foreach (['Gioi', 'Kha', 'TrungBinh', 'Yeu'] as $v): ?>
                                            <option value="<?= $v ?>" <?= ($gradeRow['hoc_luc_ca_nam'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr class="bg-slate-50/50 border-t border-slate-100 text-slate-700">
                            <td class="px-5 py-1.5 text-slate-600 border-r sticky left-0 bg-inherit border-slate-100 font-medium uppercase">Hạnh Kiểm</td>
                            <?php foreach ([10, 11, 12] as $g): 
                                $gradeRow = $rowsByGrade[$g] ?? [];
                            ?>
                                <td class="px-2 py-1 text-center border-r border-slate-50">
                                    <select name="scores[<?= $g ?>][hanh_kiem_ca_nam]" class="w-20 px-1 py-1 text-center bg-white border border-slate-200 rounded-lg text-[10px] font-medium text-slate-600 outline-none shadow-sm focus:ring-4 focus:ring-blue-50 focus:border-blue-400 transition-all appearance-none">
                                        <option value="">-- Chọn --</option>
                                        <?php foreach (['Tot', 'Kha', 'TrungBinh', 'Yeu'] as $v): ?>
                                            <option value="<?= $v ?>" <?= ($gradeRow['hanh_kiem_ca_nam'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Evidence & Save (Merged into main div) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <?php foreach ([10, 11, 12] as $g): 
                $gradeRow = $rowsByGrade[$g] ?? [];
            ?>
                <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <i class="fas fa-camera text-blue-400"></i> Minh chứng học bạ Lớp <?= $g ?>
                    </h4>
                    <input type="file" name="transcripts_<?= $g ?>[]" multiple accept="image/*" class="block w-full text-[10px] text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[9px] file:font-black file:uppercase file:bg-blue-50 file:text-[#0066FF] mb-3">
                    <div class="flex gap-2">
                        <?php foreach ([1, 2] as $i): ?>
                            <?php if (!empty($gradeRow["file_minh_chung_$i"])): ?>
                                <div class="relative group w-12 h-16 rounded-lg border border-slate-200 overflow-hidden shadow-sm">
                                    <img src="<?= strpos($gradeRow["file_minh_chung_$i"], 'http') === 0 ? google_drive_thumbnail_url($gradeRow["file_minh_chung_$i"], 'w200') : asset($gradeRow["file_minh_chung_$i"]) ?>" class="w-full h-full object-cover">
                                    <a href="<?= strpos($gradeRow["file_minh_chung_$i"], 'http') === 0 ? $gradeRow["file_minh_chung_$i"] : asset($gradeRow["file_minh_chung_$i"]) ?>" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[10px] transition-opacity"><i class="fas fa-eye"></i></a>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-8 border-t border-slate-100">
            <div class="p-4 bg-orange-50/20 rounded-2xl border border-orange-100">
                <label class="block text-[10px] font-black text-orange-600 uppercase tracking-widest mb-2">Minh chứng Khu vực</label>
                <input type="file" name="kv_file" accept=".pdf,image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 transition-all cursor-pointer">
            </div>
            <div class="p-4 bg-blue-50/20 rounded-2xl border border-blue-100">
                <label class="block text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2">Minh chứng Đối tượng</label>
                <input type="file" name="dt_file" accept=".pdf,image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 transition-all cursor-pointer">
            </div>
        </div>

        <div class="flex justify-end gap-4 pt-10">
            <button type="button" onclick="toggleEdit('academic')" class="px-6 py-3.5 rounded-xl text-slate-500 font-bold text-sm hover:bg-slate-50 hover:text-slate-700 transition-colors uppercase tracking-wider">Hủy bỏ</button>
            <button type="button" onclick="saveSection('academic')" class="px-10 py-4 bg-gradient-to-r from-[#0066FF] to-blue-700 text-white font-black text-sm uppercase tracking-wider rounded-xl shadow-xl shadow-blue-200 hover:-translate-y-1 transition-all flex items-center">
                <i class="fas fa-save mr-3"></i> Lưu thay đổi
            </button>
        </div>
    </div>
</div>