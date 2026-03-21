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
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Gửi Email</h3>
                <div class="mt-2 space-y-4">
                     <p class="text-sm text-gray-500">Gửi email đến <span id="email-count" class="font-bold">0</span> thí sinh đã chọn.</p>
                     
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Chọn mẫu Email nhanh</label>
                        <select id="modal-email-template" onchange="applyEmailTemplate(this.value)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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

                     <div>
                        <label class="block text-sm font-medium text-gray-700">Tiêu đề</label>
                        <input type="text" id="modal-email-subject" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                     </div>
                     
                     <div>
                        <label class="block text-sm font-medium text-gray-700">Nội dung</label>
                        <p class="text-xs text-gray-500 mb-1">Sử dụng {{name}} để chèn tên thí sinh.</p>
                        <textarea id="modal-email-content" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                     </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="confirmSendEmail()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0066FF] text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Gửi</button>
                <button type="button" onclick="closeModal('email-modal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Hủy</button>
            </div>
        </div>
    </div>
</div>
