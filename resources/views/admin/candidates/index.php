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
    }
    .candidate-table {
        border-spacing: 0;
        border-collapse: separate;
    }
    .candidate-table th, .candidate-table td {
        padding: 0.25rem 0.75rem !important; /* Compact padding */
    }
    /* Alternating row colors */
    .candidate-table tbody tr:nth-child(even) {
        background-color: #f8fafc;
    }
    .candidate-table tbody tr:nth-child(odd) {
        background-color: #ffffff;
    }
    /* Hover effect: Change font color and background */
    .candidate-table tbody tr:hover td {
        color: #1e3a8a !important; /* Darker, more prominent blue text */
        background-color: #e0f2fe !important; /* Distinct light blue background */
    }
    .candidate-table tbody tr:hover td a, 
    .candidate-table tbody tr:hover td span,
    .candidate-table tbody tr:hover td p {
        color: #1e3a8a !important;
    }
    
    /* Sticky Columns Support */
    .sticky-col {
        position: sticky;
        background-color: inherit;
        z-index: 10;
        border-right: 1px solid #f1f5f9 !important;
    }
    .sticky-col-left-0 { left: 0; }
    .sticky-col-left-1 { left: 40px; } /* Adjust based on checkbox width */
    .sticky-col-left-2 { left: 80px; } /* Adjust based on STT width */
    .sticky-col-left-3 { left: 180px; } /* Adjust based on Action width */
    
    thead th.sticky-col {
        z-index: 20;
        background-color: #f8fafc;
    }
    
    /* Sort icon styling */
    .sort-link {
        color: #94a3b8;
        transition: color 0.15s;
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

<?php include __DIR__ . '/../partials/_modals.php'; ?>

<script>
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
            
            // Set action and submit
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            bulkForm.appendChild(actionInput);
            
            Loading.show();
            bulkForm.submit();
        }
    }
    
    // Checkbox Listeners
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked);
            updateBulkUI();
        });
    }

    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkUI);
    });

    // Modal Functions
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
