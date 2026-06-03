<?php ob_start(); ?>
<style>
    /* Decision Radio Colors */
    .decision-radio:checked + .decision-box-approve {
        background-color: #ecfdf5 !important;
        border-color: #10b981 !important;
        color: #047857 !important;
    }
    .decision-radio:checked + .decision-box-edit {
        background-color: #fffbeb !important;
        border-color: #f59e0b !important;
        color: #b45309 !important;
    }
    .decision-radio:checked + .decision-box-reject {
        background-color: #fef2f2 !important;
        border-color: #ef4444 !important;
        color: #b91c1c !important;
    }
</style>

<div class="-mt-4 mb-2">
    <!-- Global Hidden Inputs for JS Save -->
    <input type="hidden" name="cccd" value="<?= $user['so_cccd'] ?>">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h2 class="text-lg font-bold text-slate-800 uppercase flex items-center gap-2">
                <i class="fas fa-user-edit text-[#0066FF]"></i>
                <?= htmlspecialchars($user['ho_va_ten'] ?? 'Chưa có tên') ?>
            </h2>
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
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1 mr-4">
                    <?php if (!empty($prevCCCD)): ?>
                        <a href="<?= url('/admin/review?cccd=' . $prevCCCD) ?>" class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 hover:text-[#0066FF] transition-all shadow-sm" title="Hồ sơ trước">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </a>
                    <?php else: ?>
                        <span class="w-8 h-8 flex items-center justify-center bg-slate-100 border border-slate-200 text-slate-400 rounded-lg cursor-not-allowed" title="Không có hồ sơ trước">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($nextCCCD)): ?>
                        <a href="<?= url('/admin/review?cccd=' . $nextCCCD) ?>" class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 hover:text-[#0066FF] transition-all shadow-sm" title="Hồ sơ tiếp">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </a>
                    <?php else: ?>
                        <span class="w-8 h-8 flex items-center justify-center bg-slate-100 border border-slate-200 text-slate-400 rounded-lg cursor-not-allowed" title="Không có hồ sơ tiếp">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <span class="text-xs font-bold text-slate-400">
                    Hồ sơ chờ duyệt: <span class="text-[#0066FF]"><?= $navPosition ?? 0 ?>/<?= $navTotal ?></span>
                </span>
            </div>
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

    // Standardize helpers (shared across review tabs and main page)
    include_once __DIR__ . '/review/_helpers.php';
    ?>

    <!-- TABS NAVIGATION -->
    <div class="bg-white rounded-t-2xl shadow-sm border-b border-slate-200">
        <div class="flex overflow-x-auto gap-1 p-2" id="reviewTabs">
            <button type="button" onclick="switchTab('personal')" class="tab-btn px-3 py-1.5 font-black text-xs uppercase tracking-widest rounded-xl transition-all duration-200 whitespace-nowrap bg-[#0066FF] text-white shadow-md shadow-blue-200/50" data-tab="personal">
                <i class="fas fa-user mr-1"></i> 1. Thông tin cá nhân
            </button>
            <button type="button" onclick="switchTab('academic')" class="tab-btn px-3 py-1.5 font-black text-xs uppercase tracking-widest rounded-xl transition-all duration-200 whitespace-nowrap text-slate-500 hover:bg-slate-100 hover:text-slate-700" data-tab="academic">
                <i class="fas fa-graduation-cap mr-1"></i> 2. Học bạ
            </button>
            <button type="button" onclick="switchTab('certs')" class="tab-btn px-3 py-1.5 font-black text-xs uppercase tracking-widest rounded-xl transition-all duration-200 whitespace-nowrap text-slate-500 hover:bg-slate-100 hover:text-slate-700" data-tab="certs">
                <i class="fas fa-certificate mr-1"></i> 3. Chứng chỉ
            </button>
            <button type="button" onclick="switchTab('thpt')" class="tab-btn px-3 py-1.5 font-black text-xs uppercase tracking-widest rounded-xl transition-all duration-200 whitespace-nowrap text-slate-500 hover:bg-slate-100 hover:text-slate-700" data-tab="thpt">
                <i class="fas fa-poll-h mr-1"></i> 4. Điểm THPT
            </button>
            <button type="button" onclick="switchTab('wishes')" class="tab-btn px-3 py-1.5 font-black text-xs uppercase tracking-widest rounded-xl transition-all duration-200 whitespace-nowrap text-slate-500 hover:bg-slate-100 hover:text-slate-700" data-tab="wishes">
                <i class="fas fa-list-ol mr-1"></i> 5. Nguyện vọng
            </button>
        </div>
    </div>

    <!-- TABS CONTENT AREA -->
    <div class="bg-white rounded-b-2xl shadow-sm border border-t-0 border-slate-200 px-2 pb-2 pt-1" id="reviewTabContent">
        <?php include __DIR__ . '/review/_helpers.php'; ?>

        <div id="tab_personal" class="tab-content transition-all duration-300">
            <?php include __DIR__ . '/review/_tab_personal.php'; ?>
        </div>

        <div id="tab_academic" class="tab-content hidden transition-all duration-300">
            <div class="p-20 text-center">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
                <p class="text-slate-500 font-bold">Đang tải học bạ...</p>
            </div>
        </div>

        <div id="tab_certs" class="tab-content hidden transition-all duration-300">
            <div class="p-20 text-center">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
                <p class="text-slate-500 font-bold">Đang tải chứng chỉ...</p>
            </div>
        </div>

        <div id="tab_thpt" class="tab-content hidden transition-all duration-300">
            <div class="p-20 text-center">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
                <p class="text-slate-500 font-bold">Đang tải điểm THPT...</p>
            </div>
        </div>

        <div id="tab_wishes" class="tab-content hidden transition-all duration-300">
            <div class="p-20 text-center">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
                <p class="text-slate-500 font-bold">Đang tải nguyện vọng...</p>
            </div>
        </div>
    </div>
</form>

<!-- Action Bar: Fixed sticky bottom, respects sidebar width -->
<div id="review-action-bar" style="
    position: fixed;
    bottom: 0;
    left: var(--sidebar-width);
    right: 0;
    z-index: 50;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border-top: 1px solid #e2e8f0;
    padding: 16px 32px;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    transition: left 0.3s ease;
">
    <!-- Previous -->
    <div>
        <?php if (!empty($prevCCCD)): ?>
            <a href="<?= url('/admin/review?cccd=' . $prevCCCD) ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-[#0066FF] text-white font-bold text-sm rounded-xl hover:bg-blue-700 transition-all shadow-md">
                <i class="fas fa-chevron-left"></i> Hồ sơ trước
            </a>
        <?php else: ?>
            <span class="inline-flex items-center gap-2 px-6 py-3 bg-slate-100 border border-slate-200 text-slate-400 font-bold text-sm rounded-xl cursor-not-allowed">
                <i class="fas fa-ban"></i> Không có hồ sơ trước
            </span>
        <?php endif; ?>
    </div>

    <!-- Submit -->
    <div class="flex items-center gap-3">
        <button type="button" onclick="openReviewModal()" class="px-8 py-3.5 bg-emerald-600 text-white font-medium rounded-xl shadow-xl hover:bg-emerald-700 hover:-translate-y-0.5 transition-all flex items-center text-sm whitespace-nowrap ring-4 ring-emerald-500/10">
            Duyệt hồ sơ
        </button>
        <button type="button" onclick="openEmailModal()" class="px-8 py-3.5 bg-[#0066FF] text-white font-medium rounded-xl shadow-lg hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center text-sm whitespace-nowrap">
            Gửi email
        </button>
        <button type="button" onclick="confirmResetReviewStatus()" class="px-8 py-3.5 bg-[#0066FF] text-white font-medium rounded-xl shadow-lg hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center text-sm whitespace-nowrap">
            Hủy duyệt
        </button>

        <!-- Quick Search CCCD (Refined Outline Style) -->
        <input type="text" placeholder="Tìm CCCD..." 
               class="py-3.5 bg-white border-2 border-[#0066FF] text-slate-800 placeholder-black font-medium rounded-xl shadow-sm focus:ring-4 focus:ring-blue-500/10 transition-all outline-none text-sm w-32 text-center"
               onkeydown="if(event.key === 'Enter') { event.preventDefault(); const cccd = this.value.trim(); if(cccd) window.location.href = '<?= url('/admin/review') ?>?cccd=' + cccd; }">

        <!-- Zalo Button -->
        <?php
        $purePhone = preg_replace('/\D/', '', $user['dien_thoai'] ?? '');
        if (!empty($purePhone)): 
        ?>
            <a href="https://zalo.me/<?= $purePhone ?>" target="_blank" 
               class="w-12 h-12 flex items-center justify-center bg-[#0066FF] text-white font-bold text-xl rounded-xl hover:bg-blue-700 hover:-translate-y-0.5 transition-all shadow-lg" 
               title="Gửi tin nhắn Zalo (<?= htmlspecialchars($user['dien_thoai']) ?>)">Z</a>
        <?php else: ?>
            <span class="w-12 h-12 flex items-center justify-center bg-slate-100 border border-slate-200 text-slate-400 font-bold text-xl rounded-xl cursor-not-allowed" 
                  title="Thí sinh không có số điện thoại">Z</span>
        <?php endif; ?>
    </div>

    <!-- Next -->
    <div>
        <?php if (!empty($nextCCCD)): ?>
            <a href="<?= url('/admin/review?cccd=' . $nextCCCD) ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-[#0066FF] text-white font-bold text-sm rounded-xl hover:bg-blue-700 transition-all shadow-md">
                Hồ sơ tiếp <i class="fas fa-chevron-right"></i>
            </a>
        <?php else: ?>
            <span class="inline-flex items-center gap-2 px-6 py-3 bg-slate-100 border border-slate-200 text-slate-400 font-bold text-sm rounded-xl cursor-not-allowed">
                Không có hồ sơ tiếp <i class="fas fa-ban"></i>
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Push space so content isn't hidden behind fixed bar -->
<div style="height: 88px;"></div>

<!-- Review Modal -->
<div id="reviewModal" class="fixed inset-0 z-[100] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">Xác nhận duyệt hồ sơ</h3>
            <button type="button" onclick="closeReviewModal()" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="p-6">
            <div class="mb-5">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Trạng thái quyết định <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="cursor-pointer relative">
                        <input type="radio" name="modal_master_status" value="Đã duyệt" class="decision-radio sr-only" checked>
                        <div class="decision-box-approve text-center px-3 py-3 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition-all shadow-sm">
                            <i class="fas fa-check-circle block text-lg mb-1"></i> Đã duyệt
                        </div>
                    </label>
                    <label class="cursor-pointer relative">
                        <input type="radio" name="modal_master_status" value="Yêu cầu sửa" class="decision-radio sr-only">
                        <div class="decision-box-edit text-center px-3 py-3 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition-all shadow-sm">
                            <i class="fas fa-edit block text-lg mb-1"></i> Yêu cầu sửa
                        </div>
                    </label>
                    <label class="cursor-pointer relative">
                        <input type="radio" name="modal_master_status" value="Từ chối" class="decision-radio sr-only">
                        <div class="decision-box-reject text-center px-3 py-3 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition-all shadow-sm">
                            <i class="fas fa-times-circle block text-lg mb-1"></i> Từ chối
                        </div>
                    </label>
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Ghi chú nội bộ</label>
                <textarea id="modal_master_note" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#0066FF] focus:ring focus:ring-blue-100 outline-none transition-all resize-none shadow-sm" placeholder="Nhập ghi chú cho ban tuyển sinh (không gửi cho thí sinh)..."></textarea>
                <p class="text-[11px] text-slate-500 mt-2 bg-slate-50 p-2 rounded-lg border border-slate-100"><i class="fas fa-info-circle text-blue-500 mr-1"></i> Trạng thái lỗi chi tiết của từng phần vẫn được tổng hợp gửi qua Email cho thí sinh nếu chọn Từ chối/Yêu cầu sửa.</p>
            </div>
        </div>
        
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
            <button type="button" onclick="closeReviewModal()" class="px-5 py-2.5 text-slate-600 font-bold hover:bg-slate-200 rounded-xl transition-colors">
                Hủy bỏ
            </button>
            <button type="button" id="btnConfirmSubmit" onclick="confirmSubmitReview()" class="px-6 py-2.5 bg-[#0066FF] hover:bg-blue-700 text-white font-bold rounded-xl shadow-md transition-colors flex items-center">
                <i class="fas fa-paper-plane mr-2"></i> Lưu & Gửi Email
            </button>
        </div>
    </div>
</div>

<script>
// Adjust action bar left position when sidebar collapses/expands
function updateActionBarOffset() {
    const bar = document.getElementById('review-action-bar');
    if (!bar) return;
    const sidebar = document.querySelector('.admin-sidebar');
    const collapsed = sidebar && sidebar.classList.contains('collapsed');
    bar.style.left = collapsed ? 'var(--sidebar-collapsed)' : 'var(--sidebar-width)';
}
// Watch for sidebar toggle
const sidebarObserver = new MutationObserver(updateActionBarOffset);
const sidebarEl = document.querySelector('.admin-sidebar');
if (sidebarEl) sidebarObserver.observe(sidebarEl, { attributes: true, attributeFilter: ['class'] });
// Initial call
updateActionBarOffset();
</script>


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

            handleFocus() {
                this.open = true;
                this.search = '';
            },

            handleClickAway() {
                this.open = false;
                if (this.selectedCode) {
                    const found = this.wards.find(w => w.ma_xa == this.selectedCode);
                    if (found) this.search = found.ten_xa;
                } else {
                    this.search = '';
                }
            },

            get filteredWards() {
                if (this.search === '') return this.wards;
                const lower = this.search.toLowerCase();
                return this.wards.filter(w => w.ten_xa.toLowerCase().includes(lower));
            }
        }));

        Alpine.data('schoolSearch', (initialProvince, initialSchool, initialText = '') => ({
            open: false,
            search: initialText,
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
                        ma_kv: school.khu_vuc
                    }
                }));
            },

            handleFocus() {
                this.open = true;
                this.search = '';
            },

            handleClickAway() {
                this.open = false;
                if (this.selectedCode) {
                    const found = this.schools.find(s => s.ma_truong == this.selectedCode);
                    if (found) this.search = found.ten_truong;
                } else {
                    this.search = '';
                }
            },

            get filteredSchools() {
                if (this.search === '') return this.schools;
                const lower = this.search.toLowerCase();
                return this.schools.filter(s => s.ten_truong.toLowerCase().includes(lower) || s.ma_truong.toLowerCase().includes(lower));
            }
        }));
    });

    // TABS LOGIC
    let currentActiveTab = 'personal'; 
    const tabCache = { personal: true };

    async function loadTab(tabName) {
        if (tabCache[tabName]) return true;
        const container = document.getElementById('tab_' + tabName);
        if (!container) return false;

        try {
            const url = `<?= url('/admin/review/tab') ?>?cccd=<?= $user['so_cccd'] ?>&tab=${tabName}`;
            const res = await fetch(url);
            if (!res.ok) throw new Error('Network response was not ok');
            const html = await res.text();
            
            // Inject HTML
            container.innerHTML = html;
            tabCache[tabName] = true;

            // Re-init Alpine for the new content
            if (window.Alpine) {
                window.Alpine.initTree(container);
            }
            return true;
        } catch (e) {
            console.error('Error loading tab ' + tabName + ':', e);
            // If it failed during a background preload, we'll let the user retry on click
            // But we don't overwrite the spinner unless it's an active switch (handled in switchTab)
            return false;
        }
    }

    async function switchTab(tabName) {
        currentActiveTab = tabName;
        
        // Update URL without reload (optional but useful for manual refresh)
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);

        // UI: Tab Buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('bg-[#0066FF]', 'text-white', 'shadow-md', 'shadow-blue-200/50');
            btn.classList.add('text-slate-500', 'hover:bg-slate-100', 'hover:text-slate-700');
            if (btn.dataset.tab === tabName) {
                btn.classList.add('bg-[#0066FF]', 'text-white', 'shadow-md', 'shadow-blue-200/50');
                btn.classList.remove('text-slate-500', 'hover:bg-slate-100', 'hover:text-slate-700');
            }
        });

        // Toggle Content Panes
        const container = document.getElementById('tab_' + tabName);
        if (!container) return;

        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        container.classList.remove('hidden');

        // Load if not cached (active request)
        if (!tabCache[tabName]) {
            const success = await loadTab(tabName);
            if (!success) {
                container.innerHTML = `<div class="p-20 text-center text-rose-500">
                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                    <p class="font-bold">Lỗi tải dữ liệu. Vui lòng thử lại.</p>
                    <button type="button" onclick="switchTab('${tabName}')" class="mt-4 px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm text-slate-700 font-bold transition-all">
                        Thử lại
                    </button>
                </div>`;
            }
        }
    }

    // Eager Pre-loader: Load remaining tabs when idle
    window.addEventListener('load', () => {
        const preloadTabs = async () => {
            // Check for initial tab from URL
            const urlParams = new URLSearchParams(window.location.search);
            const initialTab = urlParams.get('tab');
            if (initialTab && initialTab !== 'personal' && ['personal', 'academic', 'certs', 'thpt', 'wishes'].includes(initialTab)) {
                switchTab(initialTab);
            }

            try {
                const url = `<?= url('/admin/review/batch-tabs') ?>?cccd=<?= $user['so_cccd'] ?>`;
                const res = await fetch(url);
                if (!res.ok) throw new Error('Batch fetch failed');
                
                const data = await res.json();
                if (data.success && data.tabs) {
                    Object.keys(data.tabs).forEach(tab => {
                        if (!tabCache[tab]) {
                            const container = document.getElementById('tab_' + tab);
                            if (container) {
                                container.innerHTML = data.tabs[tab];
                                tabCache[tab] = true;
                                if (window.Alpine) window.Alpine.initTree(container);
                            }
                        }
                    });
                }
            } catch (e) {
                console.error('Batch preload failed:', e);
            }
        };

        if ('requestIdleCallback' in window) {
            requestIdleCallback(preloadTabs);
        } else {
            setTimeout(preloadTabs, 2000);
        }
    });

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

    function openReviewModal() {
        const modal = document.getElementById('reviewModal');
        modal.classList.remove('hidden');
        
        let hasRejected = false;
        let collectedNotes = [];
        
        const sectionsList = ['personal', 'academic', 'certs', 'thpt', 'wishes'];
        const sectionNames = {
            'personal': 'Thông tin cá nhân',
            'academic': 'Học bạ',
            'certs': 'Chứng chỉ',
            'thpt': 'Điểm THPT',
            'wishes': 'Nguyện vọng'
        };
        
        sectionsList.forEach(sec => {
            const checkedRadio = document.querySelector(`input[name="status_${sec}"]:checked`);
            if (checkedRadio && checkedRadio.value === 'rejected') {
                hasRejected = true;
                const reasonInput = document.querySelector(`textarea[name="note_${sec}"]`);
                if (reasonInput && reasonInput.value.trim() !== '') {
                    collectedNotes.push(`[${sectionNames[sec]}]: ${reasonInput.value.trim()}`);
                }
            }
        });
        
        const statusRadios = document.querySelectorAll('input[name="modal_master_status"]');
        statusRadios.forEach(r => r.checked = false);
        
        // Luôn mặc định là Đã duyệt theo yêu cầu người dùng
        document.querySelector('input[name="modal_master_status"][value="Đã duyệt"]').checked = true;
        
        const noteArea = document.getElementById('modal_master_note');
        if (collectedNotes.length > 0) {
            noteArea.value = collectedNotes.join('\n');
        } else {
            noteArea.value = '';
        }
    }

    function closeReviewModal() {
        document.getElementById('reviewModal').classList.add('hidden');
    }

    function confirmSubmitReview() {
        const btn = document.getElementById('btnConfirmSubmit');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang xử lý...';
        btn.disabled = true;
        
        Loading.show();
        const form = document.getElementById('reviewForm');
        const formData = new FormData(form);
        
        const masterStatus = document.querySelector('input[name="modal_master_status"]:checked').value;
        const masterNote = document.getElementById('modal_master_note').value;
        formData.append('master_status', masterStatus);
        formData.append('master_note', masterNote);

        fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const msg = masterStatus === 'Đã duyệt' ? 'Đã duyệt hồ sơ thành công!' : 'Đã cập nhật trạng thái hồ sơ.';
                    if (typeof Toast !== 'undefined') Toast.success(msg);
                    window.location.href = data.next_url;
                } else {
                    Loading.hide();
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                    closeReviewModal();
                    if (typeof Toast !== 'undefined') Toast.error(data.error || 'Có lỗi xảy ra');
                }
            })
            .catch(err => {
                Loading.hide();
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                closeReviewModal();
                if (typeof Toast !== 'undefined') Toast.error('Lỗi kết nối: ' + err.message);
            });
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

        if (section === 'academic' || section === 'thpt') {
            const fields = document.querySelectorAll(`.${section}-edit-field, .${section}-view-field`);
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


            // For 'academic' and 'thpt' sections, collect outer inputs (like evidence in sidebar)
            if (section === 'academic' || section === 'thpt') {
                const outerInputs = document.querySelectorAll(`.${section}-edit-field input, .${section}-edit-field select`);
                outerInputs.forEach(input => {
                    if (!input.name) return;
                    if (input.type === 'file') {
                        if (input.files && input.files.length > 0) {
                            formData.append(input.name, input.files[0]);
                        }
                    } else if (input.type === 'checkbox' || input.type === 'radio') {
                        if (input.checked) formData.append(input.name, input.value);
                    } else {
                        if (!formData.has(input.name)) {
                            formData.append(input.name, input.value);
                        } else {
                            formData.set(input.name, input.value);
                        }
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
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const contentType = res.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                const data = await res.json();
                if (data.success) {
                    showToast('Đã lưu dữ liệu thành công!', 'success');
                    setTimeout(() => {
                        const url = new URL(window.location.href);
                        if (data.new_cccd) url.searchParams.set('cccd', data.new_cccd);
                        url.searchParams.set('tab', currentActiveTab);
                        window.location.href = url.toString();
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
        const img = document.getElementById(imgId);
        if (!img) return;

        // Visual Feedback: Immediate CSS Rotation
        // Initialize or increment rotation state
        let currentRotation = parseInt(img.dataset.rotation || 0);
        currentRotation = (currentRotation + 90) % 360;
        img.dataset.rotation = currentRotation;

        // Apply transform.
        img.style.transition = 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
        img.style.transform = `rotate(${currentRotation}deg)`;

        try {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('path', path);

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
                // Background update: Refresh src to get potentially updated GDrive thumbnail eventually
                // But CSS rotation keeps it looking right NOW.
                setTimeout(() => {
                    const url = new URL(img.src);
                    url.searchParams.set('t', new Date().getTime());
                    img.src = url.toString();
                }, 2000); // Small delay to let GDrive process
                
                if (typeof showToast === 'function') showToast('Đã xoay ảnh thành công', 'success');
                else if (typeof Toast !== 'undefined') Toast.success('Đã xoay ảnh thành công');
            } else {
                if (typeof showToast === 'function') showToast(data.error || 'Lỗi khi xoay ảnh', 'error');
            }
        } catch (e) {
            console.error(e);
        } finally {
            btn.innerHTML = '<i class="fas fa-redo-alt"></i>';
            btn.disabled = false;
        }
    }

    // Email Modal Functions
    function openEmailModal() {
        const countSpan = document.getElementById('email-target-count');
        if (countSpan) countSpan.innerText = '1';
        
        const subjectArea = document.getElementById('email-modal-subject');
        const contentArea = document.getElementById('email-editor');
        const noteArea = document.getElementById('email-modal-internal-note');
        
        const today = new Date().toLocaleDateString('vi-VN');
        
        if (subjectArea) subjectArea.value = '';
        if (contentArea) contentArea.innerHTML = '';

        if (noteArea) {
            noteArea.value = `Gửi mail ngày: ${today}`;
        }
        
        document.getElementById('email-modal').classList.remove('hidden');
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    }

    function applyEmailTemplate(val) {
        var sel = document.getElementById('email-template-select');
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !val) {
            document.getElementById('email-modal-subject').value = '';
            document.getElementById('email-editor').innerHTML = '';
            return;
        }
        var subject = opt.getAttribute('data-subject') || '';
        var body = opt.getAttribute('data-body') || '';
        document.getElementById('email-modal-subject').value = subject;
        document.getElementById('email-editor').innerHTML = body;
    }

    function confirmSendEmail() {
        const subject = document.getElementById('email-modal-subject').value;
        const content = document.getElementById('email-editor').innerHTML;
        const templateId = document.getElementById('email-template-select').value;

        if (!subject || !content || content.trim() === '') {
            showToast('Vui lòng nhập tiêu đề và nội dung', 'warning');
            return;
        }

        const ids = ['<?= $user['so_cccd'] ?>'];

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= url("/admin/candidates/bulk-action") ?>';

        const inputCsrf = document.createElement('input');
        inputCsrf.type = 'hidden';
        inputCsrf.name = '_csrf_token';
        inputCsrf.value = '<?= csrf_token() ?>';
        form.appendChild(inputCsrf);

        ids.forEach(id => {
            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'ids[]';
            inputId.value = id;
            form.appendChild(inputId);
        });

        const inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'action';
        inputAction.value = 'send_email';
        form.appendChild(inputAction);

        const inputTpl = document.createElement('input');
        inputTpl.type = 'hidden';
        inputTpl.name = 'template_id';
        inputTpl.value = templateId;
        form.appendChild(inputTpl);

        const inputSubject = document.createElement('input');
        inputSubject.type = 'hidden';
        inputSubject.name = 'email_subject';
        inputSubject.value = subject;
        form.appendChild(inputSubject);

        const inputContent = document.createElement('input');
        inputContent.type = 'hidden';
        inputContent.name = 'email_content';
        inputContent.value = content;
        form.appendChild(inputContent);

        const inputNote = document.createElement('input');
        inputNote.type = 'hidden';
        inputNote.name = 'internal_note';
        const noteEl = document.getElementById('email-modal-internal-note');
        inputNote.value = noteEl ? noteEl.value : '';
        form.appendChild(inputNote);

        const inputRedirect = document.createElement('input');
        inputRedirect.type = 'hidden';
        inputRedirect.name = 'redirect_to';
        inputRedirect.value = window.location.href;
        form.appendChild(inputRedirect);

        document.body.appendChild(form);
        Loading.show();
        form.submit();
    }
    // --- SECTION: CERTIFICATES (TAB 3) GLOBAL HANDLING ---
    // Moved here because innerHTML tab-swapping prevents script execution in sub-views.
    
    function updateTab3Indices() {
        const tbody = document.getElementById('tab3_cert_list_tbody');
        if (!tbody) return;
        const rows = tbody.querySelectorAll('.cert-item');
        rows.forEach((row, i) => {
            const indexCell = row.querySelector('.index-cell');
            if (indexCell) indexCell.textContent = i + 1;
        });
    }

    function removeAdminCert(btn) {
        const row = btn.closest('.cert-item');
        if (!row) return;
        
        row.style.opacity = '0';
        row.style.transform = 'scale(0.95)';
        row.style.transition = 'all 0.3s ease';
        
        setTimeout(() => {
            row.remove();
            updateTab3Indices();
            
            const tbody = document.getElementById('tab3_cert_list_tbody');
            if (tbody && tbody.querySelectorAll('.cert-item').length === 0) {
                tbody.innerHTML = `<tr id="no_tab3_certs_row"><td colspan="4" style="padding: 20px; text-align: center; color: #94a3b8; font-style: italic;">Chưa có chứng chỉ ngoại ngữ / tin học</td></tr>`;
            }
        }, 300);
    }

    function handleTab3AddRow() {
        const tbody = document.getElementById('tab3_cert_list_tbody');
        if (!tbody) return;

        const noRow = document.getElementById('no_tab3_certs_row');
        if (noRow) noRow.remove();

        const nextIndex = Date.now(); 

        const rowHtml = `
            <tr class="cert-item bg-white border-b border-slate-100 transition-all duration-300 animate-in fade-in slide-in-from-left-2 transition-all duration-300 hover:bg-blue-50/10">
                <td style="padding: 20px !important; text-align: center; border-right: 1px solid #e2e8f0; color: #000; font-weight: 500;" class="index-cell">0</td>
                <td style="padding: 10px 12px !important; border-right: 1px solid #e2e8f0; color: #000;">
                    <div class="relative">
                        <select name="certs[${nextIndex}][type]" 
                            style="width: 100%; height: 40px; padding: 0 8px; border: 1px solid transparent; background: transparent; font-size: 12px; font-weight: 500; color: #000; outline: none; appearance: none; cursor: pointer; display: block;"
                            class="hover:border-slate-200 focus:border-[#0066FF] focus:bg-white focus:ring-0" required>
                            <option value="">-- Chọn --</option>
                            <optgroup label="Tiếng Anh"><option value="IELTS">IELTS</option><option value="TOEFL iBT">TOEFL iBT</option><option value="TOEIC">TOEIC</option></optgroup>
                            <optgroup label="Ngoại ngữ khác"><option value="HSK">HSK (Tiếng Trung)</option><option value="JLPT">JLPT (Tiếng Nhật)</option></optgroup>
                            <optgroup label="Tin học"><option value="IC3">IC3</option><option value="MOS">MOS</option></optgroup>
                        </select>
                        <i class="fas fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-slate-300 text-[8px] pointer-events-none"></i>
                    </div>
                </td>
                <td style="padding: 10px 12px !important; border-right: 1px solid #e2e8f0; color: #000;">
                    <input type="text" name="certs[${nextIndex}][score]" placeholder="—" 
                        style="width: 100%; height: 40px; padding: 0; text-align: center; border: 1px solid transparent; background: transparent; font-size: 12px; font-weight: 500; color: #000; outline: none;"
                        class="hover:border-slate-200 focus:border-[#0066FF] focus:bg-white focus:ring-0 placeholder-slate-300 font-medium">
                </td>
                <td style="padding: 20px !important; text-align: center; color: #000;">
                    <div class="flex items-center justify-center gap-2">
                        <label class="cursor-pointer group/upload">
                            <i class="fas fa-camera text-blue-600 group-hover/upload:text-blue-700 transition-colors text-xs"></i>
                            <input type="file" name="cert_files[${nextIndex}]" accept="image/*" class="hidden" onchange="window.previewAdminCert(this)">
                        </label>
                        <button type="button" onclick="window.removeAdminCert(this)" class="text-red-500 hover:text-red-600 transition-colors p-1">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>
                    </div>
                </td>
            </tr>`;

        tbody.insertAdjacentHTML('beforeend', rowHtml);
        updateTab3Indices();
    }

    function previewAdminCert(input) {
        if (!input.files || !input.files[0]) return;
        const icon = input.closest('label').querySelector('.fa-camera');
        if (icon) {
            icon.classList.remove('text-blue-600');
            icon.classList.add('text-emerald-500');
            icon.style.filter = 'drop-shadow(0 0 4px rgba(16, 185, 129, 0.4))';
        }
    }

    // Force global visibility
    window.v5_row_add = v5_row_add;
    window.v5_row_remove = v5_row_remove;
    window.v5_update_combo = v5_update_combo;
</script>

<!-- ASPIRATIONS (TAB 5) GLOBAL HANDLING -->
<script>
    var v5_combos = <?= json_encode(array_column($majors ?? [], 'to_hop_xet_tuyen', 'ma_nganh')) ?>;

    function v5_update_combo(select, index) {
        if (!select) return;
        var majorId = select.value;
        var div = document.getElementById('v5-combo-' + index);
        if (!div) return;
        
        if (majorId && v5_combos[majorId]) {
            div.textContent = v5_combos[majorId]; // Hiển thị chữ thuần ngăn cách bằng dấu phẩy
        } else {
            div.innerHTML = '<span class="text-[10px] text-slate-300 italic">N/A</span>';
        }
    }

    function v5_refresh_orders() {
        var table = document.getElementById('adminChoicesTable');
        if (!table) return;
        var rows = table.querySelectorAll('.choice-row');
        rows.forEach(function(row, index) {
            var stt = index + 1;
            var sttCell = row.querySelector('td:first-child');
            if (sttCell) sttCell.textContent = stt;
            
            var hidden = row.querySelector('input[type="hidden"]');
            if (hidden) {
                hidden.value = stt;
                hidden.name = 'choices[' + index + '][thu_tu]';
            }
            
            var select = row.querySelector('select');
            if (select) {
                select.name = 'choices[' + index + '][nganh_id]';
                select.setAttribute('onchange', 'v5_update_combo(this, ' + index + ')');
            }
            
            var combo = row.querySelector('.admin-combo-display');
            if (combo) combo.id = 'v5-combo-' + index;
        });
    }

    function v5_row_add() {
        var tbody = document.querySelector('#adminChoicesTable tbody');
        if (!tbody) return;
        var rows = tbody.querySelectorAll('.choice-row');
        if (rows.length >= 6) { alert('Tối đa 06 nguyện vọng'); return; }
        
        var empty = tbody.querySelector('.empty-row');
        if (empty) empty.remove();
        
        var templateEl = document.getElementById('v5RowTemplate');
        if (!templateEl) {
            console.error('v5RowTemplate not found');
            return;
        }
        var template = templateEl.innerHTML;
        var nIdx = rows.length;
        var nSTT = nIdx + 1;
        
        var html = template.replace(/INDEX_VAL/g, nIdx).replace(/STT_VAL/g, nSTT);
        tbody.insertAdjacentHTML('beforeend', html);
        v5_refresh_orders();
    }

    function v5_row_remove(btn) {
        var row = btn.closest('tr');
        if (row) {
            row.remove();
            v5_refresh_orders();
            
            var tbody = document.querySelector('#adminChoicesTable tbody');
            if (tbody && tbody.querySelectorAll('.choice-row').length === 0) {
                tbody.innerHTML = '<tr class="empty-row border-b border-slate-50"><td colspan="4" class="py-16 text-center text-slate-400 font-medium italic">Chưa có nguyện vọng nào. Bấm "Thêm nguyện vọng" bên dưới.</td></tr>';
            }
        }
    }

    // Logic Hủy duyệt: Đưa trạng thái về Chờ duyệt
    async function confirmResetReviewStatus() {
        if (!confirm('Bạn có chắc chắn muốn hủy trạng thái duyệt và đưa hồ sơ này về trạng thái "Chờ duyệt"?')) return;
        
        Loading.show();
        const form = document.getElementById('reviewForm');
        const formData = new FormData(form);
        
        formData.append('master_status', 'Chờ duyệt');
        formData.append('master_note', 'Hủy duyệt bởi quản trị viên');

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                if (typeof Toast !== 'undefined') Toast.success('Đã đưa hồ sơ về trạng thái Chờ duyệt');
                setTimeout(() => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', currentActiveTab);
                    window.location.href = url.toString();
                }, 800);
            } else {
                Loading.hide();
                alert('Lỗi: ' + (data.error || 'Không thể cập nhật trạng thái'));
            }
        } catch (e) {
            Loading.hide();
            console.error(e);
            alert('Lỗi kết nối: ' + e.message);
        }
    }

    // Explicitly expose to window
    window.v5_row_add = v5_row_add;
    window.v5_row_remove = v5_row_remove;
    window.v5_update_combo = v5_update_combo;
    window.v5_refresh_orders = v5_refresh_orders;
    window.confirmResetReviewStatus = confirmResetReviewStatus;
</script>


<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin.php';
?>