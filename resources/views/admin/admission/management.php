<?php
$title = $title ?? 'Quản lý Chỉ tiêu & Điểm chuẩn';
ob_start();
?>

<div class="h-full flex flex-col p-6 bg-slate-50" x-data="admissionManager()">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight"><?= $title ?></h1>
            <p class="text-slate-500 text-sm mt-1">Thiết lập điểm chuẩn và tiêu chí phụ theo đợt tuyển sinh.</p>
        </div>
        
        <div class="flex items-center gap-3 bg-white p-1.5 rounded-xl shadow-sm border border-slate-200">
            <select x-model="selectedYear" @change="fetchSessions" class="border-none bg-transparent text-sm font-semibold text-slate-700 focus:ring-0 cursor-pointer min-w-[100px]">
                <option value="">-- Năm --</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                <?php endforeach; ?>
            </select>
            <div class="w-px h-6 bg-slate-200"></div>
            <select x-model="selectedSession" @change="fetchData" class="border-none bg-transparent text-sm font-semibold text-slate-700 focus:ring-0 cursor-pointer min-w-[220px]" :disabled="!selectedYear">
                <option value="">-- Chọn đợt tuyển sinh --</option>
                <template x-for="s in sessions" :key="s.id">
                    <option :value="s.id" x-text="s.ten_dot" :selected="s.id == <?= $activeSession['id'] ?? 0 ?>"></option>
                </template>
            </select>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex-1 flex flex-col overflow-hidden relative">
        <!-- Loading Overlay -->
        <div x-show="isLoading" class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-20 flex items-center justify-center transition-all duration-300">
            <div class="flex flex-col items-center">
                <div class="w-12 h-12 border-4 border-indigo-100 border-t-indigo-600 rounded-full animate-spin mb-4"></div>
                <p class="text-slate-600 font-medium animate-pulse">Đang tải dữ liệu...</p>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50">
            <div class="relative w-full sm:w-80">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" x-model="searchQuery" placeholder="Tìm tên ngành, mã ngành..." class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none">
            </div>
            <div class="flex items-center gap-3">
                <!-- Export Excel Button -->
                <a :href="selectedSession
                        ? '<?= url('/admin/admission/management/export') ?>?session_id=' + selectedSession
                        : '<?= url('/admin/admission/management/export') ?>'"
                   :class="!selectedSession ? 'pointer-events-none opacity-40' : ''"
                   target="_blank"
                   title="Xuất danh sách ngành & điểm chuẩn ra Excel"
                   class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm border border-emerald-300 bg-white text-emerald-700 hover:bg-emerald-50 shadow-sm transition-all">
                    <i class="fas fa-file-excel text-emerald-600"></i>
                    <span>Xuất Excel</span>
                </a>
                <!-- Save Button -->
                <button @click="saveData" :disabled="!selectedSession || isSaving" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2 group disabled:opacity-50 disabled:shadow-none">
                    <template x-if="!isSaving">
                        <i class="fas fa-save group-hover:scale-110 transition-transform"></i>
                    </template>
                    <template x-if="isSaving">
                        <i class="fas fa-circle-notch fa-spin"></i>
                    </template>
                    <span x-text="isSaving ? 'Đang lưu...' : 'Lưu cấu hình'"></span>
                </button>
            </div>
        </div>


        <!-- Table -->
        <div class="flex-1 overflow-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-slate-50/80 sticky top-0 z-10">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 w-16">Xét</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">Ngành & Nhóm</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 text-center w-36">Ngưỡng (Ref)</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 text-center w-28">Chỉ tiêu</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 text-center w-28">Điểm chuẩn</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 text-center w-32">
                            Tiêu chí phụ
                            <i class="fas fa-question-circle text-[10px] text-slate-400 cursor-help" title="Dùng để xét ưu tiên khi các thí sinh bằng điểm nhau (VD: Ưu tiên môn Toán >= 8.0)"></i>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-for="(item, index) in filteredData" :key="item.ma_nganh">
                        <tr class="hover:bg-slate-100/50 transition-all group border-b border-slate-50" 
                            :style="item.has_benchmark ? 'background-color: rgba(34, 197, 94, 0.08)' : ''"
                            :class="!item.kich_hoat ? 'opacity-50' : ''">
                            <td class="px-6 py-4">
                                <template x-if="item.kich_hoat">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="item.has_benchmark" class="sr-only">
                                        <div class="w-11 h-6 rounded-full transition-all relative"
                                             :style="item.has_benchmark ? 'background-color: #22c55e' : 'background-color: #cbd5e1'">
                                            <div class="absolute top-[2px] left-[2px] bg-white border border-gray-200 rounded-full h-5 w-5 transition-all transform"
                                                 :style="item.has_benchmark ? 'transform: translateX(20px)' : 'transform: translateX(0)'"></div>
                                        </div>
                                    </label>
                                </template>
                                <template x-if="!item.kich_hoat">
                                    <span class="text-[10px] px-1.5 py-0.5 rounded" style="background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">Ngưng</span>
                                </template>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 group-hover:text-indigo-700 transition-colors" x-text="item.ten_nganh"></span>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs font-mono px-1.5 py-0.5 rounded border" style="background-color: #eef2ff; color: #4f46e5; border-color: #e0e7ff;" x-text="item.ma_nganh"></span>
                                        <span class="text-[10px] text-slate-400 italic" x-text="item.nhom_nganh"></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col gap-1 items-center">
                                    <template x-if="item.nguong_hoc_luc">
                                        <span class="text-[10px] px-1.5 py-0.5 rounded w-fit" style="background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                                            HL: <strong x-text="item.nguong_hoc_luc"></strong>
                                        </span>
                                    </template>
                                    <template x-if="item.nguong_diem_thpt">
                                        <span class="text-[10px] px-1.5 py-0.5 rounded w-fit" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                            Sàn: <strong x-text="item.nguong_diem_thpt"></strong>
                                        </span>
                                    </template>
                                    <template x-if="!item.nguong_hoc_luc && !item.nguong_diem_thpt">
                                        <span class="text-[10px] text-slate-300">—</span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-full text-center py-1.5 rounded-lg text-sm font-semibold" style="background-color: #f8fafc; color: #64748b;" x-text="item.chi_tieu || '—'"></div>
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" step="0.01" x-model.number="item.diem_chuan" :disabled="!item.has_benchmark || !item.kich_hoat" class="w-full text-center py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none disabled:bg-slate-50 disabled:text-slate-300 transition-all font-bold text-blue-600">
                            </td>
                            <td class="px-6 py-4 text-center">
                                <input type="text" x-model="item.tieuchi_phu" :disabled="!item.has_benchmark || !item.kich_hoat" placeholder="..." class="w-full text-center py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none disabled:bg-slate-50 disabled:text-slate-300 transition-all text-slate-500">
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            
            <template x-if="filteredData.length === 0 && !isLoading">
                <div class="flex flex-col items-center justify-center p-12 text-slate-400">
                    <i class="fas fa-folder-open text-5xl mb-4 opacity-20"></i>
                    <p>Không tìm thấy ngành nào phù hợp.</p>
                </div>
            </template>
        </div>

        <!-- Footer Stats -->
        <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex justify-between items-center text-xs font-medium text-slate-500">
            <div class="flex gap-4">
                <span>Tổng số ngành: <span class="text-slate-800 font-bold" x-text="data.length"></span></span>
                <span>Đang xét tuyển: <span class="font-bold" style="color: #22c55e;" x-text="data.filter(x => x.has_benchmark).length"></span></span>
                <span>Ngưng tuyển: <span class="font-bold" style="color: #dc2626;" x-text="data.filter(x => !x.kich_hoat).length"></span></span>
            </div>
            <div>
                Đợt ID: <span class="font-mono text-indigo-500" x-text="selectedSession || 'N/A'"></span>
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
        isLoading: false,
        isSaving: false,
        
        get filteredData() {
            if (!this.searchQuery) return this.data;
            const q = this.searchQuery.toLowerCase();
            return this.data.filter(item => 
                item.ten_nganh.toLowerCase().includes(q) || 
                item.ma_nganh.toLowerCase().includes(q)
            );
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
            
            // Chỉ gửi dữ liệu cần thiết (chi_tieu tự đồng bộ từ dm_nganh)
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
                alert('Lỗi kết nối máy chủ');
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
