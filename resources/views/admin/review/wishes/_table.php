<!-- Wishes List — Card Style -->
<?php if (empty($choices)): ?>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-16 text-center">
        <div class="w-16 h-16 mx-auto mb-4 bg-slate-50 rounded-2xl flex items-center justify-center shadow-inner">
            <i class="fas fa-inbox text-2xl text-slate-200"></i>
        </div>
        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Chưa đăng ký nguyện vọng</p>
    </div>
<?php else: ?>
    <?php
    $majorMap = [];
    foreach ($majors as $m) {
        $majorMap[$m['ma_nganh']] = $m;
    }
    ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse border border-slate-100">
            <thead>
                <tr class="bg-slate-100/50">
                    <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest w-16 text-center border border-slate-100">STT</th>
                    <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest border border-slate-100">Ngành đăng ký</th>
                    <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest w-48 border border-slate-100">Tổ hợp môn</th>
                    <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest w-40 text-center border border-slate-100">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($choices as $index => $wish):
                    $maNganh = $wish['ma_nganh'];
                    $majorInfo = $majorMap[$maNganh] ?? null;
                    $status = $wish['trang_thai'] ?? 'Chờ duyệt';
                    $statusCfgMap = [
                        'Đã duyệt' => ['bg-emerald-50 text-emerald-600 border-emerald-100', 'fa-check-circle'],
                        'Từ chối'   => ['bg-rose-50 text-rose-600 border-rose-100', 'fa-times-circle'],
                    ];
                    $statusCfg = $statusCfgMap[$status] ?? ['bg-amber-50 text-amber-600 border-amber-100', 'fa-clock'];
                ?>
                    <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/30' ?> hover:bg-blue-50/50 transition-colors">
                        <td class="px-4 py-4 text-center border border-slate-100">
                            <span class="text-xs font-mono font-bold text-slate-400"><?= $index + 1 ?></span>
                        </td>
                        <td class="px-4 py-4 border border-slate-100">
                            <div class="flex flex-col gap-1.5">
                                <span class="font-bold text-slate-800 text-sm tracking-tight leading-tight"><?= $wish['ten_nganh'] ?></span>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center px-1.5 py-0.5 bg-blue-50 text-[10px] text-[#0066FF] font-black rounded border border-blue-100"><?= $wish['ma_nganh'] ?></span>
                                </div>
                                <?php if ($majorInfo && (!empty($majorInfo['nguong_hoc_luc']) || !empty($majorInfo['nguong_diem_thpt']))): 
                                    $nhomLabels = ['SuPham' => 'Sư phạm', 'SuPhamDacThu' => 'SP Đặc thù', 'DieuDuong' => 'Điều dưỡng'];
                                    $hlLabels = ['Tot' => 'Tốt', 'Kha' => 'Khá', 'Dat' => 'Đạt', 'ChuaDat' => 'Chưa Đạt'];
                                    $nhom = $majorInfo['nhom_nganh'] ?? '';
                                    $nhomLabel = $nhomLabels[$nhom] ?? '';
                                    $parts = [];
                                    if (!empty($majorInfo['nguong_hoc_luc'])) {
                                        $hl = $hlLabels[$majorInfo['nguong_hoc_luc']] ?? $majorInfo['nguong_hoc_luc'];
                                        $parts[] = 'HL ≥ ' . $hl;
                                    }
                                    if (!empty($majorInfo['nguong_diem_thpt'])) {
                                        $parts[] = 'Điểm ≥ ' . number_format((float)$majorInfo['nguong_diem_thpt'], 1);
                                    }
                                ?>
                                    <div class="flex items-center gap-2 mt-1">
                                        <?php if ($nhomLabel): ?>
                                            <span class="px-1.5 py-0.5 bg-amber-50 text-amber-600 text-[8px] font-black rounded border border-amber-100 uppercase"><?= $nhomLabel ?></span>
                                        <?php endif; ?>
                                        <span class="text-rose-500 text-[9px] font-bold">
                                            <i class="fas fa-bolt text-[8px] mr-1"></i> Ngưỡng: <?= implode(' | ', $parts) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-4 border border-slate-100">
                            <?php 
                                $displayCombos = !empty($majorInfo['to_hop_xet_tuyen']) ? $majorInfo['to_hop_xet_tuyen'] : ($wish['to_hop_mon'] ?? '');
                                if (!empty($displayCombos)):
                                    $combos = array_map('trim', explode(',', $displayCombos));
                            ?>
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($combos as $c): ?>
                                        <span class="px-1.5 py-0.5 bg-slate-50 text-slate-600 text-[9px] font-bold rounded border border-slate-200"><?= htmlspecialchars($c) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-[10px] text-slate-300 italic">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 text-center border border-slate-100">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[9px] font-black uppercase tracking-wider rounded-lg border <?= $statusCfg[0] ?> shadow-sm">
                                <i class="fas <?= $statusCfg[1] ?> text-[8px]"></i> <?= $status ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>