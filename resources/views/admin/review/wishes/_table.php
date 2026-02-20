<!-- Wishes List — Card Style -->
<?php if (empty($choices)): ?>
    <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-12 text-center">
        <div class="w-14 h-14 mx-auto mb-3 bg-slate-50 rounded-xl flex items-center justify-center">
            <i class="fas fa-inbox text-xl text-slate-300"></i>
        </div>
        <p class="text-sm font-bold text-slate-400">Chưa đăng ký nguyện vọng nào</p>
    </div>
<?php else: ?>
    <div class="space-y-3">
        <?php foreach ($choices as $index => $wish):
            $status = $wish['trang_thai'] ?? 'Chờ duyệt';
            $statusCfg = match($status) {
                'Đã duyệt'  => ['bg-emerald-50 text-emerald-600 border-emerald-200', 'fa-check-circle', 'from-emerald-400 to-teal-500'],
                'Từ chối'    => ['bg-rose-50 text-rose-600 border-rose-200', 'fa-times-circle', 'from-rose-400 to-pink-500'],
                default      => ['bg-amber-50 text-amber-600 border-amber-200', 'fa-clock', 'from-amber-400 to-orange-500'],
            };
        ?>
        <div class="rounded-xl border border-blue-100 bg-white shadow-sm overflow-hidden flex group hover:shadow-md hover:border-blue-200 transition-all">
            <!-- Color Bar -->
            <div class="w-1.5 bg-gradient-to-b <?= $statusCfg[2] ?> shrink-0"></div>

            <!-- Content -->
            <div class="flex-1 flex items-center gap-5 px-5 py-4">
                <!-- NV Number -->
                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-sm text-slate-700 group-hover:bg-[#0066FF] group-hover:text-white transition-all shrink-0">
                    <?= $index + 1 ?>
                </div>

                <!-- Ngành Info -->
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-slate-800 text-base truncate"><?= $wish['ten_nganh'] ?></div>
                    <div class="flex items-center gap-3 mt-1.5">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-sky-50 text-sky-600 rounded text-[10px] font-bold border border-sky-100">
                            <i class="fas fa-barcode text-[8px]"></i> <?= $wish['ma_nganh'] ?>
                        </span>
                        <?php if (!empty($wish['to_hop_mon'])): ?>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-blue-50 text-blue-600 rounded text-[10px] font-bold border border-blue-100">
                            <i class="fas fa-puzzle-piece text-[8px]"></i> <?= $wish['to_hop_mon'] ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status Badge -->
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold rounded-full border shrink-0 <?= $statusCfg[0] ?>">
                    <i class="fas <?= $statusCfg[1] ?> text-[9px]"></i> <?= $status ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
