    <!-- Review Status Section (Extracted from Form) -->
    <?php
    $currentStatus = $user['trang_thai_diem_thi'] ?? '';
    // Default to 'Approved' if not explicitly 'Rejected'
    $checkApproved = ($currentStatus !== 'Từ chối') ? 'checked' : '';
    $checkRejected = ($currentStatus === 'Từ chối') ? 'checked' : '';
    ?>
    <div class="bg-slate-50 rounded-[2rem] p-6 border border-slate-200 mt-6 shadow-sm transition-all hover:bg-white">
        <div class="flex items-center justify-between mb-5">
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center">
                    <i class="fas fa-clipboard-check mr-2"></i> Tình trạng kiểm duyệt Điểm thi
                </h4>
                <div class="h-px flex-1 bg-slate-200 mx-6"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="relative flex items-center p-3 bg-white border-2 border-slate-100 rounded-2xl cursor-pointer transition-all hover:border-emerald-200 group has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                <input type="radio" name="status_thpt" value="approved" <?= $checkApproved ?> class="w-5 h-5 text-emerald-600 border-slate-300 focus:ring-emerald-500">
                <div class="ml-4">
                    <span class="block text-sm font-black text-slate-800 uppercase tracking-tight">Hợp lệ</span>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase mt-0.5">Điểm khớp với hệ thống</span>
                </div>
                <div class="ml-auto opacity-0 group-has-[:checked]:opacity-100 transition-opacity">
                    <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
                </div>
            </label>

            <label class="relative flex items-center p-3 bg-white border-2 border-slate-100 rounded-2xl cursor-pointer transition-all hover:border-rose-200 group has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/50">
                <input type="radio" name="status_thpt" value="rejected" <?= $checkRejected ?> class="w-5 h-5 text-rose-600 border-slate-300 focus:ring-rose-500">
                <div class="ml-4">
                    <span class="block text-sm font-black text-slate-800 uppercase tracking-tight">Cần bổ sung</span>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase mt-0.5">Sai lệch thông tin</span>
                </div>
                <div class="ml-auto opacity-0 group-has-[:checked]:opacity-100 transition-opacity">
                    <i class="fas fa-exclamation-circle text-rose-500 text-xl"></i>
                </div>
            </label>
        </div>
        <div class="<?= $checkRejected ? '' : 'hidden' ?> mt-4 animate-in zoom-in-95 duration-200" id="reason_thpt_container">
            <textarea name="note_thpt" class="w-full p-4 bg-white border-2 border-rose-100 rounded-2xl text-sm font-bold text-slate-700 placeholder:text-slate-300 focus:border-rose-500 focus:ring-0 outline-none transition-all" rows="3" placeholder="Nhập lý do chi tiết..."></textarea>
        </div>
    </div>
