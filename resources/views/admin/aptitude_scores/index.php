<?php ob_start(); ?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Quản lý Điểm Năng Khiếu</h1>
    <div class="flex gap-2">
        <a href="<?= url('/admin/aptitude-scores/template') ?>" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition flex items-center gap-2">
            <i class="fas fa-download"></i> Tải mẫu CSV
        </a>
        <button onclick="document.getElementById('import-modal').classList.remove('hidden')" class="px-4 py-2 bg-[#0066FF] text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2 shadow-lg shadow-indigo-200">
            <i class="fas fa-upload"></i> Nhập dữ liệu / Import
        </button>
    </div>
</div>

<!-- Stats / Info -->
<div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-6 flex items-start gap-3">
    <i class="fas fa-info-circle text-[#0066FF] mt-1"></i>
    <div>
        <p class="text-sm text-indigo-800 font-bold">Lưu ý về Điểm Năng Khiếu</p>
        <p class="text-sm text-indigo-700 mt-1">
            Điểm năng khiếu được sử dụng cho các ngành: Sư phạm Âm nhạc, Sư phạm Mỹ thuật, Giáo dục Mầm non.<br>
            Hệ thống sẽ tự động ghép điểm này vào công thức tính điểm xét tuyển khi chạy "Tính điểm".
        </p>
    </div>
</div>

<!-- Search & Table -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex justify-between items-center">
        <form action="<?= url('/admin/aptitude-scores') ?>" method="GET" class="relative">
            <input type="text" name="search" placeholder="Tìm CCCD hoặc Tên..." value="<?= htmlspecialchars($search ?? '') ?>" class="pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none w-64">
            <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                <tr>
                    <th class="px-6 py-3">Thí sinh</th>
                    <th class="px-6 py-3">SBD</th>
                    <th class="px-6 py-3">Môn NK</th>
                    <th class="px-6 py-3">Điểm NK</th>
                    <th class="px-6 py-3">Ghi chú</th>
                    <th class="px-6 py-3 text-right">Ngày nhập</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($scores)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                            Chưa có dữ liệu điểm năng khiếu.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($scores as $s): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-700"><?= htmlspecialchars($s['ho_va_ten'] ?? 'N/A') ?></p>
                                <p class="text-xs text-slate-500 font-mono"><?= htmlspecialchars($s['so_cccd']) ?></p>
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-600"><?= htmlspecialchars($s['sbd'] ?: '-') ?></td>
                            <td class="px-6 py-4 font-mono text-[#0066FF] font-bold"><?= htmlspecialchars($s['ma_mon'] ?? 'NK1') ?></td>
                            <td class="px-6 py-4">
                                <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded font-bold">
                                    <?= htmlspecialchars($s['diem_nang_khieu']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500"><?= htmlspecialchars($s['ghi_chu']) ?></td>
                            <td class="px-6 py-4 text-right text-slate-400 text-xs">
                                <?= date('d/m/Y H:i', strtotime($s['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="p-4 border-t border-slate-100 flex justify-center">
        <div class="flex gap-1">
            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 flex items-center justify-center rounded border <?= $i == $pagination['current_page'] ? 'bg-[#0066FF] text-white border-indigo-600' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Import Modal -->
<div id="import-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('import-modal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Nhập điểm Năng khiếu</h3>
                
                <div x-data="{ tab: 'manual' }" class="mt-4">
                    <div class="flex border-b border-gray-200 mb-4">
                        <button @click="tab = 'manual'" :class="{ 'border-indigo-500 text-[#0066FF]': tab === 'manual', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'manual' }" class="w-1/2 py-2 px-1 text-center border-b-2 font-medium text-sm">Nhập thủ công</button>
                        <button @click="tab = 'csv'" :class="{ 'border-indigo-500 text-[#0066FF]': tab === 'csv', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'csv' }" class="w-1/2 py-2 px-1 text-center border-b-2 font-medium text-sm">Upload CSV</button>
                    </div>

                    <!-- Manual Form -->
                    <form x-show="tab === 'manual'" action="<?= url('/admin/aptitude-scores/import') ?>" method="POST">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Số CCCD</label>
                                <input type="text" name="cccd" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Số Báo Danh (SBD)</label>
                                <input type="text" name="sbd" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mã Môn NK (VD: NK1, NK2)</label>
                                <input type="text" name="ma_mon" value="NK1" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm uppercase">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Điểm Năng Khiếu</label>
                                <input type="number" step="0.01" name="score" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
                                <input type="text" name="note" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0066FF] text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:text-sm mt-2">Lưu điểm</button>
                        </div>
                    </form>

                    <!-- CSV Form -->
                    <form x-show="tab === 'csv'" action="<?= url('/admin/aptitude-scores/import') ?>" method="POST" enctype="multipart/form-data">
                        <div class="space-y-4 text-center">
                            <div class="border-2 border-gray-300 border-dashed rounded-md px-6 pt-5 pb-6">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-file-csv text-gray-400 text-3xl mb-2"></i>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-[#0066FF] hover:text-[#0066FF] focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Upload a file</span>
                                            <input id="file-upload" name="import_file" type="file" class="sr-only" accept=".csv">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">CSV up to 10MB</p>
                                </div>
                            </div>
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0066FF] text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:text-sm">Import CSV</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Đóng</button>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php'; 
?>
