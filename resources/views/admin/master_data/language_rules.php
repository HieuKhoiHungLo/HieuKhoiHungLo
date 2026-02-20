<?php ob_start(); ?>

<div class="main-content flex-1 bg-gray-50 mt-12 md:mt-2 pb-24 md:pb-5">
    <div class="bg-gray-800 pt-3">
        <div class="rounded-tl-3xl bg-gradient-to-r from-blue-900 to-gray-800 p-4 shadow text-2xl text-white">
            <h1 class="font-bold pl-2">Quy đổi điểm Ngoại ngữ</h1>
        </div>
    </div>

    <div class="p-4">
        <div class="mb-4 flex justify-between items-center">
            <div class="relative">
                <i class="fas fa-search absolute top-3 left-4 text-gray-400"></i>
                <input type="text" id="searchInput" onkeyup="searchTable()" class="bg-white h-10 px-10 pr-5 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-[#0066FF] shadow-sm w-64 transition" placeholder="Tìm kiếm quy tắc...">
            </div>
            <button onclick="openModal()" class="bg-[#0066FF] hover:bg-[#9d1926] text-white font-black py-2 px-5 rounded-xl shadow-lg transform hover:scale-105 transition flex items-center">
                <i class="fas fa-plus mr-2"></i> Thêm Quy tắc
            </button>
        </div>

        <div class="overflow-x-auto bg-white rounded-xl shadow-md">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 uppercase text-xs font-bold font-mono tracking-wider">
                        <th class="px-6 py-3 text-left">Loại chứng chỉ</th>
                        <th class="px-6 py-3 text-center">Điểm Min</th>
                        <th class="px-6 py-3 text-center">Điểm Max</th>
                        <th class="px-6 py-3 text-center">Điểm quy đổi (Thang 10)</th>
                        <th class="px-6 py-3 text-left">Ghi chú</th>
                        <th class="px-6 py-3 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="text-sm border-t border-slate-200" id="tableBody">
                    <?php foreach ($rules as $rule): ?>
                        <tr class="hover:bg-red-50/40 border-b border-slate-100 group transition duration-200">
                            <td class="px-6 py-3 font-bold text-slate-700"><?= htmlspecialchars($rule['loai_chung_chi']) ?></td>
                            <td class="px-6 py-3 text-center font-mono font-bold text-slate-500"><?= $rule['diem_min'] ?></td>
                            <td class="px-6 py-3 text-center font-mono font-bold text-slate-500"><?= $rule['diem_max'] ?></td>
                            <td class="px-6 py-3 text-center font-black text-[#0066FF] text-lg"><?= $rule['diem_quy_doi'] ?></td>
                            <td class="px-6 py-3 text-slate-500 italic"><?= htmlspecialchars($rule['ghi_chu'] ?? '') ?></td>
                            <td class="px-6 py-3 text-center opacity-0 group-hover:opacity-100 transition">
                                <button onclick='editRule(<?= json_encode($rule) ?>)' class="text-[#0066FF] hover:text-blue-800 font-bold text-xs uppercase mr-4">Sửa</button>
                                <form action="<?= url('/admin/master-data/language-rules/delete') ?>" method="POST" class="inline" onsubmit="return confirm('Xóa quy tắc này?')">
                                    <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
                                    <input type="hidden" name="id" value="<?= $rule['id'] ?>">
                                    <button type="submit" class="text-slate-400 hover:text-red-600 font-bold text-xs uppercase">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="modal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-11/12 md:w-1/3 overflow-hidden transform transition-all scale-95" id="modal-content">
        <div class="bg-gradient-to-r from-[#0066FF] to-blue-600 px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold text-white text-lg tracking-wide" id="modal-title">Thêm Quy tắc</h3>
            <button onclick="closeModal()" class="text-white/80 hover:text-white transition transform hover:rotate-90">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form action="<?= url('/admin/master-data/language-rules') ?>" method="POST" class="p-6">
            <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
            <input type="hidden" name="id" id="rule-id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Loại chứng chỉ</label>
                    <input type="text" name="loai_chung_chi" id="loai_chung_chi" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-bold text-indigo-900 placeholder-gray-300" placeholder="VD: IELTS">
                </div>
                
                <div class="flex space-x-4">
                    <div class="w-1/2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Điểm Min</label>
                        <input type="number" step="0.1" name="diem_min" id="diem_min" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-mono text-sm placeholder-gray-300">
                    </div>
                    <div class="w-1/2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Điểm Max</label>
                        <input type="number" step="0.1" name="diem_max" id="diem_max" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-mono text-sm placeholder-gray-300" placeholder="VD: 4.5">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Điểm quy đổi (Thang 10)</label>
                    <input type="number" step="0.01" name="diem_quy_doi" id="diem_quy_doi" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition font-black text-[#0066FF] text-lg placeholder-gray-300">
                </div>

                <div>
                     <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Ghi chú</label>
                     <input type="text" name="ghi_chu" id="ghi_chu" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0066FF] outline-none transition text-sm placeholder-gray-300">
                </div>
            </div>

            <div class="flex space-x-3 pt-8 mt-4">
                <button type="button" onclick="closeModal()" class="flex-grow py-3 bg-gray-100 text-gray-600 font-black uppercase text-xs tracking-widest rounded-xl hover:bg-gray-200 transition">Hủy</button>
                <button type="submit" class="flex-grow py-3 bg-[#0066FF] text-white font-black uppercase text-xs tracking-widest rounded-xl shadow-lg hover:shadow-xl hover:bg-[#9d1926] transition">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modal').classList.remove('hidden');
        document.getElementById('modal').classList.add('flex');
        document.getElementById('modal-title').innerText = 'Thêm Quy tắc';
        document.getElementById('rule-id').value = '';
        document.getElementById('loai_chung_chi').value = '';
        document.getElementById('diem_min').value = '';
        document.getElementById('diem_max').value = '';
        document.getElementById('diem_quy_doi').value = '';
        document.getElementById('ghi_chu').value = '';
    }
    function closeModal() {
        document.getElementById('modal').classList.add('hidden');
        document.getElementById('modal').classList.remove('flex');
    }
    function editRule(r) {
        openModal();
        document.getElementById('modal-title').innerText = 'Cập nhật Quy tắc';
        document.getElementById('rule-id').value = r.id;
        document.getElementById('loai_chung_chi').value = r.loai_chung_chi;
        document.getElementById('diem_min').value = r.diem_min;
        document.getElementById('diem_max').value = r.diem_max;
        document.getElementById('diem_quy_doi').value = r.diem_quy_doi;
        document.getElementById('ghi_chu').value = r.ghi_chu;
    }
    
    // Simple search
    function searchTable() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        table = document.getElementById("tableBody");
        tr = table.getElementsByTagName("tr");
        for (i = 0; i < tr.length; i++) {
            var found = false;
            var cols = tr[i].getElementsByTagName("td");
            for(var j=0; j<cols.length-1; j++){
                td = cols[j];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            if (found) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
