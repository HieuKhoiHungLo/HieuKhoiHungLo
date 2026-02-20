<?php $title = 'Nhập điểm Năng khiếu Ngoại - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-2xl mx-auto p-8">
    <header class="mb-8">
        <a href="<?= url('/admin/dashboard') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition block mb-2">&larr; Quay lại Dashboard</a>
        <h2 class="text-3xl font-black text-gray-900 uppercase">Nhập điểm Năng khiếu (Ngoài trường)</h2>
        <p class="text-gray-500 mt-2">Nhập điểm năng khiếu từ các trường khác hoặc tổ chức khác cho thí sinh.</p>
    </header>

    <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
        <?php if (!empty($_GET['msg'])): ?>
            <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-xl font-bold border border-green-100 flex items-center">
                <i class="fas fa-check-circle mr-2 text-xl"></i>
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($_GET['error'])): ?>
            <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl font-bold border border-red-100 flex items-center">
                <i class="fas fa-exclamation-circle mr-2 text-xl"></i>
                Lỗi: <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <form action="<?= url('/admin/aptitude-scores/import') ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <div class="text-center mb-8">
                <a href="<?= url('/admin/aptitude-scores/template') ?>" class="inline-flex items-center text-[#0066FF] hover:text-blue-800 font-bold transition">
                    <span class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center mr-2">
                        <i class="fas fa-file-download"></i>
                    </span>
                    Tải file mẫu CSV
                </a>
            </div>

            <div class="border-3 border-dashed border-gray-200 rounded-2xl p-10 text-center hover:bg-gray-50 transition-colors relative group cursor-pointer">
                <input type="file" name="file" required accept=".csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                <div class="transform group-hover:scale-110 transition duration-300">
                    <i class="fas fa-cloud-upload-alt text-5xl text-gray-300 group-hover:text-[#0066FF] mb-4"></i>
                </div>
                <h4 class="text-lg font-bold text-gray-700">Kéo thả file CSV vào đây</h4>
                <p class="text-sm text-gray-400 mt-1">hoặc click để chọn file từ máy tính</p>
            </div>

            <button type="submit" class="w-full py-4 bg-gradient-to-r from-[#0066FF] to-blue-600 text-white font-black uppercase tracking-wider rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition duration-300">
                <i class="fas fa-upload mr-2"></i> Tiến hành Nhập liệu
            </button>

        </form>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
