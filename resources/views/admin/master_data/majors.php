<?php $title = 'Quản lý Ngành học - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-7xl mx-auto p-8">
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    <header class="mb-8 flex justify-between items-center">
        <div>
            <a href="<?= url('/admin/master-data') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition block mb-2">&larr; Quay lại danh mục</a>
            <h2 class="text-3xl font-black text-gray-900 uppercase">Quản lý Ngành học</h2>
        </div>
        <div class="flex space-x-2">
            <a href="<?= url('/admin/master-data/majors/export') ?>?csrf_token=<?= (string) $this->csrfToken() ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-xl shadow transition flex items-center">
                <i class="fas fa-file-export mr-2"></i> Xuất Excel
            </a>
            <button onclick="openImportModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl shadow transition flex items-center">
                <i class="fas fa-file-import mr-2"></i> Nhập Excel
            </button>
            <button onclick="openModal()" class="bg-[#BE1E2D] hover:bg-[#9d1926] text-white font-black py-2 px-5 rounded-xl shadow-lg transform hover:scale-105 transition flex items-center">
                <i class="fas fa-plus mr-2"></i> Thêm ngành mới
            </button>
        </div>
    </header>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <form action="<?= url('/admin/master-data/majors/actions') ?>" method="POST" id="bulk-delete-form">
            <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
            <input type="hidden" name="action" value="bulk_delete">

            <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center hidden" id="bulk-actions">
                <span class="text-sm font-bold text-slate-600">Đã chọn <span id="selected-count">0</span> mục</span>
                <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa các mục đã chọn?')" class="text-red-600 hover:text-red-800 font-bold text-xs uppercase bg-red-50 hover:bg-red-100 py-2 px-4 rounded-lg transition">
                    <i class="fas fa-trash mr-1"></i> Xóa đã chọn
                </button>
            </div>

            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4 w-10">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-[#0066FF] focus:ring-[#0066FF]">
                        </th>
                        <th class="px-6 py-4">Mã ngành</th>
                        <th class="px-6 py-4">Tên ngành</th>
                        <th class="px-6 py-4 text-center">Nhóm</th>
                        <th class="px-6 py-4 text-center">Chỉ tiêu</th>
                        <th class="px-6 py-4 text-center">KQHT L12</th>
                        <th class="px-6 py-4 text-center">Ngưỡng THPT</th>
                        <th class="px-6 py-4 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($majors as $major): ?>
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <td class="px-6 py-4">
                                <input type="checkbox" name="ids[]" value="<?= $major['ma_nganh'] ?>" class="item-checkbox rounded border-gray-300 text-[#0066FF] focus:ring-[#0066FF]">
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-[#0066FF]"><?= $major['ma_nganh'] ?></td>
                            <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($major['ten_nganh']) ?></td>
                            <td class="px-6 py-4 text-center">
                                <?php
                                $nhom = $major['nhom_nganh'] ?? 'Khac';
                                $nhomLabels = ['SuPham' => 'Sư phạm', 'SuPhamDacThu' => 'SP Đặc thù', 'DieuDuong' => 'Điều dưỡng', 'Khac' => ''];
                                $nhomColors = ['SuPham' => 'bg-emerald-100 text-emerald-700', 'SuPhamDacThu' => 'bg-orange-100 text-orange-700', 'DieuDuong' => 'bg-green-100 text-green-700', 'Khac' => ''];
                                if ($nhom !== 'Khac'):
                                ?>
                                    <span class="px-2 py-1 rounded text-[10px] font-bold <?= $nhomColors[$nhom] ?? '' ?>"><?= $nhomLabels[$nhom] ?? '' ?></span>
                                <?php else: echo '--';
                                endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-500"><?= $major['chi_tieu'] ?: '--' ?></td>
                            <td class="px-6 py-4 text-center font-bold text-sm">
                                <?php
                                $nguongHL = $major['nguong_hoc_luc'] ?? null;
                                $hlLabels = ['Tot' => 'Tốt', 'Kha' => 'Khá', 'Dat' => 'Đạt', 'ChuaDat' => 'Chưa Đạt'];
                                echo $nguongHL ? '<span class="text-amber-600">' . ($hlLabels[$nguongHL] ?? $nguongHL) . '</span>' : '--';
                                ?>
                            </td>
                            <td class="px-6 py-4 text-center font-black text-amber-600"><?= isset($major['nguong_diem_thpt']) && $major['nguong_diem_thpt'] ? number_format($major['nguong_diem_thpt'], 1) : '--' ?></td>
                            <td class="px-6 py-3 text-center flex items-center justify-center space-x-2">
                                <button type="button" onclick='editMajor(<?= json_encode($major) ?>)' class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-[#0066FF] hover:bg-[#0066FF] hover:text-white transition" title="Sửa"><i class="fas fa-edit text-xs"></i></button>
                                <button type="button" onclick="deleteSingle('<?= $major['ma_nganh'] ?>')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition" title="Xóa"><i class="fas fa-trash-alt text-xs"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>

<!-- Single Delete Form -->
<form id="single-delete-form" action="<?= url('/admin/master-data/majors/delete') ?>" method="POST" class="hidden">
    <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
    <input type="hidden" name="ma" id="single-delete-id">
</form>

<!-- Modal -->
<div id="modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-3xl w-full max-w-2xl shadow-2xl flex flex-col max-h-[95vh]">
        <!-- Modal Header -->
        <div class="p-6 border-b flex justify-between items-center bg-slate-50 rounded-t-3xl">
            <h3 id="modal-title" class="text-xl font-black uppercase text-slate-800">Thêm Ngành học</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 overflow-y-auto custom-scrollbar">
            <form action="<?= url('/admin/master-data/majors') ?>" method="POST" id="major-form">
                <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="old_ma" id="old_ma">

                <div class="space-y-8">
                    <!-- Section: Thông tin cơ bản -->
                    <section>
                        <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center">
                            <span class="bg-slate-100 w-6 h-6 rounded-full flex items-center justify-center mr-2 text-slate-500">1</span>
                            Thông tin cơ bản
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-1">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-1">Mã ngành <span class="text-red-500">*</span></label>
                                <input type="text" name="ma_nganh" id="ma_nganh" required
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition font-mono font-bold text-slate-700">
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-1">Tên ngành <span class="text-red-500">*</span></label>
                                <input type="text" name="ten_nganh" id="ten_nganh" required
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition font-bold text-slate-700">
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-1">Chỉ tiêu (CT)</label>
                                <input type="number" name="chi_tieu" id="chi_tieu"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition font-bold text-slate-700">
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5 ml-1">Điểm nhận HS năm 2025</label>
                                <input type="number" step="0.01" name="diem_nam_truoc" id="diem_nam_truoc"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] focus:border-transparent outline-none transition font-bold text-slate-700">
                            </div>
                        </div>
                    </section>

                    <!-- Section: Ngưỡng đầu vào -->
                    <section class="bg-amber-50/50 border border-amber-100 rounded-2xl p-5">
                        <h4 class="text-[11px] font-black text-amber-600 uppercase tracking-widest mb-4 flex items-center">
                            <span class="bg-amber-100 w-6 h-6 rounded-full flex items-center justify-center mr-2 text-amber-600">2</span>
                            Ngưỡng đầu vào (TT06/2026)
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-amber-700/60 uppercase mb-1.5 ml-1">Nhóm ngành</label>
                                <select name="nhom_nganh" id="nhom_nganh"
                                    class="w-full px-3 py-2 bg-white border border-amber-200 rounded-lg focus:ring-2 focus:ring-amber-400 outline-none transition font-bold text-sm text-amber-900">
                                    <option value="Khac">Khác (không ngưỡng)</option>
                                    <option value="SuPham">Sư phạm</option>
                                    <option value="SuPhamDacThu">SP Đặc thù (GDTC/ÂN/MT)</option>
                                    <option value="DieuDuong">Điều dưỡng</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-amber-700/60 uppercase mb-1.5 ml-1">KQ Học tập L12</label>
                                <select name="nguong_hoc_luc" id="nguong_hoc_luc"
                                    class="w-full px-3 py-2 bg-white border border-amber-200 rounded-lg focus:ring-2 focus:ring-amber-400 outline-none transition font-bold text-sm text-amber-900">
                                    <option value="">-- Không yêu cầu --</option>
                                    <option value="Tot">≥ Tốt</option>
                                    <option value="Kha">≥ Khá</option>
                                    <option value="Dat">≥ Đạt</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-amber-700/60 uppercase mb-1.5 ml-1">Ngưỡng THPT</label>
                                <input type="number" step="0.5" min="0" max="30" name="nguong_diem_thpt" id="nguong_diem_thpt" placeholder="VD: 20"
                                    class="w-full px-3 py-2 bg-white border border-amber-200 rounded-lg focus:ring-2 focus:ring-amber-400 outline-none transition font-bold text-sm text-amber-900">
                            </div>
                        </div>
                    </section>

                    <!-- Section: Cấu hình xét tuyển đặc thù -->
                    <section class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-5">
                        <h4 class="text-[11px] font-black text-indigo-600 uppercase tracking-widest mb-4 flex items-center">
                            <span class="bg-indigo-100 w-6 h-6 rounded-full flex items-center justify-center mr-2 text-indigo-600">3</span>
                            Xét đặc thù (TS03, TS04, TS05)
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="flex items-center space-x-3 bg-white p-3 rounded-xl border border-indigo-200 hover:border-indigo-400 cursor-pointer shadow-sm">
                                <input type="checkbox" name="co_xet_chung_chi" id="co_xet_chung_chi" value="1" class="form-checkbox h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <div>
                                    <span class="block font-bold text-sm text-gray-800">Xét Ngoại ngữ</span>
                                    <span class="block text-xs text-gray-500">Mở khóa TS03</span>
                                </div>
                            </label>

                            <label class="flex items-center space-x-3 bg-white p-3 rounded-xl border border-indigo-200 hover:border-indigo-400 cursor-pointer shadow-sm">
                                <input type="checkbox" name="co_diem_nangkhieu_thpt" id="co_diem_nangkhieu_thpt" value="1" class="form-checkbox h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <div>
                                    <span class="block font-bold text-sm text-gray-800">Xét THPT + N.khiếu</span>
                                    <span class="block text-xs text-gray-500">Mở khóa TS04</span>
                                </div>
                            </label>
                            
                            <label class="flex items-center space-x-3 bg-white p-3 rounded-xl border border-indigo-200 hover:border-indigo-400 cursor-pointer shadow-sm">
                                <input type="checkbox" name="co_diem_nangkhieu_hochba" id="co_diem_nangkhieu_hochba" value="1" class="form-checkbox h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <div>
                                    <span class="block font-bold text-sm text-gray-800">Xét Học bạ + N.khiếu</span>
                                    <span class="block text-xs text-gray-500">Mở khóa TS05</span>
                                </div>
                            </label>
                        </div>
                    </section>

                    <!-- Section: Khối xét tuyển -->
                    <section>
                        <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center">
                            <span class="bg-slate-100 w-6 h-6 rounded-full flex items-center justify-center mr-2 text-slate-500">3</span>
                            Khối xét tuyển
                        </h4>
                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2 max-h-40 overflow-y-auto p-3 border border-slate-100 rounded-2xl bg-slate-50/30">
                            <?php foreach ($combinations as $c): ?>
                                <label class="flex items-center space-x-2 bg-white p-2 rounded-lg border border-slate-200 hover:border-[#0066FF] hover:bg-[#0066FF]/5 cursor-pointer transition shadow-sm">
                                    <input type="checkbox" name="combinations[]" value="<?= $c['ma_to_hop'] ?>" class="form-checkbox text-[#0066FF] rounded focus:ring-[#0066FF] border-slate-300">
                                    <span class="font-bold text-xs text-slate-700"><?= $c['ma_to_hop'] ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Section: Khu vực tuyển sinh -->
                    <section>
                        <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center">
                            <span class="bg-slate-100 w-6 h-6 rounded-full flex items-center justify-center mr-2 text-slate-500">4</span>
                            Khu vực tuyển sinh
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 max-h-48 overflow-y-auto p-3 border border-slate-100 rounded-2xl bg-slate-50/30">
                            <?php foreach ($provinces as $p): ?>
                                <label class="flex items-center space-x-2 bg-white p-2 rounded-lg border border-slate-200 hover:border-[#0066FF] hover:bg-[#0066FF]/5 cursor-pointer transition shadow-sm">
                                    <input type="checkbox" name="provinces[]" value="<?= $p['ma_tinh'] ?>" class="form-checkbox text-[#0066FF] rounded focus:ring-[#0066FF] border-slate-300">
                                    <span class="font-bold text-[10px] leading-tight text-slate-700 line-clamp-1"><?= $p['ten_tinh'] ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-2 italic px-1">* Bỏ chọn tất cả để tuyển sinh toàn quốc</p>
                    </section>

                    <!-- Section: Ghi chú -->
                    <section>
                        <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center">
                            <span class="bg-slate-100 w-6 h-6 rounded-full flex items-center justify-center mr-2 text-slate-500">5</span>
                            Ghi chú
                        </h4>
                        <textarea name="ghi_chu" id="ghi_chu" rows="2"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-medium text-sm text-slate-700"
                            placeholder="Thông tin bổ sung..."></textarea>
                    </section>
                </div>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t bg-slate-50 rounded-b-3xl flex space-x-3">
            <button type="button" onclick="closeModal()"
                class="flex-1 py-3 bg-white border border-slate-200 text-slate-600 font-black uppercase text-xs tracking-widest rounded-xl hover:bg-slate-100 transition shadow-sm">Hủy</button>
            <button type="submit" form="major-form"
                class="flex-1 py-3 bg-[#BE1E2D] text-white font-black uppercase text-xs tracking-widest rounded-xl shadow-lg shadow-red-200 hover:bg-[#9d1926] transition transform active:scale-95">Lưu dữ liệu</button>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl transform transition-all">
        <h3 class="text-xl font-black uppercase mb-6 border-b pb-4 text-gray-800">Nhập Ngành từ Excel</h3>
        <form action="<?= url('/admin/master-data/majors/actions') ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
            <input type="hidden" name="action" value="import">

            <div class="space-y-4">
                <p class="text-sm text-gray-600 mb-4">Vui lòng tải lên file CSV (UTF-8) theo mẫu.</p>
                <div class="text-center">
                    <a href="<?= url('/admin/master-data/majors/template') ?>" class="text-[#0066FF] hover:underline text-sm font-bold flex justify-center items-center">
                        <i class="fas fa-download mr-1"></i> Tải file mẫu
                    </a>
                </div>

                <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-50 transition cursor-pointer relative">
                    <input type="file" name="file" required accept=".csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                    <p class="text-sm font-bold text-gray-500">Kéo thả file hoặc click để chọn</p>
                </div>
            </div>

            <div class="flex space-x-3 pt-8 mt-4">
                <button type="button" onclick="closeImportModal()" class="flex-grow py-3 bg-gray-100 text-gray-600 font-black uppercase text-xs tracking-widest rounded-xl hover:bg-gray-200 transition">Hủy</button>
                <button type="submit" class="flex-grow py-3 bg-blue-600 text-white font-black uppercase text-xs tracking-widest rounded-xl shadow-lg hover:shadow-xl hover:bg-blue-700 transition">Upload</button>
            </div>
        </form>
    </div>
</div>

    <script>
    // Existing modal functions
    function openModal() {
        document.getElementById('modal').classList.remove('hidden');
        document.getElementById('modal').classList.add('flex');
        document.getElementById('modal-title').innerText = 'Thêm Ngành học mới';
        document.getElementById('form-action').value = 'create';
        document.getElementById('old_ma').value = '';
        document.getElementById('ma_nganh').value = '';
        document.getElementById('ten_nganh').value = '';
        document.getElementById('chi_tieu').value = '';
        document.getElementById('diem_nam_truoc').value = '';
        document.getElementById('ghi_chu').value = '';
        document.getElementById('nhom_nganh').value = 'Khac';
        document.getElementById('nguong_hoc_luc').value = '';
        document.getElementById('nguong_diem_thpt').value = '';
        document.getElementById('co_xet_chung_chi').checked = false;
        document.getElementById('co_diem_nangkhieu_thpt').checked = false;
        document.getElementById('co_diem_nangkhieu_hochba').checked = false;

        // Reset checkboxes
        document.querySelectorAll('input[name="combinations[]"]').forEach(cb => cb.checked = false);
        document.querySelectorAll('input[name="provinces[]"]').forEach(cb => cb.checked = false);
    }

    function closeModal() {
        document.getElementById('modal').classList.add('hidden');
        document.getElementById('modal').classList.remove('flex');
    }

    function editMajor(m) {
        openModal();
        document.getElementById('modal-title').innerText = 'Cập nhật Ngành học';
        document.getElementById('form-action').value = 'update';
        document.getElementById('old_ma').value = m.ma_nganh;
        document.getElementById('ma_nganh').value = m.ma_nganh;
        document.getElementById('ten_nganh').value = m.ten_nganh;
        document.getElementById('chi_tieu').value = m.chi_tieu;
        document.getElementById('diem_nam_truoc').value = m.diem_nam_truoc;
        document.getElementById('ghi_chu').value = m.ghi_chu;
        document.getElementById('nhom_nganh').value = m.nhom_nganh || 'Khac';
        document.getElementById('nguong_hoc_luc').value = m.nguong_hoc_luc || '';
        document.getElementById('nguong_diem_thpt').value = m.nguong_diem_thpt || '';
        
        document.getElementById('co_xet_chung_chi').checked = m.co_xet_chung_chi ? true : false;
        document.getElementById('co_diem_nangkhieu_thpt').checked = m.co_diem_nangkhieu_thpt ? true : false;
        document.getElementById('co_diem_nangkhieu_hochba').checked = m.co_diem_nangkhieu_hochba ? true : false;

        // Check checkboxes based on m.combination_ids
        document.querySelectorAll('input[name="combinations[]"]').forEach(cb => {
            cb.checked = m.combination_ids && m.combination_ids.includes(cb.value);
        });

        // Check provinces
        document.querySelectorAll('input[name="provinces[]"]').forEach(cb => {
            cb.checked = false;
            if (m.khu_vuc_tuyen_sinh) {
                const allowed = m.khu_vuc_tuyen_sinh.split(',');
                if (allowed.includes(cb.value)) cb.checked = true;
            }
        });
    }

    // New Import Modal Logic
    function openImportModal() {
        document.getElementById('import-modal').classList.remove('hidden');
        document.getElementById('import-modal').classList.add('flex');
    }

    function closeImportModal() {
        document.getElementById('import-modal').classList.add('hidden');
        document.getElementById('import-modal').classList.remove('flex');
    }

    // Bulk Delete Logic
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');

    function updateBulkActions() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        if (checked.length > 0) {
            bulkActions.classList.remove('hidden');
            selectedCount.innerText = checked.length;
        } else {
            bulkActions.classList.add('hidden');
        }
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkActions();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });

    // Single delete
    function deleteSingle(ma) {
        if (confirm('Xác nhận xóa ngành này? Hành động không thể hoàn tác.')) {
            document.getElementById('single-delete-id').value = ma;
            document.getElementById('single-delete-form').submit();
        }
    }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>