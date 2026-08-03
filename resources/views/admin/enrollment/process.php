<?php
$title = "Xử lý Nhập học";
ob_start();
?>
<style>
/* ========== ENROLLMENT PROCESS - CUSTOM STYLES ========== */
.ep-wrapper {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 90px);
    gap: 0;
    font-family: 'Inter', sans-serif;
}
.ep-header {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 16px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0;
    flex-wrap: wrap;
}
.ep-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 5px 14px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid transparent;
    transition: all 0.2s;
}
.ep-body {
    display: flex;
    flex: 1;
    overflow: hidden;
    border-radius: 0 0 12px 12px;
}
/* LEFT PANEL */
.ep-left {
    width: 52%;
    min-width: 400px;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #e2e8f0;
    background: #f8fafc;
    overflow: hidden;
}
/* RIGHT PANEL */
.ep-right {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #fff;
    overflow: hidden;
}
/* SEARCH AREA */
.ep-search-area {
    flex-shrink: 0;
    padding: 14px 16px 10px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
}
.ep-search-input-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f1f5f9;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 0 12px;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.ep-search-input-wrap:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    background: #fff;
}
.ep-search-input-wrap input {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    padding: 10px 0;
    font-size: 14px;
    color: #1e293b;
}
.ep-search-input-wrap input::placeholder { color: #94a3b8; }
.ep-search-btn {
    padding: 7px 18px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    box-shadow: 0 2px 6px rgba(37,99,235,0.3);
}
.ep-search-btn:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); transform: translateY(-1px); }
/* SEARCH RESULTS */
.ep-search-results {
    margin-top: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    max-height: 220px;
    overflow-y: auto;
    background: #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.ep-result-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 14px;
    cursor: pointer;
    transition: background 0.15s;
    border-bottom: 1px solid #f1f5f9;
}
.ep-result-item:last-child { border-bottom: none; }
.ep-result-item:hover, .ep-result-item.active { background: #eff6ff; }
.ep-result-name { font-weight: 700; font-size: 13px; color: #1e293b; }
.ep-result-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
/* CARD AREA */
.ep-card-area {
    flex: 1;
    overflow-y: auto;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
.ep-card-area::-webkit-scrollbar { width: 5px; }
.ep-card-area::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
/* CANDIDATE CARD */
.ep-candidate-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}
.ep-profile-header {
    background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%);
    padding: 16px;
    display: flex;
    gap: 14px;
    align-items: flex-start;
    position: relative;
    overflow: hidden;
}
.ep-profile-header::before {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 120px; height: 120px;
    border-radius: 50%;
    background: rgba(255,255,255,0.07);
}
.ep-avatar {
    width: 68px; height: 82px;
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
    border: 2px solid rgba(255,255,255,0.3);
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,0.7);
    font-size: 28px;
    flex-shrink: 0;
}
.ep-profile-name { font-size: 17px; font-weight: 800; color: #fff; line-height: 1.2; text-transform: uppercase; }
.ep-profile-code { 
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);
    padding: 3px 10px; border-radius: 99px;
    font-size: 11px; font-weight: 700; color: #fff;
    margin-top: 6px;
}
.ep-profile-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 99px;
    font-size: 11px; font-weight: 700;
    margin-top: 6px; margin-left: 6px;
}
.badge-enrolled { background: #dcfce7; color: #15803d; }
.badge-pending  { background: #fef9c3; color: #a16207; }
.badge-cancelled{ background: #fee2e2; color: #b91c1c; }
.badge-new      { background: #f1f5f9; color: #475569; }
/* TABS */
.ep-tabs { display: flex; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
.ep-tab {
    flex: 1; padding: 11px 8px;
    font-size: 12px; font-weight: 600;
    color: #94a3b8; border: none;
    background: transparent; cursor: pointer;
    text-transform: uppercase; letter-spacing: 0.4px;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.ep-tab.active { color: #2563eb; border-bottom-color: #2563eb; background: #fff; }
.ep-tab:hover:not(.active) { color: #475569; background: #f1f5f9; }
/* TAB CONTENT */
.ep-tab-pane { display: none; padding: 14px 16px; }
.ep-tab-pane.active { display: block; }
/* INFO GRID */
.ep-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px 16px;
}
.ep-info-item label { display: block; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
.ep-info-item span { display: block; font-size: 13px; color: #1e293b; font-weight: 600; }
.ep-info-item input[type="text"],
.ep-info-item input[type="date"] {
    width: 100%;
    border: 1.5px solid #e2e8f0;
    border-radius: 7px;
    padding: 5px 9px;
    font-size: 13px;
    color: #1e293b;
    outline: none;
    background: #fff;
    transition: border-color 0.15s;
    font-family: inherit;
}
.ep-info-item input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
/* SCORE BADGE */
.ep-score-badge {
    display: inline-flex;
    align-items: baseline;
    gap: 3px;
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border: 1px solid #fbbf24;
    border-radius: 8px;
    padding: 4px 10px;
}
.ep-score-badge .score-num { font-size: 20px; font-weight: 800; color: #d97706; }
.ep-score-badge .score-label { font-size: 10px; color: #92400e; font-weight: 600; }
/* SECTION TITLE */
.ep-section-title {
    font-size: 11px; font-weight: 700; color: #2563eb;
    text-transform: uppercase; letter-spacing: 0.6px;
    display: flex; align-items: center; gap: 6px;
    margin-bottom: 10px; padding-bottom: 6px;
    border-bottom: 2px solid #eff6ff;
}
/* DOCUMENT CHECKLIST */
.ep-doc-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    margin-bottom: 7px;
    transition: background 0.15s;
}
.ep-doc-toggle {
    width: 44px; height: 24px;
    background: #e2e8f0; border-radius: 99px;
    position: relative; cursor: pointer;
    border: none; flex-shrink: 0;
    transition: background 0.2s;
}
.ep-doc-toggle.checked { background: #22c55e; }
.ep-doc-toggle::after {
    content: '';
    position: absolute;
    width: 18px; height: 18px;
    background: #fff; border-radius: 50%;
    top: 3px; left: 3px;
    transition: left 0.2s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.2);
}
.ep-doc-toggle.checked::after { left: 23px; }
.ep-doc-label { flex: 1; font-size: 13px; color: #374151; font-weight: 500; }
.ep-doc-select {
    border: 1px solid #e2e8f0; border-radius: 6px;
    padding: 4px 8px; font-size: 12px; color: #374151;
    background: #fff; outline: none; min-width: 100px;
}
.ep-doc-select:focus { border-color: #3b82f6; }
/* QR CARD */
.ep-qr-card {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #bbf7d0;
    border-radius: 14px;
    padding: 20px;
    text-align: center;
    display: flex; flex-direction: column; align-items: center; gap: 10px;
}
.ep-qr-img { width: 160px; height: 160px; border-radius: 12px; border: 3px solid #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.ep-qr-amount { font-size: 26px; font-weight: 800; color: #15803d; }
/* ACTION BAR */
.ep-action-bar {
    flex-shrink: 0;
    padding: 12px 16px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
}
.ep-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: 9px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    border: 1.5px solid transparent;
    transition: all 0.18s;
    white-space: nowrap;
}
.ep-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.ep-btn-ghost { background: #f8fafc; border-color: #e2e8f0; color: #475569; }
.ep-btn-ghost:hover:not(:disabled) { background: #f1f5f9; }
.ep-btn-save { background: #fff; border-color: #93c5fd; color: #2563eb; }
.ep-btn-save:hover:not(:disabled) { background: #eff6ff; }
.ep-btn-enroll { background: linear-gradient(135deg, #16a34a, #15803d); border-color: #16a34a; color: #fff; box-shadow: 0 2px 8px rgba(22,163,74,0.3); }
.ep-btn-enroll:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(22,163,74,0.4); }
.ep-btn-cancel { background: #fff7ed; border-color: #fdba74; color: #c2410c; }
.ep-btn-cancel:hover:not(:disabled) { background: #ffedd5; }
.ep-btn-print { background: linear-gradient(135deg, #7c3aed, #6d28d9); border-color: #7c3aed; color: #fff; box-shadow: 0 2px 8px rgba(124,58,237,0.25); }
.ep-btn-print:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(124,58,237,0.35); }
/* EMPTY STATE */
.ep-empty-state {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 12px; color: #94a3b8; text-align: center;
    padding: 40px;
}
.ep-empty-icon { font-size: 48px; color: #e2e8f0; }
.ep-empty-title { font-size: 15px; font-weight: 700; color: #94a3b8; }
.ep-empty-desc { font-size: 13px; color: #b0bec5; max-width: 260px; }
/* RIGHT PANEL */
.ep-right-header {
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}
.ep-right-title { font-size: 13px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; }
.ep-table-wrap { flex: 1; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
.ep-table-wrap::-webkit-scrollbar { width: 4px; }
.ep-table-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
.ep-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.ep-table thead th {
    padding: 9px 10px; text-align: left;
    font-size: 10px; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: 0.5px;
    background: #f8fafc; border-bottom: 1px solid #e2e8f0;
    position: sticky; top: 0; z-index: 1;
}
.ep-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background 0.12s;
}
.ep-table tbody tr:hover { background: #eff6ff; }
.ep-table tbody td { padding: 8px 10px; vertical-align: middle; }
.ep-name-cell { font-weight: 700; color: #1e293b; max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ep-cccd-cell { font-size: 10px; color: #64748b; font-family: monospace; margin-top: 2px; }
.ep-nganh-cell { color: #475569; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; }
/* DOC DOTS */
.ep-doc-dot {
    display: inline-flex; align-items: center; justify-content: center;
    width: 20px; height: 20px; border-radius: 50%;
    font-size: 9px; font-weight: 700;
    border: 1px solid;
    margin: 1px;
}
.ep-doc-dot.submitted { background: #dcfce7; color: #15803d; border-color: #86efac; }
.ep-doc-dot.missing   { background: #f1f5f9; color: #cbd5e1; border-color: #e2e8f0; }
/* STATUS BADGE */
.ep-status {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px; border-radius: 99px;
    font-size: 10px; font-weight: 700;
    white-space: nowrap;
}
.ep-status.enrolled  { background: #dcfce7; color: #15803d; }
.ep-status.pending   { background: #fef9c3; color: #a16207; }
.ep-status.cancelled { background: #fee2e2; color: #b91c1c; }
/* PAGINATION */
.ep-pagination {
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    font-size: 12px; color: #64748b;
}
.ep-page-btn {
    width: 28px; height: 28px;
    border: 1px solid #e2e8f0; border-radius: 7px;
    background: #fff; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 11px; color: #475569;
    transition: all 0.15s;
}
.ep-page-btn:hover:not(:disabled) { background: #eff6ff; border-color: #93c5fd; color: #2563eb; }
.ep-page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
/* SCROLLBAR */
.ep-search-results::-webkit-scrollbar { width: 4px; }
.ep-search-results::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
/* NOTIFICATION TOAST */
.ep-toast {
    position: fixed; bottom: 24px; right: 24px;
    padding: 12px 20px; border-radius: 12px;
    font-size: 13px; font-weight: 600;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    z-index: 9999;
    animation: ep-toast-in 0.3s ease;
}
.ep-toast.success { background: #15803d; color: #fff; }
.ep-toast.error   { background: #b91c1c; color: #fff; }
.ep-toast.info    { background: #1d4ed8; color: #fff; }
@keyframes ep-toast-in { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
/* LOADING SHIMMER */
.ep-shimmer {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: ep-shimmer 1.5s infinite;
    border-radius: 6px;
    height: 14px;
}
@keyframes ep-shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
</style>

<div class="ep-wrapper" x-data="enrollmentProcess()">

    <!-- =========== HEADER BAR =========== -->
    <div class="ep-header">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#2563eb,#1d4ed8);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-id-card-alt" style="color:#fff;font-size:16px;"></i>
            </div>
            <div>
                <div style="font-size:15px;font-weight:800;color:#1e293b;">Quản lý Nhập học</div>
                <div style="font-size:11px;color:#64748b;">Đợt tuyển sinh</div>
            </div>
        </div>

        <!-- STATS ROW -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <div class="ep-stat-pill" style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;">
                <i class="fas fa-users" style="font-size:11px;"></i>
                <span>Trúng tuyển: <strong x-text="stats.tong_thi_sinh">–</strong></span>
            </div>
            <div class="ep-stat-pill" style="background:#f0fdf4;border-color:#bbf7d0;color:#15803d;">
                <i class="fas fa-check-circle" style="font-size:11px;"></i>
                <span>Đã nhập học: <strong x-text="stats.da_nhap_hoc">–</strong></span>
                <span style="font-size:10px;opacity:0.8;" x-show="stats.tong_thi_sinh > 0" x-text="'(' + ((stats.da_nhap_hoc / stats.tong_thi_sinh)*100).toFixed(0) + '%)'"></span>
            </div>
            <div class="ep-stat-pill" style="background:#fefce8;border-color:#fde68a;color:#a16207;">
                <i class="fas fa-clock" style="font-size:11px;"></i>
                <span>Chờ duyệt: <strong x-text="stats.cho_xet_duyet">–</strong></span>
            </div>
            <div class="ep-stat-pill" style="background:#f1f5f9;border-color:#cbd5e1;color:#64748b;">
                <i class="fas fa-chart-pie" style="font-size:11px;"></i>
                <span>Còn chỉ tiêu: <strong x-text="stats.con_chi_tieu">–</strong></span>
            </div>
        </div>

        <!-- SESSION SELECTOR -->
        <div style="display:flex;align-items:center;gap:8px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:9px;padding:6px 12px;">
            <i class="fas fa-calendar-alt" style="color:#64748b;font-size:13px;"></i>
            <select id="session-selector" x-model="sessionId" @change="loadAllData()"
                style="border:none;background:transparent;outline:none;font-size:13px;font-weight:600;color:#374151;cursor:pointer;">
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= $s['id'] ?>">
                        <?= htmlspecialchars($s['ten_dot']) ?> (<?= $s['nam_tuyen_sinh'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- =========== BODY =========== -->
    <div class="ep-body">

        <!-- ===== LEFT PANEL ===== -->
        <div class="ep-left">

            <!-- SEARCH AREA -->
            <div class="ep-search-area">
                <div class="ep-search-input-wrap">
                    <i class="fas fa-search" style="color:#94a3b8;font-size:14px;flex-shrink:0;"></i>
                    <input type="text" id="search-input"
                        x-model="searchKeyword"
                        @keydown.enter="searchCandidates()"
                        placeholder="Tìm theo tên hoặc số CCCD/CMND..."
                        autocomplete="off">
                    <button class="ep-search-btn" @click="searchCandidates()" :disabled="isSearching">
                        <template x-if="isSearching">
                            <i class="fas fa-spinner fa-spin"></i>
                        </template>
                        <template x-if="!isSearching">
                            <i class="fas fa-search"></i>
                        </template>
                        <span x-text="isSearching ? 'Đang tìm...' : 'Tìm kiếm'"></span>
                    </button>
                </div>

                <!-- SEARCH RESULTS -->
                <div class="ep-search-results" x-show="searchResults.length > 0" x-cloak>
                    <template x-for="c in searchResults" :key="c.ket_qua_id">
                        <div class="ep-result-item"
                            :class="selectedCandidate && selectedCandidate.ket_qua_id === c.ket_qua_id ? 'active' : ''"
                            @click="selectCandidate(c)">
                            <div>
                                <div class="ep-result-name" x-text="c.ho_ten"></div>
                                <div class="ep-result-sub">
                                    <i class="far fa-id-card"></i>&nbsp;<span x-text="c.so_cccd"></span>
                                    &nbsp;·&nbsp;
                                    <i class="fas fa-graduation-cap"></i>&nbsp;<span x-text="c.ten_nganh"></span>
                                </div>
                            </div>
                            <span class="ep-status"
                                :class="{
                                    'enrolled': c.trang_thai_nhap_hoc === 'da_nhap_hoc',
                                    'pending':  c.trang_thai_nhap_hoc === 'cho_xet_duyet',
                                    'cancelled':c.trang_thai_nhap_hoc === 'da_huy'
                                }"
                                x-text="c.trang_thai_nhap_hoc === 'da_nhap_hoc' ? '✓ Đã nhập' : (c.trang_thai_nhap_hoc === 'cho_xet_duyet' ? '⏳ Chờ' : (c.trang_thai_nhap_hoc === 'da_huy' ? '✕ Hủy' : 'Mới'))">
                            </span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- CARD AREA -->
            <div class="ep-card-area">

                <!-- EMPTY STATE -->
                <template x-if="!selectedCandidate">
                    <div class="ep-empty-state">
                        <div class="ep-empty-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="ep-empty-title">Chưa chọn thí sinh</div>
                        <div class="ep-empty-desc">Tìm kiếm theo họ tên hoặc số CCCD để bắt đầu xử lý nhập học</div>
                        <div style="margin-top:4px;display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
                            <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#64748b;">
                                <div style="width:20px;height:20px;background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#2563eb;">1</div>
                                Tìm thí sinh
                            </div>
                            <div style="color:#cbd5e1;font-size:12px;">→</div>
                            <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#64748b;">
                                <div style="width:20px;height:20px;background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#2563eb;">2</div>
                                Kiểm tra hồ sơ
                            </div>
                            <div style="color:#cbd5e1;font-size:12px;">→</div>
                            <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#64748b;">
                                <div style="width:20px;height:20px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#15803d;">3</div>
                                Xác nhận nhập học
                            </div>
                        </div>
                    </div>
                </template>

                <!-- CANDIDATE CARD -->
                <template x-if="selectedCandidate">
                    <div>
                        <!-- PROFILE HEADER -->
                        <div class="ep-candidate-card">
                            <div class="ep-profile-header">
                                <div class="ep-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div class="ep-profile-name" x-text="selectedCandidate.ho_ten"></div>
                                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:6px;">
                                        <div class="ep-profile-code">
                                            <i class="fas fa-hashtag" style="font-size:9px;"></i>
                                            <span x-text="selectedCandidate.ma_phieu || 'Chưa cấp mã'"></span>
                                        </div>
                                        <span class="ep-profile-badge"
                                            :class="{
                                                'badge-enrolled': selectedCandidate.trang_thai_nhap_hoc === 'da_nhap_hoc',
                                                'badge-pending':  selectedCandidate.trang_thai_nhap_hoc === 'cho_xet_duyet',
                                                'badge-cancelled':selectedCandidate.trang_thai_nhap_hoc === 'da_huy',
                                                'badge-new':      !selectedCandidate.trang_thai_nhap_hoc
                                            }"
                                            x-text="selectedCandidate.trang_thai_nhap_hoc === 'da_nhap_hoc' ? '✓ Đã nhập học' : (selectedCandidate.trang_thai_nhap_hoc === 'cho_xet_duyet' ? '⏳ Chờ xét duyệt' : (selectedCandidate.trang_thai_nhap_hoc === 'da_huy' ? '✕ Đã hủy' : '● Chưa nhập học'))">
                                        </span>
                                    </div>
                                    <div style="display:flex;gap:14px;margin-top:8px;flex-wrap:wrap;">
                                        <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:rgba(255,255,255,0.8);">
                                            <i class="far fa-id-card"></i>
                                            <span x-text="selectedCandidate.so_cccd"></span>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:rgba(255,255,255,0.8);">
                                            <i class="fas fa-phone-alt"></i>
                                            <span x-text="selectedCandidate.dien_thoai || 'N/A'"></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- SCORE BADGE -->
                                <div style="text-align:right;flex-shrink:0;">
                                    <div class="ep-score-badge">
                                        <span class="score-num" x-text="selectedCandidate.diem_xet_tuyen"></span>
                                        <span class="score-label">điểm</span>
                                    </div>
                                    <div style="font-size:10px;color:rgba(255,255,255,0.7);margin-top:4px;" x-text="selectedCandidate.to_hop"></div>
                                </div>
                            </div>

                            <!-- TABS -->
                            <div class="ep-tabs">
                                <button class="ep-tab" :class="activeTab === 'admission' ? 'active' : ''" @click="activeTab = 'admission'" id="tab-admission">
                                    <i class="fas fa-folder-open"></i> Hồ sơ & Nhập học
                                </button>
                                <button class="ep-tab" :class="activeTab === 'info' ? 'active' : ''" @click="activeTab = 'info'" id="tab-info">
                                    <i class="fas fa-user-circle"></i> Thông tin thí sinh
                                </button>
                                <button class="ep-tab" :class="activeTab === 'payment' ? 'active' : ''" @click="activeTab = 'payment'" id="tab-payment">
                                    <i class="fas fa-qrcode"></i> Học phí
                                </button>
                            </div>

                            <!-- TAB: HỒ SƠ & NHẬP HỌC -->
                            <div class="ep-tab-pane" :class="activeTab === 'admission' ? 'active' : ''">
                                <!-- Admission Info Quick View -->
                                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 14px;margin-bottom:14px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
                                    <div>
                                        <div style="font-size:10px;font-weight:700;color:#166534;text-transform:uppercase;">Ngành trúng tuyển</div>
                                        <div style="font-size:12px;font-weight:700;color:#15803d;margin-top:2px;" x-text="selectedCandidate.ten_nganh"></div>
                                    </div>
                                    <div>
                                        <div style="font-size:10px;font-weight:700;color:#166534;text-transform:uppercase;">Phương thức</div>
                                        <div style="font-size:12px;font-weight:700;color:#15803d;margin-top:2px;" x-text="selectedCandidate.phuong_thuc || 'N/A'"></div>
                                    </div>
                                    <div>
                                        <div style="font-size:10px;font-weight:700;color:#166534;text-transform:uppercase;">Khu vực / Đối tượng</div>
                                        <div style="font-size:12px;font-weight:700;color:#15803d;margin-top:2px;" x-text="(selectedCandidate.khu_vuc_kq || 'N/A') + ' / ' + (selectedCandidate.doi_tuong_kq || 'Thí sinh tự do')"></div>
                                    </div>
                                </div>

                                <div class="ep-section-title">
                                    <i class="fas fa-folder-check"></i> Danh sách hồ sơ cần nộp
                                </div>

                                <template x-if="!selectedCandidate.documents || selectedCandidate.documents.length === 0">
                                    <div style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;">
                                        <i class="fas fa-inbox" style="font-size:24px;display:block;margin-bottom:8px;color:#e2e8f0;"></i>
                                        Chưa cấu hình danh sách hồ sơ yêu cầu cho đợt này.
                                    </div>
                                </template>

                                <template x-for="doc in selectedCandidate.documents" :key="doc.id">
                                    <div class="ep-doc-item">
                                        <button type="button" class="ep-doc-toggle"
                                            :class="doc.selected_value && doc.selected_value !== '' && doc.selected_value !== 'Chưa nộp' && doc.selected_value !== 'Không có' ? 'checked' : ''"
                                            @click="toggleDoc(doc)">
                                        </button>
                                        <div class="ep-doc-label" x-text="doc.ten_ho_so + (doc.bat_buoc === 'true' ? ' *' : '')"></div>
                                        <template x-if="doc.cac_gia_tri && doc.cac_gia_tri.split(',').length > 1">
                                            <select class="ep-doc-select" x-model="doc.selected_value">
                                                <template x-for="val in doc.cac_gia_tri.split(',')" :key="val">
                                                    <option :value="val.trim()" x-text="val.trim()"></option>
                                                </template>
                                            </select>
                                        </template>
                                    </div>
                                </template>

                                <!-- Extra fields -->
                                <div style="margin-top:14px;">
                                    <div class="ep-section-title"><i class="fas fa-edit"></i> Thông tin bổ sung</div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                        <div class="ep-info-item">
                                            <label>Ngày cấp CCCD</label>
                                            <input type="date" x-model="selectedCandidate.extra_info.ngay_cap_cccd">
                                        </div>
                                        <div class="ep-info-item">
                                            <label>Nơi cấp CCCD</label>
                                            <input type="text" x-model="selectedCandidate.extra_info.noi_cap_cccd" placeholder="VD: Cục CSQLHC về TTXH">
                                        </div>
                                        <div class="ep-info-item">
                                            <label>Lớp (học kỳ đầu)</label>
                                            <input type="text" x-model="selectedCandidate.extra_info.lop" placeholder="VD: K20A1">
                                        </div>
                                        <div class="ep-info-item">
                                            <label>Địa chỉ liên hệ</label>
                                            <input type="text" x-model="selectedCandidate.extra_info.dia_chi_lien_he" placeholder="Địa chỉ hiện tại">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: THÔNG TIN THÍ SINH -->
                            <div class="ep-tab-pane" :class="activeTab === 'info' ? 'active' : ''">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                    <!-- CỘT TRÁI -->
                                    <div>
                                        <div class="ep-section-title"><i class="fas fa-user"></i> Thông tin cá nhân</div>
                                        <div class="ep-info-grid">
                                            <div class="ep-info-item" style="grid-column:1/-1;">
                                                <label>Họ và tên</label>
                                                <span x-text="selectedCandidate.ho_ten"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Ngày sinh</label>
                                                <span x-text="selectedCandidate.ngay_sinh"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Giới tính</label>
                                                <span x-text="selectedCandidate.gioi_tinh == '1' ? '👨 Nam' : '👩 Nữ'"></span>
                                            </div>
                                            <div class="ep-info-item" style="grid-column:1/-1;">
                                                <label>Số CCCD/CMND</label>
                                                <span style="font-family:monospace;" x-text="selectedCandidate.so_cccd"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Điện thoại</label>
                                                <span x-text="selectedCandidate.dien_thoai || 'N/A'"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Email</label>
                                                <span x-text="selectedCandidate.email || 'N/A'" style="word-break:break-all;"></span>
                                            </div>
                                            <div class="ep-info-item" style="grid-column:1/-1;">
                                                <label>Địa chỉ thường trú</label>
                                                <span x-text="selectedCandidate.dia_chi_chi_tiet || 'Chưa cập nhật'"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- CỘT PHẢI -->
                                    <div>
                                        <div class="ep-section-title"><i class="fas fa-graduation-cap"></i> Thông tin tuyển sinh</div>
                                        <div class="ep-info-grid">
                                            <div class="ep-info-item" style="grid-column:1/-1;">
                                                <label>Ngành trúng tuyển</label>
                                                <span style="color:#1d4ed8;font-weight:700;" x-text="selectedCandidate.ten_nganh"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Mã ngành</label>
                                                <span x-text="selectedCandidate.ma_nganh"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Số báo danh</label>
                                                <span x-text="selectedCandidate.so_bao_danh || 'N/A'"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Phương thức xét</label>
                                                <span x-text="selectedCandidate.phuong_thuc || 'N/A'"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Tổ hợp môn</label>
                                                <span style="font-weight:700;" x-text="selectedCandidate.to_hop || 'N/A'"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Điểm xét tuyển</label>
                                                <span style="font-size:16px;font-weight:800;color:#d97706;" x-text="selectedCandidate.diem_xet_tuyen"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Khu vực ưu tiên</label>
                                                <span x-text="selectedCandidate.khu_vuc_kq || 'N/A'"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Đối tượng ưu tiên</label>
                                                <span x-text="selectedCandidate.doi_tuong_kq || 'Thí sinh tự do'"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Năm tốt nghiệp</label>
                                                <span x-text="selectedCandidate.nam_tot_nghiep || 'N/A'"></span>
                                            </div>
                                            <div class="ep-info-item">
                                                <label>Trường THPT</label>
                                                <span x-text="selectedCandidate.extra_info.truong_thpt || 'N/A'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: HỌC PHÍ -->
                            <div class="ep-tab-pane" :class="activeTab === 'payment' ? 'active' : ''">
                                <div style="display:flex;gap:16px;flex-wrap:wrap;">
                                    <div class="ep-qr-card" style="flex:1;min-width:220px;">
                                        <div style="font-size:12px;font-weight:700;color:#166534;text-transform:uppercase;letter-spacing:0.5px;">Quét mã để thanh toán</div>
                                        <img class="ep-qr-img"
                                            :src="`https://img.vietqr.io/image/<?= $qrConfig['bank_id'] ?>-<?= $qrConfig['account_no'] ?>-compact.png?amount=<?= $qrConfig['amount'] ?>&addInfo=<?= urlencode($qrConfig['description_prefix']) ?>${selectedCandidate.so_cccd}`"
                                            alt="QR Code thanh toán">
                                        <div class="ep-qr-amount"><?= number_format($qrConfig['amount'], 0, ',', '.') ?> VNĐ</div>
                                        <div style="font-size:12px;font-weight:600;color:#374151;"><?= $qrConfig['bank_id'] ?> – <?= $qrConfig['account_no'] ?></div>
                                        <div style="font-size:11px;color:#64748b;background:#fff;border:1px solid #bbf7d0;border-radius:8px;padding:6px 12px;max-width:280px;word-break:break-all;">
                                            Nội dung: <?= $qrConfig['description_prefix'] ?><span x-text="selectedCandidate.so_cccd"></span> <span x-text="selectedCandidate.ho_ten ? selectedCandidate.ho_ten.replace(/\s+/g,'') : ''"></span>
                                        </div>
                                    </div>
                                    <div style="flex:1;min-width:200px;">
                                        <div class="ep-section-title"><i class="fas fa-info-circle"></i> Thông tin chuyển khoản</div>
                                        <div style="display:flex;flex-direction:column;gap:10px;">
                                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">
                                                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:3px;">Ngân hàng</div>
                                                <div style="font-size:14px;font-weight:700;color:#1e293b;"><?= $qrConfig['bank_id'] ?></div>
                                            </div>
                                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">
                                                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:3px;">Số tài khoản</div>
                                                <div style="font-size:14px;font-weight:700;color:#1e293b;font-family:monospace;"><?= $qrConfig['account_no'] ?></div>
                                            </div>
                                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">
                                                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:3px;">Chủ tài khoản</div>
                                                <div style="font-size:13px;font-weight:700;color:#1e293b;"><?= $qrConfig['account_name'] ?></div>
                                            </div>
                                            <div style="background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:12px;">
                                                <div style="font-size:10px;font-weight:700;color:#92400e;text-transform:uppercase;margin-bottom:3px;">Số tiền cần nộp</div>
                                                <div style="font-size:18px;font-weight:800;color:#d97706;"><?= number_format($qrConfig['amount'], 0, ',', '.') ?> VNĐ</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div><!-- end candidate-card -->
                    </div>
                </template>
            </div>

            <!-- ACTION BAR -->
            <div class="ep-action-bar" x-show="selectedCandidate">
                <button class="ep-btn ep-btn-ghost" @click="resetForm()" id="btn-reset">
                    <i class="fas fa-undo"></i> Làm mới
                </button>
                <div style="display:flex;gap:7px;flex-wrap:wrap;">
                    <button class="ep-btn ep-btn-save" @click="submitEnrollment('luu_tam')" :disabled="isSaving" id="btn-save-draft">
                        <i class="far fa-save"></i>
                        <span x-text="isSaving ? 'Đang lưu...' : 'Lưu tạm'"></span>
                    </button>
                    <button class="ep-btn ep-btn-enroll" @click="submitEnrollment('nhap_hoc')" :disabled="isSaving" id="btn-enroll">
                        <i class="fas fa-check-circle"></i>
                        <span x-text="isSaving ? 'Đang lưu...' : 'Xác nhận nhập học'"></span>
                    </button>
                    <button class="ep-btn ep-btn-cancel" @click="submitEnrollment('huy')" :disabled="isSaving" id="btn-cancel-enroll">
                        <i class="fas fa-ban"></i> Hủy nhập học
                    </button>
                    <template x-if="selectedCandidate && selectedCandidate.nhap_hoc_id">
                        <button class="ep-btn ep-btn-print" @click="printReceipt()" id="btn-print">
                            <i class="fas fa-print"></i> In phiếu
                        </button>
                    </template>
                    <template x-if="selectedCandidate && selectedCandidate.nhap_hoc_id">
                        <button class="ep-btn" style="background:#7c3aed;color:#fff;border-color:#7c3aed;" @click="openPrintWordModal()" id="btn-print-word">
                            <i class="fas fa-file-word"></i> In Word
                        </button>
                    </template>
                </div>
            </div>

        </div><!-- end ep-left -->

        <!-- ===== RIGHT PANEL ===== -->
        <div class="ep-right">
            <div class="ep-right-header">
                <div>
                    <div class="ep-right-title"><i class="fas fa-list-check" style="color:#2563eb;margin-right:6px;"></i>Danh sách đã nhập học</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                        Tổng <strong x-text="totalEnrolled">0</strong> thí sinh &nbsp;·&nbsp; Trang <span x-text="currentPage"></span>/<span x-text="lastPage"></span>
                    </div>
                </div>
                <button @click="loadEnrolledList()" title="Làm mới danh sách" id="btn-refresh-list"
                    style="width:32px;height:32px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;transition:all 0.15s;"
                    onmouseover="this.style.color='#2563eb';this.style.borderColor='#93c5fd';"
                    onmouseout="this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
                    <i class="fas fa-sync-alt" :class="isLoadingList ? 'fa-spin' : ''" style="font-size:12px;"></i>
                </button>
            </div>

            <!-- TABLE -->
            <div class="ep-table-wrap">
                <table class="ep-table">
                    <thead>
                        <tr>
                            <th style="width:36px;text-align:center;">#</th>
                            <th>Thí sinh</th>
                            <th>Ngành</th>
                            <th>Hồ sơ nộp</th>
                            <th style="text-align:right;">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in enrolledList" :key="row.ket_qua_id || idx">
                            <tr @click="quickSelect(row)">
                                <td style="text-align:center;color:#94a3b8;font-size:11px;" x-text="(currentPage - 1) * 20 + idx + 1"></td>
                                <td>
                                    <div class="ep-name-cell" :title="row.ho_ten" x-text="row.ho_ten"></div>
                                    <div class="ep-cccd-cell" x-text="row.so_cccd"></div>
                                </td>
                                <td>
                                    <div class="ep-nganh-cell" :title="row.ten_nganh" x-text="row.ten_nganh || 'N/A'"></div>
                                </td>
                                <td>
                                    <div style="display:flex;flex-wrap:wrap;gap:2px;max-width:130px;">
                                        <template x-if="!row.documents || row.documents.length === 0">
                                            <span style="font-size:10px;color:#cbd5e1;">–</span>
                                        </template>
                                        <template x-for="doc in row.documents" :key="doc.id">
                                            <span class="ep-doc-dot" :class="doc.submitted ? 'submitted' : 'missing'"
                                                :title="doc.ten_ho_so + (doc.submitted ? ' ✓' : ' (chưa nộp)')">
                                                <i class="fas" :class="doc.submitted ? 'fa-check' : 'fa-times'"></i>
                                            </span>
                                        </template>
                                    </div>
                                </td>
                                <td style="text-align:right;">
                                    <span class="ep-status"
                                        :class="{
                                            'enrolled':  row.trang_thai === 'da_nhap_hoc',
                                            'pending':   row.trang_thai === 'cho_xet_duyet',
                                            'cancelled': row.trang_thai === 'da_huy'
                                        }"
                                        x-text="row.trang_thai === 'da_nhap_hoc' ? '✓ Đã nhập' : (row.trang_thai === 'cho_xet_duyet' ? '⏳ Chờ' : '✕ Hủy')">
                                    </span>
                                    <div style="font-size:10px;color:#94a3b8;margin-top:3px;" x-text="row.ngay_nhap_hoc_format"></div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="enrolledList.length === 0 && !isLoadingList">
                            <td colspan="5" style="text-align:center;padding:32px;color:#94a3b8;">
                                <i class="fas fa-inbox" style="font-size:28px;color:#e2e8f0;display:block;margin-bottom:8px;"></i>
                                Chưa có thí sinh nhập học trong đợt này
                            </td>
                        </tr>
                        <tr x-show="isLoadingList">
                            <td colspan="5" style="padding:12px;">
                                <div class="ep-shimmer" style="margin-bottom:8px;"></div>
                                <div class="ep-shimmer" style="margin-bottom:8px;width:80%;"></div>
                                <div class="ep-shimmer" style="width:60%;"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="ep-pagination">
                <span>Tổng <strong x-text="totalEnrolled"></strong> bản ghi</span>
                <div style="display:flex;gap:5px;align-items:center;">
                    <button class="ep-page-btn" id="btn-prev-page"
                        @click="currentPage > 1 && (currentPage--, loadEnrolledList())"
                        :disabled="currentPage <= 1">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span style="font-size:12px;color:#374151;font-weight:600;padding:0 4px;">
                        <span x-text="currentPage"></span> / <span x-text="lastPage"></span>
                    </span>
                    <button class="ep-page-btn" id="btn-next-page"
                        @click="currentPage < lastPage && (currentPage++, loadEnrolledList())"
                        :disabled="currentPage >= lastPage">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

        </div><!-- end ep-right -->

    </div><!-- end ep-body -->

</div><!-- end ep-wrapper -->

<!-- TOAST CONTAINER -->
<div id="ep-toast-container" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px;"></div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('enrollmentProcess', () => ({
        sessionId: '<?= $currentSessionId ?>',
        searchKeyword: '',
        isSearching: false,
        searchResults: [],
        selectedCandidate: null,
        activeTab: 'admission',
        isSaving: false,

        stats: { tong_thi_sinh: 0, da_nhap_hoc: 0, cho_xet_duyet: 0, da_huy: 0, con_chi_tieu: 0 },
        enrolledList: [],
        isLoadingList: false,
        currentPage: 1,
        lastPage: 1,
        totalEnrolled: 0,

        init() {
            this.loadAllData();
        },

        loadAllData() {
            this.loadStats();
            this.loadEnrolledList();
        },

        loadStats() {
            fetch('<?= url("/admin/enrollment/api/stats") ?>?session_id=' + this.sessionId)
                .then(r => r.json())
                .then(data => { if (data.success) this.stats = data.data; })
                .catch(() => {});
        },

        loadEnrolledList() {
            this.isLoadingList = true;
            fetch('<?= url("/admin/enrollment/api/list") ?>?session_id=' + this.sessionId + '&page=' + this.currentPage)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.enrolledList = data.data;
                        this.totalEnrolled = data.total;
                        this.lastPage = data.last_page || 1;
                    }
                })
                .catch(() => {})
                .finally(() => { this.isLoadingList = false; });
        },

        searchCandidates() {
            if (!this.searchKeyword.trim()) return;
            this.isSearching = true;
            this.searchResults = [];
            fetch(`<?= url("/admin/enrollment/search") ?>?session_id=${this.sessionId}&keyword=${encodeURIComponent(this.searchKeyword)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.searchResults = data.data;
                        if (this.searchResults.length === 0) {
                            this.showToast('Không tìm thấy thí sinh nào phù hợp.', 'info');
                        }
                    }
                })
                .catch(() => this.showToast('Lỗi kết nối khi tìm kiếm.', 'error'))
                .finally(() => { this.isSearching = false; });
        },

        selectCandidate(c) {
            this.selectedCandidate = JSON.parse(JSON.stringify(c));
            this.searchResults = [];
            this.activeTab = 'admission';
        },

        quickSelect(row) {
            this.searchKeyword = row.so_cccd;
            this.searchCandidates();
        },

        toggleDoc(doc) {
            const isChecked = doc.selected_value && doc.selected_value !== '' && doc.selected_value !== 'Chưa nộp' && doc.selected_value !== 'Không có';
            if (isChecked) {
                doc.selected_value = 'Chưa nộp';
            } else {
                // Pick first value that's not 'Chưa nộp'
                const vals = doc.cac_gia_tri ? doc.cac_gia_tri.split(',').map(v => v.trim()) : [];
                const first = vals.find(v => v !== 'Chưa nộp') || vals[0] || 'Đã nộp';
                doc.selected_value = first;
            }
        },

        resetForm() {
            this.selectedCandidate = null;
            this.searchKeyword = '';
            this.searchResults = [];
            this.activeTab = 'admission';
        },

        submitEnrollment(action) {
            if (!this.selectedCandidate) return;
            this.isSaving = true;

            const payload = new URLSearchParams();
            payload.append('csrf_token', '<?= \App\Middleware\SecurityMiddleware::generateCsrfToken() ?>');
            payload.append('session_id', this.sessionId);
            payload.append('ket_qua_id', this.selectedCandidate.ket_qua_id);
            payload.append('so_cccd', this.selectedCandidate.so_cccd);
            payload.append('action', action);

            if (this.selectedCandidate.extra_info) {
                for (let key in this.selectedCandidate.extra_info) {
                    payload.append('extra[' + key + ']', this.selectedCandidate.extra_info[key] || '');
                }
            }

            if (this.selectedCandidate.documents) {
                this.selectedCandidate.documents.forEach(doc => {
                    payload.append(`documents[${doc.id}][gia_tri]`, doc.selected_value || '');
                    payload.append(`documents[${doc.id}][ghi_chu]`, doc.ghi_chu_val || '');
                });
            }

            fetch('<?= url("/admin/enrollment/submit") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: payload.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.showToast(data.message || 'Cập nhật thành công!', 'success');
                    this.selectedCandidate.nhap_hoc_id = data.nhap_hoc_id;
                    this.selectedCandidate.ma_phieu = data.ma_phieu || this.selectedCandidate.ma_phieu;
                    if (action === 'nhap_hoc')  this.selectedCandidate.trang_thai_nhap_hoc = 'da_nhap_hoc';
                    if (action === 'luu_tam')   this.selectedCandidate.trang_thai_nhap_hoc = 'cho_xet_duyet';
                    if (action === 'huy')       this.selectedCandidate.trang_thai_nhap_hoc = 'da_huy';
                    this.loadStats();
                    this.loadEnrolledList();
                } else {
                    this.showToast(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(() => this.showToast('Lỗi kết nối máy chủ.', 'error'))
            .finally(() => { this.isSaving = false; });
        },

        printReceipt() {
            if (!this.selectedCandidate || !this.selectedCandidate.nhap_hoc_id) return;
            window.open(`<?= url("/admin/enrollment/print") ?>?id=${this.selectedCandidate.nhap_hoc_id}`, '_blank');
        },

        // ── In Word ──────────────────────────────────────────────
        phieuTemplates: [],
        selectedTemplateId: '',
        isLoadingTemplates: false,
        showPrintWordModal: false,

        async openPrintWordModal() {
            if (!this.selectedCandidate?.nhap_hoc_id) return;
            this.showPrintWordModal = true;
            this.isLoadingTemplates = true;
            try {
                const res = await fetch('<?= url("/admin/phieu/list") ?>?loai=phieu_nhap_hoc');
                const data = await res.json();
                this.phieuTemplates = data.success ? data.data : [];
                if (this.phieuTemplates.length > 0) this.selectedTemplateId = this.phieuTemplates[0].id;
            } catch { this.phieuTemplates = []; }
            finally { this.isLoadingTemplates = false; }
        },

        closePrintWordModal() { this.showPrintWordModal = false; },

        downloadPhieuWord() {
            if (!this.selectedTemplateId) { this.showToast('Vui lòng chọn mẫu phiếu', 'error'); return; }
            const url = `<?= url("/admin/phieu/download") ?>?type=nhap_hoc&ids=${this.selectedCandidate.nhap_hoc_id}&template_id=${this.selectedTemplateId}`;
            window.open(url, '_blank');
            this.closePrintWordModal();
        },

        showToast(message, type = 'info') {
            const container = document.getElementById('ep-toast-container');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = 'ep-toast ' + type;
            const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
            toast.innerHTML = `<i class="fas ${icons[type] || 'fa-info-circle'}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.transition = 'all 0.3s';
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100px)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }));
});
</script>

<!-- ── Modal Chọn Mẫu In Word ────────────────────────────────── -->
<div x-data="enrollmentProcess()" x-cloak>
  <template x-if="showPrintWordModal">
    <div class="fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-4" @click.self="closePrintWordModal()">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <h3 class="font-bold text-gray-800 text-lg mb-1">🖨️ In Phiếu Nhập Học (Word)</h3>
        <p class="text-sm text-gray-500 mb-4">Chọn mẫu phiếu để xuất file .docx</p>
        <template x-if="isLoadingTemplates">
          <div class="text-center py-6 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Đang tải danh sách mẫu...</div>
        </template>
        <template x-if="!isLoadingTemplates && phieuTemplates.length === 0">
          <div class="text-center py-6">
            <p class="text-gray-500 text-sm mb-3">Chưa có mẫu nào. Hãy upload mẫu trước.</p>
            <a href="<?= url('/admin/phieu/templates') ?>" target="_blank" class="text-blue-600 underline text-sm">Quản lý mẫu phiếu →</a>
          </div>
        </template>
        <template x-if="!isLoadingTemplates && phieuTemplates.length > 0">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Chọn mẫu phiếu</label>
            <select x-model="selectedTemplateId" class="w-full border rounded-lg px-3 py-2 text-sm mb-4">
              <template x-for="t in phieuTemplates" :key="t.id">
                <option :value="t.id" x-text="t.ten_mau"></option>
              </template>
            </select>
            <div class="flex gap-2">
              <button @click="closePrintWordModal()" class="flex-1 px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Hủy</button>
              <button @click="downloadPhieuWord()" class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-bold">
                <i class="fas fa-download mr-1"></i> Tải xuống
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </template>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>
