<?php
$title = 'Tính điểm xét tuyển - Đại học Hùng Vương';
include __DIR__ . '/layouts/header.php';
?>

<div class="relative bg-gradient-to-br from-red-900 via-hvu-red to-red-800 text-white overflow-hidden py-10">
    <div class="container mx-auto px-4 text-center relative z-10">
        <h1 class="text-2xl md:text-4xl font-black font-heading leading-tight mb-2 uppercase">
            TÍNH ĐIỂM XÉT TUYỂN VÀO HVU
        </h1>
        <p class="text-red-100 font-medium max-w-2xl mx-auto opacity-90 text-sm">
            Công cụ hỗ trợ thí sinh xác định điểm xét tuyển và lựa chọn tổ hợp tối ưu nhất năm 2026.
        </p>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f9fafb; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 20px; }
button:active { transform: scale(0.98); }
[x-cloak] { display: none !important; }

/* Định dạng tường minh cho các nút để tránh bị trùng màu nền */
.btn-hvu-sync {
    background-color: #BE1E2D !important;
    color: #ffffff !important;
}
.btn-hvu-sync:hover {
    background-color: #9b1825 !important;
}
.btn-hvu-calculate {
    background-color: #0e5c7e !important;
    color: #ffffff !important;
}
.btn-hvu-calculate:hover {
    background-color: #0a4661 !important;
}

/* UI Redesign Updates */
.card-glass {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.result-gradient {
    background: linear-gradient(135deg, #BE1E2D 0%, #80141E 100%);
}
.score-number {
    font-family: 'Inter', sans-serif;
    letter-spacing: -0.05em;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}
.shimmer-text {
    background: linear-gradient(90deg, #fff 0%, #ffccd0 50%, #fff 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: shimmer 3s linear infinite;
}
@keyframes shimmer {
    to { background-position: 200% center; }
}
</style>

<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="calculatorApp()" style="font-family: 'Inter', sans-serif;">
    
    <!-- GRID 3 CỘT - BỐ TRÍ ĐIỂM -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-10">
        
        <!-- CỘT 1: ĐIỂM HỌC BẠ -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center min-h-[64px]">
                <h3 class="font-bold text-gray-800 uppercase text-xs flex items-center">
                    <i class="fas fa-book-open text-hvu-red mr-2 text-sm"></i> Điểm học bạ
                </h3>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-gray-50 text-gray-600 uppercase">
                        <tr>
                            <th class="border border-gray-200 px-3 py-2 text-left w-2/5">Môn học</th>
                            <th class="border border-gray-200 px-1 py-2 text-center">Lớp 10</th>
                            <th class="border border-gray-200 px-1 py-2 text-center">Lớp 11</th>
                            <th class="border border-gray-200 px-1 py-2 text-center">Lớp 12</th>
                        </tr>
                    </thead>
                    <tbody class="text-black">
                        <template x-for="(sub, label) in subjects" :key="sub">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="border border-gray-200 px-3 py-2 font-normal bg-gray-50/30" x-text="label"></td>
                                <td class="border border-gray-200 p-0">
                                    <input type="number" step="0.1" min="0" max="10" x-model.number="scores[sub].gr10" 
                                           class="w-full text-center p-2 border-0 focus:ring-0 placeholder-gray-300" placeholder="0.00">
                                </td>
                                <td class="border border-gray-200 p-0">
                                    <input type="number" step="0.1" min="0" max="10" x-model.number="scores[sub].gr11" 
                                           class="w-full text-center p-2 border-0 focus:ring-0 placeholder-gray-300" placeholder="0.00">
                                </td>
                                <td class="border border-gray-200 p-0">
                                    <input type="number" step="0.1" min="0" max="10" x-model.number="scores[sub].gr12" 
                                           class="w-full text-center p-2 border-0 focus:ring-0 placeholder-gray-300" placeholder="0.00">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CỘT 2: ĐIỂM THI TN THPT -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center min-h-[64px]">
                <h3 class="font-bold text-gray-800 uppercase text-xs flex items-center">
                    <i class="fas fa-pen-nib text-hvu-red mr-2 text-sm"></i> Điểm thi TN THPT
                </h3>
            </div>
            <div class="flex-1 overflow-y-auto">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-gray-50 text-gray-600 uppercase">
                        <tr>
                            <th class="border border-gray-200 px-3 py-2 text-left">Môn học</th>
                            <th class="border border-gray-200 px-3 py-2 text-center">Điểm</th>
                        </tr>
                    </thead>
                    <tbody class="text-black">
                        <template x-for="(sub, label) in thptSubjects" :key="'exam-'+sub">
                            <tr>
                                <td class="border border-gray-200 px-3 py-2 font-normal bg-gray-50/30" x-text="label"></td>
                                <td class="border border-gray-200 p-0">
                                    <input type="number" step="0.01" min="0" max="10" x-model.number="scores[sub].exam" 
                                           class="w-full text-center p-2 border-0 focus:ring-0 placeholder-gray-300" placeholder="0.00">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="p-4 bg-red-50 border-t border-gray-200">
                    <p class="text-[10px] text-red-600 leading-relaxed font-medium">
                        * Nhập điểm thi tốt nghiệp THPT Quốc gia năm 2026 để tính toán cho phương thức xét điểm thi (TS01).
                    </p>
                </div>
            </div>
        </div>

        <!-- CỘT 3: ĐIỂM HỌC BẠ QUY ĐỔI -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between min-h-[64px]">
                <h3 class="font-bold text-gray-800 uppercase text-xs flex items-center">
                    <i class="fas fa-calculator text-hvu-red mr-2 text-sm"></i> Điểm quy đổi: 
                    <input type="number" step="0.01" x-model.number="coefficient" 
                           class="ml-2 w-16 p-1 text-center border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 font-black text-blue-700 bg-white">
                </h3>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-gray-50 text-gray-600 uppercase">
                        <tr>
                            <th class="border border-gray-200 px-3 py-2 text-left">Môn học</th>
                            <th class="border border-gray-200 px-1 py-2 text-center">Điểm TBC</th>
                            <th class="border border-gray-200 px-1 py-2 text-center text-blue-600">Điểm QĐ</th>
                        </tr>
                    </thead>
                    <tbody class="text-black font-normal">
                        <template x-for="(sub, label) in subjects" :key="'qd-'+sub">
                            <tr class="hover:bg-blue-50/30 transition-colors">
                                <td class="border border-gray-200 px-3 py-2 font-normal bg-gray-50/30" x-text="label"></td>
                                <td class="border border-gray-200 px-1 py-2 text-center font-mono" x-text="getTBC(sub)"></td>
                                <td class="border border-gray-200 px-1 py-2 text-center text-black font-mono" x-text="getQD(sub)"></td>
                          <!-- KHỐI DASHBOARD 3 CỘT: DỰ TUYỂN | BẢNG TỔ HỢP | KẾT QUẢ -->
    <div class="bg-white rounded-[2rem] shadow-xl border border-gray-200 overflow-hidden mt-10">
        <div class="grid grid-cols-1 lg:grid-cols-3">
            
            <!-- CỘT 1: NHẬP THÔNG TIN DỰ TUYỂN -->
            <div class="p-6 md:p-8 border-b lg:border-b-0 lg:border-r border-gray-100 bg-white">
                <h2 class="text-lg font-black text-gray-900 uppercase mb-8 flex items-center tracking-tight">
                    <span class="bg-hvu-red/10 text-hvu-red w-8 h-8 rounded-full flex items-center justify-center mr-3 text-xs"><i class="fas fa-user-graduate"></i></span>
                    Thông tin dự tuyển
                </h2>
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 mb-2 ml-1 uppercase tracking-widest">Ngành đào tạo</label>
                        <div class="relative">
                            <select x-model="form.majorCode" class="w-full rounded-xl border border-gray-200 shadow-sm focus:border-hvu-red focus:ring-hvu-red p-3.5 transition-all appearance-none cursor-pointer bg-gray-50 hover:bg-white text-gray-900 text-sm font-semibold">
                                <option value="">-- Tính tất cả các ngành --</option>
                                <?php foreach ($majors as $major): ?>
                                    <option value="<?= htmlspecialchars($major['ma_nganh']) ?>">
                                        <?= htmlspecialchars($major['ten_nganh'] . ' (' . str_replace(['.1', '.2'], '', $major['ma_nganh']) . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-400">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 mb-2 ml-1 uppercase tracking-widest">Đối tượng ưu tiên</label>
                        <select x-model="form.doiTuong" class="w-full rounded-xl border border-gray-200 shadow-sm focus:border-hvu-red focus:ring-hvu-red p-3.5 transition-all bg-gray-50 hover:bg-white text-gray-900 text-sm font-medium">
                            <option value="">Không có ưu tiên (0đ)</option>
                            <optgroup label="Nhóm ưu tiên 1 (+2.0đ)">
                                <option value="DT1">Đối tượng 01; 02; 03; 04</option>
                            </optgroup>
                            <optgroup label="Nhóm ưu tiên 2 (+1.0đ)">
                                <option value="DT2">Đối tượng 05; 06; 07</option>
                            </optgroup>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 mb-2 ml-1 uppercase tracking-widest">Khu vực ưu tiên</label>
                        <select x-model="form.khuVuc" class="w-full rounded-xl border border-gray-200 shadow-sm focus:border-hvu-red focus:ring-hvu-red p-3.5 transition-all bg-gray-50 hover:bg-white text-gray-900 text-sm font-medium">
                            <option value="KV3">KV3 (0 điểm)</option>
                            <option value="KV2">KV2 (+0.25 điểm)</option>
                            <option value="KV2-NT">KV2-NT (+0.5 điểm)</option>
                            <option value="KV1">KV1 (+0.75 điểm)</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-8">
                    <button @click="calculateBestResult" class="btn-hvu-calculate px-8 py-5 font-black rounded-xl shadow-2xl transition-all uppercase tracking-[0.1em] text-[10px] w-full text-white flex items-center justify-center group overflow-hidden relative">
                        <span class="relative z-10 flex items-center">
                            <i class="fas fa-calculator mr-2 group-hover:rotate-12 transition-transform"></i>
                            Tính điểm xét tuyển
                        </span>
                        <div class="absolute inset-0 bg-white/10 translate-x-full group-hover:translate-x-0 transition-transform skew-x-12 duration-500"></div>
                    </button>
                </div>
            </div>

            <!-- CỘT 2: BẢNG TỔ HỢP ĐỦ ĐIỂM -->
            <div class="p-6 md:p-8 border-b lg:border-b-0 lg:border-r border-gray-100 bg-gray-50/20 flex flex-col min-h-[400px]">
                <h3 class="text-[10px] font-black text-gray-400 uppercase mb-6 flex items-center tracking-[0.2em] ml-1">
                    <i class="fas fa-list-ol text-hvu-red mr-2 text-xs opacity-50"></i> Tổ hợp đủ điểm vào ngành
                </h3>
                
                <div x-show="!bestItem" class="flex-1 flex flex-col items-center justify-center text-center opacity-30 select-none py-20">
                    <i class="fas fa-table text-3xl mb-3 text-gray-300"></i>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Đang chờ tính toán...</p>
                </div>

                <div x-show="bestItem" x-cloak x-transition class="space-y-4">
                    <div class="overflow-hidden rounded-2xl border border-gray-200 shadow-sm bg-white">
                        <table class="w-full text-[11px]">
                            <thead class="bg-gray-50/80 text-gray-500 font-bold uppercase text-[9px] tracking-wider">
                                <tr>
                                    <th class="px-3 py-3 text-left border-b">Tổ hợp</th>
                                    <th class="px-2 py-3 text-center border-b">TS01</th>
                                    <th class="px-2 py-3 text-center border-b">TS02</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-black">
                                <template x-for="item in validCombinations" :key="item.ma_to_hop">
                                    <tr class="hover:bg-blue-50/50 transition-colors" :class="bestItem && bestItem.ma_to_hop === item.ma_to_hop ? 'bg-green-50/80' : ''">
                                        <td class="px-3 py-3 font-black text-gray-700" x-text="item.ma_to_hop"></td>
                                        <td class="px-2 py-3 text-center font-mono font-bold text-gray-900" x-text="item.ts01.toFixed(2)"></td>
                                        <td class="px-2 py-3 text-center font-mono font-bold text-gray-900" x-text="item.ts02.toFixed(2)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- CỘT 3: KẾT QUẢ TỐI ƯU NHẤT -->
            <div class="p-6 md:p-8 bg-gray-50/50 flex flex-col justify-center">
                <div x-show="!bestItem" class="text-center py-20 select-none group text-gray-400 opacity-30">
                    <div class="mb-4"><i class="fas fa-chart-line text-3xl"></i></div>
                    <p class="text-[10px] uppercase font-bold tracking-widest">Kết quả tối ưu</p>
                </div>
                <div x-show="bestItem" x-cloak x-transition class="space-y-4">
                    <!-- BEST COMBINATION CARD -->
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-xl border border-gray-100 relative overflow-hidden group">
                        <div class="flex flex-col relative z-10">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[8px] font-black bg-green-100 text-green-700 uppercase tracking-widest mb-2 border border-green-200 w-fit">
                                <i class="fas fa-star mr-1"></i> KHUYÊN DÙNG
                            </span>
                            <div class="flex justify-between items-end">
                                <div>
                                    <h3 class="text-3xl font-black text-gray-900 score-number" x-text="bestItem.score.toFixed(2)"></h3>
                                    <div class="mt-0.5 text-[10px] font-bold text-gray-500 uppercase" x-text="bestItem.ma_to_hop"></div>
                                </div>
                                <div class="text-[9px] font-medium text-gray-400 text-right italic" x-text="bestItem.methodName"></div>
                            </div>
                        </div>
                    </div>

                    <div class="result-gradient p-6 rounded-[2rem] shadow-[0_15px_35px_rgba(190,30,45,0.25)] text-white relative overflow-hidden text-center">
                        <h4 class="text-[9px] text-white/70 font-black uppercase tracking-[0.2em] mb-2">Tổng điểm xét tuyển</h4>
                        <div class="text-5xl font-black score-number shimmer-text" x-text="bestItem.finalTotal.toFixed(2)"></div>
                        <div class="mt-3 inline-flex items-center bg-black/20 px-3 py-1 rounded-full text-[9px] font-bold backdrop-blur-sm">
                            Hệ số: <span class="ml-1 text-white" x-text="bestItem.convPrio.toFixed(2)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f9fafb; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 20px; }
button:active { transform: scale(0.98); }
[x-cloak] { display: none !important; }
</style>

<script>
function calculatorApp() {
    return {
        // Master Data passed from PHP
        subjects: <?= json_encode($subjects) ?>,
        thptSubjects: <?= json_encode($thptSubjects) ?>,
        combinationsList: <?= json_encode($combinations) ?>,
        majorsList: <?= json_encode($majors) ?>,
        priorityMeta: <?= json_encode($priorityData) ?>,
        
        // App State
        coefficient: 0.95,
        scores: {}, // mapped at init
        form: {
            majorCode: '',
            khuVuc: 'KV3',
            doiTuong: ''
        },
        validCombinations: [],
        bestItem: null,

        // Map Internal Keys to Database Subject IDs/Codes
        // Since $combinations contains mon_1_id, mon_2_id... we might need a lookup
        // But wait, $combinations already contains mon1_ma, mon2_ma...
        subjectCodeMap: {
            'ngu_van': 'VA', 'toan': 'TO', 'lich_su': 'SU', 'dia_li': 'DI', 
            'gdkt_pl': 'GDKTPL', 'vat_li': 'LI', 'hoa_hoc': 'HO', 'sinh_hoc': 'SI', 
            'cong_nghe': 'CN', 'tin_hoc': 'TH', 'ngoai_ngu': 'N1'
        },

        init() {
            // Initialize scores structure
            Object.values(this.subjects).forEach(k => {
                this.scores[k] = { gr10: null, gr11: null, gr12: null, exam: null };
            });
            // Ensure thptSubjects keys are covered
            Object.values(this.thptSubjects).forEach(k => {
                if (!this.scores[k]) this.scores[k] = { gr10: null, gr11: null, gr12: null, exam: null };
            });
        },

        getTBC(key) {
            const s = this.scores[key];
            if (s.gr10 === null || s.gr11 === null || s.gr12 === null) return '-';
            const val = (parseFloat(s.gr10) + parseFloat(s.gr11) + parseFloat(s.gr12)) / 3;
            return isNaN(val) ? '-' : val.toFixed(3);
        },

        getQD(key) {
            const tbc = this.getTBC(key);
            if (tbc === '-') return '-';
            const val = parseFloat(tbc) * this.coefficient;
            return isNaN(val) ? '-' : val.toFixed(3);
        },

        triggerToHopCalc() {
            const results = [];
            let hasAny = false;
            
            // Get allowed combinations for current major if selected
            let allowedCombs = [];
            if (this.form.majorCode) {
                const major = this.majorsList.find(m => m.ma_nganh === this.form.majorCode);
                if (major && major.combination_list) {
                    allowedCombs = major.combination_list.split(',').map(s => s.trim());
                }
            }

            this.combinationsList.forEach(comb => {
                // Filter by major if selected
                if (allowedCombs.length > 0 && !allowedCombs.includes(comb.ma_to_hop)) {
                    return;
                }

                // Find internal keys for the subjects in this combo
                const key1 = this.findKeyByMa(comb.mon1_ma);
                const key2 = this.findKeyByMa(comb.mon2_ma);
                const key3 = this.findKeyByMa(comb.mon3_ma);

                if (!key1 || !key2 || !key3) return;

                // TS01 Point (Exam)
                const e1 = this.scores[key1]?.exam;
                const e2 = this.scores[key2]?.exam;
                const e3 = this.scores[key3]?.exam;
                const ts01 = (e1 !== null && e2 !== null && e3 !== null) ? (parseFloat(e1) + parseFloat(e2) + parseFloat(e3)) : 0;

                // TS02 Point (Converted Học bạ)
                const q1 = this.getQD(key1);
                const q2 = this.getQD(key2);
                const q3 = this.getQD(key3);
                const ts02 = (q1 !== '-' && q2 !== '-' && q3 !== '-') ? (parseFloat(q1) + parseFloat(q2) + parseFloat(q3)) : 0;

                if (ts01 > 0 || ts02 > 0) {
                    hasAny = true;
                    results.push({
                        ma_to_hop: comb.ma_to_hop,
                        details: `${comb.mon1_ma}-${comb.mon2_ma}-${comb.mon3_ma}`,
                        ts01: ts01,
                        ts02: ts02
                    });
                }
            });

            if (!hasAny) {
                alert("Vui lòng nhập đầy đủ điểm ít nhất 3 môn của một tổ hợp hợp lệ cho ngành này để tính kết quả.");
                return;
            }

            this.validCombinations = results.sort((a, b) => Math.max(b.ts01, b.ts02) - Math.max(a.ts01, a.ts02));
        },

        findKeyByMa(ma) {
            // Map common DB codes to our internal keys
            const map = {
                'TO': 'toan', 'VA': 'ngu_van', 'SU': 'lich_su', 'DI': 'dia_li',
                'LI': 'vat_li', 'HO': 'hoa_hoc', 'SI': 'sinh_hoc', 'GDCD': 'gdkt_pl',
                'GDKTPL': 'gdkt_pl', 'CN': 'cong_nghe', 'TH': 'tin_hoc', 'N1': 'ngoai_ngu'
            };
            return map[ma] || null;
        },

        getRawPriority() {
            let p = 0;
            p += this.priorityMeta[this.form.khuVuc] || 0;
            p += this.priorityMeta[this.form.doiTuong] || 0;
            return p;
        },

        calculateBestResult() {
            // First refresh combinations if not already done or points changed
            this.triggerToHopCalc();
            if (this.validCombinations.length === 0) return;

            // Simple validation
            // if (!this.form.khuVuc) { alert("Vui lòng chọn Khu vực ưu tiên"); return; }

            const rawPrio = this.getRawPriority();
            let best = null;

            this.validCombinations.forEach(c => {
                // Check TS01
                if (c.ts01 > 0) {
                    const convPrio = c.ts01 > 22.5 ? ((30 - c.ts01) / 7.5) * rawPrio : rawPrio;
                    const total = c.ts01 + convPrio;
                    if (!best || total > best.finalTotal) {
                        best = { 
                            ...c, score: c.ts01, method: 'TS01', methodName: 'Xét điểm thi TN THPT', 
                            rawPrio, convPrio, finalTotal: total 
                        };
                    }
                }
                // Check TS02
                if (c.ts02 > 0) {
                    const convPrio = c.ts02 > 22.5 ? ((30 - c.ts02) / 7.5) * rawPrio : rawPrio;
                    const total = c.ts02 + convPrio;
                    if (!best || total > best.finalTotal) {
                        best = { 
                            ...c, score: c.ts02, method: 'TS02', methodName: 'Xét học bạ quy đổi', 
                            rawPrio, convPrio, finalTotal: total 
                        };
                    }
                }
            });

            this.bestItem = best;
        }
    }
}
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>
