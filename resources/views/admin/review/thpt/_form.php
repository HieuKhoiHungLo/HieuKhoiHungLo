<!-- Edit Form (Redesigned Grid) -->
<div id="form_thpt" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible">
        <input type="hidden" name="application_id" value="<?= $user['application_id'] ?? '' ?>">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60 rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#0066FF]/10 text-[#0066FF] flex items-center justify-center">
                    <i class="fas fa-edit text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Chỉnh sửa</p>
                    <p class="text-sm font-bold text-slate-700">Điểm thi tốt nghiệp THPT</p>
                </div>
            </div>
            
            <label class="flex items-center cursor-pointer group bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm">
                <span class="mr-3 text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-[#0066FF] transition-colors">Đã có kết quả thi</span>
                <div class="relative">
                    <input type="checkbox" name="has_scores" value="1" <?= (isset($diemThi['da_co_diem']) && $diemThi['da_co_diem']) ? 'checked' : '' ?> class="sr-only peer" onchange="toggleThptInputs(this.checked)">
                    <div class="w-10 h-5.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4.5 after:w-4.5 after:transition-all peer-checked:bg-[#0066FF]"></div>
                </div>
            </label>
        </div>

        <div class="p-6">
            <div id="thpt_input_container" class="<?= (isset($diemThi['da_co_diem']) && $diemThi['da_co_diem']) ? '' : 'opacity-40 pointer-events-none' ?> transition-all duration-300">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4 mb-8">
                    <?php 
                    $subjects = [
                        'toan' => 'Toán', 'van' => 'Văn', 'ly' => 'Lí', 'hoa' => 'Hóa', 'sinh' => 'Sinh',
                        'su' => 'Sử', 'dia' => 'Địa', 'gdcd' => 'GDCD', 'tieng_anh' => 'T.Anh', 'tieng_trung' => 'T.Trung',
                        'ktpl' => 'Kinh tế PL', 'tin_hoc' => 'Tin học', 'cnnn' => 'CN Nông nghiệp'
                    ];
                    foreach ($subjects as $key => $name): ?>
                        <div class="space-y-1.5">
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest px-1"><?= $name ?></label>
                            <input type="number" step="0.01" min="0" max="10" 
                                name="thpt_<?= $key ?>" 
                                value="<?= isset($diemThi[$key]) ? $diemThi[$key] : '' ?>" 
                                class="w-full h-11 bg-white border border-slate-200 rounded-xl px-3 text-center font-bold text-slate-800 focus:bg-white focus:ring-4 focus:ring-blue-50 focus:border-[#0066FF] outline-none transition-all shadow-sm" 
                                placeholder="0.0">
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Evidence Upload -->
                <div class="space-y-4 pt-8 border-t border-slate-100">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest">
                        <i class="fas fa-file-invoice mr-1 text-[#0066FF]"></i> Giấy chứng nhận kết quả thi
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                        <div class="relative group aspect-video bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 overflow-hidden hover:border-[#0066FF] transition-all shadow-inner">
                            <img id="preview_thpt_cert" src="<?= !empty($diemThi['file_chung_nhan']) ? url($diemThi['file_chung_nhan']) : '' ?>" class="w-full h-full object-contain <?= !empty($diemThi['file_chung_nhan']) ? '' : 'hidden' ?>">
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-300 <?= !empty($diemThi['file_chung_nhan']) ? 'hidden' : '' ?>" id="placeholder_thpt_cert">
                                <i class="fas fa-cloud-upload-alt text-3xl mb-2 group-hover:text-[#0066FF] transition-colors"></i>
                                <span class="text-[10px] font-black uppercase tracking-widest group-hover:text-[#0066FF] transition-colors">Tải lên minh chứng</span>
                            </div>
                            <input type="file" name="thpt_file_evidence" accept="image/*" onchange="previewThptCert(this)" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                        </div>
                        <div class="bg-blue-50/40 p-6 rounded-2xl border border-blue-100">
                            <h4 class="text-[11px] font-black text-[#0066FF] uppercase tracking-widest mb-3 flex items-center gap-2">
                                <i class="fas fa-info-circle"></i> Hướng dẫn nhập điểm
                            </h4>
                            <ul class="text-xs text-slate-600 space-y-2.5 font-medium">
                                <li class="flex items-start gap-2.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#0066FF] mt-1.5 flex-shrink-0 shadow-sm shadow-blue-200"></span>
                                    Nhập điểm theo đúng Giấy chứng nhận kết quả thi.
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#0066FF] mt-1.5 flex-shrink-0 shadow-sm shadow-blue-200"></span>
                                    Ảnh minh chứng cần rõ nét, không bị lóa hoặc che khuất thông tin.
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#0066FF] mt-1.5 flex-shrink-0 shadow-sm shadow-blue-200"></span>
                                    Nếu chưa có điểm, hãy tắt nút gạt "Đã có kết quả thi".
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-8 border-t border-slate-100 mt-8">
                <button type="button" onclick="toggleEdit('thpt')" 
                    class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-all border border-slate-200 uppercase tracking-widest">Hủy bỏ</button>
                <button type="button" onclick="saveSection('thpt')" 
                    class="px-8 py-2.5 bg-[#0066FF] text-white font-bold text-sm rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center gap-2 uppercase tracking-widest">
                    <i class="fas fa-save"></i> Lưu
                </button>
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
