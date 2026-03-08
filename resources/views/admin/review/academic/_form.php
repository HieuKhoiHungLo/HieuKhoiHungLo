<!-- Edit Form -->
<div id="form_academic" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-[2rem] p-8 border border-blue-100 shadow-xl shadow-blue-50/50 overflow-visible">

        <!-- ===== SECTION 1: Thông tin Trường THPT ===== -->
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#0066FF] shadow-sm">
                <i class="fas fa-school"></i>
            </div>
            <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Thông tin Trường THPT Lớp 12</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Tỉnh/TP Trường Lớp 12 -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tỉnh/TP Trường THPT</label>
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

            <!-- Trường THPT (Searchable) -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Trường THPT</label>
                <div x-data="schoolSearch('<?= $user['ma_tinh_lop_12'] ?? '' ?>', '<?= $user['ma_truong_lop_12'] ?? '' ?>')"
                    @province-school-change.window="handleProvinceChange($event.detail)"
                    class="relative">

                    <input type="hidden" name="ma_truong_lop_12" :value="selectedCode">
                    <i class="fas fa-university absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text"
                        x-model="search"
                        @focus="open = true; search = ''"
                        @click.away="open = false"
                        placeholder="-- Nhập tên trường để tìm --"
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>

                    <!-- Dropdown -->
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

            <!-- Năm tốt nghiệp -->
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
        </div>

        <!-- ===== SECTION 2: Khu vực & Đối tượng Ưu tiên ===== -->
        <div class="h-px bg-slate-100 my-6"></div>

        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#ff8800] shadow-sm">
                <i class="fas fa-star"></i>
            </div>
            <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Khu vực & Đối tượng Ưu tiên</h3>
        </div>

        <div x-data="{ 
            kv: '<?= $user['khu_vuc_uu_tien'] ?? '' ?>', 
            isCustomKv: <?= ($user['is_custom_kv'] ?? 0) ? 'true' : 'false' ?>,
            dt: '<?= $user['doi_tuong_uu_tien'] ?? '' ?>',
            isCustomDt: <?= ($user['is_custom_dt'] ?? 0) ? 'true' : 'false' ?>
        }"
            @school-selected.window="if(!isCustomKv) kv = $event.detail.ma_kv"
            class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

            <!-- Khu vực ưu tiên -->
            <div class="p-4 bg-orange-50/30 rounded-2xl border border-orange-100">
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-[11px] font-bold text-orange-600 uppercase tracking-wider">Khu vực ưu tiên</label>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="is_custom_kv" value="1" x-model="isCustomKv" class="w-4 h-4 rounded border-orange-200 text-orange-500 focus:ring-orange-200">
                        <span class="text-[10px] font-bold text-slate-400 group-hover:text-orange-500 transition-colors uppercase">Tùy chỉnh thủ công</span>
                    </label>
                </div>
                <div class="relative">
                    <select name="kv_uu_tien" x-model="kv" :disabled="!isCustomKv" class="w-full pl-4 pr-10 py-3 bg-white border border-orange-100 rounded-xl text-sm font-bold text-slate-700 focus:ring-4 focus:ring-orange-50 transition-all outline-none appearance-none disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed">
                        <option value="">-- Chọn Khu vực --</option>
                        <?php foreach ($priorityAreas as $ma_kv => $diem): ?>
                            <option value="<?= $ma_kv ?>"><?= $ma_kv ?> (+<?= $diem ?> điểm)</option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-orange-300 text-xs pointer-events-none"></i>
                </div>
                <input type="hidden" name="kv_uu_tien" :value="kv" x-show="!isCustomKv">
            </div>

            <!-- Đối tượng ưu tiên -->
            <div class="p-4 bg-blue-50/30 rounded-2xl border border-blue-100">
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-[11px] font-bold text-blue-600 uppercase tracking-wider">Đối tượng ưu tiên</label>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="is_custom_dt" value="1" x-model="isCustomDt" class="w-4 h-4 rounded border-blue-200 text-blue-500 focus:ring-blue-200">
                        <span class="text-[10px] font-bold text-slate-400 group-hover:text-blue-500 transition-colors uppercase">Tự chọn ĐT ưu tiên</span>
                    </label>
                </div>
                <div class="relative">
                    <select name="dt_uu_tien" x-model="dt" :disabled="!isCustomDt" class="w-full pl-4 pr-10 py-3 bg-white border border-blue-100 rounded-xl text-sm font-bold text-slate-700 focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed">
                        <option value="">-- Không thuộc đối tượng ưu tiên --</option>
                        <?php foreach ($priorityObjects as $ma_dt => $diem): ?>
                            <option value="<?= $ma_dt ?>"><?= $ma_dt ?> (+<?= $diem ?> điểm)</option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-blue-300 text-xs pointer-events-none"></i>
                </div>
                <input type="hidden" name="dt_uu_tien" :value="dt" x-show="!isCustomDt">
            </div>
        </div>

        <!-- ===== SECTION 3: Điểm Học tập ===== -->
        <div class="h-px bg-slate-100 my-6"></div>

        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#0066FF] shadow-sm">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Chỉnh sửa Kết quả Học tập</h3>
        </div>

        <!-- Scores Form (Dynamic Tabs) -->
        <div x-data="{ editGrade: '10' }" class="space-y-6">
            <div class="flex p-1.5 bg-slate-100 rounded-2xl gap-1">
                <?php foreach ([10, 11, 12] as $g): ?>
                    <button type="button" @click="editGrade = '<?= $g ?>'"
                        :class="editGrade === '<?= $g ?>' ? 'bg-white text-[#0066FF] shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 py-3 text-xs font-black uppercase tracking-wider rounded-xl transition-all">
                        Lớp <?= $g ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ([10, 11, 12] as $g): ?>
                <div x-show="editGrade === '<?= $g ?>'" class="space-y-6 animate-in fade-in slide-in-from-left-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <?php
                        $gradeRow = $rowsByGrade[$g] ?? [];
                        foreach ($subjects as $code => $name):
                        ?>
                            <div class="flex items-center justify-between px-4 py-2 bg-slate-50 border border-slate-100 rounded-xl hover:bg-white hover:border-blue-200 transition-all hover:shadow-sm">
                                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-tight"><?= $name ?></span>
                                <input type="number" step="0.1" name="scores[<?= $g ?>][diem_<?= $code ?>_cn]" value="<?= $gradeRow["diem_{$code}_cn"] ?? '' ?>" placeholder="-" class="w-16 px-1 py-1.5 text-center bg-white border border-slate-200 rounded-lg text-sm font-black text-slate-800 focus:ring-4 focus:ring-blue-50 focus:border-blue-400 outline-none transition-all">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Summary Row -->
                    <div class="bg-blue-50/30 p-5 rounded-2xl border border-blue-100/50">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-[11px] font-black text-blue-600 uppercase tracking-widest mb-2">ĐTB Cả Năm</label>
                                <input type="number" step="0.01" name="scores[<?= $g ?>][diem_tb_ca_nam]" value="<?= $gradeRow['diem_tb_ca_nam'] ?? '' ?>" class="w-full px-4 py-3 bg-white border border-blue-200 rounded-xl text-sm font-black text-[#0066FF] shadow-sm outline-none focus:ring-4 focus:ring-blue-100 transition-all" placeholder="Ví dụ: 8.54">
                            </div>
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Học lực</label>
                                <select name="scores[<?= $g ?>][hoc_luc_ca_nam]" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:border-[#0066FF] transition-all">
                                    <option value="">-- Chọn --</option>
                                    <?php foreach (['Gioi', 'Kha', 'TrungBinh', 'Yeu'] as $v): ?>
                                        <option value="<?= $v ?>" <?= ($gradeRow['hoc_luc_ca_nam'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Hạnh kiểm</label>
                                <select name="scores[<?= $g ?>][hanh_kiem_ca_nam]" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:border-[#0066FF] transition-all">
                                    <option value="">-- Chọn --</option>
                                    <?php foreach (['Tot', 'Kha', 'TrungBinh', 'Yeu'] as $v): ?>
                                        <option value="<?= $v ?>" <?= ($gradeRow['hanh_kiem_ca_nam'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Evidence Uploads -->
                    <div class="pt-6 border-t border-slate-100">
                        <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                            <i class="fas fa-camera text-blue-400"></i> Minh chứng học bạ lớp <?= $g ?>
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Tải lên / Thay thế ảnh (Tối đa 2 file)</label>
                                <input type="file" name="transcripts_<?= $g ?>[]" multiple accept="image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-[#0066FF] hover:file:bg-blue-100 transition-all cursor-pointer">
                                <p class="text-[10px] text-slate-400 italic font-medium">* Bạn có thể chọn nhiều file cùng lúc.</p>
                            </div>
                            <div class="flex gap-3 items-end">
                                <?php foreach ([1, 2] as $i): ?>
                                    <?php if (!empty($gradeRow["file_minh_chung_$i"])): ?>
                                        <div class="relative group w-20 h-24 rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-all">
                                            <?php
                                            $path = $gradeRow["file_minh_chung_$i"];
                                            $src = strpos($path, 'http') === 0 ? google_drive_thumbnail_url($path, 'w200') : asset($path);
                                            ?>
                                            <img src="<?= $src ?>" class="w-full h-full object-cover">
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                                <a href="<?= strpos($path, 'http') === 0 ? $path : asset($path) ?>" target="_blank" class="text-white text-xs"><i class="fas fa-external-link-alt"></i></a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== Minh chứng Ưu tiên ===== -->
        <div class="h-px bg-slate-100 my-6"></div>
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#ff8800] shadow-sm">
                <i class="fas fa-file-signature"></i>
            </div>
            <h3 class="font-black text-slate-800 text-base uppercase tracking-tight">Minh chứng Ưu tiên</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- KV Evidence -->
            <div class="p-4 bg-orange-50/20 rounded-2xl border border-orange-100">
                <label class="block text-[10px] font-black text-orange-600 uppercase tracking-widest mb-2">Minh chứng Khu vực</label>
                <?php if (!empty($user['file_minh_chung_kv'])): ?>
                    <div class="text-[10px] text-slate-400 mb-2 truncate">Hiện có: <?= basename($user['file_minh_chung_kv']) ?></div>
                <?php endif; ?>
                <input type="file" name="kv_file" accept=".pdf,image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 transition-all cursor-pointer">
            </div>
            <!-- DT Evidence -->
            <div class="p-4 bg-blue-50/20 rounded-2xl border border-blue-100">
                <label class="block text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2">Minh chứng Đối tượng</label>
                <?php if (!empty($user['file_minh_chung_dt'])): ?>
                    <div class="text-[10px] text-slate-400 mb-2 truncate">Hiện có: <?= basename($user['file_minh_chung_dt']) ?></div>
                <?php endif; ?>
                <input type="file" name="dt_file" accept=".pdf,image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 transition-all cursor-pointer">
            </div>
        </div>

        <div class="flex justify-end gap-4 pt-8 mt-8 border-t border-slate-100">
            <button type="button" onclick="toggleEdit('academic')" class="px-6 py-3.5 rounded-xl text-slate-500 font-bold text-sm hover:bg-slate-50 hover:text-slate-700 transition-colors uppercase tracking-wider">Hủy bỏ</button>
            <button type="button" onclick="saveSection('academic')" class="px-8 py-3.5 bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-black text-sm uppercase tracking-wider rounded-xl shadow-lg shadow-blue-200 hover:shadow-xl hover:shadow-blue-300 hover:-translate-y-1 active:translate-y-0 transition-all flex items-center">
                <i class="fas fa-save mr-2"></i> Lưu thay đổi
            </button>
        </div>
    </div>

</div>