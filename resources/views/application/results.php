<?php 
$title = 'Kết quả xét tuyển';
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Font for the quote -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&display=swap');
.font-cursive { font-family: 'Dancing Script', cursive; }
</style>

<?php if ($sessionId): ?>
<style>
    main.flex-grow {
        padding-top: 4px !important;
        padding-bottom: 4px !important;
    }
    main.flex-grow.container {
        max-width: 100% !important;
        width: 100% !important;
        padding-left: 8px !important;
        padding-right: 8px !important;
    }
</style>
<?php endif; ?>

<div class="<?= $sessionId ? 'max-w-7xl mx-auto px-2 mt-1 pb-2 space-y-2' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6 pb-24 space-y-6' ?>">

    <?php if (!$sessionId): ?>
        <!-- === MÀN HÌNH 1: DASHBOARD DANH SÁCH ĐỢT === -->
        
        <!-- Header: Profile & Quote -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex flex-col md:flex-row w-full">
            <!-- Profile Info -->
            <div class="p-5 md:p-6 flex items-center gap-5 border-b md:border-b-0 md:border-r border-gray-100 relative overflow-hidden" style="width: 40%; flex-shrink: 0;">
                <div class="absolute left-0 top-0 bottom-0 w-3 bg-gradient-to-b from-indigo-500 to-blue-600"></div>
                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center shrink-0 border-4 border-white shadow-md z-10 relative overflow-hidden">
                    <!-- Avatar -->
                    <?php 
                    $fallbackAvatar = "https://ui-avatars.com/api/?name=" . urlencode($user['ho_va_ten'] ?? $_SESSION['ho_va_ten'] ?? 'SV') . "&background=eff6ff&color=1d4ed8&size=150&bold=true";
                    $avatarPath = $user['anh_dai_dien'] ?? $user['avatar'] ?? null;
                    if ($avatarPath) {
                        $avatarUrl = (strpos($avatarPath, 'http') === 0) ? $avatarPath : url('/' . ltrim($avatarPath, '/'));
                    } else {
                        $avatarUrl = $fallbackAvatar;
                    }
                    ?>
                    <img src="<?= $avatarUrl ?>" alt="Avatar" class="w-full h-full object-cover">
                    <div class="absolute bottom-0 right-0 w-5 h-5 bg-green-500 rounded-full border-2 border-white flex items-center justify-center">
                        <i class="fas fa-check text-[10px] text-white"></i>
                    </div>
                </div>
                <div class="z-10">
                    <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <?= htmlspecialchars($user['ho_va_ten'] ?? $_SESSION['ho_va_ten'] ?? 'Thí sinh') ?> 
                        <i class="fas fa-check-circle text-blue-500 text-lg"></i>
                    </h2>
                    <div class="text-sm text-gray-500 mt-1 flex items-center gap-2">
                        <span>Số CCCD: <strong class="text-gray-700"><?= htmlspecialchars($user['so_cccd'] ?? $_SESSION['cccd'] ?? '') ?></strong></span>
                        <i class="far fa-copy cursor-pointer hover:text-blue-600 transition-colors" title="Copy" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($user['so_cccd'] ?? $_SESSION['cccd'] ?? '') ?>')"></i>
                    </div>
                    <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-600">
                        <span class="flex items-center gap-1.5"><i class="fas fa-phone-alt text-gray-400"></i> <?= htmlspecialchars($user['dien_thoai'] ?? $user['dien_thoai_lien_he'] ?? $user['dien_thoai_thuong_tru'] ?? $_SESSION['dien_thoai'] ?? 'Chưa cập nhật') ?></span>
                        <span class="flex items-center gap-1.5"><i class="fas fa-envelope text-gray-400"></i> <?= htmlspecialchars($user['email'] ?? $_SESSION['email'] ?? 'Chưa cập nhật') ?></span>
                    </div>
                </div>
                <!-- Decorative element -->
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-blue-50 rounded-full blur-3xl opacity-50"></div>
            </div>

            <!-- Quote Info -->
            <div class="p-5 md:p-6 flex items-center justify-center flex-1 bg-gradient-to-br from-white to-gray-50 relative" style="min-width: 0;">
                <i class="fas fa-quote-left absolute top-6 left-6 text-3xl text-indigo-200/50"></i>
                <div class="text-center px-4 z-10 w-full">
                    <p class="text-lg text-indigo-900/80 font-medium leading-relaxed">
                        "Hãy kiên trì với ước mơ của bạn.<br>Thành công sẽ đến với những người không ngừng cố gắng!"
                    </p>
                </div>
                <i class="fas fa-quote-right absolute bottom-6 right-6 text-3xl text-purple-200/50"></i>
            </div>
        </div>

        <div class="container mx-auto px-4 py-6 max-w-7xl">
        <!-- Main Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Left Column: Kết quả xét tuyển -->
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-id-card-alt"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 uppercase tracking-wide">KẾT QUẢ XÉT TUYỂN CỦA BẠN</h3>
                </div>

                <?php if (empty($candidateSessions)): ?>
                    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center shadow-sm">
                        <div class="w-20 h-20 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-inbox text-3xl text-gray-400"></i>
                        </div>
                        <h4 class="text-xl font-bold text-gray-800 mb-2">Chưa có dữ liệu xét tuyển</h4>
                        <p class="text-gray-500">Bạn chưa đăng ký nguyện vọng trong đợt xét tuyển nào.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($candidateSessions as $index => $s): ?>
                            <?php 
                            $isPublished = ($s['is_published_results'] === true || $s['is_published_results'] === 1 || $s['is_published_results'] === '1' || $s['is_published_results'] === 't');
                            $dotNumber = $index + 1;
                            
                            // Determine style based on status
                            $cardBorder = $isPublished ? 'border-green-200 shadow-green-100/50' : 'border-gray-200';
                            $leftBand = $isPublished ? 'bg-green-500' : 'bg-gray-400';
                            $statusBg = $isPublished ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600';
                            $statusText = $isPublished ? 'Đã công bố' : 'Hồ sơ đang trong quá trình xét duyệt.';
                            ?>
                            <div class="bg-white rounded-2xl border <?= $cardBorder ?> shadow-sm hover:shadow-md transition-shadow relative overflow-hidden flex flex-col sm:flex-row">
                                <!-- Left Band -->
                                <div class="absolute left-0 top-0 bottom-0 w-1.5 <?= $leftBand ?>"></div>
                                
                                <!-- Đợt Column -->
                                <div class="p-4 sm:w-24 flex flex-col items-center justify-center border-b sm:border-b-0 sm:border-r border-gray-100/80">
                                    <div class="w-14 h-14 rounded-full <?= $isPublished ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?> flex items-center justify-center font-black text-2xl shadow-inner">
                                        <?= $dotNumber ?>
                                    </div>
                                </div>
                                
                                <!-- Content Column -->
                                <div class="p-4 flex-1 flex flex-col justify-center">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-base font-bold text-gray-900 mb-2"><?= htmlspecialchars($s['ten_dot']) ?></h4>
                                            
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full <?= $statusBg ?> text-xs font-bold">
                                                    <?= $isPublished ? '<i class="fas fa-check-circle mr-1.5"></i>' : '<i class="fas fa-hourglass-half mr-1.5"></i>' ?>
                                                    <?= $statusText ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <a href="<?= url('/application/results?session_id=' . $s['id']) ?>" class="shrink-0 inline-flex items-center justify-center px-4 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold border border-blue-200 transition-colors ml-4">
                                            Xem chi tiết <i class="fas fa-arrow-right ml-2 text-xs"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Support Banner -->
                <div class="bg-gradient-to-r from-indigo-500 to-purple-500 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-lg shadow-indigo-200/50 text-white mt-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center shrink-0">
                            <i class="fas fa-bell text-2xl animate-bounce"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold">Bạn cần hỗ trợ?</h4>
                            <p class="text-indigo-100 text-sm mt-1">
                                Nếu có thắc mắc về kết quả xét tuyển, vui lòng liên hệ Bộ phận tuyển sinh - Phòng Đào tạo.
                                <br class="hidden sm:inline">
                                Hotline: <strong class="text-white">0866.993.468</strong> | Email: <strong class="text-white">tuyensinh@hvu.edu.vn</strong>
                            </p>
                        </div>
                    </div>
                    <a href="https://zalo.me/0866993468" target="_blank" class="shrink-0 px-6 py-2.5 bg-white text-indigo-600 rounded-xl font-bold hover:bg-indigo-50 transition-colors shadow-sm flex items-center gap-2 text-sm">
                        <i class="far fa-comment-dots"></i> Liên hệ Zalo
                    </a>
                </div>
            </div>

            <!-- Right Column: Timeline & Notifications -->
            <div class="lg:col-span-1 space-y-5">

                <!-- Notifications Section -->
                <div class="bg-white rounded-3xl border border-gray-100 p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">THÔNG BÁO MỚI NHẤT</h3>
                    </div>
                    
                    <div class="space-y-3">
                        <?php if (empty($latestPosts)): ?>
                            <p class="text-gray-500 text-sm">Chưa có thông báo mới.</p>
                        <?php else: ?>
                            <?php foreach ($latestPosts as $index => $post): 
                                $postDate = date('d/m/Y', strtotime($post['created_at']));
                                $thumbnail = !empty($post['thumbnail']) ? (filter_var($post['thumbnail'], FILTER_VALIDATE_URL) ? $post['thumbnail'] : url('/' . $post['thumbnail'])) : url('/assets/img/Logo.png');
                            ?>
                                <a href="<?= url('/news/detail?slug=' . $post['slug']) ?>" class="group cursor-pointer block border-b border-gray-50 last:border-0 pb-3 last:pb-0 mt-3 first:mt-0">
                                    <div class="flex gap-3 items-start">
                                        <div class="w-16 h-16 shrink-0 rounded-lg overflow-hidden border border-gray-100 shadow-sm bg-white relative flex items-center justify-center">
                                            <img src="<?= $thumbnail ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition duration-500" onerror="this.src='<?= url('/assets/img/Logo.png') ?>'">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-xs font-bold text-blue-900 group-hover:text-blue-600 transition-colors line-clamp-2 leading-snug mb-1" title="<?= htmlspecialchars($post['title']) ?>">
                                                <?= htmlspecialchars($post['title']) ?>
                                            </h4>
                                            <p class="text-[10px] text-gray-500 line-clamp-2 leading-relaxed">
                                                <?= htmlspecialchars($post['summary'] ?? strip_tags(mb_substr($post['content'] ?? '', 0, 100))) ?>
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <a href="<?= url('/news') ?>" class="block text-center mt-6 pt-4 border-t border-gray-50 text-sm font-bold text-blue-600 hover:text-blue-700 bg-blue-50/50 rounded-xl py-2">
                        Xem tất cả thông báo <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>

            </div>
        </div>


    <?php else: ?>
        <!-- === MÀN HÌNH 2: CHI TIẾT KẾT QUẢ CỦA 1 ĐỢT === -->
        

        <?php if ($admissionRecord && (!empty($renderedAdmissionLetter) || !empty($admissionRecord['file_giay_bao']))): ?>
            
            <form id="confirm-form" method="POST" action="<?= url('/application/confirm-enrollment') ?>" style="display:none;">
                <input type="hidden" name="session_id" value="<?= htmlspecialchars($sessionId) ?>">
                <?= \App\Middleware\SecurityMiddleware::csrfField() ?>
            </form>

            <form id="confirm-bo-form" method="POST" action="<?= url('/application/confirm-enrollment-bo') ?>" style="display:none;">
                <input type="hidden" name="session_id" value="<?= htmlspecialchars($sessionId) ?>">
                <?= \App\Middleware\SecurityMiddleware::csrfField() ?>
            </form>

            <form id="confirm-kinhphi-form" method="POST" action="<?= url('/application/confirm-kinhphi') ?>" style="display:none;">
                <input type="hidden" name="session_id" value="<?= htmlspecialchars($sessionId) ?>">
                <?= \App\Middleware\SecurityMiddleware::csrfField() ?>
            </form>

            <!-- Giấy báo trúng tuyển (Letter) -->
            <div class="mt-1 mb-2 w-full flex justify-center items-start">
                <?php if (!empty($renderedAdmissionLetter)): ?>
                    <?= $renderedAdmissionLetter ?>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center h-64 bg-gray-50 rounded-2xl border border-gray-200 w-full max-w-4xl mx-auto opacity-70">
                        <i class="fas fa-file-pdf text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 font-medium">Nội dung văn bản được đính kèm bằng PDF.</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif (!empty($enableResults)): ?>
            <!-- Chưa có kết quả -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-8 rounded-2xl shadow-sm">
                <div class="flex items-start gap-4">
                    <i class="fas fa-info-circle text-yellow-500 text-3xl"></i>
                    <div>
                        <h3 class="text-xl font-bold text-yellow-900 mb-2">Chưa có thông tin trúng tuyển</h3>
                        <p class="text-yellow-800">
                            Hệ thống chưa ghi nhận kết quả trúng tuyển của bạn trong đợt này. Vui lòng quay lại sau khi có thông báo chính thức tiếp theo.
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-gray-50 border border-gray-200 p-8 rounded-2xl shadow-sm text-center">
                <i class="fas fa-lock text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-700 mb-2">Hệ thống chưa mở Tra cứu</h3>
                <p class="text-gray-500">Kết quả đợt này hiện chưa được công bố.</p>
            </div>
        <?php endif; ?>

        <!-- Điểm thi năng khiếu -->
        <?php if (!empty($talentResults)): ?>
            <div class="mt-10">
                <h3 class="text-lg font-bold text-gray-900 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="fas fa-star text-yellow-500"></i> KẾT QUẢ THI NĂNG KHIẾU
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($talentResults as $talent): ?>
                        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col md:flex-row justify-between items-center gap-6">
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1"><?= htmlspecialchars($talent['session_name']) ?></div>
                                <h4 class="text-xl font-black text-gray-800 mb-3"><?= htmlspecialchars($talent['subject_name']) ?></h4>
                                <div class="flex gap-6 text-sm">
                                    <div>
                                        <span class="text-gray-500">SBD:</span> <strong class="text-gray-900"><?= htmlspecialchars($talent['exam_number']) ?></strong>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Phòng:</span> <strong class="text-gray-900"><?= htmlspecialchars($talent['room_name'] ?: 'Chưa phân') ?></strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="w-24 h-24 rounded-full border-4 border-blue-50 flex items-center justify-center shrink-0">
                                <div class="text-center">
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">ĐIỂM</div>
                                    <div class="text-3xl font-black text-blue-600 leading-none">
                                        <?= $talent['score'] !== null ? number_format($talent['score'], 1) : '--' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
