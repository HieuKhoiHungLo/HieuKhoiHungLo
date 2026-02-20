<?php $title = 'Quản lý Vùng Tuyển Sinh - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-4xl mx-auto p-8">
    <header class="mb-8 flex justify-between items-center">
        <div>
            <a href="<?= url('/admin/master-data') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition block mb-2">&larr; Quay lại danh mục</a>
            <h2 class="text-3xl font-black text-gray-900 uppercase">Cấu hình Vùng Tuyển Sinh</h2>
            <p class="text-slate-500 text-sm mt-1">Thiết lập giới hạn hộ khẩu cho các nhóm ngành (VD: Sư phạm)</p>
        </div>
        <div>
            <button onclick="openModal()" class="bg-[#BE1E2D] hover:bg-[#9d1926] text-white font-black py-2 px-5 rounded-xl shadow-lg transform hover:scale-105 transition flex items-center">
                <i class="fas fa-plus mr-2"></i> Thêm Cấu hình
            </button>
        </div>
    </header>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-100 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase tracking-wider">
                    <th class="px-6 py-3 font-heading">Mã Ngành (Prefix)</th>
                    <th class="px-6 py-3 font-heading">Tỉnh/Thành được phép</th>
                    <th class="px-6 py-3 text-center font-heading">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($zones)): ?>
                    <tr><td colspan="3" class="px-6 py-8 text-center text-slate-400 italic">Chưa có cấu hình nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($zones as $zone): ?>
                        <tr class="hover:bg-red-50/40 transition duration-200 ease-in-out">
                            <td class="px-6 py-3 font-mono font-bold text-[#0066FF] text-sm"><?= htmlspecialchars($zone['ma_nganh_prefix']) ?></td>
                            <td class="px-6 py-3 font-medium text-slate-700 text-sm"><?= htmlspecialchars($zone['ten_tinh'] ?? $zone['ma_tinh']) ?></td>
                            <td class="px-6 py-3 text-center">
                                <form action="<?= url('/admin/master-data/zones/delete') ?>" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa cấu hình này?')" class="inline-block">
                                    <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
                                    <input type="hidden" name="id" value="<?= $zone['id'] ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs uppercase" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-xl text-sm text-blue-800">
        <h4 class="font-bold mb-1"><i class="fas fa-info-circle mr-1"></i> Lưu ý:</h4>
        <p>Cấu hình này sẽ giới hạn thí sinh đăng ký xét tuyển vào các ngành bắt đầu bằng <strong>Prefix</strong> (ví dụ: 7140) chỉ được phép nếu hộ khẩu thường trú thuộc <strong>Tỉnh</strong> được chọn.</p>
    </div>
</div>

<!-- Modal -->
<div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl">
        <h3 class="text-xl font-black uppercase mb-6 text-gray-800">Thêm Cấu hình Vùng</h3>
        <form action="<?= url('/admin/master-data/zones/save') ?>" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
            
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Mã Ngành (Prefix)</label>
                <input type="text" name="ma_nganh_prefix" required value="7140" placeholder="VD: 7140" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition">
                <p class="text-[10px] text-gray-400 mt-1">Nhập 4 số đầu mã ngành (7140 là nhóm Sư phạm)</p>
            </div>
            
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Tỉnh/Thành được phép</label>
                <select name="ma_tinh" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-[#0066FF] focus:ring-1 focus:ring-[#0066FF] transition">
                    <option value="">-- Chọn Tỉnh --</option>
                    <?php foreach ($provinces as $p): ?>
                        <option value="<?= $p['ma_tinh'] ?>"><?= htmlspecialchars($p['ten_tinh']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex space-x-3 pt-6">
                <button type="button" onclick="closeModal()" class="flex-grow py-3 bg-gray-100 text-gray-600 font-black uppercase text-xs tracking-widest rounded-xl hover:bg-gray-200 transition">Hủy</button>
                <button type="submit" class="flex-grow py-3 bg-[#BE1E2D] text-white font-black uppercase text-xs tracking-widest rounded-xl shadow-lg hover:shadow-xl hover:bg-[#9d1926] transition">Lưu lại</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modal').classList.remove('hidden');
        document.getElementById('modal').classList.add('flex');
    }
    function closeModal() {
        document.getElementById('modal').classList.add('hidden');
        document.getElementById('modal').classList.remove('flex');
    }
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
