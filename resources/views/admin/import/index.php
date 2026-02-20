<?php
$title = 'Import dữ liệu Bộ GD&ĐT';
require_once __DIR__ . '/../../layouts/admin.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Import Dữ liệu Bộ GD&ĐT</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createBatchModal">
            <i class="fas fa-plus"></i> Tạo Đợt Tuyển sinh Mới
        </button>
    </div>
</div>

<!-- Active Batch Info -->
<?php if ($activeBatch): ?>
<div class="alert alert-info d-flex align-items-center" role="alert">
    <i class="fas fa-info-circle me-2"></i>
    <div>
        <strong>Đợt đang hoạt động:</strong> <?= htmlspecialchars($activeBatch['ten_dot']) ?> (Năm <?= $activeBatch['nam'] ?>)
    </div>
</div>
<?php else: ?>
    <div class="alert alert-warning" role="alert">
        Chưa có đợt tuyển sinh nào đang hoạt động. Vui lòng tạo đợt mới.
    </div>
<?php endif; ?>

<!-- Admissions Processing Phase 5 -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Xử lý Xét tuyển (Phase 5)</h5>
                <span class="badge bg-light text-primary">Mới</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Tính điểm và Xếp hạng</h6>
                        <p class="card-text small text-muted">Hệ thống sẽ tính điểm xét tuyển cho tất cả thí sinh trong đợt tuyển sinh này dựa trên điểm THPT và Nguyện vọng.</p>
                    </div>
                    <div class="d-flex gap-2">
                            <form action="<?= url('/admin/admission/process') ?>" method="POST" onsubmit="return confirm('Hành động này sẽ tính điểm cho TẤT CẢ thí sinh trong đợt này. Quá trình có thể mất vài phút. Bạn có chắc chắn?');">
                            <?= csrf_field() ?? '' ?>
                            <button type="submit" class="btn btn-primary" <?= empty($activeBatch) ? 'disabled' : '' ?>>
                                <i class="fas fa-calculator me-2"></i>Tính điểm Xét tuyển
                            </button>
                        </form>
                        <a href="<?= url('/admin/admission/results') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Xem Kết quả
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- File 1: Candidates -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                File 1: Thí sinh & Điểm thi
            </div>
            <div class="card-body">
                <p class="card-text small">Import thông tin cá nhân, khu vực ưu tiên và điểm thi THPT.</p>
                <form id="form-candidates" class="import-form">
                    <input type="hidden" name="type" value="candidates">
                    <input type="hidden" name="batch_id" value="<?= $activeBatch['id'] ?? '' ?>">
                    <div class="mb-3">
                        <label for="file1" class="form-label">Chọn file CSV</label>
                        <input class="form-control" type="file" id="file1" name="file" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" <?= empty($activeBatch) ? 'disabled' : '' ?>>
                        <i class="fas fa-upload"></i> Upload & Import
                    </button>
                    <div class="mt-2 status-msg"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- File 3: Applications -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                File 3: Nguyện vọng
            </div>
            <div class="card-body">
                <p class="card-text small">Import danh sách nguyện vọng xét tuyển.</p>
                <form id="form-applications" class="import-form">
                    <input type="hidden" name="type" value="applications">
                    <input type="hidden" name="batch_id" value="<?= $activeBatch['id'] ?? '' ?>">
                    <div class="mb-3">
                        <label for="file3" class="form-label">Chọn file CSV</label>
                        <input class="form-control" type="file" id="file3" name="file" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100" <?= empty($activeBatch) ? 'disabled' : '' ?>>
                        <i class="fas fa-upload"></i> Upload & Import
                    </button>
                    <div class="mt-2 status-msg"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- File 9: Transcripts -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-warning text-dark">
                File 9: Học bạ
            </div>
            <div class="card-body">
                <p class="card-text small">Import điểm học tập THPT (Nếu có).</p>
                <form id="form-transcripts" class="import-form">
                    <input type="hidden" name="type" value="transcripts">
                    <input type="hidden" name="batch_id" value="<?= $activeBatch['id'] ?? '' ?>">
                    <div class="mb-3">
                        <label for="file9" class="form-label">Chọn file CSV</label>
                        <input class="form-control" type="file" id="file9" name="file" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100" <?= empty($activeBatch) ? 'disabled' : '' ?>>
                        <i class="fas fa-upload"></i> Upload & Import
                    </button>
                    <div class="mt-2 status-msg"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        Lịch sử Import
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ngày</th>
                        <th>File</th>
                        <th>Loại</th>
                        <th>Số bản ghi</th>
                        <th>Người Import</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td><?= $log['created_at'] ?></td>
                        <td><?= htmlspecialchars($log['file_name']) ?></td>
                        <td><?= $log['loai_file'] ?></td>
                        <td><?= $log['record_count'] ?></td>
                        <td>Admin #<?= $log['imported_by'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Create Batch -->
<div class="modal fade" id="createBatchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/admin/import/batch/create" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tạo Đợt Tuyển sinh Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên đợt</label>
                        <input type="text" class="form-control" name="name" required placeholder="Ví dụ: Đợt bổ sung tháng 9">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Năm</label>
                        <input type="number" class="form-control" name="year" value="<?= date('Y') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.import-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const msgDiv = this.querySelector('.status-msg');
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';
        msgDiv.innerHTML = '';

        const formData = new FormData(this);

        try {
            const response = await fetch('/admin/import/upload', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status) {
                msgDiv.innerHTML = `<div class="alert alert-success mt-2">Thành công! Import ${result.success}/${result.count} dòng.</div>`;
                setTimeout(() => location.reload(), 2000);
            } else {
                let errorHtml = `<strong>Lỗi:</strong> ${result.message}`;
                if (result.errors && result.errors.length > 0) {
                    errorHtml += '<ul class="mb-0 small">';
                    result.errors.slice(0, 5).forEach(err => errorHtml += `<li>${err}</li>`);
                    if (result.errors.length > 5) errorHtml += `<li>... và ${result.errors.length - 5} lỗi khác</li>`;
                    errorHtml += '</ul>';
                }
                msgDiv.innerHTML = `<div class="alert alert-danger mt-2">${errorHtml}</div>`;
            }
        } catch (error) {
            msgDiv.innerHTML = `<div class="alert alert-danger mt-2">Lỗi kết nối: ${error.message}</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload"></i> Upload & Import';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
