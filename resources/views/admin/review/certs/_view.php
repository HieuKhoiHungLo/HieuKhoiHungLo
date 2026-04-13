<!-- View Mode -->
<div id="view_certs" class="animate-in fade-in duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible relative">
        <div class="p-4">
            <?php if (empty($certificates)): ?>
                <div class="flex flex-col items-center justify-center py-12 bg-slate-50 border-2 border-dashed border-slate-100 rounded-xl mb-4">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-slate-200 mb-3 shadow-sm">
                        <i class="fas fa-award text-2xl"></i>
                    </div>
                    <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest text-center">Chưa có chứng chỉ ngoại ngữ / tin học</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto text-xs border border-slate-200 rounded-xl mb-6">
                    <table class="w-full text-left border-collapse" style="font-size: 12px;">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th style="padding: 20px !important; text-align: center; font-weight: 600; color:#000; border-right: 1px solid #e2e8f0; width: 60px; min-width: 60px;">STT</th>
                                <th style="padding: 20px !important; text-align: left; font-weight: 600; color:#000; border-right: 1px solid #e2e8f0; width: 180px; min-width: 180px;">Loại chứng chỉ</th>
                                <th style="padding: 20px !important; text-align: center; font-weight: 600; color:#000; border-right: 1px solid #e2e8f0;">Điểm số / Kết quả</th>
                                <th style="padding: 20px !important; text-align: center; font-weight: 600; color:#000; width: 80px; min-width: 80px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $index => $cert): ?>
                                <tr class="bg-white border-b border-slate-100 last:border-b-0 hover:bg-blue-50/20 transition-colors">
                                    <td style="padding: 20px !important; text-align: center; border-right: 1px solid #e2e8f0; color: #000; font-weight: 500;">
                                        <?= $index + 1 ?>
                                    </td>
                                    <td style="padding: 20px !important; border-right: 1px solid #e2e8f0; color: #000; font-weight: 500;">
                                        <?= htmlspecialchars($cert['loai_chung_chi']) ?>
                                    </td>
                                    <td style="padding: 20px !important; text-align: center; border-right: 1px solid #e2e8f0; color: #000; font-weight: 500;">
                                        <?= htmlspecialchars($cert['diem_chung_chi']) ?>
                                    </td>
                                    <td style="padding: 20px !important; text-align: center; color: #000;">
                                        <div class="flex items-center justify-center gap-2">
                                            <i class="fas fa-file-alt text-blue-600"></i>
                                            <i class="fas fa-trash-alt opacity-0"></i> <!-- Invisible placeholder to match width -->
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Dòng cuối: Trạng thái & Nút sửa -->
            <div class="mt-2 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-4">

                        <div class="flex items-center gap-6">
                            <?php
                            $currentStatus = $user['trang_thai_chung_chi'] ?? '';
                            $isRejected = ($currentStatus === 'Từ chối');
                            ?>
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" name="status_certs" value="approved" <?= !$isRejected ? 'checked' : '' ?> class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                                <span class="ml-2 text-xs font-bold text-emerald-700 group-hover:text-emerald-800 transition-colors">Duyệt thông tin</span>
                            </label>
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" name="status_certs" value="rejected" <?= $isRejected ? 'checked' : '' ?> class="w-4 h-4 text-rose-600 focus:ring-rose-500 border-gray-300">
                                <span class="ml-2 text-xs font-bold text-rose-700 group-hover:text-rose-800 transition-colors">Yêu cầu sửa</span>
                            </label>
                        </div>
                    </div>

                    <button type="button" onclick="toggleEdit('certs')" 
                        class="px-4 py-1.5 bg-white text-[#0066FF] border border-[#0066FF]/20 rounded-xl shadow-sm hover:bg-[#0066FF] hover:text-white transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                        <i class="fas fa-edit"></i> Sửa thông tin
                    </button>
                </div>

                <!-- Lý do từ chối -->
                <div class="<?= $isRejected ? '' : 'hidden' ?>" id="reason_certs_container">
                    <textarea name="note_certs" class="w-full text-xs border border-slate-200 rounded-xl p-3 focus:ring-rose-500 focus:border-rose-500 bg-rose-50/30" rows="2" placeholder="Nhập lý do sai sót/cần bổ sung..."><?= htmlspecialchars($user['ghi_chu_chung_chi'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>