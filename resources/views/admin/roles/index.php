<?php $title = 'Quản lý Vai trò - Admin'; ?>
<?php ob_start(); ?>

<!-- CSS overrides to lock scroll and create a premium 100vh app experience -->
<style>
    /* Hide the default global admin footer to keep layout within viewport */
    body > div.main-content > footer {
        display: none !important;
    }
    
    /* Make the main workspace container fill 100vh and handle scrolling internally */
    body > div.main-content > main {
        padding: 1.25rem !important;
        height: calc(100vh - 4rem) !important; /* Header is 4rem (64px) */
        overflow: hidden !important;
        display: flex;
        flex-direction: column;
    }

    /* Custom ultra-thin scrollbars */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.2);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(156, 163, 175, 0.4);
    }
    
    /* Hide scrollbar for Chrome, Safari and Opera */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    /* Hide scrollbar for IE, Edge and Firefox */
    .no-scrollbar {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
</style>

<div x-data="{
    selectedRoleId: <?= $roles[0]['id'] ?? 0 ?>,
    roles: <?= htmlspecialchars(json_encode($roles), ENT_QUOTES, 'UTF-8') ?>,
    allPermissions: <?= htmlspecialchars(json_encode($allPermissions), ENT_QUOTES, 'UTF-8') ?>,
    searchQuery: '',
    activeTab: '',
    showCreateModal: false,
    newRolePermissions: [],
    modalActiveTab: '',
    
    init() {
        const keys = Object.keys(this.allPermissions);
        if (keys.length > 0) {
            this.activeTab = keys[0];
            this.modalActiveTab = keys[0];
        }
    },
    get filteredRoles() {
        if (!this.searchQuery) return this.roles;
        const q = this.searchQuery.toLowerCase();
        return this.roles.filter(r => 
            r.display_name.toLowerCase().includes(q) || 
            r.name.toLowerCase().includes(q)
        );
    },
    get selectedRole() {
        return this.roles.find(r => r.id == this.selectedRoleId) || null;
    },
    get selectedPermissions() {
        if (!this.selectedRole) return [];
        try {
            return JSON.parse(this.selectedRole.permissions) || [];
        } catch(e) {
            return [];
        }
    },
    hasPermission(key) {
        return this.selectedPermissions.includes('all') || this.selectedPermissions.includes(key);
    },
    getPermissionCount(role) {
        try {
            const perms = JSON.parse(role.permissions) || [];
            if (perms.includes('all')) return 'Toàn quyền';
            return perms.length + ' quyền';
        } catch(e) {
            return '0 quyền';
        }
    },
    toggleModalGroup(keys, event) {
        const isChecked = event.target.checked;
        if (isChecked) {
            keys.forEach(k => {
                if (!this.newRolePermissions.includes(k)) {
                    this.newRolePermissions.push(k);
                }
            });
        } else {
            this.newRolePermissions = this.newRolePermissions.filter(k => !keys.includes(k));
        }
    },
    isModalGroupChecked(keys) {
        return keys.length > 0 && keys.every(k => this.newRolePermissions.includes(k));
    }
}" class="flex-grow flex flex-col min-h-0 w-full animate-fadeIn">

    <!-- Compact Header -->
    <header class="mb-3.5 flex justify-between items-center flex-shrink-0">
        <div>
            <h2 class="text-xl lg:text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight font-heading flex items-center gap-2">
                <span class="p-1.5 bg-blue-600/10 text-blue-600 rounded-xl dark:bg-blue-500/20 dark:text-blue-400">
                    <i class="fas fa-user-shield text-lg"></i>
                </span>
                Quản lý Vai trò & Phân quyền
            </h2>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Phân bổ và chủ động khởi tạo quyền hạn truy cập cho các nhóm người dùng</p>
        </div>

        <!-- Add Role Button -->
        <button @click="showCreateModal = true" 
                class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-100 dark:shadow-none hover:-translate-y-0.5 transition duration-150 gap-1.5 uppercase tracking-wider">
            <i class="fas fa-plus text-xs"></i> Thêm vai trò mới
        </button>
    </header>

    <!-- Message Banners -->
    <?php if (!empty($_GET['msg']) && $_GET['msg'] == 'created'): ?>
        <div class="mb-3 p-3 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 rounded-xl font-bold border border-emerald-100 dark:border-emerald-900/30 flex items-center text-xs flex-shrink-0">
            <i class="fas fa-check-circle mr-2 text-base text-emerald-500"></i> Đã tạo mới vai trò thành công!
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
        <div class="mb-3 p-3 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 rounded-xl font-bold border border-emerald-100 dark:border-emerald-900/30 flex items-center text-xs flex-shrink-0">
            <i class="fas fa-check-circle mr-2 text-base text-emerald-500"></i> Đã cập nhật vai trò và lưu thay đổi thành công!
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="mb-3 p-3 bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 rounded-xl font-bold border border-amber-100 dark:border-amber-900/30 flex items-center text-xs flex-shrink-0">
            <i class="fas fa-trash-alt mr-2 text-base text-amber-500"></i> Đã xóa vai trò thành công!
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="mb-3 p-3 bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-400 rounded-xl font-bold border border-rose-100 dark:border-rose-900/30 flex items-center text-xs flex-shrink-0">
            <i class="fas fa-exclamation-circle mr-2 text-base text-rose-500"></i> <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Main Workspace Split Pane -->
    <div class="flex-grow grid grid-cols-1 lg:grid-cols-12 gap-4 min-h-0">
        
        <!-- Left Side: Role Card List (4 cols) -->
        <div class="lg:col-span-4 flex flex-col min-h-0 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/50 rounded-2xl shadow-sm">
            <div class="p-4 border-b border-slate-100 dark:border-slate-700/50 flex-shrink-0 flex justify-between items-center">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block">Danh sách vai trò</span>
                <span class="text-[10px] font-bold text-slate-400 font-mono" x-text="roles.length + ' vai trò'"></span>
            </div>
            
            <div class="p-3 border-b border-slate-100 dark:border-slate-700/50 flex-shrink-0">
                <!-- Instant Search Box -->
                <div class="relative">
                    <input type="text" x-model="searchQuery" placeholder="Tìm kiếm vai trò..." 
                           class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors dark:text-white dark:placeholder-slate-500">
                    <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 dark:text-slate-500 text-[11px]"></i>
                </div>
            </div>
            
            <!-- Roles Card List Scroll Container -->
            <div class="flex-grow overflow-y-auto p-3 custom-scrollbar space-y-2">
                <template x-for="role in filteredRoles" :key="role.id">
                    <div @click="selectedRoleId = role.id"
                         class="p-3.5 rounded-xl border cursor-pointer transition-all duration-200 group flex flex-col hover:-translate-y-0.5"
                         :class="selectedRoleId == role.id 
                            ? 'bg-blue-50/50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800 shadow-md shadow-blue-100/10' 
                            : 'bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700/60 hover:bg-slate-50 dark:hover:bg-slate-700/30 hover:border-slate-200 dark:hover:border-slate-600'">
                        <div class="flex justify-between items-center">
                            <div class="min-w-0 flex-1">
                                <h4 class="font-bold text-xs transition-colors flex items-center gap-1.5"
                                    :class="selectedRoleId == role.id ? 'text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-slate-100'">
                                    <i class="fas fa-shield-alt text-[10px]"></i>
                                    <span x-text="role.display_name"></span>
                                </h4>
                                <span class="text-[9px] text-slate-400 dark:text-slate-500 font-mono mt-0.5 block tracking-wider" x-text="role.name"></span>
                            </div>
                            <span class="text-[9px] px-2 py-0.5 rounded-full font-bold ml-2 flex-shrink-0 tracking-wide uppercase"
                                  :class="selectedRoleId == role.id 
                                    ? 'bg-blue-100/80 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300' 
                                    : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400'"
                                  x-text="getPermissionCount(role)"></span>
                        </div>
                    </div>
                </template>
                
                <!-- Empty State -->
                <template x-if="filteredRoles.length === 0">
                    <div class="py-12 text-center text-slate-400 dark:text-slate-500">
                        <i class="fas fa-user-shield text-2xl mb-2 text-slate-300 dark:text-slate-600"></i>
                        <p class="text-xs font-semibold">Không tìm thấy vai trò phù hợp</p>
                    </div>
                </template>
            </div>
            
            <!-- Dynamic Compact Footer -->
            <div class="p-3 border-t border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/10 text-[9px] text-center text-slate-400 dark:text-slate-500 flex-shrink-0 font-medium">
                <?php $v = include __DIR__ . '/../../../../config/version.php'; ?>
                &copy; <?= date('Y') ?> <?= $v['name'] ?> (V <?= $v['version'] ?>)
            </div>
        </div>

        <!-- Right Side: Interactive Permissions Matrix (8 cols) -->
        <div class="lg:col-span-8 flex flex-col min-h-0 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/50 rounded-2xl shadow-sm">
            
            <template x-if="selectedRole">
                <div class="flex-grow flex flex-col min-h-0">
                    
                    <!-- Detail Header Section -->
                    <div class="p-4 lg:p-4 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center flex-shrink-0 bg-slate-50/40 dark:bg-slate-900/10 rounded-t-2xl">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm lg:text-base font-extrabold text-slate-800 dark:text-white" x-text="selectedRole.display_name"></h3>
                                <span class="text-[9px] bg-slate-100 dark:bg-slate-700/60 text-slate-500 dark:text-slate-400 font-mono px-1.5 py-0.5 rounded tracking-wide uppercase" x-text="selectedRole.name"></span>
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Danh sách phân bổ quyền của vai trò này</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a :href="'<?= url('/admin/roles/edit?id=') ?>' + selectedRoleId"
                               class="inline-flex items-center px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-100 dark:shadow-none hover:-translate-y-0.5 transition duration-150">
                                <i class="fas fa-edit mr-1.5 text-[10px]"></i> Thiết lập quyền
                            </a>

                            <!-- Delete Role Option (Disabled for Super Admin ID 1) -->
                            <template x-if="selectedRole.id != 1">
                                <form action="<?= url('/admin/roles/delete') ?>" method="POST" @submit="if(!confirm('Bạn có chắc chắn muốn xóa vai trò này không?')) $event.preventDefault()">
                                    <input type="hidden" name="csrf_token" value="<?= (string) $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="id" :value="selectedRole.id">
                                    <button type="submit" 
                                            class="inline-flex items-center px-3 py-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-900/40 text-rose-600 dark:text-rose-400 font-bold text-xs rounded-xl border border-rose-200 dark:border-rose-900/40 transition duration-150">
                                        <i class="fas fa-trash-alt mr-1.5 text-[10px]"></i> Xóa
                                    </button>
                                </form>
                            </template>
                        </div>
                    </div>

                    <!-- Scrollable Permissions Matrix Container -->
                    <div class="flex-grow flex flex-col min-h-0 overflow-hidden">
                        
                        <!-- Case: Super Admin (Has 'all' permission) -->
                        <template x-if="selectedPermissions.includes('all')">
                            <div class="flex-grow overflow-y-auto p-5 custom-scrollbar">
                                <div class="p-6 rounded-2xl bg-emerald-50/30 dark:bg-emerald-950/10 border border-emerald-100/80 dark:border-emerald-900/40 flex items-start max-w-xl mx-auto mt-10 shadow-sm">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0 text-emerald-600 dark:text-emerald-400">
                                        <i class="fas fa-shield-alt text-lg"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="font-bold text-xs text-emerald-800 dark:text-emerald-400 uppercase tracking-wider">Toàn quyền hệ thống (Super Admin)</h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400/80 mt-1.5 leading-relaxed">
                                            Tài khoản sở hữu vai trò này có đặc quyền tối cao trên toàn hệ thống, cho phép thực hiện mọi thao tác cấu hình hệ thống, quản trị phân quyền, xử lý tất cả hồ sơ thí sinh mà không gặp bất kỳ giới hạn nào.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Case: Regular Role (Tabbed display of permissions) -->
                        <template x-if="!selectedPermissions.includes('all')">
                            <div class="flex-grow flex flex-col min-h-0">
                                
                                <!-- Permission Tabs Navigation bar -->
                                <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/30 dark:bg-slate-900/5 flex-shrink-0">
                                    <div class="flex gap-2 overflow-x-auto no-scrollbar py-1">
                                        <template x-for="[groupName, perms] in Object.entries(allPermissions)" :key="groupName">
                                            <button type="button" @click="activeTab = groupName"
                                                    class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-xl transition duration-150 flex-shrink-0 border"
                                                    :class="activeTab === groupName 
                                                        ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-100/10 dark:shadow-none' 
                                                        : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-600 hover:text-slate-700 dark:hover:text-slate-300'">
                                                <i class="fas fa-folder text-[9px] mr-1.5 opacity-70"></i>
                                                <span x-text="groupName"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <!-- Current Tab Permissions Grid (Scrollable) -->
                                <div class="flex-grow overflow-y-auto p-4 custom-scrollbar">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <template x-for="[groupName, perms] in Object.entries(allPermissions)" :key="groupName">
                                            <template x-if="activeTab === groupName">
                                                <template x-for="[key, label] in Object.entries(perms)" :key="key">
                                                    <div class="flex items-center p-3 rounded-xl border transition-all duration-150"
                                                         :class="hasPermission(key) 
                                                            ? 'bg-emerald-50/20 dark:bg-emerald-950/5 border-emerald-100 dark:border-emerald-900/30' 
                                                            : 'bg-slate-50/40 dark:bg-slate-900/10 border-slate-100 dark:border-slate-800'">
                                                        <template x-if="hasPermission(key)">
                                                            <div class="flex items-center text-xs text-slate-700 dark:text-slate-300 font-semibold w-full">
                                                                <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0 mr-2.5">
                                                                    <i class="fas fa-check text-[9px]"></i>
                                                                </span>
                                                                <span class="min-w-0 flex-1 truncate" x-text="label"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!hasPermission(key)">
                                                            <div class="flex items-center text-xs text-slate-400 dark:text-slate-600 w-full font-medium">
                                                                <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-300 dark:text-slate-700 flex-shrink-0 mr-2.5">
                                                                    <i class="fas fa-times text-[9px]"></i>
                                                                </span>
                                                                <span class="min-w-0 flex-1 truncate line-through decoration-slate-200 dark:decoration-slate-800/80" x-text="label"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </template>
                                        </template>
                                    </div>
                                </div>
                                
                            </div>
                        </template>

                    </div>
                </div>
            </template>
            
            <!-- Empty state when no role is active -->
            <template x-if="!selectedRole">
                <div class="flex-grow flex flex-col justify-center items-center text-slate-400 dark:text-slate-500 py-20 animate-pulse">
                    <i class="fas fa-shield-alt text-3xl mb-3 text-slate-300 dark:text-slate-600"></i>
                    <p class="text-xs font-bold uppercase tracking-wider">Vui lòng chọn một vai trò ở danh sách bên trái</p>
                </div>
            </template>
            
        </div>
        
    </div>

    <!-- Modal: Create New Role -->
    <div x-show="showCreateModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
        
        <div @click.away="showCreateModal = false"
             class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-2xl max-w-3xl w-full flex flex-col max-h-[90vh] overflow-hidden animate-scaleIn">
            
            <!-- Modal Header -->
            <div class="p-4 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/20">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-blue-600/10 text-blue-600 rounded-xl dark:bg-blue-500/20 dark:text-blue-400">
                        <i class="fas fa-plus-circle text-base"></i>
                    </span>
                    <h3 class="text-base font-extrabold text-slate-800 dark:text-white uppercase tracking-tight">Thêm Vai trò & Phân quyền Mới</h3>
                </div>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form action="<?= url('/admin/roles/store') ?>" method="POST" class="flex flex-col flex-grow min-h-0">
                <input type="hidden" name="csrf_token" value="<?= (string) $_SESSION['csrf_token'] ?>">

                <!-- Modal Body Scrollable -->
                <div class="p-5 space-y-4 flex-grow overflow-y-auto custom-scrollbar">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Tên hiển thị -->
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Tên Vai Trò (Tên Hiển Thị) *</label>
                            <input type="text" name="display_name" placeholder="Ví dụ: Nhập học, Cán bộ Tài chính..." required
                                   class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-bold focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 dark:text-white">
                        </div>

                        <!-- Mã vai trò -->
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Mã Hệ Thống (Mã Key tiếng Anh) - Tùy chọn</label>
                            <input type="text" name="name" placeholder="Ví dụ: enrollment_officer, finance_staff..." 
                                   class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-mono font-semibold focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 dark:text-white">
                            <span class="text-[9px] text-slate-400 dark:text-slate-500 mt-1 block">Để trống hệ thống sẽ tự sinh theo tên hiển thị.</span>
                        </div>
                    </div>

                    <!-- Permission Selection Section -->
                    <div class="pt-2">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Chọn các quyền hạn gán cho vai trò này</span>
                            <button type="button" @click="newRolePermissions = []" class="text-[9px] font-bold text-rose-500 hover:underline">Xóa tất cả quyền</button>
                        </div>

                        <!-- Permission Tabs Navigation -->
                        <div class="px-3 py-2 border border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/30 rounded-xl mb-3">
                            <div class="flex gap-2 overflow-x-auto no-scrollbar py-0.5">
                                <template x-for="[groupName, perms] in Object.entries(allPermissions)" :key="groupName">
                                    <button type="button" @click="modalActiveTab = groupName"
                                            class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg transition duration-150 flex-shrink-0 border"
                                            :class="modalActiveTab === groupName 
                                                ? 'bg-blue-600 border-blue-600 text-white shadow-sm' 
                                                : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'">
                                        <span x-text="groupName"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Permission Grid Checklist -->
                        <template x-for="[groupName, perms] in Object.entries(allPermissions)" :key="groupName">
                            <template x-if="modalActiveTab === groupName">
                                <div>
                                    <div class="flex justify-between items-center mb-2 px-1">
                                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider" x-text="groupName"></span>
                                        <label class="flex items-center gap-1.5 cursor-pointer text-[10px] font-bold text-blue-600 dark:text-blue-400">
                                            <input type="checkbox" 
                                                   :checked="isModalGroupChecked(Object.keys(perms))"
                                                   @change="toggleModalGroup(Object.keys(perms), $event)"
                                                   class="w-3.5 h-3.5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                            <span>Chọn tất cả quyền nhóm này</span>
                                        </label>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 max-h-56 overflow-y-auto p-1 custom-scrollbar">
                                        <template x-for="[key, label] in Object.entries(perms)" :key="key">
                                            <label class="flex items-center p-2.5 rounded-xl border cursor-pointer select-none transition-all duration-150"
                                                   :class="newRolePermissions.includes(key) 
                                                      ? 'bg-blue-50/50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800' 
                                                      : 'bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700/60'">
                                                <input type="checkbox" name="permissions[]" :value="key"
                                                       x-model="newRolePermissions"
                                                       class="w-4 h-4 text-blue-600 border-slate-300 dark:border-slate-700 rounded focus:ring-blue-500">
                                                <span class="ml-2.5 text-xs font-bold text-slate-700 dark:text-slate-300" x-text="label"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </template>

                    </div>

                </div>

                <!-- Modal Footer Actions -->
                <div class="p-4 border-t border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/20 flex justify-end gap-3 flex-shrink-0">
                    <button type="button" @click="showCreateModal = false"
                            class="px-4 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition-colors uppercase tracking-wider">
                        Hủy
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-check text-xs"></i> Tạo vai trò
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
