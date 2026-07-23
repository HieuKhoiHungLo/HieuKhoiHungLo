<?php
$title = "Cấu hình Hồ sơ Nhập học";
ob_start();
?>

<div class="p-6 bg-slate-50 min-h-screen" x-data="enrollmentSetup()">
    <div class="max-w-5xl mx-auto">
        <!-- Header & Session Selector -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-cogs text-blue-600"></i> Cấu hình Hồ sơ Nhập học
                </h1>
                <p class="text-sm text-slate-500 mt-1">Thiết lập các loại giấy tờ sinh viên cần nộp khi làm thủ tục nhập học.</p>
            </div>
            
            <div class="flex items-center gap-3 bg-white p-2 rounded-xl shadow-sm border border-slate-200">
                <i class="fas fa-calendar-alt text-slate-400 ml-2"></i>
                <select id="session-selector" class="border-none text-sm font-medium focus:ring-0 text-slate-700 bg-transparent py-1 pr-8" @change="changeSession($event)">
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $s['id'] == $currentSessionId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['ten_dot']) ?> (<?= $s['nam_tuyen_sinh'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Main Content Box -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-semibold text-slate-700">Danh mục Hồ sơ Yêu cầu</h3>
                <button @click="addDocument()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-plus"></i> Thêm hồ sơ
                </button>
            </div>
            
            <div class="p-0">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3 font-semibold w-16 text-center">TT</th>
                            <th class="px-6 py-3 font-semibold">Tên Hồ sơ / Giấy tờ</th>
                            <th class="px-6 py-3 font-semibold">Các giá trị lựa chọn (Cách nhau bởi dấu phẩy)</th>
                            <th class="px-6 py-3 font-semibold">Giá trị mặc định</th>
                            <th class="px-6 py-3 font-semibold text-center w-24">Bắt buộc</th>
                            <th class="px-6 py-3 font-semibold text-right w-24">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(doc, index) in documents" :key="doc.id">
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="px-6 py-4 text-center">
                                    <input type="number" x-model="doc.thu_tu" class="w-16 text-center text-sm border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5" min="0">
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" x-model="doc.ten_ho_so" placeholder="VD: Học bạ THPT bản gốc" class="w-full text-sm border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 font-medium text-slate-800">
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" x-model="doc.cac_gia_tri" placeholder="VD: Gốc, Sao y, Bản công chứng" class="w-full text-sm border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 text-slate-600" @change="updateDefaults(index)">
                                </td>
                                <td class="px-6 py-4">
                                    <select x-model="doc.gia_tri_mac_dinh" class="w-full text-sm border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5">
                                        <template x-for="val in getValuesArray(doc.cac_gia_tri)">
                                            <option :value="val" x-text="val" :selected="val == doc.gia_tri_mac_dinh"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <select x-model="doc.bat_buoc" class="w-24 text-sm border-slate-300 rounded-md py-1.5" :class="(doc.bat_buoc === 'true' || doc.bat_buoc === true) ? 'text-green-600 font-medium' : 'text-slate-500'">
                                        <option value="true">Bắt buộc</option>
                                        <option value="false">Không</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="removeDocument(index)" class="text-slate-400 hover:text-red-500 transition-colors p-2" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="documents.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-folder-open text-2xl text-slate-400"></i>
                                </div>
                                <p>Chưa có hồ sơ nào được cấu hình cho đợt này.</p>
                                <button @click="loadDefaultTemplate()" class="mt-4 text-blue-600 hover:text-blue-700 font-medium text-sm underline underline-offset-2">
                                    Tải bộ hồ sơ mẫu
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="p-5 border-t border-slate-200 bg-slate-50 flex justify-end gap-3">
                <button @click="saveData()" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-xl shadow-sm transition-all flex items-center gap-2" :disabled="isSaving" :class="{'opacity-75 cursor-wait': isSaving}">
                    <i class="fas fa-save" x-show="!isSaving"></i>
                    <i class="fas fa-spinner fa-spin" x-show="isSaving"></i>
                    <span x-text="isSaving ? 'Đang lưu...' : 'Lưu Cấu Hình'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('enrollmentSetup', () => ({
        sessionId: <?= json_encode($currentSessionId) ?>,
        documents: [],
        isSaving: false,
        nextId: 1,

        init() {
            // Load existing data from PHP
            const initialDocs = <?= json_encode($documents) ?>;
            this.documents = initialDocs.map(doc => ({
                id: doc.id,
                ten_ho_so: doc.ten_ho_so,
                cac_gia_tri: Array.isArray(doc.cac_gia_tri) ? doc.cac_gia_tri.join(', ') : (typeof doc.cac_gia_tri === 'string' && doc.cac_gia_tri.startsWith('[') ? JSON.parse(doc.cac_gia_tri).join(', ') : doc.cac_gia_tri),
                gia_tri_mac_dinh: doc.gia_tri_mac_dinh,
                bat_buoc: doc.bat_buoc == 1,
                thu_tu: parseInt(doc.thu_tu) || 0
            }));
            
            // If empty and it's a new load, maybe auto-suggest template?
        },

        changeSession(e) {
            window.location.href = '?session_id=' + e.target.value;
        },

        getValuesArray(str) {
            if (!str) return [];
            return str.split(',').map(s => s.trim()).filter(s => s !== '');
        },

        updateDefaults(index) {
            const arr = this.getValuesArray(this.documents[index].cac_gia_tri);
            if (arr.length > 0 && !arr.includes(this.documents[index].gia_tri_mac_dinh)) {
                this.documents[index].gia_tri_mac_dinh = arr[0];
            }
        },

        addDocument() {
            this.documents.push({
                id: 'new_' + Date.now(),
                thu_tu: this.documents.length * 10 + 10,
                ten_ho_so: '',
                cac_gia_tri: 'Bản gốc, Bản sao công chứng, Chưa nộp',
                gia_tri_mac_dinh: 'Bản gốc',
                bat_buoc: 'false'
            });
        },

        removeDocument(index) {
            if (confirm('Bạn có chắc chắn muốn xóa mục này?')) {
                this.documents.splice(index, 1);
            }
        },

        loadDefaultTemplate() {
            const template = [
                { ten: 'Học bạ THPT', val: 'Bản gốc, Bản sao công chứng, Chưa nộp', def: 'Bản gốc', req: true },
                { ten: 'Giấy chứng nhận kết quả thi tốt nghiệp THPT', val: 'Bản gốc, Chưa nộp', def: 'Bản gốc', req: true },
                { ten: 'Bằng tốt nghiệp THPT hoặc Giấy chứng nhận tốt nghiệp tạm thời', val: 'Bản sao công chứng, Chưa nộp', def: 'Bản sao công chứng', req: true },
                { ten: 'Giấy khai sinh', val: 'Bản sao công chứng, Bản sao, Chưa nộp', def: 'Bản sao công chứng', req: true },
                { ten: 'Căn cước công dân', val: 'Bản sao công chứng, Chưa nộp', def: 'Bản sao công chứng', req: true },
                { ten: 'Ảnh thẻ (3x4 hoặc 4x6)', val: 'Đã nộp đủ, Thiếu ảnh, Chưa nộp', def: 'Đã nộp đủ', req: true },
                { ten: 'Giấy tờ chứng minh đối tượng ưu tiên (nếu có)', val: 'Bản sao công chứng, Không có', def: 'Không có', req: false },
            ];

            template.forEach((item, idx) => {
                this.documents.push({
                    id: 'new_' + this.nextId++,
                    ten_ho_so: item.ten,
                    cac_gia_tri: item.val,
                    gia_tri_mac_dinh: item.def,
                    bat_buoc: item.req,
                    thu_tu: (idx + 1) * 10
                });
            });
        },

        saveData() {
            if (this.documents.some(d => d.ten_ho_so.trim() === '')) {
                alert('Vui lòng nhập đầy đủ tên hồ sơ!');
                return;
            }

            this.isSaving = true;

            // Prepare data
            const payload = new URLSearchParams();
            payload.append('csrf_token', '<?= \App\Middleware\SecurityMiddleware::generateCsrfToken() ?>');
            payload.append('session_id', this.sessionId);
            
            this.documents.forEach((doc, idx) => {
                payload.append(`documents[${idx}][id]`, doc.id);
                payload.append(`documents[${idx}][ten_ho_so]`, doc.ten_ho_so);
                
                // Convert string to array
                const valArr = this.getValuesArray(doc.cac_gia_tri);
                valArr.forEach((v, vIdx) => {
                    payload.append(`documents[${idx}][cac_gia_tri][${vIdx}]`, v);
                });
                
                payload.append(`documents[${idx}][gia_tri_mac_dinh]`, doc.gia_tri_mac_dinh);
                payload.append(`documents[${idx}][bat_buoc]`, doc.bat_buoc);
                payload.append(`documents[${idx}][thu_tu]`, doc.thu_tu);
            });

            fetch('<?= url("/admin/enrollment/setup/save") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: payload.toString()
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Server returned non-JSON:", text);
                    throw new Error("Lỗi máy chủ trả về định dạng sai: " + text.substring(0, 150));
                }
            })
            .then(data => {
                if (data.success) {
                    alert('Lưu cấu hình thành công!');
                    window.location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra khi lưu.');
                }
            })
            .catch(err => {
                console.error(err);
                alert(err.message || 'Lỗi kết nối đến máy chủ.');
            })
            .finally(() => {
                this.isSaving = false;
            });
        }
    }));
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>
