<!-- Edit Form (Redesigned) -->
<div id="form_personal" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible">
        <input type="hidden" name="application_id" value="<?= $user['application_id'] ?? '' ?>">
        
        <div class="p-4">
            <div class="overflow-x-auto overflow-y-hidden">
                <table class="w-full border-separate border-spacing-0 table-fixed">
                    <tbody>
                        <!-- Dòng 1: Họ tên | CCCD -->
                        <tr>
                            <td style="padding: 5px 4px 5px 5px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Họ và tên:</td>
                            <td style="padding: 8px 10px 8px 0; width: 200px;" class="align-middle">
                                <input type="text" name="ho_va_ten" value="<?= htmlspecialchars($user['ho_va_ten']) ?>" 
                                    class="w-full h-9 px-3 bg-white border border-slate-300 rounded focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition-all outline-none uppercase font-medium text-xs text-slate-700"
                                    oninput="this.value = this.value.toUpperCase();">
                            </td>
                            <td style="padding: 5px 4px 5px 15px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Số CCCD:</td>
                            <td style="padding: 8px 0; width: 200px;" class="align-middle">
                                <input type="text" name="so_cccd" value="<?= $user['so_cccd'] ?>" 
                                    class="w-full h-9 px-3 bg-white border border-slate-300 rounded focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition-all outline-none font-medium tracking-wider text-xs text-slate-700">
                            </td>
                        </tr>

                        <!-- Dòng 2: Ngày sinh | Giới tính -->
                        <tr>
                            <td style="padding: 5px 4px 5px 5px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Ngày sinh:</td>
                            <td style="padding: 8px 10px 8px 0; width: 200px;" class="align-middle">
                                <input type="date" name="ngay_sinh" value="<?= $user['ngay_sinh'] ?>" 
                                    class="w-full h-9 px-3 bg-white border border-slate-300 rounded focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition-all outline-none text-xs font-medium text-slate-700">
                            </td>
                            <td style="padding: 5px 4px 5px 15px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Giới tính:</td>
                            <td style="padding: 8px 0; width: 200px;" class="align-middle">
                                <div class="relative">
                                    <select name="gioi_tinh" class="w-full h-9 pl-3 pr-8 bg-white border border-slate-300 rounded text-xs font-medium text-slate-700 focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition-all outline-none appearance-none cursor-pointer">
                                        <option value="Nam" <?= $user['gioi_tinh'] == 'Nam' ? 'selected' : '' ?>>Nam</option>
                                        <option value="Nữ" <?= $user['gioi_tinh'] == 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                                </div>
                            </td>
                        </tr>

                        <!-- Dòng 3: HKTT (Tỉnh) | Dân tộc -->
                        <tr>
                            <td style="padding: 5px 4px 5px 5px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">HKTT (Tỉnh):</td>
                            <td style="padding: 8px 10px 8px 0; width: 200px;" class="align-middle">
                                <div class="relative">
                                    <select name="ma_tinh_ho_khau" class="w-full h-9 pl-3 pr-8 bg-white border border-slate-300 rounded text-xs font-medium text-slate-700 focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition-all outline-none appearance-none cursor-pointer">
                                        <option value="">-- Chọn Tỉnh/TP --</option>
                                        <?php foreach ($provinces as $p): ?>
                                            <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_ho_khau'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                                </div>
                            </td>
                            <td style="padding: 5px 4px 5px 15px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Dân tộc:</td>
                            <td style="padding: 8px 0; width: 200px;" class="align-middle">
                                <input type="text" name="dan_toc" value="<?= $user['dan_toc'] ?? '' ?>" 
                                    class="w-full h-9 px-3 bg-white border border-slate-300 rounded focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition-all outline-none text-xs font-medium text-slate-700">
                            </td>
                        </tr>

                        <!-- Dòng 4: SĐT | Email -->
                        <tr>
                            <td style="padding: 5px 4px 5px 5px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Điện thoại:</td>
                            <td style="padding: 8px 10px 8px 0; width: 200px;" class="align-middle">
                                <input type="text" name="dien_thoai" value="<?= $user['dien_thoai'] ?>" 
                                    class="w-full h-9 px-3 bg-white border border-slate-300 rounded focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition-all outline-none font-medium text-xs text-slate-700">
                            </td>
                            <td style="padding: 5px 4px 5px 15px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Email:</td>
                            <td style="padding: 8px 0; width: 200px;" class="align-middle">
                                <input type="email" name="email" value="<?= $user['email'] ?>" 
                                    class="w-full h-9 px-3 bg-white border border-slate-300 rounded focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition-all outline-none text-xs font-medium text-slate-700">
                            </td>
                        </tr>

                        <!-- Dòng 5: Tỉnh/TP Liên lạc | Phường xã -->
                        <tr>
                            <td style="padding: 5px 4px 5px 5px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Tỉnh/TP Liên lạc:</td>
                            <td style="padding: 8px 10px 8px 0; width: 200px;" class="align-middle">
                                <div class="relative">
                                    <select name="ma_tinh_thuong_tru"
                                        onchange="window.dispatchEvent(new CustomEvent('province-change', {detail: this.value}))"
                                        class="w-full h-9 pl-3 pr-8 bg-white border border-slate-300 rounded text-xs font-medium text-slate-700 focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition-all outline-none appearance-none cursor-pointer">
                                        <option value="">-- Chọn Tỉnh/TP --</option>
                                        <?php foreach ($provinces as $p): ?>
                                            <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_thuong_tru'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                                </div>
                            </td>
                            <td style="padding: 5px 4px 5px 15px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Phường/Xã:</td>
                            <td style="padding: 8px 0; width: 200px;" class="align-middle">
                                <div class="relative" x-data="wardSearch('<?= $user['ma_tinh_thuong_tru'] ?? '' ?>', '<?= $user['ma_xa_thuong_tru'] ?? '' ?>')" @province-change.window="handleProvinceChange($event.detail)">
                                    <input type="hidden" name="ma_xa_thuong_tru" :value="selectedCode">
                                    <input type="text" x-model="search" @focus="open = true; search = ''" @click.away="open = false" placeholder="..."
                                        class="w-full h-9 pl-3 pr-8 bg-white border border-slate-300 rounded text-xs font-medium text-slate-700 focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition-all outline-none">
                                    <i class="fas fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                                    <!-- Dropdown -->
                                    <div x-show="open" class="absolute z-[100] w-full mt-1 bg-white border border-blue-100 rounded-md shadow-2xl max-h-56 overflow-y-auto" style="top: 100%; left: 0;">
                                        <template x-for="ward in filteredWards" :key="ward.ma_xa">
                                            <div @click="select(ward)" class="px-3 py-1.5 hover:bg-blue-50 cursor-pointer text-xs font-medium text-slate-700 border-b border-slate-50 last:border-0 transition-colors">
                                                <span x-text="ward.ten_xa"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- Dòng 6: Địa chỉ chi tiết -->
                        <tr>
                            <td style="padding: 5px 4px 5px 5px; width: 80px;" class="min-w-[80px] max-w-[80px] text-xs font-semibold text-slate-700 whitespace-nowrap align-middle">Địa chỉ chi tiết:</td>
                            <td colspan="3" style="padding: 8px 0;" class="align-middle">
                                <input type="text" name="dia_chi_chi_tiet" value="<?= $user['dia_chi_chi_tiet'] ?? '' ?>" 
                                    class="w-full h-9 px-3 bg-white border border-slate-300 rounded focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition-all outline-none text-xs font-medium text-slate-700">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div> <!-- Close Table Wrapper -->

            <!-- Dòng cuối: Nút thao tác (Phía dưới Grid) -->
            <div class="flex justify-end gap-2 pt-4 mt-2 border-t border-slate-100">
                <button type="button" onclick="toggleEdit('personal')" 
                    class="px-4 py-2 rounded-lg text-xs font-bold text-slate-500 hover:bg-slate-100 transition-all border border-slate-200">
                    <i class="fas fa-times mr-1"></i> Hủy
                </button>
                <button type="button" onclick="saveSection('personal')" 
                    class="px-6 py-2 bg-[#0066FF] text-white font-bold text-xs rounded-lg shadow-md shadow-blue-100 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    <i class="fas fa-save"></i> Lưu dữ liệu
                </button>
            </div>
        </div>
    </div>
</div>