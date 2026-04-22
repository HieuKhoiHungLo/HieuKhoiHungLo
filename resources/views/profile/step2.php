<?php
$title = 'Hồ sơ thí sinh - Bước 2';
include __DIR__ . '/../layouts/header.php';

// Map subject keys to names
$subjectMap = [
    'toan' => 'Toán',
    'van' => 'Ngữ văn',
    'ngoai_ngu' => 'Ngoại ngữ',
    'ly' => 'Vật lí',
    'hoa' => 'Hóa học',
    'sinh' => 'Sinh học',
    'su' => 'Lịch sử',
    'dia' => 'Địa lí',
    'gdcd' => 'GDKT & PL',
    'cong_nghe' => 'Công nghệ',
    'tin_hoc' => 'Tin học'
];

// Helper to get annual score value
$getVal = function ($grade, $field) use ($records) {
    if (!isset($records[$grade]) || empty($records[$grade])) return '';

    // Subject scores
    if (array_key_exists($field, ['toan' => 1, 'van' => 1, 'ngoai_ngu' => 1, 'ly' => 1, 'hoa' => 1, 'sinh' => 1, 'su' => 1, 'dia' => 1, 'gdcd' => 1, 'cong_nghe' => 1, 'tin_hoc' => 1])) {
        $col = "diem_{$field}_cn";
        $val = $records[$grade][$col] ?? '';
        return ($val === '') ? '' : $val;
    }

    // Summary fields
    if ($field === 'diem_tb') {
        $val = $records[$grade]['diem_tb_ca_nam'] ?? '';
        return ($val === '') ? '' : $val;
    }
    if ($field === 'hoc_luc') {
        return $records[$grade]['hoc_luc_ca_nam'] ?? '';
    }
    if ($field === 'hanh_kiem') {
        return $records[$grade]['hanh_kiem_ca_nam'] ?? '';
    }

    return '';
};
?>

<div class="max-w-6xl mx-auto pb-20 px-4 sm:px-6">
    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-red-900/5 border border-gray-100 overflow-hidden">

        <!-- Header -->
        <div class="bg-gradient-to-r from-hvu-red to-red-700 p-6 text-white text-center">
            <h2 class="text-2xl font-bold uppercase tracking-wide">Nhập điểm Học bạ THPT</h2>
            <p class="text-white/80 text-sm mt-1">Bước 2/<?= (int) ($totalStepsCount ?? 0) ?>: Điểm trung bình cả năm theo Thông tư 06/2026</p>
        </div>

        <!-- Wizard Navigation -->
        <div class="bg-gray-100 px-6 py-4 border-b flex justify-between items-center text-xs md:text-sm font-semibold overflow-x-auto">
            <a href="<?= url('/profile/step1') ?>" class="text-green-600 flex flex-col items-center min-w-fit px-2 hover:text-green-700 transition-colors">
                <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg></span>
                <span class="hidden sm:block">Hồ sơ</span>
            </a>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
            <div class="text-hvu-red flex flex-col items-center min-w-fit px-2">
                <span class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mb-1 border-2 border-hvu-red">2</span>
                <span class="hidden sm:block">Học bạ</span>
            </div>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
            <a href="<?= url('/profile/step3') ?>" class="text-gray-400 flex flex-col items-center min-w-fit px-2 hover:text-hvu-red transition-colors">
                <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1">3</span>
                <span class="hidden sm:block">Chứng chỉ quốc tế</span>
            </a>

            <?php if ($enableTHPTSetting): ?>
                <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
                <a href="<?= url('/profile/step4') ?>" class="text-gray-400 flex flex-col items-center min-w-fit px-2 hover:text-hvu-red transition-colors">
                    <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1">4</span>
                    <span class="hidden sm:block">Điểm thi</span>
                </a>
            <?php endif; ?>

            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-gray-200"></div>
            <a href="<?= url('/profile/step5') ?>" class="text-gray-400 flex flex-col items-center min-w-fit px-2 hover:text-hvu-red transition-colors">
                <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mb-1"><?= $enableTHPTSetting ? 5 : 4 ?></span>
                <span class="hidden sm:block">Nguyện vọng</span>
            </a>
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

            <form method="POST" action="<?= url('/profile/step2') ?>" id="academicForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">

                <?php if (!empty($isLocked)): ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6" role="alert">
                        <div class="flex">
                            <i class="fas fa-lock text-yellow-400 flex-shrink-0 mt-1"></i>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700 font-bold">Hồ sơ đã được duyệt. Bạn không thể chỉnh sửa thông tin.</p>
                                <?php if (!empty($editRequestPending)): ?>
                                    <p class="text-xs text-yellow-600 mt-1"><i class="fas fa-clock mr-1"></i> Đã gửi yêu cầu chỉnh sửa, vui lòng chờ Quản trị viên xử lý.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <fieldset <?= (!empty($isLocked)) ? 'disabled' : '' ?> class="group/locked contents">

                    <div class="bg-white border text-center p-5 rounded-lg mb-6 shadow-sm border-gray-200">
                        <h3 class="text-[15px] font-bold text-gray-800 mb-4 uppercase tracking-wide">Bạn đã có Điểm trung bình cả năm Lớp 12 chưa?</h3>
                        <div class="flex flex-col sm:flex-row justify-center gap-4">
                            <label class="group relative flex items-center p-4 bg-white border-2 border-transparent hover:border-hvu-red/20 rounded-xl cursor-pointer transition-all has-[:checked]:border-hvu-red has-[:checked]:bg-red-50/30">
                                <input type="radio" name="da_du_6_ky" value="1" <?= ($user['da_du_6_ky'] ?? false) ? 'checked' : '' ?> class="w-5 h-5 text-hvu-red border-gray-300 focus:ring-hvu-red">
                                <div class="ml-3 text-left">
                                    <span class="block text-sm font-bold text-gray-800 group-hover:text-hvu-red transition-colors">Đã có đủ cả 3 năm</span>
                                    <span class="block text-[11px] text-gray-500 mt-1 leading-snug">Đã hoàn thành chương trình lớp 12</span>
                                </div>
                                <div class="absolute inset-0 border-2 border-gray-200 rounded-xl pointer-events-none group-has-[:checked]:border-hvu-red transition-all"></div>
                            </label>

                            <label class="group relative flex items-center p-4 bg-white border-2 border-transparent hover:border-gray-300 rounded-xl cursor-pointer transition-all has-[:checked]:border-gray-500 has-[:checked]:bg-gray-50">
                                <input type="radio" name="da_du_6_ky" value="0" <?= !($user['da_du_6_ky'] ?? false) ? 'checked' : '' ?> class="w-5 h-5 text-gray-500 border-gray-300 focus:ring-gray-500">
                                <div class="ml-3 text-left">
                                    <span class="block text-sm font-bold text-gray-800 group-hover:text-gray-900 transition-colors">Chưa có điểm Lớp 12</span>
                                    <span class="block text-[11px] text-gray-500 mt-1 leading-snug">Chỉ có điểm lớp 10, 11 và điểm HK1 Lớp 12</span>
                                </div>
                                <div class="absolute inset-0 border-2 border-gray-200 rounded-xl pointer-events-none group-has-[:checked]:border-gray-400 transition-all"></div>
                            </label>
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6 flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <span class="text-sm text-blue-800 font-bold">Nhập điểm trung bình chung <u>cả năm</u> của từng môn cho các lớp 10, 11, 12.</span>
                            <p class="text-xs text-blue-600 mt-1">(Theo quy định Thông tư 06/2026/TT-BGDĐT)</p>
                        </div>
                    </div>

                    <!-- ========== DESKTOP VIEW: Full table (hidden on mobile) ========== -->
                    <div class="hidden md:block overflow-x-auto border border-gray-200 rounded-lg shadow-sm" id="desktopGrades">
                        <table class="w-full text-sm text-center border-collapse">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 uppercase font-bold text-xs">
                                    <th class="px-3 py-3 border whitespace-nowrap sticky left-0 bg-gray-100 z-20 shadow-sm" style="min-width: 150px;">Môn học</th>
                                    <th class="px-3 py-3 border bg-red-50 text-hvu-red">Lớp 10<br><span class="text-[10px] font-medium normal-case">(ĐTB cả năm)</span></th>
                                    <th class="px-3 py-3 border bg-blue-50 text-blue-700">Lớp 11<br><span class="text-[10px] font-medium normal-case">(ĐTB cả năm)</span></th>
                                    <th class="px-3 py-3 border bg-yellow-50 text-yellow-700">Lớp 12<br><span class="text-[10px] font-medium normal-case">(ĐTB cả năm)</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <?php foreach ($subjectMap as $key => $name): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-3 py-2.5 font-bold text-gray-800 text-left border-r sticky left-0 bg-white z-10 whitespace-nowrap"><?= $name ?></td>
                                        <?php foreach ([10, 11, 12] as $g): ?>
                                            <td class="p-1.5 border text-center">
                                                <input type="number" step="0.01" min="0" max="10" class="hvu-input-sm w-20 score-input"
                                                    name="records[<?= $g ?>][<?= $key ?>]"
                                                    value="<?= $getVal($g, $key) ?>" placeholder="0.00">
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>

                                <!-- ĐTB cả năm -->
                                <tr class="bg-blue-50/50 border-t-2 border-blue-100 font-bold">
                                    <td class="px-3 py-3 text-blue-800 text-left border-r sticky left-0 bg-blue-50/50 z-10">Điểm TB cả năm <br><span class="text-[10px] font-normal italic text-blue-600">(nếu có)</span></td>
                                    <?php foreach ([10, 11, 12] as $g): ?>
                                        <td class="p-1.5 border text-center">
                                            <input type="number" step="0.01" min="0" max="10" class="hvu-input-sm bg-white font-bold text-blue-700 w-20 score-input"
                                                name="records[<?= $g ?>][diem_tb]"
                                                value="<?= $getVal($g, 'diem_tb') ?>" placeholder="0.00">
                                        </td>
                                    <?php endforeach; ?>
                                </tr>

                                <!-- Kết quả học tập cả năm -->
                                <tr class="bg-gray-50 border-t border-gray-200">
                                    <td class="px-3 py-2 text-gray-700 text-left border-r sticky left-0 bg-gray-50 z-10 font-medium">Kết quả học tập cả năm</td>
                                    <?php foreach ([10, 11, 12] as $g): ?>
                                        <td class="p-1.5 border text-center">
                                            <select class="hvu-input-sm font-bold" name="records[<?= $g ?>][hoc_luc]">
                                                <option value="">--</option>
                                                <option value="Tot" <?= $getVal($g, 'hoc_luc') == 'Tot' ? 'selected' : '' ?>>Tốt</option>
                                                <option value="Kha" <?= $getVal($g, 'hoc_luc') == 'Kha' ? 'selected' : '' ?>>Khá</option>
                                                <option value="Dat" <?= $getVal($g, 'hoc_luc') == 'Dat' ? 'selected' : '' ?>>Đạt</option>
                                                <option value="ChuaDat" <?= $getVal($g, 'hoc_luc') == 'ChuaDat' ? 'selected' : '' ?>>Chưa Đạt</option>
                                            </select>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>

                                <!-- Kết quả rèn luyện cả năm -->
                                <tr class="bg-white border-t border-gray-200">
                                    <td class="px-3 py-2 text-gray-700 text-left border-r sticky left-0 bg-white z-10 font-medium">Kết quả rèn luyện cả năm</td>
                                    <?php foreach ([10, 11, 12] as $g): ?>
                                        <td class="p-1.5 border text-center">
                                            <select class="hvu-input-sm font-bold" name="records[<?= $g ?>][hanh_kiem]">
                                                <option value="">--</option>
                                                <option value="Tot" <?= $getVal($g, 'hanh_kiem') == 'Tot' ? 'selected' : '' ?>>Tốt</option>
                                                <option value="Kha" <?= $getVal($g, 'hanh_kiem') == 'Kha' ? 'selected' : '' ?>>Khá</option>
                                                <option value="Dat" <?= $getVal($g, 'hanh_kiem') == 'Dat' ? 'selected' : '' ?>>Đạt</option>
                                                <option value="ChuaDat" <?= $getVal($g, 'hanh_kiem') == 'ChuaDat' ? 'selected' : '' ?>>Chưa Đạt</option>
                                            </select>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Desktop File Uploads -->
                        <div class="p-6 bg-white border-t border-gray-200">
                            <h4 class="font-bold text-gray-800 mb-4 text-sm"><i class="fas fa-camera mr-2 text-hvu-red"></i> Ảnh chụp minh chứng học bạ</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <?php foreach ([10, 11, 12] as $g): ?>
                                    <?php
                                    // file_hoc_ba stores comma-separated paths
                                    $fileHocBa = $records[$g]['file_hoc_ba'] ?? '';
                                    $hocBaFiles = !empty($fileHocBa) ? array_filter(array_map('trim', explode(',', $fileHocBa))) : [];
                                    $hasImages = count($hocBaFiles) > 0;
                                    $imgCount = count($hocBaFiles);
                                    ?>
                                    <div class="border border-gray-100 rounded-xl p-4 bg-gray-50/50">
                                        <label class="block text-xs font-bold text-gray-800 mb-3 uppercase tracking-wide">Học bạ Lớp <?= $g ?></label>

                                        <?php if ($hasImages): ?>
                                            <!-- Existing Images Preview -->
                                            <div class="grid <?= (count($hocBaFiles) >= 2) ? 'grid-cols-2' : 'grid-cols-1' ?> gap-2 mb-3">
                                                <?php foreach ($hocBaFiles as $i => $rawUrl): ?>
                                                    <?php
                                                    $rawUrl = trim($rawUrl);
                                                    if (empty($rawUrl)) continue;
                                                    $isExt = strpos($rawUrl, 'http') === 0;
                                                    $fullUrl = $isExt ? $rawUrl : asset($rawUrl);
                                                    $thumbUrl = $isExt ? google_drive_thumbnail_url($rawUrl, 'w300') : $fullUrl;
                                                    ?>
                                                    <div class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow aspect-[4/3]">
                                                        <a href="<?= $fullUrl ?>" target="_blank" class="block w-full h-full">
                                                            <img loading="lazy" src="<?= $thumbUrl ?>" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.15]" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-gray-100 text-gray-400\'><i class=\'fas fa-image text-2xl\'></i></div>'">
                                                        </a>
                                                        <span class="absolute top-1 right-1 bg-green-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full shadow pointer-events-none z-10">HK<?= $i + 1 ?></span>
                                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                                            <span class="text-white text-xs font-bold bg-hvu-red/80 px-4 py-2 rounded-full shadow-lg scale-75 group-hover:scale-100 transition-transform duration-300"><i class="fas fa-search-plus mr-1"></i> Xem lớn</span>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <p class="text-[10px] text-green-600 mb-2 font-medium"><i class="fas fa-check-circle mr-1"></i> Đã có <?= $imgCount ?> ảnh. Chọn file mới để thay thế:</p>
                                        <?php else: ?>
                                            <!-- No Images Placeholder -->
                                            <div id="no_image_<?= $g ?>_desktop" class="aspect-[4/3] rounded-lg border-2 border-dashed border-gray-200 bg-white flex flex-col items-center justify-center text-gray-300 mb-3">
                                                <i class="fas fa-image text-3xl mb-2"></i>
                                                <span class="text-xs font-medium">Chưa có ảnh</span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Upload Button -->
                                        <div id="preview_<?= $g ?>_desktop" class="w-full empty:hidden mb-3"></div>
                                        <label class="group cursor-pointer block">
                                            <div class="flex items-center justify-center py-2.5 px-4 rounded-xl border-2 border-dashed border-gray-200 bg-white hover:border-hvu-red/40 hover:bg-red-50/30 transition-all">
                                                <i class="fas fa-cloud-upload-alt text-gray-400 group-hover:text-hvu-red mr-2 transition-colors"></i>
                                                <span class="text-xs font-bold text-gray-500 group-hover:text-hvu-red transition-colors"><?= $hasImages ? 'Thay đổi ảnh' : 'Tải ảnh lên' ?></span>
                                            </div>
                                            <input type="file" name="transcripts_<?= $g ?>[]" multiple accept="image/*" class="hidden" onchange="previewMultipleImages(this, 'preview_<?= $g ?>_desktop', 'no_image_<?= $g ?>_desktop'); updateUploadLabel(this)" />
                                        </label>
                                        <p class="text-[10px] text-gray-400 mt-2 italic text-center">Tối đa 2 ảnh/năm (HK1, HK2)</p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- /Desktop File Uploads -->
                    </div>

                    <!-- ========== MOBILE VIEW: Tab-based (hidden on desktop) ========== -->
                    <div class="md:hidden" id="mobileGrades">
                        <!-- Tab Bar -->
                        <div class="flex border border-gray-200 rounded-t-xl overflow-hidden">
                            <?php
                            $tabColors = [10 => ['bg' => 'bg-red-50', 'text' => 'text-hvu-red', 'border' => 'border-hvu-red'], 11 => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-600'], 12 => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'border' => 'border-yellow-500']];
                            foreach ([10, 11, 12] as $g):
                            ?>
                                <button type="button" onclick="switchGradeTab(<?= $g ?>)" id="gradeTab<?= $g ?>"
                                    class="flex-1 py-3 text-center font-bold text-sm uppercase tracking-wide transition-all
                            <?= $g === 10 ? $tabColors[$g]['bg'] . ' ' . $tabColors[$g]['text'] . ' border-b-3 ' . $tabColors[$g]['border'] : 'bg-gray-50 text-gray-400 border-b-3 border-transparent' ?>">
                                    Lớp <?= $g ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Tab Content -->
                        <?php foreach ([10, 11, 12] as $g): ?>
                            <div id="gradePanel<?= $g ?>" class="<?= $g !== 10 ? 'hidden' : '' ?> border border-t-0 border-gray-200 rounded-b-xl overflow-hidden">
                                <table class="w-full text-sm text-center border-collapse">
                                    <thead>
                                        <tr class="<?= $tabColors[$g]['bg'] ?> <?= $tabColors[$g]['text'] ?> font-bold text-xs uppercase">
                                            <th class="px-3 py-2.5 text-left" style="width:55%">Môn học</th>
                                            <th class="px-2 py-2.5 border-l border-white/50" style="width:45%">ĐTB cả năm (nếu có)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        <?php foreach ($subjectMap as $key => $name): ?>
                                            <tr>
                                                <td class="px-3 py-2 font-semibold text-gray-800 text-left text-[13px]"><?= $name ?></td>
                                                <td class="p-1.5 text-center">
                                                    <input type="number" step="0.01" min="0" max="10" inputmode="decimal"
                                                        class="w-full text-center text-sm font-semibold rounded-lg border border-gray-200 py-2 focus:border-hvu-red focus:ring-1 focus:ring-hvu-red/30 outline-none transition score-input"
                                                        name="records[<?= $g ?>][<?= $key ?>]" value="<?= $getVal($g, $key) ?>" placeholder="0.00">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>

                                        <!-- Điểm TB cả năm -->
                                        <tr class="bg-blue-50/60 border-t-2 border-blue-200">
                                            <td class="px-3 py-2.5 font-bold text-blue-800 text-left text-[13px]">Điểm TB cả năm <br><span class="text-[10px] font-normal italic text-blue-600">(nếu có)</span></td>
                                            <td class="p-1.5 text-center">
                                                <input type="number" step="0.01" min="0" max="10" inputmode="decimal"
                                                    class="w-full text-center text-sm font-bold text-blue-700 rounded-lg border border-blue-200 py-2 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-300 outline-none transition score-input"
                                                    name="records[<?= $g ?>][diem_tb]" value="<?= $getVal($g, 'diem_tb') ?>" placeholder="0.00">
                                            </td>
                                        </tr>

                                        <!-- Kết quả học tập -->
                                        <tr class="bg-gray-50/50">
                                            <td class="px-3 py-2 font-semibold text-gray-600 text-left text-[13px]">Kết quả học tập cả năm</td>
                                            <td class="p-1.5 text-center">
                                                <select class="w-full text-center text-sm font-bold rounded-lg border border-gray-200 py-2 bg-white focus:border-hvu-red outline-none" name="records[<?= $g ?>][hoc_luc]">
                                                    <option value="">--</option>
                                                    <option value="Tot" <?= $getVal($g, 'hoc_luc') == 'Tot' ? 'selected' : '' ?>>Tốt</option>
                                                    <option value="Kha" <?= $getVal($g, 'hoc_luc') == 'Kha' ? 'selected' : '' ?>>Khá</option>
                                                    <option value="Dat" <?= $getVal($g, 'hoc_luc') == 'Dat' ? 'selected' : '' ?>>Đạt</option>
                                                    <option value="ChuaDat" <?= $getVal($g, 'hoc_luc') == 'ChuaDat' ? 'selected' : '' ?>>Chưa Đạt</option>
                                                </select>
                                            </td>
                                        </tr>

                                        <!-- Kết quả rèn luyện -->
                                        <tr>
                                            <td class="px-3 py-2 font-semibold text-gray-600 text-left text-[13px]">Kết quả rèn luyện cả năm</td>
                                            <td class="p-1.5 text-center">
                                                <select class="w-full text-center text-sm font-bold rounded-lg border border-gray-200 py-2 bg-white focus:border-hvu-red outline-none" name="records[<?= $g ?>][hanh_kiem]">
                                                    <option value="">--</option>
                                                    <option value="Tot" <?= $getVal($g, 'hanh_kiem') == 'Tot' ? 'selected' : '' ?>>Tốt</option>
                                                    <option value="Kha" <?= $getVal($g, 'hanh_kiem') == 'Kha' ? 'selected' : '' ?>>Khá</option>
                                                    <option value="Dat" <?= $getVal($g, 'hanh_kiem') == 'Dat' ? 'selected' : '' ?>>Đạt</option>
                                                    <option value="ChuaDat" <?= $getVal($g, 'hanh_kiem') == 'ChuaDat' ? 'selected' : '' ?>>Chưa Đạt</option>
                                                </select>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Mobile File Uploads -->
                                <div class="p-4 bg-gray-50 border-t border-gray-100">
                                    <label class="block text-xs font-bold text-gray-800 mb-3"><i class="fas fa-camera mr-1 text-hvu-red"></i> Ảnh chụp minh chứng Lớp <?= $g ?></label>

                                    <?php
                                    // file_hoc_ba stores comma-separated paths
                                    $mFileHocBa = $records[$g]['file_hoc_ba'] ?? '';
                                    $mHocBaFiles = !empty($mFileHocBa) ? array_filter(array_map('trim', explode(',', $mFileHocBa))) : [];
                                    $mHasImages = count($mHocBaFiles) > 0;
                                    ?>
                                    <?php if ($mHasImages): ?>
                                        <div class="grid <?= (count($mHocBaFiles) >= 2) ? 'grid-cols-2' : 'grid-cols-1' ?> gap-2 mb-3">
                                            <?php foreach ($mHocBaFiles as $mi => $mRawUrl): ?>
                                                <?php
                                                $mRawUrl = trim($mRawUrl);
                                                if (empty($mRawUrl)) continue;
                                                $mIsExt = strpos($mRawUrl, 'http') === 0;
                                                $mFullUrl = $mIsExt ? $mRawUrl : asset($mRawUrl);
                                                $mThumbUrl = $mIsExt ? google_drive_thumbnail_url($mRawUrl, 'w200') : $mFullUrl;
                                                ?>
                                                <div class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow aspect-[4/3]">
                                                    <a href="<?= $mFullUrl ?>" target="_blank" class="block w-full h-full">
                                                        <img loading="lazy" src="<?= $mThumbUrl ?>" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.15]" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-gray-100 text-gray-400\'><i class=\'fas fa-image text-2xl\'></i></div>'">
                                                    </a>
                                                    <span class="absolute top-1 right-1 bg-green-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full shadow pointer-events-none z-10">HK<?= $mi + 1 ?></span>
                                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                                        <span class="text-white text-xs font-bold bg-hvu-red/80 px-4 py-2 rounded-full shadow-lg scale-75 group-hover:scale-100 transition-transform duration-300"><i class="fas fa-search-plus mr-1"></i> Xem lớn</span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div id="no_image_<?= $g ?>_mobile" class="aspect-[4/3] rounded-lg border-2 border-dashed border-gray-200 bg-white flex flex-col items-center justify-center text-gray-300 mb-3">
                                            <i class="fas fa-image text-3xl mb-2"></i>
                                            <span class="text-xs font-medium">Chưa có ảnh</span>
                                        </div>
                                    <?php endif; ?>

                                    <div id="preview_<?= $g ?>_mobile" class="w-full empty:hidden mb-3"></div>
                                    <label class="group cursor-pointer block">
                                        <div class="flex items-center justify-center py-2.5 px-4 rounded-xl border-2 border-dashed border-gray-200 bg-white hover:border-hvu-red/40 hover:bg-red-50/30 transition-all">
                                            <i class="fas fa-cloud-upload-alt text-gray-400 group-hover:text-hvu-red mr-2 transition-colors"></i>
                                            <span class="text-xs font-bold text-gray-500 group-hover:text-hvu-red transition-colors"><?= $mHasImages ? 'Thay đổi ảnh' : 'Tải ảnh lên' ?></span>
                                        </div>
                                        <input type="file" name="transcripts_<?= $g ?>[]" multiple accept="image/*" class="hidden" onchange="previewMultipleImages(this, 'preview_<?= $g ?>_mobile', 'no_image_<?= $g ?>_mobile'); updateUploadLabel(this)" />
                                    </label>
                                </div>
                                <!-- /Mobile File Uploads -->
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="bg-gray-50 px-8 py-6 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0 mt-6">
                        <a href="<?= url('/profile/step1') ?>" class="text-gray-600 hover:text-gray-900 font-bold flex items-center transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i> Quay lại
                        </a>
                        <button type="submit" class="w-full md:w-auto px-12 py-4 bg-hvu-red border-b-4 border-red-800 text-white font-black text-lg rounded-2xl shadow-xl hover:bg-red-700 hover:border-red-900 active:border-b-0 active:translate-y-1 transition-all">
                            Lưu thông tin và tiếp tục <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-4 italic text-center">* Nhập điểm trung bình chung cả năm (không phải điểm từng học kỳ) theo Thông tư 06/2026/TT-BGDĐT.</p>
                </fieldset>
            </form>
        </div>
    </div>
</div>

<script>
    // Preview selected images immediately
    function previewMultipleImages(input, previewId, placeholderId) {
        const previewContainer = document.getElementById(previewId);
        previewContainer.innerHTML = '';

        const placeholder = document.getElementById(placeholderId);
        if (input.files && input.files.length > 0) {
            if (placeholder) placeholder.style.display = 'none';
        } else {
            if (placeholder) placeholder.style.display = 'flex';
        }

        if (input.files && input.files.length > 0) {
            // Setup grid dynamic class like the existing image preview
            previewContainer.className = 'grid gap-2 w-full empty:hidden mb-3 ' + (input.files.length >= 2 ? 'grid-cols-2' : 'grid-cols-1');

            Array.from(input.files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const imgContainer = document.createElement('div');
                        imgContainer.className = 'group relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow aspect-[4/3]';

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-full h-full object-cover transition-transform duration-300 hover:scale-[1.15]';

                        imgContainer.appendChild(img);
                        previewContainer.appendChild(imgContainer);
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    }

    // Update upload button label when files are selected
    function updateUploadLabel(input) {
        const label = input.closest('label').querySelector('span');
        if (input.files && input.files.length > 0) {
            const names = Array.from(input.files).map(f => f.name).join(', ');
            label.textContent = '✓ ' + (input.files.length > 1 ? input.files.length + ' ảnh đã chọn' : names);
            label.classList.remove('text-gray-500');
            label.classList.add('text-green-600');
        }
    }

    // Tab switching for mobile grade tabs
    const tabColorConfig = {
        10: {
            bg: 'bg-red-50',
            text: 'text-hvu-red',
            border: 'border-hvu-red'
        },
        11: {
            bg: 'bg-blue-50',
            text: 'text-blue-700',
            border: 'border-blue-600'
        },
        12: {
            bg: 'bg-yellow-50',
            text: 'text-yellow-700',
            border: 'border-yellow-500'
        }
    };

    function switchGradeTab(grade) {
        [10, 11, 12].forEach(g => {
            const tab = document.getElementById('gradeTab' + g);
            const panel = document.getElementById('gradePanel' + g);
            const colors = tabColorConfig[g];

            if (g === grade) {
                panel.classList.remove('hidden');
                tab.className = `flex-1 py-3 text-center font-bold text-sm uppercase tracking-wide transition-all ${colors.bg} ${colors.text} border-b-3 ${colors.border}`;
            } else {
                panel.classList.add('hidden');
                tab.className = 'flex-1 py-3 text-center font-bold text-sm uppercase tracking-wide transition-all bg-gray-50 text-gray-400 border-b-3 border-transparent';
            }
        });
    }

    // On form submit: disable inputs from the view that is NOT visible to avoid duplicate field names
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('academicForm');
        if (!form) return;

        // Auto-select input content on focus & Handle fast score input
        document.querySelectorAll('.score-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.select();
            });

            input.addEventListener('blur', function() {
                let val = this.value;
                if (!val || val.includes('.') || val.includes(',')) return;
                
                let num = parseInt(val);
                if (isNaN(num)) return;

                if (num > 10) {
                    if (num <= 100) {
                        this.value = (num / 10).toFixed(2);
                    } else if (num <= 1000) {
                        this.value = (num / 100).toFixed(2);
                    }
                }
            });
        });

        form.addEventListener('submit', () => {
            const isMobile = window.innerWidth < 768;
            const desktopView = document.getElementById('desktopGrades');
            const mobileView = document.getElementById('mobileGrades');

            if (isMobile && desktopView) {
                desktopView.querySelectorAll('input, select').forEach(el => el.disabled = true);
            } else if (!isMobile && mobileView) {
                mobileView.querySelectorAll('input, select').forEach(el => el.disabled = true);
            }
        });
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>