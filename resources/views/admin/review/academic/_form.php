<?php
$mapDisplay = function($v) {
    return [
        'Gioi' => 'Giỏi', 'Kha' => 'Khá', 'TrungBinh' => 'T.Bình', 'Yeu' => 'Yếu',
        'Tot' => 'Tốt'
    ][$v] ?? $v;
};
?>
<style>
    /* Hide spin buttons for input type number */
    #form_academic input::-webkit-outer-spin-button,
    #form_academic input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    #form_academic input[type=number] { -moz-appearance: textfield; }
</style>
<div id="form_academic" class="hidden animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-visible">

        <div style="padding: 2px;">

            <!-- Scores Table -->
            <div class="rounded-xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse" style="font-size: 11px;">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th style="padding: 5px 6px; text-align: left; font-weight: 700; font-size: 10px; color: #000; border-right: 1px solid #e2e8f0; width: 120px; position: sticky; left: 0; background: #f8fafc; z-index: 10;" class="">
                                    Môn học
                                </th>
                                <?php foreach ([10 => 'Lớp 10', 11 => 'Lớp 11', 12 => 'Lớp 12'] as $g => $label): ?>
                                    <th style="padding: 5px 4px; text-align: center; font-weight: 700; font-size: 10px; border-right: 1px solid #e2e8f0; color: #000;" class="last:border-r-0">
                                        <?= $label ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $code => $name): ?>
                                <tr style="border-bottom: 1px solid #e2e8f0; background: #fff;" class="hover:bg-blue-50/20 transition-colors group">
                                    <td style="padding: 5px 6px; font-weight: 400; color: #000; border-right: 1px solid #e2e8f0; position: sticky; left: 0; background: #fff; z-index: 10;" class="group-hover:bg-blue-50/20">
                                        <?= $name ?>
                                    </td>
                                    <?php foreach ([10, 11, 12] as $g):
                                        $gradeRow = $rowsByGrade[$g] ?? [];
                                        $val = is_numeric($gradeRow['diem_'.$code.'_cn']) ? number_format((float)$gradeRow['diem_'.$code.'_cn'], 3, '.', '') : ($gradeRow['diem_'.$code.'_cn'] ?? '');
                                    ?>
                                        <td style="padding: 0; text-align: center; border-right: 1px solid #e2e8f0; background: #fff;" class="last:border-r-0">
                                            <input type="number" step="0.1" min="0" max="10"
                                                name="scores[<?= $g ?>][diem_<?= $code ?>_cn]"
                                                value="<?= $val ?>"
                                                placeholder="—"
                                                style="width: 100%; height: 25px; padding: 0; text-align: center; padding-left: 0; padding-right: 0; border: 1px solid transparent; background: transparent; font-size: 11px; font-weight: 400; color: #000; outline: none; display: block; transition: all 0.2s;"
                                                class="hover:border-slate-200 focus:border-[#0066FF] focus:bg-white focus:ring-0 placeholder-slate-300">
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>

                            <!-- ĐTB chung -->
                            <tr style="background: #fff; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 5px 6px; font-weight: 500; color: #000; border-right: 1px solid #e2e8f0; position: sticky; left: 0; background: #fff; z-index: 10;">
                                    TB chung
                                </td>
                                <?php foreach ([10, 11, 12] as $g):
                                    $gradeRow = $rowsByGrade[$g] ?? [];
                                    $val = is_numeric($gradeRow['diem_tb_ca_nam']) ? number_format((float)$gradeRow['diem_tb_ca_nam'], 3, '.', '') : ($gradeRow['diem_tb_ca_nam'] ?? '');
                                ?>
                                    <td style="padding: 0; text-align: center; border-right: 1px solid #e2e8f0; background: #fff;" class="last:border-r-0">
                                        <input type="number" step="0.001" min="0" max="10"
                                            name="scores[<?= $g ?>][diem_tb_ca_nam]"
                                            value="<?= $val ?>"
                                            placeholder="—"
                                            style="width: 100%; height: 25px; padding: 0; text-align: center; border: 1px solid transparent; background: transparent; font-size: 11px; font-weight: 500; color: #000; outline: none; display: block; transition: all 0.2s;"
                                            class="hover:border-slate-200 focus:border-[#0066FF] focus:bg-white focus:ring-0 placeholder-slate-300 font-medium">
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <!-- Học lực -->
                            <tr style="background: #fff; border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 5px 6px; font-weight: 400; color: #000; border-right: 1px solid #e2e8f0; position: sticky; left: 0; background: #fff; z-index: 10;">
                                    Học lực
                                </td>
                                <?php foreach ([10, 11, 12] as $g):
                                    $gradeRow = $rowsByGrade[$g] ?? [];
                                ?>
                                    <td style="padding: 0; text-align: center; border-right: 1px solid #e2e8f0; background: #fff;" class="last:border-r-0">
                                        <select name="scores[<?= $g ?>][hoc_luc_ca_nam]"
                                            style="width: 100%; height: 26px; padding: 0; text-align: center; text-align-last: center; border: 1px solid transparent; background: transparent; font-size: 11px; font-weight: 400; color: #000; outline: none; appearance: none; cursor: pointer; display: block; transition: all 0.2s;">
                                            <option value="">—</option>
                                            <?php foreach (['Gioi', 'Kha', 'TrungBinh', 'Yeu'] as $v): ?>
                                                <option value="<?= $v ?>" <?= ($gradeRow['hoc_luc_ca_nam'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <!-- Hạnh kiểm -->
                            <tr style="background: #fff;">
                                <td style="padding: 5px 6px; font-weight: 400; color: #000; border-right: 1px solid #e2e8f0; position: sticky; left: 0; background: #fff; z-index: 10;">
                                    Hạnh kiểm
                                </td>
                                <?php foreach ([10, 11, 12] as $g):
                                    $gradeRow = $rowsByGrade[$g] ?? [];
                                ?>
                                    <td style="padding: 0; text-align: center; border-right: 1px solid #e2e8f0; background: #fff;" class="last:border-r-0">
                                        <select name="scores[<?= $g ?>][hanh_kiem_ca_nam]"
                                            style="width: 100%; height: 26px; padding: 0; text-align: center; border: 1px solid transparent; background: transparent; font-size: 11px; font-weight: 400; color: #000; outline: none; appearance: none; cursor: pointer; display: block; transition: all 0.2s;">
                                            <option value="">—</option>
                                            <?php foreach (['Tot', 'Kha', 'TrungBinh', 'Yeu'] as $v): ?>
                                                <option value="<?= $v ?>" <?= ($gradeRow['hanh_kiem_ca_nam'] ?? '') == $v ? 'selected' : '' ?>><?= $mapDisplay($v) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3.5 mt-1.5 border-t border-slate-100">
                <button type="button" onclick="toggleEdit('academic')"
                    class="px-4 py-1.5 bg-white text-slate-500 border border-slate-200 rounded-xl shadow-sm hover:bg-slate-50 transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" onclick="saveSection('academic')"
                    class="px-4 py-1.5 bg-white text-[#0066FF] border border-[#0066FF]/20 rounded-xl shadow-sm hover:bg-[#0066FF] hover:text-white transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                    <i class="fas fa-save"></i> Lưu dữ liệu
                </button>
            </div>
        </div>
    </div>
</div>