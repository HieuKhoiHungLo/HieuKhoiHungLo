<!-- Edit Form -->
<div id="form_academic" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-[2rem] p-8 border border-blue-100 shadow-xl shadow-blue-50/50">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#0066FF] shadow-sm">
                <i class="fas fa-school"></i>
            </div>
            <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Chỉnh sửa Kết quả Học tập</h3>
        </div>

        <!-- Scores Form (Dynamic Tabs) -->
        <div x-data="{ editGrade: '10' }" class="space-y-6">
            <div class="flex p-1.5 bg-slate-100 rounded-2xl gap-1">
                <?php foreach([10, 11, 12] as $g): ?>
                    <button type="button" @click="editGrade = '<?= $g ?>'" 
                            :class="editGrade === '<?= $g ?>' ? 'bg-white text-[#0066FF] shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            class="flex-1 py-3 text-xs font-black uppercase tracking-wider rounded-xl transition-all">
                        Lớp <?= $g ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach([10, 11, 12] as $g): ?>
            <div x-show="editGrade === '<?= $g ?>'" class="space-y-6 animate-in fade-in slide-in-from-left-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <?php 
                    $gradeRow = $rowsByGrade[$g] ?? [];
                    foreach ($subjects as $code => $name): 
                    ?>
                    <div class="flex items-center justify-between px-4 py-2 bg-slate-50 border border-slate-100 rounded-xl hover:bg-white hover:border-blue-200 transition-all hover:shadow-sm">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-tight"><?= $name ?></span>
                        <input type="number" step="0.1" name="scores[<?= $g ?>][diem_<?= $code ?>]" value="<?= $gradeRow["diem_{$code}"] ?? '' ?>" placeholder="-" class="w-16 px-1 py-1.5 text-center bg-white border border-slate-200 rounded-lg text-sm font-black text-slate-800 focus:ring-4 focus:ring-blue-50 focus:border-blue-400 outline-none transition-all">
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Summary Row -->
                <div class="bg-blue-50/30 p-5 rounded-2xl border border-blue-100/50">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-[11px] font-black text-blue-600 uppercase tracking-widest mb-2">ĐTB Cả Năm</label>
                            <input type="number" step="0.01" name="scores[<?= $g ?>][diem_tb]" value="<?= $gradeRow['diem_tb'] ?? '' ?>" class="w-full px-4 py-3 bg-white border border-blue-200 rounded-xl text-sm font-black text-[#0066FF] shadow-sm outline-none focus:ring-4 focus:ring-blue-100 transition-all" placeholder="Ví dụ: 8.54">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Học lực</label>
                            <select name="scores[<?= $g ?>][hoc_luc]" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:border-[#0066FF] transition-all">
                                <option value="">-- Chọn --</option>
                                <?php foreach(['Gioi', 'Kha', 'TrungBinh', 'Yeu'] as $v): ?>
                                    <option value="<?= $v ?>" <?= ($gradeRow['hoc_luc'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Hạnh kiểm</label>
                            <select name="scores[<?= $g ?>][hanh_kiem]" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:border-[#0066FF] transition-all">
                                <option value="">-- Chọn --</option>
                                <?php foreach(['Tot', 'Kha', 'TrungBinh', 'Yeu'] as $v): ?>
                                    <option value="<?= $v ?>" <?= ($gradeRow['hanh_kiem'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
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

        <div class="flex justify-end gap-4 pt-8 mt-8 border-t border-slate-100">
            <button type="button" onclick="toggleEdit('academic')" class="px-6 py-3.5 rounded-xl text-slate-500 font-bold text-sm hover:bg-slate-50 hover:text-slate-700 transition-colors uppercase tracking-wider">Hủy bỏ</button>
            <button type="button" onclick="saveSection('academic')" class="px-8 py-3.5 bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-black text-sm uppercase tracking-wider rounded-xl shadow-lg shadow-blue-200 hover:shadow-xl hover:shadow-blue-300 hover:-translate-y-1 active:translate-y-0 transition-all flex items-center">
                <i class="fas fa-save mr-2"></i> Lưu thay đổi
            </button>
        </div>
    </div>

</div>
