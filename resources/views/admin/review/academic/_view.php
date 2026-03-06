<!-- View Mode -->
<div id="view_academic" class="space-y-6">
    <!-- Priority & School -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Trường THPT -->
        <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex transition-all hover:shadow-md h-full">
            <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
            <div class="px-4 py-2 w-full">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[10px]"><i class="fas fa-school"></i></span>
                    <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Trường THPT Lớp 12</span>
                </div>
                <div class="font-bold text-slate-800 text-base leading-tight">
                    <?= $user['ten_truong_lop_12'] ?? 'Chưa cập nhật' ?>
                </div>
                <div class="text-[11px] text-slate-400 font-bold mt-1 uppercase tracking-tight">
                    <i class="fas fa-map-marker-alt mr-1"></i> <?= isset($provinceMap[$user['ma_tinh_lop_12']]) ? $provinceMap[$user['ma_tinh_lop_12']] : '' ?>
                </div>
            </div>
        </div>

        <!-- Năm Tốt Nghiệp -->
        <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex transition-all hover:shadow-md h-full">
            <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
            <div class="px-4 py-2 w-full">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[10px]"><i class="fas fa-calendar-check"></i></span>
                    <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Năm Tốt Nghiệp</span>
                </div>
                <div class="font-bold text-slate-800 text-base leading-tight">
                    <?= $user['nam_tot_nghiep'] ?? 'Chưa cập nhật' ?>
                </div>
                <div class="text-[11px] text-slate-400 font-bold mt-1 uppercase tracking-tight">
                    <i class="fas fa-info-circle mr-1"></i> Quy định xét ưu tiên
                </div>
            </div>
        </div>

        <!-- KV -->
        <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex transition-all hover:shadow-md">
            <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
            <div class="px-4 py-2 w-full">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[10px]"><i class="fas fa-map"></i></span>
                    <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Khu vực UT</span>
                </div>
                <div class="font-black text-slate-800 text-2xl">
                    <?= $user['khu_vuc_uu_tien'] ?? '--' ?>
                </div>
            </div>
        </div>

        <!-- ĐT -->
        <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex transition-all hover:shadow-md">
            <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
            <div class="px-4 py-2 w-full">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[10px]"><i class="fas fa-user-tag"></i></span>
                    <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Đối tượng UT</span>
                </div>
                <div class="font-black text-slate-800 text-2xl">
                    <?= $user['doi_tuong_uu_tien'] ?? '--' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scores Table (Student Interface Style) -->
    <div class="bg-white rounded-[1.5rem] border border-blue-100 overflow-hidden shadow-sm">
        <div class="bg-slate-50/50 px-5 py-3 border-b border-slate-100 flex items-center gap-3">
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-[#0066FF] flex items-center justify-center text-xs shadow-sm"><i class="fas fa-table"></i></span>
            <div>
                <span class="block text-[11px] font-black text-slate-400 uppercase tracking-widest leading-none">Chi tiết điểm học tập</span>
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
        // Helper to safely get value
        $getScore = function ($grade, $field) use ($rowsByGrade) {
            return $rowsByGrade[$grade][$field] ?? '-';
        };
        ?>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-700 uppercase font-bold text-xs tracking-widest">
                        <th class="px-5 py-4 border-b border-r sticky left-0 bg-slate-50 z-10 w-48 text-left">Môn học</th>
                        <th class="px-2 py-4 border-b border-r bg-slate-50 text-center">Lớp 10<br><span class="text-[10px] normal-case font-medium">(ĐTB cả năm)</span></th>
                        <th class="px-2 py-4 border-b border-r bg-slate-50 text-center">Lớp 11<br><span class="text-[10px] normal-case font-medium">(ĐTB cả năm)</span></th>
                        <th class="px-2 py-4 border-b bg-slate-50 text-center">Lớp 12<br><span class="text-[10px] normal-case font-medium">(ĐTB cả năm)</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-xs text-slate-600">
                    <?php
                    foreach ($subjects as $code => $name):
                        $fieldCode = $code;
                    ?>
                        <tr class="hover:bg-blue-50/30 transition-colors group">
                            <td class="px-5 py-3 font-medium text-slate-700 border-r sticky left-0 bg-white group-hover:bg-blue-50/30 z-0 border-slate-100 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-200 group-hover:bg-blue-400"></span>
                                <?= $name ?>
                            </td>

                            <!-- Grade 10 -->
                            <td class="px-2 py-3 text-center border-r border-slate-50 bg-white text-slate-700 font-bold"><?= $getScore(10, "diem_{$fieldCode}_cn") ?></td>

                            <!-- Grade 11 -->
                            <td class="px-2 py-3 text-center border-r border-slate-50 bg-white text-slate-700 font-bold"><?= $getScore(11, "diem_{$fieldCode}_cn") ?></td>

                            <!-- Grade 12 -->
                            <td class="px-2 py-3 text-center bg-white text-slate-700 border-r-0 font-bold"><?= $getScore(12, "diem_{$fieldCode}_cn") ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <!-- Avg Row -->
                    <tr class="bg-slate-50 font-medium text-xs text-slate-700 border-t-2 border-slate-200">
                        <td class="px-5 py-4 border-r sticky left-0 bg-slate-50 z-0 border-slate-200 uppercase tracking-widest">Điểm TB chung</td>
                        <td class="px-2 py-4 text-center border-r border-slate-200"><?= $getScore(10, 'diem_tb_ca_nam') ?></td>
                        <td class="px-2 py-4 text-center border-r border-slate-200"><?= $getScore(11, 'diem_tb_ca_nam') ?></td>
                        <td class="px-2 py-4 text-center"><?= $getScore(12, 'diem_tb_ca_nam') ?></td>
                    </tr>

                    <?php
                    $mapDisplay = function ($val) {
                        $map = [
                            'Gioi' => 'Giỏi',
                            'Kha' => 'Khá',
                            'Trung binh' => 'Trung bình',
                            'TB' => 'Trung bình',
                            'Yeu' => 'Yếu',
                            'Tot' => 'Tốt',
                            'TrungBinh' => 'Trung bình'
                        ];
                        return $map[$val] ?? $val;
                    };
                    ?>
                    <!-- Rank Row -->
                    <tr class="bg-white font-medium text-xs uppercase border-t border-slate-100 text-slate-700">
                        <td class="px-5 py-3 text-slate-500 border-r sticky left-0 bg-white border-slate-100 tracking-tighter">Kết quả Học Lực</td>
                        <?php foreach ([10, 11, 12] as $g): ?>
                            <td class="px-2 py-3 text-center border-r border-slate-50<?= $g == 12 ? ' border-r-0' : '' ?>"><?= $mapDisplay($getScore($g, 'hoc_luc_ca_nam')) ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Conduct Row -->
                    <tr class="bg-slate-50 font-medium text-xs uppercase border-t border-slate-200 text-slate-700">
                        <td class="px-5 py-3 text-slate-500 border-r sticky left-0 bg-slate-50 border-slate-200">Hạnh Kiểm</td>
                        <?php foreach ([10, 11, 12] as $g): ?>
                            <td class="px-2 py-3 text-center border-r border-slate-50<?= $g == 12 ? ' border-r-0' : '' ?>"><?= $mapDisplay($getScore($g, 'hanh_kiem_ca_nam')) ?></td>
                        <?php endforeach; ?>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
</div>