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
        padding: 1.5rem !important;
        height: calc(100vh - 4rem) !important; /* Header is 4rem (64px) */
        overflow: hidden !important;
        display: flex;
        flex-direction: column;
    }

    /* Custom ultra-thin scrollbars */
    .custom-scrollbar::-webkit-scrollbar {
        width: 5px;
        height: 5px;
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
</style>

<div x-data="{
    selectedRoleId: <?= $roles[0]['id'] ?? 0 ?>,
    roles: <?= htmlspecialchars(json_encode($roles), ENT_QUOTES, 'UTF-8') ?>,
    allPermissions: <?= htmlspecialchars(json_encode($allPermissions), ENT_QUOTES, 'UTF-8') ?>,
    searchQuery: '',
    
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
    }
}" class="flex-grow flex flex-col min-h-0">

    <!-- Compact Header -->
    <header class="mb-4 flex justify-between items-center flex-shrink-0">
        <div>
            <h2 class="text-xl lg:text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight font-heading">Quản lý Vai trò</h2>
            <p class="text-xs text-slate-400 dark:text-slate-500">Phân bổ quyền hạn truy cập hệ thống cho các nhóm người dùng</p>
        </div>
    </header>

    <!-- Success Message Banner -->
    <?php if (!empty($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
        <div class="mb-4 p-3 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 rounded-xl font-bold border border-emerald-100 dark:border-emerald-900/30 flex items-center text-xs flex-shrink-0">
            <i class="fas fa-check-circle mr-2 text-base"></i> Đã cập nhật vai trò và lưu thay đổi thành công!
        </div>
    <?php endif; ?>

    <!-- Main Workspace Split Pane -->
    <div class="flex-grow grid grid-cols-1 lg:grid-cols-12 gap-5 min-h-0">
        
        <!-- Left Side: Role Card List (4 cols) -->
        <div class="lg:col-span-4 flex flex-col min-h-0 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/50 rounded-2xl shadow-sm">
            <div class="p-4 border-b border-slate-100 dark:border-slate-700/50 flex-shrink-0">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-3">Danh sách vai trò</span>
                
                <!-- Instant Search Box -->
                <div class="relative">
                    <input type="text" x-model="searchQuery" placeholder="Tìm kiếm vai trò..." 
                           class="w-full pl-9 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors dark:text-white dark:placeholder-slate-500">
                    <i class="fas fa-search absolute left-3.5 top-3.5 text-slate-400 dark:text-slate-500 text-[11px]"></i>
                </div>
            </div>
            
            <!-- Roles Card List Scroll Container -->
            <div class="flex-grow overflow-y-auto p-4 custom-scrollbar">
                <template x-for="role in filteredRoles" :key="role.id">
                    <div @click="selectedRoleId = role.id"
                         class="p-4 rounded-xl border cursor-pointer transition-all duration-200 group flex flex-col mb-3"
                         :class="selectedRoleId == role.id 
                            ? 'bg-blue-50/40 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800/80 shadow-sm shadow-blue-100/10' 
                            : 'bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-700/30 hover:border-slate-200 dark:hover:border-slate-700'">
                        <div class="flex justify-between items-start">
                            <div class="min-w-0 flex-1">
                                <h4 class="font-bold text-xs transition-colors"
                                    :class="selectedRoleId == role.id ? 'text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-slate-100'"
                                    x-text="role.display_name"></h4>
                                <span class="text-[9px] text-slate-400 dark:text-slate-500 font-mono mt-0.5 block" x-text="role.name"></span>
                            </div>
                            <span class="text-[9px] px-2 py-0.5 rounded-full font-bold ml-2 flex-shrink-0"
                                  :class="selectedRoleId == role.id ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400'"
                                  x-text="getPermissionCount(role)"></span>
                        </div>
                    </div>
                </template>
                
                <!-- Empty State -->
                <template x-if="filteredRoles.length === 0">
                    <div class="py-10 text-center text-slate-400 dark:text-slate-500">
                        <i class="fas fa-user-shield text-xl mb-2"></i>
                        <p class="text-xs">Không tìm thấy vai trò phù hợp</p>
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
                    <div class="p-4 lg:p-5 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center flex-shrink-0 bg-slate-50/40 dark:bg-slate-900/10 rounded-t-2xl">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-extrabold text-slate-800 dark:text-white" x-text="selectedRole.display_name"></h3>
                                <span class="text-[9px] bg-slate-100 dark:bg-slate-700/60 text-slate-500 dark:text-slate-400 font-mono px-1.5 py-0.5 rounded" x-text="selectedRole.name"></span>
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Quyền hạn thực tế của nhóm vai trò này</p>
                        </div>
                        <a :href="'<?= url('/admin/roles/edit?id=') ?>' + selectedRoleId"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-100 dark:shadow-none hover:-translate-y-0.5 transition duration-150">
                            <i class="fas fa-edit mr-1.5"></i> Thiết lập quyền
                        </a>
                    </div>

                    <!-- Scrollable Permissions Matrix -->
                    <div class="flex-grow overflow-y-auto p-5 space-y-4 custom-scrollbar">
                        
                        <!-- Case: Super Admin (Has 'all' permission) -->
                        <template x-if="selectedPermissions.includes('all')">
                            <div class="p-5 rounded-2xl bg-emerald-50/30 dark:bg-emerald-950/10 border border-emerald-100/80 dark:border-emerald-900/40 flex items-start">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0 text-emerald-600 dark:text-emerald-400">
                                    <i class="fas fa-shield-alt text-base"></i>
                                </div>
                                <div class="ml-3.5">
                                    <h4 class="font-bold text-xs text-emerald-800 dark:text-emerald-400 uppercase tracking-wider">Toàn quyền hệ thống (Super Admin)</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400/80 mt-1 leading-relaxed">Tài khoản thuộc vai trò này có toàn bộ đặc quyền trên hệ thống, cho phép thực hiện mọi thao tác quản trị, cấu hình, xử lý hồ sơ thí sinh và thay đổi cài đặt hệ thống.</p>
                                </div>
                            </div>
                        </template>

                        <!-- Case: Regular Role (List grouped permissions) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="!selectedPermissions.includes('all')">
                            <template x-for="[groupName, perms] in Object.entries(allPermissions)" :key="groupName">
                                <div class="bg-slate-50/30 dark:bg-slate-900/10 border border-slate-100 dark:border-slate-800 rounded-2xl p-4 flex flex-col min-h-0">
                                    <h4 class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest flex items-center mb-3 flex-shrink-0">
                                        <i class="fas fa-folder-open mr-2 text-[11px]"></i>
                                        <span x-text="groupName"></span>
                                    </h4>
                                    <div class="flex-grow overflow-y-auto custom-scrollbar">
                                        <template x-for="[key, label] in Object.entries(perms)" :key="key">
                                            <div class="flex items-center text-xs mb-2.5">
                                                <template x-if="hasPermission(key)">
                                                    <div class="flex items-center text-slate-700 dark:text-slate-300 font-medium">
                                                        <i class="fas fa-check-circle text-emerald-500 mr-2 text-sm"></i>
                                                        <span x-text="label"></span>
                                                    </div>
                                                </template>
                                                <template x-if="!hasPermission(key)">
                                                    <div class="flex items-center text-slate-400 dark:text-slate-600 line-through decoration-slate-200 dark:decoration-slate-800">
                                                        <i class="far fa-circle text-slate-300 dark:text-slate-700 mr-2 text-sm"></i>
                                                        <span x-text="label"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                    </div>
                    
                </div>
            </template>
            
            <!-- Empty state when no role is active -->
            <template x-if="!selectedRole">
                <div class="flex-grow flex flex-col justify-center items-center text-slate-400 dark:text-slate-500 py-20">
                    <i class="fas fa-shield-alt text-3xl mb-3"></i>
                    <p class="text-sm">Vui lòng chọn một vai trò ở danh sách bên trái</p>
                </div>
            </template>
            
        </div>
        
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
