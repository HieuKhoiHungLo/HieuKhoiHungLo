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

    <!-- Admission Letter Lookup Banner -->
    <?php
    // Kiểm tra thí sinh có trong danh sách trúng tuyển không
    $cccdForLookup = $_SESSION['cccd'] ?? '';
    $hasAdmissionLetter = false;
    if ($cccdForLookup) {
        $dbCheck = \App\Core\Database::getInstance()->getConnection();
        $chkStmt = $dbCheck->prepare("SELECT COUNT(*) FROM thu_trung_tuyen WHERE so_cccd = ? LIMIT 1");
        $chkStmt->execute([$cccdForLookup]);
        $hasAdmissionLetter = $chkStmt->fetchColumn() > 0;
    }
    ?>
    <?php if ($hasAdmissionLetter): ?>
    <div class="relative overflow-hidden rounded-3xl shadow-2xl" id="admissionBanner">
        <!-- Animated gradient background -->
        <div class="absolute inset-0 bg-gradient-to-r from-red-700 via-red-600 to-orange-500" style="background-size:200% 200%; animation: gradientShift 4s ease infinite;"></div>
        <div class="absolute inset-0 opacity-10" style="background-image: repeating-linear-gradient(45deg, white 0, white 1px, transparent 0, transparent 50%); background-size: 20px 20px;"></div>
        <!-- Floating orb decoration -->
        <div class="absolute -top-10 -right-10 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-orange-300/20 rounded-full blur-3xl"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6 p-6 md:p-8">
            <div class="flex items-center gap-5">
                <!-- Bell icon with pulse -->
                <div class="relative flex-shrink-0">
                    <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center border border-white/30">
                        <i class="fas fa-graduation-cap text-white text-2xl"></i>
                    </div>
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-yellow-400 rounded-full border-2 border-red-600" style="animation: ping 1.5s cubic-bezier(0,0,0.2,1) infinite;"></span>
                </div>
                <div>
                    <div class="text-yellow-300 text-xs font-black uppercase tracking-widest mb-1">📢 Thông báo quan trọng</div>
                    <h2 class="text-white font-black text-xl leading-tight">Bạn có thông báo TRÚNG TUYỂN!</h2>
                    <p class="text-white/80 text-sm mt-1 font-medium">Xem đầy đủ nội dung thông báo ghi danh tuyển sinh ngay tại đây nếu email bị chặn.</p>
                </div>
            </div>
            <a href="<?= url('/tra-cuu-trung-tuyen') ?>?q=<?= urlencode($cccdForLookup) ?>"
               class="flex-shrink-0 inline-flex items-center gap-3 px-7 py-4 bg-white text-red-700 font-black rounded-2xl shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all text-sm whitespace-nowrap">
                <i class="fas fa-envelope-open-text text-lg"></i>
                Xem thông báo của tôi
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
    <style>
        @keyframes gradientShift { 0%,100%{background-position:0% 50%} 50%{background-position:100% 50%} }
        @keyframes ping { 75%,100%{transform:scale(2);opacity:0} }
    </style>
    <?php endif; ?>

    <!-- Roadmap Section -->
    <?php include __DIR__ . '/partials/roadmap.php'; ?>

    <!-- History / Other Applications -->
    <?php include __DIR__ . '/partials/history.php'; ?>

</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
