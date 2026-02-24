<?php $title = 'Tạo Thông báo - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-3xl mx-auto">
    <header class="mb-6">
        <a href="<?= url('/admin/notifications') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition inline-flex items-center mb-2">
            <i class="fas fa-arrow-left mr-2"></i> Danh sách Thông báo
        </a>
        <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Tạo Thông báo Mới</h2>
    </header>

    <?php if (!empty($_GET['error'])): ?>
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl font-bold border border-red-100 flex items-center text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php if ($_GET['error'] == 'missing_fields'): ?>Vui lòng điền đầy đủ tiêu đề và nội dung!<?php endif; ?>
            <?php if ($_GET['error'] == 'failed'): ?>Không thể tạo thông báo. Vui lòng thử lại.<?php endif; ?>
        </div>
    <?php endif; ?>

    <form action="<?= url('/admin/notifications/store') ?>" method="POST" 
          class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden"
          x-data="{ target: 'all', sessionValue: '', updateId() { if(this.target === 'session') document.getElementById('target_id_input').value = this.sessionValue; } }">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        
        <div class="bg-gradient-to-r from-[#0066FF] to-blue-700 px-6 py-4">
            <h3 class="text-white font-bold flex items-center">
                <i class="fas fa-edit mr-2"></i> Nội dung Thông báo
            </h3>
        </div>
        
        <div class="p-6 space-y-5">
            <!-- Title -->
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Tiêu đề <span class="text-red-500">*</span></label>
                <input type="text" name="title" required
                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition text-sm font-medium dark:text-white"
                       placeholder="Nhập tiêu đề thông báo...">
            </div>

            <!-- Type & Target -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Loại thông báo</label>
                    <select name="type" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition text-sm font-medium dark:text-white">
                        <option value="info">ℹ️ Thông tin</option>
                        <option value="success">✅ Thành công</option>
                        <option value="warning">⚠️ Cảnh báo</option>
                        <option value="important">🔴 Quan trọng</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Đối tượng nhận</label>
                    <select name="target_type" x-model="target" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition text-sm font-medium dark:text-white">
                        <option value="all">🌐 Tất cả thí sinh</option>
                        <option value="individual">👤 Thí sinh cụ thể (CCCD)</option>
                        <option value="session">📅 Theo Đợt tuyển sinh</option>
                    </select>
                </div>
            </div>

            <!-- Target ID (Individual) -->
            <div x-show="target === 'individual'" x-cloak class="transition-all">
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Nhập số CCCD thí sinh</label>
                <input type="text" name="target_id" 
                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition text-sm font-mono dark:text-white"
                       placeholder="012345678901" :required="target === 'individual'">
            </div>

            <!-- Session dropdown -->
            <div x-show="target === 'session'" x-cloak class="transition-all">
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Chọn Đợt tuyển sinh</label>
                <select x-model="sessionValue" @change="updateId()" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition text-sm font-medium dark:text-white"
                        :required="target === 'session'">
                    <option value="">-- Chọn đợt --</option>
                    <?php foreach ($sessions ?? [] as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['ten_dot']) ?></option>
                    <?php endforeach; ?>
                </select>
                <!-- Hidden input for target_id when session is selected -->
                <input type="hidden" name="target_id" id="target_id_input" :value="sessionValue" :disabled="target !== 'session'">
            </div>

            <!-- Content -->
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Nội dung <span class="text-red-500">*</span></label>
                <textarea name="content" rows="8" required
                          class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition text-sm dark:text-white"
                          placeholder="Nhập nội dung thông báo..."></textarea>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-100 dark:border-gray-600 flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white font-bold uppercase text-sm tracking-wider rounded-xl shadow hover:bg-emerald-700 hover:shadow-lg transition-all">
                <i class="fas fa-paper-plane mr-2"></i> Gửi Thông báo
            </button>
        </div>
    </form>
</div>
<script>
// Legacy script removed - replaced with AlpineJS
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
