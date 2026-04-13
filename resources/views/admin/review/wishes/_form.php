<?php
/**
 * @var array $choices List of current choices
 * @var array $majors List of all available majors
 * @var array $majorMap Map for major info lookup
 */
?>
<div id="form_wishes" class="hidden animate-in fade-in slide-in-from-top-4 duration-500">
    <div id="editForm_wishes">
        <input type="hidden" name="application_id" value="<?= $user['application_id'] ?? '' ?>">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-6">
            
            <div class="px-4 py-2 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nguyện vọng tập trung (Tối đa 06)</span>
            </div>

            <div class="p-0 overflow-x-auto text-xs">
                <table class="w-full text-left border-collapse" id="adminChoicesTable">
                    <thead class="bg-slate-50/50 text-slate-500 uppercase leading-normal text-[10px] font-medium tracking-widest border-b border-slate-100">
                        <tr>
                            <th style="padding: 12px 10px; text-align: center; border-right: 1px solid #e2e8f0; width: 45px;">STT</th>
                            <th style="padding: 12px 10px; border-right: 1px solid #e2e8f0;">Ngành xét tuyển</th>
                            <th style="padding: 12px 10px; text-align: center; border-right: 1px solid #e2e8f0; width: 220px;">Tổ hợp môn</th>
                            <th style="padding: 12px 10px; text-align: center; width: 60px;">Tác vụ</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#000] text-[12px]">
                        <?php if (empty($choices)): ?>
                            <tr class="empty-row border-b border-slate-50">
                                <td colspan="4" class="py-16 text-center text-slate-400 font-medium italic">Chưa có nguyện vọng nào. Bấm "Thêm nguyện vọng" bên dưới.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($choices as $index => $wish): 
                                $maNganh = $wish['ma_nganh'];
                                $majorInfo = $majorMap[$maNganh] ?? null;
                            ?>
                                <tr class="choice-row border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                    <td style="padding: 12px 10px; text-align: center; border-right: 1px solid #e2e8f0; color: #000; font-weight: 400;"><?= $index + 1 ?></td>
                                    <td style="padding: 12px 10px; border-right: 1px solid #e2e8f0;">
                                        <input type="hidden" name="choices[<?= $index ?>][thu_tu]" value="<?= $index + 1 ?>">
                                        <div class="relative">
                                            <select name="choices[<?= $index ?>][nganh_id]" onchange="v5_update_combo(this, <?= $index ?>)" 
                                                class="w-full py-1.5 px-2 bg-white border border-slate-200 rounded-md pr-8 focus:ring-1 focus:ring-slate-200 focus:border-slate-400 outline-none text-[#000] transition-all appearance-none shadow-sm">
                                                <option value="">-- Chọn ngành --</option>
                                                <?php foreach ($majors as $m): ?>
                                                    <option value="<?= $m['ma_nganh'] ?>" <?= $wish['ma_nganh'] == $m['ma_nganh'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($m['ten_nganh']) ?> (<?= $m['ma_nganh'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 10px; border-right: 1px solid #e2e8f0; text-align: center;">
                                        <div id="v5-combo-<?= $index ?>" class="admin-combo-display text-[#000]">
                                            <?php 
                                            // Show plain text as requested
                                            $displayCombos = !empty($wish['to_hop_mon']) ? $wish['to_hop_mon'] : ($majorInfo['to_hop_xet_tuyen'] ?? '');
                                            echo !empty($displayCombos) ? htmlspecialchars($displayCombos) : '<span class="text-[10px] text-slate-300 italic">N/A</span>';
                                            ?>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 10px; text-align: center;">
                                        <button type="button" onclick="v5_row_remove(this)" 
                                            class="w-7 h-7 rounded border border-slate-100 text-rose-500 bg-rose-50/30 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm flex items-center justify-center mx-auto" title="Xóa ngành này">
                                            <i class="fas fa-trash-alt text-[10px]"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer Buttons -->
            <div style="margin-top: 0; padding: 12px 16px; border-top: 1px solid #f1f5f9; background: #fafafa;">
                <div class="flex items-center justify-between">
                    <button type="button" onclick="v5_row_add()" 
                        class="px-4 py-1.5 bg-white text-slate-500 border border-slate-200 rounded-xl shadow-sm hover:bg-slate-50 transition-all flex items-center gap-2 text-[10px] font-black uppercase tracking-wider">
                        <i class="fas fa-plus"></i> Thêm nguyện vọng
                    </button>
                    
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="toggleEdit('wishes')" 
                            class="px-5 py-1.5 bg-white text-slate-500 border border-slate-200 rounded-xl shadow-sm hover:bg-slate-50 transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                        
                        <button type="button" onclick="saveSection('wishes')" 
                            class="px-5 py-1.5 bg-white text-[#0066FF] border border-[#0066FF]/20 rounded-xl shadow-sm hover:bg-[#0066FF] hover:text-white transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-save"></i> Lưu dữ liệu
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row Template for Admin -->
<template id="v5RowTemplate">
    <tr class="choice-row border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
        <td style="padding: 12px 10px; text-align: center; border-right: 1px solid #e2e8f0; color: #000; font-weight: 400;">STT_VAL</td>
        <td style="padding: 12px 10px; border-right: 1px solid #e2e8f0;">
            <input type="hidden" name="choices[INDEX_VAL][thu_tu]" value="STT_VAL">
            <div class="relative">
                <select name="choices[INDEX_VAL][nganh_id]" onchange="v5_update_combo(this, INDEX_VAL)" 
                    class="w-full py-1.5 px-2 bg-white border border-slate-200 rounded-md outline-none text-[#000] transition-all appearance-none shadow-sm pr-8" required>
                    <option value="">-- Chọn ngành --</option>
                    <?php foreach ($majors as $m): ?>
                        <option value="<?= $m['ma_nganh'] ?>"><?= htmlspecialchars($m['ten_nganh']) ?> (<?= $m['ma_nganh'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
            </div>
        </td>
        <td style="padding: 12px 10px; border-right: 1px solid #e2e8f0; text-align: center;">
            <div id="v5-combo-INDEX_VAL" class="admin-combo-display text-[#000]">
                <span class="text-[10px] text-slate-300 italic">N/A</span>
            </div>
        </td>
        <td style="padding: 12px 10px; text-align: center;">
            <button type="button" onclick="v5_row_remove(this)" 
                class="w-7 h-7 mx-auto rounded border border-slate-100 text-rose-500 bg-rose-50/30 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm flex items-center justify-center">
                <i class="fas fa-trash-alt text-[10px]"></i>
            </button>
        </td>
    </tr>
</template>
