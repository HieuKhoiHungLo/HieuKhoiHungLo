<!-- Wishes List — Card Style -->
<?php if (empty($choices)): ?>
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-8 text-center">
        <div class="w-12 h-12 mx-auto mb-3 bg-slate-50 rounded-xl flex items-center justify-center shadow-inner">
            <i class="fas fa-inbox text-2xl text-slate-200"></i>
        </div>
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Chưa đăng ký nguyện vọng</p>
    </div>
<?php else: ?>
    <?php
    $majorMap = [];
    foreach ($majors as $m) {
        $majorMap[$m['ma_nganh']] = $m;
    }
    ?>
    <div class="overflow-x-auto border border-slate-200 rounded-lg">
        <table class="w-full text-left border-collapse border border-slate-100">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th style="padding: 12px 10px; text-align: center; font-weight: 600; color:#000; border-right: 1px solid #e2e8f0; width: 45px; font-size: 11px; text-transform: uppercase;">STT</th>
                    <th style="padding: 12px 10px; text-align: left; font-weight: 600; color:#000; border-right: 1px solid #e2e8f0; font-size: 11px; text-transform: uppercase;">Ngành đăng ký</th>
                    <th style="padding: 12px 10px; text-align: center; font-weight: 600; color:#000; border-right: 1px solid #e2e8f0; width: 70px; font-size: 11px; text-transform: uppercase;">Mã ngành</th>
                    <th style="padding: 12px 10px; text-align: center; font-weight: 600; color:#000; border-right: 1px solid #e2e8f0; width: 140px; font-size: 11px; text-transform: uppercase;">Tổ hợp</th>
                    <th style="padding: 12px 10px; text-align: left; font-weight: 600; color:#000; border-right: 1px solid #e2e8f0; font-size: 11px; text-transform: uppercase;">Ngưỡng</th>
                    <th style="padding: 12px 10px; text-align: left; font-weight: 600; color:#000; border-right: 1px solid #e2e8f0; width: 100px; font-size: 11px; text-transform: uppercase;">Loại ngành</th>
                    <th style="padding: 12px 10px; text-align: center; font-weight: 600; color:#000; width: 60px; font-size: 11px; text-transform: uppercase;">NV Bộ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($choices as $index => $wish):
                    $maNganh = $wish['ma_nganh'];
                    $majorInfo = $majorMap[$maNganh] ?? null;
                    
                    $nhomLabels = ['SuPham' => 'Sư phạm', 'SuPhamDacThu' => 'SP Đặc thù', 'DieuDuong' => 'Điều dưỡng'];
                    $nhom = $majorInfo['nhom_nganh'] ?? ($wish['nhom_nganh'] ?? '');
                    $nhomLabel = $nhomLabels[$nhom] ?? 'Ngoài SP';
                    
                    $hlLabels = ['Tot' => 'Tốt', 'Kha' => 'Khá', 'Dat' => 'Đạt', 'ChuaDat' => 'Chưa Đạt'];
                    $nguongParts = [];
                    if ($majorInfo) {
                        if (!empty($majorInfo['nguong_hoc_luc'])) {
                            $hl = $hlLabels[$majorInfo['nguong_hoc_luc']] ?? $majorInfo['nguong_hoc_luc'];
                            $nguongParts[] = 'HL ≥ ' . $hl;
                        }
                        if (!empty($majorInfo['nguong_diem_thpt'])) {
                            $nguongParts[] = 'Điểm ≥ ' . number_format((float)$majorInfo['nguong_diem_thpt'], 1);
                        }
                    }
                    $nguongStr = !empty($nguongParts) ? implode(' | ', $nguongParts) : '—';
                ?>
                    <tr class="bg-white border-b border-slate-100 last:border-b-0 hover:bg-slate-50 transition-colors">
                        <td style="padding: 12px 10px; text-align: center; border-right: 1px solid #e2e8f0; color: #000; font-weight: 400; font-size: 12px;">
                            <?= $index + 1 ?>
                        </td>
                        <td style="padding: 12px 10px; border-right: 1px solid #e2e8f0; color: #000; font-weight: 400; font-size: 12px;">
                            <?= htmlspecialchars($majorInfo['ten_nganh'] ?? ($wish['ten_nganh'] ?? '—')) ?>
                        </td>
                        <td style="padding: 12px 10px; text-align: center; border-right: 1px solid #e2e8f0; color: #000; font-weight: 400; font-size: 12px; font-family: monospace;">
                            <?= $wish['ma_nganh'] ?>
                        </td>
                        <td style="padding: 12px 10px; border-right: 1px solid #e2e8f0; color: #000; font-weight: 400; font-size: 12px; text-align: center;">
                            <?php 
                                $displayCombos = !empty($majorInfo['to_hop_xet_tuyen']) ? $majorInfo['to_hop_xet_tuyen'] : ($wish['to_hop_mon'] ?? '');
                                echo htmlspecialchars(str_replace(',', ', ', $displayCombos) ?: '—');
                            ?>
                        </td>
                        <td style="padding: 12px 10px; border-right: 1px solid #e2e8f0; color: #000; font-weight: 400; font-size: 12px;">
                            <?= $nguongStr ?>
                        </td>
                        <td style="padding: 12px 10px; border-right: 1px solid #e2e8f0; color: #000; font-weight: 400; font-size: 12px;">
                            <?= $nhomLabel ?>
                        </td>
                        <td style="padding: 12px 10px; text-align: center; color: #000; font-weight: 400; font-size: 12px;">
                            <?= $wish['thu_tu_nv_bo'] ?: '—' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>