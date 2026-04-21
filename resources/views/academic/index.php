<?php
$title = 'Nhập liệu Học bạ';
include __DIR__ . '/../layouts/header.php';

$subjects = [
    'toan' => 'Toán',
    'van' => 'Ngữ văn',
    'ngoai' => 'Ngoại ngữ',
    'ly' => 'Vật lí',
    'hoa' => 'Hóa học',
    'sinh' => 'Sinh học',
    'su' => 'Lịch sử',
    'dia' => 'Địa lí',
    'gdcd' => 'GDKT & PL',
    'cong_nghe' => 'Công nghệ',
    'tin_hoc' => 'Tin học'
];

$getVal = function ($grade, $sem, $field) use ($records) {
    if (!isset($records[$grade])) return '';

    $prefix = 'diem_';
    if ($field === 'tb') $prefix = 'diem_';
    if ($field === 'ngoai') $field = 'ngoai_ngu';

    if (in_array($field, ['hoc_luc', 'hanh_kiem'])) {
        $col = "{$field}_{$sem}";
        if ($sem === 'cn') $col = "{$field}_ca_nam";
        return $records[$grade][$col] ?? ''; // Keep empty for dropdowns
    } elseif ($field === 'tb') {
        $col = "diem_tb_{$sem}";
        if ($sem === 'cn') $col = "diem_tb_ca_nam";
    } else {
        $col = "diem_{$field}_{$sem}";
    }

    $val = $records[$grade][$col] ?? '';
    return ($val === '') ? 0 : $val; // Default to 0 for numbers
};
?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('focus', function() {
                this.select();
            });
        });
    });
</script>

<div class="max-w-6xl mx-auto">
    <div class="bg-white shadow-xl rounded-xl overflow-hidden">

        <!-- Header -->
        <div class="bg-hvu-red p-6 text-white text-center">
            <h2 class="text-2xl font-bold uppercase tracking-wide">Nhập Điểm Học Bạ THPT</h2>
            <p class="text-white/80 text-sm font-bold italic">Bước 2/4: Cập nhật điểm và tải học bạ</p>
        </div>

        <!-- Wizard Navigation -->
        <div class="bg-gray-100 px-6 py-4 border-b flex justify-between items-center text-xs md:text-sm font-semibold">
            <div class="text-green-600 flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg></span>
                <span>Hồ sơ</span>
            </div>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
            <div class="text-hvu-red flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mb-1 border-2 border-hvu-red">2</span>
                <span>Học bạ</span>
            </div>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
            <div class="text-gray-400 flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1">3</span>
                <span>Chứng chỉ ngoại ngữ quốc tế</span>
            </div>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
            <div class="text-gray-400 flex flex-col items-center">
                <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1">4</span>
                <span>Nguyện vọng</span>
            </div>
        </div>

        <div class="p-6 md:p-8">
            <?php if (!empty($error)): ?>
                <div class="bg-red-50 border-l-4 border-hvu-red text-red-700 p-4 rounded mb-6 flex items-start">
                    <svg class="w-6 h-6 mr-3 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div><?= (string) ($error ?? "") ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/academic') ?>" id="academicForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">

                <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6 flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm text-blue-800">Nhập đầy đủ điểm TB các môn học và <strong>tải lên ảnh chụp học bạ</strong> của 3 năm (Lớp 10, 11, 12).</span>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm">
                    <table class="w-full text-sm text-center border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700 uppercase font-bold text-xs">
                                <th class="px-2 py-3 border whitespace-nowrap sticky left-0 bg-gray-100 z-20 shadow-sm" style="min-width: 150px;">Môn học</th>
                                <th class="px-2 py-3 border bg-red-50 text-hvu-red" colspan="2">Lớp 10</th>
                                <th class="px-2 py-3 border bg-blue-50 text-blue-700" colspan="2">Lớp 11</th>
                                <th class="px-2 py-3 border bg-yellow-50 text-yellow-700" colspan="2">Lớp 12</th>
                            </tr>
                            <tr class="bg-gray-50 text-gray-600 font-semibold text-xs border-b">
                                <th class="px-2 py-2 border sticky left-0 bg-gray-50 z-20"></th>
                                <th class="px-1 py-2 border w-20">HK 1</th>
                                <th class="px-1 py-2 border w-20">HK 2</th>
                                <th class="px-1 py-2 border w-20">HK 1</th>
                                <th class="px-1 py-2 border w-20">HK 2</th>
                                <th class="px-1 py-2 border w-20">HK 1</th>
                                <th class="px-1 py-2 border w-20">HK 2</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php foreach ($subjects as $key => $name): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-3 py-2 font-bold text-gray-800 text-left border-r sticky left-0 bg-white z-10 whitespace-nowrap"><?= $name ?></td>

                                    <!-- Lớp 10 -->
                                    <td class="p-1 border text-center">
                                        <input type="number" step="0.1" min="0" max="10" class="hvu-input-sm"
                                            name="grades[10][hk1][<?= $key ?>]" value="<?= $getVal(10, 'hk1', $key) ?>" placeholder="0.0">
                                    </td>
                                    <td class="p-1 border text-center">
                                        <input type="number" step="0.1" min="0" max="10" class="hvu-input-sm"
                                            name="grades[10][hk2][<?= $key ?>]" value="<?= $getVal(10, 'hk2', $key) ?>" placeholder="0.0">
                                    </td>

                                    <!-- Lớp 11 -->
                                    <td class="p-1 border text-center bg-gray-50/30">
                                        <input type="number" step="0.1" min="0" max="10" class="hvu-input-sm"
                                            name="grades[11][hk1][<?= $key ?>]" value="<?= $getVal(11, 'hk1', $key) ?>" placeholder="0.0">
                                    </td>
                                    <td class="p-1 border text-center bg-gray-50/30">
                                        <input type="number" step="0.1" min="0" max="10" class="hvu-input-sm"
                                            name="grades[11][hk2][<?= $key ?>]" value="<?= $getVal(11, 'hk2', $key) ?>" placeholder="0.0">
                                    </td>

                                    <!-- Lớp 12 -->
                                    <td class="p-1 border text-center">
                                        <input type="number" step="0.1" min="0" max="10" class="hvu-input-sm"
                                            name="grades[12][hk1][<?= $key ?>]" value="<?= $getVal(12, 'hk1', $key) ?>" placeholder="0.0">
                                    </td>
                                    <td class="p-1 border text-center">
                                        <input type="number" step="0.1" min="0" max="10" class="hvu-input-sm"
                                            name="grades[12][hk2][<?= $key ?>]" value="<?= $getVal(12, 'hk2', $key) ?>" placeholder="0.0">
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Summary Row: Điểm Trung Bình -->
                            <tr class="bg-blue-50/50 border-t-2 border-blue-100 font-bold">
                                <td class="px-3 py-3 text-blue-800 text-left border-r sticky left-0 bg-blue-50/50 z-10">Điểm TB cả năm <br><span class="text-[10px] font-normal italic text-blue-600">(nếu có)</span></td>
                                <td class="p-1 border text-center"><input type="number" step="0.1" min="0" max="10" class="hvu-input-sm bg-white font-bold text-blue-700" name="grades[10][hk1][tb]" value="<?= $getVal(10, 'hk1', 'tb') ?>"></td>
                                <td class="p-1 border text-center"><input type="number" step="0.1" min="0" max="10" class="hvu-input-sm bg-white font-bold text-blue-700" name="grades[10][hk2][tb]" value="<?= $getVal(10, 'hk2', 'tb') ?>"></td>
                                <td class="p-1 border text-center"><input type="number" step="0.1" min="0" max="10" class="hvu-input-sm bg-white font-bold text-blue-700" name="grades[11][hk1][tb]" value="<?= $getVal(11, 'hk1', 'tb') ?>"></td>
                                <td class="p-1 border text-center"><input type="number" step="0.1" min="0" max="10" class="hvu-input-sm bg-white font-bold text-blue-700" name="grades[11][hk2][tb]" value="<?= $getVal(11, 'hk2', 'tb') ?>"></td>
                                <td class="p-1 border text-center"><input type="number" step="0.1" min="0" max="10" class="hvu-input-sm bg-white font-bold text-blue-700" name="grades[12][hk1][tb]" value="<?= $getVal(12, 'hk1', 'tb') ?>"></td>
                                <td class="p-1 border text-center"><input type="number" step="0.1" min="0" max="10" class="hvu-input-sm bg-white font-bold text-blue-700" name="grades[12][hk2][tb]" value="<?= $getVal(12, 'hk2', 'tb') ?>"></td>
                            </tr>

                            <!-- Summary Row: Học Lực -->
                            <tr class="bg-gray-50 border-t border-gray-200">
                                <td class="px-3 py-2 text-gray-700 text-left border-r sticky left-0 bg-gray-50 z-10 font-medium">Học Lực</td>
                                <?php foreach ([10, 11, 12] as $g): foreach (['hk1', 'hk2'] as $s): 
                                    $currentHl = $getVal($g, $s, 'hoc_luc');
                                ?>
                                        <td class="p-1 border text-center">
                                            <select class="hvu-input-sm font-bold" name="grades[<?= $g ?>][<?= $s ?>][hoc_luc]">
                                                <option value="">--</option>
                                                <?php foreach(['TỐT', 'ĐẠT', 'TRUNG BÌNH', 'CHƯA ĐẠT'] as $l): ?>
                                                    <option value="<?= $l ?>" <?= ($currentHl == $l || $currentHl == array_search($l, ['Gioi'=>'TỐT', 'Kha'=>'ĐẠT', 'TrungBinh'=>'TRUNG BÌNH', 'Yeu'=>'CHƯA ĐẠT'])) ? 'selected' : '' ?>><?= $l ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                <?php endforeach;
                                endforeach; ?>
                            </tr>

                            <!-- Summary Row: Hạnh Kiểm -->
                            <tr class="bg-white border-t border-gray-200">
                                <td class="px-3 py-2 text-gray-700 text-left border-r sticky left-0 bg-white z-10 font-medium">Hạnh Kiểm</td>
                                <?php foreach ([10, 11, 12] as $g): foreach (['hk1', 'hk2'] as $s): 
                                    $currentHk = $getVal($g, $s, 'hanh_kiem');
                                ?>
                                        <td class="p-1 border text-center">
                                            <select class="hvu-input-sm font-bold" name="grades[<?= $g ?>][<?= $s ?>][hanh_kiem]">
                                                <option value="">--</option>
                                                <?php foreach(['TỐT', 'ĐẠT', 'TRUNG BÌNH', 'CHƯA ĐẠT'] as $l): ?>
                                                    <option value="<?= $l ?>" <?= ($currentHk == $l || $currentHk == array_search($l, ['Tot'=>'TỐT', 'Kha'=>'ĐẠT', 'TrungBinh'=>'TRUNG BÌNH', 'Yeu'=>'CHƯA ĐẠT'])) ? 'selected' : '' ?>><?= $l ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                <?php endforeach;
                                endforeach; ?>
                            </tr>

                            <!-- Upload Section -->
                            <tr class="bg-gray-50">
                                <td class="px-3 py-4 text-gray-700 text-left border-r sticky left-0 bg-gray-50 z-10 font-bold italic">Ảnh chụp Học bạ</td>
                                <?php foreach ([10, 11, 12] as $g): ?>
                                    <td colspan="2" class="p-3 border text-center">
                                        <div class="flex flex-col items-center space-y-2 w-full">
                                            <input type="file" name="transcripts_<?= $g ?>[]" multiple accept="image/*" onchange="previewMultipleImages(this, 'preview_<?= $g ?>')" class="text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-hvu-red/10 file:text-hvu-red hover:file:bg-hvu-red/20 w-full">
                                            <div id="preview_<?= $g ?>" class="flex gap-2 flex-wrap justify-center p-1 w-full empty:hidden rounded-lg bg-white border border-dashed border-gray-200 min-h-[4rem]"></div>
                                            <div class="flex space-x-1">
                                                <?php if (!empty($data[$g]['file_minh_chung_1'])): ?>
                                                    <a href="<?= url($data[$g]['file_minh_chung_1']) ?>" target="_blank" class="text-[10px] text-blue-600 font-bold underline">Ảnh 1</a>
                                                <?php endif; ?>
                                                <?php if (!empty($data[$g]['file_minh_chung_2'])): ?>
                                                    <a href="<?= url($data[$g]['file_minh_chung_2']) ?>" target="_blank" class="text-[10px] text-blue-600 font-bold underline">Ảnh 2</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-10 bg-gray-50 border border-gray-100 rounded-2xl p-6 shadow-inner">
                    <h4 class="text-sm font-black text-gray-500 uppercase tracking-widest mb-4 flex items-center">
                        <i class="fas fa-tasks mr-2 text-hvu-red"></i> Xác nhận tình trạng học bạ
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="group relative flex items-center p-4 bg-white border-2 border-transparent hover:border-hvu-red/20 rounded-xl cursor-pointer transition-all has-[:checked]:border-hvu-red has-[:checked]:bg-red-50/30">
                            <input type="radio" name="da_du_6_ky" value="1" <?= ($user['da_du_6_ky'] ?? false) ? 'checked' : '' ?> class="w-5 h-5 text-hvu-red border-gray-300 focus:ring-hvu-red">
                            <div class="ml-4">
                                <span class="block text-sm font-bold text-gray-900 group-hover:text-hvu-red transition-colors">Đã đủ điểm 6 học kỳ</span>
                                <span class="block text-xs text-gray-500">Thí sinh đã có đầy đủ điểm từ HK1 lớp 10 đến HK2 lớp 12.</span>
                            </div>
                        </label>

                        <label class="group relative flex items-center p-4 bg-white border-2 border-transparent hover:border-gray-300 rounded-xl cursor-pointer transition-all has-[:checked]:border-gray-500 has-[:checked]:bg-gray-50">
                            <input type="radio" name="da_du_6_ky" value="0" <?= !($user['da_du_6_ky'] ?? false) ? 'checked' : '' ?> class="w-5 h-5 text-gray-500 border-gray-300 focus:ring-gray-500">
                            <div class="ml-4">
                                <span class="block text-sm font-bold text-gray-900">Chưa đủ điểm 6 học kỳ</span>
                                <span class="block text-xs text-gray-500">Thí sinh chưa có đủ điểm 6 kỳ (Ví dụ: đang học kỳ 2 lớp 12).</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mt-12 text-center pb-8 border-t border-gray-100 pt-10">
                    <button type="submit" class="inline-flex items-center justify-center px-16 py-5 bg-gradient-to-br from-hvu-red to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black text-xl rounded-2xl shadow-2xl hover:shadow-red-500/30 transform hover:-translate-y-1 transition-all">
                        <i class="fas fa-save mr-3"></i> Lưu Học Bạ & Tiếp Tục
                    </button>
                    <p class="text-xs text-gray-400 mt-4 italic">* Tải lên ảnh chụp Học bạ (không chấp nhận PDF) để Nhà trường đối soát.</p>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
    function previewMultipleImages(input, previewId) {
        const previewContainer = document.getElementById(previewId);
        previewContainer.innerHTML = '';

        if (input.files) {
            Array.from(input.files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const imgContainer = document.createElement('div');
                        imgContainer.className = 'relative w-16 h-16 md:w-20 md:h-20 rounded-md overflow-hidden border border-gray-200 shadow-sm';

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-full h-full object-cover';

                        imgContainer.appendChild(img);
                        previewContainer.appendChild(imgContainer);
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>