<?php 
$title = 'Kết quả xét tuyển';
include __DIR__ . '/../layouts/header.php'; 
?>

<div class="max-w-5xl mx-auto space-y-8 pb-20">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-l-8 border-hvu-red pl-6 py-2">
        <div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">TRA CỨU KẾT QUẢ</h1>
            <p class="text-gray-500 mt-2 font-medium">Bảng điểm xét tuyển dự kiến dựa trên hồ sơ của bạn.</p>
        </div>
        <div>
            <a href="<?= url('/application/index') ?>" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Results List -->
    <div class="space-y-6">
        <?php if (empty($enableResults)): ?>
            <!-- System Closed Message -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-r-xl shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-bullhorn text-yellow-500 text-xl mt-1"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-yellow-800">Thông báo từ Hội đồng Tuyển sinh</h3>
                        <p class="mt-2 text-yellow-700">
                            Hiện tại, hệ thống tra cứu kết quả xét tuyển <strong>chưa mở</strong> hoặc <strong>chưa có kết quả chính thức</strong>. 
                            Vui lòng quay lại sau khi có thông báo mới nhất từ nhà trường.
                        </p>
                        <div class="mt-4">
                            <a href="<?= url('/') ?>" class="text-sm font-bold text-yellow-800 hover:underline">
                                <i class="fas fa-home mr-1"></i> Về trang chủ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif (empty($results)): ?>
             <!-- No data view -->
             <div class="text-center py-12 bg-white rounded-3xl border border-gray-100 shadow-xl">
                <div class="text-gray-300 text-6xl mb-4"><i class="fas fa-file-circle-question"></i></div>
                <h3 class="text-xl font-bold text-gray-800">Chưa có dữ liệu xét tuyển</h3>
                <p class="text-gray-500 mt-2">Bạn chưa đăng ký nguyện vọng nào.</p>
             </div>
        <?php else: ?>
            <?php foreach ($results as $index => $item): ?>
                <?php 
                $score = $item['score_data']; 
                $major = $item['major'];
                $choice = $item['choice']; // Has 'trung_tuyen'
                $status = $item['status_hint'] ?? '';
                $isAdmitted = $item['is_admitted'] ?? false;
                
                // Styling based on Official Result
                if ($isAdmitted) {
                    $cardClass = 'border-green-200 bg-green-50/30 ring-4 ring-green-100';
                    $statusClass = 'text-green-700 bg-green-100 border-green-200';
                    $iconClass = 'fa-check-circle';
                    $scoreClass = 'text-green-600';
                } else {
                    $cardClass = 'border-gray-200 bg-white opacity-90 grayscale-[0.3]';
                    $statusClass = 'text-gray-600 bg-gray-100 border-gray-200';
                    $iconClass = 'fa-clock'; // Or times-circle if failed
                    $scoreClass = 'text-gray-400';
                }
                ?>
                <div class="rounded-3xl border p-6 md:p-8 shadow-xl shadow-gray-100/50 <?= $cardClass ?> relative overflow-hidden group hover:scale-[1.005] transition-transform duration-300">
                    
                    <!-- Background Decoration -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gray-50 rounded-full blur-2xl -mr-10 -mt-10 group-hover:bg-red-50 transition-colors"></div>

                    <div class="relative z-10 flex flex-col md:flex-row gap-6 md:gap-8">
                        <!-- Major Info -->
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-3 py-1 rounded-lg bg-white/80 border border-gray-100 text-gray-600 text-xs font-black uppercase tracking-widest">NV<?= $index + 1 ?></span>
                                <span class="text-xs font-bold text-gray-400">Mã ngành: <?= $major['ma_nganh'] ?? '' ?></span>
                            </div>
                            <h3 class="text-2xl font-black text-gray-900 mb-2 leading-tight"><?= $major['ten_nganh'] ?? 'Ngành chưa cập nhật' ?></h3>
                            
                            <div class="flex items-center gap-2 mt-4">
                                <?php if (!empty($major['diem_nam_truoc'])): ?>
                                    <span class="text-xs font-medium text-gray-500">Điểm chuẩn năm trước:</span>
                                    <span class="font-black text-gray-700 text-lg"><?= $major['diem_nam_truoc'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Score Info -->
                        <div class="w-full md:w-1/3 bg-white/60 rounded-2xl p-5 border border-gray-100 backdrop-blur-sm">
                            <div class="text-center mb-4">
                                <span class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Điểm xét tuyển</span>
                                <span class="block text-4xl font-black <?= $isAdmitted ? 'text-green-600' : 'text-gray-600' ?>"><?= $score['total'] > 0 ? $score['total'] : '--' ?></span>
                            </div>
                            
                            <?php if ($score['total'] > 0): ?>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between items-center pb-2 border-b border-gray-200 border-dashed">
                                        <span class="text-gray-500 font-medium">Phương thức</span>
                                        <span class="font-bold text-gray-800"><?= $score['method_code'] == '200' ? 'Học bạ' : 'Điểm thi THPT' ?></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500 font-medium">Tổ hợp</span>
                                        <span class="font-bold text-gray-800"><?= $score['combination'] ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-sm text-gray-500 italic">Chưa đủ dữ liệu tính điểm.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Status Footer -->
                    <div class="mt-6 pt-4 border-t border-gray-100/50 flex justify-between items-center relative z-10">
                        <div>
                            <span class="text-xs text-gray-400 font-medium"><i class="fas fa-sync-alt mr-1"></i> Cập nhật: <?= date('d/m/Y') ?></span>
                        </div>
                        <?php if ($status): ?>
                            <div class="px-5 py-2 rounded-xl border font-bold text-sm <?= $statusClass ?> flex items-center shadow-sm uppercase tracking-wide">
                                <i class="fas <?= $iconClass ?> mr-2"></i>
                                <?= $status ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Disclaimer -->
    <div class="bg-blue-50/50 border border-blue-100 rounded-3xl p-6 text-center">
        <p class="text-sm text-blue-800 font-medium">
            <i class="fas fa-info-circle mr-1"></i> 
            Kết quả trên đây là tính toán dự kiến dựa trên dữ liệu bạn đã nhập và điểm chuẩn năm trước. Kết quả chính thức sẽ được công bố theo kế hoạch tuyển sinh của nhà trường.
        </p>
    </div>

</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
