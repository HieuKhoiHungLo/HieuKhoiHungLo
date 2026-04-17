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
</style>

<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="calculatorApp()" style="font-family: 'Inter', sans-serif;">
    
    <!-- GRID 4 CỘT - BỐ TRÍ ĐIỂM -->
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-4 mb-10">
        
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
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CỘT 4: CÁC TỔ HỢP ĐỦ ĐIỂM -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col relative">
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center min-h-[64px]">
                <h3 class="font-bold text-gray-800 uppercase text-xs flex items-center">
                    <i class="fas fa-list-ol text-hvu-red mr-2 text-sm"></i> Tổ hợp đủ điểm
                </h3>
            </div>
            <div class="flex-1 overflow-y-auto max-h-[600px] custom-scrollbar">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-gray-100 text-gray-600 uppercase sticky top-0 z-10 shadow-sm">
                        <tr>
                             <th class="border border-gray-200 px-3 py-3 text-left w-1/2">Tổ hợp</th>
                             <th class="border border-gray-200 px-1 py-3 text-center">PT TS01</th>
                             <th class="border border-gray-200 px-1 py-3 text-center">PT TS02</th>
                        </tr>
                    </thead>
                    <tbody class="text-black">
                        <template x-for="item in validCombinations" :key="item.ma_to_hop">
                            <tr class="hover:bg-green-50/50 transition-colors">
                                 <td class="border border-gray-200 px-3 py-3 font-normal" x-text="item.ma_to_hop + ' (' + item.details + ')'"></td>
                                <td class="border border-gray-200 px-1 py-3 text-center font-mono text-black" x-text="item.ts01.toFixed(3)"></td>
                                <td class="border border-gray-200 px-1 py-3 text-center text-black font-mono" x-text="item.ts02.toFixed(3)"></td>
                            </tr>
                        </template>
                        <tr x-show="validCombinations.length === 0">
                            <td colspan="3" class="px-4 py-10 text-center text-gray-400 font-medium">
                                <i class="fas fa-info-circle mb-2 text-2xl"></i><br>
                                Chưa có tổ hợp nào đủ điểm.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-gray-200 bg-gray-50">
                <button @click="triggerToHopCalc" class="btn-hvu-sync w-full py-4 font-bold rounded-xl shadow-lg transition-all uppercase tracking-wide text-xs flex items-center justify-center">
                    <i class="fas fa-sync-alt mr-2"></i> Tính điểm tổ hợp
                </button>
            </div>
        </div>
    </div>

    <!-- PHẦN THÔNG TIN CHI TIẾT & KẾT QUẢ XÉT TUYỂN -->
    <div class="bg-white rounded-[2rem] shadow-xl border border-gray-200 overflow-hidden mt-10">
        <div class="grid grid-cols-1 lg:grid-cols-2">
            
            <!-- NHẬP THÔNG TIN DỰ TUYỂN -->
            <div class="p-8 md:p-10 border-b lg:border-b-0 lg:border-r border-gray-200">
                <h2 class="text-xl font-black text-gray-900 uppercase mb-8 flex items-center tracking-tight">
                    <span class="bg-hvu-red/10 text-hvu-red w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm"><i class="fas fa-user-graduate"></i></span>
                    Thông tin dự tuyển
                </h2>
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-2 ml-1">Ngành đào tạo mong muốn (Tùy chọn)</label>
                        <select x-model="form.majorCode" class="w-full rounded-xl border border-gray-200 shadow-sm focus:border-hvu-red focus:ring-hvu-red p-3.5 transition-all appearance-none cursor-pointer bg-gray-50 hover:bg-white text-gray-900 text-sm font-medium">
                            <option value="">-- Tính tất cả các ngành --</option>
                            <?php foreach ($majors as $major): ?>
                                <option value="<?= htmlspecialchars($major['ma_nganh']) ?>">
                                    <?= htmlspecialchars($major['ten_nganh'] . ' (' . str_replace(['.1', '.2'], '', $major['ma_nganh']) . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-2 ml-1">Đối tượng ưu tiên</label>
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
                            <label class="block text-xs font-bold text-gray-600 mb-2 ml-1">Khu vực ưu tiên</label>
                            <select x-model="form.khuVuc" class="w-full rounded-xl border border-gray-200 shadow-sm focus:border-hvu-red focus:ring-hvu-red p-3.5 transition-all bg-gray-50 hover:bg-white text-gray-900 text-sm font-medium">
                                <option value="KV3">KV3 (0 điểm)</option>
                                <option value="KV2">KV2 (+0.25 điểm)</option>
                                <option value="KV2-NT">KV2-NT (+0.5 điểm)</option>
                                <option value="KV1">KV1 (+0.75 điểm)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mt-10">
                    <button @click="calculateBestResult" class="btn-hvu-calculate px-10 py-5 font-bold rounded-xl shadow-xl transition-all uppercase tracking-widest text-sm w-full md:w-auto text-white">
                        Tính điểm xét tuyển
                    </button>
                </div>
            </div>
            
            <!-- KẾT QUẢ TỐI ƯU NHẤT -->
            <div class="p-8 md:p-10 bg-gray-100 flex flex-col justify-center border-l border-gray-100">
                <div x-show="!bestItem" class="text-center py-20 grayscale select-none">
                    <img src="<?= url('/assets/img/Logo.png') ?>" class="w-20 mx-auto mb-4 opacity-30">
                    <p class="text-gray-600 font-bold uppercase tracking-widest text-xs">Nhập thông tin và nhấn nút để xem kết quả</p>
                </div>
                
                <div x-show="bestItem" x-cloak x-transition class="space-y-4">
                    <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 flex flex-col md:flex-row justify-between items-start md:items-center">
                        <div class="space-y-1">
                            <div class="text-gray-400 font-bold uppercase text-[9px] tracking-widest">Tổ hợp điểm cao nhất</div>
                            <div class="text-xl font-black text-gray-900">
                                <span x-text="bestItem.score.toFixed(3)"></span> 
                                <span class="text-gray-500 font-medium ml-1 text-sm" x-text="'(' + bestItem.ma_to_hop + ': ' + bestItem.details + ')'"></span>
                            </div>
                        </div>
                        <div class="mt-4 md:mt-0 px-3 py-1 bg-green-100 text-green-700 rounded-lg font-black text-[9px] uppercase tracking-wider border border-green-200">
                            TỐT NHẤT CHO BẠN
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mb-1">Phương thức</div>
                            <div class="text-lg font-bold text-gray-800" x-text="bestItem.methodName"></div>
                        </div>
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mb-1">Điểm ưu tiên gốc</div>
                            <div class="text-lg font-bold text-blue-600 font-mono" x-text="bestItem.rawPrio.toFixed(2)"></div>
                        </div>
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mb-1">Điểm ưu tiên quy đổi</div>
                            <div class="text-lg font-bold text-orange-600 font-mono" x-text="bestItem.convPrio.toFixed(3)"></div>
                        </div>
                        <div class="bg-[#0e5c7e] p-5 rounded-xl shadow-lg text-white">
                            <div class="text-[9px] text-white/80 font-bold uppercase tracking-widest mb-1">ĐIỂM XÉT TUYỂN</div>
                            <div class="text-3xl font-black font-mono" x-text="bestItem.finalTotal.toFixed(3)"></div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-blue-50/50 rounded-xl border border-blue-100 text-[9px] text-blue-800 font-medium leading-relaxed italic">
                        * Điểm xét tuyển được tính bằng tổng điểm tổ hợp và điểm ưu tiên đã quy đổi theo quy định của Bộ GD&ĐT (đối với thí sinh đạt trên 22.5 điểm).
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
            
            this.combinationsList.forEach(comb => {
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
                alert("Vui lòng nhập đầy đủ điểm ít nhất 3 môn của một tổ hợp (ví dụ: Toán, Lý, Hóa cho khối A00) để tính kết quả.");
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
