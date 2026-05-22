<?php $title = 'Quản lý Tài khoản - Admin'; ?>
<?php ob_start(); ?>

<!-- Custom Styles matching the candidate table style with grid lines and normal fonts -->
<style>
    .accounts-table-container {
        background: #fff;
    }
    .accounts-table {
        border-collapse: separate !important;
        border-spacing: 0;
        width: 100%;
    }
    .accounts-table th, .accounts-table td {
        padding: 0.5rem 0.75rem;
        border: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        border-right: 1px solid #e2e8f0 !important;
        vertical-align: middle;
        font-size: 14px;
        font-weight: 400 !important; /* Chữ thường, không đậm */
        color: #0f172a !important; /* Màu đen/slate đậm tối giản */
    }
    .accounts-table th:first-child, .accounts-table td:first-child {
        border-left: 1px solid #e2e8f0 !important;
    }
    .accounts-table thead tr:first-child th {
        border-top: 1px solid #e2e8f0 !important;
    }

    /* Header Styling */
    .accounts-table thead th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 600 !important; /* Đậm vừa phải cho tiêu đề */
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-size: 13px !important;
    }

    /* Hover State */
    .accounts-table tbody tr:hover td {
        background-color: #f8fafc !important;
    }
</style>

<!-- Compact Header -->
<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl lg:text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight font-heading">Quản lý Tài khoản</h2>
        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Danh sách quản trị viên và cán bộ xử lý hệ thống</p>
    </div>
    <a href="<?= url('/admin/accounts/create') ?>" class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow-lg shadow-blue-100 dark:shadow-none hover:-translate-y-0.5 transition duration-150">
        <i class="fas fa-plus mr-1.5"></i> Thêm mới
    </a>
</div>

<!-- Table Card Container -->
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm overflow-hidden accounts-table-container">
    <table class="accounts-table">
        <thead>
            <tr>
                <th class="w-1/6">Tài khoản</th>
                <th class="w-1/4">Tên hiển thị</th>
                <th class="w-1/3">Phân quyền</th>
                <th class="w-1/8 text-center">Trạng thái</th>
                <th class="w-1/8 text-right">Tác vụ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($accounts)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic font-normal">
                        Danh sách trống.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($accounts as $acc): ?>
                    <tr class="group">
                        <!-- Tài khoản (Username) -->
                        <td class="font-mono text-slate-800 dark:text-slate-200">
                            @<?= htmlspecialchars($acc['ten_dang_nhap']) ?>
                        </td>
                        
                        <!-- Tên hiển thị -->
                        <td class="text-slate-800 dark:text-slate-200">
                            <?= htmlspecialchars($acc['ho_ten']) ?>
                        </td>
                        
                        <!-- Phân quyền (Roles/Permissions) -->
                        <td class="text-slate-800 dark:text-slate-200">
                            <?php 
                                $perms = json_decode($acc['permissions'] ?? '[]', true);
                                if (in_array('all', $perms)) {
                                    echo 'super admin';
                                } else {
                                    if (empty($perms)) {
                                        echo '<span class="text-slate-400 dark:text-slate-600 italic">không có quyền</span>';
                                    } else {
                                        echo implode(', ', array_map('strtolower', $perms));
                                    }
                                }
                            ?>
                        </td>
                        
                        <!-- Trạng thái (Status) -->
                        <td class="text-center">
                            <span class="inline-flex items-center text-slate-800 dark:text-slate-200">
                                <span class="w-1.5 h-1.5 rounded-full <?= $acc['is_active'] ? 'bg-emerald-500' : 'bg-slate-300' ?> mr-1.5"></span>
                                <?= $acc['is_active'] ? 'hoạt động' : 'khóa' ?>
                            </span>
                        </td>
                        
                        <!-- Tác vụ (Actions) -->
                        <td class="text-right">
                            <div class="flex items-center justify-end space-x-1">
                                <a href="<?= url('/admin/accounts/edit?id=' . $acc['id']) ?>" 
                                   class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-700 rounded-lg transition-colors" title="Chỉnh sửa">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                                <?php if ($acc['id'] != $_SESSION['admin_id'] && $acc['id'] != 1): ?>
                                    <a href="<?= url('/admin/accounts/delete?id=' . $acc['id']) ?>" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?')" 
                                       class="p-2 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-colors" title="Xóa">
                                        <i class="fas fa-trash-alt text-sm"></i>
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
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
