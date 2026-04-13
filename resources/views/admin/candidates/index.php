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
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    const bulkActionSelect = document.getElementById('bulk-action-select');
    const bulkStatusOpt = document.getElementById('bulk-status-opt');
    const bulkForm = document.getElementById('bulk-form');

    function updateBulkUI() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        selectedCount.innerText = checked.length;
        if (checked.length > 0) {
            bulkActions.classList.remove('hidden');
        } else {
            bulkActions.classList.add('hidden');
        }
    }

    function toggleBulkOptions() {
        if (!bulkStatusOpt) return;
        bulkStatusOpt.classList.add('hidden');
        const action = bulkActionSelect.value;
        
        if (action === 'update_status') {
            bulkStatusOpt.classList.remove('hidden');
        } else if (action === 'transfer') {
            openTransferModal();
        } else if (action === 'send_email') {
            openEmailModal();
        } else if (action === 'change_password') {
            openBulkPasswordModal();
        }
    }

    function handleBulkSubmit() {
        const action = bulkActionSelect.value;
        const checked = document.querySelectorAll('.item-checkbox:checked');

        if (!action) {
            if (typeof Toast !== 'undefined') Toast.warning('Vui lòng chọn một hành động');
            else alert('Vui lòng chọn một hành động');
            return;
        }

        if (checked.length === 0) {
            if (typeof Toast !== 'undefined') Toast.warning('Vui lòng chọn ít nhất 1 hồ sơ');
            else alert('Vui lòng chọn ít nhất 1 hồ sơ');
            return;
        }

        // Action is already validated, if it was status update, we submit
        if (action === 'update_status' || action === 'delete' || action === 'normalize_names') {
            if (confirm('Xác nhận thực hiện hành động này cho ' + checked.length + ' hồ sơ đã chọn?')) {
                Loading.show();
                bulkForm.submit();
            }
        } else {
            // These actions usually open modals via toggleBulkOptions, 
            // but if user clicks Apply without changing (already selected), we open them here.
            if (action === 'send_email') openEmailModal();
            else if (action === 'change_password') openBulkPasswordModal();
            else if (action === 'transfer') openTransferModal();
        }
    }

    function sendSingleEmail(cccd) {
        // Uncheck all
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
        // Check this one
        const cb = document.querySelector(`.item-checkbox[value="${cccd}"]`);
        if (cb) {
            cb.checked = true;
            updateBulkUI();
            openEmailModal();
        }
    }

    function deleteSingle(cccd) {
        if (!confirm('Bạn có chắc chắn muốn xóa thí sinh này?')) return;
        
        // Uncheck all
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
        // Check this one
        const cb = document.querySelector(`.item-checkbox[value="${cccd}"]`);
        if (cb) {
            cb.checked = true;
            updateBulkUI();
            
            // Set action as a separate hidden field to be sure
            let fa = document.getElementById('forced-action');
            if (!fa) {
                fa = document.createElement('input');
                fa.type = 'hidden';
                fa.id = 'forced-action';
                fa.name = 'forced_action';
                bulkForm.appendChild(fa);
            }
            fa.value = 'delete';
            
            Loading.show();
            bulkForm.submit();
        }
    }

    function openPasswordModal(cccd, name) {
        console.log('Opening Password Modal for:', name, cccd);
        const modal = document.getElementById('password-modal');
        const form = document.getElementById('password-modal-form');
        const inputCccd = document.getElementById('pwd-modal-cccd');
        const spanName = document.getElementById('pwd-modal-name');
        const title = document.getElementById('pwd-modal-title');
        const desc = document.getElementById('pwd-modal-desc');
        const pwdInput = form.querySelector('[name="new_password"]');

        if (!modal || !form || !spanName) return;

        // Reset previous dynamic fields if any
        form.querySelectorAll('.dynamic-bulk-field').forEach(el => el.remove());
        if (pwdInput) pwdInput.value = '';

        if (cccd === 'BULK') {
            title.innerText = 'Đổi mật khẩu hàng loạt';
            desc.innerText = 'Thiết lập mật khẩu mới cho:';
            spanName.innerText = name;
            form.action = '<?= url('/admin/candidates/bulk-action') ?>';
            if (inputCccd) inputCccd.disabled = true;

            // Add action hidden field
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'change_password';
            actionInput.className = 'dynamic-bulk-field';
            form.appendChild(actionInput);

            // Add selected IDs
            const checked = document.querySelectorAll('.item-checkbox:checked');
            checked.forEach(cb => {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'ids[]';
                idInput.value = cb.value;
                idInput.className = 'dynamic-bulk-field';
                form.appendChild(idInput);
            });
        } else {
            title.innerText = 'Đổi mật khẩu';
            desc.innerText = 'Thiết lập mật khẩu mới cho:';
            spanName.innerText = name;
            form.action = '<?= url('/admin/candidates/change-password') ?>';
            if (inputCccd) {
                inputCccd.disabled = false;
                inputCccd.value = cccd;
            }
        }

        modal.classList.remove('hidden');
    }
    
    // Checkbox Listeners
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked);
            updateBulkUI();
        });
    }

    // Use event delegation for checkboxes to handle dynamic table updates if any
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-checkbox')) {
            updateBulkUI();
        }
    });

    // Modal Functions
    function openBulkPasswordModal() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        const count = checked.length;
        if (count === 0) return;

        openPasswordModal('BULK', `${count} thí sinh đã chọn`);
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        bulkActionSelect.value = ''; // Reset select
    }

    function openTransferModal() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        const count = checked.length;
        document.getElementById('transfer-count').innerText = count;

        const currentSessionIds = new Set();
        checked.forEach(cb => {
            const sid = cb.getAttribute('data-session-id');
            if(sid) currentSessionIds.add(sid);
        });

        const select = document.getElementById('modal-target-session');
        if (!select) return;
        
        const options = select.options;
        const shouldFilter = currentSessionIds.size === 1;
        const filterId = shouldFilter ? currentSessionIds.values().next().value : null;

        for (let i = 0; i < options.length; i++) {
            const opt = options[i];
            opt.style.display = '';
            opt.disabled = false;

            if (shouldFilter && opt.value && opt.value == filterId) {
                opt.style.display = 'none';
                opt.disabled = true;
            } 
        }
        
        if (select.value && select.options[select.selectedIndex].disabled) {
             select.value = "";
        }

        document.getElementById('transfer-modal').classList.remove('hidden');
    }

    function confirmTransfer() {
        const targetSessionId = document.getElementById('modal-target-session').value;
        if (!targetSessionId) return;

        let input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'target_session_id';
        input.value = targetSessionId;
        bulkForm.appendChild(input);

        bulkActionSelect.value = 'transfer';

        Loading.show();
        bulkForm.submit();
    }

    function openEmailModal() {
        const count = document.querySelectorAll('.item-checkbox:checked').length;
        document.getElementById('email-count').innerText = count;

        const noteArea = document.getElementById('modal-internal-note');
        if (noteArea) {
            const now = new Date();
            const today = now.getDate().toString().padStart(2, '0') + '/' + (now.getMonth() + 1).toString().padStart(2, '0') + '/' + now.getFullYear();
            console.log('Setting note value to:', today);
            
            // Set immediately
            noteArea.value = `Gửi mail ngày: ${today}`;
            
            // Set after a small delay to override any auto-clear
            setTimeout(() => {
                noteArea.value = `Gửi mail ngày: ${today}`;
                console.log('Confirmed note value after timeout:', noteArea.value);
            }, 100);
        } else {
            console.error('Element #modal-internal-note not found!');
        }

        document.getElementById('email-modal').classList.remove('hidden');
    }

    window.applyEmailTemplate = function(val) {
        var sel = document.getElementById('modal-email-template');
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !val) {
            document.getElementById('modal-email-subject').value = '';
            document.getElementById('modal-email-content').value = '';
            return;
        }
        var subject = opt.getAttribute('data-subject') || '';
        var body = opt.getAttribute('data-body') || '';
        document.getElementById('modal-email-subject').value = subject;
        document.getElementById('modal-email-content').value = body;
    };

    window.confirmSendEmail = function() {
        const subject = document.getElementById('modal-email-subject').value;
        const content = document.getElementById('modal-email-content').value;
        const templateId = document.getElementById('modal-email-template').value;

        if (!subject || !content) {
            showToast('Vui lòng nhập tiêu đề và nội dung', 'warning');
            return;
        }

        // Get selected candidate IDs
        const ids = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= url("/admin/candidates/bulk-action") ?>';

        const inputCsrf = document.createElement('input');
        inputCsrf.type = 'hidden';
        inputCsrf.name = '_csrf_token';
        inputCsrf.value = '<?= csrf_token() ?>';
        form.appendChild(inputCsrf);

        const inputIds = document.createElement('input');
        inputIds.type = 'hidden';
        inputIds.name = 'ids[]';
        ids.forEach(id => {
            const clone = inputIds.cloneNode();
            clone.value = id;
            form.appendChild(clone);
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
        inputNote.value = document.getElementById('modal-internal-note').value;
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
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php'; 
?>
