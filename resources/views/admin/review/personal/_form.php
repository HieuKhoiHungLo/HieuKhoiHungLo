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
                        for($y = $currentYear; $y >= $currentYear - 10; $y--): ?>
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
                <i class="fas fa-school"></i>
            </div>
            <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Thông tin Trường THPT Lớp 12</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Tỉnh/TP Trường Lớp 12 -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tỉnh/TP Trường THPT</label>
                <div class="relative">
                    <i class="fas fa-map-marker-alt absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <select name="ma_tinh_lop_12" 
                            onchange="window.dispatchEvent(new CustomEvent('province-school-change', {detail: this.value}))"
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none">
                        <option value="">-- Chọn Tỉnh/TP --</option>
                        <?php foreach($provinces as $p): ?>
                            <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_lop_12'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                </div>
            </div>

            <!-- Trường THPT (Searchable) -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Trường THPT</label>
                <div x-data="schoolSearch('<?= $user['ma_tinh_lop_12'] ?? '' ?>', '<?= $user['ma_truong_lop_12'] ?? '' ?>')" 
                        @province-school-change.window="handleProvinceChange($event.detail)"
                        class="relative">
                    
                    <input type="hidden" name="ma_truong_lop_12" :value="selectedCode">
                    <i class="fas fa-university absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" 
                            x-model="search"
                            @focus="open = true; search = ''"
                            @click.away="open = false"
                            placeholder="-- Nhập tên trường để tìm --"
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-[#0066FF] focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>

                    <!-- Dropdown -->
                    <div x-show="open" class="absolute z-50 w-full mt-2 bg-white border border-blue-100 rounded-xl shadow-xl max-h-60 overflow-y-auto custom-scrollbar">
                        <template x-for="school in filteredSchools" :key="school.ma_truong">
                            <div @click="select(school)" class="px-4 py-3 hover:bg-blue-50 cursor-pointer text-sm font-medium text-slate-700 border-b border-slate-50 last:border-0 transition-colors">
                                <div class="flex flex-col">
                                    <span x-text="school.ten_truong" class="font-bold"></span>
                                    <span x-text="'Mã: ' + school.ma_truong" class="text-[10px] text-slate-400"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="h-px bg-slate-100 my-6"></div>

        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#ff8800] shadow-sm">
                <i class="fas fa-star"></i>
            </div>
            <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Khu vực & Đối tượng Ưu tiên</h3>
        </div>

        <div x-data="{ 
            kv: '<?= $user['khu_vuc_uu_tien'] ?? '' ?>', 
            isCustomKv: <?= ($user['is_custom_kv'] ?? 0) ? 'true' : 'false' ?>,
            dt: '<?= $user['doi_tuong_uu_tien'] ?? '' ?>',
            isCustomDt: <?= ($user['is_custom_dt'] ?? 0) ? 'true' : 'false' ?>
        }" 
        @school-selected.window="if(!isCustomKv) kv = $event.detail.ma_kv"
        class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Khu vực ưu tiên -->
            <div class="p-4 bg-orange-50/30 rounded-2xl border border-orange-100">
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-[11px] font-bold text-orange-600 uppercase tracking-wider">Khu vực ưu tiên</label>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="is_custom_kv" value="1" x-model="isCustomKv" class="w-4 h-4 rounded border-orange-200 text-orange-500 focus:ring-orange-200">
                        <span class="text-[10px] font-bold text-slate-400 group-hover:text-orange-500 transition-colors uppercase">Tùy chỉnh thủ công</span>
                    </label>
                </div>
                <div class="relative">
                    <select name="kv_uu_tien" x-model="kv" :disabled="!isCustomKv" class="w-full pl-4 pr-10 py-3 bg-white border border-orange-100 rounded-xl text-sm font-bold text-slate-700 focus:ring-4 focus:ring-orange-50 transition-all outline-none appearance-none disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed">
                        <option value="">-- Chọn Khu vực --</option>
                        <?php foreach($priorityAreas as $ma_kv => $diem): ?>
                            <option value="<?= $ma_kv ?>"><?= $ma_kv ?> (+<?= $diem ?> điểm)</option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-orange-300 text-xs pointer-events-none"></i>
                </div>
                <input type="hidden" name="kv_uu_tien" :value="kv" x-show="!isCustomKv">
            </div>

            <!-- Đối tượng ưu tiên -->
            <div class="p-4 bg-blue-50/30 rounded-2xl border border-blue-100">
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-[11px] font-bold text-blue-600 uppercase tracking-wider">Đối tượng ưu tiên</label>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="is_custom_dt" value="1" x-model="isCustomDt" class="w-4 h-4 rounded border-blue-200 text-blue-500 focus:ring-blue-200">
                        <span class="text-[10px] font-bold text-slate-400 group-hover:text-blue-500 transition-colors uppercase">Tự chọn ĐT ưu tiên</span>
                    </label>
                </div>
                <div class="relative">
                    <select name="dt_uu_tien" x-model="dt" :disabled="!isCustomDt" class="w-full pl-4 pr-10 py-3 bg-white border border-blue-100 rounded-xl text-sm font-bold text-slate-700 focus:ring-4 focus:ring-blue-50 transition-all outline-none appearance-none disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed">
                        <option value="">-- Không thuộc đối tượng ưu tiên --</option>
                        <?php foreach($priorityObjects as $r): ?>
                            <option value="<?= trim($r['ma_dt']) ?>"><?= trim($r['ma_dt']) ?> - <?= $r['ten_dt'] ?? '' ?> (+<?= $r['diem_uu_tien'] ?> điểm)</option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-blue-300 text-xs pointer-events-none"></i>
                </div>
                <input type="hidden" name="dt_uu_tien" :value="dt" x-show="!isCustomDt">
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
                        <?php foreach($provinces as $p): ?>
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
                        <?php foreach($provinces as $p): ?>
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
