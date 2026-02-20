<?php
$title = 'Hồ sơ thí sinh - Bước 2';
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

$getVal = function($grade, $sem, $field) use ($records) {
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




<div class="max-w-6xl mx-auto pb-20 px-4 sm:px-6">
    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-red-900/5 border border-gray-100 overflow-hidden">
        
        <!-- Header -->
        <div class="bg-hvu-red p-6 text-white text-center">
            <h2 class="text-2xl font-bold uppercase tracking-wide">Nhập Điểm Học Bạ THPT</h2>
            <p class="text-white/80 text-sm font-bold italic">Bước 2/<?= $totalStepsCount ?>: Cập nhật điểm và tải học bạ</p>
        </div>

        <!-- Wizard Navigation -->
        <div class="bg-gray-100 px-6 py-4 border-b flex justify-between items-center text-xs md:text-sm font-semibold overflow-x-auto">
           <a href="<?= url('/profile/step1') ?>" class="text-green-600 flex flex-col items-center min-w-fit px-2 hover:text-green-700 transition-colors">
               <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></span>
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
                     <svg class="w-6 h-6 mr-3 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                     <div><?= $error ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/profile/step2') ?>" id="academicForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $this->csrfToken() ?>">
                
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
                
                <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6 flex items-center">
                     <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                     <span class="text-sm text-blue-800">Nhập đầy đủ điểm TB các môn học và <strong>tải lên ảnh chụp học bạ</strong> của 3 năm (Lớp 10, 11, 12).</span>
                </div>

                <!-- ========== DESKTOP VIEW: Full table (hidden on mobile) ========== -->
                <div class="hidden md:block overflow-x-auto border border-gray-200 rounded-lg shadow-sm" id="desktopGrades">
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
                                <?php foreach ([10, 11, 12] as $g): ?>
                                <td class="p-1 border text-center"><input type="number" step="0.1" min="0" max="10" class="hvu-input-sm" name="grades[<?= $g ?>][hk1][<?= $key ?>]" value="<?= $getVal($g, 'hk1', $key) ?>" placeholder="-"></td>
                                <td class="p-1 border text-center"><input type="number" step="0.1" min="0" max="10" class="hvu-input-sm" name="grades[<?= $g ?>][hk2][<?= $key ?>]" value="<?= $getVal($g, 'hk2', $key) ?>" placeholder="-"></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>

                            <tr class="bg-blue-50/50 border-t-2 border-blue-100 font-bold">
                                <td class="px-3 py-3 text-blue-800 text-left border-r sticky left-0 bg-blue-50/50 z-10">Điểm TB</td>
                                <?php foreach ([10, 11, 12] as $g): foreach(['hk1','hk2'] as $s): ?>
                                <td class="p-1 border text-center"><input type="number" step="0.1" min="0" max="10" class="hvu-input-sm bg-white font-bold text-blue-700" name="grades[<?= $g ?>][<?= $s ?>][tb]" value="<?= $getVal($g, $s, 'tb') ?>"></td>
                                <?php endforeach; endforeach; ?>
                            </tr>

                             <tr class="bg-gray-50 border-t border-gray-200">
                                <td class="px-3 py-2 text-gray-700 text-left border-r sticky left-0 bg-gray-50 z-10 font-medium">Học Lực</td>
                                <?php foreach ([10, 11, 12] as $g): foreach(['hk1','hk2'] as $s): ?>
                                <td class="p-1 border text-center">
                                    <select class="hvu-input-sm font-bold" name="grades[<?= $g ?>][<?= $s ?>][hoc_luc]">
                                        <option value="">--</option>
                                        <option value="Gioi" <?= $getVal($g, $s, 'hoc_luc') == 'Gioi' ? 'selected' : '' ?>>Giỏi</option>
                                        <option value="Kha" <?= $getVal($g, $s, 'hoc_luc') == 'Kha' ? 'selected' : '' ?>>Khá</option>
                                        <option value="TrungBinh" <?= $getVal($g, $s, 'hoc_luc') == 'TrungBinh' ? 'selected' : '' ?>>TB</option>
                                        <option value="Yeu" <?= $getVal($g, $s, 'hoc_luc') == 'Yeu' ? 'selected' : '' ?>>Yếu</option>
                                    </select>
                                </td>
                                <?php endforeach; endforeach; ?>
                            </tr>

                            <tr class="bg-white border-t border-gray-200">
                                <td class="px-3 py-2 text-gray-700 text-left border-r sticky left-0 bg-white z-10 font-medium">Hạnh Kiểm</td>
                                <?php foreach ([10, 11, 12] as $g): foreach(['hk1','hk2'] as $s): ?>
                                <td class="p-1 border text-center">
                                    <select class="hvu-input-sm font-bold" name="grades[<?= $g ?>][<?= $s ?>][hanh_kiem]">
                                        <option value="">--</option>
                                        <option value="Tot" <?= $getVal($g, $s, 'hanh_kiem') == 'Tot' ? 'selected' : '' ?>>Tốt</option>
                                        <option value="Kha" <?= $getVal($g, $s, 'hanh_kiem') == 'Kha' ? 'selected' : '' ?>>Khá</option>
                                        <option value="TrungBinh" <?= $getVal($g, $s, 'hanh_kiem') == 'TrungBinh' ? 'selected' : '' ?>>TB</option>
                                        <option value="Yeu" <?= $getVal($g, $s, 'hanh_kiem') == 'Yeu' ? 'selected' : '' ?>>Yếu</option>
                                    </select>
                                </td>
                                <?php endforeach; endforeach; ?>
                            </tr>

                            <tr class="bg-gray-50">
                                <td class="px-3 py-4 text-gray-700 text-left border-r sticky left-0 bg-gray-50 z-10 font-bold italic">Minh chứng Học bạ</td>
                                <?php foreach ([10, 11, 12] as $g): ?>
                                <td colspan="2" class="p-3 border">
                                    <div class="flex flex-col space-y-3">
                                        <label class="block cursor-pointer group/upload">
                                            <div class="h-16 border-2 border-dashed border-gray-200 rounded-xl flex items-center justify-center bg-white group-hover/upload:bg-red-50 group-hover/upload:border-hvu-red transition-all">
                                                <?php 
                                                    $hasFiles = !empty($records[$g]['file_minh_chung_1']) || !empty($records[$g]['file_minh_chung_2']);
                                                    $label = $hasFiles ? 'Thay đổi file' : 'Tải lên (Tối đa 2)';
                                                    $iconClass = $hasFiles ? 'text-hvu-red' : 'text-gray-400';
                                                ?>
                                                <div class="flex items-center <?= $iconClass ?> group-hover/upload:text-hvu-red">
                                                    <i class="fas fa-image text-sm mr-2"></i>
                                                    <span class="text-[10px] font-black uppercase tracking-tight transcript-label-<?= $g ?>"><?= $label ?></span>
                                                </div>
                                                <input type="file" name="transcripts_<?= $g ?>[]" multiple accept="image/*" class="hidden" onchange="previewTranscript(this, <?= $g ?>)">
                                            </div>
                                        </label>
                                        <div class="flex justify-center space-x-2">
                                            <?php for($i=1; $i<=2; $i++): $field = "file_minh_chung_$i"; ?>
                                                <?php if (!empty($records[$g][$field])): ?>
                                                    <div class="relative group/thumb">
                                                        <div class="w-10 h-10 rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                                                            <img loading="lazy" src="<?= url($records[$g][$field]) ?>" class="w-full h-full object-cover">
                                                        </div>
                                                        <a href="<?= url($records[$g][$field]) ?>" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover/thumb:opacity-100 flex items-center justify-center text-white text-[8px] transition-opacity">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
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
                                    <th class="px-3 py-2.5 text-left" style="width:50%">Môn học</th>
                                    <th class="px-2 py-2.5 border-l border-white/50" style="width:25%">HK 1</th>
                                    <th class="px-2 py-2.5 border-l border-white/50" style="width:25%">HK 2</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <?php foreach ($subjects as $key => $name): ?>
                                <tr>
                                    <td class="px-3 py-2 font-semibold text-gray-800 text-left text-[13px]"><?= $name ?></td>
                                    <td class="p-1.5 text-center">
                                        <input type="number" step="0.1" min="0" max="10" inputmode="decimal"
                                            class="w-full text-center text-sm font-semibold rounded-lg border border-gray-200 py-2 focus:border-hvu-red focus:ring-1 focus:ring-hvu-red/30 outline-none transition"
                                            name="grades[<?= $g ?>][hk1][<?= $key ?>]" value="<?= $getVal($g, 'hk1', $key) ?>" placeholder="-">
                                    </td>
                                    <td class="p-1.5 text-center">
                                        <input type="number" step="0.1" min="0" max="10" inputmode="decimal"
                                            class="w-full text-center text-sm font-semibold rounded-lg border border-gray-200 py-2 focus:border-hvu-red focus:ring-1 focus:ring-hvu-red/30 outline-none transition"
                                            name="grades[<?= $g ?>][hk2][<?= $key ?>]" value="<?= $getVal($g, 'hk2', $key) ?>" placeholder="-">
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <!-- Điểm TB -->
                                <tr class="bg-blue-50/60 border-t-2 border-blue-200">
                                    <td class="px-3 py-2.5 font-bold text-blue-800 text-left text-[13px]">Điểm TB</td>
                                    <td class="p-1.5 text-center">
                                        <input type="number" step="0.1" min="0" max="10" inputmode="decimal"
                                            class="w-full text-center text-sm font-bold text-blue-700 rounded-lg border border-blue-200 py-2 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-300 outline-none transition"
                                            name="grades[<?= $g ?>][hk1][tb]" value="<?= $getVal($g, 'hk1', 'tb') ?>">
                                    </td>
                                    <td class="p-1.5 text-center">
                                        <input type="number" step="0.1" min="0" max="10" inputmode="decimal"
                                            class="w-full text-center text-sm font-bold text-blue-700 rounded-lg border border-blue-200 py-2 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-300 outline-none transition"
                                            name="grades[<?= $g ?>][hk2][tb]" value="<?= $getVal($g, 'hk2', 'tb') ?>">
                                    </td>
                                </tr>

                                <!-- Học Lực -->
                                <tr class="bg-gray-50/50">
                                    <td class="px-3 py-2 font-semibold text-gray-600 text-left text-[13px]">Học Lực</td>
                                    <?php foreach(['hk1','hk2'] as $s): ?>
                                    <td class="p-1.5 text-center">
                                        <select class="w-full text-center text-sm font-bold rounded-lg border border-gray-200 py-2 bg-white focus:border-hvu-red outline-none" name="grades[<?= $g ?>][<?= $s ?>][hoc_luc]">
                                            <option value="">--</option>
                                            <option value="Gioi" <?= $getVal($g, $s, 'hoc_luc') == 'Gioi' ? 'selected' : '' ?>>Giỏi</option>
                                            <option value="Kha" <?= $getVal($g, $s, 'hoc_luc') == 'Kha' ? 'selected' : '' ?>>Khá</option>
                                            <option value="TrungBinh" <?= $getVal($g, $s, 'hoc_luc') == 'TrungBinh' ? 'selected' : '' ?>>TB</option>
                                            <option value="Yeu" <?= $getVal($g, $s, 'hoc_luc') == 'Yeu' ? 'selected' : '' ?>>Yếu</option>
                                        </select>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>

                                <!-- Hạnh Kiểm -->
                                <tr>
                                    <td class="px-3 py-2 font-semibold text-gray-600 text-left text-[13px]">Hạnh Kiểm</td>
                                    <?php foreach(['hk1','hk2'] as $s): ?>
                                    <td class="p-1.5 text-center">
                                        <select class="w-full text-center text-sm font-bold rounded-lg border border-gray-200 py-2 bg-white focus:border-hvu-red outline-none" name="grades[<?= $g ?>][<?= $s ?>][hanh_kiem]">
                                            <option value="">--</option>
                                            <option value="Tot" <?= $getVal($g, $s, 'hanh_kiem') == 'Tot' ? 'selected' : '' ?>>Tốt</option>
                                            <option value="Kha" <?= $getVal($g, $s, 'hanh_kiem') == 'Kha' ? 'selected' : '' ?>>Khá</option>
                                            <option value="TrungBinh" <?= $getVal($g, $s, 'hanh_kiem') == 'TrungBinh' ? 'selected' : '' ?>>TB</option>
                                            <option value="Yeu" <?= $getVal($g, $s, 'hanh_kiem') == 'Yeu' ? 'selected' : '' ?>>Yếu</option>
                                        </select>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Upload Section for this grade -->
                        <div class="p-4 bg-gray-50 border-t border-gray-200">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2"><i class="fas fa-image mr-1 text-hvu-red"></i>Minh chứng Học bạ Lớp <?= $g ?></p>
                            <label class="block cursor-pointer">
                                <div class="h-14 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center bg-white active:bg-red-50 active:border-hvu-red transition-all">
                                    <?php 
                                        $hasFiles = !empty($records[$g]['file_minh_chung_1']) || !empty($records[$g]['file_minh_chung_2']);
                                        $mLabel = $hasFiles ? 'Thay đổi file' : 'Tải lên (Tối đa 2 ảnh)';
                                        $mIconClass = $hasFiles ? 'text-hvu-red' : 'text-gray-400';
                                    ?>
                                    <div class="flex items-center <?= $mIconClass ?>">
                                        <i class="fas fa-cloud-upload-alt mr-2"></i>
                                        <span class="text-xs font-bold uppercase tracking-tight mobile-transcript-label-<?= $g ?>"><?= $mLabel ?></span>
                                    </div>
                                    <input type="file" name="transcripts_<?= $g ?>[]" multiple accept="image/*" class="hidden" onchange="previewMobileTranscript(this, <?= $g ?>)">
                                </div>
                            </label>
                            <!-- Existing thumbnails -->
                            <?php 
                            $hasAnyFile = false;
                            for($i=1; $i<=2; $i++) { if (!empty($records[$g]["file_minh_chung_$i"])) $hasAnyFile = true; }
                            if ($hasAnyFile): ?>
                            <div class="flex gap-2 mt-2">
                                <?php for($i=1; $i<=2; $i++): $field = "file_minh_chung_$i"; ?>
                                    <?php if (!empty($records[$g][$field])): ?>
                                    <a href="<?= url($records[$g][$field]) ?>" target="_blank" class="w-12 h-12 rounded-lg border border-gray-200 overflow-hidden shadow-sm block">
                                        <img loading="lazy" src="<?= url($records[$g][$field]) ?>" class="w-full h-full object-cover">
                                    </a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
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

                <div class="bg-gray-50 px-8 py-6 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                    <a href="<?= url('/profile/step1') ?>" class="text-gray-600 hover:text-gray-900 font-bold flex items-center transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i> Quay lại
                    </a>
                    <button type="submit" class="w-full md:w-auto px-12 py-4 bg-hvu-red border-b-4 border-red-800 text-white font-black text-lg rounded-2xl shadow-xl hover:bg-red-700 hover:border-red-900 active:border-b-0 active:translate-y-1 transition-all">
                        Lưu thông tin và tiếp tục <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-4 italic text-center text-center">* Tải lên ảnh chụp Học bạ (không chấp nhận PDF) để Nhà trường đối soát.</p>
                </fieldset>
            </form>
        </div>
    </div>
</div>

<script>
function previewTranscript(input, grade) {
    const label = document.querySelector('.transcript-label-' + grade);
    if (input.files && input.files.length > 0) {
        label.textContent = 'Đã chọn: ' + input.files.length + ' tệp';
        label.classList.remove('text-gray-400');
        label.classList.add('text-hvu-red');
    }
}

function previewMobileTranscript(input, grade) {
    const label = document.querySelector('.mobile-transcript-label-' + grade);
    if (input.files && input.files.length > 0) {
        label.textContent = 'Đã chọn: ' + input.files.length + ' ảnh';
        label.parentElement.classList.remove('text-gray-400');
        label.parentElement.classList.add('text-hvu-red');
    }
}

// Tab switching for mobile grade tabs
const tabColorConfig = {
    10: { bg: 'bg-red-50', text: 'text-hvu-red', border: 'border-hvu-red' },
    11: { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-600' },
    12: { bg: 'bg-yellow-50', text: 'text-yellow-700', border: 'border-yellow-500' }
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

    // Auto-select input content on focus
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('focus', function() { this.select(); });
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
