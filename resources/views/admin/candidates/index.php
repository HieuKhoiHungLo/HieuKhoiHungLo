<?php ob_start(); ?>

<?php if ($mode !== 'all'): ?>
    <?php include __DIR__ . '/../partials/_stats.php'; ?>
<?php endif; ?>

<!-- Main Content Area with AlpineJS context -->
<!-- Custom Table Styles -->
<style>
    .candidate-table-container {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        background: #fff;
    }
    .candidate-table {
        border-collapse: separate !important;
        border-spacing: 0;
        width: 100%;
        table-layout: fixed; /* Crucial for sticky alignment reliability */
    }
    .candidate-table th, .candidate-table td {
        padding: 0.4rem 0.5rem;
        border: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        border-right: 1px solid #e2e8f0 !important;
        vertical-align: middle;
        font-size: 13px;
        color: #334155;
        background-clip: padding-box;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .candidate-table th:first-child, .candidate-table td:first-child {
        border-left: 1px solid #e2e8f0 !important;
    }
    .candidate-table thead tr:first-child th {
        border-top: 1px solid #e2e8f0 !important;
    }

    /* Header Specifics */
    .candidate-table thead th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        z-index: 5;
    }
    .candidate-table thead tr:first-child th {
        top: 0;
        position: sticky;
        z-index: 30 !important;
    }

    /* Dynamic Sticky Columns Calculation */
    <?php
    $offset0 = 0;
    $width0 = 45; // Checkbox
    
    // Cột Thao tác hiện lại cho trang xét duyệt (review)
    $widthAction = ($mode === 'review') ? 130 : 0;
    $offsetAction = $offset0 + $width0;

    $offsetTrangThai = $offsetAction + $widthAction; // Trạng thái
    $widthTrangThai = 45;

    $offset3 = $offsetTrangThai + $widthTrangThai; // Họ tên
    $width3 = 240;
    ?>

    .sticky-col {
        position: sticky !important;
        background-color: #fff !important;
        z-index: 10;
    }
    thead th.sticky-col {
        z-index: 40 !important;
        background-color: #f8fafc !important;
    }

    .sticky-col-left-0 { left: <?= $offset0 ?>px; width: <?= $width0 ?>px; min-width: <?= $width0 ?>px; max-width: <?= $width0 ?>px; }
    .sticky-col-action { left: <?= $offsetAction ?>px; width: <?= $widthAction ?>px; min-width: <?= $widthAction ?>px; max-width: <?= $widthAction ?>px; }
    .sticky-col-trangthai { left: <?= $offsetTrangThai ?>px; width: <?= $widthTrangThai ?>px; min-width: <?= $widthTrangThai ?>px; max-width: <?= $widthTrangThai ?>px; }
    .sticky-col-left-3 { 
        left: <?= $offset3 ?>px; 
        width: <?= $width3 ?>px; 
        min-width: <?= $width3 ?>px; 
        max-width: <?= $width3 ?>px; 
        box-shadow: 2px 0 5px -2px rgba(0,0,0,0.1); 
        clip-path: inset(-20px -20px -20px 0px); /* Allow shadow on right, but hide on top/left/bottom */
    }

    /* Hover State */
    .candidate-table tbody tr:hover td {
        background-color: #f1f5f9 !important;
    }
    
    /* Sort icon styling */
    .sort-trigger {
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: inherit;
    }
    .sort-link {
        color: #94a3b8;
        transition: color 0.15s;
        font-size: 10px;
    }
    .sort-link.active {
        color: #0066FF;
    }
</style>

<div x-data="{ 
    showCols: (function() {
        let cols = JSON.parse(localStorage.getItem('admin_cols')) || { 
            cccd: true, phone: true, email: true, province: false, school: false, nv1: true,
            gender: false, dob: false, ethnicity: false, area: false, object: false, grad_year: false
        };
        // Enforce fixed columns
        cols.cccd = true;
        cols.ho_va_ten = true; // Added for name
        cols.dob = true;
        cols.phone = true;
        cols.nv1 = true;
        return cols;
    })(),
    fixedCols: ['cccd', 'ho_va_ten', 'dob', 'phone', 'nv1'],
    toggleCol(col) {
        if (this.fixedCols.includes(col)) return;
        this.showCols[col] = !this.showCols[col];
        localStorage.setItem('admin_cols', JSON.stringify(this.showCols));
    },
    colLabel(col) {
        const labels = { 
            cccd: 'Số CCCD',
            ho_va_ten: 'Họ tên',
            phone: 'Điện thoại',
            email: 'Email',
            province: 'Hộ khẩu', 
            school: 'Trường THPT', 
            nv1: 'NV1',
            gender: 'Giới tính',
            dob: 'Ngày sinh',
            ethnicity: 'Dân tộc',
            area: 'Khu vực ƯT',
            object: 'Đối tượng ƯT',
            grad_year: 'Năm tốt nghiệp'
        };
        return labels[col] || col;
    }
}">

    <?php include __DIR__ . '/../partials/_filters.php'; ?>

    <?php include __DIR__ . '/../partials/_candidates_table.php'; ?>
</div>



<script>
    // Configuration from PHP
    const baseUrl = '<?= $baseUrl ?>';
    const currentFilters = <?= json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    // Bulk Action Logic
    // Bulk action logic is handled by _candidates_table.php
    // Logic handled by _candidates_table.php

</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php'; 
?>
