<?php $title = 'Import Danh sách Gửi thư'; ?>
<?php ob_start(); ?>

<div class="max-w-4xl mx-auto">
    <header class="mb-6">
        <a href="<?= url('/admin/admission-letters') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition inline-flex items-center mb-2">
            <i class="fas fa-arrow-left mr-2"></i> Thư báo Trúng Tuyển
        </a>
        <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Import Danh Sách</h2>
    </header>

    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
            <i class="fas fa-exclamation-triangle mr-2 text-xl"></i>
            <div>
                <strong>Lỗi Import!</strong> <?= htmlspecialchars($_GET['error']) ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8">
            <form action="<?= url('/admin/admission-letters/import') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
                
                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Tên Đợt Gửi (Tùy chọn)</label>
                    <input type="text" name="batch_id" 
                           class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" 
                           placeholder="Ví dụ: Đợt 1 - 2025" value="Đợt <?= date('d/m/Y') ?>">
                    <p class="text-xs text-slate-500 mt-1">Hệ thống dùng tên này để bạn lọc danh sách thí sinh sau khi import.</p>
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2">File Danh Sách (Excel) <span class="text-red-500">*</span></label>
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-10 text-center hover:bg-slate-50 transition cursor-pointer" id="dropzone" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-file-excel text-4xl text-green-500 mb-3 block"></i>
                        <h4 class="font-bold text-slate-700 mb-1">Click để tải lên hoặc Kéo thả file Excel vào đây</h4>
                        <p class="text-xs text-slate-500">Định dạng hỗ trợ: .xlsx, .xls</p>
                        <input type="file" name="file" id="fileInput" accept=".xlsx,.xls" class="hidden" required onchange="showFileName(this)">
                        <div id="fileName" class="mt-4 font-bold text-blue-600 hidden bg-blue-50 py-2 px-4 rounded inline-block"></div>
                    </div>
                </div>

                <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-5 mb-8">
                    <h4 class="font-bold text-blue-800 mb-2 flex items-center"><i class="fas fa-info-circle mr-2"></i> Yêu cầu cấu trúc File Excel</h4>
                    <ul class="text-sm text-blue-700 space-y-2 list-disc list-inside">
                        <li>Dòng 1 bắt buộc là <strong>Dòng Tiêu Đề</strong> (Tên cột).</li>
                        <li>Bắt buộc phải có cột: <strong>CCCD</strong> và <strong>Email</strong>. (Hệ thống dùng 2 cột này làm gốc)</li>
                        <li>Các cột thường dùng được map tự động: <code class="bg-blue-100 px-1 rounded">HOTEN</code>, <code class="bg-blue-100 px-1 rounded">NGAYSINH</code>, <code class="bg-blue-100 px-1 rounded">SBD</code>, <code class="bg-blue-100 px-1 rounded">KV</code>, <code class="bg-blue-100 px-1 rounded">DOITUONG</code>, <code class="bg-blue-100 px-1 rounded">TOHOP</code></li>
                        <li>Cột điểm: <code class="bg-blue-100 px-1 rounded">DM1</code>, <code class="bg-blue-100 px-1 rounded">DM2</code>, <code class="bg-blue-100 px-1 rounded">DM3</code>, <code class="bg-blue-100 px-1 rounded">DIEMTOHOP</code>, <code class="bg-blue-100 px-1 rounded">DIEMUT</code>, <code class="bg-blue-100 px-1 rounded">UTQ</code>, <code class="bg-blue-100 px-1 rounded">DIEMXT</code></li>
                        <li>Ngành & Tiền: <code class="bg-blue-100 px-1 rounded">MANGANH</code>, <code class="bg-blue-100 px-1 rounded">NGANH</code>, <code class="bg-blue-100 px-1 rounded">SOTK</code>, <code class="bg-blue-100 px-1 rounded">NGANHANG</code>, <code class="bg-blue-100 px-1 rounded">SOTIEN</code>, <code class="bg-blue-100 px-1 rounded">NOIDUNGCK</code></li>
                        <li>Cột email rỗng sẽ bị hệ thống bỏ qua tự động.</li>
                    </ul>
                    <div class="mt-4 border-t border-blue-200/50 pt-4">
                        <a href="<?= url('/assets/Mau_Import_Thu_Trung_Tuyen.xlsx') ?>" download class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 hover:bg-blue-200 hover:text-blue-800 rounded-lg text-sm font-bold transition">
                            <i class="fas fa-download mr-2"></i> Tải xuống File Excel Mẫu
                        </a>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="<?= url('/admin/admission-letters') ?>" class="px-6 py-2.5 bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold rounded-lg transition">Hủy bỏ</a>
                    <button type="submit" class="px-6 py-2.5 bg-[#0066FF] hover:bg-blue-700 text-white font-bold rounded-lg shadow transition flex items-center">
                        <i class="fas fa-upload mr-2"></i> Bắt đầu Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showFileName(input) {
        const fileNameDiv = document.getElementById('fileName');
        if (input.files && input.files.length > 0) {
            fileNameDiv.textContent = 'Đã chọn: ' + input.files[0].name;
            fileNameDiv.classList.remove('hidden');
        } else {
            fileNameDiv.classList.add('hidden');
        }
    }
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
