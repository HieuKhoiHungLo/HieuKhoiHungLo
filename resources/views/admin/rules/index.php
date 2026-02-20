<?php $title = 'Quản lý Điều kiện Xét tuyển - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-6xl mx-auto">
    <header class="mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-black text-gray-900 uppercase">Điều kiện Xét tuyển</h2>
            <p class="text-gray-500 mt-1">Thiết lập các quy tắc xét tuyển động</p>
        </div>
        <button onclick="openModal()" class="px-6 py-3 bg-[#0066FF] text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transition">
            <i class="fas fa-plus mr-2"></i> Thêm Quy tắc
        </button>
    </header>

    <?php if (!empty($_GET['msg'])): ?>
        <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-xl font-bold border border-green-100">
            <i class="fas fa-check-circle mr-2"></i> Đã lưu thành công!
        </div>
    <?php endif; ?>

    <!-- Rules List -->
    <div class="space-y-4">
        <?php foreach ($rules as $rule): ?>
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="flex items-center space-x-3 mb-2">
                            <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($rule['name']) ?></h3>
                            <?php if ($rule['is_active']): ?>
                                <span class="px-2 py-1 text-xs font-bold bg-green-100 text-green-700 rounded">Đang bật</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs font-bold bg-gray-100 text-gray-500 rounded">Tắt</span>
                            <?php endif; ?>
                            <span class="px-2 py-1 text-xs font-bold bg-blue-100 text-blue-700 rounded"><?= $rule['rule_type'] ?></span>
                        </div>
                        <p class="text-sm text-gray-500">
                            <?php if ($rule['ten_nganh']): ?>
                                <span class="font-bold">Ngành:</span> <?= htmlspecialchars($rule['ten_nganh']) ?>
                            <?php else: ?>
                                <span class="font-bold text-purple-600">Áp dụng toàn bộ</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-sm text-gray-600 mt-2"><i class="fas fa-comment-alt text-gray-400 mr-1"></i> <?= htmlspecialchars($rule['message']) ?></p>
                        <pre class="mt-2 p-2 bg-gray-50 rounded text-xs text-gray-600 overflow-x-auto"><?= htmlspecialchars($rule['condition']) ?></pre>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick='editRule(<?= json_encode($rule) ?>)' class="px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="<?= url('/admin/rules/delete?id=' . $rule['id']) ?>" onclick="return confirm('Xóa quy tắc này?')" class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl p-8 m-4">
        <div class="flex justify-between items-center mb-6">
            <h3 id="modal-title" class="text-xl font-bold text-gray-800">Thêm Quy tắc</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="<?= url('/admin/rules/save') ?>" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="rule_id">
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Tên quy tắc</label>
                <input type="text" name="name" id="name" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Loại</label>
                    <select name="rule_type" id="rule_type" class="w-full px-4 py-2 border rounded-lg">
                        <option value="minimum">Điều kiện tối thiểu</option>
                        <option value="disqualify">Điều kiện loại</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Áp dụng cho Ngành</label>
                    <select name="ma_nganh" id="ma_nganh" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Tất cả ngành</option>
                        <?php foreach ($majors as $m): ?>
                            <option value="<?= $m['ma_nganh'] ?>"><?= htmlspecialchars($m['ten_nganh']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Điều kiện (JSON)</label>
                <textarea name="condition" id="condition" rows="4" required class="w-full px-4 py-2 border rounded-lg font-mono text-sm" placeholder='{"field": "diem_tong", "op": ">=", "value": 15}'></textarea>
                <p class="text-xs text-gray-400 mt-1">Ví dụ: {"field": "diem_tong", "op": ">=", "value": 15}</p>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Thông báo lỗi</label>
                <input type="text" name="message" id="message" class="w-full px-4 py-2 border rounded-lg">
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" checked class="mr-2">
                <label for="is_active" class="text-sm text-gray-700">Kích hoạt</label>
            </div>
            
            <button type="submit" class="w-full py-3 bg-[#0066FF] text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transition">
                <i class="fas fa-save mr-2"></i> Lưu Quy tắc
            </button>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
    document.getElementById('modal-title').textContent = 'Thêm Quy tắc';
    document.getElementById('rule_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('rule_type').value = 'minimum';
    document.getElementById('ma_nganh').value = '';
    document.getElementById('condition').value = '';
    document.getElementById('message').value = '';
    document.getElementById('is_active').checked = true;
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal').classList.remove('flex');
}

function editRule(rule) {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
    document.getElementById('modal-title').textContent = 'Sửa Quy tắc';
    document.getElementById('rule_id').value = rule.id;
    document.getElementById('name').value = rule.name;
    document.getElementById('rule_type').value = rule.rule_type;
    document.getElementById('ma_nganh').value = rule.ma_nganh || '';
    document.getElementById('condition').value = rule.condition;
    document.getElementById('message').value = rule.message;
    document.getElementById('is_active').checked = rule.is_active;
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
