<?php ob_start(); ?>

<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="<?= url('/admin/talent-tests/edit?id=' . $session['id']) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Danh sách xét tuyển</h1>
                <p class="text-slate-500 text-sm"><?= htmlspecialchars($session['session_name']) ?></p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('/admin/talent-tests/exam-numbers?session_id=' . $session['id']) ?>" class="px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-xl hover:bg-emerald-700 transition flex items-center">
                <i class="fas fa-arrow-right mr-2"></i> Bước tiếp: Lập SBD
            </a>
        </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex justify-between items-center text-sm">
            <span><i class="fas fa-check-circle mr-2"></i>Đã cập nhật <?= (int)$_GET['updated'] ?> thí sinh.</span>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-600"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <!-- BẢNG ĐỦ ĐIỀU KIỆN -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-100 bg-emerald-50/50 flex justify-between items-center">
            <h2 class="font-bold text-slate-800 flex items-center">
                <i class="fas fa-check-circle text-emerald-500 mr-2"></i>
                DANH SÁCH ĐỦ ĐIỀU KIỆN DỰ THI: <span class="text-emerald-600 ml-1"><?= count($eligible) ?> THÍ SINH</span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" id="eligibleTable">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-3 py-3 w-10"><input type="checkbox" id="checkAllEligible" class="w-4 h-4 rounded"></th>
                        <th class="px-3 py-3 text-xs font-bold text-slate-500 uppercase w-10">STT</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Mã hồ sơ</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Họ tên</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Ngày sinh</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Giới tính</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Số CCCD</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Ngành tuyển sinh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eligible)): ?>
                        <tr><td colspan="8" class="px-6 py-10 text-center text-slate-400 italic">Chưa có thí sinh. Hãy đồng bộ trước.</td></tr>
                    <?php else: ?>
                        <?php foreach ($eligible as $i => $c): ?>
                            <tr class="border-b border-slate-50 hover:bg-blue-50/30 transition">
                                <td class="px-3 py-2.5"><input type="checkbox" class="eligible-check w-4 h-4 rounded" value="<?= $c['id'] ?>"></td>
                                <td class="px-3 py-2.5 text-slate-400"><?= $i + 1 ?></td>
                                <td class="px-4 py-2.5 font-mono text-xs text-blue-600"><?= htmlspecialchars($c['application_code'] ?? '--') ?></td>
                                <td class="px-4 py-2.5 font-bold text-slate-800"><?= htmlspecialchars($c['name']) ?></td>
                                <td class="px-4 py-2.5 text-slate-600"><?= $c['birth_date'] ? date('d/m/Y', strtotime($c['birth_date'])) : '--' ?></td>
                                <td class="px-4 py-2.5 text-slate-600"><?= htmlspecialchars($c['gender'] ?? '--') ?></td>
                                <td class="px-4 py-2.5 font-mono text-xs"><?= htmlspecialchars($c['cccd'] ?? '--') ?></td>
                                <td class="px-4 py-2.5 text-slate-600"><?= htmlspecialchars($c['subject_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- THANH HÀNH ĐỘNG Ở GIỮA -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6 flex flex-wrap items-center gap-3">
        <label class="text-sm font-bold text-slate-600 mr-1">Lý do:</label>
        <input type="text" id="reasonInput" placeholder="Nhập lý do không đủ điều kiện..." 
               class="flex-1 min-w-[250px] px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 outline-none">
        <button onclick="markIneligible()" class="px-4 py-2 bg-rose-600 text-white text-sm font-bold rounded-xl hover:bg-rose-700 transition flex items-center">
            <i class="fas fa-arrow-down mr-2"></i> Đánh dấu không đủ ĐK
        </button>
        <button onclick="markEligible()" class="px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-xl hover:bg-emerald-700 transition flex items-center">
            <i class="fas fa-arrow-up mr-2"></i> Khôi phục đủ ĐK
        </button>
    </div>

    <!-- BẢNG KHÔNG ĐỦ ĐIỀU KIỆN -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-rose-50/50 flex justify-between items-center">
            <h2 class="font-bold text-slate-800 flex items-center">
                <i class="fas fa-times-circle text-rose-500 mr-2"></i>
                DANH SÁCH KHÔNG ĐỦ ĐIỀU KIỆN DỰ THI: <span class="text-rose-600 ml-1"><?= count($ineligible) ?> THÍ SINH</span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" id="ineligibleTable">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-3 py-3 w-10"><input type="checkbox" id="checkAllIneligible" class="w-4 h-4 rounded"></th>
                        <th class="px-3 py-3 text-xs font-bold text-slate-500 uppercase w-10">STT</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Mã hồ sơ</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Họ tên</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Ngày sinh</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Giới tính</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Số CCCD</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Ngành tuyển sinh</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Lý do</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ineligible)): ?>
                        <tr><td colspan="9" class="px-6 py-10 text-center text-slate-400 italic">Không có thí sinh không đủ điều kiện.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ineligible as $i => $c): ?>
                            <tr class="border-b border-slate-50 hover:bg-rose-50/30 transition">
                                <td class="px-3 py-2.5"><input type="checkbox" class="ineligible-check w-4 h-4 rounded" value="<?= $c['id'] ?>"></td>
                                <td class="px-3 py-2.5 text-slate-400"><?= $i + 1 ?></td>
                                <td class="px-4 py-2.5 font-mono text-xs text-blue-600"><?= htmlspecialchars($c['application_code'] ?? '--') ?></td>
                                <td class="px-4 py-2.5 font-bold text-slate-800"><?= htmlspecialchars($c['name']) ?></td>
                                <td class="px-4 py-2.5 text-slate-600"><?= $c['birth_date'] ? date('d/m/Y', strtotime($c['birth_date'])) : '--' ?></td>
                                <td class="px-4 py-2.5 text-slate-600"><?= htmlspecialchars($c['gender'] ?? '--') ?></td>
                                <td class="px-4 py-2.5 font-mono text-xs"><?= htmlspecialchars($c['cccd'] ?? '--') ?></td>
                                <td class="px-4 py-2.5 text-slate-600"><?= htmlspecialchars($c['subject_name']) ?></td>
                                <td class="px-4 py-2.5 text-rose-600 text-xs italic"><?= htmlspecialchars($c['ineligible_reason'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?= csrf_token() ?>';
const sessionId = <?= $session['id'] ?>;

document.getElementById('checkAllEligible').addEventListener('change', function() {
    document.querySelectorAll('.eligible-check').forEach(cb => cb.checked = this.checked);
});
document.getElementById('checkAllIneligible').addEventListener('change', function() {
    document.querySelectorAll('.ineligible-check').forEach(cb => cb.checked = this.checked);
});

function getCheckedIds(selector) {
    return Array.from(document.querySelectorAll(selector + ':checked')).map(cb => cb.value);
}

function markIneligible() {
    const ids = getCheckedIds('.eligible-check');
    const reason = document.getElementById('reasonInput').value.trim();
    if (ids.length === 0) { alert('Chưa chọn thí sinh nào!'); return; }
    if (!reason) { alert('Vui lòng nhập lý do!'); return; }
    submitToggle(ids, 'mark_ineligible', reason);
}

function markEligible() {
    const ids = getCheckedIds('.ineligible-check');
    if (ids.length === 0) { alert('Chưa chọn thí sinh nào!'); return; }
    submitToggle(ids, 'mark_eligible', '');
}

function submitToggle(ids, action, reason) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url('/admin/talent-tests/toggle-eligibility') ?>';
    
    const fields = { csrf_token: csrfToken, session_id: sessionId, action: action, reason: reason };
    for (const [k, v] of Object.entries(fields)) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = k; input.value = v;
        form.appendChild(input);
    }
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
