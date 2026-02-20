<?php
$title = "Kết quả Xét tuyển";
require_once __DIR__ . '/../../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Kết quả Xét tuyển (Dự kiến)</h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?= url('/admin/import') ?>" class="btn btn-secondary">Quay lại Import</a>
        <form action="<?= url('/admin/admission/process') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn tính lại điểm không? Dữ liệu cũ sẽ bị ghi đè.');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">Tính lại Điểm</button>
        </form>
    </div>
</div>

<?php if (isset($_GET['message'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['message']) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-auto">
                <select name="major" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả các ngành --</option>
                    <?php foreach ($majors as $m): ?>
                        <option value="<?= $m['ma_nganh'] ?>" <?= ($filterMajor == $m['ma_nganh']) ? 'selected' : '' ?>>
                            <?= $m['ma_nganh'] ?> - <?= $m['ten_nganh'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Lọc</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($groupedResults)): ?>
    <div class="alert alert-info">Chưa có dữ liệu xét tuyển. Vui lòng bấm "Tính lại Điểm".</div>
<?php else: ?>
    <?php foreach ($groupedResults as $ma_nganh => $rows): ?>
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Ngành: <?= htmlspecialchars($ma_nganh) ?> (<?= count($rows) ?> thí sinh)</h5>
                <div>
                    <a href="<?= url('/admin/reports/export-admitted?ma_nganh=' . $ma_nganh) ?>" class="btn btn-sm btn-success me-2" target="_blank">
                        <i class="fas fa-file-excel"></i> Xuất DS Trúng tuyển
                    </a>
                    <form action="<?= url('/admin/admission/notify') ?>" method="POST" class="d-inline" onsubmit="return confirm('Gửi email thông báo cho tất cả thí sinh trúng tuyển ngành này? Quy trình này có thể mất vài phút.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ma_nganh" value="<?= htmlspecialchars($ma_nganh) ?>">
                        <button type="submit" class="btn btn-sm btn-info text-white">
                            <i class="fas fa-envelope"></i> Gửi Thông báo
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>CCCD</th>
                                <th>Họ và Tên</th>
                                <th>Tổ hợp</th>
                                <th>Phương thức</th>
                                <th>Chi tiết điểm</th>
                                <th>Tổng điểm</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($row['so_cccd']) ?></td>
                                    <td><?= htmlspecialchars($row['ho_va_ten']) ?></td>
                                    <td><?= htmlspecialchars($row['to_hop_xet_tuyen_id'] ?? '') ?></td> <!-- Need map ID to Code if needed -->
                                    <td><?= htmlspecialchars($row['phuong_thuc_xet_tuyen']) ?></td>
                                    <td>
                                        <small>
                                            <?php 
                                            $details = json_decode($row['chi_tiet_diem'], true);
                                            if ($details) {
                                                foreach ($details as $k => $v) {
                                                    if ($k === 'details' || $k === 'total_raw') continue;
                                                    if (is_array($v)) {
                                                        echo "$k: " . ($v['final'] ?? '-') . " ";
                                                    } else {
                                                        echo "$k: $v ";
                                                    }
                                                }
                                            }
                                            ?>
                                        </small>
                                    </td>
                                    <td class="fw-bold text-primary"><?= number_format($row['diem_xet_tuyen'], 2) ?></td>
                                    <td>
                                        <?php if ($row['diem_xet_tuyen'] > 0): ?>
                                            <span class="badge bg-success">Đã tính</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Chưa đạt</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
