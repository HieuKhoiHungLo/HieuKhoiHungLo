<!-- TAB 4: THPT -->
<div id="tab_thpt" class="tab-content hidden transition-all duration-300">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        <!-- Left: Scores (6/12) -->
        <div class="md:col-span-6 space-y-6 min-w-0">


            <?php include __DIR__ . '/thpt/_view.php'; ?>
            <?php include __DIR__ . '/thpt/_form.php'; ?>
            <?php include __DIR__ . '/thpt/_status.php'; ?>
        </div>

        <!-- Right: Evidence (6/12) -->
        <div class="md:col-span-6 space-y-6 min-w-0">
            <?php include __DIR__ . '/thpt/_evidence.php'; ?>
        </div>
    </div>
</div>