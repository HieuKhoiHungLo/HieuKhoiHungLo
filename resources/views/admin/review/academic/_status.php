    <!-- Review Status Section (Extracted from Form) -->
    <?php
    $currentStatus = $user['trang_thai_hoc_ba'] ?? '';
    // Default to 'Approved' if not explicitly 'Rejected'
    $checkApproved = ($currentStatus !== 'Từ chối') ? 'checked' : '';
    $checkRejected = ($currentStatus === 'Từ chối') ? 'checked' : '';
    ?>
    <div class="bg-slate-50 rounded-[2rem] p-6 border border-slate-200 mt-6 shadow-sm">
        <div class="flex items-center justify-between mb-5">
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center">
                    <i class="fas fa-clipboard-check mr-2"></i> Tình trạng kiểm duyệt Học bạ
                </h4>
                <div class="h-px flex-1 bg-slate-200 mx-6"></div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <label class="relative flex items-center p-3 bg-white border-2 border-slate-100 rounded-2xl cursor-pointer transition-all hover:border-emerald-200 group has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                <input type="radio" name="status_academic" value="approved" <?= $checkApproved ?> class="w-5 h-5 text-emerald-600 border-slate-300 focus:ring-emerald-500">
                <div class="ml-4">
                    <span class="block text-sm font-black text-slate-800 uppercase tracking-tight">Duyệt học lực</span>
                    <span class="block text-[10px] font-bold text-slate-400 mt-0.5">Xác nhận điểm số chính xác</span>
                </div>
                <div class="ml-auto opacity-0 group-has-[:checked]:opacity-100 transition-opacity">
                    <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
                </div>
            </label>

            <label class="relative flex items-center p-3 bg-white border-2 border-slate-100 rounded-2xl cursor-pointer transition-all hover:border-rose-200 group has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/50">
                <input type="radio" name="status_academic" value="rejected" <?= $checkRejected ?> class="w-5 h-5 text-rose-600 border-slate-300 focus:ring-rose-500">
                <div class="ml-4">
                    <span class="block text-sm font-black text-slate-800 uppercase tracking-tight">Cần bổ sung</span>
                    <span class="block text-[10px] font-bold text-slate-400 mt-0.5">Yêu cầu chỉnh sửa thông tin</span>
                </div>
                <div class="ml-auto opacity-0 group-has-[:checked]:opacity-100 transition-opacity">
                    <i class="fas fa-exclamation-circle text-rose-500 text-xl"></i>
                </div>
            </label>
        </div>
            <div class="<?= $checkRejected ? '' : 'hidden' ?>" id="reason_academic_container">
            <textarea name="note_academic" class="w-full text-sm border-gray-300 rounded-lg focus:ring-rose-500 focus:border-rose-500" rows="2" placeholder="Nhập lý do sai sót..."></textarea>
        </div>
    </div>
