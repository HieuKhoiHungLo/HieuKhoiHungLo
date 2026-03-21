<!-- Edit Form (Dynamic Certs) -->
<div id="form_certs" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
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
                    <p class="text-sm font-bold text-slate-700">Quản lý chứng chỉ</p>
                </div>
            </div>
            <button type="button" onclick="addAdminCertRow()" 
                class="px-4 py-2 bg-[#0066FF] text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-md shadow-blue-100 flex items-center gap-2">
                <i class="fas fa-plus"></i> Thêm mới
            </button>
        </div>

        <div class="p-6">
            <div id="admin_cert_list" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php if (!empty($certificates)): ?>
                    <?php foreach ($certificates as $index => $cert): ?>
                        <div class="cert-item bg-white border border-slate-200 rounded-2xl p-5 relative group/item shadow-sm">
                            <button type="button" onclick="removeAdminCert(this)" 
                                class="absolute -top-2 -right-2 w-7 h-7 bg-white border border-slate-200 rounded-full text-slate-300 hover:text-red-500 hover:border-red-100 hover:shadow-sm transition-all flex items-center justify-center">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                            <div class="grid grid-cols-1 gap-5">
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Loại chứng chỉ</label>
                                    <div class="relative">
                                        <select name="certs[<?= $index ?>][type]" 
                                            class="w-full h-11 bg-slate-50/50 border border-slate-200 rounded-xl px-4 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 outline-none font-semibold text-slate-700 transition-all appearance-none">
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
                                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-[10px] pointer-events-none"></i>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Điểm / Kết quả</label>
                                    <input type="text" name="certs[<?= $index ?>][score]" value="<?= htmlspecialchars($cert['diem_chung_chi']) ?>" 
                                        placeholder="VD: 6.5, 450, Đạt..." 
                                        class="w-full h-11 bg-slate-50/50 border border-slate-200 rounded-xl px-4 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 outline-none font-semibold text-slate-700 transition-all shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Minh chứng (Ảnh)</label>
                                    <div class="flex items-center space-x-3">
                                        <label class="flex-grow cursor-pointer group/upload">
                                            <div class="h-11 px-4 border border-slate-200 rounded-xl flex items-center bg-slate-50/50 group-hover/upload:bg-blue-50 group-hover/upload:border-blue-400 transition-all">
                                                <i class="fas fa-cloud-upload-alt mr-2 text-slate-300 group-hover/upload:text-blue-500 text-xs"></i>
                                                <span class="text-[10px] font-bold text-slate-400 group-hover/upload:text-blue-500 admin-cert-file-label truncate">Thay đổi ảnh...</span>
                                                <input type="file" name="cert_files[<?= $index ?>]" accept="image/*" class="hidden" onchange="previewAdminCert(this)">
                                            </div>
                                        </label>
                                        <input type="hidden" name="certs[<?= $index ?>][existing_file]" value="<?= $cert['file_minh_chung_cc'] ?>">
                                        <?php if (!empty($cert['file_minh_chung_cc'])): ?>
                                            <div class="w-11 h-11 rounded-lg border border-slate-200 overflow-hidden shadow-sm flex-shrink-0">
                                                <img loading="lazy" src="<?= url($cert['file_minh_chung_cc']) ?>" class="w-full h-full object-cover">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (empty($certificates)): ?>
                <div id="no_certs_msg" class="text-center py-12 bg-slate-50 rounded-2xl border border-dashed border-slate-200 mb-6">
                    <i class="fas fa-certificate text-slate-200 text-4xl mb-3"></i>
                    <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Chưa có chứng chỉ nào</p>
                </div>
            <?php endif; ?>

            <div class="flex justify-end gap-3 pt-6 border-t border-slate-100 mt-6">
                <button type="button" onclick="toggleEdit('certs')" 
                    class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-all border border-slate-200 uppercase tracking-wider">Hủy bỏ</button>
                <button type="button" onclick="saveSection('certs')" 
                    class="px-8 py-2.5 bg-[#0066FF] text-white font-bold text-sm rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center gap-2 uppercase tracking-wider">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template for Dynamic Row -->
<template id="admin_cert_template">
    <div class="cert-item bg-white border border-[#0066FF]/20 rounded-2xl p-5 relative group/item animate-fadeIn shadow-sm">
        <button type="button" onclick="removeAdminCert(this)" 
            class="absolute -top-2 -right-2 w-7 h-7 bg-white border border-slate-200 rounded-full text-slate-300 hover:text-red-500 hover:border-red-100 transition-all flex items-center justify-center">
            <i class="fas fa-times text-xs"></i>
        </button>
        <div class="grid grid-cols-1 gap-5">
            <div>
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Loại chứng chỉ</label>
                <div class="relative">
                    <select name="certs[INDEX][type]" 
                        class="w-full h-11 bg-slate-50/50 border border-slate-200 rounded-xl px-4 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 outline-none font-semibold text-slate-700 transition-all appearance-none" required>
                        <option value="">-- Chọn --</option>
                        <optgroup label="Tiếng Anh">
                            <option value="IELTS">IELTS</option>
                            <option value="TOEFL iBT">TOEFL iBT</option>
                            <option value="TOEIC">TOEIC</option>
                        </optgroup>
                        <optgroup label="Ngoại ngữ khác">
                            <option value="HSK">HSK (Tiếng Trung)</option>
                            <option value="JLPT">JLPT (Tiếng Nhật)</option>
                        </optgroup>
                        <optgroup label="Tin học">
                            <option value="IC3">IC3</option>
                            <option value="MOS">MOS</option>
                        </optgroup>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-[10px] pointer-events-none"></i>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Điểm / Kết quả</label>
                <input type="text" name="certs[INDEX][score]" placeholder="VD: 6.5, 450, Đạt..." 
                    class="w-full h-11 bg-slate-50/50 border border-slate-200 rounded-xl px-4 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 outline-none font-semibold text-slate-700 transition-all shadow-sm">
            </div>
            <div>
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Minh chứng (Ảnh)</label>
                <label class="block cursor-pointer group/upload">
                    <div class="h-11 px-4 border border-slate-200 rounded-xl flex items-center bg-slate-50/50 group-hover/upload:bg-blue-50 group-hover/upload:border-blue-400 transition-all">
                        <i class="fas fa-cloud-upload-alt mr-2 text-slate-300 group-hover/upload:text-blue-500 text-xs"></i>
                        <span class="text-[10px] font-bold text-slate-400 group-hover/upload:text-blue-500 admin-cert-file-label truncate">Tải lên minh chứng...</span>
                        <input type="file" name="cert_files[INDEX]" accept="image/*" class="hidden" onchange="previewAdminCert(this)">
                    </div>
                </label>
            </div>
        </div>
    </div>
</template>

<script>
    let adminCertCount = <?= !empty($certificates) ? count($certificates) : 0 ?>;

    function addAdminCertRow() {
        const list = document.getElementById('admin_cert_list');
        const msg = document.getElementById('no_certs_msg');
        if (msg) msg.classList.add('hidden');

        const template = document.getElementById('admin_cert_template').innerHTML;
        const newRow = template.replace(/INDEX/g, adminCertCount);

        const div = document.createElement('div');
        div.innerHTML = newRow;
        list.appendChild(div.firstElementChild);

        adminCertCount++;
    }

    function removeAdminCert(btn) {
        const item = btn.closest('.cert-item');
        item.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            item.remove();
            if (document.querySelectorAll('#admin_cert_list .cert-item').length === 0) {
                const msg = document.getElementById('no_certs_msg');
                if (msg) msg.classList.remove('hidden');
            }
        }, 200);
    }

    function previewAdminCert(input) {
        const label = input.closest('label').querySelector('.admin-cert-file-label');
        if (input.files && input.files[0]) {
            label.textContent = 'Đã chọn: ' + input.files[0].name;
            label.classList.remove('text-slate-400');
            label.classList.add('text-[#0066FF]');
        }
    }
</script>