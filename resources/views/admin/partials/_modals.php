<!-- Modals -->
<!-- Transfer Modal -->
<div id="transfer-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Chuyển đợt tuyển sinh</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500 mb-4">Chọn đợt tuyển sinh đích để chuyển <span id="transfer-count" class="font-bold">0</span> hồ sơ đã chọn.</p>
                    <select id="modal-target-session" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Chọn đợt tuyển sinh --</option>
                        <?php foreach ($yearSessions as $s): ?>
                            <option value="<?= $s['id'] ?>">
                                <?= htmlspecialchars(!empty($s['ma_dot']) ? $s['ma_dot'] : $s['ten_dot']) ?> - <?= $s['nam_tuyen_sinh'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="confirmTransfer()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0066FF] text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Chuyển</button>
                <button type="button" onclick="closeModal('transfer-modal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Hủy</button>
            </div>
        </div>
    </div>
</div>

<!-- Email Modal -->
<div id="email-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-paper-plane text-blue-600"></i> Gửi Email
                </h3>
                <button type="button" onclick="closeModal('email-modal')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="px-6 py-6 overflow-y-auto max-h-[70vh]">
                <div class="space-y-5">
                    <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white shadow-sm">
                            <i class="fas fa-users text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-blue-900">Gửi gửi đến <span id="email-count" class="font-bold">0</span> thí sinh</p>
                            <p class="text-xs text-blue-700/70">Nội dung sẽ được cá nhân hóa cho từng thí sinh.</p>
                        </div>
                    </div>
                     
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Ghi chú nội bộ (Chỉ quản trị viên thấy)</label>
                            <input type="text" id="modal-internal-note" value="Gửi mail ngày: <?= date('d/m/Y') ?>" placeholder="Nhập ghi chú cho ban tuyển sinh..." class="w-full px-4 py-2.5 bg-amber-50/50 border border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all outline-none text-amber-900 font-medium">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Chọn mẫu Email nhanh</label>
                            <select id="modal-email-template" onchange="applyEmailTemplate(this.value)" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                                <option value="">-- Để trống hoặc tự soạn --</option>
                                <?php if (!empty($emailTemplates)): ?>
                                    <?php foreach ($emailTemplates as $tpl): ?>
                                        <option value="<?= $tpl['id'] ?>" data-subject="<?= htmlspecialchars($tpl['subject']) ?>" data-body="<?= htmlspecialchars($tpl['body']) ?>">
                                            <?= htmlspecialchars($tpl['subject']) ?> (<?= $tpl['code'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tiêu đề Email <span class="text-rose-500">*</span></label>
                            <input type="text" id="modal-email-subject" placeholder="Nhập tiêu đề email..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                        </div>
                    </div>
                     
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Nội dung Email <span class="text-rose-500">*</span></label>
                            <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-mono italic">Dùng {{name}} để chèn tên</span>
                        </div>
                        <textarea id="modal-email-content" rows="6" placeholder="Nhập nội dung thư gửi cho thí sinh..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700 min-h-[150px]"></textarea>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex flex-col sm:flex-row-reverse gap-3">
                <button type="button" onclick="confirmSendEmail()" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 transition-all flex items-center justify-center gap-2 group">
                    <span>Gửi ngay</span>
                    <i class="fas fa-paper-plane text-xs group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform"></i>
                </button>
                <button type="button" onclick="closeModal('email-modal')" class="px-6 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                    Hủy
                </button>
            </div>
        </div>
    </div>
</div>
