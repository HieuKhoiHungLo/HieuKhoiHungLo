<!-- TAB 1: PERSONAL -->
<div id="tab_personal" class="tab-content transition-opacity duration-300">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        <!-- Left: Info (2/3) -->
        <div class="md:col-span-8 space-y-4">


            <?php include __DIR__ . '/personal/_view.php'; ?>
            <?php include __DIR__ . '/personal/_form.php'; ?>
            <?php include __DIR__ . '/personal/_status.php'; ?>
        </div>

        <!-- Right: Avatar (1/3) -->
        <div class="md:col-span-4 w-full">
            <?php include __DIR__ . '/personal/_evidence.php'; ?>
        </div>
    </div>
</div>
