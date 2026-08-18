<!-- View Mode -->
<div id="view_academic" class="animate-in fade-in duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible relative">
        <div style="padding: 2px;">

            <!-- Scores Table -->
            <div class="rounded-xl border border-slate-200 overflow-hidden">
                <?php
                $rowsByGrade = [];
                if (isset($academicRows) && is_array($academicRows)) {
                    foreach ($academicRows as $r) {
                        $rowsByGrade[$r['lop']] = $r;
                    }
                }
                $getScore = function ($grade, $field) use ($rowsByGrade) {
                    $val = $rowsByGrade[$grade][$field] ?? null;
                    
                    // Fallback for GDCD/KTPL: if field is diem_gdcd_cn and it's null, try diem_ktpl_cn
                    if ($field === 'diem_gdcd_cn' && ($val === null || $val === '')) {
                        $val = $rowsByGrade[$grade]['diem_ktpl_cn'] ?? null;
                    }

                    if ($val === null || $val === '') return '<span class="text-slate-300">—</span>';
                    if (is_numeric($val)) return str_replace('.', ',', number_format((float)$val, 3, '.', ''));
                    return $val;
                };
                $mapDisplay = function ($val) {
                    // Data is already normalized to TỐT, ĐẠT, TRUNG BÌNH, CHƯA ĐẠT in DB
                    return ($val !== null && $val !== '') ? $val : '<span class="text-slate-300">—</span>';
                };
                ?>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse" style="font-size: 11px;">
                        <thead>
                            <tr style="background:#f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 5px 6px; text-align: left; font-weight: 600; font-size: 10px; color:#000; border-right: 1px solid #e2e8f0; width: 120px;">Môn học</th>
                                <?php foreach ([10 => 'Lớp 10', 11 => 'Lớp 11', 12 => 'Lớp 12'] as $g => $label): ?>
                                    <th style="padding: 5px 4px; text-align: center; font-weight: 600; font-size: 10px; color:#000; border-right: 1px solid #e2e8f0;">
                                        <?= $label ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $code => $name): ?>
                                <tr style="border-bottom: 1px solid #e2e8f0; background: #fff;">
                                    <td style="padding: 5px 6px; color: #000; font-weight: 400; border-right: 1px solid #e2e8f0;"><?= $name ?></td>
                                    <?php foreach ([10, 11, 12] as $g): ?>
                                        <td style="padding: 5px 4px; text-align: center; border-right: 1px solid #e2e8f0; font-weight: 400; color: #000; background: #fff;">
                                            <?= $getScore($g, "diem_{$code}_cn") ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background: #fff; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 5px 6px; color: #000; font-weight: 500; border-right: 1px solid #e2e8f0; font-size: 11px;">TB chung</td>
                                <?php foreach ([10, 11, 12] as $g): ?>
                                    <td style="padding: 5px 4px; text-align: center; border-right: 1px solid #e2e8f0; color: #000; font-weight: 500; background: #fff;">
                                        <?= $getScore($g, 'diem_tb_ca_nam') ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <tr style="background: #fff; border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 5px 6px; color: #000; font-weight: 400; border-right: 1px solid #e2e8f0;">Học lực</td>
                                <?php foreach ([10, 11, 12] as $g): ?>
                                    <td style="padding: 5px 4px; text-align: center; border-right: 1px solid #e2e8f0; font-weight: 400; color: #000; background: #fff;">
                                        <?= $mapDisplay($getScore($g, 'hoc_luc_ca_nam')) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <tr style="background: #fff;">
                                <td style="padding: 5px 6px; color: #000; font-weight: 400; border-right: 1px solid #e2e8f0;">Hạnh kiểm</td>
                                <?php foreach ([10, 11, 12] as $g): ?>
                                    <td style="padding: 5px 4px; text-align: center; border-right: 1px solid #e2e8f0; font-weight: 400; color: #000; background: #fff;">
                                        <?= $mapDisplay($getScore($g, 'hanh_kiem_ca_nam')) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Dòng cuối: Trạng thái & Nút sửa -->
            <div style="margin-top: 0; padding: 3px 4px 2px; border-top: 1px solid #f1f5f9;">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3">
                        <!-- Biểu tượng đủ 3 năm -->
                        <?php if (isset($user['da_du_6_ky']) && $user['da_du_6_ky']): ?>
                            <span title="Đủ điểm 3 năm" style="color:#059669; font-size:15px; line-height:1;"><i class="fas fa-check-circle"></i></span>
                        <?php else: ?>
                            <span title="Chưa đủ điểm 3 năm" style="color:#d97706; font-size:15px; line-height:1;"><i class="fas fa-exclamation-circle"></i></span>
                        <?php endif; ?>
                        <div class="flex items-center gap-3">
                            <?php
                            $currentStatus = $user['trang_thai_hoc_ba'] ?? '';
                            $isRejected = ($currentStatus === 'Từ chối');
                            ?>
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" name="status_academic" value="approved" <?= !$isRejected ? 'checked' : '' ?>
                                    onchange="document.getElementById('reason_academic_container').classList.add('hidden')"
                                    class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                                <span class="ml-1.5 text-xs font-bold text-emerald-700">Duyệt</span>
                            </label>
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" name="status_academic" value="rejected" <?= $isRejected ? 'checked' : '' ?>
                                    onchange="document.getElementById('reason_academic_container').classList.remove('hidden')"
                                    class="w-4 h-4 text-rose-600 focus:ring-rose-500 border-gray-300">
                                <span class="ml-1.5 text-xs font-bold text-rose-700">Yêu cầu sửa</span>
                            </label>
                        </div>
                    </div>
                    <?php if (!empty($isScoreLocked)): ?>
                        <span class="px-3 py-1.5 bg-slate-100 text-slate-500 rounded-xl text-xs font-bold flex items-center gap-1.5" title="Đợt này đã khóa chỉnh sửa điểm hoặc hồ sơ có điểm từ Bộ">
                            <i class="fas fa-lock text-slate-400"></i> Đã khóa điểm
                        </span>
                    <?php else: ?>
                        <button type="button" onclick="toggleEdit('academic')"
                            class="px-4 py-1.5 bg-white text-[#0066FF] border border-[#0066FF]/20 rounded-xl shadow-sm hover:bg-[#0066FF] hover:text-white transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-edit"></i> Sửa thông tin
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Lý do từ chối -->
                <div class="<?= $isRejected ? '' : 'hidden' ?>" id="reason_academic_container">
                    <textarea name="note_academic" class="w-full text-xs border border-slate-200 rounded-xl p-3 focus:ring-rose-500 focus:border-rose-500 bg-rose-50/30" rows="2" placeholder="Nhập lý do sai sót/cần bổ sung..."><?= htmlspecialchars($user['ghi_chu_hoc_ba'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>