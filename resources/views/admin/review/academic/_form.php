<?php
$mapDisplay = function($v) {
    return [
        'Gioi' => 'Giỏi', 'Kha' => 'Khá', 'TrungBinh' => 'T.Bình', 'Yeu' => 'Yếu',
        'Tot' => 'Tốt'
    ][$v] ?? $v;
};
?>
<div id="form_academic" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible">

        <!-- Section Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/60 rounded-t-2xl">
            <div class="w-9 h-9 rounded-xl bg-[#0066FF]/10 text-[#0066FF] flex items-center justify-center">
                <i class="fas fa-edit text-sm"></i>
            </div>
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Chỉnh sửa thông tin</p>
                <p class="text-sm font-bold text-slate-700">Học bạ & Thông tin trường</p>
            </div>
        </div>

        <div class="p-6">

            <!-- School Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-5 mb-6">

                <!-- Tỉnh/TP -->
                <div class="md:col-span-4">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        <i class="fas fa-map-marker-alt mr-1 text-[#0066FF]"></i> Tỉnh/TP
                    </label>
                    <div class="relative">
                        <select name="ma_tinh_lop_12"
                            onchange="window.dispatchEvent(new CustomEvent('province-school-change', {detail: this.value}))"
                            class="w-full pl-4 pr-8 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none shadow-sm">
                            <option value="">-- Chọn Tỉnh/TP --</option>
                            <?php foreach ($provinces as $p): ?>
                                <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_lop_12'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <!-- Trường THPT -->
                <div class="md:col-span-8">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        <i class="fas fa-university mr-1 text-[#0066FF]"></i> Trường THPT
                    </label>
                    <div x-data="schoolSearch('<?= $user['ma_tinh_lop_12'] ?? '' ?>', '<?= $user['ma_truong_lop_12'] ?? '' ?>')"
                        @province-school-change.window="handleProvinceChange($event.detail)"
                        class="relative">
                        <input type="hidden" name="ma_truong_lop_12" :value="selectedCode">
                        <input type="text" x-model="search" @focus="open = true" @click.away="open = false"
                            placeholder="Nhập tên trường để tìm..."
                            class="w-full pl-4 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none shadow-sm">
                        <div x-show="open" class="absolute z-[100] w-full mt-1 bg-white border border-blue-100 rounded-xl shadow-2xl max-h-56 overflow-y-auto" style="top: 100%; left: 0;">
                            <template x-for="school in filteredSchools" :key="school.ma_truong">
                                <div @click="select(school)" class="px-4 py-2.5 hover:bg-blue-50 cursor-pointer text-sm border-b border-slate-50 last:border-0 transition-colors">
                                    <span x-text="school.ten_truong" class="font-semibold text-slate-700 block"></span>
                                    <span x-text="'Mã: ' + school.ma_truong" class="text-[10px] text-slate-400"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Năm tốt nghiệp -->
                <div class="md:col-span-4">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        <i class="fas fa-calendar-check mr-1 text-[#0066FF]"></i> Năm tốt nghiệp
                    </label>
                    <div class="relative">
                        <select name="nam_tot_nghiep" class="w-full pl-4 pr-8 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none shadow-sm">
                            <?php $currentYear = date('Y'); for ($y = $currentYear; $y >= $currentYear - 10; $y--): ?>
                                <option value="<?= $y ?>" <?= ($user['nam_tot_nghiep'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <!-- KV ưu tiên -->
                <div x-data="{
                        kv: '<?= $user['khu_vuc_uu_tien'] ?? '' ?>',
                        isCustomKv: <?= ($user['is_custom_kv'] ?? 0) ? 'true' : 'false' ?>
                    }"
                    @school-selected.window="if(!isCustomKv) kv = $event.detail.ma_kv"
                    class="md:col-span-4">
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest">
                            <i class="fas fa-star mr-1 text-amber-400"></i> KV ưu tiên
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer group">
                            <input type="checkbox" name="is_custom_kv" value="1" x-model="isCustomKv"
                                class="w-3.5 h-3.5 rounded border-slate-300 text-orange-500 focus:ring-orange-200">
                            <span class="text-[9px] font-black text-slate-400 group-hover:text-orange-500 transition-colors uppercase">Tùy chỉnh</span>
                        </label>
                    </div>
                    <div class="relative">
                        <select name="kv_uu_tien" x-model="kv" :disabled="!isCustomKv"
                            class="w-full pl-4 pr-8 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none shadow-sm disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed">
                            <option value="">-- Chọn --</option>
                            <?php foreach ($priorityAreas as $ma_kv => $diem): ?>
                                <option value="<?= $ma_kv ?>"><?= $ma_kv ?> (+<?= $diem ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                    </div>
                    <template x-if="isCustomKv">
                        <p class="mt-1.5 text-[10px] text-orange-500 italic font-medium flex items-center gap-1">
                            <i class="fas fa-info-circle text-[9px]"></i> thí sinh tự chọn
                        </p>
                    </template>
                    <input type="hidden" name="kv_uu_tien" :value="kv" x-show="!isCustomKv">
                </div>

                <!-- ĐT ưu tiên -->
                <div x-data="{
                        dt: '<?= $user['doi_tuong_uu_tien'] ?? '' ?>',
                        isCustomDt: <?= ($user['is_custom_dt'] ?? 0) ? 'true' : 'false' ?>
                    }"
                    class="md:col-span-4">
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest">
                            <i class="fas fa-user-shield mr-1 text-[#0066FF]"></i> ĐT ưu tiên
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer group">
                            <input type="checkbox" name="is_custom_dt" value="1" x-model="isCustomDt"
                                class="w-3.5 h-3.5 rounded border-slate-300 text-blue-500 focus:ring-blue-200">
                            <span class="text-[9px] font-black text-slate-400 group-hover:text-blue-500 transition-colors uppercase">Tùy chỉnh</span>
                        </label>
                    </div>
                    <div class="relative">
                        <select name="dt_uu_tien" x-model="dt" :disabled="!isCustomDt"
                            class="w-full pl-4 pr-10 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none shadow-sm disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed">
                            <option value="">-- Không ưu tiên --</option>
                            <?php foreach ($priorityObjects as $ma_dt => $diem): ?>
                                <option value="<?= $ma_dt ?>"><?= $ma_dt ?> (+<?= $diem ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-[10px] pointer-events-none"></i>
                    </div>
                    <input type="hidden" name="dt_uu_tien" :value="dt" x-show="!isCustomDt">
                </div>
            </div>

            <!-- Scores Table -->
            <div class="rounded-xl border border-slate-200 overflow-hidden">
                <!-- Table Header Banner -->
                <div class="bg-[#0066FF] px-5 py-3 flex items-center gap-2">
                    <i class="fas fa-table text-white/70 text-xs"></i>
                    <span class="text-xs font-black text-white uppercase tracking-widest">Bảng điểm học bạ</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-5 py-3 text-left text-[11px] font-black text-slate-500 uppercase tracking-widest sticky left-0 bg-slate-50 z-10 w-48 border-r border-slate-200">
                                    Môn học
                                </th>
                                <?php foreach ([10 => 'Lớp 10', 11 => 'Lớp 11', 12 => 'Lớp 12'] as $g => $label): ?>
                                    <th class="px-4 py-3 text-center text-[11px] font-black uppercase tracking-widest border-r border-slate-200 last:border-r-0 <?= $g == 12 ? 'text-[#0066FF] bg-blue-50/40' : 'text-slate-500' ?>">
                                        <?= $label ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $code => $name): ?>
                                <tr class="border-b border-slate-100 hover:bg-blue-50/20 transition-colors group">
                                    <td class="px-5 py-2 text-sm font-medium text-slate-600 border-r border-slate-100 sticky left-0 bg-white group-hover:bg-blue-50/20 z-10">
                                        <?= $name ?>
                                    </td>
                                    <?php foreach ([10, 11, 12] as $g):
                                        $gradeRow = $rowsByGrade[$g] ?? [];
                                        $val = is_numeric($gradeRow['diem_'.$code.'_cn']) ? number_format((float)$gradeRow['diem_'.$code.'_cn'], 1, '.', '') : ($gradeRow['diem_'.$code.'_cn'] ?? '');
                                    ?>
                                        <td class="px-4 py-2 text-center border-r border-slate-100 last:border-r-0 <?= $g == 12 ? 'bg-blue-50/20' : '' ?>">
                                            <input type="number" step="0.1" min="0" max="10"
                                                name="scores[<?= $g ?>][diem_<?= $code ?>_cn]"
                                                value="<?= $val ?>"
                                                placeholder="—"
                                                class="w-16 px-2 py-1.5 text-center bg-white border border-slate-200 rounded-lg text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-[#0066FF]/20 focus:border-[#0066FF] outline-none transition-all shadow-sm hover:border-slate-300 placeholder-slate-300">
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>

                            <!-- ĐTB chung -->
                            <tr class="border-t-2 border-[#0066FF]/20 bg-blue-50/30">
                                <td class="px-5 py-2.5 text-xs font-black text-slate-500 uppercase tracking-widest border-r border-slate-200 sticky left-0 bg-blue-50/30 z-10">
                                    Điểm TB chung
                                </td>
                                <?php foreach ([10, 11, 12] as $g):
                                    $gradeRow = $rowsByGrade[$g] ?? [];
                                    $val = is_numeric($gradeRow['diem_tb_ca_nam']) ? number_format((float)$gradeRow['diem_tb_ca_nam'], 1, '.', '') : ($gradeRow['diem_tb_ca_nam'] ?? '');
                                ?>
                                    <td class="px-4 py-2.5 text-center border-r border-slate-200 last:border-r-0 <?= $g == 12 ? 'bg-blue-50/40' : '' ?>">
                                        <input type="number" step="0.01" min="0" max="10"
                                            name="scores[<?= $g ?>][diem_tb_ca_nam]"
                                            value="<?= $val ?>"
                                            placeholder="—"
                                            class="w-16 px-2 py-1.5 text-center bg-white border border-[#0066FF]/30 rounded-lg text-sm font-bold text-[#0066FF] focus:ring-2 focus:ring-[#0066FF]/20 focus:border-[#0066FF] outline-none transition-all shadow-sm placeholder-slate-300">
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <!-- Học lực -->
                            <tr class="border-t border-slate-100 hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-2.5 text-xs font-black text-slate-500 uppercase tracking-widest border-r border-slate-200 sticky left-0 bg-white hover:bg-slate-50/50 z-10">
                                    Học lực
                                </td>
                                <?php foreach ([10, 11, 12] as $g):
                                    $gradeRow = $rowsByGrade[$g] ?? [];
                                ?>
                                    <td class="px-4 py-2 text-center border-r border-slate-100 last:border-r-0 <?= $g == 12 ? 'bg-blue-50/10' : '' ?>">
                                        <select name="scores[<?= $g ?>][hoc_luc_ca_nam]"
                                            class="w-24 px-2 py-1.5 text-center bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 outline-none shadow-sm focus:ring-2 focus:ring-[#0066FF]/20 focus:border-[#0066FF] transition-all appearance-none">
                                            <option value="">-- Chọn --</option>
                                            <?php foreach (['Gioi', 'Kha', 'TrungBinh', 'Yeu'] as $v): ?>
                                                <option value="<?= $v ?>" <?= ($gradeRow['hoc_luc_ca_nam'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <!-- Hạnh kiểm -->
                            <tr class="border-t border-slate-100 hover:bg-slate-50/50 transition-colors bg-slate-50/30">
                                <td class="px-5 py-2.5 text-xs font-black text-slate-500 uppercase tracking-widest border-r border-slate-200 sticky left-0 bg-slate-50/30 z-10">
                                    Hạnh kiểm
                                </td>
                                <?php foreach ([10, 11, 12] as $g):
                                    $gradeRow = $rowsByGrade[$g] ?? [];
                                ?>
                                    <td class="px-4 py-2 text-center border-r border-slate-100 last:border-r-0 <?= $g == 12 ? 'bg-blue-50/10' : '' ?>">
                                        <select name="scores[<?= $g ?>][hanh_kiem_ca_nam]"
                                            class="w-24 px-2 py-1.5 text-center bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 outline-none shadow-sm focus:ring-2 focus:ring-[#0066FF]/20 focus:border-[#0066FF] transition-all appearance-none">
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

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6 mt-2 border-t border-slate-100">
                <button type="button" onclick="toggleEdit('academic')"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-all border border-slate-200">
                    <i class="fas fa-times mr-1.5"></i> Hủy bỏ
                </button>
                <button type="button" onclick="saveSection('academic')"
                    class="px-8 py-2.5 bg-[#0066FF] text-white font-bold text-sm rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>
        </div>
    </div>
</div>