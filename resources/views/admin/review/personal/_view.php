<!-- View Mode -->
<div id="view_personal" class="animate-in fade-in duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible relative">
        <div class="p-4">
            <div class="overflow-x-auto overflow-y-hidden">
                <table class="w-full border-separate border-spacing-0 table-fixed">
                    <tbody>
                        <!-- Dòng 1: Họ tên | CCCD -->
                        <tr>
                            <td style="padding: 5px 4px 5px 5px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Họ và tên:</td>
                            <td style="padding: 8px 10px 8px 0; width: 200px;" class="min-w-[200px] max-w-[200px] align-middle">
                                <div class="w-full h-9 px-3 flex items-center bg-white border border-slate-300 rounded text-xs text-slate-700 font-medium uppercase truncate">
                                    <?= htmlspecialchars($user['ho_va_ten']) ?>
                                </div>
                            </td>
                            <td style="padding: 5px 4px 5px 15px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Số CCCD:</td>
                            <td style="padding: 8px 0; width: 200px;" class="min-w-[200px] max-w-[200px] align-middle">
                                <div class="w-full h-9 px-3 flex items-center bg-white border border-slate-300 rounded text-xs text-slate-700 font-medium tracking-wider">
                                    <?= $user['so_cccd'] ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Dòng 2: Ngày sinh | Giới tính -->
                        <tr>
                            <td style="padding: 5px 4px 5px 5px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Ngày sinh:</td>
                            <td style="padding: 8px 10px 8px 0; width: 200px;" class="min-w-[200px] max-w-[200px] align-middle">
                                <div class="w-full h-9 px-3 flex items-center bg-white border border-slate-300 rounded text-xs text-slate-700 font-medium">
                                    <?= date('d/m/Y', strtotime($user['ngay_sinh'])) ?>
                                </div>
                            </td>
                            <td style="padding: 5px 4px 5px 15px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Giới tính:</td>
                            <td style="padding: 8px 0; width: 200px;" class="min-w-[200px] max-w-[200px] align-middle">
                                <div class="w-full h-9 px-3 flex items-center bg-white border border-slate-300 rounded text-xs text-slate-700 font-medium">
                                    <?= $user['gioi_tinh'] ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Dòng 3: HKTT (Tỉnh) | Dân tộc -->
                        <tr>
                            <td style="padding: 5px 4px 5px 5px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">HKTT (Tỉnh):</td>
                            <td style="padding: 8px 10px 8px 0; width: 200px;" class="min-w-[200px] max-w-[200px] align-middle">
                                <div class="w-full h-9 px-3 flex items-center bg-white border border-slate-300 rounded text-xs text-slate-700 font-medium truncate">
                                    <?= $provinceMap[$user['ma_tinh_ho_khau'] ?? ''] ?? ($user['ma_tinh_ho_khau'] ?? '...') ?>
                                </div>
                            </td>
                            <td style="padding: 5px 4px 5px 15px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Dân tộc:</td>
                            <td style="padding: 8px 0; width: 200px;" class="min-w-[200px] max-w-[200px] align-middle">
                                <div class="w-full h-9 px-3 flex items-center bg-white border border-slate-300 rounded text-xs text-slate-700 font-medium">
                                    <?= $user['dan_toc'] ?? '...' ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Dòng 4: SĐT | Email -->
                        <tr>
                            <td style="padding: 5px 4px 5px 5px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Điện thoại:</td>
                            <td style="padding: 8px 10px 8px 0; width: 200px;" class="min-w-[200px] max-w-[200px] align-middle">
                                <div class="w-full h-9 px-3 flex items-center bg-white border border-slate-300 rounded text-xs text-slate-700 font-medium">
                                    <?= $user['dien_thoai'] ?>
                                </div>
                            </td>
                            <td style="padding: 5px 4px 5px 15px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Email:</td>
                            <td style="padding: 8px 0; width: 200px;" class="min-w-[200px] max-w-[200px] align-middle">
                                <div class="w-full h-9 px-3 flex items-center bg-white border border-slate-300 rounded text-xs text-slate-700 font-medium truncate">
                                    <?= $user['email'] ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Dòng 5: Liên lạc | Phường xã -->
                        <tr>
                            <td style="padding: 5px 4px 5px 5px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Tỉnh/TP Liên lạc:</td>
                            <td style="padding: 8px 10px 8px 0; width: 200px;" class="min-w-[200px] max-w-[200px] align-middle">
                                <div class="w-full h-9 px-3 flex items-center bg-white border border-slate-300 rounded text-xs text-slate-700 font-medium truncate">
                                    <?= $provinceMap[$user['ma_tinh_thuong_tru'] ?? ''] ?? '...' ?>
                                </div>
                            </td>
                            <td style="padding: 5px 4px 5px 15px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Phường/Xã:</td>
                            <td style="padding: 8px 0; width: 200px;" class="min-w-[200px] max-w-[200px] align-middle">
                                <div class="w-full h-9 px-3 flex items-center bg-white border border-slate-300 rounded text-xs text-slate-700 font-medium truncate">
                                    <?= $wardName ?? '...' ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Dòng 6: Địa chỉ chi tiết -->
                        <tr>
                            <td style="padding: 5px 4px 5px 5px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Địa chỉ chi tiết:</td>
                            <td colspan="3" style="padding: 8px 0;" class="align-middle">
                                <div class="w-full h-9 px-3 flex items-center bg-white border border-slate-300 rounded text-xs text-slate-700 font-medium truncate">
                                    <?= $user['dia_chi_chi_tiet'] ?? '...' ?>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div> <!-- Close Table Wrapper -->

            <!-- Dòng 7: Dải phân cách & Trạng thái (Phía dưới Grid) -->
            <div class="mt-4 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-6">
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" name="status_personal" value="approved" <?= ($user['trang_thai'] ?? '') !== 'Từ chối' ? 'checked' : '' ?> class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                                <span class="ml-2 text-xs font-bold text-emerald-700 group-hover:text-emerald-800 transition-colors">Duyệt thông tin</span>
                            </label>
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" name="status_personal" value="rejected" <?= ($user['trang_thai'] ?? '') === 'Từ chối' ? 'checked' : '' ?> class="w-4 h-4 text-rose-600 focus:ring-rose-500 border-gray-300">
                                <span class="ml-2 text-xs font-bold text-rose-700 group-hover:text-rose-800 transition-colors">Yêu cầu sửa</span>
                            </label>
                        </div>
                    </div>

                    <button type="button" onclick="toggleEdit('personal')" 
                        class="px-4 py-1.5 bg-white text-[#0066FF] border border-[#0066FF]/20 rounded-xl shadow-sm hover:bg-[#0066FF] hover:text-white transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                        <i class="fas fa-edit"></i> Sửa thông tin
                    </button>
                </div>

                <!-- Lý do từ chối -->
                <div class="<?= ($user['trang_thai'] ?? '') === 'Từ chối' ? '' : 'hidden' ?>" id="reason_personal_container">
                    <textarea name="note_personal" class="w-full text-xs border border-slate-200 rounded-xl p-3 focus:ring-rose-500 focus:border-rose-500 bg-rose-50/30" rows="2" placeholder="Nhập lý do sai sót/cần bổ sung..."><?= htmlspecialchars($user['ghi_chu_ca_nhan'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
