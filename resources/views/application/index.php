<?php 
$title = 'Bảng điều khiển';
include __DIR__ . '/../layouts/header.php'; 

// Helper to check step status
$isDone = function($step) use ($stepStatus) {
    return isset($stepStatus[$step]) && $stepStatus[$step];
};

$totalSteps = $enableTHPT ? 5 : 4;

$nextStep = 1;
for($i=1; $i<=$totalSteps; $i++) {
    if (!$isDone($i)) {
        $nextStep = $i;
        break;
    }
}
?>

<div class="max-w-6xl mx-auto space-y-10 pb-20">
    
    <!-- Header Greeting -->
    <?php include __DIR__ . '/partials/header_profile.php'; ?>

    <!-- Roadmap Section -->
    <?php include __DIR__ . '/partials/roadmap.php'; ?>

</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
