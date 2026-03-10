<!-- Wishes List — Card Style -->
<?php if (empty($choices)): ?>
    <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-12 text-center">
        <div class="w-14 h-14 mx-auto mb-3 bg-slate-50 rounded-xl flex items-center justify-center">
            <i class="fas fa-inbox text-xl text-slate-300"></i>
        </div>
        <p class="text-sm font-bold text-slate-400">Chưa đăng ký nguyện vọng nào</p>
    </div>
<?php else: ?>
    <!-- Map majors for quick thresholds lookup -->
    <?php
    $majorMap = [];
    foreach ($majors as $m) {
        $majorMap[$m['ma_nganh']] = $m;
    }
    ?>
    <div class="space-y-3">
        <?php foreach ($choices as $index => $wish):
            $maNganh = $wish['ma_nganh'];
            $majorInfo = $majorMap[$maNganh] ?? null;
            $status = $wish['trang_thai'] ?? 'Chờ duyệt';
            $statusCfgMap = [
                'Đã duyệt' => ['bg-emerald-50 text-emerald-600 border-emerald-200', 'fa-check-circle', 'from-emerald-400 to-teal-500'],
                'Từ chối'   => ['bg-rose-50 text-rose-600 border-rose-200', 'fa-times-circle', 'from-rose-400 to-pink-500'],
            ];
            $statusCfg = $statusCfgMap[$status] ?? ['bg-amber-50 text-amber-600 border-amber-200', 'fa-clock', 'from-amber-400 to-orange-500'];
        ?>
            <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex group hover:shadow-md hover:border-blue-200 transition-all">
                <!-- Color Bar -->
                <div class="w-1.5 bg-gradient-to-b <?= $statusCfg[2] ?> shrink-0"></div>

                <!-- Content -->
                <div class="flex-1 flex items-center gap-5 px-5 py-4">
                    <!-- NV Number -->
                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-sm text-slate-700 group-hover:bg-[#0066FF] group-hover:text-white transition-all shrink-0">
                        <?= $index + 1 ?>
                    </div>

                    <!-- Ngành Info -->
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-slate-800 text-base truncate"><?= $wish['ten_nganh'] ?></div>
                        <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-sky-50 text-sky-600 rounded text-[10px] font-bold border border-sky-100">
                                <?= $wish['ma_nganh'] ?>
                            </span>

                            <!-- School Code (Always THV as per requirement/image) -->
                            <div class="flex items-center bg-slate-100 rounded-md px-2 py-0.5 border border-slate-200">
                                <span class="text-[9px] text-slate-400 font-bold uppercase mr-1.5">Mã trường:</span>
                                <span class="font-black text-slate-700 text-[10px]">THV</span>
                            </div>

                            <!-- Combinations (Badges) -->
                            <?php if (!empty($wish['to_hop_mon'])):
                                $combos = array_map('trim', explode(',', $wish['to_hop_mon']));
                            ?>
                                <div class="flex flex-wrap items-center gap-1">
                                    <?php foreach ($combos as $c): ?>
                                        <span class="inline-block px-1.5 py-0.5 bg-slate-50 text-slate-600 text-[9px] font-bold rounded border border-slate-200"><?= htmlspecialchars($c) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Threshold Badges (Match Student View) -->
                            <?php if ($majorInfo && (!empty($majorInfo['nguong_hoc_luc']) || !empty($majorInfo['nguong_diem_thpt']))): ?>
                                <?php
                                $nhomLabels = ['SuPham' => 'Sư phạm', 'SuPhamDacThu' => 'SP Đặc thù', 'DieuDuong' => 'Điều dưỡng'];
                                $hlLabels = ['Tot' => 'Tốt', 'Kha' => 'Khá', 'Dat' => 'Đạt', 'ChuaDat' => 'Chưa Đạt'];
                                $nhom = $majorInfo['nhom_nganh'] ?? '';
                                $nhomLabel = $nhomLabels[$nhom] ?? '';
                                $parts = [];
                                if (!empty($majorInfo['nguong_hoc_luc'])) {
                                    $hl = $hlLabels[$majorInfo['nguong_hoc_luc']] ?? $majorInfo['nguong_hoc_luc'];
                                    $parts[] = 'KQHT L12 ≥ ' . $hl;
                                }
                                if (!empty($majorInfo['nguong_diem_thpt'])) {
                                    $parts[] = 'Tổng ĐThi ≥ ' . number_format((float)$majorInfo['nguong_diem_thpt'], 1);
                                }
                                ?>
                                <div class="flex items-center gap-1.5">
                                    <?php if ($nhomLabel): ?>
                                        <span class="px-1.5 py-0.5 bg-amber-50 text-amber-600 text-[9px] font-bold rounded border border-amber-100 uppercase tracking-tighter"><?= $nhomLabel ?></span>
                                    <?php endif; ?>
                                    <span class="px-1.5 py-0.5 bg-rose-50 text-rose-600 text-[9px] font-bold rounded border border-rose-100 whitespace-nowrap">
                                        <i class="fas fa-bolt text-[8px] mr-1"></i> <?= implode(' | ', $parts) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold rounded-full border shrink-0 <?= $statusCfg[0] ?>">
                        <i class="fas <?= $statusCfg[1] ?> text-[9px]"></i> <?= $status ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>