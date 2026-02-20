<?php 
$title = 'Thùng rác - Hồ sơ đã xóa';
include __DIR__ . '/../../layouts/admin_header.php'; 
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-trash-alt text-red-500"></i> Thùng rác
            </h1>
            <p class="text-gray-500 text-sm mt-1">Danh sách hồ sơ đã bị xóa tạm thời. Bạn có thể khôi phục hoặc xóa vĩnh viễn.</p>
        </div>
        
        <div class="flex gap-2">
            <a href="<?= url('/admin/dashboard') ?>" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại Dashboard
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex justify-between items-center animate-fade-in-down">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-xl mr-3"></i>
                <div>
                    <?php if ($_GET['success'] == 'restored'): ?>
                        <span class="font-bold">Khôi phục thành công!</span> Hồ sơ đã được đưa trở lại danh sách chính.
                    <?php elseif ($_GET['success'] == 'deleted_forever'): ?>
                        <span class="font-bold">Đã xóa vĩnh viễn!</span> Không thể khôi phục lại hồ sơ này.
                    <?php endif; ?>
                </div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <!-- Search/Filter -->
    <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-200">
        <form action="" method="GET" class="flex gap-3">
            <div class="relative flex-grow max-w-md">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none" 
                    placeholder="Tìm theo tên, CCCD...">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                Tìm kiếm
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-16">TT</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Họ và Tên</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">CCCD</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Thời gian xóa</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-40">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($candidates)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-trash-restore text-4xl text-gray-300 mb-3"></i>
                                <p>Thùng rác trống!</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($candidates as $index => $c): ?>
                        <tr class="hover:bg-red-50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center font-bold">
                                <?= ($currentPage - 1) * 20 + $index + 1 ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-gray-900"><?= htmlspecialchars($c['ho_va_ten']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($c['email'] ?? '') ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600 bg-gray-50 px-2 rounded w-fit">
                                <?= htmlspecialchars($c['so_cccd']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full font-medium">
                                    <?= date('H:i d/m/Y', strtotime($c['deleted_at'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center space-x-2 opacity-100 md:opacity-0 group-hover:opacity-100 transition-opacity">
                                    <!-- Restore Button -->
                                    <form action="<?= url('/admin/candidates/restore') ?>" method="POST" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="cccd" value="<?= $c['so_cccd'] ?>">
                                        <button type="submit" class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 p-2 rounded-lg transition-colors" title="Khôi phục" onclick="return confirm('Bạn có chắc chắn muốn khôi phục hồ sơ này?');">
                                            <i class="fas fa-trash-restore-alt"></i>
                                        </button>
                                    </form>

                                    <!-- Force Delete Button -->
                                    <form action="<?= url('/admin/candidates/force-delete') ?>" method="POST" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="cccd" value="<?= $c['so_cccd'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors" title="Xóa vĩnh viễn" onclick="return confirm('CẢNH BÁO: Hành động này KHÔNG THỂ hoàn tác! Bạn có chắc chắn muốn xóa vĩnh viễn hồ sơ này?');">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                       class="<?= $i == $currentPage ? 'bg-red-50 border-red-500 text-red-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
