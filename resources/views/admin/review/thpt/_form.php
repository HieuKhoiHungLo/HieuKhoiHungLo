<!-- Edit Form (Redesigned Grid) -->
<div id="form_thpt" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-[2rem] p-8 border border-blue-100 shadow-xl shadow-blue-50/50">
        <input type="hidden" name="application_id" value="<?= $user['application_id'] ?? '' ?>">
        
        <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#0066FF] shadow-sm">
                    <i class="fas fa-edit"></i>
                </div>
                <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Chỉnh sửa Điểm thi THPT</h3>
            </div>
            
            <label class="flex items-center cursor-pointer group bg-slate-50 px-4 py-2 rounded-xl border border-slate-100">
                <span class="mr-3 text-xs font-black text-slate-400 uppercase tracking-wider group-hover:text-blue-500 transition-colors">Đã có kết quả thi</span>
                <div class="relative">
                    <input type="checkbox" name="has_scores" value="1" <?= (isset($diemThi['da_co_diem']) && $diemThi['da_co_diem']) ? 'checked' : '' ?> class="sr-only peer" onchange="toggleThptInputs(this.checked)">
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </div>
            </label>
        </div>

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
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest px-1"><?= $name ?></label>
                        <input type="number" step="0.01" min="0" max="10" 
                               name="thpt_<?= $key ?>" 
                               value="<?= isset($diemThi[$key]) ? $diemThi[$key] : '' ?>" 
                               class="w-full h-11 bg-slate-50 border border-slate-100 rounded-xl px-3 text-center font-black text-slate-700 focus:bg-white focus:ring-4 focus:ring-blue-50 focus:border-blue-400 outline-none transition-all" 
                               placeholder="0.0">
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Evidence Upload -->
            <div class="space-y-4">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Giấy chứng nhận kết quả thi</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                    <div class="relative group aspect-video bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 overflow-hidden hover:border-blue-400 transition-all">
                        <img id="preview_thpt_cert" src="<?= !empty($diemThi['file_chung_nhan']) ? url($diemThi['file_chung_nhan']) : '' ?>" class="w-full h-full object-contain <?= !empty($diemThi['file_chung_nhan']) ? '' : 'hidden' ?>">
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 <?= !empty($diemThi['file_chung_nhan']) ? 'hidden' : '' ?>" id="placeholder_thpt_cert">
                            <i class="fas fa-file-upload text-3xl mb-2"></i>
                            <span class="text-[10px] font-bold uppercase">Nhấn để tải lên minh chứng</span>
                        </div>
                        <input type="file" name="thpt_file_evidence" accept="image/*" onchange="previewThptCert(this)" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                    </div>
                    <div class="bg-blue-50/50 p-6 rounded-2xl border border-blue-100">
                        <h4 class="text-xs font-black text-blue-600 uppercase mb-2 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i> Lưu ý hướng dẫn
                        </h4>
                        <ul class="text-[11px] text-slate-500 space-y-2 font-medium">
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-300 mt-1 flex-shrink-0"></span>
                                Nhập điểm theo đúng Giấy chứng nhận kết quả thi.
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-300 mt-1 flex-shrink-0"></span>
                                Ảnh minh chứng cần rõ nét, không bị lóa hoặc che khuất thông tin.
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-300 mt-1 flex-shrink-0"></span>
                                Nếu thí sinh chưa có điểm, vui lòng tắt nút gạt "Đã có kết quả thi".
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-8 border-t border-slate-100 mt-8">
            <button type="button" onclick="toggleEdit('thpt')" class="px-6 py-3 text-slate-500 font-bold text-sm uppercase tracking-wider hover:bg-slate-50 rounded-xl transition-colors">Hủy bỏ </button>
            <button type="button" onclick="saveSection('thpt')" class="px-6 py-3 bg-[#0066FF] text-white font-bold text-sm uppercase tracking-wider rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">Lưu thay đổi</button>
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
