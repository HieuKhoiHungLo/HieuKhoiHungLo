<?php
$mapDisplay = function($v) {
    return [
        'Gioi' => 'Giỏi', 'Kha' => 'Khá', 'TrungBinh' => 'T.Bình', 'Yeu' => 'Yếu',
        'Tot' => 'Tốt'
    ][$v] ?? $v;
};
?>
<style>
    /* Hide spin buttons for input type number */
    #form_certs input::-webkit-outer-spin-button,
    #form_certs input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    #form_certs input[type=number] { -moz-appearance: textfield; }
</style>

<!-- Edit Form (Stable Table Format) -->
<div id="form_certs" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible">
        <input type="hidden" name="application_id" value="<?= $user['application_id'] ?? '' ?>">

        <div class="p-4">
            <div class="overflow-x-auto text-xs border border-slate-200 rounded-xl mb-6">
                <table class="w-full text-left border-collapse" style="font-size: 12px;">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th style="padding: 20px !important; text-align: center; font-weight: 600; color:#000; border-right: 1px solid #e2e8f0; width: 60px; min-width: 60px;">STT</th>
                                <th style="padding: 20px !important; text-align: left; font-weight: 600; color:#000; border-right: 1px solid #e2e8f0; width: 180px; min-width: 180px;">Loại chứng chỉ</th>
                                <th style="padding: 20px !important; text-align: center; font-weight: 600; color:#000; border-right: 1px solid #e2e8f0;">Điểm số / Kết quả</th>
                                <th style="padding: 20px !important; text-align: center; font-weight: 600; color:#000; width: 80px; min-width: 80px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="tab3_cert_list_tbody">
                            <?php if (!empty($certificates)): ?>
                                <?php foreach ($certificates as $index => $cert): ?>
                                    <tr class="cert-item bg-white border-b border-slate-100 last:border-b-0 hover:bg-blue-50/20 transition-all duration-300">
                                        <td style="padding: 20px !important; text-align: center; border-right: 1px solid #e2e8f0; color: #000; font-weight: 500;" class="index-cell">
                                            <?= $index + 1 ?>
                                        </td>
                                        <td style="padding: 10px 12px !important; border-right: 1px solid #e2e8f0; color: #000;">
                                            <div class="relative">
                                                <select name="certs[<?= $index ?>][type]" 
                                                    style="width: 100%; height: 40px; padding: 0 8px; border: 1px solid transparent; background: transparent; font-size: 12px; font-weight: 500; color: #000; outline: none; appearance: none; cursor: pointer; display: block; transition: all 0.2s;"
                                                    class="hover:border-slate-200 focus:border-[#0066FF] focus:bg-white focus:ring-0">
                                                    <option value="">-- Chọn --</option>
                                                    <optgroup label="Tiếng Anh">
                                                        <option value="IELTS" <?= $cert['loai_chung_chi'] == 'IELTS' ? 'selected' : '' ?>>IELTS</option>
                                                        <option value="TOEFL iBT" <?= $cert['loai_chung_chi'] == 'TOEFL iBT' ? 'selected' : '' ?>>TOEFL iBT</option>
                                                        <option value="TOEIC" <?= $cert['loai_chung_chi'] == 'TOEIC' ? 'selected' : '' ?>>TOEIC</option>
                                                    </optgroup>
                                                    <optgroup label="Ngoại ngữ khác">
                                                        <option value="HSK" <?= $cert['loai_chung_chi'] == 'HSK' ? 'selected' : '' ?>>HSK (Tiếng Trung)</option>
                                                        <option value="JLPT" <?= $cert['loai_chung_chi'] == 'JLPT' ? 'selected' : '' ?>>JLPT (Tiếng Nhật)</option>
                                                    </optgroup>
                                                    <optgroup label="Tin học">
                                                        <option value="IC3" <?= $cert['loai_chung_chi'] == 'IC3' ? 'selected' : '' ?>>IC3</option>
                                                        <option value="MOS" <?= $cert['loai_chung_chi'] == 'MOS' ? 'selected' : '' ?>>MOS</option>
                                                    </optgroup>
                                                </select>
                                                <i class="fas fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-slate-300 text-[8px] pointer-events-none"></i>
                                            </div>
                                        </td>
                                        <td style="padding: 10px 12px !important; border-right: 1px solid #e2e8f0; color: #000;">
                                            <input type="text" name="certs[<?= $index ?>][score]" value="<?= htmlspecialchars($cert['diem_chung_chi']) ?>" 
                                                placeholder="—" 
                                                style="width: 100%; height: 40px; padding: 0; text-align: center; border: 1px solid transparent; background: transparent; font-size: 12px; font-weight: 500; color: #000; outline: none; display: block; transition: all 0.2s;"
                                                class="hover:border-slate-200 focus:border-[#0066FF] focus:bg-white focus:ring-0 placeholder-slate-300">
                                        </td>
                                        <td style="padding: 20px !important; text-align: center; color: #000;">
                                            <div class="flex items-center justify-center gap-2">
                                                <label class="cursor-pointer group/upload">
                                                    <i class="fas fa-camera text-blue-600 group-hover/upload:text-blue-700 transition-colors text-xs"></i>
                                                    <input type="file" name="cert_files[<?= $index ?>]" accept="image/*" class="hidden" onchange="window.previewAdminCert(this)">
                                                    <input type="hidden" name="certs[<?= $index ?>][existing_file]" value="<?= $cert['file_minh_chung_cc'] ?>">
                                                </label>
                                                <button type="button" onclick="window.removeAdminCert(this)" class="text-red-500 hover:text-red-600 transition-colors">
                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr id="no_tab3_certs_row">
                                    <td colspan="4" style="padding: 20px; text-align: center; color: #94a3b8; font-style: italic;">
                                        Chưa có chứng chỉ ngoại ngữ / tin học
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <div class="mt-2 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <button type="button" onclick="window.handleTab3AddRow()" 
                        class="px-3 py-1.5 bg-white text-[#0066FF] border border-[#0066FF]/20 rounded-xl shadow-sm hover:bg-[#0066FF] hover:text-white transition-all flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider">
                        <i class="fas fa-plus text-[8px]"></i> Thêm chứng chỉ
                    </button>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="toggleEdit('certs')" 
                            class="px-4 py-1.5 bg-white text-slate-500 border border-slate-200 rounded-xl shadow-sm hover:bg-slate-50 transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                        <button type="button" onclick="saveSection('certs')" 
                            class="px-4 py-1.5 bg-white text-[#0066FF] border border-[#0066FF]/20 rounded-xl shadow-sm hover:bg-[#0066FF] hover:text-white transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-save"></i> Lưu dữ liệu
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
