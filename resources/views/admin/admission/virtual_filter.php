<?php include __DIR__ . '/../../layouts/admin.php'; ?>

<div class="p-6 bg-gray-50 min-h-screen" x-data="virtualFilterApp()">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Xét tuyển & Lọc Ảo nội bộ</h1>
            <p class="text-sm text-gray-500 mt-1">Chạy thuật toán tính điểm và trượt dây chuyền nguyện vọng để chốt số lượng trúng tuyển.</p>
        </div>
        
        <div class="flex space-x-3">
            <select x-model="selectedBatch" @change="loadBatchData" class="rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                <option value="">-- Chọn đợt tuyển sinh --</option>
                <?php foreach ($batches as $batch): ?>
                    <option value="<?= $batch['id'] ?>"><?= htmlspecialchars($batch['ten_dot'] . ' (' . $batch['nam_tuyen_sinh'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
            
            <div class="flex items-center space-x-2">
                <button @click="recalculateScores" :disabled="!selectedBatch || isCalculating" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 active:bg-yellow-800 disabled:opacity-50 transition ease-in-out duration-150">
                    <span x-show="isCalculating"><i class="fas fa-spinner fa-spin mr-2"></i> Đang tính...</span>
                    <span x-show="!isCalculating"><i class="fas fa-calculator mr-2"></i> 1. Chạy Hàm Tính Điểm</span>
                </button>
                <label class="flex items-center space-x-1 cursor-pointer">
                    <input type="checkbox" x-model="forceRecalculate" class="rounded border-gray-300 text-yellow-600 shadow-sm focus:border-yellow-500 focus:ring-yellow-500">
                    <span class="text-[10px] font-bold text-yellow-700 uppercase">Toàn bộ</span>
                </label>
            </div>
            <button @click="runFilter" :disabled="!selectedBatch || isFiltering || majors.length === 0" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-800 disabled:opacity-50 transition ease-in-out duration-150">
                <span x-show="isFiltering"><i class="fas fa-spinner fa-spin mr-2"></i> Đang lọc ảo...</span>
                <span x-show="!isFiltering"><i class="fas fa-filter mr-2"></i> 2. Chạy Lọc Ảo Dây Chuyền</span>
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <template x-if="message">
        <div :class="{'bg-green-100 text-green-800': messageType === 'success', 'bg-red-100 text-red-800': messageType === 'error', 'bg-blue-100 text-blue-800': messageType === 'info'}" class="p-4 rounded-md mb-6 flex justify-between items-center transition-all duration-300">
            <div class="flex items-center">
                <i :class="{'fa-check-circle': messageType === 'success', 'fa-exclamation-circle': messageType === 'error', 'fa-info-circle': messageType === 'info'}" class="fas mr-2"></i>
                <span x-text="message"></span>
            </div>
            <button @click="message = ''" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
        </div>
    </template>

    <!-- Content Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-show="selectedBatch">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">Ngành Tuyển Sinh</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Tổng NV Tồn tại</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Chỉ tiêu dự kiến</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Điểm chuẩn dự kiến</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Số đậu (Sau lọc ảo)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(major, index) in majors" :key="major.ma_nganh">
                        <tr class="hover:bg-gray-50 transition text-sm">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900" x-text="major.ten_nganh"></div>
                                <div class="text-gray-500 text-xs" x-text="major.ma_nganh"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-gray-700 font-semibold">
                                <span x-text="major.tong_dang_ky" class="bg-gray-100 text-gray-800 py-1 px-3 rounded-full text-xs"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <input type="number" x-model.number="major.chi_tieu" class="w-20 text-center border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex flex-col items-center">
                                    <input type="number" step="0.001" min="0" max="30" x-model.number="major.diem_chuan_nhap" class="w-24 text-center border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-bold text-blue-600 mb-2">
                                    <input type="range" step="0.25" min="15" max="30" x-model.number="major.diem_chuan_nhap" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span 
                                    x-text="major.so_luong_dat" 
                                    class="inline-flex items-center justify-center px-3 py-1 text-sm font-bold leading-none text-white rounded-full bg-green-500"
                                    :class="{'bg-red-500': major.so_luong_dat < major.chi_tieu * 0.5, 'bg-green-500': major.so_luong_dat >= major.chi_tieu * 0.8, 'bg-yellow-500': major.so_luong_dat >= major.chi_tieu * 0.5 && major.so_luong_dat < major.chi_tieu * 0.8}"
                                ></span>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="majors.length === 0 && !isLoading">
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Không có dữ liệu ngành nào có thí sinh đăng ký trong đợt này.
                        </td>
                    </tr>
                    <tr x-show="isLoading">
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-spinner fa-spin text-2xl mb-2 text-red-600"></i><br>
                            Đang tải dữ liệu...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function virtualFilterApp() {
    return {
        selectedBatch: '',
        majors: [],
        isLoading: false,
        isCalculating: false,
        isFiltering: false,
        message: '',
        messageType: 'info', // 'success', 'error', 'info'

        showMessage(msg, type = 'info') {
            this.message = msg;
            this.messageType = type;
            setTimeout(() => { this.message = ''; }, 10000);
        },

        loadBatchData() {
            if (!this.selectedBatch) {
                this.majors = [];
                return;
            }
            
            this.isLoading = true;
            this.majors = [];
            
            fetch('/TS/admin/admission/virtual-filter/api-load?batch_id=' + this.selectedBatch)
                .then(response => response.json())
                .then(data => {
                    this.isLoading = false;
                    if (data.status) {
                        this.majors = data.majors;
                    } else {
                        this.showMessage(data.message || 'Lỗi tải dữ liệu', 'error');
                    }
                })
                .catch(error => {
                    this.isLoading = false;
                    this.showMessage('Lỗi kết nối máy chủ', 'error');
                });
        },

        recalculateScores() {
            if (!confirm('Hệ thống sẽ tính điểm lại cho TẤT CẢ thí sinh trong đợt nếu họ chưa có điểm xét tuyển. Quá trình này có thể mất tới vài phút. Bạn có chắc chắn muốn tiếp tục?')) {
                return;
            }

            this.isCalculating = true;
            this.showMessage('Hệ thống đang chạy hàm tìm tổ hợp/phương thức tối ưu cho hàng vạn nguyện vọng. Vui lòng KHÔNG đóng trình duyệt...', 'info');

            const formData = new FormData();
            formData.append('batch_id', this.selectedBatch);
            // formData.append('force', '1'); // Uncomment nếu muốn bắt buộc tính lại từ số 0 kể cả ng đã có điểm

            fetch('/TS/admin/admission/virtual-filter/api-recalculate', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                this.isCalculating = false;
                if (data.status) {
                    this.showMessage(data.message, 'success');
                } else {
                    this.showMessage(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(error => {
                this.isCalculating = false;
                this.showMessage('Lỗi kết nối máy chủ', 'error');
            });
        },

        runFilter() {
            this.isFiltering = true;
            this.showMessage('Đang chạy thuật toán Lọc Ảo Vét cạn (Dây chuyền)...', 'info');

            const formData = new FormData();
            formData.append('batch_id', this.selectedBatch);
            
            // Build benchmarks map
            this.majors.forEach(m => {
                formData.append('benchmarks[' + m.ma_nganh + ']', m.diem_chuan_nhap);
                formData.append('quotas[' + m.ma_nganh + ']', m.chi_tieu);
            });

            fetch('/TS/admin/admission/virtual-filter/api-run', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                this.isFiltering = false;
                if (data.status) {
                    this.showMessage('Lọc Ảo thành công! Số liệu đã được cập nhật.', 'success');
                    // Cập nhật lại cột Số Đậu
                    if (data.data) {
                        this.majors.forEach(m => {
                           if (data.data[m.ma_nganh] !== undefined) {
                               m.so_luong_dat = data.data[m.ma_nganh];
                           } else {
                               m.so_luong_dat = 0;
                           }
                        });
                    }
                } else {
                    this.showMessage(data.message || 'Lỗi lọc ảo', 'error');
                }
            })
            .catch(error => {
                this.isFiltering = false;
                this.showMessage('Lỗi kết nối máy chủ', 'error');
            });
        }
    }
}
</script>
