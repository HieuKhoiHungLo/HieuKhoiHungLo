<!-- TAB 4: THPT -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; width: 100%; align-items: start;">
    <!-- Left: Scores (1/2) -->
    <div style="min-width: 0;">
        <?php include __DIR__ . '/thpt/_view.php'; ?>
        <?php include __DIR__ . '/thpt/_form.php'; ?>
    </div>

    <!-- Right: Evidence (1/2) -->
    <div style="min-width: 0;">
        <?php include __DIR__ . '/thpt/_evidence.php'; ?>
    </div>
</div>