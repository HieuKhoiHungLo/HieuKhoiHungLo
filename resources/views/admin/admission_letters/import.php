<?php $title = 'Import Danh sách Gửi thư'; ?>
<?php ob_start(); ?>

<div class="max-w-2xl mx-auto flex flex-col" style="height: calc(100vh - 11rem);">
    <header class="mb-4 flex-shrink-0 flex justify-between items-end">
        <div>
            <a href="<?= url('/admin/admission-letters') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition inline-flex items-center mb-1">
                <i class="fas fa-arrow-left mr-2"></i> Thư báo Trúng Tuyển
            </a>
            <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Import Danh Sách</h2>
        </div>
        <a href="<?= url('/assets/Mau_Import_Thu_Trung_Tuyen.xlsx') ?>" download class="inline-flex items-center px-4 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-sm font-bold transition border border-emerald-200 shadow-sm">
            <i class="fas fa-file-download mr-2"></i> File Excel Mẫu
        </a>
    </header>

    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center flex-shrink-0">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <div><strong>Lỗi Import!</strong> <?= htmlspecialchars($_GET['error']) ?></div>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex-grow flex flex-col min-h-0 overflow-hidden">
        <form action="<?= url('/admin/admission-letters/import') ?>" method="POST" enctype="multipart/form-data" class="flex flex-col h-full">
            <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">

            <!-- Body -->
            <div class="flex-grow flex flex-col p-6 md:p-8 gap-5 min-h-0">

                <!-- Tên đợt -->
                <div class="flex-shrink-0">
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Tên Đợt Gửi <span class="text-slate-400 font-normal">(Tùy chọn)</span></label>
                    <input type="text" name="batch_id"
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm text-sm"
                           placeholder="VD: Đợt 1 - 2026"
                           value="Đợt <?= date('d/m/Y') ?>">
                </div>

                <!-- Dropzone -->
                <div class="flex-grow relative group cursor-pointer min-h-0" onclick="document.getElementById('fileInput').click()">
                    <input type="file" name="file" id="fileInput" accept=".xlsx,.xls" class="hidden" required onchange="handleFileSelect(this)">

                    <div id="dropzone" class="absolute inset-0 border-2 border-dashed border-blue-300 bg-blue-50/30 rounded-2xl flex flex-col items-center justify-center text-center p-6 transition-all duration-300 group-hover:bg-blue-50 group-hover:border-blue-500">
                        <!-- Trạng thái chưa chọn file -->
                        <div id="uploadPrompt" class="flex flex-col items-center">
                            <div class="w-20 h-20 bg-white rounded-full shadow-md flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-cloud-upload-alt text-4xl text-blue-500"></i>
                            </div>
                            <h4 class="text-xl font-black text-slate-700 mb-1">Tải lên File Excel</h4>
                            <p class="text-sm text-slate-500 mb-3">Kéo thả hoặc click để chọn file</p>
                            <span class="text-xs px-3 py-1 bg-slate-200 text-slate-600 rounded-full font-semibold">.xlsx &nbsp;/&nbsp; .xls</span>
                        </div>

                        <!-- Trạng thái đã chọn file -->
                        <div id="fileSelected" class="hidden flex-col items-center w-full">
                            <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-file-excel text-4xl text-emerald-600"></i>
                            </div>
                            <h4 class="text-lg font-black text-emerald-700 mb-1">Đã chọn file!</h4>
                            <p id="fileName" class="text-sm text-slate-700 font-semibold truncate max-w-xs text-center"></p>
                            <p id="fileSize" class="text-xs text-slate-400 mt-1"></p>
                            <button type="button" class="mt-4 text-xs font-bold text-blue-600 hover:underline px-3 py-1 rounded-lg hover:bg-blue-50 transition"
                                    onclick="event.stopPropagation(); document.getElementById('fileInput').click();">
                                <i class="fas fa-redo mr-1 text-[10px]"></i> Chọn file khác
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex-shrink-0 px-6 md:px-8 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end gap-3">
                <a href="<?= url('/admin/admission-letters') ?>"
                   class="px-6 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold rounded-xl transition shadow-sm text-sm">
                    Hủy bỏ
                </a>
                <button type="submit" id="submitBtn"
                        class="px-8 py-2.5 bg-[#0066FF] hover:bg-blue-700 text-white font-bold rounded-xl shadow-md shadow-blue-500/20 transition flex items-center text-sm opacity-40 cursor-not-allowed"
                        disabled>
                    <i class="fas fa-upload mr-2"></i> Bắt đầu Import
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const dropzone    = document.getElementById('dropzone');
    const fileInput   = document.getElementById('fileInput');
    const uploadPrompt = document.getElementById('uploadPrompt');
    const fileSelected = document.getElementById('fileSelected');
    const fileName    = document.getElementById('fileName');
    const fileSize    = document.getElementById('fileSize');
    const submitBtn   = document.getElementById('submitBtn');

    // Drag & Drop
    ['dragenter','dragover','dragleave','drop'].forEach(e => {
        dropzone.addEventListener(e, ev => { ev.preventDefault(); ev.stopPropagation(); }, false);
    });
    ['dragenter','dragover'].forEach(e => dropzone.addEventListener(e, () => {
        dropzone.classList.add('bg-blue-50','border-blue-500','scale-[1.02]');
    }));
    ['dragleave','drop'].forEach(e => dropzone.addEventListener(e, () => {
        dropzone.classList.remove('bg-blue-50','border-blue-500','scale-[1.02]');
    }));
    dropzone.addEventListener('drop', ev => {
        const files = ev.dataTransfer.files;
        if (files.length > 0) { fileInput.files = files; handleFileSelect(fileInput); }
    });

    function handleFileSelect(input) {
        if (!input.files || input.files.length === 0) { resetUI(); return; }

        const file = input.files[0];
        if (!file.name.match(/\.(xls|xlsx)$/i)) {
            alert('Vui lòng chọn file Excel (.xls, .xlsx)');
            input.value = '';
            resetUI();
            return;
        }

        uploadPrompt.classList.add('hidden');
        fileSelected.classList.remove('hidden');
        fileSelected.classList.add('flex');

        fileName.textContent = file.name;
        let sz = file.size / 1024;
        fileSize.textContent = sz > 1024 ? (sz / 1024).toFixed(2) + ' MB' : sz.toFixed(2) + ' KB';

        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-40','cursor-not-allowed');

        dropzone.classList.add('ring-4','ring-emerald-500/30','border-emerald-500','bg-emerald-50/10');
        setTimeout(() => dropzone.classList.remove('ring-4','ring-emerald-500/30','border-emerald-500','bg-emerald-50/10'), 1200);
    }

    function resetUI() {
        uploadPrompt.classList.remove('hidden');
        fileSelected.classList.add('hidden');
        fileSelected.classList.remove('flex');
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-40','cursor-not-allowed');
    }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>
