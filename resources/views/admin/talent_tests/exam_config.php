<?php ob_start(); ?>

<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="<?= url('/admin/talent-tests/edit?id=' . $session['id']) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Tổ chức thi - Môn thi</h1>
                <p class="text-slate-500 text-sm"><?= htmlspecialchars($session['session_name']) ?></p>
            </div>
        </div>
        <a href="<?= url('/admin/talent-tests/scores?session_id=' . $session['id']) ?>" class="px-4 py-2 bg-teal-600 text-white text-sm font-bold rounded-xl hover:bg-teal-700 transition flex items-center">
            <i class="fas fa-arrow-right mr-2"></i> Bước tiếp: Nhập điểm
        </a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex justify-between items-center text-sm shadow-sm">
            <span><i class="fas fa-check-circle mr-2"></i>Lưu cấu hình môn thi thành công!</span>
            <button onclick="this.parentElement.remove()" class="opacity-50 hover:opacity-100"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Danh sách môn thi -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 bg-slate-50 border-b border-slate-100">
                    <h3 class="font-bold text-slate-700 uppercase text-xs">CHỌN NGÀNH / MÔN THI</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($subjects as $s): ?>
                        <a href="<?= url('/admin/talent-tests/exam-config?session_id=' . $session['id'] . '&subject_id=' . $s['id']) ?>" 
                           class="block p-4 hover:bg-blue-50 transition <?= $currentSubject && $currentSubject['id'] == $s['id'] ? 'bg-blue-50 border-l-4 border-blue-500' : 'border-l-4 border-transparent' ?>">
                            <div class="font-bold text-slate-800 text-sm mb-1"><?= htmlspecialchars($s['subject_name']) ?></div>
                            <div class="text-xs text-slate-500 font-mono">Mã: <?= $s['major_code'] ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if ($currentSubject): ?>
        <!-- Nội dung cấu hình môn được chọn -->
        <div class="lg:col-span-9 space-y-6">
            
            <!-- Cấu hình thời gian & hình thức -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="font-bold text-slate-800 text-lg mb-4 flex items-center">
                    <i class="fas fa-cogs text-slate-400 mr-2"></i> Cấu hình môn: <span class="text-blue-600 ml-2"><?= htmlspecialchars($currentSubject['subject_name']) ?></span>
                </h2>
                
                <form action="<?= url('/admin/talent-tests/save-exam-config') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                    <input type="hidden" name="subject_id" value="<?= $currentSubject['id'] ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Hình thức thi</label>
                            <select name="exam_type" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none">
                                <option value="written" <?= ($currentSubject['exam_type'] ?? '') == 'written' ? 'selected' : '' ?>>Thi tự luận (Viết)</option>
                                <option value="practice" <?= ($currentSubject['exam_type'] ?? '') == 'practice' ? 'selected' : '' ?>>Thực hành / Biểu diễn</option>
                                <option value="interview" <?= ($currentSubject['exam_type'] ?? '') == 'interview' ? 'selected' : '' ?>>Phỏng vấn / Vấn đáp</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Thời gian làm bài (phút)</label>
                            <input type="number" name="duration_minutes" value="<?= $currentSubject['duration_minutes'] ?? 120 ?>" min="0" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Ngày thi</label>
                            <input type="date" name="exam_date" value="<?= $currentSubject['exam_date'] ?? '' ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Giờ bắt đầu</label>
                                <input type="time" name="exam_time" value="<?= $currentSubject['exam_time'] ?? '' ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">TG thủ tục (phút)</label>
                                <input type="number" name="preparation_minutes" value="<?= $currentSubject['preparation_minutes'] ?? 15 ?>" min="0" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 outline-none">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i> Lưu cấu hình
                    </button>
                </form>
            </div>

            <!-- Dashboard Phòng thi của môn này -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-emerald-50/50 flex justify-between items-center">
                    <h2 class="font-bold text-slate-800 flex items-center">
                        <i class="fas fa-door-open text-emerald-500 mr-2"></i> SỐ LIỆU PHÒNG THI & THÍ SINH
                    </h2>
                    <span class="text-sm font-bold text-slate-500"><?= count($subjectCandidates) ?> thí sinh / <?= count($subjectRooms) ?> phòng</span>
                </div>
                <div class="p-6">
                    <?php if (empty($subjectRooms)): ?>
                        <div class="text-center text-slate-500 py-4 italic">Chưa có phòng thi nào chứa thí sinh môn này. Vui lòng phân phòng trước.</div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <?php foreach ($subjectRooms as $r): ?>
                                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                                    <div class="text-lg font-bold text-slate-800 mb-1">Phòng <?= htmlspecialchars($r['room_name']) ?></div>
                                    <div class="text-sm text-slate-500">Số lượng: <span class="font-bold text-blue-600"><?= $r['current_count'] ?> TS</span></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="flex flex-wrap gap-3">
                            <a href="<?= url('/admin/talent-tests/print-room-list?session_id=' . $session['id']) ?>" target="_blank" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition border border-slate-200">
                                <i class="fas fa-print mr-2 text-slate-500"></i> In danh sách phòng thi
                            </a>
                            <a href="<?= url('/admin/talent-tests/print-exam-notice?session_id=' . $session['id']) ?>" target="_blank" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition border border-slate-200">
                                <i class="fas fa-file-alt mr-2 text-slate-500"></i> In giấy báo dự thi
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php else: ?>
        <div class="lg:col-span-9 bg-white rounded-2xl shadow-sm border border-slate-200 p-10 text-center flex flex-col justify-center items-center">
            <i class="fas fa-hand-pointer text-4xl text-slate-200 mb-4"></i>
            <p class="text-slate-500 text-lg">Chọn một môn thi ở cột trái để bắt đầu cấu hình.</p>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
