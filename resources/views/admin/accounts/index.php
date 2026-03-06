<?php ob_start(); ?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight font-heading">Quản lý Tài khoản</h2>
        <p class="text-sm text-slate-500 font-medium">Danh sách quản trị viên hệ thống</p>
    </div>
    <a href="<?= url('/admin/accounts/create') ?>" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-cyan-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-200 hover:shadow-indigo-300 transition transform hover:-translate-y-0.5 flex items-center">
        <i class="fas fa-plus mr-2 text-sm"></i> Thêm mới
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 border-b border-slate-100">
            <tr>
                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-wider">Thông tin tài khoản</th>
                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-wider">Phân quyền</th>
                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-wider text-center">Trạng thái</th>
                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-wider text-right">Tác vụ</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            <?php if (empty($accounts)): ?>
                <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400 italic font-medium">Danh sách trống.</td></tr>
            <?php else: ?>
                <?php foreach ($accounts as $acc): ?>
                    <tr class="hover:bg-slate-50/80 transition duration-150 group">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold border border-slate-200 uppercase">
                                    <?= mb_substr($acc['ho_ten'], 0, 1) ?>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($acc['ho_ten']) ?></p>
                                    <p class="text-xs text-slate-500 font-mono">@<?= htmlspecialchars($acc['ten_dang_nhap']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                <?php 
                                    $perms = json_decode($acc['permissions'] ?? '[]', true);
                                    if (in_array('all', $perms)) {
                                        echo '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase bg-cyan-100 text-cyan-700 border border-cyan-200">Super Admin</span>';
                                    } else {
                                        foreach($perms as $p) {
                                            echo '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-600 border border-slate-200">'.$p.'</span>';
                                        }
                                    }
                                ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($acc['is_active']): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Active
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-500 border border-slate-200">
                                    Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <a href="<?= url('/admin/accounts/edit?id=' . $acc['id']) ?>" 
                                   class="p-2 text-[#0066FF] hover:bg-indigo-50 rounded-lg transition" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($acc['id'] != $_SESSION['admin_id'] && $acc['id'] != 1): ?>
                                    <a href="<?= url('/admin/accounts/delete?id=' . $acc['id']) ?>" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?')" 
                                       class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition" title="Xóa">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php'; 
?>
