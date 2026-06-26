<?php ob_start(); ?>

<div class="p-6">
    <div class="mb-8 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="<?= url('/admin/talent-tests') ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($session['session_name']) ?></h1>
                <p class="text-slate-500 text-sm">Quản lý quy trình tổ chức thi năng khiếu</p>
            </div>
        </div>
        <div class="flex gap-2">
            <form action="<?= url('/admin/talent-tests/toggle-publish') ?>" method="POST" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                <input type="hidden" name="status" value="<?= $session['is_published'] ? 0 : 1 ?>">
                <button type="submit" class="px-5 py-3 <?= $session['is_published'] ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100' ?> border font-bold rounded-2xl hover:opacity-80 transition flex items-center shadow-sm">
                    <i class="fas <?= $session['is_published'] ? 'fa-eye-slash' : 'fa-eye' ?> mr-2"></i> 
                    <?= $session['is_published'] ? 'Hủy công bố' : 'Công bố điểm' ?>
                </button>
            </form>
            <form action="<?= url('/admin/talent-tests/sync') ?>" method="POST" onsubmit="return confirm('Đồng bộ thí sinh đã duyệt vào đợt thi này?')">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                <button type="submit" class="px-5 py-3 bg-emerald-600 text-white font-bold rounded-2xl hover:bg-emerald-700 transition flex items-center shadow-lg shadow-emerald-200">
                    <i class="fas fa-sync-alt mr-2"></i> Đồng bộ thí sinh
                </button>
            </form>
        </div>
    </div>

    <?php if (isset($_GET['synced'])): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl flex justify-between items-center shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-3 text-lg"></i>
                <span class="font-medium">Đồng bộ thành công <?= (int)$_GET['synced'] ?> thí sinh!</span>
            </div>
            <button class="text-emerald-400 hover:text-emerald-600" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['published'])): ?>
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-2xl flex justify-between items-center shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-info-circle mr-3 text-lg"></i>
                <span class="font-medium"><?= $_GET['published'] == 1 ? 'Đã công bố điểm!' : 'Đã hủy công bố điểm.' ?></span>
            </div>
            <button class="text-blue-400 hover:text-blue-600" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-3xl font-black text-slate-800"><?= $stats['total'] ?? 0 ?></div>
            <div class="text-xs text-slate-400 font-bold uppercase mt-1">Tổng thí sinh</div>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-100 p-5">
            <div class="text-3xl font-black text-emerald-600"><?= $stats['eligible'] ?? 0 ?></div>
            <div class="text-xs text-slate-400 font-bold uppercase mt-1">Đủ điều kiện</div>
        </div>
        <div class="bg-white rounded-2xl border border-rose-100 p-5">
            <div class="text-3xl font-black text-rose-500"><?= $stats['ineligible'] ?? 0 ?></div>
            <div class="text-xs text-slate-400 font-bold uppercase mt-1">Không đủ ĐK</div>
        </div>
        <div class="bg-white rounded-2xl border border-blue-100 p-5">
            <div class="text-3xl font-black text-blue-600"><?= $stats['has_sbd'] ?? 0 ?></div>
            <div class="text-xs text-slate-400 font-bold uppercase mt-1">Đã có SBD</div>
        </div>
        <div class="bg-white rounded-2xl border border-amber-100 p-5">
            <div class="text-3xl font-black text-amber-600"><?= $stats['assigned_room'] ?? 0 ?></div>
            <div class="text-xs text-slate-400 font-bold uppercase mt-1">Đã xếp phòng</div>
        </div>
    </div>

    <!-- Workflow Steps -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Step 1: Danh sách xét tuyển -->
        <a href="<?= url('/admin/talent-tests/candidates?session_id=' . $session['id']) ?>" class="bg-white rounded-3xl border-2 border-slate-100 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50 transition-all p-6 group block">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-black text-sm mr-3 group-hover:scale-110 transition">1</div>
                <h3 class="font-bold text-slate-800 text-lg">Danh sách xét tuyển</h3>
            </div>
            <p class="text-sm text-slate-500 mb-3">Quản lý thí sinh đủ / không đủ điều kiện dự thi, duyệt và phân loại.</p>
            <div class="flex items-center text-blue-600 text-sm font-bold">
                <span>Mở trang</span> <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- Step 2: Lập số báo danh -->
        <a href="<?= url('/admin/talent-tests/exam-numbers?session_id=' . $session['id']) ?>" class="bg-white rounded-3xl border-2 border-slate-100 hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-50 transition-all p-6 group block">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-black text-sm mr-3 group-hover:scale-110 transition">2</div>
                <h3 class="font-bold text-slate-800 text-lg">Lập số báo danh</h3>
            </div>
            <p class="text-sm text-slate-500 mb-3">Cấu hình tiền tố, độ dài và đánh SBD tự động cho thí sinh đủ ĐK.</p>
            <div class="flex items-center text-emerald-600 text-sm font-bold">
                <span>Mở trang</span> <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- Step 3: Phân phòng thi -->
        <a href="<?= url('/admin/talent-tests/room-assignment?session_id=' . $session['id']) ?>" class="bg-white rounded-3xl border-2 border-slate-100 hover:border-amber-200 hover:shadow-lg hover:shadow-amber-50 transition-all p-6 group block">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center font-black text-sm mr-3 group-hover:scale-110 transition">3</div>
                <h3 class="font-bold text-slate-800 text-lg">Phân phòng thi</h3>
            </div>
            <p class="text-sm text-slate-500 mb-3">Tạo phòng thi, phân bổ thí sinh vào phòng tự động hoặc thủ công.</p>
            <div class="flex items-center text-amber-600 text-sm font-bold">
                <span>Mở trang</span> <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- Step 4: Tổ chức thi - Môn thi -->
        <a href="<?= url('/admin/talent-tests/exam-config?session_id=' . $session['id']) ?>" class="bg-white rounded-3xl border-2 border-slate-100 hover:border-indigo-200 hover:shadow-lg hover:shadow-indigo-50 transition-all p-6 group block">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-black text-sm mr-3 group-hover:scale-110 transition">4</div>
                <h3 class="font-bold text-slate-800 text-lg">Tổ chức thi - Môn thi</h3>
            </div>
            <p class="text-sm text-slate-500 mb-3">Cấu hình ngày thi, thời gian, hình thức thi cho từng môn.</p>
            <div class="flex items-center text-indigo-600 text-sm font-bold">
                <span>Mở trang</span> <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- Step 5: Nhập điểm -->
        <a href="<?= url('/admin/talent-tests/scores?session_id=' . $session['id']) ?>" class="bg-white rounded-3xl border-2 border-slate-100 hover:border-teal-200 hover:shadow-lg hover:shadow-teal-50 transition-all p-6 group block">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center font-black text-sm mr-3 group-hover:scale-110 transition">5</div>
                <h3 class="font-bold text-slate-800 text-lg">Nhập điểm & Báo cáo</h3>
            </div>
            <p class="text-sm text-slate-500 mb-3">Nhập điểm thi, xuất Excel, xem thống kê và phổ điểm.</p>
            <div class="flex items-center text-teal-600 text-sm font-bold">
                <span>Mở trang</span> <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition"></i>
            </div>
        </a>

        <!-- Step 6: In ấn -->
        <div class="bg-white rounded-3xl border-2 border-slate-100 p-6">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center font-black text-sm mr-3">6</div>
                <h3 class="font-bold text-slate-800 text-lg">In ấn tài liệu</h3>
            </div>
            <div class="space-y-2">
                <a href="<?= url('/admin/talent-tests/print-cards?session_id=' . $session['id']) ?>" target="_blank" class="flex items-center text-sm text-slate-600 hover:text-blue-600 transition py-1">
                    <i class="fas fa-id-card w-5 text-blue-400 mr-2"></i> In Thẻ dự thi
                </a>
                <a href="<?= url('/admin/talent-tests/print-photos?session_id=' . $session['id']) ?>" target="_blank" class="flex items-center text-sm text-slate-600 hover:text-blue-600 transition py-1">
                    <i class="fas fa-images w-5 text-amber-400 mr-2"></i> In Sổ ảnh
                </a>
                <a href="<?= url('/admin/talent-tests/print-room-list?session_id=' . $session['id']) ?>" target="_blank" class="flex items-center text-sm text-slate-600 hover:text-blue-600 transition py-1">
                    <i class="fas fa-door-open w-5 text-emerald-400 mr-2"></i> In DS phòng thi
                </a>
                <a href="<?= url('/admin/talent-tests/print-exam-notice?session_id=' . $session['id']) ?>" target="_blank" class="flex items-center text-sm text-slate-600 hover:text-blue-600 transition py-1">
                    <i class="fas fa-file-alt w-5 text-indigo-400 mr-2"></i> In Giấy báo dự thi
                </a>
                <a href="<?= url('/admin/talent-tests/dashboard?session_id=' . $session['id']) ?>" class="flex items-center text-sm text-slate-600 hover:text-blue-600 transition py-1">
                    <i class="fas fa-chart-bar w-5 text-rose-400 mr-2"></i> Thống kê & Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Subjects Info -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
            <i class="fas fa-book mr-3 text-slate-400"></i> Các môn thi trong đợt này
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($subjects as $sub): ?>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-blue-500 mr-3 shadow-sm text-sm">
                        <i class="fas fa-book"></i>
                    </div>
                    <div>
                        <div class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($sub['subject_name']) ?></div>
                        <div class="text-[10px] text-slate-400 font-mono">Mã: <?= $sub['major_code'] ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
