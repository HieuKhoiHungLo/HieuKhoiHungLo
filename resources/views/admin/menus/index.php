<?php
$title = 'Quản lý Menu';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center">
            <i class="fas fa-list text-blue-600 mr-3"></i> Quản lý Menu
        </h2>
        <p class="text-slate-500 text-sm mt-1">Cấu hình các liên kết điều hướng trên hệ thống.</p>
    </div>
    <a href="<?= url('/admin/menus/create?position=' . $position) ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition flex items-center">
        <i class="fas fa-plus mr-2"></i> Thêm Menu
    </a>
</div>

<!-- Tabs cho Position -->
<div class="mb-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-wrap">
    <?php
    $positions = [
        'header_public' => ['label' => 'Thanh điều hướng Frontend', 'icon' => 'fa-laptop'],
        'admin_sidebar' => ['label' => 'Sidebar Quản trị', 'icon' => 'fa-columns'],
        'footer_quick' => ['label' => 'Footer (Liên kết nhanh)', 'icon' => 'fa-bolt'],
        'footer' => ['label' => 'Footer (Hỗ trợ thí sinh)', 'icon' => 'fa-life-ring']
    ];
    foreach ($positions as $posKey => $posData):
        $isActive = ($position === $posKey);
    ?>
        <a href="<?= url('/admin/menus?position=' . $posKey) ?>" class="flex-1 py-3 px-4 text-center font-semibold text-sm transition-colors border-b-2 <?= $isActive ? 'border-blue-600 text-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
            <i class="fas <?= $posData['icon'] ?> mr-2"></i> <?= $posData['label'] ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-slate-50 dark:bg-slate-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-16">STT</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tên hiển thị</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Đường dẫn</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Quy tắc / Phân quyền</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($menus)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                            Chưa có menu nào được cấu hình cho khu vực này.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $index = 1;
                    function renderMenuRows($items, $level, &$index) {
                        $html = '';
                        foreach ($items as $item) {
                            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                            $icon = $item['icon'] ? "<i class='fas {$item['icon']} w-5 text-center mr-1 text-slate-400'></i>" : "";
                            
                            $visibilityHtml = '';
                            if ($item['visibility'] == 'public') $visibilityHtml = '<span class="px-2 py-1 text-[10px] font-bold rounded bg-slate-100 text-slate-600">Tất cả</span>';
                            if ($item['visibility'] == 'guest_only') $visibilityHtml = '<span class="px-2 py-1 text-[10px] font-bold rounded bg-amber-100 text-amber-700">Khách</span>';
                            if ($item['visibility'] == 'auth_only') $visibilityHtml = '<span class="px-2 py-1 text-[10px] font-bold rounded bg-blue-100 text-blue-700">Đã đăng nhập</span>';
                            if ($item['visibility'] == 'admin_only') $visibilityHtml = '<span class="px-2 py-1 text-[10px] font-bold rounded bg-purple-100 text-purple-700">Admin</span>';
                            
                            $permHtml = $item['permission_required'] ? "<div class='text-xs text-rose-500 font-medium mt-1'><i class='fas fa-lock text-[10px]'></i> {$item['permission_required']}</div>" : '';
                            
                            $status = $item['is_active'] ? '<span class="px-2 py-1 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700"><i class="fas fa-check mr-1"></i>Hoạt động</span>' : '<span class="px-2 py-1 text-[10px] font-bold rounded-full bg-slate-100 text-slate-500">Tạm ẩn</span>';

                            $html .= "
                            <tr class='hover:bg-slate-50 dark:hover:bg-slate-700/50 transition'>
                                <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-500'>{$item['order_index']}</td>
                                <td class='px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white font-medium'>
                                    {$indent} {$icon} {$item['title']}
                                </td>
                                <td class='px-6 py-4 whitespace-nowrap text-sm text-blue-600 font-mono'>
                                    <a href='".url($item['url'])."' target='_blank' class='hover:underline'>{$item['url']}</a>
                                </td>
                                <td class='px-6 py-4 whitespace-nowrap'>
                                    {$visibilityHtml}
                                    {$permHtml}
                                </td>
                                <td class='px-6 py-4 whitespace-nowrap text-center'>
                                    {$status}
                                </td>
                                <td class='px-6 py-4 whitespace-nowrap text-right text-sm font-medium'>
                                    <a href='".url("/admin/menus/edit?id={$item['id']}")."' class='text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 p-2 rounded-lg transition mr-1'>
                                        <i class='fas fa-edit'></i>
                                    </a>
                                    <a href='#' onclick=\"confirmDelete('".url("/admin/menus/delete?id={$item['id']}")."')\" class='text-rose-600 hover:text-rose-900 bg-rose-50 hover:bg-rose-100 p-2 rounded-lg transition'>
                                        <i class='fas fa-trash'></i>
                                    </a>
                                </td>
                            </tr>
                            ";
                            $index++;
                            if (!empty($item['children'])) {
                                $html .= renderMenuRows($item['children'], $level + 1, $index);
                            }
                        }
                        return $html;
                    }
                    echo renderMenuRows($menus, 0, $index);
                    ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function confirmDelete(url) {
        if(confirm('Bạn có chắc chắn muốn xóa menu này? Tất cả menu con (nếu có) cũng sẽ bị xóa.')) {
            window.location.href = url;
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>
