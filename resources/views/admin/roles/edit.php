<?php $title = 'Chỉnh sửa Vai trò - Admin'; ?>
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

<!-- Form wraps the entire layout with display:contents to bypass form-specific browser layout bugs -->
<form action="<?= url('/admin/roles/edit') ?>" method="POST" style="display: contents;">
    <div x-data="{
        checkedPermissions: <?= htmlspecialchars(json_encode($rolePermissions), ENT_QUOTES, 'UTF-8') ?>,
        allPermissions: <?= htmlspecialchars(json_encode($allPermissions), ENT_QUOTES, 'UTF-8') ?>,
        activeTab: '',
        
        init() {
            const keys = Object.keys(this.allPermissions);
            if (keys.length > 0) {
                this.activeTab = keys[0];
            }
        },
        toggleGroup(keys, event) {
            const isChecked = event.target.checked;
            if (isChecked) {
                keys.forEach(k => {
                    if (!this.checkedPermissions.includes(k)) {
                        this.checkedPermissions.push(k);
                    }
                });
            } else {
                this.checkedPermissions = this.checkedPermissions.filter(k => !keys.includes(k));
            }
        },
        isGroupChecked(keys) {
            return keys.every(k => this.checkedPermissions.includes(k));
        }
    }" class="flex-grow flex flex-col min-h-0 w-full animate-fadeIn">
        
        <input type="hidden" name="csrf_token" value="<?= (string) $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="id" value="<?= (string) $role['id'] ?>">

        <!-- Compact Header -->
        <header class="mb-3.5 flex justify-between items-center flex-shrink-0">
            <div>
                <h2 class="text-xl lg:text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight font-heading flex items-center gap-2">
                    <span class="p-1.5 bg-blue-600/10 text-blue-600 rounded-xl dark:bg-blue-500/20 dark:text-blue-400">
                        <i class="fas fa-edit text-lg"></i>
                    </span>
                    Chỉnh sửa Vai trò
                </h2>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Phân bổ chi tiết quyền hạn truy cập chức năng cho nhóm người dùng</p>
            </div>
            <a href="<?= url('/admin/roles') ?>" class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition-colors">
                <i class="fas fa-arrow-left mr-1.5"></i> Quay lại
            </a>
        </header>

        <!-- Error Alert Section -->
        <?php if (!empty($_GET['error'])): ?>
            <div class="mb-3 p-3 bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-400 rounded-xl font-bold border border-rose-100 dark:border-rose-900/30 flex items-center text-xs flex-shrink-0">
                <i class="fas fa-exclamation-circle mr-2 text-base text-rose-500"></i> <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <!-- Main Workspace Split Pane -->
        <div class="flex-grow grid grid-cols-1 lg:grid-cols-12 gap-4 min-h-0">

            <!-- Left Side: Role Identity Form (4 cols) -->
            <div class="lg:col-span-4 flex flex-col min-h-0 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/50 rounded-2xl shadow-sm">
                <div class="p-4 border-b border-slate-100 dark:border-slate-700/50 flex-shrink-0 bg-slate-50/50 dark:bg-slate-900/10 rounded-t-2xl">
                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block">Thông tin Vai trò</span>
                </div>
                
                <div class="flex-grow p-4 space-y-4 overflow-y-auto custom-scrollbar">
                    
                    <!-- Display Name input -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tên hiển thị</label>
                        <input type="text" name="display_name" value="<?= htmlspecialchars($role['display_name']) ?>" required
                               class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-bold focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 dark:text-slate-100 transition-colors">
                    </div>

                    <!-- Code name input (system key) -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Mã Vai trò (System Key)</label>
                        <input type="text" value="<?= htmlspecialchars($role['name']) ?>" disabled
                               class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-mono text-slate-700 dark:text-slate-300 font-semibold cursor-not-allowed">
                    </div>

                    <!-- Special Warning for Super Admin -->
                    <?php if ($role['name'] === 'super_admin' || $role['id'] == 1): ?>
                        <div class="p-4 rounded-xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-100/50 dark:border-amber-900/30 text-[11px] text-amber-700 dark:text-amber-400 leading-relaxed font-semibold">
                            <i class="fas fa-exclamation-triangle mr-1 text-amber-500"></i> Đây là vai trò quản trị tối cao. Tài khoản sở hữu vai trò này sẽ luôn thừa hưởng tất cả quyền hạn bất kể cấu hình bên phải.
                        </div>
                    <?php endif; ?>

                </div>

                <!-- Sticky Bottom Actions -->
                <div class="p-4 border-t border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/10 rounded-b-2xl flex-shrink-0">
                    <button type="submit" class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-100 dark:shadow-none hover:-translate-y-0.5 transition duration-150 text-xs uppercase tracking-wider flex items-center justify-center gap-1.5">
                        <i class="fas fa-save text-sm"></i> Lưu thay đổi
                    </button>
                </div>
            </div>

            <!-- Right Side: Interactive Permission Mapping Tree (8 cols) -->
            <div class="lg:col-span-8 flex flex-col min-h-0 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/50 rounded-2xl shadow-sm">
                
                <!-- Dynamic Header with global controls -->
                <div class="p-4 border-b border-slate-100 dark:border-slate-700/50 flex-shrink-0 bg-slate-50/50 dark:bg-slate-900/10 rounded-t-2xl flex justify-between items-center">
                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Phân bổ đặc quyền</span>
                    
                    <!-- Quick Global Reset -->
                    <button type="button" @click="checkedPermissions = []" 
                            class="text-[9px] font-bold text-slate-400 hover:text-rose-600 transition-colors uppercase tracking-wider flex items-center gap-1">
                        <i class="fas fa-trash-alt"></i> Xóa tất cả
                    </button>
                </div>

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

                <!-- Interactive Permissions Mapping Grid -->
                <div class="flex-grow flex flex-col min-h-0 overflow-hidden">
                    
                    <template x-for="[groupName, perms] in Object.entries(allPermissions)" :key="groupName">
                        <template x-if="activeTab === groupName">
                            <div class="flex-grow flex flex-col min-h-0">
                                
                                <!-- Tab Subheader: Select All within Tab -->
                                <div class="px-4 py-2 bg-slate-50/30 dark:bg-slate-900/15 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center flex-shrink-0">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider flex items-center">
                                        <i class="fas fa-chevron-right mr-1.5 text-[8px] text-blue-500"></i>
                                        Danh sách quyền nhóm <span class="text-slate-600 dark:text-slate-300 ml-1" x-text="groupName"></span>
                                    </span>
                                    
                                    <label class="flex items-center gap-1.5 cursor-pointer text-[10px] font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
                                        <input type="checkbox" 
                                               :checked="isGroupChecked(Object.keys(perms))"
                                               @change="toggleGroup(Object.keys(perms), $event)"
                                               class="w-3.5 h-3.5 text-blue-600 border-slate-300 dark:border-slate-700 rounded focus:ring-blue-500 dark:bg-slate-900">
                                        <span>Chọn tất cả nhóm</span>
                                    </label>
                                </div>

                                <!-- Checklist Grid of Toggles (Scrollable) -->
                                <div class="flex-grow overflow-y-auto p-4 custom-scrollbar">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <template x-for="[key, label] in Object.entries(perms)" :key="key">
                                            <label class="flex items-center p-3.5 rounded-xl border cursor-pointer transition-all duration-150 select-none"
                                                   :class="checkedPermissions.includes(key) 
                                                      ? 'bg-blue-50/30 dark:bg-blue-950/15 border-blue-200 dark:border-blue-800' 
                                                      : 'bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700/60 hover:bg-slate-50 dark:hover:bg-slate-700/30 hover:border-slate-200 dark:hover:border-slate-600'">
                                                <input type="checkbox" name="permissions[]" :value="key"
                                                       x-model="checkedPermissions"
                                                       class="w-4 h-4 text-blue-600 border-slate-300 dark:border-slate-700 rounded focus:ring-blue-500 dark:bg-slate-900">
                                                <span class="ml-3 text-xs font-bold text-slate-600 dark:text-slate-300" x-text="label"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </template>

                </div>
            </div>

        </div>
    </div>
</form>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>