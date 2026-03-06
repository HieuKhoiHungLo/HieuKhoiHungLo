<?php
// Note: $certificates is passed from the controller/view bundle
?>
<!-- Edit Form (Dynamic Certs) -->
<div id="form_certs" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-[2rem] p-8 border border-blue-100 shadow-xl shadow-blue-50/50">
        <input type="hidden" name="application_id" value="<?= $user['application_id'] ?? '' ?>">

        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#0066FF] shadow-sm">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Chỉnh sửa Chứng chỉ</h3>
            </div>
            <button type="button" onclick="addAdminCertRow()" class="px-4 py-2 bg-blue-50 text-blue-600 rounded-xl text-xs font-black uppercase hover:bg-blue-100 transition-colors flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> Thêm chứng chỉ
            </button>
        </div>

        <div id="admin_cert_list" class="space-y-6">
            <?php if (!empty($certificates)): ?>
                <?php foreach ($certificates as $index => $cert): ?>
                    <div class="cert-item bg-slate-50 border border-slate-100 rounded-2xl p-6 relative group/item">
                        <button type="button" onclick="removeAdminCert(this)" class="absolute top-4 right-4 text-slate-300 hover:text-red-500 transition-colors">
                            <i class="fas fa-times-circle text-xl"></i>
                        </button>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Loại chứng chỉ</label>
                                <select name="certs[<?= $index ?>][type]" class="w-full h-11 bg-white border border-slate-200 rounded-xl px-4 focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
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
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Điểm / Kết quả</label>
                                <input type="text" name="certs[<?= $index ?>][score]" value="<?= htmlspecialchars($cert['diem_chung_chi']) ?>" placeholder="VD: 6.5, 450, Đạt..." class="w-full h-11 bg-white border border-slate-200 rounded-xl px-4 focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Minh chứng (Ảnh)</label>
                                <div class="flex items-center space-x-4">
                                    <label class="flex-grow cursor-pointer group/upload">
                                        <div class="h-16 border-2 border-dashed border-slate-200 rounded-xl flex items-center justify-center bg-white group-hover/upload:bg-blue-50 group-hover/upload:border-blue-400 transition-all">
                                            <i class="fas fa-cloud-upload-alt mr-2 text-slate-300 group-hover/upload:text-blue-500"></i>
                                            <span class="text-[10px] font-bold text-slate-300 group-hover/upload:text-blue-500 admin-cert-file-label">Chọn ảnh mới để thay đổi</span>
                                            <input type="file" name="cert_files[<?= $index ?>]" accept="image/*" class="hidden" onchange="previewAdminCert(this)">
                                        </div>
                                    </label>
                                    <input type="hidden" name="certs[<?= $index ?>][existing_file]" value="<?= $cert['file_minh_chung_cc'] ?>">
                                    <?php if (!empty($cert['file_minh_chung_cc'])): ?>
                                        <div class="w-16 h-16 rounded-xl border border-slate-200 overflow-hidden shadow-sm flex-shrink-0">
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
            <div id="no_certs_msg" class="text-center py-10 bg-slate-50 rounded-2xl border border-dashed border-slate-200 mb-6">
                <i class="fas fa-certificate text-slate-200 text-4xl mb-3"></i>
                <p class="text-slate-400 font-bold text-sm">Chưa có chứng chỉ nào được tải lên.</p>
            </div>
        <?php endif; ?>

        <div class="flex justify-end gap-3 pt-6 border-t border-slate-100 mt-6">
            <button type="button" onclick="toggleEdit('certs')" class="px-6 py-3 text-slate-500 font-bold text-sm uppercase tracking-wider hover:bg-slate-50 rounded-xl transition-colors">Hủy bỏ </button>
            <button type="button" onclick="saveSection('certs')" class="px-6 py-3 bg-[#0066FF] text-white font-bold text-sm uppercase tracking-wider rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">Lưu thay đổi</button>
        </div>
    </div>
</div>

<!-- Template for Dynamic Row -->
<template id="admin_cert_template">
    <div class="cert-item bg-slate-50 border border-slate-100 rounded-2xl p-6 relative group/item animate-fadeIn">
        <button type="button" onclick="removeAdminCert(this)" class="absolute top-4 right-4 text-slate-300 hover:text-red-500 transition-colors">
            <i class="fas fa-times-circle text-xl"></i>
        </button>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Loại chứng chỉ</label>
                <select name="certs[INDEX][type]" class="w-full h-11 bg-white border border-slate-200 rounded-xl px-4 focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700" required>
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
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Điểm / Kết quả</label>
                <input type="text" name="certs[INDEX][score]" placeholder="VD: 6.5, 450, Đạt..." class="w-full h-11 bg-white border border-slate-200 rounded-xl px-4 focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Minh chứng (Ảnh)</label>
                <label class="block cursor-pointer group/upload">
                    <div class="h-16 border-2 border-dashed border-slate-200 rounded-xl flex items-center justify-center bg-white group-hover/upload:bg-blue-50 group-hover/upload:border-blue-400 transition-all">
                        <i class="fas fa-cloud-upload-alt mr-2 text-slate-300 group-hover/upload:text-blue-500"></i>
                        <span class="text-[10px] font-bold text-slate-300 group-hover/upload:text-blue-500 admin-cert-file-label">Tải lên minh chứng</span>
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
            label.classList.remove('text-slate-300');
            label.classList.add('text-blue-600');
        }
    }
</script>