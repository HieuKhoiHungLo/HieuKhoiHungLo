    <!-- Review Status Section (Extracted from Form) -->
    <?php
    $currentStatus = $user['trang_thai'] ?? '';
    // Check Approved if explicitly approved OR pending/empty (default)
    $checkApproved = ($currentStatus === 'Đã duyệt' || empty($currentStatus) || $currentStatus === 'Chờ duyệt') ? 'checked' : '';
    $checkRejected = ($currentStatus === 'Từ chối') ? 'checked' : '';
    ?>
    <div class="bg-slate-50 rounded-[2rem] p-4 border border-slate-200 mt-6">
        <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Tình trạng kiểm duyệt</h4>
                <div class="h-px flex-1 bg-slate-100 mx-4"></div>
        </div>
        <div class="flex items-center gap-6 mb-3">
            <label class="flex items-center cursor-pointer">
                <input type="radio" name="status_personal" value="approved" <?= $checkApproved ?> class="w-5 h-5 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                <span class="ml-2 font-bold text-emerald-700">Duyệt thông tin</span>
            </label>
            <label class="flex items-center cursor-pointer">
                <input type="radio" name="status_personal" value="rejected" <?= $checkRejected ?> class="w-5 h-5 text-rose-600 focus:ring-rose-500 border-gray-300">
                <span class="ml-2 font-bold text-rose-700">Thông tin cần bổ sung/chỉnh sửa</span>
            </label>
        </div>
            <div class="<?= $checkRejected ? '' : 'hidden' ?>" id="reason_personal_container">
            <textarea name="note_personal" class="w-full text-sm border-gray-300 rounded-lg focus:ring-rose-500 focus:border-rose-500" rows="2" placeholder="Nhập lý do sai sót..."></textarea>
        </div>
    </div>
