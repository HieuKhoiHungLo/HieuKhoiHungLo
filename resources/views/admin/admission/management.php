<?php
$title = $title ?? 'Quản lý Chỉ tiêu & Điểm chuẩn';
ob_start();
?>

<style>
    /* Ẩn các nút tăng/giảm (spin buttons) của input type="number" */
    .no-spinner::-webkit-outer-spin-button,
    .no-spinner::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .no-spinner[type=number] {
        -moz-appearance: textfield;
    }

    /* Hiệu ứng chuyển động mượt mà cho hàng của bảng */
    .hover-row-highlight {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .hover-row-highlight:hover {
        background-color: #f8fafc !important;
    }

    /* Hiệu ứng focus input điểm chuẩn */
    .premium-input {
        transition: all 0.15s ease-in-out;
    }
    .premium-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }
</style>

<div class="h-full flex flex-col p-6 bg-slate-50" x-data="admissionManager()">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight font-heading flex items-center gap-2">
                <i class="fas fa-sliders-h text-indigo-600"></i>
                <?= $title ?>
            </h1>
            <p class="text-slate-500 text-sm mt-1">Cấu hình điểm trúng tuyển tối thiểu và tiêu chí phụ cho từng ngành tuyển sinh.</p>
        </div>
        
        <!-- Bộ chọn đợt/năm tuyển sinh -->
        <div class="flex items-center gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-200">
            <div class="flex items-center gap-1.5 px-3 py-1 bg-slate-50 rounded-xl border border-slate-100">
                <i class="fas fa-calendar-alt text-slate-400 text-xs"></i>
                <select x-model="selectedYear" @change="fetchSessions" class="border-none bg-transparent text-xs font-bold text-slate-700 focus:ring-0 cursor-pointer py-0.5 pl-0 pr-6">
                    <option value="">-- Năm --</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-px h-6 bg-slate-200"></div>
            <div class="flex items-center gap-1.5 px-3 py-1 bg-slate-50 rounded-xl border border-slate-100">
                <i class="fas fa-list-ol text-slate-400 text-xs"></i>
                <select x-model="selectedSession" @change="fetchData" class="border-none bg-transparent text-xs font-bold text-slate-700 focus:ring-0 cursor-pointer min-w-[200px] py-0.5 pl-0 pr-6" :disabled="!selectedYear">
                    <option value="">-- Chọn đợt tuyển sinh --</option>
                    <template x-for="s in sessions" :key="s.id">
                        <option :value="s.id" x-text="s.ten_dot" :selected="s.id == <?= $activeSession['id'] ?? 0 ?>"></option>
                    </template>
                </select>
            </div>
        </div>
    </div>

    <!-- Main Content Panel -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 flex-1 flex flex-col overflow-hidden relative">
        
        <!-- Loading Overlay -->
        <div x-show="isLoading" x-transition.opacity class="absolute inset-0 bg-white/70 backdrop-blur-[2px] z-20 flex items-center justify-center">
            <div class="flex flex-col items-center">
                <div class="w-12 h-12 border-4 border-indigo-100 border-t-indigo-600 rounded-full animate-spin mb-4"></div>
                <p class="text-slate-600 font-bold tracking-wide animate-pulse text-sm">Đang tải dữ liệu...</p>
            </div>
        </div>

        <!-- Toolbar (Search, Filter, Export & Save) -->
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col xl:flex-row justify-between items-stretch xl:items-center gap-4 bg-slate-50/50">
            <!-- Search and Group Selector -->
            <div class="flex flex-col sm:flex-row gap-3 flex-1">
                <!-- Search bar -->
                <div class="relative flex-1 sm:max-w-xs">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input type="text" x-model="searchQuery" placeholder="Tìm tên ngành, mã ngành..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none shadow-sm">
                </div>
                
                <!-- Group filter drop-down -->
                <div class="relative sm:max-w-xs">
                    <select x-model="selectedGroup" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none cursor-pointer shadow-sm text-slate-700 font-semibold">
                        <option value="">-- Tất cả nhóm ngành --</option>
                        <template x-for="g in groupOptions" :key="g">
                            <option :value="g" x-text="g"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Status Tabs and Action Buttons -->
            <div class="flex flex-wrap items-center gap-4 justify-between xl:justify-end">
                <!-- Status Filter Badges -->
                <div class="flex bg-slate-200/50 p-1 rounded-xl text-xs font-bold text-slate-600 gap-0.5 shadow-inner">
                    <button @click="selectedStatus = ''" :class="selectedStatus === '' ? 'bg-white text-slate-800 shadow-sm' : 'hover:bg-white/40'" class="px-3.5 py-2 rounded-lg transition-all">Tất cả</button>
                    <button @click="selectedStatus = 'has_benchmark'" :class="selectedStatus === 'has_benchmark' ? 'bg-white text-emerald-700 shadow-sm' : 'hover:bg-white/40'" class="px-3.5 py-2 rounded-lg transition-all flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Đang xét
                    </button>
                    <button @click="selectedStatus = 'no_benchmark'" :class="selectedStatus === 'no_benchmark' ? 'bg-white text-slate-800 shadow-sm' : 'hover:bg-white/40'" class="px-3.5 py-2 rounded-lg transition-all flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span> Chưa xét
                    </button>
                    <button @click="selectedStatus = 'inactive'" :class="selectedStatus === 'inactive' ? 'bg-white text-rose-700 shadow-sm' : 'hover:bg-white/40'" class="px-3.5 py-2 rounded-lg transition-all flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span> Ngưng
                    </button>
                </div>

                <!-- Operations -->
                <div class="flex items-center gap-2">
                    <!-- Export to Excel -->
                    <a :href="selectedSession ? '<?= url('/admin/admission/management/export') ?>?session_id=' + selectedSession : '<?= url('/admin/admission/management/export') ?>'"
                       :class="!selectedSession ? 'pointer-events-none opacity-40' : ''"
                       target="_blank"
                       title="Xuất Excel danh sách điểm chuẩn"
                       class="flex items-center gap-2 px-4.5 py-2.5 rounded-xl font-bold text-sm border border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-50 hover:border-emerald-300 shadow-sm active:scale-95 transition-all">
                        <i class="fas fa-file-excel text-emerald-600"></i>
                        <span>Xuất Excel</span>
                    </a>
                    
                    <!-- Save Settings -->
                    <button @click="saveData" :disabled="!selectedSession || isSaving" class="bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white px-5 py-2.5 rounded-xl font-bold shadow-md shadow-indigo-100 hover:shadow-indigo-200 transition-all flex items-center gap-2 disabled:opacity-50 disabled:shadow-none disabled:pointer-events-none">
                        <template x-if="!isSaving">
                            <i class="fas fa-save"></i>
                        </template>
                        <template x-if="isSaving">
                            <i class="fas fa-circle-notch fa-spin"></i>
                        </template>
                        <span x-text="isSaving ? 'Đang lưu...' : 'Lưu cấu hình'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Scrollable Table Body -->
        <div class="flex-1 overflow-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[950px]">
                <thead>
                    <tr class="bg-slate-50/80 sticky top-0 z-10 border-b border-slate-200/80">
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-500 uppercase tracking-wider text-center w-20 cursor-pointer select-none" @click="toggleSort('has_benchmark')">
                            Xét <i class="fas ml-1" :class="sortBy === 'has_benchmark' ? (sortAsc ? 'fa-sort-up text-indigo-600' : 'fa-sort-down text-indigo-600') : 'fa-sort text-slate-300'"></i>
                        </th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-500 uppercase tracking-wider cursor-pointer select-none" @click="toggleSort('ma_nganh')">
                            Ngành & Nhóm <i class="fas ml-1" :class="sortBy === 'ma_nganh' ? (sortAsc ? 'fa-sort-up text-indigo-600' : 'fa-sort-down text-indigo-600') : 'fa-sort text-slate-300'"></i>
                        </th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-500 uppercase tracking-wider text-center w-40">Ngưỡng sàn (Ref)</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-500 uppercase tracking-wider text-center w-28 cursor-pointer select-none" @click="toggleSort('chi_tieu')">
                            Chỉ tiêu <i class="fas ml-1" :class="sortBy === 'chi_tieu' ? (sortAsc ? 'fa-sort-up text-indigo-600' : 'fa-sort-down text-indigo-600') : 'fa-sort text-slate-300'"></i>
                        </th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-500 uppercase tracking-wider text-center w-36 cursor-pointer select-none" @click="toggleSort('diem_chuan')">
                            Điểm chuẩn <i class="fas ml-1" :class="sortBy === 'diem_chuan' ? (sortAsc ? 'fa-sort-up text-indigo-600' : 'fa-sort-down text-indigo-600') : 'fa-sort text-slate-300'"></i>
                        </th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-500 uppercase tracking-wider text-center w-48">
                            Tiêu chí phụ
                            <i class="fas fa-question-circle text-[10px] text-slate-400 cursor-help" title="Dùng để xét ưu tiên khi các thí sinh bằng điểm nhau (VD: Toán >= 8.0)"></i>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(item, index) in filteredData" :key="item.ma_nganh">
                        <tr class="hover-row-highlight border-b border-slate-100"
                            :class="{
                                'bg-emerald-50/30 border-l-4 border-l-emerald-500': item.has_benchmark && item.kich_hoat,
                                'opacity-55 bg-slate-100/40': !item.kich_hoat
                            }">
                            <!-- Cột công tắc Xét -->
                            <td class="px-6 py-4 text-center">
                                <template x-if="item.kich_hoat">
                                    <label class="relative inline-flex items-center cursor-pointer select-none">
                                        <input type="checkbox" x-model="item.has_benchmark" class="sr-only peer">
                                        <div class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500 shadow-inner"></div>
                                    </label>
                                </template>
                                <template x-if="!item.kich_hoat">
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-rose-100 text-rose-600 border border-rose-200">Ngưng</span>
                                </template>
                            </td>
                            
                            <!-- Cột Ngành & Nhóm -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 text-sm hover:text-indigo-600 transition-colors" x-text="item.ten_nganh"></span>
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded-lg border bg-indigo-50/50 text-indigo-700 border-indigo-100" x-text="item.ma_nganh"></span>
                                        <span class="text-[10px] text-slate-400 italic" x-text="item.nhom_nganh"></span>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Cột Ngưỡng Sàn (Ref) -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1 items-center justify-center">
                                    <template x-if="item.nguong_hoc_luc">
                                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-amber-50 text-amber-700 border border-amber-200/60">
                                            HL: <span x-text="item.nguong_hoc_luc"></span>
                                        </span>
                                    </template>
                                    <template x-if="item.nguong_diem_thpt">
                                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-teal-50 text-teal-700 border border-teal-200/60">
                                            Sàn: <span x-text="item.nguong_diem_thpt"></span>
                                        </span>
                                    </template>
                                    <template x-if="!item.nguong_hoc_luc && !item.nguong_diem_thpt">
                                        <span class="text-xs text-slate-300">—</span>
                                    </template>
                                </div>
                            </td>
                            
                            <!-- Cột Chỉ tiêu -->
                            <td class="px-6 py-4 text-center">
                                <div class="inline-block px-3 py-1 rounded-lg text-sm font-bold bg-slate-50 border border-slate-100 text-slate-500 min-w-[55px]" x-text="item.chi_tieu || '—'"></div>
                            </td>
                            
                            <!-- Cột Điểm chuẩn (Chặn phím mũi tên & cuộn chuột để chống đổi nhầm) -->
                            <td class="px-6 py-4">
                                <input type="number" 
                                       step="0.001" 
                                       min="0"
                                       max="30"
                                       placeholder="0.000"
                                       x-model.number="item.diem_chuan" 
                                       :disabled="!item.has_benchmark || !item.kich_hoat" 
                                       @wheel.prevent
                                       @keydown.up.prevent
                                       @keydown.down.prevent
                                       class="w-full text-center py-2 bg-white border border-slate-200 rounded-xl text-sm outline-none disabled:bg-slate-100/50 disabled:text-slate-400 disabled:border-slate-100 font-bold text-indigo-600 no-spinner shadow-sm premium-input">
                            </td>
                            
                            <!-- Cột Tiêu chí phụ -->
                            <td class="px-6 py-4 text-center">
                                <input type="text" 
                                       placeholder="VD: Toán >= 8.0"
                                       x-model="item.tieuchi_phu" 
                                       :disabled="!item.has_benchmark || !item.kich_hoat" 
                                       class="w-full text-center py-2 bg-white border border-slate-200 rounded-xl text-sm outline-none disabled:bg-slate-100/50 disabled:text-slate-400 disabled:border-slate-100 text-slate-600 shadow-sm premium-input">
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            
            <!-- Trạng thái rỗng -->
            <template x-if="filteredData.length === 0 && !isLoading">
                <div class="flex flex-col items-center justify-center p-16 text-slate-400">
                    <i class="fas fa-folder-open text-5xl mb-4 text-slate-300"></i>
                    <p class="font-medium text-sm">Không tìm thấy ngành nào phù hợp với bộ lọc.</p>
                </div>
            </template>
        </div>

        <!-- Footer Stats -->
        <div class="px-6 py-4 border-t border-slate-150 bg-slate-50 flex justify-between items-center text-xs font-bold text-slate-500">
            <div class="flex gap-4">
                <span>Tổng số ngành: <span class="text-slate-800 font-extrabold" x-text="data.length"></span></span>
                <span>Đang xét tuyển: <span class="text-emerald-600 font-extrabold" x-text="data.filter(x => x.has_benchmark).length"></span></span>
                <span>Ngưng tuyển: <span class="text-rose-600 font-extrabold" x-text="data.filter(x => !x.kich_hoat).length"></span></span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-id-badge text-indigo-400"></i>
                Đợt ID: <span class="font-mono text-indigo-600 bg-indigo-50 border border-indigo-100 px-2.5 py-0.5 rounded-lg" x-text="selectedSession || 'N/A'"></span>
            </div>
        </div>
    </div>
</div>

<script>
function admissionManager() {
    return {
        selectedYear: '<?= $activeSession['nam_tuyen_sinh'] ?? '' ?>',
        selectedSession: '<?= $activeSession['id'] ?? '' ?>',
        sessions: [],
        data: [],
        searchQuery: '',
        selectedGroup: '',
        selectedStatus: '',
        sortBy: 'ma_nganh',
        sortAsc: true,
        isLoading: false,
        isSaving: false,
        
        // Trả về danh sách nhóm ngành duy nhất (đã được lọc & sắp xếp)
        get groupOptions() {
            if (!this.data) return [];
            const groups = this.data.map(item => item.nhom_nganh).filter(Boolean);
            return [...new Set(groups)].sort((a, b) => a.localeCompare(b));
        },

        // Bộ lọc & sắp xếp dữ liệu động
        get filteredData() {
            let result = [...this.data];

            // 1. Tìm kiếm theo tên / mã ngành
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase().trim();
                result = result.filter(item => 
                    item.ten_nganh.toLowerCase().includes(q) || 
                    item.ma_nganh.toLowerCase().includes(q)
                );
            }

            // 2. Lọc theo Nhóm ngành
            if (this.selectedGroup) {
                result = result.filter(item => item.nhom_nganh === this.selectedGroup);
            }

            // 3. Lọc theo trạng thái điểm chuẩn / kích hoạt
            if (this.selectedStatus) {
                if (this.selectedStatus === 'has_benchmark') {
                    result = result.filter(item => item.has_benchmark && item.kich_hoat);
                } else if (this.selectedStatus === 'no_benchmark') {
                    result = result.filter(item => !item.has_benchmark && item.kich_hoat);
                } else if (this.selectedStatus === 'inactive') {
                    result = result.filter(item => !item.kich_hoat);
                }
            }

            // 4. Sắp xếp động
            return result.sort((a, b) => {
                let valA = a[this.sortBy];
                let valB = b[this.sortBy];

                // Nếu giá trị null/undefined, đẩy xuống cuối
                if (valA === undefined || valA === null) valA = '';
                if (valB === undefined || valB === null) valB = '';

                // So sánh chuỗi hoặc số
                let comparison = 0;
                if (typeof valA === 'string' && typeof valB === 'string') {
                    comparison = valA.localeCompare(valB, 'vi', { numeric: true });
                } else {
                    comparison = (parseFloat(valA) || 0) - (parseFloat(valB) || 0);
                }

                return this.sortAsc ? comparison : -comparison;
            });
        },

        // Chuyển đổi cột sắp xếp
        toggleSort(field) {
            if (this.sortBy === field) {
                this.sortAsc = !this.sortAsc;
            } else {
                this.sortBy = field;
                this.sortAsc = true;
            }
        },

        init() {
            if (this.selectedYear) {
                this.fetchSessions().then(() => {
                    if (this.selectedSession) this.fetchData();
                });
            }
        },

        async fetchSessions() {
            if (!this.selectedYear) {
                this.sessions = [];
                this.selectedSession = '';
                this.data = [];
                return;
            }
            this.isLoading = true;
            try {
                const res = await fetch(`<?= url('/admin/admission/management/api-sessions') ?>?year=${this.selectedYear}`);
                const json = await res.json();
                if (json.status) {
                    this.sessions = json.sessions;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.isLoading = false;
            }
        },

        async fetchData() {
            if (!this.selectedSession) {
                this.data = [];
                return;
            }
            this.isLoading = true;
            try {
                const res = await fetch(`<?= url('/admin/admission/management/api-data') ?>?session_id=${this.selectedSession}`);
                const json = await res.json();
                if (json.status) {
                    this.data = json.data;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.isLoading = false;
            }
        },

        async saveData() {
            if (!this.selectedSession) return;
            this.isSaving = true;
            
            const formData = new FormData();
            formData.append('session_id', this.selectedSession);
            formData.append('_csrf_token', '<?= csrf_token() ?>');
            
            // Chỉ gửi các dữ liệu cần thiết của cấu hình điểm chuẩn
            this.data.forEach((item, index) => {
                formData.append(`data[${index}][ma_nganh]`, item.ma_nganh);
                formData.append(`data[${index}][has_benchmark]`, item.has_benchmark);
                formData.append(`data[${index}][diem_chuan]`, item.diem_chuan);
                formData.append(`data[${index}][tieuchi_phu]`, item.tieuchi_phu || '');
            });

            try {
                const res = await fetch(`<?= url('/admin/admission/management/api-save') ?>`, {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                if (json.status) {
                    typeof showToast === 'function' ? showToast(json.message, 'success') : alert(json.message);
                    this.fetchData();
                } else {
                    typeof showToast === 'function' ? showToast(json.message, 'error') : alert(json.message);
                }
            } catch (e) {
                console.error(e);
                typeof showToast === 'function' ? showToast('Lỗi kết nối máy chủ', 'error') : alert('Lỗi kết nối máy chủ');
            } finally {
                this.isSaving = false;
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>
