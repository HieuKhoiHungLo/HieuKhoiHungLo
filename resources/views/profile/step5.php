<?php 
$title = 'Đăng ký xét tuyển';
include __DIR__ . '/../layouts/header.php'; 
?>

<div class="max-w-6xl mx-auto pb-20 px-4 sm:px-6">
    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-red-900/5 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-hvu-red to-red-700 p-6 text-white text-center">
            <h2 class="text-2xl font-bold uppercase tracking-wide">Đăng ký nguyện vọng xét tuyển</h2>
            <p class="text-white/80 text-sm mt-1">Bước <?= $enableTHPTSetting ? '5' : '4' ?>/<?= $totalStepsCount ?? ($enableTHPTSetting ? 5 : 4) ?>: Chọn ngành và phương thức xét tuyển</p>
        </div>

        <!-- Wizard Navigation -->
        <div class="bg-gray-100 px-6 py-4 border-b flex justify-between items-center text-xs md:text-sm font-semibold overflow-x-auto">
           <a href="<?= url('/profile/step1') ?>" class="text-green-600 flex flex-col items-center min-w-fit px-2 hover:text-green-700 transition-colors">
               <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></span>
                <span class="hidden sm:block">Hồ sơ</span>
           </a>
           <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
           <a href="<?= url('/profile/step2') ?>" class="text-green-600 flex flex-col items-center min-w-fit px-2 hover:text-green-700 transition-colors">
                 <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></span>
                <span class="hidden sm:block">Học bạ</span>
           </a>
            <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
           <a href="<?= url('/profile/step3') ?>" class="text-green-600 flex flex-col items-center min-w-fit px-2 hover:text-green-700 transition-colors">
                 <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></path></svg></span>
                <span class="hidden sm:block">Chứng chỉ quốc tế</span>
           </a>

           <?php if ($enableTHPTSetting): ?>
               <div class="text-gray-300 mx-2 flex-1 border-t-2 border-green-200"></div>
               <a href="<?= url('/profile/step4') ?>" class="text-green-600 flex flex-col items-center min-w-fit px-2 hover:text-green-700 transition-colors">
                    <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></span>
                    <span class="hidden sm:block">Điểm thi</span>
               </a>
           <?php endif; ?>

           <div class="text-gray-300 mx-2 flex-1 border-t-2 border-hvu-red"></div>
           <div class="text-hvu-red flex flex-col items-center min-w-fit px-2">
               <span class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mb-1 border-2 border-hvu-red font-bold"><?= $enableTHPTSetting ? 5 : 4 ?></span>
                <span class="hidden sm:block">Nguyện vọng</span>
           </div>
        </div>

        <div class="p-6">
            <?php if (!empty($error)): ?>
                <div class="bg-red-50 border-l-4 border-hvu-red text-red-700 p-4 mb-6 rounded shadow-sm flex items-start">
                     <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span><?= (string) ($error ?? "") ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/profile/step5?id=' . $applicationId) ?>" id="choicesForm">
                <input type="hidden" name="csrf_token" value="<?= (string) $this->csrfToken() ?>">
                
                <?php if (!empty($isLocked) && (in_array($applicationStatus ?? '', ['Đã duyệt', 'approved', 'DaDuyet']))): ?>
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

                <fieldset <?= (!empty($isLocked)) ? 'disabled' : '' ?> class="contents group/locked">
                <!-- ========== DESKTOP VIEW: Table (hidden on mobile) ========== -->
                <div class="hidden md:block overflow-x-auto rounded-lg border border-gray-200 mb-6" id="desktopChoices">
                    <table class="w-full text-left border-collapse" id="choicesTable">
                        <thead class="bg-gray-100 text-gray-700 uppercase leading-normal text-xs font-bold">
                            <tr>
                                <th class="py-3 px-4 text-center w-16">TT</th>
                                <th class="py-3 px-4">Ngành xét tuyển</th>
                                <th class="py-3 px-4 w-24">Mã trường</th>
                                <th class="py-3 px-4 w-60">Các tổ hợp xét tuyển</th>
                                <th class="py-3 px-4 w-16 text-center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm">
                            <?php if (empty($choices)): ?>
                                <tr class="empty-row border-b border-gray-100">
                                    <td colspan="5" class="py-8 text-center text-gray-400 italic">Chưa có nguyện vọng nào. Vui lòng bấm "Thêm nguyện vọng" bên dưới.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($choices as $index => $choice): ?>
                                    <tr class="choice-row border-b border-gray-100 hover:bg-gray-50 transition-colors" data-index="<?= $index ?>">
                                        <td class="py-4 px-4 text-center font-bold text-gray-400"><?= $index + 1 ?></td>
                                        <td class="py-4 px-4">
                                            <input type="hidden" name="choices[<?= $index ?>][thu_tu]" value="<?= $index + 1 ?>">
                                            <select name="choices[<?= $index ?>][nganh_id]" onchange="updateCombinationText(this, <?= $index ?>)" class="w-full h-10 border border-gray-200 rounded-lg px-2 focus:border-hvu-red outline-none bg-white font-bold" required>
                                                <option value="">-- Chọn ngành --</option>
                                                <?php foreach ($majors as $m): ?>
                                                    <option value="<?= $m['ma_nganh'] ?>" <?= $choice['ma_nganh'] == $m['ma_nganh'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($m['ten_nganh']) ?> (<?= $m['ma_nganh'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="py-4 px-4">
                                            <input type="text" value="THV" class="w-full h-10 bg-gray-50 border border-gray-200 rounded-lg px-2 text-center font-bold" readonly>
                                        </td>
                                        <td class="py-4 px-4 text-xs font-medium text-gray-500">
                                            <span id="combo-text-<?= $index ?>" class="block">
                                                <?php 
                                                    $comboStrDesktop = '';
                                                    $matchedMajorDesktop = null;
                                                    foreach($majors as $m) {
                                                        if(isset($choice['ma_nganh']) && $m['ma_nganh'] == $choice['ma_nganh']) {
                                                            $comboStrDesktop = $m['to_hop_xet_tuyen'] ?? '';
                                                            $matchedMajorDesktop = $m;
                                                            break;
                                                        }
                                                    }
                                                    if ($comboStrDesktop): 
                                                        $combosD = array_map('trim', explode(',', $comboStrDesktop));
                                                ?>
                                                    <div class="flex flex-wrap items-center justify-center gap-1.5 mb-1.5">
                                                        <?php foreach ($combosD as $c): ?>
                                                            <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-bold rounded shadow-sm border border-gray-200"><?= htmlspecialchars($c) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <?php 
                                                        // Render badge điều kiện trực tiếp từ PHP
                                                        if ($matchedMajorDesktop && (!empty($matchedMajorDesktop['nguong_hoc_luc']) || !empty($matchedMajorDesktop['nguong_diem_thpt']))) {
                                                            $nhomLabels = ['SuPham' => 'Sư phạm', 'SuPhamDacThu' => 'SP Đặc thù', 'DieuDuong' => 'Điều dưỡng'];
                                                            $hlLabels = ['Gioi' => 'Giỏi', 'Kha' => 'Khá'];
                                                            $nhom = $matchedMajorDesktop['nhom_nganh'] ?? '';
                                                            $nhomLabel = $nhomLabels[$nhom] ?? '';
                                                            $parts = [];
                                                            if (!empty($matchedMajorDesktop['nguong_hoc_luc'])) {
                                                                $hl = $hlLabels[$matchedMajorDesktop['nguong_hoc_luc']] ?? $matchedMajorDesktop['nguong_hoc_luc'];
                                                                $parts[] = 'HL lớp 12 ≥ ' . $hl;
                                                            }
                                                            if (!empty($matchedMajorDesktop['nguong_diem_thpt'])) {
                                                                $parts[] = 'Tổng ĐThi ≥ ' . number_format((float)$matchedMajorDesktop['nguong_diem_thpt'], 1);
                                                            }
                                                            echo '<div class="mt-2 flex flex-col md:flex-row items-center justify-center gap-1.5">';
                                                            if ($nhomLabel) echo '<span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[11px] font-bold rounded whitespace-nowrap">' . htmlspecialchars($nhomLabel) . '</span>';
                                                            echo '<span class="px-2 py-0.5 bg-red-50 text-red-600 text-[11px] font-bold rounded border border-red-200 whitespace-nowrap">⚡ ' . htmlspecialchars(implode(' | ', $parts)) . '</span>';
                                                            echo '</div>';
                                                        }
                                                    ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            <button type="button" onclick="removeChoice(this)" class="text-gray-300 hover:text-red-500 transition-colors">
                                                <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ========== MOBILE VIEW: Cards (hidden on desktop) ========== -->
                <div class="md:hidden mb-6 space-y-4" id="mobileChoices">
                    <?php if (empty($choices)): ?>
                        <div class="mobile-empty-state py-10 text-center text-gray-400 italic border-2 border-dashed border-gray-200 rounded-2xl">
                            <i class="fas fa-clipboard-list text-3xl mb-2 text-gray-300"></i>
                            <p class="text-sm">Chưa có nguyện vọng nào.</p>
                            <p class="text-xs mt-1">Bấm "Thêm nguyện vọng" bên dưới.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($choices as $index => $choice): ?>
                        <div class="choice-card bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden" data-index="<?= $index ?>">
                            <!-- Card Header -->
                            <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100">
                                <div class="flex items-center">
                                    <span class="w-8 h-8 rounded-full bg-hvu-red text-white text-sm font-black flex items-center justify-center mr-3 card-order"><?= $index + 1 ?></span>
                                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Nguyện vọng <?= $index + 1 ?></span>
                                </div>
                                <button type="button" onclick="removeChoiceMobile(this)" class="w-8 h-8 rounded-full bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-all">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                            <!-- Card Body -->
                            <div class="p-4 space-y-3">
                                <input type="hidden" name="choices[<?= $index ?>][thu_tu]" value="<?= $index + 1 ?>">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Ngành xét tuyển</label>
                                    <select name="choices[<?= $index ?>][nganh_id]" onchange="updateMobileCombo(this, <?= $index ?>)" class="w-full h-12 border border-gray-200 rounded-xl px-3 focus:border-hvu-red focus:ring-2 focus:ring-hvu-red/10 outline-none bg-white font-bold text-sm" required>
                                        <option value="">-- Chọn ngành --</option>
                                        <?php foreach ($majors as $m): ?>
                                            <option value="<?= $m['ma_nganh'] ?>" <?= $choice['ma_nganh'] == $m['ma_nganh'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($m['ten_nganh']) ?> (<?= $m['ma_nganh'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                                        <span class="text-[10px] text-gray-400 font-bold uppercase mr-2">Mã trường:</span>
                                        <span class="font-black text-gray-800 text-sm">THV</span>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Tổ hợp xét tuyển:</span>
                                    <div class="mt-1.5" id="mobile-combo-<?= $index ?>">
                                        <?php 
                                            $comboStr = '';
                                            $matchedMajorMobile = null;
                                            foreach($majors as $m) {
                                                if(isset($choice['ma_nganh']) && $m['ma_nganh'] == $choice['ma_nganh']) {
                                                    $comboStr = $m['to_hop_xet_tuyen'] ?? '';
                                                    $matchedMajorMobile = $m;
                                                    break;
                                                }
                                            }
                                            if ($comboStr): 
                                                $combos = array_map('trim', explode(',', $comboStr));
                                        ?>
                                            <div class="flex flex-wrap gap-1.5">
                                                <?php foreach ($combos as $c): ?>
                                                    <span class="inline-block px-2.5 py-1 bg-red-50 text-hvu-red text-xs font-bold rounded-lg"><?= htmlspecialchars($c) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php 
                                                // Render badge điều kiện trực tiếp từ PHP
                                                if ($matchedMajorMobile && (!empty($matchedMajorMobile['nguong_hoc_luc']) || !empty($matchedMajorMobile['nguong_diem_thpt']))) {
                                                    $nhomLabels = ['SuPham' => 'Sư phạm', 'SuPhamDacThu' => 'SP Đặc thù', 'DieuDuong' => 'Điều dưỡng'];
                                                    $hlLabels = ['Gioi' => 'Giỏi', 'Kha' => 'Khá'];
                                                    $nhom = $matchedMajorMobile['nhom_nganh'] ?? '';
                                                    $nhomLabel = $nhomLabels[$nhom] ?? '';
                                                    $parts = [];
                                                    if (!empty($matchedMajorMobile['nguong_hoc_luc'])) {
                                                        $hl = $hlLabels[$matchedMajorMobile['nguong_hoc_luc']] ?? $matchedMajorMobile['nguong_hoc_luc'];
                                                        $parts[] = 'HL lớp 12 ≥ ' . $hl;
                                                    }
                                                    if (!empty($matchedMajorMobile['nguong_diem_thpt'])) {
                                                        $parts[] = 'Tổng ĐThi ≥ ' . number_format((float)$matchedMajorMobile['nguong_diem_thpt'], 1);
                                                    }
                                                    echo '<div class="mt-2 flex flex-wrap items-center gap-1.5">';
                                                    if ($nhomLabel) echo '<span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[11px] font-bold rounded">' . htmlspecialchars($nhomLabel) . '</span>';
                                                    echo '<span class="px-2 py-0.5 bg-red-50 text-red-600 text-[11px] font-bold rounded border border-red-200">⚡ ' . htmlspecialchars(implode(' | ', $parts)) . '</span>';
                                                    echo '</div>';
                                                }
                                            ?>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-300 italic">Chọn ngành để xem tổ hợp</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-3">
                    <button type="button" onclick="addChoice()" class="flex items-center text-hvu-red font-black uppercase tracking-widest text-xs hover:text-red-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Thêm nguyện vọng
                    </button>
                    <span class="text-xs text-gray-400 italic">* Sắp xếp nguyện vọng theo thứ tự ưu tiên giảm dần.</span>
                </div>

                <div class="p-6 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                    <a href="<?= $enableTHPTSetting ? url('/profile/step4') : url('/profile/step3') ?>" class="text-gray-600 hover:text-gray-900 font-bold flex items-center transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i> Quay lại
                    </a>
                    <button type="submit" class="w-full md:w-auto px-12 py-4 bg-hvu-red border-b-4 border-red-800 text-white font-black text-lg rounded-2xl shadow-xl hover:bg-red-700 hover:border-red-900 active:border-b-0 active:translate-y-1 transition-all">
                        Lưu thông tin và hoàn tất <i class="fas fa-check-circle ml-2"></i>
                    </button>
                </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>

<!-- Desktop Row Template -->
<template id="choiceRowTemplate">
    <tr class="choice-row border-b border-gray-100 hover:bg-gray-50 transition-colors" data-index="INDEX_MINUS_1">
        <td class="py-4 px-4 text-center font-bold text-gray-400">INDEX</td>
        <td class="py-4 px-4">
            <input type="hidden" name="choices[INDEX_MINUS_1][thu_tu]" value="INDEX">
            <select name="choices[INDEX_MINUS_1][nganh_id]" onchange="updateCombinationText(this, INDEX_MINUS_1)" class="w-full h-10 border border-gray-200 rounded-lg px-2 focus:border-hvu-red outline-none bg-white font-bold" required>
                <option value="">-- Chọn ngành --</option>
                <?php foreach ($majors as $m): ?>
                    <option value="<?= $m['ma_nganh'] ?>"><?= htmlspecialchars($m['ten_nganh']) ?> (<?= $m['ma_nganh'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="py-4 px-4">
            <input type="text" value="THV" class="w-full h-10 bg-gray-50 border border-gray-200 rounded-lg px-2 text-center font-bold" readonly>
        </td>
        <td class="py-4 px-4 text-xs font-medium text-gray-500">
            <span id="combo-text-INDEX_MINUS_1"></span>
        </td>
        <td class="py-4 px-4 text-center">
            <button type="button" onclick="removeChoice(this)" class="text-gray-300 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
        </td>
    </tr>
</template>

<!-- Mobile Card Template -->
<template id="mobileCardTemplate">
    <div class="choice-card bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden" data-index="INDEX_MINUS_1">
        <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100">
            <div class="flex items-center">
                <span class="w-8 h-8 rounded-full bg-hvu-red text-white text-sm font-black flex items-center justify-center mr-3 card-order">INDEX</span>
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Nguyện vọng INDEX</span>
            </div>
            <button type="button" onclick="removeChoiceMobile(this)" class="w-8 h-8 rounded-full bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-all">
                <i class="fas fa-trash-alt text-xs"></i>
            </button>
        </div>
        <div class="p-4 space-y-3">
            <input type="hidden" name="choices[INDEX_MINUS_1][thu_tu]" value="INDEX">
            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Ngành xét tuyển</label>
                <select name="choices[INDEX_MINUS_1][nganh_id]" onchange="updateMobileCombo(this, INDEX_MINUS_1)" class="w-full h-12 border border-gray-200 rounded-xl px-3 focus:border-hvu-red focus:ring-2 focus:ring-hvu-red/10 outline-none bg-white font-bold text-sm" required>
                    <option value="">-- Chọn ngành --</option>
                    <?php foreach ($majors as $m): ?>
                        <option value="<?= $m['ma_nganh'] ?>"><?= htmlspecialchars($m['ten_nganh']) ?> (<?= $m['ma_nganh'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                    <span class="text-[10px] text-gray-400 font-bold uppercase mr-2">Mã trường:</span>
                    <span class="font-black text-gray-800 text-sm">THV</span>
                </div>
            </div>
            <div>
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Tổ hợp xét tuyển:</span>
                <div class="mt-1.5" id="mobile-combo-INDEX_MINUS_1">
                    <span class="text-xs text-gray-300 italic">Chọn ngành để xem tổ hợp</span>
                </div>
            </div>
        </div>
    </div>
</template>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
const majorCombinations = {};
const majorThresholds = {};
<?php foreach ($majors as $m): ?>
    majorCombinations['<?= $m['ma_nganh'] ?>'] = '<?= htmlspecialchars($m['to_hop_xet_tuyen'] ?? '') ?>';
    <?php if (!empty($m['nguong_hoc_luc']) || !empty($m['nguong_diem_thpt'])): ?>
    majorThresholds['<?= $m['ma_nganh'] ?>'] = {
        nhom: '<?= $m['nhom_nganh'] ?? 'Khac' ?>',
        hocLuc: '<?= $m['nguong_hoc_luc'] ?? '' ?>',
        diemThpt: <?= empty($m['nguong_diem_thpt']) ? 'null' : (float)$m['nguong_diem_thpt'] ?>
    };
    <?php endif; ?>
<?php endforeach; ?>

function getThresholdBadge(majorId, isDesktop = false) {
    const t = majorThresholds[majorId];
    if (!t) return '';
    const hlLabels = {'Gioi':'Giỏi','Kha':'Khá'};
    const nhomLabels = {'SuPham':'Sư phạm','SuPhamDacThu':'SP Đặc thù','DieuDuong':'Điều dưỡng'};
    let parts = [];
    if (t.hocLuc) parts.push('HL lớp 12 ≥ ' + (hlLabels[t.hocLuc]||t.hocLuc));
    if (t.diemThpt) parts.push('Tổng ĐThi ≥ ' + t.diemThpt.toFixed(1));
    const nhomLabel = nhomLabels[t.nhom] || '';
    
    if (isDesktop) {
        return `<div class="mt-2 flex flex-col md:flex-row items-center justify-center gap-1.5">`
            + (nhomLabel ? `<span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[11px] font-bold rounded whitespace-nowrap">${nhomLabel}</span>` : '')
            + `<span class="px-2 py-0.5 bg-red-50 text-red-600 text-[11px] font-bold rounded border border-red-200 whitespace-nowrap">⚡ ${parts.join(' | ')}</span></div>`;
    } else {
        return `<div class="mt-2 flex flex-wrap items-center gap-1.5">`
            + (nhomLabel ? `<span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[11px] font-bold rounded">${nhomLabel}</span>` : '')
            + `<span class="px-2 py-0.5 bg-red-50 text-red-600 text-[11px] font-bold rounded border border-red-200">⚡ ${parts.join(' | ')}</span></div>`;
    }
}

function updateOrders() {
    // Desktop rows
    const rows = document.querySelectorAll('#choicesTable .choice-row');
    rows.forEach((row, index) => {
        row.setAttribute('data-index', index);
        row.querySelector('td:first-child').textContent = index + 1;
        row.querySelector('input[type="hidden"]').value = index + 1;
        row.querySelector('input[type="hidden"]').name = `choices[${index}][thu_tu]`;
        const select = row.querySelector('select');
        select.name = `choices[${index}][nganh_id]`;
        select.setAttribute('onchange', `updateCombinationText(this, ${index})`);
        const combo = row.querySelector('td:nth-child(4) span');
        if (combo) combo.id = `combo-text-${index}`;
    });
    // Mobile cards
    const cards = document.querySelectorAll('#mobileChoices > .choice-card');
    cards.forEach((card, index) => {
        card.setAttribute('data-index', index);
        card.querySelector('.card-order').textContent = index + 1;
        const label = card.querySelector('.text-xs.font-bold.text-gray-500');
        if (label) label.textContent = `Nguyện vọng ${index + 1}`;
        card.querySelector('input[type="hidden"]').value = index + 1;
        card.querySelector('input[type="hidden"]').name = `choices[${index}][thu_tu]`;
        const select = card.querySelector('select');
        select.name = `choices[${index}][nganh_id]`;
        select.setAttribute('onchange', `updateMobileCombo(this, ${index})`);
        const comboDiv = card.querySelector('[id^="mobile-combo-"]');
        if (comboDiv) comboDiv.id = `mobile-combo-${index}`;
    });
}

function updateCombinationText(select, index) {
    const majorId = select.value;
    const comboText = document.getElementById('combo-text-' + index);
    if (majorId && majorCombinations[majorId]) {
        const combos = majorCombinations[majorId].split(',').map(c => c.trim()).filter(Boolean);
        comboText.innerHTML = '<div class="flex flex-wrap items-center justify-center gap-1.5 mb-1.5">' +
            combos.map(c => `<span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-bold rounded shadow-sm border border-gray-200">${c}</span>`).join('') +
            '</div>' + getThresholdBadge(majorId, true);
    } else {
        comboText.innerHTML = '';
    }
}

function updateMobileCombo(select, index) {
    const majorId = select.value;
    const comboDiv = document.getElementById('mobile-combo-' + index);
    if (majorId && majorCombinations[majorId]) {
        const combos = majorCombinations[majorId].split(',').map(c => c.trim()).filter(Boolean);
        comboDiv.innerHTML = '<div class="flex flex-wrap gap-1.5">' +
            combos.map(c => `<span class="inline-block px-2.5 py-1 bg-red-50 text-hvu-red text-xs font-bold rounded-lg">${c}</span>`).join('') +
            '</div>' + getThresholdBadge(majorId, false);
    } else {
        comboDiv.innerHTML = '<span class="text-xs text-gray-300 italic">Chọn ngành để xem tổ hợp</span>';
    }
}

function addChoice() {
    const currentRows = document.querySelectorAll('#choicesTable .choice-row');
    const currentCards = document.querySelectorAll('#mobileChoices > .choice-card');

    if (currentRows.length >= 6 || currentCards.length >= 6) {
        alert('Bạn chỉ được đăng ký tối đa 6 nguyện vọng.');
        return;
    }

    // Remove empty states
    document.querySelector('.empty-row')?.remove();
    document.querySelector('.mobile-empty-state')?.remove();

    const nextIndex = Math.max(currentRows.length, currentCards.length) + 1;
    const nextIndexMinus1 = nextIndex - 1;

    // Add desktop row — use insertAdjacentHTML to avoid nested <tr> wrapping
    const rowTemplate = document.querySelector('#choiceRowTemplate').innerHTML;
    let rowHtml = rowTemplate.replace(/INDEX_MINUS_1/g, nextIndexMinus1).replace(/INDEX/g, nextIndex);
    const tbody = document.querySelector('#choicesTable tbody');
    tbody.insertAdjacentHTML('beforeend', rowHtml);

    // Add mobile card — use insertAdjacentHTML to avoid nested .choice-card wrapping
    const cardTemplate = document.querySelector('#mobileCardTemplate').innerHTML;
    let cardHtml = cardTemplate.replace(/INDEX_MINUS_1/g, nextIndexMinus1).replace(/INDEX/g, nextIndex);
    const mobileContainer = document.getElementById('mobileChoices');
    mobileContainer.insertAdjacentHTML('beforeend', cardHtml);

    updateOrders();
    syncActiveView();
}

function removeChoice(btn) {
    const row = btn.closest('tr');
    const index = row.getAttribute('data-index');
    row.remove();
    // Also remove matching mobile card
    const card = document.querySelector(`#mobileChoices > .choice-card[data-index="${index}"]`);
    if (card) card.remove();
    updateOrders();
    checkEmpty();
}

function removeChoiceMobile(btn) {
    const card = btn.closest('.choice-card');
    const index = card.getAttribute('data-index');
    card.remove();
    // Also remove matching desktop row
    const row = document.querySelector(`#choicesTable .choice-row[data-index="${index}"]`);
    if (row) row.remove();
    updateOrders();
    checkEmpty();
}

function checkEmpty() {
    if (document.querySelectorAll('#choicesTable .choice-row').length === 0) {
        const tbody = document.querySelector('#choicesTable tbody');
        tbody.innerHTML = `<tr class="empty-row border-b border-gray-100">
            <td colspan="5" class="py-8 text-center text-gray-400 italic">Chưa có nguyện vọng nào. Vui lòng bấm "Thêm nguyện vọng" bên dưới.</td>
        </tr>`;
    }
    if (document.querySelectorAll('#mobileChoices > .choice-card').length === 0) {
        document.getElementById('mobileChoices').innerHTML = `<div class="mobile-empty-state py-10 text-center text-gray-400 italic border-2 border-dashed border-gray-200 rounded-2xl">
            <i class="fas fa-clipboard-list text-3xl mb-2 text-gray-300"></i>
            <p class="text-sm">Chưa có nguyện vọng nào.</p>
            <p class="text-xs mt-1">Bấm "Thêm nguyện vọng" bên dưới.</p>
        </div>`;
    }
}

// Disable inputs in the view that is NOT visible (prevent required validation on hidden fields)
function syncActiveView() {
    const isMobile = window.innerWidth < 768;
    const desktopView = document.getElementById('desktopChoices');
    const mobileView = document.getElementById('mobileChoices');

    if (isMobile) {
        desktopView.querySelectorAll('input, select').forEach(el => el.disabled = true);
        mobileView.querySelectorAll('input, select').forEach(el => el.disabled = false);
    } else {
        mobileView.querySelectorAll('input, select').forEach(el => el.disabled = true);
        desktopView.querySelectorAll('input, select').forEach(el => el.disabled = false);
    }
}

// Run on page load
syncActiveView();

// Bỏ init JS on load để ưu tiên render server-side cho các lựa chọn đã lưu trong DB
// Chỉ render cập nhật bằng JS khi user thực hiện OnChange

// Run on resize (in case user rotates device)
window.addEventListener('resize', syncActiveView);

// Duplicate major validation on form submit
document.getElementById('choicesForm').addEventListener('submit', function(e) {
    // Ensure correct view is active
    syncActiveView();

    const isMobile = window.innerWidth < 768;
    const activeView = isMobile
        ? document.getElementById('mobileChoices')
        : document.getElementById('desktopChoices');
    const selects = activeView.querySelectorAll('select[name*="nganh_id"]:not([disabled])');
    const selectedMajors = [];
    
    for (const select of selects) {
        if (select.value) {
            if (selectedMajors.includes(select.value)) {
                e.preventDefault();
                alert('Bạn không được đăng ký trùng ngành. Vui lòng chọn các ngành khác nhau.');
                select.focus();
                return false;
            }
            selectedMajors.push(select.value);
        }
    }
});
</script>
