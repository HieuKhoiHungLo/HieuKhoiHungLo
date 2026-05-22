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

<!-- Form wraps the entire layout to ensure grid container is a standard div (resolving flex-shrink issues in forms) -->
<form action="<?= url('/admin/roles/edit') ?>" method="POST" x-data="{
    checkedPermissions: <?= htmlspecialchars(json_encode($rolePermissions), ENT_QUOTES, 'UTF-8') ?>,
    
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
}" class="flex-grow flex flex-col min-h-0">
    <input type="hidden" name="csrf_token" value="<?= (string) $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="id" value="<?= (string) $role['id'] ?>">

    <!-- Compact Header -->
    <header class="mb-4 flex justify-between items-center flex-shrink-0">
        <div>
            <h2 class="text-xl lg:text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight font-heading">Chỉnh sửa Vai trò</h2>
            <p class="text-xs text-slate-400 dark:text-slate-500">Phân bổ chi tiết quyền hạn truy cập chức năng cho nhóm người dùng</p>
        </div>
        <a href="<?= url('/admin/roles') ?>" class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition-colors">
            <i class="fas fa-arrow-left mr-1.5"></i> Quay lại
        </a>
    </header>

    <!-- Error Alert Section -->
    <?php if (!empty($_GET['error'])): ?>
        <div class="mb-4 p-3 bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-400 rounded-xl font-bold border border-rose-100 dark:border-rose-900/30 flex items-center text-xs flex-shrink-0">
            <i class="fas fa-exclamation-circle mr-2 text-base"></i> <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Main Workspace Split Pane (div wrapper for perfect height limits) -->
    <div class="flex-grow grid grid-cols-1 lg:grid-cols-12 gap-5 min-h-0">

        <!-- Left Side: Role Identity Form (4 cols) -->
        <div class="lg:col-span-4 flex flex-col min-h-0 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/50 rounded-2xl shadow-sm">
            <div class="p-4 border-b border-slate-100 dark:border-slate-700/50 flex-shrink-0 bg-slate-50/50 dark:bg-slate-900/10 rounded-t-2xl">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Thông tin Vai trò</span>
            </div>
            
            <div class="flex-grow p-5 space-y-5 overflow-y-auto custom-scrollbar">
                
                <!-- Display Name input -->
                <div class="space-y-1.5">
                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tên hiển thị</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($role['display_name']) ?>" required
                           class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-bold focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 dark:text-slate-100">
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
                        <i class="fas fa-exclamation-triangle mr-1"></i> Đây là vai trò quản trị tối cao. Tài khoản sở hữu vai trò này sẽ luôn thừa hưởng tất cả quyền hạn bất kể cấu hình bên phải.
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
            <div class="p-4 border-b border-slate-100 dark:border-slate-700/50 flex-shrink-0 bg-slate-50/50 dark:bg-slate-900/10 rounded-t-2xl flex justify-between items-center">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Phân bổ đặc quyền</span>
                
                <!-- Quick Global Reset -->
                <div class="flex gap-3">
                    <button type="button" @click="checkedPermissions = []" 
                            class="text-[9px] font-bold text-slate-400 hover:text-rose-600 transition-colors uppercase tracking-wider">
                        Xóa tất cả
                    </button>
                </div>
            </div>

            <!-- Scrollable Permissions Selection Grid -->
            <div class="flex-grow overflow-y-auto p-5 space-y-6 custom-scrollbar">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($allPermissions as $group => $perms): ?>
                        <div class="bg-slate-50/30 dark:bg-slate-900/10 border border-slate-100 dark:border-slate-800 rounded-2xl p-4 flex flex-col min-h-0">
                            
                            <!-- Group Title & Select All -->
                            <div class="flex justify-between items-center mb-3 flex-shrink-0">
                                <h4 class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-folder-open mr-2 text-[11px]"></i>
                                    <span><?= $group ?></span>
                                </h4>
                                
                                <label class="flex items-center gap-1.5 cursor-pointer text-[10px] font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
                                    <input type="checkbox" 
                                           :checked="isGroupChecked(<?= htmlspecialchars(json_encode(array_keys($perms)), ENT_QUOTES, 'UTF-8') ?>)"
                                           @change="toggleGroup(<?= htmlspecialchars(json_encode(array_keys($perms)), ENT_QUOTES, 'UTF-8') ?>, $event)"
                                           class="w-3.5 h-3.5 text-blue-600 border-slate-300 dark:border-slate-700 rounded focus:ring-blue-500 dark:bg-slate-900">
                                    <span>Tất cả</span>
                                </label>
                            </div>

                            <!-- List of Individual Permissions Checkboxes -->
                            <div class="space-y-1.5 flex-grow overflow-y-auto custom-scrollbar">
                                <?php foreach ($perms as $key => $label): ?>
                                    <label class="flex items-center p-2.5 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-xl hover:border-blue-200 dark:hover:border-blue-800 cursor-pointer transition duration-150">
                                        <input type="checkbox" name="permissions[]" value="<?= $key ?>"
                                               x-model="checkedPermissions"
                                               class="w-4 h-4 text-blue-600 border-slate-300 dark:border-slate-700 rounded focus:ring-blue-500 dark:bg-slate-900">
                                        <span class="ml-3 text-xs font-semibold text-slate-600 dark:text-slate-300"><?= htmlspecialchars($label) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>

    </div>
</form>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>