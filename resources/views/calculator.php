<?php
$title = 'Tính điểm xét tuyển - Đại học Hùng Vương';
include __DIR__ . '/layouts/header.php';
?>

<div class="bg-white border-b border-gray-100 pt-3 pb-2">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-xl md:text-2xl font-black font-heading leading-tight mb-0.5 text-blue-800 uppercase tracking-tighter">
            TÍNH ĐIỂM XÉT TUYỂN VÀO HVU
        </h1>
        <p class="text-blue-800 font-bold max-w-2xl mx-auto text-[11px] uppercase tracking-widest">
            Công cụ hỗ trợ thí sinh xác định điểm xét tuyển & chọn tổ hợp tối ưu nhất năm 2026.
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
main { padding-top: 0 !important; }
</style>

<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 pt-1 pb-4" x-data="calculatorApp()" style="font-family: 'Inter', sans-serif;">
    <div class="border-b border-gray-100 pb-2 mb-1">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        
        <!-- CỘT 1: ĐIỂM HỌC BẠ -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center min-h-[40px]">
                <h3 class="font-bold text-hvu-red uppercase text-[10px] flex items-center tracking-widest">
                    <i class="fas fa-book-open text-hvu-red mr-2 text-xs"></i> Nhập điểm học bạ
                </h3>
            </div>
            <div class="overflow-x-auto flex-1 pt-[2px]">
                <table class="w-full text-[11px] border-collapse">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[9px] tracking-wider">
                        <tr>
                            <th class="border border-gray-100 px-3 py-2 text-left w-2/5">Môn học</th>
                            <th class="border border-gray-100 px-1 py-2 text-center text-[8px]">Lớp 10</th>
                            <th class="border border-gray-100 px-1 py-2 text-center text-[8px]">Lớp 11</th>
                            <th class="border border-gray-100 px-1 py-2 text-center text-[8px]">Lớp 12</th>
                        </tr>
                    </thead>
                    <tbody class="text-black">
                        <template x-for="(sub, label) in subjects" :key="sub">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="border border-gray-100 px-3 py-1.5 font-medium bg-gray-50/20" x-text="label"></td>
                                <td class="border border-gray-100 p-0 text-center">
                                    <input type="number" step="0.01" min="0" max="10" x-model="scores[sub].gr10" 
                                           @blur="formatScore(sub, 'gr10')"
                                           class="w-full text-center p-1.5 border-0 focus:ring-0 placeholder-gray-400 bg-transparent score-input font-mono font-bold text-gray-900" placeholder="0.00">
                                </td>
                                <td class="border border-gray-100 p-0 text-center">
                                    <input type="number" step="0.01" min="0" max="10" x-model="scores[sub].gr11" 
                                           @blur="formatScore(sub, 'gr11')"
                                           class="w-full text-center p-1.5 border-0 focus:ring-0 placeholder-gray-400 bg-transparent score-input font-mono font-bold text-gray-900" placeholder="0.00">
                                </td>
                                <td class="border border-gray-100 p-0 text-center">
                                    <input type="number" step="0.01" min="0" max="10" x-model="scores[sub].gr12" 
                                           @blur="formatScore(sub, 'gr12')"
                                           class="w-full text-center p-1.5 border-0 focus:ring-0 placeholder-gray-400 bg-transparent score-input font-mono font-bold text-gray-900" placeholder="0.00">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CỘT 2: ĐIỂM HỌC BẠ QUY ĐỔI (ĐƯA VÀO GIỮA) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between min-h-[40px]">
                <h3 class="font-bold text-hvu-red uppercase text-[9px] flex items-center tracking-widest leading-tight">
                    <i class="fas fa-calculator text-hvu-red mr-2 text-xs"></i> Điểm học bạ quy đổi - Hệ số quy đổi: 
                    <input type="number" step="0.01" x-model.number="coefficient" 
                           class="ml-2 w-14 h-[24px] py-0 text-center border-2 border-blue-400 rounded-lg shadow-sm focus:border-hvu-red focus:ring-hvu-red font-black text-red-600 bg-yellow-50 outline-none transition-all text-[11px]">
                </h3>
            </div>
            <div class="overflow-x-auto flex-1 pt-[2px]">
                <table class="w-full text-[11px] border-collapse">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[9px] tracking-wider">
                        <tr>
                            <th class="border border-gray-100 px-3 py-2 text-left">Môn học</th>
                            <th class="border border-gray-100 px-1 py-2 text-center">TBC</th>
                            <th class="border border-gray-100 px-1 py-2 text-center text-black">Quy đổi</th>
                        </tr>
                    </thead>
                    <tbody class="text-black">
                        <template x-for="(sub, label) in subjects" :key="'qd-'+sub">
                            <tr class="hover:bg-blue-50/20 transition-colors">
                                <td class="border border-gray-100 px-3 py-1.5 font-medium bg-gray-50/20" x-text="label"></td>
                                <td class="border border-gray-100 px-1 py-1.5 text-center font-mono text-gray-500" x-text="getTBC(sub)"></td>
                                <td class="border border-gray-100 px-1 py-1.5 text-center text-gray-900 font-bold font-mono" x-text="getQD(sub)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CỘT 3: ĐIỂM THI TN THPT (CHUYỂN SANG PHẢI) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center min-h-[40px]">
                <h3 class="font-bold text-hvu-red uppercase text-[10px] flex items-center tracking-widest">
                    <i class="fas fa-pen-nib text-hvu-red mr-2 text-xs"></i> Nhập điểm thi TN THPT 2026
                </h3>
            </div>
            <div class="flex-1 overflow-y-auto pt-[3px]">
                <table class="w-full text-[11px] border-collapse">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[9px] tracking-wider">
                        <tr>
                            <th class="border border-gray-100 px-3 py-2 text-left">Môn học</th>
                            <th class="border border-gray-100 px-3 py-2 text-center">Điểm thi</th>
                        </tr>
                    </thead>
                    <tbody class="text-black">
                        <template x-for="(sub, label) in thptSubjects" :key="'exam-'+sub">
                            <tr>
                                <td class="border border-gray-100 px-3 py-1.5 font-medium bg-gray-50/20" x-text="label"></td>
                                <td class="border border-gray-100 p-0 text-center">
                                    <input type="number" step="0.01" min="0" max="10" x-model="scores[sub].exam" 
                                           @blur="formatScore(sub, 'exam')"
                                           class="w-full text-center p-1.5 border-0 focus:ring-0 placeholder-gray-400 bg-transparent score-input font-mono font-bold text-gray-900" placeholder="0.00">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="p-3 bg-red-50/50 border-t border-gray-100">
                    <p class="text-[9px] text-red-500 leading-tight italic">
                        * Nhập điểm thi TN THPT Quốc gia để tính cho phương thức TS01.
                    </p>
                </div>
            </div>
        </div>

        </div> <!-- END GRID 3 COLUMNS -->
    </div> <!-- END BORDER WRAPPER -->

    <!-- KHỐI DASHBOARD 3 CARD RỜI (DỰ TUYỂN | TỔ HỢP | KẾT QUẢ) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-1">
        
        <!-- CỘT 1: NHẬP THÔNG TIN DỰ TUYỂN -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="px-3 py-1.5 bg-gray-50 border-b border-gray-200 flex items-center min-h-[36px]">
                <h3 class="font-bold text-hvu-red uppercase text-[10px] flex items-center tracking-widest">
                    <i class="fas fa-user-graduate text-hvu-red mr-2 text-xs"></i> Thông tin dự tuyển
                </h3>
            </div>
            <div class="p-3 pb-2 flex-1 flex flex-col space-y-1">
                <div class="flex items-center space-x-3">
                    <label class="w-24 flex-shrink-0 text-[9px] font-bold text-gray-500 uppercase tracking-widest leading-tight">Ngành đào tạo</label>
                    <div class="relative flex-1">
                        <select x-model="form.majorCode" class="w-full rounded-xl border border-gray-200 shadow-sm focus:border-hvu-red focus:ring-hvu-red p-2 transition-all appearance-none cursor-pointer bg-gray-50 hover:bg-white text-gray-900 text-xs font-semibold">
                            <?php foreach ($majors as $major): ?>
                                <option value="<?= htmlspecialchars($major['ma_nganh']) ?>">
                                    <?= htmlspecialchars($major['ten_nganh'] . ' (' . str_replace(['.1', '.2'], '', $major['ma_nganh']) . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-400">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-center space-x-2">
                        <label class="w-16 flex-shrink-0 text-[9px] font-bold text-gray-500 uppercase tracking-widest leading-tight">Đối tượng</label>
                        <select x-model="form.doiTuong" class="flex-1 rounded-xl border border-gray-200 shadow-sm focus:border-hvu-red text-xs p-2 bg-gray-50 font-medium">
                            <option value="">Không ưu tiên</option>
                            <option value="DT1">Nhóm 1 (+2đ)</option>
                            <option value="DT2">Nhóm 2 (+1đ)</option>
                        </select>
                    </div>

                    <div class="flex items-center space-x-2">
                        <label class="w-16 flex-shrink-0 text-[9px] font-bold text-gray-500 uppercase tracking-widest leading-tight">Khu vực</label>
                        <select x-model="form.khuVuc" class="flex-1 rounded-xl border border-gray-200 shadow-sm focus:border-hvu-red text-xs p-2 bg-gray-50 font-medium">
                            <option value="KV3">KV3 (0đ)</option>
                            <option value="KV2">KV2 (0.25đ)</option>
                            <option value="KV2-NT">KV2-NT (0.5đ)</option>
                            <option value="KV1">KV1 (0.75đ)</option>
                        </select>
                    </div>
                </div>
                <div class="mt-2 flex justify-center mb-0">
                    <button @click="calculateBestResult" class="btn-hvu-calculate py-3 px-8 font-black rounded-xl shadow-md transition-all uppercase tracking-[0.1em] text-[10px] w-auto text-white flex items-center justify-center group overflow-hidden relative">
                        <span class="relative z-10 flex items-center">
                            <i class="fas fa-calculator mr-2 group-hover:rotate-12 transition-transform"></i>
                            Tính điểm xét tuyển
                        </span>
                        <div class="absolute inset-0 bg-white/10 translate-x-full group-hover:translate-x-0 transition-transform skew-x-12 duration-500"></div>
                    </button>
                </div>
            </div>
                
        </div>
        
        <!-- CỘT 2: BẢNG TỔ HỢP ĐỦ ĐIỂM -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="px-3 py-1.5 bg-gray-50 border-b border-gray-200 flex items-center min-h-[36px]">
                <h3 class="font-bold text-hvu-red uppercase text-[10px] flex items-center tracking-widest">
                    <i class="fas fa-list-ol text-hvu-red mr-2 text-xs"></i> Tổ hợp đủ điểm vào ngành
                </h3>
            </div>
            <div class="flex-1 flex flex-col">
                
                <div x-show="!bestItem" class="flex-1 flex flex-col items-center justify-center text-center opacity-30 select-none py-8">
                    <i class="fas fa-table text-3xl mb-3 text-gray-300"></i>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Đang chờ tính toán...</p>
                </div>

                <div x-show="bestItem" x-cloak x-transition class="flex-1">
                    <table class="w-full text-[11px] border-collapse">
                        <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[9px] tracking-wider">
                            <tr>
                                <th class="px-3 py-2 text-left border-b border-gray-100">Tổ hợp</th>
                                <th class="px-2 py-2 text-center border-b border-gray-100">TS01</th>
                                <th class="px-2 py-2 text-center border-b border-gray-100">TS02</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-black">
                            <template x-for="item in validCombinations" :key="item.ma_to_hop">
                                <tr class="hover:bg-blue-50/50 transition-colors" :class="bestItem && bestItem.ma_to_hop === item.ma_to_hop ? 'bg-green-50/50' : ''">
                                    <td class="px-3 py-1.5 font-black text-gray-700 text-[9px]" x-text="item.ma_to_hop + ' (' + item.details + ')'"></td>
                                    <td class="px-2 py-1.5 text-center font-mono font-bold text-gray-900" x-text="item.ts01.toFixed(3)"></td>
                                    <td class="px-2 py-1.5 text-center font-mono font-bold text-gray-900" x-text="item.ts02.toFixed(3)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CỘT 3: KẾT QUẢ TỐI ƯU -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="px-3 py-1.5 bg-gray-50 border-b border-gray-200 flex items-center min-h-[36px]">
                <h3 class="font-bold text-hvu-red uppercase text-[10px] flex items-center tracking-widest">
                    <i class="fas fa-trophy text-hvu-red mr-2 text-xs"></i> Kết quả tối ưu
                </h3>
            </div>
            <div class="flex-1 flex flex-col items-center justify-center bg-gray-50/10 p-2">
                <div x-show="!bestItem" class="flex-1 flex flex-col items-center justify-center text-center opacity-30 select-none py-8">
                    <i class="fas fa-chart-line text-3xl mb-3 text-gray-300"></i>
                    <p class="text-[9px] uppercase font-bold tracking-widest text-gray-400">Kết quả tối ưu</p>
                </div>
                
                <div x-show="bestItem" x-cloak x-transition class="w-full p-3 space-y-1">
                    <div class="flex items-start space-x-2">
                        <span class="text-[11px] text-gray-500 font-bold w-36 flex-shrink-0 tracking-tight">Tổ hợp tối ưu:</span>
                        <span class="text-[11px] text-blue-600 font-black uppercase" x-text="bestItem.ma_to_hop + ' (' + bestItem.details + ') - ' + bestItem.methodName"></span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <span class="text-[11px] text-gray-500 font-bold w-36 flex-shrink-0 tracking-tight">Điểm tổ hợp (quy đổi):</span>
                        <span class="text-[11px] text-blue-600 font-black" x-text="bestItem.score.toFixed(2)"></span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <span class="text-[11px] text-gray-500 font-bold w-36 flex-shrink-0 tracking-tight">Điểm ưu tiên (quy đổi):</span>
                        <span class="text-[11px] text-blue-600 font-black" x-text="bestItem.convPrio.toFixed(2)"></span>
                    </div>
                    <div class="pt-1">
                        <div class="flex items-start space-x-2">
                            <span class="text-[11px] text-red-700 font-bold w-36 flex-shrink-0 tracking-tight uppercase">Điểm xét tuyển:</span>
                            <span class="text-[11px] font-black text-red-600 uppercase" x-text="bestItem.finalTotal.toFixed(2)"></span>
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
            majorCode: '7220204',
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

        formatScore(sub, field) {
            let val = this.scores[sub][field];
            if (val === null || val === undefined || val === '') return;
            
            let num = parseFloat(val);
            if (isNaN(num)) return;

            // Handle Fast Input logic (num > 10)
            if (num > 10) {
                if (num <= 100) {
                    num = num / 10;
                } else if (num <= 1000) {
                    num = num / 100;
                }
            }
            
            // Format to 2 decimal places
            this.scores[sub][field] = num.toFixed(2);
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
