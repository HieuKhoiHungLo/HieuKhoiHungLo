    <?php if (empty($groupedResults)): ?>
        <div class="flex-1 flex flex-col items-center justify-center bg-white rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center mt-6">
            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-search text-slate-300 text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-700 mb-1">Chưa có dữ liệu ứng viên</h3>
            <p class="text-slate-500 text-sm">Chưa tìm thấy hồ sơ nào đăng ký vào đợt tuyển sinh này.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col mt-6">
            <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/50 flex flex-wrap justify-between items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="bg-indigo-100 text-indigo-700 p-2 rounded-lg">
                        <i class="fas fa-table"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-800 text-sm">Bảng Lưới Chi tiết Toàn bộ Ứng viên</h2>
                        <p class="text-xs text-slate-500">Giúp rà soát điểm của tất cả thí sinh trước khi chốt Lọc Ảo</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto custom-scrollbar" style="max-height: 80vh;">
                <table class="w-full text-left border-collapse whitespace-nowrap text-[11px]">
                    <thead class="sticky top-0 z-20 shadow-sm">
                        <!-- Row 1: Group Headers -->
                        <tr class="bg-slate-100 text-slate-600 uppercase tracking-wider font-bold border-b border-slate-200 text-center">
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200 sticky left-0 bg-slate-100 z-30 shadow-[1px_0_0_#e2e8f0]">STT</th>
                            <th rowspan="2" class="py-2 px-3 border-r border-slate-200 sticky left-[40px] bg-slate-100 z-30 shadow-[1px_0_0_#e2e8f0] min-w-[110px]">CCCD</th>
                            <th rowspan="2" class="py-2 px-3 border-r border-slate-200 sticky left-[150px] bg-slate-100 z-30 shadow-[1px_0_0_#e2e8f0] min-w-[160px]">Họ và Tên</th>
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200">Mã ngành</th>
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200">NV</th>
                            <th rowspan="2" class="py-2 px-3 border-r border-slate-200 w-32 whitespace-normal break-words">Tổ hợp</th>
                            
                            <th colspan="4" class="py-1 px-2 border-r border-slate-200 bg-blue-50/80 text-blue-800">PT 100</th>
                            <th colspan="4" class="py-1 px-2 border-r border-slate-200 bg-emerald-50/80 text-emerald-800">PT 200</th>
                            
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200 text-[10px]">Tổ hợp<br>max</th>
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200 text-[10px]">PT<br>max</th>
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200">Điểm M1</th>
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200">Điểm M2</th>
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200">Điểm M3</th>
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200 text-[10px] text-orange-700 bg-orange-50/50" title="Điểm quy đổi (Tổng 3 môn chưa UT)">Điểm QĐ</th>
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200 text-[10px]" title="Điểm ưu tiên khu vực/đối tượng gốc">Điểm<br>UT gốc</th>
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200 text-[10px]" title="Điểm ưu tiên quy đổi (Nếu tổng >= 22.5)">Điểm<br>UT QĐ</th>
                            <th rowspan="2" class="py-2 px-3 border-r border-slate-200 bg-indigo-50 font-black text-[12px] text-indigo-800 shadow-[inset_0_0_2px_rgba(0,0,0,0.1)]">Điểm<br>xét tuyển</th>
                            
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200">ĐK học lực</th>
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200">ĐK Ngưỡng</th>
                            <th rowspan="2" class="py-2 px-2 border-r border-slate-200 min-w-[100px]">Trạng thái</th>
                        </tr>
                        <!-- Row 2: Sub Headers -->
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[9px] font-bold border-b border-slate-200">
                            <th class="py-1 px-2 text-center border-r border-slate-200 bg-blue-50/50">TH1</th>
                            <th class="py-1 px-2 text-center border-r border-slate-200 bg-blue-50/50">TH2</th>
                            <th class="py-1 px-2 text-center border-r border-slate-200 bg-blue-50/50">TH3</th>
                            <th class="py-1 px-2 text-center border-r border-slate-200 bg-blue-50/50">TH4</th>
                            
                            <th class="py-1 px-2 text-center border-r border-slate-200 bg-emerald-50/50">TH1</th>
                            <th class="py-1 px-2 text-center border-r border-slate-200 bg-emerald-50/50">TH2</th>
                            <th class="py-1 px-2 text-center border-r border-slate-200 bg-emerald-50/50">TH3</th>
                            <th class="py-1 px-2 text-center border-r border-slate-200 bg-emerald-50/50">TH4</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php 
                        $globalIndex = 1;
                        foreach ($groupedResults as $ma_nganh => $rows): 
                            foreach ($rows as $row): 
                                $details = json_decode($row['chi_tiet_diem'], true) ?: [];
                                $allCombos = $details['all_combinations'] ?? [];
                                
                                $pt100 = []; $pt200 = []; $comboNames = [];
                                foreach ($allCombos as $key => $score) {
                                    if (strpos($key, 'THPT_') === 0) {
                                        $pt100[] = is_numeric($score) ? number_format($score, 2) : $score;
                                        $comboName = str_replace('THPT_', '', $key);
                                        if(!in_array($comboName, $comboNames)) $comboNames[] = $comboName;
                                    } elseif (strpos($key, 'HB_') === 0) {
                                        $pt200[] = is_numeric($score) ? number_format($score, 2) : $score;
                                        $comboName = str_replace('HB_', '', $key);
                                        if(!in_array($comboName, $comboNames)) $comboNames[] = $comboName;
                                    }
                                }
                                $pt100 = array_pad($pt100, 4, '-');
                                $pt200 = array_pad($pt200, 4, '-');
                                
                                $majorArr = [
                                    'co_diem_nangkhieu_thpt' => $row['co_diem_nangkhieu_thpt'] ?? false,
                                    'co_xet_chung_chi' => $row['co_xet_chung_chi'] ?? false,
                                    'co_diem_nangkhieu_hochba' => $row['co_diem_nangkhieu_hochba'] ?? false
                                ];
                                $tsCode = \App\Helpers\AdmissionMethodHelper::resolvePhuongThuc($row['phuong_thuc_xet_tuyen'] ?? '', $majorArr);
                                
                                $isPass = in_array(strtolower($row['trang_thai'] ?? ''), ['trúng tuyển', 'trung tuyen', 'đỗ', 'do']);
                                $isFail = in_array(strtolower($row['trang_thai'] ?? ''), ['trượt', 'truot']);
                                $isThresholdFail = isset($details['threshold_note']);
                                
                                $trangThaiClass = $isPass ? 'text-emerald-700 bg-emerald-50 font-bold' : ($isFail ? 'text-rose-600 bg-rose-50' : 'text-slate-500');
                                $trangThaiText = $row['trang_thai'] ?? '-';
                        ?>
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    <td class="py-2 px-2 text-center text-slate-400 border-r border-slate-100 sticky left-0 bg-white group-hover:bg-slate-50 z-10"><?= $globalIndex++ ?></td>
                                    <td class="py-2 px-3 font-mono text-indigo-600 border-r border-slate-100 sticky left-[40px] bg-white group-hover:bg-slate-50 z-10"><?= htmlspecialchars($row['so_cccd']) ?></td>
                                    <td class="py-2 px-3 font-bold text-slate-700 border-r border-slate-100 sticky left-[150px] bg-white group-hover:bg-slate-50 z-10 truncate max-w-[160px]" title="<?= htmlspecialchars($row['ho_va_ten']) ?>"><?= htmlspecialchars($row['ho_va_ten']) ?></td>
                                    <td class="py-2 px-2 text-center font-medium border-r border-slate-100 bg-slate-50 border-y border-y-slate-100"><?= htmlspecialchars($row['ma_nganh']) ?></td>
                                    <td class="py-2 px-2 text-center border-r border-slate-100"><?= htmlspecialchars($row['thu_tu_nguyen_vong'] ?? '-') ?></td>
                                    <td class="py-2 px-3 border-r border-slate-100 text-[10px] text-slate-500 max-w-[120px] whitespace-normal break-words leading-tight"><?= implode(', ', $comboNames) ?></td>
                                    
                                    <!-- PT 100 -->
                                    <td class="py-2 px-2 text-center border-r border-slate-100 <?= $pt100[0] !== '-' ? 'font-medium text-slate-700' : 'text-slate-300' ?>"><?= $pt100[0] ?></td>
                                    <td class="py-2 px-2 text-center border-r border-slate-100 <?= $pt100[1] !== '-' ? 'font-medium text-slate-700' : 'text-slate-300' ?>"><?= $pt100[1] ?></td>
                                    <td class="py-2 px-2 text-center border-r border-slate-100 <?= $pt100[2] !== '-' ? 'font-medium text-slate-700' : 'text-slate-300' ?>"><?= $pt100[2] ?></td>
                                    <td class="py-2 px-2 text-center border-r border-slate-100 <?= $pt100[3] !== '-' ? 'font-medium text-slate-700' : 'text-slate-300' ?>"><?= $pt100[3] ?></td>
                                    
                                    <!-- PT 200 -->
                                    <td class="py-2 px-2 text-center border-r border-slate-100 <?= $pt200[0] !== '-' ? 'font-medium text-slate-700' : 'text-slate-300' ?>"><?= $pt200[0] ?></td>
                                    <td class="py-2 px-2 text-center border-r border-slate-100 <?= $pt200[1] !== '-' ? 'font-medium text-slate-700' : 'text-slate-300' ?>"><?= $pt200[1] ?></td>
                                    <td class="py-2 px-2 text-center border-r border-slate-100 <?= $pt200[2] !== '-' ? 'font-medium text-slate-700' : 'text-slate-300' ?>"><?= $pt200[2] ?></td>
                                    <td class="py-2 px-2 text-center border-r border-slate-100 <?= $pt200[3] !== '-' ? 'font-medium text-slate-700' : 'text-slate-300' ?>"><?= $pt200[3] ?></td>
                                    
                                    <!-- Max & Final -->
                                    <td class="py-2 px-2 text-center font-bold text-slate-600 border-r border-slate-100 bg-slate-50/50"><?= htmlspecialchars($row['to_hop_toi_uu'] ?? ($row['to_hop_xet_tuyen_id'] ?? '-')) ?></td>
                                    <td class="py-2 px-2 text-center font-bold text-slate-600 border-r border-slate-100 bg-slate-50/50"><?= $tsCode ?></td>
                                    
                                    <td class="py-2 px-2 text-center border-r border-slate-100"><?= isset($details['diem_mon_1']) ? number_format((float)$details['diem_mon_1'], 2) : '-' ?></td>
                                    <td class="py-2 px-2 text-center border-r border-slate-100"><?= isset($details['diem_mon_2']) ? number_format((float)$details['diem_mon_2'], 2) : '-' ?></td>
                                    <td class="py-2 px-2 text-center border-r border-slate-100"><?= isset($details['diem_mon_3']) ? number_format((float)$details['diem_mon_3'], 2) : '-' ?></td>
                                    
                                    <td class="py-2 px-2 text-center font-medium text-orange-600 border-r border-slate-100 bg-orange-50/20"><?= isset($details['total_raw']) ? number_format((float)$details['total_raw'], 2) : '-' ?></td>
                                    <td class="py-2 px-2 text-center text-slate-500 border-r border-slate-100"><?= isset($details['priority_raw']) ? number_format((float)$details['priority_raw'], 2) : '0' ?></td>
                                    <td class="py-2 px-2 text-center text-slate-500 border-r border-slate-100"><?= isset($details['priority_converted']) ? number_format((float)$details['priority_converted'], 2) : '0' ?></td>
                                    
                                    <td class="py-2 px-3 text-center font-black text-indigo-700 bg-indigo-50/50 border-r border-slate-100 text-[13px]"><?= $row['diem_xet_tuyen'] > 0 ? number_format((float)$row['diem_xet_tuyen'], 2) : '-' ?></td>
                                    
                                    <td class="py-2 px-2 text-center border-r border-slate-100">
                                        <?php if(isset($details['threshold_note']) && strpos($details['threshold_note'], 'HỌC LỰC') !== false): ?>
                                            <span class="text-rose-500 text-[10px] font-bold" title="Không đủ điều kiện học lực"><i class="fas fa-times"></i></span>
                                        <?php else: ?>
                                            <span class="text-emerald-500 text-[10px]"><i class="fas fa-check"></i> Đạt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2 px-2 text-center border-r border-slate-100">
                                        <?php if(isset($details['threshold_note']) && strpos($details['threshold_note'], 'NGƯỠNG') !== false): ?>
                                            <span class="text-rose-500 text-[10px] font-bold" title="Không đủ ngưỡng nhận hồ sơ"><i class="fas fa-times"></i></span>
                                        <?php else: ?>
                                            <span class="text-emerald-500 text-[10px]"><i class="fas fa-check"></i> Đạt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2 px-2 text-center border-r border-slate-100 <?= $trangThaiClass ?> text-[10px] rounded-r uppercase font-bold">
                                        <?= htmlspecialchars($trangThaiText) ?>
                                        <?php if($isThresholdFail && !$isPass): ?>
                                            <div class="text-[8px] text-rose-500 mt-0.5 whitespace-normal leading-tight max-w-[100px] mx-auto font-normal"><?= htmlspecialchars($details['threshold_note'] ?? 'Chưa đạt điều kiện') ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                        <?php 
                            endforeach; 
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
