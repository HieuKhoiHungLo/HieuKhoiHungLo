<?php
/**
 * @var array $choices List of current choices
 * @var array $majors List of all available majors
 */
?>
<div id="form_wishes" class="hidden animate-in fade-in slide-in-from-bottom-4 duration-500">
    <form id="editForm_wishes" onsubmit="saveSection('wishes'); return false;">
        <input type="hidden" name="application_id" value="<?= $user['application_id'] ?? '' ?>">
        <div class="bg-white rounded-[2rem] border border-blue-100 shadow-sm overflow-hidden mb-6">
            <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                <h4 class="font-black text-slate-800 text-sm uppercase tracking-widest flex items-center">
                    <i class="fas fa-list-ol mr-2 text-sky-500"></i> Điều chỉnh danh sách Nguyện vọng
                </h4>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-white px-3 py-1 rounded-full border border-slate-100 shadow-sm">
                    Tối đa 06 nguyện vọng
                </div>
            </div>

            <div class="p-0 overflow-x-auto">
                <table class="w-full text-left border-collapse" id="adminChoicesTable">
                    <thead class="bg-slate-50 text-slate-400 uppercase leading-normal text-[10px] font-black tracking-widest">
                        <tr>
                            <th class="py-4 px-6 text-center w-16 border-r border-slate-100">TT</th>
                            <th class="py-4 px-6 border-r border-slate-100">Ngành xét tuyển</th>
                            <th class="py-4 px-6 text-center w-28 border-r border-slate-100">Mã trường</th>
                            <th class="py-4 px-6 text-center w-56 border-r border-slate-100">Tổ hợp môn</th>
                            <th class="py-4 px-6 text-center w-16">Xóa</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-600 text-xs">
                        <?php if (empty($choices)): ?>
                            <tr class="empty-row border-b border-slate-50">
                                <td colspan="5" class="py-12 text-center text-slate-400 italic">Chưa có nguyện vọng nào. Bấm "Thêm nguyện vọng" bên dưới.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($choices as $index => $wish): ?>
                                <tr class="choice-row border-b border-slate-50 hover:bg-blue-50/30 transition-colors" data-index="<?= $index ?>">
                                    <td class="py-4 px-6 text-center font-black text-slate-400 border-r border-slate-100"><?= $index + 1 ?></td>
                                    <td class="py-4 px-6 border-r border-slate-100">
                                        <input type="hidden" name="choices[<?= $index ?>][thu_tu]" value="<?= $index + 1 ?>">
                                        <select name="choices[<?= $index ?>][nganh_id]" onchange="updateAdminCombo(this, <?= $index ?>)" class="w-full h-10 border border-slate-200 rounded-xl px-3 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 outline-none bg-white font-bold text-slate-700 transition-all appearance-none" required>
                                            <option value="">-- Chọn ngành --</option>
                                            <?php foreach ($majors as $m): ?>
                                                <option value="<?= $m['ma_nganh'] ?>" <?= $wish['ma_nganh'] == $m['ma_nganh'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($m['ten_nganh']) ?> (<?= $m['ma_nganh'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="py-4 px-6 text-center border-r border-slate-100">
                                        <span class="px-3 py-1 bg-slate-100 text-slate-700 font-black rounded-lg text-[10px]">THV</span>
                                    </td>
                                    <td class="py-4 px-6 border-r border-slate-100 text-center">
                                        <div id="admin-combo-<?= $index ?>" class="flex flex-wrap justify-center gap-1">
                                            <?php 
                                            if (!empty($wish['to_hop_mon'])): 
                                                $combos = array_map('trim', explode(',', $wish['to_hop_mon']));
                                                foreach ($combos as $c): ?>
                                                    <span class="px-1.5 py-0.5 bg-sky-50 text-sky-600 text-[9px] font-bold rounded border border-sky-100"><?= htmlspecialchars($c) ?></span>
                                                <?php endforeach;
                                            endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <button type="button" onclick="removeAdminChoice(this)" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-400 hover:bg-rose-500 hover:text-white transition-all duration-300 flex items-center justify-center">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-6 bg-slate-50/50 border-t border-slate-50 flex justify-between items-center">
                <button type="button" onclick="addAdminChoice()" class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-[#0066FF] font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-blue-50 hover:border-blue-200 transition-all shadow-sm">
                    <i class="fas fa-plus-circle"></i> Thêm nguyện vọng
                </button>
                <div class="flex gap-3">
                    <button type="button" onclick="toggleEdit('wishes')" class="px-6 py-2.5 bg-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-300 transition-all text-xs">Hủy bỏ</button>
                    <button type="submit" class="px-8 py-2.5 bg-[#0066FF] text-white font-bold rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all text-xs">Lưu thay đổi</button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Row Template for Admin -->
<template id="adminChoiceRowTemplate">
    <tr class="choice-row border-b border-slate-50 hover:bg-blue-50/30 transition-colors" data-index="INDEX_MINUS_1">
        <td class="py-4 px-6 text-center font-black text-slate-400 border-r border-slate-100">INDEX</td>
        <td class="py-4 px-6 border-r border-slate-100">
            <input type="hidden" name="choices[INDEX_MINUS_1][thu_tu]" value="INDEX">
            <select name="choices[INDEX_MINUS_1][nganh_id]" onchange="updateAdminCombo(this, INDEX_MINUS_1)" class="w-full h-10 border border-slate-200 rounded-xl px-3 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 outline-none bg-white font-bold text-slate-700 transition-all appearance-none" required>
                <option value="">-- Chọn ngành --</option>
                <?php foreach ($majors as $m): ?>
                    <option value="<?= $m['ma_nganh'] ?>"><?= htmlspecialchars($m['ten_nganh']) ?> (<?= $m['ma_nganh'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="py-4 px-6 text-center border-r border-slate-100">
            <span class="px-3 py-1 bg-slate-100 text-slate-700 font-black rounded-lg text-[10px]">THV</span>
        </td>
        <td class="py-4 px-6 border-r border-slate-100 text-center">
            <div id="admin-combo-INDEX_MINUS_1" class="flex flex-wrap justify-center gap-1"></div>
        </td>
        <td class="py-4 px-6 text-center">
            <button type="button" onclick="removeAdminChoice(this)" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-400 hover:bg-rose-500 hover:text-white transition-all duration-300 flex items-center justify-center">
                <i class="fas fa-trash-alt text-xs"></i>
            </button>
        </td>
    </tr>
</template>

<script>
const majorCombosAdmin = {
    <?php foreach ($majors as $m): ?>
    '<?= $m['ma_nganh'] ?>': '<?= htmlspecialchars($m['to_hop_xet_tuyen'] ?? '') ?>',
    <?php endforeach; ?>
};

function updateAdminCombo(select, index) {
    const majorId = select.value;
    const comboDiv = document.getElementById('admin-combo-' + index);
    if (majorId && majorCombosAdmin[majorId]) {
        const combos = majorCombosAdmin[majorId].split(',').map(c => c.trim()).filter(Boolean);
        comboDiv.innerHTML = combos.map(c => `<span class="px-1.5 py-0.5 bg-sky-50 text-sky-600 text-[9px] font-bold rounded border border-sky-100">${c}</span>`).join('');
    } else {
        comboDiv.innerHTML = '';
    }
}

function updateAdminOrders() {
    const rows = document.querySelectorAll('#adminChoicesTable .choice-row');
    rows.forEach((row, index) => {
        row.setAttribute('data-index', index);
        row.querySelector('td:first-child').textContent = index + 1;
        row.querySelector('input[type="hidden"]').value = index + 1;
        row.querySelector('input[type="hidden"]').name = `choices[${index}][thu_tu]`;
        const select = row.querySelector('select');
        select.name = `choices[${index}][nganh_id]`;
        select.setAttribute('onchange', `updateAdminCombo(this, ${index})`);
        const comboDiv = row.querySelector('div[id^="admin-combo-"]');
        if (comboDiv) comboDiv.id = `admin-combo-${index}`;
    });
}

function addAdminChoice() {
    const currentRows = document.querySelectorAll('#adminChoicesTable .choice-row');
    if (currentRows.length >= 6) {
        alert('Tối đa 06 nguyện vọng.');
        return;
    }

    document.querySelector('#adminChoicesTable .empty-row')?.remove();

    const nextIndex = currentRows.length + 1;
    const nextIndexMinus1 = nextIndex - 1;

    const rowTemplate = document.querySelector('#adminChoiceRowTemplate').innerHTML;
    let rowHtml = rowTemplate.replace(/INDEX_MINUS_1/g, nextIndexMinus1).replace(/INDEX/g, nextIndex);
    const tbody = document.querySelector('#adminChoicesTable tbody');
    tbody.insertAdjacentHTML('beforeend', rowHtml);
    
    updateAdminOrders();
}

function removeAdminChoice(btn) {
    btn.closest('tr').remove();
    updateAdminOrders();
    checkAdminEmpty();
}

function checkAdminEmpty() {
    if (document.querySelectorAll('#adminChoicesTable .choice-row').length === 0) {
        const tbody = document.querySelector('#adminChoicesTable tbody');
        tbody.innerHTML = `<tr class="empty-row border-b border-slate-50">
            <td colspan="5" class="py-12 text-center text-slate-400 italic">Chưa có nguyện vọng nào. Bấm "Thêm nguyện vọng" bên dưới.</td>
        </tr>`;
    }
}
</script>
