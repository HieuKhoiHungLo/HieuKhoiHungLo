<?php $title = 'Đăng ký Nguyện vọng'; include __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-5xl mx-auto">
    <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-hvu-red to-red-700 p-8 text-white">
            <h2 class="text-3xl font-bold">Bước 5: Đăng ký Nguyện vọng</h2>
            <p class="text-red-100 mt-2 italic">* Bạn nên sắp xếp nguyện vọng ưu tiên cao nhất ở vị trí số 1.</p>
        </div>

        <div class="p-8">
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?= (string) ($error ?? "") ?></p>
                </div>
            <?php endif; ?>

            <form action="<?= url('/application/registration') ?>" method="POST" id="choiceForm">
                <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="choiceTable">
                        <thead>
                            <tr class="text-gray-400 text-xs uppercase tracking-wider border-b border-gray-100">
                                <th class="pb-4 font-semibold w-16">Thứ tự</th>
                                <th class="pb-4 font-semibold">Ngành xét tuyển</th>
                                <th class="pb-4 font-semibold">Phương thức</th>
                                <th class="pb-4 font-semibold">Tổ hợp môn</th>
                                <th class="pb-4 font-semibold w-20 text-center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <!-- Rows will be injected here by JS if empty, or pre-rendered if choices exist -->
                            <?php if (!empty($choices)): ?>
                                <?php foreach ($choices as $index => $choice): ?>
                                    <tr class="choice-row group">
                                        <td class="py-4 font-bold text-gray-900 border-r border-transparent group-hover:border-hvu-red/20 transition-all">
                                            <span class="row-index"><?= $index + 1 ?></span>
                                        </td>
                                        <td class="py-4 px-2">
                                            <select name="majors[]" class="hvu-input text-sm font-bold" required>
                                                <option value="">-- Chọn ngành --</option>
                                                <?php foreach ($majors as $major): ?>
                                                    <option value="<?= $major['ma_nganh'] ?>" <?= $choice['ma_nganh'] == $major['ma_nganh'] ? 'selected' : '' ?>>
                                                        <?= $major['ma_nganh'] ?> - <?= $major['ten_nganh'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="py-4 px-2">
                                            <select name="methods[]" class="hvu-input text-sm font-bold">
                                                <option value="200" <?= ($choice['ma_phuong_thuc'] ?? '') == '200' ? 'selected' : '' ?>>200 - Xét Học bạ</option>
                                                <option value="100" <?= ($choice['ma_phuong_thuc'] ?? '') == '100' ? 'selected' : '' ?>>100 - Xét điểm Thi TN</option>
                                                <option value="402" <?= ($choice['ma_phuong_thuc'] ?? '') == '402' ? 'selected' : '' ?>>402 - Xét ĐGNL</option>
                                            </select>
                                        </td>
                                        <td class="py-4 px-2">
                                            <input type="text" name="groups[]" value="<?= htmlspecialchars($choice['to_hop_mon'] ?? 'A00') ?>" 
                                                   class="hvu-input text-sm font-black text-center uppercase" placeholder="VD: A00, A01...">
                                        </td>
                                        <td class="py-4 text-center">
                                            <button type="button" class="text-gray-300 hover:text-red-500 transition remove-row">
                                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-8 flex justify-between items-center bg-gray-50 p-6 rounded-2xl border border-dashed border-gray-300">
                    <div class="text-sm text-gray-500 max-w-md">
                        Bạn có thể thêm tối đa 20 nguyện vọng. Hệ thống sẽ xét tuyển theo thứ tự từ trên xuống dưới.
                    </div>
                    <button type="button" id="addRow" class="inline-flex items-center px-6 py-3 border border-gray-200 rounded-xl shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-100 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-hvu-red">
                        <svg class="w-5 h-5 mr-2 text-hvu-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Thêm nguyện vọng
                    </button>
                </div>

                <div class="mt-12 flex items-center justify-between border-t pt-8">
                    <a href="<?= url('/academic/documents') ?>" class="text-gray-500 hover:text-gray-700 font-bold flex items-center transition">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Quay lại minh chứng
                    </a>
                    <button type="submit" class="inline-flex justify-center py-4 px-12 border border-transparent shadow-2xl text-lg font-black rounded-2xl text-white bg-gradient-to-br from-red-600 to-hvu-red hover:from-red-700 hover:to-red-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-hvu-red transform hover:scale-105 transition-all">
                        Xác nhận & Gửi hồ sơ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="choiceTemplate">
    <tr class="choice-row group">
        <td class="py-4 font-bold text-gray-900 border-r border-transparent group-hover:border-hvu-red/20 transition-all">
            <span class="row-index">1</span>
        </td>
        <td class="py-4 px-2">
            <select name="majors[]" class="hvu-input text-sm font-bold" required>
                <option value="">-- Chọn ngành --</option>
                <?php foreach ($majors as $major): ?>
                    <option value="<?= $major['ma_nganh'] ?>"><?= $major['ma_nganh'] ?> - <?= $major['ten_nganh'] ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="py-4 px-2">
            <select name="methods[]" class="hvu-input text-sm font-bold">
                <option value="200">200 - Xét Học bạ</option>
                <option value="100">100 - Xét điểm Thi TN</option>
                <option value="402">402 - Xét ĐGNL</option>
            </select>
        </td>
        <td class="py-4 px-2">
            <input type="text" name="groups[]" value="A00" class="hvu-input text-sm font-black text-center uppercase" placeholder="VD: A00, A01...">
        </td>
        <td class="py-4 text-center">
            <button type="button" class="text-gray-300 hover:text-red-500 transition remove-row">
                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
        </td>
    </tr>
</template>

<script>
    const tableBody = document.querySelector('#choiceTable tbody');
    const template = document.querySelector('#choiceTemplate');
    const addBtn = document.querySelector('#addRow');

    function updateIndices() {
        document.querySelectorAll('.row-index').forEach((el, i) => {
            el.textContent = i + 1;
        });
    }

    addBtn.addEventListener('click', () => {
        const clone = template.content.cloneNode(true);
        tableBody.appendChild(clone);
        updateIndices();
    });

    tableBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-row');
        if (btn) {
            btn.closest('tr').remove();
            updateIndices();
        }
    });

    // Add first row if empty
    if (tableBody.children.length === 0) {
        addBtn.click();
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
