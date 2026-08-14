<?php 
$title = 'Cấu Hình Mẫu In (WYSIWYG)';
ob_start(); 
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0" style="font-weight: 700; color: #1e293b;">
                        <i class="fas fa-file-invoice" style="color: #2563eb; margin-right: 8px;"></i> Cấu Hình Mẫu In (WYSIWYG)
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card" style="border:none; border-radius:12px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
                        <div class="card-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; border-radius:12px 12px 0 0; padding:16px 20px;">
                            <h3 class="card-title" style="font-weight:600; color:#334155; font-size:15px;">Danh sách Mẫu In</h3>
                        </div>
                        <div class="card-body" style="padding: 24px;">
                            
                            <!-- Template Selection -->
                            <div class="form-group mb-4">
                                <label style="font-weight:600; color:#475569;">Chọn Mẫu In để chỉnh sửa:</label>
                                <select id="templateSelector" class="form-control" style="border-radius:8px; max-width:400px; border-color:#cbd5e1;" onchange="loadTemplate(this.value)">
                                    <option value="">-- Vui lòng chọn mẫu --</option>
                                    <?php foreach ($templates as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['ten_mau']) ?> (<?= htmlspecialchars($t['ma_mau']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Upload Area -->
                            <div id="uploadContainer" style="display:none; margin-top: 20px;">
                                <div class="alert alert-info" style="background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; border-radius:8px; padding:12px 16px;">
                                    <strong><i class="fas fa-info-circle mr-1"></i> Hướng dẫn:</strong> Tải lên file Word mẫu (.docx) đã được căn lỉnh chuẩn chỉ.<br>
                                    Sử dụng các biến sau trong file Word để hệ thống tự động điền dữ liệu thí sinh khi in: 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${hoten}</code>, 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${ngay_sinh}</code>, 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${gioi_tinh}</code>, 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${so_cccd}</code>, 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${dien_thoai}</code>, 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${sbd}</code>, 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${nganh}</code>, 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${gvcn}</code>, 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${ma_nganh}</code>,
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${khoi}</code>, 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${diem_tong}</code>,
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${xac_nhan_bo}</code>,
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${xac_nhan_truong}</code>,
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${nop_kinh_phi}</code>,
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${so_giay_bao}</code><br>
                                    <strong>Mã QR, Ảnh & Hồ sơ:</strong> 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${qr_cccd}</code> (Mã QR CCCD), 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${anh_the}</code> (Ảnh chân dung 3x4), 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${hs_giay_cn}</code> (Giấy CN thi), 
                                    <code style="background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px;">${hs_hoc_ba}</code> (Học bạ)
                                </div>

                                <div class="form-group">
                                    <label>Trạng thái hiện tại:</label>
                                    <div id="currentFileStatus" style="font-weight: 600; margin-bottom: 15px;"></div>
                                </div>

                                <input type="hidden" id="currentTemplateId">
                                <div class="form-group mt-4">
                                    <label>Chọn file mẫu Word (.docx):</label>
                                    <input type="file" id="templateFile" accept=".docx" class="form-control" style="padding: 5px;">
                                </div>

                                <div class="mt-4 text-right">
                                    <button class="btn btn-primary" onclick="saveTemplate()" id="saveBtn" style="border-radius:8px; padding:8px 24px; font-weight:600; background:#2563eb; border:none; box-shadow:0 2px 4px rgba(37,99,235,0.2);">
                                        <i class="fas fa-upload mr-2"></i> Tải lên Mẫu Word
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    const templatesData = <?= json_encode($templates, JSON_UNESCAPED_UNICODE) ?>;

    function loadTemplate(id) {
        if (!id) {
            document.getElementById('uploadContainer').style.display = 'none';
            return;
        }

        const tpl = templatesData.find(t => t.id == id);
        if (tpl) {
            document.getElementById('currentTemplateId').value = tpl.id;
            document.getElementById('uploadContainer').style.display = 'block';
            
            const statusDiv = document.getElementById('currentFileStatus');
            if (tpl.file_path) {
                statusDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Đã tải lên file: <strong>' + tpl.file_path + '</strong></span>';
            } else {
                statusDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Chưa có file mẫu</span>';
            }
        }
    }

    function saveTemplate() {
        const id = document.getElementById('currentTemplateId').value;
        const fileInput = document.getElementById('templateFile');
        
        if (!fileInput.files || fileInput.files.length === 0) {
            alert("Vui lòng chọn một file .docx để tải lên.");
            return;
        }
        
        const file = fileInput.files[0];
        
        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang tải lên...';

        const formData = new FormData();
        formData.append('id', id);
        formData.append('template_file', file);
        formData.append('csrf_token', '<?= \App\Middleware\SecurityMiddleware::generateCsrfToken() ?>');

        fetch('<?= url("/admin/templates/save") ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async res => {
            const isJson = res.headers.get('content-type')?.includes('application/json');
            const data = isJson ? await res.json() : null;
            
            if (!res.ok) {
                const errorMsg = data && data.error ? data.error : 'Lỗi kết nối máy chủ (' + res.status + ')';
                throw new Error(errorMsg);
            }
            return data;
        })
        .then(data => {
            if (data && data.success) {
                alert('Tải lên mẫu thành công!');
                window.location.reload();
            } else {
                alert('Lỗi: ' + (data ? data.message : 'Phản hồi không hợp lệ'));
            }
        })
        .catch(err => {
            console.error(err);
            alert(err.message || 'Lỗi kết nối máy chủ!');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload mr-2"></i> Tải lên Mẫu Word';
        });
    }
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php'; 
?>
