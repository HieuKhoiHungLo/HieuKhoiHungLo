<?php $layout = 'admin'; ?>
<?php ob_start(); ?>

<div class="space-y-6">
  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">🖨️ Quản lý Mẫu Phiếu In</h1>
      <p class="text-sm text-gray-500 mt-1">Upload mẫu Word (.docx) để in phiếu nhập học & giấy báo trúng tuyển</p>
    </div>
    <button onclick="openUploadModal()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium flex items-center gap-2 shadow">
      <i class="fas fa-upload"></i> Upload Mẫu Mới
    </button>
  </div>

  <!-- Placeholder Cheat Sheet -->
  <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5">
    <h3 class="font-bold text-blue-800 dark:text-blue-200 mb-3">
      <i class="fas fa-info-circle mr-1"></i> Hướng dẫn tạo mẫu Word
    </h3>
    <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">
      Dùng MS Word thiết kế phiếu, đặt các <strong>placeholder</strong> bên dưới vào đúng vị trí.
      Hệ thống sẽ tự thay thế bằng dữ liệu thí sinh.
    </p>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
      <?php
      $groups = [
        '👤 Thí sinh' => [
          '${HOTEN}' => 'Họ tên (IN HOA)',
          '${NGAYSINH}' => 'Ngày sinh dd/mm/yyyy',
          '${CCCD}' => 'Số CCCD/CMND',
          '${SBD}' => 'Số báo danh/hồ sơ',
          '${KV}' => 'Khu vực ưu tiên',
          '${DOITUONG}' => 'Đối tượng ưu tiên',
          '${PT}' => 'Phương thức xét tuyển',
          '${TOHOP}' => 'Tổ hợp môn',
          '${SDT}' => 'Số điện thoại',
        ],
        '📊 Điểm' => [
          '${DM1}' => 'Điểm môn 1',
          '${DM2}' => 'Điểm môn 2',
          '${DM3}' => 'Điểm môn 3',
          '${DIEMTOHOP}' => 'Điểm tổ hợp (quy đổi)',
          '${DIEMUT}' => 'Điểm ưu tiên',
          '${UTQ}' => 'Điểm UT quy đổi',
          '${DIEMXT}' => 'Điểm xét tuyển',
        ],
        '🎓 Ngành & Nhập học' => [
          '${NGANH}' => 'Tên ngành',
          '${MANGANH}' => 'Mã ngành',
          '${SOTT}' => 'Số thứ tự',
          '${KINHPHI}' => 'Kinh phí nhập học',
          '${THOIGIANNHAP}' => 'Thời gian nhập học',
          '${MAPHIEU}' => 'Mã phiếu nhập học',
          '${NGAYNHAPHOC}' => 'Ngày nhập học',
          '${TENCANBO}' => 'Tên cán bộ xử lý',
        ],
        '💳 Thanh toán / QR' => [
          '${QRT}' => '⭐ Ảnh QR VietQR (thanh toán)',
          '${QRN}' => 'QR mã CCCD',
          '${STK}' => 'Số tài khoản',
          '${NGANHANG}' => 'Ngân hàng',
          '${NOIDUNGCK}' => 'Nội dung chuyển khoản',
        ],
      ];
      foreach ($groups as $groupName => $items): ?>
        <div>
          <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase mb-1"><?= $groupName ?></p>
          <div class="space-y-1">
            <?php foreach ($items as $ph => $desc): ?>
              <div class="flex items-center gap-1 text-xs">
                <code class="bg-white dark:bg-gray-800 border border-blue-300 dark:border-blue-600 text-red-600 px-1 rounded font-mono cursor-pointer" onclick="copyPlaceholder('<?= $ph ?>')" title="Click để copy"><?= $ph ?></code>
                <span class="text-gray-500 truncate"><?= $desc ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="text-xs text-blue-500 mt-3">
      💡 <strong>Lưu ý QR:</strong> Để nhúng QR thanh toán VietQR, hãy đặt <code class="bg-white px-1 rounded">${QRT}</code> vào ô Word dạng ảnh placeholder (bất kỳ ký tự nào trong ô, hệ thống sẽ thay thế bằng ảnh QR).
    </p>
  </div>

  <!-- Template List -->
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
      <h2 class="font-bold text-gray-800 dark:text-gray-100">📋 Danh sách Mẫu Phiếu</h2>
      <span class="text-sm text-gray-500"><?= count($templates) ?> mẫu</span>
    </div>
    <div class="overflow-x-auto">
      <?php if (empty($templates)): ?>
        <div class="text-center py-12 text-gray-400">
          <i class="fas fa-file-word text-5xl mb-3 opacity-30"></i>
          <p>Chưa có mẫu phiếu nào. Hãy upload mẫu đầu tiên!</p>
        </div>
      <?php else: ?>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tên mẫu</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Loại</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Mô tả</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Ngày upload</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <?php foreach ($templates as $tpl): ?>
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <i class="fas fa-file-word text-blue-600 text-lg"></i>
                    <span class="font-medium text-gray-800 dark:text-gray-200"><?= htmlspecialchars($tpl['ten_mau']) ?></span>
                  </div>
                  <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($tpl['ten_file'] ?? '') ?></p>
                </td>
                <td class="px-4 py-3">
                  <?php if ($tpl['loai_mau'] === 'phieu_nhap_hoc'): ?>
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Phiếu Nhập Học</span>
                  <?php else: ?>
                    <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">Giấy Báo TT</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs"><?= htmlspecialchars($tpl['mo_ta'] ?? '') ?></td>
                <td class="px-4 py-3 text-gray-500 text-xs"><?= date('d/m/Y H:i', strtotime($tpl['created_at'])) ?></td>
                <td class="px-4 py-3 text-center">
                  <button onclick="deleteTemplate(<?= $tpl['id'] ?>, '<?= htmlspecialchars($tpl['ten_mau']) ?>')"
                    class="text-red-500 hover:text-red-700 text-sm px-2 py-1 rounded hover:bg-red-50">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
      <h3 class="font-bold text-gray-800 dark:text-gray-100">Upload Mẫu Phiếu Word</h3>
      <button onclick="closeUploadModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
    </div>
    <form id="uploadForm" class="p-6 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Loại mẫu *</label>
        <select name="loai_mau" id="loaiMau" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
          <option value="phieu_nhap_hoc">Phiếu Tiếp Nhận Hồ Sơ Nhập Học</option>
          <option value="giay_bao_trung_tuyen">Giấy Báo Trúng Tuyển và Nhập Học</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tên mẫu *</label>
        <input type="text" name="ten_mau" id="tenMau" placeholder="VD: Mẫu Giấy Báo 2025" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File mẫu (.docx) *</label>
        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center cursor-pointer hover:border-red-400 transition" onclick="document.getElementById('templateFile').click()">
          <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
          <p class="text-sm text-gray-500" id="fileLabel">Click để chọn file hoặc kéo thả vào đây</p>
          <p class="text-xs text-gray-400">Chỉ chấp nhận .docx, tối đa 10MB</p>
          <input type="file" name="template_file" id="templateFile" accept=".docx" class="hidden" onchange="onFileSelect(this)" required />
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mô tả (tùy chọn)</label>
        <textarea name="mo_ta" rows="2" placeholder="VD: Mẫu giấy báo năm 2025, có QR thanh toán" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"></textarea>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="closeUploadModal()" class="flex-1 px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Hủy</button>
        <button type="submit" id="uploadBtn" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">
          <i class="fas fa-upload mr-1"></i> Upload
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openUploadModal() { document.getElementById('uploadModal').classList.remove('hidden'); }
function closeUploadModal() { document.getElementById('uploadModal').classList.add('hidden'); document.getElementById('uploadForm').reset(); document.getElementById('fileLabel').textContent = 'Click để chọn file hoặc kéo thả vào đây'; }

function onFileSelect(input) {
  const f = input.files[0];
  if (f) document.getElementById('fileLabel').textContent = '✅ ' + f.name + ' (' + (f.size/1024).toFixed(1) + ' KB)';
}

function copyPlaceholder(text) {
  navigator.clipboard.writeText(text).then(() => {
    showToast('Đã copy: ' + text, 'success');
  });
}

document.getElementById('uploadForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('uploadBtn');
  btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang upload...';

  const fd = new FormData(this);
  try {
    const res = await fetch('/admin/phieu/templates/upload', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      closeUploadModal();
      setTimeout(() => location.reload(), 800);
    } else {
      showToast(data.message || 'Lỗi không xác định', 'error');
    }
  } catch (err) {
    showToast('Lỗi kết nối server', 'error');
  } finally {
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-upload mr-1"></i> Upload';
  }
});

async function deleteTemplate(id, name) {
  if (!confirm(`Xóa mẫu "${name}"?\nHành động này không thể hoàn tác!`)) return;
  try {
    const fd = new FormData(); fd.append('id', id);
    const res = await fetch('/admin/phieu/templates/delete', { method: 'POST', body: fd });
    const data = await res.json();
    showToast(data.message, data.success ? 'success' : 'error');
    if (data.success) setTimeout(() => location.reload(), 800);
  } catch { showToast('Lỗi kết nối', 'error'); }
}

function showToast(msg, type='success') {
  const t = document.createElement('div');
  t.className = `fixed bottom-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-xl text-white text-sm font-medium transition-all ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include resource_path('views/layouts/' . $layout . '.php'); ?>
