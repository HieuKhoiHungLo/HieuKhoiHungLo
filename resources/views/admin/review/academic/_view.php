<!-- View Mode -->
<div id="view_academic" class="space-y-6">
    <!-- Priority & School (Redesigned to match Tab 1) -->
    <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-xl shadow-slate-50/50 overflow-visible mb-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-10">
            <!-- Row 1: Tỉnh/TP (1/3) và Trường (2/3) -->
            <div class="md:col-span-4">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tỉnh/TP</label>
                <div class="relative">
                    <i class="fas fa-map-marker-alt absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" disabled value="<?= isset($provinceMap[$user['ma_tinh_lop_12']]) ? $provinceMap[$user['ma_tinh_lop_12']] : 'Chưa cập nhật' ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
                </div>
            </div>

            <div class="md:col-span-8">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Trường THPT</label>
                <div class="relative">
                    <i class="fas fa-university absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" disabled value="<?= $user['ten_truong_lop_12'] ?? 'Chưa cập nhật' ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
                </div>
            </div>

            <!-- Row 2: Năm TN, KV ưu tiên, ĐT ưu tiên -->
            <div class="md:col-span-4">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Năm tốt nghiệp</label>
                <div class="relative">
                    <i class="fas fa-calendar-check absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" disabled value="<?= $user['nam_tot_nghiep'] ?? 'Chưa cập nhật' ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
                </div>
            </div>

            <div class="md:col-span-4">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex justify-between items-center">
                    KV ưu tiên
                    <?php if ($user['is_custom_kv'] ?? 0): ?>
                        <span class="px-2 py-0.5 bg-orange-100 text-orange-600 text-[9px] font-black rounded-md border border-orange-200 flex items-center gap-1 animate-pulse">
                            <i class="fas fa-exclamation-circle text-[8px]"></i> THÍ SINH TỰ CHỌN
                        </span>
                    <?php endif; ?>
                </label>
                <div class="relative">
                    <i class="fas fa-star absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" disabled value="<?= $user['khu_vuc_uu_tien'] ?? '--' ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
                </div>
            </div>

            <div class="md:col-span-4">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 text-blue-600">ĐT ưu tiên</label>
                <div class="relative">
                    <i class="fas fa-user-shield absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" disabled value="<?= $user['doi_tuong_uu_tien'] ?? '--' ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
                </div>
            </div>
        </div>

        <!-- Scores Table Inner -->
        <div class="bg-slate-50/30 rounded-2xl border border-slate-100 overflow-hidden">
            <div class="bg-slate-50/50 px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-blue-50 text-[#0066FF] flex items-center justify-center text-xs shadow-sm"><i class="fas fa-table"></i></span>
                    <span class="block text-[11px] font-black text-slate-400 uppercase tracking-widest leading-none">Chi tiết điểm học tập</span>
                </div>
                <div>
                    <?php if (isset($user['da_du_6_ky']) && $user['da_du_6_ky']): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-wider rounded-lg border border-emerald-100"><i class="fas fa-check-circle"></i> Đủ 3 năm</span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-wider rounded-lg border border-amber-100"><i class="fas fa-exclamation-triangle"></i> Thiếu điểm Lớp 12</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            // Prepare Data
            $rowsByGrade = [];
            if (isset($academicRows) && is_array($academicRows)) {
                foreach ($academicRows as $r) {
                    $rowsByGrade[$r['lop']] = $r;
                }
            }
            $getScore = function ($grade, $field) use ($rowsByGrade) {
                return $rowsByGrade[$grade][$field] ?? '-';
            };
            ?>

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
                                <td class="px-2 py-1.5 text-center border-r border-slate-50 text-slate-600 font-medium tracking-tight"><?= $getScore(10, "diem_{$code}_cn") ?></td>
                                <td class="px-2 py-1.5 text-center border-r border-slate-50 text-slate-600 font-medium tracking-tight"><?= $getScore(11, "diem_{$code}_cn") ?></td>
                                <td class="px-2 py-1.5 text-center text-slate-600 font-medium tracking-tight"><?= $getScore(12, "diem_{$code}_cn") ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="bg-blue-50/30 font-medium text-xs text-slate-700 border-t-2 border-slate-200">
                            <td class="px-5 py-2 border-r sticky left-0 bg-[#f8fafc] z-0 border-slate-200 uppercase tracking-widest text-[#0066FF] font-bold">Điểm TB chung</td>
                            <td class="px-2 py-2 text-center border-r border-slate-200 font-medium text-slate-600"><?= $getScore(10, 'diem_tb_ca_nam') ?></td>
                            <td class="px-2 py-2 text-center border-r border-slate-200 font-medium text-slate-600"><?= $getScore(11, 'diem_tb_ca_nam') ?></td>
                            <td class="px-2 py-2 text-center font-medium text-[#0066FF]"><?= $getScore(12, 'diem_tb_ca_nam') ?></td>
                        </tr>

                        <?php
                        $mapDisplay = function ($val) {
                            $map = ['Gioi' => 'Giỏi', 'Kha' => 'Khá', 'Trung binh' => 'Trung bình', 'TB' => 'Trung bình', 'Yeu' => 'Yếu', 'Tot' => 'Tốt', 'TrungBinh' => 'Trung bình'];
                            return $map[$val] ?? $val;
                        };
                        ?>
                        <tr class="bg-white font-medium text-xs uppercase border-t border-slate-100 text-slate-700">
                            <td class="px-5 py-1.5 text-slate-500 border-r sticky left-0 bg-white border-slate-100 tracking-tighter font-normal">Kết quả Học Lực</td>
                            <?php foreach ([10, 11, 12] as $g): ?>
                                <td class="px-2 py-1.5 text-center border-r border-slate-50 text-slate-600 font-normal"><?= $mapDisplay($getScore($g, 'hoc_luc_ca_nam')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr class="bg-slate-50/50 font-medium text-xs uppercase border-t border-slate-100 text-slate-700">
                            <td class="px-5 py-1.5 text-slate-500 border-r sticky left-0 bg-inherit border-slate-100 font-normal">Hạnh Kiểm</td>
                            <?php foreach ([10, 11, 12] as $g): ?>
                                <td class="px-2 py-1.5 text-center border-r border-slate-50 text-slate-600 font-normal"><?= $mapDisplay($getScore($g, 'hanh_kiem_ca_nam')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>