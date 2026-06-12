<?php 
$title = 'Kết quả xét tuyển';
include __DIR__ . '/../layouts/header.php'; 
?>

<div class="max-w-5xl mx-auto space-y-8 pb-20">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-l-8 border-hvu-red pl-6 py-2">
        <div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">TRA CỨU KẾT QUẢ</h1>
            <p class="text-gray-500 mt-2 font-medium">Kết quả xử lý dựa trên hồ sơ của bạn cung cấp và hồ sơ thí sinh đăng ký.</p>
        </div>
        <?php if ($sessionId): ?>
            <a href="<?= url('/application/results') ?>" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách đợt
            </a>
        <?php else: ?>
            <a href="<?= url('/application/index') ?>" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại trang chủ
            </a>
        <?php endif; ?>
        </div>
    </div>

    <?php if (!$sessionId): ?>
        <!-- Màn hình 1: Danh sách các đợt đã đăng ký -->
        <?php if (empty($candidateSessions)): ?>
             <!-- No data view -->
             <div class="text-center py-12 bg-white rounded-3xl border border-gray-100 shadow-xl mt-8">
                <div class="text-gray-300 text-6xl mb-4"><i class="fas fa-file-circle-question"></i></div>
                <h3 class="text-xl font-bold text-gray-800">Chưa có dữ liệu xét tuyển</h3>
                <p class="text-gray-500 mt-2">Bạn chưa tham gia đợt xét tuyển nào.</p>
             </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                <?php foreach ($candidateSessions as $s): ?>
                    <?php 
                    $isPublished = ($s['is_published_results'] === true || $s['is_published_results'] === 1 || $s['is_published_results'] === '1' || $s['is_published_results'] === 't');
                    ?>
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 p-6 flex flex-col h-full group relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-full blur-2xl -mr-10 -mt-10 group-hover:bg-blue-100 transition-colors"></div>
                        <div class="relative z-10 flex-1">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center shadow-lg shadow-blue-200">
                                    <i class="fas fa-layer-group text-xl"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-black text-blue-600 uppercase tracking-widest">Đợt tuyển sinh</span>
                                    <h3 class="text-lg font-bold text-gray-900 leading-tight"><?= htmlspecialchars($s['ten_dot'] ?: 'Đợt ID ' . $s['id']) ?></h3>
                                </div>
                            </div>
                            
                            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                                <?php if ($isPublished): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-50 text-green-700 text-sm font-bold border border-green-100">
                                        <i class="fas fa-check-circle"></i> Đã có kết quả
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-yellow-50 text-yellow-700 text-sm font-bold border border-yellow-100">
                                        <i class="fas fa-spinner fa-spin"></i> Đang xử lý
                                    </span>
                                <?php endif; ?>
                                
                                <a href="<?= url('/application/results?session_id=' . $s['id']) ?>" class="inline-flex items-center justify-center px-5 py-2 rounded-xl bg-gray-900 text-white text-sm font-bold hover:bg-gray-800 transition-colors shadow-md">
                                    Xem chi tiết <i class="fas fa-arrow-right ml-2 text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Màn hình 2: Chi tiết kết quả -->
        <div class="bg-gray-50 rounded-xl border border-gray-100 p-4 mt-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <i class="fas fa-bookmark text-gray-400"></i>
                <span class="text-sm font-medium text-gray-600">Đang xem kết quả của:</span>
                <span class="text-sm font-black text-gray-800"><?= htmlspecialchars($currentSession['ten_dot'] ?? 'Đợt tuyển sinh') ?></span>
            </div>
        </div>

    <?php if ($admissionRecord && !empty($renderedAdmissionLetter)): ?>
    <!-- Section: Thư trúng tuyển -->
    <div id="thu-trung-tuyen-chinh-thuc" class="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden mt-6 scroll-mt-6">
        <!-- Header of the Paper Letter -->
        <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse"></span>
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">Thông báo xử lý dữ liệu ghi danh</h3>
            </div>
            <div>
                <span class="text-xs text-gray-500 font-bold bg-white px-3 py-1 rounded-lg border border-gray-100">
                    Mã số: <?= htmlspecialchars($admissionRecord['sbd'] ?: $admissionRecord['so_cccd']) ?>
                </span>
            </div>
        </div>
        
        <!-- Body of the Paper Letter (rendered template) -->
        <div class="p-4 md:p-8 bg-gray-50/20">
            <div class="max-w-4xl mx-auto bg-white p-4 md:p-10 rounded-2xl shadow-sm border border-gray-100/80 leading-relaxed text-gray-700 overflow-x-auto">
                <div class="min-w-[600px] sm:min-w-0">
                    <?= $renderedAdmissionLetter ?>
                </div>
            </div>
        </div>
        
        <!-- Footer notes -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400 font-medium">
                Hội đồng Tuyển sinh Trường Đại học Hùng Vương - Tuyển sinh 2026.
            </p>
        </div>
    </div>
    <?php elseif (!empty($enableResults)): ?>
    <!-- Section: Thông báo chưa có kết quả trúng tuyển -->
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-3xl shadow-sm">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-bullhorn text-yellow-500 text-xl mt-1"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-bold text-yellow-800 font-black">Thông báo từ Hội đồng Tuyển sinh</h3>
                <p class="mt-2 text-yellow-700 font-medium">
                    Hiện tại, tài khoản của bạn chưa có thông tin trúng tuyển hoặc kết quả xử lý dữ liệu. 
                    Vui lòng quay lại sau khi có thông báo mới nhất từ nhà trường.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Talent Test Results -->
    <?php if (!empty($talentResults)): ?>
        <div class="space-y-4">
            <h2 class="text-xl font-black text-gray-800 flex items-center">
                <i class="fas fa-star-of-life mr-3 text-blue-500"></i> KẾT QUẢ THI NĂNG KHIẾU
            </h2>
            <?php foreach ($talentResults as $talent): ?>
                <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-3xl p-8 text-white shadow-xl shadow-blue-200 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mr-32 -mt-32 group-hover:bg-white/20 transition-colors"></div>
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-widest opacity-80 mb-2"><?= htmlspecialchars($talent['session_name']) ?></div>
                            <h3 class="text-2xl font-black mb-4"><?= htmlspecialchars($talent['subject_name']) ?></h3>
                            <div class="grid grid-cols-2 gap-8">
                                <div>
                                    <div class="text-[10px] font-bold opacity-60 uppercase tracking-wider">Số báo danh</div>
                                    <div class="text-lg font-black font-mono"><?= htmlspecialchars($talent['exam_number']) ?></div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold opacity-60 uppercase tracking-wider">Phòng thi</div>
                                    <div class="text-lg font-bold"><?= htmlspecialchars($talent['room_name'] ?: 'Chưa phân') ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 text-center min-w-[150px]">
                            <div class="text-[10px] font-bold opacity-60 uppercase tracking-wider mb-1">Điểm số</div>
                            <div class="text-5xl font-black"><?= $talent['score'] !== null ? number_format($talent['score'], 1) : '--' ?></div>
                            <?php if ($talent['note']): ?>
                                <div class="mt-4 text-[10px] font-medium italic opacity-80 border-t border-white/20 pt-2">
                                    <?= htmlspecialchars($talent['note']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($enableResults)): ?>
        <!-- System Closed Message -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-r-xl shadow-sm">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-bullhorn text-yellow-500 text-xl mt-1"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-bold text-yellow-800">Thông báo từ Hội đồng Tuyển sinh</h3>
                    <p class="mt-2 text-yellow-700 font-medium">
                        Hiện tại, hệ thống chưa công bố kết quả cho đợt này. 
                        Vui lòng quay lại sau khi có thông báo chính thức từ nhà trường.
                    </p>
                    <div class="mt-4">
                        <a href="<?= url('/') ?>" class="text-sm font-bold text-yellow-800 hover:underline">
                            <i class="fas fa-home mr-1"></i> Về trang chủ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
