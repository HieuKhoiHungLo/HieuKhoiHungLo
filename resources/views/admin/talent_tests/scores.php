<?php ob_start(); ?>

<div class="p-6">
    <div class="mb-8 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="<?= url('/admin/talent-tests/edit?id=' . $sessionId) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Quản lý điểm & Kết quả thi</h1>
                <p class="text-slate-500 text-sm">Nhập điểm cho từng thí sinh và xuất file báo cáo tổng hợp.</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('/admin/talent-tests/export-excel?session_id=' . $sessionId) ?>" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition flex items-center">
                <i class="fas fa-file-excel mr-2 text-emerald-600"></i> Xuất Excel
            </a>
            <button class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition flex items-center">
                <i class="fas fa-file-pdf mr-2 text-rose-600"></i> Xuất PDF
            </a>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">SBD</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Thí sinh</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Môn thi</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Phòng thi</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center" style="width: 150px;">Điểm số</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Ghi chú</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($assignments)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-20 text-center text-slate-400 italic">
                                Chưa có dữ liệu thí sinh. Hãy thực hiện đồng bộ thí sinh trước.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assignments as $a): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <span class="font-mono font-bold text-blue-600"><?= htmlspecialchars($a['exam_number']) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800"><?= htmlspecialchars($a['name']) ?></div>
                                    <div class="text-xs text-slate-400">CCCD: <?= htmlspecialchars($a['cccd']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <?= htmlspecialchars($a['subject_name']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <span class="px-2 py-1 bg-slate-100 rounded-lg"><?= htmlspecialchars($a['room_name'] ?: '--') ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="number" step="0.01" 
                                           onchange="saveScore(<?= $a['id'] ?>, this.value)"
                                           value="<?= $a['score'] ?>"
                                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-center font-bold text-slate-700"
                                           placeholder="0.00">
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" 
                                           onchange="saveNote(<?= $a['id'] ?>, this.value)"
                                           value="<?= htmlspecialchars($a['note'] ?: '') ?>"
                                           class="w-full px-3 py-2 bg-transparent border-b border-transparent focus:border-slate-300 transition outline-none text-sm text-slate-600"
                                           placeholder="Thêm ghi chú...">
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div id="status-<?= $a['id'] ?>">
                                        <?php if ($a['score'] !== null): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                                <i class="fas fa-check mr-1"></i> Đã nhập
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-400">
                                                Chưa có điểm
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    async function saveScore(assignmentId, score) {
        const formData = new FormData();
        formData.append('assignment_id', assignmentId);
        formData.append('score', score);
        formData.append('ajax', '1');
        formData.append('csrf_token', '<?= csrf_token() ?>');

        try {
            const response = await fetch('<?= url('/admin/talent-tests/scores/save') ?>', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                const statusDiv = document.getElementById('status-' + assignmentId);
                statusDiv.innerHTML = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800"><i class="fas fa-check mr-1"></i> Vừa cập nhật</span>';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Lỗi khi lưu điểm!');
        }
    }

    async function saveNote(assignmentId, note) {
        // Logic lưu ghi chú tương tự saveScore nếu cần
    }
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
