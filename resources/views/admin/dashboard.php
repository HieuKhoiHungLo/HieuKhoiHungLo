<?php ob_start(); ?>

<?php include __DIR__ . '/partials/_stats.php'; ?>

<!-- Main Content Area with AlpineJS context -->
<div x-data="{ 
    showCols: JSON.parse(localStorage.getItem('admin_cols')) || { province: false, school: false, nv1: false },
    toggleCol(col) {
        this.showCols[col] = !this.showCols[col];
        localStorage.setItem('admin_cols', JSON.stringify(this.showCols));
    },
    colLabel(col) {
        const labels = { province: 'Hộ khẩu', school: 'Trường THPT', nv1: 'NV1' };
        return labels[col];
    }
}">

    <?php include __DIR__ . '/partials/_filters.php'; ?>

    <?php include __DIR__ . '/partials/_candidates_table.php'; ?>
</div>

<?php include __DIR__ . '/partials/_modals.php'; ?>

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

        let actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'forced_action';
        actionInput.value = 'transfer';
        bulkForm.appendChild(actionInput);

        Loading.show();
        bulkForm.submit();
    }

    function openEmailModal() {
        const count = document.querySelectorAll('.item-checkbox:checked').length;
        document.getElementById('email-count').innerText = count;
        document.getElementById('email-modal').classList.remove('hidden');
    }

    function confirmSendEmail() {
        const subject = document.getElementById('modal-email-subject').value;
        const content = document.getElementById('modal-email-content').value;

        if (!subject || !content) {
            showToast('Vui lòng nhập tiêu đề và nội dung', 'warning');
            return;
        }

        let inputSub = document.createElement('input');
        inputSub.type = 'hidden';
        inputSub.name = 'email_subject';
        inputSub.value = subject;
        bulkForm.appendChild(inputSub);

        let inputContent = document.createElement('input');
        inputContent.type = 'hidden';
        inputContent.name = 'email_content';
        inputContent.value = content;
        bulkForm.appendChild(inputContent);

        Loading.show();
        bulkForm.submit();
    }
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin.php'; 
?>
