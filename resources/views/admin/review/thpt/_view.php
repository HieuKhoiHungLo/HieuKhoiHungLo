<!-- View Mode -->
<div id="view_thpt" class="animate-in fade-in duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible">
        <input type="hidden" name="application_id" value="<?= $user['application_id'] ?? '' ?>">
        
        <div style="padding: 2px;">
            <?php 
                $hasData = ($diemThi['da_co_diem'] ?? false);
                if (!$hasData && !empty($diemThi)) {
                   foreach(['toan','van','ly','hoa','sinh','su','dia','gdcd','tieng_anh','tieng_trung','ktpl','tin_hoc','cnnn'] as $c) {
                       if(!empty($diemThi[$c])) { $hasData = true; break; }
                   }
                }
            ?>
            <?php if(!$hasData): ?>
                <div class="flex flex-col items-center justify-center py-12 bg-slate-50 border-2 border-dashed border-slate-100 rounded-xl mb-4">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-slate-200 mb-3 shadow-sm">
                        <i class="fas fa-ghost text-2xl"></i>
                    </div>
                    <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest text-center">Không có dữ liệu điểm thi tốt nghiệp THPT</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto border border-slate-200 rounded-xl mb-1">
                    <table class="w-full text-left border-collapse" style="font-size: 11px;">
                        <thead>
                            <tr style="background:#f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 5px 6px; text-align: center; font-weight: 700; font-size: 10px; color:#000; border-right: 1px solid #e2e8f0; width: 60px;">STT</th>
                                <th style="padding: 5px 6px; text-align: left; font-weight: 700; font-size: 10px; color:#000; border-right: 1px solid #e2e8f0;">Môn học</th>
                                <th style="padding: 5px 6px; text-align: center; font-weight: 700; font-size: 10px; color:#000; width: 120px;">Điểm số</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $thptSubjects = [
                                    'toan'=>'Toán học', 'van'=>'Ngữ văn', 'ly'=>'Vật lý', 'hoa'=>'Hóa học', 
                                    'sinh'=>'Sinh học', 'su'=>'Lịch sử', 'dia'=>'Địa lý', 'gdcd'=>'GDCD',
                                    'tieng_anh'=>'Tiếng Anh', 'tieng_trung'=>'Tiếng Trung', 'ktpl'=>'KTPL',
                                    'tin_hoc'=>'Tin học', 'cnnn'=>'CN-NN'
                                ];
                                $rowIdx = 1;
                                foreach($thptSubjects as $code => $label): 
                                    $val = $diemThi[$code] ?? null;
                                    if($val === null || $val === '') continue;
                                    $displayVal = is_numeric($val) ? str_replace('.', ',', number_format((float)$val, 2, '.', '')) : $val;
                            ?>
                                <tr style="border-bottom: 1px solid #e2e8f0; background: #fff;" class="hover:bg-blue-50/10 transition-colors">
                                    <td style="padding: 5px 6px; text-align: center; border-right: 1px solid #e2e8f0; color: #000; font-weight: 400;">
                                        <?= $rowIdx++ ?>
                                    </td>
                                    <td style="padding: 5px 6px; border-right: 1px solid #e2e8f0; color: #000; font-weight: 400;">
                                        <?= $label ?>
                                    </td>
                                    <td style="padding: 5px 6px; text-align: center; color: #000; font-weight: 400;">
                                        <?= $displayVal ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php 
                                $valXTN = $diemThi['diem_xet_tot_nghiep'] ?? null;
                                if($valXTN !== null && $valXTN !== ''):
                                    $displayXTN = is_numeric($valXTN) ? str_replace('.', ',', number_format((float)$valXTN, 2, '.', '')) : $valXTN;
                            ?>
                                <tr style="border-top: 2px solid #e2e8f0; background: #f0f9ff;" class="hover:bg-blue-50/20 transition-colors">
                                    <td style="padding: 5px 6px; text-align: center; border-right: 1px solid #e2e8f0; color: #0369a1; font-weight: 700;">
                                        <i class="fas fa-star"></i>
                                    </td>
                                    <td style="padding: 5px 6px; border-right: 1px solid #e2e8f0; color: #0369a1; font-weight: 700; text-transform: uppercase; font-size: 10px; letter-spacing: 0.05em;">
                                        Điểm xét tốt nghiệp
                                    </td>
                                    <td style="padding: 5px 6px; text-align: center; color: #0369a1; font-weight: 800; font-size: 13px;">
                                        <?= $displayXTN ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Dòng cuối: Trạng thái & Nút sửa -->
            <div style="margin-top: 0; padding: 3px 4px 2px; border-top: 1px solid #f1f5f9;">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3">
                        <?php
                        $currentStatus = $user['trang_thai_thpt'] ?? '';
                        $isRejected = ($currentStatus === 'Từ chối');
                        ?>
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="status_thpt" value="approved" <?= !$isRejected ? 'checked' : '' ?> onchange="document.getElementById('reason_thpt_container').classList.add('hidden')" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                            <span class="ml-1.5 text-xs font-bold text-emerald-700">Duyệt</span>
                        </label>
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="status_thpt" value="rejected" <?= $isRejected ? 'checked' : '' ?> onchange="document.getElementById('reason_thpt_container').classList.remove('hidden')" class="w-4 h-4 text-rose-600 focus:ring-rose-500 border-gray-300">
                            <span class="ml-1.5 text-xs font-bold text-rose-700">Yêu cầu sửa</span>
                        </label>
                    </div>

                    <button type="button" onclick="toggleEdit('thpt')" 
                        class="px-4 py-1.5 bg-white text-[#0066FF] border border-[#0066FF]/20 rounded-xl shadow-sm hover:bg-[#0066FF] hover:text-white transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                        <i class="fas fa-edit"></i> Sửa thông tin
                    </button>
                </div>

                <!-- Lý do từ chối -->
                <div class="<?= $isRejected ? '' : 'hidden' ?>" id="reason_thpt_container">
                    <textarea name="note_thpt" class="w-full text-xs border border-slate-200 rounded-xl p-3 focus:ring-rose-500 focus:border-rose-500 bg-rose-50/30" rows="2" placeholder="Nhập lý do sai sót/cần bổ sung..."><?= htmlspecialchars($user['ghi_chu_thpt'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
