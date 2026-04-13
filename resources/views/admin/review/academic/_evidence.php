<!-- Academic Evidence Sticky Sidebar -->
<div class="bg-white rounded-[2rem] border border-blue-100 shadow-sm sticky top-24 max-h-[calc(100vh-120px)] overflow-y-auto custom-scrollbar" style="padding: 12px 24px 24px 24px;" x-data="{ activeGrade: '10' }">
    <!-- Demographic Info (View Mode) -->
    <div id="view_academic_info" class="academic-view-field animate-in fade-in duration-300" style="margin-bottom: 8px;">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; font-size: 11px; color: #374151;">
            <!-- Tỉnh/TP -->
            <span style="white-space: nowrap; font-weight: 400; color: #374151; flex-shrink: 0;">Tỉnh/TP:</span>
            <div style="min-width: 100px; max-width: 140px; height: 28px; padding: 0 8px; display: flex; align-items: center; background: #fff; border: 1px solid #d1d5db; border-radius: 5px; font-size: 11px; color: #374151; overflow: hidden; white-space: nowrap; flex-shrink: 1;"
                title="<?= !empty($user['ma_tinh_lop_12']) && isset($provinceMap[$user['ma_tinh_lop_12']]) ? htmlspecialchars($provinceMap[$user['ma_tinh_lop_12']]) : '' ?>">
                <?php if (empty($user['ma_tinh_lop_12']) || !isset($provinceMap[$user['ma_tinh_lop_12']])): ?>
                    <span style="color:#9ca3af;">...</span>
                <?php else: ?>
                    <?= htmlspecialchars($provinceMap[$user['ma_tinh_lop_12']]) ?>
                <?php endif; ?>
            </div>
            <!-- Trường -->
            <span style="white-space: nowrap; font-weight: 400; color: #374151; flex-shrink: 0;">Trường:</span>
            <div style="flex: 1; min-width: 80px; height: 28px; padding: 0 8px; display: flex; align-items: center; background: #fff; border: 1px solid #d1d5db; border-radius: 5px; font-size: 11px; color: #374151; overflow: hidden;"
                title="<?= $user['ten_truong_lop_12'] ?? '' ?>">
                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; <?= empty($user['ten_truong_lop_12']) ? 'color: #9ca3af;' : '' ?>">
                    <?= !empty($user['ten_truong_lop_12']) ? htmlspecialchars($user['ten_truong_lop_12']) : '...' ?>
                </span>
            </div>
            <!-- Năm TN -->
            <span style="white-space: nowrap; font-weight: 400; color: #374151; flex-shrink: 0;">Năm TN:</span>
            <div style="width: 52px; height: 28px; padding: 0 6px; display: flex; align-items: center; justify-content: center; background: #fff; border: 1px solid #d1d5db; border-radius: 5px; font-size: 11px; color: #374151; flex-shrink: 0;" title="<?= $user['nam_tot_nghiep'] ?? '' ?>">
                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; <?= empty($user['nam_tot_nghiep']) ? 'color: #9ca3af;' : '' ?>">
                    <?= !empty($user['nam_tot_nghiep']) ? htmlspecialchars($user['nam_tot_nghiep']) : '...' ?>
                </span>
            </div>
            <!-- KV ƯT -->
            <span style="white-space: nowrap; font-weight: 400; color: #374151; flex-shrink: 0;">KV ƯT:</span>
            <div style="width: 56px; height: 28px; padding: 0 6px; display: flex; align-items: center; justify-content: center; background: #fff; border: 1px solid #d1d5db; border-radius: 5px; font-size: 11px; color: #374151; position: relative; flex-shrink: 0;">
                <?php if ($user['is_custom_kv'] ?? 0): ?>
                    <span style="position:absolute; top:-4px; right:-4px; width:8px; height:8px; background:#f97316; border-radius:50%; border:1px solid #fff;" title="Tự chọn"></span>
                <?php endif; ?>
                <?php if (empty($user['khu_vuc_uu_tien'])): ?>
                    <span style="color:#9ca3af;">...</span>
                <?php else: ?>
                    <?= htmlspecialchars($user['khu_vuc_uu_tien']) ?>
                <?php endif; ?>
            </div>
            <!-- ĐT ƯT -->
            <span style="white-space: nowrap; font-weight: 400; color: #374151; flex-shrink: 0;">ĐT ƯT:</span>
            <div style="width: 44px; height: 28px; padding: 0 6px; display: flex; align-items: center; justify-content: center; background: #fff; border: 1px solid #d1d5db; border-radius: 5px; font-size: 11px; color: #374151; position: relative; flex-shrink: 0;"
                title="<?= htmlspecialchars($user['doi_tuong_uu_tien'] ?? '') ?>">
                <?php if ($user['is_custom_dt'] ?? 0): ?>
                    <span style="position:absolute; top:-4px; right:-4px; width:8px; height:8px; background:#3b82f6; border-radius:50%; border:1px solid #fff;" title="Tự chọn"></span>
                <?php endif; ?>
                <?php if (empty($user['doi_tuong_uu_tien'])): ?>
                    <span style="color:#9ca3af;">-0-</span>
                <?php else: ?>
                    <?= htmlspecialchars($user['doi_tuong_uu_tien']) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <!-- Demographic Info (Edit Mode) -->
    <div id="edit_academic_info" class="academic-edit-field hidden mb-2 animate-in fade-in duration-300">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; font-size: 11px; color: #374151;">
            <!-- Tỉnh/TP -->
            <span style="white-space: nowrap; font-weight: 400; color: #374151; flex-shrink: 0;">Tỉnh/TP:</span>
            <div style="min-width: 100px; max-width: 140px; height: 28px; position: relative; flex-shrink: 1;">
                <select name="ma_tinh_lop_12" onchange="window.dispatchEvent(new CustomEvent('province-school-change', {detail: this.value}))" class="w-full h-full pl-2 pr-6 bg-white border border-slate-300 rounded text-[11px] font-normal text-[#374151] focus:border-[#0066FF] focus:ring-0 transition-all outline-none appearance-none cursor-pointer truncate">
                    <option value="">-- Quay lại --</option>
                    <?php foreach ($provinces as $p): ?>
                        <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_lop_12'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-[9px] pointer-events-none"></i>
            </div>

            <!-- Trường -->
            <span style="white-space: nowrap; font-weight: 400; color: #374151; flex-shrink: 0;">Trường:</span>
            <div style="flex: 1; min-width: 80px; height: 28px; position: relative;" x-data="schoolSearch('<?= $user['ma_tinh_lop_12'] ?? '' ?>', '<?= $user['ma_truong_lop_12'] ?? '' ?>', '<?= htmlspecialchars($schoolName ?? '') ?>')" @province-school-change.window="handleProvinceChange($event.detail)">
                <input type="hidden" name="ma_truong_lop_12" :value="selectedCode">
                <input type="text" x-model="search" @focus="open = true" @click.away="open = false" placeholder="..." 
                    title="<?= htmlspecialchars($schoolName ?? '') ?>"
                    class="w-full h-full px-3 bg-white border border-slate-300 rounded text-[11px] font-normal text-[#374151] focus:border-[#0066FF] focus:ring-0 transition-all outline-none"
                    style="text-overflow: ellipsis;">
                <div x-show="open" class="absolute z-[100] w-full mt-1 bg-white border border-blue-100 rounded-md shadow-2xl max-h-56 overflow-y-auto" style="top: 100%; left: 0;">
                    <template x-for="school in filteredSchools" :key="school.ma_truong">
                        <div @click="select(school)" class="px-3 py-1.5 hover:bg-blue-50 cursor-pointer border-b border-slate-50 last:border-0 transition-colors">
                            <span x-text="school.ten_truong" class="text-[11px] font-semibold text-slate-700 block truncate"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Năm TN -->
            <span style="white-space: nowrap; font-weight: 400; color: #374151; flex-shrink: 0;">Năm TN:</span>
            <div style="width: 52px; height: 28px; position: relative; flex-shrink: 0;">
                <select name="nam_tot_nghiep" class="w-full h-full pl-1 pr-4 bg-white border border-slate-300 rounded text-[11px] font-normal text-[#374151] focus:border-[#0066FF] focus:ring-0 transition-all outline-none appearance-none cursor-pointer text-center">
                    <?php $currentYear = date('Y'); for ($y = $currentYear; $y >= $currentYear - 10; $y--): ?>
                        <option value="<?= $y ?>" <?= ($user['nam_tot_nghiep'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-1 top-1/2 -translate-y-1/2 text-slate-400 text-[9px] pointer-events-none"></i>
            </div>

            <!-- KV ƯT -->
            <span style="white-space: nowrap; font-weight: 400; color: #374151; flex-shrink: 0;">KV ƯT:</span>
            <div style="width: 56px; height: 28px; position: relative; flex-shrink: 0;" x-data="{kv: '<?= $user['khu_vuc_uu_tien'] ?? '' ?>', isCustomKv: <?= ($user['is_custom_kv'] ?? 0) ? 'true' : 'false' ?>}" @school-selected.window="if(!isCustomKv) { let map = {'KV1':'1','KV2':'2','KV2-NT':'2NT','KV3':'3'}; let raw = $event.detail.ma_kv ? $event.detail.ma_kv.trim() : ''; kv = map[raw] || raw; }">
                <select name="kv_uu_tien" x-model="kv" :disabled="!isCustomKv" class="w-full h-full pl-1 pr-4 bg-white border border-slate-300 rounded text-[11px] font-normal text-[#374151] focus:border-[#0066FF] focus:ring-0 transition-all outline-none appearance-none disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed cursor-pointer text-center">
                    <option value="">--</option>
                    <?php foreach ($priorityAreas as $ma_kv => $diem): ?>
                        <option value="<?= $ma_kv ?>"><?= $ma_kv ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-1 top-1/2 -translate-y-1/2 text-slate-400 text-[9px] pointer-events-none"></i>
                <input type="hidden" name="kv_uu_tien" :value="kv" x-show="!isCustomKv">
                <label class="absolute -top-1.5 -right-1 cursor-pointer z-10" title="Tự chọn KV">
                    <input type="checkbox" name="is_custom_kv" value="1" x-model="isCustomKv" class="w-2.5 h-2.5 rounded-full border-slate-300 text-orange-500 focus:ring-0 shadow-sm bg-white">
                </label>
            </div>

            <!-- ĐT ƯT -->
            <span style="white-space: nowrap; font-weight: 400; color: #374151; flex-shrink: 0;">ĐT ƯT:</span>
            <div style="width: 44px; height: 28px; position: relative; flex-shrink: 0;" x-data="{dt: '<?= $user['doi_tuong_uu_tien'] ?? '' ?>', isCustomDt: <?= ($user['is_custom_dt'] ?? 0) ? 'true' : 'false' ?>}">
                <select name="dt_uu_tien" x-model="dt" :disabled="!isCustomDt" class="w-full h-full pl-0.5 pr-3.5 bg-white border border-slate-300 rounded text-[11px] font-normal text-[#374151] focus:border-[#0066FF] focus:ring-0 transition-all outline-none appearance-none disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed cursor-pointer text-center">
                    <option value="">-0-</option>
                    <?php foreach ($priorityObjects as $ma_dt => $diem): ?>
                        <option value="<?= $ma_dt ?>"><?= $ma_dt ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-0.5 top-1/2 -translate-y-1/2 text-slate-400 text-[9px] pointer-events-none"></i>
                <input type="hidden" name="dt_uu_tien" :value="dt" x-show="!isCustomDt">
                <label class="absolute -top-1.5 -right-1 cursor-pointer z-10" title="Tự chọn ĐT">
                    <input type="checkbox" name="is_custom_dt" value="1" x-model="isCustomDt" class="w-2.5 h-2.5 rounded-full border-slate-300 text-blue-500 focus:ring-0 shadow-sm bg-white">
                </label>
            </div>
        </div>
    </div>

    <!-- Grade Tabs -->
    <div style="display: flex; align-items: center; gap: 2px; border-bottom: 1px solid #e2e8f0; margin-bottom: 6px; padding-bottom: 0;">
        <?php foreach (['10' => 'Lớp 10', '11' => 'Lớp 11', '12' => 'Lớp 12', 'KV' => 'KVUT', 'DT' => 'ĐTUT'] as $key => $label): ?>
            <button type="button"
                @click="activeGrade = '<?= $key ?>'"
                :class="activeGrade === '<?= $key ?>'
                    ? 'bg-[#0066FF] text-white'
                    : 'bg-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-100'"
                style="padding: 4px 12px; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; border-radius: 6px 6px 0 0; border: none; cursor: pointer; white-space: nowrap; transition: all 0.2s; margin-bottom: -1px;">
                <?= $label ?>
            </button>
        <?php endforeach; ?>
    </div>


    <div class="space-y-6">
        <!-- Academic Transcripts -->
        <?php foreach ([10 => 'Lớp 10', 11 => 'Lớp 11', 12 => 'Lớp 12'] as $g => $label): ?>
            <div x-show="activeGrade === '<?= $g ?>'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="space-y-4">
                <div class="flex flex-col gap-4">
                    <?php
                    $rawFiles = $rowsByGrade[$g]['file_hoc_ba'] ?? '';
                    $fileList = !empty($rawFiles) ? explode(',', $rawFiles) : [];
                    $img1 = $fileList[0] ?? '';
                    $img2 = $fileList[1] ?? '';
                    ?>
                    <?php if (!empty($img1) || !empty($img2)): ?>
                        <?= render_evidence_item($img1, 'Ảnh minh chứng 1', "img_ev_{$g}_1", 'calc(100vh - 375px)') ?>
                        <!-- Upload below image 1 -->
                        <div class="academic-edit-field hidden">
                            <input type="file" name="transcripts_<?= $g ?>_replace_1" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-[#0066FF] hover:file:bg-blue-100 transition-all cursor-pointer">
                        </div>
                        <?php if (!empty($img2)): ?>
                            <?= render_evidence_item($img2, 'Ảnh minh chứng 2', "img_ev_{$g}_2", 'calc(100vh - 375px)') ?>
                            <!-- Upload below image 2 -->
                            <div class="academic-edit-field hidden">
                                <input type="file" name="transcripts_<?= $g ?>_replace_2" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-[#0066FF] hover:file:bg-blue-100 transition-all cursor-pointer">
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center p-8 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-center">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-slate-200 mb-3 shadow-sm">
                                <i class="fas fa-image text-xl"></i>
                            </div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Không có ảnh minh chứng</p>
                            
                            <div class="academic-edit-field hidden w-full px-4">
                                <label class="block text-[10px] font-black text-[#0066FF] uppercase tracking-widest mb-2">Thêm ảnh học bạ mới</label>
                                <input type="file" name="transcripts_<?= $g ?>_replace_1" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-[#0066FF] hover:file:bg-blue-100 transition-all cursor-pointer">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Priority Area (KV) -->
        <div x-show="activeGrade === 'KV'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            <?php if (!empty($user['file_minh_chung_kv'])): ?>
                <?= render_evidence_item($user['file_minh_chung_kv'], 'Minh chứng Khu vực Ưu tiên', 'img_ev_kv', 'calc(100vh - 375px)') ?>
                <!-- Upload below KV image -->
                <div class="academic-edit-field hidden mt-3">
                    <label class="block text-[10px] font-black text-orange-600 uppercase tracking-widest mb-2">Đổi ảnh minh chứng KV</label>
                    <input type="file" name="kv_file" accept=".pdf,image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 transition-all cursor-pointer">
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center p-12 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-center">
                    <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center text-slate-200 mb-4 shadow-sm">
                        <i class="fas fa-map-marker-alt text-2xl"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Không có ảnh minh chứng KV</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Priority Object (DT) -->
        <div x-show="activeGrade === 'DT'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            <?php if (!empty($user['file_minh_chung_dt'])): ?>
                <?= render_evidence_item($user['file_minh_chung_dt'], 'Minh chứng Đối tượng Ưu tiên', 'img_ev_dt', 'calc(100vh - 375px)') ?>
                <!-- Upload below DT image -->
                <div class="academic-edit-field hidden mt-3">
                    <label class="block text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2">Đổi ảnh minh chứng ĐT</label>
                    <input type="file" name="dt_file" accept=".pdf,image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 transition-all cursor-pointer">
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center p-12 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-center">
                    <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center text-slate-200 mb-4 shadow-sm">
                        <i class="fas fa-user-tag text-2xl"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Không có ảnh minh chứng ĐT</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>