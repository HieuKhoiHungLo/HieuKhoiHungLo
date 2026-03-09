<?php $title = 'Hồ sơ thí sinh - Thông tin tổng hợp';
include __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-6xl mx-auto pb-20 px-4 sm:px-6">
    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-red-900/5 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-hvu-red to-red-700 p-6 text-white text-center">
            <h2 class="text-2xl font-bold uppercase tracking-wide">Hồ sơ thí sinh</h2>
            <p class="text-white/80 text-sm mt-1">Bước 1/<?= $totalStepsCount ?>: Cập nhật thông tin cá nhân và minh chứng định danh</p>
        </div>

        <!-- Wizard Navigation -->
        <div class="bg-gray-100 px-3 sm:px-6 py-3 sm:py-4 border-b flex justify-between items-center text-[10px] sm:text-sm font-semibold overflow-x-auto">
            <div class="text-hvu-red flex flex-col items-center min-w-fit px-2">
                <span class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mb-1 border-2 border-hvu-red">1</span>
                <span class="hidden sm:block">Hồ sơ</span>
            </div>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
            <a href="<?= url('/profile/step2') ?>" class="text-gray-400 flex flex-col items-center min-w-fit px-2 hover:text-hvu-red transition-colors">
                <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1">2</span>
                <span class="hidden sm:block">Học bạ</span>
            </a>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
            <a href="<?= url('/profile/step3') ?>" class="text-gray-400 flex flex-col items-center min-w-fit px-2 hover:text-hvu-red transition-colors">
                <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1">3</span>
                <span class="hidden sm:block">Chứng chỉ quốc tế</span>
            </a>

            <?php if ($enableTHPTSetting): ?>
                <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
                <a href="<?= url('/profile/step4') ?>" class="text-gray-400 flex flex-col items-center min-w-fit px-2 hover:text-hvu-red transition-colors">
                    <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1">4</span>
                    <span class="hidden sm:block">Điểm thi</span>
                </a>
            <?php endif; ?>

            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
            <a href="<?= url('/profile/step5') ?>" class="text-gray-400 flex flex-col items-center min-w-fit px-2 hover:text-hvu-red transition-colors">
                <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1"><?= $enableTHPTSetting ? 5 : 4 ?></span>
                <span class="hidden sm:block">Nguyện vọng</span>
            </a>
        </div>

        <div class="p-4 sm:p-8">
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p class="font-bold">Lỗi!</p>
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <form action="<?= url('/profile/step1') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">

                <?php if (!empty($isLocked)): ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6" role="alert">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-lock text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700 font-bold">
                                    Hồ sơ đã được duyệt. Bạn không thể chỉnh sửa thông tin.
                                </p>
                                <?php if (!empty($editRequestPending)): ?>
                                    <p class="text-xs text-yellow-600 mt-1">
                                        <i class="fas fa-clock mr-1"></i> Đã gửi yêu cầu chỉnh sửa, vui lòng chờ Quản trị viên xử lý.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <fieldset <?= !empty($isLocked) ? 'disabled' : '' ?> class="space-y-10 group/locked">

                    <!-- 1. Thông tin cá nhân & Ảnh thẻ -->
                    <section>
                        <div class="flex items-center mb-6">
                            <div class="bg-hvu-red text-white w-8 h-8 rounded-full flex items-center justify-center font-bold mr-3 shadow-lg">1</div>
                            <h3 class="text-xl font-bold text-gray-800 uppercase tracking-tight">Thông tin cá nhân & Ảnh bản thân</h3>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                            <!-- Left Column: Inputs -->
                            <div class="lg:col-span-2 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="label"><i class="fas fa-user"></i> Họ và tên</label>
                                        <input type="text" name="fullname" value="<?= htmlspecialchars($user['ho_va_ten'] ?? '') ?>" class="hvu-input font-bold" required>
                                    </div>
                                    <div>
                                        <label class="label"><i class="fas fa-id-card-alt"></i> Số CCCD (Cố định)</label>
                                        <input type="text" value="<?= htmlspecialchars($user['so_cccd'] ?? '') ?>" class="hvu-input bg-gray-50 cursor-not-allowed text-gray-400" readonly>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="label"><i class="fas fa-calendar-alt"></i> Ngày sinh</label>
                                        <input type="date" name="dob" value="<?= htmlspecialchars($user['ngay_sinh'] ?? '2007-01-01') ?>" class="hvu-input font-bold" required>
                                    </div>
                                    <div>
                                        <label class="label"><i class="fas fa-venus-mars"></i> Giới tính</label>
                                        <select name="gender" class="hvu-input font-bold">
                                            <option value="Nam" <?= ($user['gioi_tinh'] ?? '') === 'Nam' ? 'selected' : '' ?>>Nam</option>
                                            <option value="Nữ" <?= ($user['gioi_tinh'] ?? '') === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="label"><i class="fas fa-flag"></i> Dân tộc</label>
                                        <input type="text" name="ethnic" value="<?= htmlspecialchars($user['dan_toc'] ?? 'Kinh') ?>" class="hvu-input font-bold">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="label"><i class="fas fa-phone"></i> Điện thoại</label>
                                        <input type="text" name="phone" value="<?= htmlspecialchars($user['dien_thoai'] ?? '') ?>" class="hvu-input font-bold" required>
                                    </div>
                                    <div>
                                        <label class="label"><i class="fas fa-envelope"></i> Email</label>
                                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="hvu-input font-bold" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Avatar Upload -->
                            <div class="flex flex-col items-center justify-start pt-2">
                                <label class="label mb-4">Ảnh thẻ (3x4)</label>
                                <div class="group relative w-40 h-48 sm:h-52 border-2 border-dashed border-gray-300 rounded-xl flex flex-col justify-center items-center bg-gray-50 overflow-hidden transition-all hover:border-hvu-red/50">
                                    <img id="preview_avatar" alt="Ảnh đại diện" loading="lazy" src="<?= $user['anh_dai_dien'] ? (strpos($user['anh_dai_dien'], 'http') === 0 ? $user['anh_dai_dien'] : url($user['anh_dai_dien'])) : 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=' ?>" class="<?= !empty($user['anh_dai_dien']) ? 'block' : 'hidden' ?> w-full h-full object-cover">
                                    <div class="<?= !empty($user['anh_dai_dien']) ? 'hidden' : 'flex' ?> flex-col items-center text-gray-400 text-center p-4">
                                        <i class="fas fa-camera text-3xl mb-2"></i>
                                        <span class="text-xs">Chưa có ảnh</span>
                                    </div>
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                        <span class="text-white text-xs font-bold bg-hvu-red/80 px-3 py-1 rounded-full">Đổi ảnh</span>
                                    </div>
                                    <input type="file" name="avatar" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(this, 'preview_avatar')">
                                </div>
                                <p class="text-xs text-center text-gray-500 mt-4 italic">* Tải lên ảnh thẻ rõ mặt, nền xanh hoặc trắng.</p>
                            </div>
                        </div>
                    </section>

                    <hr class="border-gray-100">

                    <!-- 2. Hộ khẩu & Trường THPT -->
                    <section>
                        <div class="flex items-center mb-6">
                            <div class="bg-hvu-red text-white w-8 h-8 rounded-full flex items-center justify-center font-bold mr-3 shadow-lg">2</div>
                            <h3 class="text-xl font-bold text-gray-800 uppercase tracking-tight">Hộ khẩu thường trú</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="label"><i class="fas fa-map-marker-alt"></i> Hộ khẩu (Tỉnh/TP)</label>
                                <select name="ma_tinh_ho_khau" class="hvu-input font-bold" required>
                                    <option value="">-- Chọn Tỉnh --</option>
                                    <?php foreach ($provinces as $p): ?>
                                        <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_ho_khau'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-[10px] text-gray-400 mt-1 italic">* Nơi đăng ký hộ khẩu thường trú lâu dài.</p>
                            </div>
                        </div>
                    </section>

                    <hr class="border-gray-100">

                    <!-- 3. Thông tin Trường THPT -->
                    <section>
                        <div class="flex items-center mb-6">
                            <div class="bg-hvu-red text-white w-8 h-8 rounded-full flex items-center justify-center font-bold mr-3 shadow-lg">3</div>
                            <h3 class="text-xl font-bold text-gray-800 uppercase tracking-tight">Thông tin Trường THPT</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="label"><i class="fas fa-school"></i> Tỉnh/TP của Trường THPT</label>
                                <select name="ma_tinh_lop_12" id="province_school" class="hvu-input font-bold" required>
                                    <option value="">-- Chọn Tỉnh --</option>
                                    <?php foreach ($provinces as $p): ?>
                                        <option value="<?= $p['ma_tinh'] ?>" <?= ($user['ma_tinh_lop_12'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>><?= $p['ten_tinh'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="lg:col-span-2">
                                <label class="label"><i class="fas fa-graduation-cap"></i> Tên trường THPT</label>
                                <div x-data="schoolSearch('<?= $user['ma_tinh_lop_12'] ?? '' ?>', '<?= $user['ma_truong_lop_12'] ?? '' ?>')"
                                    @school-province-change.window="handleProvinceChange($event.detail)"
                                    class="relative">
                                    <input type="hidden" name="ma_truong_lop_12" :value="selectedCode">
                                    <div class="relative group">
                                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-hvu-red transition-colors"></i>
                                        <input type="text"
                                            x-model="search"
                                            @focus="open = true; if(search === '-- Chọn Trường --') search = ''"
                                            @click.away="open = false"
                                            placeholder="-- Nhập tên trường để tìm kiếm --"
                                            class="w-full pl-11 pr-10 py-3.5 bg-white border border-gray-200 rounded-2xl text-sm font-bold text-gray-700 focus:border-hvu-red focus:ring-4 focus:ring-red-50 transition-all outline-none shadow-sm">
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2">
                                            <i x-show="isLoading" class="fas fa-circle-notch fa-spin text-hvu-red text-xs"></i>
                                            <i class="fas fa-chevron-down text-gray-300 text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                        </div>
                                    </div>

                                    <!-- Dropdown -->
                                    <div x-show="open"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 translate-y-2"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        class="absolute z-[100] w-full mt-2 bg-white border border-gray-100 rounded-2xl shadow-2xl max-h-72 overflow-y-auto custom-scrollbar"
                                        style="top: 100%; left: 0;">
                                        <div x-show="schools.length === 0 && !isLoading" class="p-8 text-center">
                                            <i class="fas fa-school text-gray-200 text-3xl mb-2"></i>
                                            <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Không tìm thấy trường nào</p>
                                        </div>
                                        <template x-for="school in filteredSchools" :key="school.ma_truong">
                                            <div @click="select(school)" class="px-5 py-4 hover:bg-red-50 cursor-pointer border-b border-gray-50 last:border-0 transition-all group">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <div class="text-sm font-bold text-gray-800 group-hover:text-hvu-red transition-colors" x-text="school.ten_truong"></div>
                                                        <div class="text-[10px] text-gray-400 mt-1 uppercase tracking-widest font-black" x-text="'Mã: ' + school.ma_truong + ' • KV: ' + school.khu_vuc"></div>
                                                    </div>
                                                    <i class="fas fa-check text-hvu-red opacity-0 group-hover:opacity-100 transition-opacity" x-show="selectedCode == school.ma_truong"></i>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <p class="text-[10px] text-red-500 mt-2 font-bold italic flex items-center"><i class="fas fa-info-circle mr-1"></i> Gõ tên trường vào ô trên để lọc và chọn nhanh.</p>
                            </div>
                            <div>
                                <label class="label"><i class="fas fa-calendar-check"></i> Năm tốt nghiệp</label>
                                <select name="nam_tot_nghiep" class="hvu-input font-bold" required>
                                    <option value="">-- Chọn Năm --</option>
                                    <?php
                                    $currentYear = date('Y');
                                    for ($y = $currentYear; $y >= 1990; $y--): ?>
                                        <option value="<?= $y ?>" <?= ($user['nam_tot_nghiep'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mt-8 bg-blue-50/50 rounded-2xl p-6 border border-blue-100/50 flex flex-col md:flex-row items-center justify-between">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-6 md:space-y-0">
                                <div class="flex items-center">
                                    <div class="bg-blue-500/10 p-3 rounded-xl mr-4">
                                        <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-blue-900">Khu vực ưu tiên</h4>
                                        <p class="text-sm text-blue-700">Mặc định được tự động thiết lập dựa trên trường THPT của bạn.</p>
                                    </div>
                                </div>

                                <div class="flex flex-col space-y-3 w-full md:w-auto">
                                    <!-- Toggle Manual Override -->
                                    <label class="flex items-center space-x-2 cursor-pointer group">
                                        <input type="checkbox" name="is_custom_kv" id="is_custom_kv" value="1" <?= ($user['is_custom_kv'] ?? false) ? 'checked' : '' ?> class="w-5 h-5 text-hvu-red border-gray-300 rounded focus:ring-hvu-red">
                                        <span class="text-sm font-bold text-gray-700 group-hover:text-hvu-red transition-colors italic">Tôi muốn tự chọn khu vực khác</span>
                                    </label>

                                    <!-- Display/Select Area -->
                                    <div class="bg-white px-6 py-4 rounded-2xl shadow-sm border border-blue-200">
                                        <div class="text-[10px] text-gray-500 uppercase font-black mb-1">Khu vực xác định:</div>
                                        <div id="kv_display_box">
                                            <div id="display_kv" class="text-3xl font-black text-hvu-red">--</div>
                                            <input type="hidden" name="kv_uu_tien" id="kv_uu_tien" value="<?= htmlspecialchars($user['khu_vuc_uu_tien'] ?? '') ?>">
                                        </div>
                                        <div id="kv_select_box" class="hidden">
                                            <select id="kv_manual_select" class="text-2xl font-black text-hvu-red bg-transparent border-b-2 border-hvu-red focus:outline-none">
                                                <?php foreach ($priorityAreas as $area): ?>
                                                    <option value="<?= htmlspecialchars($area['ma_kv']) ?>" <?= ($user['khu_vuc_uu_tien'] ?? '') == $area['ma_kv'] ? 'selected' : '' ?>><?= htmlspecialchars($area['ma_kv']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </section>

                    <hr class="border-gray-100">

                    <!-- 3. Thông tin liên hệ -->
                    <section>
                        <div class="flex items-center mb-6">
                            <div class="bg-hvu-red text-white w-8 h-8 rounded-full flex items-center justify-center font-bold mr-3 shadow-lg">5</div>
                            <h3 class="text-xl font-bold text-gray-800 uppercase tracking-tight">Thông tin liên lạc hiện nay</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="label"><i class="fas fa-map"></i> Tỉnh / Thành phố</label>
                                <select name="ma_tinh_thuong_tru" id="province_contact" class="hvu-input font-bold" required>
                                    <option value="">-- Chọn Tỉnh/TP --</option>
                                    <?php foreach ($provinces as $p): ?>
                                        <option value="<?= htmlspecialchars($p['ma_tinh']) ?>"
                                            <?= ($user['ma_tinh_thuong_tru'] ?? '') == $p['ma_tinh'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['ten_tinh']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="label"><i class="fas fa-building"></i> Xã / Phường</label>
                                <div x-data="wardSearch('<?= $user['ma_tinh_thuong_tru'] ?? '' ?>', '<?= $user['ma_xa_thuong_tru'] ?? '' ?>')"
                                    @ward-province-change.window="handleProvinceChange($event.detail)"
                                    class="relative">
                                    <input type="hidden" name="ma_xa_thuong_tru" :value="selectedCode">
                                    <div class="relative group">
                                        <i class="fas fa-building absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-hvu-red transition-colors"></i>
                                        <input type="text"
                                            x-model="search"
                                            @focus="open = true; if(search === '-- Chọn Xã/Phường --') search = ''"
                                            @click.away="open = false"
                                            placeholder="-- Nhập tên xã/phường --"
                                            class="w-full pl-11 pr-10 py-3.5 bg-white border border-gray-200 rounded-2xl text-sm font-bold text-gray-700 focus:border-hvu-red focus:ring-4 focus:ring-red-50 transition-all outline-none shadow-sm">
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2">
                                            <i x-show="isLoading" class="fas fa-circle-notch fa-spin text-hvu-red text-xs"></i>
                                            <i class="fas fa-chevron-down text-gray-300 text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                        </div>
                                    </div>

                                    <!-- Dropdown -->
                                    <div x-show="open"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 translate-y-2"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        class="absolute z-[100] w-full mt-2 bg-white border border-gray-100 rounded-2xl shadow-2xl max-h-60 overflow-y-auto custom-scrollbar"
                                        style="top: 100%; left: 0;">
                                        <div x-show="wards.length === 0 && !isLoading" class="p-6 text-center">
                                            <i class="fas fa-map-marker-alt text-gray-200 text-3xl mb-2"></i>
                                            <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Không tìm thấy xã phường</p>
                                        </div>
                                        <template x-for="ward in filteredWards" :key="ward.ma_xa">
                                            <div @click="select(ward)" class="px-5 py-3.5 hover:bg-red-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors group">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm font-bold text-gray-700 group-hover:text-hvu-red transition-colors" x-text="ward.ten_xa"></span>
                                                    <i class="fas fa-check text-hvu-red opacity-0 group-hover:opacity-100 transition-opacity" x-show="selectedCode == ward.ma_xa"></i>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-2 italic">* Chọn Tỉnh/TP bên trái trước, sau đó tìm kiếm tại đây.</p>
                            </div>
                            <div class="md:col-span-2 lg:col-span-1">
                                <label class="label"><i class="fas fa-home"></i> Địa chỉ chi tiết (Thôn/Xóm/Số nhà...)</label>
                                <input type="text" name="address" value="<?= htmlspecialchars($user['dia_chi_chi_tiet'] ?? '') ?>" class="hvu-input font-bold" required placeholder="Ví dụ: Số 123, Đường Hùng Vương">
                            </div>
                        </div>
                    </section>

                    <hr class="border-gray-100">

                    <!-- 4. Minh chứng CCCD & Đối tượng -->
                    <section>
                        <div class="flex items-center mb-6">
                            <div class="bg-hvu-red text-white w-8 h-8 rounded-full flex items-center justify-center font-bold mr-3 shadow-lg">5</div>
                            <h3 class="text-xl font-bold text-gray-800 uppercase tracking-tight">Minh chứng & Đối tượng ưu tiên</h3>
                        </div>

                        <div class="mb-8 p-6 bg-gray-50 rounded-3xl border border-gray-100">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
                                <div class="flex items-center">
                                    <div class="bg-indigo-500/10 p-3 rounded-xl mr-4">
                                        <i class="fas fa-user-shield text-indigo-600 text-xl"></i>
                                    </div>
                                    <div class="max-w-md">
                                        <label class="label mb-0">Đối tượng ưu tiên</label>
                                        <p class="text-[10px] text-gray-500 italic">Thí sinh tự chọn dựa trên các giấy tờ minh chứng mình có.</p>
                                    </div>
                                </div>
                                <div class="flex flex-col space-y-3 w-full md:w-auto">
                                    <label class="flex items-center space-x-2 cursor-pointer group">
                                        <input type="checkbox" name="is_custom_dt" id="is_custom_dt" value="1" <?= ($user['is_custom_dt'] ?? false) ? 'checked' : '' ?> class="w-5 h-5 text-hvu-red border-gray-300 rounded focus:ring-hvu-red">
                                        <span class="text-sm font-bold text-gray-700 group-hover:text-hvu-red transition-colors italic">Tôi thuộc diện đối tượng ưu tiên</span>
                                    </label>
                                    <div id="dt_select_box" class="hidden">
                                        <select name="dt_uu_tien" id="dt_uu_tien" class="hvu-input font-bold text-sm w-full md:w-96">
                                            <option value="">-- Chọn đối tượng ưu tiên --</option>
                                            <?php foreach ($priorityObjects as $obj): ?>
                                                <option value="<?= htmlspecialchars($obj['ma_dt']) ?>" <?= ($user['doi_tuong_uu_tien'] ?? '') == $obj['ma_dt'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($obj['ma_dt']) ?> - <?= htmlspecialchars($obj['ten_dt']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <!-- CCCD Front -->
                            <div class="space-y-4">
                                <label class="label text-center"><i class="fas fa-id-card"></i> Mặt trước CCCD/CMND</label>
                                <div class="group relative w-full h-40 sm:h-48 border-2 border-dashed border-gray-300 rounded-2xl flex flex-col justify-center items-center bg-gray-50 overflow-hidden transition-all hover:border-hvu-red/50">
                                    <img id="preview_front" alt="Mặt trước CCCD" loading="lazy" src="<?= $user['anh_cccd_truoc'] ? (strpos($user['anh_cccd_truoc'], 'http') === 0 ? $user['anh_cccd_truoc'] : url($user['anh_cccd_truoc'])) : 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=' ?>" class="<?= !empty($user['anh_cccd_truoc']) ? 'block' : 'hidden' ?> w-full h-full object-contain transition-transform duration-300 group-hover:scale-[1.15]" ondblclick="if(this.src && !this.src.startsWith('data:')) window.open(this.src, '_blank')" title="Click đúp để xem kích thước thật">
                                    <div class="<?= !empty($user['anh_cccd_truoc']) ? 'hidden' : 'flex' ?> flex-col items-center text-gray-400">
                                        <i class="fas fa-upload text-3xl mb-2 group-hover:scale-110 transition-transform duration-300"></i>
                                        <span class="text-sm font-bold uppercase">Nhấp để tải lên</span>
                                    </div>
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                        <div class="flex flex-col gap-2 items-center scale-75 group-hover:scale-100 transition-transform duration-300">
                                            <span class="text-white text-sm font-bold bg-hvu-red/80 px-4 py-2 rounded-full shadow-lg"><i class="fas fa-search-plus mr-1"></i> Thay đổi file</span>
                                        </div>
                                    </div>
                                    <input type="file" name="cccd_front" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(this, 'preview_front')">
                                </div>
                            </div>

                            <!-- CCCD Back -->
                            <div class="space-y-4">
                                <label class="label text-center"><i class="fas fa-id-card"></i> Mặt sau CCCD/CMND</label>
                                <div class="group relative w-full h-40 sm:h-48 border-2 border-dashed border-gray-300 rounded-2xl flex flex-col justify-center items-center bg-gray-50 overflow-hidden transition-all hover:border-hvu-red/50">
                                    <img id="preview_back" alt="Mặt sau CCCD" loading="lazy" src="<?= $user['anh_cccd_sau'] ? (strpos($user['anh_cccd_sau'], 'http') === 0 ? $user['anh_cccd_sau'] : url($user['anh_cccd_sau'])) : 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=' ?>" class="<?= !empty($user['anh_cccd_sau']) ? 'block' : 'hidden' ?> w-full h-full object-contain transition-transform duration-300 group-hover:scale-[1.15]" ondblclick="if(this.src && !this.src.startsWith('data:')) window.open(this.src, '_blank')" title="Click đúp để xem kích thước thật">
                                    <div class="<?= !empty($user['anh_cccd_sau']) ? 'hidden' : 'flex' ?> flex-col items-center text-gray-400">
                                        <i class="fas fa-upload text-3xl mb-2 group-hover:scale-110 transition-transform duration-300"></i>
                                        <span class="text-sm font-bold uppercase">Nhấp để tải lên</span>
                                    </div>
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                        <div class="flex flex-col gap-2 items-center scale-75 group-hover:scale-100 transition-transform duration-300">
                                            <span class="text-white text-sm font-bold bg-hvu-red/80 px-4 py-2 rounded-full shadow-lg"><i class="fas fa-search-plus mr-1"></i> Thay đổi file</span>
                                        </div>
                                    </div>
                                    <input type="file" name="cccd_back" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(this, 'preview_back')">
                                </div>
                            </div>
                        </div>

                        <!-- Priority Evidence Uploads (Conditional) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-10 pt-10 border-t border-gray-100">
                            <!-- KV Evidence -->
                            <div id="kv_evidence_section" class="<?= ($user['is_custom_kv'] ?? false) ? '' : 'hidden' ?>">
                                <label class="label text-center text-hvu-red mb-4">
                                    <i class="fas fa-file-signature"></i> Minh chứng Khu vực ưu tiên (Sổ hộ khẩu)
                                </label>
                                <div class="group relative w-full h-48 border-2 border-dashed border-red-200 rounded-2xl flex flex-col justify-center items-center bg-red-50/30 overflow-hidden transition-all hover:border-hvu-red/50">
                                    <img id="preview_kv" alt="Minh chứng khu vực" loading="lazy" src="<?= !empty($user['file_minh_chung_kv']) ? (strpos($user['file_minh_chung_kv'], 'http') === 0 ? $user['file_minh_chung_kv'] : url($user['file_minh_chung_kv'])) : 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=' ?>" class="<?= !empty($user['file_minh_chung_kv']) ? 'block' : 'hidden' ?> w-full h-full object-contain transition-transform duration-300 group-hover:scale-[1.15]" ondblclick="if(this.src && !this.src.startsWith('data:')) window.open(this.src, '_blank')" title="Click đúp để xem kích thước thật">
                                    <div class="<?= !empty($user['file_minh_chung_kv']) ? 'hidden' : 'flex' ?> flex-col items-center text-red-300">
                                        <i class="fas fa-cloud-upload-alt text-4xl mb-2 group-hover:scale-110 transition-transform duration-300"></i>
                                        <span class="text-xs font-black uppercase">Tải lên minh chứng Khu vực</span>
                                    </div>
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                        <div class="flex flex-col gap-2 items-center scale-75 group-hover:scale-100 transition-transform duration-300">
                                            <span class="text-white text-xs font-bold bg-hvu-red px-6 py-2 rounded-full shadow-lg"><i class="fas fa-search-plus mr-1"></i> Thay đổi</span>
                                        </div>
                                    </div>
                                    <input type="file" name="kv_file" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(this, 'preview_kv')">
                                </div>
                            </div>

                            <!-- DT Evidence -->
                            <div id="dt_evidence_section" class="<?= ($user['is_custom_dt'] ?? false) ? '' : 'hidden' ?>">
                                <label class="label text-center text-indigo-600 mb-4">
                                    <i class="fas fa-file-contract"></i> Minh chứng Đối tượng ưu tiên (Giấy xác nhận)
                                </label>
                                <div class="group relative w-full h-48 border-2 border-dashed border-indigo-200 rounded-2xl flex flex-col justify-center items-center bg-indigo-50/30 overflow-hidden transition-all hover:border-indigo-500/50">
                                    <img id="preview_dt" alt="Minh chứng đối tượng" loading="lazy" src="<?= !empty($user['file_minh_chung_dt']) ? (strpos($user['file_minh_chung_dt'], 'http') === 0 ? $user['file_minh_chung_dt'] : url($user['file_minh_chung_dt'])) : 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=' ?>" class="<?= !empty($user['file_minh_chung_dt']) ? 'block' : 'hidden' ?> w-full h-full object-contain transition-transform duration-300 group-hover:scale-[1.15]" ondblclick="if(this.src && !this.src.startsWith('data:')) window.open(this.src, '_blank')" title="Click đúp để xem kích thước thật">
                                    <div class="<?= !empty($user['file_minh_chung_dt']) ? 'hidden' : 'flex' ?> flex-col items-center text-indigo-300">
                                        <i class="fas fa-upload text-4xl mb-2 group-hover:scale-110 transition-transform duration-300"></i>
                                        <span class="text-xs font-black uppercase">Tải lên minh chứng Đối tượng</span>
                                    </div>
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                        <div class="flex flex-col gap-2 items-center scale-75 group-hover:scale-100 transition-transform duration-300">
                                            <span class="text-white text-xs font-bold bg-indigo-600 px-6 py-2 rounded-full shadow-lg"><i class="fas fa-search-plus mr-1"></i> Thay đổi</span>
                                        </div>
                                    </div>
                                    <input type="file" name="dt_file" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(this, 'preview_dt')">
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Footer Actions -->
                    <div class="pt-6 sm:pt-10 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4 sm:gap-6">
                        <div class="text-gray-500 text-xs sm:text-sm italic hidden sm:block">
                            <i class="fas fa-exclamation-triangle mr-2 text-yellow-500"></i> Vui lòng kiểm tra kỹ các thông tin trước khi nhấn "Lưu & Tiếp tục".
                        </div>
                        <button type="submit" class="w-full md:w-auto px-8 sm:px-12 py-3 sm:py-4 bg-gradient-to-br from-hvu-red to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black text-base sm:text-lg rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all flex items-center justify-center">
                            Lưu thông tin và tiếp tục <i class="fas fa-chevron-right ml-3"></i>
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>

<style>
    .label {
        display: block;
        color: #1f2937;
        font-size: 0.875rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }

    .label i {
        color: #BE1E2D;
        margin-right: 0.5rem;
        font-size: 0.75rem;
    }

    /* Fix overlap of icon and text */
    .hvu-input.pl-10 {
        padding-left: 2.5rem !important;
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('wardSearch', (initialProvince, initialWard) => ({
            open: false,
            search: initialWard ? '-- Đang nạp --' : '',
            selectedCode: initialWard,
            provinceId: initialProvince,
            wards: [],
            isLoading: false,

            async init() {
                if (this.provinceId) {
                    await this.fetchWards(this.provinceId);
                }
            },

            async handleProvinceChange(newPid) {
                this.provinceId = newPid;
                this.selectedCode = '';
                this.search = '';
                await this.fetchWards(newPid);
            },

            async fetchWards(pid) {
                if (!pid) {
                    this.wards = [];
                    return;
                }
                this.isLoading = true;
                try {
                    const res = await fetch(`<?= url('/api/wards') ?>?province_id=${pid}`);
                    this.wards = await res.json();

                    if (this.selectedCode) {
                        const found = this.wards.find(w => w.ma_xa == this.selectedCode);
                        if (found) {
                            this.search = found.ten_xa;
                        } else {
                            this.search = '';
                        }
                    }
                } catch (e) {
                    console.error(e);
                }
                this.isLoading = false;
            },

            select(ward) {
                this.selectedCode = ward.ma_xa;
                this.search = ward.ten_xa;
                this.open = false;
            },

            get filteredWards() {
                if (!this.search || this.search === '') return this.wards;
                const lower = this.search.toLowerCase();
                return this.wards.filter(w => w.ten_xa && w.ten_xa.toLowerCase().includes(lower));
            }
        }));

        Alpine.data('schoolSearch', (initialProvince, initialSchool) => ({
            open: false,
            search: initialSchool ? '-- Đang nạp --' : '',
            selectedCode: initialSchool,
            provinceId: initialProvince,
            schools: [],
            isLoading: false,

            async init() {
                if (this.provinceId) {
                    await this.fetchSchools(this.provinceId);
                }

                // Lắng nghe sự kiện từ Auto-KV logic nếu có
                window.addEventListener('update-kv-external', (e) => {
                    // Cập nhật KV nếu cần
                });
            },

            async handleProvinceChange(newPid) {
                this.provinceId = newPid;
                this.selectedCode = '';
                this.search = '';
                await this.fetchSchools(newPid);
            },

            async fetchSchools(pid) {
                if (!pid) {
                    this.schools = [];
                    return;
                }
                this.isLoading = true;
                try {
                    const res = await fetch(`<?= url('/api/schools') ?>?province_id=${pid}`);
                    this.schools = await res.json();

                    if (this.selectedCode) {
                        const found = this.schools.find(s => s.ma_truong == this.selectedCode);
                        if (found) {
                            this.search = found.ten_truong;
                            // Re-trigger KV update on initial load
                            this.dispatchKV(found.khu_vuc);
                        } else {
                            this.search = '';
                        }
                    }
                } catch (e) {
                    console.error(e);
                }
                this.isLoading = false;
            },

            select(school) {
                this.selectedCode = school.ma_truong;
                this.search = school.ten_truong;
                this.open = false;

                // Cập nhật KV
                this.dispatchKV(school.khu_vuc);
            },

            dispatchKV(kv) {
                window.dispatchEvent(new CustomEvent('school-selected-alpine', {
                    detail: {
                        khu_vuc: kv
                    }
                }));
            },

            get filteredSchools() {
                if (!this.search || this.search === '') return this.schools;
                const lower = this.search.toLowerCase();
                return this.schools.filter(s =>
                    (s.ten_truong && s.ten_truong.toLowerCase().includes(lower)) ||
                    (s.ma_truong && s.ma_truong.toLowerCase().includes(lower))
                );
            }
        }));
    });

    document.addEventListener('DOMContentLoaded', function() {
        const displayKV = document.getElementById('display_kv');
        const inputKV = document.getElementById('kv_uu_tien');
        const isCustomKv = document.getElementById('is_custom_kv');
        const kvDisplayBox = document.getElementById('kv_display_box');
        const kvSelectBox = document.getElementById('kv_select_box');
        const kvManualSelect = document.getElementById('kv_manual_select');
        const kvEvidenceSection = document.getElementById('kv_evidence_section');

        // Lắng nghe sự kiện từ Alpine School Search
        window.addEventListener('school-selected-alpine', function(e) {
            if (isCustomKv.checked) return;
            const kv = e.detail.khu_vuc;
            if (displayKV) displayKV.textContent = kv || '--';
            if (inputKV) inputKV.value = kv || '';
        });

        // Tỉnh Trường THPT Change -> Notify Alpine
        const provinceSchool = document.getElementById('province_school');
        if (provinceSchool) {
            provinceSchool.addEventListener('change', function() {
                window.dispatchEvent(new CustomEvent('school-province-change', {
                    detail: this.value
                }));
            });
        }

        // Tỉnh Liên hệ Change -> Notify Alpine
        const provinceContact = document.getElementById('province_contact');
        if (provinceContact) {
            provinceContact.addEventListener('change', function() {
                window.dispatchEvent(new CustomEvent('ward-province-change', {
                    detail: this.value
                }));
            });
        }

        function toggleKvMode() {
            if (isCustomKv.checked) {
                kvDisplayBox.classList.add('hidden');
                kvSelectBox.classList.remove('hidden');
                kvEvidenceSection.classList.remove('hidden');
                inputKV.value = kvManualSelect.value;
            } else {
                kvDisplayBox.classList.remove('hidden');
                kvSelectBox.classList.add('hidden');
                kvEvidenceSection.classList.add('hidden');
                // KV will be updated by Alpine school search event
            }
        }

        if (isCustomKv) {
            isCustomKv.addEventListener('change', toggleKvMode);
            toggleKvMode(); // Init
        }

        if (kvManualSelect) {
            kvManualSelect.addEventListener('change', function() {
                if (isCustomKv.checked) inputKV.value = this.value;
            });
        }

        // Priority Object Toggle
        const isCustomDt = document.getElementById('is_custom_dt');
        const dtSelectBox = document.getElementById('dt_select_box');
        const dtEvidenceSection = document.getElementById('dt_evidence_section');
        const inputDT = document.getElementById('dt_uu_tien');

        function toggleDtMode() {
            if (isCustomDt && isCustomDt.checked) {
                if (dtSelectBox) dtSelectBox.classList.remove('hidden');
                if (dtEvidenceSection) dtEvidenceSection.classList.remove('hidden');
            } else {
                if (dtSelectBox) dtSelectBox.classList.add('hidden');
                if (dtEvidenceSection) dtEvidenceSection.classList.add('hidden');
                if (inputDT) inputDT.value = '';
            }
        }

        if (isCustomDt) {
            isCustomDt.addEventListener('change', toggleDtMode);
            toggleDtMode(); // Init
        }
    });

    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                if (preview.nextElementSibling) preview.nextElementSibling.classList.add('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>