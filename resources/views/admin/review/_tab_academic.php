<?php
$provinceMap = [];
if (isset($provinces) && is_array($provinces)) {
    foreach ($provinces as $p) {
        $provinceMap[$p['ma_tinh']] = $p['ten_tinh'];
    }
}
?>
<!-- TAB 2: ACADEMIC -->
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; width: 100%; align-items: start;">
    <!-- Left: Info (1/3) -->
    <div style="min-width: 0;">
        <?php include __DIR__ . '/academic/_view.php'; ?>
        <?php include __DIR__ . '/academic/_form.php'; ?>
    </div>

    <!-- Right: Evidence (2/3) -->
    <div style="min-width: 0;" class="space-y-4">
        <?php include __DIR__ . '/academic/_evidence.php'; ?>
    </div>
</div>