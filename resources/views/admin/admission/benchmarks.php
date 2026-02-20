<?php
$title = "Thiết lập Điểm chuẩn";
require_once __DIR__ . '/../../layouts/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Thiết lập Điểm chuẩn</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= url('/admin/admission/results') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Xem Kết quả
        </a>
    </div>
</div>

<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] == 'success'): ?>
        <div class="alert alert-success">Đã lưu điểm chuẩn thành công.</div>
    <?php else: ?>
        <div class="alert alert-danger">Lỗi khi lưu điểm chuẩn.</div>
    <?php endif; ?>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-pencil-alt me-2"></i> Nhập Điểm chuẩn cho Đợt: <?= htmlspecialchars($activeSession['ten_dot'] ?? 'N/A') ?>
    </div>
    <div class="card-body">
        <form action="<?= url('/admin/admission/benchmarks') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Mã Ngành</th>
                            <th>Tên Ngành</th>
                            <th>Chỉ tiêu (Dự kiến)</th>
                            <th width="150">Điểm chuẩn</th>
                            <th width="150">Tiêu chí phụ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($majors as $major): ?>
                            <?php 
                                $code = $major['ma_nganh'];
                                $val = $benchmarks[$code] ?? [];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($code) ?></td>
                                <td><?= htmlspecialchars($major['ten_nganh']) ?></td>
                                <td><?= htmlspecialchars($major['chi_tieu'] ?? 0) ?></td>
                                <td>
                                    <input type="number" step="0.01" class="form-control" name="benchmarks[<?= $code ?>][score]" value="<?= $val['diem_chuan'] ?? '' ?>" placeholder="0.00">
                                </td>
                                <td>
                                    <input type="number" step="0.01" class="form-control" name="benchmarks[<?= $code ?>][sub_score]" value="<?= $val['tieuchi_phu'] ?? '' ?>" placeholder="0.00">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Lưu Điểm chuẩn
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4 border-warning">
    <div class="card-header bg-warning text-dark">
        <i class="fas fa-gavel me-2"></i> Công bố Kết quả
    </div>
    <div class="card-body">
        <p>Sau khi thiết lập điểm chuẩn, bạn cần chạy quy trình <strong>Xác định Trúng tuyển</strong> để hệ thống cập nhật trạng thái cho thí sinh.</p>
        <form action="<?= url('/admin/admission/finalize') ?>" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn CHỐT danh sách trúng tuyển theo điểm chuẩn này không? Hành động này sẽ cập nhật trạng thái Đậu/Trượt cho thí sinh.');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-warning w-100">
                <i class="fas fa-check-circle me-2"></i> Xác định Trúng tuyển & Công bố
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
