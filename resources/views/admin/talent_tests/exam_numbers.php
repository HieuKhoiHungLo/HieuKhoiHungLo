<?php ob_start(); ?>

<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="<?= url('/admin/talent-tests/edit?id=' . $session['id']) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Lập số báo danh</h1>
                <p class="text-slate-500 text-sm"><?= htmlspecialchars($session['session_name']) ?></p>
            </div>
        </div>
        <a href="<?= url('/admin/talent-tests/room-assignment?session_id=' . $session['id']) ?>" class="px-4 py-2 bg-amber-600 text-white text-sm font-bold rounded-xl hover:bg-amber-700 transition flex items-center">
            <i class="fas fa-arrow-right mr-2"></i> Bước tiếp: Phân phòng
        </a>
    </div>

    <?php if (isset($_GET['generated'])): ?>
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex justify-between items-center text-sm">
            <span><i class="fas fa-check-circle mr-2"></i>Đã lập SBD cho <?= (int)$_GET['generated'] ?> thí sinh.</span>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-600"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['cleared'])): ?>
        <div class="mb-4 p-3 bg-amber-50 border border-amber-200 text-amber-700 rounded-xl flex justify-between items-center text-sm">
            <span><i class="fas fa-info-circle mr-2"></i>Đã xóa SBD của <?= (int)$_GET['cleared'] ?> thí sinh.</span>
            <button onclick="this.parentElement.remove()" class="text-amber-400 hover:text-amber-600"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <!-- Config & Actions -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
        <div class="flex flex-wrap items-end gap-6">
            <div>
                <div class="text-xs font-bold text-slate-500 uppercase mb-1">SBD lớn nhất</div>
                <div class="text-lg font-mono font-bold text-blue-600"><?= htmlspecialchars($maxSbd) ?></div>
            </div>
            <form action="<?= url('/admin/talent-tests/generate-exam-numbers') ?>" method="POST" class="flex flex-wrap items-end gap-4 flex-1">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tiền tố SBD</label>
                    <input type="text" name="prefix" value="<?= htmlspecialchars($prefix) ?>" 
                           class="w-36 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Độ dài số</label>
                    <input type="number" name="length" value="<?= $length ?>" min="1" max="6"
                           class="w-20 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-center focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Đánh SBD từ</label>
                    <input type="number" name="start_from" value="<?= $startFrom ?>" min="1"
                           class="w-24 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-center focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                    <i class="fas fa-hashtag mr-1"></i> Lập SBD
                </button>
            </form>
            <form action="<?= url('/admin/talent-tests/clear-exam-numbers') ?>" method="POST" onsubmit="return confirm('Xóa toàn bộ SBD đã đánh?')">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                <button type="submit" class="px-4 py-2 bg-rose-100 text-rose-600 text-sm font-bold rounded-xl hover:bg-rose-200 transition">
                    <i class="fas fa-trash mr-1"></i> Xóa danh sách dự thi
                </button>
            </form>
        </div>
    </div>

    <!-- Candidates Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-blue-50/50">
            <h2 class="font-bold text-slate-800">
                <i class="fas fa-list-ol text-blue-500 mr-2"></i>
                DANH SÁCH ĐỦ ĐIỀU KIỆN DỰ THI: <?= count($candidates) ?> THÍ SINH
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-3 py-3 text-xs font-bold text-slate-500 uppercase w-10">STT</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Mã hồ sơ</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Họ tên</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Ngày sinh</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Số CCCD</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Ngành tuyển sinh</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-center">Số báo danh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($candidates)): ?>
                        <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400 italic">Chưa có thí sinh đủ điều kiện.</td></tr>
                    <?php else: ?>
                        <?php foreach ($candidates as $i => $c): ?>
                            <tr class="border-b border-slate-50 hover:bg-blue-50/30 transition">
                                <td class="px-3 py-2.5 text-slate-400"><?= $i + 1 ?></td>
                                <td class="px-4 py-2.5 font-mono text-xs text-blue-600"><?= htmlspecialchars($c['application_code'] ?? '--') ?></td>
                                <td class="px-4 py-2.5 font-bold text-slate-800"><?= htmlspecialchars($c['name']) ?></td>
                                <td class="px-4 py-2.5 text-slate-600"><?= $c['birth_date'] ? date('d/m/Y', strtotime($c['birth_date'])) : '--' ?></td>
                                <td class="px-4 py-2.5 font-mono text-xs"><?= htmlspecialchars($c['cccd'] ?? '--') ?></td>
                                <td class="px-4 py-2.5 text-slate-600"><?= htmlspecialchars($c['subject_name']) ?></td>
                                <td class="px-4 py-2.5 text-center">
                                    <?php if (!empty($c['exam_number'])): ?>
                                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg font-mono font-bold text-xs"><?= htmlspecialchars($c['exam_number']) ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-300 text-xs">--</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
