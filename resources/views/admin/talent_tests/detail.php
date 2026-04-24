<?php ob_start(); ?>

<div class="p-6">
    <div class="mb-8 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="<?= url('/admin/talent-tests') ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($session['session_name']) ?></h1>
                <p class="text-slate-500 text-sm">Quản lý đồng bộ thí sinh, phân phòng và in thẻ dự thi.</p>
            </div>
        </div>
        <div class="flex gap-2">
            <form action="<?= url('/admin/talent-tests/toggle-publish') ?>" method="POST" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                <input type="hidden" name="status" value="<?= $session['is_published'] ? 0 : 1 ?>">
                <button type="submit" class="px-5 py-3 <?= $session['is_published'] ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100' ?> border font-bold rounded-2xl hover:opacity-80 transition flex items-center shadow-sm">
                    <i class="fas <?= $session['is_published'] ? 'fa-eye-slash' : 'fa-eye' ?> mr-2"></i> 
                    <?= $session['is_published'] ? 'Hủy công bố điểm' : 'Công bố điểm ngay' ?>
                </button>
            </form>
            <a href="<?= url('/admin/talent-tests/dashboard?session_id=' . $session['id']) ?>" class="px-5 py-3 bg-white border border-slate-200 text-slate-600 font-bold rounded-2xl hover:bg-slate-50 transition shadow-sm flex items-center">
                <i class="fas fa-chart-bar mr-2 text-blue-500"></i> Thống kê
            </a>
            <a href="<?= url('/admin/talent-tests/scores?session_id=' . $session['id']) ?>" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center">
                <i class="fas fa-edit mr-2"></i> Nhập điểm & Báo cáo
            </a>
        </div>
    </div>

    <?php if (isset($_GET['published'])): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl flex justify-between items-center shadow-sm animate-fade-in">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-3 text-lg"></i>
                <span class="font-medium"><?= $_GET['published'] == 1 ? 'Đã công bố điểm cho thí sinh tra cứu!' : ' Đã hủy trạng thái công bố điểm.' ?></span>
            </div>
            <button class="text-emerald-400 hover:text-emerald-600" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['synced'])): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl flex justify-between items-center shadow-sm animate-fade-in">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-3 text-lg"></i>
                <span class="font-medium">Đồng bộ thành công <?= (int)$_GET['synced'] ?> thí sinh vào đợt thi này!</span>
            </div>
            <button class="text-emerald-400 hover:text-emerald-600" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
    <?php if (isset($_GET['assigned'])): ?>
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-2xl flex justify-between items-center shadow-sm animate-fade-in">
            <div class="flex items-center">
                <i class="fas fa-info-circle mr-3 text-lg"></i>
                <span class="font-medium">Đã xếp chỗ thành công cho <?= (int)$_GET['assigned'] ?> thí sinh vào các phòng thi!</span>
            </div>
            <button class="text-blue-400 hover:text-blue-600" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['bags'])): ?>
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-2xl flex justify-between items-center shadow-sm animate-fade-in">
            <div class="flex items-center">
                <i class="fas fa-shopping-bag mr-3 text-lg"></i>
                <span class="font-medium">Đã thực hiện đóng túi & gán mã mật mã cho <?= (int)$_GET['bags'] ?> bài thi!</span>
            </div>
            <button class="text-amber-400 hover:text-amber-600" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cột trái: Thông tin môn thi & Đồng bộ -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-slate-800">Các môn thi & Thí sinh</h2>
                    <form action="<?= url('/admin/talent-tests/sync') ?>" method="POST" onsubmit="return confirm('Hệ đồng sẽ lấy thí sinh đã duyệt thuộc các ngành này để đưa vào danh sách thi. Tiếp tục?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-xl hover:bg-emerald-700 transition flex items-center shadow-lg shadow-emerald-100">
                            <i class="fas fa-sync-alt mr-2"></i> Đồng bộ thí sinh ngay
                        </button>
                    </form>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($subjects as $sub): ?>
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex items-center">
                                <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-blue-500 mr-4 shadow-sm">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-700"><?= htmlspecialchars($sub['subject_name']) ?></div>
                                    <div class="text-xs text-slate-400 font-mono">Mã ngành: <?= $sub['major_code'] ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Danh sách thí sinh dự kiến (hoặc các chức năng in ấn) -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
                <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                    <i class="fas fa-print mr-3 text-slate-400"></i> In ấn tài liệu thi
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="<?= url('/admin/talent-tests/print-cards?session_id=' . $session['id']) ?>" target="_blank" class="p-6 rounded-3xl border-2 border-slate-50 hover:border-blue-100 hover:bg-blue-50/30 transition text-left group block">
                        <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                            <i class="fas fa-id-card text-xl"></i>
                        </div>
                        <h3 class="font-bold text-slate-800 mb-1">In Thẻ dự thi</h3>
                        <p class="text-xs text-slate-500">Xuất PDF định dạng A4 chứa thẻ dự thi của tất cả thí sinh (kèm logo & ảnh).</p>
                    </a>
                    <a href="<?= url('/admin/talent-tests/print-photos?session_id=' . $session['id']) ?>" target="_blank" class="p-6 rounded-3xl border-2 border-slate-50 hover:border-amber-100 hover:bg-amber-50/30 transition text-left group block">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                            <i class="fas fa-images text-xl"></i>
                        </div>
                        <h3 class="font-bold text-slate-800 mb-1">In Sổ ảnh</h3>
                        <p class="text-xs text-slate-500">Danh sách kèm ảnh để giám thị kiểm tra thí sinh tại phòng thi.</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Cột phải: Quản lý phòng thi -->
        <div class="space-y-8">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden" id="rooms">
                <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h2 class="text-lg font-bold text-slate-800">Phòng thi</h2>
                    <div class="flex gap-1">
                        <form action="<?= url('/admin/talent-tests/auto-assign') ?>" method="POST" title="Phân phòng tự động">
                            <?= csrf_field() ?>
                            <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                            <button type="submit" class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center hover:bg-emerald-700 transition">
                                <i class="fas fa-magic text-xs"></i>
                            </button>
                        </form>
                        <form action="<?= url('/admin/talent-tests/assign-bags') ?>" method="POST" title="Đánh mã túi bài thi">
                            <?= csrf_field() ?>
                            <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                            <input type="hidden" name="prefix" value="TUI-">
                            <button type="submit" class="w-8 h-8 rounded-lg bg-amber-600 text-white flex items-center justify-center hover:bg-amber-700 transition">
                                <i class="fas fa-shopping-bag text-xs"></i>
                            </button>
                        </form>
                        <button onclick="document.getElementById('roomModal').classList.remove('hidden')" class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition" title="Thêm phòng thủ công">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (empty($rooms)): ?>
                        <div class="py-10 text-center text-slate-400 italic text-sm">Chưa có phòng thi.</div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($rooms as $r): ?>
                                <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 mr-3 text-xs">
                                            <i class="fas fa-door-open"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($r['room_name']) ?></div>
                                            <div class="text-[10px] text-slate-400 uppercase tracking-tighter">Sức chứa: <?= $r['capacity'] ?> chỗ</div>
                                        </div>
                                    </div>
                                    <button class="text-slate-300 hover:text-rose-500 transition">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal thêm phòng -->
<div id="roomModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center">
            <h2 class="text-xl font-bold text-slate-800">Thêm phòng thi mới</h2>
            <button onclick="document.getElementById('roomModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="<?= url('/admin/talent-tests/rooms/save') ?>" method="POST" class="p-8 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tên phòng thi</label>
                <input type="text" name="room_name" required placeholder="Ví dụ: Phòng 101 - Nhà A"
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-bold">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Sức chứa (Thí sinh)</label>
                <input type="number" name="capacity" required value="40"
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-bold">
            </div>
            <div class="pt-4">
                <button type="submit" class="w-full py-4 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                    Lưu phòng thi
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
