<!-- TAB 3: CERTIFICATES -->
<div id="tab_certs" class="tab-content hidden transition-all duration-300">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        <!-- Left: List (6/12) -->
        <div class="md:col-span-6 space-y-6 min-w-0">


            <?php include __DIR__ . '/certs/_view.php'; ?>
            <?php include __DIR__ . '/certs/_form.php'; ?>
            <?php include __DIR__ . '/certs/_status.php'; ?>
        </div>

        <!-- Right Column (6/12): Evidence Preview -->
        <div class="md:col-span-6 w-full min-w-0">
            <?php include __DIR__ . '/certs/_evidence.php'; ?>
        </div>
    </div>
</div>