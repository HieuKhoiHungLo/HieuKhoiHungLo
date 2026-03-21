<!-- View Mode -->
<div id="view_personal" class="animate-in fade-in duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60 rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#0066FF]/10 text-[#0066FF] flex items-center justify-center">
                    <i class="fas fa-user-circle text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Thông tin ứng viên</p>
                    <p class="text-sm font-bold text-slate-700">Dữ liệu cá nhân & Liên hệ</p>
                </div>
            </div>
            <div id="btn_group_personal">
                <button type="button" onclick="toggleEdit('personal')" 
                    class="px-5 py-2.5 bg-[#0066FF] text-white font-bold rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center gap-2 text-sm">
                    <i class="fas fa-edit"></i> Sửa thông tin
                </button>
            </div>
        </div>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Họ tên -->
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        <i class="fas fa-user mr-1 text-[#0066FF]"></i> Họ và tên
                    </label>
                    <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800">
                        <?= htmlspecialchars($user['ho_va_ten']) ?>
                    </div>
                </div>

                <!-- Số CCCD -->
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        <i class="fas fa-id-card mr-1 text-[#0066FF]"></i> Số CCCD
                    </label>
                    <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 font-mono tracking-wider">
                        <?= $user['so_cccd'] ?>
                    </div>
                </div>

                <!-- Row: Ngày sinh, Giới tính, Dân tộc -->
                <div class="col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Ngày sinh -->
                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            <i class="fas fa-calendar-alt mr-1 text-[#0066FF]"></i> Ngày sinh
                        </label>
                        <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800">
                            <?= date('d/m/Y', strtotime($user['ngay_sinh'])) ?>
                        </div>
                    </div>

                    <!-- Giới tính -->
                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            <i class="fas fa-venus-mars mr-1 text-[#0066FF]"></i> Giới tính
                        </label>
                        <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 text-center">
                            <?= $user['gioi_tinh'] ?>
                        </div>
                    </div>

                    <!-- Dân tộc -->
                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            <i class="fas fa-users mr-1 text-[#0066FF]"></i> Dân tộc
                        </label>
                        <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 text-center">
                            <?= $user['dan_toc'] ?? '<span class="text-slate-300">---</span>' ?>
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
                        <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800">
                            <?= $user['dien_thoai'] ?>
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            <i class="fas fa-envelope mr-1 text-sky-500"></i> Email
                        </label>
                        <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800">
                            <?= $user['email'] ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Hộ khẩu thường trú (Tỉnh) -->
                <div class="col-span-2">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Hộ khẩu thường trú (Tỉnh)</label>
                    <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800">
                        <?= $provinceMap[$user['ma_tinh_ho_khau'] ?? ''] ?? ($user['ma_tinh_ho_khau'] ?? '<span class="text-slate-300">Chưa cập nhật</span>') ?>
                    </div>
                </div>

                <!-- Tỉnh/TP Liên lạc -->
                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Tỉnh/TP Liên lạc</label>
                    <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800">
                        <?= $provinceMap[$user['ma_tinh_thuong_tru'] ?? ''] ?? '<span class="text-slate-300">Chưa cập nhật</span>' ?>
                    </div>
                </div>

                <!-- Phường/Xã -->
                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Phường/Xã</label>
                    <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800">
                        <?= $wardName ?? '<span class="text-slate-300">Chưa cập nhật</span>' ?>
                    </div>
                </div>

                <!-- Địa chỉ chi tiết -->
                <div class="col-span-2">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Địa chỉ chi tiết (Thôn/Xóm/Số nhà)</label>
                    <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 italic">
                        <?= $user['dia_chi_chi_tiet'] ?? '<span class="text-slate-300">Chưa cập nhật</span>' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
