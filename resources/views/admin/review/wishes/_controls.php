    <?php
    $currentStatus = $user['trang_thai_nguyen_vong'] ?? '';
    // Default to approved for new or pending
    $checkApproved = ($currentStatus === 'Đã duyệt' || empty($currentStatus) || $currentStatus === 'Chờ duyệt') ? 'checked' : '';
    $checkRejected = ($currentStatus === 'Từ chối') ? 'checked' : '';
    ?>

                <div>
                    <span class="block text-xs font-black text-slate-800 uppercase tracking-tight">Hợp lệ</span>
                    <span class="block text-[10px] font-bold text-slate-400 mt-0.5">Nguyện vọng chuẩn</span>
                </div>
                <i class="fas fa-check-circle text-emerald-500 ml-auto opacity-0 group-has-[:checked]:opacity-100 transition-opacity"></i>
            </label>

            <label class="relative flex items-center gap-3 p-3 bg-white border-2 border-slate-100 rounded-xl cursor-pointer transition-all hover:border-rose-200 group has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/50">
                <input type="radio" name="status_wishes" value="rejected" class="w-4 h-4 text-rose-600 border-slate-300 focus:ring-rose-500">
                <div>
                    <span class="block text-xs font-black text-slate-800 uppercase tracking-tight">Cần sửa</span>
                    <span class="block text-[10px] font-bold text-slate-400 mt-0.5">Sai ngành/mã ngành</span>
                </div>
                <i class="fas fa-exclamation-circle text-rose-500 ml-auto opacity-0 group-has-[:checked]:opacity-100 transition-opacity"></i>
            </label>
        </div>
        <div class="hidden animate-in zoom-in-95 duration-200" id="reason_wishes_container">
            <textarea name="note_wishes" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 placeholder:text-slate-300 focus:border-[#0066FF] focus:ring-2 focus:ring-blue-50 outline-none transition-all" rows="3" placeholder="Nhập ghi chú hướng dẫn sửa đổi..."></textarea>
        </div>
    </div>
</div>
