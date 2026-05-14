<?php ob_start(); ?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Cấu hình Email SMTP Rotating</h1>
            <p class="text-slate-500 text-sm">Quản lý danh sách các tài khoản email để gửi thư tự động và hàng loạt.</p>
        </div>
        <button onclick="openModal()" class="px-4 py-2 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center">
            <i class="fas fa-plus mr-2"></i> Thêm tài khoản
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center shadow-sm animate-fade-in">
            <i class="fas fa-check-circle mr-3 text-lg"></i>
            <span class="font-medium">Cập nhật dữ liệu thành công!</span>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-sm overflow-hidden border border-slate-200 rounded-xl">
        <table class="w-full text-left border-collapse border border-slate-200">
            <thead>
                <tr class="bg-slate-50/80 text-[11px] uppercase font-bold text-slate-600 tracking-wider">
                    <th class="px-4 py-3 border border-slate-200 text-center" style="width: 50px;">STT</th>
                    <th class="px-4 py-3 border border-slate-200">EMAIL TÀI KHOẢN</th>
                    <th class="px-4 py-3 border border-slate-200">SMTP SERVER</th>
                    <th class="px-4 py-3 border border-slate-200 text-center">HẠN MỨC NGÀY</th>
                    <th class="px-4 py-3 border border-slate-200 text-center">ĐÃ GỬI (NAY)</th>
                    <th class="px-4 py-3 border border-slate-200 text-center">VAI TRÒ</th>
                    <th class="px-4 py-3 border border-slate-200 text-center">LOẠI</th>
                    <th class="px-4 py-3 border border-slate-200 text-center">TRẠNG THÁI</th>
                    <th class="px-4 py-3 border border-slate-200 text-center" style="width: 140px;">THAO TÁC</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-sm text-slate-700">
                <?php if (empty($senders)): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-10 text-center text-slate-400 italic">
                            Chưa có tài khoản nào được cấu hình.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($senders as $index => $s): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors <?= $s['is_default'] ? 'bg-blue-50/20' : '' ?>">
                            <td class="px-4 py-3 border border-slate-200 text-center text-slate-500">
                                <?= $index + 1 ?>
                            </td>
                            <td class="px-4 py-3 border border-slate-200">
                                <div class="flex items-center">
                                    <?php if ($s['is_default']): ?>
                                        <i class="fas fa-star text-amber-400 mr-2 text-xs" title="Mặc định"></i>
                                    <?php endif; ?>
                                    <span class="font-medium text-slate-900"><?= htmlspecialchars($s['email']) ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 border border-slate-200">
                                <span class="text-slate-600"><?= htmlspecialchars($s['smtp_host']) ?>:<?= $s['smtp_port'] ?></span>
                            </td>
                            <td class="px-4 py-3 border border-slate-200 text-center font-medium">
                                <?= number_format($s['daily_limit']) ?>
                            </td>
                            <td class="px-4 py-3 border border-slate-200 text-center">
                                <?php 
                                    $percent = min(100, ($s['sent_today'] / max(1, $s['daily_limit'])) * 100);
                                    $color = $percent > 90 ? 'text-rose-600' : ($percent > 70 ? 'text-amber-600' : 'text-emerald-600');
                                ?>
                                <span class="<?= $color ?> font-bold"><?= number_format($s['sent_today']) ?></span>
                            </td>
                            <td class="px-4 py-3 border border-slate-200 text-center">
                                <?php if ($s['is_default']): ?>
                                    <span class="text-[10px] font-bold text-blue-600 uppercase">Mặc định</span>
                                <?php else: ?>
                                    <span class="text-[10px] text-slate-400 uppercase">Thông thường</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 border border-slate-200 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 uppercase">
                                    <?= htmlspecialchars($s['category'] ?? 'all') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 border border-slate-200 text-center">
                                <?php if ($s['is_active']): ?>
                                    <span class="text-emerald-600 font-medium">Hoạt động</span>
                                <?php else: ?>
                                    <span class="text-slate-400">Tạm dừng</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 border border-slate-200 text-center">
                                <div class="flex justify-center gap-1">
                                    <button type="button" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition btn-test-connection" data-id="<?= $s['id'] ?>" title="Test">
                                        <i class="fas fa-paper-plane text-xs"></i>
                                    </button>
                                    <button onclick='editSender(<?= json_encode($s) ?>)' class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Sửa">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                    <form action="<?= url('/admin/settings/email-senders/delete') ?>" method="POST" onsubmit="return confirm('Xác nhận xóa tài khoản này?')" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Xóa">
                                            <i class="fas fa-trash-alt text-xs"></i>
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
</div>

<!-- Modal Form -->
<div id="senderModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-slide-up">
        <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h2 id="modalTitle" class="text-xl font-bold text-slate-800">Thêm tài khoản SMTP mới</h2>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form action="<?= url('/admin/settings/email-senders/save') ?>" method="POST" class="p-6">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="field-id" value="">
            
            <!-- Tên hiển thị -->
            <div class="flex items-center mb-3">
                <label class="w-32 text-[10px] font-bold text-slate-500 uppercase flex-shrink-0">Tên hiển thị</label>
                <div class="flex-1">
                    <input type="text" name="name" id="field-name" required placeholder="VD: Tuyển sinh HVU"
                           value="Tổ tuyển sinh - Trường Đại học Hùng Vương"
                           class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 text-sm">
                </div>
            </div>
            
            <!-- Email tài khoản -->
            <div class="flex items-center mb-3">
                <label class="w-32 text-[10px] font-bold text-slate-500 uppercase flex-shrink-0">Email tài khoản</label>
                <div class="flex-1">
                    <input type="email" name="email" id="field-email" required placeholder="VD: tuyensinh01@gmail.com"
                           class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 text-sm">
                </div>
            </div>

            <!-- SMTP Host & Port -->
            <div class="flex items-center mb-3 gap-2">
                <label class="w-28 text-[10px] font-bold text-slate-500 uppercase flex-shrink-0">SMTP Host</label>
                <input type="text" name="smtp_host" id="field-smtp_host" required value="smtp.gmail.com"
                       class="w-48 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 text-sm">
                
                <label class="w-12 text-[10px] font-bold text-slate-500 uppercase flex-shrink-0 text-center">Port</label>
                <input type="number" name="smtp_port" id="field-smtp_port" required value="587"
                       class="w-20 px-2 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 text-sm text-center">
            </div>

            <!-- SMTP Credentials -->
            <div class="flex items-center mb-3">
                <label class="w-28 text-[10px] font-bold text-slate-500 uppercase flex-shrink-0">SMTP User</label>
                <div class="flex-1">
                    <input type="text" name="smtp_user" id="field-smtp_user" required placeholder="Username email"
                           class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 text-sm">
                </div>
            </div>

            <div class="flex items-center mb-3">
                <label class="w-28 text-[10px] font-bold text-slate-500 uppercase flex-shrink-0">SMTP Pass</label>
                <div class="flex-1">
                    <input type="password" name="smtp_pass" id="field-smtp_pass" required placeholder="Mật khẩu ứng dụng"
                           class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 text-sm">
                </div>
            </div>

            <!-- Encryption & Limit -->
            <div class="flex items-center mb-3 gap-2">
                <label class="w-28 text-[10px] font-bold text-slate-500 uppercase flex-shrink-0">Mã hóa</label>
                <select name="smtp_encryption" id="field-smtp_encryption" class="w-32 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-blue-100 transition text-sm">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                </select>

                <label class="w-20 text-[10px] font-bold text-slate-500 uppercase flex-shrink-0 text-center">Hạn mức</label>
                <input type="number" name="daily_limit" id="field-daily_limit" required value="1000"
                       class="w-24 px-2 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 text-sm text-center">
            </div>

            <!-- Category & Default -->
            <div class="flex items-center mb-4">
                <label class="w-32 text-[10px] font-bold text-slate-500 uppercase flex-shrink-0">Phân loại</label>
                <div class="flex-1 flex items-center gap-4">
                    <select name="category" id="field-category" class="flex-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-blue-100 transition text-sm">
                        <option value="all">Tất cả (Round-robin)</option>
                        <option value="admission_letter">Thư trúng tuyển</option>
                        <option value="bulk">Gửi hàng loạt</option>
                    </select>
                    <label class="relative inline-flex items-center cursor-pointer ml-2">
                        <input type="checkbox" name="is_default" id="field-is_default" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-[10px] font-bold text-slate-500 uppercase">Mặc định</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center mb-6 pl-32">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" id="field-is_active" value="1" checked class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-xs font-bold text-slate-600">Kích hoạt tài khoản này</span>
                </label>
            </div>

            <div class="flex gap-3 pt-4 border-t border-slate-50">
                <button type="button" onclick="closeModal()" class="flex-1 px-6 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-lg hover:bg-slate-200 transition text-sm">Hủy</button>
                <button type="submit" class="flex-1 px-6 py-2.5 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition shadow-lg shadow-blue-200 text-sm">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modalTitle').innerText = 'Thêm tài khoản SMTP mới';
        document.getElementById('field-id').value = '';
        document.getElementById('field-name').value = 'Tổ tuyển sinh - Trường Đại học Hùng Vương';
        document.getElementById('field-email').value = '';
        document.getElementById('field-smtp_host').value = 'smtp.gmail.com';
        document.getElementById('field-smtp_port').value = '587';
        document.getElementById('field-smtp_encryption').value = 'tls';
        document.getElementById('field-daily_limit').value = '1000';
        document.getElementById('field-smtp_user').value = '';
        document.getElementById('field-smtp_pass').value = '';
        document.getElementById('field-is_active').checked = true;
        document.getElementById('field-is_default').checked = false;
        document.getElementById('field-category').value = 'all';
        document.getElementById('senderModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('senderModal').classList.add('hidden');
    }

    function editSender(data) {
        document.getElementById('modalTitle').innerText = 'Chỉnh sửa tài khoản';
        document.getElementById('field-id').value = data.id;
        document.getElementById('field-name').value = data.name;
        document.getElementById('field-email').value = data.email;
        document.getElementById('field-smtp_host').value = data.smtp_host;
        document.getElementById('field-smtp_port').value = data.smtp_port;
        document.getElementById('field-smtp_user').value = data.smtp_user;
        document.getElementById('field-smtp_pass').value = data.smtp_pass;
        document.getElementById('field-smtp_encryption').value = data.smtp_encryption;
        document.getElementById('field-daily_limit').value = data.daily_limit;
        document.getElementById('field-category').value = data.category || 'all';
        document.getElementById('field-is_active').checked = data.is_active == 1 || data.is_active === true || data.is_active === 't' || data.is_active === '1';
        document.getElementById('field-is_default').checked = data.is_default == 1 || data.is_default === true || data.is_default === 't' || data.is_default === '1';
        document.getElementById('senderModal').classList.remove('hidden');
    }

    // Đóng modal khi click ra ngoài
    window.onclick = function(event) {
        const modal = document.getElementById('senderModal');
        if (event.target == modal) closeModal();
    }

    // Test Connection
    $(document).ready(function() {
        $(document).on('click', '.btn-test-connection', function() {
        const btn = $(this);
        const id = btn.data('id');
        const originalHtml = btn.html();

        if (confirm('Gửi email thử nghiệm từ tài khoản này?')) {
            const recipient = prompt('Nhập email nhận thư thử nghiệm:', 'tuyensinh@hvu.edu.vn');
            if (!recipient) return;

            btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.ajax({
                url: '<?= url("/admin/settings/email-senders/test") ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    id: id,
                    test_email: recipient,
                    _csrf_token: '<?= csrf_token() ?>'
                },
                success: function(res) {
                    if (res.success) {
                        alert('Thành công: ' + res.message);
                    } else {
                        alert('Thất bại: ' + res.message);
                    }
                },
                error: function() {
                    alert('Có lỗi xảy ra trong quá trình kiểm tra.');
                },
                complete: function() {
                    btn.html(originalHtml).prop('disabled', false);
                }
            });
        }
    });
});
</script>

<style>
@keyframes slide-up {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}
</style>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
