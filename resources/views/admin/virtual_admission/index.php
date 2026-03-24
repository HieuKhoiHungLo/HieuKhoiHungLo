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

<!-- DataTables & jQuery -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>

<script>
    // Alias for Toast functionality used in this file
    window.toast = {
        success: (msg) => typeof showToast === 'function' ? showToast(msg, 'success') : alert(msg),
        error: (msg) => typeof showToast === 'function' ? showToast(msg, 'error') : alert('Error: ' + msg),
        warning: (msg) => typeof showToast === 'function' ? showToast(msg, 'warning') : alert('Warning: ' + msg),
        info: (msg) => typeof showToast === 'function' ? showToast(msg, 'info') : alert(msg)
    };
</script>

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
            
            <button @click="syncData()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2" :disabled="isLoading || !selectedSession">
                <i class="fas fa-sync-alt" :class="{'fa-spin': isSyncing}"></i> 
                <span>Đồng bộ dữ liệu</span>
            </button>
            
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
                        
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">Điểm M1</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">Điểm M2</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">Điểm M3</th>
                        
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">Điểm QĐ</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">Điểm UT<br>gốc</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">Điểm UT<br>QĐ</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-indigo-50 text-indigo-800 text-[11px]">Điểm xét<br>tuyển</th>
                        
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">ĐK học lực</th>
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-slate-100">ĐK Ngưỡng</th>
                        <th rowspan="2" class="py-2 px-3 border border-slate-200 font-bold bg-slate-100 min-w-[80px]">Kết quả<br>xét tuyển</th>
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
</div>

<script>
    function virtualAdmission() {
        return {
            selectedYear: '',
            selectedSession: '',
            allSessions: <?= json_encode($sessions) ?>,
            isLoading: false,
            isCalculating: false,
            isSyncing: false,
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
                
                // Hàm helper lấy danh sách tên tổ hợp chuẩn của thí sinh (đã sort)
                function getComboNames(rowData) {
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
                                return val !== undefined && val !== null ? parseFloat(val).toFixed(2) : '-';
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
                                return val !== undefined && val !== null ? parseFloat(val).toFixed(2) : '-';
                            } catch(e) { return '-'; }
                        }
                    });
                });

                // To Hop Max, PT Max
                columns.push({ data: 'to_hop_toi_uu', className: 'text-center font-bold' });
                columns.push({ 
                    data: 'phuong_thuc_toi_uu', 
                    className: 'text-center font-bold',
                    render: function(data) {
                        return data == '100' ? 'TS01' : (data == '200' ? 'TS02' : data);
                    }
                });

                // M1, M2, M3
                columns.push({ data: 'diem_mon_1', className: 'text-center', render: function(d) { return d !== null && d !== undefined ? parseFloat(d).toFixed(2) : '-'; } });
                columns.push({ data: 'diem_mon_2', className: 'text-center', render: function(d) { return d !== null && d !== undefined ? parseFloat(d).toFixed(2) : '-'; } });
                columns.push({ data: 'diem_mon_3', className: 'text-center', render: function(d) { return d !== null && d !== undefined ? parseFloat(d).toFixed(2) : '-'; } });

                // QD, UT Goc, UT QD
                columns.push({ data: 'chi_tiet_diem', className: 'text-center', render: function(data) {
                    try { let p = JSON.parse(data); return p.total_raw ? parseFloat(p.total_raw).toFixed(2) : '-'; } catch(e){return '-'}
                }});
                columns.push({ data: 'chi_tiet_diem', className: 'text-center', render: function(data) {
                    try { let p = JSON.parse(data); return p.priority_raw !== undefined ? parseFloat(p.priority_raw).toFixed(2) : '-'; } catch(e){return '-'}
                }});
                columns.push({ data: 'chi_tiet_diem', className: 'text-center', render: function(data) {
                    try { let p = JSON.parse(data); return p.priority_converted !== undefined ? parseFloat(p.priority_converted).toFixed(2) : '-'; } catch(e){return '-'}
                }});

                // Final Score
                columns.push({ 
                    data: 'diem_xet_tuyen', 
                    className: 'text-center font-bold',
                    render: function(data) { return data > 0 ? parseFloat(data).toFixed(2) : '-'; }
                });

                // DK Hoc Luc
                columns.push({ 
                    data: 'chi_tiet_diem', 
                    className: 'text-center text-[10px]',
                    render: function(data) {
                        try {
                            let p = JSON.parse(data);
                            if(p.threshold_note && p.threshold_note.indexOf('HỌC LỰC') !== -1) return '<span title="Không đủ ĐK">-</span>';
                            return '<span>Đạt</span>';
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
                            if(p.threshold_note && p.threshold_note.indexOf('NGƯỠNG') !== -1) return '<span title="Không đủ ĐK">-</span>';
                            return '<span>Đạt</span>';
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

                this.dt = $('#virtualGrid').DataTable({
                    ajax: {
                        url: '<?= url("/admin/admission/virtual-filter/api-load") ?>?session_id=' + (this.selectedSession || 0),
                        dataSrc: 'data',
                        error: (xhr) => {
                            this.isLoading = false;
                            let msg = "Lỗi khi tải dữ liệu";
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
                    lengthMenu: [50, 100, 200, 500, 1000],
                    language: {
                        search: 'Tìm kiếm:',
                        lengthMenu: 'Hiển thị _MENU_ dòng',
                        info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ dòng',
                        infoEmpty: 'Không có dữ liệu',
                        infoFiltered: '(lọc từ _MAX_ dòng)',
                        zeroRecords: 'Không tìm thấy kết quả',
                        paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
                    },
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
                
                this.dt.ajax.url('<?= url("/admin/admission/virtual-filter/api-load") ?>?session_id=' + this.selectedSession).load((json) => {
                    this.isLoading = false;
                    if (json && json.data) {
                        document.getElementById('rowCount').textContent = json.data.length;
                    }
                }, false);
            },

            syncData() {
                if (!this.selectedSession) return;
                
                this.isLoading = true;
                this.isSyncing = true;
                this.loadingMessage = 'Đang đồng bộ dữ liệu từ hồ sơ đã duyệt...';
                
                $.ajax({
                    url: '<?= url("/admin/admission/virtual-filter/api-sync") ?>',
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
                        this.isLoading = false;
                        this.isSyncing = false;
                    }
                });
            },

            recalculate() {
                if (!confirm('Bạn có chắc chắn muốn TÍNH LẠI ĐIỂM cho tất cả thí sinh được xét duyệt trong đợt này? Quá trình này có thể mất vài phút.')) return;
                
                this.isLoading = true;
                this.isCalculating = true;
                this.loadingMessage = 'Đang tính toán lại điểm đa tổ hợp... Vui lòng không đóng trang.';
                
                $.ajax({
                    url: '<?= url("/admin/admission/virtual-filter/api-recalculate") ?>',
                    type: 'POST',
                    data: { 
                        session_id: this.selectedSession,
                        _csrf_token: '<?= csrf_token() ?>'
                    },
                    success: (res) => {
                        let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                        if(parsed.success) {
                            toast.success(parsed.message);
                            this.loadData(); // reload Grid
                        } else {
                            toast.error(parsed.message || parsed.error);
                        }
                    },
                    error: (err) => {
                        let msg = 'Lỗi khi tính điểm';
                        if (err.responseJSON && err.responseJSON.error) {
                            msg = err.responseJSON.error;
                        }
                        toast.error(msg);
                    },
                    complete: () => {
                        this.isLoading = false;
                        this.isCalculating = false;
                    }
                });
            },

            runVirtualFilter() {
                if (!this.selectedSession) return;
                
                // Simple Prompt for now, or we could build a full modal.
                // For a proper implementation, we'd need a list of majors and their benchmarks.
                // Let's implement a basic call that sends current session.
                if (!confirm('Hệ thống sẽ chạy thuật toán lọc ảo dựa trên điểm chuẩn đã thiết lập. Tiếp tục?')) return;

                this.isLoading = true;
                this.isFiltering = true;
                this.loadingMessage = 'Đang chạy thuật toán lọc ảo...';

                $.ajax({
                    url: '<?= url("/admin/admission/virtual-filter/api-run") ?>',
                    type: 'POST',
                    data: {
                        session_id: this.selectedSession,
                        _csrf_token: '<?= csrf_token() ?>',
                        // Note: api-run expects benchmarks array. 
                        // In a real scenario, this would come from a modal.
                        // For now, we'll let the controller handle defaults if empty, 
                        // or we can pass some dummy data if we want to test.
                    },
                    success: (res) => {
                        let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                        if (parsed.status) {
                            toast.success("Lọc ảo hoàn tất!");
                            this.loadData();
                        } else {
                            toast.error(parsed.message || "Lỗi khi lọc ảo");
                        }
                    },
                    error: () => {
                        toast.error("Lỗi khi kết nối máy chủ");
                    },
                    complete: () => {
                        this.isLoading = false;
                        this.isFiltering = false;
                    }
                });
            },
            
            exportData() {
                if (!this.selectedSession) return;
                window.location.href = '<?= url("/admin/admission/virtual-filter/export") ?>?session_id=' + this.selectedSession;
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
