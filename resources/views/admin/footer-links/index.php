<?php $title = 'Quản lý Footer - Admin'; ?>

<div class="max-w-4xl mx-auto">
    <header class="mb-6 flex justify-between items-center">
        <div>
            <a href="<?= url('/admin/dashboard') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition inline-flex items-center mb-2">
                <i class="fas fa-arrow-left mr-2"></i> Dashboard
            </a>
            <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Quản lý liên kết Footer</h2>
            <p class="text-sm text-gray-500 mt-1">Chỉnh sửa mục "Hỗ trợ thí sinh" hiển thị ở footer trang web</p>
        </div>
    </header>

    <?php if (!empty($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
        <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl font-bold border border-green-100 flex items-center text-sm">
            <i class="fas fa-check-circle mr-2"></i> Đã lưu thay đổi thành công!
        </div>
    <?php endif; ?>

    <form action="<?= url('/admin/footer-links/save') ?>" method="POST" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-[#0066FF] to-blue-700 px-6 py-4">
            <h3 class="text-white font-bold flex items-center">
                <i class="fas fa-link mr-2"></i> Hỗ trợ thí sinh - Footer Links
            </h3>
        </div>
        
        <div class="p-6">
            <div id="links-container" class="space-y-3">
                <?php foreach ($links as $i => $link): ?>
                <div class="link-row flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200 group hover:border-blue-300 transition">
                    <div class="flex-shrink-0 cursor-move text-gray-300 hover:text-gray-500">
                        <i class="fas fa-grip-vertical"></i>
                    </div>
                    <div class="flex-1 grid grid-cols-12 gap-3">
                        <div class="col-span-4">
                            <input type="text" name="label[]" value="<?= htmlspecialchars($link['label']) ?>" 
                                   placeholder="Tên hiển thị" 
                                   class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                        </div>
                        <div class="col-span-5">
                            <input type="text" name="url[]" value="<?= htmlspecialchars($link['url']) ?>" 
                                   placeholder="https://example.com hoặc /path" 
                                   class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                        </div>
                        <div class="col-span-2">
                            <input type="text" name="icon[]" value="<?= htmlspecialchars($link['icon'] ?? 'fas fa-check-circle') ?>" 
                                   placeholder="fas fa-icon" 
                                   class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                        </div>
                        <div class="col-span-1 flex items-center justify-center">
                            <button type="button" onclick="removeLink(this)" class="text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg p-2 transition">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" onclick="addLink()" class="mt-4 px-4 py-2.5 bg-blue-50 text-blue-600 font-bold text-sm rounded-xl border-2 border-dashed border-blue-200 hover:bg-blue-100 hover:border-blue-400 transition w-full flex items-center justify-center">
                <i class="fas fa-plus mr-2"></i> Thêm liên kết mới
            </button>

            <!-- Header labels -->
            <div class="mt-4 grid grid-cols-12 gap-3 px-9 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                <div class="col-span-4">Nhãn</div>
                <div class="col-span-5">URL</div>
                <div class="col-span-2">Icon (Font Awesome)</div>
                <div class="col-span-1"></div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
            <p class="text-xs text-gray-400">
                <i class="fas fa-info-circle mr-1"></i> Icon sử dụng class Font Awesome, ví dụ: <code class="bg-gray-200 px-1 rounded">fas fa-check-circle</code>
            </p>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white font-bold uppercase text-sm tracking-wider rounded-xl shadow hover:bg-emerald-700 hover:shadow-lg transition-all">
                <i class="fas fa-save mr-2"></i> Lưu thay đổi
            </button>
        </div>
    </form>
</div>

<script>
function addLink() {
    const container = document.getElementById('links-container');
    const row = document.createElement('div');
    row.className = 'link-row flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200 group hover:border-blue-300 transition';
    row.innerHTML = `
        <div class="flex-shrink-0 cursor-move text-gray-300 hover:text-gray-500">
            <i class="fas fa-grip-vertical"></i>
        </div>
        <div class="flex-1 grid grid-cols-12 gap-3">
            <div class="col-span-4">
                <input type="text" name="label[]" placeholder="Tên hiển thị" 
                       class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>
            <div class="col-span-5">
                <input type="text" name="url[]" placeholder="https://example.com hoặc /path" 
                       class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>
            <div class="col-span-2">
                <input type="text" name="icon[]" value="fas fa-check-circle" placeholder="fas fa-icon" 
                       class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>
            <div class="col-span-1 flex items-center justify-center">
                <button type="button" onclick="removeLink(this)" class="text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg p-2 transition">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(row);
    row.querySelector('input').focus();
}

function removeLink(btn) {
    const row = btn.closest('.link-row');
    row.style.transition = 'all 0.3s';
    row.style.opacity = '0';
    row.style.transform = 'translateX(20px)';
    setTimeout(() => row.remove(), 300);
}
</script>
