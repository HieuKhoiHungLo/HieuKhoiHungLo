<!-- View Mode (Redesigned to look like Form) -->
<div id="view_personal" class="animate-in fade-in duration-300">
    <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-xl shadow-slate-50/50 overflow-visible">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Họ tên -->
            <div class="col-span-2 md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Họ và tên</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" disabled value="<?= htmlspecialchars($user['ho_va_ten']) ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none cursor-default">
                </div>
            </div>

            <!-- CCCD -->
            <div class="col-span-2 md:col-span-1">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Số CCCD</label>
                <div class="relative">
                    <i class="fas fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" disabled value="<?= $user['so_cccd'] ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 font-mono tracking-wider cursor-default">
                </div>
            </div>

            <!-- Row: Ngày sinh, Giới tính, Dân tộc -->
            <div class="col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Ngày sinh -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Ngày sinh</label>
                    <div class="relative">
                        <i class="fas fa-calendar-alt absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="text" disabled value="<?= date('d/m/Y', strtotime($user['ngay_sinh'])) ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
                    </div>
                </div>

                <!-- Giới tính -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Giới tính</label>
                    <div class="relative">
                        <i class="fas fa-venus-mars absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="text" disabled value="<?= $user['gioi_tinh'] ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
                    </div>
                </div>

                <!-- Dân tộc -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Dân tộc</label>
                    <div class="relative">
                        <i class="fas fa-users absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="text" disabled value="<?= $user['dan_toc'] ?? '---' ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
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
                        <input type="text" disabled value="<?= $user['dien_thoai'] ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="text" disabled value="<?= $user['email'] ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
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
                    <input type="text" disabled value="<?= $provinceMap[$user['ma_tinh_ho_khau'] ?? ''] ?? ($user['ma_tinh_ho_khau'] ?? 'Chưa cập nhật') ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
                </div>
            </div>

            <!-- Tỉnh/TP (Liên hệ) -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tỉnh/TP Liên lạc</label>
                <div class="relative">
                    <i class="fas fa-map absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" disabled value="<?= $provinceMap[$user['ma_tinh_thuong_tru'] ?? ''] ?? 'Chưa cập nhật' ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
                </div>
            </div>

            <!-- Phường/Xã -->
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Phường/Xã</label>
                <div class="relative">
                    <i class="fas fa-building absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" disabled value="<?= $wardName ?? 'Chưa cập nhật' ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
                </div>
            </div>

            <!-- Địa chỉ chi tiết -->
            <div class="col-span-2">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Địa chỉ chi tiết (Thôn/Xóm/Số nhà)</label>
                <div class="relative">
                    <i class="fas fa-home absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                    <input type="text" disabled value="<?= $user['dia_chi_chi_tiet'] ?? 'Chưa cập nhật' ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 cursor-default">
                </div>
            </div>
        </div>
    </div>
</div>
