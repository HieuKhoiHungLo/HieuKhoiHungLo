<?php $title = 'Hồ sơ thí sinh - Thông tin tổng hợp'; include __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-6xl mx-auto pb-20 px-4 sm:px-6">
    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-red-900/5 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-hvu-red to-red-700 p-4 sm:p-8 text-white">
            <h2 class="text-xl sm:text-3xl font-bold uppercase tracking-tight">Hồ sơ thí sinh</h2>
            <p class="text-red-100 mt-1 sm:mt-2 text-sm sm:text-base">Bước 1/<?= $totalStepsCount ?>: Cập nhật thông tin cá nhân và minh chứng định danh.</p>
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
                                <img id="preview_avatar" loading="lazy" src="<?= $user['anh_dai_dien'] ? url($user['anh_dai_dien']) : '' ?>" class="<?= !empty($user['anh_dai_dien']) ? 'block' : 'hidden' ?> w-full h-full object-cover">
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
                             <div class="relative">
                                <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                                <input type="text" id="school_search" class="hvu-input pl-10 mb-2 border-gray-200" placeholder="Nhập tên trường để lọc nhanh...">
                             </div>
                             <select name="ma_truong_lop_12" id="school" class="hvu-input font-bold" required size="5">
                                 <option value="">-- Chọn Tỉnh trường THPT trước --</option>
                             </select>
                             <p class="text-[10px] text-red-500 mt-1 italic">* Nhập tên vào ô tìm kiếm để lọc trường nhanh.</p>
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
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                                <input type="text" id="ward_search" class="hvu-input pl-10 mb-2 border-gray-200" placeholder="Nhập tên xã/phường...">
                            </div>
                            <select name="ma_xa_thuong_tru" id="ward" class="hvu-input font-bold" required size="5">
                                <option value="">-- Chọn Tỉnh trước --</option>
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1 italic">* Chọn Tỉnh/TP trước, sau đó tìm kiếm Xã/Phường.</p>
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
                                <img id="preview_front" loading="lazy" src="<?= $user['anh_cccd_truoc'] ? url($user['anh_cccd_truoc']) : '' ?>" class="<?= !empty($user['anh_cccd_truoc']) ? 'block' : 'hidden' ?> w-full h-full object-contain">
                                <div class="<?= !empty($user['anh_cccd_truoc']) ? 'hidden' : 'flex' ?> flex-col items-center text-gray-400">
                                    <i class="fas fa-upload text-3xl mb-2"></i>
                                    <span class="text-sm font-bold uppercase">Nhấp để tải lên</span>
                                </div>
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                    <span class="text-white text-sm font-bold bg-hvu-red/80 px-4 py-2 rounded-full shadow-lg">Thay đổi file</span>
                                </div>
                                <input type="file" name="cccd_front" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(this, 'preview_front')">
                            </div>
                        </div>

                        <!-- CCCD Back -->
                        <div class="space-y-4">
                            <label class="label text-center"><i class="fas fa-id-card"></i> Mặt sau CCCD/CMND</label>
                            <div class="group relative w-full h-40 sm:h-48 border-2 border-dashed border-gray-300 rounded-2xl flex flex-col justify-center items-center bg-gray-50 overflow-hidden transition-all hover:border-hvu-red/50">
                                <img id="preview_back" loading="lazy" src="<?= $user['anh_cccd_sau'] ? url($user['anh_cccd_sau']) : '' ?>" class="<?= !empty($user['anh_cccd_sau']) ? 'block' : 'hidden' ?> w-full h-full object-contain">
                                <div class="<?= !empty($user['anh_cccd_sau']) ? 'hidden' : 'flex' ?> flex-col items-center text-gray-400">
                                    <i class="fas fa-upload text-3xl mb-2"></i>
                                    <span class="text-sm font-bold uppercase">Nhấp để tải lên</span>
                                </div>
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                    <span class="text-white text-sm font-bold bg-hvu-red/80 px-4 py-2 rounded-full shadow-lg">Thay đổi file</span>
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
                                <img id="preview_kv" loading="lazy" src="<?= !empty($user['file_minh_chung_kv']) ? url($user['file_minh_chung_kv']) : '' ?>" class="<?= !empty($user['file_minh_chung_kv']) ? 'block' : 'hidden' ?> w-full h-full object-contain">
                                <div class="<?= !empty($user['file_minh_chung_kv']) ? 'hidden' : 'flex' ?> flex-col items-center text-red-300">
                                    <i class="fas fa-cloud-upload-alt text-4xl mb-2"></i>
                                    <span class="text-xs font-black uppercase">Tải lên minh chứng Khu vực</span>
                                </div>
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                    <span class="text-white text-xs font-bold bg-hvu-red px-6 py-2 rounded-full shadow-lg">Thay đổi</span>
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
                                <img id="preview_dt" loading="lazy" src="<?= !empty($user['file_minh_chung_dt']) ? url($user['file_minh_chung_dt']) : '' ?>" class="<?= !empty($user['file_minh_chung_dt']) ? 'block' : 'hidden' ?> w-full h-full object-contain">
                                <div class="<?= !empty($user['file_minh_chung_dt']) ? 'hidden' : 'flex' ?> flex-col items-center text-indigo-300">
                                    <i class="fas fa-upload text-4xl mb-2"></i>
                                    <span class="text-xs font-black uppercase">Tải lên minh chứng Đối tượng</span>
                                </div>
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                    <span class="text-white text-xs font-bold bg-indigo-600 px-6 py-2 rounded-full shadow-lg">Thay đổi</span>
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
        @apply block text-gray-800 text-sm font-bold mb-2 flex items-center;
    }
    .label i {
        @apply text-hvu-red mr-2 text-xs;
    }
    /* Fix overlap of icon and text */
    .hvu-input.pl-10 {
        padding-left: 2.5rem !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Handling School Selection
    const provinceSchool = document.getElementById('province_school');
    const schoolSelect = document.getElementById('school');
    const displayKV = document.getElementById('display_kv');
    const inputKV = document.getElementById('kv_uu_tien');

    const currentSchool = "<?= $user['ma_truong_lop_12'] ?? '' ?>";

    provinceSchool.addEventListener('change', function() {
        loadSchools(this.value);
    });

    schoolSelect.addEventListener('change', function() {
        updateKV();
    });

    function loadSchools(provinceId, selectedId = null) {
        if (!provinceId) {
            schoolSelect.innerHTML = '<option value="">-- Chọn Trường --</option>';
            return;
        }

        fetch(`<?= url('/api/schools') ?>?province_id=${provinceId}`)
            .then(response => response.json())
            .then(data => {
                window.allSchools = data; // Store for filtering
                renderSchools(data, selectedId);
            });
    }

    function renderSchools(schools, selectedId = null) {
        let html = '<option value="">-- Chọn Trường --</option>';
        if (schools.length === 0) {
            html = '<option value="">-- Không tìm thấy trường --</option>';
        }
        schools.forEach(item => {
            const isSelected = (selectedId == item.ma_truong || currentSchool == item.ma_truong) ? 'selected' : '';
            html += `<option value="${item.ma_truong}" data-kv="${item.khu_vuc}" ${isSelected}>${item.ten_truong}</option>`;
        });
        schoolSelect.innerHTML = html;
        updateKV();
    }
    
    // Filter Logic
    const schoolSearch = document.getElementById('school_search');
    schoolSearch.addEventListener('input', function(e) {
        const keyword = e.target.value.toLowerCase();
        if (!window.allSchools) return;
        
        const filtered = window.allSchools.filter(s => s.ten_truong.toLowerCase().includes(keyword));
        renderSchools(filtered);
    });

    function updateKV() {
        if (isCustomKv.checked) return; // Do nothing if manual override is active

        const selectedOption = schoolSelect.options[schoolSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.kv) {
            const kv = selectedOption.dataset.kv;
            displayKV.textContent = kv;
            inputKV.value = kv;
        } else {
            displayKV.textContent = '--';
            inputKV.value = '';
        }
    }

    // Manual KV Toggle Logic
    const isCustomKv = document.getElementById('is_custom_kv');
    const kvDisplayBox = document.getElementById('kv_display_box');
    const kvSelectBox = document.getElementById('kv_select_box');
    const kvManualSelect = document.getElementById('kv_manual_select');
    const kvEvidenceSection = document.getElementById('kv_evidence_section');

    function toggleKvMode() {
        if (isCustomKv.checked) {
            kvDisplayBox.classList.add('hidden');
            kvSelectBox.classList.remove('hidden');
            kvEvidenceSection.classList.remove('hidden');
            // Shift value to manual select value
            inputKV.value = kvManualSelect.value;
        } else {
            kvDisplayBox.classList.remove('hidden');
            kvSelectBox.classList.add('hidden');
            kvEvidenceSection.classList.add('hidden');
            updateKV(); // Re-trigger auto update
        }
    }

    isCustomKv.addEventListener('change', toggleKvMode);
    kvManualSelect.addEventListener('change', function() {
        if (isCustomKv.checked) inputKV.value = this.value;
    });

    // --- Handling Priority Object ---
    const isCustomDt = document.getElementById('is_custom_dt');
    const dtSelectBox = document.getElementById('dt_select_box');
    const dtEvidenceSection = document.getElementById('dt_evidence_section');
    const inputDT = document.getElementById('dt_uu_tien');

    function toggleDtMode() {
        if (isCustomDt.checked) {
            dtSelectBox.classList.remove('hidden');
            dtEvidenceSection.classList.remove('hidden');
        } else {
            dtSelectBox.classList.add('hidden');
            dtEvidenceSection.classList.add('hidden');
            inputDT.value = ''; // Reset when not checking
        }
    }

    isCustomDt.addEventListener('change', toggleDtMode);

    // Run once on load to set correct state
    toggleKvMode();
    toggleDtMode();

    // 2. Handling Contact Wards
    const provinceContact = document.getElementById('province_contact');
    const wardSelect = document.getElementById('ward');
    const wardSearch = document.getElementById('ward_search');
    const currentWard = "<?= $user['ma_xa_thuong_tru'] ?? '' ?>";

    provinceContact.addEventListener('change', function() {
        loadWards(this.value);
    });

    if (wardSearch) {
        wardSearch.addEventListener('input', function(e) {
            const keyword = e.target.value.toLowerCase();
            if (!window.allWards) return;
            const filtered = window.allWards.filter(w => w.ten_xa.toLowerCase().includes(keyword));
            renderWards(filtered);
        });
    }

    function loadWards(provinceId, selectedId = null) {
        if (!provinceId) {
            wardSelect.innerHTML = '<option value="">-- Chọn Tỉnh trước --</option>';
            window.allWards = [];
            return;
        }

        if (wardSearch) wardSearch.value = '';

        fetch(`<?= url('/api/wards') ?>?province_id=${provinceId}`)
            .then(response => response.json())
            .then(data => {
                window.allWards = data;
                renderWards(data, selectedId);
            });
    }

    function renderWards(wards, selectedId = null) {
        let html = '<option value="">-- Chọn Xã/Phường --</option>';
        if (wards.length === 0) {
             html = '<option value="">-- Không tìm thấy --</option>';
        }
        wards.forEach(item => {
            const isSelected = (selectedId == item.ma_xa || currentWard == item.ma_xa) ? 'selected' : '';
            html += `<option value="${item.ma_xa}" ${isSelected}>${item.ten_xa}</option>`;
        });
        wardSelect.innerHTML = html;
    }

    // Initial Loads
    if (provinceSchool.value) loadSchools(provinceSchool.value, currentSchool);
    if (provinceContact.value) loadWards(provinceContact.value, currentWard);
});

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            if(preview.nextElementSibling) preview.nextElementSibling.classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
