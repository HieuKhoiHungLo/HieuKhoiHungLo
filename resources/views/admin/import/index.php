<?php
ob_start();
?>

<div class="p-6 bg-gray-50 min-h-screen" x-data="importApp()">
    <!-- Premium Loading Modal -->
    <div x-cloak x-show="isLoading" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/40 backdrop-blur-md z-[100] flex items-center justify-center p-4">
        
        <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 w-full max-w-md p-8 text-center relative overflow-hidden">
            <!-- Decorative background shapes -->
            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl"></div>

            <!-- Animated Logo Container -->
            <div class="relative w-28 h-28 mx-auto mb-6">
                <!-- Outer Pulse ring -->
                <div class="absolute inset-0 bg-blue-500/20 rounded-full animate-pulsing-slow"></div>
                <!-- Dotted rotating ring -->
                <div class="absolute inset-1 border-2 border-blue-200 border-dashed rounded-full animate-spin-slow"></div>
                <!-- Glassmorphism Circle with Logo -->
                <div class="absolute inset-4 bg-white rounded-full flex items-center justify-center shadow-xl border border-white/50 overflow-hidden">
                    <img src="<?= url('/assets/img/Logo.png') ?>" 
                         alt="Logo" 
                         class="w-full h-full object-contain p-2 relative z-10">
                    <!-- Internal Shimmer -->
                    <div class="shimmer-glare absolute inset-0 z-20 opacity-30"></div>
                </div>
            </div>

            <h3 class="text-xl font-bold text-slate-800 mb-2">Hệ thống đang xử lý</h3>
            <p class="text-slate-500 text-sm mb-6 px-4" x-text="currentLoadingMessage"></p>
            
            <!-- Progress container -->
            <div class="relative h-2 bg-slate-100 rounded-full overflow-hidden mb-2">
                <div class="absolute top-0 left-0 h-full bg-blue-600 rounded-full transition-all duration-500 shadow-[0_0_10px_rgba(37,99,235,0.5)]" 
                     :style="`width: ${progress}%`"
                     id="loadingProgress">
                </div>
                <!-- Shimmering overlay -->
                <div class="shimmer-glare absolute inset-0"></div>
            </div>
            <div class="flex justify-between text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">
                <span x-text="progress + '%'"></span>
                <span x-text="progress < 100 ? 'Vui lòng không đóng trang' : 'Hoàn thành!'"></span>
            </div>
        </div>
    </div>

    <div class="mb-8 flex justify-between items-start">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Import Dữ liệu Bộ GD&ĐT</h1>
            <p class="text-sm text-gray-500 mt-1">Đẩy file Excel (.xlsx) hoặc CSV trích xuất từ Hệ thống chung của Bộ Giáo dục & Đào tạo.</p>
        </div>
        <div class="flex gap-3">
            <button @click="openBatchModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center shadow-lg shadow-blue-200">
                <i class="fas fa-plus mr-2"></i> TẠO ĐỢT MỚI
            </button>
        </div>
    </div>

    <!-- Active Batch Banner -->
    <?php if ($activeBatch): ?>
        <div class="mb-6 bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-center justify-between shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-400 mr-3"></i>
                <p class="text-sm text-blue-700">
                    <strong>Đợt đang hoạt động:</strong> <?= htmlspecialchars($activeBatch['ten_dot']) ?> (Năm <?= $activeBatch['nam_tuyen_sinh'] ?>)
                </p>
            </div>
            <button @click="confirmClearBatch" class="px-3 py-1.5 bg-red-50 text-red-600 text-xs font-bold rounded-lg border border-red-200 hover:bg-red-600 hover:text-white transition flex items-center shadow-sm">
                <i class="fas fa-trash-alt mr-2"></i> XÓA TRẮNG DỮ LIỆU ĐỢT
            </button>
        </div>
    <?php else: ?>
        <div class="mb-6 bg-amber-50 border border-amber-100 rounded-xl p-4 flex items-center shadow-sm">
            <i class="fas fa-exclamation-triangle text-amber-500 mr-3"></i>
            <p class="text-sm text-amber-700">Chưa có đợt tuyển sinh nào được kích hoạt. Vui lòng tạo hoặc kích hoạt đợt trước khi import.</p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- File 1: Candidates -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="bg-blue-600 px-4 py-3 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-white">1. File Thí sinh & Điểm (Bảng 1)</h3>
            </div>
            <div class="p-4 flex-grow flex flex-col justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-4">Import thông tin cá nhân và Điểm bài thi THPT Quốc gia của thí sinh.</p>
                    <form @submit.prevent="upload('candidates', $event)" class="mb-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="batch_id" value="<?= $activeBatch['id'] ?? '' ?>">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chọn file Excel/CSV (Bảng 1)</label>
                        <input type="file" name="file" accept=".xlsx, .xls, .csv" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 mb-4">
                        <button type="submit" :disabled="!hasBatch || loading.candidates" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed">
                            <i class="fas fa-upload mr-2" x-show="!loading.candidates"></i>
                            <i class="fas fa-spinner fa-spin mr-2" x-show="loading.candidates" x-cloak></i>
                            <span x-text="loading.candidates ? 'Đang tải lên...' : 'Upload & Import'"></span>
                        </button>
                    </form>
                </div>
                <div x-show="msg.candidates" x-html="msg.candidates" class="mt-2 text-sm" x-cloak></div>
            </div>
        </div>

        <!-- File 3: Applications -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="bg-green-600 px-4 py-3 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-white">2. File Nguyện vọng (Bảng 3)</h3>
            </div>
            <div class="p-4 flex-grow flex flex-col justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-4">Import danh sách nguyện vọng kèm Thứ tự nguyện vọng trên hệ thống của Bộ GD&ĐT</p>
                    <form @submit.prevent="upload('applications', $event)" class="mb-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="batch_id" value="<?= $activeBatch['id'] ?? '' ?>">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chọn file Excel/CSV (Bảng 3)</label>
                        <input type="file" name="file" accept=".xlsx, .xls, .csv" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 mb-4">
                        <button type="submit" :disabled="!hasBatch || loading.applications" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:bg-gray-400 disabled:cursor-not-allowed">
                            <i class="fas fa-upload mr-2" x-show="!loading.applications"></i>
                            <i class="fas fa-spinner fa-spin mr-2" x-show="loading.applications" x-cloak></i>
                            <span x-text="loading.applications ? 'Đang tải lên...' : 'Upload & Import'"></span>
                        </button>
                    </form>
                </div>
                <div x-show="msg.applications" x-html="msg.applications" class="mt-2 text-sm" x-cloak></div>
            </div>
        </div>

        <!-- File 9: Transcripts -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="bg-yellow-500 px-4 py-3 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-white">3. File Học Bạ (Bảng 9)</h3>
            </div>
            <div class="p-4 flex-grow flex flex-col justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-4">Import điểm tổng kết cả năm lớp 10, 11 và 12 tất cả các môn trong học bạ của thí sinh.</p>
                    <form @submit.prevent="upload('transcripts', $event)" class="mb-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="batch_id" value="<?= $activeBatch['id'] ?? '' ?>">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chọn file Excel/CSV (Bảng 9)</label>
                        <input type="file" name="file" accept=".xlsx, .xls, .csv" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100 mb-4">
                        <button type="submit" :disabled="!hasBatch || loading.transcripts" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 disabled:bg-gray-400 disabled:cursor-not-allowed">
                            <i class="fas fa-upload mr-2" x-show="!loading.transcripts"></i>
                            <i class="fas fa-spinner fa-spin mr-2" x-show="loading.transcripts" x-cloak></i>
                            <span x-text="loading.transcripts ? 'Đang tải lên...' : 'Upload & Import'"></span>
                        </button>
                    </form>
                </div>
                <div x-show="msg.transcripts" x-html="msg.transcripts" class="mt-2 text-sm" x-cloak></div>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Lịch sử Import gần đây</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian nạp</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên File</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại (Bảng)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kết quả (Dòng)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Xử lý</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-sm">
                    <?php if(empty($history)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">Chưa có lịch sử import nào.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($history as $log): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 italic">
                            <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 max-w-xs truncate" title="<?= htmlspecialchars($log['file_name']) ?>">
                            <?= htmlspecialchars($log['file_name']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $log['loai_file'] == 'candidates' ? 'bg-blue-100 text-blue-800' : 
                                   ($log['loai_file'] == 'applications' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                <?= ucfirst($log['loai_file']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-bold"><?= number_format($log['record_count'] ?? 0) ?> dòng</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500"><?= $log['duration'] ?? 0 ?>s</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button @click="deleteLog(<?= $log['id'] ?>)" class="text-gray-400 hover:text-red-600 transition">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Create Batch -->
    <div x-show="openBatchModal" class="fixed z-50 inset-0 overflow-y-auto" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openBatchModal" @click="openBatchModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openBatchModal" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="<?= url('/admin/import/batch/create') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-calendar-plus text-blue-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Tạo đợt Tuyển sinh & Import mới</h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tên đợt tuyển</label>
                                        <input type="text" name="name" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="VD: Xét tuyển Đợt 1 / 2026">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Năm tuyển sinh</label>
                                        <input type="number" name="year" value="<?= date('Y') ?>" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Tạo mới Đợt</button>
                        <button type="button" @click="openBatchModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Hủy bỏ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function importApp() {
    return {
        hasBatch: <?= $activeBatch ? 'true' : 'false' ?>,
        openBatchModal: false,
        isLoading: false,
        progress: 0,
        currentLoadingMessage: '',
        loading: {
            candidates: false,
            applications: false,
            transcripts: false
        },
        msg: {
            candidates: '',
            applications: '',
            transcripts: ''
        },
        
        startLoading(msg) {
            this.isLoading = true;
            this.progress = 0;
            this.currentLoadingMessage = msg;
        },

        stopLoading() {
            this.progress = 100;
            this.uploadProgress = 100;
            setTimeout(() => {
                this.isLoading = false;
            }, 600);
        },

        async upload(type, event) {
            const form = event.target;
            const importToken = 'imp_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            const formData = new FormData(form);
            formData.append('type', type);
            formData.append('import_token', importToken);
            
            this.loading[type] = true;
            this.msg[type] = '';
            
            this.startLoading('Đang tải file lên máy chủ...');

            try {
                const result = await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '<?= url("/admin/import/upload") ?>', true);

                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            const percentComplete = Math.round((e.loaded / e.total) * 100);
                            
                            // Only update main progress if we haven't started server polling yet
                            // but show it's "Uploading"
                            if (percentComplete < 100) {
                                this.progress = Math.round(percentComplete * 0.1); // Small segment for upload
                                this.currentLoadingMessage = `Đang tải file lên máy chủ: ${percentComplete}%`;
                            } else {
                                this.currentLoadingMessage = 'Đang nạp file Excel vào bộ nhớ (32k+ dòng)...';
                                // Don't set progress to 100 here to avoid the jump. 
                                // Keep it low until server data starts coming.
                                this.progress = 10; 
                            }
                            
                            if (percentComplete === 100) {
                                let lastPercent = 0;
                                const progressInterval = setInterval(async () => {
                                    try {
                                        const res = await fetch('<?= url("/admin/import/progress") ?>?token=' + importToken + '&t=' + Date.now());
                                        if (res.ok) {
                                            const data = await res.json();
                                            if (data.percent !== undefined) {
                                                const currentPercent = parseInt(data.percent);
                                                // Scaling: server progress is the main 10-100%
                                                const scaledPercent = 10 + Math.round(currentPercent * 0.9);
                                                
                                                if (scaledPercent > this.progress || currentPercent === 0) {
                                                    this.progress = scaledPercent;
                                                    if (data.message) this.currentLoadingMessage = data.message;
                                                }
                                            }
                                        }
                                    } catch (err) {
                                        console.error('Progress polling error:', err);
                                    }
                                }, 1000);

                                xhr.progressInterval = progressInterval;
                            }
                        }
                    };

                    xhr.onload = () => {
                        if (xhr.progressInterval) clearInterval(xhr.progressInterval);
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                resolve(JSON.parse(xhr.responseText));
                            } catch (e) {
                                reject(new Error('Dữ kết quả không hợp lệ.'));
                            }
                        } else {
                            reject(new Error('Lỗi máy chủ (HTTP ' + xhr.status + ')'));
                        }
                    };

                    xhr.onerror = () => {
                        if (xhr.progressInterval) clearInterval(xhr.progressInterval);
                        reject(new Error('Lỗi kết nối mạng'));
                    };
                    xhr.send(formData);
                });
                
                if (result.status) {
                    this.stopLoading();
                    setTimeout(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Hoàn tất nạp dữ liệu!',
                            html: `<div class="text-sm mt-2 text-gray-600">Đã cập nhật thành công <b>${result.success}</b> / ${result.count} dòng.</div>`,
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            location.reload();
                        });
                    }, 700);
                } else {
                    this.isLoading = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Có lỗi xảy ra',
                        text: result.message,
                        confirmButtonColor: '#3B82F6'
                    });
                }
            } catch (error) {
                this.isLoading = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi Kết Nối',
                    text: error.message,
                    confirmButtonColor: '#3B82F6'
                });
            } finally {
                this.loading[type] = false;
                form.reset();
            }
        },
        async confirmClearBatch() {
            const { value: password } = await Swal.fire({
                title: 'XÁC NHẬN XÓA DỮ LIỆU',
                input: 'password',
                inputPlaceholder: 'Nhập mật khẩu Admin...',
                showCancelButton: true,
                confirmButtonText: 'XÁC NHẬN XÓA',
                confirmButtonColor: '#DC2626',
                preConfirm: async (pass) => {
                    if (!pass) return Swal.showValidationMessage('Vui lòng nhập mật khẩu');
                    try {
                        const formData = new FormData();
                        formData.append('batch_id', '<?= $activeBatch['id'] ?? '' ?>');
                        formData.append('password', pass);
                        
                        // Add CSRF token for security (Fixed: added underscore)
                        const csrfToken = document.querySelector('input[name="_csrf_token"]');
                        if (csrfToken) formData.append('_csrf_token', csrfToken.value);

                        const response = await fetch('<?= url("/admin/import/clear-batch") ?>', { 
                            method: 'POST', 
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' } // Header for middleware detection
                        });
                        const result = await response.json();
                        if (!result.status) throw new Error(result.message);
                        return result;
                    } catch (error) {
                        Swal.showValidationMessage(`Lỗi: ${error.message}`);
                    }
                }
            });
            if (password) {
                Swal.fire({ icon: 'success', title: 'Đã xóa trắng dữ liệu!' }).then(() => location.reload());
            }
        },
        async deleteLog(id) {
            const result = await Swal.fire({
                title: 'Xóa nhật ký?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3B82F6',
                confirmButtonText: 'Đồng ý'
            });
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);
                
                // Add CSRF token for security (Fixed: added underscore)
                const csrfToken = document.querySelector('input[name="_csrf_token"]');
                if (csrfToken) formData.append('_csrf_token', csrfToken.value);

                await fetch('<?= url("/admin/import/delete-log") ?>', { 
                    method: 'POST', 
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' } // Header for middleware detection
                });
                location.reload();
            }
        }
    };
}
</script>

<style>
.shimmer-glare {
    background: linear-gradient(
        to right,
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 0.4) 50%,
        rgba(255, 255, 255, 0) 100%
    );
    animation: loading-shimmer 2s infinite linear;
}

@keyframes loading-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

@keyframes pulsing-slow {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.05); }
}

.animate-pulsing-slow {
    animation: pulsing-slow 3s infinite ease-in-out;
}

@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-spin-slow {
    animation: spin-slow 6s infinite linear;
}

[x-cloak] { display: none !important; }
</style>

<?php
$content = ob_get_clean();
$title = 'Import dữ liệu Bộ GD&ĐT';
include __DIR__ . '/../../layouts/admin.php';
?>
