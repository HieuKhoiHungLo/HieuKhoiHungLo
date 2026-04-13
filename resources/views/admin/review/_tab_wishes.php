<!-- TAB 5: WISHES -->
<div id="view_wishes" class="animate-in fade-in duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible relative">
        <div class="p-4">
            <!-- Table Container -->
            <div class="mb-6">
                <?php include __DIR__ . '/wishes/_table.php'; ?>
            </div>

            <!-- Dòng cuối: Trạng thái & Nút sửa -->
            <div class="mt-2 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Kiểm duyệt nguyện vọng</span>
                        <div class="flex items-center gap-6">
                            <?php
                            $currentStatus = $user['trang_thai_nguyen_vong'] ?? '';
                            $isRejected = ($currentStatus === 'Từ chối');
                            ?>
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" name="status_wishes" value="approved" <?= !$isRejected ? 'checked' : '' ?> class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                                <span class="ml-2 text-xs font-bold text-emerald-700 group-hover:text-emerald-800 transition-colors">Duyệt thông tin</span>
                            </label>
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" name="status_wishes" value="rejected" <?= $isRejected ? 'checked' : '' ?> class="w-4 h-4 text-rose-600 focus:ring-rose-500 border-gray-300">
                                <span class="ml-2 text-xs font-bold text-rose-700 group-hover:text-rose-800 transition-colors">Yêu cầu sửa</span>
                            </label>
                        </div>
                    </div>

                    <div id="btn_group_wishes">
                        <button type="button" onclick="toggleEdit('wishes')" 
                            class="px-4 py-1.5 bg-white text-[#0066FF] border border-[#0066FF]/20 rounded-xl shadow-sm hover:bg-[#0066FF] hover:text-white transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-edit"></i> Sửa nguyện vọng
                        </button>
                    </div>
                </div>

                <!-- Lý do từ chối -->
                <div class="<?= $isRejected ? '' : 'hidden' ?>" id="reason_wishes_container">
                    <textarea name="note_wishes" class="w-full text-xs border border-slate-200 rounded-xl p-3 focus:ring-rose-500 focus:border-rose-500 bg-rose-50/30" rows="2" placeholder="Nhập lý do sai sót/cần bổ sung..."><?= htmlspecialchars($user['ghi_chu_nguyen_vong'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Form -->
<?php include __DIR__ . '/wishes/_form.php'; ?>
