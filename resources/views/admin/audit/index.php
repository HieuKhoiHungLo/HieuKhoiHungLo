<?php $title = 'Nhật ký Hoạt động - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-7xl mx-auto">
    <header class="mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-black text-gray-900 uppercase">Nhật ký Hoạt động</h2>
            <p class="text-gray-500 mt-1">Theo dõi mọi thao tác của cán bộ quản trị</p>
        </div>
        <div class="flex space-x-2">
            <button onclick="purgeLogs(20)" class="px-4 py-2 bg-red-50 text-red-600 font-bold rounded-lg border border-red-200 hover:bg-red-100 transition text-sm">
                <i class="fas fa-history mr-1"></i> Dọn dẹp cũ hơn 20 ngày
            </button>
            <button onclick="purgeLogs(0)" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition text-sm shadow-sm">
                <i class="fas fa-trash-alt mr-1"></i> Xóa tất cả
            </button>
            <button onclick="location.reload()" class="px-4 py-2 bg-white text-gray-600 font-bold rounded-lg border hover:bg-gray-50 transition text-sm">
                <i class="fas fa-sync-alt mr-1"></i> Làm mới
            </button>
        </div>
    </header>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500">
            <p class="text-sm font-bold text-gray-400 uppercase">Thao tác hôm nay</p>
            <p class="text-3xl font-black text-gray-800 mt-2"><?= $stats['today_actions'] ?? 0 ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-red-500">
            <p class="text-sm font-bold text-gray-400 uppercase">Đăng nhập thất bại (24h)</p>
            <p class="text-3xl font-black text-red-600 mt-2"><?= $stats['failed_logins_24h'] ?? 0 ?></p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
            <p class="text-sm font-bold text-gray-400 uppercase">Admin hoạt động hôm nay</p>
            <p class="text-3xl font-black text-gray-800 mt-2"><?= $stats['active_admins'] ?? 0 ?></p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white rounded-2xl shadow-lg p-6 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Hành động</label>
                <select name="action" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">Tất cả</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= $a ?>" <?= ($filters['action'] ?? '') == $a ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Đối tượng</label>
                <select name="entity_type" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">Tất cả</option>
                    <?php foreach ($entityTypes as $e): ?>
                        <option value="<?= $e ?>" <?= ($filters['entity_type'] ?? '') == $e ? 'selected' : '' ?>><?= $e ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Từ ngày</label>
                <input type="date" name="date_from" value="<?= $filters['date_from'] ?? '' ?>" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Đến ngày</label>
                <input type="date" name="date_to" value="<?= $filters['date_to'] ?? '' ?>" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full py-2 bg-[#0066FF] text-white font-bold rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-filter mr-1"></i> Lọc
                </button>
            </div>
        </div>
    </form>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase">Thời gian</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase">Người thực hiện</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase">Hành động</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase">Đối tượng</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400">Chưa có dữ liệu</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-gray-800"><?= htmlspecialchars($log['admin_name']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php 
                                $actionColors = [
                                    'LOGIN' => 'bg-blue-100 text-blue-700',
                                    'CREATE' => 'bg-green-100 text-green-700',
                                    'UPDATE' => 'bg-yellow-100 text-yellow-700',
                                    'DELETE' => 'bg-red-100 text-red-700',
                                ];
                                $color = $actionColors[$log['action']] ?? 'bg-gray-100 text-gray-700';
                                ?>
                                <span class="px-2 py-1 rounded text-xs font-bold <?= $color ?>"><?= $log['action'] ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if ($log['entity_type']): ?>
                                    <span class="text-gray-600"><?= $log['entity_type'] ?></span>
                                    <?php if ($log['entity_id']): ?>
                                        <span class="text-gray-400">#<?= $log['entity_id'] ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-500"><?= $log['ip_address'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center">
            <span class="text-sm text-gray-500">Trang <?= $page ?></span>
            <div class="space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 bg-white border rounded-lg text-sm font-bold hover:bg-gray-100">« Trước</a>
                <?php endif; ?>
                <?php if (count($logs) >= 50): ?>
                    <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 bg-white border rounded-lg text-sm font-bold hover:bg-gray-100">Sau »</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function purgeLogs(days) {
    const msg = days === 0 
        ? 'BẠN CÓ CHẮC CHẮN MUỐN XÓA TẤT CẢ NHẬT KÝ? Thao tác này sẽ xóa sạch mọi dữ liệu lịch sử và không thể khôi phục!' 
        : 'Bạn có chắc chắn muốn xóa tất cả nhật ký cũ hơn ' + days + ' ngày không?';
    
    if (!confirm(msg)) {
        return;
    }

    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang dọn dẹp...';

    const formData = new FormData();
    formData.append('days', days);
    formData.append('_csrf_token', '<?= csrf_token() ?>');

    fetch('<?= url("/admin/audit/purge") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đã dọn dẹp nhật ký thành công!');
            location.reload();
        } else {
            alert('Lỗi: ' + (data.error || 'Không thể thực hiện'));
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi kết nối máy chủ.');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
