<!-- Edit Form (Redesigned) -->
<div id="form_personal" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-[2rem] p-8 border border-blue-100 shadow-xl shadow-blue-50/50 overflow-visible">
        <input type="hidden" name="application_id" value="<?= $user['application_id'] ?? '' ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Họ tên -->
            <div class="col-span-2 md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Họ và tên</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" name="ho_va_ten" value="<?= htmlspecialchars($user['ho_va_ten']) ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                </div>
            </div>

            <!-- CCCD -->
            <div class="col-span-2 md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Số CCCD</label>
                <div class="relative">
                    <i class="fas fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" name="so_cccd" value="<?= $user['so_cccd'] ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none font-mono tracking-wider">
                </div>
            </div>

            <!-- Row: Ngày sinh, Giới tính, Dân tộc -->
            <div class="col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Ngày sinh -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Ngày sinh</label>
                    <div class="relative">
                        <i class="fas fa-calendar-alt absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="date" name="ngay_sinh" value="<?= $user['ngay_sinh'] ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                    </div>
                </div>

                <!-- Giới tính -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Giới tính</label>
                    <div class="relative">
                        <i class="fas fa-venus-mars absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <select name="gioi_tinh" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none">
                            <option value="Nam" <?= $user['gioi_tinh'] == 'Nam' ? 'selected' : '' ?>>Nam</option>
                            <option value="Nữ" <?= $user['gioi_tinh'] == 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <!-- Dân tộc -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Dân tộc</label>
                    <div class="relative">
                        <i class="fas fa-users absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="text" name="dan_toc" value="<?= $user['dan_toc'] ?? '' ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                    </div>
                </div>
            </div>

            <!-- Row: SĐT và Email -->
            <div class="col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- SĐT -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Số điện thoại</label>
                    <div class="relative">
                        <i class="fas fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="text" name="dien_thoai" value="<?= $user['dien_thoai'] ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="email" name="email" value="<?= $user['email'] ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                    </div>
                </div>
            </div>

        </div>



        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Hộ khẩu thường trú -->
            <div class="col-span-1 md:col-span-2">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Hộ khẩu thường trú (Tỉnh)</label>
                <div class="relative">
                    <i class="fas fa-home absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <select name="ma_tinh_ho_khau" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none">
                        <option value="">-- Chọn Tỉnh/TP --</option>
                        <?php foreach ($provinces as $p): ?>
                            <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_ho_khau'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>>
                                <?= $p['ten_tinh'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                </div>
            </div>

            <!-- Tỉnh/TP (Liên hệ) -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tỉnh/TP Liên lạc</label>
                <div class="relative">
                    <i class="fas fa-map absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <select name="ma_tinh_thuong_tru"
                        onchange="window.dispatchEvent(new CustomEvent('province-change', {detail: this.value}))"
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none">
                        <option value="">-- Chọn Tỉnh/TP --</option>
                        <?php foreach ($provinces as $p): ?>
                            <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_thuong_tru'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                </div>
            </div>

            <!-- Phường/Xã (Searchable) -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Phường/Xã</label>
                <div x-data="wardSearch('<?= $user['ma_tinh_thuong_tru'] ?? '' ?>', '<?= $user['ma_xa_thuong_tru'] ?? '' ?>')"
                    @province-change.window="handleProvinceChange($event.detail)"
                    class="relative">

                    <input type="hidden" name="ma_xa_thuong_tru" :value="selectedCode">
                    <i class="fas fa-building absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text"
                        x-model="search"
                        @focus="open = true; search = ''"
                        @click.away="open = false"
                        placeholder="-- Nhập để tìm kiếm --"
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>

                    <!-- Dropdown -->
                    <div x-show="open"
                        x-transition.opacity.duration.200ms
                        class="absolute z-[100] w-full mt-2 bg-white border border-blue-100 rounded-xl shadow-2xl max-h-60 overflow-y-auto custom-scrollbar"
                        style="top: 100%; left: 0;">
                        <template x-for="ward in filteredWards" :key="ward.ma_xa">
                            <div @click="select(ward)" class="px-4 py-3 hover:bg-blue-50 cursor-pointer text-sm font-medium text-slate-700 border-b border-slate-50 last:border-0 transition-colors">
                                <span x-text="ward.ten_xa"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Địa chỉ chi tiết -->
            <div class="col-span-2">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Địa chỉ chi tiết (Thôn/Xóm/Số nhà)</label>
                <div class="relative">
                    <i class="fas fa-home absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" name="dia_chi_chi_tiet" value="<?= $user['dia_chi_chi_tiet'] ?? '' ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4 pt-8 mt-6 border-t border-slate-100">
            <button type="button" onclick="toggleEdit('personal')" class="px-6 py-3.5 rounded-xl text-slate-500 font-bold text-sm hover:bg-slate-50 hover:text-slate-700 transition-colors uppercase tracking-wider">Hủy bỏ</button>
            <button type="button" onclick="saveSection('personal')" class="px-8 py-3.5 bg-gradient-to-r from-[#0066FF] to-blue-600 text-white font-black text-sm uppercase tracking-wider rounded-xl shadow-lg shadow-blue-200 hover:shadow-xl hover:shadow-blue-300 hover:-translate-y-1 active:translate-y-0 transition-all flex items-center">
                <i class="fas fa-save mr-2"></i> Lưu thay đổi
            </button>
        </div>
    </div>

</div>