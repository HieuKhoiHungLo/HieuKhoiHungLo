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

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tên hiển thị / Email</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">SMTP Server</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Hạn mức / Đã gửi (Ngày)</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Vai trò / Loại</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Trạng thái</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($senders)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-slate-400 italic">
                            Chưa có tài khoản nào được cấu hình.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($senders as $s): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors group <?= $s['is_default'] ? 'bg-blue-50/30' : '' ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if ($s['is_default']): ?>
                                        <i class="fas fa-star text-amber-400 mr-2" title="Tài khoản mặc định"></i>
                                    <?php endif; ?>
                                    <div class="font-bold text-slate-800"><?= htmlspecialchars($s['name']) ?></div>
                                </div>
                                <div class="text-sm text-slate-500"><?= htmlspecialchars($s['email']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-slate-700"><?= htmlspecialchars($s['smtp_host']) ?>:<?= $s['smtp_port'] ?></div>
                                <div class="text-[10px] text-slate-400 uppercase"><?= htmlspecialchars($s['smtp_encryption']) ?> / <?= htmlspecialchars($s['smtp_user']) ?></div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-32 bg-slate-100 h-1.5 rounded-full overflow-hidden mb-1">
                                        <?php 
                                            $percent = min(100, ($s['sent_today'] / max(1, $s['daily_limit'])) * 100);
                                            $color = $percent > 90 ? 'bg-rose-500' : ($percent > 70 ? 'bg-amber-500' : 'bg-emerald-500');
                                        ?>
                                        <div class="<?= $color ?> h-full" style="width: <?= $percent ?>%"></div>
                                    </div>
                                    <span class="text-xs font-bold text-slate-600"><?= number_format($s['sent_today']) ?> / <?= number_format($s['daily_limit']) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col gap-1 items-center">
                                    <?php if ($s['is_default']): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 uppercase">Mặc định</span>
                                    <?php endif; ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 uppercase"><?= htmlspecialchars($s['category'] ?? 'all') ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($s['is_active']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        Đang hoạt động
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                        Tạm dừng
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="p-2 text-slate-400 hover:text-sky-600 transition btn-test-connection" data-id="<?= $s['id'] ?>" title="Kiểm tra kết nối">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                    <button onclick='editSender(<?= json_encode($s) ?>)' class="p-2 text-slate-400 hover:text-blue-600 transition">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="<?= url('/admin/settings/email-senders/delete') ?>" method="POST" onsubmit="return confirm('Xác nhận xóa tài khoản này?')" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 transition">
                                            <i class="fas fa-trash-alt"></i>
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
        
        <form action="<?= url('/admin/settings/email-senders/save') ?>" method="POST" class="p-8">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="field-id" value="">
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tên hiển thị (Người gửi)</label>
                    <input type="text" name="name" id="field-name" required placeholder="VD: Tuyển sinh HVU"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-medium">
                </div>
                
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Email tài khoản</label>
                    <input type="email" name="email" id="field-email" required placeholder="VD: tuyensinh01@gmail.com"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-medium">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">SMTP Host</label>
                    <input type="text" name="smtp_host" id="field-smtp_host" required value="smtp.gmail.com"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-medium">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Port</label>
                    <input type="number" name="smtp_port" id="field-smtp_port" required value="587"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-medium">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">SMTP User</label>
                    <input type="text" name="smtp_user" id="field-smtp_user" required placeholder="Username email"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-medium">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">SMTP Pass / App Password</label>
                    <input type="password" name="smtp_pass" id="field-smtp_pass" required placeholder="Mật khẩu ứng dụng"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-medium">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Mã hóa</label>
                    <select name="smtp_encryption" id="field-smtp_encryption" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-blue-100 transition">
                        <option value="tls">TLS (Khuyên dùng)</option>
                        <option value="ssl">SSL</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Hạn mức ngày</label>
                    <input type="number" name="daily_limit" id="field-daily_limit" required value="1500"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition outline-none text-slate-700 font-medium">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Phân loại (Category)</label>
                    <select name="category" id="field-category" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-blue-100 transition">
                        <option value="all">Tất cả (Round-robin)</option>
                        <option value="admission_letter">Thư trúng tuyển</option>
                        <option value="bulk">Gửi hàng loạt</option>
                    </select>
                </div>
                <div class="flex items-end pb-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_default" id="field-is_default" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                        <span class="ml-3 text-sm font-bold text-slate-600">Mặc định hệ thống</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center mb-8">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" id="field-is_active" value="1" checked class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-bold text-slate-600">Kích hoạt tài khoản này</span>
                </label>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 px-6 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition">Hủy</button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-200">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modalTitle').innerText = 'Thêm tài khoản SMTP mới';
        document.getElementById('field-id').value = '';
        document.getElementById('field-name').value = '';
        document.getElementById('field-email').value = '';
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
        document.getElementById('field-is_active').checked = data.is_active == 1;
        document.getElementById('field-is_default').checked = data.is_default == 1;
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
