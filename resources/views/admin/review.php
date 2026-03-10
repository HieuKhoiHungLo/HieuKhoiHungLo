<?php ob_start(); ?>

<div class="mb-6">
    <a href="<?= url('/admin/dashboard') ?>" class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-[#0066FF] uppercase tracking-wider mb-2 transition">
        <i class="fas fa-arrow-left mr-2"></i> Quay lại Dashboard
    </a>

    <!-- Global Hidden Inputs for JS Save -->
    <input type="hidden" name="cccd" value="<?= $user['so_cccd'] ?>">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h2 class="text-3xl font-black text-slate-800 font-heading uppercase tracking-tight">DUYỆT HỒ SƠ THÍ SINH</h2>
            <?php
            // Map English statuses from legacy data to Vietnamese
            $statusMap = [
                'submitted'  => 'Đã nộp',
                'draft'      => 'Nháp',
                'approved'   => 'Đã duyệt',
                'rejected'   => 'Từ chối',
                'pending'    => 'Chờ duyệt',
                'verifying'  => 'Đang xác minh',
                'verified'   => 'Đã xác minh',
                'scoring'    => 'Đang tính điểm',
                'qualified'  => 'Đủ điều kiện',
                'admitted'   => 'Trúng tuyển',
            ];
            $rawStatus = $user['trang_thai'] ?? 'Chờ duyệt';
            $statusLabel = $statusMap[strtolower($rawStatus)] ?? $rawStatus;
            $statusStyleMap = [
                'Đã duyệt'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'Từ chối'    => 'bg-rose-50 text-rose-700 border-rose-200',
                'Yêu cầu bổ sung' => 'bg-blue-50 text-blue-700 border-blue-200',
                'Đã nộp'     => 'bg-sky-50 text-sky-700 border-sky-200',
                'Đang xác minh' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'Đã xác minh'   => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                'Đang tính điểm' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'Đủ điều kiện'   => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                'Trúng tuyển'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ];
            $statusStyles = $statusStyleMap[$statusLabel] ?? 'bg-amber-50 text-amber-700 border-amber-200';

            $statusIconMap = [
                'Đã duyệt' => 'fa-check-circle',
                'Trúng tuyển' => 'fa-check-circle',
                'Từ chối'    => 'fa-times-circle',
                'Yêu cầu bổ sung' => 'fa-edit',
                'Đã nộp'     => 'fa-paper-plane',
                'Đang xác minh' => 'fa-spinner fa-spin',
                'Đang tính điểm' => 'fa-spinner fa-spin',
                'Đã xác minh' => 'fa-clipboard-check',
                'Đủ điều kiện' => 'fa-clipboard-check',
                'Nháp'       => 'fa-file-alt',
            ];
            $statusIcon = $statusIconMap[$statusLabel] ?? 'fa-clock';
            ?>
            <span class="inline-flex items-center gap-2 px-4 py-1.5 text-xs font-bold rounded-full border <?= $statusStyles ?>">
                <i class="fas <?= $statusIcon ?>"></i> <?= $statusLabel ?>
            </span>
        </div>
        <?php if (($navTotal ?? 0) > 0): ?>
            <span class="text-xs font-bold text-slate-400">
                Hồ sơ chờ duyệt: <span class="text-[#0066FF]"><?= $navPosition ?? 0 ?>/<?= $navTotal ?></span>
            </span>
        <?php endif; ?>
    </div>
</div>

<form id="reviewForm" action="<?= url('/admin/review/submit') ?>" method="POST" class="pb-20">
    <input type="hidden" name="cccd" value="<?= $user['so_cccd'] ?>">
    <?= csrf_field() ?>

    <?php
    // Province Map
    $provinceMap = [];
    foreach ($provinces as $p) $provinceMap[$p['ma_tinh']] = $p['ten_tinh'];

    // Helper function for consistent evidence rendering
    if (!function_exists('render_evidence_item')) {
        function render_evidence_item($path, $label, $imgId)
        {
            ob_start(); ?>
            <div class="relative group w-full">
                <?php if (!empty($path)): ?>
                    <?php
                    $src = strpos($path, 'http') === 0 ? google_drive_thumbnail_url($path, 'w400') : asset($path);
                    $link = strpos($path, 'http') === 0 ? $path : asset($path);
                    ?>
                    <div class="relative w-full cursor-pointer transition-transform duration-300 hover:scale-[1.3] hover:z-50 z-10 group">
                        <img id="<?= $imgId ?>" loading="lazy" src="<?= $src ?>"
                            class="w-full h-full object-contain relative rounded-xl border-2 border-slate-200 shadow-sm bg-slate-50"
                            style="min-height: 150px; max-height: 500px;"
                            title="Double click to view full size">

                        <a href="<?= $link ?>" target="_blank" class="absolute inset-0 z-30 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-white/30 transition-opacity rounded-xl" ondblclick="window.open('<?= $link ?>', '_blank')">
                            <div class="w-12 h-12 rounded-full bg-slate-800/90 text-white flex items-center justify-center shadow-2xl scale-75 group-hover:scale-100 transition-transform duration-300">
                                <i class="fas fa-external-link-alt text-lg"></i>
                            </div>
                        </a>

                        <button type="button" onclick="rotateEvidenceImage('<?= $path ?>', '<?= $imgId ?>', this)"
                            class="absolute top-2 right-2 z-50 w-8 h-8 bg-white hover:bg-[#0066FF] text-slate-700 hover:text-white rounded-full shadow-md flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300"
                            title="Xoay ảnh 90 độ">
                            <i class="fas fa-redo-alt text-sm"></i>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center aspect-[3/4] text-slate-300 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        <i class="fas fa-image text-4xl mb-2"></i>
                        <span class="text-[10px] uppercase font-bold tracking-wider">Không có ảnh minh chứng</span>
                        <img id="<?= $imgId ?>" loading="lazy" class="hidden w-full h-full object-cover absolute inset-0">
                    </div>
                <?php endif; ?>
                <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/80 via-black/40 to-transparent z-[70] pointer-events-none rounded-b-xl overflow-hidden">
                    <span class="text-[10px] font-black text-white uppercase tracking-widest drop-shadow-sm"><?= $label ?></span>
                </div>
            </div>
    <?php return ob_get_clean();
        }
    }
    ?>

    <!-- TABS NAVIGATION -->
    <div class="bg-white rounded-t-2xl shadow-sm border-b border-slate-200">
        <div class="flex overflow-x-auto gap-1 p-2" id="reviewTabs">
            <button type="button" onclick="switchTab('personal')" class="tab-btn px-5 py-3 font-bold text-sm uppercase tracking-wide rounded-xl transition-all duration-200 whitespace-nowrap bg-[#0066FF] text-white shadow-md shadow-blue-200/50" data-tab="personal">
                <i class="fas fa-user mr-2"></i> 1. Thông tin cá nhân
            </button>
            <button type="button" onclick="switchTab('academic')" class="tab-btn px-5 py-3 font-bold text-sm uppercase tracking-wide rounded-xl transition-all duration-200 whitespace-nowrap text-slate-500 hover:bg-slate-100 hover:text-slate-700" data-tab="academic">
                <i class="fas fa-graduation-cap mr-2"></i> 2. Học bạ
            </button>
            <button type="button" onclick="switchTab('certs')" class="tab-btn px-5 py-3 font-bold text-sm uppercase tracking-wide rounded-xl transition-all duration-200 whitespace-nowrap text-slate-500 hover:bg-slate-100 hover:text-slate-700" data-tab="certs">
                <i class="fas fa-certificate mr-2"></i> 3. Chứng chỉ
            </button>
            <button type="button" onclick="switchTab('thpt')" class="tab-btn px-5 py-3 font-bold text-sm uppercase tracking-wide rounded-xl transition-all duration-200 whitespace-nowrap text-slate-500 hover:bg-slate-100 hover:text-slate-700" data-tab="thpt">
                <i class="fas fa-poll-h mr-2"></i> 4. Điểm THPT
            </button>
            <button type="button" onclick="switchTab('wishes')" class="tab-btn px-5 py-3 font-bold text-sm uppercase tracking-wide rounded-xl transition-all duration-200 whitespace-nowrap text-slate-500 hover:bg-slate-100 hover:text-slate-700" data-tab="wishes">
                <i class="fas fa-list-ol mr-2"></i> 5. Nguyện vọng
            </button>
        </div>
    </div>

    <!-- TABS CONTENT AREA -->
    <div class="bg-white rounded-b-2xl shadow-sm border border-t-0 border-slate-200 p-6 min-h-[500px]">

        <?php include __DIR__ . '/review/_tab_personal.php'; ?>

        <?php include __DIR__ . '/review/_tab_academic.php'; ?>

        <?php include __DIR__ . '/review/_tab_certs.php'; ?>

        <?php include __DIR__ . '/review/_tab_thpt.php'; ?>

        <?php include __DIR__ . '/review/_tab_wishes.php'; ?>

        <!-- Action Bar: Prev / Submit / Next -->
        <div class="mt-4 pt-4 border-t border-slate-200 flex items-center justify-between gap-4">
            <!-- Previous -->
            <?php if (!empty($prevCCCD)): ?>
                <a href="<?= url('/admin/review?cccd=' . $prevCCCD) ?>" class="inline-flex items-center gap-2 px-5 py-3 bg-slate-100 text-slate-700 font-bold text-sm rounded-xl hover:bg-slate-200 hover:-translate-x-0.5 transition-all">
                    <i class="fas fa-chevron-left"></i> Hồ sơ trước
                </a>
            <?php else: ?>
                <span class="inline-flex items-center gap-2 px-5 py-3 bg-slate-50 text-slate-300 font-bold text-sm rounded-xl cursor-not-allowed">
                    <i class="fas fa-chevron-left"></i> Hồ sơ trước
                </span>
            <?php endif; ?>

            <!-- Submit -->
            <button type="button" onclick="submitReview()" class="px-8 py-4 bg-emerald-600 text-white font-bold rounded-xl shadow-xl shadow-emerald-200 hover:bg-emerald-700 hover:-translate-y-1 transition transform flex items-center text-lg">
                <i class="fas fa-paper-plane mr-3"></i> DUYỆT HỒ SƠ & GỬI EMAIL
            </button>

            <!-- Next -->
            <?php if (!empty($nextCCCD)): ?>
                <a href="<?= url('/admin/review?cccd=' . $nextCCCD) ?>" class="inline-flex items-center gap-2 px-5 py-3 bg-[#0066FF] text-white font-bold text-sm rounded-xl hover:bg-blue-700 hover:translate-x-0.5 transition-all shadow-md shadow-blue-200/50">
                    Hồ sơ tiếp <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="inline-flex items-center gap-2 px-5 py-3 bg-slate-50 text-slate-300 font-bold text-sm rounded-xl cursor-not-allowed">
                    Hồ sơ tiếp <i class="fas fa-chevron-right"></i>
                </span>
            <?php endif; ?>
        </div>
    </div>
</form>


<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('wardSearch', (initialProvince, initialWard) => ({
            open: false,
            search: '',
            selectedCode: initialWard,
            provinceId: initialProvince,
            wards: [],
            isLoading: false,

            async init() {
                if (this.provinceId) {
                    await this.fetchWards(this.provinceId);
                }
            },

            async handleProvinceChange(newPid) {
                this.provinceId = newPid;
                this.selectedCode = '';
                this.search = '';
                await this.fetchWards(newPid);
            },

            async fetchWards(pid) {
                if (!pid) {
                    this.wards = [];
                    return;
                }
                this.isLoading = true;
                try {
                    const res = await fetch(`<?= url('/api/wards') ?>?province_id=${pid}`);
                    this.wards = await res.json();

                    if (this.selectedCode) {
                        const found = this.wards.find(w => w.ma_xa == this.selectedCode);
                        if (found) this.search = found.ten_xa;
                    }
                } catch (e) {
                    console.error(e);
                }
                this.isLoading = false;
            },

            select(ward) {
                this.selectedCode = ward.ma_xa;
                this.search = ward.ten_xa;
                this.open = false;
            },

            get filteredWards() {
                if (this.search === '') return this.wards;
                const lower = this.search.toLowerCase();
                return this.wards.filter(w => w.ten_xa.toLowerCase().includes(lower));
            }
        }));

        Alpine.data('schoolSearch', (initialProvince, initialSchool) => ({
            open: false,
            search: '',
            selectedCode: initialSchool,
            provinceId: initialProvince,
            schools: [],
            isLoading: false,

            async init() {
                if (this.provinceId) {
                    await this.fetchSchools(this.provinceId);
                }
            },

            async handleProvinceChange(newPid) {
                this.provinceId = newPid;
                this.selectedCode = '';
                this.search = '';
                await this.fetchSchools(newPid);
            },

            async fetchSchools(pid) {
                if (!pid) {
                    this.schools = [];
                    return;
                }
                this.isLoading = true;
                try {
                    const res = await fetch(`<?= url('/api/public/schools') ?>?province_id=${pid}`);
                    this.schools = await res.json();

                    if (this.selectedCode) {
                        const found = this.schools.find(s => s.ma_truong == this.selectedCode);
                        if (found) this.search = found.ten_truong;
                    }
                } catch (e) {
                    console.error(e);
                }
                this.isLoading = false;
            },

            select(school) {
                this.selectedCode = school.ma_truong;
                this.search = school.ten_truong;
                this.open = false;

                // Dispatch event for auto-KV logic
                window.dispatchEvent(new CustomEvent('school-selected', {
                    detail: {
                        ma_truong: school.ma_truong,
                        ma_kv: school.ma_kv
                    }
                }));
            },

            get filteredSchools() {
                if (this.search === '') return this.schools;
                const lower = this.search.toLowerCase();
                return this.schools.filter(s => s.ten_truong.toLowerCase().includes(lower) || s.ma_truong.toLowerCase().includes(lower));
            }
        }));
    });

    // TABS LOGIC
    function switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('bg-[#0066FF]', 'text-white', 'shadow-md', 'shadow-blue-200/50');
            btn.classList.add('text-slate-500', 'hover:bg-slate-100', 'hover:text-slate-700');
            if (btn.dataset.tab === tabName) {
                btn.classList.add('bg-[#0066FF]', 'text-white', 'shadow-md', 'shadow-blue-200/50');
                btn.classList.remove('text-slate-500', 'hover:bg-slate-100', 'hover:text-slate-700');
            }
        });

        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        document.getElementById('tab_' + tabName).classList.remove('hidden');
    }

    // TOGGLE REASON INPUTS
    const sections = ['personal', 'academic', 'certs', 'thpt', 'wishes'];
    sections.forEach(sec => {
        document.querySelectorAll(`input[name="status_${sec}"]`).forEach(radio => {
            radio.addEventListener('change', function() {
                const container = document.getElementById(`reason_${sec}_container`);
                if (this.value === 'rejected') {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
            });
        });
    });

    function submitReview() {
        if (confirm('Bạn có chắc chắn muốn HOÀN TẤT việc duyệt hồ sơ và GỬI EMAIL kết quả cho thí sinh?')) {
            Loading.show();
            const form = document.getElementById('reviewForm');
            const formData = new FormData(form);

            fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const msg = data.is_rejected ? 'Đã lưu yêu cầu chỉnh sửa.' : 'Đã duyệt hồ sơ thành công!';
                        if (typeof Toast !== 'undefined') Toast.success(msg);
                        // Redirect immediately — page load happens in background
                        window.location.href = data.next_url;
                    } else {
                        Loading.hide();
                        if (typeof Toast !== 'undefined') Toast.error(data.error || 'Có lỗi xảy ra');
                    }
                })
                .catch(err => {
                    Loading.hide();
                    if (typeof Toast !== 'undefined') Toast.error('Lỗi kết nối: ' + err.message);
                });
        }
    }

    function toggleEdit(section) {
        const viewDiv = document.getElementById('view_' + section);
        const editDiv = document.getElementById('form_' + section);
        if (viewDiv) viewDiv.classList.toggle('hidden');
        if (editDiv) editDiv.classList.toggle('hidden');

        if (section === 'personal') {
            const fields = document.querySelectorAll('.personal-edit-field');
            fields.forEach(f => f.classList.toggle('hidden'));
        }

        if (section === 'academic') {
            const fields = document.querySelectorAll('.academic-edit-field');
            fields.forEach(f => f.classList.toggle('hidden'));
        }
    }

    function previewPersonalImg(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(previewId);
                if (img) {
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    async function loadWards(provinceId, targetId, selectedValue = null) {
        const target = document.getElementById(targetId);
        if (!provinceId) {
            target.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
            return;
        }
        try {
            const res = await fetch(`/api/public/wards?province_id=${provinceId}`);
            const data = await res.json();
            let html = '<option value="">-- Chọn Phường/Xã --</option>';
            data.forEach(item => {
                const isSelected = selectedValue && (item.ma_xa == selectedValue || item.ten_xa == selectedValue);
                html += `<option value="${item.ma_xa}" ${isSelected ? 'selected' : ''}>${item.ten_xa}</option>`;
            });
            target.innerHTML = html;
        } catch (e) {
            console.error(e);
        }
    }

    async function loadDistricts(provinceId, targetId, selectedValue = null) {
        const target = document.getElementById(targetId);
        if (!provinceId) {
            target.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>';
            window.dispatchEvent(new CustomEvent('district-change', {
                detail: ''
            }));
            return;
        }
        try {
            const res = await fetch(`/api/public/districts?province_id=${provinceId}`);
            const data = await res.json();
            let html = '<option value="">-- Chọn Quận/Huyện --</option>';
            data.forEach(item => {
                const isSelected = selectedValue && (item.ma_huyen == selectedValue || item.ten_huyen == selectedValue);
                html += `<option value="${item.ma_huyen}" ${isSelected ? 'selected' : ''}>${item.ten_huyen}</option>`;
            });
            target.innerHTML = html;
        } catch (e) {
            console.error(e);
        }
    }

    async function loadSchools(provinceId, targetId, selectedValue = null) {
        const target = document.getElementById(targetId);
        if (!provinceId) {
            target.innerHTML = '<option value="">-- Chọn Trường --</option>';
            return;
        }
        try {
            const res = await fetch(`<?= url('/api/public/schools') ?>?province_id=${provinceId}`);
            const data = await res.json();
            let html = '<option value="">-- Chọn Trường --</option>';
            data.forEach(item => {
                const isSelected = selectedValue && (item.ma_truong == selectedValue || item.ten_truong == selectedValue);
                html += `<option value="${item.ma_truong}" ${isSelected ? 'selected' : ''}>${item.ten_truong}</option>`;
            });
            target.innerHTML = html;
        } catch (e) {
            console.error(e);
        }
    }

    async function saveSection(section) {
        try {
            const container = document.getElementById('form_' + section);
            if (!container) {
                alert('Lỗi: Không tìm thấy container form_' + section);
                return;
            }

            let btn = container.querySelector('button[onclick*="saveSection"]');
            if (!btn) {
                const buttons = container.querySelectorAll('button');
                if (buttons.length > 0) btn = buttons[buttons.length - 1];
            }
            if (!btn) {
                alert('Lỗi: Không tìm thấy nút Lưu');
                return;
            }

            const formData = new FormData();
            const inputs = container.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (!input.name) return;
                if (input.type === 'radio' || input.type === 'checkbox') {
                    if (input.checked) formData.append(input.name, input.value);
                } else if (input.type === 'file') {
                    if (input.files.length > 0) formData.append(input.name, input.files[0]);
                } else {
                    formData.append(input.name, input.value);
                }
            });

            const cccdInput = document.querySelector('input[name="cccd"]');
            const csrfInput = document.querySelector('input[name="csrf_token"]');
            if (cccdInput) formData.append('cccd', cccdInput.value);
            else {
                alert('Lỗi: Không tìm thấy thẻ input[name="cccd"]');
                return;
            }
            if (csrfInput) formData.append('csrf_token', csrfInput.value);

            // Capture Status/Note params even if they are outside the container
            const statusRadios = document.querySelectorAll(`input[name="status_${section}"]`);
            statusRadios.forEach(r => {
                if (r.checked) formData.append(r.name, r.value);
            });
            const noteInput = document.querySelector(`textarea[name="note_${section}"]`);
            if (noteInput) formData.append(noteInput.name, noteInput.value);

            // For 'personal' section: collect files from hover overlay inputs (personal-edit-file-trigger)
            // These are always present in _evidence.php (not gated behind personal-edit-field)
            if (section === 'personal') {
                const fileInputs = document.querySelectorAll('input.personal-edit-file-trigger, .personal-edit-field input[type="file"]');
                fileInputs.forEach(input => {
                    if (input.name && input.files && input.files.length > 0) {
                        // Map field names: avatar→avatar, cccd_front→cccd_front, etc.
                        formData.set(input.name, input.files[0]);
                        console.log('Appended personal file:', input.name);
                    }
                });
            }


            // For 'academic' section, collect evidence file inputs from outside the form container
            if (section === 'academic') {
                const fileInputs = document.querySelectorAll('.academic-edit-field input[type="file"]');
                fileInputs.forEach(input => {
                    if (input.files.length > 0) {
                        formData.append(input.name, input.files[0]);
                        console.log('Appended Academic File:', input.name);
                    }
                });
            }

            formData.append('section', section);

            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang lưu...';
            btn.disabled = true;

            const url = '<?= url('/admin/candidates/update') ?>';

            const res = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const contentType = res.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                const data = await res.json();
                if (data.success) {
                    showToast('Đã lưu dữ liệu thành công!', 'success');
                    setTimeout(() => {
                        if (data.new_cccd) {
                            const url = new URL(window.location.href);
                            url.searchParams.set('cccd', data.new_cccd);
                            window.location.href = url.toString();
                        } else {
                            location.reload();
                        }
                    }, 1000);
                } else {
                    showToast('Lỗi: ' + (data.error || 'Có lỗi xảy ra'), 'error');
                    if (data.debug_data) console.log('Debug:', data.debug_data);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } else {
                const text = await res.text();
                console.error('Non-JSON Response:', text);
                showToast('Lỗi Server (Không phải JSON)', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        } catch (e) {
            console.error(e);
            showToast('Lỗi ngoại lệ: ' + e.message, 'error');
            const container = document.getElementById('form_' + section);
            if (container) {
                const btn = container.querySelector('button[onclick*="saveSection"]');
                if (btn) {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            }
        }
    }

    function switchEvidenceGroup(group) {
        document.getElementById('group_ev_priority').classList.add('hidden');
        document.getElementById('group_ev_academic').classList.add('hidden');
        document.getElementById('group_ev_' + group).classList.remove('hidden');

        const priorityBtn = document.getElementById('btn_ev_priority');
        const academicBtn = document.getElementById('btn_ev_academic');

        if (group === 'priority') {
            priorityBtn.classList.add('bg-white', 'text-emerald-700', 'shadow-sm');
            priorityBtn.classList.remove('text-slate-500');
            academicBtn.classList.remove('bg-white', 'text-emerald-700', 'shadow-sm');
            academicBtn.classList.add('text-slate-500');
            switchSubTab('kv');
        } else {
            academicBtn.classList.add('bg-white', 'text-emerald-700', 'shadow-sm');
            academicBtn.classList.remove('text-slate-500');
            priorityBtn.classList.remove('bg-white', 'text-emerald-700', 'shadow-sm');
            priorityBtn.classList.add('text-slate-500');
            switchSubTab('10');
        }
    }

    function switchSubTab(sub) {
        document.querySelectorAll('.evidence-sub-tab').forEach(el => el.classList.add('hidden'));
        const targetPane = document.getElementById('sub_ev_' + sub);
        if (targetPane) targetPane.classList.remove('hidden');

        const allSubBtns = ['kv', 'dt', '10', '11', '12'];
        allSubBtns.forEach(s => {
            const btn = document.getElementById('btn_sub_' + s);
            if (!btn) return;
            if (s === sub) {
                btn.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-100');
                btn.classList.remove('text-slate-400');
            } else {
                btn.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-100');
                btn.classList.add('text-slate-400');
            }
        });
    }

    function previewImage(input, imgId) {
        if (input.files && input.files[0]) {
            if (imgId === 'img_ev_kv') {
                switchEvidenceGroup('priority');
                switchSubTab('kv');
            } else if (imgId === 'img_ev_dt') {
                switchEvidenceGroup('priority');
                switchSubTab('dt');
            } else if (imgId === 'img_ev_10') {
                switchEvidenceGroup('academic');
                switchSubTab('10');
            } else if (imgId === 'img_ev_11') {
                switchEvidenceGroup('academic');
                switchSubTab('11');
            } else if (imgId === 'img_ev_12') {
                switchEvidenceGroup('academic');
                switchSubTab('12');
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(imgId);
                if (img) {
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                    const parent = img.closest('.relative');
                    if (parent) {
                        const placeholder = parent.querySelector('.flex.flex-col');
                        if (placeholder) placeholder.style.display = 'none';
                    }
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function showCertImage(certId) {
        document.querySelectorAll('.cert-evidence-item').forEach(el => el.classList.add('hidden'));
        const target = document.getElementById('cert_ev_' + certId);
        if (target) target.classList.remove('hidden');

        document.querySelectorAll('.cert-thumb-btn').forEach(btn => {
            if (btn.dataset.certId === certId) {
                btn.classList.add('border-teal-500', 'shadow-md', 'ring-4', 'ring-teal-500/10');
                btn.classList.remove('border-slate-100');
            } else {
                btn.classList.remove('border-teal-500', 'shadow-md', 'ring-4', 'ring-teal-500/10');
                btn.classList.add('border-slate-100');
            }
        });
    }

    async function rotateEvidenceImage(path, imgId, btn) {
        if (!path) return;

        try {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('path', path);

            // Xử lý đính kèm CSRF Token chống lỗi 403 (Phiên làm việc hết hạn)
            const csrfInput = document.querySelector('input[name="csrf_token"]');
            if (csrfInput) {
                formData.append('csrf_token', csrfInput.value);
            }

            const res = await fetch('<?= url("/admin/media/rotate") ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await res.json();
            if (data.success) {
                // Force reload image by appending timestamp
                const img = document.getElementById(imgId);
                if (img) {
                    const currentSrc = img.src;
                    const url = new URL(currentSrc);
                    url.searchParams.set('t', new Date().getTime());
                    img.src = url.toString();
                }
                showToast('Đã xoay ảnh thành công', 'success');
            } else {
                showToast(data.error || 'Lỗi khi xoay ảnh', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Lỗi kết nối khi xoay ảnh', 'error');
        } finally {
            btn.innerHTML = '<i class="fas fa-redo-alt"></i>';
            btn.disabled = false;
        }
    }
</script>


<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin.php';
?>