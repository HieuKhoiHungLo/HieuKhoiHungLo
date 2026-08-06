<?php
$isReadOnly = $isReadOnly ?? false;
$title = $isReadOnly ? 'Tổng quan Lọc ảo' : 'Xét tuyển - Lọc ảo';
ob_start();

// Tính tổng số môn/tổ hợp tối đa để tạo cột.
$comboCols = [];
if (!empty($combinations)) {
    foreach ($combinations as $c) {
        $comboCols[] = [
            'ma' => $c['ma_to_hop'],
            'desc' => $c['m1'] . '-' . $c['m2'] . '-' . $c['m3']
        ];
    }
}
?>

<style>
[x-cloak] { display: none !important; }
</style>

<!-- jQuery & Chart.js always needed -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- DataTables loaded only when NOT read-only -->
<?php if (!$isReadOnly): ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
<?php endif; ?>

<script>
    // Alias for Toast functionality used in this file
    window.toast = {
        success: (msg) => typeof showToast === 'function' ? showToast(msg || 'Thao tác thành công', 'success') : alert(msg || 'Thao tác thành công'),
        error: (msg) => typeof showToast === 'function' ? showToast(msg || 'Có lỗi xảy ra từ máy chủ', 'error') : alert('Error: ' + (msg || 'Có lỗi xảy ra')),
        warning: (msg) => typeof showToast === 'function' ? showToast(msg || 'Cảnh báo', 'warning') : alert('Warning: ' + (msg || 'Cảnh báo')),
        info: (msg) => typeof showToast === 'function' ? showToast(msg || 'Thông báo', 'info') : alert(msg || 'Thông báo')
    };
</script>

<div class="h-full flex flex-col p-6 bg-slate-50 relative" x-data="virtualAdmission()">
    <!-- Header Row (Title & Filters) -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-magic text-indigo-600"></i> Xét Tuyển Lọc Ảo
            </h1>
            <p class="text-sm text-slate-500 mt-1">Bảng tổng hợp điểm đa tổ hợp và xét tuyển tự động</p>
        </div>
        
        <!-- Year & Session Dropdowns -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Filter by Year -->
            <select id="yearFilter" class="border border-slate-300 rounded-lg text-sm bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 min-w-[120px] text-slate-700 outline-none" x-model="selectedYear" @change="selectedSession = ''; loadData()">
                <option value="">-- Năm --</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>"><?= $year ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Filter by Session (Filtered by selectedYear in Alpine) -->
            <select id="sessionFilter" class="border border-slate-300 rounded-lg text-sm bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 min-w-[220px] text-slate-700 outline-none" x-model="selectedSession" @change="loadData()">
                <option value="">-- Chọn đợt xét tuyển --</option>
                <template x-for="session in filteredSessions" :key="session.id">
                    <option :value="session.id" x-text="session.ten_dot || session.ten_dot_xet_tuyen"></option>
                </template>
            </select>
        </div>
    </div>

    <?php if (!$isReadOnly): ?>
    <!-- Action Toolbar (Down on a new line, clean layout) -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-6 shadow-sm flex flex-wrap justify-between items-center gap-4">
        <!-- Left: Computational actions -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- 1. Sync Data Button -->
            <button @click="syncData()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-2 disabled:opacity-50 disabled:pointer-events-none" 
                    :disabled="isLoading || !selectedSession">
                <i class="fas fa-sync-alt" :class="{'fa-spin': isSyncing}"></i> 
                <span>Đồng bộ dữ liệu</span>
            </button>
            
            <!-- 2. Score Calculation Button -->
            <button @click="recalculate()" 
                    class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-2 disabled:opacity-50 disabled:pointer-events-none" 
                    :disabled="isLoading || !selectedSession">
                <i class="fas fa-calculator" :class="{'fa-spin': isCalculating}"></i> 
                <span>Tính lại toàn bộ</span>
            </button>
            
            <!-- 3. Run Virtual Filter Button -->
            <button @click="runVirtualFilter()" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-2 disabled:opacity-50 disabled:pointer-events-none" 
                    :disabled="isLoading || !selectedSession">
                <i class="fas fa-magic" :class="{'fa-spin': isFiltering}"></i>
                <span>Chạy Lọc Ảo</span>
            </button>

            <!-- 4. Import BGD Result Button -->
            <button @click="showBgdUploadModal = true; bgdStatus.lastMessage = ''; bgdStatus.selectedFileName = ''; bgdStatus.selectedFile = null; if(document.getElementById('bgd-file-input-modal')) document.getElementById('bgd-file-input-modal').value = '';" 
                    style="background-color: #7c3aed;"
                    class="text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-2 disabled:opacity-50 disabled:pointer-events-none hover:opacity-90" 
                    :disabled="isLoading || !selectedSession">
                <i class="fas fa-file-import"></i>
                <span>Nhập KQ lọc ảo</span>
            </button>
        </div>

        <!-- Right: Export Dropdown -->
        <div class="relative" x-data="{ exportOpen: false }">
            <button @click="exportOpen = !exportOpen"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-2 disabled:opacity-50 disabled:pointer-events-none"
                :disabled="isLoading || !selectedSession">
                <i class="fas fa-file-excel"></i>
                <span>Xuất Excel Báo Cáo</span>
                <i class="fas fa-chevron-down text-xs transition-transform duration-250" :class="{'rotate-180': exportOpen}"></i>
            </button>

            <div x-show="exportOpen" x-cloak @click.outside="exportOpen = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 top-full mt-1.5 w-72 bg-white rounded-xl shadow-xl border border-slate-200 z-50 py-1.5 overflow-hidden">

                <a @click="exportOpen = false; exportAll()"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer">
                    <i class="fas fa-table text-slate-400 w-4"></i>
                    <div>
                        <div class="font-medium">Xuất toàn bộ dữ liệu</div>
                        <div class="text-xs text-slate-400">Tất cả nguyện vọng</div>
                    </div>
                </a>

                <hr class="border-slate-100 my-1">

                <a @click="exportOpen = false; exportAdmitted()"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-emerald-700 hover:bg-emerald-50 cursor-pointer">
                    <i class="fas fa-check-circle text-emerald-500 w-4"></i>
                    <div>
                        <div class="font-medium text-emerald-700">Danh sách trúng tuyển</div>
                        <div class="text-xs text-emerald-500/70">Sắp xếp: Ngành ↗ &bull; Điểm ↘</div>
                    </div>
                </a>

                <a @click="exportOpen = false; exportVirtualFilter()"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-indigo-700 hover:bg-indigo-50 cursor-pointer">
                    <i class="fas fa-filter text-indigo-500 w-4"></i>
                    <div>
                        <div class="font-medium text-indigo-700">Xuất kết quả lọc ảo</div>
                        <div class="text-xs text-indigo-500/70">4 cột: STT, ĐDCN, Nguyện vọng, Mã XT</div>
                    </div>
                </a>

                <a @click="exportOpen = false; exportMoetFormat()"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-sky-700 hover:bg-sky-50 cursor-pointer">
                    <i class="fas fa-file-invoice text-sky-500 w-4"></i>
                    <div>
                        <div class="font-medium text-sky-700">Kết quả XT Bộ GD&ĐT</div>
                        <div class="text-xs text-sky-500/70">Mẫu báo cáo gửi Bộ GD&ĐT (13 cột, đỗ/trượt)</div>
                    </div>
                </a>

                <a @click="exportOpen = false; exportFailed()"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-rose-700 hover:bg-rose-50 cursor-pointer">
                    <i class="fas fa-times-circle text-rose-500 w-4"></i>
                    <div>
                        <div class="font-medium text-rose-700">Danh sách không đỗ NV nào</div>
                        <div class="text-xs text-rose-500/70">Kèm cột Lý do không đỗ</div>
                    </div>
                </a>

                <a @click="exportOpen = false; exportAcademicFail()"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-amber-700 hover:bg-amber-50 cursor-pointer">
                    <i class="fas fa-exclamation-triangle text-amber-500 w-4"></i>
                    <div>
                        <div class="font-medium text-amber-700">Không đạt ĐK học lực</div>
                        <div class="text-xs text-amber-500/70">Sắp xếp: Ngành ↗ &bull; Điểm ↘</div>
                    </div>
                </a>

                <!-- Divider: BGD Section -->
                <hr class="border-slate-100 my-1">
                <div class="px-4 py-1.5">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Sau lọc ảo Bộ GD&ĐT</p>
                </div>

                <a @click="exportOpen = false; exportAdmittedFinal()"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm cursor-pointer"
                   :class="bgdStatus.imported ? 'text-emerald-800 hover:bg-emerald-50' : 'text-slate-400 pointer-events-none opacity-50'">
                    <i class="fas fa-star text-emerald-600 w-4"></i>
                    <div>
                        <div class="font-bold">DS Trúng tuyển Chính thức</div>
                        <div class="text-xs opacity-70" x-text="bgdStatus.imported ? 'Đã loại ' + (bgdStatus.bi_loai || 0) + ' TS trúng trường khác' : 'Cần import kết quả Bộ GD&ĐT trước'"></div>
                    </div>
                    <span x-show="bgdStatus.imported" class="ml-auto text-[10px] font-black bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full" x-text="(bgdStatus.giu_lai || 0) + ' TS'"></span>
                </a>

                <a @click="exportOpen = false; exportEliminatedByBGD()"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm cursor-pointer"
                   :class="bgdStatus.imported ? 'text-rose-700 hover:bg-rose-50' : 'text-slate-400 pointer-events-none opacity-50'">
                    <i class="fas fa-ban text-rose-500 w-4"></i>
                    <div>
                        <div class="font-medium">DS Bị loại (trúng trường khác)</div>
                        <div class="text-xs opacity-70" x-text="bgdStatus.imported ? 'Đã trúng tuyển ở trường khác' : 'Cần import kết quả Bộ GD&ĐT trước'"></div>
                    </div>
                    <span x-show="bgdStatus.imported" class="ml-auto text-[10px] font-black bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full" x-text="(bgdStatus.bi_loai || 0) + ' TS'"></span>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- PANEL: Kết quả Lọc ảo Bộ GD&ĐT                             -->
    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- BANNER: Trạng thái Kết quả Lọc ảo Bộ GD&ĐT                   -->
    <!-- ============================================================ -->
    <div class="mb-4 rounded-xl border p-3 flex flex-wrap items-center justify-between gap-4 transition-all duration-300"
         :class="bgdStatus.imported
             ? 'bg-emerald-50/50 border-emerald-200 text-emerald-800'
             : 'bg-slate-50 border-slate-200 text-slate-500'">
        
        <div class="flex items-center gap-2">
            <i class="fas" :class="bgdStatus.imported ? 'fa-check-circle text-emerald-500' : 'fa-info-circle text-slate-400'"></i>
            <span class="text-xs font-semibold"
                  x-text="bgdStatus.imported
                      ? 'Đã import Kết quả Bộ GD&ĐT: ' + (bgdStatus.lan_loc_ao || '') + ' lúc ' + (bgdStatus.lan_import_cuoi || '') + ' (Bởi ' + (bgdStatus.imported_by || '') + ')'
                      : 'Chưa import kết quả lọc ảo từ Bộ GD&ĐT. Vui lòng bấm nút &ldquo;Nhập KQ lọc ảo&rdquo; để đối chiếu.'"></span>
        </div>

        <!-- Stats badges (chỉ show sau khi đã import) -->
        <div x-show="bgdStatus.imported" class="flex items-center gap-2 text-[10px] font-bold">
            <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">
                📄 <span x-text="bgdStatus.tong_bo_gd"></span> dòng
            </span>
            <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">
                ✅ Giữ lại: <span x-text="bgdStatus.giu_lai"></span> TS
            </span>
            <span class="bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full">
                🚫 Bị loại: <span x-text="bgdStatus.bi_loai"></span> TS
            </span>
            <?php if (!$isReadOnly): ?>
            <!-- Download report link -->
            <a href="<?= url('/admin/api/vf/download-bgd-report') ?>" 
               class="text-indigo-600 hover:text-indigo-800 underline ml-2 flex items-center gap-1 font-bold">
                <i class="fas fa-file-download"></i> Tải báo cáo đối chiếu
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL: NHẬP KQ LỌC ẢO BỘ GD&ĐT -->
    <div x-show="showBgdUploadModal" class="fixed z-50 inset-0 overflow-y-auto" x-cloak style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 transition-opacity" @click="!bgdStatus.importing && (showBgdUploadModal = false)">
                <div class="absolute inset-0 bg-slate-900 opacity-75 backdrop-blur-sm"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Modal Content Wrapper -->
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <!-- Emerald Icon -->
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-file-excel text-emerald-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-slate-900">Import Kết quả Lọc ảo Bộ</h3>
                            
                            <!-- Expected Columns box -->
                            <div class="mt-2 text-sm text-slate-500 space-y-2">
                                 <ol class="list-decimal ml-4 text-xs font-mono bg-slate-50 p-3 rounded-md border border-slate-200">
                                     <li>Lần lọc ảo</li>
                                     <li>SBD</li>
                                     <li>Họ và tên</li>
                                     <li>ĐDCN (CCCD)</li>
                                     <li>Mã ngành (HVU)</li>
                                     <li>Thứ tự NV</li>
                                     <li>Kết quả (Đỗ/Trượt)</li>
                                     <li>Mã trường trúng tuyển</li>
                                 </ol>
                            </div>

                            <!-- File Selector -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Chọn file</label>
                                <input type="file" id="bgd-file-input-modal" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white" accept=".xlsx, .xls" @change="handleBGDFileChange($event)" :disabled="bgdStatus.importing">
                            </div>

                            <!-- Result message -->
                            <div x-show="bgdStatus.lastMessage" x-cloak class="mt-3 text-xs px-3 py-3 rounded-lg flex flex-col gap-2"
                                 :class="bgdStatus.lastMessageType === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'">
                                <div class="flex items-center gap-2 font-bold">
                                    <i class="fas" :class="bgdStatus.lastMessageType === 'success' ? 'fa-check-circle text-emerald-600' : 'fa-exclamation-circle text-rose-600'"></i>
                                    <span x-text="bgdStatus.lastMessageType === 'success' ? 'Đối chiếu thành công!' : 'Có lỗi xảy ra'"></span>
                                </div>
                                <p x-text="bgdStatus.lastMessage" class="opacity-95"></p>
                                
                                <!-- Download Report Button -->
                                <template x-if="bgdStatus.lastMessageType === 'success'">
                                    <a href="<?= url('/admin/api/vf/download-bgd-report') ?>" 
                                       class="inline-flex items-center gap-1.5 w-max bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] px-3 py-1.5 rounded-lg mt-1 transition-all active:scale-95">
                                        <i class="fas fa-file-download"></i> Tải file báo cáo đối chiếu (.xls)
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-2">
                    <button @click="uploadBGDFile()"
                            :disabled="!bgdStatus.selectedFileName || bgdStatus.importing"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 sm:w-auto sm:text-sm disabled:opacity-40 disabled:pointer-events-none">
                        Tiến hành Import
                    </button>
                    <button @click="showBgdUploadModal = false" :disabled="bgdStatus.importing"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm disabled:opacity-40">
                        Hủy
                    </button>
                </div>
            </div>
        </div>
    </div>

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
            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl"></div>

            <!-- Animated Logo Container -->
            <div class="relative w-28 h-28 mx-auto mb-6">
                <!-- Outer Pulse ring -->
                <div class="absolute inset-0 bg-indigo-500/20 rounded-full animate-pulsing-slow"></div>
                <!-- Dotted rotating ring -->
                <div class="absolute inset-1 border-2 border-indigo-200 border-dashed rounded-full animate-spin-slow"></div>
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
                <div class="absolute top-0 left-0 h-full bg-indigo-600 rounded-full transition-all duration-500 shadow-[0_0_10px_rgba(79,70,229,0.5)]" 
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

    <!-- Tab Selector -->
    <div class="flex bg-slate-100 p-1 rounded-xl mb-4 w-max shadow-sm border border-slate-200">
        <?php if (!$isReadOnly): ?>
        <button @click="activeTab = 'list'"
            :class="activeTab === 'list' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'"
            class="px-4 py-2 rounded-lg font-bold text-xs transition duration-250 uppercase tracking-wider cursor-pointer whitespace-nowrap">
            <i class="fas fa-list-ul mr-2"></i>Danh sách nguyện vọng
        </button>
        <?php endif; ?>
        <button @click="activeTab = 'stats'; fetchStats(false)"
            :class="activeTab === 'stats' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'"
            class="px-4 py-2 rounded-lg font-bold text-xs transition duration-250 uppercase tracking-wider cursor-pointer whitespace-nowrap">
            <i class="fas fa-chart-bar mr-2"></i>Thống kê lọc ảo
        </button>
        <button @click="activeTab = 'charts'; fetchStats(true).then(() => initCharts())"
            :class="activeTab === 'charts' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'"
            class="px-4 py-2 rounded-lg font-bold text-xs transition duration-250 uppercase tracking-wider cursor-pointer whitespace-nowrap">
            <i class="fas fa-chart-pie mr-2"></i>Biểu đồ phân tích
        </button>
    </div>

    <?php if (!$isReadOnly): ?>
    <div x-show="activeTab === 'list'" class="bg-white rounded-xl shadow-sm border border-slate-200 flex-1 flex flex-col overflow-hidden relative">

        <div class="px-4 py-3 border-b border-slate-200 flex flex-wrap justify-between items-center bg-slate-50 gap-2">
            <h2 class="font-semibold text-slate-700 flex items-center gap-2">
                <i class="fas fa-list text-slate-400"></i> Bảng Lưới Thí Sinh
            </h2>
            <div class="flex flex-wrap items-center gap-3 text-sm font-medium text-slate-500">
                <span>
                    Hồ sơ đã duyệt: <span class="text-slate-700 font-bold" id="totalApprovedHoso">0</span>
                </span>
                <span class="text-slate-300">|</span>
                <span>
                    Có nguyện vọng: <span class="text-indigo-600 font-bold" id="candidateCount">0</span>
                </span>
                <span class="text-slate-300">|</span>
                <span>
                    Số nguyện vọng: <span class="text-indigo-600 font-bold" id="rowCount">0</span>
                </span>
                <span id="noAspirationAlert" class="hidden items-center gap-1 px-2 py-0.5 bg-amber-50 border border-amber-200 rounded-lg text-amber-700 text-xs font-bold">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span id="noAspirationCount">0</span> TS chưa đăng ký NV
                </span>
            </div>
        </div>

        <!-- Table Container -->
        <div class="flex-1 min-h-0 relative overflow-auto custom-scrollbar w-full">
            <table id="virtualGrid" class="w-full text-left border-collapse whitespace-nowrap text-sm" style="width:100%">
                <thead class="sticky top-0 z-10 bg-slate-100">
                    <tr class="text-slate-600 uppercase tracking-wider text-[10px] text-center">
                        <th rowspan="2" class="py-2 px-3 border border-slate-200 font-bold bg-slate-100 sticky left-0 z-20">CCCD</th>
                        <th rowspan="2" class="py-2 px-3 border border-slate-200 font-bold bg-slate-100 sticky left-[120px] z-20 min-w-[150px]">Họ tên</th>
                        <th rowspan="2" class="py-2 px-3 border border-slate-200 font-bold bg-slate-100">Mã ngành</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">NV</th>
                        <th rowspan="2" class="py-2 px-3 border border-slate-200 font-bold bg-slate-100 min-w-[100px]">Tổ hợp</th>
                        
                        <th colspan="4" class="py-1 px-2 border border-slate-200 font-bold bg-blue-50 text-blue-800">PT 100</th>
                        <th colspan="4" class="py-1 px-2 border border-slate-200 font-bold bg-emerald-50 text-emerald-800">PT 200</th>
                        
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">Tổ hợp max</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">PT max</th>
                        
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">Điểm M1 QĐ</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">Điểm M2 QĐ</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">Điểm M3 QĐ</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-indigo-50 text-indigo-800 text-[11px] uppercase tracking-tighter">Điểm tổ hợp</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">Điểm UT<br>gốc</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">Điểm UT<br>QĐ</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-indigo-50 text-indigo-800 text-[11px]">Điểm xét<br>tuyển</th>
                        
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">ĐK học lực</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">ĐK Ngưỡng</th>
                        <th rowspan="2" class="py-2 px-3 border border-slate-200 font-bold bg-slate-100 min-w-[80px]">Kết quả<br>xét tuyển</th>
                        <th rowspan="2" class="py-2 px-3 border border-slate-200 font-bold bg-slate-100 min-w-[90px]">KQ lọc ảo</th>
                    </tr>
                    <tr class="text-slate-500 uppercase tracking-wider text-[9px] text-center bg-slate-50">
                        <th class="py-1 px-2 border border-slate-200 bg-blue-50/50">TH1</th>
                        <th class="py-1 px-2 border border-slate-200 bg-blue-50/50">TH2</th>
                        <th class="py-1 px-2 border border-slate-200 bg-blue-50/50">TH3</th>
                        <th class="py-1 px-2 border border-slate-200 bg-blue-50/50">TH4</th>
                        <th class="py-1 px-2 border border-slate-200 bg-emerald-50/50">TH1</th>
                        <th class="py-1 px-2 border border-slate-200 bg-emerald-50/50">TH2</th>
                        <th class="py-1 px-2 border border-slate-200 bg-emerald-50/50">TH3</th>
                        <th class="py-1 px-2 border border-slate-200 bg-emerald-50/50">TH4</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 divide-y divide-slate-100 bg-white text-[11px] font-medium">
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- TAB: STATS (Thống kê trúng tuyển) -->
    <div x-show="activeTab === 'stats'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" style="display: none;" class="space-y-6 flex-1 overflow-auto custom-scrollbar relative">
        <!-- Local Loading Overlay -->
        <div x-cloak x-show="isStatsLoading" class="absolute inset-0 bg-slate-50/60 backdrop-blur-[2px] z-50 flex items-center justify-center">
            <div class="bg-white/80 border border-slate-200 shadow-xl rounded-2xl p-6 flex flex-col items-center max-w-xs">
                <i class="fas fa-circle-notch fa-spin text-indigo-600 text-3xl mb-3"></i>
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Đang tải thống kê</span>
                <span class="text-[10px] text-slate-400 mt-1">Vui lòng đợi trong giây lát...</span>
            </div>
        </div>
        <!-- Stats Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <!-- Card 1: Total Candidates -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group">
                <div class="absolute -right-3 -top-3 w-16 h-16 bg-blue-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Thí sinh</p>
                <h3 class="text-2xl font-black text-slate-800" x-text="statsData.stats ? parseFloat(statsData.stats.total_candidates).toLocaleString() : '0'"></h3>
                <span class="text-[10px] text-blue-500 font-bold" x-text="statsData.stats ? parseFloat(statsData.stats.total_wishes).toLocaleString() + ' nguyện vọng' : '0 nguyện vọng'"></span>
            </div>

            <!-- Card 2: Admitted -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-emerald-100 relative overflow-hidden group">
                <div class="absolute -right-3 -top-3 w-16 h-16 bg-emerald-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
                <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-2">Trúng tuyển dự kiến</p>
                <h3 class="text-2xl font-black text-emerald-700" x-text="statsData.stats ? parseFloat(statsData.stats.total_admitted).toLocaleString() : '0'"></h3>
                <span class="text-[10px] text-emerald-500 font-bold" x-text="statsData.stats && statsData.stats.total_candidates > 0 ? (Math.round((statsData.stats.total_admitted / statsData.stats.total_candidates) * 1000) / 10) + '% tỉ lệ đạt' : '0% tỉ lệ đạt'"></span>
            </div>

            <!-- Card 3: NV1 Admitted -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-indigo-100 relative overflow-hidden group">
                <div class="absolute -right-3 -top-3 w-16 h-16 bg-indigo-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
                <p class="text-[9px] font-black text-indigo-600 uppercase tracking-widest mb-2">Trúng tuyển ở NV1</p>
                <h3 class="text-2xl font-black text-indigo-700" x-text="statsData.stats ? parseFloat(statsData.stats.nv1_admit).toLocaleString() : '0'"></h3>
                <span class="text-[10px] text-indigo-500 font-bold" x-text="statsData.stats && statsData.stats.total_admitted > 0 ? (Math.round((statsData.stats.nv1_admit / statsData.stats.total_admitted) * 1000) / 10) + '% tổng trúng tuyển' : '0% tổng trúng tuyển'"></span>
            </div>
            
            <!-- Card 4: NV2 Admitted -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-amber-100 relative overflow-hidden group">
                <div class="absolute -right-3 -top-3 w-16 h-16 bg-amber-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
                <p class="text-[9px] font-black text-amber-600 uppercase tracking-widest mb-2">Trúng tuyển ở NV2</p>
                <h3 class="text-2xl font-black text-amber-700" x-text="statsData.stats ? parseFloat(statsData.stats.nv2_admit).toLocaleString() : '0'"></h3>
                <span class="text-[10px] text-amber-500 font-bold" x-text="statsData.stats && statsData.stats.total_admitted > 0 ? (Math.round((statsData.stats.nv2_admit / statsData.stats.total_admitted) * 1000) / 10) + '% tổng trúng tuyển' : '0% tổng trúng tuyển'"></span>
            </div>
            
            <!-- Card 5: Admitted at remaining wishes -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-rose-100 relative overflow-hidden group">
                <div class="absolute -right-3 -top-3 w-16 h-16 bg-rose-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
                <p class="text-[9px] font-black text-rose-600 uppercase tracking-widest mb-2">Trúng tuyển ở NV còn lại</p>
                <h3 class="text-2xl font-black text-rose-700" x-text="statsData.stats ? parseFloat(statsData.stats.total_admitted - statsData.stats.nv1_admit - statsData.stats.nv2_admit).toLocaleString() : '0'"></h3>
                <span class="text-[10px] text-rose-500 font-bold" x-text="statsData.stats && statsData.stats.total_admitted > 0 ? (Math.round(((statsData.stats.total_admitted - statsData.stats.nv1_admit - statsData.stats.nv2_admit) / statsData.stats.total_admitted) * 1000) / 10) + '% tổng trúng tuyển' : '0% tổng trúng tuyển'"></span>
            </div>
        </div>

        <!-- Table of Major stats -->
        <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200">
            <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs lg:text-sm flex items-center mb-0">
                    <span class="w-1.5 h-4 bg-emerald-500 rounded-full mr-2"></span>
                    Thống kê kết quả trúng tuyển dự kiến theo ngành
                </h3>
                <button @click="exportStats()" 
                        style="background-color: #10b981;"
                        class="text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-1.5 hover:opacity-90 disabled:opacity-50 disabled:pointer-events-none"
                        :disabled="isReadOnly || !selectedSession">
                    <i class="fas fa-file-excel"></i>
                    <span>Xuất Excel Thống kê</span>
                </button>
            </div>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="premium-table min-w-[800px] lg:min-w-full">
                    <thead>
                        <tr>
                            <th style="width: 80px" class="text-center" rowspan="2">Mã ngành</th>
                            <th rowspan="2">Tên ngành</th>
                            <th style="width: 80px" class="text-center" rowspan="2">Chỉ tiêu</th>
                            <th style="width: 90px" class="text-center" rowspan="2">Điểm chuẩn</th>
                            <th class="text-center" colspan="3">Trúng tuyển dự kiến</th>
                            <th class="text-center bg-purple-50 text-purple-800 font-bold" colspan="2" style="border-bottom: 2px solid #8b5cf6;">Dự kiến sau lọc ảo Bộ</th>
                            <th style="width: 150px" class="text-center" rowspan="2">Mức điểm (Thấp-Cao)</th>
                        </tr>
                        <tr>
                            <th style="width: 80px" class="text-center">Tổng</th>
                            <th style="width: 80px" class="text-center">NV1</th>
                            <th style="width: 100px" class="text-center">Tiến độ (%)</th>
                            <th style="width: 80px" class="text-center bg-purple-50 text-purple-800 font-bold">Tổng</th>
                            <th style="width: 120px" class="text-center bg-purple-50 text-purple-800 font-bold">Tiến độ (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="ms in statsData.majorStats" :key="ms.ma_nganh">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="text-center font-mono text-slate-500 font-bold" x-text="ms.ma_nganh"></td>
                                <td class="font-bold text-slate-800" x-text="ms.ten_nganh"></td>
                                <td class="text-center font-bold text-slate-600 bg-slate-50/50" x-text="ms.chi_tieu || '-'"></td>
                                <td class="text-center font-bold text-amber-700 bg-amber-50/20" x-text="ms.diem_chuan && parseFloat(ms.diem_chuan) > 0 ? parseFloat(ms.diem_chuan).toFixed(3) : '-'"></td>
                                <td class="text-center font-black text-indigo-600" x-text="ms.so_trung_tuyen || '-'"></td>
                                <td class="text-center font-bold text-slate-500" x-text="ms.nv1_admit || '-'"></td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full" 
                                                 :class="(ms.chi_tieu > 0 ? (ms.so_trung_tuyen / ms.chi_tieu) * 100 : 0) > 200 ? 'bg-rose-500' : 'bg-emerald-500'"
                                                 :style="'width: ' + Math.min(ms.chi_tieu > 0 ? (ms.so_trung_tuyen / ms.chi_tieu) * 100 : 0, 100) + '%'"></div>
                                        </div>
                                        <span class="text-[10px] font-black w-8 text-right" 
                                              :class="(ms.chi_tieu > 0 ? (ms.so_trung_tuyen / ms.chi_tieu) * 100 : 0) > 200 ? 'text-rose-600' : 'text-emerald-600'"
                                              x-text="ms.chi_tieu > 0 ? Math.round((ms.so_trung_tuyen / ms.chi_tieu) * 1000) / 10 + '%' : '0%'"></span>
                                    </div>
                                </td>
                                <!-- DỰ KIẾN SAU LỌC ẢO BỘ (TỔNG & TIẾN ĐỘ %) -->
                                <td class="text-center font-black text-purple-700 bg-purple-50/20" x-text="ms.so_luong_do_bo || '0'"></td>
                                <td class="bg-purple-50/10">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full" 
                                                 :class="(ms.chi_tieu > 0 ? (ms.so_luong_do_bo / ms.chi_tieu) * 100 : 0) < 80 ? 'bg-amber-500' : ((ms.chi_tieu > 0 ? (ms.so_luong_do_bo / ms.chi_tieu) * 100 : 0) <= 115 ? 'bg-emerald-500' : 'bg-rose-500')"
                                                 :style="'width: ' + Math.min(ms.chi_tieu > 0 ? (ms.so_luong_do_bo / ms.chi_tieu) * 100 : 0, 100) + '%'"></div>
                                        </div>
                                        <span class="text-[10px] font-black w-8 text-right" 
                                              :class="(ms.chi_tieu > 0 ? (ms.so_luong_do_bo / ms.chi_tieu) * 100 : 0) < 80 ? 'text-amber-600' : ((ms.chi_tieu > 0 ? (ms.so_luong_do_bo / ms.chi_tieu) * 100 : 0) <= 115 ? 'text-emerald-600' : 'text-rose-600')"
                                              x-text="ms.chi_tieu > 0 ? Math.round((ms.so_luong_do_bo / ms.chi_tieu) * 1000) / 10 + '%' : '0%'"></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <template x-if="ms.diem_thap_nhat && parseFloat(ms.diem_thap_nhat) > 0">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-slate-50 border border-slate-100 text-[10px] font-bold text-slate-600">
                                            <span class="text-rose-500" x-text="parseFloat(ms.diem_thap_nhat).toFixed(3)"></span>
                                            <span class="text-slate-300">-</span>
                                            <span class="text-emerald-600" x-text="parseFloat(ms.diem_cao_nhat).toFixed(3)"></span>
                                        </span>
                                    </template>
                                    <template x-if="!ms.diem_thap_nhat || parseFloat(ms.diem_thap_nhat) <= 0">
                                        <span class="text-slate-300">-</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-slate-50 font-bold text-slate-800 border-t-2 border-slate-200">
                        <tr>
                            <td colspan="2" class="text-right uppercase">Tổng cộng:</td>
                            <td class="text-center bg-slate-100/50 text-slate-700" x-text="totalStatsSum().chi_tieu"></td>
                            <td class="bg-slate-50"></td>
                            <td class="text-center text-indigo-700" x-text="totalStatsSum().so_trung_tuyen"></td>
                            <td class="text-center text-slate-700" x-text="totalStatsSum().nv1_admit"></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full" 
                                             :class="totalStatsSum().pct > 200 ? 'bg-rose-500' : 'bg-emerald-500'"
                                             :style="'width: ' + Math.min(totalStatsSum().pct, 100) + '%'"></div>
                                    </div>
                                    <span class="text-[10px] font-black w-8 text-right" 
                                          :class="totalStatsSum().pct > 200 ? 'text-rose-600' : 'text-emerald-600'"
                                          x-text="totalStatsSum().pct + '%'"></span>
                                </div>
                            </td>
                            <!-- DỰ KIẾN SAU LỌC ẢO BỘ TOTALS -->
                            <td class="text-center text-purple-800 bg-purple-50/30 font-black" x-text="totalStatsSum().so_luong_do_bo"></td>
                            <td class="bg-purple-50/20">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full" 
                                             :class="totalStatsSum().pct_bo < 80 ? 'bg-amber-500' : (totalStatsSum().pct_bo <= 115 ? 'bg-emerald-500' : 'bg-rose-500')"
                                             :style="'width: ' + Math.min(totalStatsSum().pct_bo, 100) + '%'"></div>
                                    </div>
                                    <span class="text-[10px] font-black w-8 text-right" 
                                          :class="totalStatsSum().pct_bo < 80 ? 'text-amber-600' : (totalStatsSum().pct_bo <= 115 ? 'text-emerald-600' : 'text-rose-600')"
                                          x-text="totalStatsSum().pct_bo + '%'"></span>
                                </div>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div> <!-- END TAB STATS -->

    <!-- TAB: CHARTS (Biểu đồ phân tích) -->
    <div x-show="activeTab === 'charts'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" style="display: none;" class="space-y-6 flex-1 overflow-auto custom-scrollbar relative">
        <!-- Local Loading Overlay -->
        <div x-cloak x-show="isStatsLoading" class="absolute inset-0 bg-slate-50/60 backdrop-blur-[2px] z-50 flex items-center justify-center">
            <div class="bg-white/80 border border-slate-200 shadow-xl rounded-2xl p-6 flex flex-col items-center max-w-xs">
                <i class="fas fa-circle-notch fa-spin text-indigo-600 text-3xl mb-3"></i>
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Đang phân tích biểu đồ</span>
                <span class="text-[10px] text-slate-400 mt-1">Vui lòng đợi trong giây lát...</span>
            </div>
        </div>
        <!-- Export Button for Chart Data -->
        <div class="flex justify-between items-center gap-2 mb-2 bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div>
                <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Dữ liệu thô phân tích số liệu biểu đồ</h4>
                <p class="text-[10px] text-slate-500 mt-0.5">Tải file Excel gồm 7 Sheet chứa số liệu thống kê chi tiết của từng biểu đồ bên dưới để tùy biến báo cáo.</p>
            </div>
            <button @click="exportChartData()" 
                    style="background-color: #7c3aed;"
                    class="text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm transition-all active:scale-95 flex items-center gap-1.5 hover:opacity-90 hover:scale-[1.02] disabled:opacity-50 disabled:pointer-events-none"
                    :disabled="isReadOnly || !selectedSession">
                <i class="fas fa-file-excel"></i>
                <span>Xuất Excel Biểu đồ</span>
            </button>
        </div>
        <!-- Row 1: Full-width Major fill chart -->
        <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-emerald-500">
            <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                Biểu đồ tỷ lệ lấp đầy chuyên ngành (Đầy đủ các ngành xét tuyển)
            </h3>
            <div class="relative h-96">
                <canvas id="majorFillChart"></canvas>
            </div>
        </div>

        <!-- Row 2: Four statistics charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
            <!-- Chart: Tỷ lệ theo nguyện vọng -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-indigo-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Phân bố trúng tuyển theo NV
                </h3>
                <div class="relative h-64">
                    <canvas id="nvChart"></canvas>
                </div>
            </div>

            <!-- Chart: Giới tính -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-pink-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Phân bố Giới tính
                </h3>
                <div class="relative h-64">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>

            <!-- Chart: Khu vực ưu tiên -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-sky-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Khu vực ưu tiên
                </h3>
                <div class="relative h-64">
                    <canvas id="areaChart"></canvas>
                </div>
            </div>

            <!-- Chart: Đối tượng ưu tiên -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-amber-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Đối tượng ưu tiên
                </h3>
                <div class="relative h-64">
                    <canvas id="objectChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 mt-6">
            <!-- Chart: Theo tỉnh -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-purple-500">
                <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs flex items-center mb-6">
                    Tỉnh / Thành phố
                </h3>
                <div class="relative h-64">
                    <canvas id="provinceChart"></canvas>
                </div>
            </div>

            <!-- Chart: Trường THPT tại Phú Thọ -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-rose-500">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-slate-800 tracking-tight uppercase text-xs">
                        Trường THPT tại Phú Thọ
                    </h3>
                    <button @click="showAllSchools = !showAllSchools; chartsRendered = false; initCharts()" 
                            class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-bold transition cursor-pointer">
                        <span x-text="showAllSchools ? 'Thu gọn' : 'Xem thêm'"></span>
                    </button>
                </div>
                <div class="relative h-64">
                    <canvas id="schoolChart"></canvas>
                </div>
            </div>
        </div>
    </div> <!-- END TAB CHARTS -->
</div>

<script>
    function virtualAdmission() {
        return {
            isReadOnly: <?= json_encode($isReadOnly) ?>,
            selectedYear: '',
            selectedSession: '',
            activeTab: <?= $isReadOnly ? "'stats'" : "'list'" ?>,
            statsData: {
                stats: null,
                majorStats: [],
                chartDist: {}
            },
            chartsRendered: false,
            chartInstances: {},
            showAllSchools: false,
            allSessions: <?= json_encode($sessions) ?>,
            isLoading: false,
            isStatsLoading: false,
            isCalculating: false,
            isSyncing: false,
            isFiltering: false,
            showBgdUploadModal: false,
            bgdStatus: {
                imported: false,
                importing: false,
                tong_bo_gd: 0,
                bi_loai: 0,
                giu_lai: 0,
                lan_loc_ao: '',
                lan_import_cuoi: '',
                imported_by: '',
                selectedFileName: '',
                selectedFile: null,
                lastMessage: '',
                lastMessageType: 'success'
            },
            loadingMessage: 'Đang tải...',
            currentLoadingMessage: 'Đang tải bản ghi...',
            progress: 0,
            batchSize: 250, // Optimal for 15k scaling
            totalProcessed: 0,
            totalToProcess: 0,
            errorCount: 0,
            progressInterval: null,
            messageInterval: null,
            dt: null,
            combos: <?= json_encode($comboCols) ?>,
            
            statusMessages: [
                "Đang trích xuất dữ liệu hồ sơ...",
                "Đang tính toán lại điểm đa tổ hợp học bạ và thi cử...",
                "Đang phân tích tổ hợp môn tối ưu cho từng nguyện vọng...",
                "Đang tính điểm ưu tiên khu vực và đối tượng...",
                "Hệ thống đang kiểm tra điều kiện học lực ngưỡng đầu vào...",
                "Đang hoàn tất quá trình lưu trữ vào cơ sở dữ liệu...",
                "Quá trình này có thể tốn 1 vài phút, sắp xong rồi!"
            ],
            
            startLoading(msg, isRealProgress = false, skipMessageRotation = false) {
                this.isLoading = true;
                this.loadingMessage = msg;
                this.currentLoadingMessage = msg;
                this.progress = 0;
                
                // Start status message rotation chỉ khi KHÔNG ĐƯỢC skips
                if (!skipMessageRotation) {
                    let msgIdx = 0;
                    this.messageInterval = setInterval(() => {
                        msgIdx = (msgIdx + 1) % this.statusMessages.length;
                        this.currentLoadingMessage = this.statusMessages[msgIdx];
                    }, 4000);
                }

                if (isRealProgress) return;

                // Start a fake progress animation (only for non-chunked tasks)
                this.progressInterval = setInterval(() => {
                    if (this.progress < 95) {
                        this.progress += Math.random() * 5;
                        if (this.progress > 95) this.progress = 95;
                        this.progress = Math.round(this.progress * 10) / 10;
                    }
                }, 1500);
            },

            
            stopLoading() {
                this.progress = 100;
                setTimeout(() => {
                    this.isLoading = false;
                    this.isCalculating = false;
                    this.isSyncing = false;
                    this.isFiltering = false;
                    clearInterval(this.progressInterval);
                    clearInterval(this.messageInterval);
                }, 500);
            },

            get filteredSessions() {
                if (!this.selectedYear) return this.allSessions;
                return this.allSessions.filter(s => s.nam_tuyen_sinh == this.selectedYear);
            },

            init() {
                if (!this.isReadOnly) {
                    this.initDataTable();
                }
                
                // TỰ ĐỘNG CHỌN ĐỢT TUYỂN SINH ĐANG KÍCH HOẠT ĐỂ TIẾT KIỆM THỜI GIAN CO USER
                let activeSession = this.allSessions.find(s => s.kich_hoat == 1 || s.kich_hoat === true || s.kich_hoat === 't');
                if (!activeSession && this.allSessions.length > 0) {
                    activeSession = this.allSessions[0]; // Dự phòng nếu không có đợt nào kích hoạt, chọn đợt mới nhất
                }
                
                if (activeSession) {
                    this.selectedYear = activeSession.nam_tuyen_sinh;
                    // Đợi Alpine cập nhật DOM cho select session trước khi gán
                    setTimeout(() => {
                        this.selectedSession = activeSession.id;
                        this.loadData();
                        this.fetchBGDStatus();
                    }, 50);
                }
            },

            initDataTable() {
                var self = this;
                
                // Hàm helper lấy danh sách tên tổ hợp chuẩn của thí sinh (đã sort)
                function getComboNames(rowData) {
                    // Ưu tiên dùng danh sách đầy đủ từ máy chủ để hiển thị đủ 4 tổ hợp
                    if (rowData && rowData.all_combos) {
                        return rowData.all_combos.split(', ').map(s => s.trim()).sort();
                    }

                    if (!rowData || !rowData.chi_tiet_diem) return [];
                    try {
                        let p = JSON.parse(rowData.chi_tiet_diem);
                        let combs = p.all_combinations || {};
                        let names = [];
                        for (let k in combs) {
                            let nm = k.replace('THPT_', '').replace('HB_', '');
                            if(names.indexOf(nm) === -1) names.push(nm);
                        }
                        names.sort();
                        return names;
                    } catch(e) { return []; }
                }

                // Build dynamic columns
                var columns = [
                    { data: 'so_cccd', className: 'text-center font-medium sticky left-0 bg-white shadow-[1px_0_0_#e2e8f0] z-10' },
                    { data: 'ho_va_ten', className: 'text-left font-semibold sticky left-[120px] bg-white shadow-[1px_0_0_#e2e8f0] z-10 min-w-[150px] truncate max-w-[200px]' },
                    { data: 'ma_nganh', className: 'text-center' },
                    { data: 'thu_tu_nguyen_vong', className: 'text-center font-bold' },
                    { 
                        data: null, 
                        className: 'text-center text-[10px] whitespace-normal break-words max-w-[100px]',
                        render: function(data, type, row) {
                            let names = getComboNames(row);
                            return names.length ? names.join(', ') : '-';
                        }
                    }
                ];

                // PT100 TH1-TH4
                [0,1,2,3].forEach(i => {
                    columns.push({
                        data: null,
                        className: 'text-center',
                        render: function(data, type, row) {
                            let names = getComboNames(row);
                            if (names.length <= i) return '-';
                            let targetCombo = names[i];
                            
                            try {
                                let p = JSON.parse(row.chi_tiet_diem);
                                let combs = p.all_combinations || {};
                                let val = combs['THPT_' + targetCombo];
                                if (val === undefined || val === null) return '-';
                                let r = parseFloat(val);
                                let prioRaw = parseFloat(p.priority_raw) || 0;
                                let convertedP = prioRaw;
                                if (r >= 22.5) {
                                    convertedP = ((30 - r) / 7.5) * prioRaw;
                                }
                                return (r + convertedP).toFixed(3);
                            } catch(e) { return '-'; }
                        }
                    });
                });

                 // PT200 TH1-TH4
                 [0,1,2,3].forEach(i => {
                     columns.push({
                         data: null,
                         className: 'text-center',
                         render: function(data, type, row) {
                             let names = getComboNames(row);
                             if (names.length <= i) return '-';
                             let targetCombo = names[i];
                             
                             try {
                                 let p = JSON.parse(row.chi_tiet_diem);
                                 let combs = p.all_combinations || {};
                                 let val = combs['HB_' + targetCombo];
                                 if (val === undefined || val === null) return '-';
                                 let r = parseFloat(val);
                                 let prioRaw = parseFloat(p.priority_raw) || 0;
                                 let convertedP = prioRaw;
                                 if (r >= 22.5) {
                                     convertedP = ((30 - r) / 7.5) * prioRaw;
                                 }
                                 return (r + convertedP).toFixed(3);
                             } catch(e) { return '-'; }
                         }
                     });
                 });

                // To Hop Max, PT Max
                columns.push({ 
                    data: 'to_hop_toi_uu', 
                    className: 'text-center font-bold',
                    render: function(data) {
                        if (!data) return '-';
                        let c = self.combos.find(x => x.ma === data);
                        return c ? `${data} (${c.desc})` : data;
                    }
                });
                columns.push({ 
                    data: 'phuong_thuc_toi_uu', 
                    className: 'text-center font-bold',
                    render: function(data) {
                        const labels = {
                            '100': 'TS01',
                            '200': 'TS02',
                            'TS01': 'TS01',
                            'TS02': 'TS02',
                            'TS03': 'TS03',
                            'TS04': 'TS04',
                            'TS05': 'TS05'
                        };
                        let code = labels[data] || data || '-';
                        let title = '';
                        if(code === 'TS03') title = 'Xét Chứng chỉ QT';
                        if(code === 'TS04') title = 'THPT + Năng khiếu';
                        if(code === 'TS05') title = 'Học bạ + Năng khiếu';
                        return `<span title="${title}">${code}</span>`;
                    }
                });

                // M1, M2, M3 - hiển thị điểm đã quy đổi (×0.95) từ chi_tiet_diem
                // Helper: lấy tổng base_scaled của môn theo thứ tự (mon_1/2/3)
                function getScaledMonScore(chiTietDiem, monKey) {
                    try {
                        let p = JSON.parse(chiTietDiem);
                        let idx = parseInt(monKey.replace('mon_', '')) - 1;
                        if (p.subjects && p.subjects[idx]) {
                            // Ưu tiên hiển thị điểm "final" nếu có chứng chỉ, nếu không thì hiện base_scaled (đã quy đổi x0.95)
                            // "final" ở đây là điểm cao nhất giữa chứng chỉ và học bạ đã quy đổi
                            let s = p.subjects[idx].final !== undefined ? p.subjects[idx].final : p.subjects[idx].base_scaled;
                            return s !== undefined && s !== null ? parseFloat(s).toFixed(3) : '-';
                        }
                        return '-';
                    } catch(e) { return '-'; }
                }

                columns.push({ data: 'chi_tiet_diem', className: 'text-center', render: function(data) { return getScaledMonScore(data, 'mon_1'); } });
                columns.push({ data: 'chi_tiet_diem', className: 'text-center', render: function(data) { return getScaledMonScore(data, 'mon_2'); } });
                columns.push({ data: 'chi_tiet_diem', className: 'text-center', render: function(data) { return getScaledMonScore(data, 'mon_3'); } });
                
                // Điểm Tổ Hợp = tổng 3 base_scaled (= diem_xet_tuyen trước khi cộng UT)
                columns.push({ 
                    data: 'chi_tiet_diem', 
                    className: 'text-center bg-indigo-50 font-bold text-indigo-700',
                    render: function(data) {
                        try {
                            let p = JSON.parse(data);
                            return p.total_raw ? parseFloat(p.total_raw).toFixed(3) : '-';
                        } catch(e) { return '-'; }
                    }
                });

                // UT Goc, UT QD (bỏ cột Điểm QĐ)
                columns.push({ data: 'chi_tiet_diem', className: 'text-center', render: function(data) {
                    try { let p = JSON.parse(data); return p.priority_raw !== undefined ? parseFloat(p.priority_raw).toFixed(3) : '-'; } catch(e){return '-'}
                }});
                columns.push({ data: 'chi_tiet_diem', className: 'text-center', render: function(data) {
                    try { let p = JSON.parse(data); return p.priority_converted !== undefined ? parseFloat(p.priority_converted).toFixed(3) : '-'; } catch(e){return '-'}
                }});

                // Final Score
                columns.push({ 
                    data: 'diem_xet_tuyen', 
                    className: 'text-center font-bold',
                    render: function(data) { return data > 0 ? parseFloat(data).toFixed(3) : '-'; }
                });

                // DK Hoc Luc
                columns.push({ 
                    data: 'chi_tiet_diem', 
                    className: 'text-center text-[10px]',
                    render: function(data) {
                        try {
                            let p = JSON.parse(data);
                            let note = (p.threshold_note || "").toUpperCase();
                            if(note.indexOf('HỌC LỰC') !== -1) {
                                return `<span class="text-red-600 font-bold" title="${p.threshold_note}">K.ĐẠT</span>`;
                            }
                            if (!p.combo_code) {
                                return `<span class="text-red-600 font-bold" title="Không có tổ hợp môn xét tuyển hợp lệ">K.ĐẠT</span>`;
                            }
                            return '<span class="text-green-600 font-bold">ĐẠT</span>';
                        } catch(e) { return '-'; }
                    }
                });

                // DK Nguong
                columns.push({ 
                    data: 'chi_tiet_diem', 
                    className: 'text-center text-[10px]',
                    render: function(data) {
                        try {
                            let p = JSON.parse(data);
                            let note = (p.threshold_note || "").toUpperCase();
                            if (note.indexOf('NGƯỠNG') !== -1 || note.indexOf('THẤP HƠN') !== -1 || note.indexOf('DƯỚI ĐIỂM SÀN') !== -1) {
                                return `<span class="text-red-600 font-bold" title="${p.threshold_note}">K.ĐẠT</span>`;
                            }
                            if (p.trang_thai_do === false || p.trang_thai_do === 0) {
                                return `<span class="text-red-600 font-bold" title="${p.threshold_note || 'Không đạt ngưỡng tuyển sinh'}">K.ĐẠT</span>`;
                            }
                            if (!p.combo_code) {
                                return `<span class="text-red-600 font-bold" title="Không có tổ hợp môn xét tuyển hợp lệ">K.ĐẠT</span>`;
                            }
                            return '<span class="text-green-600 font-bold">ĐẠT</span>';
                        } catch(e) { return '-'; }
                    }
                });

                // Kết quả xét tuyển
                columns.push({
                    data: 'trang_thai_trung_tuyen',
                    className: 'text-center uppercase text-[10px] font-bold',
                    render: function(data) {
                        if (data == 1 || data === true || data === '1') {
                            return '<span class="text-emerald-700 bg-emerald-50 px-2 py-1 rounded">Đỗ</span>';
                        }
                        return '<span class="text-rose-600 bg-rose-50 px-2 py-1 rounded">Không đạt</span>';
                    }
                });

                // KQ lọc ảo
                columns.push({
                    data: 'ket_qua_bo_gd',
                    className: 'text-center uppercase text-[10px] font-bold',
                    render: function(data, type, row) {
                        // Nếu Kết quả xét tuyển nội bộ không phải Đỗ (không gửi lên Bộ lọc ảo) thì không hiện trạng thái
                        if (row.trang_thai_trung_tuyen != 1 && row.trang_thai_trung_tuyen !== true && row.trang_thai_trung_tuyen !== '1') {
                            return '-';
                        }
                        if (!data) {
                            return '<span class="text-slate-400 bg-slate-100 px-2 py-1 rounded">Chưa có</span>';
                        }
                        if (data === 'Đỗ') {
                            return '<span class="text-emerald-700 bg-emerald-50 px-2 py-1 rounded">Đỗ</span>';
                        }
                        if (data === 'Trượt') {
                            let title = '';
                            if (row.ma_truong_trung_tuyen_bgd === 'DKS' && row.ttnv_do_bo) {
                                title = `Trượt nguyện vọng này do đỗ nguyện vọng cao hơn tại HVU (Nguyện vọng ${row.ttnv_do_bo})`;
                                return `<span class="text-rose-600 bg-rose-50 px-2 py-1 rounded cursor-help" title="${title}">Trượt (Đỗ NV ${row.ttnv_do_bo})</span>`;
                            }
                            if (row.bi_loai_truong_khac && row.ma_truong_trung_tuyen_bo) {
                                title = `Trúng tuyển trường khác: ${row.ma_truong_trung_tuyen_bo}`;
                                return `<span class="text-rose-600 bg-rose-50 px-2 py-1 rounded cursor-help" title="${title}">Trượt (${row.ma_truong_trung_tuyen_bo})</span>`;
                            }
                            return '<span class="text-rose-600 bg-rose-50 px-2 py-1 rounded">Trượt</span>';
                        }
                        return `<span class="text-slate-600 bg-slate-100 px-2 py-1 rounded">${data}</span>`;
                    }
                });

                this.dt = $('#virtualGrid').DataTable({
                    serverSide: true,
                    processing: true,
                    deferLoading: 0, // Không tự động kéo data nếu chưa nhận session_id
                    ajax: {
                        url: '<?= url("/admin/api/vf/load") ?>',
                        type: 'POST',
                        data: function(d) {
                            d.session_id = self.selectedSession || 0;
                            d._csrf_token = '<?= csrf_token() ?>';
                        },
                        dataSrc: function (json) {
                            // Cập nhật Số lượng trên UI khi kéo xong trang
                            document.getElementById('candidateCount').textContent = (json.candidate_count || 0).toLocaleString();
                            document.getElementById('rowCount').textContent = (json.recordsTotal || 0).toLocaleString();
                            
                            // Hiển thị tổng hồ sơ đã duyệt và cảnh báo TS chưa có NV
                            if (json.total_approved_hoso) {
                                document.getElementById('totalApprovedHoso').textContent = (json.total_approved_hoso).toLocaleString();
                            }
                            const noNv = json.no_aspiration_count || 0;
                            const alertEl = document.getElementById('noAspirationAlert');
                            if (noNv > 0) {
                                document.getElementById('noAspirationCount').textContent = noNv.toLocaleString();
                                alertEl.classList.remove('hidden');
                                alertEl.classList.add('flex');
                            } else {
                                alertEl.classList.add('hidden');
                                alertEl.classList.remove('flex');
                            }
                            return json.data;
                        },
                        error: (xhr) => {
                            self.isLoading = false;
                            let msg = "Lỗi tải dữ liệu. Hãy F5 tải lại trang.";
                            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                            toast.error(msg);
                        }
                    },
                    columns: columns,
                    scrollX: true,
                    scrollY: 'calc(100vh - 350px)',
                    scrollCollapse: true,
                    paging: true,
                    pageLength: 50,
                    lengthMenu: [50, 100, 200, 500],
                    language: {
                        search: 'Tìm thẻ (CCCD, Họ tên, Mã ngành, Tổ hợp):',
                        lengthMenu: 'Hiển thị _MENU_ dòng',
                        info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ dòng',
                        infoEmpty: 'Không có dữ liệu phù hợp với phân loại',
                        infoFiltered: '(lọc từ _MAX_ dòng gốc)',
                        zeroRecords: 'Không có bản ghi phù hợp',
                        paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' },
                        processing: '<div class="absolute inset-0 z-50 flex items-center justify-center bg-white bg-opacity-70"><div class="flex flex-col items-center"><div class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div><div class="mt-2 text-xs font-semibold text-indigo-700">Đang tải...</div></div></div>'
                    },
                    dom: '<"flex justify-between items-center p-3 border-b border-slate-200"lf>rt<"flex justify-between items-center p-3"ip>',
                    fixedColumns: {
                        leftColumns: 2
                    },
                    initComplete: function() {
                         $('.dataTables_filter input').addClass('w-full sm:w-80 pl-3 pr-4 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder-slate-400').attr('placeholder', 'Nhập bất kỳ từ khóa...');
                    }
                });
            },

            loadData() {
                // Clear active stats and force re-render
                this.statsData = { stats: null, majorStats: [], chartDist: {} };
                this.chartsRendered = false;
                
                if (this.selectedSession) {
                    if (this.activeTab === 'stats') {
                        this.fetchStats(false);
                    } else if (this.activeTab === 'charts') {
                        this.fetchStats(true).then(() => this.initCharts());
                    }
                }

                if (this.isReadOnly) {
                    return;
                }

                if (!this.selectedSession) {
                    if (this.dt) {
                        this.dt.clear().draw();
                    }
                    if (document.getElementById('candidateCount')) document.getElementById('candidateCount').textContent = '0';
                    if (document.getElementById('rowCount')) document.getElementById('rowCount').textContent = '0';
                    return;
                }
                
                // Server-Side Processing gánh toàn bộ tải trọng, chỉ cần bắt Ajax gửi lại lệnh Reload mà ko tốn RAM của User
                if (this.dt) {
                    this.dt.ajax.reload(null, true);
                }
            },

            fetchStats(includeDemo = false) {
                if (!this.selectedSession) return Promise.resolve();
                
                // If stats are already loaded and either we don't need demo or demo is already loaded, resolve
                if (this.statsData.stats && (!includeDemo || Object.keys(this.statsData.chartDist.gender || {}).length > 0)) {
                    return Promise.resolve();
                }
                
                this.isStatsLoading = true;
                return new Promise((resolve, reject) => {
                    $.ajax({
                        url: '<?= url("/admin/api/vf/stats") ?>',
                        data: { 
                            session_id: this.selectedSession,
                            include_demo: includeDemo ? 1 : 0
                        },
                        success: (res) => {
                            if (res.success) {
                                this.statsData = res;
                                resolve();
                            } else {
                                toast.error(res.message);
                                reject();
                            }
                        },
                        error: (err) => {
                            toast.error("Lỗi khi tải thống kê");
                            reject();
                        },
                        complete: () => {
                            this.isStatsLoading = false;
                        }
                    });
                });
            },

            totalStatsSum() {
                let ct = 0, tt = 0, nv1 = 0, do_bo = 0;
                if (this.statsData && this.statsData.majorStats) {
                    this.statsData.majorStats.forEach(ms => {
                        ct += parseInt(ms.chi_tieu || 0);
                        tt += parseInt(ms.so_trung_tuyen || 0);
                        nv1 += parseInt(ms.nv1_admit || 0);
                        do_bo += parseInt(ms.so_luong_do_bo || 0);
                    });
                }
                let pct = ct > 0 ? Math.round((tt / ct) * 1000) / 10 : 0;
                let pct_bo = ct > 0 ? Math.round((do_bo / ct) * 1000) / 10 : 0;
                return { 
                    chi_tieu: ct, 
                    so_trung_tuyen: tt, 
                    nv1_admit: nv1, 
                    so_luong_do_bo: do_bo, 
                    pct: pct, 
                    pct_bo: pct_bo 
                };
            },

            initCharts() {
                this.$nextTick(() => {
                    if (this.chartsRendered || typeof Chart === 'undefined') return;
                    
                    // Destroy any previous chart instances to avoid canvas reuse warning
                    Object.values(this.chartInstances).forEach(inst => { if(inst) inst.destroy(); });
                    this.chartInstances = {};

                    // Define custom inline plugin for data labels
                    const customDatalabelsPlugin = {
                        id: 'customDatalabels',
                        afterDraw: (chart) => {
                            const ctx = chart.ctx;
                            chart.data.datasets.forEach((dataset, i) => {
                                const meta = chart.getDatasetMeta(i);
                                if (meta.hidden) return;
                                
                                const total = dataset.data.reduce((sum, val) => sum + parseFloat(val || 0), 0);
                                if (total === 0) return;

                                meta.data.forEach((element, index) => {
                                    const value = dataset.data[index];
                                    if (!value || value <= 0) return;
                                    
                                    const percent = Math.round((value / total) * 100);
                                    const midAngle = element.startAngle + (element.endAngle - element.startAngle) / 2;
                                    const radius = element.innerRadius + (element.outerRadius - element.innerRadius) / 2;
                                    
                                    const x = element.x + Math.cos(midAngle) * radius;
                                    const y = element.y + Math.sin(midAngle) * radius;
                                    
                                    ctx.save();
                                    ctx.shadowColor = 'rgba(0, 0, 0, 0.4)';
                                    ctx.shadowBlur = 3;
                                    ctx.fillStyle = '#ffffff';
                                    ctx.font = 'bold 9px sans-serif';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'middle';
                                    
                                    const angle = element.endAngle - element.startAngle;
                                    if (angle > 0.15) {
                                        ctx.fillText(`${value}`, x, y - 5);
                                        ctx.fillText(`${percent}%`, x, y + 5);
                                    }
                                    ctx.restore();
                                });
                            });
                        }
                    };

                    // 1. NGUYEN VONG CHART
                    const ctxNv = document.getElementById('nvChart');
                    if (ctxNv && this.statsData.stats) {
                        let other = Math.max(0, parseInt(this.statsData.stats.total_admitted || 0) - (parseInt(this.statsData.stats.nv1_admit || 0) + parseInt(this.statsData.stats.nv2_admit || 0)));
                        this.chartInstances.nv = new Chart(ctxNv.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: ['NV1', 'NV2', 'NV còn lại'],
                                datasets: [{
                                    data: [parseInt(this.statsData.stats.nv1_admit || 0), parseInt(this.statsData.stats.nv2_admit || 0), other],
                                    backgroundColor: ['#4f46e5', '#10b981', '#94a3b8'],
                                    borderWidth: 0,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '65%',
                                plugins: {
                                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
                                }
                            },
                            plugins: [customDatalabelsPlugin]
                        });
                    }

                    // 2. MAJOR FILL CHART
                    const ctxMajor = document.getElementById('majorFillChart');
                    if (ctxMajor && this.statsData.majorStats && this.statsData.majorStats.length) {
                        const sortedMajors = [...this.statsData.majorStats].sort((a,b) => {
                            return a.ma_nganh.localeCompare(b.ma_nganh);
                        });

                        const hasBgd = !!this.statsData.hasBgd;
                        const datasets = [];

                        if (hasBgd) {
                            datasets.push({
                                label: 'Số Trúng tuyển (Dự kiến)',
                                data: sortedMajors.map(m => m.so_trung_tuyen),
                                backgroundColor: '#60a5fa', // Light blue
                                borderRadius: 4,
                            });
                            datasets.push({
                                label: 'Đỗ Bộ GD&ĐT (Sau lọc ảo)',
                                data: sortedMajors.map(m => m.so_luong_do_bo),
                                backgroundColor: '#10b981', // Solid emerald green (final target)
                                borderRadius: 4,
                            });
                        } else {
                            datasets.push({
                                label: 'Trúng tuyển (Dự kiến)',
                                data: sortedMajors.map(m => m.so_trung_tuyen),
                                backgroundColor: '#10b981', // Solid emerald green (final target)
                                borderRadius: 4,
                            });
                        }

                        datasets.push({
                            label: 'Chỉ tiêu',
                            data: sortedMajors.map(m => m.chi_tieu),
                            backgroundColor: '#f59e0b', // Warm amber/gold
                            borderRadius: 4,
                        });

                        const majorDatalabelsPlugin = {
                            id: 'majorDatalabels',
                            afterDraw: (chart) => {
                                const ctx = chart.ctx;
                                // Label the final target dataset (index 1 if hasBgd, index 0 if not)
                                const targetIdx = hasBgd ? 1 : 0;
                                const dataset = chart.data.datasets[targetIdx];
                                if (!dataset) return;

                                const meta = chart.getDatasetMeta(targetIdx);
                                if (meta.hidden) return;

                                meta.data.forEach((element, index) => {
                                    const value = dataset.data[index];
                                    if (value === undefined || value === null || value <= 0) return;

                                    ctx.save();
                                    ctx.fillStyle = '#1e293b'; // Slate 800
                                    ctx.font = 'bold 9px sans-serif';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'bottom';
                                    ctx.fillText(value, element.x, element.y - 3);
                                    ctx.restore();
                                });
                            }
                        };
                        
                        this.chartInstances.major = new Chart(ctxMajor.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: sortedMajors.map(m => m.ma_nganh),
                                datasets: datasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            title: (ctx) => {
                                                const index = ctx[0].dataIndex;
                                                return sortedMajors[index].ten_nganh;
                                            }
                                        }
                                    },
                                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 10 } } }
                                },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 45, minRotation: 45 } },
                                    y: { beginAtZero: true, grace: '5%' }
                                }
                            },
                            plugins: [majorDatalabelsPlugin]
                        });
                    }

                    // 3. GENDER CHART
                    const ctxGender = document.getElementById('genderChart');
                    if (ctxGender && this.statsData.chartDist && this.statsData.chartDist.gender) {
                        this.chartInstances.gender = new Chart(ctxGender.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: Object.keys(this.statsData.chartDist.gender),
                                datasets: [{
                                    data: Object.values(this.statsData.chartDist.gender),
                                    backgroundColor: ['#ec4899', '#3b82f6', '#94a3b8'],
                                    borderWidth: 0,
                                }]
                            },
                            options: { 
                                responsive: true, 
                                maintainAspectRatio: false, 
                                cutout: '65%',
                                plugins: { 
                                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } 
                                } 
                            },
                            plugins: [customDatalabelsPlugin]
                        });
                    }

                    // 4. AREA CHART
                    const ctxArea = document.getElementById('areaChart');
                    if (ctxArea && this.statsData.chartDist && this.statsData.chartDist.area) {
                        this.chartInstances.area = new Chart(ctxArea.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: Object.keys(this.statsData.chartDist.area),
                                datasets: [{
                                    data: Object.values(this.statsData.chartDist.area),
                                    backgroundColor: ['#0ea5e9', '#f59e0b', '#8b5cf6', '#10b981', '#64748b', '#ec4899', '#14b8a6'],
                                    borderWidth: 0,
                                }]
                            },
                            options: { 
                                responsive: true, 
                                maintainAspectRatio: false, 
                                cutout: '65%', 
                                plugins: { 
                                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } 
                                } 
                            },
                            plugins: [customDatalabelsPlugin]
                        });
                    }

                    // 5. OBJECT CHART
                    const ctxObject = document.getElementById('objectChart');
                    if (ctxObject && this.statsData.chartDist && this.statsData.chartDist.object) {
                        this.chartInstances.object = new Chart(ctxObject.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: Object.keys(this.statsData.chartDist.object),
                                datasets: [{
                                    data: Object.values(this.statsData.chartDist.object),
                                    backgroundColor: ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#f97316', '#14b8a6'],
                                    borderWidth: 0,
                                }]
                            },
                            options: { 
                                responsive: true, 
                                maintainAspectRatio: false, 
                                cutout: '65%',
                                plugins: { 
                                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } 
                                } 
                            },
                            plugins: [customDatalabelsPlugin]
                        });
                    }

                    // Define custom inline plugin for bar charts data labels
                    const barDatalabelsPlugin = {
                        id: 'barDatalabels',
                        afterDraw: (chart) => {
                            const ctx = chart.ctx;
                            chart.data.datasets.forEach((dataset, i) => {
                                const meta = chart.getDatasetMeta(i);
                                if (meta.hidden) return;

                                meta.data.forEach((element, index) => {
                                    const value = dataset.data[index];
                                    if (value === undefined || value === null || value <= 0) return;
                                    
                                    ctx.save();
                                    ctx.fillStyle = '#475569'; // Slate 600
                                    ctx.font = 'bold 9px sans-serif';
                                    
                                    if (chart.options.indexAxis === 'y') {
                                        ctx.textAlign = 'left';
                                        ctx.textBaseline = 'middle';
                                        ctx.fillText(` ${value}`, element.x + 3, element.y);
                                    } else {
                                        ctx.textAlign = 'center';
                                        ctx.textBaseline = 'bottom';
                                        ctx.fillText(`${value}`, element.x, element.y - 3);
                                    }
                                    ctx.restore();
                                });
                            });
                        }
                    };

                    // 6. PROVINCE CHART
                    const ctxProv = document.getElementById('provinceChart');
                    if (ctxProv && this.statsData.chartDist && this.statsData.chartDist.province) {
                        const provEntries = Object.entries(this.statsData.chartDist.province)
                            .sort((a, b) => b[1] - a[1]);
                        const topProvKeys = provEntries.map(e => e[0]).slice(0, 20);
                        const topProvVals = provEntries.map(e => e[1]).slice(0, 20);
                        
                        this.chartInstances.province = new Chart(ctxProv.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: topProvKeys,
                                datasets: [{
                                    label: 'Số lượng',
                                    data: topProvVals,
                                    backgroundColor: '#a855f7',
                                    borderRadius: 4,
                                }]
                            },
                            options: { 
                                responsive: true, 
                                maintainAspectRatio: false, 
                                plugins: { 
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            title: function(context) {
                                                return topProvKeys[context[0].dataIndex];
                                            }
                                        }
                                    }
                                }, 
                                scales: { 
                                    x: { 
                                        ticks: { 
                                            maxRotation: 45, 
                                            minRotation: 45, 
                                            font: { size: 9 },
                                            callback: function(value, index) {
                                                const label = topProvKeys[index];
                                                if (!label) return '';
                                                return label.length > 15 ? label.substring(0, 15) + '...' : label;
                                            }
                                        } 
                                    }, 
                                    y: { beginAtZero: true, grace: '5%' } 
                                } 
                            },
                            plugins: [barDatalabelsPlugin]
                        });
                    }

                    // 7. SCHOOL CHART
                    const ctxSchool = document.getElementById('schoolChart');
                    if (ctxSchool && this.statsData.chartDist && this.statsData.chartDist.school) {
                        const schEntries = Object.entries(this.statsData.chartDist.school)
                            .sort((a, b) => b[1] - a[1]);
                        const allSchoolsKeys = schEntries.map(e => e[0]);
                        const allSchoolsVals = schEntries.map(e => e[1]);
                        
                        const topSchKeys = this.showAllSchools ? allSchoolsKeys : allSchoolsKeys.slice(0, 20);
                        const topSchVals = this.showAllSchools ? allSchoolsVals : allSchoolsVals.slice(0, 20);

                        // Dynamically adjust height of parent container to avoid squishing
                        const container = ctxSchool.parentElement;
                        container.style.height = Math.max(256, (topSchKeys.length * 22 + 50)) + 'px';

                        this.chartInstances.school = new Chart(ctxSchool.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: topSchKeys,
                                datasets: [{
                                    label: 'Số lượng',
                                    data: topSchVals,
                                    backgroundColor: '#f43f5e',
                                    borderRadius: 4,
                                }]
                            },
                            options: { 
                                indexAxis: 'y', 
                                responsive: true, 
                                maintainAspectRatio: false, 
                                plugins: { 
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            title: function(context) {
                                                return topSchKeys[context[0].dataIndex];
                                            }
                                        }
                                    }
                                }, 
                                scales: { 
                                    y: { 
                                        ticks: { 
                                            font: { size: 9 },
                                            callback: function(value, index) {
                                                const label = topSchKeys[index];
                                                if (!label) return '';
                                                return label.length > 25 ? label.substring(0, 25) + '...' : label;
                                            }
                                        } 
                                    }, 
                                    x: { beginAtZero: true, grace: '5%' } 
                                } 
                            },
                            plugins: [barDatalabelsPlugin]
                        });
                    }

                    this.chartsRendered = true;
                });
            },

            syncData() {
                if (!this.selectedSession) return;
                
                this.isSyncing = true;
                this.startLoading('Đang đồng bộ dữ liệu từ hồ sơ đã duyệt...');
                
                $.ajax({
                    url: '<?= url("/admin/api/vf/sync") ?>',
                    type: 'POST',
                    data: { 
                        session_id: this.selectedSession,
                        _csrf_token: '<?= csrf_token() ?>'
                    },
                    success: (res) => {
                        let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                        if(parsed.success) {
                            toast.success(parsed.message);
                            this.loadData();
                        } else {
                            toast.error(parsed.message || parsed.error);
                        }
                    },
                    error: (err) => {
                        let msg = 'Lỗi khi đồng bộ dữ liệu';
                        if (err.responseJSON && err.responseJSON.error) {
                            msg = err.responseJSON.error;
                        }
                        toast.error(msg);
                    },
                    complete: () => {
                        this.stopLoading();
                    }
                });
            },

            recalculate() {
                const confirmMsg = 'Bạn có chắc chắn muốn TÍNH LẠI TOÀN BỘ điểm cho tất cả thí sinh?';

                if (!confirm(confirmMsg)) return;
                
                this.isCalculating = true;
                this.startLoading('Đang khởi tạo hàng chờ tính điểm toàn bộ...', true);
                
                // 1. Get the list of CCCDs first
                $.ajax({
                    url: '<?= url("/admin/api/vf/get-cccds") ?>',
                    data: { 
                        session_id: this.selectedSession,
                        force: 1
                    },
                    success: (res) => {
                        if (!res.success || !res.cccds.length) {
                            this.stopLoading();
                            toast.success("Tất cả hồ sơ đã ở trạng thái mới nhất. Không cần tính toán thêm.");
                            return;
                        }
                        
                        this.totalToProcess = res.cccds.length;
                        this.totalProcessed = 0;
                        this.errorCount = 0;
                        this.currentLoadingMessage = `Phát hiện ${this.totalToProcess} hồ sơ cần tính toán. Đang bắt đầu...`;
                        
                        // 2. Start the recursive chunk processing
                        this.processNextBatch(res.cccds);
                    },
                    error: () => {
                        this.stopLoading();
                        toast.error("Không thể kết nối máy chủ để lấy danh sách hồ sơ.");
                    }
                });
            },

            processNextBatch(cccdList) {
                if (cccdList.length === 0) {
                    this.stopLoading();
                    toast.success(`Hoàn tất! Đã tính điểm cho ${this.totalProcessed} hồ sơ. Số lỗi: ${this.errorCount}`);
                    this.loadData();
                    return;
                }

                // Slice the next chunk
                const chunk = cccdList.slice(0, this.batchSize);
                const remaining = cccdList.slice(this.batchSize);
                
                this.currentLoadingMessage = `Đang xử lý ${this.totalProcessed} - ${Math.min(this.totalProcessed + this.batchSize, this.totalToProcess)} / ${this.totalToProcess} hồ sơ...`;

                $.ajax({
                    url: '<?= url("/admin/api/vf/recalculate") ?>',
                    type: 'POST',
                    data: { 
                        session_id: this.selectedSession,
                        cccds: JSON.stringify(chunk),
                        force: '1',
                        _csrf_token: '<?= csrf_token() ?>'
                    },
                    success: (res) => {
                        let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                        if (parsed.success) {
                            this.totalProcessed += chunk.length;
                        } else {
                            this.errorCount += chunk.length;
                            console.error("Batch Error:", parsed.message);
                        }
                        
                        // Update real progress
                        this.progress = Math.round((this.totalProcessed / this.totalToProcess) * 100);
                        
                        // Recursive call for next batch
                        this.processNextBatch(remaining);
                    },
                    error: (err) => {
                        this.errorCount += chunk.length;
                        console.error("Critical Network Error in Batch", err);
                        // Even on network error, try to continue with the next batch to ensure completion
                        this.processNextBatch(remaining);
                    }
                });
            },


            runVirtualFilter() {
                if (!this.selectedSession) return;
                
                if (!confirm('Hệ thống sẽ chạy thuật toán lọc ảo dựa trên điểm chuẩn đã thiết lập. Tiếp tục?')) return;

                this.isFiltering = true;
                this.startLoading('Đang chạy thuật toán lọc ảo...');

                $.ajax({
                    url: '<?= url("/admin/api/vf/run") ?>',
                    type: 'POST',
                    data: {
                        session_id: this.selectedSession,
                        _csrf_token: '<?= csrf_token() ?>'
                    },
                    success: (res) => {
                        let parsed;
                        try {
                            parsed = typeof res === 'string' ? JSON.parse(res) : res;
                        } catch (e) {
                            console.error("Malformed JSON response:", res);
                            toast.error("Lỗi phản hồi từ máy chủ (Malformed JSON).");
                            return;
                        }

                        if (parsed.status) {
                            let msg = "Lọc ảo hoàn tất!";
                            if (parsed.candidate_count) {
                                msg += ` (Đã xét ${parsed.candidate_count} hồ sơ, đỗ ${parsed.successful_count})`;
                            }
                            toast.success(msg);
                            this.loadData();
                        } else {
                            toast.error(parsed.message || parsed.error || "Lỗi khi chạy lọc ảo");
                        }
                    },
                    error: (xhr) => {
                        let msg = "Lỗi khi kết nối máy chủ";
                        try {
                            if (xhr.responseText && xhr.responseText.includes('{')) {
                                let err = JSON.parse(xhr.responseText);
                                msg = err.message || err.error || msg;
                            }
                        } catch(e) {}
                        toast.error(msg);
                    },
                    complete: () => {
                        this.stopLoading();
                    }
                });
            },
            
            exportAll() {
                if (!this.selectedSession) return;
                window.location.href = '<?= url("/admin/api/vf/export") ?>?session_id=' + this.selectedSession;
            },

            exportAdmitted() {
                if (!this.selectedSession) return;
                window.location.href = '<?= url("/admin/api/vf/export-admitted") ?>?session_id=' + this.selectedSession;
            },

            exportVirtualFilter() {
                if (!this.selectedSession) return;
                window.location.href = '<?= url("/admin/api/vf/export-virtual-filter") ?>?session_id=' + this.selectedSession;
            },

            exportMoetFormat() {
                if (!this.selectedSession) return;
                window.location.href = '<?= url("/admin/api/vf/export-moet-format") ?>?session_id=' + this.selectedSession;
            },

            exportFailed() {
                if (!this.selectedSession) return;
                window.location.href = '<?= url("/admin/api/vf/export-failed") ?>?session_id=' + this.selectedSession;
            },

            exportAcademicFail() {
                if (!this.selectedSession) return;
                window.location.href = '<?= url("/admin/api/vf/export-academic-fail") ?>?session_id=' + this.selectedSession;
            },

            syncNotebookLM() {
                if (!this.selectedSession) return;
                
                this.isLoading = true;
                toast.info('Đang đồng bộ dữ liệu lên NotebookLM...');

                $.ajax({
                    url: '<?= url("/admin/api/vf/sync-notebooklm") ?>',
                    type: 'POST',
                    data: { 
                        session_id: this.selectedSession,
                        _csrf_token: '<?= csrf_token() ?>'
                    },
                    success: (res) => {
                        this.isLoading = false;
                        let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                        if (parsed.success) {
                            toast.success(parsed.message);
                            if (parsed.file_url) {
                                console.log("Bản sao dự phòng báo cáo lưu tại:", window.location.origin + parsed.file_url);
                            }
                        } else {
                            toast.error(parsed.message);
                        }
                    },
                    error: (xhr, status, error) => {
                        this.isLoading = false;
                        let errorMsg = 'Không thể kết nối đến máy chủ hoặc dịch vụ NotebookLM chưa hoạt động.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        toast.error(errorMsg);
                    }
                });
            },

            // =====================================================
            // BGD GD&ĐT Virtual Filter Import Methods
            // =====================================================

            fetchBGDStatus() {
                if (!this.selectedSession) return;
                $.get('<?= url("/admin/api/vf/bgd-status") ?>?session_id=' + this.selectedSession, (res) => {
                    let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                    if (parsed.success) {
                        this.bgdStatus.imported       = parsed.imported || false;
                        this.bgdStatus.tong_bo_gd     = parsed.tong_bo_gd || 0;
                        this.bgdStatus.bi_loai        = parsed.bi_loai || 0;
                        this.bgdStatus.giu_lai        = parsed.giu_lai || 0;
                        this.bgdStatus.lan_loc_ao     = parsed.lan_loc_ao || '';
                        this.bgdStatus.lan_import_cuoi= parsed.lan_import_cuoi || '';
                        this.bgdStatus.imported_by    = parsed.imported_by || '';
                    }
                });
            },

            handleBGDFileChange(event) {
                const file = event.target.files[0];
                if (!file) return;
                this.bgdStatus.selectedFile = file;
                this.bgdStatus.selectedFileName = file.name;
                this.bgdStatus.lastMessage = '';
            },

            handleBGDFileDrop(event) {
                const file = event.dataTransfer.files[0];
                if (!file) return;
                const ext = file.name.split('.').pop().toLowerCase();
                if (!['xlsx', 'xls'].includes(ext)) {
                    toast.error('Chỉ chấp nhận file .xlsx hoặc .xls');
                    return;
                }
                this.bgdStatus.selectedFile = file;
                this.bgdStatus.selectedFileName = file.name;
                this.bgdStatus.lastMessage = '';
                // Also update the input element
                const inputEl = document.getElementById('bgd-file-input-modal');
                if (inputEl) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    inputEl.files = dt.files;
                }
            },

            uploadBGDFile() {
                if (!this.bgdStatus.selectedFile || !this.selectedSession) return;

                this.bgdStatus.importing = true;
                this.bgdStatus.lastMessage = '';

                // Kích hoạt màn hình chờ Premium Loading của hệ thống giống như tính năng Import
                this.isLoading = true;
                this.currentLoadingMessage = 'Đang phân tích cấu trúc file & đối chiếu dữ liệu...';
                this.progress = 5;
                this.showBgdUploadModal = false; // Tạm ẩn modal upload để người dùng nhìn màn hình loading

                // Chạy hiệu ứng tăng dần tiến trình ảo (đến 90%) tạo cảm giác sống động
                let progressInterval = setInterval(() => {
                    if (this.progress < 90) {
                        this.progress += Math.floor(Math.random() * 8) + 2;
                    }
                }, 250);

                const formData = new FormData();
                formData.append('bgd_file', this.bgdStatus.selectedFile);
                formData.append('session_id', this.selectedSession);
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                fetch('<?= url("/admin/api/vf/import-bgd") ?>', {
                    method: 'POST',
                    headers: {
                        // Báo cho SecurityMiddleware biết đây là AJAX → trả JSON không phải HTML
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(async r => {
                    const text = await r.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // Server trả HTML (PHP error) — log ra console để debug
                        console.error('Server returned non-JSON (PHP error):');
                        console.error(text.substring(0, 2000));
                        // Lấy thông báo lỗi từ HTML
                        const match = text.match(/<b>(?:Fatal error|Warning|Notice|Error)<\/b>:\s*(.+?)\s*in/i);
                        throw new Error(match ? match[1] : 'Server error (PHP). Kiểm tra console để xem chi tiết.');
                    }
                })
                .then(parsed => {
                    clearInterval(progressInterval);
                    this.progress = 100;

                    // Chờ một khoảng nhỏ để thanh progress hoàn tất 100% về mặt thị giác
                    setTimeout(() => {
                        this.bgdStatus.importing = false;
                        this.isLoading = false; // Tắt màn hình chờ
                        this.showBgdUploadModal = true; // Mở lại modal để xem kết quả và tải file

                        if (parsed.success) {
                            this.bgdStatus.lastMessage = `✅ Đối chiếu thành công ${parsed.total_rows} dòng. Giữ lại: ${parsed.giu_lai} TS, Bị loại: ${parsed.bi_loai} TS.`;
                            this.bgdStatus.lastMessageType = 'success';
                            this.bgdStatus.selectedFileName = '';
                            this.bgdStatus.selectedFile = null;
                            
                            const inputEl = document.getElementById('bgd-file-input-modal');
                            if (inputEl) inputEl.value = '';

                            // Cập nhật lại stats & reload table
                            this.fetchBGDStatus();
                            if (this.dt) this.dt.ajax.reload(null, false);

                            if (!this.isReadOnly) {
                                toast.success(`Import hoàn tất! Tải báo cáo đối chiếu sau giây lát...`);
                                setTimeout(() => {
                                    window.location.href = '<?= url("/admin/api/vf/download-bgd-report") ?>';
                                }, 1000);
                            } else {
                                toast.success(`Import hoàn tất!`);
                            }
                        } else {
                            this.bgdStatus.lastMessage = '❌ Lỗi: ' + (parsed.message || 'Không rõ lỗi');
                            this.bgdStatus.lastMessageType = 'error';
                            if (parsed.errors && parsed.errors.length) {
                                console.warn('BGD Import Errors:', parsed.errors);
                            }
                            toast.error(parsed.message || 'Import thất bại');
                        }
                    }, 800);
                })
                .catch(err => {
                    clearInterval(progressInterval);
                    this.bgdStatus.importing = false;
                    this.isLoading = false;
                    this.showBgdUploadModal = true;
                    this.bgdStatus.lastMessage = '❌ ' + err.message;
                    this.bgdStatus.lastMessageType = 'error';
                    toast.error(err.message);
                });
            },

            exportAdmittedFinal() {
                if (!this.selectedSession) return;
                if (!this.bgdStatus.imported) {
                    toast.warning('Cần import kết quả Bộ GD&ĐT trước khi xuất danh sách chính thức!');
                    return;
                }
                window.location.href = '<?= url("/admin/api/vf/export-admitted-final") ?>?session_id=' + this.selectedSession;
            },

            exportEliminatedByBGD() {
                if (!this.selectedSession) return;
                if (!this.bgdStatus.imported) {
                    toast.warning('Cần import kết quả Bộ GD&ĐT trước!');
                    return;
                }
                window.location.href = '<?= url("/admin/api/vf/export-eliminated-bgd") ?>?session_id=' + this.selectedSession;
            },

            exportStats() {
                if (!this.selectedSession) return;
                window.location.href = '<?= url("/admin/api/vf/export-stats") ?>?session_id=' + this.selectedSession;
            },

            exportChartData() {
                if (!this.selectedSession) return;
                window.location.href = '<?= url("/admin/api/vf/export-chart-data") ?>?session_id=' + this.selectedSession;
            }
        }
    }
</script>
<style>
/* Adjust FixedColumns shadow */
table.dataTable thead tr > .dtfc-fixed-left, table.dataTable tbody tr > .dtfc-fixed-left {
    background-color: white;
}
table.dataTable thead tr th.dtfc-fixed-left {
    background-color: #f1f5f9; /* bg-slate-100 */
}

/* Premium Loading CSS */
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

.animate-spin-slow {
    animation: spin 6s infinite linear;
}

.premium-table {
    width: 100%;
    border-collapse: collapse;
}
.premium-table th {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 10px;
    vertical-align: middle;
}
.premium-table td {
    border: 1px solid #e2e8f0;
    padding: 10px;
    font-size: 12px;
    vertical-align: middle;
}
.premium-table tfoot td {
    padding: 10px;
    font-size: 12px;
    font-weight: 700;
}
[x-cloak] { display: none !important; }
</style>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>
