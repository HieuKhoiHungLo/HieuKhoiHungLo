<?php $title = 'Quản lý Đợt Tuyển sinh - Admin'; ?>
<?php ob_start(); ?>

<div class="max-w-6xl mx-auto p-4 md:p-8">
    <header class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <a href="<?= url('/admin/master-data') ?>" class="text-[#0066FF] text-xs font-bold uppercase tracking-widest hover:underline transition block mb-2">&larr; Quay lại danh mục</a>
            <h2 class="text-3xl font-black text-slate-800 uppercase font-heading">Đợt Tuyển sinh</h2>
        </div>
        <button onclick="openModal()" class="bg-[#0066FF] hover:bg-blue-700 text-white font-black py-3 px-6 rounded-xl shadow-lg transform hover:scale-105 transition flex items-center">
            <i class="fas fa-plus mr-2"></i> Thêm đợt mới
        </button>
    </header>

    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                        <th class="px-6 py-5 pl-8">#</th>
                        <th class="px-6 py-5">Tên đợt</th>
                        <th class="px-6 py-5">Năm</th>
                        <th class="px-6 py-5">Thời gian nhận hồ sơ</th>
                        <th class="px-6 py-5">Trạng thái</th>
                        <th class="px-6 py-5 pr-8 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-200">
                                        <i class="fas fa-calendar-times text-3xl"></i>
                                    </div>
                                    <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest">Chưa có đợt tuyển sinh nào</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                            <tr class="hover:bg-slate-50/50 transition group">
                                <td class="px-6 py-6 pl-8">
                                    <span class="text-xs font-black text-slate-300">#<?= $session['id'] ?></span>
                                </td>
                                <td class="px-6 py-6">
                                    <div class="font-black text-slate-700 uppercase tracking-tight"><?= htmlspecialchars($session['ten_dot']) ?></div>
                                </td>
                                <td class="px-6 py-6">
                                    <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-lg">
                                        <?= $session['nam_tuyen_sinh'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-6">
                                    <div class="flex items-center space-x-3 text-xs">
                                        <div class="text-slate-600 font-bold"><?= date('d/m/Y', strtotime($session['ngay_bat_dau'])) ?></div>
                                        <i class="fas fa-long-arrow-alt-right text-slate-200"></i>
                                        <div class="text-slate-600 font-bold"><?= date('d/m/Y', strtotime($session['ngay_ket_thuc'])) ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <?php if ($session['kich_hoat']): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black bg-amber-100 text-amber-700 uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-2 animate-pulse"></span>
                                            Đang kích hoạt
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black bg-slate-100 text-slate-400 uppercase tracking-wider">
                                            Bị khóa
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-6 pr-8 text-center text-xs opacity-0 group-hover:opacity-100 transition">
                                    <button onclick='editSession(<?= json_encode($session) ?>)' class="font-black uppercase tracking-widest text-[#0066FF] hover:text-blue-800 hover:underline">
                                        Sửa
                                    </button>
                                    <button onclick="deleteSession(<?= $session['id'] ?>)" class="ml-4 font-black uppercase tracking-widest text-red-500 hover:text-red-700 hover:underline">
                                        Xóa
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-[100]">
    <div class="bg-white rounded-[2rem] p-8 w-full max-w-md shadow-2xl transform transition-all">
        <div class="flex justify-between items-center mb-6">
            <h3 id="modal-title" class="text-xl font-black uppercase text-slate-800 font-heading">Đợt Tuyển sinh</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form action="<?= url('/admin/master-data/sessions') ?>" method="POST" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
            <input type="hidden" name="id" id="session_id">
            
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Tên đợt tuyển sinh</label>
                <input type="text" name="ten_dot" id="ten_dot" required 
                       class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-[#0066FF] focus:bg-white transition"
                       placeholder="VD: Đợt 1">
            </div>
            
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Năm tuyển sinh</label>
                <input type="number" name="nam_tuyen_sinh" id="nam_tuyen_sinh" required value="<?= date('Y') ?>" 
                       class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-[#0066FF] focus:bg-white transition">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Ngày bắt đầu</label>
                    <input type="date" name="ngay_bat_dau" id="ngay_bat_dau" required 
                           class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-[#0066FF] focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Ngày kết thúc</label>
                    <input type="date" name="ngay_ket_thuc" id="ngay_ket_thuc" required 
                           class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-[#0066FF] focus:bg-white transition">
                </div>
            </div>
            
            <label class="flex items-center space-x-3 cursor-pointer p-4 bg-slate-50 rounded-2xl hover:bg-slate-100 transition border border-slate-100">
                <div class="relative inline-block w-12 h-6 transition duration-200 ease-in-out bg-slate-200 rounded-full">
                    <input type="checkbox" name="kich_hoat" id="kich_hoat" class="absolute w-6 h-6 bg-white border-2 border-slate-200 rounded-full appearance-none cursor-pointer checked:right-0 checked:border-[#0066FF] right-6 focus:outline-none transition-all duration-200 shadow-sm" style="top: 0;">
                </div>
                <span class="text-sm font-bold text-slate-700">Kích hoạt đợt này</span>
            </label>
            
            <div class="flex space-x-4 pt-4">
                <button type="button" onclick="closeModal()" 
                        class="flex-grow py-4 bg-slate-100 text-slate-600 font-black uppercase text-xs tracking-widest rounded-2xl hover:bg-slate-200 transition">Hủy</button>
                <button type="submit" 
                        class="flex-grow py-4 bg-[#0066FF] text-white font-black uppercase text-xs tracking-widest rounded-2xl shadow-lg hover:shadow-xl hover:bg-blue-700 transition">Lưu dữ liệu</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteForm" action="<?= url('/admin/master-data/sessions/delete') ?>" method="POST" class="hidden">
    <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
    function openModal() {
        document.getElementById('modal').classList.remove('hidden');
        document.getElementById('modal').classList.add('flex');
        document.getElementById('modal-title').innerText = 'Thêm đợt mới';
        document.getElementById('session_id').value = '';
        document.getElementById('ten_dot').value = '';
        document.getElementById('nam_tuyen_sinh').value = '<?= date('Y') ?>';
        document.getElementById('ngay_bat_dau').value = '';
        document.getElementById('ngay_ket_thuc').value = '';
        document.getElementById('kich_hoat').checked = true;
    }
    function closeModal() {
        document.getElementById('modal').classList.add('hidden');
        document.getElementById('modal').classList.remove('flex');
    }
    function editSession(s) {
        openModal();
        document.getElementById('modal-title').innerText = 'Sửa đợt tuyển sinh';
        document.getElementById('session_id').value = s.id;
        document.getElementById('ten_dot').value = s.ten_dot;
        document.getElementById('nam_tuyen_sinh').value = s.nam_tuyen_sinh;
        document.getElementById('ngay_bat_dau').value = s.ngay_bat_dau;
        document.getElementById('ngay_ket_thuc').value = s.ngay_ket_thuc;
        document.getElementById('kich_hoat').checked = parseInt(s.kich_hoat) === 1;
    }
    
    function deleteSession(id) {
        if(confirm('CẢNH BÁO: Hành động này sẽ khóa hoặc xóa đợt tuyển sinh này.\n\nNếu đợt này đã có dữ liệu hồ sơ, hệ thống sẽ chặn xóa để bảo vệ dữ liệu.\n\nBạn có chắc chắn muốn tiếp tục?')) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>
