<!-- Edit Form (Redesigned) -->
<div id="form_personal" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-[2rem] p-8 border border-blue-100 shadow-xl shadow-blue-50/50">
        <input type="hidden" name="application_id" value="<?= $user['application_id'] ?? '' ?>">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#0066FF] shadow-sm">
                <i class="fas fa-user-edit"></i>
            </div>
            <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Chỉnh sửa Thông tin Cá nhân</h3>
        </div>

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

            <!-- SĐT -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Số điện thoại</label>
                <div class="relative">
                    <i class="fas fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" name="dien_thoai" value="<?= $user['dien_thoai'] ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                </div>
            </div>

            <!-- Email -->
            <div class="col-span-2 md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Email</label>
                <div class="relative">
                    <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="email" name="email" value="<?= $user['email'] ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                </div>
            </div>

            <!-- Năm tốt nghiệp -->
            <div class="col-span-2 md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Năm tốt nghiệp</label>
                <div class="relative">
                    <i class="fas fa-calendar-check absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <select name="nam_tot_nghiep" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none">
                        <?php
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= $currentYear - 10; $y--): ?>
                            <option value="<?= $y ?>" <?= ($user['nam_tot_nghiep'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="h-px bg-slate-100 my-6"></div>

        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#0066FF] shadow-sm">
                <i class="fas fa-camera"></i>
            </div>
            <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Ảnh bản thân & CCCD</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Avatar -->
            <div class="space-y-3">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ảnh thẻ (3x4)</label>
                <div class="relative group aspect-[3/4] bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 overflow-hidden hover:border-blue-400 transition-all">
                    <img id="preview_personal_avatar" src="<?= $user['anh_dai_dien'] ? url($user['anh_dai_dien']) : '' ?>" class="w-full h-full object-cover <?= !empty($user['anh_dai_dien']) ? '' : 'hidden' ?>">
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 <?= !empty($user['anh_dai_dien']) ? 'hidden' : '' ?>" id="placeholder_avatar">
                        <i class="fas fa-camera text-2xl mb-2"></i>
                        <span class="text-[10px] font-bold uppercase">Nhấn để tải lên</span>
                    </div>
                    <input type="file" name="avatar" accept="image/*" onchange="previewPersonalImg(this, 'preview_personal_avatar')" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                </div>
            </div>

            <!-- CCCD Front -->
            <div class="space-y-3">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">CCCD Mặt trước</label>
                <div class="relative group aspect-[1.6/1] bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 overflow-hidden hover:border-blue-400 transition-all">
                    <img id="preview_personal_cccd_front" src="<?= $user['anh_cccd_truoc'] ? url($user['anh_cccd_truoc']) : '' ?>" class="w-full h-full object-contain <?= !empty($user['anh_cccd_truoc']) ? '' : 'hidden' ?>">
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 <?= !empty($user['anh_cccd_truoc']) ? 'hidden' : '' ?>" id="placeholder_cccd_front">
                        <i class="fas fa-id-card text-2xl mb-2"></i>
                        <span class="text-[10px] font-bold uppercase">Mặt trước</span>
                    </div>
                    <input type="file" name="cccd_front" accept="image/*" onchange="previewPersonalImg(this, 'preview_personal_cccd_front')" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                </div>
            </div>

            <!-- CCCD Back -->
            <div class="space-y-3">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">CCCD Mặt sau</label>
                <div class="relative group aspect-[1.6/1] bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 overflow-hidden hover:border-blue-400 transition-all">
                    <img id="preview_personal_cccd_back" src="<?= $user['anh_cccd_sau'] ? url($user['anh_cccd_sau']) : '' ?>" class="w-full h-full object-contain <?= !empty($user['anh_cccd_sau']) ? '' : 'hidden' ?>">
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 <?= !empty($user['anh_cccd_sau']) ? 'hidden' : 'flex' ?>" id="placeholder_cccd_back">
                        <i class="fas fa-id-card text-2xl mb-2"></i>
                        <span class="text-[10px] font-bold uppercase">Mặt sau</span>
                    </div>
                    <input type="file" name="cccd_back" accept="image/*" onchange="previewPersonalImg(this, 'preview_personal_cccd_back')" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="h-px bg-slate-100 my-6"></div>

        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#0066FF] shadow-sm">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Hộ khẩu & Địa chỉ Liên lạc</h3>
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
                        class="absolute z-50 w-full mt-2 bg-white border border-blue-100 rounded-xl shadow-xl max-h-60 overflow-y-auto custom-scrollbar">
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