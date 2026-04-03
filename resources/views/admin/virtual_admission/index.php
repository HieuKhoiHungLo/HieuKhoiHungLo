<?php
$title = 'Xét tuyển - Lọc ảo';
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
            
            <div class="flex items-center gap-2 bg-amber-500/10 p-1 pr-3 rounded-lg border border-amber-500/20">
                <button @click="recalculate()" 
                        class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2" 
                        :disabled="isLoading || !selectedSession">
                    <i class="fas fa-calculator" :class="{'fa-spin': isCalculating}"></i> 
                    <span x-text="forceRecalculate ? 'Tính lại toàn bộ' : 'Tính điểm (Smart)'"></span>
                </button>
                <label class="flex items-center gap-1.5 cursor-pointer ml-1 select-none">
                    <input type="checkbox" x-model="forceRecalculate" class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 border-amber-300">
                    <span class="text-[10px] font-bold text-amber-700 uppercase">Toàn bộ</span>
                </label>
            </div>
            
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

                <!-- Animated Icon Container -->
                <div class="relative w-24 h-24 mx-auto mb-6">
                    <div class="absolute inset-0 bg-indigo-500/10 rounded-full animate-pulsing-slow"></div>
                    <div class="absolute inset-2 border-2 border-indigo-200 border-dashed rounded-full animate-spin-slow"></div>
                    <div class="absolute inset-4 bg-gradient-to-tr from-indigo-600 to-violet-600 rounded-full flex items-center justify-center shadow-lg shadow-indigo-200/50">
                        <i class="fas fa-calculator text-3xl text-white" :class="{'fa-calculator': isCalculating, 'fa-sync-alt fa-spin': isSyncing, 'fa-magic': isFiltering}"></i>
                    </div>
                </div>

                <h3 class="text-xl font-bold text-slate-800 mb-2">Đang xử lý dữ liệu</h3>
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

        <div class="px-4 py-3 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <h2 class="font-semibold text-slate-700 flex items-center gap-2">
                <i class="fas fa-list text-slate-400"></i> Bảng Lưới Thí Sinh
            </h2>
            <div class="text-sm font-medium text-slate-500">
                Số hồ sơ: <span class="text-indigo-600" id="candidateCount">0</span> - 
                Số nguyện vọng: <span class="text-indigo-600" id="rowCount">0</span>
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
                        <th rowspan="2" class="py-2 px-2 border border-slate-200 font-bold bg-indigo-50 text-indigo-800 text-[11px] uppercase tracking-tighter">Điểm tổ hợp</th>
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
                this.initDataTable();
                
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

                // M1, M2, M3
                columns.push({ data: 'diem_mon_1', className: 'text-center', render: function(d) { return d !== null && d !== undefined ? parseFloat(d).toFixed(2) : '-'; } });
                columns.push({ data: 'diem_mon_2', className: 'text-center', render: function(d) { return d !== null && d !== undefined ? parseFloat(d).toFixed(2) : '-'; } });
                columns.push({ data: 'diem_mon_3', className: 'text-center', render: function(d) { return d !== null && d !== undefined ? parseFloat(d).toFixed(2) : '-'; } });
                
                // Điểm Tổ Hợp (M1 + M2 + M3)
                columns.push({ 
                    data: null, 
                    className: 'text-center bg-indigo-50 font-bold text-indigo-700',
                    render: function(data, type, row) {
                        let m1 = parseFloat(row.diem_mon_1 || 0);
                        let m2 = parseFloat(row.diem_mon_2 || 0);
                        let m3 = parseFloat(row.diem_mon_3 || 0);
                        let sum = m1 + m2 + m3;
                        return sum > 0 ? sum.toFixed(2) : '-';
                    }
                });

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
                            let note = (p.threshold_note || "").toUpperCase();
                            if(note.indexOf('HỌC LỰC') !== -1) {
                                return `<span class="text-red-600 font-bold" title="${p.threshold_note}">K.ĐẠT</span>`;
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
                            if(note.indexOf('NGƯỠNG') !== -1) {
                                return `<span class="text-red-600 font-bold" title="${p.threshold_note}">K.ĐẠT</span>`;
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
                            document.getElementById('candidateCount').textContent = json.candidate_count || 0;
                            document.getElementById('rowCount').textContent = json.recordsTotal || 0;
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
                if (!this.selectedSession) {
                    this.dt.clear().draw();
                    document.getElementById('candidateCount').textContent = '0';
                    document.getElementById('rowCount').textContent = '0';
                    return;
                }
                
                // Server-Side Processing gánh toàn bộ tải trọng, chỉ cần bắt Ajax gửi lại lệnh Reload mà ko tốn RAM của User
                this.dt.ajax.reload(null, true);
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

            forceRecalculate: false,
            
            recalculate() {
                const isFull = this.forceRecalculate;
                const confirmMsg = isFull 
                    ? 'Bạn có chắc chắn muốn TÍNH LẠI TOÀN BỘ điểm cho tất cả thí sinh? Quá trình này sẽ tốn nhiều thời gian hơn.' 
                    : 'Hệ thống sẽ chỉ tính điểm cho những hồ sơ mới hoặc có thay đổi dữ liệu. Bạn có muốn tiếp tục?';

                if (!confirm(confirmMsg)) return;
                
                this.isCalculating = true;
                this.startLoading(isFull ? 'Đăng khởi tạo hàng chờ tính điểm toàn bộ...' : 'Đang kiểm tra hồ sơ cần cập nhật...', true);
                
                // 1. Get the list of CCCDs first
                $.ajax({
                    url: '<?= url("/admin/api/vf/get-cccds") ?>',
                    data: { 
                        session_id: this.selectedSession,
                        force: isFull ? 1 : 0
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
                        force: this.forceRecalculate ? '1' : '0',
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
                            toast.error(parsed.message || "Lỗi khi lọc ảo");
                        }
                    },
                    error: (xhr) => {
                        let msg = "Lỗi khi kết nối máy chủ";
                        try {
                            if (xhr.responseText && xhr.responseText.includes('{')) {
                                let err = JSON.parse(xhr.responseText);
                                msg = err.message || msg;
                            }
                        } catch(e) {}
                        toast.error(msg);
                    },
                    complete: () => {
                        this.stopLoading();
                    }
                });
            },
            
            exportData() {
                if (!this.selectedSession) return;
                window.location.href = '<?= url("/admin/api/vf/export") ?>?session_id=' + this.selectedSession;
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

[x-cloak] { display: none !important; }
</style>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>
