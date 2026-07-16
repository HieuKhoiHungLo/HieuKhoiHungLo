<style>
    /* Hide spin buttons for input type number */
    #form_thpt input::-webkit-outer-spin-button,
    #form_thpt input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    #form_thpt input[type=number] { -moz-appearance: textfield; }
</style>
<!-- Edit Form (Redesigned for Visual Parity) -->
<div id="form_thpt" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible">
        <input type="hidden" name="application_id" value="<?= $user['application_id'] ?? '' ?>">
        <?php
            $hasScores = (isset($diemThi['da_co_diem']) && $diemThi['da_co_diem']);
            if (!$hasScores && !empty($diemThi)) {
                foreach(['toan','van','ly','hoa','sinh','su','dia','gdcd','tieng_anh','tieng_trung','ktpl','tin_hoc','cnnn'] as $c) {
                    if(!empty($diemThi[$c])) { $hasScores = true; break; }
                }
            }
        ?>
        
        <div style="padding: 2px;">
            <div class="transition-all duration-300">
                <div class="overflow-x-auto border border-slate-200 rounded-xl mb-1">
                    <table class="w-full text-left border-collapse" style="font-size: 11px;">
                        <thead>
                            <tr style="background:#f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 5px 6px; text-align: center; font-weight: 700; font-size: 10px; color:#000; border-right: 1px solid #e2e8f0; width: 60px;">STT</th>
                                <th style="padding: 5px 6px; text-align: left; font-weight: 700; font-size: 10px; color:#000; border-right: 1px solid #e2e8f0;">
                                    <div class="flex items-center justify-between">
                                        <span>Môn học</span>
                                        <label class="flex items-center cursor-pointer scale-75 origin-right mr-[-10px] gap-1" title="Nhập điểm thi THPT">
                                            <span class="text-[9px] text-slate-400 font-normal">Có điểm:</span>
                                            <div class="relative">
                                                <input type="checkbox" name="has_scores" value="1" <?= $hasScores ? 'checked' : '' ?> class="sr-only peer" onchange="toggleThptInputs(this.checked)">
                                                <div class="w-8 h-4.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-[#0066FF]"></div>
                                            </div>
                                        </label>
                                    </div>
                                </th>
                                <th style="padding: 5px 6px; text-align: center; font-weight: 700; font-size: 10px; color:#000; width: 120px;">Điểm số</th>
                            </tr>
                        </thead>
                        <tbody id="thpt_input_container" class="<?= $hasScores ? '' : 'opacity-40 pointer-events-none' ?> transition-all duration-300">
                            <?php 
                                $thptSubjects = [
                                    'toan'=>'Toán học', 'van'=>'Ngữ văn', 'ly'=>'Vật lý', 'hoa'=>'Hóa học', 
                                    'sinh'=>'Sinh học', 'su'=>'Lịch sử', 'dia'=>'Địa lý', 'gdcd'=>'GDCD',
                                    'tieng_anh'=>'Tiếng Anh', 'tieng_trung'=>'Tiếng Trung', 'ktpl'=>'KTPL',
                                    'tin_hoc'=>'Tin học', 'cnnn'=>'CN-NN'
                                ];
                                $rowIdx = 1;
                                foreach($thptSubjects as $code => $label): 
                                    $val = isset($diemThi[$code]) ? $diemThi[$code] : '';
                                    if ($val !== '' && is_numeric($val)) $val = number_format((float)$val, 2, '.', '');
                            ?>
                                <tr style="border-bottom: 1px solid #e2e8f0; background: #fff;" class="hover:bg-blue-50/10 transition-colors">
                                    <td style="padding: 5px 6px; text-align: center; border-right: 1px solid #e2e8f0; color: #000; font-weight: 400;">
                                        <?= $rowIdx++ ?>
                                    </td>
                                    <td style="padding: 5px 6px; border-right: 1px solid #e2e8f0; color: #000; font-weight: 400;">
                                        <?= $label ?>
                                    </td>
                                    <td style="padding: 0; text-align: center;">
                                        <input type="number" step="0.1" min="0" max="10" 
                                            name="thpt_<?= $code ?>" 
                                            value="<?= $val ?>" 
                                            style="width: 100%; height: 26px; padding: 0; text-align: center; padding-left: 0; padding-right: 0; border: 1px solid transparent; background: transparent; font-size: 11px; font-weight: 400; color: #000; outline: none; transition: all 0.2s;" 
                                            class="hover:bg-blue-50/50 focus:border-[#0066FF] focus:bg-white focus:ring-0 placeholder-slate-300"
                                            placeholder="—">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php 
                                $valXTN = isset($diemThi['diem_xet_tot_nghiep']) ? $diemThi['diem_xet_tot_nghiep'] : '';
                                if ($valXTN !== '' && is_numeric($valXTN)) $valXTN = number_format((float)$valXTN, 2, '.', '');
                            ?>
                            <tr style="border-top: 2px solid #e2e8f0; background: #f0f9ff;" class="hover:bg-blue-50/20 transition-colors">
                                <td style="padding: 5px 6px; text-align: center; border-right: 1px solid #e2e8f0; color: #0369a1; font-weight: 700;">
                                    <i class="fas fa-star"></i>
                                </td>
                                <td style="padding: 5px 6px; border-right: 1px solid #e2e8f0; color: #0369a1; font-weight: 700; text-transform: uppercase; font-size: 10px; letter-spacing: 0.05em;">
                                    Điểm xét tốt nghiệp
                                </td>
                                <td style="padding: 0; text-align: center;">
                                    <input type="number" step="0.01" min="0" max="10" 
                                        name="thpt_diem_xet_tot_nghiep" 
                                        value="<?= $valXTN ?>" 
                                        style="width: 100%; height: 26px; padding: 0; text-align: center; border: 1px solid transparent; background: transparent; font-size: 12px; font-weight: 800; color: #0369a1; outline: none;" 
                                        class="hover:bg-blue-50/50 focus:border-[#0066FF] focus:bg-white focus:ring-0 placeholder-slate-300"
                                        placeholder="0.00">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Footer Buttons aligned with Academic tab style -->
            <div style="margin-top: 0; padding: 6px 4px 4px; border-top: 1px solid #f1f5f9;">
                <div class="flex items-center justify-end gap-2">
                    <button type="button" onclick="toggleEdit('thpt')" 
                        class="px-4 py-1.5 bg-white text-slate-500 border border-slate-200 rounded-xl shadow-sm hover:bg-slate-50 transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    
                    <button type="button" onclick="saveSection('thpt')" 
                        class="px-4 py-1.5 bg-white text-[#0066FF] border border-[#0066FF]/20 rounded-xl shadow-sm hover:bg-[#0066FF] hover:text-white transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                        <i class="fas fa-save"></i> Lưu dữ liệu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleThptInputs(checked) {
    const container = document.getElementById('thpt_input_container');
    if (checked) {
        container.classList.remove('opacity-40', 'pointer-events-none');
    } else {
        container.classList.add('opacity-40', 'pointer-events-none');
    }
}

function previewThptCert(input) {
    const preview = document.getElementById('preview_thpt_cert');
    const placeholder = document.getElementById('placeholder_thpt_cert');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
