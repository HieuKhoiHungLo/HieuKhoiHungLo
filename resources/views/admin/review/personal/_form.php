<!-- Edit Form (Redesigned) -->
<div id="form_personal" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible">
        <input type="hidden" name="application_id" value="<?= $user['application_id'] ?? '' ?>">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/60 rounded-t-2xl">
            <div class="w-9 h-9 rounded-xl bg-[#0066FF]/10 text-[#0066FF] flex items-center justify-center">
                <i class="fas fa-user-edit text-sm"></i>
            </div>
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Chỉnh sửa thông tin</p>
                <p class="text-sm font-bold text-slate-700">Thông tin cá nhân & Liên lạc</p>
            </div>
        </div>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Họ tên -->
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        <i class="fas fa-user mr-1 text-[#0066FF]"></i> Họ và tên
                    </label>
                    <div class="relative">
                        <input type="text" name="ho_va_ten" value="<?= htmlspecialchars($user['ho_va_ten']) ?>" 
                            class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none shadow-sm uppercase" oninput="this.value = this.value.toUpperCase();">
                    </div>
                </div>

                <!-- Số CCCD -->
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        <i class="fas fa-id-card mr-1 text-[#0066FF]"></i> Số CCCD
                    </label>
                    <div class="relative">
                        <input type="text" name="so_cccd" value="<?= $user['so_cccd'] ?>" 
                            class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none font-mono tracking-wider shadow-sm">
                    </div>
                </div>

                <!-- Row: Ngày sinh, Giới tính, Dân tộc -->
                <div class="col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Ngày sinh -->
                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            <i class="fas fa-calendar-alt mr-1 text-[#0066FF]"></i> Ngày sinh
                        </label>
                        <div class="relative">
                            <input type="date" name="ngay_sinh" value="<?= $user['ngay_sinh'] ?>" 
                                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none shadow-sm">
                        </div>
                    </div>

                    <!-- Giới tính -->
                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            <i class="fas fa-venus-mars mr-1 text-[#0066FF]"></i> Giới tính
                        </label>
                        <div class="relative">
                            <select name="gioi_tinh" class="w-full pl-4 pr-8 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none shadow-sm">
                                <option value="Nam" <?= $user['gioi_tinh'] == 'Nam' ? 'selected' : '' ?>>Nam</option>
                                <option value="Nữ" <?= $user['gioi_tinh'] == 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Dân tộc -->
                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            <i class="fas fa-users mr-1 text-[#0066FF]"></i> Dân tộc
                        </label>
                        <div class="relative">
                            <input type="text" name="dan_toc" value="<?= $user['dan_toc'] ?? '' ?>" 
                                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none shadow-sm">
                        </div>
                    </div>
                </div>

                <!-- Row: SĐT và Email -->
                <div class="col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 py-4 border-y border-slate-50">
                    <!-- SĐT -->
                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            <i class="fas fa-phone mr-1 text-emerald-500"></i> Số điện thoại
                        </label>
                        <div class="relative">
                            <input type="text" name="dien_thoai" value="<?= $user['dien_thoai'] ?>" 
                                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none shadow-sm">
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            <i class="fas fa-envelope mr-1 text-sky-500"></i> Email
                        </label>
                        <div class="relative">
                            <input type="email" name="email" value="<?= $user['email'] ?>" 
                                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none shadow-sm">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Hộ khẩu thường trú (Tỉnh) -->
                <div class="col-span-2">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Hộ khẩu thường trú (Tỉnh)</label>
                    <div class="relative">
                        <select name="ma_tinh_ho_khau" class="w-full pl-4 pr-8 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none shadow-sm">
                            <option value="">-- Chọn Tỉnh/TP --</option>
                            <?php foreach ($provinces as $p): ?>
                                <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_ho_khau'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>>
                                    <?= $p['ten_tinh'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <!-- Tỉnh/TP (Liên hệ) -->
                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Tỉnh/TP Liên lạc</label>
                    <div class="relative">
                        <select name="ma_tinh_thuong_tru"
                            onchange="window.dispatchEvent(new CustomEvent('province-change', {detail: this.value}))"
                            class="w-full pl-4 pr-8 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none shadow-sm">
                            <option value="">-- Chọn Tỉnh/TP --</option>
                            <?php foreach ($provinces as $p): ?>
                                <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_thuong_tru'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <!-- Phường/Xã (Searchable) -->
                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Phường/Xã</label>
                    <div x-data="wardSearch('<?= $user['ma_tinh_thuong_tru'] ?? '' ?>', '<?= $user['ma_xa_thuong_tru'] ?? '' ?>')"
                        @province-change.window="handleProvinceChange($event.detail)"
                        class="relative">

                        <input type="hidden" name="ma_xa_thuong_tru" :value="selectedCode">
                        <input type="text"
                            x-model="search"
                            @focus="open = true; search = ''"
                            @click.away="open = false"
                            placeholder="Nhập để tìm kiếm..."
                            class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none shadow-sm">
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>

                        <!-- Dropdown -->
                        <div x-show="open"
                            class="absolute z-[100] w-full mt-1 bg-white border border-blue-100 rounded-xl shadow-2xl max-h-56 overflow-y-auto"
                            style="top: 100%; left: 0;">
                            <template x-for="ward in filteredWards" :key="ward.ma_xa">
                                <div @click="select(ward)" class="px-4 py-2.5 hover:bg-blue-50 cursor-pointer text-sm font-semibold text-slate-700 border-b border-slate-50 last:border-0 transition-colors">
                                    <span x-text="ward.ten_xa"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Địa chỉ chi tiết -->
                <div class="col-span-2">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Địa chỉ chi tiết (Thôn/Xóm/Số nhà)</label>
                    <div class="relative">
                        <input type="text" name="dia_chi_chi_tiet" value="<?= $user['dia_chi_chi_tiet'] ?? '' ?>" 
                            class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none shadow-sm">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-8 mt-6 border-t border-slate-100">
                <button type="button" onclick="toggleEdit('personal')" 
                    class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-all border border-slate-200">
                    <i class="fas fa-times mr-1.5"></i> Hủy bỏ
                </button>
                <button type="button" onclick="saveSection('personal')" 
                    class="px-8 py-2.5 bg-[#0066FF] text-white font-bold text-sm rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>
        </div>
    </div>
</div>