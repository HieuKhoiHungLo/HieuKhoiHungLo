<?php
$title = 'Xét tuyển - Lọc ảo';
ob_start();

// Tính tổng số môn/tổ hợp tối đa để tạo cột. (Tạm thời lấy 6 tổ hợp phổ biến hoặc động)
$comboCols = [];
if (!empty($combinations)) {
    foreach ($combinations as $c) {
        $comboCols[] = $c['ma_to_hop'];
    }
}
?>

<div class="h-full flex flex-col p-6 bg-slate-50 relative" x-data="virtualAdmission()">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Xét Tuyển Lọc Ảo</h1>
            <p class="text-sm text-slate-500 mt-1">Bảng tổng hợp điểm đa tổ hợp và xét tuyển tự động</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3">
            <!-- Filter by Year -->
            <select id="yearFilter" class="border-slate-300 rounded-lg text-sm bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-2 min-w-[120px]" x-model="selectedYear" @change="selectedSession = ''; loadData()">
                <option value="">-- Năm --</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>"><?= $year ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Filter by Session (Filtered by selectedYear in Alpine) -->
            <select id="sessionFilter" class="border-slate-300 rounded-lg text-sm bg-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-2 min-w-[200px]" x-model="selectedSession" @change="loadData()">
                <option value="">-- Chọn đợt xét tuyển --</option>
                <template x-for="session in filteredSessions" :key="session.id">
                    <option :value="session.id" x-text="session.ten_dot || session.ten_dot_xet_tuyen"></option>
                </template>
            </select>
            
            <button @click="recalculate()" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2" :disabled="isLoading || !selectedSession">
                <i class="fas fa-calculator" :class="{'fa-spin': isCalculating}"></i> 
                <span>Tính điểm tất cả</span>
            </button>
            
            <button @click="runVirtualFilter()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2" :disabled="isLoading || !selectedSession">
                <i class="fas fa-magic" :class="{'fa-spin': isFiltering}"></i>
                <span>Chạy Lọc Ảo</span>
            </button>
            
            <button @click="exportData()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2" :disabled="isLoading || !selectedSession">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 flex-1 flex flex-col overflow-hidden relative">
        <!-- Overlay Loading -->
        <div x-show="isLoading" class="absolute inset-x-0 inset-y-0 bg-white/70 backdrop-blur-sm z-20 flex flex-col items-center justify-center">
            <i class="fas fa-circle-notch fa-spin text-4xl text-indigo-500 mb-4"></i>
            <p class="text-lg font-semibold text-slate-700" x-text="loadingMessage"></p>
        </div>

        <div class="px-4 py-3 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <h2 class="font-semibold text-slate-700 flex items-center gap-2">
                <i class="fas fa-list text-slate-400"></i> Bảng Lưới Thí Sinh
            </h2>
            <div class="text-sm font-medium text-slate-500">
                Hiển thị: <span class="text-indigo-600" id="rowCount">0</span> nguyện vọng
            </div>
        </div>

        <!-- Table Container -->
        <div class="flex-1 min-h-0 relative overflow-auto custom-scrollbar w-full">
            <table id="virtualGrid" class="w-full text-left border-collapse whitespace-nowrap text-sm" style="width:100%">
                <thead class="sticky top-0 z-10 bg-slate-100">
                    <tr class="text-slate-600 uppercase tracking-wider text-xs">
                        <th class="py-2 px-3 border border-slate-200 font-bold bg-slate-100 sticky left-0 z-20">CCCD/CMND</th>
                        <th class="py-2 px-3 border border-slate-200 font-bold bg-slate-100 sticky left-[120px] z-20">Họ và Tên</th>
                        <th class="py-2 px-3 border border-slate-200 font-bold bg-slate-100">Ngành (Mã)</th>
                        <th class="py-2 px-3 border border-slate-200 font-bold bg-slate-100 text-center">NV</th>
                        
                        <!-- Dynamic Combo Columns -->
                        <?php foreach ($comboCols as $col): ?>
                            <th class="py-2 px-3 border border-slate-200 font-bold bg-amber-50 text-center text-amber-800" title="Học bạ <?= $col ?>">HB <?= $col ?></th>
                            <th class="py-2 px-3 border border-slate-200 font-bold bg-blue-50 text-center text-blue-800" title="THPT <?= $col ?>">THPT <?= $col ?></th>
                        <?php endforeach; ?>

                        <th class="py-2 px-3 border border-slate-200 font-bold bg-emerald-50 text-emerald-800 text-center">Tổ hợp Max</th>
                        <th class="py-2 px-3 border border-slate-200 font-bold bg-emerald-50 text-emerald-800 text-center">PT Max</th>
                        <th class="py-2 px-3 border border-slate-200 font-bold bg-indigo-50 text-center text-indigo-800">Điểm M1</th>
                        <th class="py-2 px-3 border border-slate-200 font-bold bg-indigo-50 text-center text-indigo-800">Điểm M2</th>
                        <th class="py-2 px-3 border border-slate-200 font-bold bg-indigo-50 text-center text-indigo-800">Điểm M3</th>
                        <th class="py-2 px-3 border border-slate-200 font-bold bg-indigo-50 text-center text-indigo-800">ƯT Gốc</th>
                        <th class="py-2 px-3 border border-slate-200 font-bold bg-indigo-50 text-center text-indigo-800">ƯT QĐ</th>
                        <th class="py-2 px-3 border border-slate-200 font-bold bg-slate-800 text-white text-center">Tổng Điểm</th>
                        <th class="py-2 px-3 border border-slate-200 font-bold bg-red-50 text-red-800 text-center">Trạng Thái</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 divide-y divide-slate-100 bg-white">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function virtualAdmission() {
        return {
            selectedYear: '',
            selectedSession: '',
            allSessions: <?= json_encode($sessions) ?>,
            isLoading: false,
            isCalculating: false,
            isFiltering: false,
            loadingMessage: 'Đang tải...',
            dt: null,
            combos: <?= json_encode($comboCols) ?>,

            get filteredSessions() {
                if (!this.selectedYear) return this.allSessions;
                return this.allSessions.filter(s => s.nam_tuyen_sinh == this.selectedYear);
            },

            init() {
                this.initDataTable();
            },

            initDataTable() {
                var self = this;
                
                // Build dynamic columns
                var columns = [
                    { data: 'so_cccd', className: 'font-mono text-indigo-600 font-medium sticky left-0 bg-white shadow-[1px_0_0_#e2e8f0] z-10' },
                    { data: 'ho_va_ten', className: 'font-semibold text-slate-800 sticky left-[120px] bg-white shadow-[1px_0_0_#e2e8f0] z-10 min-w-[200px]' },
                    { data: 'ma_nganh', className: 'font-mono text-slate-500' },
                    { data: 'thu_tu_nv', className: 'text-center font-bold text-slate-600' },
                ];

                // Append Combo Columns
                this.combos.forEach(c => {
                    // Hoc Ba
                    columns.push({
                        data: 'chi_tiet_diem',
                        className: 'text-center text-amber-700 bg-amber-50/30',
                        render: function(data) {
                            if (!data) return '-';
                            try {
                                let parsed = JSON.parse(data);
                                let combs = parsed.all_combinations || {};
                                return combs['HB_' + c] ? parseFloat(combs['HB_' + c]).toFixed(2) : '-';
                            } catch(e) { return '-'; }
                        }
                    });
                    // THPT
                    columns.push({
                        data: 'chi_tiet_diem',
                        className: 'text-center text-blue-700 bg-blue-50/30',
                        render: function(data) {
                            if (!data) return '-';
                            try {
                                let parsed = JSON.parse(data);
                                let combs = parsed.all_combinations || {};
                                return combs['THPT_' + c] ? parseFloat(combs['THPT_' + c]).toFixed(2) : '-';
                            } catch(e) { return '-'; }
                        }
                    });
                });

                // Append other static columns
                columns.push({ data: 'to_hop_toi_uu', className: 'text-center font-bold text-emerald-700 bg-emerald-50/50' });
                columns.push({ 
                    data: 'phuong_thuc_toi_uu', 
                    className: 'text-center font-bold text-emerald-700 bg-emerald-50/50',
                    render: function(data) {
                        return data == '100' ? 'THPT' : (data == '200' ? 'Học bạ' : data);
                    }
                });
                
                // M1, M2, M3 from raw columns
                columns.push({ data: 'diem_mon_1', className: 'text-center bg-indigo-50/30', render: function(d) { return d ? parseFloat(d).toFixed(2) : '-'; } });
                columns.push({ data: 'diem_mon_2', className: 'text-center bg-indigo-50/30', render: function(d) { return d ? parseFloat(d).toFixed(2) : '-'; } });
                columns.push({ data: 'diem_mon_3', className: 'text-center bg-indigo-50/30', render: function(d) { return d ? parseFloat(d).toFixed(2) : '-'; } });
                // Prior
                columns.push({ data: 'chi_tiet_diem', className: 'text-center bg-indigo-50/30', render: function(data) {
                    if (!data) return '-';
                    try { let p = JSON.parse(data); return p.priority_raw ? p.priority_raw : '-'; } catch(e){return '-'}
                }});
                columns.push({ data: 'chi_tiet_diem', className: 'text-center bg-indigo-50/30', render: function(data) {
                    if (!data) return '-';
                    try { let p = JSON.parse(data); return p.priority_converted ? p.priority_converted : '-'; } catch(e){return '-'}
                }});

                // Final Score
                columns.push({ 
                    data: 'diem_xet_tuyen', 
                    className: 'text-center font-black text-slate-800 bg-slate-100',
                    render: function(data) { return data ? parseFloat(data).toFixed(2) : '-'; }
                });

                // Status Trúng tuyển
                columns.push({
                    data: 'trang_thai_trung_tuyen',
                    className: 'text-center',
                    render: function(data) {
                        if (data == 1 || data === true || data === '1') {
                            return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800 border border-green-200 shadow-sm"><i class="fas fa-check mr-1"></i> Trúng Tuyển</span>';
                        }
                        return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 border border-red-200">Không đạt</span>';
                    }
                });

                this.dt = $('#virtualGrid').DataTable({
                    data: [],
                    columns: columns,
                    scrollX: true,
                    scrollY: 'calc(100vh - 300px)',
                    scrollCollapse: true,
                    paging: true,
                    pageLength: 50,
                    lengthMenu: [50, 100, 200, 500, 1000],
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json' },
                    dom: '<"flex justify-between items-center p-3 border-b border-slate-200"lf>rt<"flex justify-between items-center p-3"ip>',
                    fixedColumns: {
                        leftColumns: 2 // stick CCCD + Name
                    },
                    initComplete: function() {
                         $('.dataTables_filter input').addClass('w-full sm:w-64 pl-3 pr-4 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500');
                    }
                });
            },

            loadData() {
                if (!this.selectedSession) {
                    this.dt.clear().draw();
                    document.getElementById('rowCount').textContent = '0';
                    return;
                }
                
                this.isLoading = true;
                this.loadingMessage = 'Đang tải danh sách nguyện vọng...';
                
                $.ajax({
                    url: '/admin/admission/virtual-filter/api-load',
                    type: 'GET',
                    data: { session_id: this.selectedSession },
                    success: (res) => {
                        let parsed = JSON.parse(res);
                        this.dt.clear();
                        this.dt.rows.add(parsed.data).draw();
                        document.getElementById('rowCount').textContent = parsed.data.length;
                        this.isLoading = false;
                    },
                    error: () => {
                        toast.error("Lỗi khi tải dữ liệu");
                        this.isLoading = false;
                    }
                });
            },

            recalculate() {
                if (!confirm('Bạn có chắc chắn muốn TÍNH LẠI ĐIỂM cho tất cả thí sinh được xét duyệt trong đợt này? Quá trình này có thể mất vài phút.')) return;
                
                this.isLoading = true;
                this.isCalculating = true;
                this.loadingMessage = 'Đang tính toán lại điểm đa tổ hợp... Vui lòng không đóng trang.';
                
                $.ajax({
                    url: '/admin/admission/virtual-filter/api-recalculate',
                    type: 'POST',
                    data: { session_id: this.selectedSession },
                    success: (res) => {
                        let parsed = JSON.parse(res);
                        if(parsed.success) {
                            toast.success(parsed.message);
                            this.loadData(); // reload Grid
                        } else {
                            toast.error(parsed.message);
                        }
                    },
                    error: () => toast.error('Lỗi khi tính điểm'),
                    complete: () => {
                        this.isLoading = false;
                        this.isCalculating = false;
                    }
                });
            },

            runVirtualFilter() {
                alert('Tính năng gọi luồng Lọc Ảo tự động đang chờ thiết lập điểm chuẩn. Vui lòng cập nhật điểm chuẩn (Hệ thống -> Lọc Ảo -> Thiết lập điểm).');
                // Could call /admin/admission/virtual-filter/api-run but it needs benchmarks array
            },
            
            exportData() {
                // TBD: Could trigger csv/excel download through an endpoint 
                // Currently DataTables can handle it if we add Buttons extension.
                alert('Tính năng Export Excel chuyên sâu sẽ được bổ sung ở bản sau. Hiện tại bạn có thể dùng Export chung.');
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
</style>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>
