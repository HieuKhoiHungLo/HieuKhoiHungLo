<aside class="admin-sidebar fixed left-0 top-0 h-full text-white flex flex-col z-50 shadow-2xl transition-all duration-300">
    <!-- Brand -->
    <div class="h-20 flex items-center px-6 border-b border-white/10 bg-black/10">
        <div class="flex items-center justify-center">
            <img src="<?= url('/assets/img/Logo.png') ?>" alt="HVU Logo" class="h-10 w-auto object-contain">
        </div>
        <div class="ml-4">
            <h1 class="font-black text-[10px] tracking-wider text-white font-heading uppercase leading-tight">QUẢN TRỊ HỆ THỐNG</h1>
            <p class="text-[14px] text-indigo-100 uppercase tracking-widest font-bold">TUYỂN SINH</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-grow py-6 px-3 space-y-1 overflow-y-auto custom-scrollbar">
        <?php
        $currentUri = $_SERVER['REQUEST_URI'];
        $menu = [
            ['section' => 'QUẢN LÝ CHUNG'],
            ['url' => '/admin/dashboard', 'icon' => 'fa-chart-line', 'label' => 'Dashboard'],
            ['url' => '/admin/stats', 'icon' => 'fa-chart-pie', 'label' => 'Báo cáo Thống kê'],
            
            ['section' => 'HỒ SƠ TUYỂN SINH'],
            ['url' => '/admin/review', 'icon' => 'fa-user-graduate', 'label' => 'Xét duyệt Hồ sơ'],
            ['url' => '/admin/admission/benchmarks', 'icon' => 'fa-sliders-h', 'label' => 'Thiết lập Điểm chuẩn'],
            ['url' => '/admin/admission/results', 'icon' => 'fa-list-ol', 'label' => 'Kết quả Trúng tuyển'],
            // ['url' => '/admin/candidates', 'icon' => 'fa-users', 'label' => 'Danh sách Thí sinh'],
            
            ['section' => 'QUẢN TRỊ HỆ THỐNG'],
            ['url' => '/admin/accounts', 'icon' => 'fa-users-cog', 'label' => 'Tài khoản Admin'],
            ['url' => '/admin/posts', 'icon' => 'fa-newspaper', 'label' => 'Tin tức & Bài viết'],
            
            ['url' => '#', 'icon' => 'fa-database', 'label' => 'Danh mục dữ liệu', 'submenu' => [
                 ['url' => '/admin/master-data/subjects', 'label' => 'Môn học'],
                 ['url' => '/admin/master-data/combinations', 'label' => 'Tổ hợp xét tuyển'],
                 ['url' => '/admin/master-data/majors', 'label' => 'Ngành đào tạo'],
                 ['url' => '/admin/master-data/schools', 'label' => 'Trường THPT'],
                 ['url' => '/admin/master-data/provinces', 'label' => 'Danh mục Tỉnh'],
                 ['url' => '/admin/master-data/wards', 'label' => 'Xã / Phường'],
                 ['url' => '/admin/master-data/sessions', 'label' => 'Đợt tuyển sinh'],
                 ['url' => '/admin/master-data/settings', 'label' => 'Cấu hình chung']
            ]],
            
            ['section' => 'BÁO CÁO'],
            ['url' => '/admin/reports', 'icon' => 'fa-file-export', 'label' => 'Xuất dữ liệu'],
            
            ['section' => 'HỆ THỐNG'],
            ['url' => '/admin/roles', 'icon' => 'fa-user-shield', 'label' => 'Quản lý Vai trò'],
            ['url' => '/admin/audit', 'icon' => 'fa-history', 'label' => 'Nhật ký Hoạt động'],
            ['url' => '#', 'icon' => 'fa-cog', 'label' => 'Cấu hình', 'submenu' => [
                 ['url' => '/admin/settings/email', 'label' => 'Cấu hình Email'],
                 ['url' => '/admin/settings/scoring', 'label' => 'Cấu hình Điểm'],
            ]],
        ];

        foreach ($menu as $item):
            if (isset($item['section'])): ?>
                <div class="px-4 mt-6 mb-2">
                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest"><?= $item['section'] ?></p>
                </div>
            <?php continue; endif;

            $isActive = strpos($currentUri, $item['url']) !== false && $item['url'] !== '#';
            if ($item['url'] == '/admin/dashboard' && $currentUri == '/TS/admin/dashboard') $isActive = true;
        ?>
            <?php if (isset($item['submenu'])): ?>
                <div x-data="{ open: false }" class="mb-1">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 text-indigo-100 hover:bg-white/5 hover:text-white group">
                        <div class="flex items-center">
                            <span class="w-6 text-center"><i class="fas <?= $item['icon'] ?> text-indigo-400 group-hover:text-white transition-colors"></i></span>
                            <span class="ml-3 font-semibold"><?= $item['label'] ?></span>
                        </div>
                        <i class="fas fa-chevron-right text-[10px] transition-transform duration-200 text-indigo-400" :class="{'rotate-90': open}"></i>
                    </button>
                    <div x-show="open" class="pl-11 pr-2 py-1 space-y-1 mt-1" style="display: none;">
                        <?php foreach ($item['submenu'] as $sub): 
                            $isSubActive = strpos($currentUri, $sub['url']) !== false;
                        ?>
                            <a href="<?= url($sub['url']) ?>" class="block px-3 py-2 rounded-lg text-xs font-semibold transition-colors <?= $isSubActive ? 'bg-indigo-600 text-white shadow-md' : 'text-indigo-300 hover:text-white hover:bg-white/5' ?>">
                                <?= $sub['label'] ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= url($item['url']) ?>" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 group mb-1 <?= $isActive ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-indigo-100 hover:bg-white/5 hover:text-white' ?>">
                    <span class="w-6 text-center"><i class="fas <?= $item['icon'] ?> transition-colors <?= $isActive ? 'text-white' : 'text-indigo-400 group-hover:text-white' ?>"></i></span>
                    <span class="ml-3 font-semibold"><?= $item['label'] ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom Actions -->
    <div class="p-4 border-t border-white/10 bg-black/20 backdrop-blur-sm">
        <a href="<?= url('/logout') ?>" class="flex items-center justify-center w-full px-4 py-3 text-xs font-bold text-red-200 bg-red-900/20 hover:bg-red-600 hover:text-white rounded-xl transition-all duration-300 border border-red-900/30 group">
            <i class="fas fa-sign-out-alt mr-2 group-hover:-translate-x-1 transition-transform"></i> 
            ĐĂNG XUẤT
        </a>
    </div>
</aside>
<!-- Script for AlpineJS if not present in main layout, but added here just in case for submenus -->
<script src="<?= url('/assets/js/alpine.min.js') ?>" defer></script>
