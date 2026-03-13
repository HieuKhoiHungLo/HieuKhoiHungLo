<?php
$title = 'Tuyển sinh Đại học Hùng Vương 2026 - Khởi đầu vững chắc';
include __DIR__ . '/layouts/header.php';
?>

<!-- Scrolling Announcement Bar -->
<?php if (!empty($homeSettings['announcement'])): ?>
    <div class="w-full text-white py-0 overflow-hidden relative z-40 border-b border-white/5" style="background-color: #8b0000;">
        <div class="flex items-center px-0 max-w-full mx-auto h-12">
            <!-- Red Slanted Clock Section -->
            <div id="live-clock-container" class="relative px-8 h-full flex items-center justify-center shadow-lg z-20" style="background-color: #9d1926; clip-path: polygon(0 0, 100% 0, 85% 100%, 0% 100%);">
                <div class="flex flex-col items-center pt-1" style="border-top: 1px solid rgba(255,255,255,0.3);">
                    <span id="live-clock" class="text-xs md:text-sm font-black uppercase tracking-widest whitespace-nowrap min-w-[160px] text-center leading-none tabular-nums font-mono" style="color: #ffd700;">
                        <?= date('d/m/Y H:i:s') ?>
                    </span>
                </div>
                <!-- Slanted overlay to fix clip-path edge -->
                <div class="absolute top-0 bottom-0 right-0 w-8 transform translate-x-1/2 -skew-x-[25deg]" style="background-color: #9d1926;"></div>
            </div>

            <div class="marquee-container flex-1 overflow-hidden ml-2 pr-6 relative">
                <div class="marquee-content whitespace-nowrap inline-block py-1 text-sm md:text-base font-bold tracking-tight drop-shadow-sm" style="color: #ffffff;">
                    <span class="inline-block"><?= htmlspecialchars($homeSettings['announcement']) ?></span>
                    <span class="mx-32" style="color: #ffd700;">★</span>
                    <span class="inline-block"><?= htmlspecialchars($homeSettings['announcement']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <script>
        function updateClock() {
            const now = new Date();
            const d = String(now.getDate()).padStart(2, '0');
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const y = now.getFullYear();
            const h = String(now.getHours()).padStart(2, '0');
            const min = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            const clockEl = document.getElementById('live-clock');
            if (clockEl) {
                clockEl.textContent = `${d}/${m}/${y} ${h}:${min}:${s}`;
            }
        }
        setInterval(updateClock, 1000);
    </script>
    <style>
        .marquee-content {
            display: inline-block;
            animation: marquee 120s linear infinite;
            padding-left: 100%;
        }

        .marquee-container:hover .marquee-content {
            animation-play-state: paused;
        }

        @keyframes marquee {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        /* Mobile adjustment */
        @media (max-width: 768px) {
            .marquee-content {
                animation-duration: 80s;
            }

            #live-clock-container {
                min-width: 140px;
                padding-left: 1rem;
                padding-right: 2rem;
            }

            #live-clock {
                min-width: 120px;
                font-size: 10px;
            }
        }
    </style>
<?php endif; ?>

<!-- Helper logic (unchanged) -->
<?php
$isDone = function ($step) use ($stepStatus) {
    return isset($stepStatus[$step]) && $stepStatus[$step];
};
$totalSteps = $enableTHPT ? 5 : 4;
$nextStep = 1;
if (isset($stepStatus)) {
    $allDone = true;
    for ($i = 1; $i <= $totalSteps; $i++) {
        if (!$isDone($i)) {
            $nextStep = $i;
            $allDone = false;
            break;
        }
    }
    if ($allDone) {
        $nextStep = $totalSteps + 1;
    }
}
?>

<!-- Hero Section (Only for Guests) -->
<?php if (!isset($_SESSION['user_id'])): ?>
    <div class="relative bg-gradient-to-br from-red-900 via-hvu-red to-red-800 text-white overflow-hidden">
        <!-- Abstract Background -->
        <div class="absolute inset-0 opacity-20" style="background-image: url('https://www.transparenttextures.com/patterns/cubes.png');"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-hvu-gold rounded-full blur-3xl opacity-20 animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-gray-50 to-transparent"></div>

        <div class="container mx-auto px-4 py-8 md:py-12 relative z-10 flex flex-col md:flex-row items-center gap-8 md:gap-12">
            <!-- Hero Content -->
            <div class="md:w-1/2 space-y-6 text-center md:text-left">
                <span class="inline-block px-4 py-1.5 bg-white/10 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-widest border border-white/20">
                    ⭐ Tuyển sinh Đại học Chính quy <?= date('Y') ?>
                </span>
                <h1 class="text-3xl md:text-6xl font-black font-heading leading-tight">
                    KHÁT VỌNG <br />
                    <span class="text-hvu-gold text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-yellow-500">VƯƠN XA</span>
                </h1>
                <p class="text-lg text-red-100 font-medium max-w-lg mx-auto md:mx-0 leading-relaxed">
                    Chào mừng bạn đến với Cổng thông tin Tuyển sinh Đại học Hùng Vương.
                    Nơi khởi đầu cho hành trình chinh phục tri thức và tương lai.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start pt-4">
                    <a href="<?= url('/register') ?>" class="px-8 py-4 bg-hvu-gold text-red-900 font-black rounded-xl shadow-lg shadow-yellow-500/30 hover:bg-white hover:scale-105 transition transform">
                        ĐĂNG KÝ HỒ SƠ NGAY
                    </a>
                    <a href="<?= url('/login') ?>" class="px-8 py-4 bg-white/10 backdrop-blur-md border border-white/30 text-white font-bold rounded-xl hover:bg-white/20 transition">
                        ĐĂNG NHẬP
                    </a>
                </div>

                <!-- Quick Stats (Always horizontal) -->
                <div class="grid grid-cols-3 gap-2 sm:gap-6 pt-6 sm:pt-8 border-t border-white/10 mt-6 sm:mt-8">
                    <div class="text-center sm:text-left">
                        <p class="text-xl sm:text-2xl font-black text-hvu-gold"><?= $homeSettings['stats_majors'] ?></p>
                        <p class="text-[10px] sm:text-xs uppercase tracking-wider opacity-80">Ngành đào tạo</p>
                    </div>
                    <div class="text-center sm:text-left">
                        <p class="text-xl sm:text-2xl font-black text-hvu-gold"><?= $homeSettings['stats_quota'] ?></p>
                        <p class="text-[10px] sm:text-xs uppercase tracking-wider opacity-80">Chỉ tiêu</p>
                    </div>
                    <div class="text-center sm:text-left">
                        <p class="text-xl sm:text-2xl font-black text-hvu-gold"><?= $homeSettings['stats_employ'] ?></p>
                        <p class="text-[10px] sm:text-xs uppercase tracking-wider opacity-80">Sinh viên có việc</p>
                    </div>
                </div>
            </div>

            <!-- Hero Image/Card (Desktop only) -->
            <div class="hidden md:block md:w-1/2 relative mt-8 md:mt-0">
                <!-- Mobile: No rotation, simplified -->
                <div class="relative z-10 bg-white/10 backdrop-blur-xl border border-white/20 p-2 rounded-[1.5rem] md:rounded-[2rem] shadow-2xl md:transform md:rotate-3 md:hover:rotate-0 transition duration-500 group">
                    <!-- Desktop: Autoplay iframe -->
                    <div class="hidden md:block relative w-full rounded-[1.5rem] overflow-hidden" style="aspect-ratio: 16/9;">
                        <iframe
                            class="absolute top-0 left-0 w-full h-full"
                            src="https://www.youtube.com/embed/<?= $homeSettings['video_url'] ?>?autoplay=1&mute=1&loop=1&playlist=<?= $homeSettings['video_url'] ?>&controls=0&modestbranding=1&rel=0&playsinline=1"
                            title="HVU Campus Tour"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen>
                        </iframe>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    </div>

                    <!-- Floating Badge -->
                    <div class="absolute -bottom-6 -left-6 bg-white p-4 rounded-2xl shadow-xl hidden md:flex items-center gap-4 animate-bounce" style="animation-duration: 3s;">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: #BE1E2D; color: #ffd700; font-size: 1.25rem;">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div>
                            <p class="font-black" style="color: #BE1E2D;">ĐANG MỞ ĐĂNG KÝ</p>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">
                                <?= $activeSession ? 'ĐỢT ' . htmlspecialchars($activeSession['ten_dot']) : 'Tuyển sinh ' . date('Y') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Video Section (outside hero to avoid overflow-hidden clipping) -->
    <div class="md:hidden relative bg-gradient-to-b from-red-800 to-gray-50 px-4 pt-2 pb-10">
        <div class="relative z-10">
            <!-- Glass Card Frame (same as desktop, with tilt effect) -->
            <div class="relative bg-white/10 backdrop-blur-xl border border-white/20 p-2 rounded-[1.5rem] shadow-2xl transform rotate-2 active:rotate-0 transition duration-500">
                <div class="relative w-full rounded-[1.5rem] overflow-hidden" style="aspect-ratio: 16/9;">
                    <iframe
                        class="absolute top-0 left-0 w-full h-full"
                        src="https://www.youtube.com/embed/<?= $homeSettings['video_url'] ?>?rel=0&playsinline=1&modestbranding=1"
                        title="HVU Campus Tour"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
            <!-- Floating Badge (same as desktop) -->
            <div class="absolute -bottom-5 left-2 bg-white p-3 rounded-xl shadow-xl flex items-center gap-3 animate-bounce" style="animation-duration: 3s;">
                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: #BE1E2D; color: #ffd700; font-size: 1rem;">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div>
                    <p class="font-black text-sm" style="color: #BE1E2D;">ĐANG MỞ ĐĂNG KÝ</p>
                    <p class="text-[9px] text-gray-400 font-bold uppercase">
                        <?= $activeSession ? 'ĐỢT ' . htmlspecialchars($activeSession['ten_dot']) : 'Tuyển sinh ' . date('Y') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Main Content Container -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 -mt-10 relative z-20">

    <!-- DASHBOARD VIEW (LOGGED IN) -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="bg-white rounded-[2.5rem] shadow-xl border border-gray-100 overflow-hidden">
            <div class="p-8 md:p-12">

                <!-- ADMISSION RESULTS SECTION -->
                <?php
                $admitted = false;
                $admissionAspiration = null;
                $hasResults = false;
                if (!empty($choices) && is_array($choices)) {
                    foreach ($choices as $choice) {
                        $st = trim($choice['trang_thai'] ?? '');
                        if ($st === 'Trung tuyen') { // Updated to match DB string
                            $admitted = true;
                            $admissionAspiration = $choice;
                        }
                        if (!empty($st) && $st !== 'Cho xet') {
                            $hasResults = true;
                        }
                    }
                }
                ?>

                <!-- Congratulations Banner -->
                <?php if ($admitted && $admissionAspiration): ?>
                    <div class="mb-10 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-3xl p-8 text-center shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-green-200 rounded-full blur-3xl opacity-20 -mr-10 -mt-10"></div>

                        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 text-green-600 rounded-full mb-6 shadow-md text-4xl animate-bounce">
                            <i class="fas fa-graduation-cap"></i>
                        </div>

                        <h2 class="text-3xl md:text-4xl font-black text-green-800 uppercase mb-4 tracking-tight">
                            CHÚC MỪNG BẠN ĐÃ TRÚNG TUYỂN!
                        </h2>

                        <div class="bg-white/60 backdrop-blur-sm rounded-xl p-6 inline-block max-w-2xl mx-auto border border-green-100 shadow-sm">
                            <p class="text-lg text-gray-600 mb-2">Bạn đã chính thức trúng tuyển vào ngành:</p>
                            <h3 class="text-2xl font-bold text-hvu-red mb-2">
                                <?php
                                $majorName = $admissionAspiration['ma_nganh'];
                                foreach ($majors as $m) {
                                    if ($m['ma_nganh'] == $admissionAspiration['ma_nganh']) {
                                        $majorName = $m['ten_nganh'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($majorName);
                                ?>
                            </h3>
                            <p class="font-mono text-gray-500 font-bold">Mã ngành: <?= htmlspecialchars($admissionAspiration['ma_nganh']) ?></p>
                            <div class="mt-4 pt-4 border-t border-dashed border-green-200">
                                <div class="text-sm font-bold text-gray-500 uppercase tracking-widest">Tổng điểm xét tuyển</div>
                                <div class="text-3xl font-black text-green-600"><?= number_format($admissionAspiration['diem_xet_tuyen'], 2) ?></div>
                            </div>
                        </div>

                        <p class="mt-6 text-sm text-green-700 font-medium bg-green-100/50 inline-block px-4 py-2 rounded-full">
                            <i class="fas fa-envelope-open-text mr-2"></i> Giấy báo trúng tuyển đã được gửi về email của bạn.
                        </p>
                    </div>
                <?php elseif ($hasResults): ?>
                    <!-- Failed Banner -->
                    <div class="mb-10 bg-gray-50 border border-gray-200 rounded-3xl p-8 text-center">
                        <div class="text-gray-400 text-5xl mb-4"><i class="fas fa-clipboard-list"></i></div>
                        <h2 class="text-2xl font-bold text-gray-700 uppercase">KẾT QUẢ XÉT TUYỂN</h2>
                        <p class="text-gray-600 mt-2">Rất tiếc, bạn chưa trúng tuyển vào các nguyện vọng đã đăng ký trong đợt này.</p>
                    </div>
                <?php endif; ?>

                <!-- Aspirations List Table (Only show if has results) -->
                <?php if ($hasResults): ?>
                    <div class="mb-12 overflow-hidden rounded-2xl border border-gray-200 shadow-sm">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 font-bold text-gray-800 flex items-center">
                            <i class="fas fa-list-ol mr-3 text-hvu-red"></i> Chi tiết Kết quả Xét tuyển
                        </div>
                        <table class="w-full text-sm text-left">
                            <thead class="bg-white uppercase text-gray-500 font-bold text-xs tracking-wider border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4 text-center">TT</th>
                                    <th class="px-6 py-4">Ngành / Chuyên ngành</th>
                                    <th class="px-6 py-4 text-center">Điểm xét tuyển</th>
                                    <th class="px-6 py-4 text-center">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($choices as $choice): ?>
                                    <?php
                                    $status = trim($choice['trang_thai'] ?? '');
                                    $isPass = $status === 'Trung tuyen';
                                    $rowClass = $isPass ? 'bg-green-50/50' : 'bg-white';
                                    $statusClass = $isPass ? 'bg-green-100 text-green-700 border-green-200' : ((strpos($status, 'Truot') !== false) ? 'bg-gray-100 text-gray-500 border-gray-200' : 'bg-yellow-100 text-yellow-700 border-yellow-200');
                                    ?>
                                    <tr class="<?= $rowClass ?> hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 text-center font-bold text-gray-400"><?= $choice['thu_tu_nguyen_vong'] ?></td>
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-800 text-base"><?= htmlspecialchars($choice['ma_nganh']) ?></div>
                                            <?php foreach ($majors as $m) {
                                                if ($m['ma_nganh'] == $choice['ma_nganh']) {
                                                    echo '<div class="text-xs text-gray-500 mt-0.5">' . htmlspecialchars($m['ten_nganh']) . '</div>';
                                                    break;
                                                }
                                            } ?>
                                        </td>
                                        <td class="px-6 py-4 text-center font-mono font-bold text-gray-700">
                                            <?= ($choice['diem_xet_tuyen'] > 0) ? number_format($choice['diem_xet_tuyen'], 2) : '--' ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $statusClass ?>">
                                                <?= htmlspecialchars($status ?: 'Chờ xét') ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-3xl font-black font-heading text-gray-900 uppercase">Hồ sơ của bạn</h2>
                        <p class="text-gray-500 mt-2">Hoàn thành các bước bên dưới để nộp hồ sơ xét tuyển.</p>
                    </div>
                    <div class="hidden md:block">
                        <div class="w-24 h-24 rounded-full bg-red-50 flex items-center justify-center text-hvu-red text-3xl font-black border-4 border-white shadow-lg">
                            <?= floor((($nextStep - 1) / $totalSteps) * 100) ?>%
                        </div>
                    </div>
                </div>

                <!-- Process Steps (Horizontal) -->
                <div class="relative">
                    <div class="absolute top-1/2 left-0 w-full h-1 bg-gray-100 -translate-y-1/2 hidden md:block z-0"></div>
                    <div class="grid grid-cols-1 md:grid-cols-<?= $totalSteps ?> gap-6 relative z-10">
                        <!-- Step 1 -->
                        <?php $s = 1;
                        $sDone = $isDone($s);
                        $sActive = ($nextStep == $s); ?>
                        <div class="group relative bg-white p-4 rounded-2xl border-2 <?= $sActive ? 'border-hvu-red shadow-lg shadow-red-100' : (($sDone && $s < $nextStep) ? 'border-green-500' : 'border-gray-100') ?> transition hover:-translate-y-1 text-center md:text-left">
                            <div class="w-10 h-10 mx-auto md:mx-0 rounded-full flex items-center justify-center font-bold text-sm mb-3 <?= ($sDone && $s < $nextStep) ? 'bg-green-500 text-white' : ($sActive ? 'bg-hvu-red text-white' : 'bg-gray-100 text-gray-400') ?>">
                                <?= ($sDone && $s < $nextStep) ? '<i class="fas fa-check"></i>' : $s ?>
                            </div>
                            <h3 class="font-bold text-gray-900">Thông tin cá nhân</h3>
                            <a href="<?= url('/profile/step1') ?>" class="text-[10px] uppercase font-black tracking-widest mt-2 inline-block <?= $sActive ? 'text-hvu-red' : 'text-gray-400' ?>">
                                <?= ($sDone && $s < $nextStep) ? 'Cập nhật' : ($sActive ? 'Thực hiện ngay' : 'Đang khóa') ?>
                            </a>
                        </div>

                        <!-- Step 2 -->
                        <?php $s = 2;
                        $sDone = $isDone($s);
                        $sActive = ($nextStep == $s); ?>
                        <div class="group relative bg-white p-4 rounded-2xl border-2 <?= $sActive ? 'border-hvu-red shadow-lg shadow-red-100' : (($sDone && $s < $nextStep) ? 'border-green-500' : 'border-gray-100') ?> transition hover:-translate-y-1 text-center md:text-left">
                            <div class="w-10 h-10 mx-auto md:mx-0 rounded-full flex items-center justify-center font-bold text-sm mb-3 <?= ($sDone && $s < $nextStep) ? 'bg-green-500 text-white' : ($sActive ? 'bg-hvu-red text-white' : 'bg-gray-100 text-gray-400') ?>">
                                <?= ($sDone && $s < $nextStep) ? '<i class="fas fa-check"></i>' : $s ?>
                            </div>
                            <h3 class="font-bold text-gray-900">Học bạ THPT</h3>
                            <a href="<?= url('/profile/step2') ?>" class="text-[10px] uppercase font-black tracking-widest mt-2 inline-block <?= $sActive ? 'text-hvu-red' : 'text-gray-400' ?>">
                                <?= ($sDone && $s < $nextStep) ? 'Cập nhật' : ($sActive ? 'Thực hiện ngay' : 'Đang khóa') ?>
                            </a>
                        </div>

                        <!-- Step 3 -->
                        <?php
                        $s = 3;
                        $sDone = $isDone($s);
                        $sActive = ($nextStep == $s);
                        ?>
                        <div class="group relative bg-white p-4 rounded-2xl border-2 <?= $sActive ? 'border-hvu-red shadow-lg shadow-red-100' : (($sDone && $s < $nextStep) ? 'border-green-500' : 'border-gray-100') ?> transition hover:-translate-y-1 text-center md:text-left">
                            <div class="w-10 h-10 mx-auto md:mx-0 rounded-full flex items-center justify-center font-bold text-sm mb-3 <?= ($sDone && $s < $nextStep) ? 'bg-green-500 text-white' : ($sActive ? 'bg-hvu-red text-white' : 'bg-gray-100 text-gray-400') ?>">
                                <?= ($sDone && $s < $nextStep) ? '<i class="fas fa-check"></i>' : $s ?>
                            </div>
                            <h3 class="font-bold text-gray-900">Chứng chỉ ngoại ngữ quốc tế</h3>
                            <a href="<?= url('/profile/step3') ?>" class="text-[10px] uppercase font-black tracking-widest mt-2 inline-block <?= $sActive ? 'text-hvu-red' : 'text-gray-400' ?>">
                                <?= ($sDone && $s < $nextStep) ? 'Cập nhật' : ($sActive ? 'Thực hiện ngay' : 'Đang khóa') ?>
                            </a>
                        </div>

                        <!-- Step 4 (Conditional) -->
                        <?php if ($enableTHPT): ?>
                            <?php $s = 4;
                            $sDone = $isDone($s);
                            $sActive = ($nextStep == $s); ?>
                            <div class="group relative bg-white p-4 rounded-2xl border-2 <?= $sActive ? 'border-hvu-red shadow-lg shadow-red-100' : (($sDone && $s < $nextStep) ? 'border-green-500' : 'border-gray-100') ?> transition hover:-translate-y-1 text-center md:text-left">
                                <div class="w-10 h-10 mx-auto md:mx-0 rounded-full flex items-center justify-center font-bold text-sm mb-3 <?= ($sDone && $s < $nextStep) ? 'bg-green-500 text-white' : ($sActive ? 'bg-hvu-red text-white' : 'bg-gray-100 text-gray-400') ?>">
                                    <?= ($sDone && $s < $nextStep) ? '<i class="fas fa-check"></i>' : $s ?>
                                </div>
                                <h3 class="font-bold text-gray-900">Điểm thi 2026</h3>
                                <a href="<?= url('/profile/step4') ?>" class="text-[10px] uppercase font-black tracking-widest mt-2 inline-block <?= $sActive ? 'text-hvu-red' : 'text-gray-400' ?>">
                                    <?= ($sDone && $s < $nextStep) ? 'Cập nhật' : ($sActive ? 'Thực hiện ngay' : 'Đang khóa') ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Step Final (Choice) -->
                        <?php $s = $totalSteps;
                        $sDone = $isDone($s);
                        $sActive = ($nextStep == $s); ?>
                        <div class="group relative bg-white p-4 rounded-2xl border-2 <?= $sActive ? 'border-hvu-red shadow-lg shadow-red-100' : (($sDone && $s < $nextStep) ? 'border-green-500' : 'border-gray-100') ?> transition hover:-translate-y-1 text-center md:text-left">
                            <div class="w-10 h-10 mx-auto md:mx-0 rounded-full flex items-center justify-center font-bold text-sm mb-3 <?= ($sDone && $s < $nextStep) ? 'bg-green-500 text-white' : ($sActive ? 'bg-hvu-red text-white' : 'bg-gray-100 text-gray-400') ?>">
                                <?= ($sDone && $s < $nextStep) ? '<i class="fas fa-check"></i>' : $s ?>
                            </div>
                            <h3 class="font-bold text-gray-900">Nguyện vọng</h3>
                            <a href="<?= url('/profile/step' . $s) ?>" class="text-[10px] uppercase font-black tracking-widest mt-2 inline-block <?= $sActive ? 'text-hvu-red' : 'text-gray-400' ?>">
                                <?= ($sDone && $s < $nextStep) ? 'Cập nhật' : ($sActive ? 'Thực hiện ngay' : 'Đang khóa') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-gray-100 text-center">
                    <?php if ($nextStep > $totalSteps): ?>
                        <div class="inline-flex flex-col items-center gap-3">
                            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                            </div>
                            <p class="text-green-700 font-bold text-lg">Bạn đã hoàn thành tất cả các bước!</p>
                            <p class="text-gray-500 text-sm">Hồ sơ của bạn đã sẵn sàng để xét tuyển.</p>
                            <a href="<?= url('/profile/step1') ?>" class="inline-flex items-center px-8 py-4 bg-[#0066FF] text-white font-black rounded-xl shadow-xl hover:bg-blue-700 transition transform hover:-translate-y-1">
                                <i class="fas fa-eye mr-3"></i> XEM LẠI HỒ SƠ
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="<?= url('/profile/step' . $nextStep) ?>" class="inline-flex items-center px-8 py-4 bg-hvu-red text-white font-black rounded-xl shadow-xl hover:bg-red-700 transition transform hover:-translate-y-1">
                            TIẾP TỤC ĐẾN BƯỚC <?= $nextStep ?> <i class="fas fa-arrow-right ml-3"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- LANDING VIEW (GUEST) -->
    <?php else: ?>
        <!-- Majors Table Section (Restored & Redesigned) -->
        <div class="bg-white rounded-[2rem] shadow-none border border-gray-200 overflow-hidden mb-20 relative">
            <!-- Decorative -->
            <div class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-red-700 via-hvu-red to-orange-500"></div>

            <div class="p-4 md:p-8 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4 relative z-10 border-b border-gray-200">
                <div class="flex flex-col md:flex-row items-center text-center md:text-left">
                    <img loading="lazy" src="<?= url('/assets/img/Logo.png') ?>" alt="HVU Logo" class="hidden md:block w-14 h-14 object-contain md:mr-4 drop-shadow-sm filter hover:brightness-110 transition">
                    <div>
                        <h2 class="text-lg md:text-xl font-bold font-sans text-gray-900 uppercase leading-none tracking-tight">THÔNG TIN TUYỂN SINH NĂM 2026</h2>
                        <p class="text-sm text-gray-500 font-medium mt-1 uppercase tracking-wider">Mã trường: <span class="text-hvu-red font-bold">THV</span></p>
                    </div>
                </div>
                <a href="<?= url('/register') ?>" class="hidden md:flex px-5 py-2.5 bg-hvu-red text-white font-bold font-sans rounded-lg text-sm uppercase tracking-widest hover:bg-red-800 transition shadow-lg shadow-red-100 items-center">
                    Đăng ký xét tuyển <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>

            <!-- Desktop Table (Hidden on Mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse font-sans text-sm">
                    <thead>
                        <tr class="bg-white text-gray-800 font-bold uppercase tracking-wider border-b-2 border-gray-100">
                            <th class="px-5 py-3 align-middle">Ngành đào tạo</th>
                            <th class="px-4 py-3 text-center align-middle">Mã ngành</th>
                            <th class="px-4 py-3 text-center align-middle">Chỉ tiêu</th>
                            <th class="px-4 py-3 text-center align-middle">Tổ hợp môn</th>
                            <th class="px-5 py-3 text-center text-hvu-red align-middle">Điểm chuẩn 2025</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 bg-white">
                        <?php if (!empty($majors)): ?>
                            <?php foreach ($majors as $index => $major): ?>
                                <tr class="group hover:bg-red-50 transition-colors border-b border-gray-100 last:border-0">
                                    <td class="px-5 py-2.5 text-gray-900 font-medium align-middle">
                                        <?= htmlspecialchars($major['ten_nganh']) ?>
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-gray-600 font-medium align-middle">
                                        <?= str_replace(['.1', '.2'], '', $major['ma_nganh']) ?>
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-gray-600 font-medium align-middle">
                                        <?= $major['chi_tieu'] ?: '--' ?>
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-gray-600 font-medium align-middle uppercase">
                                        <?= $major['combination_list'] ?: $major['khoi_xet_tuyen'] ?>
                                    </td>
                                    <td class="px-5 py-2.5 text-center align-middle">
                                        <?php if ($major['diem_nam_truoc'] > 0): ?>
                                            <span class="text-hvu-red font-bold group-hover:scale-105 inline-block transition-transform"><?= number_format($major['diem_nam_truoc'], 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-300 italic">--</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-6 text-center text-gray-400 italic">
                                    Dữ liệu đang được cập nhật.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Accordion List (Hidden on Desktop) -->
            <div class="md:hidden">
                <div class="divide-y divide-gray-100">
                    <?php if (!empty($majors)): ?>
                        <?php foreach ($majors as $index => $major): ?>
                            <div class="mobile-major-item">
                                <!-- Trigger: Tên ngành (Mã ngành): chỉ tiêu X -->
                                <button onclick="toggleMajor('major-<?= $index ?>')" class="w-full text-left px-4 py-2.5 flex items-center justify-between bg-white active:bg-gray-50 transition">
                                    <div class="flex-1 min-w-0 pr-3">
                                        <p class="text-[13px] leading-tight">
                                            <span class="font-bold text-gray-900"><?= htmlspecialchars($major['ten_nganh']) ?></span>
                                            <span class="text-gray-400 font-mono text-[10px]">(<?= str_replace(['.1', '.2'], '', $major['ma_nganh']) ?>)</span>
                                            <span class="text-gray-400"> - </span>
                                            <span class="font-black text-hvu-red"><?= $major['chi_tieu'] ?: '--' ?></span>
                                        </p>
                                    </div>
                                    <span id="icon-major-<?= $index ?>" class="text-gray-300 text-xs transition-transform duration-300 flex-shrink-0">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </button>

                                <!-- Expanded: 1 cột, 2 dòng -->
                                <div id="major-<?= $index ?>" class="hidden">
                                    <div class="px-5 py-2.5 bg-gray-50/80 border-t border-gray-100 space-y-1">
                                        <p class="text-[12px] text-gray-600">
                                            <span class="text-gray-400 font-semibold">Tổ hợp xét:</span>
                                            <span class="font-bold text-gray-800 uppercase"><?= $major['combination_list'] ?: ($major['khoi_xet_tuyen'] ?: '--') ?></span>
                                        </p>
                                        <p class="text-[12px] text-gray-600">
                                            <span class="text-gray-400 font-semibold">Điểm chuẩn 2025:</span>
                                            <span class="font-black <?= $major['diem_nam_truoc'] > 0 ? 'text-hvu-red' : 'text-gray-300' ?>"><?= $major['diem_nam_truoc'] > 0 ? number_format($major['diem_nam_truoc'], 2) : '--' ?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-6 text-center text-gray-400 italic text-sm">
                            Dữ liệu đang được cập nhật.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Accordion JS (auto-close others) -->
                <script>
                    function toggleMajor(id) {
                        const content = document.getElementById(id);
                        const icon = document.getElementById('icon-' + id);

                        // Close all others
                        document.querySelectorAll('.mobile-major-item [id^="major-"]').forEach(el => {
                            if (el.id !== id && !el.classList.contains('hidden')) {
                                el.classList.add('hidden');
                                const otherIcon = document.getElementById('icon-' + el.id);
                                if (otherIcon) {
                                    otherIcon.style.transform = 'rotate(0deg)';
                                    otherIcon.classList.remove('text-hvu-red');
                                }
                            }
                        });

                        if (content.classList.contains('hidden')) {
                            content.classList.remove('hidden');
                            icon.style.transform = 'rotate(180deg)';
                            icon.classList.add('text-hvu-red');
                        } else {
                            content.classList.add('hidden');
                            icon.style.transform = 'rotate(0deg)';
                            icon.classList.remove('text-hvu-red');
                        }
                    }
                </script>
            </div>

            <!-- Mobile: Register button at bottom of table -->
            <div class="md:hidden p-4 border-t border-gray-100">
                <a href="<?= url('/register') ?>" class="w-full py-3 bg-hvu-red text-white font-bold font-sans rounded-xl text-sm uppercase tracking-widest hover:bg-red-800 transition shadow-lg flex items-center justify-center">
                    Đăng ký xét tuyển <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>

        <!-- Feature Highlights -->

        <!-- Latest News -->
        <div class="mb-20">
            <div class="flex justify-between items-end mb-6 sm:mb-10">
                <div>
                    <span class="text-hvu-red font-bold uppercase tracking-widest text-xs">Tin tức & Sự kiện</span>
                    <h2 class="text-xl sm:text-3xl font-black font-heading text-gray-900 mt-1 sm:mt-2">THÔNG TIN TUYỂN SINH</h2>
                </div>
                <a href="<?= url('/news') ?>" class="text-gray-500 hover:text-hvu-red font-bold flex items-center transition text-sm">Xem tất cả <i class="fas fa-arrow-right ml-2"></i></a>
            </div>

            <!-- Desktop: Grid 3 columns -->
            <div class="hidden md:grid md:grid-cols-3 gap-8">
                <?php foreach (array_slice($posts ?? [], 0, 3) as $post): ?>
                    <a href="<?= url('/news/detail?slug=' . $post['slug']) ?>" class="group block">
                        <div class="relative overflow-hidden rounded-2xl mb-4 shadow-md">
                            <img loading="lazy" src="<?= $post['thumbnail'] ? (filter_var($post['thumbnail'], FILTER_VALIDATE_URL) ? $post['thumbnail'] : url('/' . $post['thumbnail'])) : url('/assets/img/Logo.png') ?>" class="w-full h-56 object-cover transform group-hover:scale-110 transition duration-700">
                            <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-lg text-xs font-bold uppercase text-hvu-red">
                                <?= $post['category'] ?>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 font-bold mb-2 uppercase tracking-wide"><?= date('d/m/Y', strtotime($post['created_at'])) ?></p>
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-hvu-red transition leading-tight"><?= htmlspecialchars($post['title']) ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Mobile: Horizontal Scroll Slider -->
            <div class="md:hidden">
                <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-4 -mx-4 px-4 news-slider">
                    <?php foreach (array_slice($posts ?? [], 0, 3) as $i => $post): ?>
                        <a href="<?= url('/news/detail?slug=' . $post['slug']) ?>" class="snap-center flex-shrink-0 block w-full">
                            <div class="relative overflow-hidden rounded-2xl mb-3 shadow-md">
                                <img loading="lazy" src="<?= $post['thumbnail'] ? (filter_var($post['thumbnail'], FILTER_VALIDATE_URL) ? $post['thumbnail'] : url('/' . $post['thumbnail'])) : url('/assets/img/Logo.png') ?>" class="w-full h-44 object-cover">
                                <div class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase text-hvu-red">
                                    <?= $post['category'] ?>
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 font-bold mb-1 uppercase tracking-wide"><?= date('d/m/Y', strtotime($post['created_at'])) ?></p>
                            <h3 class="text-sm font-bold text-gray-900 leading-tight line-clamp-2"><?= htmlspecialchars($post['title']) ?></h3>
                        </a>
                    <?php endforeach; ?>
                </div>
                <!-- Scroll indicators -->
                <div class="flex justify-center gap-1.5 mt-2">
                    <?php foreach (array_slice($posts ?? [], 0, 3) as $i => $post): ?>
                        <div class="w-1.5 h-1.5 rounded-full <?= $i === 0 ? 'bg-hvu-red' : 'bg-gray-300' ?> news-dot" data-index="<?= $i ?>"></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <style>
                .news-slider {
                    scrollbar-width: none;
                    -ms-overflow-style: none;
                }

                .news-slider::-webkit-scrollbar {
                    display: none;
                }

                .line-clamp-2 {
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                }
            </style>
            <script>
                (function() {
                    const slider = document.querySelector('.news-slider');
                    if (!slider) return;
                    const dots = document.querySelectorAll('.news-dot');
                    slider.addEventListener('scroll', function() {
                        const scrollLeft = slider.scrollLeft;
                        const itemWidth = slider.firstElementChild?.offsetWidth || 1;
                        const activeIndex = Math.round(scrollLeft / (itemWidth + 16));
                        dots.forEach((dot, i) => {
                            dot.classList.toggle('bg-hvu-red', i === activeIndex);
                            dot.classList.toggle('bg-gray-300', i !== activeIndex);
                        });
                    });
                })();
            </script>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>