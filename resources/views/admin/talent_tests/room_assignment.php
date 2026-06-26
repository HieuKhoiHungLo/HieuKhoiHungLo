<?php ob_start(); ?>

<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="<?= url('/admin/talent-tests/edit?id=' . $session['id']) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Phân phòng thi</h1>
                <p class="text-slate-500 text-sm"><?= htmlspecialchars($session['session_name']) ?></p>
            </div>
        </div>
        <a href="<?= url('/admin/talent-tests/exam-config?session_id=' . $session['id']) ?>" class="px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition flex items-center">
            <i class="fas fa-arrow-right mr-2"></i> Bước tiếp: Tổ chức thi
        </a>
    </div>

    <!-- Messages -->
    <?php 
    $msgs = [
        'created_rooms' => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'fa-check-circle', 'Đã tạo tự động %s phòng thi mới.'],
        'deleted_rooms' => ['bg-rose-50 text-rose-700 border-rose-200', 'fa-trash-alt', 'Đã xóa toàn bộ %s phòng thi.'],
        'room_deleted' => ['bg-amber-50 text-amber-700 border-amber-200', 'fa-info-circle', 'Đã xóa phòng thi thành công.'],
        'reset' => ['bg-blue-50 text-blue-700 border-blue-200', 'fa-undo', 'Đã đưa %s thí sinh ra khỏi phòng.']
    ];
    foreach ($msgs as $key => $msg): 
        if (isset($_GET[$key])): ?>
            <div class="mb-4 p-3 <?= $msg[0] ?> border rounded-xl flex justify-between items-center text-sm shadow-sm">
                <span><i class="fas <?= $msg[1] ?> mr-2"></i><?= sprintf($msg[2], (int)$_GET[$key]) ?></span>
                <button onclick="this.parentElement.remove()" class="opacity-50 hover:opacity-100"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; 
    endforeach; ?>

    <!-- Config & Actions -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
        <div class="flex flex-wrap items-end gap-6 justify-between">
            <form action="<?= url('/admin/talent-tests/auto-create-rooms') ?>" method="POST" class="flex flex-wrap items-end gap-4">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Số TS / Phòng</label>
                    <input type="number" name="per_room" value="29" min="1" max="100" class="w-24 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-center focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Bắt đầu từ phòng số</label>
                    <input type="number" name="start_num" value="1" min="1" class="w-24 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-center focus:ring-2 focus:ring-amber-100 outline-none">
                </div>
                <button type="submit" class="px-5 py-2 bg-amber-600 text-white text-sm font-bold rounded-xl hover:bg-amber-700 transition shadow-lg shadow-amber-200">
                    <i class="fas fa-magic mr-1"></i> Tạo phòng & Chia TS
                </button>
            </form>

            <div class="flex gap-2">
                <form action="<?= url('/admin/talent-tests/reset-rooms') ?>" method="POST" onsubmit="return confirm('Đưa tất cả thí sinh ra khỏi phòng?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                    <button type="submit" class="px-4 py-2 bg-blue-50 text-blue-600 text-sm font-bold rounded-xl hover:bg-blue-100 border border-blue-200 transition">
                        <i class="fas fa-undo mr-1"></i> Bỏ xếp phòng
                    </button>
                </form>
                <form action="<?= url('/admin/talent-tests/delete-all-rooms') ?>" method="POST" onsubmit="return confirm('Xóa toàn bộ phòng thi?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                    <button type="submit" class="px-4 py-2 bg-rose-50 text-rose-600 text-sm font-bold rounded-xl hover:bg-rose-100 border border-rose-200 transition">
                        <i class="fas fa-trash mr-1"></i> Xóa tất cả phòng
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Workspace: Split Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Panel Trái: Danh sách Phòng (Drag Target) -->
        <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col h-[700px]">
            <div class="px-5 py-4 border-b border-slate-100 bg-amber-50/50 flex justify-between items-center shrink-0">
                <h2 class="font-bold text-slate-800"><i class="fas fa-door-open text-amber-500 mr-2"></i>DANH SÁCH PHÒNG</h2>
                <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-lg"><?= count($rooms) ?> phòng</span>
            </div>
            
            <div class="overflow-y-auto flex-1 p-3 space-y-2 bg-slate-50/50 room-list-container">
                <!-- Vùng cho thí sinh chưa xếp phòng -->
                <div class="room-dropzone p-3 rounded-xl border-2 border-dashed border-slate-300 bg-white transition cursor-pointer hover:border-blue-400 hover:bg-blue-50" 
                     data-room-id="" onclick="selectRoom(this, '')">
                    <div class="flex justify-between items-center">
                        <div class="font-bold text-slate-600"><i class="fas fa-users-slash text-slate-400 mr-2"></i>Chưa xếp phòng</div>
                        <span class="text-xs font-bold text-rose-500 bg-rose-50 px-2 py-1 rounded-lg unassigned-count"><?= count($unassigned) ?> TS</span>
                    </div>
                </div>

                <?php foreach ($rooms as $r): ?>
                    <div class="room-dropzone p-3 rounded-xl border-2 border-transparent border-b-slate-100 bg-white shadow-sm transition cursor-pointer hover:border-amber-400" 
                         data-room-id="<?= $r['id'] ?>" onclick="selectRoom(this, <?= $r['id'] ?>)">
                        <div class="flex justify-between items-center mb-2">
                            <div class="font-bold text-slate-800 text-lg">Phòng <?= htmlspecialchars($r['room_name']) ?></div>
                            <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded-lg room-count-<?= $r['id'] ?>"><?= $r['current_count'] ?> / <?= $r['capacity'] ?> TS</span>
                        </div>
                        <div class="flex justify-end gap-2">
                            <form action="<?= url('/admin/talent-tests/delete-room') ?>" method="POST" class="inline" onsubmit="return confirm('Xóa phòng này?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                                <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                                <button type="submit" title="Xóa phòng" class="text-slate-300 hover:text-rose-500 transition px-2" onclick="event.stopPropagation();"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Panel Phải: Danh sách Thí sinh (Draggable Items) -->
        <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col h-[700px]">
            <div class="px-5 py-4 border-b border-slate-100 bg-blue-50/50 flex justify-between items-center shrink-0">
                <h2 class="font-bold text-slate-800 flex items-center">
                    <i class="fas fa-users text-blue-500 mr-2"></i>
                    THÍ SINH: <span id="currentRoomLabel" class="text-blue-600 ml-1">Chưa xếp phòng</span>
                </h2>
                <div class="flex items-center gap-2">
                    <span id="candidateCountLabel" class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-lg"><?= count($unassigned) ?> TS</span>
                </div>
            </div>

            <!-- Loader -->
            <div id="candidatesLoader" class="hidden absolute inset-0 bg-white/80 z-10 flex items-center justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <div class="overflow-y-auto flex-1 p-0">
                <table class="w-full text-left text-sm">
                    <thead class="sticky top-0 bg-slate-50 border-b border-slate-200 z-10 shadow-sm">
                        <tr>
                            <th class="px-3 py-3 w-10 text-center"><input type="checkbox" id="checkAllDrag" class="w-4 h-4 rounded"></th>
                            <th class="px-3 py-3 text-xs font-bold text-slate-500 uppercase w-10">SBD</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Họ tên</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Ngày sinh</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Ngành</th>
                        </tr>
                    </thead>
                    <tbody id="candidatesTbody">
                        <!-- Render init: Unassigned -->
                        <?php if (empty($unassigned)): ?>
                            <tr class="empty-row"><td colspan="5" class="px-6 py-10 text-center text-slate-400 italic">Không có thí sinh.</td></tr>
                        <?php else: ?>
                            <?php foreach ($unassigned as $c): ?>
                                <tr class="border-b border-slate-50 hover:bg-blue-50/30 transition candidate-row" data-id="<?= $c['id'] ?>">
                                    <td class="px-3 py-2.5 text-center"><input type="checkbox" class="drag-check w-4 h-4 rounded" value="<?= $c['id'] ?>"></td>
                                    <td class="px-3 py-2.5 font-mono text-xs text-blue-600 font-bold"><?= htmlspecialchars($c['exam_number'] ?? '--') ?></td>
                                    <td class="px-4 py-2.5 font-bold text-slate-800"><?= htmlspecialchars($c['name']) ?></td>
                                    <td class="px-4 py-2.5 text-slate-600"><?= $c['birth_date'] ? date('d/m/Y', strtotime($c['birth_date'])) : '--' ?></td>
                                    <td class="px-4 py-2.5 text-slate-600 text-xs"><?= htmlspecialchars($c['subject_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Action Bar for Right Panel -->
            <div class="p-3 border-t border-slate-100 bg-slate-50 shrink-0 flex items-center justify-between">
                <span class="text-xs text-slate-500"><i class="fas fa-info-circle mr-1"></i>Chọn thí sinh, sau đó click vào phòng ở cột trái để chuyển.</span>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?= csrf_token() ?>';
let currentSelectedRoomId = ''; // '' means unassigned
let allCandidatesRaw = <?= json_encode($allCandidates) ?>; // Lấy toàn bộ TS đủ ĐK để filter local nhanh

// Init check all
document.getElementById('checkAllDrag').addEventListener('change', function() {
    document.querySelectorAll('.drag-check').forEach(cb => cb.checked = this.checked);
});

// Select Room to view candidates
function selectRoom(element, roomId) {
    // UI update for room list
    document.querySelectorAll('.room-dropzone').forEach(el => {
        el.classList.remove('border-blue-400', 'bg-blue-50/50');
        el.classList.add('border-transparent');
    });
    element.classList.remove('border-transparent');
    element.classList.add('border-blue-400', 'bg-blue-50/50');

    // Chuyển TS nếu đang có check
    const checkedIds = Array.from(document.querySelectorAll('.drag-check:checked')).map(cb => cb.value);
    
    if (checkedIds.length > 0) {
        if (roomId == currentSelectedRoomId) {
            alert('Thí sinh đã ở phòng này rồi.');
            return;
        }
        if (confirm(`Chuyển ${checkedIds.length} thí sinh vào ${roomId ? 'Phòng này' : 'Chưa xếp phòng'}?`)) {
            moveCandidates(checkedIds, roomId);
        }
        return;
    }

    // Nếu ko có check -> Đổi view panel phải
    currentSelectedRoomId = roomId;
    document.getElementById('currentRoomLabel').textContent = roomId ? element.querySelector('.text-lg').textContent : 'Chưa xếp phòng';
    
    loadCandidatesByRoom(roomId);
}

// Gọi API để lấy TS của phòng
function loadCandidatesByRoom(roomId) {
    const tbody = document.getElementById('candidatesTbody');
    const loader = document.getElementById('candidatesLoader');
    loader.classList.remove('hidden');

    fetch(`<?= url('/admin/talent-tests/api/room-candidates') ?>?room_id=${roomId}`)
        .then(res => res.json())
        .then(data => {
            renderCandidatesTable(data.candidates || []);
        })
        .catch(err => {
            console.error(err);
            alert('Lỗi tải dữ liệu phòng.');
        })
        .finally(() => {
            loader.classList.add('hidden');
        });
}

// Xử lý riêng cho unassigned
function getUnassignedCandidates() {
    // In real app, you might want to call API too, but here we can filter from allCandidatesRaw 
    // Wait, allCandidates doesn't have room_id joined simply in this context unless we re-fetch.
    // For safety, let's reload page if switching to unassigned, or create an API for unassigned.
    // To keep it simple, reload page:
    window.location.reload();
}

function renderCandidatesTable(candidates) {
    const tbody = document.getElementById('candidatesTbody');
    document.getElementById('candidateCountLabel').textContent = `${candidates.length} TS`;
    document.getElementById('checkAllDrag').checked = false;
    
    if (candidates.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="5" class="px-6 py-10 text-center text-slate-400 italic">Không có thí sinh.</td></tr>`;
        return;
    }

    let html = '';
    candidates.forEach(c => {
        html += `
            <tr class="border-b border-slate-50 hover:bg-blue-50/30 transition candidate-row" data-id="${c.id}">
                <td class="px-3 py-2.5 text-center"><input type="checkbox" class="drag-check w-4 h-4 rounded" value="${c.id}"></td>
                <td class="px-3 py-2.5 font-mono text-xs text-blue-600 font-bold">${c.exam_number || '--'}</td>
                <td class="px-4 py-2.5 font-bold text-slate-800">${c.name}</td>
                <td class="px-4 py-2.5 text-slate-600">${c.birth_date ? new Date(c.birth_date).toLocaleDateString('vi-VN') : '--'}</td>
                <td class="px-4 py-2.5 text-slate-600 text-xs">${c.subject_name}</td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// Chuyển TS via AJAX
function moveCandidates(assignmentIds, targetRoomId) {
    const promises = assignmentIds.map(id => {
        const fd = new FormData();
        fd.append('csrf_token', csrfToken);
        fd.append('assignment_id', id);
        fd.append('room_id', targetRoomId);
        fd.append('ajax', '1');
        return fetch(`<?= url('/admin/talent-tests/move-candidate') ?>`, {
            method: 'POST',
            body: fd
        });
    });

    Promise.all(promises).then(() => {
        window.location.reload(); // Reload to update counts properly
    }).catch(err => {
        console.error(err);
        alert('Có lỗi khi chuyển thí sinh.');
    });
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php'; 
?>
