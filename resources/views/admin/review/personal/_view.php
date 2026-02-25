<!-- View Mode -->
<div id="view_personal" class="space-y-5">

    <!-- Hero: Họ và tên -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#0066FF] to-indigo-500 px-6 py-5 shadow-lg shadow-blue-200/50">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-1/2 w-24 h-24 bg-white/5 rounded-full translate-y-1/2"></div>
        <span class="block text-[11px] font-bold text-blue-100 uppercase tracking-widest mb-1"><i class="fas fa-user-tag mr-1.5"></i>Họ và tên thí sinh</span>
        <div class="flex justify-between items-center">
            <span class="font-black text-white text-2xl tracking-tight drop-shadow"><?= htmlspecialchars($user['ho_va_ten']) ?></span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 rounded-lg text-white font-bold text-sm tracking-widest border border-white/30 backdrop-blur-sm shadow-sm" title="Số Căn cước công dân">
                <i class="fas fa-id-card text-blue-100"></i> <?= $user['so_cccd'] ?>
            </span>
        </div>
    </div>

    <!-- Row: Ngày sinh / Giới tính / Dân tộc -->
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex">
            <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
            <div class="px-4 py-2">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[10px]"><i class="fas fa-calendar-alt"></i></span>
                    <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Ngày sinh</span>
                </div>
                <span class="font-bold text-slate-800 text-lg"><?= date('d/m/Y', strtotime($user['ngay_sinh'])) ?></span>
            </div>
        </div>
        <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex">
            <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
            <div class="px-4 py-2">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[10px]"><i class="fas fa-venus-mars"></i></span>
                    <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Giới tính</span>
                </div>
                <span class="font-bold text-slate-800 text-lg"><?= $user['gioi_tinh'] ?></span>
            </div>
        </div>
        <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex">
            <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
            <div class="px-4 py-2">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[10px]"><i class="fas fa-users"></i></span>
                    <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Dân tộc</span>
                </div>
                <span class="font-bold text-slate-800 text-lg"><?= $user['dan_toc'] ?? '---' ?></span>
            </div>
        </div>
    </div>

    <!-- Row: Điện thoại / Email -->
    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex">
            <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
            <div class="px-4 py-3.5">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[10px]"><i class="fas fa-phone-alt"></i></span>
                    <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Số điện thoại</span>
                </div>
                <span class="font-bold text-slate-800 text-lg tracking-wide"><?= $user['dien_thoai'] ?></span>
            </div>
        </div>
        <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex">
            <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
            <div class="px-4 py-2">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[10px]"><i class="fas fa-envelope"></i></span>
                    <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Email</span>
                </div>
                <span class="font-bold text-slate-800 text-base"><?= $user['email'] ?></span>
            </div>
        </div>
    </div>

    <!-- Row: Hộ khẩu thường trú / Địa chỉ liên hệ -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Hộ khẩu thường trú (1 phần) -->
        <div class="md:col-span-1 rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex">
            <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
            <div class="px-4 py-2 w-full">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[10px]"><i class="fas fa-home"></i></span>
                    <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Hộ khẩu thường trú</span>
                </div>
                <div class="font-bold text-slate-800 text-base break-words">
                    <?= $provinceMap[$user['ma_tinh_ho_khau'] ?? ''] ?? ($user['ma_tinh_ho_khau'] ?? 'Chưa cập nhật') ?>
                </div>
            </div>
        </div>

        <!-- Địa chỉ liên hệ (2 phần) -->
        <div class="md:col-span-2 rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex">
            <div class="w-1.5 bg-gradient-to-b from-sky-400 to-cyan-500 shrink-0"></div>
            <div class="px-4 py-2 w-full">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center text-[10px]"><i class="fas fa-map-marker-alt"></i></span>
                    <span class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Địa chỉ liên hệ</span>
                </div>
                <div class="font-bold text-slate-800 text-base break-words">
                    <?php 
                        $fullAddress = [];
                        if(!empty($user['dia_chi_chi_tiet'])) $fullAddress[] = $user['dia_chi_chi_tiet'];
                        if(!empty($wardName)) $fullAddress[] = $wardName;
                        if(!empty($user['ma_tinh_thuong_tru']) && isset($provinceMap[$user['ma_tinh_thuong_tru']])) {
                            $fullAddress[] = $provinceMap[$user['ma_tinh_thuong_tru']];
                        }
                        echo !empty($fullAddress) ? implode(', ', $fullAddress) : 'Chưa cập nhật';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
