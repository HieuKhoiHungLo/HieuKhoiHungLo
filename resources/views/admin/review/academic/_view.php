<!-- View Mode -->
<div id="view_academic" class="space-y-6">
    <!-- School & Priority Info -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible mb-6">

        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60 rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#0066FF]/10 text-[#0066FF] flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Thông tin học tập</p>
                    <p class="text-sm font-bold text-slate-700">Học bạ THPT</p>
                </div>
            </div>
            <div id="btn_group_academic">
                <button type="button" onclick="toggleEdit('academic')" 
                    class="px-5 py-2.5 bg-[#0066FF] text-white font-bold rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center gap-2 text-sm">
                    <i class="fas fa-edit"></i> Sửa thông tin
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-5 mb-6">

                <!-- Tỉnh/TP -->
                <div class="md:col-span-4">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        <i class="fas fa-map-marker-alt mr-1 text-[#0066FF]"></i> Tỉnh/TP
                    </label>
                    <div class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800">
                        <?= isset($provinceMap[$user['ma_tinh_lop_12']]) ? $provinceMap[$user['ma_tinh_lop_12']] : '<span class="text-slate-300">Chưa cập nhật</span>' ?>
                    </div>
                </div>

                <!-- Trường THPT -->
                <div class="md:col-span-8">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        <i class="fas fa-university mr-1 text-[#0066FF]"></i> Trường THPT
                    </label>
                    <div class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800">
                        <?= $user['ten_truong_lop_12'] ?? '<span class="text-slate-300">Chưa cập nhật</span>' ?>
                    </div>
                </div>

                <!-- Năm tốt nghiệp -->
                <div class="md:col-span-4">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        <i class="fas fa-calendar-check mr-1 text-[#0066FF]"></i> Năm tốt nghiệp
                    </label>
                    <div class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800">
                        <?= $user['nam_tot_nghiep'] ?? '<span class="text-slate-300">Chưa cập nhật</span>' ?>
                    </div>
                </div>

                <!-- KV ưu tiên -->
                <div class="md:col-span-4">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        <i class="fas fa-star mr-1 text-amber-400"></i> KV ưu tiên
                    </label>
                    <div class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 flex items-center gap-2">
                        <?= $user['khu_vuc_uu_tien'] ?? '<span class="text-slate-300">--</span>' ?>
                        <?php if ($user['is_custom_kv'] ?? 0): ?>
                            <span class="text-[9px] font-black text-orange-500 bg-orange-50 border border-orange-100 px-2 py-0.5 rounded-full">THÍ SINH TỰ CHỌN</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ĐT ưu tiên -->
                <div class="md:col-span-4">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        <i class="fas fa-user-shield mr-1 text-[#0066FF]"></i> ĐT ưu tiên
                    </label>
                    <div class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 flex items-center gap-2">
                        <?php if (empty($user['doi_tuong_uu_tien'])): ?>
                            <span class="text-slate-300">Không ưu tiên</span>
                        <?php else: ?>
                            <?= htmlspecialchars($user['doi_tuong_uu_tien']) ?>
                        <?php endif; ?>
                        
                        <?php if ($user['is_custom_dt'] ?? 0): ?>
                            <span class="text-[9px] font-black text-blue-500 bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-full">THÍ SINH TỰ CHỌN</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Scores Table -->
            <div class="rounded-xl border border-slate-200 overflow-hidden">
                <!-- Table Header Banner -->
                <div class="bg-[#0066FF] px-5 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-table text-white/70 text-xs"></i>
                        <span class="text-xs font-black text-white uppercase tracking-widest">Bảng điểm học bạ</span>
                    </div>
                    <div>
                        <?php if (isset($user['da_du_6_ky']) && $user['da_du_6_ky']): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 text-white text-[10px] font-black uppercase tracking-wider rounded-lg">
                                <i class="fas fa-check-circle"></i> Đủ 3 năm
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-400/30 text-amber-100 text-[10px] font-black uppercase tracking-wider rounded-lg">
                                <i class="fas fa-exclamation-triangle"></i> Thiếu điểm Lớp 12
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                $rowsByGrade = [];
                if (isset($academicRows) && is_array($academicRows)) {
                    foreach ($academicRows as $r) {
                        $rowsByGrade[$r['lop']] = $r;
                    }
                }
                $getScore = function ($grade, $field) use ($rowsByGrade) {
                    $val = $rowsByGrade[$grade][$field] ?? null;
                    return ($val !== null && $val !== '') ? $val : '<span class="text-slate-300">-</span>';
                };
                $mapDisplay = function ($val) {
                    $map = ['Gioi' => 'Giỏi', 'Kha' => 'Khá', 'Trung binh' => 'Trung bình', 'TB' => 'Trung bình', 'Yeu' => 'Yếu', 'Tot' => 'Tốt', 'TrungBinh' => 'Trung bình'];
                    return $map[$val] ?? ($val ?: '<span class="text-slate-300">-</span>');
                };
                ?>

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
                                <tr class="border-b border-slate-100 hover:bg-blue-50/20 transition-colors">
                                    <td class="px-5 py-2 text-sm font-medium text-slate-700 border-r border-slate-100 sticky left-0 bg-white hover:bg-blue-50/20 z-10">
                                        <?= $name ?>
                                    </td>
                                    <?php foreach ([10, 11, 12] as $g): ?>
                                        <td class="px-4 py-2 text-center border-r border-slate-100 last:border-r-0 text-sm font-semibold text-slate-800 <?= $g == 12 ? 'bg-blue-50/20' : '' ?>">
                                            <?= $getScore($g, "diem_{$code}_cn") ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>

                            <!-- ĐTB chung -->
                            <tr class="border-t-2 border-[#0066FF]/20 bg-blue-50/30">
                                <td class="px-5 py-2.5 text-xs font-black text-slate-500 uppercase tracking-widest border-r border-slate-200 sticky left-0 bg-blue-50/30 z-10">
                                    Điểm TB chung
                                </td>
                                <?php foreach ([10, 11, 12] as $g): ?>
                                    <td class="px-4 py-2.5 text-center border-r border-slate-200 last:border-r-0 text-sm font-bold text-slate-800 <?= $g == 12 ? 'bg-blue-50/40' : '' ?>">
                                        <?= $getScore($g, 'diem_tb_ca_nam') ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <!-- Kết quả Học lực -->
                            <tr class="border-t border-slate-100 hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-2.5 text-xs font-black text-slate-500 uppercase tracking-widest border-r border-slate-200 sticky left-0 bg-white z-10">
                                    Kết quả học lực
                                </td>
                                <?php foreach ([10, 11, 12] as $g): ?>
                                    <td class="px-4 py-2.5 text-center border-r border-slate-100 last:border-r-0 text-sm font-semibold text-slate-800 <?= $g == 12 ? 'bg-blue-50/10' : '' ?>">
                                        <?= $mapDisplay($rowsByGrade[$g]['hoc_luc_ca_nam'] ?? '') ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <!-- Hạnh kiểm -->
                            <tr class="border-t border-slate-100 hover:bg-slate-50/50 transition-colors bg-slate-50/30">
                                <td class="px-5 py-2.5 text-xs font-black text-slate-500 uppercase tracking-widest border-r border-slate-200 sticky left-0 bg-slate-50/30 z-10">
                                    Hạnh kiểm
                                </td>
                                <?php foreach ([10, 11, 12] as $g): ?>
                                    <td class="px-4 py-2.5 text-center border-r border-slate-100 last:border-r-0 text-sm font-semibold text-slate-800 <?= $g == 12 ? 'bg-blue-50/10' : '' ?>">
                                        <?= $mapDisplay($rowsByGrade[$g]['hanh_kiem_ca_nam'] ?? '') ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>